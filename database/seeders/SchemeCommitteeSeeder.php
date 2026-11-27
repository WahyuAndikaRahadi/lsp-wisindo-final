<?php

namespace Database\Seeders;

use App\Models\Scheme;
use App\Models\SchemeCommittee;
use App\Services\SignatureHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SchemeCommitteeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseDir = base_path('all-document-lsp-Wisindo/TTD Digital');

        $schemesData = [
            'SKM-CC-02' => [
                'penyusun' => [
                    ['name' => 'Panca Nugraha', 'no_met' => 'MET. 000.002973 2023', 'file' => 'Commis Chef/Tanda Tangan chef panca.jpg'],
                    ['name' => 'George Edwin A', 'no_met' => 'MET. 000.000683 2021', 'file' => 'Commis Chef/Tanda Tangan pak edwin.png'],
                ],
                'validator' => [
                    ['name' => 'Lulut Budiyono', 'no_met' => 'MET. 000.002985 2023', 'file' => 'Commis Chef/Tanda Tangan LULUT.png'],
                    ['name' => 'Hendryan Afrianto', 'no_met' => 'MET. 000.002975 2023', 'file' => 'Commis Chef/Tanda Tangan Hendryan Afrianto.png'],
                    ['name' => 'Efnu Nugroho', 'no_met' => 'MET. 000.000697 2021', 'file' => 'Commis Chef/Tanda Tangan pak Efnu.png'],
                ],
            ],
            'SKM-CDP-01' => [
                'penyusun' => [
                    ['name' => 'Efnu Nugroho', 'no_met' => 'MET. 000.000697 2021', 'file' => 'Chef De Partie/Tanda Tangan Pak Efnu.png'],
                    ['name' => 'George Edwin A', 'no_met' => 'MET. 000.000683 2021', 'file' => 'Chef De Partie/Tanda Tangan pak edwin.png'],
                ],
                'validator' => [
                    ['name' => 'Panca Nugraha', 'no_met' => 'MET. 000.002973 2023', 'file' => 'Chef De Partie/Tanda Tangan chef panca.jpg'],
                    ['name' => 'Lulut Budiyono', 'no_met' => 'MET. 000.002985 2023', 'file' => 'Chef De Partie/Tanda Tangan LULUT.png'],
                    ['name' => 'Hendryan Afrianto', 'no_met' => 'MET. 000.002975 2023', 'file' => 'Chef De Partie/Tanda Tangan Hendryan Afrianto.png'],
                ],
            ],
            'SKM-HK-04' => [
                'penyusun' => [
                    ['name' => 'Rita Irawati P', 'no_met' => 'MET. 000.000699 2021', 'file' => 'Room Attendant/Tanda Tangan bu Rita.png'],
                    ['name' => 'Dwi Agung Jayan Nurcahyo', 'no_met' => 'MET. 000.002977 2023', 'file' => 'Room Attendant/Tanda Tangan Dwi Agung Jayan.png'],
                ],
                'validator' => [
                    ['name' => 'Ahmad Rosyid Rafiudin', 'no_met' => 'MET. 000.002980 2023', 'file' => 'Room Attendant/Tanda Tangan Ahmad Rosyid.png'],
                    ['name' => 'Agus Setyawan', 'no_met' => 'MET. 000.002981 2023', 'file' => 'Room Attendant/Tanda Tangan Agus Setyawan.png'],
                    ['name' => 'Agus Budiyono', 'no_met' => 'MET. 000.002979 2023', 'file' => 'Room Attendant/Tanda Tangan Agus Budiyono.png'],
                ],
            ],
            'SKM-FO-03' => [
                'penyusun' => [
                    ['name' => 'Deni Dwi Ken Hamam Nurrosyid', 'no_met' => 'MET. 000.002969 2023', 'file' => 'Receptionist/Tanda Tangan Deni Dwi Ken Hamam N.png'],
                    ['name' => 'Silvia Herliana Pramono', 'no_met' => 'MET. 000.002970 2023', 'file' => 'Receptionist/Tanda Tangan Silvia Herliana Pramono.png'],
                ],
                'validator' => [
                    ['name' => 'Supriono', 'no_met' => 'MET. 000.002972 2023', 'file' => 'Receptionist/Tanda Tangan Supriono.png'],
                    ['name' => 'Rubi', 'no_met' => 'MET. 000.002971 2023', 'file' => 'Receptionist/Tanda Tangan Rubi.png'],
                    ['name' => 'Rita Irawati P', 'no_met' => 'MET. 000.000699 2021', 'file' => 'Receptionist/Tanda Tangan bu Rita.png'],
                ],
            ],
            'SKM-FB-05' => [
                'penyusun' => [
                    ['name' => 'Stephanus Arry Briantoro Santoso', 'no_met' => 'MET. 000.000693 2021', 'file' => 'Waiter Server/Tanda Tangan pak Arry.png'],
                    ['name' => 'Lulut Budiyono', 'no_met' => 'MET. 000.002985 2023', 'file' => 'Waiter Server/Tanda Tangan LULUT.png'],
                ],
                'validator' => [
                    ['name' => 'Agil Ardiansyah', 'no_met' => 'MET. 000.002986 2023', 'file' => 'Waiter Server/Tanda Tangan Agil Ardiansyah.png'],
                    ['name' => 'Novi Primadani', 'no_met' => 'MET. 000.002964 2023', 'file' => 'Waiter Server/Tanda Tangan Novi Primadani.png'],
                    ['name' => 'Rita Irawati P', 'no_met' => 'MET. 000.000699 2021', 'file' => 'Waiter Server/Tanda Tangan bu Rita.png'],
                    ['name' => 'Suwartono', 'no_met' => 'MET. 000.002976 2023', 'file' => 'Waiter Server/Tanda Tangan Suwartono.png'],
                ],
            ],
        ];

        foreach ($schemesData as $code => $roles) {
            $scheme = Scheme::where('code', $code)->first();
            if (! $scheme) {
                continue;
            }

            // 1. Seed Penyusun
            foreach ($roles['penyusun'] as $seq => $item) {
                $signature = $this->resolveSignatureBase64($baseDir, $item['file']);
                SchemeCommittee::updateOrCreate(
                    [
                        'scheme_id' => $scheme->id,
                        'role' => 'PENYUSUN',
                        'name' => $item['name'],
                    ],
                    [
                        'no_met' => $item['no_met'],
                        'signature' => $signature,
                        'sequence' => $seq + 1,
                    ]
                );
            }

            // 2. Seed Validator
            foreach ($roles['validator'] as $seq => $item) {
                $signature = $this->resolveSignatureBase64($baseDir, $item['file']);
                SchemeCommittee::updateOrCreate(
                    [
                        'scheme_id' => $scheme->id,
                        'role' => 'VALIDATOR',
                        'name' => $item['name'],
                    ],
                    [
                        'no_met' => $item['no_met'],
                        'signature' => $signature,
                        'sequence' => $seq + 1,
                    ]
                );
            }
        }
    }

    /**
     * Convert signature image file to trimmed base64 data URI.
     */
    private function resolveSignatureBase64(string $baseDir, string $relFile): ?string
    {
        $fullPath = $baseDir.'/'.$relFile;
        if (! File::exists($fullPath)) {
            return null;
        }

        return SignatureHelper::trimSignature($fullPath, 12);
    }
}
