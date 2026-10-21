<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentRevisionsAndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $asesor;

    protected User $asesi;

    protected Schedule $schedule;

    protected Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->asesor = User::where('role', 'ASESOR')->first();
        $this->asesi = User::where('role', 'ASESI')->first();

        // Ensure asesor and asesi have profile and signatures
        UserProfile::updateOrCreate(
            ['user_id' => $this->asesor->id],
            [
                'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                'nik' => '3201010101010001',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1985-05-15',
                'jenis_kelamin' => 'L',
                'alamat_rumah' => 'Jl. Asesor No. 1',
                'kode_pos' => '12345',
                'pendidikan_terakhir' => 'S1 Teknik Mesin',
                'kebangsaan' => 'Indonesia',
                'nama_institusi_perusahaan' => 'LSP Pro Indo',
                'jabatan' => 'Master Assessor',
            ]
        );

        UserProfile::updateOrCreate(
            ['user_id' => $this->asesi->id],
            [
                'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                'nik' => '3201010101010002',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '2000-01-01',
                'jenis_kelamin' => 'L',
                'alamat_rumah' => 'Jl. Asesi No. 2',
                'kode_pos' => '54321',
                'pendidikan_terakhir' => 'SMK Otomotif',
                'kebangsaan' => 'Indonesia',
                'nama_institusi_perusahaan' => 'SMK Negeri 1',
                'jabatan' => 'Siswa',
            ]
        );

        $admin = User::where('role', 'ADMIN_LSP')->first();
        $scheme = Scheme::first();

        $this->schedule = Schedule::create([
            'scheme_id' => $scheme->id,
            'name' => 'Jadwal Uji Kompetensi',
            'code' => 'SCH-TEST-'.uniqid(),
            'tuk_name' => 'TUK Otomotif Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'selected_instruments' => ['AK01', 'IA01', 'IA01B', 'IA02', 'IA03', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);
        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment = Assessment::updateOrCreate(
            ['schedule_id' => $this->schedule->id, 'asesi_id' => $this->asesi->id],
            [
                'assessor_id' => $this->asesor->id,
                'apl_status' => 'APPROVED',
                'asesi_signature' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                'asesi_signed_at' => now(),
            ]
        );
    }

    public function test_apl02_inputs_and_fast_actions_are_locked_when_submitted(): void
    {
        $this->assessment->update(['apl_status' => 'SUBMITTED']);

        $response = $this->actingAs($this->asesi)->get(route('asesi.assessment.apl', $this->assessment));
        $response->assertStatus(200);
        $response->assertSee('Formulir Terkunci &amp; Terkirim', false);
        $response->assertDontSee('✓ Semua K (Kompeten)');
    }

    public function test_asesor_store_instrument_auto_populates_asesi_signature(): void
    {
        $payload = [
            'tasks' => [
                1 => ['result' => 'YA', 'note' => 'Sesuai SOP'],
            ],
            'general_notes' => 'Praktik baik',
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ];

        $response = $this->actingAs($this->asesor)->post(
            route('asesor.assessment.instrument.store', ['assessment' => $this->assessment->id, 'code' => 'ia02']),
            $payload
        );

        $response->assertRedirect();

        $iaResponse = AssessmentIaResponse::where('assessment_id', $this->assessment->id)
            ->where('instrument_code', 'IA02')
            ->first();

        $this->assertNotNull($iaResponse);
        $this->assertNotNull($iaResponse->asesi_signature);
        $this->assertNotNull($iaResponse->asesi_signed_at);
        $this->assertEquals($this->assessment->asesi_signature, $iaResponse->asesi_signature);
    }

    public function test_ia02_asesor_view_does_not_contain_bloated_upload_text(): void
    {
        $response = $this->actingAs($this->asesor)->get(
            route('asesor.assessment.instrument', ['assessment' => $this->assessment->id, 'code' => 'ia02'])
        );

        $response->assertStatus(200);
        $response->assertDontSee('Unggah Foto Lembar Kerja Fisik oleh Asesor (Opsional)');
        $response->assertDontSee('Karena instrumen ini berbasis kertas (paper-based), asesi menyelesaikan tugas demonstrasi');
    }

    public function test_ia05_and_hub_do_not_contain_min_passing_grade_text(): void
    {
        $hubResponse = $this->actingAs($this->asesi)->get(route('asesi.assessment.ruang-uji', $this->assessment));
        $hubResponse->assertStatus(200);
        $hubResponse->assertDontSee('Min. 75');
        $hubResponse->assertDontSee('Min. 75.00%');

        $cbtResponse = $this->actingAs($this->asesi)->get(route('asesi.assessment.instrument.ia05', $this->assessment));
        $cbtResponse->assertStatus(200);
        $cbtResponse->assertDontSee('Passing Grade: 75');
        $cbtResponse->assertDontSee('Min. 75');
    }

    public function test_ia01_and_ia_view_render_kuk_statement_and_code(): void
    {
        $response = $this->actingAs($this->asesor)->get(
            route('asesor.assessment.instrument', ['assessment' => $this->assessment->id, 'code' => 'ia01'])
        );
        $response->assertStatus(200);
        // Ensure KUK statement is present and not blank
        $firstKuk = $this->schedule->scheme->competencyUnits->first()?->elements->first()?->kuks->first();
        if ($firstKuk) {
            $response->assertSee($firstKuk->statement);
        }
    }
}
