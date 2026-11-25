<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\CompetencyUnit;
use App\Models\InterviewQuestion;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Services\MukDynamicDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicDokumenMukTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $asesor;

    private User $asesi;

    private Scheme $scheme;

    private CompetencyUnit $unit;

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

        $this->unit = $this->scheme->competencyUnits()->create([
            'code' => 'G.45OTO01.001.2',
            'title' => 'Melaksanakan Keselamatan dan Kesehatan Kerja',
            'sequence' => 1,
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
        // 1. Export FR.IA.08 PDF stream
        $resIa08 = $this->actingAs($this->admin)
            ->get(route('admin.dokumen-muk.export-asesi', [$this->assessment, 'ia08']));

        $resIa08->assertOk();
        $this->assertEquals('application/pdf', $resIa08->headers->get('Content-Type'));
        $this->assertStringContainsString('filename="FR.IA.08 ('.$this->asesi->full_name.').pdf"', (string) $resIa08->headers->get('Content-Disposition'));

        // HTML format
        $resIa08Html = $this->actingAs($this->admin)
            ->get(route('admin.dokumen-muk.export-asesi', ['assessment' => $this->assessment->id, 'type' => 'ia08', 'format' => 'html']));
        $resIa08Html->assertOk();
        $resIa08Html->assertSee('FR.IA.08 ('.$this->asesi->full_name.')');
        $resIa08Html->assertSee('CVP - CEKLIS VERIFIKASI PORTOFOLIO');
        $resIa08Html->assertSee($this->asesi->full_name);

        // 2. Export FR.IA.09 PDF stream
        $resIa09 = $this->actingAs($this->admin)
            ->get(route('admin.dokumen-muk.export-asesi', [$this->assessment, 'ia09']));

        $resIa09->assertOk();
        $this->assertEquals('application/pdf', $resIa09->headers->get('Content-Type'));
        $this->assertStringContainsString('filename="FR.IA.09 ('.$this->asesi->full_name.').pdf"', (string) $resIa09->headers->get('Content-Disposition'));

        // HTML format
        $resIa09Html = $this->actingAs($this->admin)
            ->get(route('admin.dokumen-muk.export-asesi', ['assessment' => $this->assessment->id, 'type' => 'ia09', 'format' => 'html']));
        $resIa09Html->assertOk();
        $resIa09Html->assertSee('FR.IA.09 ('.$this->asesi->full_name.')');
        $resIa09Html->assertSee('PW - PERTANYAAN WAWANCARA');
        $resIa09Html->assertSee($this->asesi->full_name);
    }

    public function test_asesi_dynamic_route_supports_ia08_and_ia09(): void
    {
        $resDynamicIa08 = $this->actingAs($this->admin)
            ->get(route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $this->assessment->id, 'type' => 'ia08']));

        $resDynamicIa08->assertOk();
        $this->assertEquals('application/pdf', $resDynamicIa08->headers->get('Content-Type'));

        $resPdfIa08 = $this->actingAs($this->admin)
            ->get(route('admin.dokumen-muk.export-asesi', ['assessment' => $this->assessment->id, 'type' => 'ia08', 'format' => 'pdf']));

        $resPdfIa08->assertOk();
        $this->assertEquals('application/pdf', $resPdfIa08->headers->get('Content-Type'));

        $resDynamicIa09 = $this->actingAs($this->admin)
            ->get(route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $this->assessment->id, 'type' => 'ia09']));

        $resDynamicIa09->assertOk();
        $this->assertEquals('application/pdf', $resDynamicIa09->headers->get('Content-Type'));
    }

    public function test_ia08_and_ia09_blank_when_not_assessed(): void
    {
        $q = InterviewQuestion::create([
            'scheme_id' => $this->scheme->id,
            'order' => 1,
            'evidence_context' => 'Sesuai bukti 1',
            'question_text' => 'Jelaskan prosedur K3.',
            'expected_answer' => 'Pakai APD.',
            'is_active' => true,
        ]);

        $service = app(MukDynamicDocumentService::class);

        // IA08 blank check
        $ia08Payload = $service->buildIa08Data($this->schedule, $this->assessment);
        $this->assertFalse($ia08Payload['is_assessed']);
        $this->assertEmpty($ia08Payload['vatm']);
        $this->assertEmpty($ia08Payload['clarification_elements']);
        $this->assertSame('', $ia08Payload['recommendation']);
        $this->assertNull($ia08Payload['signature_asesor']);

        // IA09 blank check
        $ia09Payload = $service->buildIa09Data($this->schedule, $this->assessment);
        $this->assertFalse($ia09Payload['is_assessed']);
        $this->assertSame('', $ia09Payload['recommendation']);
        $this->assertNull($ia09Payload['signature_asesor']);
        $this->assertNotEmpty($ia09Payload['questions']);
        $this->assertSame('', $ia09Payload['questions'][0]['candidate_answer_summary']);
        $this->assertSame('', $ia09Payload['questions'][0]['is_achieved']);
    }

    public function test_ia08_and_ia09_filled_when_assessed(): void
    {
        $q = InterviewQuestion::create([
            'scheme_id' => $this->scheme->id,
            'order' => 1,
            'evidence_context' => 'Sesuai bukti 1',
            'question_text' => 'Jelaskan prosedur K3.',
            'expected_answer' => 'Pakai APD.',
            'is_active' => true,
        ]);

        // Evaluate IA08
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA08',
            'assessor_signed_at' => now(),
            'assessor_signature' => 'data:image/png;base64,sample',
            'data_json' => [
                'vatm' => [
                    'p1' => ['v' => 'YA', 'a' => 'YA', 't' => 'YA', 'm' => 'YA'],
                ],
                'clarification_elements' => ['1'],
                'recommendation' => 'K',
                'additional_evidence' => 'Semua bukti valid',
            ],
        ]);

        // Evaluate IA09
        AssessmentIaResponse::create([
            'assessment_id' => $this->assessment->id,
            'instrument_code' => 'IA09',
            'assessor_signed_at' => now(),
            'assessor_signature' => 'data:image/png;base64,sample',
            'data_json' => [
                'questions' => [
                    $q->id => [
                        'candidate_answer_summary' => 'Asesi menjawab dengan lengkap dan benar.',
                        'is_achieved' => 'YA',
                    ],
                ],
                'recommendation' => 'K',
                'assessor_notes' => 'Sangat kompeten.',
            ],
        ]);

        $service = app(MukDynamicDocumentService::class);

        // IA08 filled check
        $ia08Payload = $service->buildIa08Data($this->schedule, $this->assessment);
        $this->assertTrue($ia08Payload['is_assessed']);
        $this->assertNotEmpty($ia08Payload['vatm']);
        $this->assertEquals(['1'], $ia08Payload['clarification_elements']);
        $this->assertEquals('K', $ia08Payload['recommendation']);
        $this->assertNotNull($ia08Payload['signature_asesor']);

        // IA09 filled check
        $ia09Payload = $service->buildIa09Data($this->schedule, $this->assessment);
        $this->assertTrue($ia09Payload['is_assessed']);
        $this->assertEquals('K', $ia09Payload['recommendation']);
        $this->assertNotNull($ia09Payload['signature_asesor']);
        $this->assertEquals('Asesi menjawab dengan lengkap dan benar.', $ia09Payload['questions'][0]['candidate_answer_summary']);
        $this->assertEquals('YA', $ia09Payload['questions'][0]['is_achieved']);
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
        // Add element and KUKs to the competency unit
        $elem = $this->unit->elements()->create([
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
        $response->assertSee('Melaksanakan Keselamatan dan Kesehatan Kerja');
        $response->assertSee('Menyiapkan prosedur Keselamatan');
        // Assert full KUK code numbers and statements are displayed
        $response->assertSee('1.1');
        $response->assertSee('Prosedur K3 di tempat kerja diidentifikasi sesuai standar bengkel.');
        $response->assertSee('1.2');
        $response->assertSee('Alat Pelindung Diri (APD) disiapkan secara lengkap sebelum bekerja.');
    }

    public function test_ia08_saves_clarification_elements_correctly(): void
    {
        $elem = $this->unit->elements()->create([
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

    public function test_admin_can_export_dynamic_mapa02_from_fixed_route(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dokumen-muk.fixed', [
                'schedule' => $this->schedule->id,
                'type' => 'mapa02',
            ]));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type') ?? '');
        $this->assertStringContainsString('FR.MAPA.02', $response->headers->get('Content-Disposition') ?? '');
    }

    public function test_mapa02_html_view_displays_all_5_potensi_asesi_categories(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dokumen-muk.export', [
                'schedule' => $this->schedule->id,
                'type' => 'mapa02',
                'format' => 'html',
            ]));

        $response->assertOk();
        $response->assertSee('Hasil pelatihan/pendidikan (berbasis kompetensi)');
        $response->assertSee('Hasil pelatihan/pendidikan (belum berbasis kompetensi)');
        $response->assertSee('Pekerja berpengalaman (tempat kerja berbasis kompetensi)');
        $response->assertSee('Pekerja berpengalaman (tempat kerja belum berbasis kompetensi)');
        $response->assertSee('Pelatihan/belajar mandiri (otodidak)');
    }
}
