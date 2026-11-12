<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentAk02Item;
use App\Models\AssessmentAk03Item;
use App\Models\AssessmentIaResponse;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\Tuk;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FinishedJadwalWith20AssesseesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::whereIn('role', ['ADMIN_LSP', 'SUPER_ADMIN'])->first();
        if (! $admin) {
            $admin = User::create([
                'full_name' => 'Admin LSP Pro Indo',
                'email' => 'admin.lsp@proindo.id',
                'phone' => '081234567890',
                'role' => 'ADMIN_LSP',
                'status' => 'ACTIVE',
                'password' => Hash::make('@Kompeten86'),
            ]);
        }

        $scheme = Scheme::with('competencyUnits')->first();
        if (! $scheme) {
            $this->command?->warn('Scheme not found. Run SchemeSeeder first.');

            return;
        }

        $tuk = Tuk::first();
        $tukName = $tuk?->name ?? 'TUK Cikarang Mandiri';

        $assessors = User::where('role', 'ASESOR')->where('status', 'ACTIVE')->take(2)->get();
        if ($assessors->count() < 2) {
            $assessors = collect([
                User::updateOrCreate(
                    ['email' => 'asesor.randi@gmail.com'],
                    [
                        'full_name' => 'Randi Ardiansyah, S.T.',
                        'phone' => '08100000001',
                        'assessor_registration_number' => 'MET.000.002498 2013',
                        'role' => 'ASESOR',
                        'status' => 'ACTIVE',
                        'password' => Hash::make('@Kompeten86'),
                    ]
                ),
                User::updateOrCreate(
                    ['email' => 'asesor.ari@gmail.com'],
                    [
                        'full_name' => 'Ari Susanto, M.T.',
                        'phone' => '08100000002',
                        'assessor_registration_number' => 'MET.000.002499 2013',
                        'role' => 'ASESOR',
                        'status' => 'ACTIVE',
                        'password' => Hash::make('@Kompeten86'),
                    ]
                ),
            ]);
        }

        $sampleSignature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAATwAAACMCAYAAADhuBXHAAANnklEQVR4Xu3dfWwb5QHH8ee580vi+JymSe2UFtI2aZxQxl6EpjG0laFJQ2gSYsDGtGowaUzbEDCVbQiatxbYBtqqaYi98Q\/TCmIwNiGhSWgDwRiqNk0INpW8tISka0ucNgnxW2LH9zzTc\/FdLnaTuonPOfv5faSq7p1bu6301fPccy+UcNJPAAAkQBE8AJAFggcA0kDwAEAaCB4ASAPBAwBpIHgAIA0EDwCkgeABgDQQPACQBoIHANJA8ABAGggeAEgDwQMAaSB4ACANBA8ApIHgAYA0EDwAkAaCBwDSQPAAQBoIHgBIA8EDAGkgeAAgDQQPAKSB4AGANBA8AJAGggcA0kDwAEAaCB4ASAPBAwBpIHgAIA0ED9avufl7Dd6WRoUolPL8NspJYn4uwT8c\/1l+C8CGQ\/BgbczIcYUqlFqb7TjnJB4bHLA2AGwwBA8uCm1q2q\/5IiFKFWubHeOciPzRfASzPKvPxU48lN8NsKEQPChZMBLtN6atBSM6ETlGGU8tnJslU1M\/r9\/S2eNTPR6xL6svsLmzxw+Z7wXYSAgeXJAW7uqjVMxcl0JXGDlrByGkfsvuPp\/qNYaACB64CYIHK2pojfaoRFEpWSqdOC6XyGayfGb0R+a2QggeuBWCB0Vo064HNZ\/fZx\/RccK5Tpiemhh+2Nq4AgQP3ArBgyWbN+\/XvJGQfdVVjOgY1XlyYuSgtfECEDxwKwQPDL7Wjp467vWYozojdITxZGy45NCZEDxwKwQPSCDc2eehqrUoIRYkktmJOJ+ZObyWfx4ED9wKwZPZE0QJHuruU21TWF3Erm\/wELmLMGvjRULwwK0QPFm1tNyrqVuazON1nHCSYzpLT46s+5w5cb6eSlXjD86yjD43+R5OPAZXQPAkVLe14wEf9\/rM003ECmyO6Ln0xMgj5fjnCEW6BowrMTgns6HRIXI886y1E2ADIXiSqWtt7\/Nx37LjdYmFWJxMT6\/peF2h+pZdB3yeOq94zRgjickhXEsLroHgSUQLR\/sXL5iwxW5g8BD5ztqP1xXSwl0DirJ4nW2WLmTnPji+4gnKAJWG4Mngm2RP6KXuW+xTWPEjMTG07uN1y7T7v9qYao+Kl5wzEo9hdAfuguBJIBTpHrCfX7fAco5c0B8Md\/apiscY3ulcX9M5fABOQvBqXDDS2afSxQiJ2GVJLjsfc2Ca+SGhoWh3vwir+Jz48OBBsomYtwMFcAUEr8Zp4e5+JX\/uyWx2omyLE4Xsp6LoTOfJSYzuwH0QvBpnniJijLqcuvvwY4Q2Hr683\/zl7P53D5IfYnQH7oPg1Tjz+J2TiwgY3UG1QPBq2SXb7m1kjU3ir8gY54nJwfIvImB0B1UEwath9eH2Xp\/iV8VfUVw2lpocLv\/KLI7dQRVB8GqY\/SJ+zrhYky9NqY9YPEFo6BrbyuybgwdJB47dgXsheDXMHryLVcoiB0Z3UG0QvBpWGDwRsVKYJymv+ojFUUJDVy+O7oxL1I4OHiS7MLoDd0PwatiyKa2IUi79IZ8aX\/aEsUK+Lbt76lXvBR+xiNEdVCMEr4YVBm9xfMeNw3k65ZzrC3qGvT9OpvkRsYduarsv6A9o5j3yVgqeeJ\/mb9DE20qZ+gK4BYJXwwqntCsxprqiceKnfOyEhYWcnp4aWTaltcdOYFznCVwzC1UCwathpQav0OKxPjFyW36icmHsMLqDaoPgbaTbmvY2vBq5VlzrSktbT7AwwkjyG2f+TH6SeMfaWMAePGt6upnu8ys72zyqVyVc3B3POAuFckqts1YSmXTRKSmLsQto5ghQNDGRSRW9D8DNELyNsF\/Zoz3deasiboO+DsZCRHfyP\/y1\/\/3J2mhz3uCtQfHIDrGD6oTgbYBgODqgKsYFEBZxasfS0bMLWxppcaI35aZSQ8cfz++ylCN4xSM7ft4RIEA1QPAqrL6z7bu+eENYfKxYN9UZ56nrYq+RZ2dev5ivokW6B8zVVEEnbCE5MbTsITzrDV5x7DCyg+qG4FVYKNw9YBw4E6ugPMfSsbU\/FlGLdPUr5h9mjBIZT8SGrBsE2K+lNUZmysRZ\/sHME+b+1RTHDiM7qH4IXgUFIp19XvPuw4yReBme6BVs7TqgEsV4SpjAOCMJ2+pq4e3dS4keYge1CsGrIPs0NBtKTc6NjP+yHB\/f0LX7bnXG07wUNkZyfjaXPjnyKN3adJfGWrfYo1cK8\/1C8e8pPmUFoBogeBViPZzauAU6I8kyjO7sAtddeqvnWHBPYagY5SxFkymNadb0tBzms7lcZnrkYWsDQBVA8CogEIk+6KWqT3yUiFD8a8PPk8PsWNk\/+j7t49qR7TfaFzNMxaO0YoWxNBWPDjHCg+qE4FWA\/TjaAtez6dhw+Z8aZuP\/9M59\/hP+Dpp\/ILYdY5yk7jn5HOtJvWtt3Lx5f8gbDpkjUHM1llDVr\/n8PvHdRewS2UyWz4w6+t0BnITgOcw+lXXyuRLn1Re8QvvNti9RYlzLYW02proqzyXPDD3c0NrR4+FeT\/4Z3Ythy59nZz\/muMB1lo6V\/47JAJWE4DkoEGl\/wEv9fvFahGQj7yoS3BZ9gOao3351x+IMlS9NZRkn8exi7OyhFpexJSYqGGoAhyB4Trlf6Qo9Fb3NmsoqejZ9xtmpbCnqb7nkeu8boU+ZMTMZQX5u8BDZS1gg0tnrpR7r\/L2NDDVAOSF4Dqnf0tnrU81oVHgquwpxjl3IF9DI0vnKFnEOX+4jyXe8\/9U+aoX6PLeIAqhWCJ5D1ntZl1PsCyhiTss459Q4wlccQDeFGqAcEDyHuDF4WqSzV7FPVRdicTI9fVj8Org12qMwxWNf3NAVZixsWBsAqhyC5xA3Bk8QK69ioSKZKX4M47LRX54II6njyfj40E+tjQBVCsFziFuDtxL7quz5cDHBbcuMJv85+ntrI0CVQfAcUk3Ba2jp7PV4Fqe6xiMXbx98njxGjjVc1X6HesrXRs2T9PIjvsRnZt\/kfzzzV3MbQLVA8BxSNcH7HL029G7Xtautymo7On9A59SGZcf3OOPpA+NPs3vmTlgbAVwOwXNItQRPC3cNKPlL0C60KtuwNdqrMkU1wydGe8kDY0cQPagWCJ5DqiF49u9Y6gnGysGWPQ2\/arlVIWYkET2oHgieQ1wfvCu37wvFtA5rKsv0XHpyuKRTUJRf1HcEH9mxb2mkt\/rIEMAtEDyHuD14WqRrwLyuljHOE5OD1q3hS1EYvezu9MjcG2PP5HcDuBKC5xA3By8YifapVM1PZRmJvzJ0kFxBLnzDvAK+m7bfUH809EnxOrMndWz+lfHnzX0AboTgOcStwVN37Li7Ya7euB28OP6WVRYy8x+c+LH1xS9C3c3bb\/C\/mQ\/eNfF\/zb9w6i\/mPgA3QvAc4tbg2a+myHGdp2LDFzWVtav7yvYv+F8PXS1eZ\/bGj87\/4dTL5j4AN0LwHOLG4Nkf61jqquxq\/PdHrqr7XfMXxev526deyjwa+7e5D8CNEDyHuC14dZGOB\/3UZz1XIxWYm9LfH3vc\/L5r4X2hcVvgrm13itfpJ04\/uXDz7GlzH4AbIXgOcVXw3iI0dH13v\/nMbp3rLFmm27U33Nh2o\/g59eL4i+Y2ALdC8BxSH27v9Sl+4\/rUnK6z1NnyBGYt7FNZ41rZdU5lAaoVgueU72+6MnRk603iwntdXI2wQZEJtHYe8BDVI76HuONJvGPmbfKPCYzGQEoInoPMp35t5KjKviqb1XP63Fncrh3kheA5KBjpHlDzsZm9bvBV8gz\/e35XRdgfs8i4ThKxYUxlQWoInoOCkc4+lXqMKxp0nmPJ2EjFjuMFI9F+lar5U1BwrSuAgOA5aYOO4xUet0vXzU\/nxt5f1ykoALUAwXOYFu4eUBQqLtAniUnnV0c9O3beHZiv22zGLkf0XHpi5BHrCwFIDMFzWLA1OqAS4+wUMnvN4MvkBX40v6vs6KWXfVvLBlrNZ1PoXOfJdVw6BlBrEDyHVeI4Hm277FvafOAScYTCXJHdyJVhALdC8BxGr2r+cuhU5HLxmjGdJybLOOLqar4jNLNlhz104rIx8Ywx3JAToBiCVwHmuXDluGDfsKfl68Gp5l0KV5aFjlFGkpvOjZGhqafy7wQAGwSvAsxnvq47eCJ051p2iedJ5DtHxICOERG6s2NkGKEDWA2CVwFaONqvKIvnxMUbMxN8+L1fX8zH0h2X3Rmcr99GC0Z0xtS18ewYGUHoAEqB4FWAfeFChCqxbXKIvzX17Hk\/+vP0s4G3d+2liqqo4oJ\/TsX\/UlHoEnXp03z85JP53wUAJUDwKsSc1gr26AVa2nsV1acoXKw8LIWtkHmMLuWbO8NPnvyttQMASobgVQj9RPNt2ulw1\/KR2sqBM4n3iR\/JeozoANYLwaugwujZGWHL\/4fonHHOdJb+2Ojr5G+VveEAQC1D8CrMjJ7xsWKFlRLO9CxLn3vvoQp\/FQDpIHgAIA0EDwCkgeABgDQQPACQBoIHANJA8ABAGggeAEgDwQMAaSB4ACANBA8ApIHgAYA0EDwAkAaCBwDSQPAAQBoIHgBIA8EDAGkgeAAgDQQPAKSB4AGANBA8AJAGggcA0kDwAEAaCB4ASAPBAwBpIHgAIA0EDwCkgeABgDQQPACQBoIHANJA8ABAGggeAEjj\/2gHYN6wzxxoAAAAEGRlQkc1OUZFQzRCREREOEIyREQysX8gjAAAAABJRU5ErkJggg==';

        $scheduleDate = Carbon::now()->subDays(3)->toDateString();

        $schedule = Schedule::updateOrCreate(
            ['code' => 'SCH-FIN-20-2026'],
            [
                'name' => 'Uji Kompetensi Selesai - Gelombang 20 Asesi',
                'scheme_id' => $scheme->id,
                'tuk_id' => $tuk?->id,
                'tuk_name' => $tukName,
                'date' => $scheduleDate,
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'duration_minutes' => 480,
                'selected_instruments' => ['AK01', 'IA01', 'IA01B', 'IA02', 'IA03', 'IA05'],
                'pic_name' => 'Drs. H. Mulyadi, M.Pd.',
                'pic_position' => 'Penanggung Jawab TUK',
                'pic_signature' => $sampleSignature,
                'ak01_data' => [
                    'evidence' => ['Hasil Observasi Langsung', 'Hasil Pertanyaan Tertulis', 'Hasil Pertanyaan Lisan'],
                    'tuk_type' => 'Sewaktu',
                    'agreed_date' => $scheduleDate,
                    'agreed_time' => '08:00 - 16:00 WIB',
                    'selected_instruments' => ['AK01', 'IA01', 'IA01B', 'IA02', 'IA03', 'IA05'],
                ],
                'ak07_data' => [
                    'saved_at' => Carbon::now()->subDays(3)->toIso8601String(),
                    'assessor_id' => $assessors->first()->id,
                    'adjustment_type' => 'STANDAR',
                ],
                'ak05_report' => [
                    'aspek_positif' => 'Seluruh sarana dan prasarana bengkel TUK lengkap, alat ukur terkalibrasi, kepatuhan K3 sangat baik, dan kehadiran peserta tepat waktu.',
                    'aspek_negatif' => 'Tidak ditemukan kendala mayor selama berlangsungnya rangkaian uji asesmen.',
                    'rekomendasi' => 'Pertahankan pemeliharaan berkala peralatan uji dan program pembinaan peserta.',
                    'assessor_id' => $assessors->first()->id,
                    'submitted_at' => Carbon::now()->subDays(3)->setTime(16, 15)->toIso8601String(),
                ],
                'ak06_review' => [
                    'valid' => 'YA',
                    'valid_notes' => 'Bukti yang dikumpulkan mencerminkan seluruh KUK dalam SKKNI skema terkait.',
                    'reliabel' => 'YA',
                    'reliabel_notes' => 'Konsistensi penilaian dan kunci jawaban instrumen diterapkan seragam oleh asesor.',
                    'fleksibel' => 'YA',
                    'fleksibel_notes' => 'Akomodasi penyesuaian yang wajar (FR.AK.07) tersedia bagi peserta yang membutuhkan.',
                    'adil' => 'YA',
                    'adil_notes' => 'Semua peserta mendapatkan perlakuan setara dan objektif tanpa diskriminasi.',
                    'catatan_peninjau' => 'Pelaksanaan asesmen memenuhi standar jaminan mutu LSP dan regulasi BNSP.',
                    'reviewer_id' => $assessors->first()->id,
                    'reviewed_at' => Carbon::now()->subDays(3)->setTime(16, 30)->toIso8601String(),
                ],
                'is_closed' => true,
                'closed_at' => Carbon::now()->subDays(3)->setTime(16, 30),
                'status' => 'FINISHED',
                'created_by' => $admin->id,
            ]
        );

        $schedule->assessors()->sync($assessors->pluck('id')->toArray());

        $candidateNames = [
            'Achmad Fauzi',
            'Aisyah Putri Rahmawati',
            'Bagus Pratama Putra',
            'Bayu Setiawan',
            'Citra Lestari Dewi',
            'Danu Wicaksono',
            'Dewi Anggraini Kusuma',
            'Dimas Wahyu Saputra',
            'Eka Rahmawati',
            'Fajar Hidayatullah',
            'Gilang Ramadhan',
            'Hana Safitri',
            'Ilham Kurniawan',
            'Indah Permata Sari',
            'Joko Susilo Utomo',
            'Muhammad Rizky Ananda',
            'Nadia Nuraini',
            'Rian Kurniawan Pratama',
            'Siti Nurhaliza',
            'Wahyu Tri Handoko',
        ];

        $units = $scheme->competencyUnits;
        $passwordHash = Hash::make('@Kompeten86');

        foreach ($candidateNames as $idx => $name) {
            $num = $idx + 1;
            $paddedNum = str_pad((string) $num, 2, '0', STR_PAD_LEFT);
            $email = "asesi.fin{$paddedNum}@lsp-proindo.test";
            $nik = '321601'.str_pad((string) (20261100 + $num), 10, '0', STR_PAD_LEFT);
            $phone = '081299900'.str_pad((string) $num, 3, '0', STR_PAD_LEFT);
            $gender = in_array($num, [2, 5, 7, 9, 12, 14, 17, 19], true) ? 'P' : 'L';

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'full_name' => $name,
                    'phone' => $phone,
                    'role' => 'ASESI',
                    'status' => 'ACTIVE',
                    'password' => $passwordHash,
                ]
            );

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nik' => $nik,
                    'tempat_lahir' => 'Bekasi',
                    'tanggal_lahir' => '2004-'.str_pad((string) (($num % 12) + 1), 2, '0', STR_PAD_LEFT).'-'.str_pad((string) (($num % 25) + 1), 2, '0', STR_PAD_LEFT),
                    'jenis_kelamin' => $gender,
                    'kebangsaan' => 'Indonesia',
                    'alamat_rumah' => "Jl. Industri Cikarang Raya No. {$num}, Cikarang Barat",
                    'kode_pos' => '17530',
                    'no_telepon_rumah' => $phone,
                    'pendidikan_terakhir' => 'SMK Teknik',
                    'nama_institusi_perusahaan' => 'SMK Negeri 1 Cikarang',
                    'jabatan' => 'Siswa / Teknisi Magang',
                    'signature_data' => $sampleSignature,
                ]
            );

            $assignedAssessor = $assessors[$idx % $assessors->count()];
            $isKompeten = ($num <= 18);
            $finalResult = $isKompeten ? 'KOMPETEN' : 'BELUM_KOMPETEN';
            $followUpAction = $isKompeten
                ? 'Direkomendasikan untuk diterbitkan sertifikat kompetensi BNSP'
                : 'Perlu penguatan uji praktik ulang pada unit kompetensi inti';

            $assessment = Assessment::updateOrCreate(
                [
                    'schedule_id' => $schedule->id,
                    'asesi_id' => $user->id,
                ],
                [
                    'assessor_id' => $assignedAssessor->id,
                    'assessor_signature' => $sampleSignature,
                    'assessor_signed_at' => Carbon::parse($scheduleDate)->setTime(15, 30),
                    'asesi_signature' => $sampleSignature,
                    'asesi_signed_at' => Carbon::parse($scheduleDate)->setTime(15, 0),
                    'apl_status' => 'APPROVED',
                    'tujuan_asesmen' => 'Sertifikasi',
                    'admin_status' => 'APPROVED',
                    'admin_approved_at' => Carbon::parse($scheduleDate)->setTime(8, 30),
                    'last_active_step' => 'ruang-uji',
                    'status' => 'COMPLETED',
                    'final_result' => $finalResult,
                    'final_result_at' => Carbon::parse($scheduleDate)->setTime(15, 45),
                    'final_notes' => $isKompeten
                        ? 'Asesi berhasil mendemonstrasikan seluruh elemen kompetensi sesuai SOP.'
                        : 'Beberapa kriteria unjuk kerja belum tercapai secara konsisten.',
                    'follow_up_action' => $followUpAction,
                    'ak01_agreed_at' => Carbon::parse($scheduleDate)->setTime(8, 15),
                    'ak01_data' => [
                        'evidence' => ['Hasil Observasi Langsung', 'Hasil Pertanyaan Tertulis', 'Hasil Pertanyaan Lisan'],
                        'tuk_type' => 'Sewaktu',
                        'agreed_date' => $scheduleDate,
                        'agreed_time' => '08:00 - 16:00 WIB',
                        'selected_instruments' => ['AK01', 'IA01', 'IA01B', 'IA02', 'IA03', 'IA05'],
                    ],
                    'registered_at' => Carbon::parse($scheduleDate)->subDays(7),
                    'approved_at' => Carbon::parse($scheduleDate)->subDays(5),
                    'completed_at' => Carbon::parse($scheduleDate)->setTime(16, 0),
                ]
            );

            // Populate IA01B upload response
            AssessmentIaResponse::updateOrCreate(
                [
                    'assessment_id' => $assessment->id,
                    'instrument_code' => 'IA01B',
                ],
                [
                    'data_json' => [
                        'uploaded_file' => 'mock_ia01b_report.pdf',
                        'evidence_image_path' => 'mock_ia01b_report.pdf',
                        'notes' => 'Laporan lembar kerja demonstrasi telah diunggah dan diverifikasi asesor.',
                        'uploaded_at' => Carbon::parse($scheduleDate)->setTime(13, 0)->toIso8601String(),
                    ],
                    'score' => 90.00,
                    'assessor_notes' => 'Hasil observasi dan dokumentasi demonstrasi valid.',
                    'assessor_signature' => $sampleSignature,
                    'asesi_signature' => $sampleSignature,
                    'assessor_signed_at' => Carbon::parse($scheduleDate)->setTime(15, 30),
                    'asesi_signed_at' => Carbon::parse($scheduleDate)->setTime(15, 0),
                ]
            );

            // Populate AK02 items
            foreach ($units as $unit) {
                AssessmentAk02Item::updateOrCreate(
                    [
                        'assessment_id' => $assessment->id,
                        'competency_unit_id' => $unit->id,
                    ],
                    [
                        'methods_json' => ['observasi' => true, 'tulis' => true, 'lisan' => true],
                        'is_competent' => $isKompeten,
                    ]
                );
            }

            // Populate AK03 feedback item
            AssessmentAk03Item::updateOrCreate(
                [
                    'assessment_id' => $assessment->id,
                ],
                [
                    'ratings_json' => [
                        'q1' => 'SANGAT_SETUJU',
                        'q2' => 'SANGAT_SETUJU',
                        'q3' => 'SETUJU',
                        'q4' => 'SANGAT_SETUJU',
                        'q5' => 'SETUJU',
                    ],
                    'general_notes' => 'Proses asesmen berlangsung transparan, jelas, dan sangat profesional.',
                ]
            );
        }

        $this->command?->info("Finished schedule {$schedule->code} with 20 assessees successfully seeded.");
    }
}
