<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAppeal;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $pendingRegistrations = Assessment::with(['asesi.profile', 'schedule.scheme'])
            ->where('admin_status', 'PENDING')
            ->latest('registered_at')
            ->take(5)
            ->get();
        $pendingRegistrationsCount = Assessment::where('admin_status', 'PENDING')->count();

        $schedulesToClose = Schedule::with('scheme')
            ->where('is_closed', false)
            ->where('status', 'ACTIVE')
            ->whereHas('assessments', function ($q) {
                $q->whereNotNull('final_result');
            })
            ->latest('date')
            ->take(5)
            ->get();
        $schedulesToCloseCount = Schedule::where('is_closed', false)
            ->where('status', 'ACTIVE')
            ->whereHas('assessments', function ($q) {
                $q->whereNotNull('final_result');
            })
            ->count();

        $pendingAppeals = AssessmentAppeal::with(['assessment.asesi', 'assessment.schedule.scheme'])
            ->where('status', 'SUBMITTED')
            ->latest('created_at')
            ->take(5)
            ->get();
        $pendingAppealsCount = AssessmentAppeal::where('status', 'SUBMITTED')->count();

        $stats = [
            'total_schedules' => Schedule::count(),
            'total_asesi' => User::where('role', 'ASESI')->count(),
            'total_asesor' => User::where('role', 'ASESOR')->count(),
            'total_schemes' => Scheme::active()->count(),
            'pending_registrations' => $pendingRegistrationsCount,
            'schedules_to_close' => $schedulesToCloseCount,
            'pending_appeals' => $pendingAppealsCount,
        ];

        $recentSchedules = Schedule::with(['scheme'])
            ->latest('date')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentSchedules',
            'pendingRegistrations',
            'pendingRegistrationsCount',
            'schedulesToClose',
            'schedulesToCloseCount',
            'pendingAppeals',
            'pendingAppealsCount'
        ));
    }
}
