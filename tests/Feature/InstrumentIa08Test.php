<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\CompetencyUnit;
use App\Models\Kuk;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\UnitElement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstrumentIa08Test extends TestCase
{
    use RefreshDatabase;

    private User $asesor;

    private User $asesi;

    private Scheme $scheme;

    private Schedule $schedule;

    private Assessment $assessment;

    private UnitElement $element;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->asesor = User::create([
            'full_name' => 'Asesor Kompetensi IA08',
            'email' => 'asesor_ia08@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
        ]);

        $this->asesi = User::create([
            'full_name' => 'Asesi Uji IA08',
            'email' => 'asesi_ia08@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-01-2026',
            'name' => 'Engine Junior Technician',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);

        $unit = CompetencyUnit::create([
            'scheme_id' => $this->scheme->id,
            'code' => 'G.45OTO01.001.2',
            'title' => 'Melaksanakan K3 di Tempat Kerja',
            'sequence' => 1,
        ]);

        $this->element = UnitElement::create([
            'competency_unit_id' => $unit->id,
            'title' => 'Mengidentifikasi potensi bahaya kerja',
            'sequence' => 1,
        ]);

        Kuk::create([
            'unit_element_id' => $this->element->id,
            'code_number' => '1.1',
            'statement' => 'Potensi bahaya diidentifikasi sesuai SOP',
            'sequence' => 1,
        ]);

        $this->schedule = Schedule::create([
            'code' => 'SCH-IA08-01',
            'name' => 'Jadwal Uji Portofolio',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'SMKS Inovasi Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $this->asesor->id,
            'selected_instruments' => ['AK01', 'IA08'],
            'ak01_data' => [
                'selected_instruments' => ['IA08'],
                'evidence' => ['Hasil Verifikasi Portofolio'],
            ],
        ]);
        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->asesor->id,
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
            'status' => 'IN_PROGRESS',
            'ak01_data' => [
                'selected_instruments' => ['IA08'],
                'evidence' => ['Hasil Verifikasi Portofolio'],
            ],
            'ak01_agreed_at' => now(),
            'assessor_signature' => 'data:image/png;base64,sampleAsesorSig',
            'assessor_signed_at' => now(),
        ]);
    }

    public function test_asesi_can_upload_up_to_3_portfolio_files(): void
    {
        $file1 = UploadedFile::fake()->create('surat_pengalaman.pdf', 500, 'application/pdf');
        $file2 = UploadedFile::fake()->create('sertifikat_kompetensi.pdf', 600, 'application/pdf');
        $file3 = UploadedFile::fake()->create('spk_work_order.jpg', 400, 'image/jpeg');

        // 1. Upload first portfolio
        $resp1 = $this->actingAs($this->asesi)->post(route('asesi.assessment.upload-portfolio', $this->assessment), [
            'portfolio_type' => 'Surat Pengalaman Kerja / Rekomendasi Kerja',
            'portfolio_title' => 'Surat Pengalaman Kerja Bengkel Maju',
            'portfolio_file' => $file1,
        ]);
        $resp1->assertSessionHas('success');

        // 2. Upload second portfolio
        $resp2 = $this->actingAs($this->asesi)->post(route('asesi.assessment.upload-portfolio', $this->assessment), [
            'portfolio_type' => 'Sertifikat Pelatihan / Sertifikat Kompetensi',
            'portfolio_title' => 'Sertifikat Tune Up EFI',
            'portfolio_file' => $file2,
        ]);
        $resp2->assertSessionHas('success');

        // 3. Upload third portfolio
        $resp3 = $this->actingAs($this->asesi)->post(route('asesi.assessment.upload-portfolio', $this->assessment), [
            'portfolio_type' => 'Surat Perintah Kerja (SPK) / Work Order',
            'portfolio_title' => 'Work Order Overhaul',
            'portfolio_file' => $file3,
        ]);
        $resp3->assertSessionHas('success');

        $iaResponse = AssessmentIaResponse::where('assessment_id', $this->assessment->id)
            ->where('instrument_code', 'IA08')
            ->first();

        $this->assertNotNull($iaResponse);
        $portfolios = $iaResponse->data_json['portfolios'] ?? [];
        $this->assertCount(3, $portfolios);
        $this->assertEquals('Surat Pengalaman Kerja Bengkel Maju', $portfolios[0]['title']);
    }

    public function test_asesi_cannot_upload_more_than_3_portfolio_files(): void
    {
        // Pre-fill 3 portfolios
        $existingPortfolios = [
            ['id' => 'p1', 'type' => 'Surat Pengalaman', 'title' => 'Porto 1', 'path' => 'p1.pdf'],
            ['id' => 'p2', 'type' => 'Sertifikat', 'title' => 'Porto 2', 'path' => 'p2.pdf'],
            ['id' => 'p3', 'type' => 'SPK', 'title' => 'Porto 3', 'path' => 'p3.pdf'],
        ];

        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA08',
            'data_json' => ['portfolios' => $existingPortfolios],
        ]);

        $file4 = UploadedFile::fake()->create('porto_4.pdf', 300, 'application/pdf');

        $response = $this->actingAs($this->asesi)->post(route('asesi.assessment.upload-portfolio', $this->assessment), [
            'portfolio_type' => 'Log Book',
            'portfolio_title' => 'Porto 4 Melebihi Batas',
            'portfolio_file' => $file4,
        ]);

        $response->assertSessionHas('error');
    }

    public function test_asesi_can_delete_portfolio_before_assessor_evaluates(): void
    {
        $portfolios = [
            ['id' => 'p-del-1', 'type' => 'Surat Pengalaman', 'title' => 'Porto Mau Dihapus', 'path' => 'p1.pdf'],
            ['id' => 'p-keep-2', 'type' => 'Sertifikat', 'title' => 'Porto Tetap', 'path' => 'p2.pdf'],
        ];

        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA08',
            'data_json' => ['portfolios' => $portfolios],
        ]);

        $response = $this->actingAs($this->asesi)->delete(route('asesi.assessment.delete-portfolio', [
            'assessment' => $this->assessment->id,
            'portfolioId' => 'p-del-1',
        ]));

        $response->assertSessionHas('success');

        $iaResponse = AssessmentIaResponse::where('assessment_id', $this->assessment->id)
            ->where('instrument_code', 'IA08')
            ->first();

        $updatedPortfolios = $iaResponse->data_json['portfolios'] ?? [];
        $this->assertCount(1, $updatedPortfolios);
        $this->assertEquals('p-keep-2', $updatedPortfolios[0]['id']);
    }

    public function test_asesor_can_view_and_evaluate_ia08_with_vatm_and_remediation(): void
    {
        $portfolios = [
            ['id' => 'p-1', 'type' => 'Surat Pengalaman', 'title' => 'Porto 1', 'path' => 'p1.pdf'],
        ];

        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA08',
            'data_json' => ['portfolios' => $portfolios],
        ]);

        // 1. Assessor opens IA08 evaluation view
        $viewResp = $this->actingAs($this->asesor)->get(route('asesor.assessment.instrument', [
            'assessment' => $this->assessment->id,
            'code' => 'ia08',
        ]));
        $viewResp->assertOk();
        $viewResp->assertSee('FR.IA.08 – Ceklis Verifikasi Portofolio (CVP)');
        $viewResp->assertSee('Porto 1');

        // 2. Assessor submits evaluation with BK (remediation)
        $storeResp = $this->actingAs($this->asesor)->post(route('asesor.assessment.instrument.store', [
            'assessment' => $this->assessment->id,
            'code' => 'ia08',
        ]), [
            'vatm' => [
                'p-1' => [
                    'v' => 'YA',
                    'a' => 'YA',
                    't' => 'YA',
                    'm' => 'TIDAK',
                ],
            ],
            'clarification_elements' => [$this->element->id],
            'additional_evidence' => 'Perlu surat verifikasi pihak ketiga dari bengkel asal.',
            'recommendation' => 'BK',
            'remediation_unit' => 'Unit 001 Melaksanakan K3',
            'remediation_element' => 'Elemen 1',
            'remediation_kuk' => 'KUK 1.1',
            'assessor_notes' => 'Portofolio belum mencakup bukti K3 memadai.',
            'assessor_signature' => 'data:image/png;base64,asesorSignatureData',
        ]);

        $storeResp->assertRedirect(route('asesor.schedule.instrument', [
            'schedule' => $this->schedule->id,
            'code' => 'ia08',
        ]));
        $storeResp->assertSessionHas('success');

        $iaResponse = AssessmentIaResponse::where('assessment_id', $this->assessment->id)
            ->where('instrument_code', 'IA08')
            ->first();

        $this->assertNotNull($iaResponse->assessor_signed_at);
        $this->assertEquals('BK', $iaResponse->data_json['recommendation']);
        $this->assertEquals('Unit 001 Melaksanakan K3', $iaResponse->data_json['remediation_unit']);
        $this->assertContains($this->element->id, $iaResponse->data_json['clarification_elements']);
    }

    public function test_asesi_can_view_transparent_ia08_results_after_assessor_evaluates(): void
    {
        $portfolios = [
            ['id' => 'p-1', 'type' => 'Surat Pengalaman', 'title' => 'Porto Asesi', 'path' => 'p1.pdf'],
        ];

        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA08',
            'data_json' => [
                'portfolios' => $portfolios,
                'vatm' => [
                    'p-1' => ['v' => 'YA', 'a' => 'YA', 't' => 'YA', 'm' => 'YA'],
                ],
                'recommendation' => 'K',
            ],
            'assessor_notes' => 'Portofolio asesi sangat lengkap dan valid.',
            'assessor_signature' => 'data:image/png;base64,sigAsesor',
            'assessor_signed_at' => now(),
        ]);

        $response = $this->actingAs($this->asesi)->get(route('asesi.assessment.instrument.view', [
            'assessment' => $this->assessment->id,
            'code' => 'IA08',
        ]));

        $response->assertOk();
        $response->assertSee('FR.IA.08. CEKLIS VERIFIKASI PORTOFOLIO (CVP)');
        $response->assertSee('KOMPETEN (K)');
        $response->assertSee('Portofolio asesi sangat lengkap dan valid.');

        // Verify HTML print export returns 200 OK
        $admin = User::create([
            'full_name' => 'Admin MUK Export',
            'email' => 'admin_export_ia08@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        $exportResp = $this->actingAs($admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'ia08',
            'format' => 'html',
        ]));
        $exportResp->assertOk();
        $exportResp->assertSee('CVP - CEKLIS VERIFIKASI PORTOFOLIO');
    }
}
