<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Asesi;
use App\Http\Controllers\Asesor;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PrintExampleController;
use App\Http\Controllers\SecureFileController;
use App\Http\Controllers\WilayahController;
use App\Models\Assessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// TEMPORARY MUK PRINT EXAMPLE ROUTES (akan dihapus bersih setelah verifikasi)
Route::prefix('print-example')->group(function () {
    Route::get('/', function () {
        $docs = [
            [
                'code' => 'FR.APL.01',
                'title' => 'Permohonan Sertifikasi Kompetensi',
                'url' => url('/print-example/apl01'),
                'ref_url' => url('/print-example/apl01?ref=1'),
                'pages' => '3 Halaman',
                'desc' => 'Data Pribadi, Data Pekerjaan, Skema, Unit & Bukti Kelengkapan (Tanpa Watermark)',
            ],
            [
                'code' => 'FR.APL.02',
                'title' => 'Asesmen Mandiri',
                'url' => url('/print-example/apl02'),
                'ref_url' => url('/print-example/apl02?ref=1'),
                'pages' => '6 Halaman',
                'desc' => 'Asesmen Mandiri 9 Unit Kompetensi, 21 Elemen, 74 KUK & Rekomendasi Asesor (Tanpa Watermark)',
            ],
            [
                'code' => 'FR.AK.01',
                'title' => 'Persetujuan Asesmen dan Kerahasiaan',
                'url' => url('/print-example/ak01'),
                'ref_url' => url('/print-example/ak01?ref=1'),
                'pages' => '1 Halaman',
                'desc' => 'Persetujuan Kerahasiaan, Rincian Bukti & Tanda Tangan 2 Pihak (Tanpa Watermark)',
            ],
            [
                'code' => 'FR.AK.02',
                'title' => 'Rekaman Asesmen Kompetensi',
                'url' => url('/print-example/ak02'),
                'ref_url' => url('/print-example/ak02?ref=1'),
                'pages' => '3 Halaman',
                'desc' => 'Rekaman Bukti 9 Unit Kompetensi, Rekomendasi Asesor, Tindak Lanjut & Tanda Tangan (Tanpa Watermark)',
            ],
            [
                'code' => 'FR.AK.03',
                'title' => 'Umpan Balik dan Catatan Asesmen',
                'url' => url('/print-example/ak03'),
                'ref_url' => url('/print-example/ak03?ref=1'),
                'pages' => '1 Halaman',
                'desc' => '10 Komponen Umpan Balik Asesi, Penilaian Pelaksanaan & Catatan Tambahan (Tanpa Watermark)',
            ],
            [
                'code' => 'FR.AK.04',
                'title' => 'Banding Asesmen',
                'url' => url('/print-example/ak04'),
                'ref_url' => url('/print-example/ak04?ref=1'),
                'pages' => '1 Halaman',
                'desc' => 'Formulir Pengajuan Banding Hasil Asesmen, Alasan & Pernyataan Hak Asesi (Tanpa Watermark)',
            ],
            [
                'code' => 'FR.AK.07',
                'title' => 'Penyesuaian Wajar dan Beralasan',
                'url' => url('/print-example/ak07'),
                'ref_url' => url('/print-example/ak07?ref=1'),
                'pages' => '4 Halaman',
                'desc' => 'Formulir Identifikasi Kebutuhan Khusus Asesi, Potensi Hambatan & Kesepakatan (Tanpa Watermark)',
            ],
            [
                'code' => 'FR.IA.01',
                'title' => 'Ceklis Observasi Aktivitas Praktik',
                'url' => url('/print-example/ia01'),
                'ref_url' => url('/print-example/ia01?ref=1'),
                'pages' => '9 Halaman',
                'desc' => 'Ceklis Observasi 3 Kelompok Pekerjaan, 9 Unit Kompetensi & 74 KUK Praktik (Tanpa Watermark)',
            ],
            [
                'code' => 'FR.IA.01.B',
                'title' => 'Report Sheet Observasi Praktik',
                'url' => url('/print-example/ia01b'),
                'ref_url' => url('/print-example/ia01b?ref=1'),
                'pages' => '3 Halaman',
                'desc' => 'Lembar Kerja Pengukuran Engine, Baterai, Pengaman & Manajemen Engine EFI (Tanpa Watermark)',
            ],
            [
                'code' => 'FR.IA.03',
                'title' => 'Pertanyaan Mendukung Observasi (PMO)',
                'url' => url('/print-example/ia03'),
                'ref_url' => url('/print-example/ia03?ref=1'),
                'pages' => '4 Halaman',
                'desc' => 'Daftar Pertanyaan Lisan & Bukti Tanggapan Asesi K3, Alat Ukur, Engine & EFI (Tanpa Watermark)',
            ],
            [
                'code' => 'FR.IA.05.C',
                'title' => 'Lembar Jawaban Pilihan Ganda',
                'url' => url('/print-example/ia05c'),
                'ref_url' => url('/print-example/ia05c?ref=1'),
                'pages' => '2 Halaman',
                'desc' => 'Kisi Lembar Jawaban 40 Butir Pilihan Ganda A/B/C/D & Hasil Penilaian (Tanpa Watermark)',
            ],
            [
                'code' => 'BA & HADIR',
                'title' => 'Berita Acara & Daftar Hadir Peserta',
                'url' => url('/print-example/berita-acara'),
                'ref_url' => url('/print-example/berita-acara?ref=1'),
                'pages' => '2 Halaman (Portrait + Landscape)',
                'desc' => 'Berita Acara Hasil Uji BNSP & Presensi Peserta Uji Kompetensi TUK (Tanpa Watermark)',
            ],
            [
                'code' => 'HADIR ASESOR',
                'title' => 'Daftar Hadir Asesor Kompetensi',
                'url' => url('/print-example/hadir-asesor'),
                'ref_url' => url('/print-example/hadir-asesor?ref=1'),
                'pages' => '1 Halaman',
                'desc' => 'Presensi Resmi Asesor Kompetensi Bersama Penanggung Jawab TUK (Tanpa Watermark)',
            ],
            [
                'code' => 'PERNYATAAN',
                'title' => 'Surat Pernyataan Pemegang Sertifikat',
                'url' => url('/print-example/pernyataan'),
                'ref_url' => url('/print-example/pernyataan?ref=1'),
                'pages' => '1 Halaman',
                'desc' => 'Kop Resmi LSP, Komitmen 4 Poin Kepatuhan Kode Etik & Sertifikasi (Tanpa Watermark)',
            ],
            [
                'code' => 'DAFTAR PERIKSA',
                'title' => 'Daftar Periksa Kelengkapan Dokumen MUK',
                'url' => url('/print-example/daftar-periksa'),
                'ref_url' => url('/print-example/daftar-periksa?ref=1'),
                'pages' => '1 Halaman',
                'desc' => 'Ceklis Master 25 Dokumen Asesmen, TUK & Manager Sertifikasi (Tanpa Watermark)',
            ],
        ];

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>MUK Print Preview Dashboard</title>'
              .'<script src="https://cdn.tailwindcss.com"></script></head><body class="bg-slate-100 p-8 font-sans">'
              .'<div class="max-w-4xl mx-auto space-y-6">'
              .'<div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">'
              .'<span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-1 rounded">Rute Sementara (Untuk Verifikasi/Audit)</span>'
              .'<h1 class="text-2xl font-black text-slate-900 mt-2">Pratinjau Dokumen Cetak MUK Dinamis (LSP PrO Indo)</h1>'
              .'<p class="text-xs text-slate-600 mt-1">Bandingkan hasil render dokumen Python ReportLab dengan PDF Master resmi secara langsung.</p>'
              .'</div>'
              .'<div class="grid grid-cols-1 gap-4">';
        foreach ($docs as $d) {
            $html .= '<div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">'
                  .'<div>'
                  .'<div class="flex items-center gap-2">'
                  .'<span class="font-mono text-xs font-bold bg-indigo-50 text-indigo-700 px-2.5 py-0.5 rounded border border-indigo-200">'.$d['code'].'</span>'
                  .'<h3 class="text-base font-bold text-slate-800">'.$d['title'].'</h3>'
                  .'<span class="text-[11px] text-slate-400">('.$d['pages'].')</span>'
                  .'</div>'
                  .'<p class="text-xs text-slate-500 mt-1">'.$d['desc'].'</p>'
                  .'</div>'
                  .'<div class="flex items-center gap-2 shrink-0">'
                  .'<a href="'.$d['url'].'" target="_blank" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-sm transition">Lihat Hasil Render &rarr;</a>'
                  .'<a href="'.$d['ref_url'].'" target="_blank" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold transition">Buka Master Asli &nearr;</a>'
                  .'</div>'
                  .'</div>';
        }
        $html .= '</div>'
              .'<div class="p-4 bg-slate-200/60 rounded-xl text-center text-xs text-slate-500">'
              .'Setelah Anda selesai mengecek, route dan script sementara ini akan dihapus bersih.'
              .'</div>'
              .'</div></body></html>';

        return response($html);
    });

    Route::get('/apl01', [PrintExampleController::class, 'apl01'])->name('print-example.apl01');
    Route::get('/apl02', [PrintExampleController::class, 'apl02'])->name('print-example.apl02');
    Route::get('/ak01', [PrintExampleController::class, 'ak01'])->name('print-example.ak01');
    Route::get('/ak02', [PrintExampleController::class, 'ak02'])->name('print-example.ak02');
    Route::get('/ak03', [PrintExampleController::class, 'ak03'])->name('print-example.ak03');
    Route::get('/ak04', [PrintExampleController::class, 'ak04'])->name('print-example.ak04');
    Route::get('/ak07', [PrintExampleController::class, 'ak07'])->name('print-example.ak07');
    Route::get('/ia01', [PrintExampleController::class, 'ia01'])->name('print-example.ia01');
    Route::get('/ia01b', [PrintExampleController::class, 'ia01b'])->name('print-example.ia01b');
    Route::get('/ia03', [PrintExampleController::class, 'ia03'])->name('print-example.ia03');
    Route::get('/ia05c', [PrintExampleController::class, 'ia05c'])->name('print-example.ia05c');
    Route::get('/berita-acara', [PrintExampleController::class, 'beritaAcara'])->name('print-example.ba');
    Route::get('/hadir-asesor', [PrintExampleController::class, 'hadirAsesor'])->name('print-example.hadir-asesor');
    Route::get('/pernyataan', [PrintExampleController::class, 'pernyataan'])->name('print-example.pernyataan');
    Route::get('/daftar-periksa', [PrintExampleController::class, 'daftarPeriksa'])->name('print-example.daftar-periksa');
});

