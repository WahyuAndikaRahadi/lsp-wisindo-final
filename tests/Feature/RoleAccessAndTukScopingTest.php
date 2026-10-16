<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAppeal;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\Tuk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleAccessAndTukScopingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $adminLsp;

    protected User $komiteTeknis;

    protected User $tukAdmin1;

    protected User $tukAdmin2;

    protected Tuk $tuk1;

    protected Tuk $tuk2;

    protected Scheme $scheme;

    protected Schedule $schedule1;

    protected Schedule $schedule2;

    protected Assessment $assessment1;

    protected AssessmentAppeal $appeal1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::create([
            'full_name' => 'Super Admin Test',
            'email' => 'superadmin@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->adminLsp = User::create([
            'full_name' => 'Admin LSP Test',
            'email' => 'adminlsp@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);

        $this->komiteTeknis = User::create([
            'full_name' => 'Komite Teknis Test',
            'email' => 'komite@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'KOMITE_TEKNIS',
            'status' => 'ACTIVE',
        ]);

        $this->tuk1 = Tuk::create([
            'code' => 'TUK-01',
            'name' => 'TUK Otomotif Barat',
            'address' => 'Jl. Barat No. 1',
            'type' => 'SEWAKTU',
            'is_active' => true,
        ]);

        $this->tuk2 = Tuk::create([
            'code' => 'TUK-02',
            'name' => 'TUK Otomotif Timur',
            'address' => 'Jl. Timur No. 2',
            'type' => 'SEWAKTU',
            'is_active' => true,
        ]);

        $this->tukAdmin1 = User::create([
            'full_name' => 'TUK Admin 1',
            'email' => 'tuk1@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'TUK_ADMIN',
            'status' => 'ACTIVE',
            'tuk_id' => $this->tuk1->id,
        ]);

        $this->tukAdmin2 = User::create([
            'full_name' => 'TUK Admin 2',
            'email' => 'tuk2@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'TUK_ADMIN',
            'status' => 'ACTIVE',
            'tuk_id' => $this->tuk2->id,
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-ROLES-01',
            'name' => 'Skema Roles Test',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);

        $this->schedule1 = Schedule::create([
            'code' => 'SCH-TUK-01',
            'name' => 'Jadwal TUK 1',
            'scheme_id' => $this->scheme->id,
            'tuk_id' => $this->tuk1->id,
            'tuk_name' => $this->tuk1->name,
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $this->adminLsp->id,
            'selected_instruments' => ['AK01'],
        ]);

        $this->schedule2 = Schedule::create([
            'code' => 'SCH-TUK-02',
            'name' => 'Jadwal TUK 2',
            'scheme_id' => $this->scheme->id,
            'tuk_id' => $this->tuk2->id,
            'tuk_name' => $this->tuk2->name,
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $this->adminLsp->id,
            'selected_instruments' => ['AK01'],
        ]);

        $asesi = User::create([
            'full_name' => 'Asesi Role Test',
            'email' => 'asesirole@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->assessment1 = Assessment::create([
            'schedule_id' => $this->schedule1->id,
            'asesi_id' => $asesi->id,
            'status' => 'ACTIVE',
            'admin_status' => 'PENDING',
        ]);

        $this->appeal1 = AssessmentAppeal::create([
            'assessment_id' => $this->assessment1->id,
            'appeal_date' => now()->toDateString(),
            'appeal_reasons' => 'Perlu peninjauan ulang',
            'status' => 'SUBMITTED',
        ]);
    }

    public function test_komite_teknis_and_tuk_admin_can_read_schedules(): void
    {
        $this->actingAs($this->komiteTeknis)
            ->get(route('admin.jadwal.index'))
            ->assertOk();

        $this->actingAs($this->tukAdmin1)
            ->get(route('admin.jadwal.index'))
            ->assertOk();

        $this->actingAs($this->komiteTeknis)
            ->get(route('admin.jadwal.show', $this->schedule1))
            ->assertOk();

        $this->actingAs($this->tukAdmin1)
            ->get(route('admin.jadwal.show', $this->schedule1))
            ->assertOk();
    }

    public function test_komite_teknis_is_forbidden_from_write_routes(): void
    {
        $this->actingAs($this->komiteTeknis)
            ->get(route('admin.jadwal.create'))
            ->assertStatus(403);

        $this->actingAs($this->komiteTeknis)
            ->post(route('admin.jadwal.store'), [
                'name' => 'Unauthorized Schedule',
                'scheme_id' => $this->scheme->id,
                'date' => now()->toDateString(),
            ])
            ->assertStatus(403);

        $this->actingAs($this->komiteTeknis)
            ->get(route('admin.jadwal.edit', $this->schedule1))
            ->assertStatus(403);

        $this->actingAs($this->komiteTeknis)
            ->put(route('admin.jadwal.update', $this->schedule1), [
                'name' => 'Updated Name',
                'scheme_id' => $this->scheme->id,
                'date' => now()->toDateString(),
            ])
            ->assertStatus(403);

        $this->actingAs($this->komiteTeknis)
            ->delete(route('admin.jadwal.destroy', $this->schedule1))
            ->assertStatus(403);

        $this->actingAs($this->komiteTeknis)
            ->post(route('admin.assessment.review-registration', $this->assessment1), [
                'admin_status' => 'APPROVED',
            ])
            ->assertStatus(403);

        $this->actingAs($this->komiteTeknis)
            ->post(route('admin.jadwal.batch-review-registration', $this->schedule1), [
                'admin_status' => 'APPROVED',
            ])
            ->assertStatus(403);

        $this->actingAs($this->komiteTeknis)
            ->post(route('admin.appeals.decide', $this->appeal1), [
                'decision' => 'APPROVED',
                'notes' => 'Testing decision',
            ])
            ->assertStatus(403);
    }

    public function test_tuk_admin_is_forbidden_from_write_routes(): void
    {
        $this->actingAs($this->tukAdmin1)
            ->get(route('admin.jadwal.create'))
            ->assertStatus(403);

        $this->actingAs($this->tukAdmin1)
            ->post(route('admin.jadwal.store'), [
                'name' => 'Unauthorized Schedule',
                'scheme_id' => $this->scheme->id,
                'date' => now()->toDateString(),
            ])
            ->assertStatus(403);

        $this->actingAs($this->tukAdmin1)
            ->post(route('admin.assessment.review-registration', $this->assessment1), [
                'admin_status' => 'APPROVED',
            ])
            ->assertStatus(403);

        $this->actingAs($this->tukAdmin1)
            ->post(route('admin.appeals.decide', $this->appeal1), [
                'decision' => 'APPROVED',
            ])
            ->assertStatus(403);
    }

    public function test_tuk_admin_only_sees_schedules_belonging_to_their_tuk(): void
    {
        // TUK 1 Admin sees Schedule 1 but NOT Schedule 2
        $response1 = $this->actingAs($this->tukAdmin1)
            ->get(route('admin.jadwal.index'));

        $response1->assertOk()
            ->assertSee('SCH-TUK-01')
            ->assertDontSee('SCH-TUK-02');

        // TUK 2 Admin sees Schedule 2 but NOT Schedule 1
        $response2 = $this->actingAs($this->tukAdmin2)
            ->get(route('admin.jadwal.index'));

        $response2->assertOk()
            ->assertSee('SCH-TUK-02')
            ->assertDontSee('SCH-TUK-01');

        // Admin LSP sees BOTH schedules
        $responseAdmin = $this->actingAs($this->adminLsp)
            ->get(route('admin.jadwal.index'));

        $responseAdmin->assertOk()
            ->assertSee('SCH-TUK-01')
            ->assertSee('SCH-TUK-02');
    }
}
