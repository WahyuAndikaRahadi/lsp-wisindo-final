<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAk02Item;
use App\Models\AssessmentAk03Item;
use App\Models\AssessmentAppeal;
use App\Models\AssessmentIaResponse;
use App\Models\CompetencyUnit;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Services\MukDynamicDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DokumenMukPrintViewsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $asesor;

    protected User $asesi;

    protected Scheme $scheme;

    protected Schedule $schedule;

    protected Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('role', 'ADMIN_LSP')->first();
        $this->asesor = User::where('role', 'ASESOR')->first();
        $this->asesor->update([
            'full_name' => 'Bambang Sutrisno, M.T.',
            'assessor_registration_number' => 'MET.000.001928.2021',
        ]);

        $this->asesi = User::where('role', 'ASESI')->first();
        $this->asesi->profile()->updateOrCreate(
            ['user_id' => $this->asesi->id],
            [
                'nik' => '3215012345670001',
                'nama_institusi_perusahaan' => 'SMK Negeri 3 Karawang',
                'alamat_rumah' => 'Perum Griya Kondang Asri Blok B No 12 Karawang',
                'tempat_lahir' => 'Karawang',
                'tanggal_lahir' => '2005-08-17',
                'jenis_kelamin' => 'L',
                'kebangsaan' => 'Indonesia',
                'jabatan' => 'Teknisi Junior Otomotif',
            ]
        );

        $this->scheme = Scheme::first();
        $this->scheme->update([
            'name' => 'Engine Junior Technician',
            'code' => '01/LSP PrO Indo/I/2026',
        ]);

        $this->asesor->update(['full_name' => 'Bambang Sutrisno, M.T.']);
        $this->asesi->update(['full_name' => 'Rian Kurniawan']);

        $unit = $this->scheme->competencyUnits()->first();
        if (! $unit) {
            $unit = CompetencyUnit::create([
                'scheme_id' => $this->scheme->id,
                'code' => 'G.45OTO01.001.2',
                'title' => 'Melaksanakan Keselamatan dan Kesehatan Kerja',
            ]);
        }

        $this->schedule = Schedule::create([
            'code' => 'SCH-PRINT-TEST',
            'name' => 'Uji Kompetensi Otomotif Batch 1',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'SMK Negeri 3 Karawang',
            'tuk_address' => 'Jl. Kemakmuran No. 15 Karawang Timur',
            'tuk_city' => 'Karawang',
            'date' => '2026-10-20',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA02'],
            'status' => 'FINISHED',
            'created_by' => $this->admin->id,
            'pic_name' => 'Drs. H. Mulyadi, M.Pd.',
            'pic_position' => 'Kepala Bengkel Otomotif TUK',
            'ak05_report' => [
                'negative_aspects' => 'Asesi sempat tegang saat pembukaan uji.',
                'positive_aspects' => 'Penguasaan alat ukur presisi dan APD sangat baik.',
                'rejection_notes' => 'Tidak ada penolakan.',
                'improvement_suggestions' => 'Pertahankan pemeliharaan SST berkala.',
            ],
            'ak06_review' => [
                'procedure_recommendation' => 'Seluruh prinsip asesmen terpenuhi secara valid dan reliabel.',
                'improvement_recommendations' => 'Update bank soal materi mobil listrik.',
            ],
        ]);
        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment = Assessment::create([
            'asesi_id' => $this->asesi->id,
            'schedule_id' => $this->schedule->id,
            'scheme_id' => $this->scheme->id,
            'assessor_id' => $this->asesor->id,
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
            'status' => 'COMPLETED',
            'final_result' => 'KOMPETEN',
            'finalized_at' => now(),
            'assessor_notes' => 'Asesi sangat kompeten mendemonstrasikan SOP penanganan servis.',
            'blanko_number' => 'BL-2026-00129',
            'certificate_number' => 'CERT-LSP-2026-0089',
        ]);

        AssessmentAk02Item::create([
            'assessment_id' => $this->assessment->id,
            'competency_unit_id' => $unit->id,
            'is_observasi' => true,
            'is_tulis' => true,
            'is_lisan' => false,
            'is_portofolio' => false,
            'is_pihak_ketiga' => false,
            'is_proyek' => false,
            'is_lainnya' => false,
        ]);

        AssessmentAk03Item::create([
            'assessment_id' => $this->assessment->id,
            'component_number' => 1,
            'result' => true,
            'comment' => 'Penjelasan awal sangat jelas.',
        ]);

        AssessmentAppeal::create([
            'assessment_id' => $this->assessment->id,
            'appeal_date' => now()->toDateString(),
            'appeal_reasons' => 'Pengajuan banding atas hasil observasi.',
            'contested_units' => ['G.45OTO01.001.2'],
            'status' => 'ACCEPTED',
            'decision_notes' => 'Banding diterima setelah verifikasi ulang rekaman kerja.',
            'decision_date' => now()->toDateString(),
            'decided_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_access_dokumen_muk_index_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.index'));
        $response->assertOk();
        $response->assertSee('SCH-PRINT-TEST');
        $response->assertSee('Dokumen MUK');
        $response->assertSee('Cari Jadwal');
    }

    public function test_admin_can_search_schedules_on_index_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.index', ['search' => 'SCH-PRINT-TEST']));
        $response->assertOk();
        $response->assertSee('SCH-PRINT-TEST');

        $notFoundResponse = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.index', ['search' => 'NON_EXISTENT_SCHEDULE_12345']));
        $notFoundResponse->assertOk();
        $notFoundResponse->assertSee('Tidak ada jadwal asesmen ditemukan');
    }

    public function test_admin_can_access_dokumen_muk_show_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.show', $this->schedule));
        $response->assertOk();
        $response->assertSee('SCH-PRINT-TEST');
        $response->assertSee('Berita Acara (BA)');
        $response->assertSee('Hadir Asesor');
        $response->assertSee('Hadir Asesi');
        $response->assertSee('FR.AK.05 (Laporan)');
        $response->assertSee('FR.AK.06 (Tinjauan)');
        $response->assertSee('Rian Kurniawan');
        $response->assertSee('Cari Asesi');
    }

    public function test_admin_can_search_assessees_on_show_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.show', [
            'schedule' => $this->schedule->id,
            'search' => 'Rian',
        ]));
        $response->assertOk();
        $response->assertSee('Rian Kurniawan');

        $notFoundResponse = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.show', [
            'schedule' => $this->schedule->id,
            'search' => 'NON_EXISTENT_ASSESSEE_XYZ',
        ]));
        $notFoundResponse->assertOk();
        $notFoundResponse->assertSee('Tidak ada asesi ditemukan');
    }

    public function test_can_render_berita_acara_matching_master(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-ba', $this->schedule));
        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_can_render_daftar_hadir_asesor_matching_master(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-hadir-asesor', $this->schedule));
        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_berita_acara_and_attendance_list_wraps_long_institution_and_names(): void
    {
        $this->asesi->update(['full_name' => 'Muhammad Rizky Ramadhan Alamsyah Suryokusumo']);
        $this->asesi->profile()->update([
            'nama_institusi_perusahaan' => 'SMK Negeri 1 Cikarang Barat Kabupaten Bekasi Jawa Barat',
            'alamat_rumah' => 'Perumahan Griya Kondang Asri Blok B2 No. 15, RT 04 RW 02, Desa Kondangjaya, Karawang Timur',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-ba', $this->schedule));
        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));

        $pdfPath = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($pdfPath);

        $python = app(MukDynamicDocumentService::class)->getPythonExecutable();
        $script = "import pymupdf, sys; doc = pymupdf.open(sys.argv[1]); text = ''.join(p.get_text() for p in doc); print(text)";
        $extracted = shell_exec(escapeshellarg($python).' -c '.escapeshellarg($script).' '.escapeshellarg($pdfPath));

        $this->assertStringContainsString('SMK Negeri 1 Cikarang', $extracted);
        $this->assertStringContainsString('Barat Kabupaten Bekasi', $extracted);
        $this->assertStringContainsString('Jawa Barat', $extracted);
        $this->assertStringContainsString('Muhammad Rizky Ramadhan', $extracted);

        // Verify letterhead is centered on both pages (Berita Acara and Attendance List)
        $centerScript = "import pymupdf, sys, json; doc = pymupdf.open(sys.argv[1]); res = [{'left': page.get_image_rects(page.get_images()[0][0])[0].x0, 'right': page.rect.width - page.get_image_rects(page.get_images()[0][0])[0].x1} for page in doc if page.get_images()]; print(json.dumps(res))";
        $centerJson = shell_exec(escapeshellarg($python).' -c '.escapeshellarg($centerScript).' '.escapeshellarg($pdfPath));
        $centerData = json_decode($centerJson, true);
        $this->assertIsArray($centerData);
        $this->assertGreaterThanOrEqual(2, count($centerData));
        // Page 2 (Daftar Hadir / Attendance list)
        $this->assertEqualsWithDelta($centerData[1]['left'], $centerData[1]['right'], 1.0, 'Page 2 attendance list letterhead should be centered.');
    }

    public function test_daftar_hadir_asesor_wraps_long_tuk_institution_and_assessor_names(): void
    {
        $this->schedule->update([
            'tuk_name' => 'SMK Negeri 1 Cikarang Barat Kabupaten Bekasi Jawa Barat (TUK Terverifikasi BNSP)',
            'tuk_address' => 'Jl. Teuku Umar No. 1, Desa Telaga Asih, Kecamatan Cikarang Barat, Kabupaten Bekasi, Provinsi Jawa Barat 17530',
        ]);
        $this->asesor->update([
            'full_name' => 'Prof. Dr. Ir. Raden Mas Sudirman Suryokusumo, S.Pd., M.Pd., M.Kom., IPM., ASEAN Eng.',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-hadir-asesor', $this->schedule));
        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));

        $pdfPath = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($pdfPath);

        $python = app(MukDynamicDocumentService::class)->getPythonExecutable();
        $script = "import pymupdf, sys; doc = pymupdf.open(sys.argv[1]); text = ''.join(p.get_text() for p in doc); print(text)";
        $extracted = shell_exec(escapeshellarg($python).' -c '.escapeshellarg($script).' '.escapeshellarg($pdfPath));

        $this->assertStringContainsString('SMK Negeri 1 Cikarang Barat', $extracted);
        $this->assertStringContainsString('Raden Mas Sudirman', $extracted);
        $this->assertStringContainsString('ASEAN Eng.', $extracted);

        // Verify letterhead is centered horizontally on assessor attendance list
        $centerScript = "import pymupdf, sys, json; doc = pymupdf.open(sys.argv[1]); res = [{'left': page.get_image_rects(page.get_images()[0][0])[0].x0, 'right': page.rect.width - page.get_image_rects(page.get_images()[0][0])[0].x1} for page in doc if page.get_images()]; print(json.dumps(res))";
        $centerJson = shell_exec(escapeshellarg($python).' -c '.escapeshellarg($centerScript).' '.escapeshellarg($pdfPath));
        $centerData = json_decode($centerJson, true);
        $this->assertIsArray($centerData);
        $this->assertNotEmpty($centerData);
        $this->assertEqualsWithDelta($centerData[0]['left'], $centerData[0]['right'], 1.0, 'Daftar hadir asesor letterhead should be centered.');
    }

    public function test_can_render_daftar_hadir_asesi_with_alternating_signatures(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-hadir-asesi', $this->schedule));
        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_can_render_fr_ak02_rekaman_asesmen(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'ak02',
        ]));
        $response->assertOk();
        $response->assertSee('FR.AK.02. REKAMAN ASESMEN KOMPETENSI');
        $response->assertSee('Observasi Demonstrasi');
        $response->assertSee('Portofolio');
        $response->assertSee('Rekomendasi hasil asesmen');
        $response->assertSee('Kompeten');
        $response->assertSee('LAMPIRAN DOKUMEN:');
        $response->assertSee('Rian Kurniawan');
    }

    public function test_can_render_fr_ak03_umpan_balik(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'ak03',
        ]));
        $response->assertOk();
        $response->assertSee('FR.AK.03. UMPAN BALIK DAN CATATAN ASESMEN');
        $response->assertSee('Umpan balik dari Asesi');
        $response->assertSee('Saya mendapatkan penjelasan yang cukup memadai');
        $response->assertSee('Rian Kurniawan');
    }

    public function test_can_render_fr_ak04_banding_asesmen(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'ak04',
        ]));
        $response->assertOk();
        $response->assertSee('FR.AK.04. BANDING ASESMEN');
        $response->assertSee('Apakah Proses Banding telah dijelaskan kepada Anda?');
        $response->assertSee('KEPUTUSAN BANDING OLEH ADMIN LSP / TIM BANDING:');
        $response->assertSee('DITERIMA (Hasil Asesmen Diubah/Diuji Ulang)');
    }

    public function test_can_render_fr_ak07_penyesuaian_wajar(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'ak07',
        ]));
        $response->assertOk();
        $response->assertSee('FR.AK.07.');
        $response->assertSee('CEKLIS PENYESUAIAN YANG WAJAR dan BERALASAN');
        $response->assertSee('PANDUAN BAGI ASESOR');
        $response->assertSee('Potensi Asesi');
        $response->assertSee('Hasil kesepakatan antara asesi dengan asesor');
    }

    public function test_can_render_fr_apl02_asesmen_mandiri(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'apl02',
        ]));
        $response->assertOk();
        $response->assertSee('FR.APL.02. ASESMEN MANDIRI');
        $response->assertSee('PANDUAN ASESMEN MANDIRI');
        $response->assertSee('Dapatkah Saya ................?');
        $response->assertSee('Rekomendasi Asesor');
    }

    public function test_can_render_fr_ak05_laporan_asesmen(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export', [
            'schedule' => $this->schedule->id,
            'type' => 'ak05',
        ]));
        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_can_render_fr_ak06_meninjau_proses_asesmen(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export', [
            'schedule' => $this->schedule->id,
            'type' => 'ak06',
        ]));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_can_render_cover_daftar_periksa_and_surat_pernyataan(): void
    {
        $coverRes = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export', [
            'schedule' => $this->schedule->id,
            'type' => 'cover',
        ]));
        $coverRes->assertOk();
        $coverRes->assertSee('MATERI UJI KOMPETENSI (MUK)');

        $periksaRes = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export', [
            'schedule' => $this->schedule->id,
            'type' => 'daftar-periksa',
        ]));
        $periksaRes->assertOk();
        $periksaRes->assertSee('DAFTAR PERIKSA KELENGKAPAN DOKUMEN');

        $suratRes = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'pernyataan',
        ]));
        $suratRes->assertOk();
        $suratRes->assertSee('SURAT PERNYATAAN PEMEGANG SERTIFIKAT KOMPETENSI');
        $suratRes->assertSee('Rian Kurniawan');
        $suratRes->assertSee('3215012345670001');
        $suratRes->assertSee('Mematuhi ketentuan yang relevan dalam skema sertifikasi.');
    }

    public function test_can_render_fr_mapa02_peta_instrumen_asesmen(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export', [
            'schedule' => $this->schedule->id,
            'type' => 'mapa02',
        ]));
        $response->assertOk();
        $response->assertSee('FR.MAPA.02');
        $response->assertSee('PETA INSTRUMEN ASESMEN');
        $response->assertSee('Engine Junior Technician');
    }

    public function test_can_render_fr_ia05_variants_a_b_c(): void
    {
        // IA.05.B Answer Key (Schedule level)
        $resB = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export', [
            'schedule' => $this->schedule->id,
            'type' => 'ia05b',
        ]));
        $resB->assertOk();
        $resB->assertSee('FR.IA.05.B');
        $resB->assertSee('LEMBAR KUNCI JAWABAN');

        // IA.05.A Question sheet (Asesi level)
        $resA = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'ia05a',
        ]));
        $resA->assertOk();
        $resA->assertSee('FR.IA.05.A');
        $resA->assertSee('PERTANYAAN TERTULIS PILIHAN GANDA');

        // IA.05.C Answer sheet (Asesi level)
        $resC = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'ia05c',
        ]));
        $resC->assertOk();
        $resC->assertSee('FR.IA.05.C');
        $resC->assertSee('LEMBAR JAWABAN PILIHAN GANDA');

        // Dynamic PDF export for IA.05.C
        $resDynC = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.asesi-dynamic', [
            'assessment' => $this->assessment->id,
            'type' => 'ia05c',
        ]));
        $resDynC->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $resDynC->headers->get('Content-Type'));

        // Candidate can view their own IA.05.C
        $resAsesiC = $this->actingAs($this->asesi)->get(route('admin.dokumen-muk.asesi-dynamic', [
            'assessment' => $this->assessment->id,
            'type' => 'ia05c',
        ]));
        $resAsesiC->assertOk();
    }

    public function test_can_render_fr_ia01_ia01b_ia02_ia03(): void
    {
        // IA.01 Observation Checklist
        $res01 = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'ia01',
        ]));
        $res01->assertOk();
        $res01->assertSee('FR.IA.01');
        $res01->assertSee('CEKLIS OBSERVASI AKTIVITAS');

        // IA.01B Report Sheet (exports assessee's uploaded file or shows not-uploaded view)
        $res01bMissing = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'ia01b',
        ]));
        $res01bMissing->assertOk();
        $res01bMissing->assertViewIs('admin.dokumen-muk.not-uploaded');

        Storage::fake('local');
        $uploadedPath = 'ia01b_evidence/test_report_sheet.pdf';
        Storage::disk('local')->put($uploadedPath, '%PDF-1.4 sample report sheet');

        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA01B',
            'data_json' => [
                'evidence_image_path' => $uploadedPath,
                'evidence_original_name' => 'Report_Sheet_Asesi.pdf',
            ],
        ]);

        $res01b = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'ia01b',
        ]));
        $res01b->assertOk();

        $resDynamic01b = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.asesi-dynamic', [
            'assessment' => $this->assessment->id,
            'type' => 'ia01b',
        ]));
        $resDynamic01b->assertOk();

        // IA.02 Practical Demonstration
        $res02 = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'ia02',
        ]));
        $res02->assertOk();
        $res02->assertSee('FR.IA.02');
        $res02->assertSee('TUGAS PRAKTIK DEMONSTRASI');

        // IA.03 Oral Questions
        $res03 = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'ia03',
        ]));
        $res03->assertOk();
        $res03->assertSee('FR.IA.03');
        $res03->assertSee('PERTANYAAN MENDUKUNG OBSERVASI');
    }

    public function test_can_render_fr_apl01_with_candidate_personal_and_employment_data(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'apl01',
        ]));

        $response->assertOk();
        $response->assertSee('FR.APL.01. PERMOHONAN SERTIFIKASI KOMPETENSI');
        $response->assertSee('Rian Kurniawan');
        $response->assertSee('3215012345670001');
        $response->assertSee('SMK Negeri 3 Karawang');
        $response->assertSee('Teknisi Junior Otomotif');
        $response->assertSee('Perum Griya Kondang Asri Blok B No 12 Karawang');
        $response->assertSee('Engine Junior Technician');
        $response->assertSee('01/LSP PrO Indo/I/2026');
        $response->assertSee('check-box checked');
        $response->assertSee('Tujuan Asesmen');
        $response->assertSee('Bukti Persyaratan Dasar');
        $response->assertSee('Bukti Administratif');
    }

    public function test_ba_and_hadir_asesi_organized_by_assigned_assessors(): void
    {
        $asesor2 = User::create([
            'full_name' => 'Dr. Hendra Gunawan, M.T.',
            'email' => 'asesor.hendra@test.com',
            'phone' => '081234567899',
            'assessor_registration_number' => 'MET.000.009876.2023',
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'password' => Hash::make('password'),
        ]);
        $this->schedule->assessors()->attach($asesor2->id);

        $asesi2 = User::create([
            'full_name' => 'Dimas Pratama',
            'email' => 'dimas.pratama@test.com',
            'phone' => '081234567888',
            'role' => 'ASESI',
            'status' => 'ACTIVE',
            'password' => Hash::make('password'),
        ]);
        $asesi2->profile()->create([
            'nik' => '3215019999990002',
            'nama_institusi_perusahaan' => 'SMK Negeri 1 Karawang',
            'alamat_rumah' => 'Jl. Tuparev No 5 Karawang',
        ]);

        Assessment::create([
            'asesi_id' => $asesi2->id,
            'schedule_id' => $this->schedule->id,
            'assessor_id' => $asesor2->id,
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
            'status' => 'COMPLETED',
            'final_result' => 'KOMPETEN',
        ]);

        $dynamicService = app(MukDynamicDocumentService::class);
        $payload = $dynamicService->buildPayload($this->schedule);

        $this->assertArrayHasKey('assessor_groups', $payload);
        $this->assertCount(2, $payload['assessor_groups']);

        $group1 = $payload['assessor_groups'][0];
        $this->assertEquals($this->asesor->id, $group1['assessor']['id']);
        $this->assertEquals('Rian Kurniawan', $group1['peserta'][0]['nama']);

        $group2 = $payload['assessor_groups'][1];
        $this->assertEquals($asesor2->id, $group2['assessor']['id']);
        $this->assertEquals('Dimas Pratama', $group2['peserta'][0]['nama']);

        // Test BA PDF generation with multiple assessors
        $pdfRes = $dynamicService->exportSchedule($this->schedule, 'ba');
        $this->assertFileExists($pdfRes->getFile()->getPathname());

        // Test HTML view displays both assessor sections
        $baHtml = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-ba', ['schedule' => $this->schedule, 'format' => 'html']));
        $baHtml->assertOk();
        $baHtml->assertSee('Bambang Sutrisno, M.T.');
        $baHtml->assertSee('Dr. Hendra Gunawan, M.T.');
        $baHtml->assertSee('Rian Kurniawan');
        $baHtml->assertSee('Dimas Pratama');

        // Test Hadir Asesi HTML view displays both assessor sections
        $hadirHtml = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-hadir-asesi', ['schedule' => $this->schedule, 'format' => 'html']));
        $hadirHtml->assertOk();
        $hadirHtml->assertSee('Bambang Sutrisno, M.T.');
        $hadirHtml->assertSee('Dr. Hendra Gunawan, M.T.');
        $hadirHtml->assertSee('Rian Kurniawan');
        $hadirHtml->assertSee('Dimas Pratama');
    }

    public function test_surat_pernyataan_pdf_export_matches_schedule_tuk_wraps_long_name_blanks_numbers_and_displays_signature(): void
    {
        $longTukName = 'SMK Negeri 1 Cikarang Barat Kabupaten Bekasi Jawa Barat (Tempat Uji Kompetensi Terverifikasi BNSP)';
        $this->schedule->update([
            'tuk_name' => $longTukName,
        ]);

        $sampleSignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJYAAAA8CAYAAACEhkNqAAABkElEQVR4nO3c4Y2DMAyGYe5myBDsP80N0R3oj+okhEIhJNifzfsMUDn2h6Oq3E0TAAAAAAAAACC2UuallHnxrgMfP94F9KqF6fX6C3+u6MIO4Mx2ImB+fr0LaFW78vYCxNXoJ8wTffbK42rUIN/wK0HZ21QEzI5so3s3D+HyJdfk0VcZV6MPmQbfGQC2lz33xlptFMJly62pXlcUV2OfUublTL/MG6owWLZXu3XPpIKlEKg1wnWsp0ePv3rU6/Mwoie3NTDSwNhe43tg8u0rwoCeGq675jWsaVEDtZXlHN9YPETdH5RxEFm3l+Ws+KlkR5ZweZ2j+cOzB2or6nm96+56WyBCg0eIsr2U6rwULLWGWlAa2pbiQ98ULO9iFagMUTno0yTwdkNEnkNVCfYRuYKisAyX+naqkS0sCusXFJXDtBaiSHUjN0rE7VQTqlhlvYGIvJ1qwhauqiUgWbZTTfgDKDoKTLbtVJPqMErO/nl/tkD9S3koJU/YTnDC/+4CAAAAAAAAAAAAAAAAAABAfm/ikyAKkwlsyAAAAABJRU5ErkJggg==';

        $this->assessment->update([
            'asesi_signature' => $sampleSignature,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'pernyataan',
            'format' => 'pdf',
        ]));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));

        $pdfPath = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($pdfPath);

        $python = file_exists(base_path('venv/bin/python')) ? base_path('venv/bin/python') : (file_exists(base_path('.venv/bin/python')) ? base_path('.venv/bin/python') : 'python3');
        $script = <<<'PY'
