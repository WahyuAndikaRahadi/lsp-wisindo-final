<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;

class FinalizationController extends Controller
{
    public function finalize(Schedule $schedule): RedirectResponse
    {
        $schedule->update(['status' => 'FINISHED']);

        return redirect()->route('asesor.jadwal.index')
            ->with('success', 'Jadwal asesmen telah difinalisasi.');
    }

    public function completeAssessment(Assessment $assessment): RedirectResponse
    {
        $assessment->update(['status' => 'COMPLETED']);

        return back()->with('success', 'Asesmen asesi telah diselesaikan.');
    }
}
