<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMenuSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function getFixture(): array
    {
        $superAdmin = User::where('role', 'SUPER_ADMIN')->first();
        $admin = User::where('role', 'ADMIN_LSP')->first();
        $asesor = User::where('role', 'ASESOR')->first();
        $asesi = User::where('role', 'ASESI')->first();
        $scheme = Scheme::first();

        $schedule = Schedule::create([
            'code' => 'SCH-SMOKE-'.uniqid(),
            'name' => 'Smoke Test Schedule',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Cikarang',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'AK07', 'IA01', 'IA02', 'IA03', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);
        $schedule->assessors()->attach($asesor->id);

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $asesor->id,
            'scheme_id' => $scheme->id,
            'status' => 'IN_PROGRESS',
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
        ]);

        return compact('superAdmin', 'admin', 'asesor', 'asesi', 'scheme', 'schedule', 'assessment');
    }

    public function test_asesi_menu_routes_smoke(): void
    {
        $f = $this->getFixture();
        $user = $f['asesi'];
        $asm = $f['assessment'];

        $routes = [
            route('asesi.dashboard'),
            route('asesi.profile.edit'),
            route('asesi.jadwal.index'),
            route('asesi.assessment.apl', $asm),
            route('asesi.assessment.ak01', $asm),
            route('asesi.assessment.ruang-uji', $asm),
            route('asesi.assessment.instrument.ia05', $asm),
            route('asesi.assessment.instrument.view', [$asm, 'ia01']),
            route('asesi.assessment.ak03', $asm),
            route('asesi.assessment.appeal', $asm),
        ];

        foreach ($routes as $url) {
            $res = $this->actingAs($user)->get($url);
            $this->assertTrue(
                in_array($res->status(), [200, 302]),
                "Asesi failed GET $url with status {$res->status()}"
            );
        }
    }

    public function test_asesor_menu_routes_smoke(): void
    {
        $f = $this->getFixture();
        $user = $f['asesor'];
        $sch = $f['schedule'];
        $asm = $f['assessment'];

        $routes = [
            route('asesor.jadwal.index'),
            route('asesor.jadwal.asesi', $sch),
            route('asesor.apl.review', $asm),
            route('asesor.schedule.ak01', $sch),
            route('asesor.schedule.ak07', $sch),
            route('asesor.schedule.ruang-uji', $sch),
            route('asesor.schedule.instrument', [$sch, 'IA01']),
            route('asesor.assessment.ruang-uji', $asm),
            route('asesor.assessment.instrument', [$asm, 'ia01']),
            route('asesor.assessment.ak02', $asm),
            route('asesor.schedule.reports', $sch),
        ];

        foreach ($routes as $url) {
            $res = $this->actingAs($user)->get($url);
            $this->assertTrue(
                in_array($res->status(), [200, 302]),
                "Asesor failed GET $url with status {$res->status()}"
            );
        }
    }

    public function test_admin_menu_routes_smoke(): void
    {
        $f = $this->getFixture();
        $user = $f['admin'];
        $sch = $f['schedule'];

        $routes = [
            'dashboard' => route('admin.dashboard'),
            'jadwal.index' => route('admin.jadwal.index'),
            'jadwal.create' => route('admin.jadwal.create'),
            'jadwal.show' => route('admin.jadwal.show', $sch),
            'jadwal.edit' => route('admin.jadwal.edit', $sch),
            'dokumen-muk.index' => route('admin.dokumen-muk.index'),
            'appeals.index' => route('admin.appeals.index'),
            'proxy.index' => route('admin.proxy.index'),
        ];

        $brokenRoutes = [];
        $workingRoutes = [];

        foreach ($routes as $name => $url) {
            $res = $this->actingAs($user)->get($url);
            if (in_array($res->status(), [200, 302])) {
                $workingRoutes[$name] = $res->status();
            } else {
                $brokenRoutes[$name] = $res->status();
            }
        }

        $this->assertEquals(200, $workingRoutes['dashboard']);
        $this->assertEquals(200, $workingRoutes['jadwal.index']);
        $this->assertEquals(200, $workingRoutes['jadwal.create']);
        $this->assertEquals(200, $workingRoutes['jadwal.show']);
        $this->assertEquals(200, $workingRoutes['jadwal.edit']);
        $this->assertEquals(200, $workingRoutes['dokumen-muk.index']);
        $this->assertEquals(200, $workingRoutes['appeals.index']);
        $this->assertEquals(200, $workingRoutes['proxy.index']);
    }

    public function test_superadmin_master_routes_smoke(): void
    {
        $f = $this->getFixture();
        $user = $f['superAdmin'];
        $scheme = $f['scheme'];

        $qb = QuestionBank::first();
        if (! $qb) {
            $qb = QuestionBank::create([
                'scheme_id' => $scheme->id,
                'question_text' => 'Berapa tegangan baterai?',
                'option_a' => '12V',
                'option_b' => '6V',
                'option_c' => '24V',
                'option_d' => '48V',
                'correct_answer' => 'A',
            ]);
        }

        $routes = [
            'schemes.index' => route('admin.schemes.index'),
            'schemes.create' => route('admin.schemes.create'),
            'schemes.show' => route('admin.schemes.show', $scheme),
            'schemes.edit' => route('admin.schemes.edit', $scheme),
            'users.index' => route('admin.users.index'),
            'users.create' => route('admin.users.create'),
            'users.show' => route('admin.users.show', $user),
            'users.edit' => route('admin.users.edit', $user),
            'question-banks.index' => route('admin.question-banks.index'),
            'question-banks.create' => route('admin.question-banks.create'),
            'question-banks.show' => route('admin.question-banks.show', $qb),
            'question-banks.edit' => route('admin.question-banks.edit', $qb),
        ];

        foreach ($routes as $name => $url) {
            $res = $this->actingAs($user)->get($url);
            $this->assertEquals(200, $res->status(), "Route $name ($url) returned status {$res->status()}");
        }
    }

    public function test_entities_with_active_relations_are_safely_deactivated_instead_of_hard_deleted(): void
    {
        $f = $this->getFixture();
        $admin = $f['admin'];
        $superAdmin = $f['superAdmin'];
        $sch = $f['schedule'];
        $scheme = $f['scheme'];
        $asesi = $f['asesi'];

        // 1. Schedule with candidates -> soft deactivated, not deleted
        $resSch = $this->actingAs($admin)->delete(route('admin.jadwal.destroy', $sch));
        $resSch->assertRedirect(route('admin.jadwal.index'));
        $resSch->assertSessionHas('warning');
        $sch->refresh();
        $this->assertEquals('FINISHED', $sch->status);
        $this->assertDatabaseHas('t_schedules', ['id' => $sch->id]);

        // 2. Scheme used in schedules -> is_active = false, not deleted
        $resScm = $this->actingAs($superAdmin)->delete(route('admin.schemes.destroy', $scheme));
        $resScm->assertRedirect(route('admin.schemes.index'));
        $resScm->assertSessionHas('warning');
        $scheme->refresh();
        $this->assertFalse((bool) $scheme->is_active);
        $this->assertDatabaseHas('m_schemes', ['id' => $scheme->id]);

        // 3. User deleting self -> blocked with error
        $resSelf = $this->actingAs($superAdmin)->delete(route('admin.users.destroy', $superAdmin));
        $resSelf->assertRedirect(route('admin.users.index'));
        $resSelf->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);

        // 4. User with assessments -> status = INACTIVE, not deleted
        $resUsr = $this->actingAs($superAdmin)->delete(route('admin.users.destroy', $asesi));
        $resUsr->assertRedirect(route('admin.users.index'));
        $resUsr->assertSessionHas('warning');
        $asesi->refresh();
        $this->assertEquals('INACTIVE', $asesi->status);
        $this->assertDatabaseHas('users', ['id' => $asesi->id]);
    }
}
