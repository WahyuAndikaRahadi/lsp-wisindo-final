<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Concerns\AuthorizesAssessor;
use App\Http\Controllers\Controller;
use App\Services\RegistrationTokenService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalController extends Controller
{
    use AuthorizesAssessor;

    public function index(Request $request): View
    {
        $user = auth()->user();

        $query = $user->assignedSchedules()
            ->with(['scheme', 'assessments'])
            ->latest('date');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('tuk_name', 'like', "%{$search}%");
            });
        }

        $schedules = $query->paginate(12)->withQueryString();
        $jadwals = $schedules;

        if ($request->ajax()) {
            return view('asesor.jadwal._table', compact('schedules', 'jadwals'));
        }

        $registrationToken = RegistrationTokenService::getCurrentToken();

        return view('asesor.jadwal.index', compact('schedules', 'jadwals', 'registrationToken'));
    }
}
