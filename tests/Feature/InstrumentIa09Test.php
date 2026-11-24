<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\CompetencyUnit;
use App\Models\InterviewQuestion;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstrumentIa09Test extends TestCase
{
    use RefreshDatabase;

    private User $asesor;

    private User $asesi;

    private Scheme $scheme;

    private Schedule $schedule;

    private Assessment $assessment;

    private InterviewQuestion $question;

    protected function setUp(): void
    {
        parent::setUp();

        $this->asesor = User::create([
            'full_name' => 'Asesor Kompetensi IA09',
            'email' => 'asesor_ia09@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
        ]);

        $this->asesi = User::create([
            'full_name' => 'Asesi Uji IA09',
            'email' => 'asesi_ia09@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-01-2026',
            'name' => 'Engine Junior Technician',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);

        $unit = CompetencyUnit::create([
            'scheme_id' => $this->scheme->id,
            'code' => 'G.45OTO01.001.2',
            'title' => 'Melaksanakan K3 di Tempat Kerja',
            'sequence' => 1,
        ]);

        $this->question = InterviewQuestion::create([
            'scheme_id' => $this->scheme->id,
            'competency_unit_id' => $unit->id,
            'order' => 1,
            'evidence_context' => 'Sesuai dengan bukti no. 1 yang Anda ajukan',
            'question_text' => 'Ceritakan kecelakaan kerja akibat tidak menggunakan APD saat bekerja',
            'expected_answer' => 'Asesi menceritakan insiden luka bakar knalpot atau serpihan logam saat gerinda',
            'is_active' => true,
        ]);

        $this->schedule = Schedule::create([
            'code' => 'SCH-IA09-01',
            'name' => 'Jadwal Uji Wawancara',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'SMKS Inovasi Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $this->asesor->id,
            'selected_instruments' => ['AK01', 'IA09'],
            'ak01_data' => [
                'selected_instruments' => ['IA09'],
                'evidence' => ['Hasil Pertanyaan Wawancara'],
            ],
        ]);
        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->asesor->id,
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
            'status' => 'IN_PROGRESS',
            'ak01_data' => [
                'selected_instruments' => ['IA09'],
                'evidence' => ['Hasil Pertanyaan Wawancara'],
            ],
            'ak01_agreed_at' => now(),
            'assessor_signature' => 'data:image/png;base64,sampleSig',
            'assessor_signed_at' => now(),
        ]);
    }

    public function test_asesor_can_view_questions_and_evaluate_ia09(): void
    {
        // 1. Assessor opens IA09 view
        $viewResp = $this->actingAs($this->asesor)->get(route('asesor.assessment.instrument', [
            'assessment' => $this->assessment->id,
            'code' => 'ia09',
        ]));
        $viewResp->assertOk();
        $viewResp->assertSee('FR.IA.09 – Pertanyaan Wawancara (PW)');
        $viewResp->assertSee('Ceritakan kecelakaan kerja akibat tidak menggunakan APD');

        // 2. Assessor submits interview evaluation
        $storeResp = $this->actingAs($this->asesor)->post(route('asesor.assessment.instrument.store', [
            'assessment' => $this->assessment->id,
            'code' => 'ia09',
        ]), [
            'questions' => [
                $this->question->id => [
                    'candidate_answer_summary' => 'Asesi menjelaskan secara rinci tentang bahaya percikan api dan luka bakar.',
                    'is_achieved' => 'YA',
                ],
            ],
            'recommendation' => 'K',
            'assessor_notes' => 'Penguasaan teori dan pemahaman K3 sangat memuaskan.',
            'assessor_signature' => 'data:image/png;base64,asesorSignatureData',
        ]);

        $storeResp->assertRedirect(route('asesor.schedule.instrument', [
            'schedule' => $this->schedule->id,
            'code' => 'ia09',
        ]));
        $storeResp->assertSessionHas('success');

        $iaResponse = AssessmentIaResponse::where('assessment_id', $this->assessment->id)
            ->where('instrument_code', 'IA09')
            ->first();

        $this->assertNotNull($iaResponse->assessor_signed_at);
        $this->assertEquals('K', $iaResponse->data_json['recommendation']);
        $this->assertEquals('YA', $iaResponse->data_json['questions'][$this->question->id]['is_achieved']);
    }

    public function test_scheme_without_interview_questions_handles_empty_state_safely(): void
    {
        $emptyScheme = Scheme::create([
            'code' => 'SKM-EMPTY-2026',
            'name' => 'Skema Belum Ada Soal Wawancara',
            'scheme_type' => 'KLASTER',
            'is_active' => true,
        ]);

        $scheduleEmpty = Schedule::create([
            'code' => 'SCH-EMPTY-01',
            'name' => 'Jadwal Skema Tanpa Bank Soal',
            'scheme_id' => $emptyScheme->id,
            'tuk_name' => 'TUK Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $this->asesor->id,
            'selected_instruments' => ['AK01', 'IA09'],
        ]);
        $scheduleEmpty->assessors()->attach($this->asesor->id);

        $assessmentEmpty = Assessment::create([
            'schedule_id' => $scheduleEmpty->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->asesor->id,
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
            'status' => 'IN_PROGRESS',
            'ak01_data' => ['selected_instruments' => ['IA09']],
            'ak01_agreed_at' => now(),
        ]);

        $response = $this->actingAs($this->asesor)->get(route('asesor.assessment.instrument', [
            'assessment' => $assessmentEmpty->id,
            'code' => 'ia09',
        ]));

        $response->assertOk();
        $response->assertSee('Bank Soal Wawancara Belum Tersedia untuk Skema Ini');
    }

    public function test_asesi_cannot_see_confidential_questions_before_assessor_evaluates(): void
    {
        // Asesi accesses IA09 before assessor evaluation
        $response = $this->actingAs($this->asesi)->get(route('asesi.assessment.instrument.view', [
            'assessment' => $this->assessment->id,
            'code' => 'IA09',
        ]));

        $response->assertOk();
        $response->assertSee('Materi Pertanyaan Bersifat Rahasia (Wawancara Tatap Muka)');
        // Ensure the question text is NOT exposed before evaluation
        $response->assertDontSee('Ceritakan kecelakaan kerja akibat tidak menggunakan APD saat bekerja');
    }

    public function test_asesi_can_view_transparent_interview_results_after_assessor_evaluates(): void
    {
        // Assessor has recorded evaluation
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA09',
            'data_json' => [
                'questions' => [
                    $this->question->id => [
                        'candidate_answer_summary' => 'Asesi memaparkan pengalaman kerja dengan sangat baik.',
                        'is_achieved' => 'YA',
                    ],
                ],
                'recommendation' => 'K',
            ],
            'assessor_notes' => 'Kompetensi wawancara terbukti valid.',
            'assessor_signature' => 'data:image/png;base64,sigAsesor',
            'assessor_signed_at' => now(),
        ]);

        $response = $this->actingAs($this->asesi)->get(route('asesi.assessment.instrument.view', [
            'assessment' => $this->assessment->id,
            'code' => 'IA09',
        ]));

        $response->assertOk();
        $response->assertSee('Transparansi Penilaian FR.IA.09');
        $response->assertSee('Ceritakan kecelakaan kerja akibat tidak menggunakan APD saat bekerja');
        $response->assertSee('Asesi memaparkan pengalaman kerja dengan sangat baik.');
        $response->assertSee('KOMPETEN (K)');

        // Verify HTML print export returns 200 OK
        $admin = User::create([
            'full_name' => 'Admin MUK Export 09',
            'email' => 'admin_export_ia09@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        $exportResp = $this->actingAs($admin)->get(route('admin.dokumen-muk.export-asesi', [
            'assessment' => $this->assessment->id,
            'type' => 'ia09',
        ]));
        $exportResp->assertOk();
        $exportResp->assertSee('PW - PERTANYAAN WAWANCARA');
    }
}
