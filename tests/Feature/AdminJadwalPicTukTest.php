<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use App\Services\MukDynamicDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminJadwalPicTukTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Scheme $scheme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'ADMIN_LSP',
            'status' => 'ACTIVE',
        ]);

        $this->scheme = Scheme::create([
            'name' => 'Skema Uji Kompetensi Otomotif',
            'code' => 'SKM-001',
            'scheme_type' => 'KLASTER',
            'is_active' => true,
        ]);
    }

    public function test_create_and_edit_pages_render_pic_tuk_fields(): void
    {
        $responseCreate = $this->actingAs($this->admin)->get(route('admin.jadwal.create'));
        $responseCreate->assertOk();
        $responseCreate->assertSee('Nama Penanggung Jawab TUK');
        $responseCreate->assertSee('name="pic_name"', false);
        $responseCreate->assertSee('name="pic_position"', false);

        $schedule = Schedule::create([
            'code' => 'SCH-TEST-01',
            'name' => 'Jadwal Asesmen TUK Test',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'SMKN 1 Cikarang',
            'tuk_address' => 'Jl. Industri No. 1',
            'pic_name' => 'Dr. H. Mulyadi, M.Pd.',
            'pic_position' => 'Kepala Sekolah',
            'date' => '2026-11-25',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'selected_instruments' => ['AK01'],
            'created_by' => $this->admin->id,
        ]);

        $responseEdit = $this->actingAs($this->admin)->get(route('admin.jadwal.edit', $schedule));
        $responseEdit->assertOk();
        $responseEdit->assertSee('Nama Penanggung Jawab TUK');
        $responseEdit->assertSee('Dr. H. Mulyadi, M.Pd.');
        $responseEdit->assertSee('Kepala Sekolah');
    }

    public function test_can_store_and_update_schedule_with_optional_pic_tuk(): void
    {
        // Store with PIC fields filled
        $responseStore = $this->actingAs($this->admin)->post(route('admin.jadwal.store'), [
            'name' => 'Jadwal Baru dengan PJ TUK',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Mandiri',
            'tuk_address' => 'Jl. Merdeka No. 45',
            'pic_name' => 'Budi Santoso, S.T.',
            'pic_position' => 'Ketua TUK',
            'date' => '2026-12-01',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'code' => 'SCH-NEW-01',
        ]);

        $responseStore->assertRedirect();
        $schedule = Schedule::where('code', 'SCH-NEW-01')->firstOrFail();
        $this->assertEquals('Budi Santoso, S.T.', $schedule->pic_name);
        $this->assertEquals('Ketua TUK', $schedule->pic_position);
        $this->assertEquals('Ketua TUK', $schedule->jabatan);
        $this->assertEquals('Budi Santoso, S.T.', $schedule->pic_tuk_name);
        $this->assertEquals('Ketua TUK', $schedule->pic_tuk_position);

        // Update clearing PIC fields (making them blank/empty)
        $responseUpdate = $this->actingAs($this->admin)->put(route('admin.jadwal.update', $schedule), [
            'name' => 'Jadwal Diperbarui Tanpa PJ TUK',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Mandiri',
            'tuk_address' => 'Jl. Merdeka No. 45',
            'pic_name' => '',
            'pic_position' => '',
            'date' => '2026-12-01',
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);

        $responseUpdate->assertRedirect(route('admin.jadwal.show', $schedule));
        $schedule->refresh();
        $this->assertNull($schedule->pic_name);
        $this->assertNull($schedule->pic_position);
        $this->assertNull($schedule->jabatan);
        $this->assertNull($schedule->pic_tuk_name);
        $this->assertNull($schedule->pic_tuk_position);
    }

    public function test_berita_acara_and_daftar_hadir_asesor_export_use_pic_tuk_when_filled(): void
    {
        $schedule = Schedule::create([
            'code' => 'SCH-FILLED-01',
            'name' => 'Jadwal Lengkap',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Inovasi',
            'tuk_address' => 'Jl. Raya No. 10',
            'pic_name' => 'Ahmad Suhendra, M.Kom.',
            'pic_position' => 'Manajer Operasional TUK',
            'date' => '2026-11-25',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'selected_instruments' => ['AK01'],
            'created_by' => $this->admin->id,
        ]);

        // 1. Dynamic service payload test
        $service = app(MukDynamicDocumentService::class);
        $baPayload = $service->buildPayload($schedule);
        $this->assertEquals('Ahmad Suhendra, M.Kom.', $baPayload['penanggungjawab_nama']);
        $this->assertEquals('Manajer Operasional TUK', $baPayload['penanggungjawab_jabatan']);

        // 2. HTML Export views test
        $resBa = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-ba', [$schedule, 'format' => 'html']));
        $resBa->assertOk();
        $resBa->assertSee('Ahmad Suhendra, M.Kom.');
        $resBa->assertSee('Manajer Operasional TUK');

        $resHadir = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-hadir-asesor', [$schedule, 'format' => 'html']));
        $resHadir->assertOk();
        $resHadir->assertSee('Ahmad Suhendra, M.Kom.');
        $resHadir->assertSee('Manajer Operasional TUK');
    }

    public function test_berita_acara_and_daftar_hadir_asesor_export_leave_fields_blank_when_empty(): void
    {
        $schedule = Schedule::create([
            'code' => 'SCH-EMPTY-01',
            'name' => 'Jadwal Kosong PJ',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Inovasi',
            'tuk_address' => 'Jl. Raya No. 10',
            'pic_name' => null,
            'pic_position' => null,
            'date' => '2026-11-25',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'selected_instruments' => ['AK01'],
            'created_by' => $this->admin->id,
        ]);

        // 1. Dynamic service payload must be empty string, not mock name
        $service = app(MukDynamicDocumentService::class);
        $baPayload = $service->buildPayload($schedule);
        $this->assertEquals('', $baPayload['penanggungjawab_nama']);
        $this->assertEquals('', $baPayload['penanggungjawab_jabatan']);

        // 2. HTML Export views must leave Penanggungjawab name & jabatan empty (blank)
        $resBa = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-ba', [$schedule, 'format' => 'html']));
        $resBa->assertOk();
        $resBa->assertDontSee('H. Ahmad Fauzi');
        $resBa->assertDontSee('Penanggung Jawab TUK</td>', false);
        $resBa->assertSee('<td style="width: 68%;"><strong></strong></td>', false);

        $resHadir = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-hadir-asesor', [$schedule, 'format' => 'html']));
        $resHadir->assertOk();
        $resHadir->assertDontSee('H. Ahmad Fauzi');
        $resHadir->assertDontSee('Penanggung Jawab TUK</td>', false);
        $resHadir->assertSee('<td style="width: 63%;"><strong></strong></td>', false);
    }

    public function test_pdf_export_berita_acara_and_daftar_hadir_asesor(): void
    {
        // 1. Test when filled
        $scheduleFilled = Schedule::create([
            'code' => 'SCH-PDF-FILLED',
            'name' => 'Jadwal PDF Filled',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Inovasi',
            'tuk_address' => 'Jl. Industri No. 10',
            'pic_name' => 'Dr. Bambang Hermanto',
            'pic_position' => 'Kepala TUK Mandiri',
            'date' => '2026-11-25',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'selected_instruments' => ['AK01'],
            'created_by' => $this->admin->id,
        ]);

        $resBaFilled = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-ba', $scheduleFilled));
        $resBaFilled->assertOk();
        $pdfPathBa = $resBaFilled->baseResponse->getFile()->getPathname();
        $textBaFilled = $this->extractPdfText($pdfPathBa);
        $this->assertStringContainsString('Penanggungjawab kegiatan', $textBaFilled);
        $this->assertStringContainsString('Dr. Bambang Hermanto', $textBaFilled);
        $this->assertStringContainsString('Kepala TUK Mandiri', $textBaFilled);

        $resHadirFilled = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-hadir-asesor', $scheduleFilled));
        $resHadirFilled->assertOk();
        $pdfPathHadir = $resHadirFilled->baseResponse->getFile()->getPathname();
        $textHadirFilled = $this->extractPdfText($pdfPathHadir);
        $this->assertStringContainsString('Penanggungjawab kegiatan', $textHadirFilled);
        $this->assertStringContainsString('Dr. Bambang Hermanto', $textHadirFilled);
        $this->assertStringContainsString('Kepala TUK Mandiri', $textHadirFilled);

        // 2. Test when empty
        $scheduleEmpty = Schedule::create([
            'code' => 'SCH-PDF-EMPTY',
            'name' => 'Jadwal PDF Empty',
            'scheme_id' => $this->scheme->id,
            'tuk_name' => 'TUK Inovasi',
            'tuk_address' => 'Jl. Industri No. 10',
            'pic_name' => null,
            'pic_position' => null,
            'date' => '2026-11-25',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'selected_instruments' => ['AK01'],
            'created_by' => $this->admin->id,
        ]);

        $resBaEmpty = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-ba', $scheduleEmpty));
        $resBaEmpty->assertOk();
        $textBaEmpty = $this->extractPdfText($resBaEmpty->baseResponse->getFile()->getPathname());
        $this->assertStringContainsString('Penanggungjawab kegiatan', $textBaEmpty);
        $this->assertStringNotContainsString('H. Ahmad Fauzi', $textBaEmpty);
        $this->assertStringNotContainsString('Kepala TUK Sewaktu', $textBaEmpty);

        $resHadirEmpty = $this->actingAs($this->admin)->get(route('admin.dokumen-muk.export-hadir-asesor', $scheduleEmpty));
        $resHadirEmpty->assertOk();
        $textHadirEmpty = $this->extractPdfText($resHadirEmpty->baseResponse->getFile()->getPathname());
        $this->assertStringContainsString('Penanggungjawab kegiatan', $textHadirEmpty);
        $this->assertStringNotContainsString('H. Ahmad Fauzi', $textHadirEmpty);
        $this->assertStringNotContainsString('Kepala TUK Sewaktu', $textHadirEmpty);
    }

    private function extractPdfText(string $pdfPath): string
    {
        $python = app(MukDynamicDocumentService::class)->getPythonExecutable();
        $script = <<<'PY'
import sys, pymupdf
doc = pymupdf.open(sys.argv[1])
full_text = ""
for page in doc:
    full_text += page.get_text() + "\n"
print(full_text)
PY;
        $tmp = tempnam(sys_get_temp_dir(), 'extract_pdf_');
        file_put_contents($tmp, $script);
        $output = shell_exec(escapeshellarg($python).' '.escapeshellarg($tmp).' '.escapeshellarg($pdfPath));
        @unlink($tmp);

        return $output ?: '';
    }
}
