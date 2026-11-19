<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\AssessmentProof;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminRemoveCandidateAndIa05ResetTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $assessor;

    protected User $otherAssessor;

    protected User $asesi;

    protected Scheme $scheme;

    protected Schedule $schedule;

    protected Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->admin = User::create([
            'full_name' => 'Admin LSP Test',
            'email' => 'admin_remove_test@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);

        $this->assessor = User::create([
            'full_name' => 'Asesor Assigned',
            'email' => 'asesor_assigned@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'MET.000.888999',
        ]);

        $this->otherAssessor = User::create([
            'full_name' => 'Asesor Lain',
            'email' => 'asesor_lain@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'MET.000.111222',
        ]);

        $this->asesi = User::create([
            'full_name' => 'Asesi Ujicoba',
            'email' => 'asesi_ujicoba@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-TEST-001',
            'name' => 'Skema Sertifikasi Ujicoba',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);

        $this->schedule = Schedule::create([
            'code' => 'SCH-TEST-001',
            'name' => 'Jadwal Asesmen Ujicoba',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Ujicoba',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['IA01', 'IA02', 'IA05'],
        ]);
        $this->schedule->assessors()->attach($this->assessor->id);

        $this->assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'APPROVED',
            'status' => 'IN_PROGRESS',
        ]);
    }

    public function test_admin_can_remove_candidate_from_schedule_and_purge_all_assessment_data(): void
    {
        // 1. Create proof file
        $proofPath = 'proofs/test_portfolio.pdf';
        Storage::disk('local')->put($proofPath, 'dummy proof content');

        AssessmentProof::create([
            'assessment_id' => $this->assessment->id,
            'proof_type' => 'KTP',
            'proof_category' => 'PERSYARATAN_DASAR',
            'file_path' => $proofPath,
            'original_filename' => 'ktp.pdf',
        ]);

        // 2. Create IA evidence file and IA response
        $iaEvidencePath = 'ia_evidence/test_ia01b.jpg';
        Storage::disk('local')->put($iaEvidencePath, 'dummy image content');

        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA01',
            'data_json' => [
                'evidence_image_path' => $iaEvidencePath,
                'status' => 'SUBMITTED',
            ],
            'score' => 85.00,
        ]);

        // 3. Create dummy MUK cache file in storage_path('app/muk-cache')
        $cacheDir = storage_path('app/muk-cache');
        if (! File::isDirectory($cacheDir)) {
            File::makeDirectory($cacheDir, 0755, true);
        }
        $dummyCacheFile = $cacheDir."/dyn_{$this->schedule->id}_sch_ak05.pdf";
        File::put($dummyCacheFile, 'dummy pdf cache');
        $this->assertTrue(File::exists($dummyCacheFile));

        // 4. Perform DELETE request as Admin
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.jadwal.remove-candidate', [
                'jadwal' => $this->schedule->id,
                'assessment' => $this->assessment->id,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 5. Assert database records purged
        $this->assertDatabaseMissing('t_assessments', ['id' => $this->assessment->id]);
        $this->assertDatabaseMissing('t_assessment_proofs', ['assessment_id' => $this->assessment->id]);
        $this->assertDatabaseMissing('t_assessment_ia_responses', ['assessment_id' => $this->assessment->id]);

        // 6. Assert files deleted from storage
        Storage::disk('local')->assertMissing($proofPath);
        Storage::disk('local')->assertMissing($iaEvidencePath);

        // 7. Assert MUK cache deleted
        $this->assertFalse(File::exists($dummyCacheFile));

        // 8. Assert Asesi User account still exists
        $this->assertDatabaseHas('users', ['id' => $this->asesi->id]);
    }

    public function test_remove_candidate_aborts_404_if_schedule_mismatch(): void
    {
        $otherSchedule = Schedule::create([
            'code' => 'SCH-TEST-002',
            'name' => 'Jadwal Lain',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Lain',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['IA01'],
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.jadwal.remove-candidate', [
                'jadwal' => $otherSchedule->id,
                'assessment' => $this->assessment->id,
            ]));

        $response->assertStatus(404);
        $this->assertDatabaseHas('t_assessments', ['id' => $this->assessment->id]);
    }

    public function test_unauthorized_user_cannot_remove_candidate(): void
    {
        // Asesi cannot delete
        $response = $this->actingAs($this->asesi)
            ->delete(route('admin.jadwal.remove-candidate', [
                'jadwal' => $this->schedule->id,
                'assessment' => $this->assessment->id,
            ]));

        $response->assertStatus(403);

        // Assessor cannot delete
        $responseAssessor = $this->actingAs($this->assessor)
            ->delete(route('admin.jadwal.remove-candidate', [
                'jadwal' => $this->schedule->id,
                'assessment' => $this->assessment->id,
            ]));

        $responseAssessor->assertStatus(403);
    }

    public function test_assessor_can_reset_ia05_for_remedial(): void
    {
        // Setup IA05 as submitted with Belum Kompeten score
        $ia05 = AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA05',
            'score' => 45.00,
            'asesi_signature' => 'data:image/png;base64,dummy',
            'asesi_signed_at' => now(),
            'assessor_signature' => 'data:image/png;base64,dummy',
            'assessor_signed_at' => now(),
            'data_json' => [
                'answers' => ['1' => 'A', '2' => 'B', '3' => 'C'],
                'doubtful' => ['2'],
                'status' => 'SUBMITTED',
            ],
        ]);

        $this->assessment->update([
            'status' => 'COMPLETED',
            'final_result' => 'BELUM_KOMPETEN',
        ]);

        // Dummy cache files for IA05 and summary
        $cacheDir = storage_path('app/muk-cache');
        if (! File::isDirectory($cacheDir)) {
            File::makeDirectory($cacheDir, 0755, true);
        }
        $cacheIa05 = $cacheDir."/dyn_{$this->schedule->id}_{$this->assessment->id}_ia05.pdf";
        File::put($cacheIa05, 'dummy ia05 cache');

        // Assessor calls reset
        $response = $this->actingAs($this->assessor)
            ->post(route('asesor.assessment.ia05.reset', $this->assessment));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Refresh models
        $ia05->refresh();
        $this->assessment->refresh();

        // Check IA05 status & fields
        $this->assertNull($ia05->score);
        $this->assertNull($ia05->asesi_signed_at);
        $this->assertNull($ia05->asesi_signature);
        $this->assertNull($ia05->assessor_signed_at);
        $this->assertNull($ia05->assessor_signature);
        $this->assertEquals('DRAFT', $ia05->data_json['status']);
        // Crucial: previous answers are kept so asesi can review/remedial without starting from scratch
        $this->assertEquals(['1' => 'A', '2' => 'B', '3' => 'C'], $ia05->data_json['answers']);

        // Check assessment status is reverted to IN_PROGRESS
        $this->assertEquals('IN_PROGRESS', $this->assessment->status);
        $this->assertEquals('BELUM_SELESAI', $this->assessment->final_result);

        // Check cache file was purged
        $this->assertFalse(File::exists($cacheIa05));
    }

    public function test_assessor_cannot_reset_ia05_if_schedule_is_closed(): void
    {
        $this->schedule->update(['is_closed' => true]);

        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA05',
            'score' => 50.00,
            'asesi_signed_at' => now(),
            'data_json' => ['status' => 'SUBMITTED'],
        ]);

        $response = $this->actingAs($this->assessor)
            ->post(route('asesor.assessment.ia05.reset', $this->assessment));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Jadwal asesmen sudah resmi ditutup. Status ujian tidak dapat di-reset.');
    }

    public function test_unassigned_assessor_cannot_reset_ia05(): void
    {
        $response = $this->actingAs($this->otherAssessor)
            ->post(route('asesor.assessment.ia05.reset', $this->assessment));

        $response->assertStatus(403);
    }
}
