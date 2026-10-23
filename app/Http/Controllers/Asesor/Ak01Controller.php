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

        $schedule->load(['scheme', 'assessments.asesi.profile', 'assessors']);

        $assessments = $schedule->assessments;
        $selectedAssessmentId = $request->query('assessment');

        // Defaults for Bukti yang akan dikumpulkan
        $defaultEvidence = ['Hasil Observasi Langsung', 'Hasil Pertanyaan Tertulis'];
        $savedEvidence = $schedule->ak01_data['evidence'] ?? $defaultEvidence;
        $savedOtherEvidence = $schedule->ak01_data['other_evidence'] ?? '';
        $savedTukType = $schedule->ak01_data['tuk_type'] ?? 'Sewaktu';

        return view('asesor.ak01', compact(
            'schedule',
            'assessments',
            'selectedAssessmentId',
            'savedEvidence',
            'savedOtherEvidence',
            'savedTukType'
        ));
    }

    public function store(Schedule $schedule, Request $request): RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        if ($schedule->is_closed) {
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

        DB::transaction(function () use ($schedule, $request, $ak01Data, $assessorSignature) {
            $schedule->update([
                'ak01_data' => $ak01Data,
            ]);

            $selectedIds = $request->input('assessment_ids', []);
            $targetAssessments = ! empty($selectedIds)
                ? $schedule->assessments()->whereIn('id', $selectedIds)->get()
                : $schedule->assessments;

            foreach ($targetAssessments as $assessment) {
                $assessment->update([
                    'ak01_data' => $ak01Data,
                    'assessor_signature' => $assessorSignature ?? $assessment->assessor_signature,
                    'assessor_signed_at' => $assessorSignature ? now() : $assessment->assessor_signed_at,
                    'assessor_id' => auth()->id(),
                ]);
            }
        });

        return redirect()->route('asesor.schedule.ak07', $schedule->id)
            ->with('success', 'FR.AK.01 Persetujuan Asesmen dan Kerahasiaan berhasil disimpan. Melanjutkan ke FR.AK.07.');
    }
}
