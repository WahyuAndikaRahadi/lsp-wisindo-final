<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAk03Item;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Database\Seeders\CompleteMissingAk03FeedbackSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteMissingAk03FeedbackSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_seeder_completes_missing_ak03_for_assessments_with_ya(): void
    {
        $scheme = Scheme::first();
        $admin = User::where('role', 'SUPER_ADMIN')->first();
        $asesor = User::where('role', 'ASESOR')->first();
        $asesi1 = User::factory()->create(['role' => 'ASESI']);
        $asesi2 = User::factory()->create(['role' => 'ASESI']);

        $schedule = Schedule::create([
            'code' => 'SCH-TEST-'.uniqid(),
            'name' => 'Jadwal Test AK03 Seeder',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
            'selected_instruments' => ['AK01', 'IA01', 'IA05'],
        ]);

        // Assessment 1: Missing AK03
        $asm1 = Assessment::create([
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'asesi_id' => $asesi1->id,
            'assessor_id' => $asesor->id,
            'apl_status' => 'APPROVED',
            'status' => 'ACTIVE',
        ]);

        // Assessment 2: Already has existing AK03 with TIDAK on question 1
        $asm2 = Assessment::create([
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'asesi_id' => $asesi2->id,
            'assessor_id' => $asesor->id,
            'apl_status' => 'APPROVED',
            'status' => 'ACTIVE',
        ]);

        $customRatings = [];
        for ($i = 1; $i <= 10; $i++) {
            $customRatings[$i] = ['value' => $i === 1 ? 'TIDAK' : 'YA', 'comment' => $i === 1 ? 'Kurang jelas' : ''];
        }
        AssessmentAk03Item::create([
            'assessment_id' => $asm2->id,
            'ratings_json' => $customRatings,
            'general_notes' => 'Catatan khusus asesi',
        ]);

        $this->assertFalse($asm1->ak03Item()->exists());
        $this->assertTrue($asm2->ak03Item()->exists());

        // Run the seeder
        (new CompleteMissingAk03FeedbackSeeder)->run();

        // Assessment 1 must now have completed AK03 with all 'YA'
        $asm1->refresh();
        $this->assertTrue($asm1->ak03Item()->exists());
        $ratings1 = $asm1->ak03Item->ratings_json;
        $this->assertCount(10, $ratings1);
        foreach ($ratings1 as $num => $item) {
            $this->assertEquals('YA', $item['value'], "Question $num must be YA");
        }

        // Assessment 2 must keep its custom 'TIDAK' and custom notes (idempotency)
        $asm2->refresh();
        $ratings2 = $asm2->ak03Item->ratings_json;
        $this->assertEquals('TIDAK', $ratings2[1]['value']);
        $this->assertEquals('Catatan khusus asesi', $asm2->ak03Item->general_notes);
    }

    public function test_pilih_semua_ya_button_rendered_in_ak03_view(): void
    {
        $scheme = Scheme::first();
        $admin = User::where('role', 'SUPER_ADMIN')->first();
        $asesor = User::where('role', 'ASESOR')->first();
        $asesi = User::factory()->create(['role' => 'ASESI']);

        $schedule = Schedule::create([
            'code' => 'SCH-TEST-'.uniqid(),
            'name' => 'Jadwal Test View',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $admin->id,
            'selected_instruments' => ['AK01', 'IA01', 'IA05'],
        ]);

        $asm = Assessment::create([
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $asesor->id,
            'apl_status' => 'APPROVED',
            'status' => 'COMPLETED',
            'ak01_agreed_at' => now(),
        ]);

        // When AK03 is not submitted, button must appear
        $response = $this->actingAs($asesi)->get(route('asesi.assessment.ak03', $asm));
        $response->assertOk();
        $response->assertSeeText('Pilih Semua "Ya"');

        // After submission, button must not be visible
        (new CompleteMissingAk03FeedbackSeeder)->run();
        $response2 = $this->actingAs($asesi)->get(route('asesi.assessment.ak03', $asm));
        $response2->assertOk();
        $response2->assertDontSeeText('Pilih Semua "Ya"');
        $response2->assertSeeText('FR.AK.03 Telah Terkirim');
    }

    public function test_asesi_can_access_and_submit_ak03_when_schedule_is_closed(): void
    {
        $scheme = Scheme::first();
        $admin = User::where('role', 'SUPER_ADMIN')->first();
        $asesor = User::where('role', 'ASESOR')->first();
        $asesi = User::factory()->create(['role' => 'ASESI']);

        $schedule = Schedule::create([
            'code' => 'SCH-CLOSED-'.uniqid(),
            'name' => 'Jadwal Ditutup Test',
            'scheme_id' => $scheme->id,
            'tuk_name' => 'TUK Mandiri',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'FINISHED',
            'is_closed' => true,
            'created_by' => $admin->id,
            'selected_instruments' => ['AK01', 'IA01', 'IA05'],
        ]);

        $asm = Assessment::create([
            'schedule_id' => $schedule->id,
            'scheme_id' => $scheme->id,
            'asesi_id' => $asesi->id,
            'assessor_id' => $asesor->id,
            'apl_status' => 'APPROVED',
            'status' => 'COMPLETED',
        ]);

        // 1. Visit Ruang Uji hub: Banner closed is shown, but AK03 is unlocked and ready to fill
        $hubResponse = $this->actingAs($asesi)->get(route('asesi.assessment.ruang-uji', $asm));
        $hubResponse->assertOk();
        $hubResponse->assertSeeText('Jadwal Asesmen Telah Resmi Ditutup & Dikunci');
        $hubResponse->assertSeeText('FR.AK.03 Umpan Balik dan Catatan Asesmen');
        $hubResponse->assertSeeText('Isi Umpan Balik Sekarang');

        // 2. Open AK.03 view: Can access even though schedule is closed
        $viewResponse = $this->actingAs($asesi)->get(route('asesi.assessment.ak03', $asm));
        $viewResponse->assertOk();
        $viewResponse->assertSeeText('Pilih Semua "Ya"');
        $viewResponse->assertSeeText('Kirim Umpan Balik');

        // 3. Submit AK.03 feedback: Form accepts submission successfully
        $ratings = [];
        for ($i = 1; $i <= 10; $i++) {
            $ratings[$i] = ['value' => 'YA', 'comment' => ''];
        }

        $submitResponse = $this->actingAs($asesi)->post(route('asesi.assessment.ak03.submit', $asm), [
            'ratings' => $ratings,
            'general_notes' => 'Pelaksanaan asesmen sangat baik',
        ]);

        $submitResponse->assertRedirect(route('asesi.dashboard'));
        $submitResponse->assertSessionHas('success');

        // 4. Verify data in DB
        $asm->refresh();
        $this->assertTrue($asm->ak03Item()->exists());
        $this->assertEquals('Pelaksanaan asesmen sangat baik', $asm->ak03Item->general_notes);

        // 5. Open AK.03 view after submission: Locked in read-only mode
        $readonlyResponse = $this->actingAs($asesi)->get(route('asesi.assessment.ak03', $asm));
        $readonlyResponse->assertOk();
        $readonlyResponse->assertSeeText('Umpan Balik Terkirim (Terkunci)');
    }
}
