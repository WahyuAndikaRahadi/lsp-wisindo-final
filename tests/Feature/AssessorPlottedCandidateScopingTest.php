<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAk07Item;
use App\Models\AssessmentIaResponse;
use App\Models\CompetencyUnit;
use App\Models\Kuk;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\UnitElement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AssessorPlottedCandidateScopingTest extends TestCase
{
    use RefreshDatabase;

    private User $assessor1;

    private User $assessor2;

    private User $asesiA;

    private User $asesiB;

    private User $asesiC;

    private User $asesiD;

    private Scheme $scheme;

    private Schedule $schedule;

    private Assessment $asmA;

    private Assessment $asmB;

    private Assessment $asmC;

    private Assessment $asmD;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assessor1 = User::create([
            'full_name' => 'Budi Assessor Satu',
            'email' => 'asesor1@lsp.test',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'MET.000.111111',
        ]);

        $this->assessor2 = User::create([
            'full_name' => 'Citra Assessor Dua',
            'email' => 'asesor2@lsp.test',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'MET.000.222222',
        ]);

        $this->asesiA = User::create([
            'full_name' => 'Asesi Asep Sanjaya',
            'email' => 'asesi_a@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->asesiB = User::create([
            'full_name' => 'Asesi Bambang Pamungkas',
            'email' => 'asesi_b@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->asesiC = User::create([
            'full_name' => 'Asesi Cecep Supriatna',
            'email' => 'asesi_c@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->asesiD = User::create([
            'full_name' => 'Asesi Dedi Kusnandar',
            'email' => 'asesi_d@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-TEST-MULTI',
            'name' => 'Skema Pengujian Multi Asesor',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);

        $unit = CompetencyUnit::create([
            'scheme_id' => $this->scheme->id,
            'code' => 'OTO.TEST.001.01',
            'title' => 'Unit Uji Multi Asesor',
            'standard_type' => 'SKKNI',
        ]);

        $element = UnitElement::create([
            'competency_unit_id' => $unit->id,
            'title' => 'Elemen Uji 1',
        ]);

        Kuk::create([
            'unit_element_id' => $element->id,
            'code_number' => '1.1',
            'statement' => 'Kriteria Uji 1.1',
        ]);

        $this->schedule = Schedule::create([
            'code' => 'SCH-MULTI-01',
            'name' => 'Jadwal Multi Asesor Independent',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Utama',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $this->assessor1->id,
            'selected_instruments' => ['AK01', 'IA01', 'IA02'],
        ]);

        $this->schedule->assessors()->attach([$this->assessor1->id, $this->assessor2->id]);

        $this->asmA = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesiA->id,
            'assessor_id' => $this->assessor1->id,
            'apl_status' => 'SUBMITTED',
            'admin_status' => 'APPROVED',
            'last_active_step' => 'apl',
        ]);

        $this->asmB = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesiB->id,
            'assessor_id' => $this->assessor1->id,
            'apl_status' => 'SUBMITTED',
            'admin_status' => 'APPROVED',
            'last_active_step' => 'apl',
        ]);

        $this->asmC = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesiC->id,
            'assessor_id' => $this->assessor2->id,
            'apl_status' => 'SUBMITTED',
            'admin_status' => 'APPROVED',
            'last_active_step' => 'apl',
        ]);

        $this->asmD = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesiD->id,
            'assessor_id' => $this->assessor2->id,
            'apl_status' => 'SUBMITTED',
            'admin_status' => 'APPROVED',
            'last_active_step' => 'apl',
        ]);
    }

    public function test_assessor_only_sees_plotted_candidates_in_apl_review_list(): void
    {
        // Assessor 1 should only see Asesi A and Asesi B
        $response1 = $this->actingAs($this->assessor1)
            ->get(route('asesor.schedule.instrument', ['schedule' => $this->schedule->id, 'code' => 'apl']));

        $response1->assertOk();
        $response1->assertSee($this->asesiA->full_name);
        $response1->assertSee($this->asesiB->full_name);
        $response1->assertDontSee($this->asesiC->full_name);
        $response1->assertDontSee($this->asesiD->full_name);

        // Assessor 2 should only see Asesi C and Asesi D
        $response2 = $this->actingAs($this->assessor2)
            ->get(route('asesor.schedule.instrument', ['schedule' => $this->schedule->id, 'code' => 'apl']));

        $response2->assertOk();
        $response2->assertSee($this->asesiC->full_name);
        $response2->assertSee($this->asesiD->full_name);
        $response2->assertDontSee($this->asesiA->full_name);
        $response2->assertDontSee($this->asesiB->full_name);
    }

    public function test_assessor_1_is_not_blocked_when_approving_their_candidates_while_assessor_2_has_unreviewed_candidates(): void
    {
        // Before approval, both assessors are locked out of AK.01 because their candidates are SUBMITTED
        $this->assertFalse($this->schedule->isAk01Unlocked($this->assessor1));
        $this->assertFalse($this->schedule->isAk01Unlocked($this->assessor2));

        // Assessor 1 reviews and approves their candidates (A and B)
        $this->asmA->update([
            'apl_status' => 'APPROVED',
            'approved_at' => now(),
            'last_active_step' => 'ak01',
        ]);
        $this->asmB->update([
            'apl_status' => 'APPROVED',
            'approved_at' => now(),
            'last_active_step' => 'ak01',
        ]);

        // Assessor 2 candidates (C and D) remain pending in 'SUBMITTED'
        $this->schedule->refresh();

        // Assessor 1 MUST be unlocked! (Not blocked by C and D)
        $this->assertTrue($this->schedule->isAk01Unlocked($this->assessor1));

        // Assessor 2 MUST remain locked until they review C and D
        $this->assertFalse($this->schedule->isAk01Unlocked($this->assessor2));

        // Assessor 1 can access AK.01
        $response1 = $this->actingAs($this->assessor1)
            ->get(route('asesor.schedule.ak01', $this->schedule->id));
        $response1->assertOk();
        $response1->assertSee($this->asesiA->full_name);
        $response1->assertSee($this->asesiB->full_name);
        $response1->assertDontSee($this->asesiC->full_name);
        $response1->assertDontSee($this->asesiD->full_name);

        // Assessor 2 is redirected with warning because their candidates are not yet reviewed
        $response2 = $this->actingAs($this->assessor2)
            ->get(route('asesor.schedule.ak01', $this->schedule->id));
        $response2->assertRedirect(route('asesor.schedule.instrument', ['schedule' => $this->schedule->id, 'code' => 'apl']));
        $response2->assertSessionHas('warning');
    }

    public function test_assessor_1_can_proceed_through_ak07_and_ruang_uji_independently(): void
    {
        // Approve Assessor 1's candidates
        $this->asmA->update(['apl_status' => 'APPROVED', 'approved_at' => now()]);
        $this->asmB->update(['apl_status' => 'APPROVED', 'approved_at' => now()]);

        // Assessor 1 saves AK.01
        $sigData = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
        $postAk01 = $this->actingAs($this->assessor1)->post(route('asesor.schedule.ak01.store', $this->schedule->id), [
            'assessment_ids' => [$this->asmA->id, $this->asmB->id],
            'evidence' => ['Hasil Observasi Langsung', 'Hasil Pertanyaan Tertulis'],
            'tuk_type' => 'Sewaktu',
            'assessor_signature' => $sigData,
            'selected_instruments' => ['IA01', 'IA02'],
        ]);
        $postAk01->assertRedirect(route('asesor.schedule.ak07', $this->schedule->id));

        $this->schedule->refresh();

        // Assessor 1 has AK.07 unlocked, Assessor 2 does NOT
        $this->assertTrue($this->schedule->isAk07Unlocked($this->assessor1));
        $this->assertFalse($this->schedule->isAk07Unlocked($this->assessor2));

        // Assessor 1 saves AK.07
        $postAk07 = $this->actingAs($this->assessor1)->post(route('asesor.schedule.ak07.store', $this->schedule->id), [
            'assessment_ids' => [$this->asmA->id, $this->asmB->id],
        ]);
        $postAk07->assertRedirect(route('asesor.schedule.ruang-uji', $this->schedule->id));

        $this->schedule->refresh();

        // Assessor 1 has Ruang Uji unlocked, Assessor 2 does NOT
        $this->assertTrue($this->schedule->isRuangUjiUnlocked($this->assessor1));
        $this->assertFalse($this->schedule->isRuangUjiUnlocked($this->assessor2));

        // In Ruang Uji hub, Assessor 1 sees only their total candidate count (2 asesi)
        $hubResponse = $this->actingAs($this->assessor1)->get(route('asesor.schedule.ruang-uji', $this->schedule->id));
        $hubResponse->assertOk();
        $hubResponse->assertSee('2 <span class="text-xs font-semibold text-slate-500">Orang', false);

        // In Instrument candidate list (IA01), Assessor 1 only sees Asesi A and B
        $iaResponse = $this->actingAs($this->assessor1)->get(route('asesor.schedule.instrument', ['schedule' => $this->schedule->id, 'code' => 'IA01']));
        $iaResponse->assertOk();
        $iaResponse->assertSee($this->asesiA->full_name);
        $iaResponse->assertSee($this->asesiB->full_name);
        $iaResponse->assertDontSee($this->asesiC->full_name);
        $iaResponse->assertDontSee($this->asesiD->full_name);
    }

    public function test_assessor_1_can_unlock_ak02_and_reports_independently(): void
    {
        // Approve and progress Assessor 1 candidates
        $this->asmA->update([
            'apl_status' => 'APPROVED',
            'approved_at' => now(),
            'ak01_agreed_at' => now(),
            'assessor_signature' => 'sig_a',
            'ak01_data' => ['selected_instruments' => ['IA01']],
        ]);
        $this->asmB->update([
            'apl_status' => 'APPROVED',
            'approved_at' => now(),
            'ak01_agreed_at' => now(),
            'assessor_signature' => 'sig_b',
            'ak01_data' => ['selected_instruments' => ['IA01']],
        ]);

        AssessmentAk07Item::create([
            'assessment_id' => $this->asmA->id,
            'assessor_id' => $this->assessor1->id,
            'adjustment_category' => 'Standar',
        ]);
        AssessmentAk07Item::create([
            'assessment_id' => $this->asmB->id,
            'assessor_id' => $this->assessor1->id,
            'adjustment_category' => 'Standar',
        ]);

        // Fake completion of assigned instruments for A and B
        // Note: For IA01, hasCompletedAllAssignedInstruments checks iaResponses
        $unit = $this->scheme->competencyUnits->first();
        AssessmentIaResponse::create([
            'assessment_id' => $this->asmA->id,
            'instrument_code' => 'IA01',
            'competency_unit_id' => $unit->id,
            'assessor_id' => $this->assessor1->id,
            'scores' => ['1.1' => 'K'],
            'completed_at' => now(),
        ]);
        AssessmentIaResponse::create([
            'assessment_id' => $this->asmB->id,
            'instrument_code' => 'IA01',
            'competency_unit_id' => $unit->id,
            'assessor_id' => $this->assessor1->id,
            'scores' => ['1.1' => 'K'],
            'completed_at' => now(),
        ]);

        $this->schedule->refresh();

        // AK.02 must be unlocked for Assessor 1
        $this->assertTrue($this->schedule->isAk02Unlocked($this->assessor1));
        // Still locked for Assessor 2
        $this->assertFalse($this->schedule->isAk02Unlocked($this->assessor2));

        // Assessor 1 visits AK.02
        $ak02Response = $this->actingAs($this->assessor1)->get(route('asesor.schedule.ak02', $this->schedule->id));
        $ak02Response->assertOk();
        $ak02Response->assertSee($this->asesiA->full_name);
        $ak02Response->assertSee($this->asesiB->full_name);
        $ak02Response->assertDontSee($this->asesiC->full_name);
        $ak02Response->assertDontSee($this->asesiD->full_name);

        // Assessor 1 submits AK.02 recommendations for their candidates
        $postAk02 = $this->actingAs($this->assessor1)->post(route('asesor.schedule.ak02.bulk', $this->schedule->id), [
            'results' => [
                $this->asmA->id => [
                    'final_result' => 'KOMPETEN',
                    'follow_up_action' => 'Penerbitan Sertifikat',
                    'final_notes' => 'Kompeten pada seluruh unit.',
                ],
                $this->asmB->id => [
                    'final_result' => 'KOMPETEN',
                    'follow_up_action' => 'Penerbitan Sertifikat',
                    'final_notes' => 'Kompeten pada seluruh unit.',
                ],
            ],
        ]);
        $postAk02->assertRedirect(route('asesor.schedule.ak02', $this->schedule->id));

        $this->schedule->refresh();

        // Reports (Tutup Jadwal) must be unlocked for Assessor 1
        $this->assertTrue($this->schedule->isReportsUnlocked($this->assessor1));
        $this->assertFalse($this->schedule->isReportsUnlocked($this->assessor2));
    }
}
