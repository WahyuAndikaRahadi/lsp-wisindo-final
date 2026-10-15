<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAk02Item;
use App\Models\AssessmentAk03Item;
use App\Models\AssessmentAppeal;
use App\Models\CompetencyUnit;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $response->assertSee('Berita Acara (BA)');
        $response->assertSee('Hadir Asesor');
        $response->assertSee('Hadir Asesi');
        $response->assertSee('FR.AK.05 (Laporan)');
        $response->assertSee('FR.AK.06 (Tinjauan)');
    }

    public function test_can_render_berita_acara_matching_master(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-ba', $this->schedule));
        $response->assertOk();
        $response->assertSee('BERITA ACARA PELAKSANAAN UJI KOMPETENSI');
        $response->assertSee('Pada hari ini, Hari/Tanggal :');
        $response->assertSee('SMK Negeri 3 Karawang');
        $response->assertSee('Engine Junior Technician');
        $response->assertSee('Rian Kurniawan');
        $response->assertSee('Jumlah kandidat yang dinyatakan Kompeten');
        $response->assertSee('Bambang Sutrisno, M.T.');
        $response->assertSee('MET.000.001928.2021');
        $response->assertSee('Drs. H. Mulyadi, M.Pd.');
    }

    public function test_can_render_daftar_hadir_asesor_matching_master(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-hadir-asesor', $this->schedule));
        $response->assertOk();
        $response->assertSee('DAFTAR HADIR ASESOR');
        $response->assertSee('1. Skema');
        $response->assertSee('2. TUK');
        $response->assertSee('Bambang Sutrisno, M.T.');
        $response->assertSee('MET.000.001928.2021');
        $response->assertSee('Penanggungjawab kegiatan');
        $response->assertSee('Drs. H. Mulyadi, M.Pd.');
    }

    public function test_can_render_daftar_hadir_asesi_with_alternating_signatures(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-hadir-asesi', $this->schedule));
        $response->assertOk();
        $response->assertSee('DAFTAR HADIR');
        $response->assertSee('SMK Negeri 3 Karawang');
        $response->assertSee('Rian Kurniawan');
        $response->assertSee('Perum Griya Kondang Asri Blok B No 12 Karawang');
        $response->assertSee('Bambang Sutrisno, M.T.');
        $response->assertSee('1.');
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

    public function test_can_render_fr_ak05_laporan_asesmen(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export', [
            'schedule' => $this->schedule->id,
            'type' => 'ak05',
        ]));
        $response->assertOk();
        $response->assertSee('FR.AK.05. LAPORAN ASESMEN');
        $response->assertSee('Aspek Negatif dan Positif dalam Asesmen');
        $response->assertSee('Aspek Positif :');
        $response->assertSee('Pencatatan Penolakan Hasil Asesmen');
    }

    public function test_can_render_fr_ak06_meninjau_proses_asesmen(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export', [
            'schedule' => $this->schedule->id,
            'type' => 'ak06',
        ]));
        $response->assertOk();
        $response->assertSee('FR.AK.06. MENINJAU PROSES ASESMEN');
        $response->assertSee('Kesesuaian dengan prinsip asesmen');
        $response->assertSee('Validitas');
        $response->assertSee('Reliabel');
        $response->assertSee('Pemenuhan dimensi kompetensi');
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
}
