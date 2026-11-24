<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentProof;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AplProofDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected User $asesi;

    protected User $otherAsesi;

    protected Schedule $schedule;

    protected Scheme $scheme;

    protected Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->asesi = User::factory()->create([
            'full_name' => 'Budi Pemohon',
            'email' => 'budi.asesi@example.com',
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->otherAsesi = User::factory()->create([
            'full_name' => 'Siti Pemohon',
            'email' => 'siti.asesi@example.com',
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-OTO-01',
            'name' => 'Teknik Otomotif Kendaraan Ringan',
            'scheme_type' => 'KLASTER',
            'is_active' => true,
        ]);

        $this->schedule = Schedule::create([
            'scheme_id' => $this->scheme->id,
            'code' => 'JDW-OTO-01',
            'name' => 'Jadwal Asesmen Otomotif Batch 1',
            'tuk_name' => 'TUK Mandiri',
            'status' => 'ACTIVE',
            'schedule_type' => 'OFFLINE',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'created_by' => $this->asesi->id,
            'selected_instruments' => ['AK01', 'IA01'],
            'is_active' => true,
            'is_closed' => false,
        ]);

        $this->assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi->id,
            'apl_status' => 'DRAFT',
            'admin_status' => 'APPROVED',
        ]);
    }

    public function test_apl_page_renders_remove_button_for_uploaded_proofs(): void
    {
        $proofPath = UploadedFile::fake()->create('surat_aktif.pdf', 100)->store('proofs', 'local');

        AssessmentProof::create([
            'assessment_id' => $this->assessment->id,
            'proof_type' => 'SURAT_SISWA_AKTIF',
            'proof_category' => 'PERSYARATAN_DASAR',
            'file_path' => $proofPath,
            'original_filename' => 'surat_aktif.pdf',
        ]);

        $response = $this->actingAs($this->asesi)->get(route('asesi.assessment.apl', $this->assessment));

        $response->assertStatus(200);
        $response->assertSee('proof-item-SURAT_SISWA_AKTIF', false);
        $response->assertSee('surat_aktif.pdf');
        $response->assertSee('Hapus');
    }

    public function test_asesi_can_delete_uploaded_proof_via_ajax(): void
    {
        $file = UploadedFile::fake()->create('ijazah.pdf', 200);
        $path = $file->store('proofs', 'local');
        $this->assertTrue(Storage::disk('local')->exists($path));

        $proof = AssessmentProof::create([
            'assessment_id' => $this->assessment->id,
            'proof_type' => 'IJAZAH_SMK',
            'proof_category' => 'PERSYARATAN_DASAR',
            'file_path' => $path,
            'original_filename' => 'ijazah.pdf',
        ]);

        $response = $this->actingAs($this->asesi)->deleteJson(
            route('asesi.assessment.proof.destroy', [$this->assessment, $proof])
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'proof_type' => 'IJAZAH_SMK',
        ]);

        $this->assertDatabaseMissing('t_assessment_proofs', ['id' => $proof->id]);
        $this->assertFalse(Storage::disk('local')->exists($path));
    }

    public function test_asesi_can_delete_uploaded_administrative_proof(): void
    {
        $file = UploadedFile::fake()->create('ktp.jpg', 150);
        $path = $file->store('proofs', 'local');

        $proof = AssessmentProof::create([
            'assessment_id' => $this->assessment->id,
            'proof_type' => 'KTP',
            'proof_category' => 'ADMINISTRATIF',
            'file_path' => $path,
            'original_filename' => 'ktp.jpg',
        ]);

        $response = $this->actingAs($this->asesi)->delete(
            route('asesi.assessment.proof.destroy', [$this->assessment, $proof])
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('t_assessment_proofs', ['id' => $proof->id]);
        $this->assertFalse(Storage::disk('local')->exists($path));
    }

    public function test_other_user_cannot_delete_proof(): void
    {
        $file = UploadedFile::fake()->create('ktp.jpg', 150);
        $path = $file->store('proofs', 'local');

        $proof = AssessmentProof::create([
            'assessment_id' => $this->assessment->id,
            'proof_type' => 'KTP',
            'proof_category' => 'ADMINISTRATIF',
            'file_path' => $path,
            'original_filename' => 'ktp.jpg',
        ]);

        $response = $this->actingAs($this->otherAsesi)->deleteJson(
            route('asesi.assessment.proof.destroy', [$this->assessment, $proof])
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('t_assessment_proofs', ['id' => $proof->id]);
    }

    public function test_cannot_delete_proof_if_apl_is_submitted(): void
    {
        $this->assessment->update(['apl_status' => 'SUBMITTED']);

        $file = UploadedFile::fake()->create('ktp.jpg', 150);
        $path = $file->store('proofs', 'local');

        $proof = AssessmentProof::create([
            'assessment_id' => $this->assessment->id,
            'proof_type' => 'KTP',
            'proof_category' => 'ADMINISTRATIF',
            'file_path' => $path,
            'original_filename' => 'ktp.jpg',
        ]);

        $response = $this->actingAs($this->asesi)->deleteJson(
            route('asesi.assessment.proof.destroy', [$this->assessment, $proof])
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('t_assessment_proofs', ['id' => $proof->id]);
    }

    public function test_cannot_delete_proof_if_schedule_is_closed(): void
    {
        $this->schedule->update(['is_closed' => true]);

        $file = UploadedFile::fake()->create('ktp.jpg', 150);
        $path = $file->store('proofs', 'local');

        $proof = AssessmentProof::create([
            'assessment_id' => $this->assessment->id,
            'proof_type' => 'KTP',
            'proof_category' => 'ADMINISTRATIF',
            'file_path' => $path,
            'original_filename' => 'ktp.jpg',
        ]);

        $response = $this->actingAs($this->asesi)->deleteJson(
            route('asesi.assessment.proof.destroy', [$this->assessment, $proof])
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('t_assessment_proofs', ['id' => $proof->id]);
    }
}
