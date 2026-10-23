<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Services\MukFixedDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class FixedMukExportTest extends TestCase
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
        $this->asesor = User::factory()->create(['role' => 'ASESOR']);
        $this->asesi = User::factory()->create(['role' => 'ASESI']);

        // Scheme 1: Engine Junior Technician
        $scheme = Scheme::firstOrCreate(
            ['code' => 'SKM-01-2026'],
            ['name' => 'Engine Junior Technician', 'is_active' => true]
        );

        $this->schedule = Schedule::create([
            'code' => 'SCH-'.uniqid(),
            'name' => 'Jadwal Uji Otomotif',
            'scheme_id' => $scheme->id,
            'date' => now()->toDateString(),
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
        ]);
    }

    public function test_service_finds_fixed_documents_for_scheme_1(): void
    {
        $service = app(MukFixedDocumentService::class);
        $scheme = $this->schedule->scheme;

        $types = ['cover', 'daftar-periksa', 'mapa01', 'mapa02', 'ia02', 'ia05a', 'ia05b', 'ba', 'hadir-asesor'];
        foreach ($types as $type) {
            $path = $service->getFixedFilePath($scheme, $type);
            $this->assertNotNull($path, "Failed finding fixed path for type: {$type}");
            $this->assertFileExists($path);
        }
    }

    public function test_admin_can_export_fixed_schedule_cover_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.fixed', [
            'schedule' => $this->schedule->id,
            'type' => 'cover',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->assertFileExists($response->baseResponse->getFile()->getPathname());
    }

    public function test_admin_can_export_fixed_schedule_mapa01_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.fixed', [
            'schedule' => $this->schedule->id,
            'type' => 'mapa01',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertFileExists($response->baseResponse->getFile()->getPathname());
    }

    public function test_admin_can_download_fixed_document_with_attachment_disposition(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.fixed', [
            'schedule' => $this->schedule->id,
            'type' => 'daftar-periksa',
            'download' => '1',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertFileExists($response->baseResponse->getFile()->getPathname());
    }

    public function test_admin_can_export_fixed_asesi_ia02_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.asesi-fixed', [
            'assessment' => $this->assessment->id,
            'type' => 'ia02',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertFileExists($response->baseResponse->getFile()->getPathname());
    }

    public function test_admin_can_export_fixed_asesi_ia05a_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.asesi-fixed', [
            'assessment' => $this->assessment->id,
            'type' => 'ia05a',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertFileExists($response->baseResponse->getFile()->getPathname());
    }

    public function test_asesor_can_access_fixed_pdf_export_routes(): void
    {
        $response = $this->actingAs($this->asesor)->get(route('asesor.schedule.fixed', [
            'schedule' => $this->schedule->id,
            'type' => 'ia02',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertFileExists($response->baseResponse->getFile()->getPathname());
    }

    public function test_export_schedule_routes_to_fixed_pdf_when_format_pdf_requested(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export', [
            'schedule' => $this->schedule->id,
            'type' => 'ia05b',
            'format' => 'pdf',
        ]));

        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertFileExists($response->baseResponse->getFile()->getPathname());
    }

    public function test_returns_404_when_fixed_document_not_available(): void
    {
        // Scheme 2 does not have MAPA.02
        $scheme2 = Scheme::firstOrCreate(
            ['code' => 'SKM-02-2026'],
            ['name' => 'Maintenance Gasoline Engine Injection System', 'is_active' => true]
        );
        $schedule2 = Schedule::create([
            'code' => 'SCH-'.uniqid(),
            'name' => 'Jadwal Skema 2',
            'scheme_id' => $scheme2->id,
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'tuk_name' => 'TUK Otomotif Mandiri',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['AK01'],
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.fixed', [
            'schedule' => $schedule2->id,
            'type' => 'mapa02',
        ]));

        $response->assertNotFound();
    }
}
