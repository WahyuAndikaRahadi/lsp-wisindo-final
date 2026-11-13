<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAppeal;
use App\Models\AssessmentProof;
use App\Models\Certificate;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\Setting;
use App\Models\User;
use App\Services\RegistrationTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AsesiRegistrationTokenAndAppealReworkTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $asesor;

    private Scheme $scheme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'full_name' => 'Admin LSP System',
            'email' => 'admin@proindo.id',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);

        $this->asesor = User::create([
            'full_name' => 'Asesor Lapangan',
            'email' => 'asesor@proindo.id',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'REG.2026.001',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-AUTO-01',
            'name' => 'Teknisi Otomotif Madya',
            'scheme_type' => 'KLASTER',
            'passing_grade_theory' => 75.00,
            'is_active' => true,
        ]);
    }

    public function test_registration_fails_without_token_or_with_invalid_token(): void
    {
        // 1. Missing token
        $response = $this->post(route('register'), [
            'full_name' => 'Calon Asesi Baru',
            'email' => 'calon_asesi1@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '081234567890',
            'place_of_birth' => 'Jakarta',
            'date_of_birth' => '2000-01-01',
            'gender' => 'L',
            'province_code' => '31',
            'city_code' => '3171',
            'district_code' => '317101',
            'subdistrict_code' => '3171011001',
            'postal_code' => '10110',
            'address' => 'Jl. Kebangsaan No. 10',
        ]);

        $response->assertSessionHasErrors('registration_token');

        // 2. Invalid token string
        $response = $this->post(route('register'), [
            'full_name' => 'Calon Asesi Baru',
            'email' => 'calon_asesi1@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '081234567890',
            'place_of_birth' => 'Jakarta',
            'date_of_birth' => '2000-01-01',
            'gender' => 'L',
            'province_code' => '31',
            'city_code' => '3171',
            'district_code' => '317101',
            'subdistrict_code' => '3171011001',
            'postal_code' => '10110',
            'address' => 'Jl. Kebangsaan No. 10',
            'registration_token' => 'salah1234',
        ]);

        $response->assertSessionHasErrors('registration_token');
    }

    public function test_registration_succeeds_with_valid_registration_token(): void
    {
        $validToken = RegistrationTokenService::getCurrentToken();
        $this->assertMatchesRegularExpression('/^proindo\d{4}$/', $validToken);

        $response = $this->post(route('register'), [
            'full_name' => 'Asesi Sukses Daftar',
            'email' => 'asesi_sukses@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '081234567890',
            'place_of_birth' => 'Bandung',
            'date_of_birth' => '2001-05-10',
            'gender' => 'P',
            'province_code' => '32',
            'city_code' => '3273',
            'district_code' => '327301',
            'subdistrict_code' => '3273011001',
            'postal_code' => '40115',
            'address' => 'Jl. Merdeka No. 45',
            'registration_token' => $validToken,
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'email' => 'asesi_sukses@test.com',
            'role' => 'ASESI',
        ]);
    }

    public function test_rotate_registration_token_command_updates_setting_with_valid_format(): void
    {
        $oldToken = RegistrationTokenService::getCurrentToken();

        $exitCode = $this->artisan('lsp:rotate-registration-token');
        $exitCode->assertExitCode(0);

        $newToken = Setting::get('asesi_registration_token');
        $this->assertNotNull($newToken);
        $this->assertMatchesRegularExpression('/^proindo\d{4}$/', $newToken);
    }

    public function test_token_is_displayed_on_admin_dashboard_and_asesor_jadwal_views(): void
    {
        $token = RegistrationTokenService::getCurrentToken();

        // Admin Dashboard
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee('Token Registrasi Asesi');
        $response->assertSee($token);

        // Asesor Jadwal
        $schedule = Schedule::create([
            'code' => 'SCH-TEST-01',
            'name' => 'Jadwal Asesmen Asesor 1',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['IA01'],
        ]);
        $schedule->assessors()->attach($this->asesor->id);

        $response = $this->actingAs($this->asesor)->get(route('asesor.jadwal.index'));
        $response->assertOk();
        $response->assertSee('Token Registrasi Asesi');
        $response->assertSee($token);
    }

    public function test_admin_appeal_reassessment_resets_appealing_candidate_and_unlocks_schedule_leaving_other_candidate_intact(): void
    {
        $schedule = Schedule::create([
            'code' => 'SCH-APPEAL-01',
            'name' => 'Jadwal Banding LSP',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Proindo',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'FINISHED',
            'is_closed' => true,
            'created_by' => $this->admin->id,
            'selected_instruments' => ['IA01', 'IA02'],
        ]);
        $schedule->assessors()->attach($this->asesor->id);

        // Candidate 1: Appealing candidate (Belum Kompeten)
        $asesi1 = User::create([
            'full_name' => 'Kandidat Yang Banding',
            'email' => 'kandidat_banding@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
            'phone' => '081234567891',
        ]);

        $assessment1 = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi1->id,
            'assessor_id' => $this->asesor->id,
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
            'status' => 'COMPLETED',
            'final_result' => 'BELUM_KOMPETEN',
            'final_result_at' => now(),
            'completed_at' => now(),
            'tujuan_asesmen' => 'Sertifikasi',
        ]);

        AssessmentProof::create([
            'assessment_id' => $assessment1->id,
            'proof_type' => 'KTP',
            'proof_category' => 'ADMINISTRATIF',
            'file_path' => 'proofs/ktp1.jpg',
            'original_filename' => 'ktp1.jpg',
        ]);

        Certificate::create([
            'assessment_id' => $assessment1->id,
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi1->id,
            'scheme_id' => $this->scheme->id,
            'certificate_number' => 'CERT/001/BK',
            'blanko_number' => 'BLK-001',
            'issued_date' => now()->toDateString(),
            'valid_until' => now()->addYears(3)->toDateString(),
            'file_path' => 'certificates/cert1.pdf',
        ]);

        // Candidate 2: Already completed & KOMPETEN
        $asesi2 = User::create([
            'full_name' => 'Kandidat Lulus Aman',
            'email' => 'kandidat_aman@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
            'phone' => '081234567892',
        ]);

        $assessment2 = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi2->id,
            'assessor_id' => $this->asesor->id,
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
            'status' => 'COMPLETED',
            'final_result' => 'KOMPETEN',
            'final_result_at' => now(),
            'completed_at' => now(),
            'tujuan_asesmen' => 'Sertifikasi',
        ]);

        AssessmentProof::create([
            'assessment_id' => $assessment2->id,
            'proof_type' => 'KTP',
            'proof_category' => 'ADMINISTRATIF',
            'file_path' => 'proofs/ktp2.jpg',
            'original_filename' => 'ktp2.jpg',
        ]);

        $cert2 = Certificate::create([
            'assessment_id' => $assessment2->id,
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi2->id,
            'scheme_id' => $this->scheme->id,
            'certificate_number' => 'CERT/002/KOMPETEN',
            'blanko_number' => 'BLK-002',
            'issued_date' => now()->toDateString(),
            'valid_until' => now()->addYears(3)->toDateString(),
            'file_path' => 'certificates/cert2.pdf',
        ]);

        // Create appeal for candidate 1
        $appeal = AssessmentAppeal::create([
            'assessment_id' => $assessment1->id,
            'appeal_date' => now()->toDateString(),
            'appeal_reasons' => 'Mohon evaluasi ulang penilaian observasi.',
            'status' => 'SUBMITTED',
        ]);

        // Admin decides: ACCEPTED (Lanjutkan dengan Asesmen Ulang)
        $response = $this->actingAs($this->admin)->post(route('admin.appeals.decide', $appeal), [
            'decision' => 'ACCEPTED',
        ]);

        $response->assertRedirect();
        $appeal->refresh();
        $assessment1->refresh();
        $assessment2->refresh();
        $schedule->refresh();

        // 1. Appeal is accepted
        $this->assertEquals('ACCEPTED', $appeal->status);

        // 2. Appealing candidate data is reset
        $this->assertEquals('DRAFT', $assessment1->status);
        $this->assertEquals('DRAFT', $assessment1->apl_status);
        $this->assertEquals('PENDING', $assessment1->admin_status);
        $this->assertEquals('BELUM_SELESAI', $assessment1->final_result);
        $this->assertNull($assessment1->completed_at);
        $this->assertStringContainsString('Dijadwalkan Asesmen Ulang', $assessment1->follow_up_action);

        // Appealing candidate's child tables are wiped
        $this->assertEquals(0, $assessment1->proofs()->count());
        $this->assertNull($assessment1->certificate);

        // 3. Schedule is unlocked
        $this->assertFalse((bool) $schedule->is_closed);
        $this->assertEquals('ACTIVE', $schedule->status);

        // 4. Other candidate (candidate 2) remains 100% UNTOUCHED
        $this->assertEquals('COMPLETED', $assessment2->status);
        $this->assertEquals('KOMPETEN', $assessment2->final_result);
        $this->assertEquals(1, $assessment2->proofs()->count());
        $this->assertNotNull($assessment2->certificate);
        $this->assertEquals('CERT/002/KOMPETEN', $assessment2->certificate->certificate_number);
    }

    public function test_admin_appeal_rejection_keeps_all_candidate_data_intact(): void
    {
        $schedule = Schedule::create([
            'code' => 'SCH-APPEAL-02',
            'name' => 'Jadwal Banding LSP 2',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Proindo',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'FINISHED',
            'is_closed' => true,
            'created_by' => $this->admin->id,
            'selected_instruments' => ['IA01'],
        ]);
        $schedule->assessors()->attach($this->asesor->id);

        $asesi = User::create([
            'full_name' => 'Kandidat Ditolak Banding',
            'email' => 'ditolak_appeal@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
            'phone' => '081234567893',
        ]);

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $this->asesor->id,
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
            'status' => 'COMPLETED',
            'final_result' => 'BELUM_KOMPETEN',
            'final_result_at' => now(),
            'completed_at' => now(),
        ]);

        AssessmentProof::create([
            'assessment_id' => $assessment->id,
            'proof_type' => 'KTP',
            'proof_category' => 'ADMINISTRATIF',
            'file_path' => 'proofs/ktp3.jpg',
            'original_filename' => 'ktp3.jpg',
        ]);

        $appeal = AssessmentAppeal::create([
            'assessment_id' => $assessment->id,
            'appeal_date' => now()->toDateString(),
            'appeal_reasons' => 'Alasan tidak berdasar.',
            'status' => 'SUBMITTED',
        ]);

        // Admin decides: REJECTED (Tidak)
        $response = $this->actingAs($this->admin)->post(route('admin.appeals.decide', $appeal), [
            'decision' => 'REJECTED',
        ]);

        $response->assertRedirect();
        $appeal->refresh();
        $assessment->refresh();
        $schedule->refresh();

        $this->assertEquals('REJECTED', $appeal->status);
        $this->assertEquals('COMPLETED', $assessment->status);
        $this->assertEquals('BELUM_KOMPETEN', $assessment->final_result);
        $this->assertEquals(1, $assessment->proofs()->count());
        $this->assertStringContainsString('Sanggahan Banding Ditolak', $assessment->follow_up_action);
        $this->assertTrue((bool) $schedule->is_closed);
    }
}
