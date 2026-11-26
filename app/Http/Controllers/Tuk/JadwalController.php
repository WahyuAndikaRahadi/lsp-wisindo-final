<?php

namespace App\Http\Controllers\Tuk;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalController extends Controller
{
    public function index(Request $request): View
    {
        $tuk = $request->user()->tuk;

        $schedules = Schedule::where('tuk_id', $tuk->id)
            ->with(['scheme', 'assessors', 'assessments.asesi.profile'])
            ->orderBy('date', 'desc')
            ->paginate(10);

        return view('tuk.jadwal.index', compact('tuk', 'schedules'));
    }

    public function show(Schedule $schedule): View
    {
        abort_if($schedule->tuk_id !== auth()->user()->tuk_id, 403, 'Anda tidak memiliki hak akses ke jadwal TUK ini.');

        $schedule->load(['scheme', 'assessors', 'assessments.asesi.profile']);

        return view('tuk.jadwal.show', compact('schedule'));
    }
}
