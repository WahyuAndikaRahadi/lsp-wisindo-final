<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class Ia01bExportReworkTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $asesor;

    protected User $asesi;

    protected Scheme $scheme;

    protected Schedule $schedule;

    protected Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('role', 'ADMIN_LSP')->first();
        $this->asesor = User::where('role', 'ASESOR')->first();
        $this->asesi = User::where('role', 'ASESI')->first();
        $this->scheme = Scheme::first();

        $this->schedule = Schedule::create([
            'code' => 'SCH-IA01B-EXP-'.uniqid(),
            'name' => 'Jadwal Uji IA01B Export',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'selected_instruments' => ['IA01B'],
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);
        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment = Assessment::create([
            'asesi_id' => $this->asesi->id,
            'schedule_id' => $this->schedule->id,
            'scheme_id' => $this->scheme->id,
            'assessor_id' => $this->asesor->id,
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
            'status' => 'IN_PROGRESS',
        ]);
    }

    public function test_legacy_generation_files_and_tools_are_removed(): void
    {
        $this->assertFileDoesNotExist(base_path('tools/render_fr_ia_01_b.py'));
        $this->assertFileDoesNotExist(base_path('tests/Fixtures/muk/FR_IA_01_B_generated.pdf'));
        $this->assertFileDoesNotExist(resource_path('views/admin/dokumen-muk/export-asesi-ia01b.blade.php'));
    }

    public function test_export_ia01b_returns_not_uploaded_view_when_assessee_has_not_uploaded_file(): void
    {
        // 1. Dynamic route returns friendly not-uploaded view
        $resDynamic = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.asesi-dynamic', [
            'assessment' => $this->assessment->id,
            'type' => 'ia01b',
        ]));
        $resDynamic->assertOk();
        $resDynamic->assertViewIs('admin.dokumen-muk.not-uploaded');
        $resDynamic->assertSee('FR.IA.01B');

        // 2. Export asesi route also returns not-uploaded view
        $resExport = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'ia01b',
        ]));
        $resExport->assertOk();
        $resExport->assertViewIs('admin.dokumen-muk.not-uploaded');
    }

    public function test_export_ia01b_serves_uploaded_pdf_file(): void
    {
        Storage::fake('local');
        $pdfContent = '%PDF-1.4 Mock Uploaded FR.IA.01B Report Sheet by Assessee';
        $storedPath = 'ia01b_evidence/report_sheet_sample.pdf';
        Storage::disk('local')->put($storedPath, $pdfContent);

        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA01B',
            'data_json' => [
                'evidence_image_path' => $storedPath,
                'evidence_original_name' => 'Lembar_Pemeriksaan_Asesi.pdf',
                'evidence_uploaded_at' => now()->toIso8601String(),
            ],
            'asesi_signed_at' => now(),
        ]);

        // Admin clicks Export fr.ia.01b (via asesi-dynamic route)
        $res = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.asesi-dynamic', [
            'assessment' => $this->assessment->id,
            'type' => 'ia01b',
        ]));

        $res->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $res->baseResponse);
        $this->assertEquals('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $res->headers->get('Content-Disposition'));
        $this->assertStringContainsString('FR.IA.01B-REPORT_SHEET', $res->headers->get('Content-Disposition'));

        // Admin downloads fr.ia.01b
        $resDownload = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.asesi-dynamic', [
            'assessment' => $this->assessment->id,
            'type' => 'ia01b',
            'download' => '1',
        ]));

        $resDownload->assertOk();
        $this->assertStringContainsString('attachment', $resDownload->headers->get('Content-Disposition'));

        // Also test export-asesi route
        $resExport = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'ia01b',
        ]));
        $resExport->assertOk();
        $this->assertEquals('application/pdf', $resExport->headers->get('Content-Type'));
    }

    public function test_export_ia01b_serves_uploaded_image_file(): void
    {
        Storage::fake('local');
        $storedPath = 'ia01b_evidence/photo_report_sheet.jpg';
        Storage::disk('local')->put($storedPath, 'fake-jpeg-data');

        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA01B',
            'data_json' => [
                'evidence_image_path' => $storedPath,
                'evidence_original_name' => 'Foto_Report_Sheet.jpg',
                'evidence_uploaded_at' => now()->toIso8601String(),
            ],
            'asesi_signed_at' => now(),
        ]);

        $res = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.asesi-dynamic', [
            'assessment' => $this->assessment->id,
            'type' => 'ia01b',
        ]));

        $res->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $res->baseResponse);
        $this->assertStringContainsString('FR.IA.01B-REPORT_SHEET', $res->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.jpg', $res->headers->get('Content-Disposition'));
    }

    public function test_assessee_uploads_file_and_admin_exports_that_exact_file(): void
    {
        Storage::fake('local');

        $fakePdf = UploadedFile::fake()->create('Laporan_Pemeriksaan_Fisik_Wahyu.pdf', 500, 'application/pdf');

        // Asesi uploads report sheet on IA01B view
        $uploadRes = $this->actingAs($this->asesi)->post(
            route('asesi.assessment.instrument.upload-evidence', [$this->assessment, 'IA01B']),
            ['evidence_image' => $fakePdf]
        );
        $uploadRes->assertSessionHasNoErrors();
        $uploadRes->assertRedirect();

        // Verify response record created in DB
        $iaResponse = AssessmentIaResponse::where('assessment_id', $this->assessment->id)
            ->where('instrument_code', 'IA01B')
            ->first();

        $this->assertNotNull($iaResponse);
        $this->assertNotEmpty($iaResponse->data_json['evidence_image_path']);
        $this->assertEquals('Laporan_Pemeriksaan_Fisik_Wahyu.pdf', $iaResponse->data_json['evidence_original_name']);

        // Admin exports fr.ia.01b
        $exportRes = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.asesi-dynamic', [
            'assessment' => $this->assessment->id,
            'type' => 'ia01b',
        ]));

        $exportRes->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $exportRes->baseResponse);
        $this->assertEquals('application/pdf', $exportRes->headers->get('Content-Type'));
        $this->assertStringContainsString('FR.IA.01B-REPORT_SHEET', $exportRes->headers->get('Content-Disposition'));
    }

    public function test_assessor_ia01b_view_is_not_marked_completed_when_only_assessee_uploaded_evidence(): void
    {
        Storage::fake('local');

        $fakePdf = UploadedFile::fake()->create('Laporan_Pemeriksaan_Asesi.pdf', 300, 'application/pdf');

        // Asesi uploads report sheet
        $uploadRes = $this->actingAs($this->asesi)->post(
            route('asesi.assessment.instrument.upload-evidence', [$this->assessment, 'IA01B']),
            ['evidence_image' => $fakePdf]
        );
        $uploadRes->assertSessionHasNoErrors();
        $uploadRes->assertRedirect();

        // Response exists with evidence, but assessor has NOT signed or evaluated yet
        $iaResponse = AssessmentIaResponse::where('assessment_id', $this->assessment->id)
            ->where('instrument_code', 'IA01B')
            ->first();
        $this->assertNotNull($iaResponse);
        $this->assertNull($iaResponse->assessor_signed_at);
        $this->assertNull($iaResponse->assessor_signature);

        // Assessor opens the evaluation page
        $assessorPage = $this->actingAs($this->asesor)->get(
            route('asesor.ruang-uji.ia01b', $this->assessment)
        );
        $assessorPage->assertOk();

        // Must NOT show completed banner or Edit Penilaian button
        $assessorPage->assertDontSee('Penilaian FR.IA.01B Telah Selesai');
        $assessorPage->assertDontSee('✎ Edit Penilaian');

        // Must show active editing state and submit button
        $assessorPage->assertSee('Simpan Penilaian FR.IA.01B');
        $assessorPage->assertSee('Semua Sesuai');
        $assessorPage->assertSee('Semua Tidak');
        $assessorPage->assertSee('isEditing: true');

        // Assessor now submits evaluation
        $submitRes = $this->actingAs($this->asesor)->post(
            route('asesor.ruang-uji.ia01b.store', $this->assessment->id),
            [
                'items' => [
                    0 => ['measured' => '12.6 Volt', 'result' => 'SESUAI'],
                ],
                'notes' => 'Pengukuran sesuai standar manual pabrikan.',
                'signature_data' => 'data:image/png;base64,mockSignatureAsesor123',
            ]
        );
        $submitRes->assertSessionHasNoErrors();
        $submitRes->assertRedirect(route('asesor.schedule.instrument', [
            'schedule' => $this->assessment->schedule_id,
            'code' => 'ia01b',
        ]));

        $iaResponse->refresh();
        $this->assertNotNull($iaResponse->assessor_signed_at);
        $this->assertEquals('data:image/png;base64,mockSignatureAsesor123', $iaResponse->assessor_signature);

        // Assessor reopens page after evaluation: now it is completed
        $reopenedPage = $this->actingAs($this->asesor)->get(
            route('asesor.ruang-uji.ia01b', $this->assessment)
        );
        $reopenedPage->assertOk();
        $reopenedPage->assertSee('Penilaian FR.IA.01B Telah Selesai');
        $reopenedPage->assertSee('✎ Edit Penilaian');
        $reopenedPage->assertSee('isEditing: false');
    }
}
