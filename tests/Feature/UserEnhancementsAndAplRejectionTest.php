<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserEnhancementsAndAplRejectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $adminLsp;

    protected User $asesorA;

    protected User $asesorB;

    protected User $asesi;

    protected Scheme $scheme;

    protected Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'full_name' => 'Super Administrator',
            'email' => 'superadmin@example.com',
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->adminLsp = User::factory()->create([
            'full_name' => 'Admin LSP Staff',
            'email' => 'adminlsp@example.com',
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);

        // Create assessors in non-alphabetical insertion order to test sorting
        $this->asesorB = User::factory()->create([
            'full_name' => 'Zulfa Asesor',
            'email' => 'zulfa@example.com',
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'REG-999',
        ]);

        $this->asesorA = User::factory()->create([
            'full_name' => 'Ahmad Asesor',
            'email' => 'ahmad@example.com',
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'REG-111',
        ]);

        $this->asesi = User::factory()->create([
            'full_name' => 'Bambang Asesi',
            'email' => 'bambang@example.com',
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-001',
            'name' => 'Teknik Otomotif',
            'scheme_type' => 'KLASTER',
            'is_active' => true,
        ]);

        $this->schedule = Schedule::create([
            'scheme_id' => $this->scheme->id,
            'name' => 'Jadwal Uji November',
            'code' => 'JDW-NOV',
            'tuk_name' => 'TUK Mandiri SMK',
            'status' => 'ACTIVE',
            'is_closed' => false,
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'created_by' => $this->superAdmin->id,
            'selected_instruments' => ['AK01', 'IA01'],
        ]);

        $this->schedule->assessors()->attach([$this->asesorB->id, $this->asesorA->id]);
    }

    public function test_user_index_filters_by_role_and_search_query(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.users.index', [
            'role' => 'ASESOR',
        ]));

        $response->assertOk();
        $response->assertSee('Ahmad Asesor');
        $response->assertSee('Zulfa Asesor');
        $response->assertDontSee('Bambang Asesi');

        // Search by registration number
        $searchResponse = $this->actingAs($this->superAdmin)->get(route('admin.users.index', [
            'search' => 'REG-111',
        ]));

        $searchResponse->assertOk();
        $searchResponse->assertSee('Ahmad Asesor');
        $searchResponse->assertDontSee('Zulfa Asesor');
    }

    public function test_super_admin_can_reset_user_password_to_proindo123(): void
    {
        $targetUser = User::factory()->create([
            'email' => 'target@example.com',
            'password' => Hash::make('old-secret-password'),
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.users.reset-password', $targetUser));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $targetUser->refresh();
        $this->assertTrue(Hash::check('proindo123', $targetUser->password));
    }

    public function test_non_super_admin_cannot_reset_user_password(): void
    {
        $targetUser = User::factory()->create([
            'password' => Hash::make('old-secret-password'),
        ]);

        $response = $this->actingAs($this->adminLsp)->post(route('admin.users.reset-password', $targetUser));

        $response->assertForbidden();
    }

    public function test_assessors_are_ordered_alphabetically_in_schedule_and_relationship(): void
    {
        $orderedAssessors = $this->schedule->assessors;
        $this->assertSame('Ahmad Asesor', $orderedAssessors->first()->full_name);
        $this->assertSame('Zulfa Asesor', $orderedAssessors->last()->full_name);

        $createPageResponse = $this->actingAs($this->superAdmin)->get(route('admin.jadwal.create'));
        $createPageResponse->assertOk();
        $assessorsInView = $createPageResponse->viewData('assessors');
        $this->assertSame('Ahmad Asesor', $assessorsInView->first()->full_name);
    }

    public function test_rejected_apl_removes_asesi_from_assessor_pov_and_blocks_asesi_from_ruang_uji(): void
    {
        $assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'scheme_id' => $this->scheme->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->asesorA->id,
            'apl_status' => 'SUBMITTED',
            'admin_status' => 'APPROVED',
            'status' => 'IN_PROGRESS',
        ]);

        // Assessor A sees the candidate before rejection
        $this->assertTrue($this->schedule->assessorAssessments($this->asesorA)->contains('id', $assessment->id));

        // Assessor A rejects the APL
        $rejectResponse = $this->actingAs($this->asesorA)->post(route('asesor.review-apl.update', $assessment), [
            'apl_status' => 'REJECTED',
            'notes' => 'Dokumen persyaratan dasar tidak valid atau salah jadwal.',
        ]);

        $rejectResponse->assertRedirect(route('asesor.schedule.instrument', [
            'schedule' => $this->schedule->id,
            'code' => 'apl',
        ]));

        $assessment->refresh();
        $this->assertSame('REJECTED', $assessment->apl_status);
        $this->assertSame('REJECTED', $assessment->admin_status);
        $this->assertSame('REJECTED', $assessment->status);

        // Candidate is now removed from assessor's active pool
        $this->schedule->unsetRelation('assessments');
        $this->assertFalse($this->schedule->assessorAssessments($this->asesorA)->contains('id', $assessment->id));
        $this->assertFalse($this->schedule->assessorAssessmentsQuery($this->asesorA)->where('id', $assessment->id)->exists());

        // Asesi is blocked from accessing Ruang Uji
        $ruangUjiResponse = $this->actingAs($this->asesi)->get(route('asesi.assessment.ruang-uji', $assessment));
        $ruangUjiResponse->assertRedirect(route('asesi.assessment.apl', $assessment));

        // Asesi is blocked from re-submitting APL
        $submitResponse = $this->actingAs($this->asesi)->post(route('asesi.assessment.apl.store', $assessment), [
            'tujuan_asesmen' => 'Sertifikasi',
        ]);
        $submitResponse->assertSessionHas('error');

        // Asesi cannot re-join this schedule
        // Need asesi profile to be complete for joining
        $this->asesi->profile()->create([
            'nik' => '3201010101010001',
            'tempat_lahir' => 'Bogor',
            'tanggal_lahir' => '2000-01-01',
            'jenis_kelamin' => 'L',
            'alamat_rumah' => 'Jl. Merdeka No 1',
            'kode_pos' => '16111',
            'pendidikan_terakhir' => 'SMK',
            'jabatan' => 'Teknisi',
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ]);
        $this->asesi->update(['phone' => '08123456789']);

        $joinResponse = $this->actingAs($this->asesi)->post(route('asesi.jadwal.join'), [
            'code' => $this->schedule->code,
        ]);
        $joinResponse->assertSessionHas('error');
    }
}
