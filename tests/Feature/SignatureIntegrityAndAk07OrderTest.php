<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAk07Item;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SignatureIntegrityAndAk07OrderTest extends TestCase
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

        $this->admin = User::create([
            'full_name' => 'Admin LSP Test',
            'email' => 'admin_sig@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);

        $this->asesor = User::create([
            'full_name' => 'Asesor Test',
            'email' => 'asesor_sig@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'MET.000.777888',
        ]);

        $this->asesi = User::create([
            'full_name' => 'Asesi Test',
            'email' => 'asesi_sig@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SCH-INT-01',
            'name' => 'Skema Integritas TTD',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);

        $this->schedule = Schedule::create([
            'code' => 'SCH-SIG-01',
            'name' => 'Jadwal Uji Integritas',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['AK01'],
        ]);
        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->asesor->id,
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
            'status' => 'IN_PROGRESS',
        ]);
    }

    public function test_asesi_cannot_access_or_sign_ak01_before_ak07_is_recorded(): void
    {
        // 1. Without AK07, accessing AK01 redirects to asesi.dashboard with warning
        $response = $this->actingAs($this->asesi)->get(route('asesi.assessment.ak01', $this->assessment));
        $response->assertRedirect(route('asesi.dashboard'));
        $response->assertSessionHas('warning');

        // 2. Direct POST to sign AK01 without AK07 redirects back with error
        $signResponse = $this->actingAs($this->asesi)->post(route('asesi.assessment.ak01.sign', $this->assessment));
        $signResponse->assertSessionHas('error');

        // Verify that signature was NOT saved
        $this->assessment->refresh();
        $this->assertNull($this->assessment->asesi_signed_at);
        $this->assertNull($this->assessment->ak01_agreed_at);

        // 3. Now Asesor records AK07
        AssessmentAk07Item::create([
            'assessment_id' => $this->assessment->id,
            'adjustment_category' => 'Standar',
            'adjustment_detail' => 'Asesmen normal tanpa penyesuaian.',
            'assessor_id' => $this->asesor->id,
        ]);

        // 4. Asesi can now access and sign AK01
        $allowedGet = $this->actingAs($this->asesi)->get(route('asesi.assessment.ak01', $this->assessment));
        $allowedGet->assertStatus(200);

        $validSig = 'data:image/png;base64,'.base64_encode('first-valid-signature');
        $allowedSign = $this->actingAs($this->asesi)->post(route('asesi.assessment.ak01.sign', $this->assessment), [
            'asesi_signature' => $validSig,
        ]);
        $allowedSign->assertRedirect(route('asesi.assessment.ruang-uji', $this->assessment));

        $this->assessment->refresh();
        $this->assertNotNull($this->assessment->asesi_signed_at);
        $this->assertNotNull($this->assessment->ak01_agreed_at);
        $this->assertEquals($validSig, $this->assessment->asesi_signature);
    }

    public function test_signing_ak01_preserves_first_signature_timestamp_and_logs_updates(): void
    {
        Log::spy();

        // Create AK07
        AssessmentAk07Item::create([
            'assessment_id' => $this->assessment->id,
            'adjustment_category' => 'Standar',
            'adjustment_detail' => 'Standar',
            'assessor_id' => $this->asesor->id,
        ]);

        // First signature
        $firstTime = now()->subHours(2)->startOfSecond();
        $initialSig = 'data:image/png;base64,'.base64_encode('initial-sig-payload');
        $this->assessment->update([
            'asesi_signature' => $initialSig,
            'asesi_signed_at' => $firstTime,
            'ak01_agreed_at' => $firstTime,
        ]);

        // Re-signing with new signature payload
        $updatedSig = 'data:image/png;base64,'.base64_encode('updated-sig-payload');
        $this->actingAs($this->asesi)->post(route('asesi.assessment.ak01.sign', $this->assessment), [
            'asesi_signature' => $updatedSig,
        ]);

        $this->assessment->refresh();
        // Timestamp must remain preserved (not bumped to current time)
        $this->assertEquals($firstTime->toDateTimeString(), $this->assessment->asesi_signed_at->toDateTimeString());
        $this->assertEquals($firstTime->toDateTimeString(), $this->assessment->ak01_agreed_at->toDateTimeString());
        $this->assertEquals($updatedSig, $this->assessment->asesi_signature);

        // Verify audit log call
        Log::shouldHaveReceived('info')->withArgs(function ($message) {
            return str_contains($message, 'Signature updated for assessment');
        });
    }

    public function test_asesor_finalization_preserves_first_signature_timestamp_and_logs_updates(): void
    {
        Log::spy();

        $firstTime = now()->subDay()->startOfSecond();
        $firstSig = 'data:image/png;base64,'.base64_encode('first-assessor-signature');
        $newSig = 'data:image/png;base64,'.base64_encode('new-assessor-signature');

        $this->assessment->update([
            'assessor_signature' => $firstSig,
            'assessor_signed_at' => $firstTime,
            'final_result' => 'KOMPETEN',
            'final_result_at' => $firstTime,
            'completed_at' => $firstTime,
            'status' => 'COMPLETED',
        ]);

        // Asesor updates AK02 recommendation
        $response = $this->actingAs($this->asesor)->post(route('asesor.assessment.ak02.update', $this->assessment), [
            'final_result' => 'KOMPETEN',
            'rekomendasi' => 'Kompeten pada seluruh unit.',
            'assessor_signature' => $newSig,
        ]);

        $response->assertRedirect();
        $this->assessment->refresh();

        // Timestamps preserved
        $this->assertEquals($firstTime->toDateTimeString(), $this->assessment->assessor_signed_at->toDateTimeString());
        $this->assertEquals($firstTime->toDateTimeString(), $this->assessment->final_result_at->toDateTimeString());
        $this->assertEquals($firstTime->toDateTimeString(), $this->assessment->completed_at->toDateTimeString());
        $this->assertEquals($newSig, $this->assessment->assessor_signature);

        // Verify audit log
        Log::shouldHaveReceived('info')->withArgs(function ($message) {
            return str_contains($message, 'Signature updated for assessment');
        });
    }
}
