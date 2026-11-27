<?php

namespace Database\Seeders;

use App\Models\CompetencyUnit;
use App\Models\JobGroup;
use App\Models\Kuk;
use App\Models\QuestionBank;
use App\Models\Scheme;
use App\Models\UnitElement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SchemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataPath = database_path('seeders/data');
        $files = File::glob($dataPath.'/*.json');
        sort($files);

        foreach ($files as $file) {
            $content = json_decode(File::get($file), true);
            if (! $content || empty($content['scheme'])) {
                continue;
            }

            $sData = $content['scheme'];
            $scheme = Scheme::updateOrCreate(
                ['code' => $sData['code']],
                [
                    'name' => $sData['name'],
                    'scheme_type' => $sData['scheme_type'] ?? 'KLASTER',
                    'version' => $sData['version'] ?? '1.0',
                    'description' => $sData['description'] ?? null,
                    'passing_grade_theory' => $sData['passing_grade_theory'] ?? 75.00,
                    'is_active' => true,
                ]
            );

            // Job Groups (Kelompok Pekerjaan)
            $jobGroupsBySequence = [];
            $seededGroupSequences = [];
            foreach ($content['job_groups'] ?? [] as $jgData) {
                $jgSeq = (int) ($jgData['sequence'] ?? 1);
                $jobGroup = JobGroup::updateOrCreate(
                    [
                        'scheme_id' => $scheme->id,
                        'sequence' => $jgSeq,
                    ],
                    [
                        'name' => $jgData['name'],
                    ]
                );
                $jobGroupsBySequence[$jgSeq] = $jobGroup;
                $seededGroupSequences[] = $jgSeq;
            }

            if (! empty($seededGroupSequences)) {
                JobGroup::withoutGlobalScopes()
                    ->where('scheme_id', $scheme->id)
                    ->whereNotIn('sequence', $seededGroupSequences)
                    ->delete();
            }

            // Units
            $unitModels = [];
            foreach ($content['units'] ?? [] as $uData) {
                $jgSeq = isset($uData['job_group_sequence']) ? (int) $uData['job_group_sequence'] : null;
                $jobGroupId = $jgSeq !== null && isset($jobGroupsBySequence[$jgSeq])
                    ? $jobGroupsBySequence[$jgSeq]->id
                    : null;

                $unit = CompetencyUnit::withoutGlobalScopes()->updateOrCreate(
                    [
                        'scheme_id' => $scheme->id,
                        'code' => $uData['code'],
                    ],
                    [
                        'job_group_id' => $jobGroupId,
                        'title' => $uData['title'],
                        'standard_reference' => $uData['standard_reference'] ?? 'SKKNI Terkait',
                        'sequence' => $uData['sequence'] ?? 1,
                        'job_group_sequence' => $uData['job_group_unit_sequence'] ?? null,
                    ]
                );
                $unitModels[$uData['code']] = $unit;

                // Remove legacy placeholder elements if updating an existing unit
                if (! empty($uData['elements'])) {
                    $existingElements = $unit->elements()->where('title', 'like', '%Unit Kompetensi%')->get();
                    foreach ($existingElements as $ee) {
                        $ee->kuks()->delete();
                        $ee->delete();
                    }
                }

                // Elements
                foreach ($uData['elements'] ?? [] as $eData) {
                    $element = UnitElement::updateOrCreate(
                        [
                            'competency_unit_id' => $unit->id,
                            'title' => $eData['title'],
                        ],
                        [
                            'sequence' => $eData['sequence'] ?? 1,
                        ]
                    );

                    // KUKs
                    foreach ($eData['kuks'] ?? [] as $kData) {
                        Kuk::updateOrCreate(
                            [
                                'unit_element_id' => $element->id,
                                'code_number' => $kData['code_number'],
                            ],
                            [
                                'statement' => $kData['statement'],
                                'sequence' => $kData['sequence'] ?? 1,
                            ]
                        );
                    }
                }
            }

            // Questions
            QuestionBank::where('scheme_id', $scheme->id)->delete();
            $firstUnit = ! empty($unitModels) ? reset($unitModels) : null;
            foreach ($content['questions'] ?? [] as $q) {
                QuestionBank::create([
                    'scheme_id' => $scheme->id,
                    'competency_unit_id' => $firstUnit ? $firstUnit->id : null,
                    'question_text' => $q['question_text'],
                    'option_a' => $q['option_a'],
                    'option_b' => $q['option_b'],
                    'option_c' => $q['option_c'],
                    'option_d' => $q['option_d'],
                    'correct_answer' => strtoupper($q['correct_answer']),
                ]);
            }
        }
    }
}
