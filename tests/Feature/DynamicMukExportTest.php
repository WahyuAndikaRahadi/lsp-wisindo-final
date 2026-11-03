<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Services\MukDynamicDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class DynamicMukExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $asesor;

    private User $asesi;

    private Schedule $schedule;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
        $this->asesor = User::factory()->create([
            'role' => 'ASESOR',
            'full_name' => 'Ir. Hendra Gunawan, S.T.',
        ]);
        $this->asesi = User::factory()->create([
            'role' => 'ASESI',
            'full_name' => 'Bambang Sudarmono',
        ]);

        // Scheme 1: Engine Junior Technician
        $scheme = Scheme::firstOrCreate(
            ['code' => 'SKM-01-2026'],
            ['name' => 'Engine Junior Technician', 'scheme_type' => 'KLASTER', 'is_active' => true]
        );

        $this->schedule = Schedule::create([
            'code' => 'SCH-'.uniqid(),
            'name' => 'Jadwal Otomotif Batch 1',
            'scheme_id' => $scheme->id,
            'date' => '2026-10-23',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'tuk_name' => 'TUK Otomotif Mandiri',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['AK01'],
        ]);

        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'scheme_id' => $scheme->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->asesor->id,
            'apl_status' => 'APPROVED',
            'final_result' => 'KOMPETEN',
        ]);
    }

    public function test_service_finds_dynamic_template_files_for_scheme_1(): void
    {
        $service = app(MukDynamicDocumentService::class);
        $scheme = $this->schedule->scheme;

        $types = ['apl01', 'apl02', 'ak01', 'ak02', 'ak03', 'ak07', 'ia01', 'ia01b', 'ia03', 'ia05c', 'ba', 'hadir-asesor'];
        foreach ($types as $type) {
            $path = $service->getDynamicFilePath($scheme, $type);
            $this->assertNotNull($path, "Failed finding dynamic path for type: {$type}");
            $this->assertFileExists($path);
        }
    }

    public function test_service_builds_correct_payload_with_candidate_data(): void
    {
        $service = app(MukDynamicDocumentService::class);
        $payload = $service->buildPayload($this->schedule, $this->assessment);

        $this->assertEquals('Bambang Sudarmono', $payload['asesi_nama']);
        $this->assertEquals('Ir. Hendra Gunawan, S.T.', $payload['asesor_nama']);
        $this->assertEquals('Engine Junior Technician', $payload['skema_nama']);
        $this->assertEquals('TUK Otomotif Mandiri', $payload['tuk_nama']);
        $this->assertEquals('KOMPETEN', $payload['final_result']);
        $this->assertEquals('K', $payload['rekomendasi']);
        $this->assertNotEmpty($payload['peserta']);
        $this->assertNotEmpty($payload['asesor']);
    }

    public function test_print_example_routes_return_pdf(): void
    {
        $response = $this->get(route('print-example.apl01'));
        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));

        $responseRef = $this->get(route('print-example.apl01', ['ref' => '1']));
        $responseRef->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $responseRef->baseResponse);

        $responseApl02 = $this->get(route('print-example.apl02'));
        $responseApl02->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $responseApl02->baseResponse);
        $this->assertEquals('application/pdf', $responseApl02->headers->get('Content-Type'));
    }

    public function test_admin_can_export_dynamic_schedule_berita_acara_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.dynamic', [
            'schedule' => $this->schedule->id,
            'type' => 'ba',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertFileExists($response->baseResponse->getFile()->getPathname());
    }

    public function test_admin_can_export_dynamic_schedule_hadir_asesor_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.dynamic', [
            'schedule' => $this->schedule->id,
            'type' => 'hadir-asesor',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertFileExists($response->baseResponse->getFile()->getPathname());
    }

    public function test_admin_can_export_dynamic_candidate_apl01_pdf(): void
    {
        $this->asesi->profile()->updateOrCreate(
            ['user_id' => $this->asesi->id],
            [
                'nik' => '3275010203050099',
                'tempat_lahir' => 'Karawang',
                'tanggal_lahir' => '2005-08-17',
                'jenis_kelamin' => 'L',
                'alamat_rumah' => 'Jl. Otomotif No. 45 Karawang',
                'pendidikan_terakhir' => 'SMK Teknik Otomotif',
                'nama_institusi_perusahaan' => 'PT Astra Otomotif Test',
                'jabatan' => 'Mekanik Junior',
            ]
        );

        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.asesi-dynamic', [
            'assessment' => $this->assessment->id,
            'type' => 'apl01',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $filePath = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($filePath);

        // Verify text extraction contains dynamic candidate info, employment, and scheme
        $nullDev = PHP_OS_FAMILY === 'Windows' ? '2>nul' : '2>/dev/null';
        $pdfText = shell_exec('pdftotext '.escapeshellarg($filePath)." - {$nullDev}") ?? '';
        $this->assertStringContainsString('Bambang Sudarmono', $pdfText);
        $this->assertStringContainsString('3275010203050099', $pdfText);
        $this->assertStringContainsString('Karawang', $pdfText);
        $this->assertStringContainsString('Jl. Otomotif No. 45 Karawang', $pdfText);
        $this->assertStringContainsString('PT Astra Otomotif Test', $pdfText);
        $this->assertStringContainsString('Mekanik Junior', $pdfText);
        $this->assertStringContainsString('Engine Junior Technician', $pdfText);
        $this->assertStringContainsString('SKM-01-2026', $pdfText);

        // Verify watermark is absent
        $this->assertStringNotContainsString('WATERMARK', strtoupper($pdfText));
    }

    public function test_admin_can_export_dynamic_candidate_apl02_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.asesi-dynamic', [
            'assessment' => $this->assessment->id,
            'type' => 'apl02',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $filePath = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($filePath);

        // Verify text extraction contains dynamic candidate & assessor info
        $nullDev = PHP_OS_FAMILY === 'Windows' ? '2>nul' : '2>/dev/null';
        $pdfText = shell_exec('pdftotext '.escapeshellarg($filePath)." - {$nullDev}") ?? '';
        $this->assertStringContainsString('FR.APL.02. ASESMEN MANDIRI', $pdfText);
        $this->assertStringContainsString('Bambang Sudarmono', $pdfText);
        $this->assertStringContainsString('Ir. Hendra Gunawan, S.T.', $pdfText);
    }

    public function test_admin_can_export_dynamic_candidate_ak01_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.asesi-dynamic', [
            'assessment' => $this->assessment->id,
            'type' => 'ak01',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $filePath = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($filePath);

        // Verify text extraction contains dynamic candidate & assessor info
        $nullDev = PHP_OS_FAMILY === 'Windows' ? '2>nul' : '2>/dev/null';
        $pdfText = shell_exec('pdftotext '.escapeshellarg($filePath)." - {$nullDev}") ?? '';
        $this->assertStringContainsString('FR.AK.01', $pdfText);
        $this->assertStringContainsString('Bambang Sudarmono', $pdfText);
        $this->assertStringContainsString('Ir. Hendra Gunawan, S.T.', $pdfText);
    }

    public function test_admin_can_export_dynamic_candidate_ak07_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.asesi-dynamic', [
            'assessment' => $this->assessment->id,
            'type' => 'ak07',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $filePath = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($filePath);

        // Verify text extraction contains dynamic candidate & assessor info
        $nullDev = PHP_OS_FAMILY === 'Windows' ? '2>nul' : '2>/dev/null';
        $pdfText = shell_exec('pdftotext '.escapeshellarg($filePath)." - {$nullDev}") ?? '';
        $this->assertStringContainsString('FR.AK.07', $pdfText);
        $this->assertStringContainsString('CEKLIS PENYESUAIAN YANG WAJAR', $pdfText);
        $this->assertStringContainsString('Bambang Sudarmono', $pdfText);
        $this->assertStringContainsString('Ir. Hendra Gunawan, S.T.', $pdfText);
    }

    public function test_admin_can_export_dynamic_candidate_ia01_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.asesi-dynamic', [
            'assessment' => $this->assessment->id,
            'type' => 'ia01',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $filePath = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($filePath);

        // Verify text extraction contains dynamic candidate & assessor info
        $nullDev = PHP_OS_FAMILY === 'Windows' ? '2>nul' : '2>/dev/null';
        $pdfText = shell_exec('pdftotext '.escapeshellarg($filePath)." - {$nullDev}") ?? '';
        $this->assertStringContainsString('FR.IA.01', $pdfText);
        $this->assertStringContainsString('CEKLIS OBSERVASI AKTIVITAS', $pdfText);
        $this->assertStringContainsString('Bambang Sudarmono', $pdfText);
        $this->assertStringContainsString('Ir. Hendra Gunawan, S.T.', $pdfText);
    }

    public function test_admin_can_export_dynamic_candidate_ia01_for_scheme_2(): void
    {
        $scheme2 = Scheme::firstOrCreate(
            ['code' => 'SKM-02-2026'],
            ['name' => 'Maintenance Gasoline Engine Injection System', 'scheme_type' => 'KLASTER', 'is_active' => true]
        );
        $schedule2 = Schedule::create([
            'code' => 'SCH-SCHEME2-'.uniqid(),
            'name' => 'Jadwal Skema 2',
            'scheme_id' => $scheme2->id,
            'date' => '2026-10-24',
            'start_time' => '08:00',
            'end_time' => '12:00',
            'tuk_name' => 'TUK Otomotif Mandiri',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['AK01'],
        ]);
        $assessment2 = Assessment::create([
            'schedule_id' => $schedule2->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->asesor->id,
            'apl_status' => 'APPROVED',
            'final_result' => 'KOMPETEN',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.asesi-dynamic', [
            'assessment' => $assessment2->id,
            'type' => 'ia01',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $filePath = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($filePath);

        $nullDev = PHP_OS_FAMILY === 'Windows' ? '2>nul' : '2>/dev/null';
        $pdfText = shell_exec('pdftotext '.escapeshellarg($filePath)." - {$nullDev}") ?? '';
        $this->assertStringContainsString('FR.IA.01', $pdfText);
        $this->assertStringContainsString('Bambang Sudarmono', $pdfText);
        $this->assertStringContainsString('Ir. Hendra Gunawan, S.T.', $pdfText);
    }

    public function test_admin_can_export_dynamic_candidate_ia03_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.asesi-dynamic', [
            'assessment' => $this->assessment->id,
            'type' => 'ia03',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $filePath = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($filePath);

        // Verify text extraction contains dynamic candidate & assessor info
        $nullDev = PHP_OS_FAMILY === 'Windows' ? '2>nul' : '2>/dev/null';
        $pdfText = shell_exec('pdftotext '.escapeshellarg($filePath)." - {$nullDev}") ?? '';
        $this->assertStringContainsString('FR.IA.03', $pdfText);
        $this->assertStringContainsString('PERTANYAAN MENDUKUNG OBSERVASI', $pdfText);
        $this->assertStringContainsString('Bambang Sudarmono', $pdfText);
        $this->assertStringContainsString('Ir. Hendra Gunawan, S.T.', $pdfText);
    }

    public function test_admin_can_export_dynamic_candidate_ak02_pdf_with_download_disposition(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.asesi-dynamic', [
            'assessment' => $this->assessment->id,
            'type' => 'ak02',
            'download' => '1',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $filePath = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($filePath);

        // Verify text extraction contains dynamic candidate & assessor info
        $nullDev = PHP_OS_FAMILY === 'Windows' ? '2>nul' : '2>/dev/null';
        $pdfText = shell_exec('pdftotext '.escapeshellarg($filePath)." - {$nullDev}") ?? '';
        $this->assertStringContainsString('FR.AK.02', $pdfText);
        $this->assertStringContainsString('REKAMAN ASESMEN KOMPETENSI', $pdfText);
        $this->assertStringContainsString('Bambang Sudarmono', $pdfText);
        $this->assertStringContainsString('Ir. Hendra Gunawan, S.T.', $pdfText);
    }

    public function test_asesor_can_access_dynamic_export_routes(): void
    {
        $response = $this->actingAs($this->asesor)->get(route('asesor.schedule.dynamic', [
            'schedule' => $this->schedule->id,
            'type' => 'ba',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);

        $responseAsesi = $this->actingAs($this->asesor)->get(route('asesor.assessment.dynamic', [
            'assessment' => $this->assessment->id,
            'type' => 'ak01',
        ]));

        $responseAsesi->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $responseAsesi->baseResponse);
    }
}
