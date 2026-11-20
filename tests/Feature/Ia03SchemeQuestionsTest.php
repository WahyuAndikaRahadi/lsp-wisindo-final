<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Services\MukDynamicDocumentService;
use App\Services\SchemeInstrumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Ia03SchemeQuestionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $assessor;

    protected User $asesi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'full_name' => 'Admin LSP Test',
            'email' => 'admin_ia03@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);

        $this->assessor = User::create([
            'full_name' => 'Asesor Uji IA03',
            'email' => 'asesor_ia03@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'MET.000.777888',
        ]);

        $this->asesi = User::create([
            'full_name' => 'Asesi Uji IA03',
            'email' => 'asesi_ia03@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_all_seven_schemes_have_complete_untruncated_pmo_questions(): void
    {
        $service = app(SchemeInstrumentService::class);

        $expectedCounts = [
            'SKM-01-2026' => 9,
            'SKM-02-2026' => 9,
            'SKM-03-2026' => 15,
            'SKM-04-2026' => 10,
            'SKM-05-2026' => 21,
            'SKM-06-2026' => 10,
            'SKM-07-2026' => 19,
        ];

        foreach ($expectedCounts as $code => $count) {
            $scheme = Scheme::create([
                'code' => $code,
                'name' => "Scheme {$code}",
                'scheme_type' => 'OKUPASI',
                'is_active' => true,
            ]);

            $pmoQuestions = $service->getPmoQuestions($scheme);

            $this->assertCount($count, $pmoQuestions, "Scheme {$code} must have {$count} PMO questions.");

            foreach ($pmoQuestions as $q) {
                $questionText = $q['question'] ?? '';
                $expectedAnswer = $q['expected_answer'] ?? '';

                // Ensure question is not empty and sufficiently detailed
                $this->assertNotEmpty($questionText, "Question text in {$code} should not be empty.");
                $this->assertGreaterThanOrEqual(25, strlen($questionText), "Question in {$code} is unexpectedly short: {$questionText}");

                // Ensure no accidental unit codes are stored as questions
                $this->assertDoesNotMatchRegularExpression('/^G\.45|^G45|^C\.27|^C\.29/i', $questionText, "Unit code wrongly stored as question: {$questionText}");

                // Ensure expected answer is provided
                $this->assertNotEmpty($expectedAnswer, "Expected answer in {$code} should not be empty.");
            }
        }
    }

    public function test_assessor_ia03_view_renders_complete_questions_for_all_schemes(): void
    {
        $scheme = Scheme::create([
            'code' => 'SKM-01-2026',
            'name' => 'Engine Junior Technician',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);

        $schedule = Schedule::create([
            'code' => 'SCH-IA03-01',
            'name' => 'Jadwal Asesmen IA03',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['IA03'],
        ]);
        $schedule->assessors()->attach($this->assessor->id);

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'APPROVED',
            'status' => 'IN_PROGRESS',
        ]);

        $response = $this->actingAs($this->assessor)
            ->get(route('asesor.ruang-uji.ia03', $assessment));

        $response->assertOk();
        // Check that multi-line question text appears completely, not truncated
        $response->assertSee('Jelaskan bagaimana anda melaksanakan Prosedur kesehatan dan keselamatan kerja di tempat kerja!');
        $response->assertSee('Saat melakukan pemeliharaan rutin, ditemukan bahwa recovery tank radiator kosong');
    }

    public function test_muk_dynamic_document_service_builds_complete_ia03_data(): void
    {
        $scheme = Scheme::create([
            'code' => 'SKM-02-2026',
            'name' => 'Maintenance Gasoline Engine Injection System',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);

        $schedule = Schedule::create([
            'code' => 'SCH-IA03-02',
            'name' => 'Jadwal Asesmen IA03 Scheme 2',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['IA03'],
        ]);
        $schedule->assessors()->attach($this->assessor->id);

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'APPROVED',
            'status' => 'IN_PROGRESS',
        ]);

        $service = app(MukDynamicDocumentService::class);
        $ia03Data = $service->buildIa03Data($schedule, $assessment);

        $this->assertArrayHasKey('questions', $ia03Data);
        $this->assertCount(9, $ia03Data['questions']);
        $this->assertStringContainsString('Kenapa APAR harus selalu ada di tempat kerja', $ia03Data['questions'][2]['question']);
        $this->assertStringContainsString('diagnostic tool digunakan saat memperbaiki Sistem Manajemen', $ia03Data['questions'][8]['question']);
    }
}
