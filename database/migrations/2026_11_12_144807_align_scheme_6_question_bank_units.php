<?php

use App\Models\CompetencyUnit;
use App\Models\QuestionBank;
use App\Models\Scheme;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $scheme = Scheme::where('code', 'SKM-06-2026')->orWhere('id', 6)->first();
        if (! $scheme) {
            return;
        }

        $units = CompetencyUnit::where('scheme_id', $scheme->id)->orderBy('id', 'asc')->get()->values();
        if ($units->isEmpty()) {
            return;
        }

        // Get all 40 questions of scheme 6 ordered by ID ascending (1 to 40)
        $questions = QuestionBank::where('scheme_id', $scheme->id)->orderBy('id', 'asc')->get()->values();
        if ($questions->count() < 40) {
            return;
        }

        // Mapping index (0-based) to unit index (0-based) matching official MUK FR.IA.05.A:
        // Q 1-5  (index 0..4)   -> Unit 0 (C.29SEV01.005.1 - Membaca EWD)
        // Q 6-10 (index 5..9)   -> Unit 1 (C.27BAT00.005.1 - Pengujian Pak Baterai)
        // Q 11-15 (index 10..14) -> Unit 2 (C.27BAT00.006.1 - Komisioning Pak Baterai)
        // Q 16-18 (index 15..17) -> Unit 3 (C.27BAT00.007.1 - Monitoring Baterai)
        // Q 19-21 (index 18..20) -> Unit 4 (G45TSM01.040.2 - Sistem Sinyal & Penerangan)
        // Q 22-24 (index 21..23) -> Unit 5 (G.45TSM01.021.2 - Free Play Throttle Gas)
        // Q 25-27 (index 24..26) -> Unit 6 (G45TSM01.031.2 - Sistem Pengereman)
        // Q 28-31 (index 27..30) -> Unit 7 (G45TSM01.032.2 - Sistem Kemudi)
        // Q 32-37 (index 31..36) -> Unit 8 (G45TSM01.033.2 - Sistem Suspensi)
        // Q 38-40 (index 37..39) -> Unit 9 (G45TSM01.034.2 - Bongkar Pasang Cover Body)

        $unitRanges = [
            0 => [0, 4],
            1 => [5, 9],
            2 => [10, 14],
            3 => [15, 17],
            4 => [18, 20],
            5 => [21, 23],
            6 => [24, 26],
            7 => [27, 30],
            8 => [31, 36],
            9 => [37, 39],
        ];

        foreach ($unitRanges as $unitIdx => [$startIdx, $endIdx]) {
            if (isset($units[$unitIdx])) {
                $targetUnitId = $units[$unitIdx]->id;
                for ($i = $startIdx; $i <= $endIdx; $i++) {
                    if (isset($questions[$i])) {
                        $questions[$i]->update(['competency_unit_id' => $targetUnitId]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive
    }
};