// API Wilayah Indonesia (cahyadsn/wilayah Kepmendagri No 300.2.2-2430 Tahun 2025)
Route::prefix('api/wilayah')->group(function () {
    Route::get('/all', [WilayahController::class, 'all'])->name('api.wilayah.all');
    Route::get('/provinces', [WilayahController::class, 'provinces'])->name('api.wilayah.provinces');
    Route::get('/regencies/{provinceCode?}', [WilayahController::class, 'regencies'])->name('api.wilayah.regencies');
});

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
});

// Authenticated Global Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/proxy/switch-back', [AuthController::class, 'proxySwitchBack'])->name('proxy.switch-back');

    // Secure Document & Evidence Access
    Route::get('/secure-file/proof/{proof}', [SecureFileController::class, 'showProof'])->name('secure-file.proof');
    Route::get('/secure-file/evidence/{response}', [SecureFileController::class, 'showEvidence'])->name('secure-file.evidence');
    Route::get('/', function () {
        $user = auth()->user();

        return match ($user->role) {
            'SUPER_ADMIN', 'ADMIN_LSP' => redirect()->route('admin.dashboard'),
            'ASESOR' => redirect()->route('asesor.jadwal.index'),
            'ASESI' => redirect()->route('asesi.dashboard'),
            default => redirect()->route('admin.dashboard'),
        };
    });
});

