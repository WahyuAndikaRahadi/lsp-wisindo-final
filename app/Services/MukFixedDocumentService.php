<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Schedule;
use App\Models\Scheme;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MukFixedDocumentService
{
    /**
     * List of document types handled by the fixed exporter.
     */
    public const FIXED_TYPES = [
        'cover',
        'daftar-periksa',
        'periksa',
        'mapa01',
        'mapa',
        'fr-mapa-01',
        'mapa02',
        'fr-mapa-02',
        'ia02',
        'fr-ia-02',
        'ia05a',
        'ia05',
        'fr-ia-05a',
        'ia05b',
        'fr-ia-05b',
        'ba',
        'berita-acara',
        'hadir-asesor',
        'daftar-hadir-asesor',
    ];

    /**
     * Check if document type is a fixed export document.
     */
    public function isFixedDocument(string $type): bool
    {
        $normalized = strtolower(str_replace(['_', ' ', '.'], '-', $type));

        return in_array($normalized, self::FIXED_TYPES, true);
    }

    /**
     * Resolve root base directory for fixed documents.
     */
    public function getBaseDirectory(): string
    {
        return base_path('lsp-document-fixed');
    }

    /**
     * Resolve directory path for a specific scheme inside lsp-document-fixed.
     */
    public function getSchemeDirectory(Scheme $scheme): ?string
    {
        $base = $this->getBaseDirectory();
        if (! is_dir($base)) {
            return null;
        }

        $dirs = array_filter(scandir($base), fn ($d) => is_dir("$base/$d") && str_starts_with($d, 'MUK'));

        // 1. Try matching scheme code number (e.g. SKM-01-2026 -> SKEMA 1)
        if (preg_match('/SKM-0?(\d+)/i', $scheme->code ?? '', $m)) {
            $num = $m[1];
            foreach ($dirs as $dir) {
                if (preg_match("/SKEMA\s+0?{$num}\b/i", $dir)) {
                    return "$base/$dir";
                }
            }
        }

        // 2. Try matching by keyword in scheme name
        $nameLower = strtolower($scheme->name ?? '');
        $keywords = [
            1 => ['engine junior'],
            2 => ['gasoline engine', 'maintenance gasoline'],
            3 => ['electrified', 'electrical'],
            4 => ['panel bodi'],
            5 => ['mekanik junior'],
            6 => ['sepeda motor listrik', 'motor listrik'],
            7 => ['motor injeksi', 'klaster servis sepeda motor'],
        ];

        foreach ($keywords as $num => $kwList) {
            foreach ($kwList as $kw) {
                if (str_contains($nameLower, $kw)) {
                    foreach ($dirs as $dir) {
                        if (preg_match("/SKEMA\s+0?{$num}\b/i", $dir)) {
                            return "$base/$dir";
                        }
                    }
                }
            }
        }

        // 3. Fallback to first scheme directory if available
        $first = reset($dirs);

        return $first ? "$base/$first" : null;
    }

    /**
     * Find the absolute file path for a fixed document type in the given scheme.
     */
    public function getFixedFilePath(Scheme $scheme, string $docType): ?string
    {
        $base = $this->getBaseDirectory();
        $type = strtolower(str_replace(['_', ' ', '.'], '-', $docType));

        // Root operational documents
        if (in_array($type, ['ba', 'berita-acara'], true)) {
            $rootBa = $base.'/2.2 & 2.3 Berita  Acara Pelaksanaan Uji Kompetensi dan Daftar Hadir Peserta.docx.pdf';
            if (file_exists($rootBa)) {
                return $rootBa;
            }
        }

        if (in_array($type, ['hadir-asesor', 'daftar-hadir-asesor'], true)) {
            $rootHadir = $base.'/2.4 DAFTAR HADIR ASESOR.docx.pdf';
            if (file_exists($rootHadir)) {
                return $rootHadir;
            }
        }

        $dir = $this->getSchemeDirectory($scheme);
        if (! $dir || ! is_dir($dir)) {
            return null;
        }

        $files = scandir($dir);

        foreach ($files as $file) {
            if (! str_ends_with(strtolower($file), '.pdf')) {
                continue;
            }

            $fl = strtolower($file);

            if ($type === 'cover') {
                if (str_contains($fl, 'cover')) {
                    return "$dir/$file";
                }
            } elseif (in_array($type, ['daftar-periksa', 'periksa'], true)) {
                if (str_contains($fl, 'daftar periksa') || str_contains($fl, 'daftar-periksa')) {
                    return "$dir/$file";
                }
            } elseif (in_array($type, ['mapa01', 'mapa', 'fr-mapa-01'], true)) {
                if (str_contains($fl, 'mapa.01') || str_contains($fl, 'mapa-01')) {
                    return "$dir/$file";
                }
            } elseif (in_array($type, ['mapa02', 'fr-mapa-02'], true)) {
                if (str_contains($fl, 'mapa.02') || str_contains($fl, 'mapa-02')) {
                    return "$dir/$file";
                }
            } elseif (in_array($type, ['ia02', 'fr-ia-02'], true)) {
                if (str_contains($fl, 'ia.02') || str_contains($fl, 'ia-02')) {
                    return "$dir/$file";
                }
            } elseif (in_array($type, ['ia05a', 'ia05', 'fr-ia-05a'], true)) {
                if (str_contains($fl, 'ia.05.a') || str_contains($fl, 'ia-05-a')) {
                    return "$dir/$file";
                }
            } elseif (in_array($type, ['ia05b', 'fr-ia-05b'], true)) {
                if (str_contains($fl, 'ia.05.b') || str_contains($fl, 'kunci jawaban')) {
                    return "$dir/$file";
                }
            } elseif (in_array($type, ['ia05c', 'fr-ia-05c'], true)) {
                if (str_contains($fl, 'ia.05.c')) {
                    return "$dir/$file";
                }
            } elseif (in_array($type, ['ia01', 'fr-ia-01'], true)) {
                if (str_contains($fl, 'ia.01.ceklis') || (str_contains($fl, 'ia.01') && ! str_contains($fl, '01b') && ! str_contains($fl, 'report'))) {
                    return "$dir/$file";
                }
            } elseif (in_array($type, ['ia01b', 'fr-ia-01b'], true)) {
                if (str_contains($fl, 'ia 01 b') || str_contains($fl, 'ia.01b') || str_contains($fl, 'report sheet')) {
                    return "$dir/$file";
                }
            } elseif (in_array($type, ['ia03', 'fr-ia-03'], true)) {
                if (str_contains($fl, 'ia.03')) {
                    return "$dir/$file";
                }
            } elseif (in_array($type, ['ak01', 'fr-ak-01'], true)) {
                if (str_contains($fl, 'ak.01')) {
                    return "$dir/$file";
                }
            } elseif (in_array($type, ['ak02', 'fr-ak-02'], true)) {
                if (str_contains($fl, 'ak.02')) {
                    return "$dir/$file";
                }
            } elseif (in_array($type, ['ak03', 'fr-ak-03'], true)) {
                if (str_contains($fl, 'ak.03')) {
                    return "$dir/$file";
                }
            } elseif (in_array($type, ['ak04', 'fr-ak-04'], true)) {
                if (str_contains($fl, 'ak.04')) {
                    return "$dir/$file";
                }
            } elseif (in_array($type, ['ak07', 'fr-ak-07'], true)) {
                if (str_contains($fl, 'ak.07')) {
                    return "$dir/$file";
                }
            } elseif ($type === 'pernyataan') {
                if (str_contains($fl, 'pernyataan')) {
                    return "$dir/$file";
                }
            }
        }

        // Generic fallback for cover if scheme has no explicit cover
        if ($type === 'cover') {
            $fallback = "$base/MUK SKEMA 7 Klaster Servis Sepeda Motor Injeksi/000. PERANGKAT COVER MUK.pdf";
            if (file_exists($fallback)) {
                return $fallback;
            }
        }

        return null;
    }

    /**
     * Get list of available fixed documents and their file availability for a scheme.
     *
     * @return array<string, array{type: string, label: string, available: bool, filename: ?string, path: ?string}>
     */
    public function getAvailableFixedDocuments(Scheme $scheme): array
    {
        $all = [
            'cover' => 'Cover Perangkat MUK',
            'daftar-periksa' => 'Daftar Periksa Kelengkapan Dokumen',
            'mapa01' => 'FR.MAPA.01 Merencanakan Aktivitas dan Proses',
            'mapa02' => 'FR.MAPA.02 Peta Instrumen Asesmen',
            'ia02' => 'FR.IA.02 Tugas Praktik Demonstrasi',
            'ia05a' => 'FR.IA.05.A Lembar Soal Pilihan Ganda',
            'ia05b' => 'FR.IA.05.B Kunci Jawaban Pilihan Ganda',
            'ba' => 'Berita Acara & Daftar Hadir Peserta',
            'hadir-asesor' => 'Daftar Hadir Asesor',
        ];

        $results = [];
        foreach ($all as $type => $label) {
            $path = $this->getFixedFilePath($scheme, $type);
            $results[$type] = [
                'type' => $type,
                'label' => $label,
                'available' => $path !== null && file_exists($path),
                'filename' => $path ? basename($path) : null,
                'path' => $path,
            ];
        }

        return $results;
    }

    /**
     * Create BinaryFileResponse for streaming or downloading the PDF file.
     */
    public function createResponse(string $filePath, ?string $downloadName = null, bool $download = false): BinaryFileResponse
    {
        $filename = $downloadName ?: basename($filePath);
        $disposition = $download ? 'attachment' : 'inline';

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Handle export for a Schedule.
     */
    public function exportSchedule(Schedule $schedule, string $type, bool $download = false): BinaryFileResponse
    {
        $filePath = $this->getFixedFilePath($schedule->scheme, $type);

        if (! $filePath || ! file_exists($filePath)) {
            abort(404, "Dokumen master tetap (fixed) untuk tipe '".strtoupper($type)."' pada skema '".$schedule->scheme->name."' tidak tersedia.");
        }

        $schemeCode = preg_replace('/[^A-Za-z0-9\-]/', '', $schedule->scheme->code ?? 'SCH');
        $cleanType = strtoupper(str_replace(['_', ' '], '-', $type));
        $downloadName = "MUK-FIXED-{$cleanType}-{$schemeCode}.pdf";

        return $this->createResponse($filePath, $downloadName, $download);
    }

    /**
     * Handle export for an Assessment.
     */
    public function exportAssessment(Assessment $assessment, string $type, bool $download = false): BinaryFileResponse
    {
        $schedule = $assessment->schedule;
        $scheme = $assessment->scheme ?? $schedule->scheme;

        $filePath = $this->getFixedFilePath($scheme, $type);

        if (! $filePath || ! file_exists($filePath)) {
            abort(404, "Dokumen master tetap (fixed) untuk tipe '".strtoupper($type)."' pada skema '".$scheme->name."' tidak tersedia.");
        }

        $cleanAsesi = preg_replace('/[^A-Za-z0-9\-]/', '_', $assessment->asesi->full_name ?? 'Asesi');
        $cleanType = strtoupper(str_replace(['_', ' '], '-', $type));
        $downloadName = "MUK-FIXED-{$cleanType}-{$cleanAsesi}.pdf";

        return $this->createResponse($filePath, $downloadName, $download);
    }
}
