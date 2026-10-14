<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\View\View;

class JadwalController extends Controller
{
    public function index(): View
    {
        $schedules = auth()->user()->assignedSchedules()
            ->with('scheme')
            ->latest('date')
            ->get();

        $jadwals = $schedules;

        return view('asesor.jadwal.index', compact('schedules', 'jadwals'));
    }

    public function asesiList(Schedule $schedule): View
    {
        $schedule->load('scheme');
        $jadwal = $schedule;
        $assessments = $schedule->assessments()
            ->with('asesi.profile')
            ->get();

        return view('asesor.jadwal.asesi', compact('schedule', 'jadwal', 'assessments'));
    }
}
