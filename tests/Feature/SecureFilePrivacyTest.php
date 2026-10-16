<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\AssessmentProof;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecureFilePrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected User $asesi;

    protected User $otherAsesi;

    protected User $assessor;

    protected User $admin;

    protected Assessment $assessment;

    protected AssessmentProof $proof;

    protected AssessmentIaResponse $iaResponse;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->asesi = User::create([
            'full_name' => 'Asesi Uji Coba',
            'email' => 'asesi_test@example.com',
            'phone' => '081234567891',
            'password' => Hash::make('password'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->otherAsesi = User::create([
            'full_name' => 'Asesi Lain',
            'email' => 'asesi_lain@example.com',
            'phone' => '081234567892',
            'password' => Hash::make('password'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->assessor = User::create([
            'full_name' => 'Asesor Uji Coba',
            'email' => 'asesor_test@example.com',
            'phone' => '081234567893',
            'password' => Hash::make('password'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'MET.000.001234',
        ]);

        $this->admin = User::create([
            'full_name' => 'Admin LSP Uji Coba',
            'email' => 'admin_test@example.com',
            'phone' => '081234567894',
            'password' => Hash::make('password'),
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);

        $scheme = Scheme::create([
            'code' => 'SKM-TEST-01',
            'name' => 'Skema Pengujian Privasi Berkas',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);

        $schedule = Schedule::create([
            'code' => 'SCH-TEST-01',
            'name' => 'Jadwal Uji Coba',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['AK01', 'IA01', 'IA02', 'IA03', 'IA05'],
        ]);
        $schedule->assessors()->attach($this->assessor->id);

        $this->assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'APPROVED',
            'status' => 'ACTIVE',
        ]);

        // Put a fake file on local storage
        $proofPath = 'proofs/test_ktp.jpg';
        Storage::disk('local')->put($proofPath, 'dummy-ktp-content');

        $this->proof = AssessmentProof::create([
            'assessment_id' => $this->assessment->id,
            'proof_type' => 'KTP',
            'proof_category' => 'ADMINISTRATIF',
            'file_path' => $proofPath,
            'original_filename' => 'ktp_original.jpg',
        ]);

        $evidencePath = 'ia02_evidence/test_lembar.jpg';
        Storage::disk('local')->put($evidencePath, 'dummy-evidence-content');

        $this->iaResponse = AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA02',
            'data_json' => [
                'evidence_image_path' => $evidencePath,
                'evidence_original_name' => 'lembar_praktik.jpg',
                'evidence_uploaded_at' => now()->toIso8601String(),
            ],
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('secure-file.proof', $this->proof));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('secure-file.evidence', $this->iaResponse));
        $response->assertRedirect(route('login'));
    }

    public function test_unauthorized_asesi_is_forbidden(): void
    {
        $this->actingAs($this->otherAsesi)
            ->get(route('secure-file.proof', $this->proof))
            ->assertStatus(403);

        $this->actingAs($this->otherAsesi)
            ->get(route('secure-file.evidence', $this->iaResponse))
            ->assertStatus(403);
    }

    public function test_owner_asesi_can_access_own_files(): void
    {
        $this->actingAs($this->asesi)
            ->get(route('secure-file.proof', $this->proof))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="ktp_original.jpg"');

        $this->actingAs($this->asesi)
            ->get(route('secure-file.evidence', $this->iaResponse))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="lembar_praktik.jpg"');
    }

    public function test_assigned_assessor_can_access_candidate_files(): void
    {
        $this->actingAs($this->assessor)
            ->get(route('secure-file.proof', $this->proof))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="ktp_original.jpg"');

        $this->actingAs($this->assessor)
            ->get(route('secure-file.evidence', $this->iaResponse))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="lembar_praktik.jpg"');
    }

    public function test_admin_lsp_can_access_files(): void
    {
        $this->actingAs($this->admin)
            ->get(route('secure-file.proof', $this->proof))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="ktp_original.jpg"');

        $this->actingAs($this->admin)
            ->get(route('secure-file.evidence', $this->iaResponse))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'inline; filename="lembar_praktik.jpg"');
    }

    public function test_fallback_to_public_disk_works_for_legacy_files(): void
    {
        $legacyProofPath = 'proofs/legacy_ktp.jpg';
        Storage::disk('public')->put($legacyProofPath, 'legacy-public-content');

        $legacyProof = AssessmentProof::create([
            'assessment_id' => $this->assessment->id,
            'proof_type' => 'IJAZAH_SMK',
            'proof_category' => 'PERSYARATAN_DASAR',
            'file_path' => $legacyProofPath,
            'original_filename' => 'legacy_smk.jpg',
        ]);

        $this->actingAs($this->asesi)
            ->get(route('secure-file.proof', $legacyProof))
            ->assertOk();
    }
}
