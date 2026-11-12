<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\CompetencyUnit;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OptionImagesAndAssessmentRejectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $asesor;

    protected User $asesi1;

    protected User $asesi2;

    protected Scheme $scheme;

    protected CompetencyUnit $unit;

    protected Schedule $schedule;

    protected Assessment $assessment1;

    protected Assessment $assessment2;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create([
            'full_name' => 'Admin LSP',
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->asesor = User::factory()->create([
            'full_name' => 'Asesor Uji',
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'REG-12345',
        ]);

        $this->asesi1 = User::factory()->create([
            'full_name' => 'Asesi Satu',
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->asesi2 = User::factory()->create([
            'full_name' => 'Asesi Dua',
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-TEST-01',
            'name' => 'Skema Uji Otomotif',
            'scheme_type' => 'KLASTER',
            'is_active' => true,
        ]);

        $this->unit = CompetencyUnit::create([
            'scheme_id' => $this->scheme->id,
            'code' => 'OTO.001.01',
            'title' => 'Unit Uji Kompetensi',
            'standard_type' => 'SKKNI',
        ]);

        $this->schedule = Schedule::create([
            'name' => 'Jadwal Uji Penolakan & Opsi Gambar',
            'code' => 'JADWAL-REJECT-01',
            'scheme_id' => $this->scheme->id,
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'tuk_name' => 'TUK Mandiri',
            'status' => 'ACTIVE',
            'is_active' => true,
            'selected_instruments' => ['AK01', 'AK02', 'IA01', 'IA05'],
            'created_by' => $this->admin->id,
        ]);

        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment1 = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi1->id,
            'assessor_id' => $this->asesor->id,
            'status' => 'COMPLETED',
            'apl_status' => 'APPROVED',
            'admin_status' => 'APPROVED',
            'final_result' => 'KOMPETEN',
            'has_rejection' => false,
        ]);

        $this->assessment2 = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi2->id,
            'assessor_id' => $this->asesor->id,
            'status' => 'COMPLETED',
            'apl_status' => 'APPROVED',
            'admin_status' => 'APPROVED',
            'final_result' => 'BELUM_KOMPETEN',
            'has_rejection' => false,
        ]);
    }

    public function test_question_bank_can_be_created_with_option_images(): void
    {
        $fileA = UploadedFile::fake()->image('option_a.jpg', 200, 200);
        $fileB = UploadedFile::fake()->image('option_b.png', 200, 200);

        $response = $this->actingAs($this->admin)->post(route('admin.question-banks.store'), [
            'scheme_id' => $this->scheme->id,
            'competency_unit_id' => $this->unit->id,
            'question_text' => 'Manakah gambar multimeter yang benar?',
            'question_type' => 'MULTIPLE_CHOICE',
            'option_a' => 'Opsi A Teks',
            'option_a_image' => $fileA,
            'option_b' => 'Opsi B Teks',
            'option_b_image' => $fileB,
            'option_c' => 'Opsi C Teks Saja',
            'option_d' => 'Opsi D Teks Saja',
            'correct_answer' => 'A',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.question-banks.index'));

        $this->assertDatabaseHas('m_question_banks', [
            'question_text' => 'Manakah gambar multimeter yang benar?',
            'correct_answer' => 'A',
        ]);

        $qb = QuestionBank::where('question_text', 'Manakah gambar multimeter yang benar?')->first();
        $this->assertNotNull($qb->option_a_image);
        $this->assertNotNull($qb->option_b_image);
        Storage::disk('public')->assertExists($qb->option_a_image);
        Storage::disk('public')->assertExists($qb->option_b_image);
    }

    public function test_question_bank_can_remove_option_images_on_update(): void
    {
        $fileA = UploadedFile::fake()->image('option_a.jpg', 200, 200);

        $qb = QuestionBank::create([
            'scheme_id' => $this->scheme->id,
            'competency_unit_id' => $this->unit->id,
            'question_text' => 'Soal dengan gambar opsi awal',
            'question_type' => 'MULTIPLE_CHOICE',
            'option_a' => 'A',
            'option_a_image' => $fileA->store('question-images/options', 'public'),
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'A',
            'is_active' => true,
        ]);

        $this->assertTrue(Storage::disk('public')->exists($qb->option_a_image));

        $response = $this->actingAs($this->admin)->put(route('admin.question-banks.update', $qb), [
            'scheme_id' => $this->scheme->id,
            'competency_unit_id' => $this->unit->id,
            'question_text' => 'Soal diperbarui tanpa gambar opsi A',
            'question_type' => 'MULTIPLE_CHOICE',
            'option_a' => 'A Baru',
            'remove_option_a_image' => '1',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_answer' => 'B',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.question-banks.show', $qb));
        $qb->refresh();

        $this->assertNull($qb->option_a_image);
        $this->assertEquals('B', $qb->correct_answer);
    }

    public function test_assessor_can_record_assessment_rejection_in_ak05(): void
    {
        $response = $this->actingAs($this->asesor)->post(
            route('asesor.schedule.reports.submit', $this->schedule->id),
            [
                'ak05_aspek_positif' => 'Sarana dan prasarana bengkel TUK lengkap dan memadai.',
                'ak05_aspek_negatif' => 'Tidak ditemukan kendala teknis krusial di lapangan.',
                'ak05_rekomendasi' => 'Peralatan ukur dipertahankan dan dilakukan kalibrasi rutin.',
                'ak05_has_penolakan' => '1',
                'ak05_penolakan_asesi_ids' => [$this->assessment2->id],
                'ak05_penolakan_catatan' => 'Asesi merasa hasil pengukuran kompresi sudah sesuai dan ingin banding.',
                'ak06_valid' => 'YA',
                'ak06_valid_notes' => 'Bukti merujuk langsung KUK.',
                'ak06_reliabel' => 'YA',
                'ak06_reliabel_notes' => 'Kunci jawaban dan rubrik diterapkan konsisten.',
                'ak06_fleksibel' => 'YA',
                'ak06_fleksibel_notes' => 'Penyesuaian wajar diakomodasi.',
                'ak06_adil' => 'YA',
                'ak06_adil_notes' => 'Seluruh kandidat dinilai adil tanpa diskriminasi.',
                'ak06_catatan_peninjau' => 'Proses asesmen mematuhi standar BNSP.',
                'confirm_draft_review' => '1',
            ]
        );

        $response->assertSessionHasNoErrors();

        $this->assessment1->refresh();
        $this->assessment2->refresh();
        $this->schedule->refresh();

        // Assessment 2 was selected for rejection, Assessment 1 was not
        $this->assertFalse($this->assessment1->has_rejection);
        $this->assertTrue($this->assessment2->has_rejection);

        // Schedule ak05 report stores penolakan data
        $this->assertTrue($this->schedule->ak05_report['has_penolakan']);
        $this->assertContains($this->assessment2->id, $this->schedule->ak05_report['penolakan_assessment_ids']);
    }

    public function test_banding_button_visibility_and_access_is_restricted_to_rejected_candidates(): void
    {
        // Asesi 1 does NOT have rejection
        $this->assessment1->update(['has_rejection' => false]);
        $this->assertFalse($this->assessment1->canAppeal());

        // Asesi 2 HAS rejection
        $this->assessment2->update(['has_rejection' => true]);
        $this->assertTrue($this->assessment2->canAppeal());

        // Check Asesi 1 Dashboard: "Banding" button should NOT be present
        $resAsesi1 = $this->actingAs($this->asesi1)->get(route('asesi.dashboard'));
        $resAsesi1->assertOk();
        $resAsesi1->assertDontSee(route('asesi.assessment.appeal', $this->assessment1->id));

        // Check Asesi 2 Dashboard: "Banding" button SHOULD be present
        $resAsesi2 = $this->actingAs($this->asesi2)->get(route('asesi.dashboard'));
        $resAsesi2->assertOk();
        $resAsesi2->assertSee(route('asesi.assessment.appeal', $this->assessment2->id));

        // Check Ruang Uji Hub for Asesi 1
        $resHub1 = $this->actingAs($this->asesi1)->get(route('asesi.assessment.ruang-uji', $this->assessment1));
        $resHub1->assertOk();
        $resHub1->assertDontSee(route('asesi.assessment.appeal', $this->assessment1));

        // Check Ruang Uji Hub for Asesi 2
        $resHub2 = $this->actingAs($this->asesi2)->get(route('asesi.assessment.ruang-uji', $this->assessment2));
        $resHub2->assertOk();
        $resHub2->assertSee(route('asesi.assessment.appeal', $this->assessment2));

        // Asesi 1 trying to directly access appeal form should get 403 Forbidden
        $directForbidden = $this->actingAs($this->asesi1)->get(route('asesi.assessment.appeal', $this->assessment1));
        $directForbidden->assertStatus(403);

        // Asesi 2 accessing appeal form should succeed
        $directAllowed = $this->actingAs($this->asesi2)->get(route('asesi.assessment.appeal', $this->assessment2));
        $directAllowed->assertOk();
    }

    public function test_export_ak05_displays_dynamic_penolakan_information(): void
    {
        $this->schedule->update([
            'ak05_report' => [
                'has_penolakan' => true,
                'penolakan_assessment_ids' => [$this->assessment2->id],
                'penolakan_catatan' => 'Asesi Dua keberatan atas hasil uji dan mengajukan banding.',
                'negative_aspects' => 'Kendala minor pada alat ukur.',
                'positive_aspects' => 'K3 dipatuhi.',
                'improvement_suggestions' => 'Kalibrasi rutin.',
            ],
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export', [
            'schedule' => $this->schedule->id,
            'type' => 'ak05',
        ]));

        $response->assertOk();
        $response->assertSee('Ada Penolakan / Permohonan Banding dari Asesi');
        $response->assertSee($this->asesi2->full_name);
        $response->assertSee('Asesi Dua keberatan atas hasil uji dan mengajukan banding.');
    }
}
