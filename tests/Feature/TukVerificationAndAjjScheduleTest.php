<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\Tuk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TukVerificationAndAjjScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Scheme $schemeA;

    protected Scheme $schemeB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create([
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);

        $this->schemeA = Scheme::create([
            'name' => 'Receptionist Hotel',
            'code' => 'SKM-FO-03',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);

        $this->schemeB = Scheme::create([
            'name' => 'Chef de Partie Hotel',
            'code' => 'SKM-CDP-01',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_tuk_list_and_create_tuk_with_admin_account(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.tuks.index'));
        $response->assertOk();
        $response->assertSee('Tempat Uji Kompetensi (TUK)');

        $createResponse = $this->actingAs($this->admin)->get(route('admin.tuks.create'));
        $createResponse->assertOk();
        $createResponse->assertSee('Tambah TUK Baru');

        $postData = [
            'name' => 'TUK Hotel Santika Premiere',
            'code' => 'TUK-STK-01',
            'type' => 'TEMPAT_KERJA',
            'address' => 'Jl. Hayam Wuruk No. 125, Jakarta Barat',
            'kode_provinsi' => '31',
            'nama_provinsi' => 'DKI Jakarta',
            'kode_kota' => '3173',
            'nama_kota' => 'Kota Jakarta Barat',
            'postal_code' => '11180',
            'phone' => '021-2600888',
            'email' => 'tuk@santika.com',
            'pic_name' => 'Bambang Wijaya',
            'pic_phone' => '081234567890',
            'pic_position' => 'Training Manager',
            'scheme_ids' => [$this->schemeA->id],
            'admin_full_name' => 'Admin TUK Santika',
            'admin_email' => 'admin.tuk@santika.com',
            'admin_password' => 'Password123!',
        ];

        $storeResponse = $this->actingAs($this->admin)->post(route('admin.tuks.store'), $postData);
        $storeResponse->assertRedirect(route('admin.tuks.index'));

        $this->assertDatabaseHas('m_tuks', [
            'name' => 'TUK Hotel Santika Premiere',
            'code' => 'TUK-STK-01',
            'pic_name' => 'Bambang Wijaya',
            'verification_status' => 'UNSUBMITTED',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin.tuk@santika.com',
            'role' => 'TUK_ADMIN',
        ]);

        $tuk = Tuk::where('code', 'TUK-STK-01')->first();
        $this->assertNotNull($tuk);
        $this->assertTrue($tuk->schemes()->where('m_schemes.id', $this->schemeA->id)->exists());
    }

    public function test_admin_can_create_tuk_with_only_name_and_login_credentials(): void
    {
        $postData = [
            'name' => 'TUK Mandiri Sunset Bali',
            'admin_email' => 'admin.bali@wisindo.id',
            'admin_password' => 'wisindo123',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.tuks.store'), $postData);
        $response->assertRedirect(route('admin.tuks.index'));

        $this->assertDatabaseHas('m_tuks', [
            'name' => 'TUK Mandiri Sunset Bali',
            'verification_status' => 'UNSUBMITTED',
            'address' => null,
            'phone' => null,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin.bali@wisindo.id',
            'role' => 'TUK_ADMIN',
        ]);
    }

    public function test_tuk_admin_login_and_gated_middleware_redirect_when_not_verified(): void
    {
        $tuk = Tuk::create([
            'name' => 'TUK Grand Melia Jakarta',
            'code' => 'TUK-GM-02',
            'type' => 'TEMPAT_KERJA',
            'address' => 'Jl. H.R. Rasuna Said Kav. X-0',
            'phone' => '021-5268080',
            'pic_name' => 'Siti Rahma',
            'pic_phone' => '081399887766',
            'pic_position' => 'HR Director',
            'verification_status' => 'UNSUBMITTED',
            'is_active' => true,
        ]);

        $tukAdmin = User::create([
            'full_name' => 'Admin TUK Melia',
            'email' => 'admin@melia.id',
            'password' => bcrypt('password123'),
            'role' => 'TUK_ADMIN',
            'status' => 'ACTIVE',
            'tuk_id' => $tuk->id,
        ]);

        // Attempt to access gated schedule route before verification
        $response = $this->actingAs($tukAdmin)->get(route('tuk.jadwal.index'));
        $response->assertRedirect(route('tuk.data.edit'));
        $response->assertSessionHas('warning');

        // Access data edit page should succeed
        $editResponse = $this->actingAs($tukAdmin)->get(route('tuk.data.edit'));
        $editResponse->assertOk();
        $editResponse->assertSee('Data &amp; Verifikasi TUK Asesmen Jarak Jauh (AJJ)', false);
    }

    public function test_tuk_admin_can_submit_evidence_and_admin_can_verify(): void
    {
        $tuk = Tuk::create([
            'name' => 'TUK Aston Priority Simatupang',
            'code' => 'TUK-AST-03',
            'type' => 'SEWAKTU',
            'address' => 'Jl. Let. Jend. T.B. Simatupang Kav. 9',
            'phone' => '021-78838777',
            'pic_name' => 'Andi Wijaya',
            'pic_phone' => '081122334455',
            'pic_position' => 'General Manager',
            'verification_status' => 'UNSUBMITTED',
            'is_active' => true,
        ]);

        $tukAdmin = User::create([
            'full_name' => 'Admin TUK Aston',
            'email' => 'admin@aston.id',
            'password' => bcrypt('password123'),
            'role' => 'TUK_ADMIN',
            'status' => 'ACTIVE',
            'tuk_id' => $tuk->id,
        ]);

        $fakePhoto1 = UploadedFile::fake()->image('room1.jpg', 600, 400);
        $fakePhoto2 = UploadedFile::fake()->image('internet.jpg', 600, 400);

        $updateData = [
            'name' => 'TUK Aston Priority Simatupang',
            'type' => 'SEWAKTU',
            'address' => 'Jl. Let. Jend. T.B. Simatupang Kav. 9',
            'kode_provinsi' => '31',
            'nama_provinsi' => 'DKI Jakarta',
            'kode_kota' => '3174',
            'nama_kota' => 'Kota Jakarta Selatan',
            'phone' => '021-78838777',
            'pic_name' => 'Andi Wijaya',
            'pic_phone' => '081122334455',
            'pic_position' => 'General Manager',
            'scheme_ids' => [$this->schemeA->id, $this->schemeB->id],
            'room_photos' => [$fakePhoto1],
            'internet_data_photos' => [$fakePhoto2],
        ];

        $submitResponse = $this->actingAs($tukAdmin)->put(route('tuk.data.update'), $updateData);
        $submitResponse->assertRedirect(route('tuk.data.edit'));

        $tuk->refresh();
        $this->assertEquals('PENDING_VERIFICATION', $tuk->verification_status);
        $this->assertNotEmpty($tuk->room_photos);

        // Admin reviews TUK
        $reviewPage = $this->actingAs($this->admin)->get(route('admin.tuks.review', $tuk));
        $reviewPage->assertOk();
        $reviewPage->assertSee('Verifikasi Kelayakan AJJ');

        // Admin requests revision
        $rejectResponse = $this->actingAs($this->admin)->post(route('admin.tuks.reject-revision', $tuk), [
            'verification_notes' => 'Foto ruang uji kurang terang, mohon perbarui foto ruangan.',
        ]);
        $rejectResponse->assertRedirect(route('admin.tuks.index'));

        $tuk->refresh();
        $this->assertEquals('REVISION', $tuk->verification_status);
        $this->assertStringContainsString('Foto ruang uji kurang terang', $tuk->verification_notes);

        // Admin verifies TUK with schemeA approved
        $verifyResponse = $this->actingAs($this->admin)->post(route('admin.tuks.verify', $tuk), [
            'scheme_ids' => [$this->schemeA->id],
            'admin_notes' => 'Fasilitas telah diperiksa dan disetujui untuk skema Receptionist Hotel.',
        ]);
        $verifyResponse->assertRedirect(route('admin.tuks.index'));

        $tuk->refresh();
        $this->assertEquals('VERIFIED', $tuk->verification_status);
        $this->assertTrue($tuk->isVerified());
        $this->assertTrue($tuk->schemes()->where('m_schemes.id', $this->schemeA->id)->wherePivot('is_verified', true)->exists());

        // Now TUK Admin can access jadwal index without redirection
        $tukAdmin->refresh();
        $jadwalResponse = $this->actingAs($tukAdmin)->get(route('tuk.jadwal.index'));
        $jadwalResponse->assertOk();
        $jadwalResponse->assertSee('Jadwal Asesmen TUK');
    }

    public function test_admin_creates_schedule_with_verified_tuk_and_meeting_link_displayed_to_all_roles(): void
    {
        $tuk = Tuk::create([
            'name' => 'TUK Aryaduta Hotel Bandung',
            'code' => 'TUK-ARY-04',
            'type' => 'TEMPAT_KERJA',
            'address' => 'Jl. Sumatera No. 51, Citarum, Bandung Wetan',
            'kode_provinsi' => '32',
            'nama_provinsi' => 'Jawa Barat',
            'kode_kota' => '3273',
            'nama_kota' => 'Kota Bandung',
            'phone' => '022-4211234',
            'pic_name' => 'Rina Sugiarto',
            'pic_phone' => '081299001122',
            'pic_position' => 'Executive Housekeeper',
            'verification_status' => 'VERIFIED',
            'is_active' => true,
        ]);
        $tuk->schemes()->attach($this->schemeA->id, ['is_verified' => true]);

        $tukAdmin = User::create([
            'full_name' => 'Admin TUK Aryaduta',
            'email' => 'admin@aryaduta.id',
            'password' => bcrypt('password123'),
            'role' => 'TUK_ADMIN',
            'status' => 'ACTIVE',
            'tuk_id' => $tuk->id,
        ]);

        $assessor = User::factory()->create([
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'full_name' => 'Budi Santoso, S.Par.',
        ]);
        $assessor->profile()->create([
            'signature_data' => 'data:image/png;base64,fakeSignatureData',
        ]);

        $meetingUrl = 'https://us02web.zoom.us/j/9876543210?pwd=testpassword';

        // Admin creates schedule
        $scheduleData = [
            'name' => 'Uji Sertifikasi Front Office Receptionist Batch 1',
            'scheme_id' => $this->schemeA->id,
            'tuk_id' => $tuk->id,
            'meeting_link' => $meetingUrl,
            'date' => now()->addDays(2)->format('Y-m-d'),
            'start_time' => '08:30',
            'end_time' => '16:00',
            'assessor_ids' => [$assessor->id],
            'code' => 'WIS-REC-01',
        ];

        $storeResponse = $this->actingAs($this->admin)->post(route('admin.jadwal.store'), $scheduleData);
        $storeResponse->assertRedirect(route('admin.jadwal.index'));

        $schedule = Schedule::where('code', 'WIS-REC-01')->first();
        $this->assertNotNull($schedule);
        $this->assertEquals($tuk->id, $schedule->tuk_id);
        $this->assertEquals($tuk->name, $schedule->tuk_name);
        $this->assertEquals($meetingUrl, $schedule->meeting_link);
        $this->assertEquals('Kota Bandung', $schedule->nama_kota);

        // 1. Admin show view displays meeting button
        $adminShow = $this->actingAs($this->admin)->get(route('admin.jadwal.show', $schedule));
        $adminShow->assertOk();
        $adminShow->assertSee($meetingUrl);
        $adminShow->assertSee('Masuk Zoom / Meet AJJ');

        // 2. TUK Admin show view displays meeting button
        $tukShow = $this->actingAs($tukAdmin)->get(route('tuk.jadwal.show', $schedule));
        $tukShow->assertOk();
        $tukShow->assertSee($meetingUrl);
        $tukShow->assertSee('Masuk Ruang Zoom / Meet AJJ');

        // 3. Assessor schedule list displays meeting button
        $assessorIndex = $this->actingAs($assessor)->get(route('asesor.jadwal.index'));
        $assessorIndex->assertOk();
        $assessorIndex->assertSee($meetingUrl);
        $assessorIndex->assertSee('Meet AJJ');

        // 4. Asesi ruang uji hub displays meeting button
        $asesi = User::factory()->create([
            'role' => 'ASESI',
            'status' => 'ACTIVE',
            'full_name' => 'Dewi Lestari',
        ]);
        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'scheme_id' => $this->schemeA->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $assessor->id,
            'status' => 'REGISTERED',
            'apl_status' => 'APPROVED',
            'registered_at' => now(),
        ]);

        $asesiRuangUji = $this->actingAs($asesi)->get(route('asesi.assessment.ruang-uji', $assessment));
        $asesiRuangUji->assertOk();
        $asesiRuangUji->assertSee($meetingUrl);
        $asesiRuangUji->assertSee('Masuk Ruang Zoom / Meet AJJ');

        // 5. Assessor schedule navigation displays meeting button once APL is reviewed
        $assessorMapa = $this->actingAs($assessor)->get(route('asesor.schedule.mapa', $schedule));
        $assessorMapa->assertOk();
        $assessorMapa->assertSee($meetingUrl);
        $assessorMapa->assertSee('Ruang Meet AJJ');
    }

    public function test_wilayah_api_and_tuk_dropdown_integration(): void
    {
        // 1. Provinces API returns provinces list
        $provResponse = $this->get('/api/wilayah/provinces');
        $provResponse->assertOk();
        $provResponse->assertJsonFragment(['code' => '32', 'name' => 'Jawa Barat']);

        // 2. Regencies API returns regencies for province 32
        $regResponse = $this->get('/api/wilayah/regencies/32');
        $regResponse->assertOk();
        $regResponse->assertJsonFragment(['name' => 'Kabupaten Bekasi']);

        // 3. TUK data edit view contains wilayahDropdown Alpine component
        $tuk = Tuk::create([
            'name' => 'TUK API Test',
            'code' => 'TUK-API-01',
            'type' => 'MANDIRI',
            'address' => 'Jl. Pariwisata Test',
            'phone' => '081299990000',
            'email' => 'api.test@tuk.com',
            'pic_name' => 'PIC Test',
            'pic_phone' => '081299990001',
            'pic_position' => 'Manager',
            'verification_status' => 'UNSUBMITTED',
            'is_active' => true,
        ]);

        $tukAdmin = User::factory()->create([
            'role' => 'TUK_ADMIN',
            'status' => 'ACTIVE',
            'tuk_id' => $tuk->id,
        ]);

        $editView = $this->actingAs($tukAdmin)->get(route('tuk.data.edit'));
        $editView->assertOk();
        $editView->assertSee('wilayahDropdown');
        $editView->assertSee('name="kode_provinsi"', false);
        $editView->assertSee('name="nama_provinsi"', false);
        $editView->assertSee('name="kode_kota"', false);
        $editView->assertSee('name="nama_kota"', false);

        // 4. Update TUK data with selected province & city
        $updateResponse = $this->actingAs($tukAdmin)->put(route('tuk.data.update'), [
            'name' => 'TUK API Test Updated',
            'type' => 'MANDIRI',
            'address' => 'Jl. Boulevard Wisata No. 99',
            'kode_provinsi' => '32',
            'nama_provinsi' => 'Jawa Barat',
            'kode_kota' => '32.16',
            'nama_kota' => 'Kabupaten Bekasi',
            'postal_code' => '17530',
            'phone' => '081299990000',
            'email' => 'api.test@tuk.com',
            'pic_name' => 'PIC Test Updated',
            'pic_phone' => '081299990001',
            'pic_position' => 'Manager Operasional',
            'scheme_ids' => [$this->schemeA->id],
        ]);

        $updateResponse->assertRedirect(route('tuk.data.edit'));

        $this->assertDatabaseHas('m_tuks', [
            'id' => $tuk->id,
            'name' => 'TUK API Test Updated',
            'kode_provinsi' => '32',
            'nama_provinsi' => 'Jawa Barat',
            'kode_kota' => '32.16',
            'nama_kota' => 'Kabupaten Bekasi',
            'postal_code' => '17530',
            'verification_status' => 'PENDING_VERIFICATION',
        ]);
    }
}
