<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Concerns\AuthorizesAssessor;
use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class Ak04BriefingController extends Controller
{
    use AuthorizesAssessor;

    public function index(Schedule $schedule): View|RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        if (! $schedule->isStepUnlocked('ak04')) {
            return redirect()->route('asesor.schedule.mapa', $schedule->id)
                ->with('warning', 'Harap lengkapi dan simpan dokumen FR.MAPA.01 terlebih dahulu sebelum masuk ke pengenalan FR.AK.04.');
        }

        $schedule->load(['scheme.competencyUnits', 'assessments.asesi.profile', 'assessors']);
        $units = $schedule->scheme?->competencyUnits ?? collect();
        $assessments = $schedule->assessorAssessments();

        return view('asesor.ak04-briefing', compact('schedule', 'units', 'assessments'));
    }

    public function store(Schedule $schedule, Request $request): RedirectResponse
    {
        $this->authorizeAssessorSchedule($schedule);

        if ($schedule->isClosedForAssessor()) {
            return redirect()->route('asesor.schedule.ak01', $schedule->id)
                ->with('info', 'Jadwal asesmen sudah resmi ditutup.');
        }

        $request->validate([
            'is_briefed' => ['required', 'accepted'],
        ], [
            'is_briefed.accepted' => 'Anda harus mencentang konfirmasi bahwa Anda telah menjelaskan hak dan prosedur banding kepada asesi.',
        ]);

        $myAssessments = $schedule->assessorAssessments();

        DB::transaction(function () use ($schedule, $myAssessments) {
            $now = now();
            $schedule->update([
                'ak04_briefed_at' => $now,
            ]);

            foreach ($myAssessments as $assessment) {
                $assessment->update([
                    'ak04_briefed_at' => $now,
                ]);
            }
        });

        return redirect()->route('asesor.schedule.ak01', $schedule->id)
            ->with('success', 'Pengenalan prosedur banding (FR.AK.04) telah dikonfirmasi. Melanjutkan ke FR.AK.01 Persetujuan Asesmen.');
    }
}
