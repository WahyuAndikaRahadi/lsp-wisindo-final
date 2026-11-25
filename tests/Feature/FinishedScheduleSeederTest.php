<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAppeal;
use App\Models\Certificate;
use App\Models\Schedule;
use App\Models\User;
use Database\Seeders\FinishedJadwalWith20AssesseesSeeder;
use Database\Seeders\FinishedScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinishedScheduleSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed master data
        $this->seed();
    }

    public function test_finished_schedule_seeder_creates_complete_finished_schedule(): void
    {
        $this->seed(FinishedScheduleSeeder::class);

        // 1. Verify Schedule
        $schedule = Schedule::where('code', 'SCH-FIN-2026')->first();
        $this->assertNotNull($schedule);
        $this->assertSame('FINISHED', $schedule->status);
        $this->assertTrue((bool) $schedule->is_closed);
        $this->assertNotNull($schedule->closed_at);
        $this->assertNotNull($schedule->surat_tugas_number);
        $this->assertNotNull($schedule->surat_tugas_date);
        $this->assertNotNull($schedule->tuk_address);
        $this->assertSame(20, $schedule->estimated_candidates);
        $this->assertSame('01', $schedule->kode_sumber_anggaran);
        $this->assertSame('01', $schedule->kode_kementerian);

        // Verify MAPA-01, AK-04, AK-05, and AK-06
        $this->assertNotNull($schedule->mapa01_data);
        $this->assertSame('Sertifikasi', $schedule->mapa01_data['tujuan_asesmen'] ?? null);
        $this->assertNotNull($schedule->ak04_briefed_at);

        $this->assertNotNull($schedule->ak05_report);
        $this->assertArrayHasKey('by_assessor', $schedule->ak05_report);
        $this->assertFalse($schedule->ak05_report['has_penolakan']);

        $this->assertNotNull($schedule->ak06_review);
        $this->assertArrayHasKey('by_assessor', $schedule->ak06_review);
        $this->assertSame('YA', $schedule->ak06_review['valid'] ?? null);

        // 2. Verify Assessors
        $this->assertGreaterThanOrEqual(2, $schedule->assessors()->count());

        // 3. Verify 20 Assessments
        $assessments = $schedule->assessments()->with(['asesi.profile', 'iaResponses', 'ak02Items', 'ak03Item', 'apl02Items', 'proofs', 'certificate', 'appeals'])->get();
        $this->assertCount(20, $assessments);

        $kompetenCount = 0;
        $belumKompetenCount = 0;

        foreach ($assessments as $assessment) {
            $this->assertSame('COMPLETED', $assessment->status);
            $this->assertSame('APPROVED', $assessment->apl_status);
            $this->assertSame('APPROVED', $assessment->admin_status);
            $this->assertSame('Sertifikasi', $assessment->tujuan_asesmen);
            $this->assertNotNull($assessment->asesi_signature);
            $this->assertNotNull($assessment->assessor_signature);
            $this->assertNotNull($assessment->final_result_at);

            // User Profile BNSP codes
            $profile = $assessment->asesi->profile;
            $this->assertNotNull($profile);
            $this->assertSame('32', $profile->kode_provinsi);
            $this->assertSame('JAWA BARAT', $profile->nama_provinsi);
            $this->assertSame('3216', $profile->kode_kota);
            $this->assertSame('KAB. BEKASI', $profile->nama_kota);
            $this->assertSame('06', $profile->kode_pendidikan);
            $this->assertSame('SMK', $profile->pendidikan_terakhir);
            $this->assertSame('10', $profile->kode_pekerjaan);

            // APL-01 Proofs
            $this->assertGreaterThanOrEqual(3, $assessment->proofs->count());

            // APL-02 Self Assessment
            $this->assertGreaterThan(0, $assessment->apl02Items->count());

            // AK-02 Items
            $this->assertGreaterThan(0, $assessment->ak02Items->count());

            // AK-03 Feedback
            $this->assertNotNull($assessment->ak03Item);

            // IA Responses (IA01, IA01B, IA02, IA03, IA05)
            $iaCodes = $assessment->iaResponses->pluck('instrument_code')->all();
            $this->assertContains('IA01', $iaCodes);
            $this->assertContains('IA01B', $iaCodes);
            $this->assertContains('IA02', $iaCodes);
            $this->assertContains('IA03', $iaCodes);
            $this->assertContains('IA05', $iaCodes);

            if ($assessment->final_result === 'KOMPETEN') {
                $kompetenCount++;
                $this->assertNotNull($assessment->certificate);
                $this->assertNotNull($assessment->certificate->blanko_number);
                $this->assertNotNull($assessment->certificate->certificate_number);
            } elseif ($assessment->final_result === 'BELUM_KOMPETEN') {
                $belumKompetenCount++;
            }
        }

        $this->assertSame(18, $kompetenCount);
        $this->assertSame(2, $belumKompetenCount);

        // Verify Certificate count
        $this->assertSame(18, Certificate::where('schedule_id', $schedule->id)->count());

        // Verify Appeal for candidate #20
        $this->assertSame(1, AssessmentAppeal::whereHas('assessment', fn ($q) => $q->where('schedule_id', $schedule->id))->count());
    }

    public function test_finished_schedule_seeder_is_idempotent(): void
    {
        // Run once
        $this->seed(FinishedScheduleSeeder::class);
        $firstScheduleCount = Schedule::where('code', 'SCH-FIN-2026')->count();
        $firstAssessmentCount = Assessment::count();

        // Run second time
        $this->seed(FinishedScheduleSeeder::class);
        $this->assertSame($firstScheduleCount, Schedule::where('code', 'SCH-FIN-2026')->count());
        $this->assertSame($firstAssessmentCount, Assessment::count());
    }

    public function test_finished_jadwal_seeder_delegates_to_finished_schedule_seeder(): void
    {
        $this->seed(FinishedJadwalWith20AssesseesSeeder::class);

        $schedule = Schedule::where('code', 'SCH-FIN-2026')->first();
        $this->assertNotNull($schedule);
        $this->assertSame('FINISHED', $schedule->status);
        $this->assertSame(20, $schedule->assessments()->count());
    }
}
