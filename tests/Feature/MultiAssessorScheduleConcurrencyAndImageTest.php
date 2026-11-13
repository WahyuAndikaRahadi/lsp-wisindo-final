<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAk07Item;
use App\Models\AssessmentIaResponse;
use App\Models\CompetencyUnit;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MultiAssessorScheduleConcurrencyAndImageTest extends TestCase
{
    use RefreshDatabase;

    private User $assessor1;

    private User $assessor2;

    private User $asesiA;

    private User $asesiB;

    private Scheme $scheme;

    private Schedule $schedule;

    private Assessment $asmA;

    private Assessment $asmB;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->assessor1 = User::create([
            'full_name' => 'Assessor Alfa',
            'email' => 'assessor_alfa@lsp.test',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'MET.000.111111',
        ]);

        $this->assessor2 = User::create([
            'full_name' => 'Assessor Beta',
            'email' => 'assessor_beta@lsp.test',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'MET.000.222222',
        ]);

        $this->asesiA = User::create([
            'full_name' => 'Asesi Angga',
            'email' => 'asesi_angga@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->asesiB = User::create([
            'full_name' => 'Asesi Bagus',
            'email' => 'asesi_bagus@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-TEST-01',
            'name' => 'Skema Pengujian Multi Asesor',
            'description' => 'Skema uji coba',
            'is_active' => true,
        ]);

        $unit = CompetencyUnit::create([
            'scheme_id' => $this->scheme->id,
            'code' => 'TIK.TEST.001.01',
            'title' => 'Unit Uji Coba',
            'standard_type' => 'SKKNI',
        ]);

        $this->schedule = Schedule::create([
            'scheme_id' => $this->scheme->id,
            'code' => 'JDW-MULTI-01',
            'name' => 'Jadwal Multi Asesor Concurrency',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'tuk_type' => 'Sewaktu',
            'tuk_name' => 'Lab Komputer',
            'selected_instruments' => ['IA01', 'IA02'],
            'status' => 'ACTIVE',
            'is_closed' => false,
            'created_by' => $this->assessor1->id,
        ]);

        $this->schedule->assessors()->attach([$this->assessor1->id, $this->assessor2->id]);

        $this->asmA = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'scheme_id' => $this->scheme->id,
            'asesi_id' => $this->asesiA->id,
            'assessor_id' => $this->assessor1->id,
            'apl_status' => 'APPROVED',
            'admin_status' => 'APPROVED',
            'status' => 'IN_PROGRESS',
        ]);

        $this->asmB = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'scheme_id' => $this->scheme->id,
            'asesi_id' => $this->asesiB->id,
            'assessor_id' => $this->assessor2->id,
            'apl_status' => 'APPROVED',
            'admin_status' => 'APPROVED',
            'status' => 'IN_PROGRESS',
        ]);
    }

    public function test_question_bank_image_url_accessor_and_storage_fallback_route(): void
    {
        $file = UploadedFile::fake()->image('soal_mesin.jpg', 400, 300);
        $path = $file->store('question-images', 'public');

        $question = QuestionBank::create([
            'scheme_id' => $this->scheme->id,
            'question_text' => 'Berapa torsi standar baut silinder head?',
            'question_image' => $path,
            'option_a' => '25 Nm',
            'option_b' => '40 Nm',
            'option_c' => '65 Nm',
            'option_d' => '85 Nm',
            'correct_answer' => 'C',
        ]);

        $this->assertNotNull($question->image_url);
        $this->assertStringContainsString($path, $question->image_url);

        // Test fallback storage route serves file
        $response = $this->get('/storage/'.$path);
        $response->assertOk();
    }

    public function test_assessor_cannot_access_other_assessors_candidate_in_ruang_uji(): void
    {
        // Assessor 2 attempts to view/evaluate Assessor 1's candidate (asmA)
        $response = $this->actingAs($this->assessor2)->get(
            route('asesor.assessment.instrument', ['assessment' => $this->asmA->id, 'code' => 'ia02'])
        );

        $response->assertForbidden();
    }

    public function test_assessor_1_completing_schedule_reports_does_not_close_schedule_for_assessor_2(): void
    {
        // Assessor 1 completes Candidate A
        $this->asmA->update([
            'final_result' => 'KOMPETEN',
            'status' => 'COMPLETED',
            'assessor_signed_at' => now(),
            'final_result_at' => now(),
        ]);

        // Assessor 1 visits showReports
        $reportPage = $this->actingAs($this->assessor1)->get(route('asesor.schedule.reports', $this->schedule->id));
        $reportPage->assertOk();
        // Candidate count shows 1 Orang Binaan
        $reportPage->assertSee('1 Orang (1 Selesai)');
        $reportPage->assertSee('Total Jadwal: 2 Orang lintas 2 Asesor');

        // Assessor 1 submits reports
        $payload = [
            'ak05_aspek_positif' => 'Fasilitas bengkel TUK lengkap dan memenuhi SOP.',
            'ak05_aspek_negatif' => 'Tidak ada kendala berarti selama pelaksanaan uji.',
            'ak05_rekomendasi' => 'Peralatan ukur dipertahankan dan dirawat berkala.',
            'ak06_valid' => 'YA',
            'ak06_valid_notes' => 'Bukti valid sesuai SKKNI.',
            'ak06_reliabel' => 'YA',
            'ak06_reliabel_notes' => 'Konsisten dengan rubrik asesmen.',
            'ak06_fleksibel' => 'YA',
            'ak06_fleksibel_notes' => 'Tersedia instrumen penyesuaian jika diperlukan.',
            'ak06_adil' => 'YA',
            'ak06_adil_notes' => 'Tidak diskriminatif dan setara.',
            'ak06_catatan_peninjau' => 'Proses sertifikasi kompetensi berjalan lancar.',
            'confirm_draft_review' => '1',
        ];

        $postReport = $this->actingAs($this->assessor1)->post(
            route('asesor.schedule.reports.submit', $this->schedule->id),
            $payload
        );

        // Redirects to jadwal index with success explaining schedule remains active for other assessor
        $postReport->assertRedirect(route('asesor.jadwal.index'));
        $postReport->assertSessionHas('success');

        $this->schedule->refresh();

        // CRITICAL CONCURRENCY ASSERTIONS:
        // Schedule MUST NOT be closed because Assessor 2's candidate (asmB) is still IN_PROGRESS!
        $this->assertFalse((bool) $this->schedule->is_closed);
        $this->assertEquals('ACTIVE', $this->schedule->status);

        // Assessor 2 CAN STILL view and evaluate candidate B
        $assessor2RuangUji = $this->actingAs($this->assessor2)->get(
            route('asesor.schedule.instrument', ['schedule' => $this->schedule->id, 'code' => 'ia02'])
        );
        $assessor2RuangUji->assertOk();
        $assessor2RuangUji->assertSee($this->asesiB->full_name);
        $assessor2RuangUji->assertDontSee($this->asesiA->full_name);

        // Now Assessor 2 completes Candidate B
        $this->asmB->update([
            'final_result' => 'KOMPETEN',
            'status' => 'COMPLETED',
            'assessor_signed_at' => now(),
            'final_result_at' => now(),
        ]);

        // Assessor 2 submits their report
        $postReport2 = $this->actingAs($this->assessor2)->post(
            route('asesor.schedule.reports.submit', $this->schedule->id),
            $payload
        );

        $postReport2->assertRedirect(route('asesor.jadwal.index'));

        $this->schedule->refresh();

        // Now that all candidates across all assessors are completed, the schedule is officially closed
        $this->assertTrue((bool) $this->schedule->is_closed);
        $this->assertEquals('FINISHED', $this->schedule->status);
    }

    public function test_completed_assessor_sees_disabled_and_readonly_ui_while_other_assessor_remains_active(): void
    {
        // 0. Set up Candidate B so Ruang Uji and workflow steps are unlocked for Assessor 2
        $this->asmB->update([
            'ak01_agreed_at' => now(),
            'assessor_signature' => 'data:image/png;base64,mockAssessorSig',
        ]);
        AssessmentAk07Item::create([
            'assessment_id' => $this->asmB->id,
            'adjustment_category' => 'Standar',
            'adjustment_detail' => 'Tanpa penyesuaian',
            'assessor_id' => $this->assessor2->id,
        ]);
        AssessmentIaResponse::create([
            'assessment_id' => $this->asmB->id,
            'instrument_code' => 'IA01',
            'assessor_signature' => 'data:image/png;base64,mockAssessorSig',
            'data_json' => ['answers' => []],
        ]);
        AssessmentIaResponse::create([
            'assessment_id' => $this->asmB->id,
            'instrument_code' => 'IA02',
            'assessor_signature' => 'data:image/png;base64,mockAssessorSig',
            'data_json' => ['ia02_acknowledged' => true],
        ]);

        // 1. Assessor 1 completes Candidate A
        $this->asmA->update([
            'final_result' => 'KOMPETEN',
            'status' => 'COMPLETED',
            'assessor_signed_at' => now(),
            'final_result_at' => now(),
        ]);

        // Assessor 1 submits reports
        $payload = [
            'ak05_aspek_positif' => 'Fasilitas bengkel TUK lengkap dan memenuhi SOP.',
            'ak05_aspek_negatif' => 'Tidak ada kendala.',
            'ak05_rekomendasi' => 'Peralatan ukur dipertahankan.',
            'ak06_valid' => 'YA',
            'ak06_valid_notes' => 'Bukti valid sesuai SKKNI.',
            'ak06_reliabel' => 'YA',
            'ak06_reliabel_notes' => 'Konsisten dengan rubrik asesmen.',
            'ak06_fleksibel' => 'YA',
            'ak06_fleksibel_notes' => 'Tersedia instrumen penyesuaian jika diperlukan.',
            'ak06_adil' => 'YA',
            'ak06_adil_notes' => 'Tidak diskriminatif dan setara.',
            'ak06_catatan_peninjau' => 'Proses asesmen lancar.',
            'confirm_draft_review' => '1',
        ];

        $this->actingAs($this->assessor1)->post(
            route('asesor.schedule.reports.submit', $this->schedule->id),
            $payload
        )->assertRedirect(route('asesor.jadwal.index'));

        $this->schedule->refresh();

        // Database checks: schedule is still active globally, but closed for Assessor 1
        $this->assertFalse((bool) $this->schedule->is_closed);
        $this->assertEquals('ACTIVE', $this->schedule->status);
        $this->assertTrue($this->schedule->isClosedForAssessor($this->assessor1));
        $this->assertFalse($this->schedule->isClosedForAssessor($this->assessor2));

        // 2. Assessor 1 UI checks (Read-Only & Disabled states)
        // Hub / Ruang Uji
        $hubResponse1 = $this->actingAs($this->assessor1)->get(
            route('asesor.schedule.ruang-uji', $this->schedule->id)
        );
        $hubResponse1->assertOk();
        $hubResponse1->assertSee('STATUS: SELESAI');
        $hubResponse1->assertSee('Asesmen Telah Selesai');
        $hubResponse1->assertSee('Selesai (Read-Only)');

        // IA02 Instrument list
        $listResponse1 = $this->actingAs($this->assessor1)->get(
            route('asesor.schedule.instrument', ['schedule' => $this->schedule->id, 'code' => 'ia02'])
        );
        $listResponse1->assertOk();
        $listResponse1->assertSee('Selesai (Read-Only)');
        $listResponse1->assertSee('Lihat Dokumen');

        // FR.AK.02 Schedule page
        $ak02Response1 = $this->actingAs($this->assessor1)->get(
            route('asesor.schedule.ak02', $this->schedule->id)
        );
        $ak02Response1->assertOk();
        $ak02Response1->assertSee('Selesai (Read-Only)');

        // Reports page
        $reportResponse1 = $this->actingAs($this->assessor1)->get(
            route('asesor.schedule.reports', $this->schedule->id)
        );
        $reportResponse1->assertOk();
        $reportResponse1->assertSee('Laporan Telah Disimpan (Selesai)');

        // 3. Assessor 1 mutation attempts are blocked
        $postIa02Attempt = $this->actingAs($this->assessor1)->post(
            route('asesor.assessment.instrument.store', ['assessment' => $this->asmA->id, 'code' => 'ia02']),
            ['data' => ['tasks' => []]]
        );
        $postIa02Attempt->assertSessionHas('error', 'Jadwal asesmen sudah ditutup dan dikunci.');

        $postAk02Attempt = $this->actingAs($this->assessor1)->post(
            route('asesor.schedule.ak02.bulk', $this->schedule->id),
            [
                'results' => [
                    $this->asmA->id => [
                        'final_result' => 'KOMPETEN',
                        'follow_up' => 'Test',
                    ],
                ],
            ]
        );
        $postAk02Attempt->assertSessionHas('error', 'Jadwal asesmen sudah ditutup dan dikunci.');

        // 4. Assessor 2 UI checks on the SAME schedule (Fully Active & Editable)
        $hubResponse2 = $this->actingAs($this->assessor2)->get(
            route('asesor.schedule.ruang-uji', $this->schedule->id)
        );
        $hubResponse2->assertOk();
        $hubResponse2->assertDontSee('STATUS: BINAAN SELESAI');
        $hubResponse2->assertDontSee('Binaan Selesai (Mode Lihat)');

        $ak02Response2 = $this->actingAs($this->assessor2)->get(
            route('asesor.schedule.ak02', $this->schedule->id)
        );
        $ak02Response2->assertOk();
        $ak02Response2->assertDontSee('Asesmen Binaan Selesai (Read-Only)');
        $ak02Response2->assertSee('Simpan Seluruh Rekaman FR.AK.02 (Bulk Save)');

        // Assessor 2 can bulk update their candidate
        $postAk02Assessor2 = $this->actingAs($this->assessor2)->post(
            route('asesor.schedule.ak02.bulk', $this->schedule->id),
            [
                'results' => [
                    $this->asmB->id => [
                        'final_result' => 'KOMPETEN',
                        'follow_up' => 'Diusulkan sertifikat',
                        'notes' => 'Kompeten unjuk kerja',
                    ],
                ],
            ]
        );
        $postAk02Assessor2->assertRedirect(route('asesor.schedule.ak02', $this->schedule->id));
        $postAk02Assessor2->assertSessionHas('success');

        $this->asmB->refresh();
        $this->assertEquals('KOMPETEN', $this->asmB->final_result);
    }
}
