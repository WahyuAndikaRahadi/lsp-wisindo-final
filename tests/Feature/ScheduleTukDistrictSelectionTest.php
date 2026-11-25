<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScheduleTukDistrictSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $assessor;

    protected Scheme $scheme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'full_name' => 'Super Admin Test',
            'email' => 'superadmin.district@test.com',
            'password' => Hash::make('@Kompeten86'),
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->assessor = User::create([
            'full_name' => 'Asesor District Test',
            'email' => 'asesor.district@test.com',
            'password' => Hash::make('@Kompeten86'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SCH-TEST-DIST',
            'name' => 'Skema Test District',
            'scheme_type' => 'KLASTER',
            'version' => '1.0',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_create_schedule_page_with_tuk_district_fields(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.jadwal.create'));

        $response->assertOk();
        $response->assertSee('Provinsi TUK');
        $response->assertSee('Kabupaten / Kota TUK (District)');
        $response->assertSee('name="kode_provinsi"', false);
        $response->assertSee('name="kode_kota"', false);
        $response->assertSee('name="nama_provinsi"', false);
        $response->assertSee('name="nama_kota"', false);
    }

    public function test_admin_can_create_schedule_with_tuk_district(): void
    {
        $payload = [
            'name' => 'Uji Kompetensi Batch Otomotif',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'SMK Negeri 1 Cikarang Barat',
            'tuk_address' => 'Jl. Industri Jababeka No. 12',
            'kode_provinsi' => '32',
            'nama_provinsi' => 'JAWA BARAT',
            'kode_kota' => '3216',
            'nama_kota' => 'KAB. BEKASI',
            'date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'duration_minutes' => 480,
            'estimated_candidates' => 20,
            'selected_instruments' => ['AK01', 'IA01', 'IA02'],
            'assessor_ids' => [$this->assessor->id],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.jadwal.store'), $payload);

        $response->assertRedirect(route('admin.jadwal.index'));

        $this->assertDatabaseHas('t_schedules', [
            'name' => 'Uji Kompetensi Batch Otomotif',
            'tuk_name' => 'SMK Negeri 1 Cikarang Barat',
            'kode_provinsi' => '32',
            'nama_provinsi' => 'JAWA BARAT',
            'kode_kota' => '3216',
            'nama_kota' => 'KAB. BEKASI',
            'tuk_city' => 'KAB. BEKASI',
        ]);
    }

    public function test_admin_can_view_edit_schedule_page_with_preselected_district(): void
    {
        $schedule = Schedule::create([
            'code' => 'SCH-TEST-DIST-01',
            'name' => 'Jadwal Uji Edit District',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'SMK Negeri 1 Subang',
            'tuk_address' => 'Jl. Arief Rahman Hakim No. 35',
            'kode_provinsi' => '32',
            'nama_provinsi' => 'JAWA BARAT',
            'kode_kota' => '3213',
            'nama_kota' => 'KAB. SUBANG',
            'tuk_city' => 'KAB. SUBANG',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'selected_instruments' => ['AK01', 'IA01'],
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.jadwal.edit', $schedule));

        $response->assertOk();
        $response->assertSee("selectedProvince: '32'", false);
        $response->assertSee("selectedCity: '3213'", false);
        $response->assertSee('KAB. SUBANG');
    }

    public function test_admin_can_update_schedule_tuk_district(): void
    {
        $schedule = Schedule::create([
            'code' => 'SCH-TEST-DIST-02',
            'name' => 'Jadwal Uji Update District',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'SMK Negeri 1 Subang',
            'tuk_address' => 'Jl. Arief Rahman Hakim No. 35',
            'kode_provinsi' => '32',
            'nama_provinsi' => 'JAWA BARAT',
            'kode_kota' => '3213',
            'nama_kota' => 'KAB. SUBANG',
            'tuk_city' => 'KAB. SUBANG',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'selected_instruments' => ['AK01', 'IA01'],
            'created_by' => $this->admin->id,
        ]);

        $updatePayload = [
            'name' => 'Jadwal Uji Update District - Updated',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'SMK Negeri 1 Cikarang',
            'tuk_address' => 'Jl. Industri No. 5',
            'kode_provinsi' => '32',
            'nama_provinsi' => 'JAWA BARAT',
            'kode_kota' => '3216',
            'nama_kota' => 'KAB. BEKASI',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'selected_instruments' => ['AK01', 'IA01'],
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.jadwal.update', $schedule), $updatePayload);

        $response->assertRedirect(route('admin.jadwal.show', $schedule->id));

        $this->assertDatabaseHas('t_schedules', [
            'id' => $schedule->id,
            'kode_provinsi' => '32',
            'nama_provinsi' => 'JAWA BARAT',
            'kode_kota' => '3216',
            'nama_kota' => 'KAB. BEKASI',
            'tuk_city' => 'KAB. BEKASI',
        ]);
    }

    public function test_admin_show_page_displays_effective_tuk_district(): void
    {
        $schedule = Schedule::create([
            'code' => 'SCH-TEST-DIST-03',
            'name' => 'Jadwal Uji Display District',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'SMK Negeri 2 Cikarang',
            'tuk_address' => 'Jl. Lemahabang No. 8',
            'kode_provinsi' => '32',
            'nama_provinsi' => 'JAWA BARAT',
            'kode_kota' => '3216',
            'nama_kota' => 'KAB. BEKASI',
            'tuk_city' => 'KAB. BEKASI',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'selected_instruments' => ['AK01', 'IA01'],
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.jadwal.show', $schedule));

        $response->assertOk();
        $response->assertSee('KAB. BEKASI');
        $response->assertSee('JAWA BARAT');
    }
}
