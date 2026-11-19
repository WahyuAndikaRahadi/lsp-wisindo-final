<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Concerns\AuthorizesAssessor;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Setting;
use App\Rules\ValidSignature;
use App\Services\SchemeInstrumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class InstrumentController extends Controller
{
    use AuthorizesAssessor;

    /**
     * Metadata definitions for standard BNSP instruments.
     */
    protected array $instrumentsMeta = [
        'APL' => [
            'code' => 'FR.APL.01/02',
            'title' => 'Verifikasi Permohonan & Asesmen Mandiri',
            'desc' => 'Verifikasi kelengkapan berkas pendaftaran (APL.01) dan asesmen mandiri (APL.02) calon asesi.',
            'badge' => 'Verifikasi APL',
            'badge_color' => 'bg-amber-50 text-amber-700 border-amber-200',
            'route_code' => 'apl',
        ],
        'AK01' => [
            'code' => 'FR.AK.01',
            'title' => 'Persetujuan Asesmen dan Kerahasiaan',
            'desc' => 'Persetujuan pelaksanaan asesmen, kerahasiaan materi uji, dan hak serta kewajiban asesi.',
            'badge' => 'Persetujuan',
            'badge_color' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'route_code' => 'ak01',
        ],
        'IA01' => [
            'code' => 'FR.IA.01',
            'title' => 'Ceklis Observasi Aktivitas di Tempat Kerja',
            'desc' => 'Daftar periksa observasi langsung terhadap KUK dan elemen kompetensi pada unit unjuk kerja.',
            'badge' => 'Observasi Praktik',
            'badge_color' => 'bg-blue-50 text-blue-700 border-blue-200',
            'route_code' => 'ia01',
        ],
        'IA01B' => [
            'code' => 'FR.IA.01B',
            'title' => 'Lembar Pemeriksaan Teknis (Report Sheet)',
            'desc' => 'Pencatatan dan verifikasi hasil pengukuran parameter fisik komponen serta lembar kerja asesi.',
            'badge' => 'Report Sheet',
            'badge_color' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
            'route_code' => 'ia01b',
        ],
        'IA02' => [
            'code' => 'FR.IA.02',
            'title' => 'Tugas Praktik Demonstrasi (TPD)',
            'desc' => 'Penilaian tugas praktik unjuk kerja berdasarkan skenario dan instruksi demonstrasi standar di TUK.',
            'badge' => 'Demonstrasi',
            'badge_color' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'route_code' => 'ia02',
        ],
        'IA03' => [
            'code' => 'FR.IA.03',
            'title' => 'Pertanyaan Mendukung Observasi (PMO / Lisan)',
            'desc' => 'Daftar pertanyaan lisan terstruktur untuk mengonfirmasi pemahaman teknis dan dasar teori asesi.',
            'badge' => 'Tanya Jawab',
            'badge_color' => 'bg-amber-50 text-amber-700 border-amber-200',
            'route_code' => 'ia03',
        ],
        'IA05' => [
            'code' => 'FR.IA.05',
            'title' => 'Pertanyaan Tertulis Pilihan Ganda (CBT)',
            'desc' => 'Hasil ujian tertulis berbasis komputer (CBT) dengan auto-grading dan verifikasi nilai akhir.',
            'badge' => 'Ujian CBT',
            'badge_color' => 'bg-purple-50 text-purple-700 border-purple-200',
            'route_code' => 'ia05',
        ],
    ];

    /**
     * Ruang Uji Jadwal: Menampilkan seluruh instrumen terpilih untuk jadwal uji.
     */
    public function scheduleRuangUji(Schedule $schedule): View|RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        if (Gate::denies('enter-schedule', $schedule)) {
            return redirect()->route('asesor.jadwal.index')
                ->with('warning', 'Silakan simpan tanda tangan digital pada profil Anda terlebih dahulu sebelum masuk ke ruang uji jadwal.');
        }

        if (! $schedule->isStepUnlocked('ruang-uji')) {
            if (! $schedule->isStepUnlocked('ak07')) {
                return redirect()->route('asesor.schedule.ak01', $schedule->id)
                    ->with('warning', 'Lengkapi persetujuan FR.AK.01 terlebih dahulu sebelum masuk ke Ruang Uji.');
            }

            return redirect()->route('asesor.schedule.ak07', $schedule->id)
                ->with('warning', 'Lengkapi formulir penyesuaian FR.AK.07 terlebih dahulu sebelum masuk ke Ruang Uji.');
        }

        $schedule->load([
            'scheme.competencyUnits.elements.kuks',
            'assessments.asesi.profile',
            'assessments.iaResponses',
            'assessors',
        ]);

        $myAssessments = $schedule->assessorAssessments();

        // Filter ONLY FR.IA instruments. Exclude APL and AK01!
        $allAssignedIaCodes = $myAssessments
            ->flatMap(fn ($ass) => $ass->getAssignedInstruments())
            ->filter(fn ($c) => str_starts_with(strtoupper($c), 'IA'))
            ->unique()
            ->values()
            ->all();

        if (empty($allAssignedIaCodes)) {
            $allAssignedIaCodes = array_values(array_filter(
                (array) ($schedule->selected_instruments ?? ['IA01', 'IA01B', 'IA02', 'IA03', 'IA05']),
                fn ($c) => str_starts_with(strtoupper($c), 'IA')
            ));
        }

        $allAssignedIaCodes = array_values(array_filter(
            $allAssignedIaCodes,
            fn ($c) => ! in_array(strtoupper($c), ['APL', 'AK01'], true)
        ));

        sort($allAssignedIaCodes);

        $totalAsesi = $myAssessments->count();

        $instruments = [];
        foreach ($allAssignedIaCodes as $code) {
            $c = strtoupper($code);
            $meta = $this->instrumentsMeta[$c] ?? [
                'code' => 'FR.'.$c,
                'title' => 'Instrumen '.$c,
                'desc' => 'Instrumen asesmen kompetensi terstandar.',
                'badge' => $c,
                'badge_color' => 'bg-slate-50 text-slate-700 border-slate-200',
                'route_code' => strtolower($c),
            ];

            // Target asesi specifically assigned to this FR.IA instrument and this assessor
            $targetAssessmentsForInstrument = $myAssessments->filter(fn ($ass) => $ass->hasAssignedInstrument($c));
            $targetCount = $targetAssessmentsForInstrument->count();

            // Hitung asesi yang sudah dinilai/selesai pada instrumen ini
            $completedCount = $targetAssessmentsForInstrument->filter(function ($ass) use ($c) {
                $resp = $ass->iaResponses->firstWhere('instrument_code', $c);
                if (! $resp) {
                    return false;
                }
                if ($c === 'IA05') {
                    return $resp->score !== null || ! empty($resp->asesi_signed_at);
                }
                if ($c === 'IA02') {
                    return ! empty($resp->assessor_signed_at);
                }
                if ($c === 'IA01B') {
                    return ! empty($resp->assessor_signed_at);
                }

                return ! empty($resp->assessor_signed_at);
            })->count();

            $meta['raw_code'] = $c;
            $meta['completed_count'] = $completedCount;
            $meta['total_asesi'] = $targetCount;
            $meta['percentage'] = $targetCount > 0 ? round(($completedCount / $targetCount) * 100) : 0;

            $instruments[] = $meta;
        }

        return view('asesor.ruang-uji.schedule-hub', compact('schedule', 'instruments', 'totalAsesi'));
    }

    /**
     * Halaman Instrumen Jadwal: Menampilkan daftar asesi pada instrumen terpilih.
     */
    public function scheduleInstrument(Schedule $schedule, string $code, SchemeInstrumentService $instrumentService): View|RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        $codeUpper = strtoupper($code);
        $codeLower = strtolower($code);

        if ($codeUpper === 'AK01') {
            return redirect()->route('asesor.schedule.ak01', $schedule->id);
        }

        $schedule->load([
            'scheme.competencyUnits.elements.kuks',
            'assessments.asesi.profile',
            'assessments.iaResponses',
            'assessors',
        ]);

        $meta = $this->instrumentsMeta[$codeUpper] ?? [
            'code' => 'FR.'.$codeUpper,
            'title' => 'Instrumen '.$codeUpper,
            'desc' => 'Instrumen asesmen kompetensi.',
            'badge' => $codeUpper,
            'badge_color' => 'bg-slate-50 text-slate-700 border-slate-200',
            'route_code' => $codeLower,
        ];

        $myAssessments = $schedule->assessorAssessments();

        $targetAssessments = ($codeUpper === 'APL')
            ? $myAssessments
            : $myAssessments->filter(fn ($a) => $a->hasAssignedInstrument($codeUpper));

        $asesiList = $targetAssessments->map(function ($assessment) use ($codeUpper, $codeLower, $schedule) {
            $response = $assessment->iaResponses->firstWhere('instrument_code', $codeUpper);
            $isCompleted = false;
            $scoreLabel = null;
            $statusLabel = 'Belum Dinilai';
            $statusClass = 'bg-slate-100 text-slate-700 border-slate-300';

            if ($codeUpper === 'APL') {
                $isCompleted = $assessment->apl_status === 'APPROVED';
                $statusLabel = match ($assessment->apl_status ?? 'DRAFT') {
                    'APPROVED' => 'Disetujui',
                    'SUBMITTED' => 'Menunggu Verifikasi',
                    'REVISION_REQUESTED' => 'Perlu Revisi',
                    'REJECTED' => 'Ditolak',
                    default => 'Draft',
                };
                $statusClass = match ($assessment->apl_status ?? 'DRAFT') {
                    'APPROVED' => 'bg-emerald-50 text-emerald-700 border-emerald-300',
                    'SUBMITTED' => 'bg-blue-50 text-blue-700 border-blue-300',
                    'REVISION_REQUESTED' => 'bg-amber-50 text-amber-700 border-amber-300',
                    'REJECTED' => 'bg-rose-50 text-rose-700 border-rose-300',
                    default => 'bg-slate-100 text-slate-700 border-slate-300',
                };
                $inspectUrl = route('asesor.review-apl', $assessment->id);
            } elseif ($codeUpper === 'AK01') {
                $isCompleted = ! empty($assessment->assessor_signature);
                $statusLabel = $isCompleted ? 'Sudah Ditandatangani' : 'Belum Ditandatangani';
                $statusClass = $isCompleted ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-amber-50 text-amber-700 border-amber-300';
                $inspectUrl = route('asesor.schedule.ak01', ['schedule' => $assessment->schedule_id, 'assessment' => $assessment->id]);
            } else {
                if ($response) {
                    if ($codeUpper === 'IA05') {
                        $isCompleted = (bool) ($response->score !== null || ! empty($response->asesi_signed_at));
                        $score = $response->score ?? ($assessment->ia05_score ?? 0);
                        $scoreLabel = "Nilai CBT: {$score}%";
                        $passingGrade = (float) ($assessment->schedule?->scheme?->passing_grade_theory ?? Setting::get('cbt_passing_grade', 75.0));
                        $statusLabel = $score >= $passingGrade ? 'Kompeten (K)' : 'Belum Kompeten (BK)';
                        $statusClass = $score >= $passingGrade ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-rose-50 text-rose-700 border-rose-300';
                    } elseif ($codeUpper === 'IA02') {
                        $isSigned = ! empty($response->assessor_signed_at);
                        $isCompleted = $isSigned;
                        $statusLabel = $isSigned ? 'Sudah Ditandatangani' : 'Belum Ditandatangani';
                        $statusClass = $isSigned ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-amber-50 text-amber-700 border-amber-300';
                    } elseif ($codeUpper === 'IA01B') {
                        $hasUploadedEvidence = ! empty($response->data_json['evidence_image_path']);
                        $isSignedByAssessor = ! empty($response->assessor_signed_at);

                        if ($isSignedByAssessor) {
                            $isCompleted = true;
                            $statusLabel = 'Sudah Dinilai';
                            $statusClass = 'bg-emerald-50 text-emerald-700 border-emerald-300';
                        } elseif ($hasUploadedEvidence) {
                            $isCompleted = false;
                            $statusLabel = 'Menunggu Penilaian Asesor';
                            $statusClass = 'bg-amber-50 text-amber-800 border-amber-300';
                        } else {
                            $isCompleted = false;
                            $statusLabel = 'Belum Diunggah Asesi';
                            $statusClass = 'bg-slate-100 text-slate-600 border-slate-300';
                        }
                    } else {
                        $isSignedByAssessor = ! empty($response->assessor_signed_at);
                        if ($isSignedByAssessor) {
                            $isCompleted = true;
                            $statusLabel = 'Sudah Dinilai';
                            $statusClass = 'bg-emerald-50 text-emerald-700 border-emerald-300';
                        } else {
                            $isCompleted = false;
                            $statusLabel = 'Belum Dinilai';
                            $statusClass = 'bg-slate-100 text-slate-700 border-slate-300';
                        }
                    }
                } else {
                    if ($codeUpper === 'IA02') {
                        $statusLabel = 'Belum Ditandatangani';
                        $statusClass = 'bg-amber-50 text-amber-700 border-amber-300';
                    } elseif ($codeUpper === 'IA01B') {
                        $statusLabel = 'Belum Diunggah Asesi';
                        $statusClass = 'bg-slate-100 text-slate-600 border-slate-300';
                    }
                }
                $inspectUrl = route('asesor.assessment.instrument', ['assessment' => $assessment->id, 'code' => $codeLower]);
            }

            $hasEvidence = ! empty($response?->data_json['evidence_image_path']);

            $user = auth()->user();
            $isSingleAssessor = $schedule->assessors->count() === 1 && $schedule->assessors->first()?->id === $user?->id;
            $isMyCandidate = (int) $assessment->assessor_id === (int) $user?->id || (empty($assessment->assessor_id) && $isSingleAssessor);

            return (object) [
                'assessment' => $assessment,
                'asesi' => $assessment->asesi,
                'is_completed' => $isCompleted,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
                'score_label' => $scoreLabel,
                'inspect_url' => $inspectUrl,
                'has_evidence' => $hasEvidence,
                'is_my_candidate' => $isMyCandidate,
            ];
        })->values();

        $meta['completed_count'] = $asesiList->where('is_completed', true)->count();
        $meta['total_asesi'] = $targetAssessments->count();
        $meta['percentage'] = $meta['total_asesi'] > 0 ? round(($meta['completed_count'] / $meta['total_asesi']) * 100) : 0;

        return view('asesor.ruang-uji.instrument-asesi-list', compact(
            'schedule',
            'codeUpper',
            'codeLower',
            'meta',
            'asesiList'
        ));
    }

    public function ak01Schedule(Schedule $schedule, Request $request)
    {
        $this->authorizeAssessorSchedule($schedule);

        $assessmentId = $request->query('assessment');
        if ($assessmentId) {
            $assessment = Assessment::with('schedule.scheme', 'asesi.profile', 'assessor')->findOrFail($assessmentId);
            $this->authorizeAssessorAssessment($assessment);

            return view('asesor.ak01', compact('schedule', 'assessment'));
        }

        return redirect()->route('asesor.schedule.instrument', ['schedule' => $schedule->id, 'code' => 'ak01']);
    }

    public function ak01Sign(Assessment $assessment, Request $request): RedirectResponse
    {
        $this->authorizeAssessorAssessment($assessment);

        if ($assessment->schedule && $assessment->schedule->isClosedForAssessor()) {
            return back()->with('error', 'Jadwal asesmen sudah ditutup dan dikunci.');
        }

        $request->validate([
            'assessor_signature' => ['nullable', 'string', new ValidSignature],
            'signature_data' => ['nullable', 'string', new ValidSignature],
        ]);

        $signature = $request->input('assessor_signature') ?? $request->input('signature_data');
        $assessment->update([
            'assessor_signature' => $signature,
            'assessor_signed_at' => now(),
            'assessor_id' => auth()->id(),
        ]);

        return redirect()->route('asesor.schedule.instrument', [
            'schedule' => $assessment->schedule_id,
            'code' => 'ak01',
        ])->with('success', "FR.AK01 untuk {$assessment->asesi->full_name} berhasil ditandatangani.");
    }

    public function instrumentShow(Assessment $assessment, $code, SchemeInstrumentService $instrumentService): View
    {
        $this->authorizeAssessorAssessment($assessment);

        $code = strtolower($code);

        if ($code === 'ak01') {
            $assessment->load('schedule.scheme', 'asesi.profile', 'assessor');
            $schedule = $assessment->schedule;

            return view('asesor.ak01', compact('assessment', 'schedule'));
        }

        $assessment->load('schedule.scheme.competencyUnits.elements.kuks', 'asesi.profile', 'iaResponses');
        $response = $assessment->iaResponses->firstWhere('instrument_code', strtoupper($code));

        $scheme = $assessment->schedule->scheme;
        $units = $scheme->competencyUnits;
        $tpdData = $instrumentService->getTpdData($scheme);
        $tasks = collect($tpdData['tasks'])->map(fn ($t) => (object) [
            'id' => $t['id'],
            'instruction' => $t['instruction'],
            'title' => $t['instruction'],
        ]);
        $pmoQuestions = $instrumentService->getPmoQuestions($scheme);
        $questions = collect($pmoQuestions)->values()->map(function ($q, $idx) {
            $num = $idx + 1;

            return (object) [
                'id' => $num,
                'number' => $num,
                'question' => $q['question'] ?? "Pertanyaan PMO #{$num}",
                'expected_answer' => $q['expected_answer'] ?? '',
            ];
        });
        $reportSheetItems = $instrumentService->getReportSheetItems($scheme);

        $savedData = is_array($response?->data_json) ? $response->data_json : (json_decode($response?->data_json ?? '[]', true) ?: []);
        $savedAnswers = $savedData['answers'] ?? $savedData['kuks'] ?? $savedData['tasks'] ?? $savedData['questions'] ?? $savedData['items'] ?? $savedData;
        $savedNotes = $response?->assessor_notes;
        $savedGeneralNotes = $response?->assessor_notes;
        $savedSignature = $response?->assessor_signature ?? auth()->user()->profile?->signature_data;

        $viewName = "asesor.ruang-uji.{$code}";
        if (! view()->exists($viewName)) {
            $viewName = "asesor.ruang-uji.ia{$code}";
        }

        $breakdown = $savedData['breakdown'] ?? $savedData['items'] ?? [];
        if (empty($breakdown) && ! empty($savedAnswers) && $code === 'ia05') {
            $questionIds = array_keys($savedAnswers);
            $qModels = QuestionBank::whereIn('id', $questionIds)->get()->keyBy('id');
            $breakdown = [];
            foreach ($questionIds as $qid) {
                $q = $qModels->get($qid);
                if (! $q) {
                    continue;
                }
                $ans = $savedAnswers[$qid] ?? null;
                $isCorrect = ($ans !== null && strtoupper((string) $ans) === $q->correct_answer);
                $breakdown[$qid] = [
                    'question_id' => $q->id,
                    'question' => $q->question_text,
                    'question_image' => $q->question_image,
                    'answer' => $ans,
                    'user_answer' => $ans,
                    'correct_answer' => $q->correct_answer,
                    'is_correct' => $isCorrect,
                ];
            }
        }
        $score = $response?->score ?? $savedData['score'] ?? ($assessment->ia05_score ?? 0);
        $ia02Text = ($code === 'ia02') ? $instrumentService->getIa02FullText($scheme) : '';
        $ia02Html = ($code === 'ia02') ? $instrumentService->getIa02FormattedHtml($scheme) : '';

        return view($viewName, compact(
            'assessment',
            'code',
            'response',
            'scheme',
            'units',
            'tasks',
            'tpdData',
            'questions',
            'pmoQuestions',
            'reportSheetItems',
            'savedAnswers',
            'savedNotes',
            'savedGeneralNotes',
            'savedSignature',
            'breakdown',
            'score',
            'ia02Text',
            'ia02Html'
        ));
    }

    public function instrumentStore(Assessment $assessment, $code, Request $request): RedirectResponse
    {
        $this->authorizeAssessorAssessment($assessment);

        $code = strtoupper($code);

        if ($assessment->schedule && $assessment->schedule->isClosedForAssessor()) {
            return back()->with('error', 'Jadwal asesmen sudah ditutup dan dikunci.');
        }

        if ($assessment->apl_status !== 'APPROVED') {
            return back()->with('error', 'Penilaian instrumen hanya dapat direkam untuk asesi yang permohonan APL-nya telah disetujui.');
        }

        $request->validate([
            'signature_data' => ['nullable', 'string', new ValidSignature],
            'assessor_signature' => ['nullable', 'string', new ValidSignature],
        ]);

        $data = $request->input('data') ?? [
            'kuks' => $request->input('kuks'),
            'tasks' => $request->input('tasks'),
            'questions' => $request->input('questions'),
            'items' => $request->input('items'),
        ];

        if ($code === 'IA02') {
            $data = is_array($data) ? $data : [];
            $data['ia02_acknowledged'] = true;
        }

        $existing = AssessmentIaResponse::where('assessment_id', $assessment->id)
            ->where('instrument_code', $code)
            ->first();

        $existingData = is_array($existing?->data_json)
            ? $existing->data_json
            : (json_decode($existing?->data_json ?? '[]', true) ?: []);

        if (isset($existingData['evidence_image_path'])) {
            $data['evidence_image_path'] = $existingData['evidence_image_path'];
            $data['evidence_uploaded_at'] = $existingData['evidence_uploaded_at'] ?? null;
            $data['evidence_original_name'] = $existingData['evidence_original_name'] ?? null;
        }

        if ($request->hasFile('evidence_image')) {
            $request->validate([
                'evidence_image' => 'required|file|mimes:jpeg,jpg,png,webp,pdf|max:10240',
            ]);
            $folder = strtolower($code) === 'ia01b' ? 'ia01b_evidence' : 'ia_evidence';
            $path = $request->file('evidence_image')->store($folder, 'local');
            $data['evidence_image_path'] = $path;
            $data['evidence_uploaded_at'] = now()->toIso8601String();
            $data['evidence_original_name'] = $request->file('evidence_image')->getClientOriginalName();
        }

        $data['audit_trail'] = [
            'evaluated_by_id' => auth()->id(),
            'evaluated_by_name' => auth()->user()?->name,
            'evaluated_at' => now()->toIso8601String(),
            'ip_address' => $request->ip(),
        ];

        Log::info('Asesor ID '.auth()->id()." ({$data['audit_trail']['evaluated_by_name']}) menyimpan penilaian {$code} untuk asesmen #{$assessment->id} (asesi #{$assessment->asesi_id})");

        // Auto-assign asesi signature from assessment/profile so asesi does not need redundant manual signing
        $asesiSig = $existing?->asesi_signature ?? $assessment->asesi_signature ?? $assessment->asesi?->profile?->signature_data;
        $asesiSignedAt = $existing?->asesi_signed_at ?? ($asesiSig ? now() : null);

        DB::transaction(function () use ($assessment, $code, $data, $request, $asesiSig, $asesiSignedAt) {
            AssessmentIaResponse::updateOrCreate(
                ['assessment_id' => $assessment->id, 'instrument_code' => $code],
                [
                    'data_json' => $data,
                    'assessor_notes' => $request->input('general_notes') ?? $request->input('notes') ?? $request->input('assessor_notes'),
                    'assessor_signature' => $request->input('signature_data') ?? $request->input('assessor_signature'),
                    'assessor_signed_at' => now(),
                    'asesi_signature' => $asesiSig,
                    'asesi_signed_at' => $asesiSignedAt,
                ]
            );
        });

        return redirect()->route('asesor.schedule.instrument', [
            'schedule' => $assessment->schedule_id,
            'code' => strtolower($code),
        ])->with('success', "Penilaian {$code} untuk {$assessment->asesi->full_name} berhasil disimpan.");
    }

    /**
     * Tanda tangani massal (Bulk Sign) dokumen FR.IA.02 untuk asesi terpilih.
     */
    public function bulkSignIa02(Schedule $schedule, Request $request): RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        if ($schedule->isClosedForAssessor()) {
            return back()->with('error', 'Jadwal asesmen sudah ditutup dan dikunci.');
        }

        $assessmentIds = $request->input('assessment_ids', []);
        if (empty($assessmentIds)) {
            return back()->with('error', 'Silakan pilih setidaknya satu asesi untuk menandatangani FR.IA.02.');
        }

        $user = auth()->user();
        $isSingleAssessor = $schedule->assessors->count() === 1 && $schedule->assessors->first()?->id === $user->id;

        $assessments = $schedule->assessments()
            ->with(['asesi.profile', 'iaResponses'])
            ->whereIn('id', $assessmentIds)
            ->where('apl_status', 'APPROVED')
            ->get()
            ->filter(function ($a) use ($user, $isSingleAssessor) {
                if ((int) $a->assessor_id === (int) $user->id) {
                    return true;
                }
                if (empty($a->assessor_id) && $isSingleAssessor) {
                    return true;
                }

                return false;
            });

        if ($assessments->isEmpty()) {
            return back()->with('error', 'Tidak ada asesi binaan yang valid (dengan status APL disetujui) untuk ditandatangani.');
        }

        if ($request->filled('assessor_signature')) {
            $request->validate([
                'assessor_signature' => [new ValidSignature],
            ]);
        }

        $signature = $request->input('assessor_signature') ?? $request->input('signature_data') ?? $user->profile?->signature_data;

        if (empty($signature)) {
            return back()->with('error', 'Tanda tangan digital asesor belum tersedia. Silakan buat tanda tangan terlebih dahulu atau simpan di profil Anda.');
        }

        if ($request->boolean('save_signature_to_profile') && ! empty($request->input('assessor_signature'))) {
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                ['signature_data' => $signature]
            );
        }

        $now = now();
        $signedCount = 0;

        DB::transaction(function () use ($assessments, $user, $signature, $now, $request, &$signedCount) {
            foreach ($assessments as $assessment) {
                $existing = $assessment->iaResponses->firstWhere('instrument_code', 'IA02');
                $existingData = is_array($existing?->data_json)
                    ? $existing->data_json
                    : (json_decode($existing?->data_json ?? '[]', true) ?: []);

                $data = $existingData;
                $data['ia02_acknowledged'] = true;
                $data['audit_trail'] = [
                    'evaluated_by_id' => $user->id,
                    'evaluated_by_name' => $user->full_name,
                    'evaluated_at' => $now->toIso8601String(),
                    'ip_address' => $request->ip(),
                    'is_bulk_signed' => true,
                ];

                $asesiSig = $existing?->asesi_signature ?? $assessment->asesi_signature ?? $assessment->asesi?->profile?->signature_data;
                $asesiSignedAt = $existing?->asesi_signed_at ?? ($asesiSig ? $now : null);

                AssessmentIaResponse::updateOrCreate(
                    [
                        'assessment_id' => $assessment->id,
                        'instrument_code' => 'IA02',
                    ],
                    [
                        'data_json' => $data,
                        'assessor_notes' => $existing?->assessor_notes ?? 'Tugas Praktik Demonstrasi (FR.IA.02) telah disetujui dan ditandatangani oleh Asesor.',
                        'assessor_signature' => $signature,
                        'assessor_signed_at' => $now,
                        'asesi_signature' => $asesiSig,
                        'asesi_signed_at' => $asesiSignedAt,
                    ]
                );

                $signedCount++;
            }
        });

        return redirect()->route('asesor.schedule.instrument', ['schedule' => $schedule->id, 'code' => 'ia02'])
            ->with('success', "Sebanyak {$signedCount} asesi berhasil ditandatangani dokumen FR.IA.02 secara massal.");
    }

    /**
     * Reset status pengerjaan CBT (FR.IA.05) asesi agar dapat mengikuti ujian ulang / remedial.
     */
    public function resetIa05Status(Assessment $assessment): RedirectResponse
    {
        $this->authorizeAssessorAssessment($assessment);

        if ($assessment->schedule && $assessment->schedule->is_closed) {
            return back()->with('error', 'Jadwal asesmen sudah resmi ditutup. Status ujian tidak dapat di-reset.');
        }

        $response = AssessmentIaResponse::where('assessment_id', $assessment->id)
            ->where('instrument_code', 'IA05')
            ->first();

        if (! $response) {
            return back()->with('info', 'Belum ada data pengerjaan CBT (FR.IA.05) untuk asesi ini.');
        }

        $asesiName = $assessment->asesi?->full_name ?? "Asesi #{$assessment->id}";

        DB::transaction(function () use ($assessment, $response) {
            $currentData = is_array($response->data_json)
                ? $response->data_json
                : (json_decode($response->data_json ?? '[]', true) ?: []);

            // Set status to DRAFT so CBT is unlocked for the asesi
            $currentData['status'] = 'DRAFT';

            $response->update([
                'score' => null,
                'asesi_signed_at' => null,
                'asesi_signature' => null,
                'assessor_signed_at' => null,
                'assessor_signature' => null,
                'data_json' => $currentData,
            ]);

            // Revert final recommendation to IN_PROGRESS if already marked COMPLETED or BELUM_KOMPETEN
            if ($assessment->final_result === 'BELUM_KOMPETEN' || $assessment->status === 'COMPLETED') {
                $assessment->update([
                    'status' => 'IN_PROGRESS',
                    'final_result' => 'BELUM_SELESAI',
                    'final_result_at' => null,
                    'completed_at' => null,
                ]);
            }

            // Invalidate cached MUK PDF for IA.05 and schedule summaries
            $cacheDir = storage_path('app/muk-cache');
            if (File::isDirectory($cacheDir)) {
                $filesToDelete = array_merge(
                    File::glob($cacheDir."/dyn_{$assessment->schedule_id}_{$assessment->id}_ia05*.pdf") ?: [],
                    File::glob($cacheDir."/dyn_{$assessment->schedule_id}_sch_ak05*.pdf") ?: [],
                    File::glob($cacheDir."/dyn_{$assessment->schedule_id}_sch_ak06*.pdf") ?: []
                );
                foreach ($filesToDelete as $f) {
                    File::delete($f);
                }
            }
        });

        return back()->with('success', "Status pengerjaan CBT (FR.IA.05) untuk asesi {$asesiName} berhasil di-reset. Asesi sekarang dapat mengerjakan remedial ujian CBT.");
    }
}
