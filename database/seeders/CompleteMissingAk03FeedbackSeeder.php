<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentAk03Item;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompleteMissingAk03FeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds to complete missing FR.AK.03 candidate feedback.
     */
    public function run(): void
    {
        $defaultRatings = [];
        for ($i = 1; $i <= 10; $i++) {
            $defaultRatings[$i] = [
                'value' => 'YA',
                'comment' => '',
            ];
        }

        $defaultGeneralNotes = 'Pelaksanaan asesmen berjalan dengan baik, lancar, dan sesuai dengan standar kompetensi.';

        $missingAssessments = Assessment::where(function ($query) {
            $query->whereDoesntHave('ak03Item')
                ->orWhereHas('ak03Item', function ($q) {
                    $q->whereNull('ratings_json')
                        ->orWhere('ratings_json', '[]')
                        ->orWhere('ratings_json', '{}');
                });
        })->get();

        $count = 0;
        DB::transaction(function () use ($missingAssessments, $defaultRatings, $defaultGeneralNotes, &$count) {
            foreach ($missingAssessments as $assessment) {
                $timestamp = $assessment->updated_at ?? $assessment->created_at ?? now();

                AssessmentAk03Item::updateOrCreate(
                    ['assessment_id' => $assessment->id],
                    [
                        'ratings_json' => $defaultRatings,
                        'general_notes' => $defaultGeneralNotes,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]
                );

                $count++;
            }
        });

        $this->command?->info("Completed FR.AK.03 feedback for {$count} assessment(s) with 'YA' for all items.");
    }
}
