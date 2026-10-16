<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Asesi;
use App\Http\Controllers\Asesor;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\SecureFileController;
use App\Models\Assessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
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

    // Jadwal
    Route::get('/jadwal', [Asesi\JadwalController::class, 'index'])->name('jadwal.index');
    Route::post('/jadwal/join', [Asesi\JadwalController::class, 'join'])->middleware('throttle:10,1')->name('jadwal.join');

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
    Route::get('/jadwal', [Asesor\JadwalController::class, 'index'])->name('jadwal.index');
    Route::get('/jadwal/{schedule}/asesi', [Asesor\JadwalController::class, 'asesiList'])->name('jadwal.asesi');

    // Review APL (both aliases supported)
    Route::get('/assessment/{assessment}/review-apl', [Asesor\AplReviewController::class, 'show'])->name('apl.review');
    Route::post('/assessment/{assessment}/review-apl', [Asesor\AplReviewController::class, 'decide'])->name('apl.decide');
    Route::get('/review-apl/{assessment}', [Asesor\AplReviewController::class, 'show'])->name('review-apl');
    Route::post('/review-apl/{assessment}', [Asesor\AplReviewController::class, 'decide'])->name('review-apl.update');

    // AK01
    Route::get('/schedule/{schedule}/ak01', [Asesor\InstrumentController::class, 'ak01Schedule'])->name('schedule.ak01');
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

    // Ruang Uji Asesor per Assessment (Hub legacy & alias)
    Route::get('/assessment/{assessment}/ruang-uji', [Asesor\InstrumentController::class, 'hub'])->name('assessment.ruang-uji');
    Route::get('/ruang-uji/{assessment}', [Asesor\InstrumentController::class, 'hub'])->name('ruang-uji.hub');

    // Instrument aliases for IA01, IA02, IA03, IA05
    Route::get('/assessment/{assessment}/instrument/{code}', [Asesor\InstrumentController::class, 'instrumentShow'])->name('assessment.instrument');
    Route::post('/assessment/{assessment}/instrument/{code}', [Asesor\InstrumentController::class, 'instrumentStore'])->name('assessment.instrument.store');

    Route::get('/ruang-uji/{assessment}/ia01', fn (Assessment $assessment) => app()->call([app(Asesor\InstrumentController::class), 'instrumentShow'], ['assessment' => $assessment, 'code' => 'ia01']))->name('ruang-uji.ia01');
    Route::post('/ruang-uji/{assessment}/ia01', fn (Assessment $assessment, Request $req) => app(Asesor\InstrumentController::class)->instrumentStore($assessment, 'ia01', $req))->name('ruang-uji.ia01.store');

    Route::get('/ruang-uji/{assessment}/ia01b', fn (Assessment $assessment) => app()->call([app(Asesor\InstrumentController::class), 'instrumentShow'], ['assessment' => $assessment, 'code' => 'ia01b']))->name('ruang-uji.ia01b');
    Route::post('/ruang-uji/{assessment}/ia01b', fn (Assessment $assessment, Request $req) => app(Asesor\InstrumentController::class)->instrumentStore($assessment, 'ia01b', $req))->name('ruang-uji.ia01b.store');

    Route::get('/ruang-uji/{assessment}/ia02', fn (Assessment $assessment) => app()->call([app(Asesor\InstrumentController::class), 'instrumentShow'], ['assessment' => $assessment, 'code' => 'ia02']))->name('ruang-uji.ia02');
    Route::post('/ruang-uji/{assessment}/ia02', fn (Assessment $assessment, Request $req) => app(Asesor\InstrumentController::class)->instrumentStore($assessment, 'ia02', $req))->name('ruang-uji.ia02.store');

    Route::get('/ruang-uji/{assessment}/ia03', fn (Assessment $assessment) => app()->call([app(Asesor\InstrumentController::class), 'instrumentShow'], ['assessment' => $assessment, 'code' => 'ia03']))->name('ruang-uji.ia03');
    Route::post('/ruang-uji/{assessment}/ia03', fn (Assessment $assessment, Request $req) => app(Asesor\InstrumentController::class)->instrumentStore($assessment, 'ia03', $req))->name('ruang-uji.ia03.store');

    Route::get('/ruang-uji/{assessment}/ia05', fn (Assessment $assessment) => app()->call([app(Asesor\InstrumentController::class), 'instrumentShow'], ['assessment' => $assessment, 'code' => 'ia05']))->name('ruang-uji.ia05');

    // AK02 Rekaman Asesmen
    Route::get('/assessment/{assessment}/ak02', [Asesor\FinalizationController::class, 'showAk02'])->name('assessment.ak02');
    Route::post('/assessment/{assessment}/ak02', [Asesor\FinalizationController::class, 'updateAk02'])->name('assessment.ak02.update');

    // AK05 & AK06 Reports
    Route::get('/schedule/{schedule}/reports', [Asesor\FinalizationController::class, 'showReports'])->name('schedule.reports');
    Route::post('/schedule/{schedule}/reports', [Asesor\FinalizationController::class, 'submitReports'])->name('schedule.reports.submit');

    // Finalize
    Route::post('/schedule/{schedule}/finalize', [Asesor\FinalizationController::class, 'finalize'])->name('schedule.finalize');
    Route::post('/assessment/{assessment}/complete', [Asesor\FinalizationController::class, 'completeAssessment'])->name('assessment.complete');
});

