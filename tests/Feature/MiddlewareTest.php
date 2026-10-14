<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'role:ADMIN_LSP,SUPER_ADMIN'])->get('/test-role', function () {
            return response('OK');
        });

        Route::middleware(['web', 'apl.approved'])->get('/asesi/assessment/{assessment}/test-step', function (Assessment $assessment) {
            return response('OK');
        })->name('asesi.assessment.test-step');

        Route::middleware(['web', 'track.step'])->get('/asesi/assessment/{assessment}/ak01', function (Assessment $assessment) {
            return response('OK');
        })->name('asesi.assessment.ak01');
    }

    public function test_role_middleware_allows_authorized_users(): void
    {
        $admin = User::create([
            'full_name' => 'Admin Test',
            'email' => 'admin.test@example.com',
            'password' => Hash::make('password'),
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);
        $this->actingAs($admin)->get('/test-role')->assertOk();

        $superAdmin = User::create([
            'full_name' => 'Super Admin Test',
            'email' => 'super.test@example.com',
            'password' => Hash::make('password'),
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);
        $this->actingAs($superAdmin)->get('/test-role')->assertOk();
    }

    public function test_role_middleware_aborts_unauthorized_users(): void
    {
        $asesi = User::create([
            'full_name' => 'Asesi Test',
            'email' => 'asesi.test@example.com',
            'password' => Hash::make('password'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);
        $this->actingAs($asesi)->get('/test-role')->assertStatus(403);

        $this->get('/test-role')->assertStatus(403);
    }

    public function test_check_apl_approved_middleware(): void
    {
        $scheme = Scheme::create([
            'code' => 'SCH-01',
            'name' => 'Scheme 1',
            'scheme_type' => 'KKNI',
            'is_active' => true,
        ]);
        $asesi = User::create([
            'full_name' => 'Asesi Approved Test',
            'email' => 'asesi.approved@example.com',
            'password' => Hash::make('password'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);
        $schedule = Schedule::create([
            'code' => 'SCHED-01',
            'name' => 'Jadwal 1',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Cikarang',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'duration_minutes' => 120,
            'created_by' => $asesi->id,
            'selected_instruments' => ['AK01'],
        ]);

        $pendingAssessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi->id,
            'apl_status' => 'SUBMITTED',
            'last_active_step' => 'apl',
        ]);

        $this->actingAs($asesi)
            ->get("/asesi/assessment/{$pendingAssessment->id}/test-step")
            ->assertRedirect(route('asesi.assessment.apl', $pendingAssessment))
            ->assertSessionHas('error', 'Menunggu persetujuan asesor');

        $pendingAssessment->update(['apl_status' => 'APPROVED']);

        $this->actingAs($asesi)
            ->get("/asesi/assessment/{$pendingAssessment->id}/test-step")
            ->assertOk();
    }

    public function test_track_last_step_middleware(): void
    {
        $scheme = Scheme::create([
            'code' => 'SCH-02',
            'name' => 'Scheme 2',
            'scheme_type' => 'KKNI',
            'is_active' => true,
        ]);
        $asesi = User::create([
            'full_name' => 'Asesi Track Test',
            'email' => 'asesi.track@example.com',
            'password' => Hash::make('password'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);
        $schedule = Schedule::create([
            'code' => 'SCHED-02',
            'name' => 'Jadwal 2',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Cikarang',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'duration_minutes' => 120,
            'created_by' => $asesi->id,
            'selected_instruments' => ['AK01'],
        ]);

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi->id,
            'apl_status' => 'APPROVED',
            'last_active_step' => 'apl',
        ]);

        $this->actingAs($asesi)
            ->get("/asesi/assessment/{$assessment->id}/ak01")
            ->assertOk();

        $this->assertEquals('ak01', $assessment->fresh()->last_active_step);
    }
}
