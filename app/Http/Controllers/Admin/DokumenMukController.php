<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Schedule;
use App\Models\Scheme;
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

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $schemeId = $request->query('scheme_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Schedule::with(['scheme', 'assessors'])
            ->withCount('assessments');

        $user = $request->user();
        if ($user && $user->role === 'TUK_ADMIN') {
            $user->loadMissing('tuk');
            $query->where(function ($q) use ($user) {
                if ($user->tuk_id) {
                    $q->where('tuk_id', $user->tuk_id);
                }
                if ($user->tuk?->name) {
                    $q->orWhere('tuk_name', $user->tuk->name);
                }
            });
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('tuk_name', 'like', "%{$search}%")
                    ->orWhereHas('scheme', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($schemeId)) {
            $query->where('scheme_id', $schemeId);
        }

        if (! empty($startDate) && ! empty($endDate)) {
            $from = min($startDate, $endDate);
            $to = max($startDate, $endDate);
            $query->whereBetween('date', [$from, $to]);
        } elseif (! empty($startDate)) {
            $query->whereDate('date', '>=', $startDate);
        } elseif (! empty($endDate)) {
            $query->whereDate('date', '<=', $endDate);
        }

        $schedules = $query->latest('date')
            ->paginate(15)
            ->withQueryString();

        $schemes = Scheme::orderBy('name')->get();

        if ($request->ajax()) {
            return view('admin.dokumen-muk._table', compact('schedules', 'search', 'schemeId', 'startDate', 'endDate'));
        }

        return view('admin.dokumen-muk.index', compact('schedules', 'schemes', 'search', 'schemeId', 'startDate', 'endDate'));
    }

    public function show(Schedule $schedule, Request $request): View
    {
        $schedule->loadMissing(['scheme', 'assessors']);

        $search = trim((string) $request->query('search', ''));

        $assessmentsQuery = $schedule->assessments()
            ->with(['asesi.profile', 'iaResponses', 'assessor']);

        if ($search !== '') {
            $assessmentsQuery->whereHas('asesi', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('profile', function ($pq) use ($search) {
                        $pq->where('nik', 'like', "%{$search}%");
                    });
            });
        }

        $assessments = $assessmentsQuery->paginate(25)->withQueryString();

        if ($request->ajax()) {
            return view('admin.dokumen-muk._peserta_table', compact('schedule', 'assessments', 'search'));
        }

        return view('admin.dokumen-muk.show', compact('schedule', 'assessments', 'search'));
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
            'fr-ak-05' => 'ak05',
            'frak05' => 'ak05',
            'laporan-asesmen' => 'ak05',
            'laporan' => 'ak05',
            'fr-ak-06' => 'ak06',
            'frak06' => 'ak06',
            'tinjauan-asesmen' => 'ak06',
            'tinjauan' => 'ak06',
            'meninjau' => 'ak06',
        ];
        $targetType = $aliasMap[$normalizedType] ?? $normalizedType;

        if (in_array($targetType, ['excel-biodata', 'excel', 'xls', 'biodata-excel', 'asesi-excel'], true)) {
            return $this->exporter->exportScheduleAsesiXls($schedule);
        }

        if (in_array($targetType, ['excel-asesor', 'asesor-excel', 'hadir-asesor-excel'], true)) {
            return $this->exporter->exportScheduleAsesorXls($schedule);
        }

        if ($targetType === 'ak06' && ($request->query('format') === 'docx' || $request->query('source') === 'docx')) {
            $ak06Data = is_array($schedule->ak06_review) ? $schedule->ak06_review : [];
            $templateType = $ak06Data['template_type'] ?? 'langsung';
            if (! empty($ak06Data['by_assessor']) && is_array($ak06Data['by_assessor'])) {
                $first = reset($ak06Data['by_assessor']);
                $templateType = $first['template_type'] ?? $templateType;
            }

            $baseName = ($templateType === 'tidak_langsung')
                ? '24 FR.AK.06 Meninjau Proses Asesmen (Tidak Langsung)'
                : '24 FR.AK.06 Meninjau Proses Asesmen (Langsung)';

            $filePath = public_path("AK06/{$baseName}.docx");
            if (file_exists($filePath)) {
                return response()->download($filePath, "{$baseName}.docx");
            }
        }

        if (in_array($targetType, ['ak05', 'ak06', 'mapa', 'mapa01'], true) || $request->query('source') === 'dynamic' || ($request->query('format') === 'pdf' && in_array($targetType, ['ba', 'hadir-asesor', 'hadir-asesi', 'mapa', 'mapa01'], true))) {
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
        $user = $request->user();
        if ($user && $user->role === 'ASESI' && $assessment->asesi_id !== $user->id) {
            abort(403, 'Anda tidak memiliki hak akses ke dokumen asesi lain.');
        }

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
            'fr-ia-08' => 'ia08',
            'ia-08' => 'ia08',
            'ia08' => 'ia08',
            'fr-ia-09' => 'ia09',
            'ia-09' => 'ia09',
            'ia09' => 'ia09',
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

        if (in_array($targetType, ['all-muk', 'all', 'allmuk', 'semua-muk', 'semuamuk', 'print-semua', 'cetak-semua'], true)) {
            return $this->dynamicService->exportUnifiedAsesiMuk($assessment, $request->boolean('download'));
        }

        if (in_array($targetType, ['excel-biodata', 'excel', 'xls', 'biodata-excel', 'asesi-excel'], true)) {
            return $this->exporter->exportAssessmentAsesiXls($assessment);
        }

        $dynamicTypes = ['apl01', 'apl02', 'ak01', 'ak02', 'ak03', 'ak04', 'ak05', 'ak07', 'ia01', 'ia01b', 'ia02', 'ia03', 'ia05a', 'ia05c', 'pernyataan', 'mapa', 'mapa01', 'frmapa01'];
        if (in_array($targetType, ['ia01b', 'ak05', 'mapa', 'mapa01', 'frmapa01'], true) || $request->query('source') === 'dynamic' || ($request->query('format') === 'pdf' && in_array($targetType, $dynamicTypes, true))) {
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
        if (request()->query('format') === 'html' || request()->query('source') === 'html') {
            return $this->exportSchedule($schedule, 'ba');
        }

        return $this->dynamicService->exportSchedule($schedule, 'ba', request()->boolean('download'));
    }

    public function exportHadirAsesor(Schedule $schedule)
    {
        if (request()->query('format') === 'html' || request()->query('source') === 'html') {
            return $this->exportSchedule($schedule, 'hadir-asesor');
        }

        return $this->dynamicService->exportSchedule($schedule, 'hadir-asesor', request()->boolean('download'));
    }

    public function exportHadirAsesi(Schedule $schedule)
    {
        if (request()->query('format') === 'html' || request()->query('source') === 'html') {
            return $this->exportSchedule($schedule, 'hadir-asesi');
        }

        return $this->dynamicService->exportSchedule($schedule, 'hadir-asesi', request()->boolean('download'));
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
        $user = $request->user();
        if ($user && $user->role === 'ASESI' && $assessment->asesi_id !== $user->id) {
            abort(403, 'Anda tidak memiliki hak akses ke dokumen asesi lain.');
        }

        $download = $request->boolean('download', false);
        $normType = strtolower(str_replace(['_', ' ', '-', '.'], '', $type));
        if (in_array($normType, ['all', 'allmuk', 'semuamuk'], true)) {
            return $this->dynamicService->exportUnifiedAsesiMuk($assessment, $download);
        }

        return $this->dynamicService->exportAssessment($assessment, $type, $download);
    }

    /**
     * Export fixed static original PDF directly from /lsp-document-fixed based on schedule's scheme.
     */
    public function exportFixed(Schedule $schedule, string $type, Request $request)
    {
        $download = $request->boolean('download', false);
        $normType = strtolower(str_replace(['_', ' ', '-', '.'], '', $type));
        if (in_array($normType, ['ia05c', 'fria05c'], true)) {
            return $this->dynamicService->exportSchedule($schedule, $normType, $download);
        }

        return $this->fixedService->exportSchedule($schedule, $type, $download);
    }

    /**
     * Export fixed static original PDF directly from /lsp-document-fixed for individual candidate's scheme.
     */
    public function exportAsesiFixed(Assessment $assessment, string $type, Request $request)
    {
        $user = $request->user();
        if ($user && $user->role === 'ASESI' && $assessment->asesi_id !== $user->id) {
            abort(403, 'Anda tidak memiliki hak akses ke dokumen asesi lain.');
        }

        $download = $request->boolean('download', false);
        $normType = strtolower(str_replace(['_', ' ', '-', '.'], '', $type));
        if (in_array($normType, ['ia05c', 'fria05c'], true)) {
            return $this->dynamicService->exportAssessment($assessment, $normType, $download);
        }

        return $this->fixedService->exportAssessment($assessment, $type, $download);
    }
}
