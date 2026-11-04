<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Certificate;
use App\Models\CompetencyUnit;
use App\Models\Kuk;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\UnitElement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProfileAndCertificateWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $asesi;

    protected Scheme $scheme;

    protected Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create([
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
            'email' => 'admin.utama@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->asesi = User::factory()->create([
            'role' => 'ASESI',
            'status' => 'ACTIVE',
            'full_name' => 'Budi Santoso',
            'email' => 'budi.santoso@example.com',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-TEST-01',
            'name' => 'Skema Servis Otomotif',
            'scheme_type' => 'KLASTER',
            'is_active' => true,
            'passing_grade_theory' => 75.00,
        ]);

        $this->schedule = Schedule::create([
            'code' => 'SCH-TEST-CERT-1',
            'name' => 'Jadwal Sertifikasi Angkatan 1',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA05'],
            'created_by' => $this->admin->id,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_admin_can_view_and_update_profile_signature_and_password(): void
    {
        // 1. Visit profile page
        $resView = $this->actingAs($this->admin)->get(route('admin.profile.edit'));
        $resView->assertOk();
        $resView->assertSee('Pengaturan Profil & Tanda Tangan Admin', false);
        $resView->assertSee('admin.utama@example.com');

        // 2. Update profile with signature and new password
        $dummySig = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $resUpdate = $this->actingAs($this->admin)->post(route('admin.profile.update'), [
            'full_name' => 'Dr. H. Admin LSP, M.T.',
            'email' => 'admin.baru@example.com',
            'phone' => '081234567890',
            'jabatan' => 'Manajer Sertifikasi LSP',
            'nama_institusi_perusahaan' => 'LSP Praktisi Otomotif Indonesia',
            'signature_data' => $dummySig,
            'current_password' => 'password123',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $resUpdate->assertRedirect(route('admin.profile.edit'));
        $resUpdate->assertSessionHas('success');

        $this->admin->refresh();
        $this->assertEquals('Dr. H. Admin LSP, M.T.', $this->admin->full_name);
        $this->assertEquals('admin.baru@example.com', $this->admin->email);
        $this->assertEquals('081234567890', $this->admin->phone);
        $this->assertTrue(Hash::check('newpassword123', $this->admin->password));

        // Signature must be persisted in m_user_profiles
        $profile = $this->admin->profile;
        $this->assertNotNull($profile);
        $this->assertEquals($dummySig, $profile->signature_data);
        $this->assertEquals('Manajer Sertifikasi LSP', $profile->jabatan);
    }

    public function test_scheme_displays_correct_unit_count_and_super_admin_can_manage_units_elements_and_kuks(): void
    {
        // Seed 2 units under scheme
        $unit1 = CompetencyUnit::create([
            'scheme_id' => $this->scheme->id,
            'code' => 'OTO.SM01.001.01',
            'title' => 'Mengikuti Prosedur Keselamatan Kerja',
            'standard_reference' => 'SKKNI',
            'sequence' => 1,
        ]);

        $unit2 = CompetencyUnit::create([
            'scheme_id' => $this->scheme->id,
            'code' => 'OTO.SM01.002.01',
            'title' => 'Menggunakan dan Memelihara Peralatan Tangan',
            'standard_reference' => 'SKKNI',
            'sequence' => 2,
        ]);

        // 1. Check schemes index: must display '2 Unit' (not 0 Unit)
        $resIndex = $this->actingAs($this->admin)->get(route('admin.schemes.index'));
        $resIndex->assertOk();
        $resIndex->assertSee('2 Unit');
        $resIndex->assertDontSee('0 Unit');

        // 2. Super admin adds unit 3
        $resStoreUnit = $this->actingAs($this->admin)->post(route('admin.schemes.units.store', $this->scheme->id), [
            'code' => 'OTO.SM01.003.01',
            'title' => 'Melakukan Perawatan Sistem Injeksi',
            'standard_reference' => 'SKKNI 2026',
        ]);
        $resStoreUnit->assertRedirect(route('admin.schemes.show', $this->scheme->id));
        $resStoreUnit->assertSessionHas('success');

        $unit3 = CompetencyUnit::where('code', 'OTO.SM01.003.01')->first();
        $this->assertNotNull($unit3);
        $this->assertEquals('Melakukan Perawatan Sistem Injeksi', $unit3->title);

        // 3. Update unit 3
        $resUpdateUnit = $this->actingAs($this->admin)->put(route('admin.schemes.units.update', $unit3->id), [
            'code' => 'OTO.SM01.003.02',
            'title' => 'Melakukan Perawatan dan Diagnosa Sistem Injeksi',
            'standard_reference' => 'SKKNI 2026 Rev',
        ]);
        $resUpdateUnit->assertRedirect(route('admin.schemes.show', $this->scheme->id));
        $this->assertEquals('OTO.SM01.003.02', $unit3->fresh()->code);

        // 4. Add element to unit 3
        $resStoreElement = $this->actingAs($this->admin)->post(route('admin.schemes.elements.store', $unit3->id), [
            'title' => 'Menyiapkan alat scanner EFI',
        ]);
        $resStoreElement->assertRedirect(route('admin.schemes.show', $this->scheme->id));
        $element = UnitElement::where('title', 'Menyiapkan alat scanner EFI')->first();
        $this->assertNotNull($element);

        // 5. Add KUK to element
        $resStoreKuk = $this->actingAs($this->admin)->post(route('admin.schemes.kuks.store', $element->id), [
            'code_number' => '1.1',
            'statement' => 'Konektor scanner EFI dihubungkan ke soket DLC sesuai SOP.',
        ]);
        $resStoreKuk->assertRedirect(route('admin.schemes.show', $this->scheme->id));
        $kuk = Kuk::where('code_number', '1.1')->first();
        $this->assertNotNull($kuk);
        $this->assertEquals('1.1', $kuk->criteria_number);

        // 6. View scheme show page: element and kuk must be visible
        $resShow = $this->actingAs($this->admin)->get(route('admin.schemes.show', $this->scheme->id));
        $resShow->assertOk();
        $resShow->assertSee('OTO.SM01.003.02');
        $resShow->assertSee('Menyiapkan alat scanner EFI');
        $resShow->assertSee('Konektor scanner EFI dihubungkan ke soket DLC sesuai SOP.');

        // 7. Delete KUK
        $resDeleteKuk = $this->actingAs($this->admin)->delete(route('admin.schemes.kuks.destroy', $kuk->id));
        $resDeleteKuk->assertRedirect(route('admin.schemes.show', $this->scheme->id));
        $this->assertDatabaseMissing('m_kuks', ['id' => $kuk->id]);
    }

    public function test_question_bank_displays_packages_per_scheme_and_filters_individual_questions(): void
    {
        // Seed 5 questions under scheme
        for ($i = 1; $i <= 5; $i++) {
            QuestionBank::create([
                'scheme_id' => $this->scheme->id,
                'question_text' => "Soal uji CBT nomor {$i} untuk otomotif",
                'option_a' => 'Pilihan A',
                'option_b' => 'Pilihan B',
                'option_c' => 'Pilihan C',
                'option_d' => 'Pilihan D',
                'correct_answer' => 'A',
            ]);
        }

        // Visit question bank index
        $res = $this->actingAs($this->admin)->get(route('admin.question-banks.index'));
        $res->assertOk();

        // Must display package row with scheme name and count
        $res->assertSee("Paket Soal CBT - {$this->scheme->name}");
        $res->assertSee('5 Butir');
        $res->assertSee('IA05 (CBT)');
        $res->assertDontSee('Belum ada bank soal yang tersedia.');

        // Must display individual questions in list
        $res->assertSee('Soal uji CBT nomor 1 untuk otomotif');
        $res->assertSee('Soal uji CBT nomor 5 untuk otomotif');
    }

    public function test_admin_can_upload_certificate_for_kompeten_candidate_only(): void
    {
        // Candidate 1: KOMPETEN
        $asmK = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi->id,
            'apl_status' => 'APPROVED',
            'status' => 'COMPLETED',
            'final_result' => 'KOMPETEN',
        ]);

        // Candidate 2: BELUM_KOMPETEN
        $asesiBk = User::factory()->create(['role' => 'ASESI']);
        $asmBk = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $asesiBk->id,
            'apl_status' => 'APPROVED',
            'status' => 'COMPLETED',
            'final_result' => 'BELUM_KOMPETEN',
        ]);

        // 1. Visit schedule certificate management page
        $resManage = $this->actingAs($this->admin)->get(route('admin.certificates.schedule', $this->schedule->id));
        $resManage->assertOk();
        $resManage->assertSee('Budi Santoso');
        $resManage->assertSee('KOMPETEN');
        // Non-K candidate is not in the certificate issuance table
        $resManage->assertDontSee($asesiBk->full_name);

        // 2. Upload certificate for KOMPETEN candidate
        $file = UploadedFile::fake()->create('sertifikat_budi.pdf', 1024, 'application/pdf');

        $resUpload = $this->actingAs($this->admin)->post(route('admin.certificates.store', $this->schedule->id), [
            'assessment_id' => $asmK->id,
            'certificate_number' => 'No. 74100 2412 0000001 2026',
            'blanko_number' => 'BL-2026-004512',
            'issued_date' => now()->toDateString(),
            'valid_until' => now()->addYears(3)->toDateString(),
            'certificate_file' => $file,
        ]);

        $resUpload->assertRedirect(route('admin.certificates.schedule', $this->schedule->id));
        $resUpload->assertSessionHas('success');

        $cert = Certificate::where('assessment_id', $asmK->id)->first();
        $this->assertNotNull($cert);
        $this->assertEquals('No. 74100 2412 0000001 2026', $cert->certificate_number);
        $this->assertEquals('BL-2026-004512', $cert->blanko_number);
        $this->assertEquals($this->asesi->id, $cert->asesi_id);
        $this->assertEquals($this->scheme->id, $cert->scheme_id);
        Storage::disk('public')->assertExists($cert->file_path);

        // 3. Attempt to upload certificate for BELUM_KOMPETEN candidate must be rejected
        $resReject = $this->actingAs($this->admin)->post(route('admin.certificates.store', $this->schedule->id), [
            'assessment_id' => $asmBk->id,
            'certificate_number' => 'No. 74100 2412 0000002 2026',
            'blanko_number' => 'BL-2026-004513',
            'issued_date' => now()->toDateString(),
            'valid_until' => now()->addYears(3)->toDateString(),
            'certificate_file' => $file,
        ]);

        $resReject->assertSessionHas('error');
        $this->assertDatabaseMissing('t_certificates', ['assessment_id' => $asmBk->id]);
    }

    public function test_asesi_can_view_preview_and_download_own_certificates_and_cannot_access_others(): void
    {
        $asm = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi->id,
            'apl_status' => 'APPROVED',
            'status' => 'COMPLETED',
            'final_result' => 'KOMPETEN',
        ]);

        $file = UploadedFile::fake()->create('sertifikat_resmi_bnsp.pdf', 500, 'application/pdf');
        $path = $file->storeAs('certificates', 'cert_test_1.pdf', 'public');

        $cert = Certificate::create([
            'assessment_id' => $asm->id,
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi->id,
            'scheme_id' => $this->scheme->id,
            'certificate_number' => 'No. 74100 2412 0000001 2026',
            'blanko_number' => 'BL-2026-004512',
            'issued_date' => now()->toDateString(),
            'valid_until' => now()->addYears(3)->toDateString(),
            'file_path' => $path,
            'original_filename' => 'sertifikat_resmi_bnsp.pdf',
            'uploaded_by' => $this->admin->id,
        ]);

        // 1. Asesi views 'Sertifikat Saya' page
        $resIndex = $this->actingAs($this->asesi)->get(route('asesi.certificates.index'));
        $resIndex->assertOk();
        $resIndex->assertSee('Sertifikat Saya');
        $resIndex->assertSee('Skema Servis Otomotif');
        $resIndex->assertSee('No. 74100 2412 0000001 2026');
        $resIndex->assertSee('BL-2026-004512');
        $resIndex->assertSee('✓ Aktif Berlaku');
        $resIndex->assertSee('Unduh PDF Resmi');
        $resIndex->assertSee('Preview Modal');

        // 2. Asesi previews own certificate
        $resPreview = $this->actingAs($this->asesi)->get(route('asesi.certificates.preview', $cert->id));
        $resPreview->assertOk();
        $this->assertEquals('application/pdf', $resPreview->headers->get('Content-Type'));

        // 3. Asesi downloads own certificate
        $resDownload = $this->actingAs($this->asesi)->get(route('asesi.certificates.download', $cert->id));
        $resDownload->assertOk();
        $this->assertStringContainsString('attachment', (string) $resDownload->headers->get('Content-Disposition'));

        // 4. Another asesi cannot preview or download this certificate
        $otherAsesi = User::factory()->create(['role' => 'ASESI']);
        $resForbiddenPreview = $this->actingAs($otherAsesi)->get(route('asesi.certificates.preview', $cert->id));
        $resForbiddenPreview->assertForbidden();

        $resForbiddenDownload = $this->actingAs($otherAsesi)->get(route('asesi.certificates.download', $cert->id));
        $resForbiddenDownload->assertForbidden();
    }
}
