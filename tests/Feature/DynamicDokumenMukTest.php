<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicDokumenMukTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $asesor;

    private User $asesi;

    private Scheme $scheme;

    private Schedule $schedule;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'SUPER_ADMIN', 'status' => 'ACTIVE']);
        $this->asesor = User::factory()->create(['role' => 'ASESOR', 'status' => 'ACTIVE']);
        $this->asesi = User::factory()->create(['role' => 'ASESI', 'status' => 'ACTIVE']);

        $this->scheme = Scheme::create([
            'code' => 'SKM-MTR-01',
            'name' => 'Pemeliharaan Kendaraan Ringan Sistem Injeksi',
            'scheme_type' => 'KLASTER',
            'is_active' => true,
        ]);

        $this->schedule = Schedule::create([
            'code' => 'SCH-DYN-'.uniqid(),
            'name' => 'Uji Kompetensi Portofolio & Wawancara',
            'scheme_id' => $this->scheme->id,
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'tuk_name' => 'Gedung SMKS Inovasi Mandiri',
            'status' => 'ACTIVE',
            'created_by' => $this->admin->id,
            'selected_instruments' => ['AK01', 'IA08', 'IA09'],
            'is_closed' => false,
        ]);

        $this->schedule->assessors()->attach($this->asesor->id);

        $this->assessment = Assessment::create([
            'schedule_id' => $this->schedule->id,
            'scheme_id' => $this->scheme->id,
            'asesi_id' => $this->asesi->id,
            'assessor_id' => $this->asesor->id,
            'apl_status' => 'APPROVED',
            'final_result' => 'KOMPETEN',
            'status' => 'COMPLETED',
            'ak01_data' => [
                'selected_instruments' => ['IA08', 'IA09'],
            ],
        ]);
    }

    public function test_dokumen_muk_shows_only_assigned_ia_instruments_when_ak01_selects_ia08_and_ia09(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dokumen-muk.show', $this->schedule));

        $response->assertOk();
        $response->assertSee('FR.IA.08');
        $response->assertSee('FR.IA.09');
        $response->assertSee('Ceklis Verifikasi Portofolio (CVP)');
        $response->assertSee('Pertanyaan Wawancara (PW)');

        // Instruments NOT selected in AK.01 must NOT appear in the candidate's MUK list
        $response->assertDontSee('FR.IA.01</span>', false);
        $response->assertDontSee('FR.IA.01B</span>', false);
        $response->assertDontSee('FR.IA.02</span>', false);
        $response->assertDontSee('FR.IA.03</span>', false);
        $response->assertDontSee('FR.IA.05.A</span>', false);
    }

    public function test_dokumen_muk_exports_ia08_and_ia09_successfully(): void
    {
        // 1. Export FR.IA.08
        $resIa08 = $this->actingAs($this->admin)
            ->get(route('admin.dokumen-muk.export-asesi', [$this->assessment, 'ia08']));

        $resIa08->assertOk();
        $resIa08->assertSee('FR.IA.08.');
        $resIa08->assertSee('CEKLIS VERIFIKASI PORTOFOLIO');
        $resIa08->assertSee($this->asesi->full_name);

        // 2. Export FR.IA.09
        $resIa09 = $this->actingAs($this->admin)
            ->get(route('admin.dokumen-muk.export-asesi', [$this->assessment, 'ia09']));

        $resIa09->assertOk();
        $resIa09->assertSee('FR.IA.09.');
        $resIa09->assertSee('PERTANYAAN WAWANCARA');
        $resIa09->assertSee($this->asesi->full_name);
    }

    public function test_asesi_dynamic_route_supports_ia08_and_ia09(): void
    {
        $resDynamicIa08 = $this->actingAs($this->admin)
            ->get(route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $this->assessment->id, 'type' => 'ia08']));

        $resDynamicIa08->assertOk();
        $resDynamicIa08->assertSee('FR.IA.08.');

        $resDynamicIa09 = $this->actingAs($this->admin)
            ->get(route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $this->assessment->id, 'type' => 'ia09']));

        $resDynamicIa09->assertOk();
        $resDynamicIa09->assertSee('FR.IA.09.');
    }

    public function test_ia08_asesor_view_contains_enlarged_preview_modal_and_buka_berkas_controls(): void
    {
        // Attach sample portfolio response to candidate
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA08',
            'data_json' => [
                'portfolios' => [
                    [
                        'id' => 'p1',
                        'type' => 'Sertifikat Pelatihan',
                        'title' => 'Sertifikat ahay',
                        'original_name' => 'Certificate of Participation_SH-00655_IRHAM THORIQSAH SECAATMAJA-1.pdf',
                        'path' => 'evidence/sample.pdf',
                    ],
                ],
                'vatm' => [
                    'p1' => ['v' => 'YA', 'a' => 'YA', 't' => 'YA', 'm' => 'YA'],
                ],
            ],
        ]);

        $response = $this->actingAs($this->asesor)
            ->get(route('asesor.assessment.instrument', [
                'assessment' => $this->assessment->id,
                'code' => 'ia08',
            ]));

        $response->assertOk();
        $response->assertSee('Sertifikat ahay');
        $response->assertSee('Certificate of Participation_SH-00655_IRHAM THORIQSAH SECAATMAJA-1.pdf');
        $response->assertSee('Buka Berkas');
        $response->assertSee('max-w-6xl');
        $response->assertSee('viewDocument');
    }

    public function test_ia08_section_2_displays_full_kuks_and_klarifikasi_semua_button(): void
    {
        // Create unit, element, and KUK for scheme
        $unit = $this->scheme->competencyUnits()->create([
            'code' => 'G.45OTO01.001.2',
            'title' => 'Melaksanakan Keselamatan dan Kesehatan Kerja',
            'sequence' => 1,
        ]);

        $elem = $unit->elements()->create([
            'title' => 'Menyiapkan prosedur Keselamatan',
            'sequence' => 1,
        ]);

        $elem->kuks()->create([
            'code_number' => '1.1',
            'statement' => 'Prosedur K3 di tempat kerja diidentifikasi sesuai standar bengkel.',
            'sequence' => 1,
        ]);

        $elem->kuks()->create([
            'code_number' => '1.2',
            'statement' => 'Alat Pelindung Diri (APD) disiapkan secara lengkap sebelum bekerja.',
            'sequence' => 2,
        ]);

        $response = $this->actingAs($this->asesor)
            ->get(route('asesor.assessment.instrument', [
                'assessment' => $this->assessment->id,
                'code' => 'ia08',
            ]));

        $response->assertOk();
        $response->assertSee('2. Tindak Lanjut Hasil Verifikasi Bukti');
        $response->assertSee('Klarifikasi Semua');
        $response->assertSee('Batal Semua');
        $response->assertSee('setAllClarification');
        $response->assertSee('G.45OTO01.001.2');
        $response->assertSee('Menyiapkan prosedur Keselamatan');
        // Assert full KUK code numbers and statements are displayed
        $response->assertSee('1.1');
        $response->assertSee('Prosedur K3 di tempat kerja diidentifikasi sesuai standar bengkel.');
        $response->assertSee('1.2');
        $response->assertSee('Alat Pelindung Diri (APD) disiapkan secara lengkap sebelum bekerja.');
    }

    public function test_ia08_saves_clarification_elements_correctly(): void
    {
        $unit = $this->scheme->competencyUnits()->create([
            'code' => 'G.45OTO01.001.2',
            'title' => 'Melaksanakan K3',
            'sequence' => 1,
        ]);

        $elem = $unit->elements()->create([
            'title' => 'Menyiapkan APD',
            'sequence' => 1,
        ]);

        $response = $this->actingAs($this->asesor)
            ->post(route('asesor.assessment.instrument.store', [
                'assessment' => $this->assessment->id,
                'code' => 'ia08',
            ]), [
                'vatm' => [
                    0 => ['v' => 'YA', 'a' => 'YA', 't' => 'YA', 'm' => 'YA'],
                ],
                'clarification_elements' => [$elem->id],
                'additional_evidence' => 'Perlu bukti tambahan',
                'recommendation' => 'K',
            ]);

        $response->assertRedirect();

        $savedIa08 = AssessmentIaResponse::where('assessment_id', $this->assessment->id)
            ->where('instrument_code', 'IA08')
            ->first();

        $this->assertNotNull($savedIa08);
        $this->assertEquals([$elem->id], $savedIa08->data_json['clarification_elements']);
        $this->assertEquals('K', $savedIa08->data_json['recommendation']);
    }
}
