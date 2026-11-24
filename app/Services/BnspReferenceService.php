<?php

namespace App\Services;

class BnspReferenceService
{
    private static ?array $data = null;

    /**
     * Load references from json cache/file.
     */
    private static function load(): array
    {
        if (self::$data === null) {
            $path = resource_path('data/bnsp_references.json');
            if (file_exists($path)) {
                self::$data = json_decode(file_get_contents($path), true) ?: [];
            } else {
                self::$data = [];
            }
        }

        return self::$data;
    }

    public static function getPendidikanList(): array
    {
        return self::load()['pendidikan'] ?? [];
    }

    public static function getProvinsiList(): array
    {
        return self::load()['provinces'] ?? [];
    }

    public static function getAllCities(): array
    {
        return self::load()['cities'] ?? [];
    }

    public static function getCitiesByProvince(?string $provinceCode = null): array
    {
        $all = self::getAllCities();
        if (! $provinceCode) {
            return $all;
        }

        return array_values(array_filter($all, fn ($c) => (string) ($c['province_code'] ?? '') === (string) $provinceCode));
    }

    public static function getPekerjaanList(): array
    {
        return self::load()['pekerjaan'] ?? [];
    }

    public static function getSumberAnggaranList(): array
    {
        return self::load()['sumber_anggaran'] ?? [];
    }

    public static function getKementerianList(): array
    {
        return self::load()['kementerian'] ?? [];
    }

    /**
     * Resolve BNSP education code from education level text or existing code.
     */
    public static function resolveEducationCode(?string $education): string
    {
        if (! $education) {
            return '3'; // Default to SMA/Sederajat / SMK
        }

        $clean = trim(strtoupper($education));
        if (is_numeric($clean) && (int) $clean >= 1 && (int) $clean <= 10) {
            return $clean;
        }

        $map = [
            'SD' => '1',
            'SMP' => '2',
            'SMA' => '3',
            'SMK' => '3',
            'SMA/SEDERAJAT' => '3',
            'SMK/SEDERAJAT' => '3',
            'D2' => '4',
            'D3' => '5',
            'D4' => '6',
            'S1' => '7',
            'S2' => '8',
            'S3' => '9',
            'D1' => '10',
        ];

        foreach ($map as $key => $code) {
            if (str_contains($clean, $key)) {
                return $code;
            }
        }

        return '3';
    }

    /**
     * Resolve BNSP occupation code from job title or code.
     */
    public static function resolveJobCode(?string $jobTitle, ?string $existingCode = null): string
    {
        if (! empty($existingCode) && is_numeric($existingCode) && (int) $existingCode >= 1 && (int) $existingCode <= 89) {
            return (string) $existingCode;
        }

        if (! $jobTitle) {
            return '3'; // Default to Pelajar/Mahasiswa
        }

        $upper = strtoupper(trim($jobTitle));

        // Exact / keyword mappings for common LSP occupations
        if (str_contains($upper, 'PELAJAR') || str_contains($upper, 'SISWA') || str_contains($upper, 'MAHASISWA')) {
            return '3'; // Pelajar/Mahasiswa
        }
        if (str_contains($upper, 'MEKANIK') || str_contains($upper, 'TEKNISI')) {
            return '35'; // Mekanik
        }
        if (str_contains($upper, 'PNS') || str_contains($upper, 'PEGAWAI NEGERI')) {
            return '5'; // PNS
        }
        if (str_contains($upper, 'BUMN') || str_contains($upper, 'BUMD')) {
            return '16'; // Karyawan BUMN
        }
        if (str_contains($upper, 'SWASTA') || str_contains($upper, 'KARYAWAN')) {
            return '15'; // Karyawan Swasta
        }
        if (str_contains($upper, 'WIRAUSAHA') || str_contains($upper, 'WIRASWASTA')) {
            return '88'; // Wiraswasta
        }
        if (str_contains($upper, 'GURU')) {
            return '65'; // Guru
        }
        if (str_contains($upper, 'DOSEN')) {
            return '64'; // Dosen
        }
        if (str_contains($upper, 'BELUM') || str_contains($upper, 'TIDAK BEKERJA')) {
            return '1'; // Belum/Tidak Bekerja
        }

        // Search through the full 89 jobs list
        foreach (self::getPekerjaanList() as $item) {
            if (strcasecmp($item['name'], $jobTitle) === 0) {
                return (string) $item['code'];
            }
        }

        return '3'; // Fallback default to Pelajar/Mahasiswa
    }

