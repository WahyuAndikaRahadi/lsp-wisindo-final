<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AssessorSignatureGateTest extends TestCase
{
    use RefreshDatabase;

    private User $assessorWithoutSignature;

    private User $assessorWithSignature;

    private Schedule $schedule;

    private Scheme $scheme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scheme = Scheme::create([
            'code' => 'SKM-GATE-01',
            'name' => 'Skema Gate Uji Signature',
            'scheme_type' => 'KLASTER',
            'is_active' => true,
        ]);

        $this->assessorWithoutSignature = User::factory()->create([
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
        ]);

        $this->assessorWithSignature = User::factory()->withSignature()->create([
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
        ]);

        $this->schedule = Schedule::create([
            'code' => 'SCH-GATE-01',
            'name' => 'Jadwal Gate Test',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Gate Verification',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'ACTIVE',
            'created_by' => $this->assessorWithSignature->id,
            'selected_instruments' => ['AK01', 'AK07', 'IA01', 'IA02'],
        ]);

        $this->schedule->assessors()->attach([
            $this->assessorWithoutSignature->id,
            $this->assessorWithSignature->id,
        ]);

        $asesi = User::factory()->create(['role' => 'ASESI', 'status' => 'ACTIVE']);
        Assessment::create([
            'schedule_id' => $this->schedule->id,
            'scheme_id' => $this->scheme->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $this->assessorWithoutSignature->id,
            'status' => 'IN_PROGRESS',
            'apl_status' => 'APPROVED',
        ]);
    }

    public function test_assessor_without_signature_has_disabled_button_in_jadwal_listing(): void
    {
        $response = $this->actingAs($this->assessorWithoutSignature)
            ->get(route('asesor.jadwal.index'));

        $response->assertOk();
        $response->assertSee('Tanda Tangan Digital Belum Disimpan!');
        $response->assertSee('TTD Profil Diperlukan');
        $response->assertSee('disabled', false);
        $response->assertDontSee(route('asesor.schedule.ruang-uji', $this->schedule->id));
    }

    public function test_assessor_without_signature_is_redirected_when_accessing_ruang_uji(): void
    {
        $response = $this->actingAs($this->assessorWithoutSignature)
            ->get(route('asesor.schedule.ruang-uji', $this->schedule->id));

        $response->assertRedirect(route('asesor.jadwal.index'));
        $response->assertSessionHas('warning', 'Silakan simpan tanda tangan digital pada profil Anda terlebih dahulu sebelum masuk ke ruang uji jadwal.');
    }

    public function test_assessor_with_signature_sees_enabled_button_in_jadwal_listing(): void
    {
        $response = $this->actingAs($this->assessorWithSignature)
            ->get(route('asesor.jadwal.index'));

        $response->assertOk();
        $response->assertDontSee('Tanda Tangan Digital Belum Disimpan!');
        $response->assertDontSee('TTD Profil Diperlukan');
        $response->assertSee('Masuk Ruang Uji');
        $response->assertSee(route('asesor.schedule.ruang-uji', $this->schedule->id));
    }

    public function test_assessor_with_signature_can_access_schedule_and_passes_gate(): void
    {
        $this->assertTrue(Gate::forUser($this->assessorWithSignature)->allows('enter-schedule', $this->schedule));
        $this->assertFalse(Gate::forUser($this->assessorWithoutSignature)->allows('enter-schedule', $this->schedule));

        // When accessing ruang uji, with signature passes gate check (redirects to ak01 if step not yet completed, but not blocked back to jadwal.index)
        $response = $this->actingAs($this->assessorWithSignature)
            ->get(route('asesor.schedule.ruang-uji', $this->schedule->id));

        $this->assertNotEquals(route('asesor.jadwal.index'), $response->headers->get('Location'));
    }

    public function test_unassigned_assessor_is_denied_by_gate(): void
    {
        $unassignedAssessor = User::factory()->withSignature()->create([
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
        ]);

        $this->assertFalse(Gate::forUser($unassignedAssessor)->allows('enter-schedule', $this->schedule));
    }
}
