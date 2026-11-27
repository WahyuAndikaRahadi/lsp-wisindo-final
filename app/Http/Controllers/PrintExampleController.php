<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Schedule;
use App\Services\MukDynamicDocumentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PrintExampleController extends Controller
{
    public function __construct(
        protected MukDynamicDocumentService $dynamicService
    ) {}

    protected function resolveTarget(Request $request): array
    {
        $assessment = null;
        $schedule = null;

        if ($request->query('assessment_id')) {
            $assessment = Assessment::with(['asesi.profile', 'schedule.scheme', 'schedule.assessors', 'assessor'])->find($request->query('assessment_id'));
            $schedule = $assessment?->schedule;
        }

        if (! $schedule && $request->query('schedule_id')) {
            $schedule = Schedule::with(['scheme', 'assessors', 'assessments.asesi.profile'])->find($request->query('schedule_id'));
        }

        if (! $schedule) {
            $schedule = Schedule::with(['scheme', 'assessors', 'assessments.asesi.profile'])->latest('id')->first();
        }

        if ($schedule && ! $assessment) {
            $assessment = $schedule->assessments->first();
        }

        return [$schedule, $assessment];
    }

    protected function serveDynamic(Request $request, string $docType, ?string $refPath = null)
    {
        if ($request->query('ref') && $refPath && file_exists($refPath)) {
            return response()->file($refPath, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
        }

        [$schedule, $assessment] = $this->resolveTarget($request);

        if ($schedule) {
            if ($assessment && ! in_array($docType, ['ba', 'hadir-asesor', 'daftar-periksa'], true)) {
                try {
                    return $this->dynamicService->exportAssessment($assessment, $docType, $request->boolean('download'));
                } catch (HttpException $e) {
                    if ($refPath && file_exists($refPath)) {
                        return response()->file($refPath, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
                    }
                    throw $e;
                }
            }

            return $this->dynamicService->exportSchedule($schedule, $docType, $request->boolean('download'));
        }

        // Fallback to static fixture if no database records exist
        $fixtureMap = [
            'apl01' => 'tests/Fixtures/muk/FR_APL_01_generated.pdf',
            'apl02' => 'tests/Fixtures/muk/FR_APL_02_generated.pdf',
            'ak01' => 'tests/Fixtures/muk/FR_AK_01_generated.pdf',
            'ak02' => 'tests/Fixtures/muk/FR_AK_02_generated.pdf',
            'ak03' => 'tests/Fixtures/muk/FR_AK_03_generated.pdf',
            'ak04' => 'tests/Fixtures/muk/FR_AK_04_generated.pdf',
            'ak07' => 'tests/Fixtures/muk/FR_AK_07_generated.pdf',
            'ia01' => 'tests/Fixtures/muk/FR_IA_01_generated.pdf',
            'ia03' => 'tests/Fixtures/muk/FR_IA_03_generated.pdf',
            'ia05c' => 'tests/Fixtures/muk/FR_IA_05_C_generated.pdf',
            'ba' => 'tests/Fixtures/muk/Berita_Acara_Peserta_generated.pdf',
            'hadir-asesor' => 'tests/Fixtures/muk/Daftar_Hadir_Asesor_generated.pdf',
            'pernyataan' => 'tests/Fixtures/muk/Surat_Pernyataan_generated.pdf',
            'daftar-periksa' => 'tests/Fixtures/muk/Daftar_Periksa_generated.pdf',
        ];
        $fixturePath = base_path($fixtureMap[$docType] ?? "tests/Fixtures/muk/{$docType}_generated.pdf");
        abort_unless(file_exists($fixturePath), 404, 'File PDF belum ditemukan.');

        return response()->file($fixturePath, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']);
    }

    public function apl01(Request $request)
    {
        return $this->serveDynamic(
            $request,
            'apl01',
            base_path('lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/2. FR.APL.01. Permohonan Sertifikasi Kompetensi.pdf')
        );
    }

    public function apl02(Request $request)
    {
        return $this->serveDynamic(
            $request,
            'apl02',
            base_path('lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/3. FR.APL 02. Asesmen Mandiri.pdf')
        );
    }

    public function ak01(Request $request)
    {
        return $this->serveDynamic(
            $request,
            'ak01',
            base_path('lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/7. FR.AK.01. Persetujuan Asesmen dan Kerahasian  Rev 19 docx.pdf')
        );
    }

    public function ak02(Request $request)
    {
        return $this->serveDynamic(
            $request,
            'ak02',
            base_path('lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/13. FR.AK.02. Rekaman Asesmen Kompetensi.pdf')
        );
    }

    public function ak03(Request $request)
    {
        return $this->serveDynamic(
            $request,
            'ak03',
            base_path('lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/14. FR.AK.03. Umpan Balik dan Catatan Asesmen.pdf')
        );
    }

    public function ak04(Request $request)
    {
        return $this->serveDynamic(
            $request,
            'ak04',
            base_path('lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/6. FR.AK.04. Banding Asesmen.pdf')
        );
    }

    public function ak07(Request $request)
    {
        return $this->serveDynamic(
            $request,
            'ak07',
            base_path('lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/5. FR.AK.07-Penyesuan-wajar-alasan.pdf')
        );
    }

    public function ia01(Request $request)
    {
        return $this->serveDynamic(
            $request,
            'ia01',
            base_path('lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/8A. FR.IA.01.Ceklis Observasi Aktivitas Praktik.pdf')
        );
    }

    public function ia03(Request $request)
    {
        return $this->serveDynamic(
            $request,
            'ia03',
            base_path('lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/10. FR.IA.03. Pertanyaan Mendukung Observasi (PMO) docx.pdf')
        );
    }

    public function ia05c(Request $request)
    {
        return $this->serveDynamic(
            $request,
            'ia05c',
            base_path('lsp-document-dynamic/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/12. FR.IA.05.C. Lembar Jawaban Pilihan Ganda.pdf')
        );
    }

    public function beritaAcara(Request $request)
    {
        return $this->serveDynamic(
            $request,
            'ba',
            base_path('lsp-document-fixed/2.2 & 2.3 Berita  Acara Pelaksanaan Uji Kompetensi dan Daftar Hadir Peserta.docx.pdf')
        );
    }

    public function hadirAsesor(Request $request)
    {
        return $this->serveDynamic(
            $request,
            'hadir-asesor',
            base_path('lsp-document-fixed/2.4 DAFTAR HADIR ASESOR.docx.pdf')
        );
    }

    public function pernyataan(Request $request)
    {
        return $this->serveDynamic(
            $request,
            'pernyataan',
            base_path('lsp-document-fixed/15 Surat Pernyataan Pemegang Sertifikat.pdf')
        );
    }

    public function daftarPeriksa(Request $request)
    {
        return $this->serveDynamic(
            $request,
            'daftar-periksa',
            base_path('lsp-document-fixed/MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection/1. Daftar Periksa Kelengkapan Dokumen MUK Rev 19.docx.pdf')
        );
    }
}
