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

    /**
     * Get the exact official text of FR.IA.02 (Tugas Praktik Demonstrasi) for a scheme.
     */
    public function getIa02FullText(Scheme $scheme): string
    {
        $dirName = $this->schemeDirectoryMap[$scheme->id] ?? null;
        if (! $dirName) {
            foreach ($this->schemeDirectoryMap as $id => $name) {
                if (str_contains(strtolower($name), strtolower($scheme->name ?? ''))) {
                    $dirName = $name;
                    break;
                }
            }
        }

        $searchPaths = [];
        if ($dirName) {
            $searchPaths[] = base_path("lsp-document-fixed/extracted/{$dirName}");
            $searchPaths[] = base_path("all-document-lsp-ProIndo/extracted/{$dirName}");
            $searchPaths[] = base_path("lsp-document-dynamic/extracted/{$dirName}");
        }

        $raw = '';
        foreach ($searchPaths as $p) {
            if (is_dir($p)) {
                $files = glob("{$p}/*IA.02*.txt") ?: glob("{$p}/*IA*02*.txt");
                if (! empty($files) && file_exists($files[0])) {
                    $raw = file_get_contents($files[0]);
                    break;
                }
            }
        }

        if (empty($raw)) {
            $pdfPath = $this->getInstrumentTemplatePath($scheme, 'IA02');
            if ($pdfPath && file_exists($pdfPath)) {
                // If text file not found, extract from PDF via python/fitz or fallback to TPD scenario
                $tpd = $this->getTpdData($scheme);

                return "A. Petunjuk\n1. Baca dan pelajari setiap instruksi kerja di bawah ini dengan cermat sebelum melaksanakan praktek\n2. Klarifikasi kepada asesor kompetensi apabila ada hal-hal yang belum jelas\n3. Laksanakan pekerjaan sesuai dengan urutan proses yang sudah ditetapkan\n4. Seluruh proses kerja mengacu kepada SOP/WI yang dipersyaratkan\n\nB. Skenario Tugas Praktik Demonstrasi\n{$tpd['scenario']}";
            }
        }

        $start = strpos($raw, 'A. Petunjuk');
        if ($start === false) {
            $start = 0;
        }

        $end = strpos($raw, 'PENYUSUN DAN VALIDATOR');
        if ($end === false) {
            $end = strpos($raw, 'ASESI :');
        }
        if ($end === false) {
            $end = strlen($raw);
        }

        $body = substr($raw, $start, $end - $start);
        $lines = explode("\n", $body);
        $cleanedLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/^\x0c?\s*(Lembaga Sertifikasi Profesi|LSP-PrO Indo|\d+)\s*$/i', $trimmed)) {
                continue;
            }
            $cleanedLines[] = rtrim($line, "\r");
        }

        return trim(implode("\n", $cleanedLines));
    }

    /**
     * Format FR.IA.02 text into structured, elegant semantic HTML for web display.
     */
    public function getIa02FormattedHtml(Scheme $scheme): string
    {
        $rawText = $this->getIa02FullText($scheme);
        if (empty($rawText)) {
            return '';
        }

        $posA = strpos($rawText, 'A. Petunjuk');
        $posB = strpos($rawText, 'B. Skenario');

        $html = '';

        // Section A: Petunjuk
        if ($posA !== false && $posB !== false) {
            $petunjukText = substr($rawText, $posA, $posB - $posA);
            $lines = explode("\n", $petunjukText);
            $items = [];
            foreach ($lines as $l) {
                $t = trim($l);
                if (preg_match('/^(\d+)[\.\)]\s*(.+)/', $t, $m)) {
                    $items[] = ['num' => $m[1], 'text' => $m[2]];
                }
            }

            $html .= '<div class="p-5 bg-blue-50/70 border border-blue-200 rounded-xl space-y-3 mb-6 shadow-xs">';
            $html .= '<div class="flex items-center space-x-2 text-xs font-bold text-blue-900 uppercase tracking-wider">';
            $html .= '<span class="w-5 h-5 rounded-md bg-blue-600 text-white flex items-center justify-center text-[10px] font-bold">A</span>';
            $html .= '<span>Petunjuk Pelaksanaan Uji Praktik Demonstrasi</span>';
            $html .= '</div>';
            $html .= '<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">';
            foreach ($items as $it) {
                $html .= '<div class="flex items-start gap-2.5 p-3 rounded-lg bg-white border border-blue-100 shadow-2xs">';
                $html .= '<span class="w-5 h-5 rounded bg-blue-100 text-blue-800 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">'.$it['num'].'</span>';
                $html .= '<p class="text-xs text-slate-800 leading-relaxed">'.htmlspecialchars($it['text']).'</p>';
                $html .= '</div>';
            }
            $html .= '</div></div>';

            $body = substr($rawText, $posB);
        } else {
            $body = $rawText;
        }

        // Section B: Skenario & Tugas Praktik
        $paragraphs = preg_split('/\n\s*\n/', $body);
        $html .= '<div class="space-y-6">';

        foreach ($paragraphs as $para) {
            $trimmed = trim($para);
            if (empty($trimmed)) {
                continue;
            }

            if (preg_match('/^B\.\s*Skenario/i', $trimmed)) {
                $html .= '<div class="pb-2 border-b border-slate-200">';
                $html .= '<h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">';
                $html .= '<span class="w-6 h-6 rounded-md bg-slate-900 text-amber-400 flex items-center justify-center font-bold text-xs">B</span>';
                $html .= '<span>Skenario Tugas Praktik Demonstrasi</span>';
                $html .= '</h3></div>';

                continue;
            }

            // Units table
            if (preg_match_all('/([A-Z]\.[0-9A-Z]+\.[0-9A-Z]+\.[0-9A-Z]+)\s+([^\n\r]+)/', $trimmed, $unitMatches, PREG_SET_ORDER)) {
                $html .= '<div class="rounded-xl border border-slate-200 overflow-hidden bg-white shadow-2xs my-3">';
                $html .= '<div class="px-4 py-2.5 bg-slate-100 border-b border-slate-200 flex items-center justify-between text-xs font-bold text-slate-700">';
                $html .= '<span>Daftar Unit Kompetensi Terkait</span>';
                $html .= '<span class="text-[11px] font-mono text-brand-700 bg-brand-50 px-2 py-0.5 rounded border border-brand-200">'.count($unitMatches).' Unit</span>';
                $html .= '</div>';
                $html .= '<table class="w-full text-xs">';
                $html .= '<thead class="bg-slate-50 text-slate-600 border-b border-slate-200"><tr>';
                $html .= '<th class="px-3.5 py-2 text-center w-12">No</th>';
                $html .= '<th class="px-3.5 py-2 text-left w-48">Kode Unit</th>';
                $html .= '<th class="px-3.5 py-2 text-left">Judul Unit Kompetensi</th>';
                $html .= '</tr></thead>';
                $html .= '<tbody class="divide-y divide-slate-100">';
                foreach ($unitMatches as $idx => $um) {
                    $html .= '<tr class="hover:bg-slate-50/60">';
                    $html .= '<td class="px-3.5 py-2 text-center font-mono text-slate-500">'.($idx + 1).'</td>';
                    $html .= '<td class="px-3.5 py-2 font-mono font-bold text-brand-700">'.htmlspecialchars($um[1]).'</td>';
                    $html .= '<td class="px-3.5 py-2 text-slate-800 font-medium">'.htmlspecialchars(trim($um[2])).'</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody></table></div>';

                continue;
            }

            // S. Skenario
            if (preg_match('/^(?:Skenario.*?:\s*)?S\.\s*(.+)/s', $trimmed, $m)) {
                $html .= '<div class="p-4 rounded-xl border border-sky-200 bg-sky-50/50 space-y-2 shadow-2xs">';
                $html .= '<div class="flex items-center gap-2">';
                $html .= '<span class="w-5 h-5 rounded-md bg-sky-600 text-white font-bold text-xs flex items-center justify-center">S</span>';
                $html .= '<span class="font-bold text-sky-950 text-xs uppercase tracking-wide">Skenario &amp; Persyaratan Asesi</span>';
                $html .= '</div>';
                $html .= '<div class="text-xs text-slate-800 leading-relaxed pl-7 whitespace-pre-line">'.htmlspecialchars(trim($m[1])).'</div>';
                $html .= '</div>';

                continue;
            }

            // T. Tugas
            if (preg_match('/^T\.\s*(.+)/s', $trimmed, $m)) {
                $html .= '<div class="p-4 rounded-xl border border-indigo-200 bg-indigo-50/50 space-y-2 shadow-2xs">';
                $html .= '<div class="flex items-center gap-2">';
                $html .= '<span class="w-5 h-5 rounded-md bg-indigo-600 text-white font-bold text-xs flex items-center justify-center">T</span>';
                $html .= '<span class="font-bold text-indigo-950 text-xs uppercase tracking-wide">Tugas Unjuk Kerja / Praktik</span>';
                $html .= '</div>';
                $html .= '<div class="text-xs text-slate-900 font-semibold leading-relaxed pl-7 whitespace-pre-line">'.htmlspecialchars(trim($m[1])).'</div>';
                $html .= '</div>';

                continue;
            }

            // A. Alat & Bahan
            if (preg_match('/^A\.\s*(.+)/s', $trimmed, $m)) {
                $html .= '<div class="p-4 rounded-xl border border-amber-200 bg-amber-50/50 space-y-2 shadow-2xs">';
                $html .= '<div class="flex items-center gap-2">';
                $html .= '<span class="w-5 h-5 rounded-md bg-amber-600 text-white font-bold text-xs flex items-center justify-center">A</span>';
                $html .= '<span class="font-bold text-amber-950 text-xs uppercase tracking-wide">Alat, Bahan &amp; Perlengkapan yang Diberikan</span>';
                $html .= '</div>';
                $html .= '<div class="text-xs text-slate-800 leading-relaxed pl-7 whitespace-pre-line">'.htmlspecialchars(trim($m[1])).'</div>';
                $html .= '</div>';

                continue;
            }

            // R. Hasil
            if (preg_match('/^R\.\s*(.+)/s', $trimmed, $m)) {
                $html .= '<div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/50 space-y-2 shadow-2xs">';
                $html .= '<div class="flex items-center gap-2">';
                $html .= '<span class="w-5 h-5 rounded-md bg-emerald-600 text-white font-bold text-xs flex items-center justify-center">R</span>';
                $html .= '<span class="font-bold text-emerald-950 text-xs uppercase tracking-wide">Hasil yang Diharapkan (Output)</span>';
                $html .= '</div>';
                $html .= '<div class="text-xs text-emerald-950 font-semibold leading-relaxed pl-7">'.htmlspecialchars(trim($m[1])).'</div>';
                $html .= '</div>';

                continue;
            }

            // Waktu
            if (preg_match('/^Waktu\s*:\s*(.+)/i', $trimmed, $m)) {
                $html .= '<div class="flex justify-end pt-1">';
                $html .= '<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-900 text-amber-300 font-mono text-xs font-bold shadow-2xs">';
                $html .= '<span>⏱ Alokasi Waktu: '.htmlspecialchars(trim($m[1])).'</span>';
                $html .= '</span></div>';

                continue;
            }

            // Kelompok header
            if (preg_match('/Kelompok\s*(?:Pekerjaan\s*)?\d+/i', $trimmed)) {
                $html .= '<div class="pt-4 pb-1">';
                $html .= '<h4 class="text-xs font-bold uppercase tracking-wider text-brand-700 bg-brand-50 border border-brand-200 px-3.5 py-1.5 rounded-lg inline-block">';
                $html .= htmlspecialchars($trimmed);
                $html .= '</h4></div>';

                continue;
            }

            $html .= '<p class="text-xs text-slate-700 leading-relaxed pl-1">'.nl2br(htmlspecialchars($trimmed)).'</p>';
        }

        $html .= '</div>';

        return $html;
    }
}
