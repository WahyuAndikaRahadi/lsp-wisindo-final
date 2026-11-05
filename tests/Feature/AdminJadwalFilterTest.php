<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminJadwalFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Scheme $schemeA;

    protected Scheme $schemeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->schemeA = Scheme::create([
            'name' => 'Skema Teknik Otomotif A',
            'code' => 'SKM-OTO-01',
            'scheme_type' => 'KLASTER',
            'is_active' => true,
        ]);

        $this->schemeB = Scheme::create([
            'name' => 'Skema Listrik Kendaraan B',
            'code' => 'SKM-LIS-02',
            'scheme_type' => 'KLASTER',
            'is_active' => true,
        ]);
    }

    private function createSchedule(array $attributes): Schedule
    {
        return Schedule::create(array_merge([
            'code' => 'SCH-'.uniqid(),
            'name' => 'Jadwal Default',
            'scheme_id' => $this->schemeA->id,
            'tuk_name' => 'TUK Utama',
            'date' => '2026-05-10',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['AK01'],
        ], $attributes));
    }

    public function test_admin_can_filter_schedules_by_search_name(): void
    {
        $this->createSchedule([
            'name' => 'Pelatihan Batch Satu',
            'scheme_id' => $this->schemeA->id,
            'date' => '2026-05-10',
        ]);

        $this->createSchedule([
            'name' => 'Uji Mandiri Khusus',
            'scheme_id' => $this->schemeB->id,
            'date' => '2026-05-12',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.jadwal.index', [
            'search' => 'Batch Satu',
        ]));

        $response->assertOk();
        $response->assertSee('Pelatihan Batch Satu');
        $response->assertDontSee('Uji Mandiri Khusus');
    }

    public function test_admin_can_filter_schedules_by_scheme(): void
    {
        $this->createSchedule([
            'name' => 'Jadwal Skema A',
            'scheme_id' => $this->schemeA->id,
            'date' => '2026-06-01',
        ]);

        $this->createSchedule([
            'name' => 'Jadwal Skema B',
            'scheme_id' => $this->schemeB->id,
            'date' => '2026-06-02',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.jadwal.index', [
            'scheme_id' => $this->schemeA->id,
        ]));

        $response->assertOk();
        $response->assertSee('Jadwal Skema A');
        $response->assertDontSee('Jadwal Skema B');
    }

    public function test_admin_can_filter_schedules_by_date_range(): void
    {
        $this->createSchedule([
            'name' => 'Jadwal Awal Mei',
            'scheme_id' => $this->schemeA->id,
            'date' => '2026-05-02',
        ]);

        $this->createSchedule([
            'name' => 'Jadwal Pertengahan Mei',
            'scheme_id' => $this->schemeA->id,
            'date' => '2026-05-15',
        ]);

        $this->createSchedule([
            'name' => 'Jadwal Akhir Juni',
            'scheme_id' => $this->schemeA->id,
            'date' => '2026-06-28',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.jadwal.index', [
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-20',
        ]));

        $response->assertOk();
        $response->assertSee('Jadwal Pertengahan Mei');
        $response->assertDontSee('Jadwal Awal Mei');
        $response->assertDontSee('Jadwal Akhir Juni');
    }
}
