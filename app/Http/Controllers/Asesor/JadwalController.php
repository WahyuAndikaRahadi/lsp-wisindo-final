<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Concerns\AuthorizesAssessor;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class JadwalController extends Controller
{
    use AuthorizesAssessor;

    public function index(): View
    {
        $user = auth()->user();

        $schedules = $user->assignedSchedules()
            ->with(['scheme', 'assessments'])
            ->latest('date')
            ->get();

        $jadwals = $schedules;

        return view('asesor.jadwal.index', compact('schedules', 'jadwals'));
    }
}
