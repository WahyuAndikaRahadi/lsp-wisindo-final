<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentIaResponse;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MukDynamicDocumentService
{
    protected string $baseDir;

    protected string $cacheDir;

    public function __construct()
    {
        $this->baseDir = base_path('lsp-document-dynamic');
        $this->cacheDir = storage_path('app/muk-cache');

        if (! File::isDirectory($this->cacheDir)) {
            File::makeDirectory($this->cacheDir, 0755, true, true);
        }
    }

    /**
     * Map Scheme to its folder in /lsp-document-dynamic.
     */
    public function getSchemeDir(Scheme $scheme): ?string
    {
        $schemeId = $scheme->id;
        $name = strtolower($scheme->name ?? '');
        $code = strtolower($scheme->code ?? '');

        $schemeMap = [
            1 => 'MUK SKEMA 1 Engine Junior Technician',
            2 => 'MUK SKEMA 2 Klaster Maintenance Gasoline Engine Injection',
            3 => 'MUK SKEMA 3 Maintenence Electrical Vehicle',
            4 => 'MUK SKEMA 4 Memperbaiki Panel Bodi Kendaraan',
            5 => 'MUK SKEMA 5 Mekanik Junior pdf',
            6 => 'MUK SKEMA 6 Servis Sepeda Motor Listrik.pdf',
            7 => 'MUK SKEMA 7 Klaster Servis Sepeda Motor Injeksi',
        ];

        if (isset($schemeMap[$schemeId])) {
            $path = $this->baseDir.'/'.$schemeMap[$schemeId];
            if (File::isDirectory($path)) {
                return $path;
            }
        }

        if (preg_match('/(?:skm|skema)[-_ ]?0?([1-7])/i', $code, $m)) {
            $num = (int) $m[1];
            if (isset($schemeMap[$num])) {
                $path = $this->baseDir.'/'.$schemeMap[$num];
                if (File::isDirectory($path)) {
                    return $path;
                }
            }
        }

        $directories = File::directories($this->baseDir);
        foreach ($directories as $dir) {
            $dirName = basename($dir);
            if (str_contains(strtolower($dirName), $name) || (strlen($code) > 3 && str_contains(strtolower($dirName), $code))) {
                return $dir;
            }
        }

        return null;
    }

    /**
     * Find matching dynamic template PDF inside scheme folder or root.
     */
    public function getDynamicFilePath(Scheme $scheme, string $docType): ?string
    {
        $normType = strtolower(str_replace(['_', ' ', '-', '.'], '', $docType));

        // Shared operational files
        if (in_array($normType, ['ba', 'beritaacara', 'beritaacaradanpeserta'], true)) {
            $baCandidates = [
                base_path('lsp-document-fixed/2.2 & 2.3 Berita  Acara Pelaksanaan Uji Kompetensi dan Daftar Hadir Peserta.docx.pdf'),
                base_path('lsp-document-fixed/2.2 & 2.3 Berita Acara Pelaksanaan Uji Kompetensi dan Daftar Hadir Peserta.docx.pdf'),
                $this->baseDir.'/2.2 & 2.3 Berita  Acara Pelaksanaan Uji Kompetensi dan Daftar Hadir Peserta.docx.pdf',
            ];
            foreach ($baCandidates as $cand) {
                if (File::isFile($cand)) {
                    return $cand;
                }
            }
        }

        if (in_array($normType, ['hadirasesor', 'daftarhadirasesor'], true)) {
            $asesorCandidates = [
                base_path('lsp-document-fixed/2.4 DAFTAR HADIR ASESOR.docx.pdf'),
                $this->baseDir.'/2.4 DAFTAR HADIR ASESOR.docx.pdf',
            ];
            foreach ($asesorCandidates as $cand) {
                if (File::isFile($cand)) {
                    return $cand;
                }
            }
        }

        if ($normType === 'pernyataan') {
            $pernyataanCandidates = [
                base_path('lsp-document-fixed/15 Surat Pernyataan Pemegang Sertifikat.pdf'),
                $this->baseDir.'/MUK SKEMA 7 Klaster Servis Sepeda Motor Injeksi/15 Surat Pernyataan Pemegang Sertifikat.pdf',
            ];
            foreach ($pernyataanCandidates as $cand) {
                if (File::isFile($cand)) {
                    return $cand;
                }
            }
        }

        $schemeDir = $this->getSchemeDir($scheme);
        if (! $schemeDir || ! File::isDirectory($schemeDir)) {
            return null;
        }

        $files = File::files($schemeDir);

        foreach ($files as $file) {
            $name = strtolower($file->getFilename());
            if ($file->getExtension() !== 'pdf') {
                continue;
            }

            switch ($normType) {
                case 'apl01':
                case 'frapl01':
                    if (str_contains($name, 'apl.01') || str_contains($name, 'apl 01') || str_contains($name, 'apl-01')) {
                        return $file->getPathname();
                    }
                    break;
                case 'apl02':
                case 'frapl02':
                    if (str_contains($name, 'apl.02') || str_contains($name, 'apl 02') || str_contains($name, 'apl-02')) {
                        return $file->getPathname();
                    }
                    break;
                case 'ak01':
                case 'frak01':
                    if (str_contains($name, 'ak.01') || str_contains($name, 'ak 01') || str_contains($name, 'ak-01')) {
                        return $file->getPathname();
                    }
                    break;
                case 'ak02':
                case 'frak02':
                    if (str_contains($name, 'ak.02') || str_contains($name, 'ak 02') || str_contains($name, 'ak-02')) {
                        return $file->getPathname();
                    }
                    break;
                case 'ak03':
                case 'frak03':
                    if (str_contains($name, 'ak.03') || str_contains($name, 'ak 03') || str_contains($name, 'ak-03')) {
                        return $file->getPathname();
                    }
                    break;
                case 'ak04':
                case 'frak04':
                    if (str_contains($name, 'ak.04') || str_contains($name, 'ak 04') || str_contains($name, 'ak-04')) {
                        return $file->getPathname();
                    }
                    break;
                case 'ak07':
                case 'frak07':
                    if (str_contains($name, 'ak.07') || str_contains($name, 'ak 07') || str_contains($name, 'ak-07')) {
                        return $file->getPathname();
                    }
                    break;
                case 'ia01':
                case 'fria01':
                    if (str_contains($name, 'ia.01.ceklis') || (str_contains($name, 'ia.01') && ! str_contains($name, '01b') && ! str_contains($name, 'report'))) {
                        return $file->getPathname();
                    }
                    break;
                case 'ia01b':
                case 'fria01b':
                    if (str_contains($name, 'ia01b') || str_contains($name, 'ia 01 b') || str_contains($name, 'ia.01b') || str_contains($name, 'report')) {
                        return $file->getPathname();
                    }
                    break;
                case 'ia02':
                case 'fria02':
                    if (str_contains($name, 'ia.02') || str_contains($name, 'ia 02') || str_contains($name, 'ia-02')) {
                        return $file->getPathname();
                    }
                    break;
                case 'ia03':
                case 'fria03':
                    if (str_contains($name, 'ia.03') || str_contains($name, 'ia 03') || str_contains($name, 'ia-03')) {
                        return $file->getPathname();
                    }
                    break;
                case 'ia05c':
                case 'fria05c':
                    if (str_contains($name, 'ia.05.c') || str_contains($name, 'ia 05 c') || str_contains($name, 'ia-05-c')) {
                        return $file->getPathname();
                    }
                    break;
            }
        }

        return null;
    }

    /**
     * Build dynamic data payload from models.
     */
    public function buildPayload(Schedule $schedule, ?Assessment $assessment = null): array
    {
        $asesi = $assessment?->asesi;
        $profile = $asesi?->profile;

        $leadAssessor = $assessment?->assessor ?? $schedule->assessors->first();
        $date = Carbon::parse($schedule->date ?? now());

        $days = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $dayName = $days[$date->format('l')] ?? $date->format('l');
        $monthName = $months[(int) $date->format('m')] ?? $date->format('F');
        $formattedDate = $date->format('d').' '.$monthName.' '.$date->format('Y');

        $payload = [
            'skema_nama' => $schedule->scheme?->name ?? 'Skema Sertifikasi LSP PrO Indo',
            'skema_nomor' => $schedule->scheme?->code ?? 'SKM/LSP-PRO/2026',
            'tuk_nama' => $schedule->tuk_name ?? $schedule->tuk?->name ?? 'TUK Otomotif Mandiri',
            'tuk_kota' => $schedule->tuk?->address ? (explode(',', $schedule->tuk->address)[0] ?? 'Karawang') : 'Karawang',
            'tuk_alamat' => $schedule->tuk?->address ?? 'Karawang, Jawa Barat',
            'hari_tanggal' => "{$dayName}, {$formattedDate}",
            'tanggal' => $formattedDate,
            'waktu' => ($schedule->start_time ?? '08:00').' - '.($schedule->end_time ?? '12:00').' WIB',
            'asesor_nama' => $leadAssessor?->full_name ?? 'Asesor LSP PrO Indo',
            'asesor_no_reg' => $leadAssessor?->assessor_registration_number ?? $leadAssessor?->profile?->no_reg ?? 'MET.000.001234 2022',
            'tanggal_dokumen' => $formattedDate,
            'penanggungjawab_nama' => $schedule->pic_name ?? 'H. Ahmad Fauzi, S.Pd., M.M.',
            'penanggungjawab_jabatan' => $schedule->pic_position ?? 'Kepala TUK',
            'signature_penanggungjawab' => $schedule->pic_signature,
            'signature_asesor' => $leadAssessor?->profile?->signature_data,
        ];

        if ($assessment) {
            $isK = in_array($assessment->final_result, ['K', 'KOMPETEN'], true);
            $tempatLahir = $profile?->tempat_lahir ?? ($profile?->birth_place ?? 'Jakarta');
            $tglLahirRaw = $profile?->tanggal_lahir ?? $profile?->birth_date;
            $tglLahir = $tglLahirRaw ? Carbon::parse($tglLahirRaw)->format('d-m-Y') : '01-01-2000';
            $jk = strtoupper(substr((string) ($profile?->jenis_kelamin ?? $profile?->gender ?? 'L'), 0, 1));

            $payload['asesi_nama'] = $asesi?->full_name ?? 'Nama Asesi';
            $payload['asesi_nik'] = $profile?->nik ?? '-';
            $payload['asesi_ttl'] = "{$tempatLahir}, {$tglLahir}";
            $payload['asesi_gender'] = in_array($jk, ['P', 'W'], true) ? 'Perempuan' : 'Laki-laki';
            $payload['asesi_kebangsaan'] = $profile?->kebangsaan ?? ($profile?->nationality ?? 'Indonesia');
            $payload['asesi_alamat'] = $profile?->alamat_rumah ?? ($profile?->address ?? '-');
            $payload['asesi_telp'] = $asesi?->phone ?? ($profile?->no_telepon_rumah ?? ($profile?->phone_number ?? '-'));
            $payload['asesi_email'] = $asesi?->email ?? '-';
            $payload['asesi_pendidikan'] = $profile?->pendidikan_terakhir ?? ($profile?->last_education ?? 'SMK');
            $payload['asesi_institusi'] = $profile?->nama_institusi_perusahaan ?? ($profile?->company_name ?? 'SMK Negeri');
            $payload['asesi_jabatan'] = $profile?->jabatan ?? ($profile?->job_title ?? 'Pelajar');
            $payload['rekomendasi'] = $isK ? 'K' : 'BK';
            $payload['final_result'] = $isK ? 'KOMPETEN' : 'BELUM KOMPETEN';
            $payload['signature_asesi'] = $assessment->asesi_signature ?? $profile?->signature_data;
            $payload['signature_asesor'] = $assessment->assessor_signature ?? $leadAssessor?->profile?->signature_data;
        }

        // Add participants list for Berita Acara organized by assigned assessors
        $schedule->loadMissing([
            'assessors.profile',
            'assessments.asesi.profile',
            'assessments.assessor.profile',
        ]);

        $assessments = $schedule->assessments;
        $scheduleAssessors = $schedule->assessors;

        $assessorMap = [];
        foreach ($scheduleAssessors as $as) {
            $assessorMap[$as->id] = [
                'id' => $as->id,
                'nama' => $as->full_name,
                'no_met' => $as->assessor_registration_number ?? $as->profile?->no_reg ?? 'MET.000.001234 2022',
                'signature' => $as->profile?->signature_data,
                'assessments' => collect(),
            ];
        }

        $unassigned = collect();
        foreach ($assessments as $ass) {
            $assessorId = $ass->assessor_id;
            if ($assessorId && isset($assessorMap[$assessorId])) {
                $assessorMap[$assessorId]['assessments']->push($ass);
                if (empty($assessorMap[$assessorId]['signature']) && ! empty($ass->assessor_signature)) {
                    $assessorMap[$assessorId]['signature'] = $ass->assessor_signature;
                }
            } elseif ($ass->assessor) {
                $assessorId = $ass->assessor->id;
                if (! isset($assessorMap[$assessorId])) {
                    $assessorMap[$assessorId] = [
                        'id' => $ass->assessor->id,
                        'nama' => $ass->assessor->full_name,
                        'no_met' => $ass->assessor->assessor_registration_number ?? $ass->assessor->profile?->no_reg ?? 'MET.000.001234 2022',
                        'signature' => $ass->assessor->profile?->signature_data ?? $ass->assessor_signature,
                        'assessments' => collect(),
                    ];
                }
                $assessorMap[$assessorId]['assessments']->push($ass);
            } else {
                $unassigned->push($ass);
            }
        }

        if ($unassigned->isNotEmpty()) {
            if (! empty($assessorMap)) {
                $firstKey = array_key_first($assessorMap);
                $assessorMap[$firstKey]['assessments'] = $assessorMap[$firstKey]['assessments']->merge($unassigned);
            } else {
                $defaultAssessor = $schedule->assessors->first() ?? $assessment?->assessor;
                $assessorMap[0] = [
                    'id' => $defaultAssessor?->id ?? 0,
                    'nama' => $defaultAssessor?->full_name ?? 'Asesor LSP PrO Indo',
                    'no_met' => $defaultAssessor?->assessor_registration_number ?? $defaultAssessor?->profile?->no_reg ?? 'MET.000.001234 2022',
                    'signature' => $defaultAssessor?->profile?->signature_data,
                    'assessments' => $unassigned,
                ];
            }
        }

        $activeGroups = array_filter($assessorMap, fn ($grp) => $grp['assessments']->isNotEmpty());
        if (empty($activeGroups) && ! empty($assessorMap)) {
            $activeGroups = [reset($assessorMap)];
        }

        $assessorGroups = [];
        $pesertaList = [];
        $overallIdx = 0;

        foreach ($activeGroups as $grp) {
            $groupPeserta = [];
            foreach ($grp['assessments'] as $idx => $ass) {
                $overallIdx++;
                $assProfile = $ass->asesi?->profile;
                $assIsK = in_array($ass->final_result, ['K', 'KOMPETEN'], true);
                $pData = [
                    'no' => $idx + 1,
                    'global_no' => $overallIdx,
                    'nama' => $ass->asesi?->full_name ?? 'Peserta '.$overallIdx,
                    'institusi' => $assProfile?->nama_institusi_perusahaan ?? ($assProfile?->company_name ?? 'SMK Negeri'),
                    'alamat' => $assProfile?->alamat_rumah ?? ($assProfile?->address ?? 'Karawang'),
                    'pekerjaan' => $assProfile?->jabatan ?? ($assProfile?->job_title ?? 'Pelajar'),
                    'telp' => $ass->asesi?->phone ?? ($assProfile?->no_telepon_rumah ?? ($assProfile?->phone_number ?? '-')),
                    'hasil' => $assIsK ? 'K' : 'BK',
                    'rekomendasi' => $assIsK ? 'Diterbitkan Sertifikat' : 'Tidak Diterbitkan',
                    'ket' => $assIsK ? 'Lulus' : 'Belum Lulus',
                    'signature' => $ass->asesi_signature ?? $assProfile?->signature_data,
                    'assessor_id' => $grp['id'],
                    'asesor_nama' => $grp['nama'],
                    'asesor_no_reg' => $grp['no_met'],
                    'signature_asesor' => $grp['signature'],
                ];
                $groupPeserta[] = $pData;
                $pesertaList[] = $pData;
            }

            $assessorGroups[] = [
                'assessor' => [
                    'id' => $grp['id'],
                    'nama' => $grp['nama'],
                    'no_met' => $grp['no_met'],
                    'signature' => $grp['signature'],
                ],
                'peserta' => $groupPeserta,
            ];
        }

        $totalAsesi = count($pesertaList);
        $payload['peserta'] = $pesertaList;
        $payload['assessor_groups'] = $assessorGroups;
        $payload['jumlah_asesi'] = $totalAsesi.' ('.ucwords(MukExportService::terbilang($totalAsesi)).') orang';
        $payload['tanggal_uji'] = $formattedDate;

        // Add assessors list for Daftar Hadir Asesor
        $asesorList = [];
        foreach ($schedule->assessors as $idx => $as) {
            $asesorList[] = [
                'no' => $idx + 1,
                'nama' => $as->full_name,
                'no_met' => $as->assessor_registration_number ?? $as->profile?->no_reg ?? 'MET.000.001234 2022',
                'signature' => $as->profile?->signature_data,
            ];
        }
        $payload['asesor'] = $asesorList;

        return $payload;
    }

    /**
     * Build specific payload for FR.APL.01 (Permohonan Sertifikasi Kompetensi).
     */
    public function buildApl01Data(Schedule $schedule, Assessment $assessment): array
    {
        $asesi = $assessment->asesi;
        $profile = $asesi?->profile;
        $scheme = $assessment->scheme ?? $schedule->scheme;

        // Competency Units
        $units = [];
        if ($scheme) {
            foreach ($scheme->competencyUnits as $u) {
                $units[] = [$u->code, $u->title];
            }
        }
        if (empty($units)) {
            $units = [
                ['G.45OTO01.001.2', 'Melaksanakan Keselamatan dan Kesehatan Kerja'],
                ['G.45OTO01.002.2', 'Menggunakan Peralatan dan Perlengkapan Tempat Kerja'],
                ['G.45OTO01.007.2', 'Membaca Gambar Teknik'],
                ['G.45OTO01.008.2', 'Menggunakan Alat Ukur'],
                ['G.45OTO01.009.2', 'Melaksanakan Diagnosis'],
                ['G.45OTO01.010.2', 'Melakukan Perawatan Berkala Engine'],
                ['G.45OTO01.065.2', 'Memelihara Baterai'],
                ['G.45OTO01.073.2', 'Memperbaiki Kerusakan Pada Sistem Pengaman Kelistrikan'],
                ['G.45OTO01.078.2', 'Memperbaiki Sistem Manajemen Engine'],
            ];
        }

        // Standard reference
        $standarRef = $scheme?->competencyUnits->first()?->standard_reference ?? 'SKKNI Nomor 97 Tahun 2018';
        if (preg_match('/^(.*?)(Tahun\s+\d+.*)$/i', $standarRef, $sm)) {
            $standar = [trim($sm[1]), trim($sm[2])];
        } else {
            $standar = [$standarRef, ''];
        }

        // Proofs mapping
        $savedProofTypes = $assessment->proofs()->pluck('proof_type')->map(fn ($t) => strtoupper((string) $t))->toArray();
        $syaratDasar = [
            in_array('IJAZAH_SMK', $savedProofTypes, true) ? 'memenuhi' : 'tidak_ada',
            in_array('SURAT_SISWA_AKTIF', $savedProofTypes, true) ? 'memenuhi' : 'tidak_ada',
            in_array('IJAZAH_SMA_SERTIFIKAT', $savedProofTypes, true) ? 'memenuhi' : 'tidak_ada',
            in_array('PENGALAMAN_KERJA', $savedProofTypes, true) ? 'memenuhi' : 'tidak_ada',
        ];
        if (! in_array('memenuhi', $syaratDasar, true) && ($assessment->admin_status === 'APPROVED' || $assessment->apl_status === 'APPROVED')) {
            $syaratDasar[0] = 'memenuhi';
        }

        $syaratAdmin = [
            in_array('KTP', $savedProofTypes, true) ? 'memenuhi' : ($assessment->admin_status === 'APPROVED' ? 'memenuhi' : 'tidak_ada'),
            in_array('PAS_FOTO', $savedProofTypes, true) ? 'memenuhi' : ($assessment->admin_status === 'APPROVED' ? 'memenuhi' : 'tidak_ada'),
        ];

        // TTL formatting
        $tempatLahir = $profile?->tempat_lahir ?? ($profile?->birth_place ?? 'Jakarta');
        $tglLahirRaw = $profile?->tanggal_lahir ?? $profile?->birth_date;
        $tglLahir = $tglLahirRaw
            ? Carbon::parse($tglLahirRaw)->locale('id')->translatedFormat('d F Y')
            : '01 Januari 2000';
        $ttl = "{$tempatLahir}, {$tglLahir}";

        // Dates
        $tglPemohon = $assessment->registered_at
            ? Carbon::parse($assessment->registered_at)->locale('id')->translatedFormat('d F Y')
            : ($schedule->date ? Carbon::parse($schedule->date)->locale('id')->translatedFormat('d F Y') : ($assessment->created_at ? Carbon::parse($assessment->created_at)->locale('id')->translatedFormat('d F Y') : Carbon::now()->locale('id')->translatedFormat('d F Y')));

        $tglAdmin = $assessment->admin_approved_at
            ? Carbon::parse($assessment->admin_approved_at)->locale('id')->translatedFormat('d F Y')
            : ($schedule->date ? Carbon::parse($schedule->date)->locale('id')->translatedFormat('d F Y') : ($assessment->approved_at ? Carbon::parse($assessment->approved_at)->locale('id')->translatedFormat('d F Y') : $tglPemohon));

        $adminUser = $assessment->admin ?? $assessment->admin_reviewer ?? User::where('role', 'ADMIN_LSP')->first();
        $namaAdmin = $adminUser?->full_name ?? 'Admin LSP';
        $signatureAdmin = $adminUser?->profile?->signature_data;

        $isAccepted = ($assessment->admin_status === 'APPROVED' || $assessment->apl_status === 'APPROVED' || $assessment->status === 'ACTIVE' || $assessment->status === 'COMPLETED');

        // Signature data
        $signatureAsesi = $assessment->asesi_signature ?? $profile?->signature_data;

        return [
            'skema_judul' => $scheme?->name ?? 'Maintenance Gasoline Engine Injection System',
            'skema_nomor' => $scheme?->code ?? '02/LSP PrO Indo/I/2026',
            'standar' => $standar,
            'units' => $units,
            'nama_lengkap' => $asesi?->full_name ?? 'Ahmad Fauzi Ramadhan',
            'nik' => $profile?->nik ?? '-',
            'ttl' => $ttl,
            'jenis_kelamin' => ($profile?->jenis_kelamin === 'P' || strtolower((string) ($profile?->gender ?? '')) === 'p') ? 'P' : 'L',
            'kebangsaan' => $profile?->kebangsaan ?? 'Indonesia',
            'alamat_rumah' => $profile?->alamat_rumah ?? ($profile?->address ?? '-'),
            'kode_pos' => $profile?->kode_pos ?? '-',
            'telp_rumah' => $profile?->no_telepon_rumah ?? '-',
            'telp_kantor_pribadi' => $profile?->no_telepon_kantor ?? '-',
            'hp' => $asesi?->phone ?? ($profile?->phone_number ?? '-'),
            'email' => $asesi?->email ?? '-',
            'pendidikan' => $profile?->pendidikan_terakhir ?? ($profile?->last_education ?? 'SMK'),
            'instansi' => $profile?->nama_institusi_perusahaan ?? ($profile?->company_name ?? '-'),
            'jabatan' => $profile?->jabatan ?? ($profile?->job_title ?? '-'),
            'alamat_kantor' => $profile?->alamat_kantor ?? ($profile?->company_address ?? '-'),
            'kode_pos_kantor' => $profile?->kode_pos ?? '-',
            'telp_kantor' => $profile?->no_telepon_kantor ?? '-',
            'fax_kantor' => '-',
            'kontak_kantor' => ($profile?->no_telepon_kantor ?? '-').' / '.($asesi?->email ?? '-'),
            'tujuan' => [
                'sertifikasi' => ($assessment->tujuan_asesmen ?? 'Sertifikasi') === 'Sertifikasi',
                'pkt' => ($assessment->tujuan_asesmen ?? '') === 'Pengakuan Kompetensi Terkini (PKT)',
                'rpl' => ($assessment->tujuan_asesmen ?? '') === 'Rekognisi Pembelajaran Lampau (RPL)',
                'lainnya' => ($assessment->tujuan_asesmen ?? '') === 'Lainnya',
            ],
            'syarat_dasar' => $syaratDasar,
            'syarat_admin' => $syaratAdmin,
            'diterima' => $isAccepted,
            'catatan' => $assessment->admin_notes ?? ($assessment->apl_revision_notes ?? 'Berkas lengkap dan memenuhi persyaratan dasar.'),
            'tgl_pemohon' => $tglPemohon,
            'nama_admin' => $namaAdmin,
            'tgl_admin' => $tglAdmin,
            'signature_asesi' => $signatureAsesi,
            'signature_admin' => $signatureAdmin,
        ];
    }

    /**
     * Build specific payload for FR.APL.02 (Asesmen Mandiri).
     */
    public function buildApl02Data(Schedule $schedule, Assessment $assessment): array
    {
        $asesi = $assessment->asesi;
        $profile = $asesi?->profile;
        $assessor = $assessment->assessor ?? $schedule->assessors->first();
        $scheme = $schedule->scheme ?? $assessment->schedule?->scheme;

        $tgl = $assessment->registered_at
            ? Carbon::parse($assessment->registered_at)->locale('id')->translatedFormat('d F Y')
            : ($schedule->date ? Carbon::parse($schedule->date)->locale('id')->translatedFormat('d F Y') : Carbon::now()->locale('id')->translatedFormat('d F Y'));

        $isKompeten = ! in_array($assessment->final_result, ['BK', 'BELUM_KOMPETEN', 'BELUM KOMPETEN'], true);

        // Map element-level answers and proofs
        $savedApl02 = $assessment->apl02Items->keyBy('kuk_id');
        $answers = [];
        $proofs = [];

        $proofLabels = [
            'IJAZAH_SMK' => 'Ijazah SMK Otomotif',
            'SURAT_SISWA_AKTIF' => 'Surat Keterangan Siswa Aktif',
            'IJAZAH_SMA_SERTIFIKAT' => 'Ijazah SMA + Sertifikat Pelatihan',
            'PENGALAMAN_KERJA' => 'Surat Pengalaman Kerja',
            'KTP' => 'Fotokopi KTP',
            'PAS_FOTO' => 'Pas Foto 3x4',
        ];

        // Primary proof uploaded by candidate in assessment_proofs
        $candidateBasicProof = $assessment->proofs
            ->whereIn('proof_type', ['IJAZAH_SMK', 'SURAT_SISWA_AKTIF', 'IJAZAH_SMA_SERTIFIKAT', 'PENGALAMAN_KERJA'])
            ->first()?->proof_type;
        $defaultProof = $candidateBasicProof ? ($proofLabels[$candidateBasicProof] ?? 'Ijazah SMK Otomotif') : 'Ijazah SMK Otomotif';

        if ($scheme) {
            foreach ($scheme->competencyUnits as $unitIdx => $unit) {
                foreach ($unit->elements as $elemIdx => $elem) {
                    $allK = true;
                    $elemProof = null;
                    if ($savedApl02->isNotEmpty()) {
                        foreach ($elem->kuks as $kuk) {
                            $item = $savedApl02->get($kuk->id);
                            if ($item) {
                                if ($item->assessment_value === 'BK' || ($item->is_competent ?? true) === false) {
                                    $allK = false;
                                }
                                if (! empty($item->selected_proof_type) && ! $elemProof) {
                                    $elemProof = $proofLabels[$item->selected_proof_type] ?? $item->selected_proof_type;
                                }
                            }
                        }
                    }
                    $answers[] = $allK;
                    $proofs[] = $elemProof ?? $defaultProof;

                    // Unit 5 Elemen 2 (element index 1) is split across Page 2 and Page 3 in ReportLab template
                    if (($unitIdx === 4 || str_contains($unit->code, '009')) && $elemIdx === 1) {
                        $answers[] = $allK;
                        $proofs[] = $elemProof ?? $defaultProof;
                    }
                }
            }
        }

        return [
            'skema_judul' => $scheme?->name ?? 'Klaster Maintenance Gasoline Engine Injection System',
            'skema_nomor' => $scheme?->code ?? '02/LSP PrO Indo/I/2026',
            'asesi_nama' => $asesi?->full_name ?? 'Nama Asesi',
            'asesor_nama' => $assessor?->full_name ?? 'Drs. Budi Santoso, M.T.',
            'asesor_reg' => $assessor?->profile?->no_reg ?? ($assessor?->no_reg ?? 'MET.000.001234 2022'),
            'tanggal' => $tgl,
            'is_kompeten' => $isKompeten,
            'answers' => $answers,
            'proofs' => $proofs,
            'default_proof' => $defaultProof,
            'signature_asesi' => $assessment->asesi_signature ?? $profile?->signature_data,
            'signature_asesor' => $assessment->assessor_signature ?? $assessor?->profile?->signature_data,
        ];
    }

    /**
     * Build specific payload for FR.AK.01 (Persetujuan Asesmen dan Kerahasiaan).
     */
    public function buildAk01Data(Schedule $schedule, Assessment $assessment): array
    {
        $asesi = $assessment->asesi;
        $profile = $asesi?->profile;
        $assessor = $assessment->assessor ?? $schedule->assessors->first();
        $scheme = $schedule->scheme ?? $assessment->schedule?->scheme;

        $date = Carbon::parse($schedule->date ?? now());
        $days = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $dayName = $days[$date->format('l')] ?? $date->format('l');
        $monthName = $months[(int) $date->format('m')] ?? $date->format('F');
        $formattedDate = $date->format('d').' '.$monthName.' '.$date->format('Y');

        $tglTtd = $assessment->ak01_agreed_at
            ? Carbon::parse($assessment->ak01_agreed_at)->locale('id')->translatedFormat('d F Y')
            : ($schedule->date ? Carbon::parse($schedule->date)->locale('id')->translatedFormat('d F Y') : $formattedDate);

        $tglAsesor = $assessment->assessor_signed_at
            ? Carbon::parse($assessment->assessor_signed_at)->locale('id')->translatedFormat('d F Y')
            : $tglTtd;

        $tglAsesi = $assessment->asesi_signed_at
            ? Carbon::parse($assessment->asesi_signed_at)->locale('id')->translatedFormat('d F Y')
            : $tglTtd;

        $ak01Data = $assessment->ak01_data ?? $schedule->ak01_data ?? [];
        $evidence = $ak01Data['evidence'] ?? [
            'Hasil Verifikasi Portofolio',
            'Hasil Observasi Langsung',
            'Hasil Pertanyaan Lisan',
            'Hasil Pertanyaan Tertulis',
        ];
        $otherEvidence = $ak01Data['other_evidence'] ?? '';
        $tukType = $ak01Data['tuk_type'] ?? ($schedule->tuk_type ?? 'Sewaktu');

        $startTime = $schedule->start_time ? Carbon::parse($schedule->start_time)->format('H.i') : '08.00';
        $endTime = $schedule->end_time ? Carbon::parse($schedule->end_time)->format('H.i') : '12.00';

        return [
            'skema_nama' => $scheme?->name ?? 'Maintenance Gasoline Engine Injection System',
            'skema_nomor' => $scheme?->code ?? '02/LSP PrO Indo/I/2026',
            'tuk_tipe' => $tukType.'*',
            'asesor_nama' => $assessor?->full_name ?? $assessor?->name ?? 'Drs. Budi Santoso, M.T.',
            'asesi_nama' => $asesi?->full_name ?? $asesi?->name ?? 'Nama Asesi',
            'bukti_portofolio' => in_array('Hasil Verifikasi Portofolio', $evidence, true),
            'bukti_reviu' => in_array('Hasil Reviu Produk', $evidence, true),
            'bukti_observasi' => in_array('Hasil Observasi Langsung', $evidence, true),
            'bukti_terstruktur' => in_array('Hasil Kegiatan Terstruktur', $evidence, true),
            'bukti_lisan' => in_array('Hasil Pertanyaan Lisan', $evidence, true),
            'bukti_tertulis' => in_array('Hasil Pertanyaan Tertulis', $evidence, true),
            'bukti_wawancara' => in_array('Hasil Pertanyaan Wawancara', $evidence, true),
            'bukti_lainnya' => in_array('Lainnya', $evidence, true) || ! empty($otherEvidence),
            'hari_tanggal' => "{$dayName}, {$formattedDate}",
            'waktu' => "{$startTime} - {$endTime} WIB",
            'tuk_nama' => $schedule->tuk_name ?? 'TUK Otomotif Mandiri',
            'tanggal_ttd' => $tglTtd,
            'tanggal_ttd_asesor' => $tglAsesor,
            'tanggal_ttd_asesi' => $tglAsesi,
            'signature_asesi' => $assessment->asesi_signature ?? $profile?->signature_data,
            'signature_asesor' => $assessment->assessor_signature ?? $assessor?->profile?->signature_data,
        ];
    }

    /**
     * Build specific payload for FR.AK.02 (Rekaman Asesmen Kompetensi).
     */
    public function buildAk02Data(Schedule $schedule, Assessment $assessment): array
    {
        $asesi = $assessment->asesi;
        $profile = $asesi?->profile;
        $assessor = $assessment->assessor ?? $schedule->assessors->first();
        $scheme = $assessment->scheme ?? $schedule->scheme;

        $startDate = $schedule->date
            ? Carbon::parse($schedule->date)->locale('id')->translatedFormat('d F Y').' '.($schedule->start_time ? Carbon::parse($schedule->start_time)->format('H:i') : '08:30')
            : now()->locale('id')->translatedFormat('d F Y 08:30');

        $endDate = $schedule->date
            ? Carbon::parse($schedule->date)->locale('id')->translatedFormat('d F Y').' '.($schedule->end_time ? Carbon::parse($schedule->end_time)->format('H:i') : '12:00')
            : now()->locale('id')->translatedFormat('d F Y 12:00');

        $tglTtd = $assessment->final_result_at
            ? Carbon::parse($assessment->final_result_at)->locale('id')->translatedFormat('d F Y')
            : ($schedule->date ? Carbon::parse($schedule->date)->locale('id')->translatedFormat('d F Y') : Carbon::now()->locale('id')->translatedFormat('d F Y'));

        $isAk02Filled = ! empty($assessment->final_result)
            && ! in_array($assessment->final_result, ['BELUM_SELESAI', 'PENDING'], true);

        $isKompeten = $isAk02Filled
            ? in_array($assessment->final_result, ['K', 'KOMPETEN'], true)
            : null;

        $tglAsesor = ($isAk02Filled && $assessment->assessor_signed_at)
            ? Carbon::parse($assessment->assessor_signed_at)->locale('id')->translatedFormat('d F Y')
            : '';

        $tglAsesi = ($isAk02Filled && $assessment->asesi_signed_at)
            ? Carbon::parse($assessment->asesi_signed_at)->locale('id')->translatedFormat('d F Y')
            : '';

        $tukType = $schedule->ak01_data['tuk_type'] ?? ($schedule->tuk_type ?? 'Sewaktu');
        $tukName = $schedule->tuk_name;
        $tukDisplay = match (strtolower((string) $tukType)) {
            'tempat_kerja', 'tempat kerja' => 'Tempat Kerja',
            'mandiri' => 'Mandiri',
            default => 'Sewaktu',
        };
        if ($tukName && ! str_contains(strtolower($tukName), strtolower($tukDisplay))) {
            $tukDisplay = "{$tukDisplay} ({$tukName})";
        }

        // Competency Units with evidence checkmarks from ak02Items
        $units = [];
        $ak02Items = $assessment->ak02Items->keyBy('competency_unit_id');
        $compUnits = $scheme ? $scheme->competencyUnits : collect();

        foreach ($compUnits as $cu) {
            $item = $ak02Items->get($cu->id);
            $methods = ($isAk02Filled && $item) ? ($item->methods_json ?? []) : [];
            $units[] = [
                'code' => $cu->code,
                'title' => $cu->title,
                'obs' => ! empty($methods['observasi_demonstrasi']),
                'port' => ! empty($methods['portofolio']),
                'pihak3' => ! empty($methods['pihak_ketiga']),
                'lisan' => ! empty($methods['lisan']),
                'tulis' => ! empty($methods['tulis']),
                'proyek' => ! empty($methods['proyek']),
                'lain' => ! empty($methods['lainnya']),
            ];
        }

        $assessorSignature = $assessment->assessor_signature ?? $assessor?->profile?->signature_data;
        if (! $assessorSignature && $assessor?->profile?->signature_path) {
            $sigDiskPath = storage_path('app/public/'.$assessor->profile->signature_path);
            if (File::isFile($sigDiskPath)) {
                $assessorSignature = $sigDiskPath;
            }
        }

        $asesiSignature = $assessment->asesi_signature ?? $profile?->signature_data;
        if (! $asesiSignature && $profile?->signature_path) {
            $sigDiskPath = storage_path('app/public/'.$profile->signature_path);
            if (File::isFile($sigDiskPath)) {
                $asesiSignature = $sigDiskPath;
            }
        }

        return [
            'is_filled' => $isAk02Filled,
            'skema_nama' => $scheme?->name ?? 'Maintenance Gasoline Engine Injection System',
            'skema_nomor' => $scheme?->code ?? '02/LSP PrO Indo/I/2026',
            'tuk_tipe' => $tukDisplay.'*',
            'asesor_nama' => $assessor?->full_name ?? 'Drs. Budi Santoso, M.T.',
            'asesor_no_reg' => $assessor?->profile?->no_reg ?? 'MET.000.001234 2022',
            'asesi_nama' => $asesi?->full_name ?? 'Nama Asesi',
            'tanggal_mulai' => $startDate,
            'tanggal_selesai' => $endDate,
            'rekomendasi_kompeten' => $isKompeten,
            'tindak_lanjut' => $isAk02Filled ? ($assessment->follow_up_action ?? '') : '',
            'komentar_asesor' => $isAk02Filled ? ($assessment->final_notes ?? '') : '',
            'units' => $units,
            'tanggal_tanda_tangan' => $tglTtd,
            'tanggal_tanda_tangan_asesor' => $tglAsesor,
            'tanggal_tanda_tangan_asesi' => $tglAsesi,
            'signature_asesi' => $asesiSignature,
            'signature_asesor' => $assessorSignature,
        ];
    }

    /**
     * Build specific payload for FR.AK.03 (Umpan Balik dan Catatan Asesmen).
     */
    public function buildAk03Data(Schedule $schedule, Assessment $assessment): array
    {
        $asesi = $assessment->asesi;
        $profile = $asesi?->profile;
        $assessor = $assessment->assessor ?? $schedule->assessors->first();
        $scheme = $assessment->scheme ?? $schedule->scheme ?? $assessment->schedule?->scheme;

        $startDate = $schedule->date
            ? Carbon::parse($schedule->date)->locale('id')->translatedFormat('d F Y').' '.($schedule->start_time ? Carbon::parse($schedule->start_time)->format('H:i') : '08:00')
            : now()->locale('id')->translatedFormat('d F Y 08:00');

        $endDate = $schedule->date
            ? Carbon::parse($schedule->date)->locale('id')->translatedFormat('d F Y').' '.($schedule->end_time ? Carbon::parse($schedule->end_time)->format('H:i') : '16:00')
            : now()->locale('id')->translatedFormat('d F Y 16:00');

        $tukType = $schedule->ak01_data['tuk_type'] ?? ($schedule->tuk_type ?? 'Sewaktu');
        $tukName = $schedule->tuk_name;
        $tukDisplay = match (strtolower((string) $tukType)) {
            'tempat_kerja', 'tempat kerja' => 'Tempat Kerja',
            'mandiri' => 'Mandiri',
            default => 'Sewaktu',
        };
        if ($tukName && ! str_contains(strtolower($tukName), strtolower($tukDisplay))) {
            $tukDisplay = "{$tukDisplay} ({$tukName})";
        }

        if (! $assessment->relationLoaded('ak03Item')) {
            $assessment->load('ak03Item');
        }

        $ak03Item = $assessment->ak03Item;
        $isAk03Filled = $ak03Item !== null && ! empty($ak03Item->ratings_json);

        return [
            'is_filled' => $isAk03Filled,
            'skema_nama' => $scheme?->name ?? 'Maintenance Gasoline Engine Injection System',
            'skema_nomor' => $scheme?->code ?? 'SKM-02-2026',
            'tuk_tipe' => $tukDisplay.'*',
            'asesor_nama' => $assessor?->full_name ?? 'Ari Susanto',
            'asesi_nama' => $asesi?->full_name ?? 'Nama Asesi',
            'tanggal_mulai' => $startDate,
            'tanggal_selesai' => $endDate,
            'ratings' => $isAk03Filled ? ($ak03Item->ratings_json ?? []) : [],
            'general_notes' => $isAk03Filled ? ($ak03Item->general_notes ?? '') : '',
        ];
    }

    /**
     * Build specific payload for FR.AK.07 (Ceklis Penyesuaian yang Wajar dan Beralasan).
     */
    public function buildAk07Data(Schedule $schedule, Assessment $assessment): array
    {
        $asesi = $assessment->asesi;
        $profile = $asesi?->profile;
        $assessor = $assessment->assessor ?? $schedule->assessors->first();
        $scheme = $assessment->scheme ?? $schedule->scheme;

        $tgl = $assessment->registered_at
            ? Carbon::parse($assessment->registered_at)->locale('id')->translatedFormat('d F Y')
            : ($schedule->date ? Carbon::parse($schedule->date)->locale('id')->translatedFormat('d F Y') : Carbon::now()->locale('id')->translatedFormat('d F Y'));

        $tglAsesor = $assessment->assessor_signed_at
            ? Carbon::parse($assessment->assessor_signed_at)->locale('id')->translatedFormat('d F Y')
            : $tgl;

        $tglAsesi = $assessment->asesi_signed_at
            ? Carbon::parse($assessment->asesi_signed_at)->locale('id')->translatedFormat('d F Y')
            : $tgl;

        $ak07Items = $assessment->ak07Items ?? collect();
        $hasAdjustment = $ak07Items->whereNotIn('adjustment_category', ['Standar', ''])->isNotEmpty();
        $agreementItem = $ak07Items->firstWhere('adjustment_category', 'Kesepakatan Asesor-Asesi');
        $agreementNotes = $agreementItem?->adjustment_detail ?? '';

        $item1Ya = $ak07Items->contains(fn ($i) => str_contains(strtolower($i->adjustment_category ?? ''), 'bahasa') || str_contains(strtolower($i->adjustment_category ?? ''), 'literasi') || str_contains(strtolower($i->adjustment_category ?? ''), '#1'));
        $item2Ya = $ak07Items->contains(fn ($i) => str_contains(strtolower($i->adjustment_category ?? ''), 'dukungan') || str_contains(strtolower($i->adjustment_category ?? ''), 'penerjemah') || str_contains(strtolower($i->adjustment_category ?? ''), '#2'));
        $item3Ya = $ak07Items->contains(fn ($i) => str_contains(strtolower($i->adjustment_category ?? ''), 'adaptif') || str_contains(strtolower($i->adjustment_category ?? ''), '#3'));
        $item4Ya = $ak07Items->contains(fn ($i) => str_contains(strtolower($i->adjustment_category ?? ''), 'fisik') || str_contains(strtolower($i->adjustment_category ?? ''), 'kelelahan') || str_contains(strtolower($i->adjustment_category ?? ''), '#4'));
        $item5Ya = $ak07Items->contains(fn ($i) => str_contains(strtolower($i->adjustment_category ?? ''), 'braille') || str_contains(strtolower($i->adjustment_category ?? ''), '#5'));
        $item6Ya = $ak07Items->contains(fn ($i) => str_contains(strtolower($i->adjustment_category ?? ''), 'tempat fisik') || str_contains(strtolower($i->adjustment_category ?? ''), 'lingkungan') || str_contains(strtolower($i->adjustment_category ?? ''), '#6'));
        $item7Ya = $ak07Items->contains(fn ($i) => str_contains(strtolower($i->adjustment_category ?? ''), 'umur') || str_contains(strtolower($i->adjustment_category ?? ''), 'usia') || str_contains(strtolower($i->adjustment_category ?? ''), '#7'));
        $item8Ya = $ak07Items->contains(fn ($i) => str_contains(strtolower($i->adjustment_category ?? ''), 'budaya') || str_contains(strtolower($i->adjustment_category ?? ''), 'agama') || str_contains(strtolower($i->adjustment_category ?? ''), '#8'));

        $tukType = $schedule->tuk_type ?? 'Sewaktu';
        $tukDisplay = match (strtolower((string) $tukType)) {
            'tempat_kerja', 'tempat kerja' => 'Tempat Kerja',
            'mandiri' => 'Mandiri',
            default => 'Sewaktu',
        };

        $assessorSignature = $assessment->assessor_signature ?? $assessor?->profile?->signature_data;
        if (! $assessorSignature && $assessor?->profile?->signature_path) {
            $sigDiskPath = storage_path('app/public/'.$assessor->profile->signature_path);
            if (File::isFile($sigDiskPath)) {
                $assessorSignature = $sigDiskPath;
            }
        }

        $asesiSignature = $assessment->asesi_signature ?? $profile?->signature_data;
        if (! $asesiSignature && $profile?->signature_path) {
            $sigDiskPath = storage_path('app/public/'.$profile->signature_path);
            if (File::isFile($sigDiskPath)) {
                $asesiSignature = $sigDiskPath;
            }
        }

        return [
            'skema_nama' => $scheme?->name ?? 'Maintenance Gasoline Engine Injection System',
            'skema_nomor' => $scheme?->code ?? '02/LSP PrO Indo/I/2026',
            'tuk_tipe' => $tukDisplay,
            'asesor_nama' => $assessor?->full_name ?? $assessor?->name ?? 'Drs. Budi Santoso, M.T.',
            'asesi_nama' => $asesi?->full_name ?? $asesi?->name ?? 'Nama Asesi',
            'tanggal' => $tgl,
            'tanggal_ttd' => $tgl,
            'tanggal_ttd_asesor' => $tglAsesor,
            'tanggal_ttd_asesi' => $tglAsesi,
            'penyesuaian_diperlukan' => $hasAdjustment,
            'agreement_notes' => $agreementNotes,
            'agreement_acuan' => $ak07Items->firstWhere('adjustment_category', 'Acuan Pembanding')?->adjustment_detail ?? '',
            'agreement_metode' => $ak07Items->firstWhere('adjustment_category', 'Metode Asesmen')?->adjustment_detail ?? '',
            'agreement_instrumen' => $ak07Items->firstWhere('adjustment_category', 'Instrumen Asesmen')?->adjustment_detail ?? '',
            'signature_asesi' => $asesiSignature,
            'signature_asesor' => $assessorSignature,
            'item1_ya' => $item1Ya,
            'item2_ya' => $item2Ya,
            'item3_ya' => $item3Ya,
            'item4_ya' => $item4Ya,
            'item5_ya' => $item5Ya,
            'item6_ya' => $item6Ya,
            'item7_ya' => $item7Ya,
            'item8_ya' => $item8Ya,
        ];
    }

    /**
     * Build specific payload for FR.IA.01 (Ceklis Observasi Aktivitas Praktik).
     */
    public function buildIa01Data(Schedule $schedule, Assessment $assessment): array
    {
        $asesi = $assessment->asesi;
        $profile = $asesi?->profile;
        $assessor = $assessment->assessor ?? $schedule->assessors->first();
        $assessorProfile = $assessor?->profile;
        $scheme = $assessment->scheme ?? $assessment->schedule?->scheme ?? $schedule->scheme;

        $tgl = $assessment->registered_at
            ? Carbon::parse($assessment->registered_at)->locale('id')->translatedFormat('d F Y')
            : ($schedule->date ? Carbon::parse($schedule->date)->locale('id')->translatedFormat('d F Y') : Carbon::now()->locale('id')->translatedFormat('d F Y'));

        $ia01Response = $assessment->iaResponses?->firstWhere('instrument_code', 'IA01')
            ?? AssessmentIaResponse::where('assessment_id', $assessment->id)->where('instrument_code', 'IA01')->first();

        $tglAsesor = $ia01Response?->assessor_signed_at
            ? Carbon::parse($ia01Response->assessor_signed_at)->locale('id')->translatedFormat('d F Y')
            : ($assessment->assessor_signed_at ? Carbon::parse($assessment->assessor_signed_at)->locale('id')->translatedFormat('d F Y') : $tgl);

        $tglAsesi = $ia01Response?->asesi_signed_at
            ? Carbon::parse($ia01Response->asesi_signed_at)->locale('id')->translatedFormat('d F Y')
            : ($assessment->asesi_signed_at ? Carbon::parse($assessment->asesi_signed_at)->locale('id')->translatedFormat('d F Y') : $tgl);

        $assessorNotes = $ia01Response?->assessor_notes ?? $assessment->final_notes;

        $tukType = $schedule->ak01_data['tuk_type'] ?? ($schedule->tuk_type ?? 'Sewaktu');
        $tukName = $schedule->tuk_name;
        $tukDisplay = match (strtolower((string) $tukType)) {
            'tempat_kerja', 'tempat kerja' => 'Tempat Kerja',
            'mandiri' => 'Mandiri',
            default => 'Sewaktu',
        };
        if ($tukName && ! str_contains(strtolower($tukName), strtolower($tukDisplay))) {
            $tukDisplay = "{$tukDisplay} ({$tukName})";
        }

        $assessorSignature = $ia01Response?->assessor_signature ?? $assessment->assessor_signature ?? $assessorProfile?->signature_data;
        if (! $assessorSignature && $assessorProfile?->signature_path) {
            $sigDiskPath = storage_path('app/public/'.$assessorProfile->signature_path);
            if (File::isFile($sigDiskPath)) {
                $assessorSignature = $sigDiskPath;
            }
        }

        $asesiSignature = $ia01Response?->asesi_signature ?? $assessment->asesi_signature ?? $profile?->signature_data;
        if (! $asesiSignature && $profile?->signature_path) {
            $sigDiskPath = storage_path('app/public/'.$profile->signature_path);
            if (File::isFile($sigDiskPath)) {
                $asesiSignature = $sigDiskPath;
            }
        }

        // Units, Elements & KUKs mapping from Database
        $savedKuks = $ia01Response?->data_json['kuks'] ?? [];
        $isFinalKompeten = in_array(strtoupper((string) $assessment->final_result), ['K', 'KOMPETEN'], true);
        $isFinalBelumKompeten = in_array(strtoupper((string) $assessment->final_result), ['BK', 'BELUM_KOMPETEN', 'BELUM KOMPETEN'], true);

        $schemeUnits = $scheme ? $scheme->competencyUnits()
            ->with(['elements.kuks' => fn ($q) => $q->orderBy('sequence')->orderBy('id')])
            ->orderBy('sequence')
            ->orderBy('id')
            ->get() : collect();

        $unitsData = [];
        $kukResultsById = [];
        $kukResultsSeq = [];
        $unfulfilledItems = [];
        $allKuksKompeten = true;
        $hasAnyEvaluated = false;
        $globalKukIdx = 0;

        foreach ($schemeUnits as $uIdx => $u) {
            $uRow = [
                'id' => $u->id,
                'number' => $uIdx + 1,
                'code' => $u->code,
                'title' => $u->title,
                'standard' => $u->standard_reference ?? 'SKKNI No. 097 Tahun 2018',
                'elements' => [],
            ];

            foreach ($u->elements as $eIdx => $el) {
                $elRow = [
                    'id' => $el->id,
                    'number' => $eIdx + 1,
                    'title' => $el->title,
                    'kuks' => [],
                ];

                foreach ($el->kuks as $k) {
                    $kId = (string) $k->id;
                    $eval = $savedKuks[$kId] ?? $savedKuks[(string) ($globalKukIdx + 1)] ?? $savedKuks[(string) $globalKukIdx] ?? null;

                    if ($eval && isset($eval['result'])) {
                        $hasAnyEvaluated = true;
                        $res = strtoupper((string) $eval['result']);
                        $note = $eval['note'] ?? '';
                    } else {
                        $res = $isFinalBelumKompeten ? 'BK' : 'K';
                        $note = '';
                    }

                    $isK = in_array($res, ['K', 'KOMPETEN', 'YA'], true);
                    if (! $isK) {
                        $allKuksKompeten = false;
                        $unfulfilledItems[] = [
                            'unit' => $u->code.' '.$u->title,
                            'element' => $el->title,
                            'kuk' => $k->code_number.' '.$k->statement,
                        ];
                    }

                    $kukEntry = [
                        'id' => $k->id,
                        'global_index' => $globalKukIdx,
                        'code_number' => $k->code_number,
                        'statement' => $k->statement,
                        'standard' => $u->standard_reference ?? 'SKKNI No. 097 Tahun 2018',
                        'result' => $isK ? 'K' : 'BK',
                        'is_k' => $isK,
                        'note' => $note ?: '',
                        'unit_code' => $u->code,
                        'unit_title' => $u->title,
                        'element_title' => $el->title,
                    ];

                    $elRow['kuks'][] = $kukEntry;
                    $kukResultsById[$kId] = $kukEntry;
                    $kukResultsSeq[$globalKukIdx] = $kukEntry;
                    $globalKukIdx++;
                }

                $uRow['elements'][] = $elRow;
            }

            $unitsData[] = $uRow;
        }

        $overallKompeten = $hasAnyEvaluated ? $allKuksKompeten : (! $isFinalBelumKompeten);

        return [
            'skema_nama' => $scheme?->name ?? 'Maintenance Gasoline Engine Injection System',
            'skema_nomor' => $scheme?->code ?? '02/LSP PrO Indo/I/2026',
            'tuk_nama' => $schedule->tuk_name ?? 'TUK Otomotif Mandiri',
            'tuk_tipe' => $tukDisplay.'*',
            'asesor_nama' => $assessor?->full_name ?? $assessor?->name ?? 'Drs. Budi Santoso, M.T.',
            'asesor_no_reg' => $assessor?->assessor_registration_number ?? $assessorProfile?->no_reg_asesor ?? $assessorProfile?->no_reg ?? $assessorProfile?->met_number ?? 'MET.000.001234 2022',
            'asesi_nama' => $asesi?->full_name ?? $asesi?->name ?? 'Nama Asesi',
            'asesi_nik' => $profile?->nik ?? '-',
            'tanggal' => $tgl,
            'tanggal_ttd' => $tgl,
            'tanggal_ttd_asesor' => $tglAsesor,
            'tanggal_ttd_asesi' => $tglAsesi,
            'is_kompeten' => $overallKompeten,
            'umpan_balik' => $assessorNotes ?: 'Asesi telah menunjukkan pemenuhan unjuk kerja praktik sesuai standar KUK SKKNI dan SOP industri yang berlaku.',
            'signature_asesi' => $asesiSignature,
            'signature_asesor' => $assessorSignature,
            'units' => $unitsData,
            'kuk_results_by_id' => $kukResultsById,
            'kuk_results_seq' => $kukResultsSeq,
            'unfulfilled_items' => $unfulfilledItems,
        ];
    }

    /**
     * Build specific payload for FR.IA.03 (Pertanyaan Mendukung Observasi).
     */
    public function buildIa02Data(Schedule $schedule, Assessment $assessment): array
    {
        $asesi = $assessment->asesi;
        $profile = $asesi?->profile;
        $assessor = $assessment->assessor ?? $schedule->assessors->first();
        $scheme = $assessment->scheme ?? $schedule->scheme;

        $tgl = $assessment->registered_at
            ? Carbon::parse($assessment->registered_at)->locale('id')->translatedFormat('d F Y')
            : ($schedule->date ? Carbon::parse($schedule->date)->locale('id')->translatedFormat('d F Y') : Carbon::now()->locale('id')->translatedFormat('d F Y'));

        $tukType = $schedule->tuk_type ?? 'Sewaktu';
        $tukDisplay = match (strtolower((string) $tukType)) {
            'tempat_kerja', 'tempat kerja' => 'Tempat Kerja',
            'mandiri' => 'Mandiri',
            default => 'Sewaktu',
        };

        if (! $assessment->relationLoaded('iaResponses')) {
            $assessment->load('iaResponses');
        }

        $ia02Response = $assessment->iaResponses?->first(fn ($r) => strtoupper((string) $r->instrument_code) === 'IA02')
            ?? AssessmentIaResponse::where('assessment_id', $assessment->id)->where('instrument_code', 'IA02')->first();

        $tglAsesor = $ia02Response?->assessor_signed_at
            ? Carbon::parse($ia02Response->assessor_signed_at)->locale('id')->translatedFormat('d F Y')
            : ($schedule->date ? Carbon::parse($schedule->date)->locale('id')->translatedFormat('d F Y') : $tgl);

        $tglAsesi = $ia02Response?->asesi_signed_at
            ? Carbon::parse($ia02Response->asesi_signed_at)->locale('id')->translatedFormat('d F Y')
            : ($schedule->date ? Carbon::parse($schedule->date)->locale('id')->translatedFormat('d F Y') : $tgl);

        $isIa02Filled = $ia02Response !== null;

        return [
            'skema_nama' => $scheme?->name ?? 'Engine Junior Technician',
            'skema_nomor' => $scheme?->code ?? '01/LSP PrO Indo/I/2026',
            'tuk_nama' => $schedule->tuk_name,
            'tuk_tipe' => $tukDisplay.'*',
            'asesor_nama' => $assessor?->full_name ?? 'Ir. Hendra Gunawan, S.T.',
            'asesor_no_reg' => $assessor?->profile?->no_reg ?? $assessor?->profile?->no_reg_asesor ?? 'MET.000.001234 2021',
            'asesi_nama' => $asesi?->full_name ?? 'Nama Asesi',
            'asesi_nik' => $profile?->nik ?? '',
            'tanggal' => $tgl,
            'tanggal_ttd' => $tgl,
            'tanggal_ttd_asesor' => $tglAsesor,
            'tanggal_ttd_asesi' => $tglAsesi,
            'is_kompeten' => ! in_array($assessment->final_result, ['BK', 'BELUM_KOMPETEN', 'BELUM KOMPETEN'], true),
            'signature_asesi' => $ia02Response?->asesi_signature ?? $assessment->asesi_signature ?? $profile?->signature_data,
            'signature_asesor' => $ia02Response?->assessor_signature ?? $assessment->assessor_signature ?? $assessor?->profile?->signature_data,
            'has_db_response' => $isIa02Filled,
            'is_filled' => $isIa02Filled,
        ];
    }

    public function buildIa03Data(Schedule $schedule, Assessment $assessment): array
    {
        $asesi = $assessment->asesi;
        $profile = $asesi?->profile;
        $assessor = $assessment->assessor ?? $schedule->assessors->first();
        $scheme = $assessment->scheme ?? $schedule->scheme;

        $tgl = $assessment->registered_at
            ? Carbon::parse($assessment->registered_at)->locale('id')->translatedFormat('d F Y')
            : ($schedule->date ? Carbon::parse($schedule->date)->locale('id')->translatedFormat('d F Y') : Carbon::now()->locale('id')->translatedFormat('d F Y'));

        $tukType = $schedule->tuk_type ?? 'Sewaktu';
        $tukDisplay = match (strtolower((string) $tukType)) {
            'tempat_kerja', 'tempat kerja' => 'Tempat Kerja',
            'mandiri' => 'Mandiri',
            default => 'Sewaktu',
        };

        if (! $assessment->relationLoaded('iaResponses')) {
            $assessment->load('iaResponses');
        }

        $ia03Response = $assessment->iaResponses?->first(fn ($r) => strtoupper((string) $r->instrument_code) === 'IA03')
            ?? AssessmentIaResponse::where('assessment_id', $assessment->id)->where('instrument_code', 'IA03')->first();

        $tglAsesor = $ia03Response?->assessor_signed_at
            ? Carbon::parse($ia03Response->assessor_signed_at)->locale('id')->translatedFormat('d F Y')
            : $tgl;

        $tglAsesi = $ia03Response?->asesi_signed_at
            ? Carbon::parse($ia03Response->asesi_signed_at)->locale('id')->translatedFormat('d F Y')
            : $tgl;

        // Umpan balik khusus form IA.03 (jika belum diisi asesor, default KOSONG)
        $umpanBalik = $ia03Response?->assessor_notes
            ?? $ia03Response?->data_json['notes']
            ?? '';

        $savedData = is_array($ia03Response?->data_json)
            ? $ia03Response->data_json
            : (json_decode($ia03Response?->data_json ?? '[]', true) ?: []);

        $savedAnswers = $savedData['questions']
            ?? $savedData['answers']
            ?? $savedData['items']
            ?? [];

        $pmoQuestions = $scheme ? app(SchemeInstrumentService::class)->getPmoQuestions($scheme) : [];
        $questionsList = [];

        foreach ($pmoQuestions as $idx => $pmo) {
            $num = $idx + 1;
            $saved = $savedAnswers[$num]
                ?? $savedAnswers[(string) $num]
                ?? $savedAnswers[$idx]
                ?? $savedAnswers[(string) $idx]
                ?? null;

            $result = is_array($saved) ? ($saved['result'] ?? null) : (is_string($saved) ? $saved : null);
            $response = is_array($saved) ? ($saved['response'] ?? null) : null;

            $questionsList[] = [
                'id' => $num,
                'number' => $pmo['number'] ?? (string) $num,
                'question' => $pmo['question'] ?? '',
                'expected_answer' => $pmo['expected_answer'] ?? '',
                'result' => $result, // Null jika belum dinilai asesor (jangan dicentang apapun)
                'response' => $response, // Null jika belum diisi asesor (jangan diisi apapun)
            ];
        }

        $isIa03Filled = $ia03Response !== null && (! empty($savedAnswers) || ! empty($umpanBalik));

        return [
            'skema_nama' => $scheme?->name ?? 'Maintenance Gasoline Engine Injection System',
            'skema_nomor' => $scheme?->code ?? '02/LSP PrO Indo/I/2026',
            'tuk_tipe' => $tukDisplay.'*',
            'asesor_nama' => $assessor?->full_name ?? 'Drs. Budi Santoso, M.T.',
            'asesor_no_reg' => $assessor?->profile?->no_reg ?? $assessor?->profile?->no_reg_asesor ?? 'MET.000.001234 2022',
            'asesi_nama' => $asesi?->full_name ?? 'Nama Asesi',
            'asesi_nik' => $profile?->nik ?? '',
            'tanggal' => $tgl,
            'tanggal_ttd' => $tgl,
            'tanggal_ttd_asesor' => $tglAsesor,
            'tanggal_ttd_asesi' => $tglAsesi,
            'is_kompeten' => ! in_array($assessment->final_result, ['BK', 'BELUM_KOMPETEN', 'BELUM KOMPETEN'], true),
            'umpan_balik' => $umpanBalik,
            'assessor_notes' => $umpanBalik,
            'signature_asesi' => $ia03Response?->asesi_signature ?? $assessment->asesi_signature ?? $profile?->signature_data,
            'signature_asesor' => $ia03Response?->assessor_signature ?? $assessment->assessor_signature ?? $assessor?->profile?->signature_data,
            'questions' => $questionsList,
            'questions_map' => $savedAnswers,
            'has_db_response' => $isIa03Filled,
            'is_filled' => $isIa03Filled,
        ];
    }

    /**
     * Get working python executable for current platform.
     */
    public function getPythonExecutable(): string
    {
        $venvCandidates = PHP_OS_FAMILY === 'Windows'
            ? [base_path('.venv/Scripts/python.exe'), base_path('venv/Scripts/python.exe')]
            : [base_path('.venv/bin/python'), base_path('venv/bin/python')];

        foreach ($venvCandidates as $venvPython) {
            if (File::isFile($venvPython)) {
                return $venvPython;
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $laragonPython = 'C:\\laragon\\bin\\python\\python-3.10\\python.exe';
            if (File::isFile($laragonPython)) {
                return $laragonPython;
            }

            return 'python';
        }

        return 'python3';
    }

    /**
     * Generate dynamic PDF with inserted candidate data.
     */
    public function generateDynamicPdf(string $docType, Schedule $schedule, ?Assessment $assessment = null): string
    {
        $normType = strtolower(str_replace(['_', ' ', '-', '.'], '', $docType));

        if (in_array($normType, ['ia01b', 'fria01b'], true)) {
            $targetAssessment = $assessment ?? $schedule->assessments->first();
            if ($targetAssessment) {
                return $this->getUploadedIa01bPath($targetAssessment);
            }
            abort(404, 'Berkas FR.IA.01B (Report Sheet) belum diunggah oleh asesi.');
        }

        $cacheKey = "dyn_{$schedule->id}_".($assessment?->id ?? 'sch')."_{$normType}.pdf";
        $outputPath = $this->cacheDir.'/'.$cacheKey;

        // Python executable finder
        $python = $this->getPythonExecutable();
        $pythonExec = escapeshellarg($python);

        $payload = $this->buildPayload($schedule, $assessment);
        $tempJson = $this->cacheDir.'/muk_json_'.uniqid().'.json';
        File::put($tempJson, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        try {
            // 1. Operational generators (ReportLab)
            if ($normType === 'apl01') {
                $script = base_path('tools/render_fr_apl_01.py');
                if (File::isFile($script)) {
                    $aplPayload = $assessment
                        ? $this->buildApl01Data($schedule, $assessment)
                        : $this->buildPayload($schedule, null);
                    $tempAplJson = $this->cacheDir.'/muk_apl01_'.uniqid().'.json';
                    File::put($tempAplJson, json_encode($aplPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    if (File::isFile($outputPath)) {
                        @unlink($outputPath);
                    }
                    shell_exec("{$pythonExec} ".escapeshellarg($script).' '.escapeshellarg($tempAplJson).' '.escapeshellarg($outputPath).' 2>&1');
                    @unlink($tempAplJson);
                    if (File::isFile($outputPath) && filesize($outputPath) > 1000) {
                        return $outputPath;
                    }
                }
            }

            if ($normType === 'apl02') {
                $script = base_path('tools/render_fr_apl_02.py');
                if (File::isFile($script)) {
                    $apl02Payload = $assessment
                        ? $this->buildApl02Data($schedule, $assessment)
                        : $this->buildPayload($schedule, null);
                    $tempAplJson = $this->cacheDir.'/muk_apl02_'.uniqid().'.json';
                    File::put($tempAplJson, json_encode($apl02Payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    if (File::isFile($outputPath)) {
                        @unlink($outputPath);
                    }
                    shell_exec("{$pythonExec} ".escapeshellarg($script).' '.escapeshellarg($tempAplJson).' '.escapeshellarg($outputPath).' 2>&1');
                    @unlink($tempAplJson);
                    if (File::isFile($outputPath) && filesize($outputPath) > 1000) {
                        return $outputPath;
                    }
                }
            }

            if ($normType === 'ak01') {
                $script = base_path('tools/render_fr_ak_01.py');
                if (File::isFile($script)) {
                    $ak01Payload = $assessment
                        ? $this->buildAk01Data($schedule, $assessment)
                        : $this->buildPayload($schedule, null);
                    $tempAkJson = $this->cacheDir.'/muk_ak01_'.uniqid().'.json';
                    File::put($tempAkJson, json_encode($ak01Payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    if (File::isFile($outputPath)) {
                        @unlink($outputPath);
                    }
                    shell_exec("{$pythonExec} ".escapeshellarg($script).' '.escapeshellarg($tempAkJson).' '.escapeshellarg($outputPath).' 2>&1');
                    @unlink($tempAkJson);
                    if (File::isFile($outputPath) && filesize($outputPath) > 1000) {
                        return $outputPath;
                    }
                }
            }

            if ($normType === 'ak02') {
                $script = base_path('tools/render_fr_ak_02.py');
                if (File::isFile($script)) {
                    $targetAssessment = $assessment ?? $schedule->assessments->first();
                    $ak02Payload = $targetAssessment
                        ? $this->buildAk02Data($schedule, $targetAssessment)
                        : $this->buildPayload($schedule, null);
                    $tempAk02Json = $this->cacheDir.'/muk_ak02_'.uniqid().'.json';
                    File::put($tempAk02Json, json_encode($ak02Payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    if (File::isFile($outputPath)) {
                        @unlink($outputPath);
                    }
                    $masterPdf = $this->getDynamicFilePath($schedule->scheme, 'ak02');
                    $masterArg = ($masterPdf && File::isFile($masterPdf)) ? ' '.escapeshellarg($masterPdf) : '';
                    shell_exec("{$pythonExec} ".escapeshellarg($script).' '.escapeshellarg($tempAk02Json).' '.escapeshellarg($outputPath).$masterArg.' 2>&1');
                    @unlink($tempAk02Json);
                    if (File::isFile($outputPath) && filesize($outputPath) > 1000) {
                        return $outputPath;
                    }
                }
            }

            if ($normType === 'ak03') {
                $script = base_path('tools/render_fr_ak_03.py');
                if (File::isFile($script)) {
                    $targetAssessment = $assessment ?? $schedule->assessments->first();
                    $ak03Payload = $targetAssessment
                        ? $this->buildAk03Data($schedule, $targetAssessment)
                        : $this->buildPayload($schedule, null);
                    $tempAk03Json = $this->cacheDir.'/muk_ak03_'.uniqid().'.json';
                    File::put($tempAk03Json, json_encode($ak03Payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    if (File::isFile($outputPath)) {
                        @unlink($outputPath);
                    }
                    $masterPdf = $this->getDynamicFilePath($schedule->scheme, 'ak03');
                    $masterArg = ($masterPdf && File::isFile($masterPdf)) ? ' '.escapeshellarg($masterPdf) : '';
                    shell_exec("{$pythonExec} ".escapeshellarg($script).' '.escapeshellarg($tempAk03Json).' '.escapeshellarg($outputPath).$masterArg.' 2>&1');
                    @unlink($tempAk03Json);
                    if (File::isFile($outputPath) && filesize($outputPath) > 1000) {
                        return $outputPath;
                    }
                }
            }

            if ($normType === 'ak07') {
                $script = base_path('tools/render_fr_ak_07.py');
                if (File::isFile($script)) {
                    $ak07Payload = $assessment
                        ? $this->buildAk07Data($schedule, $assessment)
                        : $this->buildPayload($schedule, null);
                    $tempAk07Json = $this->cacheDir.'/muk_ak07_'.uniqid().'.json';
                    File::put($tempAk07Json, json_encode($ak07Payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    if (File::isFile($outputPath)) {
                        @unlink($outputPath);
                    }
                    shell_exec("{$pythonExec} ".escapeshellarg($script).' '.escapeshellarg($tempAk07Json).' '.escapeshellarg($outputPath).' 2>&1');
                    @unlink($tempAk07Json);
                    if (File::isFile($outputPath) && filesize($outputPath) > 1000) {
                        return $outputPath;
                    }
                }
            }

            if ($normType === 'ia01') {
                $script = base_path('tools/render_fr_ia_01.py');
                if (File::isFile($script)) {
                    $targetAssessment = $assessment ?? $schedule->assessments->first();
                    $ia01Payload = $targetAssessment
                        ? $this->buildIa01Data($schedule, $targetAssessment)
                        : $this->buildPayload($schedule, null);
                    $tempIa01Json = $this->cacheDir.'/muk_ia01_'.uniqid().'.json';
                    File::put($tempIa01Json, json_encode($ia01Payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    if (File::isFile($outputPath)) {
                        @unlink($outputPath);
                    }
                    $masterPdf = $this->getDynamicFilePath($schedule->scheme, 'ia01');
                    $masterArg = ($masterPdf && File::isFile($masterPdf)) ? ' '.escapeshellarg($masterPdf) : '';
                    shell_exec("{$pythonExec} ".escapeshellarg($script).' '.escapeshellarg($tempIa01Json).' '.escapeshellarg($outputPath).$masterArg.' 2>&1');
                    @unlink($tempIa01Json);
                    if (File::isFile($outputPath) && filesize($outputPath) > 1000) {
                        return $outputPath;
                    }
                }
            }

            if ($normType === 'ia02') {
                $script = base_path('tools/render_fr_ia_02.py');
                if (File::isFile($script)) {
                    $targetAssessment = $assessment ?? $schedule->assessments->first();
                    $ia02Payload = $targetAssessment
                        ? $this->buildIa02Data($schedule, $targetAssessment)
                        : $this->buildPayload($schedule, null);
                    $tempIa02Json = $this->cacheDir.'/muk_ia02_'.uniqid().'.json';
                    File::put($tempIa02Json, json_encode($ia02Payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    if (File::isFile($outputPath)) {
                        @unlink($outputPath);
                    }
                    $masterPdf = $this->getDynamicFilePath($schedule->scheme, 'ia02');
                    $masterArg = ($masterPdf && File::isFile($masterPdf)) ? ' '.escapeshellarg($masterPdf) : '';
                    shell_exec("{$pythonExec} ".escapeshellarg($script).' '.escapeshellarg($tempIa02Json).' '.escapeshellarg($outputPath).$masterArg.' 2>&1');
                    @unlink($tempIa02Json);
                    if (File::isFile($outputPath) && filesize($outputPath) > 1000) {
                        return $outputPath;
                    }
                }
            }

            if ($normType === 'ia03') {
                $script = base_path('tools/render_fr_ia_03.py');
                if (File::isFile($script)) {
                    $targetAssessment = $assessment ?? $schedule->assessments->first();
                    $ia03Payload = $targetAssessment
                        ? $this->buildIa03Data($schedule, $targetAssessment)
                        : $this->buildPayload($schedule, null);
                    $tempIa03Json = $this->cacheDir.'/muk_ia03_'.uniqid().'.json';
                    File::put($tempIa03Json, json_encode($ia03Payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    if (File::isFile($outputPath)) {
                        @unlink($outputPath);
                    }
                    $masterPdf = $this->getDynamicFilePath($schedule->scheme, 'ia03');
                    $masterArg = ($masterPdf && File::isFile($masterPdf)) ? ' '.escapeshellarg($masterPdf) : '';
                    shell_exec("{$pythonExec} ".escapeshellarg($script).' '.escapeshellarg($tempIa03Json).' '.escapeshellarg($outputPath).$masterArg.' 2>&1');
                    @unlink($tempIa03Json);
                    if (File::isFile($outputPath) && filesize($outputPath) > 1000) {
                        return $outputPath;
                    }
                }
            }
            if (in_array($normType, ['ba', 'beritaacara', 'beritaacaradanpeserta', 'hadirasesi', 'daftarhadirasesi'], true)) {
                $script = base_path('tools/render_berita_acara.py');
                if (File::isFile($script)) {
                    if (File::isFile($outputPath)) {
                        @unlink($outputPath);
                    }
                    shell_exec("{$pythonExec} ".escapeshellarg($script).' '.escapeshellarg($tempJson).' '.escapeshellarg($outputPath).' 2>&1');
                    if (File::isFile($outputPath) && filesize($outputPath) > 1000) {
                        return $outputPath;
                    }
                }
            }

            if (in_array($normType, ['hadirasesor', 'daftarhadirasesor'], true)) {
                $script = base_path('tools/render_daftar_hadir_asesor.py');
                if (File::isFile($script)) {
                    if (File::isFile($outputPath)) {
                        @unlink($outputPath);
                    }
                    shell_exec("{$pythonExec} ".escapeshellarg($script).' '.escapeshellarg($tempJson).' '.escapeshellarg($outputPath).' 2>&1');
                    if (File::isFile($outputPath) && filesize($outputPath) > 1000) {
                        return $outputPath;
                    }
                }
            }

            if ($normType === 'pernyataan') {
                $script = base_path('tools/render_surat_pernyataan.py');
                if (File::isFile($script)) {
                    shell_exec("{$pythonExec} ".escapeshellarg($script).' '.escapeshellarg($tempJson).' '.escapeshellarg($outputPath));
                    if (File::isFile($outputPath) && filesize($outputPath) > 1000) {
                        return $outputPath;
                    }
                }
            }

            // 2. Scheme-specific master PDF overlay via PyMuPDF
            $masterPdf = $this->getDynamicFilePath($schedule->scheme, $normType);
            if ($masterPdf && File::isFile($masterPdf)) {
                $overlayScript = base_path('tools/render_dynamic_overlay.py');
                if (File::isFile($overlayScript)) {
                    $overlayPayload = ($normType === 'apl01' && $assessment)
                        ? $this->buildApl01Data($schedule, $assessment)
                        : $payload;
                    $tempOverlayJson = $this->cacheDir.'/muk_ov_'.uniqid().'.json';
                    File::put($tempOverlayJson, json_encode($overlayPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    if (File::isFile($outputPath)) {
                        @unlink($outputPath);
                    }
                    shell_exec("{$pythonExec} ".escapeshellarg($overlayScript).' '.escapeshellarg($masterPdf).' '.escapeshellarg($tempOverlayJson).' '.escapeshellarg($outputPath).' 2>&1');
                    @unlink($tempOverlayJson);
                    if (File::isFile($outputPath) && filesize($outputPath) > 1000) {
                        return $outputPath;
                    }
                }

                // If python overlay execution failed, return clean master copy
                return $masterPdf;
            }

            // 3. Fallback to fixture or print-example generated files
            $fixtureMap = [
                'apl01' => 'tests/Fixtures/muk/FR_APL_01_generated.pdf',
                'apl02' => 'tests/Fixtures/muk/FR_APL_02_generated.pdf',
                'ak01' => 'tests/Fixtures/muk/FR_AK_01_generated.pdf',
                'ak02' => 'tests/Fixtures/muk/FR_AK_02_generated.pdf',
                'ak03' => 'tests/Fixtures/muk/FR_AK_03_generated.pdf',
                'ak04' => 'tests/Fixtures/muk/FR_AK_04_generated.pdf',
                'ak07' => 'tests/Fixtures/muk/FR_AK_07_generated.pdf',
                'ia01' => 'tests/Fixtures/muk/FR_IA_01_generated.pdf',
                'ia03' => 'tests/Fixtures/muk/FR_IA_03_generated.pdf',
                'ia05c' => 'tests/Fixtures/muk/FR_IA_05_C_generated.pdf',
                'daftarperiksa' => 'tests/Fixtures/muk/DAFTAR_PERIKSA_generated.pdf',
            ];

            if (isset($fixtureMap[$normType]) && File::isFile(base_path($fixtureMap[$normType]))) {
                return base_path($fixtureMap[$normType]);
            }
        } finally {
            if (File::isFile($tempJson)) {
                @unlink($tempJson);
            }
        }

        abort(404, "Dokumen dinamis MUK untuk skema {$schedule->scheme?->name} ({$docType}) tidak ditemukan.");
    }

    /**
     * Get file path of FR.IA.01B uploaded by assessee, or null if not yet uploaded.
     */
    public function getUploadedIa01bPath(Assessment $assessment): ?string
    {
        $iaResponse = $assessment->iaResponses?->first(fn ($r) => strtoupper((string) $r->instrument_code) === 'IA01B')
            ?? AssessmentIaResponse::where('assessment_id', $assessment->id)
                ->whereIn('instrument_code', ['IA01B', 'ia01b'])
                ->first();

        $data = is_array($iaResponse?->data_json)
            ? $iaResponse->data_json
            : (json_decode($iaResponse?->data_json ?? '[]', true) ?: []);

        $relativePath = $data['evidence_image_path'] ?? $data['file_path'] ?? $data['uploaded_file'] ?? null;

        if (! $relativePath) {
            return null;
        }

        $fullPath = null;
        if (Storage::disk('local')->exists($relativePath)) {
            $fullPath = Storage::disk('local')->path($relativePath);
        } elseif (Storage::disk('public')->exists($relativePath)) {
            $fullPath = Storage::disk('public')->path($relativePath);
        } elseif (file_exists(storage_path('app/'.$relativePath))) {
            $fullPath = storage_path('app/'.$relativePath);
        } elseif (file_exists(storage_path('app/private/'.$relativePath))) {
            $fullPath = storage_path('app/private/'.$relativePath);
        } elseif (file_exists($relativePath)) {
            $fullPath = $relativePath;
        }

        if (! $fullPath || ! file_exists($fullPath)) {
            return null;
        }

        return $fullPath;
    }

    /**
     * Export FR.IA.01B file uploaded by assessee.
     */
    public function exportUploadedIa01b(Assessment $assessment, bool $download = false): BinaryFileResponse|View
    {
        $filePath = $this->getUploadedIa01bPath($assessment);
        if (! $filePath) {
            return view('admin.dokumen-muk.not-uploaded', [
                'assessment' => $assessment,
                'documentCode' => 'FR.IA.01B',
                'documentName' => 'FR.IA.01B (Report Sheet / Lembar Pemeriksaan Teknis)',
            ]);
        }
        $asesiName = preg_replace('/[^A-Za-z0-9_-]/', '_', $assessment->asesi?->full_name ?? 'Asesi');
        $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'pdf';
        $filename = "FR.IA.01B-REPORT_SHEET-{$asesiName}.{$ext}";

        $rawMime = @mime_content_type($filePath);
        $mimeType = (! empty($rawMime) && ! in_array($rawMime, ['application/x-empty'], true))
            ? $rawMime
            : match (strtolower($ext)) {
                'pdf' => 'application/pdf',
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => $rawMime ?: 'application/octet-stream',
            };

        $disposition = ($download ? 'attachment' : 'inline').'; filename="'.$filename.'"';

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition,
        ]);
    }

    /**
     * Export dynamic schedule document.
     */
    public function exportSchedule(Schedule $schedule, string $docType, bool $download = false): BinaryFileResponse
    {
        $filePath = $this->generateDynamicPdf($docType, $schedule);
        $filename = strtoupper(str_replace(['_', ' '], '-', $docType)).'-'.preg_replace('/[^A-Za-z0-9_-]/', '_', $schedule->name).'.pdf';

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.$filename.'"',
        ]);
    }

    /**
     * Export dynamic candidate assessment document.
     */
    public function exportAssessment(Assessment $assessment, string $docType, bool $download = false): BinaryFileResponse|View
    {
        $normType = strtolower(str_replace(['_', ' ', '-', '.'], '', $docType));
        if (in_array($normType, ['ia01b', 'fria01b'], true)) {
            return $this->exportUploadedIa01b($assessment, $download);
        }

        $schedule = $assessment->schedule;
        $asesiName = $assessment->asesi?->full_name ?? 'Asesi';
        $filePath = $this->generateDynamicPdf($docType, $schedule, $assessment);
        $filename = strtoupper(str_replace(['_', ' '], '-', $docType)).'-'.preg_replace('/[^A-Za-z0-9_-]/', '_', $asesiName).'.pdf';

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.$filename.'"',
        ]);
    }
}
