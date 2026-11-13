<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\RegistrationTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationWilayahAndNumericValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_redirects_to_login_with_flash_message(): void
    {
        $response = $this->post('/register', [
            'full_name' => 'Budi Santoso',
            'email' => 'budi@test.com',
            'phone' => '081234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'registration_token' => RegistrationTokenService::getCurrentToken(),
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success', 'Anda sudah mendaftar, silahkan login.');

        $this->assertDatabaseHas('users', [
            'email' => 'budi@test.com',
            'full_name' => 'Budi Santoso',
            'role' => 'ASESI',
        ]);
    }

    public function test_login_page_renders_registration_success_alert(): void
    {
        $response = $this->withSession(['success' => 'Anda sudah mendaftar, silahkan login.'])
            ->get('/login');

        $response->assertOk();
        $response->assertSee('Pendaftaran Berhasil');
        $response->assertSee('Anda sudah mendaftar, silahkan login.');
    }

    public function test_registration_phone_rejects_non_numeric_characters(): void
    {
        $response = $this->post('/register', [
            'full_name' => 'Budi Santoso',
            'email' => 'budi2@test.com',
            'phone' => '0812-3456-ABCD',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['phone']);
        $this->assertDatabaseMissing('users', ['email' => 'budi2@test.com']);
    }

    public function test_wilayah_api_endpoints(): void
    {
        // 1. All wilayah
        $resAll = $this->getJson('/api/wilayah/all');
        $resAll->assertOk();
        $allData = $resAll->json();
        $this->assertIsArray($allData);
        $this->assertCount(38, $allData['provinces']); // 38 provinces
        $this->assertNotEmpty($allData['regencies']);

        // 2. Provinces
        $resProv = $this->getJson('/api/wilayah/provinces');
        $resProv->assertOk();
        $provData = $resProv->json();
        $this->assertCount(38, $provData);
        $this->assertEquals('11', $provData[0]['code']);
        $this->assertEquals('Aceh', $provData[0]['name']);

        // 3. Regencies for Jawa Barat (code: 32)
        $resReg = $this->getJson('/api/wilayah/regencies/32');
        $resReg->assertOk();
        $regData = $resReg->json();
        $this->assertNotEmpty($regData);
        $names = array_column($regData, 'name');
        $this->assertContains('Kabupaten Karawang', $names);
        $this->assertContains('Kota Bandung', $names);
    }

    public function test_asesi_profile_numeric_validation_rejects_alphanumeric_inputs(): void
    {
        $user = User::factory()->create([
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($user)->post(route('asesi.profile.update'), [
            'full_name' => 'Asesi Uji',
            'nik' => '1234ABCD5678', // non-numeric
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Kabupaten Karawang',
            'tanggal_lahir' => '2000-01-01',
            'kebangsaan' => 'Indonesia',
            'pendidikan_terakhir' => 'SMA/Sederajat',
            'alamat_rumah' => 'Jl. Merdeka No 1',
            'kode_pos' => '1753O', // contains letter 'O'
            'phone' => '0812-3456-7890', // contains hyphens
            'no_telepon_rumah' => '021-ABCDEF',
            'no_telepon_kantor' => '021-XYZ123',
            'jabatan' => 'Pelajar / Siswa',
            'signature_data' => 'data:image/png;base64,sample',
        ]);

        $response->assertSessionHasErrors(['nik', 'kode_pos', 'phone', 'no_telepon_rumah', 'no_telepon_kantor']);
    }

    public function test_asesi_profile_accepts_clean_integers_and_standardized_tempat_lahir(): void
    {
        $user = User::factory()->create([
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($user)->post(route('asesi.profile.update'), [
            'full_name' => 'Asesi Valid',
            'nik' => '3215012304990001',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Kabupaten Karawang',
            'tanggal_lahir' => '2000-01-01',
            'kebangsaan' => 'Indonesia',
            'pendidikan_terakhir' => 'SMA/Sederajat',
            'alamat_rumah' => 'Jl. Merdeka No 1',
            'kode_pos' => '17530',
            'phone' => '081234567890',
            'no_telepon_rumah' => '0218901234',
            'no_telepon_kantor' => '0218905678',
            'nama_institusi_perusahaan' => 'SMKN 1 Cikarang Barat',
            'jabatan' => 'Pelajar / Siswa',
            'alamat_kantor' => 'Jl. Industri No 5',
            'signature_data' => 'data:image/png;base64,sample',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('asesi.profile.edit'));

        $this->assertDatabaseHas('m_user_profiles', [
            'user_id' => $user->id,
            'nik' => '3215012304990001',
            'tempat_lahir' => 'Kabupaten Karawang',
            'kode_pos' => '17530',
            'no_telepon_rumah' => '0218901234',
            'no_telepon_kantor' => '0218905678',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'full_name' => 'Asesi Valid',
            'phone' => '081234567890',
        ]);
    }

    public function test_asesor_profile_numeric_validation(): void
    {
        $user = User::factory()->create([
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
        ]);

        // 1. Invalid non-integer
        $resInvalid = $this->actingAs($user)->post(route('asesor.profile.update'), [
            'full_name' => 'Asesor Uji',
            'phone' => '0812-XYZ',
            'nik' => '321501-ABC',
            'kode_pos' => '175-30',
        ]);
        $resInvalid->assertSessionHasErrors(['phone', 'nik', 'kode_pos']);

        // 2. Valid clean integer
        $resValid = $this->actingAs($user)->post(route('asesor.profile.update'), [
            'full_name' => 'Asesor Uji Valid',
            'phone' => '081298765432',
            'nik' => '3215012304850002',
            'kode_pos' => '17530',
        ]);
        $resValid->assertSessionHasNoErrors();
        $resValid->assertRedirect(route('asesor.profile.edit'));
    }

    public function test_asesi_profile_rejects_empty_signature_when_no_signature_exists(): void
    {
        $user = User::factory()->create([
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($user)->post(route('asesi.profile.update'), [
            'full_name' => 'Asesi Tanpa TTD',
            'nik' => '3215012304990001',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Kabupaten Karawang',
            'tanggal_lahir' => '2000-01-01',
            'kebangsaan' => 'Indonesia',
            'pendidikan_terakhir' => 'SMA/Sederajat',
            'alamat_rumah' => 'Jl. Merdeka No 1',
            'kode_pos' => '17530',
            'phone' => '081234567890',
            'jabatan' => 'Pelajar / Siswa',
            'signature_data' => '', // Empty signature
        ]);

        $response->assertSessionHasErrors(['signature_data']);
    }

    public function test_asesi_profile_accepts_when_optional_employment_fields_are_omitted(): void
    {
        $user = User::factory()->create([
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $validPng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($user)->post(route('asesi.profile.update'), [
            'full_name' => 'Asesi Fresh Graduate',
            'nik' => '3215012304990001',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Kabupaten Karawang',
            'tanggal_lahir' => '2000-01-01',
            'kebangsaan' => 'Indonesia',
            'pendidikan_terakhir' => 'SMA/Sederajat',
            'alamat_rumah' => 'Jl. Merdeka No 1',
            'kode_pos' => '17530',
            'phone' => '081234567890',
            'jabatan' => 'Belum Bekerja / Fresh Graduate', // Required
            // nama_institusi_perusahaan, alamat_kantor, no_telepon_kantor omitted
            'signature_data' => $validPng,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('asesi.profile.edit'));

        $this->assertDatabaseHas('m_user_profiles', [
            'user_id' => $user->id,
            'nik' => '3215012304990001',
            'jabatan' => 'Belum Bekerja / Fresh Graduate',
            'nama_institusi_perusahaan' => null,
            'alamat_kantor' => null,
            'no_telepon_kantor' => null,
        ]);
    }

    public function test_apl_submission_rejects_when_signature_is_missing(): void
    {
        $asesi = User::factory()->create(['role' => 'ASESI']);
        $scheme = Scheme::first() ?? Scheme::create([
            'code' => 'SCH-TEST-01',
            'name' => 'Skema Test',
            'scheme_type' => 'KKNI',
            'passing_grade_theory' => 70,
            'is_active' => true,
        ]);
        $schedule = Schedule::create([
            'scheme_id' => $scheme->id,
            'name' => 'Jadwal Test APL',
            'code' => 'SCH-'.uniqid(),
            'tuk_name' => 'TUK Test',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'IA01'],
            'status' => 'ACTIVE',
            'created_by' => $asesi->id,
        ]);
        $assessment = Assessment::create([
            'asesi_id' => $asesi->id,
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'apl_status' => 'DRAFT',
        ]);

        // Complete profile first, but with NO signature
        UserProfile::create([
            'user_id' => $asesi->id,
            'nik' => '3215012304990001',
            'tempat_lahir' => 'Kabupaten Karawang',
            'tanggal_lahir' => '2000-01-01',
            'jenis_kelamin' => 'L',
            'kebangsaan' => 'Indonesia',
            'alamat_rumah' => 'Jl. Merdeka No 1',
            'kode_pos' => '17530',
            'pendidikan_terakhir' => 'SMA/Sederajat',
            'jabatan' => 'Pelajar / Siswa',
            'signature_data' => null, // No signature in profile
        ]);

        Storage::fake('local');

        $response = $this->actingAs($asesi)->post(route('asesi.assessment.apl.store', $assessment), [
            'proofs' => [
                'IJAZAH_SMK' => UploadedFile::fake()->create('ijazah.pdf', 500, 'application/pdf'),
                'KTP' => UploadedFile::fake()->create('ktp.jpg', 300, 'image/jpeg'),
                'PAS_FOTO' => UploadedFile::fake()->create('foto.jpg', 300, 'image/jpeg'),
            ],
            'statement_integrity' => '1',
            'signature_data' => '', // NO signature in request either!
        ]);

        $response->assertSessionHasErrors(['signature_data']);
        $assessment->refresh();
        $this->assertEquals('DRAFT', $assessment->apl_status);
        $this->assertNull($assessment->asesi_signature);
    }

    public function test_apl_submission_rejects_when_apl01_profile_is_incomplete(): void
    {
        $asesi = User::factory()->create(['role' => 'ASESI']);
        $scheme = Scheme::first() ?? Scheme::create([
            'code' => 'SCH-TEST-02',
            'name' => 'Skema Test 2',
            'scheme_type' => 'KKNI',
            'passing_grade_theory' => 70,
            'is_active' => true,
        ]);
        $schedule = Schedule::create([
            'scheme_id' => $scheme->id,
            'name' => 'Jadwal Test APL 2',
            'code' => 'SCH-'.uniqid(),
            'tuk_name' => 'TUK Test',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'IA01'],
            'status' => 'ACTIVE',
            'created_by' => $asesi->id,
        ]);
        $assessment = Assessment::create([
            'asesi_id' => $asesi->id,
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'apl_status' => 'DRAFT',
        ]);

        // Profile is missing NIK, Tempat Lahir, etc.
        UserProfile::create([
            'user_id' => $asesi->id,
            'nik' => null, // INCOMPLETE
            'tempat_lahir' => null,
            'alamat_rumah' => null,
        ]);

        Storage::fake('local');
        $validPng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($asesi)->post(route('asesi.assessment.apl.store', $assessment), [
            'proofs' => [
                'IJAZAH_SMK' => UploadedFile::fake()->create('ijazah.pdf', 500, 'application/pdf'),
                'KTP' => UploadedFile::fake()->create('ktp.jpg', 300, 'image/jpeg'),
                'PAS_FOTO' => UploadedFile::fake()->create('foto.jpg', 300, 'image/jpeg'),
            ],
            'statement_integrity' => '1',
            'signature_data' => $validPng,
        ]);

        $response->assertSessionHasErrors(['profile_incomplete']);
        $assessment->refresh();
        $this->assertEquals('DRAFT', $assessment->apl_status);
    }
}
