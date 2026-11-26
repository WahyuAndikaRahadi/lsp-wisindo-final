<?php

namespace App\Http\Controllers\Tuk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $tuk = $user->tuk;

        $stats = [
            'is_verified' => $tuk ? $tuk->isVerified() : false,
            'status' => $tuk ? $tuk->verification_status : 'UNSUBMITTED',
            'status_label' => $tuk ? $tuk->status_label : 'Belum Melengkapi Berkas',
            'status_badge' => $tuk ? $tuk->status_badge : 'bg-slate-100 text-slate-700 border-slate-300',
            'notes' => $tuk?->verification_notes,
            'verified_schemes_count' => $tuk ? $tuk->schemes()->wherePivot('is_verified', true)->count() : 0,
            'total_schedules' => $tuk ? $tuk->schedules()->count() : 0,
            'active_schedules' => $tuk ? $tuk->schedules()->where('status', 'ACTIVE')->count() : 0,
        ];

        $upcomingSchedules = $tuk
            ? $tuk->schedules()
                ->with(['scheme', 'assessors', 'assessments'])
                ->orderBy('date', 'desc')
                ->take(5)
                ->get()
            : collect();

        $verifiedSchemes = $tuk
            ? $tuk->schemes()->wherePivot('is_verified', true)->get()
            : collect();

        return view('tuk.dashboard', compact('tuk', 'stats', 'upcomingSchedules', 'verifiedSchemes'));
    }
}
