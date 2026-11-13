<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAk03Item;
use App\Models\AssessmentAppeal;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\Tuk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminScheduleBasedAppealsAndFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $asesor;

    private Scheme $scheme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'full_name' => 'Admin LSP Umpan',
            'email' => 'admin_umpan@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);

        $this->asesor = User::create([
            'full_name' => 'Asesor Penguji 1',
            'email' => 'asesor_penguji1@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'REG.2026.999',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-TEST-01',
            'name' => 'Skema Uji Kompetensi Test',
            'scheme_type' => 'KLASTER',
            'passing_grade_theory' => 70.00,
            'is_active' => true,
        ]);
    }

    public function test_admin_appeals_index_renders_schedule_listing_with_correct_counts_and_ratios(): void
    {
        // Schedule 1: 2 asesi, both filled AK.03 (2/2 Asesi), 1 submitted appeal
        $sch1 = Schedule::create([
            'code' => 'SCH-UMPAN-01',
            'name' => 'Jadwal Asesmen Angkatan 1',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Mandiri 1',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['IA01'],
        ]);
        $sch1->assessors()->attach($this->asesor->id);

        $asesi1 = User::create([
            'full_name' => 'Asesi Umpan 1',
            'email' => 'asesi_umpan1@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);
        $assessment1 = Assessment::create([
            'schedule_id' => $sch1->id,
            'asesi_id' => $asesi1->id,
            'assessor_id' => $this->asesor->id,
            'status' => 'COMPLETED',
            'final_result' => 'BELUM_KOMPETEN',
        ]);
        AssessmentAk03Item::create([
            'assessment_id' => $assessment1->id,
            'ratings_json' => [
                1 => ['value' => 'YA', 'notes' => 'Sangat jelas'],
                2 => ['value' => 'YA', 'notes' => ''],
            ],
            'general_notes' => 'Pelayanan asesmen sangat baik.',
        ]);
        AssessmentAppeal::create([
            'assessment_id' => $assessment1->id,
            'appeal_date' => now()->toDateString(),
            'appeal_reasons' => 'Keberatan hasil observasi.',
            'status' => 'SUBMITTED',
        ]);

        $asesi2 = User::create([
            'full_name' => 'Asesi Umpan 2',
            'email' => 'asesi_umpan2@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);
        $assessment2 = Assessment::create([
            'schedule_id' => $sch1->id,
            'asesi_id' => $asesi2->id,
            'assessor_id' => $this->asesor->id,
            'status' => 'COMPLETED',
            'final_result' => 'KOMPETEN',
        ]);
        AssessmentAk03Item::create([
            'assessment_id' => $assessment2->id,
            'ratings_json' => [
                1 => ['value' => 'YA', 'notes' => ''],
            ],
            'general_notes' => 'Lancar dan nyaman.',
        ]);

        // Schedule 2: 1 asesi, not filled (0/1 Asesi), 0 appeals
        $sch2 = Schedule::create([
            'code' => 'SCH-UMPAN-02',
            'name' => 'Jadwal Asesmen Angkatan 2',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Mandiri 2',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['IA01'],
        ]);
        $sch2->assessors()->attach($this->asesor->id);

        $asesi3 = User::create([
            'full_name' => 'Asesi Umpan 3',
            'email' => 'asesi_umpan3@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);
        Assessment::create([
            'schedule_id' => $sch2->id,
            'asesi_id' => $asesi3->id,
            'assessor_id' => $this->asesor->id,
            'status' => 'IN_PROGRESS',
            'final_result' => 'BELUM_SELESAI',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.appeals.index'));
        $response->assertOk();

        // Check Schedule 1 rendering
        $response->assertSee('SCH-UMPAN-01');
        $response->assertSee('2/2 Asesi');
        $response->assertSee('1 Banding');

        // Check Schedule 2 rendering
        $response->assertSee('SCH-UMPAN-02');
        $response->assertSee('0/1 Asesi');
        $response->assertSee('0 Banding');
    }

    public function test_admin_appeals_filter_has_appeals_isolates_only_relevant_schedules(): void
    {
        $schWithAppeal = Schedule::create([
            'code' => 'SCH-HAS-APPEAL',
            'name' => 'Jadwal Ada Banding',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK 1',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['IA01'],
        ]);

        $asesi = User::create([
            'full_name' => 'Peserta Banding',
            'email' => 'peserta_banding@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);
        $assessment = Assessment::create([
            'schedule_id' => $schWithAppeal->id,
            'asesi_id' => $asesi->id,
            'status' => 'COMPLETED',
            'final_result' => 'BELUM_KOMPETEN',
        ]);
        AssessmentAppeal::create([
            'assessment_id' => $assessment->id,
            'appeal_date' => now()->toDateString(),
            'appeal_reasons' => 'Alasan keberatan.',
            'status' => 'SUBMITTED',
        ]);

        $schWithoutAppeal = Schedule::create([
            'code' => 'SCH-NO-APPEAL',
            'name' => 'Jadwal Tanpa Banding',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK 2',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['IA01'],
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.appeals.index', ['filter' => 'has_appeals']));
        $response->assertOk();
        $response->assertSee('SCH-HAS-APPEAL');
        $response->assertDontSee('SCH-NO-APPEAL');

        // Test pending_appeals filter
        $responsePending = $this->actingAs($this->admin)->get(route('admin.appeals.index', ['filter' => 'pending_appeals']));
        $responsePending->assertOk();
        $responsePending->assertSee('SCH-HAS-APPEAL');
        $responsePending->assertDontSee('SCH-NO-APPEAL');
    }

    public function test_admin_appeals_show_displays_schedule_participants_feedbacks_and_appeals(): void
    {
        $sch = Schedule::create([
            'code' => 'SCH-DETAIL-01',
            'name' => 'Jadwal Detail Test',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Utama LSP',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['IA01'],
        ]);
        $sch->assessors()->attach($this->asesor->id);

        $asesi = User::create([
            'full_name' => 'Doni Darmawan',
            'email' => 'doni@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
            'phone' => '081234567899',
        ]);

        $assessment = Assessment::create([
            'schedule_id' => $sch->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $this->asesor->id,
            'status' => 'COMPLETED',
            'final_result' => 'KOMPETEN',
        ]);

        AssessmentAk03Item::create([
            'assessment_id' => $assessment->id,
            'ratings_json' => [
                1 => ['value' => 'YA', 'notes' => 'Mantap'],
            ],
            'general_notes' => 'Sangat memuaskan.',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.appeals.show', $sch->id));
        $response->assertOk();
        $response->assertSee('SCH-DETAIL-01');
        $response->assertSee('Doni Darmawan');
        $response->assertSee('Sudah Mengisi');
        $response->assertSee('Sangat memuaskan.');
    }

    public function test_tuk_admin_can_only_view_their_own_tuk_schedule(): void
    {
        $tuk1 = Tuk::create([
            'code' => 'TUK-01',
            'name' => 'TUK Pertama',
            'type' => 'SEWAKTU',
            'address' => 'Jl. Pertama',
        ]);

        $tuk2 = Tuk::create([
            'code' => 'TUK-02',
            'name' => 'TUK Kedua',
            'type' => 'SEWAKTU',
            'address' => 'Jl. Kedua',
        ]);

        $tukAdmin = User::create([
            'full_name' => 'Admin TUK 1',
            'email' => 'tuk1@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'TUK_ADMIN',
            'status' => 'ACTIVE',
            'tuk_id' => $tuk1->id,
        ]);

        $sch1 = Schedule::create([
            'code' => 'SCH-TUK-1',
            'name' => 'Jadwal TUK 1',
            'scheme_id' => $this->scheme->id,
            'tuk_id' => $tuk1->id,
            'tuk_name' => $tuk1->name,
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['IA01'],
        ]);

        $sch2 = Schedule::create([
            'code' => 'SCH-TUK-2',
            'name' => 'Jadwal TUK 2',
            'scheme_id' => $this->scheme->id,
            'tuk_id' => $tuk2->id,
            'tuk_name' => $tuk2->name,
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '16:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['IA01'],
        ]);

        // TUK Admin visits index: only sees SCH-TUK-1
        $resIndex = $this->actingAs($tukAdmin)->get(route('admin.appeals.index'));
        $resIndex->assertOk();
        $resIndex->assertSee('SCH-TUK-1');
        $resIndex->assertDontSee('SCH-TUK-2');

        // TUK Admin visits show for their own schedule: 200 OK
        $resShowOwn = $this->actingAs($tukAdmin)->get(route('admin.appeals.show', $sch1->id));
        $resShowOwn->assertOk();

        // TUK Admin visits show for another TUK: 403 Forbidden
        $resShowOther = $this->actingAs($tukAdmin)->get(route('admin.appeals.show', $sch2->id));
        $resShowOther->assertStatus(403);
    }
}
