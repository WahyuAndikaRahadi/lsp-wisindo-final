<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\CompetencyUnit;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Services\AutoGradeService;
use App\Services\SchemeInstrumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemeInstrumentsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_all_seven_schemes_are_seeded_with_full_muk_data(): void
    {
        $this->assertEquals(5, Scheme::count());
        $this->assertGreaterThanOrEqual(100, CompetencyUnit::count());
        $this->assertGreaterThanOrEqual(50, QuestionBank::count());

        $expectedCounts = [
            'SKM-CDP-01' => ['min_units' => 40, 'q_count' => 10],
            'SKM-CC-02' => ['min_units' => 29, 'q_count' => 20],
            'SKM-FO-03' => ['min_units' => 31, 'q_count' => 10],
            'SKM-HK-04' => ['min_units' => 24, 'q_count' => 14],
            'SKM-FB-05' => ['min_units' => 33, 'q_count' => 10],
        ];

        foreach ($expectedCounts as $code => $exp) {
            $scheme = Scheme::where('code', $code)->first();
            $this->assertNotNull($scheme, "Scheme {$code} must exist");
            $this->assertGreaterThanOrEqual($exp['min_units'], $scheme->competencyUnits()->count());
            $this->assertEquals($exp['q_count'], $scheme->questionBanks()->count(), "Scheme {$code} question count must match");
        }
    }

    public function test_scheme_instrument_service_returns_data_for_each_scheme(): void
    {
        $service = app(SchemeInstrumentService::class);
        $schemes = Scheme::all();

        foreach ($schemes as $scheme) {
            $tpd = $service->getTpdData($scheme);
            $this->assertNotEmpty($tpd['scenario']);
            $this->assertNotEmpty($tpd['time_limit']);
            $this->assertNotEmpty($tpd['tasks']);

            $pmo = $service->getPmoQuestions($scheme);
            $this->assertNotEmpty($pmo);

            $rs = $service->getReportSheetItems($scheme);
            $this->assertNotEmpty($rs);
        }
    }

    public function test_assessor_can_view_all_instruments_for_assessment(): void
    {
        $admin = User::where('role', 'ADMIN_LSP')->first();
        $asesor = User::where('role', 'ASESOR')->first();
        $asesi = User::where('role', 'ASESI')->first();
        $scheme = Scheme::where('code', 'SKM-CDP-01')->first() ?? Scheme::first();

        $schedule = Schedule::create([
            'code' => 'SCH-TEST-01',
            'name' => 'Jadwal Uji Test',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA02', 'IA03', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);
        $schedule->assessors()->attach($asesor->id);

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $asesor->id,
            'status' => 'IN_PROGRESS',
            'apl_status' => 'APPROVED',
        ]);

        $this->actingAs($asesor);

        // IA01
        $resIa01 = $this->get(route('asesor.ruang-uji.ia01', $assessment));
        $resIa01->assertOk();
        $resIa01->assertSee('Semua K');
        $resIa01->assertSee('Semua BK');
        $resIa01->assertSee('setAllKuk');
        $resIa01->assertSee('setUnitKuk');

        $resIa01Url = $this->get(route('asesor.assessment.instrument', ['assessment' => $assessment->id, 'code' => 'ia01']));
        $resIa01Url->assertOk();
        $resIa01Url->assertSee('Semua K');
        $resIa01Url->assertSee('Semua BK');

        // IA02
        $resIa02 = $this->get(route('asesor.ruang-uji.ia02', $assessment));
        $resIa02->assertOk();

        // IA03
        $resIa03 = $this->get(route('asesor.ruang-uji.ia03', $assessment));
        $resIa03->assertOk();
    }

    public function test_autograde_service_calculates_cbt_score(): void
    {
        $admin = User::where('role', 'ADMIN_LSP')->first();
        $asesi = User::where('role', 'ASESI')->first();
        $scheme = Scheme::where('code', 'SKM-CC-02')->first() ?? Scheme::first();

        $schedule = Schedule::create([
            'code' => 'SCH-TEST-02',
            'name' => 'Jadwal Uji Test 2',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA02', 'IA03', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi->id,
            'status' => 'IN_PROGRESS',
            'apl_status' => 'APPROVED',
        ]);

        $questions = QuestionBank::where('scheme_id', $scheme->id)->get();
        $answers = [];
        foreach ($questions as $q) {
            $answers[$q->id] = $q->correct_answer;
        }

        $grader = app(AutoGradeService::class);
        $result = $grader->grade($assessment, $answers);

        $this->assertEquals(100.0, $result->score);
    }
}
