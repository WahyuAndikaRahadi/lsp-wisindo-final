<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAk03Item;
use App\Models\AssessmentAppeal;
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
        $activeTab = $request->query('tab', 'appeals');

        $appeals = AssessmentAppeal::with([
            'assessment.asesi',
            'assessment.assessor',
            'assessment.schedule.scheme.competencyUnits',
            'assessment.ak03Item',
            'decider',
        ])
            ->latest()
            ->paginate(15, ['*'], 'appeals_page');

        $feedbacks = AssessmentAk03Item::with([
            'assessment.asesi',
            'assessment.assessor',
            'assessment.schedule.scheme',
            'assessment.appeals',
        ])
            ->latest()
            ->paginate(15, ['*'], 'feedbacks_page');

        $counts = [
            'appeals_total' => AssessmentAppeal::count(),
            'appeals_pending' => AssessmentAppeal::whereIn('status', ['SUBMITTED', 'UNDER_REVIEW'])->count(),
            'appeals_accepted' => AssessmentAppeal::where('status', 'ACCEPTED')->count(),
            'appeals_rejected' => AssessmentAppeal::where('status', 'REJECTED')->count(),
            'feedbacks_total' => AssessmentAk03Item::count(),
        ];

        if ($request->ajax()) {
            if ($request->has('feedbacks_page') || $activeTab === 'feedbacks') {
                return view('admin.appeals._feedbacks_table', compact('feedbacks'));
            }

            return view('admin.appeals._appeals_table', compact('appeals'));
        }

        return view('admin.appeals.index', compact('appeals', 'feedbacks', 'counts', 'activeTab'));
    }

    public function decide(AssessmentAppeal $appeal, Request $request): RedirectResponse
    {
        $request->validate([
            'decision' => 'required|in:ACCEPTED,REJECTED',
            'decision_notes' => 'required|string',
            'appeals_committee_chair' => 'nullable|string|max:255',
            'action_type' => 'nullable|in:REASSESSMENT,OVERRIDE_KOMPETEN,ADMINISTRATIVE',
        ]);

        $this->workflowService->decideAppeal(
            $appeal,
            $request->input('decision'),
            $request->input('decision_notes'),
            auth()->id(),
            $request->input('appeals_committee_chair'),
            $request->input('action_type', 'REASSESSMENT')
        );

        return back()->with('success', 'Keputusan banding asesmen (FR.AK.04) berhasil disimpan.');
    }
}
