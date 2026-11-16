<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminScheduleFilterConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Scheme $schemeA;

    protected Scheme $schemeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);

        $this->schemeA = Scheme::create([
            'name' => 'Skema Digital Marketing',
            'code' => 'SKM-DM-01',
            'scheme_type' => 'KLASTER',
            'is_active' => true,
        ]);

        $this->schemeB = Scheme::create([
            'name' => 'Skema Web Development',
            'code' => 'SKM-WD-02',
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

    public function test_dokumen_muk_can_filter_by_scheme_and_date_range(): void
    {
        $sch1 = $this->createSchedule([
            'name' => 'Jadwal MUK Skema A Mei',
            'scheme_id' => $this->schemeA->id,
            'date' => '2026-05-15',
        ]);

        $sch2 = $this->createSchedule([
            'name' => 'Jadwal MUK Skema B Juni',
            'scheme_id' => $this->schemeB->id,
            'date' => '2026-06-20',
        ]);

        // Filter by scheme A
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.index', [
            'scheme_id' => $this->schemeA->id,
        ]));
        $response->assertOk();
        $response->assertSee('Jadwal MUK Skema A Mei');
        $response->assertDontSee('Jadwal MUK Skema B Juni');

        // Filter by date range (June only)
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.index', [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]));
        $response->assertOk();
        $response->assertSee('Jadwal MUK Skema B Juni');
        $response->assertDontSee('Jadwal MUK Skema A Mei');
    }

    public function test_dokumen_muk_returns_partial_on_ajax_request(): void
    {
        $this->createSchedule([
            'name' => 'Jadwal MUK Ajax Test',
            'scheme_id' => $this->schemeA->id,
            'date' => '2026-05-15',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertSee('Jadwal MUK Ajax Test');
        // Partial view does not contain full layout html tag
        $response->assertDontSee('<!DOCTYPE html>');
    }

    public function test_certificates_can_filter_by_scheme_and_date_range(): void
    {
        $sch1 = $this->createSchedule([
            'name' => 'Jadwal Sertifikat A Mei',
            'scheme_id' => $this->schemeA->id,
            'date' => '2026-05-10',
        ]);

        $sch2 = $this->createSchedule([
            'name' => 'Jadwal Sertifikat B Juli',
            'scheme_id' => $this->schemeB->id,
            'date' => '2026-07-25',
        ]);

        // Filter by scheme B
        $response = $this->actingAs($this->admin)->get(route('admin.certificates.index', [
            'scheme_id' => $this->schemeB->id,
        ]));
        $response->assertOk();
        $response->assertSee('Jadwal Sertifikat B Juli');
        $response->assertDontSee('Jadwal Sertifikat A Mei');

        // Filter by date range (May only)
        $response = $this->actingAs($this->admin)->get(route('admin.certificates.index', [
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-31',
        ]));
        $response->assertOk();
        $response->assertSee('Jadwal Sertifikat A Mei');
        $response->assertDontSee('Jadwal Sertifikat B Juli');
    }

    public function test_certificates_returns_partial_on_ajax_request(): void
    {
        $this->createSchedule([
            'name' => 'Jadwal Sertifikat Ajax Test',
            'scheme_id' => $this->schemeA->id,
            'date' => '2026-05-15',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.certificates.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $response->assertSee('Jadwal Sertifikat Ajax Test');
        // Partial view does not contain full layout html tag
        $response->assertDontSee('<!DOCTYPE html>');
    }

    public function test_empty_state_shows_reset_filter_link_when_filter_applied(): void
    {
        $this->createSchedule([
            'name' => 'Jadwal Skema A',
            'scheme_id' => $this->schemeA->id,
            'date' => '2026-05-10',
        ]);

        // Dokumen MUK empty filter result
        $responseMuk = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.index', [
            'search' => 'NonExistentScheduleSearchTerm',
        ]));
        $responseMuk->assertOk();
        $responseMuk->assertSee('Tidak ada jadwal asesmen ditemukan');
        $responseMuk->assertSee('data-ajax-reset="muk"', false);

        // Certificates empty filter result
        $responseCert = $this->actingAs($this->admin)->get(route('admin.certificates.index', [
            'search' => 'NonExistentScheduleSearchTerm',
        ]));
        $responseCert->assertOk();
        $responseCert->assertSee('Tidak ada jadwal asesmen ditemukan');
        $responseCert->assertSee('data-ajax-reset="certificates"', false);
    }
}
