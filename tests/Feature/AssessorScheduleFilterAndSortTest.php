<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessorScheduleFilterAndSortTest extends TestCase
{
    use RefreshDatabase;

    private User $assessor;

    private Scheme $scheme;

    private Schedule $scheduleActiveOld;

    private Schedule $scheduleActiveNew;

    private Schedule $scheduleCompleted;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scheme = Scheme::create([
            'code' => 'SKM-FILTER-01',
            'name' => 'Skema Filter Uji',
            'scheme_type' => 'KLASTER',
            'is_active' => true,
        ]);

        $this->assessor = User::factory()->withSignature()->create([
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
        ]);

        // Schedule 1: Active, Date: 2026-05-10, Name: Bromo Schedule
        $this->scheduleActiveOld = Schedule::create([
            'code' => 'SCH-ACTIVE-OLD',
            'name' => 'Bromo Schedule',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Bromo',
            'date' => '2026-05-10',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status' => 'ACTIVE',
            'is_closed' => false,
            'created_by' => $this->assessor->id,
            'selected_instruments' => ['AK01', 'IA01'],
        ]);

        // Schedule 2: Active, Date: 2026-06-20, Name: Semeru Schedule
        $this->scheduleActiveNew = Schedule::create([
            'code' => 'SCH-ACTIVE-NEW',
            'name' => 'Semeru Schedule',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Semeru',
            'date' => '2026-06-20',
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
            'status' => 'ACTIVE',
            'is_closed' => false,
            'created_by' => $this->assessor->id,
            'selected_instruments' => ['AK01', 'IA01'],
        ]);

        // Schedule 3: Completed, Date: 2026-04-15, Name: Anjasmoro Schedule
        $this->scheduleCompleted = Schedule::create([
            'code' => 'SCH-COMPLETED',
            'name' => 'Anjasmoro Schedule',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Anjasmoro',
            'date' => '2026-04-15',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'FINISHED',
            'is_closed' => true,
            'created_by' => $this->assessor->id,
            'selected_instruments' => ['AK01', 'IA01'],
        ]);

        $this->assessor->assignedSchedules()->attach([
            $this->scheduleActiveOld->id,
            $this->scheduleActiveNew->id,
            $this->scheduleCompleted->id,
        ]);
    }

    public function test_assessor_can_view_all_schedules_without_quick_chips(): void
    {
        $response = $this->actingAs($this->assessor)
            ->get(route('asesor.jadwal.index'));

        $response->assertOk();
        $response->assertDontSee('Semua Jadwal (');
        $response->assertDontSee('Jadwal Aktif (');
        $response->assertDontSee('Jadwal Selesai (');
        $response->assertSee('SCH-ACTIVE-OLD');
        $response->assertSee('SCH-ACTIVE-NEW');
        $response->assertSee('SCH-COMPLETED');
    }

    public function test_filter_by_status_active(): void
    {
        $response = $this->actingAs($this->assessor)
            ->get(route('asesor.jadwal.index', ['status' => 'active']));

        $response->assertOk();
        $response->assertSee('SCH-ACTIVE-OLD');
        $response->assertSee('SCH-ACTIVE-NEW');
        $response->assertDontSee('SCH-COMPLETED');
    }

    public function test_filter_by_status_completed(): void
    {
        $response = $this->actingAs($this->assessor)
            ->get(route('asesor.jadwal.index', ['status' => 'completed']));

        $response->assertOk();
        $response->assertSee('SCH-COMPLETED');
        $response->assertDontSee('SCH-ACTIVE-OLD');
        $response->assertDontSee('SCH-ACTIVE-NEW');
    }

    public function test_filter_by_single_date(): void
    {
        $response = $this->actingAs($this->assessor)
            ->get(route('asesor.jadwal.index', ['date' => '2026-05-10']));

        $response->assertOk();
        $response->assertSee('SCH-ACTIVE-OLD');
        $response->assertDontSee('SCH-ACTIVE-NEW');
        $response->assertDontSee('SCH-COMPLETED');
    }

    public function test_filter_by_date_range_fallback(): void
    {
        // Only schedule in May 2026
        $response = $this->actingAs($this->assessor)
            ->get(route('asesor.jadwal.index', [
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-31',
            ]));

        $response->assertOk();
        $response->assertSee('SCH-ACTIVE-OLD');
        $response->assertDontSee('SCH-ACTIVE-NEW');
        $response->assertDontSee('SCH-COMPLETED');
    }

    public function test_default_sorting_always_orders_latest_date_first(): void
    {
        // Default (without sort param): Semeru (June) -> Bromo (May) -> Anjasmoro (April)
        $response = $this->actingAs($this->assessor)
            ->get(route('asesor.jadwal.index'));

        $response->assertOk();
        $content = $response->getContent();
        $posSemeru = strpos($content, 'Semeru Schedule');
        $posBromo = strpos($content, 'Bromo Schedule');
        $posAnjasmoro = strpos($content, 'Anjasmoro Schedule');

        $this->assertTrue($posSemeru < $posBromo);
        $this->assertTrue($posBromo < $posAnjasmoro);
    }

    public function test_ajax_request_returns_table_partial_only(): void
    {
        $response = $this->actingAs($this->assessor)
            ->getJson(route('asesor.jadwal.index', ['status' => 'active']), [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk();
        $response->assertDontSee('<!DOCTYPE html>');
        $response->assertSee('SCH-ACTIVE-OLD');
        $response->assertSee('SCH-ACTIVE-NEW');
        $response->assertDontSee('SCH-COMPLETED');
    }
}
