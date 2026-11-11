<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Services\AutoGradeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Ia05AssessorBreakdownTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $asesor;

    protected User $asesi;

    protected Scheme $scheme;

    protected Schedule $schedule;

    protected Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('role', 'ADMIN_LSP')->first();
        $this->asesor = User::where('role', 'ASESOR')->first();
        $this->asesi = User::where('role', 'ASESI')->first();
        $this->scheme = Scheme::first();

        $this->schedule = Schedule::create([
            'code' => 'SCH-IA05-TEST-'.uniqid(),
            'name' => 'Jadwal Uji IA05 CBT',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Utama',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'selected_instruments' => ['AK01', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
        ]);
        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment = Assessment::create([
            'asesi_id' => $this->asesi->id,
            'schedule_id' => $this->schedule->id,
            'scheme_id' => $this->scheme->id,
            'assessor_id' => $this->asesor->id,
            'status' => 'IN_PROGRESS',
            'apl_status' => 'APPROVED',
            'ak01_status' => 'APPROVED',
            'ak01_data' => ['selected_instruments' => ['IA05']],
        ]);
    }

    public function test_assessor_can_see_candidate_answers_in_ia05_breakdown(): void
    {
        // Create 4 distinct questions with IDs similar to user's report (e.g. 62, 63, 65, 66)
        $q1 = QuestionBank::create([
            'scheme_id' => $this->scheme->id,
            'question_text' => 'Sebutkan peralatan yang termasuk dalam Keselamatan dan Kesehatan Kerja ?',
            'option_a' => 'Obeng',
            'option_b' => 'Tang',
            'option_c' => 'Kunci Pas',
            'option_d' => 'Kacamata dan Helm',
            'correct_answer' => 'D',
        ]);

        $q2 = QuestionBank::create([
            'scheme_id' => $this->scheme->id,
            'question_text' => 'Untuk memastikan apakah APAR masih dapat berfungsi dengan normal,maka hal yang perlu diperiksa adalah :',
            'option_a' => 'Tekanan pada jarum manometer',
            'option_b' => 'Warna tabung',
            'option_c' => 'Berat selang',
            'option_d' => 'Bentuk segel',
            'correct_answer' => 'A',
        ]);

        $q3 = QuestionBank::create([
            'scheme_id' => $this->scheme->id,
            'question_text' => 'Apa yang anda lakukan ketika pekerjaan yang anda lakukan selesai :',
            'option_a' => 'Langsung pulang',
            'option_b' => 'Meninggalkan tempat kerja kotor',
            'option_c' => 'Membersihkan dan merapikan alat kerja',
            'option_d' => 'Menyerahkan pada orang lain',
            'correct_answer' => 'C',
        ]);

        $q4 = QuestionBank::create([
            'scheme_id' => $this->scheme->id,
            'question_text' => 'Saat ini di bengkel / workshop perbaikan kendaraan sudah menggunakan Caddy portable. Apa keuntungannya.',
            'option_a' => 'Bisa untuk duduk',
            'option_b' => 'Memudahkan mobilitas dan penyimpanan perkakas',
            'option_c' => 'Harganya sangat murah',
            'option_d' => 'Tidak butuh perawatan',
            'correct_answer' => 'B',
        ]);

        $this->schedule->update([
            'cbt_question_ids' => [$q1->id, $q2->id, $q3->id, $q4->id],
        ]);

        // Candidate answers:
        // q1 => D (correct)
        // q2 => A (correct)
        // q3 => C (correct)
        // q4 => A (incorrect, correct is B)
        $answers = [
            $q1->id => 'D',
            $q2->id => 'A',
            $q3->id => 'C',
            $q4->id => 'A',
        ];

        $grader = app(AutoGradeService::class);
        $grader->grade($this->assessment, $answers);

        $response = $this->actingAs($this->asesor)->get(
            route('asesor.ruang-uji.ia05', $this->assessment)
        );

        $response->assertOk();

        // Must display the question texts
        $response->assertSee('Sebutkan peralatan yang termasuk dalam Keselamatan dan Kesehatan Kerja ?');
        $response->assertSee('Untuk memastikan apakah APAR masih dapat berfungsi dengan normal');
        $response->assertSee('Apa yang anda lakukan ketika pekerjaan yang anda lakukan selesai');
        $response->assertSee('Saat ini di bengkel / workshop perbaikan kendaraan sudah menggunakan Caddy portable');

        // Must display the candidate's answers D, A, C, and A (for q4)
        $response->assertSeeText('D');
        $response->assertSeeText('A');
        $response->assertSeeText('C');

        // Results
        $response->assertSee('Benar');
        $response->assertSee('Salah');
    }

    public function test_legacy_saved_data_without_user_answer_key_displays_answers_correctly(): void
    {
        $q = QuestionBank::create([
            'scheme_id' => $this->scheme->id,
            'question_text' => 'Pertanyaan Legacy Test',
            'option_a' => 'Opsi A',
            'option_b' => 'Opsi B',
            'option_c' => 'Opsi C',
            'option_d' => 'Opsi D',
            'correct_answer' => 'B',
        ]);

        // Simulate legacy data saved with only 'answer' key in breakdown
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA05',
            'data_json' => [
                'answers' => [
                    $q->id => 'B',
                ],
                'breakdown' => [
                    $q->id => [
                        'question_id' => $q->id,
                        'question' => $q->question_text,
                        'question_image' => null,
                        'answer' => 'B', // Only 'answer', NO 'user_answer' key
                        'correct_answer' => 'B',
                        'is_correct' => true,
                    ],
                ],
                'status' => 'SUBMITTED',
            ],
            'score' => 100,
            'asesi_signed_at' => now(),
        ]);

        $response = $this->actingAs($this->asesor)->get(
            route('asesor.ruang-uji.ia05', $this->assessment)
        );

        $response->assertOk();
        $response->assertSee('Pertanyaan Legacy Test');
        // Candidate answer B must be rendered and not fallback to '-'
        $response->assertSeeText('B');
        $response->assertSee('Benar');
    }
}
