<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAk02Item;
use App\Models\Schedule;
use App\Services\AkWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $finalResult = $request->input('final_result', 'KOMPETEN');
        $assessment->update([
            'final_result' => $finalResult,
            'final_notes' => $request->input('final_notes'),
            'follow_up_action' => $request->input('follow_up_action'),
            'assessor_signature' => $request->input('assessor_signature') ?? $assessment->assessor_signature,
            'assessor_signed_at' => now(),
            'status' => 'COMPLETED',
            'completed_at' => now(),
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
        $ak05 = [
            'aspek_positif' => $request->input('ak05_aspek_positif', 'Kesiapan sarana TUK dan kepatuhan APD/K3 asesi sangat baik.'),
            'aspek_negatif' => $request->input('ak05_aspek_negatif', 'Tidak ada kendala teknis berarti selama pelaksanaan asesmen.'),
            'rekomendasi' => $request->input('ak05_rekomendasi', 'Mempertahankan ketersediaan SST terkalibrasi secara berkala.'),
        ];

        $ak06 = [
            'valid' => $request->input('ak06_valid', 'YA'),
            'valid_notes' => $request->input('ak06_valid_notes', 'Bukti dikumpulkan mencakup seluruh elemen kompetensi.'),
            'reliabel' => $request->input('ak06_reliabel', 'YA'),
            'reliabel_notes' => $request->input('ak06_reliabel_notes', 'Penerapan rubrik penilaian objektif dan konsisten.'),
            'fleksibel' => $request->input('ak06_fleksibel', 'YA'),
            'fleksibel_notes' => $request->input('ak06_fleksibel_notes', 'Penyesuaian wajar diterapkan sesuai kondisi asesi.'),
            'adil' => $request->input('ak06_adil', 'YA'),
            'adil_notes' => $request->input('ak06_adil_notes', 'Tidak terdapat diskriminasi.'),
            'catatan_peninjau' => $request->input('ak06_catatan_peninjau', 'Proses asesmen berjalan tertib dan sesuai panduan mutu BNSP.'),
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
