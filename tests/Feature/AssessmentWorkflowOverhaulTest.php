<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAk02Item;
use App\Models\AssessmentAk07Item;
use App\Models\AssessmentIaResponse;
use App\Models\CompetencyUnit;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Services\AutoGradeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssessmentWorkflowOverhaulTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $assessor;

    protected Scheme $scheme;

    protected Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->assessor = User::factory()->create([
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-TEST-01',
            'name' => 'Skema Test Overhaul',
            'scheme_type' => 'KLASTER',
            'is_active' => true,
            'passing_grade_theory' => 75.00,
        ]);

        $this->schedule = Schedule::create([
            'code' => 'SCH-OVERHAUL-1',
            'name' => 'Jadwal Overhaul Test',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA01B', 'IA02', 'IA03', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);

        $this->schedule->assessors()->attach($this->assessor->id);

        // Add 2 competency units
        for ($i = 1; $i <= 2; $i++) {
            CompetencyUnit::create([
                'scheme_id' => $this->scheme->id,
                'code' => "UNIT-0{$i}",
                'title' => "Judul Unit 0{$i}",
                'standard_type' => 'SKKNI',
            ]);
        }
    }

    public function test_assessor_can_bulk_approve_apl_applications(): void
    {
        // Create 3 candidates
        $candidates = [];
        $assessments = [];
        for ($i = 1; $i <= 3; $i++) {
            $asesi = User::factory()->create(['role' => 'ASESI', 'status' => 'ACTIVE']);
            $asm = Assessment::create([
                'schedule_id' => $this->schedule->id,
                'asesi_id' => $asesi->id,
                'assessor_id' => $this->assessor->id,
                'apl_status' => 'SUBMITTED',
                'admin_status' => 'APPROVED',
            ]);
            $candidates[] = $asesi;
            $assessments[] = $asm;
        }

        $res = $this->actingAs($this->assessor)->post(
            route('asesor.schedule.apl.bulk-review', $this->schedule->id),
            [
                'assessment_ids' => [$assessments[0]->id, $assessments[1]->id],
            ]
        );

        $res->assertRedirect(route('asesor.schedule.instrument', ['schedule' => $this->schedule->id, 'code' => 'apl']));
        $res->assertSessionHas('success');

        $this->assertEquals('APPROVED', $assessments[0]->fresh()->apl_status);
        $this->assertEquals('APPROVED', $assessments[1]->fresh()->apl_status);
        // Candidate 3 was not selected, so still SUBMITTED
        $this->assertEquals('SUBMITTED', $assessments[2]->fresh()->apl_status);

        // AK.07 is NOT prematurely created; it requires explicit submission in Step 3 (FR.AK.07)
        $this->assertFalse(AssessmentAk07Item::where('assessment_id', $assessments[0]->id)->exists());
    }

    public function test_schedule_deterministic_20_cbt_questions_consistent_for_all_candidates(): void
    {
        // Seed 30 questions
        for ($i = 1; $i <= 30; $i++) {
            QuestionBank::create([
                'scheme_id' => $this->scheme->id,
                'question_text' => "Pertanyaan butir {$i}",
                'option_a' => 'Pilihan A',
                'option_b' => 'Pilihan B',
                'option_c' => 'Pilihan C',
                'option_d' => 'Pilihan D',
                'correct_answer' => 'A',
            ]);
        }

        // Initially schedule cbt_question_ids is empty
        $this->assertEmpty($this->schedule->cbt_question_ids);

        // First call generates exactly 20 questions
        $ids1 = $this->schedule->getCbtQuestionIds();
        $this->assertCount(20, $ids1);

        // Refresh model from DB - must be persisted
        $this->schedule->refresh();
        $this->assertCount(20, $this->schedule->cbt_question_ids);

        // Second call returns the EXACT SAME 20 questions
        $ids2 = $this->schedule->getCbtQuestionIds();
        $this->assertEquals($ids1, $ids2);

        // Verify AutoGrade calculates score out of 20
        $asesi = User::factory()->create(['role' => 'ASESI']);
        $assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $asesi->id,
            'apl_status' => 'APPROVED',
        ]);

        // Candidate answers 10 of the 20 questions correctly
        $answers = [];
        foreach (array_slice($ids1, 0, 10) as $qId) {
            $answers[$qId] = 'A';
        }

        $grader = app(AutoGradeService::class);
        $response = $grader->grade($assessment, $answers);

        // 10 out of 20 is 50.00%
        $this->assertEquals(50.00, $response->score);
    }

    public function test_admin_can_upload_and_update_question_image(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('diagram.png', 400, 300);

        $res = $this->actingAs($this->admin)->post(route('admin.question-banks.store'), [
            'scheme_id' => $this->scheme->id,
            'question_text' => 'Berapa tegangan output pada gambar?',
            'question_image' => $file,
            'option_a' => '12 Volt',
            'option_b' => '24 Volt',
            'option_c' => '48 Volt',
            'option_d' => '220 Volt',
            'correct_answer' => 'A',
        ]);

        $res->assertRedirect(route('admin.question-banks.index'));

        $question = QuestionBank::where('question_text', 'Berapa tegangan output pada gambar?')->first();
        $this->assertNotNull($question);
        $this->assertNotNull($question->question_image);

        Storage::disk('public')->assertExists($question->question_image);

        // Update with replacement image
        $newFile = UploadedFile::fake()->image('diagram_v2.png', 500, 400);
        $oldPath = $question->question_image;

        $updateRes = $this->actingAs($this->admin)->put(route('admin.question-banks.update', $question->id), [
            'scheme_id' => $this->scheme->id,
            'question_text' => 'Berapa tegangan output revisi?',
            'question_image' => $newFile,
            'option_a' => '12 Volt',
            'option_b' => '24 Volt',
            'option_c' => '48 Volt',
            'option_d' => '220 Volt',
            'correct_answer' => 'B',
        ]);

        $updateRes->assertRedirect(route('admin.question-banks.show', $question->id));
        $question->refresh();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($question->question_image);
    }

    public function test_cbt_score_is_hidden_from_candidate_view(): void
    {
        $asesi = User::factory()->create(['role' => 'ASESI']);
        $assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $asesi->id,
            'apl_status' => 'APPROVED',
            'ak01_agreed_at' => now(),
            'last_active_step' => 'ruang-uji',
        ]);

        // Submit CBT response with 18.33% score
        AssessmentIaResponse::create([
            'assessment_id' => $assessment->id,
            'instrument_code' => 'IA05',
            'score' => 18.33,
            'data_json' => ['1' => 'A'],
            'asesi_signed_at' => now(),
        ]);

        // Check candidate IA05 page
        $resCbt = $this->actingAs($asesi)->get(route('asesi.assessment.instrument.ia05', $assessment));
        $resCbt->assertOk();
        $resCbt->assertDontSee('18.33%');
        $resCbt->assertDontSee('BELUM KOMPETEN');
        $resCbt->assertSee('Jawaban CBT Telah Terkirim');

        // Check candidate Hub page
        $resHub = $this->actingAs($asesi)->get(route('asesi.assessment.ruang-uji', $assessment));
        $resHub->assertOk();
        $resHub->assertDontSee('Skor: 18.33%');
        $resHub->assertSee('✓ Selesai CBT');
    }

    public function test_assessor_can_bulk_save_fr_ak02_records(): void
    {
        $asesi1 = User::factory()->create(['role' => 'ASESI']);
        $asm1 = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $asesi1->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'APPROVED',
        ]);

        $asesi2 = User::factory()->create(['role' => 'ASESI']);
        $asm2 = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $asesi2->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'APPROVED',
        ]);

        $payload = [
            'results' => [
                $asm1->id => [
                    'final_result' => 'KOMPETEN',
                    'follow_up_action' => 'Diusulkan sertifikat BNSP',
                    'final_notes' => 'Kompeten pada seluruh unit',
                ],
                $asm2->id => [
                    'final_result' => 'BELUM_KOMPETEN',
                    'follow_up_action' => 'Perlu asesmen ulang',
                    'final_notes' => 'Perlu latihan praktik',
                ],
            ],
            'methods' => [
                'observasi_demonstrasi' => 1,
                'tulis' => 1,
                'lisan' => 1,
                'portofolio' => 1,
            ],
        ];

        $res = $this->actingAs($this->assessor)->post(
            route('asesor.schedule.ak02.bulk', $this->schedule->id),
            $payload
        );

        $res->assertRedirect(route('asesor.schedule.ak02', $this->schedule->id));
        $res->assertSessionHas('success');

        $this->assertEquals('KOMPETEN', $asm1->fresh()->final_result);
        $this->assertEquals('COMPLETED', $asm1->fresh()->status);
        $this->assertEquals('BELUM_KOMPETEN', $asm2->fresh()->final_result);
        $this->assertEquals('COMPLETED', $asm2->fresh()->status);

        // Check unit items created for all units
        $unitCount = $this->scheme->competencyUnits()->count();
        $this->assertCount($unitCount, AssessmentAk02Item::where('assessment_id', $asm1->id)->get());
        $this->assertTrue(AssessmentAk02Item::where('assessment_id', $asm1->id)->first()->is_competent);
        $this->assertFalse(AssessmentAk02Item::where('assessment_id', $asm2->id)->first()->is_competent);
    }

    public function test_asesi_apl_submission_establishes_confidentiality_and_signature_without_resign_in_ak01(): void
    {
        $validPng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $asesi = User::factory()->create(['role' => 'ASESI']);
        $asesi->profile()->create([
            'nik' => '3216012345678901',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '2000-01-01',
            'jenis_kelamin' => 'L',
            'kebangsaan' => 'Indonesia',
            'pendidikan_terakhir' => 'SMK',
            'jabatan' => 'Teknisi',
            'alamat_rumah' => 'Jl. Test No. 12',
            'signature_data' => $validPng,
        ]);

        $assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'DRAFT',
            'admin_status' => 'APPROVED',
        ]);

        // 1. Asesi views APL page
        $resAplView = $this->actingAs($asesi)->get(route('asesi.assessment.apl', $assessment));
        $resAplView->assertOk();
        $resAplView->assertSee('menjaga kerahasiaan materi uji kompetensi');
        // Progress stepper removed
        $resAplView->assertDontSee('Alur Asesmen Kompetensi');

        // 2. Submit APL with agreement checkbox
        $element = $this->scheme->competencyUnits->first()->elements()->create(['title' => 'Elemen 1', 'sequence' => 1]);
        $kuk = $element->kuks()->create(['code_number' => '1.1', 'statement' => 'KUK 1.1', 'sequence' => 1]);
        $resAplSubmit = $this->actingAs($asesi)->post(route('asesi.assessment.apl.store', $assessment), [
            'statement_integrity' => '1',
            'signature_data' => $validPng,
            'proofs' => [
                'KTP' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
                'PAS_FOTO' => UploadedFile::fake()->image('foto.jpg'),
                'IJAZAH_SMK' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
            ],
            'kuks' => [
                $kuk->id => ['value' => 'K', 'proof_type' => 'IJAZAH_SMK'],
            ],
        ]);
        $resAplSubmit->assertSessionHasNoErrors();
        $this->assertEquals('SUBMITTED', $assessment->fresh()->apl_status);
        $this->assertEquals($validPng, $assessment->fresh()->asesi_signature);

        // 3. Assessor approves APL
        $assessment->update(['apl_status' => 'APPROVED']);
        AssessmentAk07Item::create([
            'assessment_id' => $assessment->id,
            'adjustment_category' => 'Standar',
            'adjustment_detail' => 'Standar',
            'assessor_id' => $this->assessor->id,
        ]);

        // 4. Asesi visits AK01: signature is already bound and no canvas pad is displayed
        $resAk01View = $this->actingAs($asesi)->get(route('asesi.assessment.ak01', $assessment));
        $resAk01View->assertOk();
        $resAk01View->assertSee('Tanda Tangan Asesi Terverifikasi');
        $resAk01View->assertSee('Tersemat Otomatis dari APL');
        $resAk01View->assertDontSee('sig_pad_');

        // 5. Asesi agrees and moves to ruang uji without re-signing
        $resAk01Sign = $this->actingAs($asesi)->post(route('asesi.assessment.ak01.sign', $assessment), []);
        $resAk01Sign->assertRedirect(route('asesi.assessment.ruang-uji', $assessment));

        $assessment->refresh();
        $this->assertNotNull($assessment->ak01_agreed_at);
        $this->assertEquals($validPng, $assessment->asesi_signature);
    }

    public function test_schedule_step_gating_locks_and_unlocks_in_correct_order(): void
    {
        $sch = Schedule::create([
            'code' => 'SCH-GATE-'.uniqid(),
            'name' => 'Jadwal Gating Test',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Gate',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);
        $sch->assessors()->attach($this->assessor->id);

        $asesi1 = User::factory()->create(['role' => 'ASESI']);
        $asm1 = Assessment::create([
            'schedule_id' => $sch->id,
            'asesi_id' => $asesi1->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'SUBMITTED',
            'admin_status' => 'APPROVED',
        ]);

        $asesi2 = User::factory()->create(['role' => 'ASESI']);
        $asm2 = Assessment::create([
            'schedule_id' => $sch->id,
            'asesi_id' => $asesi2->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'SUBMITTED',
            'admin_status' => 'APPROVED',
        ]);

        // 1. Initial State: APL has pending SUBMITTED -> AK.01, AK.07, Ruang Uji, AK.02, Reports are ALL locked
        $this->assertTrue($sch->isStepUnlocked('apl'));
        $this->assertFalse($sch->isStepUnlocked('ak01'));
        $this->assertFalse($sch->isStepUnlocked('ak07'));
        $this->assertFalse($sch->isStepUnlocked('ruang-uji'));
        $this->assertFalse($sch->isStepUnlocked('ak02'));
        $this->assertFalse($sch->isStepUnlocked('reports'));

        // Direct GET to AK.01 redirects back to APL with warning
        $resAk01 = $this->actingAs($this->assessor)->get(route('asesor.schedule.ak01', $sch->id));
        $resAk01->assertRedirect(route('asesor.schedule.instrument', ['schedule' => $sch->id, 'code' => 'apl']));
        $resAk01->assertSessionHas('warning');

        // Direct GET to AK.02 and Reports redirects as well
        $resAk02Blocked = $this->actingAs($this->assessor)->get(route('asesor.schedule.ak02', $sch->id));
        $resAk02Blocked->assertRedirect(route('asesor.schedule.ruang-uji', $sch->id));

        // Nav on APL view displays Terkunci badge for AK.01
        $resAplView = $this->actingAs($this->assessor)->get(route('asesor.schedule.instrument', ['schedule' => $sch->id, 'code' => 'apl']));
        $resAplView->assertOk();
        $resAplView->assertSee('Terkunci');

        // 2. Assessor reviews candidates: 1 APPROVED, 1 REVISION_REQUESTED -> AK.01 is now unlocked (revision still permits continuing)
        $asm1->update(['apl_status' => 'APPROVED']);
        $asm2->update(['apl_status' => 'REVISION_REQUESTED']);
        $sch->refresh();
        $this->assertTrue($sch->isStepUnlocked('ak01'));
        $this->assertFalse($sch->isStepUnlocked('ak07'));
        $this->assertFalse($sch->isStepUnlocked('ruang-uji'));
        $this->assertFalse($sch->isStepUnlocked('ak02'));
        $this->assertFalse($sch->isStepUnlocked('reports'));

        // Assessor can view AK.01 now
        $resAk01Unlocked = $this->actingAs($this->assessor)->get(route('asesor.schedule.ak01', $sch->id));
        $resAk01Unlocked->assertOk();
        $this->assertFalse($sch->isStepUnlocked('ak07'));

        // 3. Assessor saves AK.01 -> AK.07 is now unlocked
        $resSaveAk01 = $this->actingAs($this->assessor)->post(route('asesor.schedule.ak01.store', $sch->id), [
            'assessment_ids' => [$asm1->id],
            'evidence' => ['Hasil Observasi Langsung', 'Hasil Pertanyaan Tertulis'],
            'tuk_type' => 'Sewaktu',
        ]);
        $resSaveAk01->assertRedirect(route('asesor.schedule.ak07', $sch->id));
        $sch->refresh();
        $this->assertTrue($sch->isStepUnlocked('ak07'));
        $this->assertFalse($sch->isStepUnlocked('ruang-uji'));
        $this->assertFalse($sch->isStepUnlocked('ak02'));
        $this->assertFalse($sch->isStepUnlocked('reports'));

        // Direct GET to Ruang Uji or AK.02 redirects back while AK.07 is not yet saved
        $resRuangUjiBlocked = $this->actingAs($this->assessor)->get(route('asesor.schedule.ruang-uji', $sch->id));
        $resRuangUjiBlocked->assertRedirect(route('asesor.schedule.ak07', $sch->id));

        $resAk02Blocked2 = $this->actingAs($this->assessor)->get(route('asesor.schedule.ak02', $sch->id));
        $resAk02Blocked2->assertRedirect(route('asesor.schedule.ruang-uji', $sch->id));

        // 4. Assessor saves AK.07 -> Ruang Uji is now unlocked, but AK.02 is LOCKED until instruments completed!
        $resSaveAk07 = $this->actingAs($this->assessor)->post(route('asesor.ak07.store', $sch->id), []);
        $resSaveAk07->assertRedirect(route('asesor.schedule.ruang-uji', $sch->id));
        $sch->refresh();
        $this->assertTrue($sch->isStepUnlocked('ruang-uji'));
        $this->assertFalse($sch->isStepUnlocked('ak02'));
        $this->assertFalse($sch->isStepUnlocked('reports'));

        // Accessing AK.02 while instruments in Ruang Uji are not yet completed redirects back to Ruang Uji
        $resAk02BlockedAfterAk07 = $this->actingAs($this->assessor)->get(route('asesor.schedule.ak02', $sch->id));
        $resAk02BlockedAfterAk07->assertRedirect(route('asesor.schedule.ruang-uji', $sch->id));

        // Ruang Uji view does NOT show the bloated banner, and does NOT show APL or AK.01 cards
        $resRuangUji = $this->actingAs($this->assessor)->get(route('asesor.schedule.ruang-uji', $sch->id));
        $resRuangUji->assertOk();
        $resRuangUji->assertDontSee('Persyaratan Pra-Asesmen: Lengkapi FR.AK.01 & FR.AK.07');
        $resRuangUji->assertDontSee('Selanjutnya: FR.AK.02 Rekaman Asesmen');
        $resRuangUji->assertDontSee('Buka Verifikasi APL');
        $resRuangUji->assertDontSee('Buka Form FR.AK.01');

        // Assessor can freely go back and edit AK.01 and AK.07 anytime
        $resEditAk01 = $this->actingAs($this->assessor)->get(route('asesor.schedule.ak01', $sch->id));
        $resEditAk01->assertOk();
        $resEditAk07 = $this->actingAs($this->assessor)->get(route('asesor.schedule.ak07', $sch->id));
        $resEditAk07->assertOk();

        // 5. Complete all assigned instruments for candidate asm1
        $assignedInstruments = $asm1->getAssignedInstruments();
        foreach ($assignedInstruments as $instCode) {
            AssessmentIaResponse::create([
                'assessment_id' => $asm1->id,
                'instrument_code' => $instCode,
                'response_data' => ['status' => 'COMPLETED'],
            ]);
        }
        $sch->refresh();
        $this->assertTrue($sch->isStepUnlocked('ak02'));

        // Accessing AK.02 is now allowed
        $resAk02Unlocked = $this->actingAs($this->assessor)->get(route('asesor.schedule.ak02', $sch->id));
        $resAk02Unlocked->assertOk();

        // 6. Tutup Jadwal (reports) is locked until AK.02 evaluation is recorded
        $this->assertFalse($sch->isStepUnlocked('reports'));
        $resReportsBlocked = $this->actingAs($this->assessor)->get(route('asesor.schedule.reports', $sch->id));
        $resReportsBlocked->assertRedirect(route('asesor.schedule.ak02', $sch->id));

        // Once AK.02 is saved, reports is unlocked
        $asm1->update(['final_result' => 'KOMPETEN']);
        $sch->refresh();
        $this->assertTrue($sch->isStepUnlocked('reports'));
        $resReportsUnlocked = $this->actingAs($this->assessor)->get(route('asesor.schedule.reports', $sch->id));
        $resReportsUnlocked->assertOk();
    }

    public function test_candidate_card_color_coding_and_detail_modal_in_ak01_and_ak07(): void
    {
        $sch = Schedule::create([
            'code' => 'SCH-MODAL-'.uniqid(),
            'name' => 'Jadwal Modal Test',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA02', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);
        $sch->assessors()->attach($this->assessor->id);

        $asesi = User::factory()->create(['role' => 'ASESI', 'full_name' => 'Budi Santoso']);
        $asm = Assessment::create([
            'schedule_id' => $sch->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'APPROVED',
            'admin_status' => 'APPROVED',
        ]);

        // 1. Initial State in AK.01: candidate not approved yet -> Yellow badge, Belum Disetujui
        $resAk01Init = $this->actingAs($this->assessor)->get(route('asesor.schedule.ak01', $sch->id));
        $resAk01Init->assertOk();
        $resAk01Init->assertSee('bg-amber-50');
        $resAk01Init->assertSee('Belum Disetujui');
        $resAk01Init->assertSee('Rincian Asesi');
        $resAk01Init->assertSee('Rincian Persetujuan Asesmen Asesi');

        // 2. Assessor saves AK.01 for candidate -> Card becomes Green (Disetujui)
        $this->actingAs($this->assessor)->post(route('asesor.schedule.ak01.store', $sch->id), [
            'assessment_ids' => [$asm->id],
            'evidence' => ['Hasil Observasi Langsung', 'Hasil Pertanyaan Tertulis'],
            'tuk_type' => 'Sewaktu',
        ]);

        $resAk01Saved = $this->actingAs($this->assessor)->get(route('asesor.schedule.ak01', $sch->id));
        $resAk01Saved->assertOk();
        $resAk01Saved->assertSee('bg-emerald-50');
        $resAk01Saved->assertSee('✓ Disetujui');
        $resAk01Saved->assertSee('Data FR.AK.01 tersimpan');

        // 3. Initial State in AK.07: candidate not established yet -> Yellow badge, Belum Ditetapkan
        $resAk07Init = $this->actingAs($this->assessor)->get(route('asesor.schedule.ak07', $sch->id));
        $resAk07Init->assertOk();
        $resAk07Init->assertSee('bg-amber-50');
        $resAk07Init->assertSee('Belum Ditetapkan');
        $resAk07Init->assertSee('Rincian Penyesuaian');
        $resAk07Init->assertSee('Rincian Penyesuaian yang Wajar (FR.AK.07)');

        // 4. Assessor saves AK.07 -> Card becomes Green (Ditetapkan)
        $this->actingAs($this->assessor)->post(route('asesor.ak07.store', $sch->id), [
            'assessment_ids' => [$asm->id],
            'agreement_status' => 'YA',
            'agreement_notes' => 'Penyesuaian waktu ekstra 15 menit',
        ]);

        $resAk07Saved = $this->actingAs($this->assessor)->get(route('asesor.schedule.ak07', $sch->id));
        $resAk07Saved->assertOk();
        $resAk07Saved->assertSee('bg-emerald-50');
        $resAk07Saved->assertSee('✓ Ditetapkan');
        $resAk07Saved->assertSee('Data FR.AK.07 tersimpan');
    }

    public function test_dynamic_candidate_plotting_per_instrument_in_ruang_uji_and_ak01_detail_modal(): void
    {
        $sch = Schedule::create([
            'code' => 'SCH-DYN-'.uniqid(),
            'name' => 'Jadwal Dinamis Plotting',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Dynamic',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'selected_instruments' => ['IA01', 'IA02', 'IA03', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);
        $sch->assessors()->attach($this->assessor->id);

        $asesiA = User::factory()->create(['role' => 'ASESI', 'full_name' => 'Calon Alpha']);
        $asesiB = User::factory()->create(['role' => 'ASESI', 'full_name' => 'Calon Beta']);

        $asmA = Assessment::create([
            'schedule_id' => $sch->id,
            'asesi_id' => $asesiA->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'APPROVED',
            'admin_status' => 'APPROVED',
        ]);

        $asmB = Assessment::create([
            'schedule_id' => $sch->id,
            'asesi_id' => $asesiB->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'APPROVED',
            'admin_status' => 'APPROVED',
        ]);

        // 1. Assessor assigns IA01 & IA03 to Calon Alpha
        $this->actingAs($this->assessor)->post(route('asesor.schedule.ak01.store', $sch->id), [
            'assessment_ids' => [$asmA->id],
            'selected_instruments' => ['IA01', 'IA03'],
            'evidence' => ['Hasil Observasi Langsung'],
            'tuk_type' => 'Sewaktu',
        ]);

        // 2. Assessor assigns IA02 to Calon Beta
        $this->actingAs($this->assessor)->post(route('asesor.schedule.ak01.store', $sch->id), [
            'assessment_ids' => [$asmB->id],
            'selected_instruments' => ['IA02'],
            'evidence' => ['Hasil Portofolio'],
            'tuk_type' => 'Tempat Kerja',
        ]);

        // Check AK.01 page: Modal does NOT contain FR.AK.01 as instrument
        $resAk01 = $this->actingAs($this->assessor)->get(route('asesor.schedule.ak01', $sch->id));
        $resAk01->assertOk();
        $resAk01->assertSee('Calon Alpha');
        $resAk01->assertSee('Calon Beta');
        $resAk01->assertDontSee('Dokumen persetujuan jadwal, TUK, bukti, dan kerahasiaan materi uji.');

        // Complete AK.07 for both
        $this->actingAs($this->assessor)->post(route('asesor.ak07.store', $sch->id), [
            'assessment_ids' => [$asmA->id, $asmB->id],
            'agreement_status' => 'TIDAK',
            'agreement_notes' => 'Tidak ada penyesuaian',
        ]);

        // 3. Check Ruang Uji hub
        $resRuangUji = $this->actingAs($this->assessor)->get(route('asesor.schedule.ruang-uji', $sch->id));
        $resRuangUji->assertOk();
        // APL and AK.01 cards must NOT exist in Ruang Uji hub
        $resRuangUji->assertDontSee('Buka Verifikasi APL');
        $resRuangUji->assertDontSee('Buka Form FR.AK.01');

        // Target counts are dynamic:
        // IA.01 has 1 candidate (Calon Alpha), IA.02 has 1 candidate (Calon Beta), IA.03 has 1 candidate (Calon Alpha)
        $resRuangUji->assertSee('0 / 1 Asesi');

        // 4. Instrument IA.01 page shows Calon Alpha, does NOT show Calon Beta
        $resIa01 = $this->actingAs($this->assessor)->get(route('asesor.schedule.instrument', ['schedule' => $sch->id, 'code' => 'ia01']));
        $resIa01->assertOk();
        $resIa01->assertSee('Calon Alpha');
        $resIa01->assertDontSee('Calon Beta');

        // Instrument IA.02 page shows Calon Beta, does NOT show Calon Alpha
        $resIa02 = $this->actingAs($this->assessor)->get(route('asesor.schedule.instrument', ['schedule' => $sch->id, 'code' => 'ia02']));
        $resIa02->assertOk();
        $resIa02->assertSee('Calon Beta');
        $resIa02->assertDontSee('Calon Alpha');

        // 5. Verify AK.02 gating until all instruments are completed
        $sch->refresh();
        $this->assertFalse($sch->isAk02Unlocked());

        // Alpha completes IA01
        AssessmentIaResponse::create([
            'assessment_id' => $asmA->id,
            'instrument_code' => 'IA01',
            'response_data' => ['status' => 'COMPLETED'],
        ]);
        $sch->refresh();
        $this->assertFalse($sch->isAk02Unlocked());

        // Alpha completes IA03 (Beta has not completed IA02)
        AssessmentIaResponse::create([
            'assessment_id' => $asmA->id,
            'instrument_code' => 'IA03',
            'response_data' => ['status' => 'COMPLETED'],
        ]);
        $sch->refresh();
        $this->assertFalse($sch->isAk02Unlocked());

        // Beta completes IA02 -> Now all candidates have completed all their assigned instruments
        AssessmentIaResponse::create([
            'assessment_id' => $asmB->id,
            'instrument_code' => 'IA02',
            'response_data' => ['status' => 'COMPLETED'],
        ]);
        $sch->refresh();
        $this->assertTrue($sch->isAk02Unlocked());
    }

    public function test_closed_schedule_locks_all_interactive_buttons_for_asesor_and_asesi_while_allowing_read_only_view(): void
    {
        $asesiUser = User::factory()->create([
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $sch = Schedule::create([
            'scheme_id' => $this->scheme->id,
            'code' => 'SCH-CLOSED-TEST',
            'name' => 'Jadwal Closed Test',
            'tuk_name' => 'TUK Utama',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA01B', 'IA02', 'IA03', 'IA05'],
            'created_by' => $this->admin->id,
            'is_active' => true,
            'is_closed' => true,
            'closed_at' => now(),
            'status' => 'FINISHED',
        ]);
        $sch->assessors()->attach($this->assessor->id);

        $asm = Assessment::create([
            'schedule_id' => $sch->id,
            'asesi_id' => $asesiUser->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'APPROVED',
            'status' => 'COMPLETED',
            'final_result' => 'KOMPETEN',
            'ak01_agreed_at' => now(),
        ]);

        // 1. Assessor checks
        $resAk01 = $this->actingAs($this->assessor)->get(route('asesor.schedule.ak01', $sch));
        $resAk01->assertOk();
        $resAk01->assertSee('Jadwal Telah Ditutup (Read-Only)');
        $resAk01->assertSee('Dokumen persetujuan asesmen FR.AK.01 dalam mode lihat');

        $resAk07 = $this->actingAs($this->assessor)->get(route('asesor.schedule.ak07', $sch));
        $resAk07->assertOk();
        $resAk07->assertSee('Jadwal Telah Ditutup (Read-Only)');

        $resRuangUji = $this->actingAs($this->assessor)->get(route('asesor.schedule.ruang-uji', $sch));
        $resRuangUji->assertOk();
        $resRuangUji->assertSee('Jadwal Ditutup (Mode Lihat)');

        $resAk02 = $this->actingAs($this->assessor)->get(route('asesor.schedule.ak02', $sch));
        $resAk02->assertOk();
        $resAk02->assertSee('Jadwal Telah Ditutup (Read-Only)');

        $resReports = $this->actingAs($this->assessor)->get(route('asesor.schedule.reports', $sch));
        $resReports->assertOk();
        $resReports->assertSee('Jadwal Telah Resmi Ditutup & Dikunci (Read-Only)', false);

        // 2. Asesi checks
        $resAsesiHub = $this->actingAs($asesiUser)->get(route('asesi.assessment.ruang-uji', $asm));
        $resAsesiHub->assertOk();
        $resAsesiHub->assertSee('Jadwal Asesmen Telah Resmi Ditutup &amp; Dikunci', false);

        $resAsesiIa01b = $this->actingAs($asesiUser)->get(route('asesi.assessment.instrument.view', [$asm, 'IA01B']));
        $resAsesiIa01b->assertOk();
        $resAsesiIa01b->assertSee('Pengunggahan berkas fisik tidak lagi tersedia (mode lihat)');
        $resAsesiIa01b->assertDontSee('action="'.route('asesi.assessment.instrument.upload-evidence', [$asm, 'IA01B']).'"', false);

        $resAsesiIa05 = $this->actingAs($asesiUser)->get(route('asesi.assessment.instrument.ia05', $asm));
        $resAsesiIa05->assertOk();
        $resAsesiIa05->assertSee('Seluruh lembar ujian CBT berada dalam mode lihat (read-only)');

        $resAsesiApl = $this->actingAs($asesiUser)->get(route('asesi.assessment.apl', $asm));
        $resAsesiApl->assertOk();
        $resAsesiApl->assertSee('Jadwal Telah Ditutup (Read-Only)');

        // Post-assessment: Candidate with KOMPETEN can see both AK.03 and AK.04 Banding buttons
        $resAsesiHub->assertSee('FR.AK.03 Umpan Balik');
        $resAsesiHub->assertSee('FR.AK.04 Banding');

        // FR.AK.03 Feedback remains submittable after schedule is finished
        $resAsesiAk03 = $this->actingAs($asesiUser)->get(route('asesi.assessment.ak03', $asm));
        $resAsesiAk03->assertOk();
        $resAsesiAk03->assertSee('Kirim Umpan Balik (FR.AK.03) & Selesai');

        $ratings = [];
        for ($i = 1; $i <= 10; $i++) {
            $ratings[$i] = ['value' => 'YA', 'comment' => 'Baik'];
        }
        $resSubmitAk03 = $this->actingAs($asesiUser)->post(route('asesi.assessment.ak03.submit', $asm), [
            'ratings' => $ratings,
            'general_notes' => 'Asesmen berjalan tertib.',
        ]);
        $resSubmitAk03->assertRedirect(route('asesi.dashboard'));
        $resSubmitAk03->assertSessionHas('success');

        // FR.AK.04 Banding is accessible and submittable even when final_result is KOMPETEN
        $resAppealPage = $this->actingAs($asesiUser)->get(route('asesi.assessment.appeal', $asm));
        $resAppealPage->assertOk();
        $resAppealPage->assertSee('Apakah Proses Banding telah dijelaskan kepada Anda?');
        $resAppealPage->assertSee('Apakah Anda telah mendiskusikan Banding dengan Asesor?');
        $resAppealPage->assertSee('membantu Anda dalam Proses Banding?');

        $resSubmitAppeal = $this->actingAs($asesiUser)->post(route('asesi.assessment.appeal.submit', $asm), [
            'is_process_explained' => '1',
            'is_discussed_with_assessor' => '1',
            'involves_other_person' => '0',
            'appeal_reasons' => 'Asesor tidak fokus dan menggunakan handphone saat pengujian praktik demonstrasi berlangsung.',
        ]);
        $resSubmitAppeal->assertRedirect(route('asesi.dashboard'));
        $resSubmitAppeal->assertSessionHas('success');

        // 3. POST attempts on core testing instruments (IA01B upload & IA05 CBT) on closed schedule must be rejected
        $resUpload = $this->actingAs($asesiUser)->post(route('asesi.assessment.instrument.upload-evidence', [$asm, 'IA01B']), [
            'evidence_image' => UploadedFile::fake()->image('report.jpg'),
        ]);
        $resUpload->assertSessionHas('error');

        $resSubmitIa05 = $this->actingAs($asesiUser)->post(route('asesi.assessment.instrument.ia05.submit', $asm), [
            'answers' => ['1' => 'A'],
        ]);
        $resSubmitIa05->assertSessionHas('error');
    }

    public function test_ak03_and_ak04_are_locked_immediately_after_submission_and_redirect_to_dashboard(): void
    {
        $asesi = User::factory()->create(['role' => 'ASESI', 'status' => 'ACTIVE']);
        $sch = Schedule::create([
            'scheme_id' => $this->scheme->id,
            'code' => 'SCH-LOCK-'.uniqid(),
            'name' => 'Jadwal Lock Test',
            'tuk_name' => 'TUK Utama',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA05'],
            'created_by' => $this->admin->id,
            'is_active' => true,
            'is_closed' => false,
            'status' => 'ACTIVE',
        ]);
        $sch->assessors()->attach($this->assessor->id);

        $asm = Assessment::create([
            'schedule_id' => $sch->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'APPROVED',
            'status' => 'COMPLETED',
            'final_result' => 'KOMPETEN',
        ]);

        // 1. Submit AK.03 for the first time
        $ratings = [];
        for ($i = 1; $i <= 10; $i++) {
            $ratings[$i] = ['value' => 'YA', 'comment' => 'Memuaskan'];
        }

        $resAk03 = $this->actingAs($asesi)->post(route('asesi.assessment.ak03.submit', $asm), [
            'ratings' => $ratings,
            'general_notes' => 'Sangat memuaskan.',
        ]);
        $resAk03->assertRedirect(route('asesi.dashboard'));
        $resAk03->assertSessionHas('success');

        // 2. View AK.03: inputs disabled, locked banner and locked button shown
        $resViewAk03 = $this->actingAs($asesi)->get(route('asesi.assessment.ak03', $asm));
        $resViewAk03->assertOk();
        $resViewAk03->assertSee('Formulir FR.AK.03 Umpan Balik Telah Terkirim (Terkunci)');
        $resViewAk03->assertSee('Umpan Balik Telah Terkirim (Terkunci)');
        $resViewAk03->assertSee('disabled', false);

        // 3. Attempt to submit AK.03 again: must be blocked and redirected to dashboard
        $resResubmitAk03 = $this->actingAs($asesi)->post(route('asesi.assessment.ak03.submit', $asm), [
            'ratings' => $ratings,
            'general_notes' => 'Mencoba submit ulang.',
        ]);
        $resResubmitAk03->assertRedirect(route('asesi.dashboard'));
        $resResubmitAk03->assertSessionHas('info');

        // 4. Submit AK.04 Appeal for the first time
        $resAppeal = $this->actingAs($asesi)->post(route('asesi.assessment.appeal.submit', $asm), [
            'is_process_explained' => '1',
            'is_discussed_with_assessor' => '1',
            'involves_other_person' => '0',
            'appeal_reasons' => 'Perbedaan persepsi hasil observasi lapangan.',
        ]);
        $resAppeal->assertRedirect(route('asesi.dashboard'));
        $resAppeal->assertSessionHas('success');

        // 5. View AK.04 Appeal: inputs disabled, locked banner and locked button shown
        $resViewAppeal = $this->actingAs($asesi)->get(route('asesi.assessment.appeal', $asm));
        $resViewAppeal->assertOk();
        $resViewAppeal->assertSee('Formulir FR.AK.04 Permohonan Banding Telah Terkirim (Terkunci)');
        $resViewAppeal->assertSee('Permohonan Banding Telah Terkirim (Terkunci)');
        $resViewAppeal->assertSee('Status Banding: SUBMITTED (Terkunci)');
        $resViewAppeal->assertSee('disabled', false);

        // 6. Attempt to submit AK.04 Appeal again: must be blocked and redirected to dashboard
        $resResubmitAppeal = $this->actingAs($asesi)->post(route('asesi.assessment.appeal.submit', $asm), [
            'is_process_explained' => '1',
            'is_discussed_with_assessor' => '1',
            'involves_other_person' => '0',
            'appeal_reasons' => 'Mencoba ubah sanggahan banding.',
        ]);
        $resResubmitAppeal->assertRedirect(route('asesi.dashboard'));
        $resResubmitAppeal->assertSessionHas('info');
    }
}
