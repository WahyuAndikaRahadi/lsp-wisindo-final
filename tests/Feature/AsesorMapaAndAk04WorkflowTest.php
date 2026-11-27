<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AsesorMapaAndAk04WorkflowTest extends TestCase
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
            'full_name' => 'Admin LSP Utama',
            'email' => 'admin_mapa@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);

        $this->asesor = User::create([
            'full_name' => 'Asesor Kompetensi Penguji',
            'email' => 'asesor_mapa@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'MET.000.123456',
        ]);

        $this->asesi = User::create([
            'full_name' => 'Asesi Peserta Uji',
            'email' => 'asesi_mapa@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SCH-MAPA-01',
            'name' => 'Teknisi Junior Otomotif',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);

        $this->schedule = Schedule::create([
            'code' => 'SCH-TEST-MAPA',
            'name' => 'Jadwal Asesmen MAPA & AK04',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'is_closed' => false,
            'created_by' => $this->admin->id,
            'selected_instruments' => ['AK01', 'IA01', 'IA02', 'IA03', 'IA05'],
        ]);
        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->asesor->id,
            'admin_status' => 'APPROVED',
            'apl_status' => 'SUBMITTED',
            'status' => 'IN_PROGRESS',
        ]);
    }

    public function test_asesor_cannot_access_mapa_when_apl_is_unreviewed(): void
    {
        $response = $this->actingAs($this->asesor)->get(route('asesor.schedule.mapa', $this->schedule));
        $response->assertRedirect(route('asesor.schedule.instrument', ['schedule' => $this->schedule->id, 'code' => 'apl']));
        $response->assertSessionHas('warning');
    }

    public function test_asesor_can_access_and_store_mapa01_when_apl_is_approved(): void
    {
        // Approve APL
        $this->assessment->update(['apl_status' => 'APPROVED']);

        // Access MAPA
        $getResponse = $this->actingAs($this->asesor)->get(route('asesor.schedule.mapa', $this->schedule));
        $getResponse->assertOk();
        $getResponse->assertSee('FR.MAPA.01. MERENCANAKAN AKTIVITAS DAN PROSES ASESMEN');
        $getResponse->assertSee('Hasil pelatihan dan / atau pendidikan');

        // Submit MAPA Form
        $payload = [
            'pendekatan_asesi' => 'Hasil pelatihan dan / atau pendidikan, dimana Kurikulum dan fasilitas praktek mampu telusur terhadap standar kompetensi',
            'tujuan_asesmen' => 'Sertifikasi',
            'konteks_lingkungan' => 'Tempat kerja simulasi',
            'peluang_bukti' => 'Tersedia',
            'hubungan_standar' => 'Bukti untuk mendukung asesmen',
            'pelaksana_asesmen' => 'Lembaga Sertifikasi LSP Praktisi Otomotif Indonesia',
            'orang_relevan' => ['Master Asesor / Master Trainer / Lead Asesor Kompetensi', 'Manajer sertifikasi'],
            'standar_tolok_ukur' => 'SKKNI Nomor 97 Tahun 2018',
            'has_karakteristik' => '0',
            'has_kontekstualisasi' => '0',
            'has_saran' => '0',
            'has_penyesuaian' => '0',
            'has_terintegrasi' => '1',
            'integrated_tasks' => [
                'Melaksanakan Komunikasi di Tempat Kerja',
                'Menggunakan Alat Ukur',
            ],
            'assessment_ids' => [$this->assessment->id],
        ];

        $postResponse = $this->actingAs($this->asesor)->post(route('asesor.schedule.mapa.store', $this->schedule), $payload);
        $postResponse->assertRedirect(route('asesor.schedule.ak04', $this->schedule));
        $postResponse->assertSessionHas('success');

        $this->schedule->refresh();
        $this->assessment->refresh();

        $this->assertNotNull($this->schedule->mapa01_data);
        $this->assertEquals('Sertifikasi', $this->schedule->mapa01_data['tujuan_asesmen']);
        $this->assertEquals(2, count($this->schedule->mapa01_data['integrated_tasks']));
        $this->assertNotNull($this->assessment->mapa01_data);
    }

    public function test_asesor_can_access_and_confirm_ak04_briefing(): void
    {
        $this->assessment->update(['apl_status' => 'APPROVED']);
        $this->schedule->update(['mapa01_data' => ['tujuan_asesmen' => 'Sertifikasi']]);

        // Access AK04 Briefing (view only)
        $getResponse = $this->actingAs($this->asesor)->get(route('asesor.schedule.ak04', $this->schedule));
        $getResponse->assertOk();
        $getResponse->assertSee('FR.AK.04. PENGENALAN BANDING ASESMEN');
        $getResponse->assertSee('Mode Pratinjau / View-Only');

        // Confirm Briefing
        $postResponse = $this->actingAs($this->asesor)->post(route('asesor.schedule.ak04.brief', $this->schedule), [
            'is_briefed' => '1',
        ]);
        $postResponse->assertRedirect(route('asesor.schedule.ak01', $this->schedule));
        $postResponse->assertSessionHas('success');

        $this->schedule->refresh();
        $this->assessment->refresh();

        $this->assertNotNull($this->schedule->ak04_briefed_at);
        $this->assertNotNull($this->assessment->ak04_briefed_at);
    }

    public function test_asesor_can_store_ak01_with_evidence_types_l_tl_t(): void
    {
        $this->assessment->update(['apl_status' => 'APPROVED', 'ak04_briefed_at' => now()]);
        $this->schedule->update([
            'mapa01_data' => ['tujuan_asesmen' => 'Sertifikasi'],
            'ak04_briefed_at' => now(),
        ]);

        $payload = [
            'evidence' => ['Hasil Observasi Langsung', 'Hasil Pertanyaan Tertulis'],
            'evidence_types' => ['L', 'T'],
            'evidence_types_summary' => 'L dan T',
            'tuk_type' => 'Sewaktu',
            'assessment_ids' => [$this->assessment->id],
            'selected_instruments' => ['IA01', 'IA02', 'IA05'],
        ];

        $response = $this->actingAs($this->asesor)->post(route('asesor.schedule.ak01.store', $this->schedule), $payload);
        $response->assertRedirect(route('asesor.schedule.ak07', $this->schedule));

        $this->schedule->refresh();
        $this->assertNotNull($this->schedule->ak01_data);
        $this->assertEquals(['L', 'T'], $this->schedule->ak01_data['evidence_types']);
        $this->assertEquals('L dan T', $this->schedule->ak01_data['evidence_types_summary']);
    }

    public function test_admin_can_toggle_lock_and_reopen_schedule_preserving_all_data(): void
    {
        $this->assessment->update([
            'apl_status' => 'APPROVED',
            'final_result' => 'KOMPETEN',
            'status' => 'COMPLETED',
        ]);
        $this->schedule->update([
            'ak05_report' => ['aspek_positif' => 'Baik', 'aspek_negatif' => 'Tidak ada', 'rekomendasi' => 'Pertahankan'],
            'ak06_review' => ['review' => 'Lengkap'],
        ]);

        // 1. Admin closes schedule
        $closeResponse = $this->actingAs($this->admin)->post(route('admin.jadwal.toggle-lock', $this->schedule));
        $closeResponse->assertRedirect();
        $closeResponse->assertSessionHas('success');

        $this->schedule->refresh();
        $this->assertTrue($this->schedule->is_closed);
        $this->assertEquals('FINISHED', $this->schedule->status);

        // 2. Admin reopens schedule
        $reopenResponse = $this->actingAs($this->admin)->post(route('admin.jadwal.toggle-lock', $this->schedule));
        $reopenResponse->assertRedirect();
        $reopenResponse->assertSessionHas('success');

        $this->schedule->refresh();
        $this->assertFalse($this->schedule->is_closed);
        $this->assertEquals('ACTIVE', $this->schedule->status);

        // Verify data intact
        $this->assessment->refresh();
        $this->assertEquals('KOMPETEN', $this->assessment->final_result);
        $this->assertEquals('COMPLETED', $this->assessment->status);
    }

    public function test_asesor_saving_reports_on_reopened_schedule_does_not_auto_close_it(): void
    {
        $this->assessment->update([
            'apl_status' => 'APPROVED',
            'final_result' => 'KOMPETEN',
            'status' => 'COMPLETED',
        ]);
        $this->schedule->update([
            'is_closed' => false,
            'reopened_at' => now(),
            'status' => 'ACTIVE',
        ]);

        $payload = [
            'ak05_aspek_positif' => 'Aspek positif sangat baik',
            'ak05_aspek_negatif' => 'Tidak ada catatan negatif',
            'ak05_rekomendasi' => 'Pertahankan prosedur kerja standar',
            'ak05_has_penolakan' => '0',
            'ak06_valid' => 'YA',
            'ak06_valid_notes' => 'Valid',
            'ak06_reliabel' => 'YA',
            'ak06_reliabel_notes' => 'Reliabel',
            'ak06_fleksibel' => 'YA',
            'ak06_fleksibel_notes' => 'Fleksibel',
            'ak06_adil' => 'YA',
            'ak06_adil_notes' => 'Adil',
            'ak06_catatan_peninjau' => 'Sesuai standar',
            'confirm_draft_review' => '1',
        ];

        // Verify schedule is open for assessor edits when reopened
        $this->assertFalse($this->schedule->isClosedForAssessor($this->asesor));

        $response = $this->actingAs($this->asesor)->post(route('asesor.schedule.reports.submit', $this->schedule), $payload);
        $response->assertRedirect(route('asesor.jadwal.index'));
        $response->assertSessionHas('success');

        // Verify schedule did NOT auto-close because it was reopened by admin!
        $this->schedule->refresh();
        $this->assertFalse($this->schedule->is_closed);
        $this->assertFalse($this->schedule->isClosedForAssessor($this->asesor));
        $this->assertEquals('ACTIVE', $this->schedule->status);

        // Now Admin closes it officially
        $closeResponse = $this->actingAs($this->admin)->post(route('admin.jadwal.toggle-lock', $this->schedule));
        $closeResponse->assertRedirect();
        $this->schedule->refresh();
        $this->assertTrue($this->schedule->is_closed);
        $this->assertTrue($this->schedule->isClosedForAssessor($this->asesor));
        $this->assertEquals('FINISHED', $this->schedule->status);
    }

    public function test_assessor_entering_schedule_redirects_to_mapa_when_still_at_mapa(): void
    {
        $this->asesor->profile()->updateOrCreate(
            ['user_id' => $this->asesor->id],
            ['signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=']
        );

        $this->assessment->update(['apl_status' => 'APPROVED']);

        // When entering via schedule.ruang-uji (the "Masuk Ruang Uji / Masuk Jadwal" button),
        // it must redirect to MAPA 01 (NOT AK 01!)
        $response = $this->actingAs($this->asesor)->get(route('asesor.schedule.ruang-uji', $this->schedule));
        $response->assertRedirect(route('asesor.schedule.mapa', $this->schedule));
        $response->assertSessionHas('warning', 'Harap lengkapi dokumen FR.MAPA.01 terlebih dahulu sebelum masuk ke Ruang Uji.');

        $this->assertEquals(
            route('asesor.schedule.mapa', $this->schedule),
            $this->schedule->getAssessorActiveStepRoute($this->asesor)
        );
    }

    public function test_assessor_entering_schedule_progresses_through_mapa_ak04_ak01_ak07_ruang_uji(): void
    {
        $this->asesor->profile()->updateOrCreate(
            ['user_id' => $this->asesor->id],
            ['signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=']
        );

        $this->assessment->update(['apl_status' => 'APPROVED']);

        // 1. Still at MAPA 01 -> redirects to MAPA
        $res1 = $this->actingAs($this->asesor)->get(route('asesor.schedule.ruang-uji', $this->schedule));
        $res1->assertRedirect(route('asesor.schedule.mapa', $this->schedule));

        // 2. Completed MAPA 01, not yet briefed AK.04 -> redirects to AK 04
        $this->schedule->update(['mapa01_data' => ['tujuan_asesmen' => 'Sertifikasi']]);
        $res2 = $this->actingAs($this->asesor)->get(route('asesor.schedule.ruang-uji', $this->schedule));
        $res2->assertRedirect(route('asesor.schedule.ak04', $this->schedule));

        // 3. Confirmed AK.04 briefing, not yet saved AK.01 -> redirects to AK 01
        $this->schedule->update(['ak04_briefed_at' => now()]);
        $res3 = $this->actingAs($this->asesor)->get(route('asesor.schedule.ruang-uji', $this->schedule));
        $res3->assertRedirect(route('asesor.schedule.ak01', $this->schedule));

        // 4. Saved AK.01, not yet saved AK.07 -> redirects to AK 07
        $this->schedule->update(['ak01_data' => ['evidence' => ['Hasil Observasi Langsung']]]);
        $res4 = $this->actingAs($this->asesor)->get(route('asesor.schedule.ruang-uji', $this->schedule));
        $res4->assertRedirect(route('asesor.schedule.ak07', $this->schedule));

        // 5. Saved AK.07 -> Enters Ruang Uji!
        $this->schedule->update(['ak07_data' => ['notes' => 'Penyesuaian wajar tercatat']]);
        $res5 = $this->actingAs($this->asesor)->get(route('asesor.schedule.ruang-uji', $this->schedule));
        $res5->assertOk();
    }
}