// Asesi Routes
Route::middleware(['auth', 'role:ASESI'])->prefix('asesi')->name('asesi.')->group(function () {
    Route::get('/dashboard', [Asesi\JadwalController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [Asesi\ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [Asesi\ProfileController::class, 'update'])->name('profile.update');

    // Sertifikat Saya
    Route::get('/sertifikat', [Asesi\CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/sertifikat/{certificate}/preview', [Asesi\CertificateController::class, 'preview'])->name('certificates.preview');
    Route::get('/sertifikat/{certificate}/download', [Asesi\CertificateController::class, 'download'])->name('certificates.download');

    // Jadwal
    Route::get('/jadwal', [Asesi\JadwalController::class, 'index'])->name('jadwal.index');
    Route::post('/jadwal/join', [Asesi\JadwalController::class, 'join'])->middleware('throttle:join-schedule')->name('jadwal.join');

    // Asesmen & APL
    Route::get('/assessment/{assessment}/apl', [Asesi\AplController::class, 'show'])->name('assessment.apl');
    Route::post('/assessment/{assessment}/apl', [Asesi\AplController::class, 'store'])->name('assessment.apl.store');

    // Ruang Uji (Protected by APL Approval Gate)
    Route::middleware(['apl.approved'])->prefix('assessment/{assessment}')->name('assessment.')->group(function () {
        Route::get('/ak01', [Asesi\RuangUjiController::class, 'ak01'])->name('ak01');
        Route::post('/ak01', [Asesi\RuangUjiController::class, 'signAk01'])->name('ak01.sign');

        Route::get('/ruang-uji', [Asesi\RuangUjiController::class, 'hub'])->name('ruang-uji');

        // FR.IA Instruments
        Route::get('/instrument/IA05', [Asesi\RuangUjiController::class, 'ia05'])->name('instrument.ia05');
        Route::post('/instrument/IA05', [Asesi\RuangUjiController::class, 'submitIa05'])->name('instrument.ia05.submit');
        Route::post('/instrument/IA05/autosave', [Asesi\RuangUjiController::class, 'autosaveIa05'])->name('instrument.ia05.autosave');

        Route::get('/instrument/{code}/download-template', [Asesi\RuangUjiController::class, 'downloadTemplate'])->name('instrument.download-template');
        Route::get('/instrument/{code}', [Asesi\RuangUjiController::class, 'iaView'])->name('instrument.view');
        Route::post('/instrument/{code}/feedback', [Asesi\RuangUjiController::class, 'submitFeedback'])->name('instrument.feedback');
        Route::post('/instrument/{code}/upload-evidence', [Asesi\RuangUjiController::class, 'uploadEvidence'])->name('instrument.upload-evidence');

        Route::post('/finish', [Asesi\RuangUjiController::class, 'finish'])->name('finish');
        Route::get('/ak03', [Asesi\RuangUjiController::class, 'showAk03'])->name('ak03');
        Route::post('/ak03', [Asesi\RuangUjiController::class, 'submitAk03'])->name('ak03.submit');
        Route::get('/appeal', [Asesi\RuangUjiController::class, 'showAppeal'])->name('appeal');
        Route::post('/appeal', [Asesi\RuangUjiController::class, 'submitAppeal'])->name('appeal.submit');
    });
});

// Asesor Routes
Route::middleware(['auth', 'role:ASESOR'])->prefix('asesor')->name('asesor.')->group(function () {
    Route::get('/profile', [Asesor\ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [Asesor\ProfileController::class, 'update'])->name('profile.update');

    Route::get('/jadwal', [Asesor\JadwalController::class, 'index'])->name('jadwal.index');

    // Review APL (both aliases supported)
    Route::get('/assessment/{assessment}/review-apl', [Asesor\AplReviewController::class, 'show'])->name('apl.review');
    Route::post('/assessment/{assessment}/review-apl', [Asesor\AplReviewController::class, 'decide'])->name('apl.decide');
    Route::get('/review-apl/{assessment}', [Asesor\AplReviewController::class, 'show'])->name('review-apl');
    Route::post('/review-apl/{assessment}', [Asesor\AplReviewController::class, 'decide'])->name('review-apl.update');
    Route::post('/schedule/{schedule}/bulk-review-apl', [Asesor\AplReviewController::class, 'bulkApprove'])->name('schedule.apl.bulk-review');

    // AK01
    Route::get('/schedule/{schedule}/ak01', [Asesor\Ak01Controller::class, 'index'])->name('schedule.ak01');
    Route::post('/schedule/{schedule}/ak01', [Asesor\Ak01Controller::class, 'store'])->name('schedule.ak01.store');
    Route::post('/assessment/{assessment}/ak01/sign', [Asesor\InstrumentController::class, 'ak01Sign'])->name('ak01.sign');

    // AK07 Penyesuaian Wajar (Single & Batch)
    Route::get('/schedule/{schedule}/ak07', [Asesor\Ak07Controller::class, 'index'])->name('schedule.ak07');
    Route::post('/schedule/{schedule}/ak07', [Asesor\Ak07Controller::class, 'store'])->name('schedule.ak07.store');
    Route::get('/schedule/{schedule}/ak07/create', [Asesor\Ak07Controller::class, 'index'])->name('ak07.create');
    Route::post('/schedule/{schedule}/ak07/create', [Asesor\Ak07Controller::class, 'store'])->name('ak07.store');

    // Ruang Uji Jadwal (Schedule-level: Displays Selected Instruments)
    Route::get('/schedule/{schedule}/ruang-uji', [Asesor\InstrumentController::class, 'scheduleRuangUji'])->name('schedule.ruang-uji');

    // Instrument Asesi List (Displays Asesis for a Selected Instrument)
    Route::get('/schedule/{schedule}/instrument/{code}', [Asesor\InstrumentController::class, 'scheduleInstrument'])->name('schedule.instrument');

    // Legacy hub removed — use schedule-level ruang-uji instead

    // Instrument aliases for IA01, IA02, IA03, IA05
    Route::get('/assessment/{assessment}/instrument/{code}', [Asesor\InstrumentController::class, 'instrumentShow'])->name('assessment.instrument');
    Route::post('/assessment/{assessment}/instrument/{code}', [Asesor\InstrumentController::class, 'instrumentStore'])->name('assessment.instrument.store');
    Route::post('/assessment/{assessment}/instrument/ia05/reset', [Asesor\InstrumentController::class, 'resetIa05Status'])->name('assessment.ia05.reset');
    Route::post('/assessment/{assessment}/instrument/ia01b/request-revision', [Asesor\InstrumentController::class, 'requestIa01bRevision'])->name('assessment.ia01b.request-revision');
    Route::post('/assessment/{assessment}/instrument/ia01b/cancel-revision', [Asesor\InstrumentController::class, 'cancelIa01bRevision'])->name('assessment.ia01b.cancel-revision');

    Route::get('/ruang-uji/{assessment}/ia01', fn (Assessment $assessment) => app()->call([app(Asesor\InstrumentController::class), 'instrumentShow'], ['assessment' => $assessment, 'code' => 'ia01']))->name('ruang-uji.ia01');
    Route::post('/ruang-uji/{assessment}/ia01', fn (Assessment $assessment, Request $req) => app(Asesor\InstrumentController::class)->instrumentStore($assessment, 'ia01', $req))->name('ruang-uji.ia01.store');

    Route::get('/ruang-uji/{assessment}/ia01b', fn (Assessment $assessment) => app()->call([app(Asesor\InstrumentController::class), 'instrumentShow'], ['assessment' => $assessment, 'code' => 'ia01b']))->name('ruang-uji.ia01b');
    Route::post('/ruang-uji/{assessment}/ia01b', fn (Assessment $assessment, Request $req) => app(Asesor\InstrumentController::class)->instrumentStore($assessment, 'ia01b', $req))->name('ruang-uji.ia01b.store');
    Route::post('/ruang-uji/{assessment}/ia01b/request-revision', fn (Assessment $assessment, Request $req) => app(Asesor\InstrumentController::class)->requestIa01bRevision($assessment, $req))->name('ruang-uji.ia01b.request-revision');
    Route::post('/ruang-uji/{assessment}/ia01b/cancel-revision', fn (Assessment $assessment) => app(Asesor\InstrumentController::class)->cancelIa01bRevision($assessment))->name('ruang-uji.ia01b.cancel-revision');

    Route::get('/ruang-uji/{assessment}/ia02', fn (Assessment $assessment) => app()->call([app(Asesor\InstrumentController::class), 'instrumentShow'], ['assessment' => $assessment, 'code' => 'ia02']))->name('ruang-uji.ia02');
    Route::post('/ruang-uji/{assessment}/ia02', fn (Assessment $assessment, Request $req) => app(Asesor\InstrumentController::class)->instrumentStore($assessment, 'ia02', $req))->name('ruang-uji.ia02.store');
    Route::post('/schedule/{schedule}/bulk-sign-ia02', [Asesor\InstrumentController::class, 'bulkSignIa02'])->name('schedule.ia02.bulk-sign');

    Route::get('/ruang-uji/{assessment}/ia03', fn (Assessment $assessment) => app()->call([app(Asesor\InstrumentController::class), 'instrumentShow'], ['assessment' => $assessment, 'code' => 'ia03']))->name('ruang-uji.ia03');
    Route::post('/ruang-uji/{assessment}/ia03', fn (Assessment $assessment, Request $req) => app(Asesor\InstrumentController::class)->instrumentStore($assessment, 'ia03', $req))->name('ruang-uji.ia03.store');

    Route::get('/ruang-uji/{assessment}/ia05', fn (Assessment $assessment) => app()->call([app(Asesor\InstrumentController::class), 'instrumentShow'], ['assessment' => $assessment, 'code' => 'ia05']))->name('ruang-uji.ia05');

    // AK02 Rekaman Asesmen (schedule-level list + per-assessment form)
    Route::get('/schedule/{schedule}/ak02', [Asesor\FinalizationController::class, 'scheduleAk02'])->name('schedule.ak02');
    Route::post('/schedule/{schedule}/ak02/bulk', [Asesor\FinalizationController::class, 'bulkUpdateScheduleAk02'])->name('schedule.ak02.bulk');
    Route::get('/assessment/{assessment}/ak02', [Asesor\FinalizationController::class, 'showAk02'])->name('assessment.ak02');
    Route::post('/assessment/{assessment}/ak02', [Asesor\FinalizationController::class, 'updateAk02'])->name('assessment.ak02.update');

    // AK05 & AK06 Reports
    Route::get('/schedule/{schedule}/reports', [Asesor\FinalizationController::class, 'showReports'])->name('schedule.reports');
    Route::post('/schedule/{schedule}/reports', [Asesor\FinalizationController::class, 'submitReports'])->name('schedule.reports.submit');

    // Finalize
    Route::post('/schedule/{schedule}/finalize', [Asesor\FinalizationController::class, 'finalize'])->name('schedule.finalize');
    Route::post('/assessment/{assessment}/complete', [Asesor\FinalizationController::class, 'completeAssessment'])->name('assessment.complete');
    Route::post('/assessment/{assessment}/mark-absent', [Asesor\FinalizationController::class, 'markAbsent'])->name('assessment.mark-absent');

    // Fixed and Dynamic MUK PDF exports
    Route::get('/schedule/{schedule}/fixed/{type}', [Admin\DokumenMukController::class, 'exportFixed'])->name('schedule.fixed');
    Route::get('/assessment/{assessment}/fixed/{type}', [Admin\DokumenMukController::class, 'exportAsesiFixed'])->name('assessment.fixed');
    Route::get('/schedule/{schedule}/dynamic/{type}', [Admin\DokumenMukController::class, 'exportDynamic'])->name('schedule.dynamic');
    Route::get('/assessment/{assessment}/dynamic/{type}', [Admin\DokumenMukController::class, 'exportAsesiDynamic'])->name('assessment.dynamic');
});

// Admin & Superadmin Routes
Route::middleware(['auth', 'role:SUPER_ADMIN,ADMIN_LSP,KOMITE_TEKNIS,TUK_ADMIN'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Admin Profile & Digital Signature Settings
    Route::get('/profile', [Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [Admin\ProfileController::class, 'update'])->name('profile.update');

    // Appeals (FR.AK.04) & Feedback (FR.AK.03) - Read list & detail per schedule
    Route::get('/appeals', [Admin\AppealController::class, 'index'])->name('appeals.index');
    Route::get('/appeals/{schedule}', [Admin\AppealController::class, 'show'])->name('appeals.show');

    // Jadwal - Read list
    Route::get('/jadwal', [Admin\JadwalController::class, 'index'])->name('jadwal.index');

    // Sertifikat Kompetensi
    Route::get('/certificates', [Admin\CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/jadwal/{schedule}/sertifikat', [Admin\CertificateController::class, 'scheduleCertificates'])->name('certificates.schedule');
    Route::post('/jadwal/{schedule}/sertifikat', [Admin\CertificateController::class, 'store'])->name('certificates.store');
    Route::delete('/certificates/{certificate}', [Admin\CertificateController::class, 'destroy'])->name('certificates.destroy');
    Route::get('/certificates/{certificate}/preview', [Admin\CertificateController::class, 'preview'])->name('certificates.preview');
    Route::get('/certificates/{certificate}/download', [Admin\CertificateController::class, 'download'])->name('certificates.download');

    // Write actions restricted strictly to SUPER_ADMIN and ADMIN_LSP
    Route::middleware(['role:SUPER_ADMIN,ADMIN_LSP'])->group(function () {
        // Candidate Registration Review
        Route::post('/assessment/{assessment}/review-registration', [Admin\JadwalController::class, 'reviewRegistration'])->name('assessment.review-registration');
        Route::post('/assessment/{assessment}/assign-assessor', [Admin\JadwalController::class, 'assignAssessor'])->name('assessment.assign-assessor');
        Route::post('/jadwal/{jadwal}/batch-review-registration', [Admin\JadwalController::class, 'batchReviewRegistration'])->name('jadwal.batch-review-registration');
        Route::post('/assessment/{assessment}/mark-absent', [Admin\JadwalController::class, 'markAbsent'])->name('assessment.mark-absent');
        Route::delete('/jadwal/{jadwal}/peserta/{assessment}', [Admin\JadwalController::class, 'removeCandidate'])->name('jadwal.remove-candidate');

        // Appeal Decision
        Route::post('/appeals/{appeal}/decide', [Admin\AppealController::class, 'decide'])->name('appeals.decide');

        // Jadwal Mutating routes
        Route::get('/jadwal/create', [Admin\JadwalController::class, 'create'])->name('jadwal.create');
        Route::post('/jadwal', [Admin\JadwalController::class, 'store'])->name('jadwal.store');
        Route::get('/jadwal/{jadwal}/edit', [Admin\JadwalController::class, 'edit'])->name('jadwal.edit');
        Route::match(['put', 'patch'], '/jadwal/{jadwal}', [Admin\JadwalController::class, 'update'])->name('jadwal.update');
        Route::delete('/jadwal/{jadwal}', [Admin\JadwalController::class, 'destroy'])->name('jadwal.destroy');
    });

    Route::get('/jadwal/{jadwal}', [Admin\JadwalController::class, 'show'])->name('jadwal.show');

    // Dokumen MUK (Schedule level - Admin LSP & TUK Admin)
    Route::get('/dokumen-muk', [Admin\DokumenMukController::class, 'index'])->name('dokumen-muk.index');
    Route::get('/dokumen-muk/{schedule}', [Admin\DokumenMukController::class, 'show'])->name('dokumen-muk.show')->whereNumber('schedule');
    Route::get('/dokumen-muk/{schedule}/fixed/{type}', [Admin\DokumenMukController::class, 'exportFixed'])->name('dokumen-muk.fixed');
    Route::get('/dokumen-muk/{schedule}/dynamic/{type}', [Admin\DokumenMukController::class, 'exportDynamic'])->name('dokumen-muk.dynamic');
    Route::get('/dokumen-muk/{schedule}/export/{type}', [Admin\DokumenMukController::class, 'exportSchedule'])->name('dokumen-muk.export');

    Route::get('/dokumen-muk/{schedule}/export-sertifikat', [Admin\DokumenMukController::class, 'exportSertifikat'])->name('dokumen-muk.export-sertifikat');
    Route::get('/dokumen-muk/{schedule}/export-ba', [Admin\DokumenMukController::class, 'exportBa'])->name('dokumen-muk.export-ba');
    Route::get('/dokumen-muk/{schedule}/export-hadir-asesor', [Admin\DokumenMukController::class, 'exportHadirAsesor'])->name('dokumen-muk.export-hadir-asesor');
    Route::get('/dokumen-muk/{schedule}/export-hadir-asesi', [Admin\DokumenMukController::class, 'exportHadirAsesi'])->name('dokumen-muk.export-hadir-asesi');
    Route::get('/dokumen-muk/{schedule}/export-excel-biodata', [Admin\DokumenMukController::class, 'exportExcelBiodata'])->name('dokumen-muk.export-excel-biodata');

    // Switch Role / Proxy
    Route::get('/proxy', [Admin\ProxyController::class, 'index'])->name('proxy.index');
    Route::post('/proxy/{user}', [Admin\ProxyController::class, 'impersonate'])->name('proxy.impersonate');

    // Master Data (Super Admin only)
    Route::middleware(['role:SUPER_ADMIN'])->group(function () {
        Route::post('/users/{user}/reset-password', [Admin\UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::resource('users', Admin\UserController::class);
        Route::resource('schemes', Admin\SchemeController::class);
        Route::resource('question-banks', Admin\QuestionBankController::class);

        // Scheme Competency Units, Elements & KUK Management
        Route::post('/schemes/{scheme}/units', [Admin\SchemeController::class, 'storeUnit'])->name('schemes.units.store');
        Route::put('/units/{unit}', [Admin\SchemeController::class, 'updateUnit'])->name('schemes.units.update');
        Route::delete('/units/{unit}', [Admin\SchemeController::class, 'destroyUnit'])->name('schemes.units.destroy');

        Route::post('/units/{unit}/elements', [Admin\SchemeController::class, 'storeElement'])->name('schemes.elements.store');
        Route::put('/elements/{element}', [Admin\SchemeController::class, 'updateElement'])->name('schemes.elements.update');
        Route::delete('/elements/{element}', [Admin\SchemeController::class, 'destroyElement'])->name('schemes.elements.destroy');

        Route::post('/elements/{element}/kuks', [Admin\SchemeController::class, 'storeKuk'])->name('schemes.kuks.store');
        Route::put('/kuks/{kuk}', [Admin\SchemeController::class, 'updateKuk'])->name('schemes.kuks.update');
        Route::delete('/kuks/{kuk}', [Admin\SchemeController::class, 'destroyKuk'])->name('schemes.kuks.destroy');
    });
});

// Dokumen MUK Individual Assessment (Accessible by Admin, Asesor, and candidate Asesi for their own assessment)
Route::middleware(['auth', 'role:SUPER_ADMIN,ADMIN_LSP,KOMITE_TEKNIS,TUK_ADMIN,ASESOR,ASESI'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dokumen-muk/asesi/{assessment}/fixed/{type}', [Admin\DokumenMukController::class, 'exportAsesiFixed'])->name('dokumen-muk.asesi-fixed');
    Route::get('/dokumen-muk/asesi/{assessment}/dynamic/{type}', [Admin\DokumenMukController::class, 'exportAsesiDynamic'])->name('dokumen-muk.asesi-dynamic');
    Route::get('/dokumen-muk/asesi/{assessment}/export/{type}', [Admin\DokumenMukController::class, 'exportAsesi'])->name('dokumen-muk.export-asesi');
    Route::get('/dokumen-muk/individual/{assessment}', [Admin\DokumenMukController::class, 'exportIndividual'])->name('dokumen-muk.export-individual');
});

// Global alias for proxy.switch so both route('proxy.switch') and route('admin.proxy.impersonate') work
Route::post('/admin/proxy/switch/{user}', [Admin\ProxyController::class, 'impersonate'])
    ->middleware(['auth', 'role:SUPER_ADMIN,ADMIN_LSP'])
    ->name('proxy.switch');

// Fallback storage route for public uploads in case symlink is missing or bypassed
Route::get('/storage/{path}', function (string $path) {
    $disk = Storage::disk('public');
    if (! $disk->exists($path)) {
        abort(404);
    }

    return response()->file($disk->path($path));
})->where('path', '.*')->name('storage.fallback');
