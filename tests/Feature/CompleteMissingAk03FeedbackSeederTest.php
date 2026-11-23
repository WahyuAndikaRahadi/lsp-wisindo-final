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
}
