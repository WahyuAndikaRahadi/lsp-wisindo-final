<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Concerns\AuthorizesAssessor;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAk02Item;
use App\Models\Schedule;
use App\Services\AkWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class FinalizationController extends Controller
{
    use AuthorizesAssessor;

    public function __construct(
        protected AkWorkflowService $workflowService
    ) {}

    /**
     * Schedule-level FR.AK.02 asesi list.
     */
    public function scheduleAk02(Schedule $schedule): View|RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        if (! $schedule->isStepUnlocked('ak02')) {
            return redirect()->route('asesor.schedule.ruang-uji', $schedule->id)
                ->with('warning', 'Selesaikan tahapan pra-asesmen (FR.AK.01 & FR.AK.07) terlebih dahulu sebelum mengakses FR.AK.02.');
        }

        $schedule->load([
            'scheme.competencyUnits',
            'assessments.asesi.profile',
            'assessments.ak02Items',
            'assessors',
        ]);

        $user = auth()->user();
        $isSingleAssessor = $schedule->assessors->count() === 1 && $schedule->assessors->first()?->id === $user->id;

        $myAssessments = $schedule->assessorAssessments();

        $asesiList = $myAssessments->map(function ($assessment) use ($user, $isSingleAssessor) {
            $isMyCandidate = (int) $assessment->assessor_id === (int) $user->id || (empty($assessment->assessor_id) && $isSingleAssessor);

            return (object) [
                'assessment' => $assessment,
                'asesi' => $assessment->asesi,
                'is_completed' => ! empty($assessment->final_result),
                'status_label' => match ($assessment->final_result) {
                    'KOMPETEN', 'K' => 'Kompeten (K)',
                    'BELUM_KOMPETEN', 'BK' => 'Belum Kompeten (BK)',
                    default => 'Belum Direkam',
                },
                'status_class' => match ($assessment->final_result) {
                    'KOMPETEN', 'K' => 'bg-emerald-50 text-emerald-700 border-emerald-300',
                    'BELUM_KOMPETEN', 'BK' => 'bg-rose-50 text-rose-700 border-rose-300',
                    default => 'bg-slate-100 text-slate-700 border-slate-300',
                },
                'inspect_url' => route('asesor.assessment.ak02', $assessment->id),
                'is_my_candidate' => $isMyCandidate,
            ];
        })->values();

        // Preset collective evidence methods from existing saved items or defaults
        $firstSavedItem = $myAssessments->flatMap->ak02Items->first();
        $savedMethods = $firstSavedItem?->methods_json;

        $collectiveMethods = [
            'observasi_demonstrasi' => $savedMethods ? ! empty($savedMethods['observasi_demonstrasi']) : true,
            'tulis' => $savedMethods ? ! empty($savedMethods['tulis']) : true,
            'lisan' => $savedMethods ? ! empty($savedMethods['lisan']) : true,
            'portofolio' => $savedMethods ? ! empty($savedMethods['portofolio']) : false,
        ];

        $meta = [
            'code' => 'FR.AK.02',
            'title' => 'Rekaman Asesmen Kompetensi',
            'desc' => 'Rekapitulasi bukti dan penetapan rekomendasi hasil asesmen kompetensi untuk setiap asesi.',
            'badge' => 'Rekaman',
            'badge_color' => 'bg-teal-50 text-teal-700 border-teal-200',
            'route_code' => 'ak02',
        ];

        $codeUpper = 'AK02';
        $codeLower = 'ak02';

        return view('asesor.ruang-uji.schedule-ak02', compact(
            'schedule',
            'asesiList',
            'collectiveMethods',
            'meta',
            'codeUpper',
            'codeLower'
        ));
    }

    /**
     * Bulk update FR.AK.02 for multiple candidates in a schedule.
     */
    public function bulkUpdateScheduleAk02(Schedule $schedule, Request $request): RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        if ($schedule->isClosedForAssessor()) {
            return back()->with('error', 'Jadwal asesmen sudah ditutup dan dikunci.');
        }

        $results = $request->input('results', []);
        if (empty($results)) {
            return back()->with('error', 'Tidak ada data rekaman asesi yang dikirim.');
        }

        $user = auth()->user();
        $isSingleAssessor = $schedule->assessors->count() === 1 && $schedule->assessors->first()?->id === $user->id;

        $myAssessments = $schedule->assessorAssessmentsQuery()
            ->with(['asesi'])
            ->get()
            ->keyBy('id');

        $units = $schedule->scheme->competencyUnits;
        $now = now();
        $savedCount = 0;

        $hasMethodsInput = $request->has('methods');
        $methods = [
            'observasi_demonstrasi' => $hasMethodsInput ? $request->boolean('methods.observasi_demonstrasi') : true,
            'portofolio' => $hasMethodsInput ? $request->boolean('methods.portofolio') : false,
            'pihak_ketiga' => false,
            'lisan' => $hasMethodsInput ? $request->boolean('methods.lisan') : true,
            'tulis' => $hasMethodsInput ? $request->boolean('methods.tulis') : true,
            'proyek' => false,
            'lainnya' => false,
        ];

        $assessorSignature = $user->profile?->signature_data;

        DB::transaction(function () use ($results, $myAssessments, $units, $methods, $assessorSignature, $now, &$savedCount) {
            foreach ($results as $assessmentId => $data) {
                if (! $myAssessments->has($assessmentId)) {
                    continue;
                }

                $assessment = $myAssessments->get($assessmentId);

                // Only process candidates whose APL has been approved
                if ($assessment->apl_status !== 'APPROVED') {
                    continue;
                }

                $finalResultRaw = strtoupper((string) ($data['final_result'] ?? 'KOMPETEN'));
                $finalResult = in_array($finalResultRaw, ['K', 'KOMPETEN'], true) ? 'KOMPETEN' : 'BELUM_KOMPETEN';
                $isComp = ($finalResult === 'KOMPETEN');

                $notes = $data['final_notes'] ?? ($isComp ? 'Asesi menunjukkan unjuk kerja optimal sesuai standar skema' : 'Terdapat kriteria unjuk kerja yang belum terpenuhi');
                $followUp = $data['follow_up_action'] ?? ($isComp ? 'Diusulkan penerbitan sertifikat kompetensi BNSP' : 'Perlu asesmen ulang pada unit yang belum kompeten');

                // Upsert AK02 items for all units
                foreach ($units as $unit) {
                    AssessmentAk02Item::updateOrCreate(
                        [
                            'assessment_id' => $assessment->id,
                            'competency_unit_id' => $unit->id,
                        ],
                        [
                            'methods_json' => $methods,
                            'is_competent' => $isComp,
                        ]
                    );
                }

                $assessment->update([
                    'final_result' => $finalResult,
                    'final_notes' => $notes,
                    'follow_up_action' => $followUp,
                    'status' => 'COMPLETED',
                    'completed_at' => $assessment->completed_at ?? $now,
                    'assessor_id' => auth()->id(),
                    'assessor_signature' => $assessorSignature ?? $assessment->assessor_signature,
                    'assessor_signed_at' => $assessorSignature ? $now : $assessment->assessor_signed_at,
                    'last_active_step' => 'ruang-uji',
                ]);

                $savedCount++;
            }
        });

        return redirect()->route('asesor.schedule.ak02', $schedule->id)
            ->with('success', "Sebanyak {$savedCount} rekaman FR.AK.02 asesi berhasil disimpan.");
    }

    /**
     * Complete individual candidate assessment & auto-generate AK.02.
     */
    public function completeAssessment(Assessment $assessment): RedirectResponse
    {
        $this->authorizeAssessorAssessment($assessment);

        if ($assessment->schedule && $assessment->schedule->is_closed) {
            return back()->with('error', 'Jadwal asesmen sudah resmi ditutup.');
        }

        if ($assessment->apl_status !== 'APPROVED') {
            return back()->with('error', 'Asesmen belum dapat diselesaikan karena APL belum disetujui.');
        }

        DB::transaction(function () use ($assessment) {
            $this->workflowService->syncAk02($assessment);

            $assessment->update([
                'status' => 'COMPLETED',
                'completed_at' => $assessment->completed_at ?? now(),
                'assessor_id' => auth()->id(),
                'assessor_signed_at' => $assessment->assessor_signed_at ?? now(),
            ]);
        });

        return back()->with('success', "Asesmen asesi {$assessment->asesi->full_name} berhasil diselesaikan dan FR.AK.02 telah direkapitulasi.");
    }

    /**
     * View FR.AK.02 Rekaman Asesmen for review and editing.
     */
    public function showAk02(Assessment $assessment): View
    {
        $this->authorizeAssessorAssessment($assessment);

        $assessment->load([
            'schedule.scheme.competencyUnits',
            'asesi.profile',
            'assessor',
            'ak02Items.competencyUnit',
            'iaResponses',
        ]);

        if ($assessment->ak02Items->isEmpty()) {
            $this->workflowService->syncAk02($assessment);
            $assessment->refresh();
        }

        $units = $assessment->schedule->scheme->competencyUnits;
        $ak02Items = $assessment->ak02Items->keyBy('competency_unit_id');

        return view('asesor.ak02', compact('assessment', 'units', 'ak02Items'));
    }

    /**
     * Update FR.AK.02 Rekaman Asesmen.
     */
    public function updateAk02(Assessment $assessment, Request $request): RedirectResponse
    {
        $this->authorizeAssessorAssessment($assessment);

        if ($assessment->schedule && $assessment->schedule->isClosedForAssessor()) {
            return back()->with('error', 'Jadwal asesmen sudah ditutup dan dikunci.');
        }

        if ($assessment->apl_status !== 'APPROVED') {
            return back()->with('error', 'FR.AK.02 hanya dapat direkam untuk asesi yang telah disetujui APL-nya.');
        }

        $unitsInput = $request->input('units', []);

        $validated = $request->validate([
            'final_result' => 'required|in:K,BK,KOMPETEN,BELUM_KOMPETEN',
            'final_notes' => 'nullable|string|max:1000',
            'follow_up_action' => 'nullable|string|max:1000',
        ], [
            'final_result.required' => 'Rekomendasi hasil asesmen akhir (Kompeten atau Belum Kompeten) wajib dipilih secara eksplisit oleh asesor.',
        ]);

        $finalResult = in_array($validated['final_result'], ['K', 'KOMPETEN'], true) ? 'KOMPETEN' : 'BELUM_KOMPETEN';

        Log::info('Asesor ID '.auth()->id()." memperbarui FR.AK.02 asesmen #{$assessment->id} dengan hasil {$finalResult}");

        $now = now();

        DB::transaction(function () use ($assessment, $unitsInput, $validated, $finalResult, $request, $now) {
            foreach ($unitsInput as $unitId => $data) {
                $methods = [
                    'observasi_demonstrasi' => ! empty($data['observasi_demonstrasi']),
                    'portofolio' => ! empty($data['portofolio']),
                    'pihak_ketiga' => ! empty($data['pihak_ketiga']),
                    'lisan' => ! empty($data['lisan']),
                    'tulis' => ! empty($data['tulis']),
                    'proyek' => ! empty($data['proyek']),
                    'lainnya' => ! empty($data['lainnya']),
                ];

                AssessmentAk02Item::updateOrCreate(
                    [
                        'assessment_id' => $assessment->id,
                        'competency_unit_id' => $unitId,
                    ],
                    [
                        'methods_json' => $methods,
                        'is_competent' => array_key_exists('is_competent', $data)
                            ? ! empty($data['is_competent'])
                            : ($finalResult === 'KOMPETEN'),
                    ]
                );
            }

            $newAssessorSig = $request->input('assessor_signature') ?? $assessment->assessor_signature;
            if ($assessment->assessor_signature && $newAssessorSig !== $assessment->assessor_signature) {
                Log::info("Signature updated for assessment #{$assessment->id} by assessor #".auth()->id());
            }

            $assessment->update([
                'final_result' => $finalResult,
                'final_result_at' => $assessment->final_result_at ?? $now,
                'final_notes' => $validated['final_notes'] ?? null,
                'follow_up_action' => $validated['follow_up_action'] ?? null,
                'assessor_signature' => $newAssessorSig,
                'assessor_signed_at' => $assessment->assessor_signed_at ?? $now,
                'status' => 'COMPLETED',
                'completed_at' => $assessment->completed_at ?? $now,
            ]);
        });

        return redirect()->route('asesor.schedule.ak02', $assessment->schedule_id)
            ->with('success', 'FR.AK.02 Rekaman Asesmen berhasil diperbarui.');
    }

    /**
     * View FR.AK.05 (Laporan Asesmen) & FR.AK.06 (Tinjauan Proses).
     */
    public function showReports(Schedule $schedule): View|RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        if (! $schedule->isStepUnlocked('reports')) {
            return redirect()->route('asesor.schedule.ak02', $schedule->id)
                ->with('warning', 'Rekam seluruh rekomendasi hasil akhir asesmen (FR.AK.02) terlebih dahulu sebelum membuka laporan penutupan jadwal.');
        }

        $schedule->load('scheme', 'assessors', 'assessments.asesi.profile');
        $myAssessments = $schedule->assessorAssessments();

        return view('asesor.reports-ak05-ak06', compact('schedule', 'myAssessments'));
    }

    /**
     * Submit FR.AK.05 & FR.AK.06 and optionally close schedule.
     */
    public function submitReports(Schedule $schedule, Request $request): RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        $validated = $request->validate([
            'ak05_aspek_positif' => 'required|string|min:3',
            'ak05_aspek_negatif' => 'required|string|min:3',
            'ak05_rekomendasi' => 'required|string|min:3',
            'ak05_has_penolakan' => 'nullable|in:0,1',
            'ak05_penolakan_asesi_ids' => 'nullable|array',
            'ak05_penolakan_asesi_ids.*' => 'integer|exists:t_assessments,id',
            'ak05_penolakan_catatan' => 'nullable|string|max:1000',
            'ak06_template_type' => 'required_without:ak06_valid|in:langsung,tidak_langsung',
            'ak06_catatan_peninjau' => 'nullable|string|max:1000',
            'ak06_valid' => 'nullable|in:YA,TIDAK',
            'ak06_valid_notes' => 'nullable|string',
            'ak06_reliabel' => 'nullable|in:YA,TIDAK',
            'ak06_reliabel_notes' => 'nullable|string',
            'ak06_fleksibel' => 'nullable|in:YA,TIDAK',
            'ak06_fleksibel_notes' => 'nullable|string',
            'ak06_adil' => 'nullable|in:YA,TIDAK',
            'ak06_adil_notes' => 'nullable|string',
            'confirm_draft_review' => 'accepted',
        ], [
            'ak05_aspek_positif.required' => 'Aspek positif laporan asesmen (FR.AK.05) wajib diisi.',
            'ak05_aspek_negatif.required' => 'Aspek negatif laporan asesmen (FR.AK.05) wajib diisi.',
            'ak05_rekomendasi.required' => 'Rekomendasi peningkatan mutu (FR.AK.05) wajib diisi.',
            'ak06_template_type.required' => 'Anda wajib memilih salah satu tipe FR.AK.06 (Langsung atau Tidak Langsung).',
            'ak06_template_type.required_without' => 'Anda wajib memilih salah satu tipe FR.AK.06 (Langsung atau Tidak Langsung).',
            'ak06_template_type.in' => 'Pilihan tipe FR.AK.06 tidak valid. Pilih Langsung atau Tidak Langsung.',
            'confirm_draft_review.accepted' => 'Anda wajib mencentang konfirmasi peninjauan draf laporan sebelum menyimpan dan menutup jadwal.',
        ]);

        $hasPenolakan = (string) $request->input('ak05_has_penolakan') === '1';
        $penolakanAssessmentIds = $hasPenolakan ? array_map('intval', (array) $request->input('ak05_penolakan_asesi_ids', [])) : [];
        $penolakanCatatan = $hasPenolakan ? $request->input('ak05_penolakan_catatan') : null;

        $rejectionNotes = null;
        if ($hasPenolakan) {
            $rejectionNotes = ! empty($penolakanCatatan)
                ? $penolakanCatatan
                : 'Terdapat penolakan dari asesi atas keputusan asesmen.';
        } else {
            $rejectionNotes = 'Tidak ada penolakan dari asesi atas keputusan asesmen.';
        }

        $ak05 = [
            'aspek_positif' => $validated['ak05_aspek_positif'],
            'aspek_negatif' => $validated['ak05_aspek_negatif'],
            'rekomendasi' => $validated['ak05_rekomendasi'],
            'has_penolakan' => $hasPenolakan,
            'penolakan_assessment_ids' => $penolakanAssessmentIds,
            'penolakan_catatan' => $penolakanCatatan,
            'rejection_notes' => $rejectionNotes,
            'negative_aspects' => $validated['ak05_aspek_negatif'],
            'positive_aspects' => $validated['ak05_aspek_positif'],
            'improvement_suggestions' => $validated['ak05_rekomendasi'],
        ];

        $templateType = $request->input('ak06_template_type', 'langsung');
        if (! in_array($templateType, ['langsung', 'tidak_langsung'], true)) {
            $templateType = 'langsung';
        }
        $templateName = $templateType === 'langsung'
            ? 'FR.AK.06 Meninjau Proses Asesmen (Langsung)'
            : 'FR.AK.06 Meninjau Proses Asesmen (Tidak Langsung)';

        $defaultCatatan = "Proses asesmen telah ditinjau menggunakan instrumen {$templateName} dan dinyatakan memenuhi seluruh prinsip asesmen dan dimensi kompetensi BNSP.";
        $catatanPeninjau = ! empty($validated['ak06_catatan_peninjau'] ?? null)
            ? $validated['ak06_catatan_peninjau']
            : ($request->input('ak06_catatan_peninjau') ?: $defaultCatatan);

        $ak06 = [
            'template_type' => $templateType,
            'template_name' => $templateName,
            'source' => 'static',
            'catatan_peninjau' => $catatanPeninjau,
        ];

        if ($schedule->isClosedForAssessor()) {
            return redirect()->route('asesor.jadwal.index')
                ->with('info', 'Jadwal asesmen ini sudah resmi ditutup sebelumnya.');
        }

        $myPoolReadiness = $this->workflowService->isAssessorPoolReadyForReports($schedule, auth()->user());
        $scheduleReadiness = $this->workflowService->isScheduleReadyForClosure($schedule);

        DB::transaction(function () use ($schedule, $ak05, $ak06, $scheduleReadiness) {
            $this->workflowService->saveScheduleReports($schedule, $ak05, $ak06, $scheduleReadiness['is_ready']);
        });

        if (! $myPoolReadiness['is_ready']) {
            return redirect()->route('asesor.schedule.ruang-uji', $schedule->id)
                ->with('warning', "Laporan tersimpan. Jadwal belum dapat ditutup: Masih ada {$myPoolReadiness['uncompleted_count']} asesi binaan Anda yang belum selesai.");
        }

        if (! $scheduleReadiness['is_ready']) {
            return redirect()->route('asesor.jadwal.index')
                ->with('success', "FR.AK.05 & FR.AK.06 untuk asesi binaan Anda berhasil disimpan. Seluruh asesi binaan Anda ({$myPoolReadiness['my_total']} orang) telah selesai dinilai. Jadwal tetap aktif untuk asesor lain hingga seluruh asesi selesai.");
        }

        return redirect()->route('asesor.jadwal.index')
            ->with('success', 'FR.AK.05 Laporan Asesmen dan FR.AK.06 Tinjauan Proses berhasil disimpan. Seluruh asesi telah selesai dan jadwal resmi ditutup.');
    }

    /**
     * Mark candidate as absent (TIDAK_HADIR).
     */
    public function markAbsent(Assessment $assessment, Request $request): RedirectResponse
    {
        $this->authorizeAssessorAssessment($assessment);

        if ($assessment->schedule && $assessment->schedule->isClosedForAssessor()) {
            return back()->with('error', 'Jadwal asesmen sudah ditutup dan dikunci.');
        }

        $notes = $request->input('notes') ?? $request->input('final_notes');
        $this->workflowService->markCandidateAbsent($assessment, $notes, auth()->id());

        return back()->with('success', "Asesi {$assessment->asesi->full_name} berhasil ditandai TIDAK HADIR.");
    }

    /**
     * Finalize & close schedule.
     */
    public function finalize(Schedule $schedule): RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        if ($schedule->isClosedForAssessor()) {
            return redirect()->route('asesor.jadwal.index')
                ->with('info', 'Jadwal asesmen ini sudah resmi ditutup sebelumnya.');
        }

        $readiness = $this->workflowService->isScheduleReadyForClosure($schedule);

        if (! $readiness['is_ready']) {
            return redirect()->route('asesor.schedule.ruang-uji', $schedule->id)
                ->with('error', "Jadwal belum dapat ditutup. Masih ada {$readiness['uncompleted_count']} asesi yang belum diselesaikan asesmennya.");
        }

        if (! $readiness['has_ak05'] || ! $readiness['has_ak06']) {
            return redirect()->route('asesor.schedule.reports', $schedule->id)
                ->with('info', 'Harap melengkapi FR.AK.05 (Laporan Asesmen) dan FR.AK.06 (Tinjauan Proses Asesmen) sebelum menutup jadwal asesmen.');
        }

        DB::transaction(function () use ($schedule) {
            $schedule->update([
                'is_closed' => true,
                'closed_at' => now(),
                'status' => 'FINISHED',
            ]);
        });

        return redirect()->route('asesor.jadwal.index')
            ->with('success', 'Jadwal asesmen telah resmi ditutup dan dikunci.');
    }
}
