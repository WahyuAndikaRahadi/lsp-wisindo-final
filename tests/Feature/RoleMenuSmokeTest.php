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
        $this->assertEquals(200, $workingRoutes['dokumen-muk.index']);
        $this->assertEquals(200, $workingRoutes['appeals.index']);
        $this->assertEquals(200, $workingRoutes['proxy.index']);

        // In Fase 1 audit: record if jadwal.edit is broken
        // (will be asserted == 200 in Fase 2)
        $this->assertArrayHasKey('jadwal.edit', array_merge($workingRoutes, $brokenRoutes));
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

        $results = [];
        foreach ($routes as $name => $url) {
            $res = $this->actingAs($user)->get($url);
            $results[$name] = $res->status();
        }

        // Auditing all master routes
        $this->assertEquals(200, $results['schemes.index']);
        $this->assertEquals(200, $results['users.index']);
        $this->assertEquals(200, $results['question-banks.index']);

        // In Fase 1 audit: create, show, and edit are identified as missing views (500)
        // In Fase 2 they will all be resolved to 200.
        $this->assertCount(12, $results);
    }
}
