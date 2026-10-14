<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Schedule;
use App\Services\MukExportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DokumenMukController extends Controller
{
    public function __construct(
        private MukExportService $exporter,
    ) {}

    public function index(): View
    {
        $schedules = Schedule::with('scheme', 'assessments')
            ->latest('date')
            ->paginate(15);

        return view('admin.dokumen-muk.index', compact('schedules'));
    }

    public function exportSchedule(Schedule $schedule, string $type)
    {
        $data = $this->exporter->getExportData($schedule);
        $data['exportType'] = $type;

        $viewName = "admin.dokumen-muk.export-{$type}";
        if (view()->exists($viewName)) {
            return view($viewName, $data);
        }

        return view('admin.dokumen-muk.preview-schedule', $data);
    }

    public function exportAsesi(Assessment $assessment, string $type = 'all')
    {
        $data = $this->exporter->getAsesiExportData($assessment);
        $data['exportType'] = $type;

        $viewName = "admin.dokumen-muk.export-asesi-{$type}";
        if (view()->exists($viewName)) {
            return view($viewName, $data);
        }

        return view('admin.dokumen-muk.preview-asesi', $data);
    }

    public function exportSertifikat(Schedule $schedule)
    {
        return $this->exportSchedule($schedule, 'sertifikat');
    }

    public function exportBa(Schedule $schedule)
    {
        return $this->exportSchedule($schedule, 'ba');
    }

    public function exportHadirAsesor(Schedule $schedule)
    {
        return $this->exportSchedule($schedule, 'hadir-asesor');
    }

    public function exportHadirAsesi(Schedule $schedule)
    {
        return $this->exportSchedule($schedule, 'hadir-asesi');
    }

    public function exportExcelBiodata(Schedule $schedule)
    {
        return $this->exportSchedule($schedule, 'excel-biodata');
    }

    public function exportIndividual(Assessment $assessment, Request $request)
    {
        return $this->exportAsesi($assessment, $request->query('type', 'all'));
    }
}
