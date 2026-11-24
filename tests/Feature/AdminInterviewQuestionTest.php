<?php

namespace Tests\Feature;

use App\Models\InterviewQuestion;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminInterviewQuestionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Scheme $scheme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'full_name' => 'Admin LSP Utama',
            'email' => 'admin_interview@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-01-2026',
            'name' => 'Engine Junior Technician',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_interview_questions_index(): void
    {
        InterviewQuestion::create([
            'scheme_id' => $this->scheme->id,
            'order' => 1,
            'evidence_context' => 'Sesuai dengan bukti no. 1 yang Anda ajukan',
            'question_text' => 'Ceritakan pengalaman kerja di bengkel',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.interview-questions.index', ['scheme_id' => $this->scheme->id]));

        $response->assertOk();
        $response->assertSee('Bank Soal Wawancara (FR.IA.09)');
        $response->assertSee('Ceritakan pengalaman kerja di bengkel');
    }

    public function test_admin_can_create_interview_question(): void
    {
        $payload = [
            'scheme_id' => $this->scheme->id,
            'order' => 2,
            'evidence_context' => 'Sesuai dengan bukti no. 2 yang Anda ajukan',
            'question_text' => 'Jelaskan prosedur pemeliharaan sistem rem',
            'expected_answer' => 'Langkah-langkah bleeding dan pemeriksaan minyak rem',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.interview-questions.store'), $payload);

        $response->assertRedirect(route('admin.interview-questions.index', ['scheme_id' => $this->scheme->id]));

        $this->assertDatabaseHas('m_interview_questions', [
            'scheme_id' => $this->scheme->id,
            'order' => 2,
            'question_text' => 'Jelaskan prosedur pemeliharaan sistem rem',
        ]);
    }

    public function test_admin_can_update_interview_question(): void
    {
        $q = InterviewQuestion::create([
            'scheme_id' => $this->scheme->id,
            'order' => 1,
            'question_text' => 'Pertanyaan awal',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.interview-questions.update', $q->id), [
                'order' => 1,
                'evidence_context' => 'Sesuai bukti portofolio',
                'question_text' => 'Pertanyaan yang sudah diedit',
                'expected_answer' => 'Kunci jawaban terupdate',
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('admin.interview-questions.index', ['scheme_id' => $this->scheme->id]));

        $this->assertDatabaseHas('m_interview_questions', [
            'id' => $q->id,
            'question_text' => 'Pertanyaan yang sudah diedit',
        ]);
    }

    public function test_admin_can_delete_interview_question(): void
    {
        $q = InterviewQuestion::create([
            'scheme_id' => $this->scheme->id,
            'order' => 1,
            'question_text' => 'Pertanyaan untuk dihapus',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.interview-questions.destroy', $q->id));

        $response->assertRedirect(route('admin.interview-questions.index', ['scheme_id' => $this->scheme->id]));

        $this->assertDatabaseMissing('m_interview_questions', [
            'id' => $q->id,
        ]);
    }

    public function test_scheme_without_questions_renders_friendly_empty_state(): void
    {
        $emptyScheme = Scheme::create([
            'code' => 'SKM-EMPTY',
            'name' => 'Skema Belum Ada Soal',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.interview-questions.index', ['scheme_id' => $emptyScheme->id]));

        $response->assertOk();
        $response->assertSee('Bank Soal Wawancara Belum Tersedia');
    }
}
