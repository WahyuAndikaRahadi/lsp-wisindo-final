<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\CompetencyUnit;
use App\Models\Kuk;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\UnitElement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SignaturePadConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $asesor;

    private User $asesi;

    private Scheme $scheme;

    private Schedule $schedule;

    private Assessment $assessment;

    private string $validSignature;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validSignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

        $this->admin = User::create([
            'full_name' => 'Admin Sig LSP',
            'email' => 'admin_sig@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);
        $this->admin->profile()->create([
            'signature_data' => $this->validSignature,
        ]);

        $this->asesor = User::create([
            'full_name' => 'Asesor Sig LSP',
            'email' => 'asesor_sig@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESOR',
            'status' => 'ACTIVE',
            'assessor_registration_number' => 'MET.000.999888',
        ]);
        $this->asesor->profile()->create([
            'signature_data' => $this->validSignature,
        ]);

        $this->asesi = User::create([
            'full_name' => 'Asesi Sig LSP',
            'email' => 'asesi_sig@test.com',
            'password' => Hash::make('Kompeten86'),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);
        $this->asesi->profile()->create([
            'signature_data' => $this->validSignature,
        ]);

        $this->scheme = Scheme::create([
            'code' => 'SKM-SIG-PAD',
            'name' => 'Skema Standardisasi Signature Pad',
            'scheme_type' => 'OKUPASI',
            'is_active' => true,
        ]);

        $unit = CompetencyUnit::create([
            'scheme_id' => $this->scheme->id,
            'code' => 'OTO.SIG.001.01',
            'title' => 'Unit Uji Signature Pad',
            'standard_type' => 'SKKNI',
        ]);

        $element = UnitElement::create([
            'competency_unit_id' => $unit->id,
            'title' => 'Elemen Uji Sig',
        ]);

        Kuk::create([
            'unit_element_id' => $element->id,
            'code_number' => '1.1',
            'statement' => 'Kriteria Uji Sig 1.1',
        ]);

        $this->schedule = Schedule::create([
            'code' => 'SCH-SIG-PAD',
            'name' => 'Jadwal Signature Pad',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Utama',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['AK01', 'IA01', 'IA02'],
        ]);
        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->asesor->id,
            'apl_status' => 'APPROVED',
            'admin_status' => 'APPROVED',
            'last_active_step' => 'ruang-uji',
            'asesi_signature' => $this->validSignature,
            'assessor_signature' => $this->validSignature,
        ]);
    }

    public function test_admin_profile_renders_standardized_signature_pad_and_preview(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.profile.edit'));
        $response->assertOk();

        // Must render standard preview box and standard signature pad
        $response->assertSee('w-[340px] max-w-full h-[140px]', false);
        $response->assertSee('max-w-[340px]', false);
    }

    public function test_asesor_profile_renders_standardized_signature_pad_and_preview(): void
    {
        $response = $this->actingAs($this->asesor)->get(route('asesor.profile.edit'));
        $response->assertOk();

        $response->assertSee('w-[340px] max-w-full h-[140px]', false);
        $response->assertSee('max-w-[340px]', false);
    }

    public function test_asesi_profile_renders_standardized_signature_pad_and_preview(): void
    {
        $response = $this->actingAs($this->asesi)->get(route('asesi.profile.edit'));
        $response->assertOk();

        $response->assertSee('w-[340px] max-w-full h-[140px]', false);
        $response->assertSee('max-w-[340px]', false);
    }

    public function test_asesor_ruang_uji_ia01_renders_standardized_signature_pad(): void
    {
        $response = $this->actingAs($this->asesor)
            ->get(route('asesor.ruang-uji.ia01', $this->assessment));
        $response->assertOk();

        // Must be capped with max-w-[340px]
        $response->assertSee('max-w-[340px]', false);
    }

    public function test_asesi_apl_renders_standardized_signature_pad(): void
    {
        $response = $this->actingAs($this->asesi)
            ->get(route('asesi.assessment.apl', $this->assessment));
        $response->assertOk();

        $response->assertSee('max-w-[340px]', false);
    }
}
