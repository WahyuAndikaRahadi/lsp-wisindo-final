<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        // Check header and instruments
        $response->assertSee('Instrumen Asesmen Terpilih');
        $response->assertSee('Pilih dan kerjakan instrumen asesmen kompetensi yang telah ditetapkan untuk Anda');

        // Check self-actionable instruments
        $response->assertSee('FR.IA.05');
        $response->assertSee('FR.IA.02');
        $response->assertSee(route('asesi.assessment.instrument.ia05', $assessment));
        $response->assertDontSee(route('asesi.assessment.instrument.download-template', [$assessment, 'IA02']));

        // Check assessor evaluation instruments
        $response->assertSee('FR.IA.01');
        $response->assertSee('FR.IA.01B');
        $response->assertSee('FR.IA.03');

        // Check that AK.01, top unlock navbar, and "Selesaikan Asesmen Mandiri" are NOT shown
        $response->assertDontSee('FR.AK.01');
        $response->assertDontSee('Alur Asesmen Kompetensi');
        $response->assertDontSee('Penyelesaian Akhir Asesi');
        $response->assertDontSee('Selesaikan Asesmen Mandiri');
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
            'ak01_agreed_at' => now(),
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

    public function test_ia02_asesi_view_is_review_only_without_upload_or_assessor_notes(): void
    {
        $asesor = User::where('role', 'ASESOR')->first();
        $asesi = User::where('role', 'ASESI')->first();
        $scheme = Scheme::first();
        $admin = User::where('role', 'ADMIN_LSP')->first();

        $schedule = Schedule::create([
            'scheme_id' => $scheme->id,
            'name' => 'Jadwal IA02 Review Only',
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
            'assessor_id' => $asesor->id,
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'apl_status' => 'APPROVED',
            'ak01_agreed_at' => now(),
            'asesi_signature' => 'data:image/png;base64,mockSignatureAsesi123',
        ]);

        AssessmentIaResponse::create([
            'assessment_id' => $assessment->id,
            'instrument_code' => 'IA02',
            'assessor_notes' => 'Catatan ini tidak boleh muncul di view asesi IA02.',
            'assessor_signature' => 'data:image/png;base64,mockSignatureAsesor123',
            'assessor_signed_at' => now(),
        ]);

        // View IA02 as asesi
        $viewRes = $this->actingAs($asesi)->get(route('asesi.assessment.instrument.view', [$assessment, 'IA02']));
        $viewRes->assertOk();

        // Must NOT have upload form or PDF download
        $viewRes->assertDontSee('Template Lembar Uji Praktik FR.IA.02 (PDF Resmi BNSP)');
        $viewRes->assertDontSee(route('asesi.assessment.instrument.download-template', [$assessment, 'IA02']));
        $viewRes->assertDontSee('Pilih Foto Lembar Jawaban Kertas:');
        $viewRes->assertDontSee('Unggah Foto Bukti');

        // Must NOT have assessor notes card
        $viewRes->assertDontSee('Catatan Rekomendasi & Hasil Asesmen');
        $viewRes->assertDontSee('Catatan ini tidak boleh muncul di view asesi IA02.');

        // Must have demonstration instructions and signatures
        $viewRes->assertSee('FR.IA.02. TUGAS PRAKTIK DEMONSTRASI');
        $viewRes->assertSee('Tugas Praktik Demonstrasi');
        $viewRes->assertSee('Tanda Tangan Asesor');
        $viewRes->assertSee('Tanda Tangan Asesi');
    }

    public function test_ia01_and_ia03_asesi_views_only_show_assessor_evaluation_and_signatures(): void
    {
        $asesor = User::where('role', 'ASESOR')->first();
        $asesi = User::where('role', 'ASESI')->first();
        $scheme = Scheme::first();
        $admin = User::where('role', 'ADMIN_LSP')->first();

        $schedule = Schedule::create([
            'scheme_id' => $scheme->id,
            'name' => 'Jadwal Review IA01 and IA03',
            'code' => 'SCH-REV-'.uniqid(),
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['IA01', 'IA03'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);

        $assessment = Assessment::create([
            'asesi_id' => $asesi->id,
            'assessor_id' => $asesor->id,
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'apl_status' => 'APPROVED',
            'ak01_agreed_at' => now(),
            'asesi_signature' => 'data:image/png;base64,mockAsesiSign',
        ]);

        AssessmentIaResponse::create([
            'assessment_id' => $assessment->id,
            'instrument_code' => 'IA01',
            'assessor_notes' => 'Praktik observasi dilakukan dengan sangat aman dan teliti.',
            'assessor_signature' => 'data:image/png;base64,mockAsesorSign',
            'assessor_signed_at' => now(),
        ]);

        AssessmentIaResponse::create([
            'assessment_id' => $assessment->id,
            'instrument_code' => 'IA03',
            'assessor_notes' => 'Seluruh pertanyaan lisan teknis dijawab dengan jelas dan tepat.',
            'assessor_signature' => 'data:image/png;base64,mockAsesorSign',
            'assessor_signed_at' => now(),
        ]);

        // IA01 view: No KUK observation table/summary, only evaluation and signature
        $resIa01 = $this->actingAs($asesi)->get(route('asesi.assessment.instrument.view', [$assessment, 'IA01']));
        $resIa01->assertOk();
        $resIa01->assertDontSee('Ringkasan Hasil Observasi Aktivitas Praktik');
        $resIa01->assertDontSee('Total Kriteria (KUK)');
        $resIa01->assertSee('FR.IA.01. CEKLIS OBSERVASI AKTIVITAS PRAKTIK');
        $resIa01->assertSee('Catatan Rekomendasi & Hasil Asesmen', false);
        $resIa01->assertSee('Praktik observasi dilakukan dengan sangat aman dan teliti.');
        $resIa01->assertSee('Tanda Tangan Asesor');
        $resIa01->assertSee('Tanda Tangan Asesi');

        // IA03 view: No PMO questions list, only evaluation and signature
        $resIa03 = $this->actingAs($asesi)->get(route('asesi.assessment.instrument.view', [$assessment, 'IA03']));
        $resIa03->assertOk();
        $resIa03->assertDontSee('Hasil Tanya Jawab Teknis Lisan / Wawancara');
        $resIa03->assertDontSee('Kunci Jawaban yang Diharapkan:');
        $resIa03->assertSee('FR.IA.03. PERTANYAAN MENDUKUNG OBSERVASI (PMO)');
        $resIa03->assertSee('Catatan Rekomendasi & Hasil Asesmen', false);
        $resIa03->assertSee('Seluruh pertanyaan lisan teknis dijawab dengan jelas dan tepat.');
        $resIa03->assertSee('Tanda Tangan Asesor');
        $resIa03->assertSee('Tanda Tangan Asesi');
    }

    public function test_ia01b_view_has_clean_upload_space_without_bloated_tables_or_duplicate_downloads(): void
    {
        $asesi = User::where('role', 'ASESI')->first();
        $scheme = Scheme::first();
        $admin = User::where('role', 'ADMIN_LSP')->first();

        $schedule = Schedule::create([
            'scheme_id' => $scheme->id,
            'name' => 'Jadwal IA01B Clean',
            'code' => 'SCH-IA01B-'.uniqid(),
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['IA01B'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);

        $assessment = Assessment::create([
            'asesi_id' => $asesi->id,
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'apl_status' => 'APPROVED',
            'ak01_agreed_at' => now(),
        ]);

        $res = $this->actingAs($asesi)->get(route('asesi.assessment.instrument.view', [$assessment, 'IA01B']));
        $res->assertOk();

        // Must NOT have PDF template download
        $res->assertDontSee('Unduh Template PDF');
        $res->assertDontSee('Unduh Report Sheet PDF');
        $res->assertDontSee(route('asesi.assessment.instrument.download-template', [$assessment, 'IA01B']));

        // Must NOT have bloated stats or empty digital measurement table
        $res->assertDontSee('Total Item Ukur');
        $res->assertDontSee('0% COMPLIANT');
        $res->assertDontSee('Belum ada data rekaman item pengukuran FR.IA.01B');

        // Must have the clean physical upload space
        $res->assertSee('Ruang Unggah Lembar Pemeriksaan Teknis Fisik');
        $res->assertSee('Pilih Berkas Foto / Scan PDF Lembar Report Sheet:');
        $res->assertSee('Unggah Berkas Fisik');

        // Must NOT have bloated signature explanation paragraphs
        $res->assertDontSee('Disematkan otomatis secara sah dari data permohonan sertifikasi Anda');
        $res->assertDontSee('Tanda tangan digital Anda telah disematkan secara otomatis pada instrumen ini');
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
            'ak01_agreed_at' => now(),
        ]);

        // Access CBT page
        $cbtPage = $this->actingAs($asesi)->get(route('asesi.assessment.instrument.ia05', $assessment));
        $cbtPage->assertOk();
        $cbtPage->assertSee('FR.IA.05. PERTANYAAN TERTULIS (CBT)');
        $cbtPage->assertSee('Nomor Soal');
        $cbtPage->assertSee('Ragu-ragu');
        $cbtPage->assertSee('Kirim Jawaban CBT');

        // Test jQuery AJAX Autosave
        $questions = QuestionBank::where('scheme_id', $scheme->id)->take(5)->get();
        $firstQ = $questions->first();
        $autosaveRes = $this->actingAs($asesi)->postJson(route('asesi.assessment.instrument.ia05.autosave', $assessment), [
            'answers' => [$firstQ->id => 'A'],
            'doubtful' => [$firstQ->id],
        ]);
        $autosaveRes->assertOk();
        $autosaveRes->assertJson(['success' => true]);

        // Draft response exists, but score is still null and instruments are NOT completed
        $draftResp = AssessmentIaResponse::where('assessment_id', $assessment->id)->where('instrument_code', 'IA05')->first();
        $this->assertNotNull($draftResp);
        $this->assertNull($draftResp->score);
        $this->assertFalse($assessment->fresh()->hasCompletedAllAssignedInstruments());

        // Submit final answers
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
            'ak01_agreed_at' => now(),
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
        $viewRes->assertDontSee('Format Lembar Pemeriksaan FR.IA.01B (PDF Resmi BNSP)');
        $viewRes->assertSee('FR.IA.01B. LEMBAR PENGUKURAN TEKNIS (REPORT SHEET)');
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
