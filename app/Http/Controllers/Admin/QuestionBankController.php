<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionBank;
use App\Models\Scheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class QuestionBankController extends Controller
{
    public function index(Request $request): View
    {
        $selectedSchemeId = $request->query('scheme_id');
        $search = $request->query('search');

        $schemes = Scheme::active()
            ->withCount('questionBanks')
            ->get();

        $questionBanks = $schemes->map(function ($scheme) {
            return (object) [
                'id' => $scheme->id,
                'title' => "Paket Soal CBT - {$scheme->name}",
                'code' => "BANK-{$scheme->code}",
                'scheme' => $scheme,
                'type' => 'IA05 (CBT)',
                'questions_count' => $scheme->question_banks_count,
                'status' => $scheme->is_active ? 'ACTIVE' : 'INACTIVE',
            ];
        });

        $questionsQuery = QuestionBank::with('scheme', 'competencyUnit')->latest();
        if ($selectedSchemeId) {
            $questionsQuery->where('scheme_id', $selectedSchemeId);
        }
        if ($search) {
            $questionsQuery->where('question_text', 'like', "%{$search}%");
        }

        $questions = $questionsQuery->paginate(15)->withQueryString();

        return view('admin.question-banks.index', compact('questionBanks', 'questions', 'schemes', 'selectedSchemeId', 'search'));
    }

    public function show(QuestionBank $questionBank): View
    {
        $questionBank->load('scheme', 'competencyUnit');

        return view('admin.question-banks.show', compact('questionBank'));
    }

    public function create(): View
    {
        $schemes = Scheme::active()->with('competencyUnits')->get();

        return view('admin.question-banks.create', compact('schemes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scheme_id' => 'required|exists:m_schemes,id',
            'competency_unit_id' => 'nullable|exists:m_competency_units,id',
            'question_text' => 'required|string',
            'question_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:2048',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'required|string',
            'option_d' => 'required|string',
            'correct_answer' => 'required|in:A,B,C,D',
        ]);

        if ($request->hasFile('question_image')) {
            $validated['question_image'] = $request->file('question_image')->store('question-images', 'public');
        }

        QuestionBank::create($validated);

        return redirect()->route('admin.question-banks.index')
            ->with('success', 'Soal berhasil dibuat.');
    }

    public function edit(QuestionBank $questionBank): View
    {
        $schemes = Scheme::active()->with('competencyUnits')->get();

        return view('admin.question-banks.edit', compact('questionBank', 'schemes'));
    }

    public function update(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        $validated = $request->validate([
            'scheme_id' => 'required|exists:m_schemes,id',
            'competency_unit_id' => 'nullable|exists:m_competency_units,id',
            'question_text' => 'required|string',
            'question_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:2048',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'option_c' => 'required|string',
            'option_d' => 'required|string',
            'correct_answer' => 'required|in:A,B,C,D',
        ]);

        if ($request->hasFile('question_image')) {
            if ($questionBank->question_image && Storage::disk('public')->exists($questionBank->question_image)) {
                Storage::disk('public')->delete($questionBank->question_image);
            }
            $validated['question_image'] = $request->file('question_image')->store('question-images', 'public');
        } elseif ($request->boolean('remove_image')) {
            if ($questionBank->question_image && Storage::disk('public')->exists($questionBank->question_image)) {
                Storage::disk('public')->delete($questionBank->question_image);
            }
            $validated['question_image'] = null;
        }

        $questionBank->update($validated);

        return redirect()->route('admin.question-banks.show', $questionBank->id)
            ->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy(QuestionBank $questionBank): RedirectResponse
    {
        if ($questionBank->question_image && Storage::disk('public')->exists($questionBank->question_image)) {
            Storage::disk('public')->delete($questionBank->question_image);
        }

        $questionBank->delete();

        return redirect()->route('admin.question-banks.index')
            ->with('success', 'Soal berhasil dihapus.');
    }
}
