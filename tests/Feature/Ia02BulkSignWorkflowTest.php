<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\CompetencyUnit;
use App\Models\Kuk;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\UnitElement;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Ia02BulkSignWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $assessor;

    private User $asesi1;

    private User $asesi2;

    private User $asesi3;

    private Scheme $scheme;

    private Schedule $schedule;

    private Assessment $asm1;

    private Assessment $asm2;

    private Assessment $asm3;

    private string $sampleSig = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        $this->assessor = User::create([
            'full_name' => 'Budi Assessor Mandiri',
            'email' => 'asesor_ia02@lsp.test',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'MET.000.999999',
        ]);

        UserProfile::create([
            'user_id' => $this->assessor->id,
            'signature_data' => $this->sampleSig,
        ]);

        $this->asesi1 = User::create([
            'full_name' => 'Asesi Uji Satu',
            'email' => 'asesi1@test.com',
            'password' => Hash::make('password'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);
        UserProfile::create([
            'user_id' => $this->asesi1->id,
            'nik' => '3201010101010001',
            'signature_data' => $this->sampleSig,
        ]);

        $this->asesi2 = User::create([
            'full_name' => 'Asesi Uji Dua',
            'email' => 'asesi2@test.com',
            'password' => Hash::make('password'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);
        UserProfile::create([
            'user_id' => $this->asesi2->id,
            'nik' => '3201010101010002',
            'signature_data' => $this->sampleSig,
        ]);

        $this->asesi3 = User::create([
            'full_name' => 'Asesi Uji Tiga (Belum APL)',
            'email' => 'asesi3@test.com',
            'password' => Hash::make('password'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);
        UserProfile::create([
            'user_id' => $this->asesi3->id,
            'nik' => '3201010101010003',
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-IA02-TEST',
            'name' => 'Skema Pengujian FR.IA.02',
            'version' => '2026',
            'is_active' => true,
        ]);

        $unit = CompetencyUnit::create([
            'scheme_id' => $this->scheme->id,
            'code' => 'OTO.IA02.001',
            'title' => 'Unit Uji Praktik Demonstrasi',
            'standard_reference' => 'SKKNI',
        ]);

        $element = UnitElement::create([
            'competency_unit_id' => $unit->id,
            'title' => 'Elemen Demonstrasi 1',
        ]);

        Kuk::create([
            'unit_element_id' => $element->id,
            'code_number' => '1.1',
            'statement' => 'KUK unjuk kerja demonstrasi dilakukan sesuai SOP.',
        ]);

        $this->schedule = Schedule::create([
            'name' => 'Jadwal Demo FR.IA.02',
            'code' => 'LSP-IA02-01',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Otomotif Uji',
            'date' => now()->toDateString(),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'created_by' => $this->assessor->id,
            'selected_instruments' => ['IA01', 'IA02'],
            'status' => 'ACTIVE',
            'is_active' => true,
        ]);
        $this->schedule->assessors()->attach($this->assessor->id);

        $this->asm1 = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi1->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'APPROVED',
            'admin_status' => 'APPROVED',
            'ak01_agreed_at' => now(),
            'assigned_instruments' => ['IA01', 'IA02'],
        ]);

        $this->asm2 = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi2->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'APPROVED',
            'admin_status' => 'APPROVED',
            'ak01_agreed_at' => now(),
            'assigned_instruments' => ['IA01', 'IA02'],
        ]);

        $this->asm3 = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi3->id,
            'assessor_id' => $this->assessor->id,
            'apl_status' => 'SUBMITTED', // Not approved yet!
            'admin_status' => 'PENDING',
            'assigned_instruments' => ['IA01', 'IA02'],
        ]);
    }

    public function test_assessor_can_view_ia02_list_with_bulk_sign_bar_and_checkboxes(): void
    {
        $response = $this->actingAs($this->assessor)
            ->get(route('asesor.schedule.instrument', ['schedule' => $this->schedule->id, 'code' => 'ia02']));

        $response->assertStatus(200);
        $response->assertSee('Tanda Tangan Massal / Bulk Sign FR.IA.02');
        $response->assertSee('Tanda Tangani Terpilih (Bulk Sign)');
        $response->assertSee('checkAllItems');
        $response->assertSee('bulk-item-checkbox');
        $response->assertSee('name="assessment_ids[]"', false);
    }

    public function test_assessor_can_bulk_sign_ia02_using_profile_signature(): void
    {
        $response = $this->actingAs($this->assessor)
            ->post(route('asesor.schedule.ia02.bulk-sign', $this->schedule->id), [
                'assessment_ids' => [$this->asm1->id, $this->asm2->id],
            ]);

        $response->assertRedirect(route('asesor.schedule.instrument', ['schedule' => $this->schedule->id, 'code' => 'ia02']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('t_assessment_ia_responses', [
            'assessment_id' => $this->asm1->id,
            'instrument_code' => 'IA02',
            'assessor_signature' => $this->sampleSig,
        ]);

        $this->assertDatabaseHas('t_assessment_ia_responses', [
            'assessment_id' => $this->asm2->id,
            'instrument_code' => 'IA02',
            'assessor_signature' => $this->sampleSig,
        ]);

        $resp1 = AssessmentIaResponse::where('assessment_id', $this->asm1->id)->where('instrument_code', 'IA02')->first();
        $this->assertNotNull($resp1->assessor_signed_at);
        $this->assertTrue($resp1->data_json['ia02_acknowledged'] ?? false);
        $this->assertTrue($resp1->data_json['audit_trail']['is_bulk_signed'] ?? false);
    }

    public function test_assessor_can_bulk_sign_ia02_with_new_signature_and_save_to_profile(): void
    {
        // Remove profile signature
        $this->assessor->profile()->update(['signature_data' => null]);

        $newSig = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAAEklEQVR42mNk+M9QzwAEjAwMDAwAPgwD/2d2UxsAAAAASUVORK5CYII=';

        $response = $this->actingAs($this->assessor)
            ->post(route('asesor.schedule.ia02.bulk-sign', $this->schedule->id), [
                'assessment_ids' => [$this->asm1->id],
                'assessor_signature' => $newSig,
                'save_signature_to_profile' => '1',
            ]);

        $response->assertRedirect(route('asesor.schedule.instrument', ['schedule' => $this->schedule->id, 'code' => 'ia02']));
        $response->assertSessionHas('success');

        // Check profile updated
        $this->assessor->refresh();
        $this->assertSame($newSig, $this->assessor->profile->signature_data);

        // Check IA02 recorded
        $this->assertDatabaseHas('t_assessment_ia_responses', [
            'assessment_id' => $this->asm1->id,
            'instrument_code' => 'IA02',
            'assessor_signature' => $newSig,
        ]);
    }

    public function test_bulk_sign_ia02_validates_empty_selection(): void
    {
        $response = $this->actingAs($this->assessor)
            ->post(route('asesor.schedule.ia02.bulk-sign', $this->schedule->id), [
                'assessment_ids' => [],
            ]);

        $response->assertSessionHas('error', 'Silakan pilih setidaknya satu asesi untuk menandatangani FR.IA.02.');
    }

    public function test_bulk_sign_ia02_ignores_candidates_with_unapproved_apl(): void
    {
        // Try signing candidate 3 whose APL is still SUBMITTED
        $response = $this->actingAs($this->assessor)
            ->post(route('asesor.schedule.ia02.bulk-sign', $this->schedule->id), [
                'assessment_ids' => [$this->asm3->id],
            ]);

        $response->assertSessionHas('error');

        // Ensure candidate 3 was NOT signed
        $this->assertDatabaseMissing('t_assessment_ia_responses', [
            'assessment_id' => $this->asm3->id,
            'instrument_code' => 'IA02',
        ]);
    }

    public function test_bulk_sign_ia02_prevented_when_schedule_is_closed(): void
    {
        $this->schedule->update(['is_closed' => true]);

        $response = $this->actingAs($this->assessor)
            ->post(route('asesor.schedule.ia02.bulk-sign', $this->schedule->id), [
                'assessment_ids' => [$this->asm1->id],
            ]);

        $response->assertSessionHas('error', 'Jadwal asesmen sudah ditutup dan dikunci.');
    }
}
