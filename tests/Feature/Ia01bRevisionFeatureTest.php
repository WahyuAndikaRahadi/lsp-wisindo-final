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
use Tests\TestCase;

class Ia01bRevisionFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $asesor;

    protected User $asesi;

    protected Scheme $scheme;

    protected Schedule $schedule;

    protected Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        Storage::fake('local');

        $this->asesor = User::where('role', 'ASESOR')->first();
        $this->asesi = User::where('role', 'ASESI')->first();
        $this->scheme = Scheme::first();

        $this->schedule = Schedule::create([
            'code' => 'SCH-IA01B-REV-'.uniqid(),
            'name' => 'Jadwal Uji Revisi IA01B',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'selected_instruments' => ['IA01B'],
            'status' => 'ACTIVE',
            'created_by' => $this->asesor->id,
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
            'ak01_agreed_at' => now(),
            'ak01_data' => [
                'selected_instruments' => ['IA01B'],
            ],
        ]);
    }

    public function test_assessor_can_request_revision_on_ia01b(): void
    {
        // Setup initial upload by asesi
        $file = UploadedFile::fake()->create('report_sheet_initial.pdf', 300, 'application/pdf');
        $storedPath = $file->store('ia01b_evidence', 'local');

        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA01B',
            'data_json' => [
                'evidence_image_path' => $storedPath,
                'evidence_uploaded_at' => now()->toIso8601String(),
                'evidence_original_name' => 'report_sheet_initial.pdf',
            ],
            'assessor_signed_at' => now(),
            'assessor_signature' => 'data:image/png;base64,sample_signature',
        ]);

        $this->actingAs($this->asesor);

        $response = $this->post(route('asesor.assessment.ia01b.request-revision', $this->assessment->id), [
            'revision_notes' => 'Foto buram dan hasil pengukuran rem belum terbaca jelas. Mohon foto ulang tegak lurus.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $iaResponse = AssessmentIaResponse::where('assessment_id', $this->assessment->id)
            ->where('instrument_code', 'IA01B')
            ->first();

        $this->assertNotNull($iaResponse);
        $this->assertTrue($iaResponse->data_json['revision_requested']);
        $this->assertEquals('REVISION_REQUESTED', $iaResponse->data_json['revision_status']);
        $this->assertEquals('Foto buram dan hasil pengukuran rem belum terbaca jelas. Mohon foto ulang tegak lurus.', $iaResponse->data_json['revision_notes']);
        $this->assertNull($iaResponse->assessor_signed_at);
        $this->assertNull($iaResponse->assessor_signature);
    }

    public function test_schedule_instrument_list_reflects_revision_requested_status(): void
    {
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA01B',
            'data_json' => [
                'evidence_image_path' => 'ia01b_evidence/test.pdf',
                'evidence_uploaded_at' => now()->toIso8601String(),
                'evidence_original_name' => 'test.pdf',
                'revision_requested' => true,
                'revision_status' => 'REVISION_REQUESTED',
                'revision_notes' => 'Tolong lengkapi toleransi pengukuran.',
            ],
        ]);

        $this->actingAs($this->asesor);

        $response = $this->get(route('asesor.schedule.instrument', [
            'schedule' => $this->schedule->id,
            'code' => 'ia01b',
        ]));

        $response->assertOk();
        $response->assertSee('Perlu Revisi Asesi');
    }

    public function test_asesi_ruang_uji_hub_and_view_display_revision_notice(): void
    {
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA01B',
            'data_json' => [
                'evidence_image_path' => 'ia01b_evidence/test.pdf',
                'evidence_uploaded_at' => now()->toIso8601String(),
                'evidence_original_name' => 'test.pdf',
                'revision_requested' => true,
                'revision_status' => 'REVISION_REQUESTED',
                'revision_notes' => 'Tolong lengkapi toleransi pengukuran pada halaman 2.',
            ],
        ]);

        $this->actingAs($this->asesi);

        // Check Hub
        $hubResponse = $this->get(route('asesi.assessment.ruang-uji', $this->assessment->id));
        $hubResponse->assertOk();
        $hubResponse->assertSee('Perlu Revisi Berkas');
        $hubResponse->assertSee('Unggah Berkas Revisi');

        // Check IA01B View
        $viewResponse = $this->get(route('asesi.assessment.instrument.view', [
            'assessment' => $this->assessment->id,
            'code' => 'IA01B',
        ]));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Perhatian: Asesor Meminta Revisi Lembar Report Sheet');
        $viewResponse->assertSee('Tolong lengkapi toleransi pengukuran pada halaman 2.');
        $viewResponse->assertSee('Unggah Berkas Baru Hasil Revisi');
    }

    public function test_asesi_can_resubmit_revised_report_sheet_and_archive_history(): void
    {
        $oldFile = UploadedFile::fake()->create('old_report.pdf', 200, 'application/pdf');
        $oldPath = $oldFile->store('ia01b_evidence', 'local');

        $iaResponse = AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA01B',
            'data_json' => [
                'evidence_image_path' => $oldPath,
                'evidence_uploaded_at' => now()->subHour()->toIso8601String(),
                'evidence_original_name' => 'old_report.pdf',
                'revision_requested' => true,
                'revision_status' => 'REVISION_REQUESTED',
                'revision_notes' => 'Foto ulang dengan pencahayaan terang.',
            ],
        ]);

        $this->actingAs($this->asesi);

        $newFile = UploadedFile::fake()->create('revised_report.pdf', 350, 'application/pdf');

        $uploadResponse = $this->post(route('asesi.assessment.instrument.upload-evidence', [
            'assessment' => $this->assessment->id,
            'code' => 'IA01B',
        ]), [
            'evidence_image' => $newFile,
        ]);

        $uploadResponse->assertRedirect();
        $uploadResponse->assertSessionHas('success', 'Berkas revisi lembar Report Sheet berhasil diunggah ulang dan siap diperiksa kembali oleh asesor.');

        $iaResponse->refresh();
        $this->assertFalse($iaResponse->data_json['revision_requested']);
        $this->assertEquals('RESUBMITTED', $iaResponse->data_json['revision_status']);
        $this->assertEquals('revised_report.pdf', $iaResponse->data_json['evidence_original_name']);

        // Check history archived
        $this->assertArrayHasKey('evidence_history', $iaResponse->data_json);
        $this->assertCount(1, $iaResponse->data_json['evidence_history']);
        $this->assertEquals('old_report.pdf', $iaResponse->data_json['evidence_history'][0]['original_name']);
        $this->assertEquals($oldPath, $iaResponse->data_json['evidence_history'][0]['path']);
        $this->assertEquals('Foto ulang dengan pencahayaan terang.', $iaResponse->data_json['evidence_history'][0]['revision_notes']);
    }

    public function test_assessor_can_cancel_revision(): void
    {
        $iaResponse = AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA01B',
            'data_json' => [
                'evidence_image_path' => 'ia01b_evidence/file.pdf',
                'evidence_uploaded_at' => now()->toIso8601String(),
                'evidence_original_name' => 'file.pdf',
                'revision_requested' => true,
                'revision_status' => 'REVISION_REQUESTED',
                'revision_notes' => 'Salah klik revisi',
            ],
        ]);

        $this->actingAs($this->asesor);

        $response = $this->post(route('asesor.assessment.ia01b.cancel-revision', $this->assessment->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $iaResponse->refresh();
        $this->assertFalse($iaResponse->data_json['revision_requested']);
        $this->assertNull($iaResponse->data_json['revision_status']);
    }

    public function test_assessor_submitting_evaluation_resolves_revision_status(): void
    {
        $iaResponse = AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA01B',
            'data_json' => [
                'evidence_image_path' => 'ia01b_evidence/file.pdf',
                'evidence_uploaded_at' => now()->toIso8601String(),
                'evidence_original_name' => 'file.pdf',
                'revision_requested' => false,
                'revision_status' => 'RESUBMITTED',
            ],
        ]);

        $this->actingAs($this->asesor);

        $response = $this->post(route('asesor.ruang-uji.ia01b.store', $this->assessment->id), [
            'notes' => 'Hasil revisi sudah tepat dan memenuhi toleransi standar.',
            'items' => [
                0 => ['result' => 'SESUAI', 'measured' => '0.25 mm'],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $iaResponse->refresh();
        $this->assertEquals('APPROVED', $iaResponse->data_json['revision_status']);
        $this->assertFalse($iaResponse->data_json['revision_requested']);
        $this->assertNotNull($iaResponse->assessor_signed_at);
    }
}
