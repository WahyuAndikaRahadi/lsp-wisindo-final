<?php

namespace Tests\Unit;

use App\Models\InterviewQuestion;
use App\Models\Scheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterviewQuestionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_interview_question_can_be_created_and_belongs_to_scheme(): void
    {
        $scheme = Scheme::create([
            'code' => 'SCH-01',
            'name' => 'Skema Test',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);

        $question = InterviewQuestion::create([
            'scheme_id' => $scheme->id,
            'order' => 1,
            'evidence_context' => 'Sesuai dengan bukti no. 1 yang Anda ajukan',
            'question_text' => 'Ceritakan pengalaman kecelakaan kerja akibat tidak menggunakan APD',
            'expected_answer' => 'Menjelaskan APD dan risiko cedera',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('m_interview_questions', [
            'id' => $question->id,
            'scheme_id' => $scheme->id,
            'order' => 1,
        ]);

        $this->assertEquals($scheme->id, $question->scheme->id);
        $this->assertCount(1, $scheme->interviewQuestions);
    }
}
