<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Services\MukDynamicDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDokumenMukUnifiedPrintTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $asesor;

    private User $asesi;

    private Scheme $scheme;

    private Schedule $schedule;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'SUPER_ADMIN', 'status' => 'ACTIVE']);
        $this->asesor = User::factory()->create(['role' => 'ASESOR', 'status' => 'ACTIVE']);
        $this->asesi = User::factory()->create(['role' => 'ASESI', 'status' => 'ACTIVE']);

        $this->scheme = Scheme::create([
            'code' => 'SKM-02-2026',
            'name' => 'Maintenance Gasoline Engine Injection System',
            'scheme_type' => 'KLASTER',
            'is_active' => true,
        ]);

        $this->schedule = Schedule::create([
            'code' => 'SCH-'.uniqid(),
            'name' => 'Jadwal Uji Kompetensi',
            'scheme_id' => $this->scheme->id,
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'tuk_name' => 'TUK Mandiri',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['AK01', 'IA01', 'IA02', 'IA03', 'IA05'],
            'is_closed' => false,
        ]);

        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'scheme_id' => $this->scheme->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->asesor->id,
            'final_result' => 'KOMPETEN',
            'status' => 'COMPLETED',
        ]);
    }

    public function test_dokumen_muk_page_renders_print_semua_muk_button(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.show', $this->schedule));

        $response->assertStatus(200);
        $response->assertSee('Print Semua MUK');
        $expectedUrl = route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $this->assessment->id, 'type' => 'all-muk']);
        $response->assertSee($expectedUrl, false);
    }

    public function test_export_asesi_dynamic_all_muk_returns_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $this->assessment->id, 'type' => 'all-muk'])
        );

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_generate_unified_asesi_muk_pdf_produces_valid_pdf_file(): void
    {
        $service = app(MukDynamicDocumentService::class);
        $pdfPath = $service->generateUnifiedAsesiMukPdf($this->assessment);

        $this->assertFileExists($pdfPath);
        $this->assertGreaterThan(1000, filesize($pdfPath));
    }

    public function test_export_asesi_dynamic_mapa01_returns_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $this->assessment->id, 'type' => 'mapa01'])
        );

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_generate_dynamic_mapa01_pdf_produces_clean_unwatermarked_pdf(): void
    {
        $service = app(MukDynamicDocumentService::class);
        $pdfPath = $service->generateDynamicPdf('mapa01', $this->schedule, $this->assessment);

        $this->assertFileExists($pdfPath);
        $this->assertGreaterThan(1000, filesize($pdfPath));

        $content = file_get_contents($pdfPath);
        $this->assertStringNotContainsString('0.722 0.8 0.894 rg', $content);
    }
}
