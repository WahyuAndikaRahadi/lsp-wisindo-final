<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAk07Item;
use App\Models\AssessmentAppeal;
use App\Models\AssessmentIaResponse;
use App\Models\QuestionBank;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Services\AkWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdempotencyAndStatusGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function getFixture(): array
    {
        $superAdmin = User::where('role', 'SUPER_ADMIN')->first();
        $admin = User::where('role', 'ADMIN_LSP')->first();
        $asesor = User::where('role', 'ASESOR')->first();
        $asesi = User::where('role', 'ASESI')->first();
        $scheme = Scheme::first();

        $schedule = Schedule::create([
            'code' => 'SCH-GUARD-'.uniqid(),
            'name' => 'Guard Test Schedule',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Cikarang',
            'date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'selected_instruments' => ['AK01', 'AK07', 'IA01', 'IA02', 'IA03', 'IA05'],
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
        ]);
        $schedule->assessors()->attach($asesor->id);

        $assessment = Assessment::create([
            'schedule_id' => $schedule->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $asesor->id,
            'scheme_id' => $scheme->id,
            'status' => 'IN_PROGRESS',
            'admin_status' => 'APPROVED',
            'apl_status' => 'APPROVED',
        ]);

        return compact('superAdmin', 'admin', 'asesor', 'asesi', 'scheme', 'schedule', 'assessment');
    }

    public function test_duplicate_schedule_join_is_idempotent(): void
    {
        $f = $this->getFixture();
        $asesi = $f['asesi'];
        $schedule = $f['schedule'];

        // Second join attempt for existing schedule should redirect with info, not crash on unique index
        $res = $this->actingAs($asesi)->post(route('asesi.jadwal.join'), [
            'code' => $schedule->code,
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('info');
        $this->assertEquals(1, Assessment::where('schedule_id', $schedule->id)->where('asesi_id', $asesi->id)->count());
    }

    public function test_cannot_join_closed_or_finished_schedule(): void
    {
        $f = $this->getFixture();
        $asesi = User::create([
            'full_name' => 'Calon Asesi Baru',
            'email' => 'asesibaru.'.uniqid().'@test.com',
            'phone' => '081234567890',
            'password' => bcrypt('password'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);
        $asesi->profile()->create([
            'nik' => '3216010000000099',
            'tempat_lahir' => 'Bekasi',
            'tanggal_lahir' => '2000-01-01',
            'jenis_kelamin' => 'L',
            'kebangsaan' => 'Indonesia',
            'alamat_rumah' => 'Jl. Cikarang No. 1',
            'pendidikan_terakhir' => 'SMA/Sederajat',
            'jabatan' => 'Pelajar / Siswa',
            'signature_data' => 'data:image/png;base64,sample',
        ]);
        $schedule = $f['schedule'];
        $schedule->update(['status' => 'FINISHED']);

        $res = $this->actingAs($asesi)->post(route('asesi.jadwal.join'), [
            'code' => $schedule->code,
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('error');
        $this->assertEquals(0, Assessment::where('schedule_id', $schedule->id)->where('asesi_id', $asesi->id)->count());
    }

    public function test_asesor_can_approve_apl_directly_without_admin_approval_gate(): void
    {
        $f = $this->getFixture();
        $asesor = $f['asesor'];
        $assessment = $f['assessment'];
        $assessment->update([
            'admin_status' => 'PENDING',
            'apl_status' => 'SUBMITTED',
        ]);

        $res = $this->actingAs($asesor)->post(route('asesor.apl.decide', $assessment), [
            'decision' => 'APPROVED',
            'notes' => 'Catatan verifikasi',
        ]);

        $res->assertRedirect(route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'apl']));
        $res->assertSessionHas('success');
        $assessment->refresh();
        $this->assertEquals('APPROVED', $assessment->apl_status);
        $this->assertNull($assessment->ak01_agreed_at);
    }

    public function test_asesi_cannot_resubmit_ia05_cbt_once_submitted(): void
    {
        $f = $this->getFixture();
        $asesi = $f['asesi'];
        $assessment = $f['assessment'];

        $qb = QuestionBank::create([
            'scheme_id' => $assessment->schedule->scheme_id,
            'question_text' => 'Berapa voltase aki standar?',
            'option_a' => '12V',
            'option_b' => '24V',
            'option_c' => '6V',
            'option_d' => '48V',
            'correct_answer' => 'A',
        ]);

        AssessmentIaResponse::create([
            'assessment_id' => $assessment->id,
            'instrument_code' => 'IA05',
            'score' => 100,
            'data_json' => ['answers' => [$qb->id => 'A']],
            'asesi_signed_at' => now(),
        ]);

        // Attempting to post answers again should be guarded
        $res = $this->actingAs($asesi)->post(route('asesi.assessment.instrument.ia05.submit', $assessment), [
            'answers' => [$qb->id => 'B'],
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('info');

        $record = AssessmentIaResponse::where('assessment_id', $assessment->id)->where('instrument_code', 'IA05')->first();
        $this->assertEquals(100, $record->score);
    }

    public function test_sync_ak02_preserves_manual_assessor_signed_override(): void
    {
        $f = $this->getFixture();
        $assessment = $f['assessment'];
        $service = app(AkWorkflowService::class);

        // Manually finalize as BK with signature
        $assessment->update([
            'final_result' => 'BK',
            'assessor_signed_at' => now(),
            'final_notes' => 'Keputusan asesor: Belum Kompeten pada unit inti',
        ]);

        // Run sync
        $summary = $service->syncAk02($assessment);
        $assessment->refresh();

        $this->assertEquals('BK', $summary['final_result']);
        $this->assertEquals('BK', $assessment->final_result);
        $this->assertNotNull($assessment->assessor_signed_at);
        $this->assertEquals('Keputusan asesor: Belum Kompeten pada unit inti', $assessment->final_notes);
    }

    public function test_ak07_submission_is_idempotent_and_does_not_duplicate_rows(): void
    {
        $f = $this->getFixture();
        $asesor = $f['asesor'];
        $schedule = $f['schedule'];
        $assessment = $f['assessment'];

        $payload = [
            'assessment_ids' => [$assessment->id],
            'items' => [
                1 => [
                    'needed' => 'YA',
                    'title' => 'Identifikasi Karakteristik',
                    'custom_note' => 'Sesuai standar operasional',
                ],
                2 => [
                    'needed' => 'YA',
                    'title' => 'Pemeriksaan Kesesuaian',
                    'custom_note' => 'Peralatan memadai',
                ],
            ],
            'notes' => 'Kesesuaian asesmen terpenuhi',
        ];

        // First submission
        $res1 = $this->actingAs($asesor)->post(route('asesor.schedule.ak07.store', $schedule), $payload);
        $res1->assertRedirect();
        $this->assertEquals(2, AssessmentAk07Item::where('assessment_id', $assessment->id)->count());

        // Second submission with same payload
        $res2 = $this->actingAs($asesor)->post(route('asesor.schedule.ak07.store', $schedule), $payload);
        $res2->assertRedirect();
        // Count should still be 2, not 4
        $this->assertEquals(2, AssessmentAk07Item::where('assessment_id', $assessment->id)->count());
    }

    public function test_cannot_submit_duplicate_pending_appeal(): void
    {
        $f = $this->getFixture();
        $asesi = $f['asesi'];
        $assessment = $f['assessment'];

        $assessment->update(['final_result' => 'BELUM_KOMPETEN']);

        // First appeal succeeds
        $resBk = $this->actingAs($asesi)->post(route('asesi.assessment.appeal.submit', $assessment), [
            'appeal_reasons' => 'Perlu peninjauan ulang unit kelistrikan dan sistem injeksi.',
        ]);
        $resBk->assertRedirect();
        $this->assertEquals(1, AssessmentAppeal::where('assessment_id', $assessment->id)->count());

        // Duplicate appeal while pending should be rejected with info redirect
        $resDup = $this->actingAs($asesi)->post(route('asesi.assessment.appeal.submit', $assessment), [
            'appeal_reasons' => 'Banding susulan kedua untuk asesmen yang sama.',
        ]);
        $resDup->assertRedirect();
        $resDup->assertSessionHas('info');
        $this->assertEquals(1, AssessmentAppeal::where('assessment_id', $assessment->id)->count());
    }
}
