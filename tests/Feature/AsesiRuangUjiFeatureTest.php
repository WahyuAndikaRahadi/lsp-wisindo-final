<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AsesiRuangUjiFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_ruang_uji_hub_displays_selected_instruments_in_asesi_version(): void
    {
        $asesi = User::where('role', 'ASESI')->first();
        $scheme = Scheme::first();
        $admin = User::where('role', 'ADMIN_LSP')->first();

        $schedule = Schedule::create([
            'scheme_id' => $scheme->id,
            'name' => 'Jadwal Ruang Uji Asesi',
            'code' => 'SCH-RUANG-UJI-'.uniqid(),
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['IA01', 'IA01B', 'IA02', 'IA03', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);

        $assessment = Assessment::create([
            'asesi_id' => $asesi->id,
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'apl_status' => 'APPROVED',
        ]);

        $response = $this->actingAs($asesi)->get(route('asesi.assessment.ruang-uji', $assessment));
        $response->assertOk();

        // Check header and sections
        $response->assertSee('Ruang Uji Asesi');
        $response->assertSee('1. Instrumen Dikerjakan Mandiri oleh Asesi');
        $response->assertSee('2. Instrumen Penilaian Asesor');

        // Check self-actionable instruments
        $response->assertSee('FR.IA.05. Pertanyaan Tertulis (CBT)');
        $response->assertSee('FR.IA.02. Tugas Praktik Demonstrasi (TPD)');
        $response->assertSee(route('asesi.assessment.instrument.ia05', $assessment));
        $response->assertSee(route('asesi.assessment.instrument.download-template', [$assessment, 'IA02']));

        // Check assessor evaluation instruments
        $response->assertSee('FR.IA.01. Ceklis Observasi Aktivitas Praktik');
        $response->assertSee('FR.IA.01B. Lembar Laporan Pemeriksaan (Report Sheet)');
        $response->assertSee('FR.IA.03. Pertanyaan Mendukung Observasi (PMO)');
    }

    public function test_asesi_can_download_instrument_pdf_templates(): void
    {
        $asesi = User::where('role', 'ASESI')->first();
        $scheme = Scheme::first();
        $admin = User::where('role', 'ADMIN_LSP')->first();

        $schedule = Schedule::create([
            'scheme_id' => $scheme->id,
            'name' => 'Jadwal Download Template',
            'code' => 'SCH-TMPL-'.uniqid(),
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['IA01', 'IA01B', 'IA02', 'IA03'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);

        $assessment = Assessment::create([
            'asesi_id' => $asesi->id,
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'apl_status' => 'APPROVED',
        ]);

        // Download IA02 template
        $responseIa02 = $this->actingAs($asesi)->get(route('asesi.assessment.instrument.download-template', [$assessment, 'IA02']));
        $responseIa02->assertOk();
        $this->assertEquals('application/pdf', $responseIa02->headers->get('Content-Type'));

        // Download IA01B template
        $responseIa01b = $this->actingAs($asesi)->get(route('asesi.assessment.instrument.download-template', [$assessment, 'IA01B']));
        $responseIa01b->assertOk();
        $this->assertEquals('application/pdf', $responseIa01b->headers->get('Content-Type'));
    }

    public function test_ia02_asesi_view_displays_template_download_and_supports_photo_upload(): void
    {
        Storage::fake('local');
        $asesi = User::where('role', 'ASESI')->first();
        $scheme = Scheme::first();
        $admin = User::where('role', 'ADMIN_LSP')->first();

        $schedule = Schedule::create([
            'scheme_id' => $scheme->id,
            'name' => 'Jadwal IA02 Work',
            'code' => 'SCH-IA02-'.uniqid(),
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['IA02'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);

        $assessment = Assessment::create([
            'asesi_id' => $asesi->id,
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'apl_status' => 'APPROVED',
        ]);

        // View IA02 as asesi
        $viewRes = $this->actingAs($asesi)->get(route('asesi.assessment.instrument.view', [$assessment, 'IA02']));
        $viewRes->assertOk();
        $viewRes->assertSee('Template Lembar Uji Praktik FR.IA.02 (PDF Resmi BNSP)');
        $viewRes->assertSee(route('asesi.assessment.instrument.download-template', [$assessment, 'IA02']));
        $viewRes->assertSee('Petunjuk Uji Praktik');

        // Upload evidence image
        $image = UploadedFile::fake()->image('hasil_kerja_praktik.jpg', 800, 1000);
        $uploadRes = $this->actingAs($asesi)->post(
            route('asesi.assessment.instrument.upload-evidence', [$assessment, 'IA02']),
            ['evidence_image' => $image]
        );
        $uploadRes->assertSessionHasNoErrors();
        $uploadRes->assertRedirect();

        $record = AssessmentIaResponse::where('assessment_id', $assessment->id)->where('instrument_code', 'IA02')->first();
        $this->assertNotNull($record);
        $this->assertNotEmpty($record->data_json['evidence_image_path']);
        Storage::disk('local')->assertExists($record->data_json['evidence_image_path']);
    }

    public function test_ia05_cbt_flow_and_completion(): void
    {
        $asesi = User::where('role', 'ASESI')->first();
        $scheme = Scheme::first();
        $admin = User::where('role', 'ADMIN_LSP')->first();

        $schedule = Schedule::create([
            'scheme_id' => $scheme->id,
            'name' => 'Jadwal CBT Test',
            'code' => 'SCH-CBT-'.uniqid(),
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['IA05'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);

        $assessment = Assessment::create([
            'asesi_id' => $asesi->id,
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'apl_status' => 'APPROVED',
        ]);

        // Access CBT page
        $cbtPage = $this->actingAs($asesi)->get(route('asesi.assessment.instrument.ia05', $assessment));
        $cbtPage->assertOk();
        $cbtPage->assertSee('FR.IA.05. PERTANYAAN TERTULIS (CBT)');
        $cbtPage->assertSee('Kirim Jawaban CBT');

        // Submit answers
        $questions = QuestionBank::where('scheme_id', $scheme->id)->take(5)->get();
        $answers = [];
        foreach ($questions as $q) {
            $answers[$q->id] = $q->correct_answer;
        }

        $submitRes = $this->actingAs($asesi)->post(route('asesi.assessment.instrument.ia05.submit', $assessment), [
            'answers' => $answers,
        ]);
        $submitRes->assertRedirect(route('asesi.assessment.ruang-uji', $assessment));

        $ia05Resp = AssessmentIaResponse::where('assessment_id', $assessment->id)->where('instrument_code', 'IA05')->first();
        $this->assertNotNull($ia05Resp);
        $this->assertGreaterThanOrEqual(0, $ia05Resp->score);
    }

    public function test_asesi_view_and_feedback_signature_on_assessor_filled_instrument(): void
    {
        $asesor = User::where('role', 'ASESOR')->first();
        $asesi = User::where('role', 'ASESI')->first();
        $scheme = Scheme::first();
        $admin = User::where('role', 'ADMIN_LSP')->first();

        $schedule = Schedule::create([
            'scheme_id' => $scheme->id,
            'name' => 'Jadwal Assessor Instrument View',
            'code' => 'SCH-VIEW-'.uniqid(),
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['IA01B', 'IA01', 'IA03'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);

        $assessment = Assessment::create([
            'asesi_id' => $asesi->id,
            'assessor_id' => $asesor->id,
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'apl_status' => 'APPROVED',
        ]);

        // Assessor saves evaluation data for IA01B
        AssessmentIaResponse::create([
            'assessment_id' => $assessment->id,
            'instrument_code' => 'IA01B',
            'data_json' => [
                'items' => [
                    ['component' => 'Komponen A', 'specification' => '10 mm', 'measurement' => '10.02 mm', 'result' => 'SESUAI'],
                ],
            ],
            'assessor_notes' => 'Pengukuran sangat presisi dan teliti.',
            'assessor_signature' => 'data:image/png;base64,mockAsesorSign',
            'assessor_signed_at' => now(),
        ]);

        // Asesi views IA01B
        $viewRes = $this->actingAs($asesi)->get(route('asesi.assessment.instrument.view', [$assessment, 'IA01B']));
        $viewRes->assertOk();
        $viewRes->assertSee('Format Lembar Pemeriksaan FR.IA.01B (PDF Resmi BNSP)');
        $viewRes->assertSee('Pengukuran sangat presisi dan teliti.');

        // Asesi submits feedback and signature
        $feedbackRes = $this->actingAs($asesi)->post(
            route('asesi.assessment.instrument.feedback', [$assessment, 'IA01B']),
            [
                'asesi_feedback' => 'Saya setuju dengan hasil pengukuran dan penilaian asesor.',
                'signature_data' => 'data:image/png;base64,mockAsesiSignature123',
            ]
        );
        $feedbackRes->assertRedirect();

        $updated = AssessmentIaResponse::where('assessment_id', $assessment->id)->where('instrument_code', 'IA01B')->first();
        $this->assertEquals('Saya setuju dengan hasil pengukuran dan penilaian asesor.', $updated->asesi_feedback);
        $this->assertEquals('data:image/png;base64,mockAsesiSignature123', $updated->asesi_signature);
        $this->assertNotNull($updated->asesi_signed_at);
    }
}
