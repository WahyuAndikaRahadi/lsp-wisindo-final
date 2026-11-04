<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Schedule;
use App\Services\MukDynamicDocumentService;
use App\Services\MukExportService;
use App\Services\MukFixedDocumentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DokumenMukController extends Controller
{
    public function __construct(
        private MukExportService $exporter,
        private MukFixedDocumentService $fixedService,
        private MukDynamicDocumentService $dynamicService,
    ) {}

    public function index(): View
    {
        $schedules = Schedule::with('scheme', 'assessments')
            ->latest('date')
            ->paginate(15);

        return view('admin.dokumen-muk.index', compact('schedules'));
    }

    public function exportSchedule(Schedule $schedule, string $type, ?Request $request = null)
    {
        $request = $request ?? request();
        if ($request->query('format') === 'pdf' && $request->query('source') === 'fixed' || $request->boolean('fixed')) {
            return $this->exportFixed($schedule, $type, $request);
        }

        $normalizedType = strtolower(str_replace(['_', ' '], '-', $type));
        $aliasMap = [
            'mapa01' => 'mapa',
            'mapa1' => 'mapa',
            'fr-mapa-01' => 'mapa',
            'fr-mapa-02' => 'mapa02',
            'ia07' => 'ia07',
            'fr-ia-07' => 'ia07',
            'ia11' => 'ia11',
            'fr-ia-11' => 'ia11',
            'daftar-periksa' => 'daftar-periksa',
        ];
        $targetType = $aliasMap[$normalizedType] ?? $normalizedType;

        if (in_array($targetType, ['excel-biodata', 'excel', 'xls', 'biodata-excel', 'asesi-excel'], true)) {
            return $this->exporter->exportScheduleAsesiXls($schedule);
        }

        if (in_array($targetType, ['excel-asesor', 'asesor-excel', 'hadir-asesor-excel'], true)) {
            return $this->exporter->exportScheduleAsesorXls($schedule);
        }

        if ($request->query('source') === 'dynamic' || ($request->query('format') === 'pdf' && in_array($targetType, ['ba', 'hadir-asesor', 'hadir-asesi'], true))) {
            return $this->dynamicService->exportSchedule($schedule, $targetType, $request->boolean('download'));
        }

        if ($request->query('format') === 'pdf' || $request->query('source') === 'fixed') {
            return $this->exportFixed($schedule, $type, $request);
        }

        $data = $this->exporter->getExportData($schedule);
        $data['exportType'] = $targetType;

        $viewName = "admin.dokumen-muk.export-{$targetType}";
        if (view()->exists($viewName)) {
            return view($viewName, $data);
        }

        $asesiViewName = "admin.dokumen-muk.export-asesi-{$targetType}";
        if (view()->exists($asesiViewName)) {
            $firstAssessment = $schedule->assessments->first();
            if ($firstAssessment) {
                $asesiData = $this->exporter->getAsesiExportData($firstAssessment);
                $asesiData['exportType'] = $targetType;

                return view($asesiViewName, $asesiData);
            }
        }

        return view('admin.dokumen-muk.preview-schedule', $data);
    }

    public function exportAsesi(Assessment $assessment, string $type = 'all', ?Request $request = null)
    {
        $request = $request ?? request();
        if ($request->query('format') === 'pdf' && $request->query('source') === 'fixed' || $request->boolean('fixed')) {
            return $this->exportAsesiFixed($assessment, $type, $request);
        }

        $normalizedType = strtolower(str_replace(['_', ' '], '-', $type));
        $aliasMap = [
            'mapa' => 'mapa01',
            'mapa1' => 'mapa01',
            'fr-mapa-01' => 'mapa01',
            'fr-mapa-02' => 'mapa02',
            'ia05' => 'ia05a',
            'fr-ia-01' => 'ia01',
            'fr-ia-01b' => 'ia01b',
            'fr-ia-02' => 'ia02',
            'fr-ia-03' => 'ia03',
            'fr-ia-05a' => 'ia05a',
            'fr-ia-05b' => 'ia05b',
            'fr-ia-05c' => 'ia05c',
            'fr-ia-07' => 'ia07',
            'fr-ia-11' => 'ia11',
            'fr-ak-01' => 'ak01',
            'fr-ak-02' => 'ak02',
            'fr-ak-03' => 'ak03',
            'fr-ak-04' => 'ak04',
            'fr-ak-05' => 'ak05',
            'fr-ak-06' => 'ak06',
            'fr-ak-07' => 'ak07',
            'ak7' => 'ak07',
            'ak-07' => 'ak07',
            'apl1' => 'apl01',
            'apl-01' => 'apl01',
            'fr-apl-01' => 'apl01',
            'apl2' => 'apl02',
            'apl-02' => 'apl02',
            'fr-apl-02' => 'apl02',
        ];
        $targetType = $aliasMap[$normalizedType] ?? $normalizedType;

        if (in_array($targetType, ['excel-biodata', 'excel', 'xls', 'biodata-excel', 'asesi-excel'], true)) {
            return $this->exporter->exportAssessmentAsesiXls($assessment);
        }

        $dynamicTypes = ['apl01', 'apl02', 'ak01', 'ak02', 'ak03', 'ak04', 'ak07', 'ia01', 'ia01b', 'ia02', 'ia03', 'ia05c', 'pernyataan'];
        if ($targetType === 'ia01b' || $request->query('source') === 'dynamic' || ($request->query('format') === 'pdf' && in_array($targetType, $dynamicTypes, true))) {
            return $this->dynamicService->exportAssessment($assessment, $targetType, $request->boolean('download'));
        }

        if ($request->query('format') === 'pdf' || $request->query('source') === 'fixed') {
            return $this->exportAsesiFixed($assessment, $type, $request);
        }

        $data = $this->exporter->getAsesiExportData($assessment);
        $data['exportType'] = $targetType;

        $viewName = "admin.dokumen-muk.export-asesi-{$targetType}";
        if (view()->exists($viewName)) {
            return view($viewName, $data);
        }

        $scheduleView = "admin.dokumen-muk.export-{$targetType}";
        if (view()->exists($scheduleView)) {
            $data['assessors'] = $assessment->schedule?->assessors ?? collect();
            $data['leadAssessor'] = $data['assessor'] ?? $data['assessors']->first();

            return view($scheduleView, $data);
        }

        return view('admin.dokumen-muk.preview-asesi', $data);
    }

    public function exportSertifikat(Schedule $schedule)
    {
        return $this->exportSchedule($schedule, 'sertifikat');
    }

    public function exportBa(Schedule $schedule)
    {
        return $this->dynamicService->exportSchedule($schedule, 'ba', request()->boolean('download'));
    }

    public function exportHadirAsesor(Schedule $schedule)
    {
        return $this->dynamicService->exportSchedule($schedule, 'hadir-asesor', request()->boolean('download'));
    }

    public function exportHadirAsesi(Schedule $schedule)
    {
        return $this->dynamicService->exportSchedule($schedule, 'ba', request()->boolean('download'));
    }

    public function exportExcelBiodata(Schedule $schedule)
    {
        return $this->exporter->exportScheduleAsesiXls($schedule);
    }

    public function exportIndividual(Assessment $assessment, Request $request)
    {
        return $this->exportAsesi($assessment, $request->query('type', 'all'), $request);
    }

    /**
     * Export dynamic PDF with candidate and schedule data.
     */
    public function exportDynamic(Schedule $schedule, string $type, Request $request)
    {
        $download = $request->boolean('download', false);

        return $this->dynamicService->exportSchedule($schedule, $type, $download);
    }

    /**
     * Export dynamic PDF with candidate assessment data.
     */
    public function exportAsesiDynamic(Assessment $assessment, string $type, Request $request)
    {
        $download = $request->boolean('download', false);

        return $this->dynamicService->exportAssessment($assessment, $type, $download);
    }

    /**
     * Export fixed static original PDF directly from /lsp-document-fixed based on schedule's scheme.
     */
    public function exportFixed(Schedule $schedule, string $type, Request $request)
    {
        $download = $request->boolean('download', false);

        return $this->fixedService->exportSchedule($schedule, $type, $download);
    }

    /**
     * Export fixed static original PDF directly from /lsp-document-fixed for individual candidate's scheme.
     */
    public function exportAsesiFixed(Assessment $assessment, string $type, Request $request)
    {
        $download = $request->boolean('download', false);

        return $this->fixedService->exportAssessment($assessment, $type, $download);
    }
}