import pymupdf, sys, json
doc = pymupdf.open(sys.argv[1])
page = doc[0]
text = page.get_text()
images = page.get_images()
res = {
    'text': text,
    'has_signature_image': len(images) >= 2,
    'images_count': len(images),
}
print(json.dumps(res))
PY;
        $output = shell_exec(escapeshellarg($python).' -c '.escapeshellarg($script).' '.escapeshellarg($pdfPath));
        $data = json_decode($output, true);

        $this->assertIsArray($data);
        // 1. TUK name matches schedule and is wrapped across lines
        $this->assertStringContainsString('SMK Negeri 1', $data['text']);
        $this->assertStringContainsString('Cikarang Barat Kabupaten Bekasi', $data['text']);
        $this->assertStringContainsString('Tempat Uji Kompetensi Terverifikasi BNSP', $data['text']);

        // 2. Dummy numbers BLK- and CERT- are removed, fields are blank
        $this->assertStringNotContainsString('BLK-', $data['text']);
        $this->assertStringNotContainsString('CERT-', $data['text']);
        $this->assertMatchesRegularExpression('/No\.Blanko\s*:\s*\n/m', $data['text']);
        $this->assertMatchesRegularExpression('/No\.\s*Sertifikat\s*:\s*\n/m', $data['text']);

        // 3. Assessee signature image is embedded in the PDF
        $this->assertTrue($data['has_signature_image'], 'Assessee signature should be embedded in the PDF document.');
    }

    public function test_berita_acara_and_daftar_hadir_numbering_restarts_from_1_to_10_on_each_batch(): void
    {
        // Add 14 more assessees to the schedule (total 15 assessees with $this->asesi)
        for ($i = 2; $i <= 15; $i++) {
            $user = User::create([
                'full_name' => "Peserta Tambahan {$i}",
                'email' => "peserta{$i}@test.com",
                'phone' => '08123456780'.$i,
                'role' => 'ASESI',
                'status' => 'ACTIVE',
                'password' => Hash::make('password'),
            ]);
            $user->profile()->create([
                'nik' => '321501000000000'.$i,
                'nama_institusi_perusahaan' => 'SMK Negeri 1 Karawang',
                'alamat_rumah' => 'Karawang',
            ]);
            Assessment::create([
                'asesi_id' => $user->id,
                'schedule_id' => $this->schedule->id,
                'assessor_id' => $this->asesor->id,
                'admin_status' => 'APPROVED',
                'apl_status' => 'APPROVED',
                'status' => 'COMPLETED',
                'final_result' => 'KOMPETEN',
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-ba', $this->schedule));
        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));

        $pdfPath = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($pdfPath);

        $python = file_exists(base_path('venv/bin/python')) ? base_path('venv/bin/python') : (file_exists(base_path('.venv/bin/python')) ? base_path('.venv/bin/python') : 'python3');
        $script = <<<'PY'
import pymupdf, sys, json
doc = pymupdf.open(sys.argv[1])
pages_data = []
for p in doc:
    lines = [line.strip() for line in p.get_text().split('\n') if line.strip()]
    pages_data.append({
        'has_1': '1' in lines,
        'has_10': '10' in lines,
        'has_11': '11' in lines,
        'has_12': '12' in lines,
        'has_15': '15' in lines,
        'has_bagian': any('Bagian' in line for line in lines),
    })
print(json.dumps({'total_pages': len(doc), 'pages': pages_data}))
PY;
        $output = shell_exec(escapeshellarg($python).' -c '.escapeshellarg($script).' '.escapeshellarg($pdfPath));
        $res = json_decode($output, true);

        $this->assertIsArray($res);
        $this->assertEquals(4, $res['total_pages'], '15 candidates should produce 4 pages (2 batches of BA and Daftar Hadir).');

        // Check Page 1 (BA Batch 1) & Page 2 (Daftar Hadir Batch 1)
        $this->assertTrue($res['pages'][0]['has_1']);
        $this->assertTrue($res['pages'][0]['has_10']);
        $this->assertFalse($res['pages'][0]['has_11']);
        $this->assertFalse($res['pages'][0]['has_bagian'], 'Page 1 (BA Batch 1) must not contain the "(Bagian x dari x)" subtitle.');

        $this->assertTrue($res['pages'][1]['has_1']);
        $this->assertTrue($res['pages'][1]['has_10']);
        $this->assertFalse($res['pages'][1]['has_11']);
        $this->assertFalse($res['pages'][1]['has_bagian'], 'Page 2 (Daftar Hadir Batch 1) must not contain the "(Bagian x dari x)" subtitle.');

        // Check Page 3 (BA Batch 2) & Page 4 (Daftar Hadir Batch 2): numbering restarts from 1 to 10
        $this->assertTrue($res['pages'][2]['has_1']);
        $this->assertTrue($res['pages'][2]['has_10']);
        $this->assertFalse($res['pages'][2]['has_11'], 'Page 3 should not have row 11; numbering must restart from 1 to 10.');
        $this->assertFalse($res['pages'][2]['has_bagian'], 'Page 3 (BA Batch 2) must not contain the "(Bagian x dari x)" subtitle.');

        $this->assertTrue($res['pages'][3]['has_1']);
        $this->assertTrue($res['pages'][3]['has_10']);
        $this->assertFalse($res['pages'][3]['has_11'], 'Page 4 should not have row 11; numbering must restart from 1 to 10.');
        $this->assertFalse($res['pages'][3]['has_bagian'], 'Page 4 (Daftar Hadir Batch 2) must not contain the "(Bagian x dari x)" subtitle.');
    }

    public function test_berita_acara_pdf_rekomendasi_values_and_no_digital_signature_on_kepala_tuk(): void
    {
        // 1 competent ($this->asesi is KOMPETEN) and 1 not competent candidate
        $asesiBk = User::create([
            'full_name' => 'Fajar Pratama BK',
            'email' => 'fajar.bk@test.com',
            'phone' => '081299990001',
            'role' => 'ASESI',
            'status' => 'ACTIVE',
            'password' => Hash::make('password'),
        ]);
        $asesiBk->profile()->create([
            'nik' => '3215019999990099',
            'nama_institusi_perusahaan' => 'SMK Negeri 2 Karawang',
            'alamat_rumah' => 'Karawang Barat',
        ]);
        Assessment::create([
            'asesi_id' => $asesiBk->id,
            'schedule_id' => $this->schedule->id,
            'assessor_id' => $this->asesor->id,
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
            'status' => 'COMPLETED',
            'final_result' => 'BELUM_KOMPETEN',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-ba', $this->schedule));
        $response->assertOk();
        $pdfPath = $response->baseResponse->getFile()->getPathname();
        $this->assertFileExists($pdfPath);

        $python = file_exists(base_path('venv/bin/python')) ? base_path('venv/bin/python') : (file_exists(base_path('.venv/bin/python')) ? base_path('.venv/bin/python') : 'python3');
        $script = <<<'PY'
import pymupdf, sys, json
doc = pymupdf.open(sys.argv[1])
p1 = doc[0]
text_p1 = p1.get_text()

# Check blocks on page 1 under Penanggungjawab (right side of signature block)
blocks = p1.get_text("blocks")
right_side_blocks = [b[4] for b in blocks if b[0] > 300 and b[1] > 500]
right_text = "".join(right_side_blocks)

print(json.dumps({
    'full_p1': text_p1,
    'right_side_sig_text': right_text,
    'has_direkomendasikan': 'direkomendasikan' in text_p1,
    'has_belum_kompeten': 'belum kompeten' in text_p1,
}))
PY;
        $output = shell_exec(escapeshellarg($python).' -c '.escapeshellarg($script).' '.escapeshellarg($pdfPath));
        $res = json_decode($output, true);

        $this->assertIsArray($res);
        $this->assertTrue($res['has_direkomendasikan'], 'Berita Acara must show "direkomendasikan" for competent asesi.');
        $this->assertTrue($res['has_belum_kompeten'], 'Berita Acara must show "belum kompeten" for not competent asesi.');
        $this->assertStringNotContainsString('Ttd Digital', $res['right_side_sig_text'], 'Kepala TUK field must not contain the phrase "ttd digital".');
    }
}
