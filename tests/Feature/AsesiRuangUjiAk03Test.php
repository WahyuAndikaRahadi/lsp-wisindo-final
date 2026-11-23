<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsesiRuangUjiAk03Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $asesor;

    private User $asesi;

    private Scheme $scheme;

    private Schedule $schedule;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'SUPER_ADMIN']);
        $this->asesor = User::factory()->create(['role' => 'ASESOR']);
        $this->asesi = User::factory()->create(['role' => 'ASESI']);

        $this->scheme = Scheme::create([
            'code' => 'SKM-TEST-01',
            'name' => 'Skema Uji Kompetensi',
            'scheme_type' => 'KLASTER',
            'is_active' => true,
        ]);

        $this->schedule = Schedule::create([
            'code' => 'SCH-'.uniqid(),
            'name' => 'Jadwal Asesmen Ruang Uji',
            'scheme_id' => $this->scheme->id,
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'tuk_name' => 'TUK Mandiri',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['AK01', 'IA01', 'IA05'],
            'is_closed' => false,
        ]);

        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'scheme_id' => $this->scheme->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->asesor->id,
            'apl_status' => 'APPROVED',
            'status' => 'ACTIVE',
            'ak01_agreed_at' => now(),
            'ak01_data' => [
                'selected_instruments' => ['IA01', 'IA05'],
            ],
        ]);
    }

    public function test_ak03_is_locked_in_ruang_uji_when_cbt_is_not_completed(): void
    {
        // Candidate has not submitted CBT (IA05)
        $response = $this->actingAs($this->asesi)->get(route('asesi.assessment.ruang-uji', $this->assessment));

        $response->assertOk();
        $response->assertSee('FR.AK.03');
        $response->assertSee('Umpan Balik dan Catatan Asesmen');
        $response->assertSee('🔒 Terkunci');
        $response->assertSee('Terkunci (Belum Aktif)');

        // Attempting to visit /ak03 directly should redirect back with warning
        $ak03Response = $this->actingAs($this->asesi)->get(route('asesi.assessment.ak03', $this->assessment));
        $ak03Response->assertRedirect(route('asesi.assessment.ruang-uji', $this->assessment));
        $ak03Response->assertSessionHas('warning');
    }

    public function test_ak03_unlocks_immediately_when_asesi_submits_cbt_without_waiting_assessor_closure(): void
    {
        // 1. Submit CBT (IA05) for this candidate
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA05',
            'score' => 85.00,
            'asesi_signed_at' => now(),
            'data_json' => ['status' => 'SUBMITTED', 'score' => 85],
        ]);

        // Schedule is NOT closed and assessment is still ACTIVE
        $this->assertFalse($this->schedule->is_closed);
        $this->assertEquals('ACTIVE', $this->assessment->status);

        // In Ruang Uji, AK.03 must now be UNLOCKED and active
        $response = $this->actingAs($this->asesi)->get(route('asesi.assessment.ruang-uji', $this->assessment));
        $response->assertOk();
        $response->assertSee('FR.AK.03');
        $response->assertSee('Siap Diisi');
        $response->assertSee('Isi Umpan Balik Sekarang');
        $response->assertDontSee('Terkunci (Belum Aktif)');

        // Candidate can open and fill AK.03 directly
        $ak03Page = $this->actingAs($this->asesi)->get(route('asesi.assessment.ak03', $this->assessment));
        $ak03Page->assertOk();
        $ak03Page->assertSee('FR.AK.03 Umpan Balik Asesmen');
        $ak03Page->assertSee('Kembali ke Ruang Uji');

        // Submit AK.03 feedback
        $ratings = [];
        for ($i = 1; $i <= 10; $i++) {
            $ratings[$i] = ['value' => 'YA', 'comment' => 'Sangat baik'];
        }

        $submitRes = $this->actingAs($this->asesi)->post(route('asesi.assessment.ak03.submit', $this->assessment), [
            'ratings' => $ratings,
            'general_notes' => 'Proses ujian lancar.',
        ]);

        $submitRes->assertSessionHas('success');
        $this->assertDatabaseHas('t_assessment_ak03_items', ['assessment_id' => $this->assessment->id]);

        // In Ruang Uji, AK.03 now shows completed
        $hubAfter = $this->actingAs($this->asesi)->get(route('asesi.assessment.ruang-uji', $this->assessment));
        $hubAfter->assertOk();
        $hubAfter->assertSee('✓ Umpan Balik Terkirim');
        $hubAfter->assertSee('Lihat Umpan Balik');
    }

    public function test_umpan_balik_is_not_displayed_on_asesi_dashboard(): void
    {
        // Even when assessment has final decision or is completed
        $this->assessment->update([
            'status' => 'COMPLETED',
            'final_result' => 'KOMPETEN',
        ]);

        $response = $this->actingAs($this->asesi)->get(route('asesi.dashboard'));

        $response->assertOk();
        $response->assertSee('Rekap Hasil');
        $response->assertDontSee('FR.AK.03 Umpan Balik Asesmen');
        $response->assertDontSee(route('asesi.assessment.ak03', $this->assessment->id));
    }
}
