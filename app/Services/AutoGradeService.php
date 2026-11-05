<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\QuestionBank;

class AutoGradeService
{
    public function grade(Assessment $assessment, array $answers): AssessmentIaResponse
    {
        $schedule = $assessment->schedule;
        $questionIds = $schedule ? $schedule->getCbtQuestionIds() : [];
        $questions = ! empty($questionIds)
            ? QuestionBank::whereIn('id', $questionIds)->get()
            : QuestionBank::where('scheme_id', $assessment->schedule->scheme_id)->get();
        $total = $questions->count();
        $correct = 0;

        foreach ($questions as $q) {
            if (isset($answers[$q->id]) && strtoupper($answers[$q->id]) === $q->correct_answer) {
                $correct++;
            }
        }

        $score = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
        $breakdown = [];
        foreach ($questions as $q) {
            $ans = $answers[$q->id] ?? null;
            $isCorrect = ($ans !== null && strtoupper((string) $ans) === $q->correct_answer);
            $breakdown[$q->id] = [
                'question_id' => $q->id,
                'question' => $q->question_text,
                'question_image' => $q->question_image,
                'answer' => $ans,
                'correct_answer' => $q->correct_answer,
                'is_correct' => $isCorrect,
            ];
        }

        $asesiSig = $assessment->asesi_signature ?? $assessment->asesi?->profile?->signature_data;

        return AssessmentIaResponse::updateOrCreate(
            ['assessment_id' => $assessment->id, 'instrument_code' => 'IA05'],
            [
                'data_json' => [
                    'answers' => $answers,
                    'breakdown' => $breakdown,
                    'status' => 'SUBMITTED',
                ],
                'score' => $score,
                'asesi_signature' => $asesiSig,
                'asesi_signed_at' => now(),
            ]
        );
    }
}
