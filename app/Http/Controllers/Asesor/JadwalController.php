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

        // 3. Filter by Date (single date with fallback for date range)
        if ($request->filled('date')) {
            $query->whereDate('t_schedules.date', $request->date);
        } elseif ($request->filled('start_date') || $request->filled('end_date') || $request->filled('date_from') || $request->filled('date_to')) {
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
        }

        // 4. Sorting: always default to latest date and start time
        $query->orderBy('t_schedules.date', 'desc')->orderBy('t_schedules.start_time', 'desc');

        $schedules = $query->paginate(12)->withQueryString();
        $jadwals = $schedules;

        if ($request->ajax()) {
            return view('asesor.jadwal._table', compact('schedules', 'jadwals'));
        }

        $tokenInfo = RegistrationTokenService::getTokenInfo();
        $registrationToken = $tokenInfo['token'];

        return view('asesor.jadwal.index', compact('schedules', 'jadwals', 'registrationToken', 'tokenInfo'));
    }

    public function suratTugas(Request $request, Schedule $schedule): View
    {
        $this->authorizeAssessorSchedule($schedule);

        $schedule->load(['scheme', 'assessors', 'assessments']);

        if ($request->has('modal') || $request->ajax()) {
            return view('asesor.jadwal._surat_tugas_content', [
                'schedule' => $schedule,
                'assessors' => $schedule->assessors,
            ]);
        }

        return view('asesor.jadwal.surat-tugas', [
            'schedule' => $schedule,
            'assessors' => $schedule->assessors,
            'backUrl' => route('asesor.jadwal.index'),
        ]);
    }
}
