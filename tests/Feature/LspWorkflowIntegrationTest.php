<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAppeal;
use App\Models\AssessmentIaResponse;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
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
    }

    public function test_user_seeder_contains_all_23_demo_accounts(): void
    {
        $this->assertDatabaseHas('users', ['email' => 'lsp.proindo@gmail.com', 'role' => 'SUPER_ADMIN']);
        $this->assertDatabaseHas('users', ['email' => 'adminlsp@gmail.com', 'role' => 'ADMIN_LSP']);
        $this->assertDatabaseHas('users', ['email' => 'asesor.randi@gmail.com', 'role' => 'ASESOR']);
        $this->assertDatabaseHas('users', ['email' => 'asesi1@gmail.com', 'role' => 'ASESI']);
        $this->assertDatabaseHas('users', ['email' => 'tukadmin@gmail.com', 'role' => 'TUK_ADMIN']);
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
            'proofs' => [
                'IJAZAH_SMK' => UploadedFile::fake()->create('ijazah.pdf', 500, 'application/pdf'),
                'KTP' => UploadedFile::fake()->create('ktp.jpg', 300, 'image/jpeg'),
                'PAS_FOTO' => UploadedFile::fake()->create('foto.jpg', 300, 'image/jpeg'),
            ],
            'apl02' => $kukPayload,
            'signature_data' => 'data:image/png;base64,sampleFakeSignatureData',
        ]);
        $aplSubmit->assertRedirect(route('asesi.assessment.apl', $assessment));

        $assessment->refresh();
        $this->assertEquals('SUBMITTED', $assessment->apl_status);

        // 4. Admin candidate approval gate
        // Asesor cannot approve if admin has not approved candidate registration
        $earlyAsesorReview = $this->actingAs($asesor)->post(route('asesor.apl.decide', $assessment), [
            'decision' => 'APPROVED',
        ]);
        $earlyAsesorReview->assertSessionHas('error');

        // Admin approves candidate registration
        $adminApprove = $this->actingAs($admin)->post(route('admin.assessment.review-registration', $assessment), [
            'admin_status' => 'APPROVED',
            'admin_notes' => 'Berkas pendaftaran memenuhi persyaratan.',
        ]);
        $adminApprove->assertSessionHas('success');

        // Asesor reviews APL and APPROVES (redirects to Batch FR.AK07)
        $reviewResponse = $this->actingAs($asesor)->post(route('asesor.apl.decide', $assessment), [
            'decision' => 'APPROVED',
        ]);
        $reviewResponse->assertRedirect(route('asesor.ak07.create', ['schedule' => $schedule->id, 'assessment' => $assessment->id]));

        $assessment->refresh();
        $this->assertEquals('APPROVED', $assessment->apl_status);

        // 5. Asesi can now enter AK01 (Gate is unlocked)
        $ak01Response = $this->actingAs($asesi)->get(route('asesi.assessment.ak01', $assessment));
        $ak01Response->assertStatus(200);

        // 6. Asesi signs AK01
        $signAk01 = $this->actingAs($asesi)->post(route('asesi.assessment.ak01.sign', $assessment));
        $signAk01->assertRedirect(route('asesi.assessment.ruang-uji', $assessment));

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

        // 11. Asesi submits FR.AK.03 Umpan Balik
        $ak03Submit = $this->actingAs($asesi)->post(route('asesi.assessment.ak03.submit', $assessment), [
            'ratings' => [1 => ['value' => 'YA', 'comment' => 'Sangat baik']],
            'general_notes' => 'Pelaksanaan asesmen objektif dan profesional.',
        ]);
        $ak03Submit->assertRedirect(route('asesi.jadwal.index'));
        $this->assertDatabaseHas('t_assessment_ak03_items', ['assessment_id' => $assessment->id]);

        // 12. Asesor checks & finalizes FR.AK.02 Rekaman Asesmen
        $ak02View = $this->actingAs($asesor)->get(route('asesor.assessment.ak02', $assessment));
        $ak02View->assertOk();
        $this->assertDatabaseHas('t_assessment_ak02_items', ['assessment_id' => $assessment->id]);

        // 13. Asesor completes schedule with FR.AK.05 & FR.AK.06
        $reportSubmit = $this->actingAs($asesor)->post(route('asesor.schedule.reports.submit', $schedule), [
            'ak05_aspek_positif' => 'Fasilitas bengkel TUK sangat memadai.',
            'ak06_valid' => 'YA',
        ]);
        $reportSubmit->assertRedirect(route('asesor.jadwal.index'));
        $schedule->refresh();
        $this->assertTrue($schedule->is_closed);
        $this->assertEquals('FINISHED', $schedule->status);

        // 14. Asesi submits FR.AK.04 Banding & Admin decides
        $appealSubmit = $this->actingAs($asesi)->post(route('asesi.assessment.appeal.submit', $assessment), [
            'appeal_reasons' => 'Mohon peninjauan ulang terhadap bukti unjuk kerja pada sistem pengapian.',
        ]);
        $appealSubmit->assertRedirect(route('asesi.jadwal.index'));
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

        $resWithSchedule = $this->actingAs($asesor)->get(route('asesor.jadwal.index'));
        $resWithSchedule->assertOk();
        $resWithSchedule->assertSee($schedule->code);

        // Check asesi list view
        $resAsesi = $this->actingAs($asesor)->get(route('asesor.jadwal.asesi', $schedule));
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

        // Test assessor trying to approve APL before admin approval (should fail)
        $failPostRes = $this->actingAs($asesor)->post(route('asesor.review-apl.update', $assessment), [
            'apl_status' => 'APPROVED',
            'notes' => 'Mencoba menyetujui tanpa approval admin.',
        ]);
        $failPostRes->assertSessionHas('error');
        $this->assertEquals('SUBMITTED', $assessment->fresh()->apl_status);

        // Admin approves registration first
        $assessment->update(['admin_status' => 'APPROVED']);

        // Now assessor approving APL succeeds
        $postRes = $this->actingAs($asesor)->post(route('asesor.review-apl.update', $assessment), [
            'apl_status' => 'APPROVED',
            'notes' => 'Berkas dan asesmen mandiri lengkap memenuhi syarat.',
        ]);
        $postRes->assertRedirect(route('asesor.ak07.create', ['schedule' => $schedule->id, 'assessment' => $assessment->id]));
        $this->assertEquals('APPROVED', $assessment->fresh()->apl_status);
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

        // Assessor visits asesi list
        $asesiListRes = $this->actingAs($asesor)->get(route('asesor.jadwal.asesi', $schedule));
        $asesiListRes->assertOk();
        $asesiListRes->assertSee(route('asesor.ak07.create', $schedule->id));

        // Assessor visits AK07 create form
        $ak07Res = $this->actingAs($asesor)->get(route('asesor.ak07.create', ['schedule' => $schedule->id, 'assessment' => $assessment->id]));
        $ak07Res->assertOk();
        $ak07Res->assertSee('AK07');

        // Assessor submits AK07 and continues to AK01 pra-asesmen (urutan diperbaiki: AK07 -> AK01)
        $postAk07 = $this->actingAs($asesor)->post(route('asesor.ak07.store', $schedule), [
            'assessment_ids' => [$assessment->id],
            'category' => 'waktu',
            'notes' => 'Penambahan waktu 15 menit untuk asesi.',
        ]);
        $postAk07->assertRedirect(route('asesor.schedule.ak01', $schedule->id));
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
        Storage::disk('public')->assertExists($response->data_json['evidence_image_path']);

        // Assessor views IA02 assessment page and sees uploaded evidence
        $asesorRes = $this->actingAs($asesorUser)->get(
            route('asesor.assessment.instrument', ['assessment' => $assessment->id, 'code' => 'ia02'])
        );
        $asesorRes->assertOk();
        $asesorRes->assertSee('Pemeriksaan Bukti Lembar Fisik');
        $asesorRes->assertSee('Lembar Jawaban Terunggah');
        $asesorRes->assertSee(asset('storage/'.$response->data_json['evidence_image_path']));

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
        $viewRes->assertSee('FR.IA.01B REPORT SHEET');
        $viewRes->assertSee('Lembar Pemeriksaan');
        $viewRes->assertSee('Total Item Ukur');
        $viewRes->assertSee('12.6 Volt');
        $viewRes->assertSee('SESUAI');
        $viewRes->assertSee('TIDAK');
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

        // 4. Verify asesi viewing after signed displays signature and badge
        $signedViewRes = $this->actingAs($asesiUser)->get(
            route('asesi.assessment.instrument.view', [$assessment, 'IA01B'])
        );
        $signedViewRes->assertOk();
        $signedViewRes->assertSee('✓ Sudah Ditandatangani');
        $signedViewRes->assertSee('Proses verifikasi pengukuran objektif dan jelas.');
        $signedViewRes->assertSee($sigData);
    }
}
