<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAk03Item;
use App\Models\AssessmentAppeal;
use App\Models\Schedule;
use App\Services\AkWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppealController extends Controller
{
    public function __construct(
        protected AkWorkflowService $workflowService
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $filter = (string) $request->query('filter', 'all');
        $activeTab = (string) $request->query('tab', 'schedules');

        // 1. Query Schedules with assessments, appeals, and ak03 feedback counts
        $schedulesQuery = Schedule::with(['scheme', 'assessors'])
            ->withCount([
                'assessments',
                'appeals',
                'appeals as pending_appeals_count' => function ($q) {
                    $q->whereIn('t_assessment_appeals.status', ['SUBMITTED', 'UNDER_REVIEW']);
                },
                'ak03Items',
            ]);

        $user = $request->user();
        if ($user && $user->role === 'TUK_ADMIN') {
            $user->loadMissing('tuk');
            $schedulesQuery->where(function ($q) use ($user) {
                if ($user->tuk_id) {
                    $q->where('tuk_id', $user->tuk_id);
                }
                if ($user->tuk?->name) {
                    $q->orWhere('tuk_name', $user->tuk->name);
                }
            });
        }

        if ($search !== '') {
            $schedulesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('tuk_name', 'like', "%{$search}%")
                    ->orWhereHas('scheme', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if ($filter === 'has_appeals') {
            $schedulesQuery->has('appeals');
        } elseif ($filter === 'pending_appeals') {
            $schedulesQuery->whereHas('appeals', function ($q) {
                $q->whereIn('status', ['SUBMITTED', 'UNDER_REVIEW']);
            });
        } elseif ($filter === 'feedbacks_incomplete') {
            $schedulesQuery->has('assessments')
                ->whereRaw('(SELECT COUNT(*) FROM t_assessment_ak03_items INNER JOIN t_assessments ON t_assessments.id = t_assessment_ak03_items.assessment_id WHERE t_schedules.id = t_assessments.schedule_id) < (SELECT COUNT(*) FROM t_assessments WHERE t_schedules.id = t_assessments.schedule_id)');
        } elseif ($filter === 'feedbacks_complete') {
            $schedulesQuery->has('assessments')
                ->whereRaw('(SELECT COUNT(*) FROM t_assessment_ak03_items INNER JOIN t_assessments ON t_assessments.id = t_assessment_ak03_items.assessment_id WHERE t_schedules.id = t_assessments.schedule_id) >= (SELECT COUNT(*) FROM t_assessments WHERE t_schedules.id = t_assessments.schedule_id)');
        }

        $schedules = $schedulesQuery->latest('date')
            ->paginate(15)
            ->withQueryString();

        // 2. Query all appeals for global appeals tab
        $appealsQuery = AssessmentAppeal::with([
            'assessment.asesi',
            'assessment.assessor',
            'assessment.schedule.scheme.competencyUnits',
            'assessment.ak03Item',
            'decider',
        ]);

        if ($user && $user->role === 'TUK_ADMIN') {
            $appealsQuery->whereHas('assessment.schedule', function ($sq) use ($user) {
                if ($user->tuk_id) {
                    $sq->where('tuk_id', $user->tuk_id);
                }
                if ($user->tuk?->name) {
                    $sq->orWhere('tuk_name', $user->tuk->name);
                }
            });
        }

        $appeals = $appealsQuery->latest()
            ->paginate(15, ['*'], 'appeals_page')
            ->withQueryString();

        // 3. Query all feedbacks
        $feedbacks = AssessmentAk03Item::with([
            'assessment.asesi',
            'assessment.assessor',
            'assessment.schedule.scheme',
            'assessment.appeals',
        ])
            ->latest()
            ->paginate(15, ['*'], 'feedbacks_page');

        // 4. Metric counts for stat cards and badges
        $counts = [
            'schedules_total' => Schedule::count(),
            'schedules_with_appeals' => Schedule::has('appeals')->count(),
            'appeals_total' => AssessmentAppeal::count(),
            'appeals_pending' => AssessmentAppeal::whereIn('status', ['SUBMITTED', 'UNDER_REVIEW'])->count(),
            'appeals_accepted' => AssessmentAppeal::where('status', 'ACCEPTED')->count(),
            'appeals_rejected' => AssessmentAppeal::where('status', 'REJECTED')->count(),
            'feedbacks_total' => AssessmentAk03Item::count(),
        ];

        // 5. AJAX partial rendering support
        if ($request->ajax()) {
            if ($request->has('appeals_page') || $activeTab === 'appeals') {
                return view('admin.appeals._appeals_table', compact('appeals'));
            }

            if ($request->has('feedbacks_page') || $activeTab === 'feedbacks') {
                return view('admin.appeals._feedbacks_table', compact('feedbacks'));
            }

            return view('admin.appeals._schedules_table', compact('schedules', 'search', 'filter'));
        }

        return view('admin.appeals.index', compact('schedules', 'appeals', 'feedbacks', 'counts', 'activeTab', 'search', 'filter'));
    }

    public function show(Schedule $schedule, Request $request): View
    {
        $user = $request->user();
        if ($user && $user->role === 'TUK_ADMIN') {
            $user->loadMissing('tuk');
            $belongsToTuk = ($user->tuk_id && $schedule->tuk_id === $user->tuk_id)
                || ($user->tuk?->name && $schedule->tuk_name === $user->tuk->name);
            abort_unless($belongsToTuk, 403, 'Anda tidak memiliki akses ke jadwal TUK ini.');
        }

        $schedule->loadMissing([
            'scheme.competencyUnits',
            'assessors',
            'assessments' => function ($q) {
                $q->with([
                    'asesi',
                    'assessor',
                    'ak03Item',
                    'appeals' => function ($aq) {
                        $aq->with('decider')->latest();
                    },
                ])->orderBy('id', 'asc');
            },
        ]);

        $schedule->loadCount([
            'assessments',
            'appeals',
            'appeals as pending_appeals_count' => function ($q) {
                $q->whereIn('t_assessment_appeals.status', ['SUBMITTED', 'UNDER_REVIEW']);
            },
            'ak03Items',
        ]);

        $schemeUnits = $schedule->scheme?->competencyUnits ? $schedule->scheme->competencyUnits->keyBy('code') : collect();
        $schemeUnitsById = $schedule->scheme?->competencyUnits ? $schedule->scheme->competencyUnits->keyBy('id') : collect();

        $scheduleAppeals = AssessmentAppeal::whereHas('assessment', function ($q) use ($schedule) {
            $q->where('schedule_id', $schedule->id);
        })->with([
            'assessment.asesi',
            'assessment.assessor',
            'assessment.schedule.scheme.competencyUnits',
            'assessment.ak03Item',
            'decider',
        ])->latest()->get();

        $ak03Statements = [
            1 => 'Saya mendapatkan penjelasan yang cukup memadai mengenai proses asesmen / uji kompetensi.',
            2 => 'Saya diberikan kesempatan untuk mempelajari standar kompetensi yang akan diujikan dan menilai diri sendiri terhadap pencapaiannya.',
            3 => 'Asesor memberikan kesempatan untuk mendiskusikan / menegosiasikan metoda, instrumen, dan sumber asesmen serta jadwal uji.',
            4 => 'Asesor berusaha menggali seluruh bukti pendukung yang sesuai dengan latar belakang pelatihan dan pengalaman saya.',
            5 => 'Saya sepenuhnya diberikan kesempatan untuk mendemonstrasikan kompetensi yang saya miliki selama asesmen berlangsung.',
            6 => 'Saya mendapatkan penjelasan yang memadai mengenai keputusan asesmen (K / BK).',
            7 => 'Asesor memberikan umpan balik yang membangun dan mendukung setelah asesmen serta arahan tindak lanjutnya.',
            8 => 'Asesor bersama saya mempelajari semua dokumen asesmen serta menandatanganinya secara transparan.',
            9 => 'Saya mendapatkan jaminan kerahasiaan hasil asesmen serta penjelasan mengenai penanganan berkas portofolio.',
            10 => 'Asesor menggunakan keterampilan komunikasi yang efektif, santun, dan profesional selama asesmen.',
        ];

        $defaultTab = $scheduleAppeals->isNotEmpty() && $scheduleAppeals->whereIn('status', ['SUBMITTED', 'UNDER_REVIEW'])->isNotEmpty() ? 'appeals' : 'feedbacks';
        $activeTab = $request->query('tab', $defaultTab);

        return view('admin.appeals.show', compact(
            'schedule',
            'scheduleAppeals',
            'schemeUnits',
            'schemeUnitsById',
            'ak03Statements',
            'activeTab'
        ));
    }

    public function decide(AssessmentAppeal $appeal, Request $request): RedirectResponse
    {
        $request->validate([
            'decision' => 'required|in:ACCEPTED,REJECTED',
            'decision_notes' => 'nullable|string',
            'appeals_committee_chair' => 'nullable|string|max:255',
            'action_type' => 'nullable|string',
        ]);

        $decision = $request->input('decision');
        $actionType = $request->input('action_type') ?: ($decision === 'ACCEPTED' ? 'REASSESSMENT' : 'REJECTED');
        $defaultNote = $decision === 'ACCEPTED' ? 'Disetujui untuk asesmen ulang.' : 'Permohonan banding ditolak / Tidak dilanjutkan asesmen ulang.';
        $decisionNotes = $request->input('decision_notes') ?: $defaultNote;

        $this->workflowService->decideAppeal(
            $appeal,
            $decision,
            $decisionNotes,
            auth()->id(),
            $request->input('appeals_committee_chair'),
            $actionType
        );

        $msg = $decision === 'ACCEPTED'
            ? 'Banding disetujui. Jadwal asesmen untuk asesi telah dibuka kembali dan data direset untuk asesmen ulang.'
            : 'Banding ditolak. Keputusan hasil asesmen tetap dipertahankan.';

        return back()->with('success', $msg);
    }
}
