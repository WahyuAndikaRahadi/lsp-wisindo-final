<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Concerns\AuthorizesAssessor;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\Schedule;
use App\Rules\ValidSignature;
use App\Services\SchemeInstrumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class InstrumentController extends Controller
{
    use AuthorizesAssessor;

    /**
     * Metadata definitions for standard BNSP instruments.
     */
    protected array $instrumentsMeta = [
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

        $unapprovedCount = $schedule->assessments()->where('apl_status', '!=', 'APPROVED')->count();
        if ($unapprovedCount > 0) {
            return redirect()->route('asesor.jadwal.asesi', $schedule->id)
                ->with('error', "Ruang Uji belum dapat dibuka. Masih ada {$unapprovedCount} asesi yang belum disetujui APL-nya.");
        }

        $schedule->load([
            'scheme.competencyUnits.elements.kuks',
            'assessments.asesi.profile',
            'assessments.iaResponses',
            'assessors',
        ]);

        $selectedCodes = ! empty($schedule->selected_instruments)
            ? (array) $schedule->selected_instruments
            : ['AK01', 'IA01', 'IA01B', 'IA02', 'IA03', 'IA05'];

        $totalAsesi = $schedule->assessments->count();

        $instruments = [];
        foreach ($selectedCodes as $code) {
            $c = strtoupper($code);
            $meta = $this->instrumentsMeta[$c] ?? [
                'code' => 'FR.'.$c,
                'title' => 'Instrumen '.$c,
                'desc' => 'Instrumen asesmen kompetensi terstandar.',
                'badge' => $c,
                'badge_color' => 'bg-slate-50 text-slate-700 border-slate-200',
                'route_code' => strtolower($c),
            ];

            // Hitung asesi yang sudah dinilai/selesai pada instrumen ini
            $completedCount = 0;
            foreach ($schedule->assessments as $ass) {
                if ($c === 'AK01') {
                    if (! empty($ass->assessor_signature) || ! empty($ass->approved_at)) {
                        $completedCount++;
                    }
                } else {
                    $hasResp = $ass->iaResponses->firstWhere('instrument_code', $c);
                    if ($hasResp) {
                        $completedCount++;
                    }
                }
            }

            $meta['raw_code'] = $c;
            $meta['completed_count'] = $completedCount;
            $meta['total_asesi'] = $totalAsesi;
            $meta['percentage'] = $totalAsesi > 0 ? round(($completedCount / $totalAsesi) * 100) : 0;

            $instruments[] = $meta;
        }

        return view('asesor.ruang-uji.schedule-hub', compact('schedule', 'instruments', 'totalAsesi'));
    }

    /**
     * Halaman Instrumen Jadwal: Menampilkan daftar asesi pada instrumen terpilih.
     */
    public function scheduleInstrument(Schedule $schedule, string $code, SchemeInstrumentService $instrumentService): View
    {
        $this->authorizeAssessorSchedule($schedule);

        $codeUpper = strtoupper($code);
        $codeLower = strtolower($code);

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

        $asesiList = $schedule->assessments->map(function ($assessment) use ($codeUpper, $codeLower) {
            $response = $assessment->iaResponses->firstWhere('instrument_code', $codeUpper);
            $isCompleted = false;
            $scoreLabel = null;
            $statusLabel = 'Belum Dinilai';
            $statusClass = 'bg-slate-100 text-slate-700 border-slate-300';

            if ($codeUpper === 'AK01') {
                $isCompleted = ! empty($assessment->assessor_signature) || ! empty($assessment->approved_at);
                $statusLabel = $isCompleted ? 'Sudah Ditandatangani' : 'Belum Ditandatangani';
                $statusClass = $isCompleted ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-amber-50 text-amber-700 border-amber-300';
                $inspectUrl = route('asesor.schedule.ak01', ['schedule' => $assessment->schedule_id, 'assessment' => $assessment->id]);
            } else {
                if ($response) {
                    $isCompleted = true;
                    if ($codeUpper === 'IA05') {
                        $score = $response->score ?? ($assessment->ia05_score ?? 0);
                        $scoreLabel = "Nilai CBT: {$score}%";
                        $statusLabel = $score >= 70 ? 'Kompeten (K)' : 'Belum Kompeten (BK)';
                        $statusClass = $score >= 70 ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-rose-50 text-rose-700 border-rose-300';
                    } else {
                        $statusLabel = 'Sudah Dinilai';
                        $statusClass = 'bg-emerald-50 text-emerald-700 border-emerald-300';
                    }
                }
                $inspectUrl = route('asesor.assessment.instrument', ['assessment' => $assessment->id, 'code' => $codeLower]);
            }

            $hasEvidence = ! empty($response?->data_json['evidence_image_path']);

            return (object) [
                'assessment' => $assessment,
                'asesi' => $assessment->asesi,
                'is_completed' => $isCompleted,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
                'score_label' => $scoreLabel,
                'inspect_url' => $inspectUrl,
                'has_evidence' => $hasEvidence,
            ];
        });

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

    public function hub(Assessment $assessment): View
    {
        $this->authorizeAssessorAssessment($assessment);

        $assessment->load('schedule.scheme', 'asesi.profile', 'iaResponses');

        return view('asesor.ruang-uji.hub', compact('assessment'));
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
        $questions = collect($pmoQuestions)->map(fn ($q) => (object) [
            'id' => $q['number'] ?? $q['id'] ?? 1,
            'question' => $q['question'],
            'expected_answer' => $q['expected_answer'] ?? '',
        ]);
        $reportSheetItems = $instrumentService->getReportSheetItems($scheme);

        $savedData = is_array($response?->data_json) ? $response->data_json : (json_decode($response?->data_json ?? '[]', true) ?: []);
        $savedAnswers = $savedData['answers'] ?? $savedData['kuks'] ?? $savedData['tasks'] ?? $savedData['questions'] ?? $savedData['items'] ?? $savedData;
        $savedNotes = $response?->assessor_notes;
        $savedGeneralNotes = $response?->assessor_notes;
        $savedSignature = $response?->assessor_signature;

        $viewName = "asesor.ruang-uji.{$code}";
        if (! view()->exists($viewName)) {
            $viewName = "asesor.ruang-uji.ia{$code}";
        }

        $breakdown = $savedData['breakdown'] ?? $savedData['items'] ?? [];
        $score = $response?->score ?? $savedData['score'] ?? ($assessment->ia05_score ?? 0);

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
            'score'
        ));
    }

    public function instrumentStore(Assessment $assessment, $code, Request $request): RedirectResponse
    {
        $this->authorizeAssessorAssessment($assessment);

        $code = strtoupper($code);

        if ($assessment->schedule && $assessment->schedule->is_closed) {
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
            $path = $request->file('evidence_image')->store('ia02_evidence', 'public');
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

        DB::transaction(function () use ($assessment, $code, $data, $request) {
            AssessmentIaResponse::updateOrCreate(
                ['assessment_id' => $assessment->id, 'instrument_code' => $code],
                [
                    'data_json' => $data,
                    'assessor_notes' => $request->input('general_notes') ?? $request->input('notes') ?? $request->input('assessor_notes'),
                    'assessor_signature' => $request->input('signature_data') ?? $request->input('assessor_signature'),
                    'assessor_signed_at' => now(),
                ]
            );
        });

        return redirect()->route('asesor.schedule.instrument', [
            'schedule' => $assessment->schedule_id,
            'code' => strtolower($code),
        ])->with('success', "Penilaian {$code} untuk {$assessment->asesi->full_name} berhasil disimpan.");
    }
}
