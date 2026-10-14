<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Schedule;

class MukExportService
{
    public function getExportData(Schedule $schedule): array
    {
        return [
            'schedule' => $schedule->load('scheme', 'assessors', 'assessments.asesi.profile'),
            'total_asesi' => $schedule->assessments->count(),
            'total_kompeten' => $schedule->assessments->where('status', 'COMPLETED')->count(),
        ];
    }

    public function getAsesiExportData(Assessment $assessment): array
    {
        return [
            'assessment' => $assessment->load(
                'schedule.scheme',
                'asesi.profile',
                'assessor',
                'proofs',
                'apl02Items.kuk.unitElement.competencyUnit',
                'iaResponses',
                'ak07Items'
            ),
        ];
    }
}
