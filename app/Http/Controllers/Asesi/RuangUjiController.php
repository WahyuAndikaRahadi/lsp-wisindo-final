<?php

namespace App\Http\Controllers\Asesi;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\QuestionBank;
use App\Services\AkWorkflowService;
use App\Services\AutoGradeService;
use App\Services\SchemeInstrumentService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RuangUjiController extends Controller
{
    public function ak01(Assessment $assessment): View
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        $assessment->load('schedule.scheme');

        return view('asesi.ruang-uji.ak01', compact('assessment'));
    }

    public function signAk01(Assessment $assessment, Request $request): RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        $assessment->update([
            'last_active_step' => 'ruang-uji',
        ]);

        return redirect()->route('asesi.assessment.ruang-uji', $assessment)
            ->with('success', 'FR.AK01 berhasil ditandatangani.');
    }

    public function hub(Assessment $assessment): View
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        $assessment->load('schedule.scheme', 'schedule.assessors', 'iaResponses', 'assessor');
        $selectedInstruments = $assessment->schedule->getEffectiveInstruments();
        $responses = $assessment->iaResponses->keyBy('instrument_code');

        return view('asesi.ruang-uji.hub', compact('assessment', 'selectedInstruments', 'responses'));
    }

    public function downloadTemplate(Assessment $assessment, string $code, SchemeInstrumentService $instrumentService)
    {
        abort_if($assessment->asesi_id !== auth()->id() && auth()->user()->role !== 'ASESOR' && auth()->user()->role !== 'ADMIN_LSP', 403);

        $path = $instrumentService->getInstrumentTemplatePath($assessment->schedule->scheme, $code);

        if (! $path || ! file_exists($path)) {
            return back()->with('error', 'File template PDF resmi untuk instrumen FR.'.strtoupper($code).' belum tersedia.');
        }

        $cleanSchemeName = str_replace(['/', '\\'], '-', $assessment->schedule->scheme->name);
        $filename = 'FR.'.strtoupper($code).' - '.$cleanSchemeName.'.pdf';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function ia05(Assessment $assessment): View
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        $assessment->load('schedule.scheme');
        $questions = QuestionBank::where('scheme_id', $assessment->schedule->scheme_id)->get();

        return view('asesi.ruang-uji.ia05', compact('assessment', 'questions'));
    }

    public function submitIa05(Assessment $assessment, Request $request, AutoGradeService $grader): RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        $result = $grader->grade($assessment, $request->input('answers', []));

        $assessment->update(['last_active_step' => 'ruang-uji']);

        return redirect()->route('asesi.assessment.ruang-uji', $assessment)
            ->with('success', "Jawaban tersimpan. Skor: {$result->score}");
    }

    public function iaView(Assessment $assessment, $code, SchemeInstrumentService $instrumentService): View
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        $assessment->load('schedule.scheme.competencyUnits.elements.kuks', 'iaResponses', 'assessor');
        $codeUpper = strtoupper($code);
        $response = $assessment->iaResponses->firstWhere('instrument_code', $codeUpper);
        $scheme = $assessment->schedule->scheme;
        $reportSheetItems = $instrumentService->getReportSheetItems($scheme);
        $tpdData = $instrumentService->getTpdData($scheme);
        $pmoQuestions = $instrumentService->getPmoQuestions($scheme);
        $units = $scheme->competencyUnits;

        return view('asesi.ruang-uji.ia-view', compact('assessment', 'code', 'codeUpper', 'response', 'scheme', 'reportSheetItems', 'tpdData', 'pmoQuestions', 'units'));
    }

    public function submitFeedback(Assessment $assessment, $code, Request $request): RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        $codeUpper = strtoupper($code);
        $response = AssessmentIaResponse::firstOrNew([
            'assessment_id' => $assessment->id,
            'instrument_code' => $codeUpper,
        ]);

        $dataJson = is_array($response->data_json)
            ? $response->data_json
            : (json_decode($response->data_json ?? '[]', true) ?: []);

        if ($request->hasFile('evidence_image')) {
            $request->validate([
                'evidence_image' => 'required|file|mimes:jpeg,jpg,png,webp,pdf|max:10240',
            ]);
            $path = $request->file('evidence_image')->store('ia02_evidence', 'public');
            $dataJson['evidence_image_path'] = $path;
            $dataJson['evidence_uploaded_at'] = now()->toIso8601String();
            $dataJson['evidence_original_name'] = $request->file('evidence_image')->getClientOriginalName();
        }

        $response->data_json = $dataJson;

        if ($request->filled('asesi_feedback')) {
            $response->asesi_feedback = $request->input('asesi_feedback');
        }

        $signature = $request->input('signature_data') ?? $request->input('asesi_signature');
        if ($signature) {
            $response->asesi_signature = $signature;
            $response->asesi_signed_at = now();
            if (empty($assessment->asesi_signature)) {
                $assessment->update([
                    'asesi_signature' => $signature,
                    'asesi_signed_at' => now(),
                ]);
            }
        }

        $response->save();

        return back()->with('success', 'Data tanggapan instrumen FR.'.$codeUpper.' berhasil disimpan.');
    }

    public function uploadEvidence(Assessment $assessment, $code, Request $request): RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        $request->validate([
            'evidence_image' => 'required|file|mimes:jpeg,jpg,png,webp,pdf|max:10240',
        ]);

        $codeUpper = strtoupper($code);
        $path = $request->file('evidence_image')->store('ia02_evidence', 'public');

        $response = AssessmentIaResponse::firstOrNew([
            'assessment_id' => $assessment->id,
            'instrument_code' => $codeUpper,
        ]);

        $dataJson = is_array($response->data_json)
            ? $response->data_json
            : (json_decode($response->data_json ?? '[]', true) ?: []);

        $dataJson['evidence_image_path'] = $path;
        $dataJson['evidence_uploaded_at'] = now()->toIso8601String();
        $dataJson['evidence_original_name'] = $request->file('evidence_image')->getClientOriginalName();

        $response->data_json = $dataJson;
        $response->save();

        return back()->with('success', 'Foto bukti lembar kerja fisik berhasil diunggah.');
    }

    public function finish(Assessment $assessment): RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        $assessment->update([
            'status' => 'COMPLETED',
            'completed_at' => now(),
        ]);

        return redirect()->route('asesi.assessment.ak03', $assessment)
            ->with('success', 'Asesmen teknis selesai. Harap mengisi formulir FR.AK.03 (Umpan Balik Asesi).');
    }

    public function showAk03(Assessment $assessment): View
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        $assessment->load('schedule.scheme', 'assessor', 'ak03Item');
        $ak03 = $assessment->ak03Item;

        return view('asesi.ruang-uji.ak03', compact('assessment', 'ak03'));
    }

    public function submitAk03(Assessment $assessment, Request $request): RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        $validated = $request->validate([
            'ratings' => 'required|array|min:10',
            'ratings.*.value' => 'required|in:YA,TIDAK',
            'ratings.*.comment' => 'nullable|string|max:500',
            'general_notes' => 'nullable|string|max:1000',
        ], [
            'ratings.required' => 'Seluruh butir evaluasi FR.AK.03 wajib diisi.',
            'ratings.min' => 'Seluruh 10 butir pernyataan evaluasi wajib dipilih.',
            'ratings.*.value.required' => 'Setiap butir pertanyaan evaluasi wajib dipilih (Ya atau Tidak).',
        ]);

        app(AkWorkflowService::class)->submitAk03Feedback($assessment, $validated['ratings'], $validated['general_notes'] ?? null);

        return redirect()->route('asesi.jadwal.index')
            ->with('success', 'Formulir FR.AK.03 Umpan Balik Asesi berhasil dikirim.');
    }

    public function showAppeal(Assessment $assessment): View
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        $assessment->load('schedule.scheme.competencyUnits', 'assessor', 'appeals');
        $units = $assessment->schedule->scheme->competencyUnits;
        $appeal = $assessment->latestAppeal;

        $workflowService = app(AkWorkflowService::class);
        $appealWindowDays = $workflowService->getAppealWindowDays();
        $baseDate = $assessment->completed_at
            ? Carbon::parse($assessment->completed_at)
            : ($assessment->schedule?->date ? Carbon::parse($assessment->schedule->date) : now());
        $deadline = $workflowService->calculateAppealDeadline($baseDate);
        $isWithinDeadline = $workflowService->isWithinAppealDeadline($assessment);

        return view('asesi.ruang-uji.ak04-appeal', compact('assessment', 'units', 'appeal', 'appealWindowDays', 'deadline', 'isWithinDeadline'));
    }

    public function submitAppeal(Assessment $assessment, Request $request): RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        $request->validate([
            'appeal_reasons' => 'required|string|min:10',
            'contested_units' => 'nullable|array',
        ]);

        app(AkWorkflowService::class)->submitAppeal(
            $assessment,
            $request->input('appeal_reasons'),
            $request->input('contested_units', [])
        );

        return redirect()->route('asesi.jadwal.index')
            ->with('success', 'Pengajuan Banding Asesmen (FR.AK.04) berhasil dikirimkan ke Admin LSP.');
    }
}
