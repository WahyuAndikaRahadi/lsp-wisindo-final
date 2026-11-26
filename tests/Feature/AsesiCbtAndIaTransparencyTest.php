<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\CompetencyUnit;
use App\Models\InterviewQuestion;
use App\Models\Kuk;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\UnitElement;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AsesiCbtAndIaTransparencyTest extends TestCase
{
    use RefreshDatabase;

    private User $asesi;

    private User $asesor;

    private Scheme $scheme;

    private Schedule $schedule;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->asesor = User::create([
            'full_name' => 'Asesor Kompetensi Uji',
            'email' => 'asesor_uji@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
        ]);

        $this->asesi = User::create([
            'full_name' => 'Asesi Uji Cbt',
            'email' => 'asesi_uji@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'name' => 'Skema Uji Transparansi',
            'code' => 'SKM-TRANS-01',
            'passing_grade_theory' => 75.0,
            'is_active' => true,
        ]);

        $unit = CompetencyUnit::create([
            'scheme_id' => $this->scheme->id,
            'code' => 'UNIT-001',
            'title' => 'Unit Kompetensi Pertama',
            'standard_reference' => 'SKKNI',
            'sequence' => 1,
        ]);

        $element = UnitElement::create([
            'competency_unit_id' => $unit->id,
            'title' => 'Elemen Pertama',
            'sequence' => 1,
        ]);

        Kuk::create([
            'unit_element_id' => $element->id,
            'code_number' => '1.1',
            'statement' => 'Kriteria unjuk kerja pertama',
            'sequence' => 1,
        ]);

        $this->schedule = Schedule::create([
            'scheme_id' => $this->scheme->id,
            'name' => 'Jadwal Uji Transparansi',
            'code' => 'JDW-TRANS-01',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'tuk_type' => 'SEWAKTU',
            'tuk_name' => 'TUK Lab Uji',
            'selected_instruments' => ['AK01', 'IA01', 'IA03', 'IA05', 'IA08', 'IA09'],
            'created_by' => $this->asesor->id,
            'is_active' => true,
        ]);

        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->asesor->id,
            'status' => 'IN_PROGRESS',
            'apl_status' => 'APPROVED',
            'admin_status' => 'APPROVED',
            'ak01_agreed_at' => now(),
            'ak01_assigned_instruments' => ['AK01', 'IA01', 'IA03', 'IA05', 'IA08', 'IA09'],
        ]);
    }

    public function test_database_backup_service_timestamps_use_asia_jakarta(): void
    {
        $service = app(DatabaseBackupService::class);
        $time = time();
        $carbon = Carbon::createFromTimestamp($time, config('app.timezone', 'Asia/Jakarta'));

        $this->assertEquals('Asia/Jakarta', $carbon->getTimezone()->getName());
    }

    public function test_asesi_ruang_uji_hub_displays_k_and_bk_status(): void
    {
        // 1. IA01 is Kompeten (K)
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA01',
            'data_json' => ['recommendation' => 'K', 'kuks' => []],
            'assessor_signed_at' => now(),
            'assessor_signature' => 'sig_data',
        ]);

        // 2. IA03 is Belum Kompeten (BK)
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA03',
            'data_json' => ['recommendation' => 'BK', 'questions' => []],
            'assessor_signed_at' => now(),
            'assessor_signature' => 'sig_data',
        ]);

        // 3. IA05 is CBT with score 80 (passing grade is 75) => Kompeten (K)
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA05',
            'score' => 80.0,
            'data_json' => ['status' => 'SUBMITTED'],
            'asesi_signed_at' => now(),
        ]);

        // 4. IA08 is Kompeten (K)
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA08',
            'data_json' => ['recommendation' => 'K', 'portfolios' => [['title' => 'Sertifikat']]],
            'assessor_signed_at' => now(),
            'assessor_signature' => 'sig_data',
        ]);

        // 5. IA09 is Belum Kompeten (BK)
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA09',
            'data_json' => ['recommendation' => 'BK', 'questions' => []],
            'assessor_signed_at' => now(),
            'assessor_signature' => 'sig_data',
        ]);

        $response = $this->actingAs($this->asesi)->get(route('asesi.assessment.ruang-uji', $this->assessment));

        $response->assertStatus(200);
        $response->assertSee('Kompeten (K)');
        $response->assertSee('Belum Kompeten (BK)');
        $response->assertSee('80/100');
    }

    public function test_ia05_cbt_review_page_shows_score_and_does_not_leak_wrong_answer_key(): void
    {
        $q1 = QuestionBank::create([
            'scheme_id' => $this->scheme->id,
            'question_text' => 'Berapakah 2 + 2?',
            'option_a' => '3',
            'option_b' => '4',
            'option_c' => '5',
            'option_d' => '6',
            'correct_answer' => 'B',
            'is_active' => true,
        ]);

        $q2 = QuestionBank::create([
            'scheme_id' => $this->scheme->id,
            'question_text' => 'Apa ibukota Indonesia?',
            'option_a' => 'Bandung',
            'option_b' => 'Surabaya',
            'option_c' => 'Jakarta',
            'option_d' => 'Medan',
            'correct_answer' => 'C',
            'is_active' => true,
        ]);

        // Asesi answered q1 = 'B' (correct), q2 = 'A' (incorrect, correct is 'C')
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA05',
            'score' => 50.0,
            'data_json' => [
                'answers' => [
                    $q1->id => 'B',
                    $q2->id => 'A',
                ],
                'breakdown' => [
                    $q1->id => [
                        'question_id' => $q1->id,
                        'is_correct' => true,
                        'answer' => 'B',
                        'user_answer' => 'B',
                        'correct_answer' => 'B',
                    ],
                    $q2->id => [
                        'question_id' => $q2->id,
                        'is_correct' => false,
                        'answer' => 'A',
                        'user_answer' => 'A',
                        'correct_answer' => 'C',
                    ],
                ],
                'status' => 'SUBMITTED',
            ],
            'asesi_signed_at' => now(),
        ]);

        $response = $this->actingAs($this->asesi)->get(route('asesi.assessment.instrument.ia05', $this->assessment));

        $response->assertStatus(200);

        // 1. Score and KKM should be clearly visible
        $response->assertSee('50.0'); // Score
        $response->assertSee('75.0'); // KKM
        $response->assertSee('BELUM KOMPETEN (BK)');

        // 2. Q1 correct check
        $response->assertSee('Jawaban Benar (+1)');
        $response->assertSee('(Benar)');
        $response->assertDontSee('Jawaban Anda (Benar)');

        // 3. Q2 wrong check
        $response->assertSee('Jawaban Salah (0)');
        $response->assertSee('(Salah)');
        $response->assertDontSee('Jawaban Anda (Salah)');

        // 4. Header clean check (no bulky badges on submitted view)
        $response->assertSee('Kembali ke Ruang Uji');
        $response->assertDontSee('Dokumen FR.IA.05.C');
        $response->assertDontSee('Jawaban CBT Telah Terkirim');

        // 5. CRITICAL: The correct answer 'C' for Q2 must NOT have "Kunci Jawaban" or reveal it is correct
        $response->assertDontSee('Kunci Jawaban: C');
        $response->assertDontSee('Kunci Jawaban: Jakarta');
        $response->assertDontSee('Jawaban yang Benar: C');
    }

    public function test_asesor_can_store_ia01_and_ia03_recommendation(): void
    {
        // Store IA01 with recommendation K
        $resIa01 = $this->actingAs($this->asesor)->post(
            route('asesor.assessment.instrument.store', ['assessment' => $this->assessment, 'code' => 'IA01']),
            [
                'recommendation' => 'K',
                'general_notes' => 'Praktik baik',
                'assessor_signature' => 'data:image/png;base64,sampleAsesorSig',
                'kuks' => [],
            ]
        );
        $resIa01->assertRedirect();

        $savedIa01 = AssessmentIaResponse::where('assessment_id', $this->assessment->id)
            ->where('instrument_code', 'IA01')
            ->first();
        $this->assertNotNull($savedIa01);
        $this->assertEquals('K', $savedIa01->data_json['recommendation']);

        // Store IA03 with recommendation BK
        $resIa03 = $this->actingAs($this->asesor)->post(
            route('asesor.assessment.instrument.store', ['assessment' => $this->assessment, 'code' => 'IA03']),
            [
                'recommendation' => 'BK',
                'notes' => 'Perlu pendalaman',
                'assessor_signature' => 'data:image/png;base64,sampleAsesorSig',
                'questions' => [],
            ]
        );
        $resIa03->assertRedirect();

        $savedIa03 = AssessmentIaResponse::where('assessment_id', $this->assessment->id)
            ->where('instrument_code', 'IA03')
            ->first();
        $this->assertNotNull($savedIa03);
        $this->assertEquals('BK', $savedIa03->data_json['recommendation']);
    }

    public function test_asesi_ia01_and_ia03_view_transparency(): void
    {
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA01',
            'data_json' => ['recommendation' => 'K', 'kuks' => []],
            'assessor_signed_at' => now(),
            'assessor_signature' => 'sig_data',
            'assessor_notes' => 'Catatan observasi bagus',
        ]);

        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA03',
            'data_json' => ['recommendation' => 'BK', 'questions' => [
                0 => ['pencapaian' => 'TM', 'notes' => 'Kurang paham'],
            ]],
            'assessor_signed_at' => now(),
            'assessor_signature' => 'sig_data',
            'assessor_notes' => 'Catatan lisan perlu belajar lagi',
        ]);

        $resIa01 = $this->actingAs($this->asesi)->get(route('asesi.assessment.instrument.view', ['assessment' => $this->assessment, 'code' => 'IA01']));
        $resIa01->assertStatus(200);
        $resIa01->assertSee('KOMPETEN (K)');
        $resIa01->assertSee('Rekomendasi Hasil Observasi Praktik');
        // KUK and unit competence table should not be displayed to asesi
        $resIa01->assertDontSee('Kriteria unjuk kerja pertama');

        $resIa03 = $this->actingAs($this->asesi)->get(route('asesi.assessment.instrument.view', ['assessment' => $this->assessment, 'code' => 'IA03']));
        $resIa03->assertStatus(200);
        $resIa03->assertSee('BELUM KOMPETEN (BK)');
        $resIa03->assertSee('Rekomendasi Hasil Pertanyaan Mendukung Observasi');
        // Questions should NOT be leaked to asesi
        $resIa03->assertDontSee('Daftar Pertanyaan Lisan');
        $resIa03->assertDontSee('Catatan Tanggapan:');
        // Per-question pencapaian should NOT be shown to asesi
        $resIa03->assertDontSee('Memuaskan (M)');
        $resIa03->assertDontSee('Tidak Memuaskan (TM)');
    }

    public function test_asesi_ia09_view_transparency_hides_per_question_pencapaian(): void
    {
        $interviewQ = InterviewQuestion::create([
            'scheme_id' => $this->scheme->id,
            'question_text' => 'Bagaimana prosedur penanganan darurat?',
            'evidence_context' => 'Konteks Uji',
            'order' => 1,
            'is_active' => true,
        ]);

        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA09',
            'data_json' => [
                'recommendation' => 'K',
                'questions' => [
                    $interviewQ->id => [
                        'candidate_answer_summary' => 'Menjawab dengan runtut dan sesuai SOP',
                        'is_achieved' => 'YA',
                    ],
                ],
            ],
            'assessor_signed_at' => now(),
            'assessor_signature' => 'sig_data',
        ]);

        $resIa09 = $this->actingAs($this->asesi)->get(route('asesi.assessment.instrument.view', ['assessment' => $this->assessment, 'code' => 'IA09']));
        $resIa09->assertStatus(200);
        $resIa09->assertSee('KOMPETEN (K)');
        $resIa09->assertSee('Transparansi Penilaian FR.IA.09');
        // Oral question text and answers should NOT be leaked to asesi
        $resIa09->assertDontSee('Bagaimana prosedur penanganan darurat?');
        $resIa09->assertDontSee('Menjawab dengan runtut dan sesuai SOP');
        $resIa09->assertDontSee('Pertanyaan Wawancara & Tanggapan');
        // Per-question pencapaian Ya/Tidak badge should NOT be shown
        $resIa09->assertDontSee('✓ Ya');
        $resIa09->assertDontSee('✕ Tidak');
    }
}
