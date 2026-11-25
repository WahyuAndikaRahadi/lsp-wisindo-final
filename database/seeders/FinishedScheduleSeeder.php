<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentAk02Item;
use App\Models\AssessmentAk03Item;
use App\Models\AssessmentApl02Item;
use App\Models\AssessmentAppeal;
use App\Models\AssessmentIaResponse;
use App\Models\AssessmentProof;
use App\Models\Certificate;
use App\Models\Schedule;
use App\Models\Scheme;
use App\Models\Tuk;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FinishedScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Admin exists
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

        // 2. Ensure Scheme exists with units, elements, KUKs, and question banks
        $scheme = Scheme::with(['competencyUnits.elements.kuks', 'questionBanks'])->first();
        if (! $scheme) {
            $this->command?->warn('Scheme not found. Run SchemeSeeder first.');

            return;
        }

        // 3. Ensure TUK exists
        $tuk = Tuk::first();
        if (! $tuk) {
            $tuk = Tuk::create([
                'name' => 'TUK Cikarang Mandiri',
                'address' => 'Kawasan Industri Jababeka II, Jl. Industri Selatan No. 10, Cikarang, Bekasi, Jawa Barat',
                'phone' => '021-89830001',
                'is_active' => true,
            ]);
        }
        $tukName = $tuk->name ?: 'TUK Cikarang Mandiri';
        $tukAddress = $tuk->address ?: 'Kawasan Industri Jababeka II, Jl. Industri Selatan No. 10, Cikarang, Bekasi, Jawa Barat';

        // 4. Ensure at least 2 active Asesors exist
        $sampleSignature = 'data:image/png;base64,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';

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

        $leadAssessor = $assessors->first();
        $secondAssessor = $assessors->get(1) ?? $leadAssessor;

        // Schedule dates
        $scheduleDate = Carbon::now()->subDays(3)->toDateString();
        $suratTugasDate = Carbon::parse($scheduleDate)->subDays(4)->toDateString();

        // CBT questions from scheme
        $cbtQuestions = $scheme->questionBanks->take(20);
        $cbtQuestionIds = $cbtQuestions->pluck('id')->toArray();

        // 5. Create or Update Finished Schedule
        $schedule = Schedule::updateOrCreate(
            ['code' => 'SCH-FIN-2026'],
            [
                'name' => 'Uji Kompetensi Selesai - '.$scheme->name,
                'surat_tugas_number' => '001/ST/PRO-INDO/INT/XI/2026',
                'scheme_id' => $scheme->id,
                'tuk_id' => $tuk->id,
                'tuk_name' => $tukName,
                'tuk_address' => $tukAddress,
                'date' => $scheduleDate,
                'end_date' => $scheduleDate,
                'start_time' => '08:00:00',
                'end_time' => '16:30:00',
                'duration_minutes' => 510,
                'estimated_candidates' => 20,
                'surat_tugas_date' => $suratTugasDate,
                'letter_city' => 'Bekasi',
                'director_name' => 'RIDWAN',
                'pic_name' => 'Drs. H. Mulyadi, M.Pd.',
                'pic_position' => 'Penanggung Jawab TUK',
                'pic_signature' => $sampleSignature,
                'selected_instruments' => ['AK01', 'IA01', 'IA01B', 'IA02', 'IA03', 'IA05'],
                'cbt_question_ids' => $cbtQuestionIds,
                'kode_sumber_anggaran' => '01',
                'kode_kementerian' => '01',
                'ak01_data' => [
                    'evidence' => ['Hasil Observasi Langsung', 'Hasil Pertanyaan Tertulis', 'Hasil Pertanyaan Lisan'],
                    'evidence_types' => ['L', 'T'],
                    'evidence_types_summary' => 'L dan T',
                    'tuk_type' => 'Sewaktu',
                    'agreed_date' => $scheduleDate,
                    'agreed_time' => '08:00 - 16:30 WIB',
                    'selected_instruments' => ['AK01', 'IA01', 'IA01B', 'IA02', 'IA03', 'IA05'],
                ],
                'ak07_data' => [
                    'saved_at' => Carbon::parse($scheduleDate)->setTime(8, 20)->toIso8601String(),
                    'assessor_id' => $leadAssessor->id,
                    'adjustment_type' => 'STANDAR',
                ],
                'mapa01_data' => [
                    'pendekatan_asesi' => 'Hasil pelatihan dan / atau pendidikan, dimana Kurikulum dan fasilitas praktek mampu telusur terhadap standar kompetensi',
                    'tujuan_asesmen' => 'Sertifikasi',
                    'konteks_lingkungan' => 'Tempat kerja simulasi',
                    'peluang_bukti' => 'Tersedia',
                    'hubungan_standar' => 'Bukti untuk mendukung asesmen',
                    'pelaksana_asesmen' => 'Lembaga Sertifikasi Profesi Praktisi Otomotif Indonesia',
                    'orang_relevan' => ['Master Asesor / Master Trainer / Lead Asesor Kompetensi', 'Manajer sertifikasi'],
                    'standar_tolok_ukur' => 'SKKNI Nomor 97 Tahun 2018',
                    'has_karakteristik' => false,
                    'karakteristik_detail' => '',
                    'has_kontekstualisasi' => false,
                    'kontekstualisasi_detail' => '',
                    'has_saran' => false,
                    'saran_detail' => '',
                    'has_penyesuaian' => false,
                    'penyesuaian_detail' => '',
                    'has_terintegrasi' => true,
                    'terintegrasi_detail' => 'Observasi unjuk kerja dan tanya jawab teori terintegrasi dengan SOP bengkel.',
                    'integrated_tasks' => $scheme->competencyUnits->pluck('title')->take(3)->toArray(),
                ],
                'ak04_briefed_at' => Carbon::parse($scheduleDate)->setTime(8, 10),
                'ak05_report' => [
                    'aspek_positif' => 'Seluruh sarana dan prasarana bengkel TUK lengkap, alat ukur terkalibrasi, kepatuhan K3 sangat baik, dan kehadiran peserta tepat waktu.',
                    'aspek_negatif' => 'Tidak ditemukan kendala mayor selama berlangsungnya rangkaian uji asesmen.',
                    'rekomendasi' => 'Pertahankan pemeliharaan berkala peralatan uji dan program pembinaan peserta.',
                    'assessor_id' => $leadAssessor->id,
                    'submitted_at' => Carbon::parse($scheduleDate)->setTime(16, 15)->toIso8601String(),
                    'has_penolakan' => false,
                    'penolakan_assessment_ids' => [],
                    'rejection_notes' => 'Tidak ada penolakan dari asesi atas keputusan asesmen.',
                    'by_assessor' => [
                        $leadAssessor->id => [
                            'aspek_positif' => 'Seluruh sarana dan prasarana bengkel TUK lengkap dan alat ukur terkalibrasi.',
                            'aspek_negatif' => 'Tidak ada kendala mayor selama asesmen.',
                            'rekomendasi' => 'Pertahankan pemeliharaan berkala.',
                            'has_penolakan' => false,
                            'penolakan_assessment_ids' => [],
                            'rejection_notes' => 'Tidak ada penolakan dari asesi atas keputusan asesmen.',
                            'assessor_name' => $leadAssessor->full_name,
                            'submitted_at' => Carbon::parse($scheduleDate)->setTime(16, 15)->toIso8601String(),
                        ],
                        $secondAssessor->id => [
                            'aspek_positif' => 'Asesi sangat tertib dalam penerapan K3 dan penggunaan APD.',
                            'aspek_negatif' => 'Tidak ada hambatan teknis yang signifikan.',
                            'rekomendasi' => 'Tingkatkan ketersediaan bahan praktik habis pakai.',
                            'has_penolakan' => false,
                            'penolakan_assessment_ids' => [],
                            'rejection_notes' => 'Tidak ada penolakan dari asesi atas keputusan asesmen.',
                            'assessor_name' => $secondAssessor->full_name,
                            'submitted_at' => Carbon::parse($scheduleDate)->setTime(16, 20)->toIso8601String(),
                        ],
                    ],
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
                    'reviewer_id' => $leadAssessor->id,
                    'reviewed_at' => Carbon::parse($scheduleDate)->setTime(16, 30)->toIso8601String(),
                    'by_assessor' => [
                        $leadAssessor->id => [
                            'valid' => 'YA',
                            'valid_notes' => 'Bukti yang dikumpulkan mencerminkan seluruh KUK dalam SKKNI skema terkait.',
                            'reliabel' => 'YA',
                            'reliabel_notes' => 'Konsistensi penilaian diterapkan seragam.',
                            'fleksibel' => 'YA',
                            'fleksibel_notes' => 'Akomodasi penyesuaian wajar tersedia.',
                            'adil' => 'YA',
                            'adil_notes' => 'Perlakuan setara tanpa diskriminasi.',
                            'catatan_peninjau' => 'Pelaksanaan asesmen memenuhi standar jaminan mutu LSP.',
                            'reviewer_name' => $leadAssessor->full_name,
                            'reviewed_at' => Carbon::parse($scheduleDate)->setTime(16, 30)->toIso8601String(),
                        ],
                        $secondAssessor->id => [
                            'valid' => 'YA',
                            'valid_notes' => 'Seluruh instrumen mengukur kompetensi sesuai standar.',
                            'reliabel' => 'YA',
                            'reliabel_notes' => 'Hasil penilaian konsisten antar instrumen.',
                            'fleksibel' => 'YA',
                            'fleksibel_notes' => 'Metode asesmen adaptif terhadap kondisi TUK.',
                            'adil' => 'YA',
                            'adil_notes' => 'Penilaian transparan dan objektif.',
                            'catatan_peninjau' => 'Rekomendasi hasil asesmen dapat dipertanggungjawabkan.',
                            'reviewer_name' => $secondAssessor->full_name,
                            'reviewed_at' => Carbon::parse($scheduleDate)->setTime(16, 35)->toIso8601String(),
                        ],
                    ],
                ],
                'is_closed' => true,
                'closed_at' => Carbon::parse($scheduleDate)->setTime(16, 30),
                'status' => 'FINISHED',
                'created_by' => $admin->id,
            ]
        );

        // Sync assessors to schedule
        $schedule->assessors()->sync($assessors->pluck('id')->toArray());

        // 6. Candidates (20 Assessees)
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
        $allKuks = $units->flatMap(fn ($u) => $u->elements)->flatMap(fn ($e) => $e->kuks);
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
                    'kode_provinsi' => '32',
                    'nama_provinsi' => 'JAWA BARAT',
                    'kode_kota' => '3216',
                    'nama_kota' => 'KAB. BEKASI',
                    'kode_pendidikan' => '06',
                    'pendidikan_terakhir' => 'SMK',
                    'kode_pekerjaan' => '10',
                    'jabatan' => 'Siswa / Teknisi Magang',
                    'nama_institusi_perusahaan' => 'SMK Negeri 1 Cikarang',
                    'no_telepon_rumah' => $phone,
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
                    'admin_id' => $admin->id,
                    'last_active_step' => 'ruang-uji',
                    'status' => 'COMPLETED',
                    'final_result' => $finalResult,
                    'final_result_at' => Carbon::parse($scheduleDate)->setTime(15, 45),
                    'final_notes' => $isKompeten
                        ? 'Asesi berhasil mendemonstrasikan seluruh elemen kompetensi sesuai SOP.'
                        : 'Beberapa kriteria unjuk kerja belum tercapai secara konsisten.',
                    'follow_up_action' => $followUpAction,
                    'ak01_agreed_at' => Carbon::parse($scheduleDate)->setTime(8, 15),
                    'ak01_data' => $schedule->ak01_data,
                    'mapa01_data' => $schedule->mapa01_data,
                    'ak04_briefed_at' => $schedule->ak04_briefed_at,
                    'registered_at' => Carbon::parse($scheduleDate)->subDays(7),
                    'approved_at' => Carbon::parse($scheduleDate)->subDays(5),
                    'completed_at' => Carbon::parse($scheduleDate)->setTime(16, 0),
                ]
            );

            // 7. APL-01 Supporting Proofs
            AssessmentProof::updateOrCreate(
                ['assessment_id' => $assessment->id, 'proof_type' => 'KTP'],
                ['proof_category' => 'ADMINISTRATIF', 'file_path' => 'mock_ktp.jpg', 'original_filename' => 'ktp.jpg']
            );
            AssessmentProof::updateOrCreate(
                ['assessment_id' => $assessment->id, 'proof_type' => 'PAS_FOTO'],
                ['proof_category' => 'ADMINISTRATIF', 'file_path' => 'mock_pas_foto.jpg', 'original_filename' => 'pas_foto.jpg']
            );
            AssessmentProof::updateOrCreate(
                ['assessment_id' => $assessment->id, 'proof_type' => 'IJAZAH_SMK'],
                ['proof_category' => 'PERSYARATAN_DASAR', 'file_path' => 'mock_ijazah.pdf', 'original_filename' => 'ijazah.pdf']
            );

            // 8. APL-02 Self Assessment (all KUKs)
            foreach ($allKuks as $kuk) {
                AssessmentApl02Item::updateOrCreate(
                    [
                        'assessment_id' => $assessment->id,
                        'kuk_id' => $kuk->id,
                    ],
                    [
                        'assessment_value' => 'K',
                        'selected_proof_type' => 'Bukti Langsung',
                    ]
                );
            }

            // 9. AK-02 Competency Unit verification
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

            // 10. AK-03 Feedback
            AssessmentAk03Item::updateOrCreate(
                ['assessment_id' => $assessment->id],
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

            // 11. IA-01 Observation Checklist
            $kukEvaluations = [];
            foreach ($allKuks as $kuk) {
                $kukEvaluations[(string) $kuk->id] = [
                    'result' => $isKompeten ? 'K' : ($kuk->id % 4 === 0 ? 'BK' : 'K'),
                    'note' => $isKompeten ? 'Telah memenuhi standar kriteria unjuk kerja.' : '',
                ];
            }
            AssessmentIaResponse::updateOrCreate(
                ['assessment_id' => $assessment->id, 'instrument_code' => 'IA01'],
                [
                    'data_json' => ['kuks' => $kukEvaluations],
                    'score' => $isKompeten ? 95.00 : 65.00,
                    'assessor_notes' => $isKompeten
                        ? 'Seluruh kriteria unjuk kerja terpenuhi dengan baik dan memuaskan.'
                        : 'Terdapat kriteria unjuk kerja yang belum terpenuhi secara konsisten.',
                    'assessor_signature' => $sampleSignature,
                    'asesi_signature' => $sampleSignature,
                    'assessor_signed_at' => Carbon::parse($scheduleDate)->setTime(15, 30),
                    'asesi_signed_at' => Carbon::parse($scheduleDate)->setTime(15, 0),
                ]
            );

            // 12. IA-01B Report Sheet (practical worksheet upload)
            AssessmentIaResponse::updateOrCreate(
                ['assessment_id' => $assessment->id, 'instrument_code' => 'IA01B'],
                [
                    'data_json' => [
                        'uploaded_file' => 'mock_ia01b_report.pdf',
                        'evidence_image_path' => 'mock_ia01b_report.pdf',
                        'notes' => 'Laporan lembar kerja demonstrasi telah diunggah dan diverifikasi asesor.',
                        'uploaded_at' => Carbon::parse($scheduleDate)->setTime(13, 0)->toIso8601String(),
                    ],
                    'score' => $isKompeten ? 90.00 : 60.00,
                    'assessor_notes' => 'Hasil observasi dan dokumentasi demonstrasi valid.',
                    'assessor_signature' => $sampleSignature,
                    'asesi_signature' => $sampleSignature,
                    'assessor_signed_at' => Carbon::parse($scheduleDate)->setTime(15, 30),
                    'asesi_signed_at' => Carbon::parse($scheduleDate)->setTime(15, 0),
                ]
            );

            // 13. IA-02 Practical Task
            AssessmentIaResponse::updateOrCreate(
                ['assessment_id' => $assessment->id, 'instrument_code' => 'IA02'],
                [
                    'data_json' => [
                        'audit_trail' => [
                            'ip_address' => '127.0.0.1',
                            'is_bulk_signed' => true,
                        ],
                    ],
                    'assessor_notes' => 'Tugas Praktik Demonstrasi (FR.IA.02) telah disetujui dan ditandatangani oleh Asesor.',
                    'assessor_signature' => $sampleSignature,
                    'asesi_signature' => $sampleSignature,
                    'assessor_signed_at' => Carbon::parse($scheduleDate)->setTime(15, 30),
                    'asesi_signed_at' => Carbon::parse($scheduleDate)->setTime(15, 0),
                ]
            );

            // 14. IA-03 Oral Questions
            AssessmentIaResponse::updateOrCreate(
                ['assessment_id' => $assessment->id, 'instrument_code' => 'IA03'],
                [
                    'data_json' => [
                        'notes' => 'Pertanyaan lisan telah dijawab dengan baik sesuai standar unjuk kerja.',
                    ],
                    'score' => $isKompeten ? 90.00 : 60.00,
                    'assessor_notes' => 'Peserta mampu menjawab pertanyaan pendukung observasi dengan tepat dan percaya diri.',
                    'assessor_signature' => $sampleSignature,
                    'asesi_signature' => $sampleSignature,
                    'assessor_signed_at' => Carbon::parse($scheduleDate)->setTime(15, 30),
                    'asesi_signed_at' => Carbon::parse($scheduleDate)->setTime(15, 0),
                ]
            );

            // 15. IA-05 CBT Theory Exam
            if ($cbtQuestions->isNotEmpty()) {
                $cbtAnswers = [];
                $cbtBreakdown = [];
                $correctCount = 0;
                $totalCbt = $cbtQuestions->count();
                $targetCorrect = $isKompeten ? min($totalCbt, max(15, $totalCbt - ($num % 3))) : max(5, (int) round($totalCbt * 0.6));

                foreach ($cbtQuestions as $qIdx => $question) {
                    $shouldBeCorrect = ($qIdx < $targetCorrect);
                    $answer = $shouldBeCorrect
                        ? $question->correct_answer
                        : (['A' => 'B', 'B' => 'C', 'C' => 'D', 'D' => 'A'][$question->correct_answer] ?? 'A');

                    if ($answer === $question->correct_answer) {
                        $correctCount++;
                    }

                    $cbtAnswers[$question->id] = $answer;
                    $cbtBreakdown[$question->id] = [
                        'question_id' => $question->id,
                        'question' => $question->question_text,
                        'question_image' => $question->question_image,
                        'answer' => $answer,
                        'user_answer' => $answer,
                        'correct_answer' => $question->correct_answer,
                        'is_correct' => ($answer === $question->correct_answer),
                    ];
                }

                $cbtScore = $totalCbt > 0 ? round(($correctCount / $totalCbt) * 100, 2) : 0;

                AssessmentIaResponse::updateOrCreate(
                    ['assessment_id' => $assessment->id, 'instrument_code' => 'IA05'],
                    [
                        'data_json' => [
                            'answers' => $cbtAnswers,
                            'breakdown' => $cbtBreakdown,
                            'status' => 'SUBMITTED',
                        ],
                        'score' => $cbtScore,
                        'asesi_signature' => $sampleSignature,
                        'asesi_signed_at' => Carbon::parse($scheduleDate)->setTime(11, 30),
                    ]
                );
            }

            // 16. Certificates for competent assessees
            if ($isKompeten) {
                Certificate::updateOrCreate(
                    ['assessment_id' => $assessment->id],
                    [
                        'schedule_id' => $schedule->id,
                        'asesi_id' => $user->id,
                        'scheme_id' => $scheme->id,
                        'certificate_number' => "CERT/LSP-PROINDO/2026/{$paddedNum}",
                        'blanko_number' => "BNSP-BL-2026-{$paddedNum}",
                        'issued_date' => $scheduleDate,
                        'valid_until' => Carbon::parse($scheduleDate)->addYears(3)->toDateString(),
                        'file_path' => "certificates/cert-{$paddedNum}.pdf",
                        'original_filename' => "sertifikat-{$paddedNum}.pdf",
                        'uploaded_by' => $admin->id,
                    ]
                );
            }

            // 17. Sample Appeal for candidate #20 (Belum Kompeten)
            if ($num === 20) {
                AssessmentAppeal::updateOrCreate(
                    ['assessment_id' => $assessment->id],
                    [
                        'appeal_date' => Carbon::parse($scheduleDate)->addDays(1)->toDateString(),
                        'is_process_explained' => true,
                        'is_discussed_with_assessor' => true,
                        'involves_other_person' => false,
                        'appeal_reasons' => 'Pengajuan klarifikasi dan banding atas evaluasi demonstrasi pada unit keselamatan kerja.',
                        'contested_units' => [$units->first()?->code ?? 'G.45OTO01.001.2'],
                        'status' => 'ACCEPTED',
                        'decision_notes' => 'Banding diterima setelah verifikasi ulang dokumentasi rekaman unjuk kerja.',
                        'decision_date' => Carbon::parse($scheduleDate)->addDays(2)->toDateString(),
                        'decided_by' => $admin->id,
                        'appeals_committee_chair' => 'Drs. H. Mulyadi, M.Pd.',
                    ]
                );
            }
        }

        $this->command?->info("Finished schedule {$schedule->code} with 20 assessees successfully seeded.");
    }
}
