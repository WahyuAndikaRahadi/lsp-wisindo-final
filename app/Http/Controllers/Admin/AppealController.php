<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

    public function index(): View
    {
        $appeals = AssessmentAppeal::with(['assessment.asesi', 'assessment.schedule.scheme', 'decider'])
            ->latest()
            ->paginate(15);

        return view('admin.appeals.index', compact('appeals'));
    }

    public function decide(AssessmentAppeal $appeal, Request $request): RedirectResponse
    {
        $request->validate([
            'decision' => 'required|in:ACCEPTED,REJECTED',
            'decision_notes' => 'nullable|string',
        ]);

        $this->workflowService->decideAppeal(
            $appeal,
            $request->input('decision'),
            $request->input('decision_notes'),
            auth()->id()
        );

        return back()->with('success', 'Keputusan banding asesmen (FR.AK.04) berhasil disimpan.');
    }
}
