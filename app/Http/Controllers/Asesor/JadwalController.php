<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Concerns\AuthorizesAssessor;
use App\Http\Controllers\Controller;
use App\Models\Schedule;
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
            ->with(['scheme', 'assessments']);

        // 1. Text Search (Name, Code, TUK, Scheme)
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('t_schedules.name', 'like', "%{$search}%")
                    ->orWhere('t_schedules.code', 'like', "%{$search}%")
                    ->orWhere('t_schedules.tuk_name', 'like', "%{$search}%")
                    ->orWhereHas('scheme', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        // 2. Filter by Status (active vs completed/finished/selesai)
        if ($request->filled('status') && $request->status !== 'all') {
            $status = strtolower((string) $request->status);
            if (in_array($status, ['active', 'aktif'], true)) {
                $query->where(function ($q) {
                    $q->where('t_schedules.is_closed', false)
                        ->whereNotIn('t_schedules.status', ['COMPLETED', 'FINISHED', 'CANCELLED']);
                });
            } elseif (in_array($status, ['completed', 'finished', 'selesai'], true)) {
                $query->where(function ($q) {
                    $q->where('t_schedules.is_closed', true)
                        ->orWhereIn('t_schedules.status', ['COMPLETED', 'FINISHED']);
                });
            }
        }

        // 3. Filter by Date Range (start_date / end_date or date_from / date_to)
        $startDate = $request->input('start_date', $request->input('date_from'));
        $endDate = $request->input('end_date', $request->input('date_to'));

        if (! empty($startDate) && ! empty($endDate)) {
            $from = min($startDate, $endDate);
            $to = max($startDate, $endDate);
            $query->whereBetween('t_schedules.date', [$from, $to]);
        } elseif (! empty($startDate)) {
            $query->whereDate('t_schedules.date', '>=', $startDate);
        } elseif (! empty($endDate)) {
            $query->whereDate('t_schedules.date', '<=', $endDate);
        }

        // 4. Sorting
        $sort = $request->input('sort', 'date_desc');
        match ($sort) {
            'date_asc' => $query->orderBy('t_schedules.date', 'asc')->orderBy('t_schedules.start_time', 'asc'),
            'name_asc' => $query->orderBy('t_schedules.name', 'asc'),
            'name_desc' => $query->orderBy('t_schedules.name', 'desc'),
            default => $query->orderBy('t_schedules.date', 'desc')->orderBy('t_schedules.start_time', 'desc'),
        };

        // Compute counts for quick filter chips
        $baseQuery = $user->assignedSchedules();
        $counts = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('t_schedules.is_closed', false)
                ->whereNotIn('t_schedules.status', ['COMPLETED', 'FINISHED', 'CANCELLED'])->count(),
            'completed' => (clone $baseQuery)->where(function ($q) {
                $q->where('t_schedules.is_closed', true)
                    ->orWhereIn('t_schedules.status', ['COMPLETED', 'FINISHED']);
            })->count(),
        ];

        $schedules = $query->paginate(12)->withQueryString();
        $jadwals = $schedules;

        if ($request->ajax()) {
            return view('asesor.jadwal._table', compact('schedules', 'jadwals'));
        }

        $registrationToken = RegistrationTokenService::getCurrentToken();

        return view('asesor.jadwal.index', compact('schedules', 'jadwals', 'registrationToken', 'counts'));
    }

    public function suratTugas(Schedule $schedule): View
    {
        $user = auth()->user();

        // Check if the assessor is assigned to this schedule
        $isAssigned = $schedule->assessors()->where('users.id', $user->id)->exists();
        abort_unless($isAssigned, 403, 'Anda tidak ditugaskan pada jadwal asesmen ini.');

        $schedule->load(['scheme', 'assessors', 'assessments']);

        return view('asesor.jadwal.surat-tugas', [
            'schedule' => $schedule,
            'assessors' => $schedule->assessors,
            'backUrl' => route('asesor.jadwal.index'),
        ]);
    }
}
