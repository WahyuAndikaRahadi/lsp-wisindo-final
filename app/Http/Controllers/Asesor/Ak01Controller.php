<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Concerns\AuthorizesAssessor;
use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Rules\ValidSignature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class Ak01Controller extends Controller
{
    use AuthorizesAssessor;

    public function index(Schedule $schedule, Request $request): View|RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        if (! $schedule->isStepUnlocked('ak01')) {
            return redirect()->route('asesor.schedule.instrument', ['schedule' => $schedule->id, 'code' => 'apl'])
                ->with('warning', 'Harap selesaikan peninjauan seluruh berkas APL asesi terlebih dahulu sebelum mengisi FR.AK.01.');
        }

        $schedule->load(['scheme', 'assessments.asesi.profile', 'assessors']);

        $assessments = $schedule->assessorAssessments();

        $selectedAssessmentId = $request->query('assessment');

        // Defaults for Bukti yang akan dikumpulkan
        $defaultEvidence = ['Hasil Observasi Langsung', 'Hasil Pertanyaan Tertulis'];
        $savedEvidence = $schedule->ak01_data['evidence'] ?? $defaultEvidence;
        $savedOtherEvidence = $schedule->ak01_data['other_evidence'] ?? '';
        $savedTukType = $schedule->ak01_data['tuk_type'] ?? 'Sewaktu';
        $selectedInstruments = $schedule->getEffectiveInstruments();

        return view('asesor.ak01', compact(
            'schedule',
            'assessments',
            'selectedAssessmentId',
            'savedEvidence',
            'savedOtherEvidence',
            'savedTukType',
            'selectedInstruments'
        ));
    }

    public function store(Schedule $schedule, Request $request): RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        if ($schedule->isClosedForAssessor()) {
            return back()->with('error', 'Jadwal asesmen sudah resmi ditutup.');
        }

        $request->validate([
            'evidence' => ['nullable', 'array'],
            'other_evidence' => ['nullable', 'string', 'max:255'],
            'assessor_signature' => ['nullable', 'string', new ValidSignature],
            'tuk_type' => ['nullable', 'string'],
        ]);

        $evidence = $request->input('evidence', ['Hasil Observasi Langsung', 'Hasil Pertanyaan Tertulis']);
        if (empty($evidence)) {
            $evidence = ['Hasil Observasi Langsung', 'Hasil Pertanyaan Tertulis'];
        }

        $otherEvidence = $request->input('other_evidence');
        $tukType = $request->input('tuk_type', 'Sewaktu');
        $assessorSignature = $request->input('assessor_signature') ?? auth()->user()->profile?->signature_data;

        $ak01Data = [
            'evidence' => $evidence,
            'other_evidence' => $otherEvidence,
            'tuk_type' => $tukType,
            'agreed_date' => $schedule->date?->format('Y-m-d'),
            'agreed_time' => substr($schedule->start_time, 0, 5).' - '.substr($schedule->end_time, 0, 5).' WIB',
        ];

        $schedule->load(['assessments', 'assessors']);

        $myAssessments = $schedule->assessorAssessments();

        $selectedIds = array_map('intval', (array) $request->input('assessment_ids', []));
        $targetAssessments = ! empty($selectedIds)
            ? $myAssessments->whereIn('id', $selectedIds)
            : $myAssessments;

        $selectedInstruments = (array) $request->input('selected_instruments', []);
        $selectedIa = array_values(array_filter($selectedInstruments, fn ($c) => str_starts_with(strtoupper($c), 'IA')));
        if (empty($selectedIa)) {
            $selectedIa = ['IA01', 'IA01B', 'IA02', 'IA03', 'IA05'];
        }

        DB::transaction(function () use ($schedule, $targetAssessments, $ak01Data, $assessorSignature, $selectedIa) {
            foreach ($targetAssessments as $assessment) {
                $candAk01 = $assessment->ak01_data ?? [];
                $candAk01 = array_merge($candAk01, $ak01Data);
                $candAk01['selected_instruments'] = $selectedIa;

                $assessment->update([
                    'ak01_data' => $candAk01,
                    'assessor_signature' => $assessorSignature ?? $assessment->assessor_signature,
                    'assessor_signed_at' => $assessorSignature ? now() : $assessment->assessor_signed_at,
                    'assessor_id' => $assessment->assessor_id ?? auth()->id(),
                ]);
            }

            // Union of all assigned IA instruments across schedule assessments
            $allScheduleIa = $schedule->assessments()
                ->get()
                ->flatMap(fn ($asm) => $asm->ak01_data['selected_instruments'] ?? [])
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (empty($allScheduleIa)) {
                $allScheduleIa = $selectedIa;
            }

            $schedule->update([
                'ak01_data' => $ak01Data,
                'selected_instruments' => array_values(array_unique(array_merge(['AK01'], $allScheduleIa))),
            ]);
        });

        return redirect()->route('asesor.schedule.ak07', $schedule->id)
            ->with('success', 'FR.AK.01 Persetujuan Asesmen dan Kerahasiaan berhasil disimpan. Melanjutkan ke FR.AK.07.');
    }
}
