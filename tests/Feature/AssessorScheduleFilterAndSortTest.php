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

    public function test_assessor_can_view_all_schedules_with_quick_chips(): void
    {
        $response = $this->actingAs($this->assessor)
            ->get(route('asesor.jadwal.index'));

        $response->assertOk();
        $response->assertSee('Semua Jadwal (3)');
        $response->assertSee('Jadwal Aktif (2)');
        $response->assertSee('Jadwal Selesai (1)');
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

    public function test_filter_by_date_range(): void
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

    public function test_sorting_by_date_descending_and_ascending(): void
    {
        // Date Descending (Newest first): Semeru (June) -> Bromo (May) -> Anjasmoro (April)
        $responseDesc = $this->actingAs($this->assessor)
            ->get(route('asesor.jadwal.index', ['sort' => 'date_desc']));

        $responseDesc->assertOk();
        $contentDesc = $responseDesc->getContent();
        $posSemeru = strpos($contentDesc, 'Semeru Schedule');
        $posBromo = strpos($contentDesc, 'Bromo Schedule');
        $posAnjasmoro = strpos($contentDesc, 'Anjasmoro Schedule');

        $this->assertTrue($posSemeru < $posBromo);
        $this->assertTrue($posBromo < $posAnjasmoro);

        // Date Ascending (Oldest first): Anjasmoro (April) -> Bromo (May) -> Semeru (June)
        $responseAsc = $this->actingAs($this->assessor)
            ->get(route('asesor.jadwal.index', ['sort' => 'date_asc']));

        $responseAsc->assertOk();
        $contentAsc = $responseAsc->getContent();
        $posSemeruAsc = strpos($contentAsc, 'Semeru Schedule');
        $posBromoAsc = strpos($contentAsc, 'Bromo Schedule');
        $posAnjasmoroAsc = strpos($contentAsc, 'Anjasmoro Schedule');

        $this->assertTrue($posAnjasmoroAsc < $posBromoAsc);
        $this->assertTrue($posBromoAsc < $posSemeruAsc);
    }

    public function test_sorting_by_name_alphabetical(): void
    {
        // Name Asc: Anjasmoro -> Bromo -> Semeru
        $responseAsc = $this->actingAs($this->assessor)
            ->get(route('asesor.jadwal.index', ['sort' => 'name_asc']));

        $responseAsc->assertOk();
        $content = $responseAsc->getContent();
        $posAnjasmoro = strpos($content, 'Anjasmoro Schedule');
        $posBromo = strpos($content, 'Bromo Schedule');
        $posSemeru = strpos($content, 'Semeru Schedule');

        $this->assertTrue($posAnjasmoro < $posBromo);
        $this->assertTrue($posBromo < $posSemeru);
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
