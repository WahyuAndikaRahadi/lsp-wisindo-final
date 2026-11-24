<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Services\RegistrationTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_schedules' => Schedule::count(),
            'total_asesi' => User::where('role', 'ASESI')->count(),
            'total_asesor' => User::where('role', 'ASESOR')->count(),
            'total_schemes' => Scheme::active()->count(),
        ];

        $recentSchedules = Schedule::with(['scheme'])
            ->latest('date')
            ->take(5)
            ->get();

        $tokenInfo = RegistrationTokenService::getTokenInfo();
        $registrationToken = $tokenInfo['token'];

        return view('admin.dashboard', compact(
            'stats',
            'recentSchedules',
            'registrationToken',
            'tokenInfo'
        ));
    }

    public function rotateRegistrationToken(): RedirectResponse
    {
        $newToken = RegistrationTokenService::generateNewToken();

        return redirect()->route('admin.dashboard')
            ->with('success', "Token registrasi asesi berhasil diacak ulang menjadi: {$newToken}");
    }
}
