<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Database\Seeders\CorrectedQuestionBankAnswerKeySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorrectedQuestionBankAnswerKeySeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_seeder_corrects_fr_ia_05_question_bank_answer_keys(): void
    {
        (new CorrectedQuestionBankAnswerKeySeeder)->run();

        // Skema 2
        $s2 = Scheme::where('code', 'SKM-02-2026')->first();
        if ($s2) {
            $q2 = QuestionBank::where('scheme_id', $s2->id)->orderBy('id')->get();
            $this->assertEquals('B', $q2[2]->correct_answer, 'Skema 2 No 3 must be B');
            $this->assertEquals('D', $q2[4]->correct_answer, 'Skema 2 No 5 must be D');
            $this->assertEquals('B', $q2[12]->correct_answer, 'Skema 2 No 13 must be B');
            $this->assertEquals('A', $q2[19]->correct_answer, 'Skema 2 No 20 must be A');
        }

        // Skema 3
        $s3 = Scheme::where('code', 'SKM-03-2026')->first();
        if ($s3) {
            $q3 = QuestionBank::where('scheme_id', $s3->id)->orderBy('id')->get();
            $expectedS3 = [
                1 => 'A', 2 => 'B', 3 => 'D', 4 => 'C', 5 => 'C', 6 => 'D', 7 => 'A',
                8 => 'A', 9 => 'B', 10 => 'D', 11 => 'A', 12 => 'A', 13 => 'D', 14 => 'D',
                15 => 'A', 16 => 'A', 17 => 'B', 18 => 'D', 19 => 'A', 20 => 'B', 21 => 'A',
                22 => 'C', 23 => 'B', 24 => 'D', 25 => 'D', 26 => 'A', 27 => 'B', 28 => 'B',
                29 => 'B', 30 => 'A',
            ];
            foreach ($expectedS3 as $num => $exp) {
                $this->assertEquals($exp, $q3[$num - 1]->correct_answer, "Skema 3 No $num must be $exp");
            }
        }

        // Skema 5
        $s5 = Scheme::where('code', 'SKM-05-2026')->first();
        if ($s5) {
            $q5 = QuestionBank::where('scheme_id', $s5->id)->orderBy('id')->get();
            $this->assertEquals('B', $q5[60]->correct_answer, 'Skema 5 No 61 must be B');
        }

        // Skema 6
        $s6 = Scheme::where('code', 'SKM-06-2026')->first();
        if ($s6) {
            $q6 = QuestionBank::where('scheme_id', $s6->id)->orderBy('id')->get();
            $this->assertEquals('C', $q6[22]->correct_answer, 'Skema 6 No 23 must be C');
            $this->assertEquals('A', $q6[30]->correct_answer, 'Skema 6 No 31 must be A');
        }

        // Skema 7
        $s7 = Scheme::where('code', 'SKM-07-2026')->first();
        if ($s7) {
            $q7 = QuestionBank::where('scheme_id', $s7->id)->orderBy('id')->get();
            $this->assertEquals('B', $q7[14]->correct_answer, 'Skema 7 No 15 must be B');
            $this->assertEquals('Penggantian', $q7[14]->option_b, 'Skema 7 No 15 option B must be Penggantian');
            $this->assertEquals('B', $q7[51]->correct_answer, 'Skema 7 No 52 must be B');
        }
    }

    public function test_seeder_recalculates_submitted_ia05_cbt_responses(): void
    {
        $scheme = Scheme::where('code', 'SKM-03-2026')->first();
        if (! $scheme) {
            $this->markTestSkipped('Automotive scheme SKM-03-2026 is not applicable for Wisindo.');
        }
        $admin = User::where('role', 'SUPER_ADMIN')->first();
        $asesor = User::where('role', 'ASESOR')->first();
        $asesi = User::factory()->create(['role' => 'ASESI']);

        $schedule = Schedule::create([
            'code' => 'SCH-RECALC-'.uniqid(),
            'name' => 'Jadwal Recalc Test',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'FINISHED',
            'is_closed' => true,
            'created_by' => $admin->id,
            'selected_instruments' => ['AK01', 'IA01', 'IA05'],
        ]);

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $asesor->id,
            'apl_status' => 'APPROVED',
            'status' => 'COMPLETED',
        ]);

        $questions = QuestionBank::where('scheme_id', $scheme->id)->orderBy('id')->get();
        $this->assertNotEmpty($questions);

        // Simulate an asesi response submitted with old wrong answer keys (score: 0)
        // Suppose the asesi answered with the new correct keys:
        $answers = [];
        $expectedCorrect = [
            1 => 'A', 2 => 'B', 3 => 'D', 4 => 'C', 5 => 'C', 6 => 'D', 7 => 'A',
            8 => 'A', 9 => 'B', 10 => 'D', 11 => 'A', 12 => 'A', 13 => 'D', 14 => 'D',
            15 => 'A', 16 => 'A', 17 => 'B', 18 => 'D', 19 => 'A', 20 => 'B', 21 => 'A',
            22 => 'C', 23 => 'B', 24 => 'D', 25 => 'D', 26 => 'A', 27 => 'B', 28 => 'B',
            29 => 'B', 30 => 'A',
        ];

        foreach ($questions as $idx => $q) {
            $answers[$q->id] = $expectedCorrect[$idx + 1] ?? 'A';
        }

        // Initially create response with old bad score of 15.00
        $ia05 = AssessmentIaResponse::create([
            'assessment_id' => $assessment->id,
            'instrument_code' => 'IA05',
            'score' => 15.00,
            'data_json' => [
                'answers' => $answers,
                'breakdown' => [],
                'status' => 'SUBMITTED',
            ],
            'asesi_signed_at' => now(),
        ]);

        // Run the seeder
        (new CorrectedQuestionBankAnswerKeySeeder)->run();

        // Refresh and verify recalculation
        $ia05->refresh();
        $this->assertEquals(100.00, (float) $ia05->score);
        $this->assertNotEmpty($ia05->data_json['breakdown']);

        $firstQ = $questions->first();
        $this->assertTrue($ia05->data_json['breakdown'][$firstQ->id]['is_correct']);
        $this->assertEquals('A', $ia05->data_json['breakdown'][$firstQ->id]['correct_answer']);
    }
}
