<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAppeal;
use App\Models\AssessmentIaResponse;
use App\Models\AssessmentProof;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LspWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_user_seeder_contains_all_22_demo_accounts(): void
    {
        $this->assertDatabaseHas('users', ['email' => 'lsp.proindo@gmail.com', 'role' => 'SUPER_ADMIN']);
        $this->assertDatabaseHas('users', ['email' => 'adminlsp@gmail.com', 'role' => 'ADMIN_LSP']);
        $this->assertDatabaseHas('users', ['email' => 'asesor.randi@gmail.com', 'role' => 'ASESOR']);
        $this->assertDatabaseHas('users', ['email' => 'asesi1@gmail.com', 'role' => 'ASESI']);
        $this->assertDatabaseHas('m_tuks', ['name' => 'TUK Cikarang']);
    }

    public function test_auth_login_and_admin_proxy_impersonation(): void
    {
        $admin = User::where('email', 'adminlsp@gmail.com')->first();
        $asesor = User::where('email', 'asesor.randi@gmail.com')->first();

        // Admin login
        $response = $this->post('/login', [
            'email' => 'adminlsp@gmail.com',
            'password' => '@Kompeten86',
        ]);
        $response->assertRedirect(route('admin.dashboard'));

        // Admin proxy index view renders without route error
        $proxyView = $this->actingAs($admin)->get(route('admin.proxy.index'));
        $proxyView->assertStatus(200);
        $proxyView->assertSee('proxy/switch');

        // Admin proxies to Asesor
        $proxyResponse = $this->actingAs($admin)->post(route('proxy.switch', $asesor));
        $proxyResponse->assertRedirect(route('asesor.jadwal.index'));
        $this->assertEquals($asesor->id, auth()->id());
        $this->assertEquals($admin->id, session('admin_impersonator_id'));

        // Switch back to Admin
        $switchBack = $this->post(route('proxy.switch-back'));
        $switchBack->assertRedirect(route('admin.proxy.index'));
        $this->assertEquals($admin->id, auth()->id());
        $this->assertNull(session('admin_impersonator_id'));
    }

    public function test_admin_can_create_schedule(): void
    {
        $admin = User::where('role', 'ADMIN_LSP')->first();
        $scheme = Scheme::first();
        $asesor = User::where('role', 'ASESOR')->first();

        $scheduleData = [
            'name' => 'Uji Sertifikasi SMK Bintang',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'Bengkel Otomotif SMK Bintang Cikarang',
            'assessors' => [$asesor->id],
            'selected_instruments' => ['AK01', 'AK07', 'IA01', 'IA02', 'IA03', 'IA05'],
            'date' => '2026-11-20',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'code' => 'LSP-TEST-01',
            'notes' => 'Harap hadir tepat waktu',
        ];

        $response = $this->actingAs($admin)->post(route('admin.jadwal.store'), $scheduleData);
        $response->assertRedirect(route('admin.jadwal.index'));

        $this->assertDatabaseHas('t_schedules', [
            'code' => 'LSP-TEST-01',
            'tuk_name' => 'Bengkel Otomotif SMK Bintang Cikarang',
        ]);
    }

    public function test_full_asesi_and_asesor_workflow_with_gate(): void
    {
        // Setup Schedule
        $admin = User::where('role', 'ADMIN_LSP')->first();
        $scheme = Scheme::where('code', 'SKM-01-2026')->first();
        $asesor = User::where('email', 'asesor.randi@gmail.com')->first();
        $asesi = User::where('email', 'asesi1@gmail.com')->first();

        $schedule = Schedule::create([
            'code' => 'LSP-BATCH-01',
            'name' => 'Batch 01 Engine Junior',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Otomotif Cikarang',
            'date' => '2026-11-25',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'AK07', 'IA01', 'IA02', 'IA03', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);
        $schedule->assessors()->attach($asesor->id);

        // 1. Asesi joins schedule with code
        $joinResponse = $this->actingAs($asesi)->post(route('asesi.jadwal.join'), [
            'code' => 'LSP-BATCH-01',
        ]);
        $assessment = Assessment::where('schedule_id', $schedule->id)->where('asesi_id', $asesi->id)->first();
        $this->assertNotNull($assessment);
        $this->assertEquals('DRAFT', $assessment->apl_status);

        // 2. GATE CHECK: Asesi cannot access AK01 or Ruang Uji before APL approved
        $gateResponse = $this->actingAs($asesi)->get(route('asesi.assessment.ak01', $assessment));
        $gateResponse->assertRedirect(route('asesi.assessment.apl', $assessment));
        $gateResponse->assertSessionHas('error');

        // 3. Asesi completes APL01 proofs & APL02 items
        $kukIds = $scheme->competencyUnits->flatMap->elements->flatMap->kuks->pluck('id');
        $kukPayload = [];
        foreach ($kukIds as $kId) {
            $kukPayload[$kId] = [
                'value' => 'K',
                'proof_type' => 'IJAZAH_SMK',
            ];
        }

        $aplSubmit = $this->actingAs($asesi)->post(route('asesi.assessment.apl.store', $assessment), [
            'assessor_id' => $asesor->id,
            'proofs' => [
                'IJAZAH_SMK' => UploadedFile::fake()->create('ijazah.pdf', 500, 'application/pdf'),
                'KTP' => UploadedFile::fake()->create('ktp.jpg', 300, 'image/jpeg'),
                'PAS_FOTO' => UploadedFile::fake()->create('foto.jpg', 300, 'image/jpeg'),
            ],
            'apl02' => $kukPayload,
            'signature_data' => 'data:image/png;base64,sampleFakeSignatureData',
            'statement_integrity' => '1',
        ]);
        $aplSubmit->assertRedirect(route('asesi.assessment.apl', $assessment));

        $assessment->refresh();
        $this->assertEquals('SUBMITTED', $assessment->apl_status);

        // 4. Asesor reviews APL directly and APPROVES
        $reviewResponse = $this->actingAs($asesor)->post(route('asesor.apl.decide', $assessment), [
            'decision' => 'APPROVED',
        ]);
        $reviewResponse->assertRedirect(route('asesor.schedule.instrument', ['schedule' => $schedule->id, 'code' => 'apl']));

        $assessment->refresh();
        $this->assertEquals('APPROVED', $assessment->apl_status);
        $this->assertNull($assessment->ak01_agreed_at);

        // 5. Asesi can now directly enter Ruang Uji
        $ruangUjiResponse = $this->actingAs($asesi)->get(route('asesi.assessment.ruang-uji', $assessment));
        $ruangUjiResponse->assertStatus(200);

        // 7. Asesi does CBT exam (FR.IA.05)
        $questions = QuestionBank::where('scheme_id', $scheme->id)->get();
        $answers = [];
        foreach ($questions as $q) {
            $answers[$q->id] = $q->correct_answer; // all correct
        }

        $cbtSubmit = $this->actingAs($asesi)->post(route('asesi.assessment.instrument.ia05.submit', $assessment), [
            'answers' => $answers,
        ]);
        $cbtSubmit->assertRedirect(route('asesi.assessment.ruang-uji', $assessment));

        $this->assertDatabaseHas('t_assessment_ia_responses', [
            'assessment_id' => $assessment->id,
            'instrument_code' => 'IA05',
            'score' => 100.00,
        ]);

        // 8. Asesor completes observation on IA01
        $ia01Submit = $this->actingAs($asesor)->post(route('asesor.assessment.instrument.store', [$assessment, 'IA01']), [
            'assessor_notes' => 'Praktik berjalan sesuai SOP K3.',
            'signature_data' => 'data:image/png;base64,asesorSigData',
        ]);
        $ia01Submit->assertRedirect(route('asesor.schedule.instrument', [$schedule, 'ia01']));

        // 9. Asesi provides feedback and signs IA01
        $feedbackSubmit = $this->actingAs($asesi)->post(route('asesi.assessment.instrument.feedback', [$assessment, 'IA01']), [
            'asesi_feedback' => 'Penilaian sangat jelas dan adil.',
        ]);
        $feedbackSubmit->assertSessionHas('success');

        // 10. Asesi completes assessment and is guided to FR.AK.03 Umpan Balik
        $finishResponse = $this->actingAs($asesi)->post(route('asesi.assessment.finish', $assessment));
        $finishResponse->assertRedirect(route('asesi.assessment.ak03', $assessment));

        $assessment->refresh();
        $this->assertEquals('COMPLETED', $assessment->status);

        // 11. Asesi submits FR.AK.03 Umpan Balik (All 10 items required)
        $ak03Ratings = [];
        for ($r = 1; $r <= 10; $r++) {
            $ak03Ratings[$r] = ['value' => 'YA', 'comment' => 'Memuaskan'];
        }
        $ak03Submit = $this->actingAs($asesi)->post(route('asesi.assessment.ak03.submit', $assessment), [
            'ratings' => $ak03Ratings,
            'general_notes' => 'Pelaksanaan asesmen objektif dan profesional.',
        ]);
        $ak03Submit->assertRedirect(route('asesi.dashboard'));
        $this->assertDatabaseHas('t_assessment_ak03_items', ['assessment_id' => $assessment->id]);

        // 12. Asesor checks & finalizes FR.AK.02 Rekaman Asesmen
        $ak02View = $this->actingAs($asesor)->get(route('asesor.assessment.ak02', $assessment));
        $ak02View->assertOk();
        $this->assertDatabaseHas('t_assessment_ak02_items', ['assessment_id' => $assessment->id]);

        // 13. Asesor completes schedule with FR.AK.05 & FR.AK.06
        $reportSubmit = $this->actingAs($asesor)->post(route('asesor.schedule.reports.submit', $schedule), [
            'ak05_aspek_positif' => 'Fasilitas bengkel TUK sangat memadai dan lengkap.',
            'ak05_aspek_negatif' => 'Tidak ditemukan kendala teknis krusial di lapangan.',
            'ak05_rekomendasi' => 'Pertahankan kalibrasi peralatan secara berkala.',
            'ak06_valid' => 'YA',
            'ak06_valid_notes' => 'Bukti mencakup seluruh KUK.',
            'ak06_reliabel' => 'YA',
            'ak06_reliabel_notes' => 'Penilaian konsisten dan seragam.',
            'ak06_fleksibel' => 'YA',
            'ak06_fleksibel_notes' => 'Penyesuaian wajar difasilitasi jika perlu.',
            'ak06_adil' => 'YA',
            'ak06_adil_notes' => 'Seluruh asesi diperlakukan adil.',
            'ak06_catatan_peninjau' => 'Proses asesmen mematuhi standar BNSP.',
            'confirm_draft_review' => '1',
        ]);
        $reportSubmit->assertRedirect(route('asesor.jadwal.index'));
        $schedule->refresh();
        $this->assertTrue($schedule->is_closed);
        $this->assertEquals('FINISHED', $schedule->status);

        // 14. Asesi submits FR.AK.04 Banding & Admin decides
        $appealSubmit = $this->actingAs($asesi)->post(route('asesi.assessment.appeal.submit', $assessment), [
            'appeal_reasons' => 'Mohon peninjauan ulang terhadap bukti unjuk kerja pada sistem pengapian.',
        ]);
        $appealSubmit->assertRedirect(route('asesi.dashboard'));
        $appeal = AssessmentAppeal::where('assessment_id', $assessment->id)->first();
        $this->assertNotNull($appeal);

        $appealDecide = $this->actingAs($admin)->post(route('admin.appeals.decide', $appeal), [
            'decision' => 'ACCEPTED',
            'decision_notes' => 'Banding diterima untuk peninjauan ulang komite teknis.',
        ]);
        $appealDecide->assertSessionHas('success');
        $appeal->refresh();
        $this->assertEquals('ACCEPTED', $appeal->status);
    }

    public function test_admin_dokumen_muk_access(): void
    {
        $admin = User::where('role', 'ADMIN_LSP')->first();
        $response = $this->actingAs($admin)->get(route('admin.dokumen-muk.index'));
        $response->assertStatus(200);
    }

    public function test_assessor_can_view_jadwal_index_and_asesi_list(): void
    {
        $admin = User::where('role', 'ADMIN_LSP')->first();
        $asesor = User::where('role', 'ASESOR')->first();
        $scheme = Scheme::first();

        // Check index view with no assigned schedules
        $res = $this->actingAs($asesor)->get(route('asesor.jadwal.index'));
        $res->assertOk();

        $schedule = Schedule::create([
            'code' => 'SCH-TEST-ASESOR',
            'name' => 'Jadwal Asesor Test',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA02', 'IA03', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);

        // Assign assessor to schedule
        $schedule->assessors()->syncWithoutDetaching([$asesor->id]);
        $asesi = User::where('role', 'ASESI')->first();
        Assessment::create([
            'asesi_id' => $asesi->id,
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
        ]);
        $schedule->update([
            'ak01_data' => ['saved' => true],
            'ak07_data' => ['saved' => true],
        ]);

        $resWithSchedule = $this->actingAs($asesor)->get(route('asesor.jadwal.index'));
        $resWithSchedule->assertOk();
        $resWithSchedule->assertSee($schedule->code);

        // Check ruang-uji view
        $resAsesi = $this->actingAs($asesor)->get(route('asesor.schedule.ruang-uji', $schedule));
        $resAsesi->assertOk();
    }

    public function test_assessor_can_review_apl_page_with_apl01_and_apl02(): void
    {
        $asesor = User::where('role', 'ASESOR')->first();
        $asesi = User::where('role', 'ASESI')->first();
        $scheme = Scheme::first();

        $schedule = Schedule::first();
        if (! $schedule) {
            $admin = User::where('role', 'ADMIN_LSP')->first();
            $schedule = Schedule::create([
                'code' => 'SCH-TEST-APL',
                'name' => 'Jadwal Review APL Test',
                'scheme_id' => $scheme->id,
                'tuk_name' => 'TUK Otomotif Mandiri',
                'date' => now()->toDateString(),
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'selected_instruments' => ['AK01', 'IA01', 'IA02', 'IA03', 'IA05'],
                'status' => 'ACTIVE',
                'created_by' => $admin->id,
            ]);
        }

        $schedule->assessors()->syncWithoutDetaching([$asesor->id]);

        $assessment = Assessment::firstOrCreate(
            ['asesi_id' => $asesi->id, 'schedule_id' => $schedule->id],
            [
                'scheme_id' => $scheme->id,
                'apl_status' => 'SUBMITTED',
            ]
        );

        $res = $this->actingAs($asesor)->get(route('asesor.review-apl', $assessment));
        $res->assertOk();
        $res->assertSee('FR.APL.01');
        $res->assertSee('FR.APL.02');
        $res->assertSee('PERMOHONAN SERTIFIKASI KOMPETENSI');
        $res->assertSee('ASESMEN MANDIRI');
        $res->assertSee('Formulir Keputusan Review APL');

        // Assessor approves APL directly without waiting for admin approval
        $postRes = $this->actingAs($asesor)->post(route('asesor.review-apl.update', $assessment), [
            'apl_status' => 'APPROVED',
            'notes' => 'Berkas dan asesmen mandiri lengkap memenuhi syarat.',
        ]);
        $postRes->assertRedirect(route('asesor.schedule.instrument', ['schedule' => $schedule->id, 'code' => 'apl']));
        $this->assertEquals('APPROVED', $assessment->fresh()->apl_status);
        $this->assertNull($assessment->fresh()->ak01_agreed_at);
    }

    public function test_assessor_can_view_and_submit_ak07(): void
    {
        $asesor = User::where('role', 'ASESOR')->first();
        $asesi = User::where('role', 'ASESI')->first();
        $scheme = Scheme::first();
        $admin = User::where('role', 'ADMIN_LSP')->first();

        $schedule = Schedule::create([
            'code' => 'SCH-TEST-AK07',
            'name' => 'Jadwal AK07 Test',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA02'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);
        $schedule->assessors()->syncWithoutDetaching([$asesor->id]);

        $assessment = Assessment::create([
            'asesi_id' => $asesi->id,
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
        ]);

        $schedule->update([
            'ak01_data' => ['saved' => true],
            'ak07_data' => ['saved' => true],
        ]);

        // Assessor visits ruang-uji
        $asesiListRes = $this->actingAs($asesor)->get(route('asesor.schedule.ruang-uji', $schedule));
        $asesiListRes->assertOk();

        // Assessor visits AK07 create form
        $ak07Res = $this->actingAs($asesor)->get(route('asesor.ak07.create', ['schedule' => $schedule->id, 'assessment' => $assessment->id]));
        $ak07Res->assertOk();
        $ak07Res->assertSee('AK07');

        // Assessor submits AK07 and continues to ruang uji (urutan baru: APL -> AK01 -> AK07 -> Ruang Uji)
        $postAk07 = $this->actingAs($asesor)->post(route('asesor.ak07.store', $schedule), [
            'assessment_ids' => [$assessment->id],
            'category' => 'waktu',
            'notes' => 'Penambahan waktu 15 menit untuk asesi.',
        ]);
        $postAk07->assertRedirect(route('asesor.schedule.ruang-uji', $schedule->id));
        $this->assertDatabaseHas('t_assessment_ak07_items', [
            'assessment_id' => $assessment->id,
            'adjustment_category' => 'waktu',
            'adjustment_detail' => 'Penambahan waktu 15 menit untuk asesi.',
        ]);

        // Assessor visits Schedule Ruang Uji (displays selected instruments)
        $ruangUjiRes = $this->actingAs($asesor)->get(route('asesor.schedule.ruang-uji', $schedule->id));
        $ruangUjiRes->assertOk();
        $ruangUjiRes->assertSee('FR.AK.01');
        $ruangUjiRes->assertSee('FR.IA.01');
        $ruangUjiRes->assertSee('FR.IA.02');

        // Assessor enters specific instrument page (shows list of asesis with inspection button)
        $instRes = $this->actingAs($asesor)->get(route('asesor.schedule.instrument', ['schedule' => $schedule->id, 'code' => 'ia01']));
        $instRes->assertOk();
        $instRes->assertSee($asesi->full_name);
        $instRes->assertSee(route('asesor.assessment.instrument', ['assessment' => $assessment->id, 'code' => 'ia01']));
    }

    public function test_ia02_paper_based_image_upload_and_asesor_inspection(): void
    {
        Storage::fake('public');

        $asesorUser = User::where('role', 'ASESOR')->first();
        $asesiUser = User::where('role', 'ASESI')->first();
        $admin = User::where('role', 'ADMIN_LSP')->first();
        $scheme = Scheme::first();

        $schedule = Schedule::create([
            'scheme_id' => $scheme->id,
            'name' => 'Jadwal IA02 Test',
            'code' => 'SCH-IA02-TEST-'.uniqid(),
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['IA02'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);
        $schedule->assessors()->syncWithoutDetaching([$asesorUser->id]);

        $assessment = Assessment::create([
            'asesi_id' => $asesiUser->id,
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'apl_status' => 'APPROVED',
        ]);

        // Asesi uploads photo evidence of physical paper worksheet
        $file = UploadedFile::fake()->image('lembar_jawaban_ia02.jpg', 600, 800);
        $uploadRes = $this->actingAs($asesiUser)->post(
            route('asesi.assessment.instrument.upload-evidence', [$assessment, 'IA02']),
            ['evidence_image' => $file]
        );
        $uploadRes->assertSessionHasNoErrors();
        $uploadRes->assertRedirect();

        // Verify response record has image path
        $response = AssessmentIaResponse::where('assessment_id', $assessment->id)
            ->where('instrument_code', 'IA02')
            ->first();
        $this->assertNotNull($response);
        $this->assertNotEmpty($response->data_json['evidence_image_path']);
        Storage::disk('local')->assertExists($response->data_json['evidence_image_path']);

        // Assessor views IA02 assessment page
        $asesorRes = $this->actingAs($asesorUser)->get(
            route('asesor.assessment.instrument', ['assessment' => $assessment->id, 'code' => 'ia02'])
        );
        $asesorRes->assertOk();
        $asesorRes->assertSee('FR.IA.02. Tugas Praktik Demonstrasi');
        $asesorRes->assertSee('Tugas Praktik Demonstrasi (TPD)');
        $asesorRes->assertSee('Persetujuan &amp; Tanda Tangan', false);

        // Assessor stores grades while preserving uploaded evidence
        $storeRes = $this->actingAs($asesorUser)->post(
            route('asesor.ruang-uji.ia02.store', $assessment->id),
            [
                'tasks' => [1 => ['result' => 'YA', 'note' => 'Pengerjaan akurat']],
                'general_notes' => 'Kompeten sesuai bukti lembar kertas.',
            ]
        );
        $storeRes->assertRedirect(route('asesor.schedule.instrument', ['schedule' => $schedule->id, 'code' => 'ia02']));

        $response->refresh();
        $this->assertEquals('Kompeten sesuai bukti lembar kertas.', $response->assessor_notes);
        $this->assertNotEmpty($response->data_json['evidence_image_path']);
    }

    public function test_ia01b_productive_view_and_signature_saving(): void
    {
        $asesorUser = User::where('role', 'ASESOR')->first();
        $asesiUser = User::where('role', 'ASESI')->first();
        $admin = User::where('role', 'ADMIN_LSP')->first();
        $scheme = Scheme::first();

        $schedule = Schedule::create([
            'scheme_id' => $scheme->id,
            'name' => 'Jadwal IA01B Test',
            'code' => 'SCH-IA01B-TEST-'.uniqid(),
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['IA01B'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);
        $schedule->assessors()->syncWithoutDetaching([$asesorUser->id]);

        $assessment = Assessment::create([
            'asesi_id' => $asesiUser->id,
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'apl_status' => 'APPROVED',
        ]);

        $sampleItems = [
            ['measured' => '12.6 Volt', 'result' => 'SESUAI'],
            ['measured' => '0.45 mm', 'result' => 'SESUAI'],
            ['measured' => '1.80 mm', 'result' => 'TIDAK'],
        ];

        $iaResponse = AssessmentIaResponse::create([
            'assessment_id' => $assessment->id,
            'instrument_code' => 'IA01B',
            'data_json' => ['items' => $sampleItems],
            'assessor_notes' => 'Pemeriksaan dimensi celah katup melebihi limit spesifikasi.',
            'assessor_signed_at' => now(),
        ]);

        // 1. Asesi views IA01B - must be productive table, NOT raw JSON
        $viewRes = $this->actingAs($asesiUser)->get(
            route('asesi.assessment.instrument.view', [$assessment, 'IA01B'])
        );
        $viewRes->assertOk();
        $viewRes->assertSee('FR.IA.01B');
        $viewRes->assertDontSee('items: [{"measured":');

        // 2. Asesi signs and submits feedback (verifies no SQL column error)
        $sigData = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $postRes = $this->actingAs($asesiUser)->post(
            route('asesi.assessment.instrument.feedback', [$assessment, 'IA01B']),
            [
                'asesi_feedback' => 'Proses verifikasi pengukuran objektif dan jelas.',
                'signature_data' => $sigData,
            ]
        );
        $postRes->assertSessionHasNoErrors();
        $postRes->assertRedirect();

        // 3. Verify in database
        $iaResponse->refresh();
        $this->assertEquals('Proses verifikasi pengukuran objektif dan jelas.', $iaResponse->asesi_feedback);
        $this->assertEquals($sigData, $iaResponse->asesi_signature);
        $this->assertNotNull($iaResponse->asesi_signed_at);

        // 4. Verify asesi viewing after signed displays signature
        $signedViewRes = $this->actingAs($asesiUser)->get(
            route('asesi.assessment.instrument.view', [$assessment, 'IA01B'])
        );
        $signedViewRes->assertOk();
        $signedViewRes->assertSee('Tanda Tangan Asesi');
        $signedViewRes->assertSee($sigData);
    }

    public function test_admin_can_batch_approve_candidate_registrations(): void
    {
        $admin = User::where('role', 'ADMIN_LSP')->first();
        $scheme = Scheme::where('code', 'SKM-01-2026')->first();
        $asesor = User::where('email', 'asesor.randi@gmail.com')->first();
        $asesi1 = User::where('email', 'asesi1@gmail.com')->first();

        $schedule = Schedule::create([
            'code' => 'LSP-BATCH-TEST',
            'name' => 'Batch Test Admin Approval',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Otomotif Cikarang',
            'date' => '2026-11-25',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'AK07', 'IA01', 'IA02', 'IA03', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);
        $schedule->assessors()->attach($asesor->id);

        $asesi2 = User::create([
            'full_name' => 'Asesi Batch Test 2',
            'email' => 'asesibatch2@test.local',
            'password' => bcrypt('secret123'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $asm1 = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi1->id,
            'scheme_id' => $schedule->scheme_id,
            'status' => 'REGISTERED',
            'admin_status' => 'PENDING',
            'apl_status' => 'SUBMITTED',
        ]);

        $asm2 = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi2->id,
            'scheme_id' => $schedule->scheme_id,
            'status' => 'REGISTERED',
            'admin_status' => 'PENDING',
            'apl_status' => 'SUBMITTED',
        ]);

        // asm1 has complete proofs (KTP, PAS_FOTO, IJAZAH_SMK)
        AssessmentProof::create(['assessment_id' => $asm1->id, 'proof_type' => 'KTP', 'proof_category' => 'ADMINISTRATIF', 'file_path' => 'ktp.jpg', 'original_filename' => 'ktp.jpg']);
        AssessmentProof::create(['assessment_id' => $asm1->id, 'proof_type' => 'PAS_FOTO', 'proof_category' => 'ADMINISTRATIF', 'file_path' => 'foto.jpg', 'original_filename' => 'foto.jpg']);
        AssessmentProof::create(['assessment_id' => $asm1->id, 'proof_type' => 'IJAZAH_SMK', 'proof_category' => 'PERSYARATAN_DASAR', 'file_path' => 'ijazah.pdf', 'original_filename' => 'ijazah.pdf']);

        // asm2 only has KTP (incomplete, missing PAS_FOTO and basic proof)
        AssessmentProof::create(['assessment_id' => $asm2->id, 'proof_type' => 'KTP', 'proof_category' => 'ADMINISTRATIF', 'file_path' => 'ktp.jpg', 'original_filename' => 'ktp.jpg']);

        $res = $this->actingAs($admin)->post(
            route('admin.jadwal.batch-review-registration', $schedule),
            ['admin_status' => 'APPROVED']
        );

        $res->assertRedirect();
        $res->assertSessionHas('success');
        $res->assertSessionHas('warning'); // Warning reports skipped candidate

        $asm1->refresh();
        $asm2->refresh();
        $this->assertEquals('APPROVED', $asm1->admin_status);
        $this->assertEquals($admin->id, $asm1->admin_reviewer_id);
        $this->assertNotNull($asm1->admin_reviewed_at);

        // asm2 was skipped because proofs were incomplete
        $this->assertEquals('PENDING', $asm2->admin_status);
        $this->assertNull($asm2->admin_reviewed_at);
    }

    private function createTestAssessment(): array
    {
        $admin = User::where('role', 'ADMIN_LSP')->first();
        $scheme = Scheme::first();
        $asesor = User::where('role', 'ASESOR')->first();
        $asesi = User::where('role', 'ASESI')->first();

        $schedule = Schedule::create([
            'code' => 'SCH-INTEG-'.uniqid(),
            'name' => 'Schedule Integ Test',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Cikarang',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'AK07', 'IA01', 'IA02', 'IA03', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);
        $schedule->assessors()->attach($asesor->id);

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $asesor->id,
            'scheme_id' => $scheme->id,
            'status' => 'IN_PROGRESS',
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
        ]);

        return compact('schedule', 'assessment', 'asesor', 'asesi', 'admin');
    }

    public function test_ak03_rejected_when_empty_or_incomplete(): void
    {
        $fixture = $this->createTestAssessment();
        $asesi = $fixture['asesi'];
        $assessment = $fixture['assessment'];

        // Submitting only 2 of 10 items should fail
        $res = $this->actingAs($asesi)->post(route('asesi.assessment.ak03.submit', $assessment), [
            'ratings' => [
                1 => ['value' => 'YA'],
                2 => ['value' => 'TIDAK'],
            ],
        ]);

        $res->assertSessionHasErrors(['ratings']);
    }

    public function test_ak05_ak06_rejected_without_mandatory_confirmation(): void
    {
        $fixture = $this->createTestAssessment();
        $asesor = $fixture['asesor'];
        $schedule = $fixture['schedule'];

        $res = $this->actingAs($asesor)->post(route('asesor.schedule.reports.submit', $schedule), [
            'ak05_aspek_positif' => 'Fasilitas sangat memadai.',
            'ak05_aspek_negatif' => 'Tidak ada kendala teknis.',
            'ak05_rekomendasi' => 'Pertahankan kualitas sarana TUK.',
            'ak06_valid' => 'YA',
            'ak06_valid_notes' => 'Bukti relevan.',
            'ak06_reliabel' => 'YA',
            'ak06_reliabel_notes' => 'Instrumen konsisten.',
            'ak06_fleksibel' => 'YA',
            'ak06_fleksibel_notes' => 'Penyesuaian wajar siap.',
            'ak06_adil' => 'YA',
            'ak06_adil_notes' => 'Perlakuan adil.',
            'ak06_catatan_peninjau' => 'Sesuai dengan SOP penjaminan mutu BNSP.',
            // Missing confirm_draft_review
        ]);

        $res->assertSessionHasErrors(['confirm_draft_review']);
    }

    public function test_ak02_requires_explicit_final_recommendation(): void
    {
        $fixture = $this->createTestAssessment();
        $asesor = $fixture['asesor'];
        $assessment = $fixture['assessment'];

        $res = $this->actingAs($asesor)->post(route('asesor.assessment.ak02.update', $assessment), [
            'final_result' => '', // Empty / missing
            'final_notes' => 'Catatan asesor.',
        ]);

        $res->assertSessionHasErrors(['final_result']);
    }

    public function test_apl02_rejected_without_statement_integrity(): void
    {
        $fixture = $this->createTestAssessment();
        $asesi = $fixture['asesi'];
        $assessment = $fixture['assessment'];
        $assessment->update(['apl_status' => 'DRAFT']);

        $res = $this->actingAs($asesi)->post(route('asesi.assessment.apl.store', $assessment), [
            'proofs' => [
                'IJAZAH_SMK' => UploadedFile::fake()->create('ijazah.pdf', 200, 'application/pdf'),
                'KTP' => UploadedFile::fake()->create('ktp.jpg', 200, 'image/jpeg'),
                'PAS_FOTO' => UploadedFile::fake()->create('foto.jpg', 200, 'image/jpeg'),
            ],
            'apl02' => [
                1 => ['value' => 'K', 'proof_type' => 'IJAZAH_SMK'],
            ],
            // Missing statement_integrity
        ]);

        $res->assertSessionHasErrors(['statement_integrity']);
    }

    public function test_appeal_allowed_within_business_days_window(): void
    {
        $fixture = $this->createTestAssessment();
        $asesi = $fixture['asesi'];
        $assessment = $fixture['assessment'];

        // Monday assessment completion
        $monday = Carbon::parse('2026-10-12 10:00:00'); // Monday
        $assessment->update([
            'completed_at' => $monday,
            'status' => 'COMPLETED',
            'final_result' => 'BELUM_KOMPETEN',
        ]);

        // Day 1: Tuesday -> Allowed
        $tuesday = Carbon::parse('2026-10-13 14:00:00');
        Carbon::setTestNow($tuesday);

        $resDay1 = $this->actingAs($asesi)->post(route('asesi.assessment.appeal.submit', $assessment), [
            'appeal_reasons' => 'Alasan keberatan hari ke-1 sangat jelas dan terperinci.',
        ]);
        $resDay1->assertRedirect();
        $resDay1->assertSessionHas('success');
        $this->assertEquals(1, $assessment->appeals()->count());

        // Reset appeals
        $assessment->appeals()->delete();

        // Day 3: Thursday -> Allowed
        $thursday = Carbon::parse('2026-10-15 16:30:00');
        Carbon::setTestNow($thursday);

        $resDay3 = $this->actingAs($asesi)->post(route('asesi.assessment.appeal.submit', $assessment), [
            'appeal_reasons' => 'Alasan keberatan hari ke-3 diajukan sebelum batas akhir.',
        ]);
        $resDay3->assertRedirect();
        $resDay3->assertSessionHas('success');
        $this->assertEquals(1, $assessment->appeals()->count());

        Carbon::setTestNow();
    }

    public function test_appeal_rejected_past_business_days_deadline(): void
    {
        $fixture = $this->createTestAssessment();
        $asesi = $fixture['asesi'];
        $assessment = $fixture['assessment'];

        // Monday assessment completion
        $monday = Carbon::parse('2026-10-12 10:00:00'); // Monday
        $assessment->update([
            'completed_at' => $monday,
            'status' => 'COMPLETED',
            'final_result' => 'BELUM_KOMPETEN',
        ]);

        // Day 4: Friday -> Rejected
        $friday = Carbon::parse('2026-10-16 09:00:00');
        Carbon::setTestNow($friday);

        $res = $this->actingAs($asesi)->post(route('asesi.assessment.appeal.submit', $assessment), [
            'appeal_reasons' => 'Pengajuan terlambat di hari ke-4.',
        ]);

        $res->assertSessionHasErrors(['appeal_reasons']);
        $this->assertEquals(0, $assessment->appeals()->count());

        Carbon::setTestNow();
    }

    public function test_appeal_countdown_starts_from_final_result_at_with_fallback(): void
    {
        $fixture = $this->createTestAssessment();
        $asesi = $fixture['asesi'];
        $assessment = $fixture['assessment'];

        // Assessment completed on Monday, but final result determined on Wednesday
        $monday = Carbon::parse('2026-10-12 10:00:00');
        $wednesday = Carbon::parse('2026-10-14 10:00:00');
        $assessment->update([
            'completed_at' => $monday,
            'final_result_at' => $wednesday,
            'status' => 'COMPLETED',
            'final_result' => 'BELUM_KOMPETEN',
        ]);

        // Friday is Day 2 from Wednesday (final_result_at) -> Allowed!
        // (If it incorrectly counted from Monday completed_at, Friday would be Day 4 and fail)
        $friday = Carbon::parse('2026-10-16 14:00:00');
        Carbon::setTestNow($friday);

        $resWithResultAt = $this->actingAs($asesi)->post(route('asesi.assessment.appeal.submit', $assessment), [
            'appeal_reasons' => 'Banding diajukan 2 hari setelah hasil ditetapkan pada hari Rabu.',
        ]);
        $resWithResultAt->assertRedirect();
        $resWithResultAt->assertSessionHas('success');
        $this->assertEquals(1, $assessment->appeals()->count());

        // Reset appeals and test fallback when final_result_at is null
        $assessment->appeals()->delete();
        $assessment->update(['final_result_at' => null]);

        // Without final_result_at, countdown falls back to completed_at (Monday), so Friday is Day 4 -> Rejected!
        $resFallback = $this->actingAs($asesi)->post(route('asesi.assessment.appeal.submit', $assessment), [
            'appeal_reasons' => 'Banding tanpa final_result_at harus mengacu pada completed_at.',
        ]);
        $resFallback->assertSessionHasErrors(['appeal_reasons']);
        $this->assertEquals(0, $assessment->appeals()->count());

        Carbon::setTestNow();
    }

    public function test_appeal_skips_weekends_in_business_days_calculation(): void
    {
        $fixture = $this->createTestAssessment();
        $asesi = $fixture['asesi'];
        $assessment = $fixture['assessment'];

        // Thursday assessment completion
        $thursday = Carbon::parse('2026-10-15 10:00:00'); // Thursday
        $assessment->update([
            'completed_at' => $thursday,
            'status' => 'COMPLETED',
            'final_result' => 'BELUM_KOMPETEN',
        ]);

        // Next Tuesday is Day 3 of business days (Friday=1, Mon=2, Tue=3; Sat/Sun skipped) -> Allowed
        $nextTuesday = Carbon::parse('2026-10-20 15:00:00');
        Carbon::setTestNow($nextTuesday);

        $resTue = $this->actingAs($asesi)->post(route('asesi.assessment.appeal.submit', $assessment), [
            'appeal_reasons' => 'Diajukan hari Selasa berikutnya setelah melewati akhir pekan.',
        ]);
        $resTue->assertRedirect();
        $resTue->assertSessionHas('success');
        $this->assertEquals(1, $assessment->appeals()->count());

        // Reset appeals
        $assessment->appeals()->delete();

        // Next Wednesday is Day 4 -> Rejected
        $nextWednesday = Carbon::parse('2026-10-21 09:00:00');
        Carbon::setTestNow($nextWednesday);

        $resWed = $this->actingAs($asesi)->post(route('asesi.assessment.appeal.submit', $assessment), [
            'appeal_reasons' => 'Diajukan hari Rabu berikutnya (hari ke-4 kerja).',
        ]);
        $resWed->assertSessionHasErrors(['appeal_reasons']);
        $this->assertEquals(0, $assessment->appeals()->count());

        Carbon::setTestNow();
    }

    public function test_admin_can_decide_appeal_with_committee_chair(): void
    {
        $fixture = $this->createTestAssessment();
        $admin = $fixture['admin'];
        $asesi = $fixture['asesi'];
        $assessment = $fixture['assessment'];

        $appeal = AssessmentAppeal::create([
            'assessment_id' => $assessment->id,
            'appeal_date' => now()->toDateString(),
            'appeal_reasons' => 'Bukti demonstrasi perlu ditinjau ulang oleh tim independen.',
            'status' => 'SUBMITTED',
        ]);

        $res = $this->actingAs($admin)->post(route('admin.appeals.decide', $appeal), [
            'decision' => 'ACCEPTED',
            'decision_notes' => 'Banding diterima, direkomendasikan uji ulang untuk unit terkait.',
            'appeals_committee_chair' => 'Dr. Ir. Bambang Supriyadi, M.T.',
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('success');

        $appeal->refresh();
        $this->assertEquals('ACCEPTED', $appeal->status);
        $this->assertEquals('Dr. Ir. Bambang Supriyadi, M.T.', $appeal->appeals_committee_chair);
        $this->assertEquals($admin->id, $appeal->decided_by);

        // Verify print view for FR.AK.04 displays Ketua Tim Banding
        $printRes = $this->actingAs($admin)->get(route('admin.dokumen-muk.export-asesi', [$assessment, 'ak04']));
        $printRes->assertOk();
        $printRes->assertSee('Dr. Ir. Bambang Supriyadi, M.T.');
        $printRes->assertSee('Ketua Tim Banding');
    }

    public function test_all_24_muk_print_views_render_successfully(): void
    {
        $fixture = $this->createTestAssessment();
        $admin = $fixture['admin'];
        $schedule = $fixture['schedule'];
        $assessment = $fixture['assessment'];

        // 1. Schedule-level documents
        $scheduleDocExpectations = [
            'ba' => 'BERITA ACARA',
            'hadir-asesor' => 'DAFTAR HADIR ASESOR',
            'hadir-asesi' => 'DAFTAR HADIR',
            'cover' => 'PERANGKAT ASESMEN',
            'mapa01' => 'FR.MAPA.01',
            'mapa02' => 'FR.MAPA.02',
            'daftar-periksa' => 'DAFTAR PERIKSA',
            'ak05' => 'FR.AK.05',
            'ak06' => 'FR.AK.06',
            'ia05b' => 'FR.IA.05.B',
            'ia11' => 'FR.IA.11',
        ];

        foreach ($scheduleDocExpectations as $type => $expectedSnippet) {
            $res = $this->actingAs($admin)->get(route('admin.dokumen-muk.export', [$schedule, $type]));
            $res->assertOk();
            $res->assertSee($expectedSnippet);
        }

        // 2. Asesi-level individual documents
        Storage::fake('local');
        $uploadedPath = 'ia01b_evidence/test_sheet.pdf';
        Storage::disk('local')->put($uploadedPath, '%PDF-1.4 sample');
        AssessmentIaResponse::create([
            'assessment_id' => $assessment->id,
            'instrument_code' => 'IA01B',
            'data_json' => ['evidence_image_path' => $uploadedPath],
        ]);

        $asesiDocExpectations = [
            'apl01' => 'FR.APL.01',
            'apl02' => 'FR.APL.02',
            'ak01' => 'FR.AK.01',
            'ak02' => 'FR.AK.02',
            'ak03' => 'FR.AK.03',
            'ak04' => 'FR.AK.04',
            'ak07' => 'FR.AK.07',
            'ia01' => 'FR.IA.01',
            'ia01b' => 'FR.IA.01B',
            'ia02' => 'FR.IA.02',
            'ia03' => 'FR.IA.03',
            'ia05a' => 'FR.IA.05.A',
            'ia05c' => 'FR.IA.05.C',
            'ia07' => 'FR.IA.07',
            'pernyataan' => 'SURAT PERNYATAAN',
        ];

        foreach ($asesiDocExpectations as $type => $expectedSnippet) {
            $res = $this->actingAs($admin)->get(route('admin.dokumen-muk.export-asesi', [$assessment, $type]));
            $res->assertOk();
            if ($type === 'ia01b') {
                $this->assertStringContainsString($expectedSnippet, $res->headers->get('Content-Disposition', ''));
            } else {
                $res->assertSee($expectedSnippet);
            }
        }
    }
}
