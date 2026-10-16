<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAk07Item;
use App\Models\AssessmentApl02Item;
use App\Models\AssessmentAppeal;
use App\Models\AssessmentIaResponse;
use App\Models\AssessmentProof;
use App\Models\CompetencyUnit;
use App\Models\Kuk;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\UnitElement;
use App\Models\User;
use App\Services\AkWorkflowService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DatabaseIntegrityAndConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $asesor;

    private User $asesi;

    private Scheme $scheme;

    private CompetencyUnit $unit;

    private Kuk $kuk;

    private Schedule $schedule;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'full_name' => 'Admin Integrity',
            'email' => 'admin_integrity@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);

        $this->asesor = User::create([
            'full_name' => 'Asesor Integrity',
            'email' => 'asesor_integrity@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'MET.000.888999',
        ]);

        $this->asesi = User::create([
            'full_name' => 'Asesi Integrity',
            'email' => 'asesi_integrity@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-INT-01',
            'name' => 'Skema Integritas DB',
            'scheme_type' => 'OKUPASI',
            'passing_grade_theory' => 75.00,
            'is_active' => true,
        ]);

        $this->unit = CompetencyUnit::create([
            'scheme_id' => $this->scheme->id,
            'code' => 'OTO.KR01.001.01',
            'title' => 'Unit Integritas',
            'sequence' => 1,
        ]);

        $element = UnitElement::create([
            'competency_unit_id' => $this->unit->id,
            'title' => 'Elemen Integritas',
            'sequence' => 1,
        ]);

        $this->kuk = Kuk::create([
            'unit_element_id' => $element->id,
            'code_number' => '1.1',
            'statement' => 'KUK Integritas',
            'sequence' => 1,
        ]);

        $this->schedule = Schedule::create([
            'code' => 'SCH-INT-001',
            'name' => 'Jadwal Integritas DB',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['IA01', 'IA05'],
        ]);
        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->asesor->id,
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
            'status' => 'IN_PROGRESS',
        ]);
    }

    public function test_composite_unique_rejection_on_proofs(): void
    {
        AssessmentProof::create([
            'assessment_id' => $this->assessment->id,
            'proof_type' => 'KTP',
            'proof_category' => 'ADMINISTRATIF',
            'file_path' => 'proofs/ktp.jpg',
            'original_filename' => 'ktp.jpg',
        ]);

        $this->expectException(QueryException::class);
        AssessmentProof::create([
            'assessment_id' => $this->assessment->id,
            'proof_type' => 'KTP',
            'proof_category' => 'ADMINISTRATIF',
            'file_path' => 'proofs/ktp_duplicate.jpg',
            'original_filename' => 'ktp_duplicate.jpg',
        ]);
    }

    public function test_composite_unique_rejection_on_apl02_items(): void
    {
        AssessmentApl02Item::create([
            'assessment_id' => $this->assessment->id,
            'kuk_id' => $this->kuk->id,
            'assessment_value' => 'K',
            'selected_proof_type' => 'KTP',
        ]);

        $this->expectException(QueryException::class);
        AssessmentApl02Item::create([
            'assessment_id' => $this->assessment->id,
            'kuk_id' => $this->kuk->id,
            'assessment_value' => 'BK',
            'selected_proof_type' => 'KTP',
        ]);
    }

    public function test_composite_unique_rejection_on_ia_responses(): void
    {
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA01',
            'score' => 85.00,
        ]);

        $this->expectException(QueryException::class);
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA01',
            'score' => 90.00,
        ]);
    }

    public function test_composite_unique_rejection_on_ak07_items(): void
    {
        AssessmentAk07Item::create([
            'assessment_id' => $this->assessment->id,
            'adjustment_category' => 'Karakteristik Bahasa',
            'adjustment_detail' => 'Penyesuaian bahasa daerah',
            'assessor_id' => $this->asesor->id,
        ]);

        $this->expectException(QueryException::class);
        AssessmentAk07Item::create([
            'assessment_id' => $this->assessment->id,
            'adjustment_category' => 'Karakteristik Bahasa',
            'adjustment_detail' => 'Penyesuaian duplikat',
            'assessor_id' => $this->asesor->id,
        ]);
    }

    public function test_appeal_submission_concurrency_and_double_submission_prevented(): void
    {
        $this->assessment->update([
            'final_result' => 'BELUM_KOMPETEN',
            'final_result_at' => now(),
            'status' => 'COMPLETED',
        ]);

        $service = app(AkWorkflowService::class);

        // First appeal succeeds
        $appeal1 = $service->submitAppeal(
            $this->assessment,
            'Alasan banding pertama mengenai penilaian unit teknis.',
            [$this->unit->id]
        );
        $this->assertInstanceOf(AssessmentAppeal::class, $appeal1);
        $this->assertEquals('SUBMITTED', $appeal1->status);

        // Second simultaneous/duplicate appeal submission throws ValidationException
        $this->expectException(ValidationException::class);
        $service->submitAppeal(
            $this->assessment,
            'Alasan banding kedua (simultan/race condition).',
            [$this->unit->id]
        );
    }

    public function test_appeal_controller_double_click_redirects_safely(): void
    {
        $this->assessment->update([
            'final_result' => 'BELUM_KOMPETEN',
            'final_result_at' => now(),
            'status' => 'COMPLETED',
        ]);

        // Pre-create an active appeal
        AssessmentAppeal::create([
            'assessment_id' => $this->assessment->id,
            'appeal_date' => now()->toDateString(),
            'appeal_reasons' => 'Banding aktif pertama kali.',
            'status' => 'SUBMITTED',
        ]);

        // Second request via controller simulates double click / retry
        $response = $this->actingAs($this->asesi)->post(route('asesi.assessment.appeal.submit', $this->assessment), [
            'appeal_reasons' => 'Percobaan submit kedua yang cepat.',
        ]);

        $response->assertRedirect(route('asesi.jadwal.index'));
        $response->assertSessionHas('info');
        $this->assertCount(1, AssessmentAppeal::where('assessment_id', $this->assessment->id)->get());
    }

    public function test_unified_cbt_passing_grade_75(): void
    {
        // 1. Below passing grade (74.00) results in BELUM_KOMPETEN
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA05',
            'score' => 74.00,
        ]);

        $service = app(AkWorkflowService::class);
        $result = $service->syncAk02($this->assessment);
        $this->assertEquals('BELUM_KOMPETEN', $result['final_result']);

        // 2. Passing grade exactly (75.00) results in KOMPETEN
        AssessmentIaResponse::where('assessment_id', $this->assessment->id)
            ->where('instrument_code', 'IA05')
            ->update(['score' => 75.00]);

        // Clear manual override to allow sync
        $this->assessment->update(['assessor_signed_at' => null]);
        $this->assessment->refresh();
        $resultPass = $service->syncAk02($this->assessment);
        $this->assertEquals('KOMPETEN', $resultPass['final_result']);
    }
}
