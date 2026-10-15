<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAk02Item;
use App\Models\Schedule;
use App\Services\AkWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class FinalizationController extends Controller
{
    public function __construct(
        protected AkWorkflowService $workflowService
    ) {}

    /**
     * Complete individual candidate assessment & auto-generate AK.02.
     */
    public function completeAssessment(Assessment $assessment): RedirectResponse
    {
        $this->workflowService->syncAk02($assessment);

        $assessment->update([
            'status' => 'COMPLETED',
            'completed_at' => now(),
            'assessor_id' => auth()->id(),
            'assessor_signed_at' => now(),
        ]);

        return back()->with('success', "Asesmen asesi {$assessment->asesi->full_name} berhasil diselesaikan dan FR.AK.02 telah direkapitulasi.");
    }

    /**
     * View FR.AK.02 Rekaman Asesmen for review and editing.
     */
    public function showAk02(Assessment $assessment): View
    {
        $assessment->load([
            'schedule.scheme.competencyUnits',
            'asesi.profile',
            'assessor',
            'ak02Items.competencyUnit',
            'iaResponses',
        ]);

        if ($assessment->ak02Items->isEmpty()) {
            $this->workflowService->syncAk02($assessment);
            $assessment->refresh();
        }

        $units = $assessment->schedule->scheme->competencyUnits;
        $ak02Items = $assessment->ak02Items->keyBy('competency_unit_id');

        return view('asesor.ak02', compact('assessment', 'units', 'ak02Items'));
    }

    /**
     * Update FR.AK.02 Rekaman Asesmen.
     */
    public function updateAk02(Assessment $assessment, Request $request): RedirectResponse
    {
        $unitsInput = $request->input('units', []);

        foreach ($unitsInput as $unitId => $data) {
            $methods = [
                'observasi_demonstrasi' => ! empty($data['observasi_demonstrasi']),
                'portofolio' => ! empty($data['portofolio']),
                'pihak_ketiga' => ! empty($data['pihak_ketiga']),
                'lisan' => ! empty($data['lisan']),
                'tulis' => ! empty($data['tulis']),
                'proyek' => ! empty($data['proyek']),
                'lainnya' => ! empty($data['lainnya']),
            ];

            AssessmentAk02Item::updateOrCreate(
                [
                    'assessment_id' => $assessment->id,
                    'competency_unit_id' => $unitId,
                ],
                [
                    'methods_json' => $methods,
                    'is_competent' => ! empty($data['is_competent']),
                ]
            );
        }

        $validated = $request->validate([
            'final_result' => 'required|in:K,BK,KOMPETEN,BELUM_KOMPETEN',
            'final_notes' => 'nullable|string|max:1000',
            'follow_up_action' => 'nullable|string|max:1000',
        ], [
            'final_result.required' => 'Rekomendasi hasil asesmen akhir (Kompeten atau Belum Kompeten) wajib dipilih secara eksplisit oleh asesor.',
        ]);

        $finalResult = in_array($validated['final_result'], ['K', 'KOMPETEN'], true) ? 'KOMPETEN' : 'BELUM_KOMPETEN';

        Log::info('Asesor ID '.auth()->id()." memperbarui FR.AK.02 asesmen #{$assessment->id} dengan hasil {$finalResult}");

        $now = now();
        $assessment->update([
            'final_result' => $finalResult,
            'final_result_at' => $now,
            'final_notes' => $validated['final_notes'] ?? null,
            'follow_up_action' => $validated['follow_up_action'] ?? null,
            'assessor_signature' => $request->input('assessor_signature') ?? $assessment->assessor_signature,
            'assessor_signed_at' => $now,
            'status' => 'COMPLETED',
            'completed_at' => $now,
        ]);

        return redirect()->route('asesor.jadwal.asesi', $assessment->schedule_id)
            ->with('success', 'FR.AK.02 Rekaman Asesmen berhasil diperbarui.');
    }

    /**
     * View FR.AK.05 (Laporan Asesmen) & FR.AK.06 (Tinjauan Proses).
     */
    public function showReports(Schedule $schedule): View
    {
        $schedule->load('scheme', 'assessors', 'assessments.asesi');

        return view('asesor.reports-ak05-ak06', compact('schedule'));
    }

    /**
     * Submit FR.AK.05 & FR.AK.06 and optionally close schedule.
     */
    public function submitReports(Schedule $schedule, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ak05_aspek_positif' => 'required|string|min:10',
            'ak05_aspek_negatif' => 'required|string|min:10',
            'ak05_rekomendasi' => 'required|string|min:10',
            'ak06_valid' => 'required|in:YA,TIDAK',
            'ak06_valid_notes' => 'required|string|min:5',
            'ak06_reliabel' => 'required|in:YA,TIDAK',
            'ak06_reliabel_notes' => 'required|string|min:5',
            'ak06_fleksibel' => 'required|in:YA,TIDAK',
            'ak06_fleksibel_notes' => 'required|string|min:5',
            'ak06_adil' => 'required|in:YA,TIDAK',
            'ak06_adil_notes' => 'required|string|min:5',
            'ak06_catatan_peninjau' => 'required|string|min:10',
            'confirm_draft_review' => 'accepted',
        ], [
            'ak05_aspek_positif.required' => 'Aspek positif laporan asesmen (FR.AK.05) wajib diisi.',
            'ak05_aspek_negatif.required' => 'Aspek negatif laporan asesmen (FR.AK.05) wajib diisi.',
            'ak05_rekomendasi.required' => 'Rekomendasi peningkatan mutu (FR.AK.05) wajib diisi.',
            'ak06_catatan_peninjau.required' => 'Catatan keseluruhan peninjau proses (FR.AK.06) wajib diisi.',
            'confirm_draft_review.accepted' => 'Anda wajib mencentang konfirmasi peninjauan draf laporan sebelum menyimpan dan menutup jadwal.',
        ]);

        $ak05 = [
            'aspek_positif' => $validated['ak05_aspek_positif'],
            'aspek_negatif' => $validated['ak05_aspek_negatif'],
            'rekomendasi' => $validated['ak05_rekomendasi'],
        ];

        $ak06 = [
            'valid' => $validated['ak06_valid'],
            'valid_notes' => $validated['ak06_valid_notes'],
            'reliabel' => $validated['ak06_reliabel'],
            'reliabel_notes' => $validated['ak06_reliabel_notes'],
            'fleksibel' => $validated['ak06_fleksibel'],
            'fleksibel_notes' => $validated['ak06_fleksibel_notes'],
            'adil' => $validated['ak06_adil'],
            'adil_notes' => $validated['ak06_adil_notes'],
            'catatan_peninjau' => $validated['ak06_catatan_peninjau'],
        ];

        $this->workflowService->submitScheduleReports($schedule, $ak05, $ak06);

        return redirect()->route('asesor.jadwal.index')
            ->with('success', 'FR.AK.05 Laporan Asesmen dan FR.AK.06 Tinjauan Proses berhasil disimpan. Jadwal resmi ditutup.');
    }

    /**
     * Finalize & close schedule.
     */
    public function finalize(Schedule $schedule): RedirectResponse
    {
        $readiness = $this->workflowService->isScheduleReadyForClosure($schedule);

        if (! $readiness['is_ready']) {
            return redirect()->route('asesor.jadwal.asesi', $schedule->id)
                ->with('error', "Jadwal belum dapat ditutup. Masih ada {$readiness['uncompleted_count']} asesi yang belum diselesaikan asesmennya.");
        }

        if (! $readiness['has_ak05'] || ! $readiness['has_ak06']) {
            return redirect()->route('asesor.schedule.reports', $schedule->id)
                ->with('info', 'Harap melengkapi FR.AK.05 (Laporan Asesmen) dan FR.AK.06 (Tinjauan Proses Asesmen) sebelum menutup jadwal asesmen.');
        }

        $schedule->update([
            'is_closed' => true,
            'closed_at' => now(),
            'status' => 'FINISHED',
        ]);

        return redirect()->route('asesor.jadwal.index')
            ->with('success', 'Jadwal asesmen telah resmi ditutup dan dikunci.');
    }
}