// Admin & Superadmin Routes
Route::middleware(['auth', 'role:SUPER_ADMIN,ADMIN_LSP,KOMITE_TEKNIS,TUK_ADMIN'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Appeals (FR.AK.04) - Read list
    Route::get('/appeals', [Admin\AppealController::class, 'index'])->name('appeals.index');

    // Jadwal - Read list
    Route::get('/jadwal', [Admin\JadwalController::class, 'index'])->name('jadwal.index');

    // Write actions restricted strictly to SUPER_ADMIN and ADMIN_LSP
    Route::middleware(['role:SUPER_ADMIN,ADMIN_LSP'])->group(function () {
        // Candidate Registration Review
        Route::post('/assessment/{assessment}/review-registration', [Admin\JadwalController::class, 'reviewRegistration'])->name('assessment.review-registration');
        Route::post('/jadwal/{jadwal}/batch-review-registration', [Admin\JadwalController::class, 'batchReviewRegistration'])->name('jadwal.batch-review-registration');

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

    // Dokumen MUK
    Route::get('/dokumen-muk', [Admin\DokumenMukController::class, 'index'])->name('dokumen-muk.index');
    Route::get('/dokumen-muk/{schedule}/export/{type}', [Admin\DokumenMukController::class, 'exportSchedule'])->name('dokumen-muk.export');
    Route::get('/dokumen-muk/asesi/{assessment}/export/{type}', [Admin\DokumenMukController::class, 'exportAsesi'])->name('dokumen-muk.export-asesi');

    Route::get('/dokumen-muk/{schedule}/export-sertifikat', [Admin\DokumenMukController::class, 'exportSertifikat'])->name('dokumen-muk.export-sertifikat');
    Route::get('/dokumen-muk/{schedule}/export-ba', [Admin\DokumenMukController::class, 'exportBa'])->name('dokumen-muk.export-ba');
    Route::get('/dokumen-muk/{schedule}/export-hadir-asesor', [Admin\DokumenMukController::class, 'exportHadirAsesor'])->name('dokumen-muk.export-hadir-asesor');
    Route::get('/dokumen-muk/{schedule}/export-hadir-asesi', [Admin\DokumenMukController::class, 'exportHadirAsesi'])->name('dokumen-muk.export-hadir-asesi');
    Route::get('/dokumen-muk/{schedule}/export-excel-biodata', [Admin\DokumenMukController::class, 'exportExcelBiodata'])->name('dokumen-muk.export-excel-biodata');
    Route::get('/dokumen-muk/individual/{assessment}', [Admin\DokumenMukController::class, 'exportIndividual'])->name('dokumen-muk.export-individual');

    // Switch Role / Proxy
    Route::get('/proxy', [Admin\ProxyController::class, 'index'])->name('proxy.index');
    Route::post('/proxy/{user}', [Admin\ProxyController::class, 'impersonate'])->name('proxy.impersonate');

    // Master Data (Super Admin only)
    Route::middleware(['role:SUPER_ADMIN'])->group(function () {
        Route::resource('users', Admin\UserController::class);
        Route::resource('schemes', Admin\SchemeController::class);
        Route::resource('question-banks', Admin\QuestionBankController::class);
    });
});

// Global alias for proxy.switch so both route('proxy.switch') and route('admin.proxy.impersonate') work
Route::post('/admin/proxy/switch/{user}', [Admin\ProxyController::class, 'impersonate'])
    ->middleware(['auth', 'role:SUPER_ADMIN,ADMIN_LSP'])
    ->name('proxy.switch');
