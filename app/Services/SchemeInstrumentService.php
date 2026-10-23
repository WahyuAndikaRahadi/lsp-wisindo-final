<?php

namespace App\Services;

use App\Models\Scheme;
use Illuminate\Support\Facades\File;

class SchemeInstrumentService
{
    /**
     * Cache for loaded scheme json data.
     */
    protected array $schemeData = [];

    /**
     * Load raw scheme data from JSON fixture.
     */
    public function getSchemeData(Scheme $scheme): array
    {
        if (isset($this->schemeData[$scheme->code])) {
            return $this->schemeData[$scheme->code];
        }

        $filePath = database_path("seeders/data/{$scheme->code}.json");
        if (File::exists($filePath)) {
            $data = json_decode(File::get($filePath), true) ?: [];
            $this->schemeData[$scheme->code] = $data;

            return $data;
        }

        return [];
    }

    /**
     * Get FR.IA.02 (Tugas Praktik Demonstrasi - TPD) data for a scheme.
     */
    public function getTpdData(Scheme $scheme): array
    {
        $data = $this->getSchemeData($scheme);
        $tpd = $data['tpd'] ?? [];

        $defaultScenario = 'Anda adalah seorang teknisi otomotif profesional yang ditugaskan untuk melakukan pemeriksaan, pemeliharaan berkala, dan perbaikan pada unit kendaraan uji di Tempat Uji Kompetensi (TUK). Lakukan seluruh tahapan kerja dengan menerapkan K3, SOP pabrikan, dan catat hasil pekerjaan pada formulir yang disediakan.';

        return [
            'scenario' => ! empty($tpd['scenario']) ? $tpd['scenario'] : $defaultScenario,
            'time_limit' => $tpd['time_limit'] ?? '30 Menit',
            'tasks' => ! empty($tpd['tasks']) ? $tpd['tasks'] : [
                ['id' => '1', 'instruction' => 'Gunakan Alat Pelindung Diri (APD) dan persiapkan tempat kerja sesuai standar K3.'],
                ['id' => '2', 'instruction' => 'Siapkan peralatan tangan (hand tools), special service tools (SST), dan alat ukur sesuai spesifikasi.'],
                ['id' => '3', 'instruction' => 'Laksanakan prosedur diagnosis, pembongkaran, pengukuran, dan perbaikan komponen sesuai manual servis.'],
                ['id' => '4', 'instruction' => 'Catat seluruh hasil pengukuran pada Lembar Laporan Pemeriksaan (Report Sheet / FR.IA.01B).'],
                ['id' => '5', 'instruction' => 'Lakukan pembersihan area kerja (housekeeping) dan kembalikan peralatan ke tempat penyimpanan.'],
            ],
            'equipment' => [
                'Alat Pelindung Diri (Wearpack, Safety Shoes, Kacamata Pelindung, Sarung Tangan)',
                'Tool set mekanik lengkap (Kunci Ring, Pas, Soket, Kunci Momen)',
                'Alat ukur presisi terkalibrasi (Vernier Caliper, Micrometer, Multitester / AVO, Dial Gauge)',
                'Kendaraan unit uji / Trainer simulator kerja',
                'Buku Manual Reparasi (Workshop Manual / Manual Service)',
            ],
        ];
    }

    /**
     * Get FR.IA.03 (Pertanyaan Mendukung Observasi - PMO) questions for a scheme.
     */
    public function getPmoQuestions(Scheme $scheme): array
    {
        $data = $this->getSchemeData($scheme);
        $pmo = $data['pmo'] ?? [];

        if (! empty($pmo)) {
            return $pmo;
        }

        return [
            [
                'number' => '1',
                'question' => 'Jelaskan prosedur penerapan K3 dan APD yang wajib diperhatikan sebelum memulai pekerjaan servis pada unit ini!',
                'expected_answer' => 'Mengidentifikasi potensi bahaya, memastikan APD lengkap terpasang, memasang pelindung kendaraan (fender cover), dan menjaga kebersihan lantai kerja.',
            ],
            [
                'number' => '2',
                'question' => 'Bagaimana prosedur kalibrasi dan pengecekan nol (zero point) pada alat ukur presisi sebelum digunakan?',
                'expected_answer' => 'Memeriksa kebersihan bidang ukur, merapatkan spindle/jaw, memastikan garis nol sejajar atau jarum tepat di angka nol, serta mengunci baut stopper jika diperlukan.',
            ],
            [
                'number' => '3',
                'question' => 'Apa tindakan yang harus Anda ambil jika menemukan komponen yang aus atau memiliki nilai ukur di luar batas toleransi servis?',
                'expected_answer' => 'Mencatat penyimpangan pada lembar kerja teknis, mengonfirmasi spesifikasi batas servis pada manual resmi, dan merekomendasikan penggantian komponen kepada konsumen/atasan.',
            ],
        ];
    }

