<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAppeal;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MvpEndToEndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_complete_happy_path_workflow_from_schedule_creation_to_muk_export(): void
    {
        $admin = User::where('role', 'ADMIN_LSP')->first();
        $asesor = User::where('role', 'ASESOR')->first();
        $scheme = Scheme::first();

        // 1. Admin creates schedule and assigns assessor
        $scheduleCode = 'SCH-E2E-'.uniqid();
        $scheduleCreate = $this->actingAs($admin)->post(route('admin.jadwal.store'), [
            'name' => 'Uji Sertifikasi Nasional LSP PrO Indo',
            'code' => $scheduleCode,
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'duration_minutes' => 540,
            'assessor_ids' => [$asesor->id],
            'selected_instruments' => ['AK01', 'IA01', 'IA02', 'IA03', 'IA05'],
        ]);
        $scheduleCreate->assertRedirect(route('admin.jadwal.index'));
        $schedule = Schedule::where('code', $scheduleCode)->first();
        $this->assertNotNull($schedule);
        $this->assertEquals('ACTIVE', $schedule->status);
        $this->assertTrue($schedule->assessors->contains($asesor->id));

        // Create CBT questions for the scheme
        $qb = QuestionBank::create([
            'scheme_id' => $scheme->id,
            'question_text' => 'Berapa voltase aki standar sepeda motor?',
            'option_a' => '12 Volt',
            'option_b' => '24 Volt',
            'option_c' => '6 Volt',
            'option_d' => '48 Volt',
            'correct_answer' => 'A',
        ]);

        // 2. Asesi registers, completes profile, and joins schedule
        $this->app['auth']->guard()->logout();
        $asesiEmail = 'asesi.e2e.'.uniqid().'@test.com';
        $registerRes = $this->post('/register', [
            'full_name' => 'Budi Santoso Teknisi',
            'email' => $asesiEmail,
            'phone' => '081298765432',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
        $registerRes->assertRedirect(route('login'));
        $asesi = User::where('email', $asesiEmail)->first();
        $this->assertNotNull($asesi);

        $validPng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $profileRes = $this->actingAs($asesi)->post(route('asesi.profile.update'), [
            'nik' => '3216012345678901',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '2000-05-15',
            'jenis_kelamin' => 'L',
            'kebangsaan' => 'Indonesia',
            'alamat_rumah' => 'Jl. Merdeka No. 45, Cikarang',
            'pendidikan_terakhir' => 'SMA/Sederajat',
            'jabatan' => 'Pelajar / Siswa',
            'signature_data' => $validPng,
        ]);
        $profileRes->assertRedirect(route('asesi.profile.edit'));
        $profileRes->assertSessionHas('success');

        $joinRes = $this->actingAs($asesi)->post(route('asesi.jadwal.join'), [
            'code' => $schedule->code,
        ]);
        $assessment = Assessment::where('schedule_id', $schedule->id)->where('asesi_id', $asesi->id)->first();
        $this->assertNotNull($assessment);
        $joinRes->assertRedirect(route('asesi.assessment.apl', $assessment));
        $this->assertEquals('DRAFT', $assessment->apl_status);

        // 3. Asesi uploads required proofs and submits APL
        $unitKuks = $scheme->competencyUnits->first()->elements->first()->kuks;
        $kukPayload = [];
        foreach ($unitKuks as $k) {
            $kukPayload[$k->id] = ['value' => 'K', 'proof_type' => 'IJAZAH_SMK'];
        }

        $aplSubmit = $this->actingAs($asesi)->post(route('asesi.assessment.apl.store', $assessment), [
            'assessor_id' => $asesor->id,
            'proofs' => [
                'KTP' => UploadedFile::fake()->create('ktp_asli.pdf', 300, 'application/pdf'),
                'PAS_FOTO' => UploadedFile::fake()->create('pas_foto_formal.jpg', 200, 'image/jpeg'),
                'IJAZAH_SMK' => UploadedFile::fake()->create('ijazah_teknik.pdf', 500, 'application/pdf'),
            ],
            'apl02' => $kukPayload,
            'statement_integrity' => '1',
        ]);
        $aplSubmit->assertRedirect(route('asesi.assessment.apl', $assessment));
        $assessment->refresh();
        $this->assertEquals('SUBMITTED', $assessment->apl_status);

        // 4. Admin verifies candidate registration
        $adminReview = $this->actingAs($admin)->post(route('admin.assessment.review-registration', $assessment), [
            'status' => 'APPROVED',
            'notes' => 'Persyaratan administratif dan bukti dasar valid.',
        ]);
        $adminReview->assertRedirect();
        $adminReview->assertSessionHas('success');
        $assessment->refresh();
        $this->assertEquals('APPROVED', $assessment->admin_status);

        // 5. Asesor reviews and approves APL
        $asesorReview = $this->actingAs($asesor)->post(route('asesor.apl.decide', $assessment), [
            'decision' => 'APPROVED',
            'notes' => 'Asesmen mandiri memenuhi syarat untuk dilanjutkan ke proses uji kompetensi.',
        ]);
        $asesorReview->assertRedirect();
        $asesorReview->assertSessionHas('success');
        $assessment->refresh();
        $this->assertEquals('APPROVED', $assessment->apl_status);

        // 5b. Asesor establishes FR.AK.07
        $this->actingAs($asesor)->post(route('asesor.ak07.store', $schedule), [
            'assessment_ids' => [$assessment->id],
            'items' => [],
        ]);

        // 6. Asesi signs AK.01 & Asesor signs AK.01
        $asesiSignAk01 = $this->actingAs($asesi)->post(route('asesi.assessment.ak01.sign', $assessment), [
            'asesi_signature' => $validPng,
        ]);
        $asesiSignAk01->assertRedirect(route('asesi.assessment.ruang-uji', $assessment));
        $assessment->refresh();
        $this->assertNotNull($assessment->ak01_agreed_at);

        $asesorSignAk01 = $this->actingAs($asesor)->post(route('asesor.ak01.sign', $assessment), [
            'assessor_signature' => $validPng,
        ]);
        $asesorSignAk01->assertRedirect();
        $assessment->refresh();
        $this->assertNotNull($assessment->assessor_signature);

        // 7. Asesi completes CBT (IA.05) in Ruang Uji
        $schemeQuestions = QuestionBank::where('scheme_id', $scheme->id)->get();
        $answers = [];
        foreach ($schemeQuestions as $q) {
            $answers[$q->id] = $q->correct_answer;
        }

        $cbtSubmit = $this->actingAs($asesi)->post(route('asesi.assessment.instrument.ia05.submit', $assessment), [
            'answers' => $answers,
        ]);
        $cbtSubmit->assertRedirect(route('asesi.assessment.ruang-uji', $assessment));
        $cbtSubmit->assertSessionHas('success');
        $this->assertDatabaseHas('t_assessment_ia_responses', [
            'assessment_id' => $assessment->id,
            'instrument_code' => 'IA05',
            'score' => 100,
        ]);

        // 8. Asesor inputs observation (IA.01) and demonstration (IA.02)
        $ia01Submit = $this->actingAs($asesor)->post(route('asesor.assessment.instrument.store', [$assessment, 'ia01']), [
            'general_notes' => 'Seluruh kriteria unjuk kerja dilakukan sesuai SOP bengkel resmi.',
            'signature_data' => $validPng,
        ]);
        $ia01Submit->assertRedirect();
        $ia01Submit->assertSessionHas('success');

        $ia02Submit = $this->actingAs($asesor)->post(route('asesor.assessment.instrument.store', [$assessment, 'ia02']), [
            'general_notes' => 'Unjuk kerja demonstrasi praktis akurat dan tuntas.',
            'signature_data' => $validPng,
            'evidence_image' => UploadedFile::fake()->create('bukti_demonstrasi.jpg', 300, 'image/jpeg'),
        ]);
        $ia02Submit->assertRedirect();
        $ia02Submit->assertSessionHas('success');

        // 9. Asesor finalizes AK.02 (Rekaman Asesmen) recommendation
        $ak02Update = $this->actingAs($asesor)->post(route('asesor.assessment.ak02.update', $assessment), [
            'final_result' => 'KOMPETEN',
            'final_notes' => 'Asesi menunjukkan kompetensi kerja optimal pada seluruh unit.',
            'follow_up_action' => 'Diusulkan penerbitan sertifikat kompetensi BNSP.',
        ]);
        $ak02Update->assertRedirect(route('asesor.jadwal.asesi', $schedule->id));
        $ak02Update->assertSessionHas('success');
        $assessment->refresh();
        $this->assertEquals('KOMPETEN', $assessment->final_result);

        // 10. Asesi finishes assessment and submits AK.03 feedback
        $finishRes = $this->actingAs($asesi)->post(route('asesi.assessment.finish', $assessment));
        $finishRes->assertRedirect(route('asesi.assessment.ak03', $assessment));
        $assessment->refresh();
        $this->assertEquals('COMPLETED', $assessment->status);

        $ratings = [];
        for ($i = 1; $i <= 10; $i++) {
            $ratings[$i] = ['value' => 'YA', 'comment' => 'Sangat memuaskan'];
        }
        $ak03Res = $this->actingAs($asesi)->post(route('asesi.assessment.ak03.submit', $assessment), [
            'ratings' => $ratings,
            'general_notes' => 'Proses asesmen sangat objektif, sarana dan prasarana lengkap.',
        ]);
        $ak03Res->assertRedirect(route('asesi.jadwal.index'));
        $ak03Res->assertSessionHas('success');

        // 11. Asesor submits AK.05 & AK.06 reports; schedule is closed
        $reportsRes = $this->actingAs($asesor)->post(route('asesor.schedule.reports.submit', $schedule), [
            'ak05_aspek_positif' => 'Fasilitas TUK sangat lengkap dan asesi sangat siap.',
            'ak05_aspek_negatif' => 'Tidak terdapat kendala teknis berarti di lokasi uji.',
            'ak05_rekomendasi' => 'Pertahankan kalibrasi peralatan secara teratur.',
            'ak06_valid' => 'YA',
            'ak06_valid_notes' => 'Bukti mencakup seluruh dimensi kompetensi.',
            'ak06_reliabel' => 'YA',
            'ak06_reliabel_notes' => 'Penilaian konsisten sesuai rubrik BNSP.',
            'ak06_fleksibel' => 'YA',
            'ak06_fleksibel_notes' => 'Metode adaptif terhadap kondisi lapangan.',
            'ak06_adil' => 'YA',
            'ak06_adil_notes' => 'Seluruh asesi diperlakukan secara objektif.',
            'ak06_catatan_peninjau' => 'Proses pelaksanaan asesmen telah memenuhi regulasi BNSP.',
            'confirm_draft_review' => '1',
        ]);
        $reportsRes->assertRedirect(route('asesor.jadwal.index'));
        $schedule->refresh();
        $this->assertTrue($schedule->is_closed);
        $this->assertEquals('FINISHED', $schedule->status);

        // 12. Admin accesses all MUK documents and exports without error
        $baRes = $this->actingAs($admin)->get(route('admin.dokumen-muk.export-ba', $schedule));
        $baRes->assertOk();
        $baRes->assertSee('BERITA ACARA');

        $hadirAsesorRes = $this->actingAs($admin)->get(route('admin.dokumen-muk.export-hadir-asesor', $schedule));
        $hadirAsesorRes->assertOk();
        $hadirAsesorRes->assertSee('DAFTAR HADIR ASESOR');

        $hadirAsesiRes = $this->actingAs($admin)->get(route('admin.dokumen-muk.export-hadir-asesi', $schedule));
        $hadirAsesiRes->assertOk();
        $hadirAsesiRes->assertSee('DAFTAR HADIR');
        $hadirAsesiRes->assertSee('Nama Peserta');

        $ak02Res = $this->actingAs($admin)->get(route('admin.dokumen-muk.export-asesi', [$assessment, 'ak02']));
        $ak02Res->assertOk();
        $ak02Res->assertSee('FR.AK.02');

        $ak05Res = $this->actingAs($admin)->get(route('admin.dokumen-muk.export', [$schedule, 'ak05']));
        $ak05Res->assertOk();

        $ak06Res = $this->actingAs($admin)->get(route('admin.dokumen-muk.export', [$schedule, 'ak06']));
        $ak06Res->assertOk();
    }

    public function test_edge_case_apl_revision_and_resubmission_cycle(): void
    {
        $admin = User::where('role', 'ADMIN_LSP')->first();
        $asesor = User::where('role', 'ASESOR')->first();
        $scheme = Scheme::first();

        $schedule = Schedule::create([
            'code' => 'SCH-REV-'.uniqid(),
            'name' => 'Schedule Revision Test',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Cikarang',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'IA01'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);
        $schedule->assessors()->attach($asesor->id);

        $asesi = User::create([
            'full_name' => 'Asesi Butuh Revisi',
            'email' => 'revisi.'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $asesor->id,
            'scheme_id' => $scheme->id,
            'status' => 'IN_PROGRESS',
            'admin_status' => 'APPROVED',
            'apl_status' => 'SUBMITTED',
        ]);

        // Asesor requests revision
        $revisionNote = 'Foto KTP buram dan tidak terbaca, harap unggah scan KTP yang jelas.';
        $decideRes = $this->actingAs($asesor)->post(route('asesor.apl.decide', $assessment), [
            'decision' => 'REVISION',
            'notes' => $revisionNote,
        ]);
        $decideRes->assertRedirect(route('asesor.jadwal.asesi', $schedule->id));
        $decideRes->assertSessionHas('success');

        $assessment->refresh();
        $this->assertEquals('REVISION_REQUESTED', $assessment->apl_status);
        $this->assertEquals($revisionNote, $assessment->apl_revision_notes);

        // Asesi views dashboard and sees revision alert and notes
        $dashRes = $this->actingAs($asesi)->get(route('asesi.dashboard'));
        $dashRes->assertOk();
        $dashRes->assertSee('Perbaikan Dokumen');
        $dashRes->assertSee($revisionNote);

        // Asesi re-uploads clear KTP and resubmits APL
        $resubmitRes = $this->actingAs($asesi)->post(route('asesi.assessment.apl.store', $assessment), [
            'proofs' => [
                'KTP' => UploadedFile::fake()->create('ktp_jelas.pdf', 300, 'application/pdf'),
                'PAS_FOTO' => UploadedFile::fake()->create('foto.jpg', 200, 'image/jpeg'),
                'IJAZAH_SMK' => UploadedFile::fake()->create('ijazah.pdf', 400, 'application/pdf'),
            ],
            'apl02' => [1 => ['value' => 'K', 'proof_type' => 'IJAZAH_SMK']],
            'statement_integrity' => '1',
        ]);
        $resubmitRes->assertRedirect();
        $assessment->refresh();
        $this->assertEquals('SUBMITTED', $assessment->apl_status);

        // Asesor re-evaluates and approves
        $approveRes = $this->actingAs($asesor)->post(route('asesor.apl.decide', $assessment), [
            'decision' => 'APPROVED',
            'notes' => 'Dokumen revisi telah diverifikasi dan memenuhi syarat.',
        ]);
        $approveRes->assertRedirect();
        $assessment->refresh();
        $this->assertEquals('APPROVED', $assessment->apl_status);
    }

    public function test_edge_case_candidate_registration_rejection_by_admin(): void
    {
        $admin = User::where('role', 'ADMIN_LSP')->first();
        $asesor = User::where('role', 'ASESOR')->first();
        $scheme = Scheme::first();

        $schedule = Schedule::create([
            'code' => 'SCH-REJ-'.uniqid(),
            'name' => 'Schedule Rejection Test',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Cikarang',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);
        $schedule->assessors()->attach($asesor->id);

        $asesi = User::create([
            'full_name' => 'Asesi Ditolak Admin',
            'email' => 'ditolak.'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $asesor->id,
            'scheme_id' => $scheme->id,
            'status' => 'IN_PROGRESS',
            'admin_status' => 'PENDING',
            'apl_status' => 'SUBMITTED',
        ]);

        // Admin rejects candidate registration
        $rejectRes = $this->actingAs($admin)->post(route('admin.assessment.review-registration', $assessment), [
            'status' => 'REJECTED',
            'notes' => 'Bukti persyaratan dasar tidak sesuai dengan kualifikasi skema yang dipilih.',
        ]);
        $rejectRes->assertRedirect();
        $rejectRes->assertSessionHas('success');

        $assessment->refresh();
        $this->assertEquals('REJECTED', $assessment->admin_status);

        // Asesor cannot approve APL for rejected candidate
        $asesorDecide = $this->actingAs($asesor)->post(route('asesor.apl.decide', $assessment), [
            'decision' => 'APPROVED',
        ]);
        $asesorDecide->assertRedirect();
        $asesorDecide->assertSessionHas('error');
        $assessment->refresh();
        $this->assertEquals('SUBMITTED', $assessment->apl_status);
    }

    public function test_edge_case_bk_candidate_appeals_and_admin_accepts_appeal(): void
    {
        $admin = User::where('role', 'ADMIN_LSP')->first();
        $asesor = User::where('role', 'ASESOR')->first();
        $scheme = Scheme::first();

        $schedule = Schedule::create([
            'code' => 'SCH-APPEAL-'.uniqid(),
            'name' => 'Schedule Appeal Test',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Cikarang',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);
        $schedule->assessors()->attach($asesor->id);

        $asesi = User::create([
            'full_name' => 'Asesi Banding BK',
            'email' => 'banding.'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $asesor->id,
            'scheme_id' => $scheme->id,
            'status' => 'COMPLETED',
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
            'final_result' => 'BELUM_KOMPETEN',
            'completed_at' => now(),
        ]);

        // Asesi submits FR.AK.04 Banding
        $appealRes = $this->actingAs($asesi)->post(route('asesi.assessment.appeal.submit', $assessment), [
            'appeal_reasons' => 'Terdapat bukti portofolio tambahan yang belum sempat dipertimbangkan pada unit sistem kelistrikan.',
        ]);
        $appealRes->assertRedirect(route('asesi.jadwal.index'));
        $appealRes->assertSessionHas('success');

        $appeal = AssessmentAppeal::where('assessment_id', $assessment->id)->first();
        $this->assertNotNull($appeal);
        $this->assertEquals('SUBMITTED', $appeal->status);

        // Admin views appeals list
        $adminIndex = $this->actingAs($admin)->get(route('admin.appeals.index'));
        $adminIndex->assertOk();
        $adminIndex->assertSee('Asesi Banding BK');

        // Admin decides appeal (ACCEPTED)
        $decideRes = $this->actingAs($admin)->post(route('admin.appeals.decide', $appeal), [
            'decision' => 'ACCEPTED',
            'decision_notes' => 'Banding diterima untuk peninjauan ulang bersama komite teknis dan asesor pembanding.',
        ]);
        $decideRes->assertRedirect();
        $decideRes->assertSessionHas('success');

        $appeal->refresh();
        $this->assertEquals('ACCEPTED', $appeal->status);
        $this->assertEquals('Banding diterima untuk peninjauan ulang bersama komite teknis dan asesor pembanding.', $appeal->decision_notes);
    }
}
