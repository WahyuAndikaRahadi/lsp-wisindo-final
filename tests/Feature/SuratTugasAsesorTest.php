<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuratTugasAsesorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $assignedAssessor;

    private User $otherAssessor;

    private Scheme $scheme;

    private Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'full_name' => 'Admin LSP Utama',
            'email' => 'admin_st@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);

        $this->assignedAssessor = User::create([
            'full_name' => 'Saptono Asesor',
            'email' => 'saptono@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => '000.003672.2016',
        ]);

        $this->otherAssessor = User::create([
            'full_name' => 'Budi Asesor Lain',
            'email' => 'budi_other@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => '000.009999.2024',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SCH-MEK-JR',
            'name' => 'Skema Sertifikasi Mekanik Junior',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);

        $this->schedule = Schedule::create([
            'code' => 'JADWAL-ST-001',
            'name' => 'Uji Kompetensi Mekanik Junior',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'Gedung SMKS Inovasi Mandiri',
            'tuk_address' => 'Jl. Umar Wirahadikusumah KM. 25 Darmaraja',
            'date' => '2026-10-12',
            'end_date' => '2026-10-16',
            'start_time' => '07:00',
            'end_time' => '17:00',
            'estimated_candidates' => 88,
            'surat_tugas_number' => '022/ST/PRO-INDO/INT/IX/2026',
            'surat_tugas_date' => '2026-09-21',
            'letter_city' => 'Bekasi',
            'director_name' => 'RIDWAN',
            'status' => 'ACTIVE',
            'is_closed' => false,
            'created_by' => $this->admin->id,
            'selected_instruments' => ['AK01', 'IA01', 'IA02'],
        ]);

        $this->schedule->assessors()->attach($this->assignedAssessor->id);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('asesor.schedule.surat-tugas', $this->schedule->id))
            ->assertRedirect(route('login'));

        $this->get(route('admin.jadwal.surat-tugas', $this->schedule->id))
            ->assertRedirect(route('login'));
    }

    public function test_assigned_assessor_can_view_surat_tugas(): void
    {
        $response = $this->actingAs($this->assignedAssessor)
            ->get(route('asesor.schedule.surat-tugas', $this->schedule->id));

        $response->assertOk();
        $response->assertSee('SURAT TUGAS ASESOR');
        $response->assertSee('022/ST/PRO-INDO/INT/IX/2026');
        $response->assertSee('Gedung SMKS Inovasi Mandiri');
        $response->assertSee('Jl. Umar Wirahadikusumah KM. 25 Darmaraja');
        $response->assertSee('Skema Sertifikasi Mekanik Junior');
        $response->assertSee('88 siswa');
        $response->assertSee('Saptono Asesor');
        $response->assertSee('000.003672.2016');
        $response->assertSee('RIDWAN');
        $response->assertSee('Bekasi');
    }

    public function test_unassigned_assessor_cannot_view_surat_tugas(): void
    {
        $response = $this->actingAs($this->otherAssessor)
            ->get(route('asesor.schedule.surat-tugas', $this->schedule->id));

        $response->assertForbidden();
    }

    public function test_admin_can_view_surat_tugas(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.jadwal.surat-tugas', $this->schedule->id));

        $response->assertOk();
        $response->assertSee('SURAT TUGAS ASESOR');
        $response->assertSee('022/ST/PRO-INDO/INT/IX/2026');
    }

    public function test_admin_can_create_schedule_with_surat_tugas_fields(): void
    {
        $payload = [
            'name' => 'Jadwal Baru dengan ST',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Mitra Industri',
            'tuk_address' => 'Jl. Industri No. 10 Jakarta Barat',
            'date' => '2026-11-10',
            'end_date' => '2026-11-12',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'estimated_candidates' => 45,
            'selected_instruments' => ['AK01', 'IA01'],
            'assessor_ids' => [$this->assignedAssessor->id],
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.jadwal.store'), $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('t_schedules', [
            'name' => 'Jadwal Baru dengan ST',
            'tuk_address' => 'Jl. Industri No. 10 Jakarta Barat',
            'estimated_candidates' => 45,
            'letter_city' => 'Bekasi',
            'director_name' => 'RIDWAN',
        ]);
    }

    public function test_admin_detail_page_renders_clean_layout_without_code_clutter(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.jadwal.show', $this->schedule->id));

        $response->assertOk();
        $response->assertSee('Skema Sertifikasi');
        $response->assertSee('Tempat Uji Kompetensi');
        $response->assertSee('Waktu Pelaksanaan');
        $response->assertSee('Peserta &amp; Surat Tugas', false);
        $response->assertSee('Gedung SMKS Inovasi Mandiri');
    }

    public function test_schedule_formatted_accessors(): void
    {
        // Single day
        $singleDay = Schedule::create([
            'code' => 'JADWAL-SINGLE',
            'name' => 'Jadwal Satu Hari',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Lab 1',
            'date' => '2026-10-15',
            'end_date' => '2026-10-15',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'status' => 'ACTIVE',
            'is_closed' => false,
            'created_by' => $this->admin->id,
            'selected_instruments' => ['AK01'],
        ]);

        $this->assertStringContainsString('15 Oktober 2026', $singleDay->formatted_date_range);

        // Multi day
        $multiDay = Schedule::create([
            'code' => 'JADWAL-MULTI',
            'name' => 'Jadwal Multi Hari',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Lab 2',
            'date' => '2026-10-12',
            'end_date' => '2026-10-16',
            'start_time' => '07:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'is_closed' => false,
            'created_by' => $this->admin->id,
            'selected_instruments' => ['AK01'],
        ]);

        $this->assertStringContainsString('12', $multiDay->formatted_date_range);
        $this->assertStringContainsString('16 Oktober 2026', $multiDay->formatted_date_range);
        $this->assertStringContainsString('s.d', $multiDay->formatted_date_range);

        // Fallbacks
        $this->assertEquals('-', $singleDay->effective_tuk_address);
        $this->assertEquals(0, $singleDay->effective_candidate_count);
    }
}
