<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class MukExportService
{
    /**
     * Data bundle for schedule-level exports (BA, Attendance, AK05, AK06, Cover, Daftar Periksa)
     *
     * @return array<string, mixed>
     */
    public function getExportData(Schedule $schedule): array
    {
        $schedule->load([
            'scheme.competencyUnits.elements.kuks',
            'scheme.questionBanks',
            'assessors.profile',
            'assessments.asesi.profile',
            'assessments.ak02Items.competencyUnit',
            'assessments.ak03Items',
            'assessments.appeals',
        ]);

        $totalAsesi = $schedule->assessments->count();
        $totalTidakHadir = $schedule->assessments->filter(function ($a) {
            return $a->final_result === 'TIDAK_HADIR';
        })->count();
        $totalKompeten = $schedule->assessments->filter(function ($a) {
            return in_array($a->final_result, ['K', 'KOMPETEN'], true) || ($a->final_result === null && $a->status === 'COMPLETED');
        })->count();
        $totalBelumKompeten = $schedule->assessments->filter(function ($a) {
            return in_array($a->final_result, ['BK', 'BELUM_KOMPETEN'], true);
        })->count();

        $schedule->loadMissing(['assessors.profile', 'assessments.asesi.profile', 'assessments.assessor.profile']);

        $assessments = $schedule->assessments;
        $scheduleAssessors = $schedule->assessors;

        $assessorMap = [];
        foreach ($scheduleAssessors as $as) {
            $assessorMap[$as->id] = [
                'assessor' => $as,
                'assessments' => collect(),
            ];
        }

        $unassigned = collect();
        foreach ($assessments as $ass) {
            $assessorId = $ass->assessor_id;
            if ($assessorId && isset($assessorMap[$assessorId])) {
                $assessorMap[$assessorId]['assessments']->push($ass);
            } elseif ($ass->assessor) {
                if (! isset($assessorMap[$ass->assessor->id])) {
                    $assessorMap[$ass->assessor->id] = [
                        'assessor' => $ass->assessor,
                        'assessments' => collect(),
                    ];
                }
                $assessorMap[$ass->assessor->id]['assessments']->push($ass);
            } else {
                $unassigned->push($ass);
            }
        }

        if ($unassigned->isNotEmpty()) {
            if (! empty($assessorMap)) {
                $firstKey = array_key_first($assessorMap);
                $assessorMap[$firstKey]['assessments'] = $assessorMap[$firstKey]['assessments']->merge($unassigned);
            } else {
                $leadAssessor = $scheduleAssessors->first();
                $assessorMap[0] = [
                    'assessor' => $leadAssessor,
                    'assessments' => $unassigned,
                ];
            }
        }

        $activeGroups = array_values(array_filter($assessorMap, fn ($g) => $g['assessments']->isNotEmpty()));
        $sortedAssessments = collect();
        foreach ($activeGroups as $g) {
            $sortedAssessments = $sortedAssessments->merge($g['assessments']);
        }

        return [
            'schedule' => $schedule,
            'scheme' => $schedule->scheme,
            'assessors' => $schedule->assessors,
            'leadAssessor' => $schedule->assessors->first(),
            'assessments' => $sortedAssessments->isNotEmpty() ? $sortedAssessments : $schedule->assessments,
            'assessorGroups' => $activeGroups,
            'total_asesi' => $totalAsesi,
            'total_asesi_terbilang' => self::terbilang($totalAsesi),
            'total_kompeten' => $totalKompeten,
            'total_kompeten_terbilang' => self::terbilang($totalKompeten),
            'total_belum_kompeten' => $totalBelumKompeten,
            'total_belum_kompeten_terbilang' => self::terbilang($totalBelumKompeten),
            'total_tidak_hadir' => $totalTidakHadir,
            'total_tidak_hadir_terbilang' => self::terbilang($totalTidakHadir),
            'questionBanks' => $schedule->scheme?->questionBanks ?? collect(),
        ];
    }

    /**
     * Data bundle for individual asesi MUK exports (APL01, APL02, AK01, AK02, AK03, AK04, AK07, Pernyataan)
     *
     * @return array<string, mixed>
     */
    public function getAsesiExportData(Assessment $assessment): array
    {
        $assessment->load([
            'schedule.scheme.competencyUnits.elements.kuks',
            'schedule.scheme.questionBanks',
            'schedule.assessors.profile',
            'asesi.profile',
            'assessor.profile',
            'proofs',
            'apl02Items.kuk.unitElement.competencyUnit',
            'iaResponses',
            'ak07Items',
            'ak02Items.competencyUnit',
            'ak03Items',
            'appeals',
        ]);

        return [
            'assessment' => $assessment,
            'schedule' => $assessment->schedule,
            'scheme' => $assessment->schedule?->scheme ?? $assessment->scheme,
            'asesi' => $assessment->asesi,
            'profile' => $assessment->asesi->profile,
            'assessor' => $assessment->assessor ?? $assessment->schedule?->assessors->first(),
            'ak02Items' => $assessment->ak02Items,
            'ak03Items' => $assessment->ak03Items,
            'appeals' => $assessment->appeals,
            'iaResponses' => $assessment->iaResponses,
            'questionBanks' => $assessment->schedule?->scheme?->questionBanks ?? collect(),
        ];
    }

    /**
     * Convert integer to Indonesian word representation (e.g. 10 -> sepuluh).
     */
    public static function terbilang(int $number): string
    {
        if ($number === 0) {
            return 'nol';
        }

        $words = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        if ($number < 12) {
            return $words[$number];
        }
        if ($number < 20) {
            return self::terbilang($number - 10).' belas';
        }
        if ($number < 100) {
            $remainder = $number % 10;

            return self::terbilang((int) ($number / 10)).' puluh'.($remainder ? ' '.self::terbilang($remainder) : '');
        }
        if ($number < 200) {
            $remainder = $number - 100;

            return 'seratus'.($remainder ? ' '.self::terbilang($remainder) : '');
        }
        if ($number < 1000) {
            $remainder = $number % 100;

            return self::terbilang((int) ($number / 100)).' ratus'.($remainder ? ' '.self::terbilang($remainder) : '');
        }

        return (string) $number;
    }

    /**
     * Export all candidates in a schedule to Excel (SpreadsheetML .xls format).
     */
    public function exportScheduleAsesiXls(Schedule $schedule, array $options = []): Response
    {
        $schedule->load([
            'scheme',
            'assessors.profile',
            'assessments.asesi.profile',
            'assessments.assessor.profile',
        ]);

        $xml = $this->generateAsesiSpreadsheetXml($schedule->assessments, $schedule, $options);
        $filename = 'REKAP-ASESI-'.preg_replace('/[^A-Za-z0-9_-]/', '_', $schedule->code ?: $schedule->id).'.xls';

        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export single candidate assessment to Excel (SpreadsheetML .xls format).
     */
    public function exportAssessmentAsesiXls(Assessment $assessment, array $options = []): Response
    {
        $assessment->load([
            'schedule.scheme',
            'schedule.assessors.profile',
            'asesi.profile',
            'assessor.profile',
        ]);

        $schedule = $assessment->schedule;
        $xml = $this->generateAsesiSpreadsheetXml(collect([$assessment]), $schedule, $options);
        $asesiName = $assessment->asesi?->full_name ?? 'Asesi';
        $filename = 'BIODATA-ASESI-'.preg_replace('/[^A-Za-z0-9_-]/', '_', $asesiName).'.xls';

        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export assessors in a schedule to Excel (SpreadsheetML .xls format).
     */
    public function exportScheduleAsesorXls(Schedule $schedule): Response
    {
        $schedule->load([
            'scheme',
            'assessors.profile',
        ]);

        $schemeName = $schedule->scheme?->name ?? 'Skema Sertifikasi LSP PrO Indo';
        $tukName = $schedule->tuk_name ?? $schedule->tuk?->name ?? 'TUK Otomotif Mandiri';
        $testDate = $schedule->date ? Carbon::parse($schedule->date)->format('d/m/Y') : '';
        $escape = static fn ($v) => htmlspecialchars((string) ($v ?? ''), ENT_XML1, 'UTF-8');

        $headers = [
            ['title' => 'NO.', 'width' => 45],
            ['title' => 'NAMA ASESOR KOMPETENSI', 'width' => 220],
            ['title' => 'NO. REGISTRASI (MET)', 'width' => 180],
            ['title' => 'SKEMA SERTIFIKASI', 'width' => 250],
            ['title' => 'TUK', 'width' => 200],
            ['title' => 'TANGGAL ASESMEN', 'width' => 140],
        ];

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<?mso-application progid=\"Excel.Sheet\"?>\n";
        $xml .= "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
        $xml .= " xmlns:o=\"urn:schemas-microsoft-com:office:office\"\n";
        $xml .= " xmlns:x=\"urn:schemas-microsoft-com:office:excel\"\n";
        $xml .= " xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
        $xml .= " xmlns:html=\"http://www.w3.org/TR/REC-html40\">\n";
        $xml .= " <Styles>\n";
        $xml .= "  <Style ss:ID=\"Header\">\n";
        $xml .= "   <Alignment ss:Horizontal=\"Center\" ss:Vertical=\"Center\" ss:WrapText=\"1\"/>\n";
        $xml .= "   <Borders><Border ss:Position=\"Bottom\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#000000\"/></Borders>\n";
        $xml .= "   <Font ss:FontName=\"Calibri\" ss:Size=\"10\" ss:Color=\"#FFFFFF\" ss:Bold=\"1\"/>\n";
        $xml .= "   <Interior ss:Color=\"#154B85\" ss:Pattern=\"Solid\"/>\n";
        $xml .= "  </Style>\n";
        $xml .= "  <Style ss:ID=\"CellLeft\">\n";
        $xml .= "   <Alignment ss:Horizontal=\"Left\" ss:Vertical=\"Center\"/>\n";
        $xml .= "   <Borders><Border ss:Position=\"Bottom\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#D9D9D9\"/></Borders>\n";
        $xml .= "   <Font ss:FontName=\"Calibri\" ss:Size=\"10\" ss:Color=\"#000000\"/>\n";
        $xml .= "   <NumberFormat ss:Format=\"@\"/>\n";
        $xml .= "  </Style>\n";
        $xml .= "  <Style ss:ID=\"CellCenter\">\n";
        $xml .= "   <Alignment ss:Horizontal=\"Center\" ss:Vertical=\"Center\"/>\n";
        $xml .= "   <Borders><Border ss:Position=\"Bottom\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#D9D9D9\"/></Borders>\n";
        $xml .= "   <Font ss:FontName=\"Calibri\" ss:Size=\"10\" ss:Color=\"#000000\"/>\n";
        $xml .= "   <NumberFormat ss:Format=\"@\"/>\n";
        $xml .= "  </Style>\n";
        $xml .= " </Styles>\n";
        $xml .= " <Worksheet ss:Name=\"Daftar Asesor\">\n";
        $xml .= "  <Table ss:DefaultRowHeight=\"20\">\n";

        foreach ($headers as $h) {
            $xml .= "   <Column ss:Width=\"{$h['width']}\"/>\n";
        }

        $xml .= "   <Row ss:Height=\"28\">\n";
        foreach ($headers as $h) {
            $xml .= '    <Cell ss:StyleID="Header"><Data ss:Type="String">'.$escape($h['title'])."</Data></Cell>\n";
        }
        $xml .= "   </Row>\n";

        foreach ($schedule->assessors as $idx => $as) {
            $noMet = $as->assessor_registration_number ?? $as->profile?->no_reg ?? 'MET.000.001234 2022';
            $xml .= "   <Row ss:Height=\"22\">\n";
            $xml .= '    <Cell ss:StyleID="CellCenter"><Data ss:Type="String">'.($idx + 1)."</Data></Cell>\n";
            $xml .= '    <Cell ss:StyleID="CellLeft"><Data ss:Type="String">'.$escape($as->full_name)."</Data></Cell>\n";
            $xml .= '    <Cell ss:StyleID="CellCenter"><Data ss:Type="String">'.$escape($noMet)."</Data></Cell>\n";
            $xml .= '    <Cell ss:StyleID="CellLeft"><Data ss:Type="String">'.$escape($schemeName)."</Data></Cell>\n";
            $xml .= '    <Cell ss:StyleID="CellLeft"><Data ss:Type="String">'.$escape($tukName)."</Data></Cell>\n";
            $xml .= '    <Cell ss:StyleID="CellCenter"><Data ss:Type="String">'.$escape($testDate)."</Data></Cell>\n";
            $xml .= "   </Row>\n";
        }

        $xml .= "  </Table>\n";
        $xml .= " </Worksheet>\n";
        $xml .= '</Workbook>';

        $filename = 'DAFTAR-ASESOR-'.preg_replace('/[^A-Za-z0-9_-]/', '_', $schedule->code ?: $schedule->id).'.xls';

        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Generate SpreadsheetML XML matching official BNSP recap template.
     *
     * @param  Collection<int, Assessment>  $assessments
     */
    public function generateAsesiSpreadsheetXml(Collection $assessments, ?Schedule $schedule = null, array $options = []): string
    {
        $leadAssessor = $schedule?->assessors?->first();
        $defaultAssessorName = $leadAssessor?->full_name ?? 'Asesor LSP PrO Indo';
        $defaultAssessorReg = $leadAssessor?->profile?->no_reg ?? $leadAssessor?->assessor_registration_number ?? 'MET.000.001234 2022';
        $scheduleCode = $schedule?->code ?? '-';
        $testDate = $schedule?->date ? Carbon::parse($schedule->date)->format('d/m/Y') : '';

        $selectedSumberAnggaran = $options['kode_sumber_anggaran']
            ?? ($options['sumber_anggaran'] ?? ($schedule?->kode_sumber_anggaran ?? '1'));
        $selectedKementerian = $options['kode_kementerian']
            ?? ($options['kementerian'] ?? ($schedule?->kode_kementerian ?? '11'));

        $escape = static fn ($v) => htmlspecialchars((string) ($v ?? ''), ENT_XML1, 'UTF-8');

        $headers = [
            ['title' => 'NO.', 'width' => 45],
            ['title' => 'NAMA ASESI', 'width' => 180],
            ['title' => 'NIK', 'width' => 140],
            ['title' => 'TEMPAT LAHIR', 'width' => 130],
            ['title' => 'TANGGAL LAHIR (dd/mm/yyyy)', 'width' => 140],
            ['title' => 'JENIS KELAMIN (L/P)', 'width' => 110],
            ['title' => 'TEMPAT TINGGAL', 'width' => 300],
            ['title' => 'KODE KOTA', 'width' => 90],
            ['title' => 'KODE PROVINSI', 'width' => 100],
            ['title' => 'TELP', 'width' => 120],
            ['title' => 'EMAIL', 'width' => 160],
            ['title' => 'KODE PENDIDIKAN', 'width' => 120],
            ['title' => 'KODE PEKERJAAN', 'width' => 120],
            ['title' => 'KODE JADWAL', 'width' => 110],
            ['title' => 'TANGGAL UJI (hh/bb/yyyy)', 'width' => 140],
            ['title' => 'Nama Asessor', 'width' => 180],
            ['title' => 'NOMOR REGISTRASI ASESOR', 'width' => 160],
            ['title' => 'KODE SUMBER ANGGARAN', 'width' => 130],
            ['title' => 'KODE KEMENTERIAN', 'width' => 120],
            ['title' => 'K/BK', 'width' => 60],
        ];

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<?mso-application progid=\"Excel.Sheet\"?>\n";
        $xml .= "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
        $xml .= " xmlns:o=\"urn:schemas-microsoft-com:office:office\"\n";
        $xml .= " xmlns:x=\"urn:schemas-microsoft-com:office:excel\"\n";
        $xml .= " xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
        $xml .= " xmlns:html=\"http://www.w3.org/TR/REC-html40\">\n";
        $xml .= " <DocumentProperties xmlns=\"urn:schemas-microsoft-com:office:office\">\n";
        $xml .= "  <Author>LSP PrO Indo</Author>\n";
        $xml .= '  <Created>'.now()->toIso8601String()."</Created>\n";
        $xml .= " </DocumentProperties>\n";
        $xml .= " <Styles>\n";
        $xml .= "  <Style ss:ID=\"Default\" ss:Name=\"Normal\">\n";
        $xml .= "   <Alignment ss:Vertical=\"Center\"/>\n";
        $xml .= "   <Font ss:FontName=\"Calibri\" ss:Size=\"11\" ss:Color=\"#000000\"/>\n";
        $xml .= "  </Style>\n";
        $xml .= "  <Style ss:ID=\"Header\">\n";
        $xml .= "   <Alignment ss:Horizontal=\"Center\" ss:Vertical=\"Center\" ss:WrapText=\"1\"/>\n";
        $xml .= "   <Borders>\n";
        $xml .= "    <Border ss:Position=\"Bottom\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#000000\"/>\n";
        $xml .= "    <Border ss:Position=\"Left\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#000000\"/>\n";
        $xml .= "    <Border ss:Position=\"Right\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#000000\"/>\n";
        $xml .= "    <Border ss:Position=\"Top\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#000000\"/>\n";
        $xml .= "   </Borders>\n";
        $xml .= "   <Font ss:FontName=\"Calibri\" ss:Size=\"10\" ss:Color=\"#FFFFFF\" ss:Bold=\"1\"/>\n";
        $xml .= "   <Interior ss:Color=\"#154B85\" ss:Pattern=\"Solid\"/>\n";
        $xml .= "  </Style>\n";
        $xml .= "  <Style ss:ID=\"CellLeft\">\n";
        $xml .= "   <Alignment ss:Horizontal=\"Left\" ss:Vertical=\"Center\"/>\n";
        $xml .= "   <Borders>\n";
        $xml .= "    <Border ss:Position=\"Bottom\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#000000\"/>\n";
        $xml .= "    <Border ss:Position=\"Left\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#000000\"/>\n";
        $xml .= "    <Border ss:Position=\"Right\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#000000\"/>\n";
        $xml .= "    <Border ss:Position=\"Top\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#000000\"/>\n";
        $xml .= "   </Borders>\n";
        $xml .= "   <Font ss:FontName=\"Calibri\" ss:Size=\"10\" ss:Color=\"#000000\"/>\n";
        $xml .= "   <NumberFormat ss:Format=\"@\"/>\n";
        $xml .= "  </Style>\n";
        $xml .= "  <Style ss:ID=\"CellCenter\">\n";
        $xml .= "   <Alignment ss:Horizontal=\"Center\" ss:Vertical=\"Center\"/>\n";
        $xml .= "   <Borders>\n";
        $xml .= "    <Border ss:Position=\"Bottom\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#000000\"/>\n";
        $xml .= "    <Border ss:Position=\"Left\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#000000\"/>\n";
        $xml .= "    <Border ss:Position=\"Right\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#000000\"/>\n";
        $xml .= "    <Border ss:Position=\"Top\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#000000\"/>\n";
        $xml .= "   </Borders>\n";
        $xml .= "   <Font ss:FontName=\"Calibri\" ss:Size=\"10\" ss:Color=\"#000000\"/>\n";
        $xml .= "   <NumberFormat ss:Format=\"@\"/>\n";
        $xml .= "  </Style>\n";
        $xml .= " </Styles>\n";
        $xml .= " <Worksheet ss:Name=\"Data Asesi\">\n";
        $xml .= "  <Table ss:DefaultRowHeight=\"20\">\n";

        foreach ($headers as $h) {
            $xml .= "   <Column ss:Width=\"{$h['width']}\"/>\n";
        }

        // Header Row
        $xml .= "   <Row ss:Height=\"30\">\n";
        foreach ($headers as $h) {
            $xml .= '    <Cell ss:StyleID="Header"><Data ss:Type="String">'.$escape($h['title'])."</Data></Cell>\n";
        }
        $xml .= "   </Row>\n";

        // Candidate Rows
        foreach ($assessments as $idx => $ass) {
            $asesi = $ass->asesi;
            $profile = $asesi?->profile;

            $birthDate = $profile?->tanggal_lahir ? Carbon::parse($profile->tanggal_lahir)->format('d/m/Y') : '';
            $gender = $profile?->jenis_kelamin ? strtoupper(substr($profile->jenis_kelamin, 0, 1)) : 'L';
            $assessor = $ass->assessor ?? $leadAssessor;
            $assessorName = $assessor?->full_name ?? $defaultAssessorName;
            $assessorReg = $assessor?->profile?->no_reg ?? $assessor?->assessor_registration_number ?? $defaultAssessorReg;

            if (in_array($ass->final_result, ['K', 'KOMPETEN'], true)) {
                $finalResult = 'K';
            } elseif (in_array($ass->final_result, ['BK', 'BELUM_KOMPETEN'], true)) {
                $finalResult = 'BK';
            } elseif ($ass->final_result === 'TIDAK_HADIR') {
                $finalResult = 'TH';
            } else {
                $finalResult = ($ass->status === 'COMPLETED' ? 'K' : ($ass->final_result ?? ''));
            }

            $cityProv = BnspReferenceService::resolveCityAndProvince(
                $profile?->kode_kota,
                $profile?->kode_provinsi,
                $profile?->alamat_rumah,
                $schedule?->tuk_name
            );

            $kodeKota = $profile?->kode_kota ?: ($cityProv['kode_kota'] ?? '');
            $kodeProvinsi = $profile?->kode_provinsi ?: ($cityProv['kode_provinsi'] ?? '');
            $kodePendidikan = $profile?->effective_kode_pendidikan ?? BnspReferenceService::resolveEducationCode($profile?->pendidikan_terakhir);
            $kodePekerjaan = $profile?->effective_kode_pekerjaan ?? BnspReferenceService::resolveJobCode($profile?->jabatan, $profile?->kode_pekerjaan);

            // Kode Jadwal sengaja dikosongkan sesuai instruksi user
            $kodeJadwal = '';

            $cells = [
                ['val' => $idx + 1, 'center' => true],
                ['val' => $asesi?->full_name ?? '', 'center' => false],
                ['val' => $profile?->nik ?? '', 'center' => true],
                ['val' => $profile?->tempat_lahir ?? '', 'center' => false],
                ['val' => $birthDate, 'center' => true],
                ['val' => $gender, 'center' => true],
                ['val' => $profile?->alamat_rumah ?? '', 'center' => false],
                ['val' => $kodeKota, 'center' => true], // KODE KOTA
                ['val' => $kodeProvinsi, 'center' => true], // KODE PROVINSI
                ['val' => $asesi?->phone ?? $profile?->no_telepon_rumah ?? '', 'center' => true],
                ['val' => $asesi?->email ?? '', 'center' => false],
                ['val' => $kodePendidikan, 'center' => true], // KODE PENDIDIKAN
                ['val' => $kodePekerjaan, 'center' => true], // KODE PEKERJAAN
                ['val' => $kodeJadwal, 'center' => true], // KODE JADWAL (empty)
                ['val' => $testDate, 'center' => true],
                ['val' => $assessorName, 'center' => false],
                ['val' => $assessorReg, 'center' => true],
                ['val' => (string) $selectedSumberAnggaran, 'center' => true], // KODE SUMBER ANGGARAN
                ['val' => (string) $selectedKementerian, 'center' => true], // KODE KEMENTERIAN
                ['val' => $finalResult, 'center' => true],
            ];

            $xml .= "   <Row ss:Height=\"22\">\n";
            foreach ($cells as $c) {
                $style = $c['center'] ? 'CellCenter' : 'CellLeft';
                $xml .= "    <Cell ss:StyleID=\"{$style}\"><Data ss:Type=\"String\">".$escape($c['val'])."</Data></Cell>\n";
            }
            $xml .= "   </Row>\n";
        }

        $xml .= "  </Table>\n";
        $xml .= "  <WorksheetOptions xmlns=\"urn:schemas-microsoft-com:office:excel\">\n";
        $xml .= "   <Selected/>\n";
        $xml .= "   <FreezePanes/>\n";
        $xml .= "   <FrozenNoSplit/>\n";
        $xml .= "   <SplitPane>\n";
        $xml .= "    <SplitRow>1</SplitRow>\n";
        $xml .= "    <TopRowBottomPane>1</TopRowBottomPane>\n";
        $xml .= "    <ActivePane>2</ActivePane>\n";
        $xml .= "   </SplitPane>\n";
        $xml .= "   <Panes>\n";
        $xml .= "    <Pane>\n";
        $xml .= "     <Number>3</Number>\n";
        $xml .= "    </Pane>\n";
        $xml .= "    <Pane>\n";
        $xml .= "     <Number>2</Number>\n";
        $xml .= "     <ActiveRow>0</ActiveRow>\n";
        $xml .= "    </Pane>\n";
        $xml .= "   </Panes>\n";
        $xml .= "   <ProtectObjects>False</ProtectObjects>\n";
        $xml .= "   <ProtectScenarios>False</ProtectScenarios>\n";
        $xml .= "  </WorksheetOptions>\n";
        $xml .= " </Worksheet>\n";
        $xml .= '</Workbook>';

        return $xml;
    }
}
