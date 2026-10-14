<?php

namespace App\Http\Controllers\Asesi;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalController extends Controller
{
    public function dashboard(): View
    {
        $userId = auth()->id();
        $activeAssessmentsCount = Assessment::where('asesi_id', $userId)
            ->where('status', 'IN_PROGRESS')
            ->count();

        $assessments = Assessment::with(['schedule.scheme'])
            ->where('asesi_id', $userId)
            ->latest('registered_at')
            ->get();

        return view('asesi.dashboard', compact('activeAssessmentsCount', 'assessments'));
    }

    public function index(): View
    {
        $assessments = Assessment::with(['schedule.scheme', 'assessor'])
            ->where('asesi_id', auth()->id())
            ->latest('registered_at')
            ->get();

        return view('asesi.jadwal.index', compact('assessments'));
    }

    public function join(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $schedule = Schedule::where('code', $request->code)->first();

        if (! $schedule || $schedule->status !== 'ACTIVE') {
            return back()->with('error', 'Kode jadwal tidak ditemukan atau jadwal sudah tidak aktif.');
        }

        $userId = auth()->id();
        $existing = Assessment::where('schedule_id', $schedule->id)
            ->where('asesi_id', $userId)
            ->first();

        if ($existing) {
            if ($existing->last_active_step === 'ruang-uji' && $existing->apl_status === 'APPROVED') {
                return redirect()->route('asesi.assessment.ruang-uji', $existing);
            }

            return redirect()->route('asesi.assessment.apl', $existing);
        }

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $userId,
            'apl_status' => 'DRAFT',
            'registered_at' => now(),
            'last_active_step' => 'apl',
        ]);

        return redirect()->route('asesi.assessment.apl', $assessment)->with('success', 'Berhasil bergabung dengan jadwal asesmen.');
    }
}
