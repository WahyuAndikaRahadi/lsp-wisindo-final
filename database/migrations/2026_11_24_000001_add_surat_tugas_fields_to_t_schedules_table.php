<?php

use App\Models\Schedule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('t_schedules', function (Blueprint $table) {
            $table->string('surat_tugas_number', 100)->nullable()->after('name');
            $table->date('end_date')->nullable()->after('date');
            $table->text('tuk_address')->nullable()->after('tuk_name');
            $table->integer('estimated_candidates')->default(0)->nullable()->after('duration_minutes');
            $table->date('surat_tugas_date')->nullable()->after('estimated_candidates');
            $table->string('letter_city', 100)->default('Bekasi')->nullable()->after('surat_tugas_date');
            $table->string('director_name', 100)->default('RIDWAN')->nullable()->after('letter_city');
        });

        // Safe production backfill for existing schedules
        if (Schema::hasTable('t_schedules')) {
            try {
                $schedules = Schedule::with(['tuk', 'assessments'])->get();
                $romanMonths = [
                    1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
                    7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
                ];

                foreach ($schedules as $schedule) {
                    $date = $schedule->date ? Carbon::parse($schedule->date) : now();
                    $monthNum = (int) $date->format('n');
                    $romanMonth = $romanMonths[$monthNum] ?? 'I';
                    $year = $date->format('Y');

                    $defaultNumber = sprintf('%03d/ST/PRO-INDO/INT/%s/%s', $schedule->id, $romanMonth, $year);
                    $tukAddress = $schedule->tuk?->address ?: '-';
                    $candidateCount = $schedule->relationLoaded('assessments') ? $schedule->assessments->count() : 0;

                    $schedule->updateQuietly([
                        'surat_tugas_number' => $schedule->surat_tugas_number ?: $defaultNumber,
                        'end_date' => $schedule->end_date ?: $schedule->date,
                        'tuk_address' => $schedule->tuk_address ?: $tukAddress,
                        'estimated_candidates' => $schedule->estimated_candidates ?: $candidateCount,
                        'surat_tugas_date' => $schedule->surat_tugas_date ?: $schedule->date,
                        'letter_city' => $schedule->letter_city ?: 'Bekasi',
                        'director_name' => $schedule->director_name ?: 'RIDWAN',
                    ]);
                }
            } catch (Throwable $e) {
                // Fail-safe for fresh test runs
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_schedules', function (Blueprint $table) {
            $table->dropColumn([
                'surat_tugas_number',
                'end_date',
                'tuk_address',
                'estimated_candidates',
                'surat_tugas_date',
                'letter_city',
                'director_name',
            ]);
        });
    }
};
