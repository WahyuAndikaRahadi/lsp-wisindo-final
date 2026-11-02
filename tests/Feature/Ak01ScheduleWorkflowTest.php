<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Ak01ScheduleWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function createFixture(): array
    {
        $asesor = User::factory()->create(['role' => 'ASESOR']);
        $asesi = User::factory()->create(['role' => 'ASESI']);
        $scheme = Scheme::first() ?? Scheme::create([
            'code' => 'SKM-OTO-'.uniqid(),
            'name' => 'Skema Uji Otomotif',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);

        $schedule = Schedule::create([
            'code' => 'SCH-'.uniqid(),
            'name' => 'Jadwal Uji Otomotif',
            'scheme_id' => $scheme->id,
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
            'tuk_name' => 'TUK Otomotif Mandiri',
            'status' => 'ACTIVE',
            'created_by' => $asesor->id,
            'selected_instruments' => ['AK01'],
        ]);
        $schedule->assessors()->attach($asesor->id);

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $asesor->id,
            'apl_status' => 'APPROVED',
        ]);

        return compact('asesor', 'asesi', 'scheme', 'schedule', 'assessment');
    }

    public function test_assessor_can_view_single_page_ak01_form(): void
    {
        $f = $this->createFixture();

        $response = $this->actingAs($f['asesor'])->get(route('asesor.schedule.ak01', $f['schedule']));

        $response->assertOk();
        $response->assertSee('FR.AK.01. PERSETUJUAN ASESMEN DAN KERAHASIAAN');
        $response->assertSee('Hasil Observasi Langsung');
        $response->assertSee('Hasil Pertanyaan Tertulis');
        $response->assertSee('Asesi yang Diberikan Penyesuaian (Pilih Asesi):');
    }

    public function test_ak01_instrument_route_redirects_to_single_page_ak01(): void
    {
        $f = $this->createFixture();

        $response = $this->actingAs($f['asesor'])->get(
            route('asesor.schedule.instrument', ['schedule' => $f['schedule']->id, 'code' => 'ak01'])
        );

        $response->assertRedirect(route('asesor.schedule.ak01', $f['schedule']->id));
    }

    public function test_assessor_can_submit_ak01_and_redirects_to_ak07(): void
    {
        $f = $this->createFixture();

        $payload = [
            'assessment_ids' => [$f['assessment']->id],
            'evidence' => ['Hasil Observasi Langsung', 'Hasil Pertanyaan Tertulis', 'Hasil Verifikasi Portofolio'],
            'tuk_type' => 'Tempat Kerja',
            'other_evidence' => 'Catatan bukti khusus',
        ];

        $response = $this->actingAs($f['asesor'])->post(
            route('asesor.schedule.ak01.store', $f['schedule']),
            $payload
        );

        $response->assertRedirect(route('asesor.schedule.ak07', $f['schedule']->id));

        $freshSchedule = $f['schedule']->fresh();
        $this->assertNotNull($freshSchedule->ak01_data);
        $this->assertEquals(['Hasil Observasi Langsung', 'Hasil Pertanyaan Tertulis', 'Hasil Verifikasi Portofolio'], $freshSchedule->ak01_data['evidence']);
        $this->assertEquals('Tempat Kerja', $freshSchedule->ak01_data['tuk_type']);

        $freshAssessment = $f['assessment']->fresh();
        $this->assertNotNull($freshAssessment->ak01_data);
    }

    public function test_full_chain_from_apl_to_ak01_to_ak07_to_ruang_uji(): void
    {
        $f = $this->createFixture();

        // 1. APL verification page has link to AK01
        $aplListResponse = $this->actingAs($f['asesor'])->get(
            route('asesor.schedule.instrument', ['schedule' => $f['schedule']->id, 'code' => 'apl'])
        );
        $aplListResponse->assertOk();
        $aplListResponse->assertSee(route('asesor.schedule.ak01', $f['schedule']->id));

        // 2. AK01 page renders and can be submitted or skipped to AK07
        $ak01Response = $this->actingAs($f['asesor'])->get(route('asesor.schedule.ak01', $f['schedule']));
        $ak01Response->assertOk();
        $ak01Response->assertSee(route('asesor.schedule.ak07', $f['schedule']->id));

        // 3. Submit AK01 -> redirects to AK07
        $ak01Submit = $this->actingAs($f['asesor'])->post(route('asesor.schedule.ak01.store', $f['schedule']), []);
        $ak01Submit->assertRedirect(route('asesor.schedule.ak07', $f['schedule']->id));

        // 4. Submit AK07 -> redirects to Ruang Uji
        $ak07Submit = $this->actingAs($f['asesor'])->post(route('asesor.ak07.store', $f['schedule']), []);
        $ak07Submit->assertRedirect(route('asesor.schedule.ruang-uji', $f['schedule']->id));
    }
}