    /**
     * Resolve city and province codes with fallback based on address.
     *
     * @return array{kode_kota: string, nama_kota: string, kode_provinsi: string, nama_provinsi: string}
     */
    public static function resolveCityAndProvince(?string $cityCode, ?string $provinceCode, ?string $address = null, ?string $tukCity = null): array
    {
        $allCities = self::getAllCities();
        $provinces = self::getProvinsiList();
        $provMap = array_column($provinces, 'name', 'code');

        // 1. Direct valid city code
        if (! empty($cityCode)) {
            foreach ($allCities as $c) {
                if ((string) $c['code'] === (string) $cityCode) {
                    $pCode = (string) $c['province_code'];

                    return [
                        'kode_kota' => (string) $c['code'],
                        'nama_kota' => $c['name'],
                        'kode_provinsi' => $pCode,
                        'nama_provinsi' => $provMap[$pCode] ?? 'Jawa Barat',
                    ];
                }
            }
        }

        // 2. Scan address / TUK text for city keywords
        $combinedText = strtoupper(trim(($address ?? '').' '.($tukCity ?? '')));

        // High frequency keywords in LSP Pro Indo
        if (str_contains($combinedText, 'KALIJATI') || str_contains($combinedText, 'SUBANG')) {
            return [
                'kode_kota' => '3213',
                'nama_kota' => 'Kab. Subang',
                'kode_provinsi' => '32',
                'nama_provinsi' => 'Jawa Barat',
            ];
        }

        if (str_contains($combinedText, 'CIKARANG') || str_contains($combinedText, 'KAB. BEKASI') || str_contains($combinedText, 'KABUPATEN BEKASI')) {
            return [
                'kode_kota' => '3216',
                'nama_kota' => 'Kab. Bekasi',
                'kode_provinsi' => '32',
                'nama_provinsi' => 'Jawa Barat',
            ];
        }

        if (str_contains($combinedText, 'BEKASI')) {
            return [
                'kode_kota' => '3275',
                'nama_kota' => 'Kota Bekasi',
                'kode_provinsi' => '32',
                'nama_provinsi' => 'Jawa Barat',
            ];
        }

        if (str_contains($combinedText, 'BANDUNG')) {
            return [
                'kode_kota' => '3273',
                'nama_kota' => 'Kota Bandung',
                'kode_provinsi' => '32',
                'nama_provinsi' => 'Jawa Barat',
            ];
        }

        if (str_contains($combinedText, 'JAKARTA TIMUR')) {
            return [
                'kode_kota' => '3175',
                'nama_kota' => 'Kota Jakarta Timur',
                'kode_provinsi' => '31',
                'nama_provinsi' => 'DKI Jakarta',
            ];
        }

        if (str_contains($combinedText, 'JAKARTA')) {
            return [
                'kode_kota' => '3171',
                'nama_kota' => 'Kota Jakarta Pusat',
                'kode_provinsi' => '31',
                'nama_provinsi' => 'DKI Jakarta',
            ];
        }

        if (str_contains($combinedText, 'TANGERANG')) {
            return [
                'kode_kota' => '3671',
                'nama_kota' => 'Kota Tangerang',
                'kode_provinsi' => '36',
                'nama_provinsi' => 'Banten',
            ];
        }

        if (str_contains($combinedText, 'BOGOR')) {
            return [
                'kode_kota' => '3271',
                'nama_kota' => 'Kota Bogor',
                'kode_provinsi' => '32',
                'nama_provinsi' => 'Jawa Barat',
            ];
        }

        if (str_contains($combinedText, 'KARAWANG')) {
            return [
                'kode_kota' => '3215',
                'nama_kota' => 'Kab. Karawang',
                'kode_provinsi' => '32',
                'nama_provinsi' => 'Jawa Barat',
            ];
        }

        if (str_contains($combinedText, 'PURWAKARTA')) {
            return [
                'kode_kota' => '3214',
                'nama_kota' => 'Kab. Purwakarta',
                'kode_provinsi' => '32',
                'nama_provinsi' => 'Jawa Barat',
            ];
        }

        // 3. Fallback to Kota Bekasi (Headquarters of LSP PrO Indo)
        return [
            'kode_kota' => '3275',
            'nama_kota' => 'Kota Bekasi',
            'kode_provinsi' => '32',
            'nama_provinsi' => 'Jawa Barat',
        ];
    }
}
