<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Services\MukExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScheduleClosureAndAbsentCandidateTest extends TestCase
{
    use RefreshDatabase;

    protected User $assessor;

    protected User $admin;

    protected Scheme $scheme;

    protected Schedule $schedule;

    protected Assessment $assessment1;

    protected Assessment $assessment2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assessor = User::create([
            'full_name' => 'Asesor Penutup',
            'email' => 'asesor_closure@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'MET.000.123456',
        ]);

        $this->admin = User::create([
            'full_name' => 'Admin LSP Closure',
            'email' => 'admin_closure@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-CLOSURE-01',
            'name' => 'Skema Penutupan Jadwal',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);

        $this->schedule = Schedule::create([
            'code' => 'SCH-CLOSURE-01',
            'name' => 'Jadwal Penutupan Asesmen',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['AK01'],
        ]);
        $this->schedule->assessors()->attach($this->assessor->id);

        $asesi1 = User::create([
            'full_name' => 'Asesi Selesai',
            'email' => 'asesi1_close@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $asesi2 = User::create([
            'full_name' => 'Asesi Belum Selesai',
            'email' => 'asesi2_close@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        // Assessment 1 is completed with Kompeten
        $this->assessment1 = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $asesi1->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'APPROVED',
            'status' => 'COMPLETED',
            'final_result' => 'KOMPETEN',
        ]);

        // Assessment 2 is still IN_PROGRESS / uncompleted
        $this->assessment2 = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $asesi2->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'APPROVED',
            'status' => 'IN_PROGRESS',
            'final_result' => 'BELUM_SELESAI',
        ]);
    }

    public function test_submit_reports_does_not_close_schedule_if_candidate_uncompleted(): void
    {
        $payload = [
            'ak05_aspek_positif' => 'Asesi menguasai materi teknis dengan baik dan presisi.',
            'ak05_aspek_negatif' => 'Waktu pengerjaan perlu lebih diefisiensikan kembali.',
            'ak05_rekomendasi' => 'Pertahankan kompetensi dan tingkatkan kecepatan kerja.',
            'ak06_valid' => 'YA',
            'ak06_valid_notes' => 'Instrumen sesuai skema.',
            'ak06_reliabel' => 'YA',
            'ak06_reliabel_notes' => 'Konsistensi penilaian tercapai.',
            'ak06_fleksibel' => 'YA',
            'ak06_fleksibel_notes' => 'Tersedia opsi instrumen pendukung.',
            'ak06_adil' => 'YA',
            'ak06_adil_notes' => 'Tidak ada perlakuan diskriminatif.',
            'ak06_catatan_peninjau' => 'Proses asesmen secara menyeluruh terlaksana dengan baik.',
            'confirm_draft_review' => '1',
        ];

        $response = $this->actingAs($this->assessor)->post(
            route('asesor.schedule.reports.submit', $this->schedule),
            $payload
        );

        // Must redirect to asesi list with warning
        $response->assertRedirect(route('asesor.jadwal.asesi', $this->schedule->id));
        $response->assertSessionHas('warning');

        $this->schedule->refresh();
        // Reports are safely saved
        $this->assertNotEmpty($this->schedule->ak05_report);
        $this->assertNotEmpty($this->schedule->ak06_review);
        // Schedule is NOT closed
        $this->assertFalse((bool) $this->schedule->is_closed);
        $this->assertEquals('ACTIVE', $this->schedule->status);
    }

    public function test_assessor_can_mark_candidate_absent(): void
    {
        $response = $this->actingAs($this->assessor)->post(
            route('asesor.assessment.mark-absent', $this->assessment2),
            ['notes' => 'Asesi berhalangan hadir karena sakit.']
        );

        $response->assertRedirect();
        $this->assessment2->refresh();

        $this->assertEquals('TIDAK_HADIR', $this->assessment2->final_result);
        $this->assertEquals('COMPLETED', $this->assessment2->status);
        $this->assertNotNull($this->assessment2->completed_at);
        $this->assertEquals('Asesi berhalangan hadir karena sakit.', $this->assessment2->final_notes);
    }

    public function test_admin_can_mark_candidate_absent(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.assessment.mark-absent', $this->assessment2),
            ['notes' => 'Dikonfirmasi tidak hadir oleh admin LSP.']
        );

        $response->assertRedirect();
        $this->assessment2->refresh();

        $this->assertEquals('TIDAK_HADIR', $this->assessment2->final_result);
        $this->assertEquals('COMPLETED', $this->assessment2->status);
    }

    public function test_schedule_can_be_closed_when_all_candidates_are_completed_or_absent(): void
    {
        // Mark candidate 2 absent
        $this->actingAs($this->assessor)->post(
            route('asesor.assessment.mark-absent', $this->assessment2),
            ['notes' => 'Tidak hadir.']
        );

        $payload = [
            'ak05_aspek_positif' => 'Asesi menguasai materi teknis dengan baik dan presisi.',
            'ak05_aspek_negatif' => 'Waktu pengerjaan perlu lebih diefisiensikan kembali.',
            'ak05_rekomendasi' => 'Pertahankan kompetensi dan tingkatkan kecepatan kerja.',
            'ak06_valid' => 'YA',
            'ak06_valid_notes' => 'Instrumen sesuai skema.',
            'ak06_reliabel' => 'YA',
            'ak06_reliabel_notes' => 'Konsistensi penilaian tercapai.',
            'ak06_fleksibel' => 'YA',
            'ak06_fleksibel_notes' => 'Tersedia opsi instrumen pendukung.',
            'ak06_adil' => 'YA',
            'ak06_adil_notes' => 'Tidak ada perlakuan diskriminatif.',
            'ak06_catatan_peninjau' => 'Proses asesmen secara menyeluruh terlaksana dengan baik.',
            'confirm_draft_review' => '1',
        ];

        $response = $this->actingAs($this->assessor)->post(
            route('asesor.schedule.reports.submit', $this->schedule),
            $payload
        );

        $response->assertRedirect(route('asesor.jadwal.index'));
        $response->assertSessionHas('success');

        $this->schedule->refresh();
        $this->assertTrue((bool) $this->schedule->is_closed);
        $this->assertEquals('FINISHED', $this->schedule->status);
    }

    public function test_muk_export_service_and_views_handle_tidak_hadir(): void
    {
        // Mark candidate 2 absent
        $this->assessment2->update([
            'final_result' => 'TIDAK_HADIR',
            'status' => 'COMPLETED',
        ]);

        $exportService = app(MukExportService::class);
        $data = $exportService->getExportData($this->schedule);

        $this->assertEquals(2, $data['total_asesi']);
        $this->assertEquals(1, $data['total_kompeten']);
        $this->assertEquals(0, $data['total_belum_kompeten']);
        $this->assertEquals(1, $data['total_tidak_hadir']);

        // Test Berita Acara Export view rendering
        $responseBa = $this->actingAs($this->admin)->get(
            route('admin.dokumen-muk.export-ba', $this->schedule)
        );
        $responseBa->assertOk()
            ->assertSee('Tidak Hadir')
            ->assertSee('Asesi Belum Selesai');

        // Test AK.05 Export view rendering
        $responseAk05 = $this->actingAs($this->admin)->get(
            route('admin.dokumen-muk.export', ['schedule' => $this->schedule->id, 'type' => 'ak05'])
        );
        $responseAk05->assertOk()
            ->assertSee('Tidak Hadir');
    }
}
