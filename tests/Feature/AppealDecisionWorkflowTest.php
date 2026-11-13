<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAppeal;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AppealDecisionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $asesor;

    private User $asesi;

    private Schedule $schedule;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'full_name' => 'Admin Banding Test',
            'email' => 'admin_banding@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);

        $this->asesor = User::create([
            'full_name' => 'Asesor Banding Test',
            'email' => 'asesor_banding@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'REG.999.001',
        ]);

        $this->asesi = User::create([
            'full_name' => 'Bagus Prayogo',
            'email' => 'bagus_appeal@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
            'phone' => '083816927804',
        ]);

        $scheme = Scheme::create([
            'code' => 'SKM-01-2026',
            'name' => 'Engine Junior Technician',
            'scheme_type' => 'KLASTER',
            'passing_grade_theory' => 70.00,
            'is_active' => true,
        ]);

        $this->schedule = Schedule::create([
            'code' => 'LSP-53A6BJ',
            'name' => 'testing jadwal 3',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Utama',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['IA01', 'IA02'],
        ]);

        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->asesor->id,
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
            'final_result' => 'BELUM_KOMPETEN',
            'final_result_at' => now(),
            'status' => 'COMPLETED',
        ]);
    }

    public function test_admin_appeals_page_renders_clean_light_theme_without_dark_header(): void
    {
        $appeal = AssessmentAppeal::create([
            'assessment_id' => $this->assessment->id,
            'appeal_date' => now()->toDateString(),
            'appeal_reasons' => 'Keberatan terhadap hasil ujian observasi praktik.',
            'status' => 'SUBMITTED',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.appeals.index'));
        $response->assertOk();
        $response->assertSee('Bagus Prayogo');
        $response->assertSee('MENUNGGU KEPUTUSAN');
        $response->assertSee('083816927804');
        $response->assertDontSee('bg-gradient-to-r from-slate-900 to-slate-800');
    }

    public function test_admin_decides_appeal_accepted_with_override_kompeten_option(): void
    {
        $appeal = AssessmentAppeal::create([
            'assessment_id' => $this->assessment->id,
            'appeal_date' => now()->toDateString(),
            'appeal_reasons' => 'Bukti portofolio kerja tidak diverifikasi secara adil.',
            'status' => 'SUBMITTED',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.appeals.decide', $appeal), [
            'decision' => 'ACCEPTED',
            'action_type' => 'OVERRIDE_KOMPETEN',
            'decision_notes' => 'Komite memutuskan bukti portofolio valid dan menganulir keputusan asesor.',
            'appeals_committee_chair' => 'Drs. Supriyadi, M.T.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $appeal->refresh();
        $this->assessment->refresh();

        $this->assertEquals('ACCEPTED', $appeal->status);
        $this->assertEquals('Drs. Supriyadi, M.T.', $appeal->appeals_committee_chair);
        $this->assertEquals('KOMPETEN', $this->assessment->final_result);
        $this->assertStringContainsString('Ditetapkan KOMPETEN oleh Komite Banding', $this->assessment->follow_up_action);
    }

    public function test_admin_decides_appeal_accepted_with_reassessment_option(): void
    {
        $appeal = AssessmentAppeal::create([
            'assessment_id' => $this->assessment->id,
            'appeal_date' => now()->toDateString(),
            'appeal_reasons' => 'Perlu uji ulang dengan asesor independen.',
            'status' => 'SUBMITTED',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.appeals.decide', $appeal), [
            'decision' => 'ACCEPTED',
            'action_type' => 'REASSESSMENT',
            'decision_notes' => 'Dijadwalkan uji ulang untuk unit UK-02.',
        ]);

        $response->assertRedirect();
        $appeal->refresh();
        $this->assessment->refresh();

        $this->assertEquals('ACCEPTED', $appeal->status);
        $this->assertEquals('DRAFT', $this->assessment->status);
        $this->assertEquals('DRAFT', $this->assessment->apl_status);
        $this->assertEquals('BELUM_SELESAI', $this->assessment->final_result);
        $this->assertStringContainsString('Dijadwalkan Asesmen Ulang', $this->assessment->follow_up_action);
    }

    public function test_admin_decides_appeal_rejected(): void
    {
        $appeal = AssessmentAppeal::create([
            'assessment_id' => $this->assessment->id,
            'appeal_date' => now()->toDateString(),
            'appeal_reasons' => 'Minta lulus langsung.',
            'status' => 'SUBMITTED',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.appeals.decide', $appeal), [
            'decision' => 'REJECTED',
            'decision_notes' => 'Alasan banding tidak memenuhi ketentuan pembuktian unjuk kerja.',
        ]);

        $response->assertRedirect();
        $appeal->refresh();
        $this->assessment->refresh();

        $this->assertEquals('REJECTED', $appeal->status);
        $this->assertEquals('BELUM_KOMPETEN', $this->assessment->final_result);
        $this->assertStringContainsString('Sanggahan Banding Ditolak', $this->assessment->follow_up_action);
    }
}
