<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Certificate;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AjaxTableAndSkeletonLoadingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $asesor;

    protected User $asesi;

    protected Scheme $scheme;

    protected Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->asesor = User::factory()->create([
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
        ]);

        $this->asesi = User::factory()->create([
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'name' => 'Pemeliharaan Kendaraan Ringan',
            'code' => 'OTO-PKR-001',
            'is_active' => true,
        ]);

        $this->schedule = Schedule::create([
            'name' => 'Uji Sertifikasi Otomotif Batch 1',
            'code' => 'JADWAL-OTO-01',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Otomotif Pusat',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'duration_minutes' => 120,
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['AK01', 'IA01', 'IA02'],
        ]);

        $this->schedule->assessors()->attach($this->asesor->id);

        Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->asesor->id,
            'status' => 'IN_PROGRESS',
            'apl_status' => 'SUBMITTED',
            'registered_at' => now(),
        ]);
    }

    public function test_admin_users_ajax_partial_and_ssr(): void
    {
        // 1. Standard SSR GET request returns full layout
        $ssrResponse = $this->actingAs($this->admin)->get(route('admin.users.index'));
        $ssrResponse->assertOk();
        $ssrResponse->assertSee('Kelola Pengguna');
        $ssrResponse->assertSee('data-ajax-container="users"', false);
        $ssrResponse->assertSee('<!DOCTYPE html>', false);

        // 2. AJAX GET request returns only table partial without layout wrapper
        $ajaxResponse = $this->actingAs($this->admin)->get(route('admin.users.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxResponse->assertOk();
        $ajaxResponse->assertSee($this->admin->email);
        $ajaxResponse->assertDontSee('<!DOCTYPE html>', false);
    }

    public function test_admin_jadwal_ajax_partial_and_ssr(): void
    {
        // 1. Standard SSR GET request
        $ssrResponse = $this->actingAs($this->admin)->get(route('admin.jadwal.index'));
        $ssrResponse->assertOk();
        $ssrResponse->assertSee('data-ajax-container="jadwal"', false);
        $ssrResponse->assertSee($this->schedule->code);
        $ssrResponse->assertSee('<!DOCTYPE html>', false);

        // 2. AJAX request
        $ajaxResponse = $this->actingAs($this->admin)->get(route('admin.jadwal.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxResponse->assertOk();
        $ajaxResponse->assertSee($this->schedule->code);
        $ajaxResponse->assertDontSee('<!DOCTYPE html>', false);
    }

    public function test_admin_jadwal_show_peserta_ajax_partial(): void
    {
        // 1. Standard SSR GET request
        $ssrResponse = $this->actingAs($this->admin)->get(route('admin.jadwal.show', $this->schedule));
        $ssrResponse->assertOk();
        $ssrResponse->assertSee('data-ajax-container="peserta"', false);
        $ssrResponse->assertSee($this->asesi->full_name);

        // 2. AJAX request returns only peserta table
        $ajaxResponse = $this->actingAs($this->admin)->get(route('admin.jadwal.show', $this->schedule), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxResponse->assertOk();
        $ajaxResponse->assertSee($this->asesi->full_name);
        $ajaxResponse->assertDontSee('<!DOCTYPE html>', false);
    }

    public function test_asesor_jadwal_ajax_partial_and_ssr(): void
    {
        // 1. Standard SSR GET request
        $ssrResponse = $this->actingAs($this->asesor)->get(route('asesor.jadwal.index'));
        $ssrResponse->assertOk();
        $ssrResponse->assertSee('data-ajax-container="asesor-jadwal"', false);
        $ssrResponse->assertSee($this->schedule->code);

        // 2. AJAX request
        $ajaxResponse = $this->actingAs($this->asesor)->get(route('asesor.jadwal.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxResponse->assertOk();
        $ajaxResponse->assertSee($this->schedule->code);
        $ajaxResponse->assertDontSee('<!DOCTYPE html>', false);
    }

    public function test_asesi_jadwal_and_certificates_ajax_partial(): void
    {
        // 1. Asesi Schedule
        $ssrResponse = $this->actingAs($this->asesi)->get(route('asesi.jadwal.index'));
        $ssrResponse->assertOk();
        $ssrResponse->assertSee('data-ajax-container="asesi-jadwal"', false);

        $ajaxResponse = $this->actingAs($this->asesi)->get(route('asesi.jadwal.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxResponse->assertOk();
        $ajaxResponse->assertSee($this->schedule->code);
        $ajaxResponse->assertDontSee('<!DOCTYPE html>', false);

        // 2. Asesi Certificate
        $certificate = Certificate::create([
            'asesi_id' => $this->asesi->id,
            'schedule_id' => $this->schedule->id,
            'scheme_id' => $this->scheme->id,
            'assessment_id' => Assessment::where('asesi_id', $this->asesi->id)->first()->id,
            'certificate_number' => 'CERT-BNSP-12345',
            'blanko_number' => 'BLANKO-9988',
            'file_path' => 'certificates/dummy.pdf',
            'issued_date' => now()->subMonth(),
            'valid_until' => now()->addYears(3),
        ]);

        $certSsr = $this->actingAs($this->asesi)->get(route('asesi.certificates.index'));
        $certSsr->assertOk();
        $certSsr->assertSee('data-ajax-container="asesi-certificates"', false);
        $certSsr->assertSee('CERT-BNSP-12345');

        $certAjax = $this->actingAs($this->asesi)->get(route('asesi.certificates.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $certAjax->assertOk();
        $certAjax->assertSee('CERT-BNSP-12345');
        $certAjax->assertDontSee('<!DOCTYPE html>', false);
    }

    public function test_submit_button_spinner_script_is_removed(): void
    {
        $appBladeContent = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringNotContainsString('btn.disabled = true;', $appBladeContent);
        $this->assertStringNotContainsString('<span>Memproses...</span>', $appBladeContent);
        $this->assertStringContainsString('ajax-tables.js', $appBladeContent);
    }
}
