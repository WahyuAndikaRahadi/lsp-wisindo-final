<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAk02Item;
use App\Models\AssessmentAk03Item;
use App\Models\AssessmentAppeal;
use App\Models\Schedule;

class AkWorkflowService
{
    /**
     * Auto-populate or update FR.AK.02 Rekaman Asesmen per unit kompetensi.
     */
    public function syncAk02(Assessment $assessment): array
    {
        $assessment->load(['schedule.scheme.competencyUnits', 'iaResponses', 'proofs']);
        $units = $assessment->schedule->scheme->competencyUnits;
        $iaResponses = $assessment->iaResponses->keyBy('instrument_code');
        $hasProofs = $assessment->proofs()->count() > 0;

        $hasDemonstration = $iaResponses->has('IA01') || $iaResponses->has('IA01B') || $iaResponses->has('IA02');
        $hasOral = $iaResponses->has('IA03');
        $hasWritten = $iaResponses->has('IA05');

        $allUnitsCompetent = true;

        foreach ($units as $unit) {
            $methods = [
                'observasi_demonstrasi' => $hasDemonstration,
                'portofolio' => $hasProofs,
                'pihak_ketiga' => false,
                'lisan' => $hasOral,
                'tulis' => $hasWritten,
                'proyek' => false,
                'lainnya' => false,
            ];

            // Determine competence: if IA05 CBT failed (< 75), competence might be false
            $isUnitCompetent = true;
            if ($hasWritten && isset($iaResponses['IA05'])) {
                $score = (float) $iaResponses['IA05']->score;
                if ($score < 75.0) {
                    $isUnitCompetent = false;
                }
            }

            if (! $isUnitCompetent) {
                $allUnitsCompetent = false;
            }

            AssessmentAk02Item::updateOrCreate(
                [
                    'assessment_id' => $assessment->id,
                    'competency_unit_id' => $unit->id,
                ],
                [
                    'methods_json' => $methods,
                    'is_competent' => $isUnitCompetent,
                ]
            );
        }

        $finalResult = $allUnitsCompetent ? 'KOMPETEN' : 'BELUM_KOMPETEN';
        $assessment->update([
            'final_result' => $finalResult,
            'final_notes' => $finalResult === 'KOMPETEN'
                ? 'Seluruh unjuk kerja memenuhi Kriteria Unjuk Kerja (KUK) dan standar SKKNI.'
                : 'Diperlukan tindak lanjut pengayaan dan asesmen ulang pada unit yang belum kompeten.',
            'follow_up_action' => $finalResult === 'KOMPETEN'
                ? 'Diusulkan untuk penerbitan Sertifikat Kompetensi BNSP.'
                : 'Mengikuti asesmen ulang pada unit kompetensi terkait.',
        ]);

        return [
            'final_result' => $finalResult,
            'total_units' => $units->count(),
            'all_competent' => $allUnitsCompetent,
        ];
    }

    /**
     * Submit candidate registration approval by Admin LSP.
     */
    public function approveCandidateRegistration(Assessment $assessment, string $status, ?string $notes = null, ?int $adminId = null): Assessment
    {
        $statusUpper = strtoupper($status);
        $validStatuses = ['PENDING', 'APPROVED', 'REVISION', 'REJECTED'];

        if (! in_array($statusUpper, $validStatuses, true)) {
            $statusUpper = 'APPROVED';
        }

        $assessment->update([
            'admin_status' => $statusUpper,
            'admin_notes' => $notes,
            'admin_approved_at' => now(),
            'admin_id' => $adminId ?? auth()->id(),
        ]);

        return $assessment;
    }

    /**
     * Submit FR.AK.03 Umpan Balik Asesi.
     */
    public function submitAk03Feedback(Assessment $assessment, array $ratings, ?string $generalNotes = null): AssessmentAk03Item
    {
        return AssessmentAk03Item::updateOrCreate(
            ['assessment_id' => $assessment->id],
            [
                'ratings_json' => $ratings,
                'general_notes' => $generalNotes,
            ]
        );
    }

    /**
     * Submit FR.AK.04 Banding Asesmen by Asesi.
     */
    public function submitAppeal(Assessment $assessment, string $reasons, array $contestedUnits = []): AssessmentAppeal
    {
        return AssessmentAppeal::create([
            'assessment_id' => $assessment->id,
            'appeal_date' => now()->toDateString(),
            'appeal_reasons' => $reasons,
            'contested_units' => $contestedUnits,
            'status' => 'SUBMITTED',
        ]);
    }

    /**
     * Decide appeal by Admin LSP.
     */
    public function decideAppeal(AssessmentAppeal $appeal, string $decision, ?string $decisionNotes = null, ?int $adminId = null): AssessmentAppeal
    {
        $status = strtoupper($decision) === 'ACCEPTED' ? 'ACCEPTED' : 'REJECTED';

        $appeal->update([
            'status' => $status,
            'decision_notes' => $decisionNotes,
            'decision_date' => now()->toDateString(),
            'decided_by' => $adminId ?? auth()->id(),
        ]);

        return $appeal;
    }

    /**
     * Submit Schedule Report (FR.AK.05 & FR.AK.06) before closure.
     */
    public function submitScheduleReports(Schedule $schedule, array $ak05Data, array $ak06Data): Schedule
    {
        $schedule->update([
            'ak05_report' => array_merge($ak05Data, [
                'assessor_id' => auth()->id(),
                'submitted_at' => now()->toIso8601String(),
            ]),
            'ak06_review' => array_merge($ak06Data, [
                'reviewer_id' => auth()->id(),
                'reviewed_at' => now()->toIso8601String(),
            ]),
            'is_closed' => true,
            'closed_at' => now(),
            'status' => 'FINISHED',
        ]);

        return $schedule;
    }

    /**
     * Check if schedule is ready to be closed.
     */
    public function isScheduleReadyForClosure(Schedule $schedule): array
    {
        $uncompletedAssessments = $schedule->assessments()
            ->where('status', '!=', 'COMPLETED')
            ->count();

        $hasAk05 = ! empty($schedule->ak05_report);
        $hasAk06 = ! empty($schedule->ak06_review);

        return [
            'is_ready' => $uncompletedAssessments === 0,
            'uncompleted_count' => $uncompletedAssessments,
            'has_ak05' => $hasAk05,
            'has_ak06' => $hasAk06,
        ];
    }
}
