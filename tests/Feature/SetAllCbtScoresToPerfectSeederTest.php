<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Database\Seeders\SetAllCbtScoresToPerfectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetAllCbtScoresToPerfectSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_seeder_sets_all_cbt_scores_and_answers_to_100(): void
    {
        $scheme = Scheme::first();
        $admin = User::where('role', 'SUPER_ADMIN')->first();
        $asesor = User::where('role', 'ASESOR')->first();
        $asesi1 = User::factory()->create(['role' => 'ASESI']);
        $asesi2 = User::factory()->create(['role' => 'ASESI']);

        $schedule = Schedule::create([
            'code' => 'SCH-PERFECT-'.uniqid(),
            'name' => 'Jadwal Perfect CBT Test',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
            'selected_instruments' => ['AK01', 'IA01', 'IA05'],
        ]);

        // Assessment 1: Has IA05 with low score (15.00)
        $asm1 = Assessment::create([
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'asesi_id' => $asesi1->id,
            'assessor_id' => $asesor->id,
            'apl_status' => 'APPROVED',
            'status' => 'COMPLETED',
        ]);

        $assignedIds = $schedule->getCbtQuestionIds();
        $questions = QuestionBank::whereIn('id', $assignedIds)->get();
        $this->assertNotEmpty($questions);

        // Fill with wrong answers
        $wrongAnswers = [];
        foreach ($questions as $q) {
            $wrongAnswers[$q->id] = 'WRONG';
        }

        AssessmentIaResponse::create([
            'assessment_id' => $asm1->id,
            'instrument_code' => 'IA05',
            'score' => 15.00,
            'data_json' => [
                'answers' => $wrongAnswers,
                'breakdown' => [],
                'status' => 'SUBMITTED',
            ],
            'asesi_signed_at' => now(),
        ]);

        // Assessment 2: Missing IA05 completely
        $asm2 = Assessment::create([
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'asesi_id' => $asesi2->id,
            'assessor_id' => $asesor->id,
            'apl_status' => 'APPROVED',
            'status' => 'COMPLETED',
        ]);

        // Run the seeder
        (new SetAllCbtScoresToPerfectSeeder)->run();

        // Check Assessment 1
        $ia05_1 = AssessmentIaResponse::where('assessment_id', $asm1->id)->where('instrument_code', 'IA05')->first();
        $this->assertNotNull($ia05_1);
        $this->assertEquals(100.00, (float) $ia05_1->score);
        $this->assertEquals('SUBMITTED', $ia05_1->data_json['status']);

        foreach ($questions as $q) {
            $this->assertEquals(strtoupper((string) $q->correct_answer), $ia05_1->data_json['answers'][$q->id]);
            $this->assertTrue($ia05_1->data_json['breakdown'][$q->id]['is_correct']);
            $this->assertEquals(strtoupper((string) $q->correct_answer), $ia05_1->data_json['breakdown'][$q->id]['answer']);
        }

        // Check Assessment 2 (created and set to 100)
        $ia05_2 = AssessmentIaResponse::where('assessment_id', $asm2->id)->where('instrument_code', 'IA05')->first();
        $this->assertNotNull($ia05_2);
        $this->assertEquals(100.00, (float) $ia05_2->score);
        $this->assertEquals('SUBMITTED', $ia05_2->data_json['status']);

        foreach ($questions as $q) {
            $this->assertEquals(strtoupper((string) $q->correct_answer), $ia05_2->data_json['answers'][$q->id]);
            $this->assertTrue($ia05_2->data_json['breakdown'][$q->id]['is_correct']);
        }
    }
}
