<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InterviewQuestion;
use App\Models\Scheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InterviewQuestionController extends Controller
{
    public function index(Request $request): View
    {
        $schemes = Scheme::active()
            ->withCount('interviewQuestions')
            ->orderBy('id')
            ->get();

        $selectedSchemeId = (int) $request->input('scheme_id', $schemes->first()?->id);
        $selectedScheme = $schemes->firstWhere('id', $selectedSchemeId) ?? $schemes->first();

        $questions = collect();
        if ($selectedScheme) {
            $questions = InterviewQuestion::where('scheme_id', $selectedScheme->id)
                ->ordered()
                ->get();
        }

        return view('admin.interview-questions.index', compact(
            'schemes',
            'selectedScheme',
            'questions'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scheme_id' => 'required|exists:m_schemes,id',
            'competency_unit_id' => 'nullable|exists:m_competency_units,id',
            'order' => 'nullable|integer|min:1',
            'evidence_context' => 'nullable|string|max:255',
            'question_text' => 'required|string',
            'expected_answer' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['order'])) {
            $maxOrder = InterviewQuestion::where('scheme_id', $validated['scheme_id'])->max('order') ?? 0;
            $validated['order'] = $maxOrder + 1;
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        if (empty($validated['evidence_context'])) {
            $validated['evidence_context'] = 'Sesuai dengan bukti no. …… yang Anda ajukan';
        }

        InterviewQuestion::create($validated);

        return redirect()->route('admin.interview-questions.index', ['scheme_id' => $validated['scheme_id']])
            ->with('success', 'Pertanyaan wawancara baru berhasil ditambahkan.');
    }

    public function update(Request $request, InterviewQuestion $interviewQuestion): RedirectResponse
    {
        $validated = $request->validate([
            'competency_unit_id' => 'nullable|exists:m_competency_units,id',
            'order' => 'required|integer|min:1',
            'evidence_context' => 'nullable|string|max:255',
            'question_text' => 'required|string',
            'expected_answer' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $interviewQuestion->update($validated);

        return redirect()->route('admin.interview-questions.index', ['scheme_id' => $interviewQuestion->scheme_id])
            ->with('success', 'Pertanyaan wawancara berhasil diperbarui.');
    }

    public function destroy(InterviewQuestion $interviewQuestion): RedirectResponse
    {
        $schemeId = $interviewQuestion->scheme_id;
        $interviewQuestion->delete();

        return redirect()->route('admin.interview-questions.index', ['scheme_id' => $schemeId])
            ->with('success', 'Pertanyaan wawancara berhasil dihapus.');
    }
}
