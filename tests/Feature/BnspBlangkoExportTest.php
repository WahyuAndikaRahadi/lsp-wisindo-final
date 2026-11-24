<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Services\BnspReferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BnspBlangkoExportTest extends TestCase
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

        $this->admin = User::factory()->create(['role' => 'SUPER_ADMIN', 'status' => 'ACTIVE']);
        $this->asesor = User::factory()->create([
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'MET.000.009876 2023',
        ]);
        $this->asesi = User::factory()->create(['role' => 'ASESI', 'status' => 'ACTIVE']);

        $this->scheme = Scheme::create([
            'code' => 'SKM-OTO-01',
            'name' => 'Pemeliharaan Kendaraan Ringan Sistem Injeksi',
            'scheme_type' => 'KLASTER',
            'is_active' => true,
        ]);

        $this->schedule = Schedule::create([
            'code' => 'SCH-BNSP-'.uniqid(),
            'name' => 'Uji Sertifikasi SMK Angkasa Kalijati',
            'scheme_id' => $this->scheme->id,
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'tuk_name' => 'SMK Angkasa 1 Kalijati Subang',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['AK01', 'IA01', 'IA02'],
            'is_closed' => false,
        ]);

        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->asesor->id,
            'status' => 'COMPLETED',
            'apl_status' => 'APPROVED',
            'final_result' => 'K',
        ]);
    }

    public function test_bnsp_reference_service_loads_and_resolves_all_codes(): void
    {
        // 1. Verify JSON references load correctly
        $pendidikan = BnspReferenceService::getPendidikanList();
        $provinces = BnspReferenceService::getProvinsiList();
        $cities = BnspReferenceService::getAllCities();
        $jobs = BnspReferenceService::getPekerjaanList();
        $sumber = BnspReferenceService::getSumberAnggaranList();
        $kemen = BnspReferenceService::getKementerianList();

        $this->assertCount(10, $pendidikan);
        $this->assertGreaterThanOrEqual(34, count($provinces));
        $this->assertGreaterThanOrEqual(500, count($cities));
        $this->assertCount(89, $jobs);
        $this->assertCount(6, $sumber);
        $this->assertGreaterThanOrEqual(150, count($kemen));

        // 2. Test education resolution
        $this->assertEquals('1', BnspReferenceService::resolveEducationCode('SD'));
        $this->assertEquals('2', BnspReferenceService::resolveEducationCode('SMP'));
        $this->assertEquals('3', BnspReferenceService::resolveEducationCode('SMA/Sederajat'));
        $this->assertEquals('3', BnspReferenceService::resolveEducationCode('SMK'));
        $this->assertEquals('7', BnspReferenceService::resolveEducationCode('S1'));
        $this->assertEquals('10', BnspReferenceService::resolveEducationCode('D1'));

        // 3. Test job resolution
        $this->assertEquals('3', BnspReferenceService::resolveJobCode('Pelajar / Siswa'));
        $this->assertEquals('3', BnspReferenceService::resolveJobCode('Pelajar/Mahasiswa'));
        $this->assertEquals('35', BnspReferenceService::resolveJobCode('Mekanik'));
        $this->assertEquals('5', BnspReferenceService::resolveJobCode('PNS'));
        $this->assertEquals('88', BnspReferenceService::resolveJobCode('Wirausaha'));

        // 4. Test city & province resolution with keywords
        $subang = BnspReferenceService::resolveCityAndProvince(null, null, 'Jl. Raya Kalijati Barat, Subang');
        $this->assertEquals('3213', $subang['kode_kota']);
        $this->assertEquals('32', $subang['kode_provinsi']);

        $bekasi = BnspReferenceService::resolveCityAndProvince(null, null, 'Harapan Indah, Kota Bekasi');
        $this->assertEquals('3275', $bekasi['kode_kota']);
        $this->assertEquals('32', $bekasi['kode_provinsi']);
    }

    public function test_asesi_profile_saves_city_province_and_job_codes(): void
    {
        $response = $this->actingAs($this->asesi)
            ->post(route('asesi.profile.update'), [
                'full_name' => 'Budi Santoso Siswa',
                'phone' => '081234567890',
                'nik' => '3213010101010001',
                'tempat_lahir' => 'Subang',
                'tanggal_lahir' => '2006-05-10',
                'jenis_kelamin' => 'L',
                'kebangsaan' => 'Indonesia',
                'pendidikan_terakhir' => 'SMA/Sederajat',
                'alamat_rumah' => 'Jl. Kalijati No. 12 Subang',
                'kode_provinsi' => '32',
                'nama_provinsi' => 'Jawa Barat',
                'kode_kota' => '3213',
                'nama_kota' => 'Kab. Subang',
                'kode_pos' => '41271',
                'jabatan' => 'Pelajar/Mahasiswa',
                'kode_pekerjaan' => '3',
                'signature_data' => 'data:image/png;base64,'.base64_encode('sample signature image data'),
            ]);

        $response->assertRedirect(route('asesi.profile.edit'));
        $response->assertSessionHas('success');

        $profile = $this->asesi->fresh()->profile;
        $this->assertNotNull($profile);
        $this->assertEquals('32', $profile->kode_provinsi);
        $this->assertEquals('3213', $profile->kode_kota);
        $this->assertEquals('3', $profile->kode_pekerjaan);
        $this->assertEquals('3', $profile->effective_kode_pendidikan);
    }

    public function test_dokumen_muk_show_page_renders_modal_for_bnsp_export(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dokumen-muk.show', $this->schedule));

        $response->assertOk();
        $response->assertSee('Permohonan Blangko (Excel)');
        $response->assertSee('exportModalOpen');
        $response->assertSee('Ekspor Permohonan Blangko BNSP');
        $response->assertSee('sumberAnggaran');
        $response->assertSee('kementerianCode');
        $response->assertSee('Ketentuan Kolom Kode Jadwal');
    }

    public function test_export_excel_populates_bnsp_codes_and_leaves_schedule_code_blank(): void
    {
        // 1. Setup asesi profile with Subang and Pelajar/Mahasiswa
        $this->asesi->profile()->create([
            'nik' => '3213010101010001',
            'tempat_lahir' => 'Subang',
            'tanggal_lahir' => '2006-05-10',
            'jenis_kelamin' => 'L',
            'kebangsaan' => 'Indonesia',
            'pendidikan_terakhir' => 'SMA/Sederajat',
            'alamat_rumah' => 'Jl. Kalijati No. 12 Subang',
            'kode_provinsi' => '32',
            'nama_provinsi' => 'Jawa Barat',
            'kode_kota' => '3213',
            'nama_kota' => 'Kab. Subang',
            'kode_pos' => '41271',
            'jabatan' => 'Pelajar/Mahasiswa',
            'kode_pekerjaan' => '3',
        ]);

        // 2. Export excel with Sumber Anggaran = 1 (APBN) and Kementerian = 11 (Kemendikbud)
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dokumen-muk.export-excel-biodata', [
                'schedule' => $this->schedule->id,
                'kode_sumber_anggaran' => '1',
                'kode_kementerian' => '11',
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');

        $content = $response->getContent();

        // 3. Verify XML content contains candidate info and proper BNSP codes
        $this->assertStringContainsString('3213010101010001', $content); // NIK
        $this->assertStringContainsString('<Data ss:Type="String">3213</Data>', $content); // KODE KOTA
        $this->assertStringContainsString('<Data ss:Type="String">32</Data>', $content); // KODE PROVINSI
        $this->assertStringContainsString('<Data ss:Type="String">3</Data>', $content); // KODE PENDIDIKAN & PEKERJAAN
        $this->assertStringContainsString('<Data ss:Type="String">1</Data>', $content); // KODE SUMBER ANGGARAN
        $this->assertStringContainsString('<Data ss:Type="String">11</Data>', $content); // KODE KEMENTERIAN

        // 4. Verify schedule remembered the chosen parameters
        $this->schedule->refresh();
        $this->assertEquals('1', $this->schedule->kode_sumber_anggaran);
        $this->assertEquals('11', $this->schedule->kode_kementerian);
    }
}
