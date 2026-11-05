<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Assessment;
use App\Models\Schedule;

trait AuthorizesAssessor
{
    /**
     * Ensure the authenticated user is an ASESOR assigned to the given schedule.
     */
    protected function authorizeAssessorSchedule(Schedule $schedule): void
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role, ['ASESOR', 'SUPER_ADMIN', 'ADMIN_LSP'], true)) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->role === 'ASESOR') {
            $isAssigned = $schedule->assessors()->where('users.id', $user->id)->exists();
            if (! $isAssigned) {
                abort(403, 'Anda tidak ditugaskan pada jadwal asesmen ini.');
            }
        }
    }

    /**
     * Ensure the authenticated user is an ASESOR assigned to the given assessment's schedule
     * and not assigned to another assessor.
     */
    protected function authorizeAssessorAssessment(Assessment $assessment): void
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role, ['ASESOR', 'SUPER_ADMIN', 'ADMIN_LSP'], true)) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->role === 'ASESOR') {
            $schedule = $assessment->schedule;
            if (! $schedule) {
                abort(404, 'Jadwal asesmen tidak ditemukan.');
            }

            $isScheduleAssessor = $schedule->assessors()->where('users.id', $user->id)->exists();
            if (! $isScheduleAssessor) {
                abort(403, 'Anda tidak ditugaskan pada jadwal asesmen ini.');
            }

            $isSingleAssessor = $schedule->assessors()->count() === 1;
            $isDirectAssessor = (int) $assessment->assessor_id === (int) $user->id;

            if (! $isDirectAssessor && (! $isSingleAssessor || ! empty($assessment->assessor_id))) {
                abort(403, 'Asesi ini telah ditugaskan kepada asesor lain atau bukan merupakan asesi binaan plotting Anda.');
            }
        }
    }
}
