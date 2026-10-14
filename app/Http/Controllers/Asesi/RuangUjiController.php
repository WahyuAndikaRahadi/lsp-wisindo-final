<?php

namespace App\Http\Controllers\Asesi;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\QuestionBank;
use App\Services\AutoGradeService;
use App\Services\SchemeInstrumentService;
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

        $assessment->load('schedule.scheme', 'iaResponses');

        return view('asesi.ruang-uji.hub', compact('assessment'));
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

        return redirect()->route('asesi.jadwal.index')
            ->with('success', 'Asesmen telah diselesaikan.');
    }
}
