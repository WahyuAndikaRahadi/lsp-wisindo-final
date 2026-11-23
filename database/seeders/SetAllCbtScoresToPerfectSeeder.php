<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\QuestionBank;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SetAllCbtScoresToPerfectSeeder extends Seeder
{
    /**
     * Run the database seeds to set all candidates' CBT (FR.IA.05) answers and scores to 100%.
     */
    public function run(): void
    {
        $assessments = Assessment::with(['schedule', 'asesi.profile'])->get();
        $updatedCount = 0;

        DB::transaction(function () use ($assessments, &$updatedCount) {
            foreach ($assessments as $assessment) {
                $schedule = $assessment->schedule;
                if (! $schedule) {
                    continue;
                }

                $questionIds = $schedule->getCbtQuestionIds();
                $questions = ! empty($questionIds)
                    ? QuestionBank::whereIn('id', $questionIds)->get()
                    : QuestionBank::where('scheme_id', $schedule->scheme_id)->get();

                if ($questions->isEmpty()) {
                    continue;
                }

                $answers = [];
                $breakdown = [];

                foreach ($questions as $q) {
                    $key = strtoupper((string) $q->correct_answer);
                    $answers[$q->id] = $key;
                    $breakdown[$q->id] = [
                        'question_id' => $q->id,
                        'question' => $q->question_text,
                        'question_image' => $q->question_image,
                        'answer' => $key,
                        'user_answer' => $key,
                        'correct_answer' => $key,
                        'is_correct' => true,
                    ];
                }

                $resp = AssessmentIaResponse::firstOrNew([
                    'assessment_id' => $assessment->id,
                    'instrument_code' => 'IA05',
                ]);

                $data = is_array($resp->data_json)
                    ? $resp->data_json
                    : (json_decode($resp->data_json ?? '[]', true) ?: []);

                $data['answers'] = $answers;
                $data['breakdown'] = $breakdown;
                $data['status'] = 'SUBMITTED';

                $signature = $resp->asesi_signature
                    ?: ($assessment->asesi_signature ?? $assessment->asesi?->profile?->signature_data);
                $signedAt = $resp->asesi_signed_at ?: now();

                $resp->fill([
                    'score' => 100.00,
                    'data_json' => $data,
                    'asesi_signature' => $signature,
                    'asesi_signed_at' => $signedAt,
                ]);

                $resp->save();
                $updatedCount++;
            }
        });

        $this->command?->info("Successfully set CBT (FR.IA.05) score to 100 for {$updatedCount} candidate assessment(s).");
    }
}
