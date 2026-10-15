<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Schedule;

class MukExportService
{
    /**
     * Data bundle for schedule-level exports (BA, Attendance, AK05, AK06, Cover, Daftar Periksa)
     *
     * @return array<string, mixed>
     */
    public function getExportData(Schedule $schedule): array
    {
        $schedule->load([
            'scheme.competencyUnits.elements.kuks',
            'assessors.profile',
            'assessments.asesi.profile',
            'assessments.ak02Items.competencyUnit',
            'assessments.ak03Items',
            'assessments.appeals',
        ]);

        $totalAsesi = $schedule->assessments->count();
        $totalKompeten = $schedule->assessments->filter(function ($a) {
            return in_array($a->final_result, ['K', 'KOMPETEN']) || ($a->final_result === null && $a->status === 'COMPLETED');
        })->count();
        $totalBelumKompeten = $schedule->assessments->filter(function ($a) {
            return in_array($a->final_result, ['BK', 'BELUM_KOMPETEN']);
        })->count();

        return [
            'schedule' => $schedule,
            'scheme' => $schedule->scheme,
            'assessors' => $schedule->assessors,
            'leadAssessor' => $schedule->assessors->first(),
            'assessments' => $schedule->assessments,
            'total_asesi' => $totalAsesi,
            'total_asesi_terbilang' => self::terbilang($totalAsesi),
            'total_kompeten' => $totalKompeten,
            'total_kompeten_terbilang' => self::terbilang($totalKompeten),
            'total_belum_kompeten' => $totalBelumKompeten,
            'total_belum_kompeten_terbilang' => self::terbilang($totalBelumKompeten),
        ];
    }

    /**
     * Data bundle for individual asesi MUK exports (APL01, APL02, AK01, AK02, AK03, AK04, AK07, Pernyataan)
     *
     * @return array<string, mixed>
     */
    public function getAsesiExportData(Assessment $assessment): array
    {
        $assessment->load([
            'schedule.scheme.competencyUnits.elements.kuks',
            'schedule.assessors.profile',
            'asesi.profile',
            'assessor.profile',
            'proofs',
            'apl02Items.kuk.unitElement.competencyUnit',
            'iaResponses',
            'ak07Items',
            'ak02Items.competencyUnit',
            'ak03Items',
            'appeals',
        ]);

        return [
            'assessment' => $assessment,
            'schedule' => $assessment->schedule,
            'scheme' => $assessment->schedule?->scheme ?? $assessment->scheme,
            'asesi' => $assessment->asesi,
            'profile' => $assessment->asesi->profile,
            'assessor' => $assessment->assessor ?? $assessment->schedule?->assessors->first(),
            'ak02Items' => $assessment->ak02Items,
            'ak03Items' => $assessment->ak03Items,
            'appeals' => $assessment->appeals,
        ];
    }

    /**
     * Convert integer to Indonesian word representation (e.g. 10 -> sepuluh).
     */
    public static function terbilang(int $number): string
    {
        if ($number === 0) {
            return 'nol';
        }

        $words = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        if ($number < 12) {
            return $words[$number];
        }
        if ($number < 20) {
            return self::terbilang($number - 10).' belas';
        }
        if ($number < 100) {
            $remainder = $number % 10;

            return self::terbilang((int) ($number / 10)).' puluh'.($remainder ? ' '.self::terbilang($remainder) : '');
        }
        if ($number < 200) {
            $remainder = $number - 100;

            return 'seratus'.($remainder ? ' '.self::terbilang($remainder) : '');
        }
        if ($number < 1000) {
            $remainder = $number % 100;

            return self::terbilang((int) ($number / 100)).' ratus'.($remainder ? ' '.self::terbilang($remainder) : '');
        }

        return (string) $number;
    }
}