    /**
     * Get FR.IA.01B (Report Sheet / Lembar Pemeriksaan Teknis) items for a scheme.
     */
    public function getReportSheetItems(Scheme $scheme): array
    {
        $data = $this->getSchemeData($scheme);
        $items = $data['report_sheet'] ?? [];

        if (! empty($items)) {
            return $items;
        }

        return [
            ['item' => '1. Pemeriksaan APD dan Keselamatan Kerja Bengkel', 'standard' => 'SOP K3 & Kebersihan'],
            ['item' => '2. Kelayakan dan Kalibrasi Alat Ukur / Hand Tools', 'standard' => 'Presisi & Terkalibrasi'],
            ['item' => '3. Pemeriksaan Visual dan Fisik Sistem Utama', 'standard' => 'Workshop Manual'],
            ['item' => '4. Pengukuran Parameter Teknis Komponen', 'standard' => 'Dalam Rentang Spesifikasi Servis'],
            ['item' => '5. Housekeeping dan Pembersihan Area Kerja Uji', 'standard' => '5R / Ringkas Rapi Resik'],
        ];
    }

    /**
     * Map of Scheme IDs to directory in all-document-lsp-ProIndo.
     */
    protected array $schemeDirectoryMap = [
        1 => 'MUK SKEMA 1 Engine Junior Technician',
        2 => 'MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection',
        3 => 'MUK SKEMA 3 Maintenence Electrical Vehicle.pdf',
        4 => 'MUK SKEMA 4 Memperbaiki Panel Bodi Kendaraan',
        5 => 'MUK SKEMA 5 Mekanik Junior pdf',
        6 => 'MUK SKEMA 6 Servis Sepeda Motor Listrik.pdf',
        7 => 'MUK SKEMA 7 Klaster Servis Sepeda Motor Injeksi',
    ];

    /**
     * Get directory path for official MUK documents of a scheme.
     */
    public function getSchemeDirectory(Scheme $scheme): ?string
    {
        $dirName = $this->schemeDirectoryMap[$scheme->id] ?? null;
        if (! $dirName) {
            foreach ($this->schemeDirectoryMap as $id => $name) {
                if (str_contains(strtolower($name), strtolower($scheme->name))) {
                    $dirName = $name;
                    break;
                }
            }
        }

        if ($dirName) {
            $fullPath = base_path('all-document-lsp-ProIndo/'.$dirName);
            if (is_dir($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    /**
     * Get official PDF template path for an instrument code (e.g. IA02, IA01B, IA01, IA03).
     */
    public function getInstrumentTemplatePath(Scheme $scheme, string $instrumentCode): ?string
    {
        $codeUpper = strtoupper(str_replace(['.', ' ', '_', '-'], '', $instrumentCode));

        $cleanFixtures = [
            'IA01' => base_path('tests/Fixtures/muk/FR_IA_01_generated.pdf'),
            'IA01B' => base_path('tests/Fixtures/muk/FR_IA_01_B_generated.pdf'),
            'AK07' => base_path('tests/Fixtures/muk/FR_AK_07_generated.pdf'),
            'APL02' => base_path('tests/Fixtures/muk/FR_APL_02_generated.pdf'),
        ];

        if (isset($cleanFixtures[$codeUpper]) && file_exists($cleanFixtures[$codeUpper])) {
            return $cleanFixtures[$codeUpper];
        }

        $dir = $this->getSchemeDirectory($scheme);
        if (! $dir || ! is_dir($dir)) {
            return null;
        }

        $codeUpper = strtoupper(str_replace(['.', ' ', '_', '-'], '', $instrumentCode));
        $files = scandir($dir);

        foreach ($files as $file) {
            if (! str_ends_with(strtolower($file), '.pdf')) {
                continue;
            }

            $norm = strtoupper(str_replace(['.', ' ', '_', '-'], '', $file));

            if ($codeUpper === 'IA01B') {
                if (str_contains($norm, 'IA01B') || str_contains($norm, 'REPORTSHEET')) {
                    return $dir.DIRECTORY_SEPARATOR.$file;
                }
            } elseif ($codeUpper === 'IA01') {
                if ((str_contains($norm, 'IA01') && ! str_contains($norm, '01B') && ! str_contains($norm, 'REPORTSHEET')) || str_contains($norm, 'IA01CEKLIS')) {
                    return $dir.DIRECTORY_SEPARATOR.$file;
                }
            } elseif ($codeUpper === 'IA02') {
                if (str_contains($norm, 'IA02')) {
                    return $dir.DIRECTORY_SEPARATOR.$file;
                }
            } elseif ($codeUpper === 'IA03') {
                if (str_contains($norm, 'IA03')) {
                    return $dir.DIRECTORY_SEPARATOR.$file;
                }
            } elseif ($codeUpper === 'AK01') {
                if (str_contains($norm, 'AK01')) {
                    return $dir.DIRECTORY_SEPARATOR.$file;
                }
            } else {
                if (str_contains($norm, $codeUpper)) {
                    return $dir.DIRECTORY_SEPARATOR.$file;
                }
            }
        }

        // Check in official fixed document store
        $fixedService = app(MukFixedDocumentService::class);
        $fixedPath = $fixedService->getFixedFilePath($scheme, $instrumentCode);
        if ($fixedPath && file_exists($fixedPath)) {
            return $fixedPath;
        }

        return null;
    }
}
