<?php

namespace App\Http\Controllers\Asesi;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAk07Item;
use App\Models\AssessmentIaResponse;
use App\Models\InterviewQuestion;
use App\Models\QuestionBank;
use App\Rules\ValidSignature;
use App\Services\AkWorkflowService;
use App\Services\AutoGradeService;
use App\Services\SchemeInstrumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RuangUjiController extends Controller
{
    public function ak01(Assessment $assessment): View|RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        if ($assessment->apl_status === 'REJECTED' || $assessment->admin_status === 'REJECTED') {
            return redirect()->route('asesi.assessment.apl', $assessment)
                ->with('error', 'Permohonan APL Anda pada jadwal asesmen ini telah ditolak. Anda tidak dapat mengikuti jadwal ini.');
        }

        if ($assessment->apl_status !== 'APPROVED') {
            return redirect()->route('asesi.assessment.apl', $assessment)
                ->with('error', 'FR.AK.01 hanya dapat diakses setelah permohonan APL Anda disetujui asesor.');
        }

        if (AssessmentAk07Item::where('assessment_id', $assessment->id)->count() === 0) {
            return redirect()->route('asesi.dashboard')
                ->with('warning', 'Asesor belum menetapkan formulir penyesuaian asesmen (FR.AK.07). FR.AK.01 persetujuan asesmen baru dapat diakses setelah FR.AK.07 ditetapkan.');
        }

        $assessment->load('schedule.scheme');

        return view('asesi.ruang-uji.ak01', compact('assessment'));
    }

    public function signAk01(Assessment $assessment, Request $request): RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        if ($assessment->schedule && $assessment->schedule->is_closed) {
            return back()->with('error', 'Jadwal asesmen sudah resmi ditutup.');
        }

        if ($assessment->apl_status !== 'APPROVED') {
            return back()->with('error', 'FR.AK.01 hanya dapat disetujui setelah permohonan APL Anda disetujui asesor.');
        }

        if (AssessmentAk07Item::where('assessment_id', $assessment->id)->count() === 0) {
            return back()->with('error', 'FR.AK.01 belum dapat ditandatangani karena formulir penyesuaian asesmen (FR.AK.07) belum ditetapkan asesor.');
        }

        $request->validate([
            'signature_data' => ['nullable', 'string', new ValidSignature],
            'asesi_signature' => ['nullable', 'string', new ValidSignature],
        ]);

        $now = now();
        $signature = $request->input('signature_data')
            ?? $request->input('asesi_signature')
            ?? $assessment->asesi_signature
            ?? auth()->user()->profile?->signature_data;

        if ($assessment->asesi_signature && $signature !== $assessment->asesi_signature) {
            Log::info("Signature updated for assessment #{$assessment->id} by asesi #{$assessment->asesi_id}");
        }

        DB::transaction(function () use ($assessment, $now, $signature) {
            $assessment->update([
                'ak01_agreed_at' => $assessment->ak01_agreed_at ?? $now,
                'asesi_signature' => $signature,
                'asesi_signed_at' => $assessment->asesi_signed_at ?? $now,
                'last_active_step' => 'ruang-uji',
            ]);
        });

        return redirect()->route('asesi.assessment.ruang-uji', $assessment)
            ->with('success', 'FR.AK01 berhasil disetujui dan ditandatangani.');
    }

    public function hub(Assessment $assessment): View|RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        if ($assessment->apl_status === 'REJECTED' || $assessment->admin_status === 'REJECTED') {
            return redirect()->route('asesi.assessment.apl', $assessment)
                ->with('error', 'Permohonan APL Anda pada jadwal asesmen ini telah ditolak. Anda tidak dapat mengikuti jadwal ini.');
        }

        if ($assessment->apl_status !== 'APPROVED') {
            return redirect()->route('asesi.assessment.apl', $assessment)
                ->with('warning', 'Ruang Uji baru dapat diakses setelah permohonan APL Anda disetujui.');
        }

        $assessment->load('schedule.scheme', 'schedule.assessors', 'iaResponses', 'assessor', 'ak03Item');
        $schedule = $assessment->schedule;
        $responses = $assessment->iaResponses->keyBy('instrument_code');

        // Ruang Uji Asesi is empty until the assessor has completed/approved FR.AK.01
        $assignedCodes = [];
        if ($assessment->hasAk01Completed()) {
            $assignedCodes = array_values(array_filter(
                $assessment->getAssignedInstruments(),
                fn ($c) => str_starts_with(strtoupper($c), 'IA')
            ));
        }

        $catalog = [
            'IA01' => [
                'code' => 'FR.IA.01',
                'title' => 'Ceklis Observasi Aktivitas di Tempat Kerja',
                'desc' => 'Daftar periksa observasi langsung terhadap KUK dan elemen kompetensi pada unit unjuk kerja.',
                'badge' => 'Observasi Praktik',
                'badge_color' => 'bg-blue-50 text-blue-700 border-blue-200',
                'has_download' => false,
                'download_url' => null,
                'action_url' => route('asesi.assessment.instrument.view', [$assessment, 'IA01']),
            ],
            'IA01B' => [
                'code' => 'FR.IA.01B',
                'title' => 'Lembar Pemeriksaan Teknis (Report Sheet)',
                'desc' => 'Pencatatan dan verifikasi hasil pengukuran parameter fisik komponen serta lembar kerja asesi.',
                'badge' => 'Report Sheet',
                'badge_color' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                'has_download' => false,
                'download_url' => null,
                'action_url' => route('asesi.assessment.instrument.view', [$assessment, 'IA01B']),
            ],
            'IA02' => [
                'code' => 'FR.IA.02',
                'title' => 'Tugas Praktik Demonstrasi (TPD)',
                'desc' => 'Penilaian tugas praktik unjuk kerja berdasarkan skenario dan instruksi demonstrasi standar di TUK.',
                'badge' => 'Praktik Demonstrasi',
                'badge_color' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'has_download' => false,
                'download_url' => null,
                'action_url' => route('asesi.assessment.instrument.view', [$assessment, 'IA02']),
            ],
            'IA03' => [
                'code' => 'FR.IA.03',
                'title' => 'Pertanyaan Mendukung Observasi (PMO / Lisan)',
                'desc' => 'Daftar pertanyaan lisan terstruktur untuk mengonfirmasi pemahaman teknis dan dasar teori asesi.',
                'badge' => 'Tanya Jawab',
                'badge_color' => 'bg-amber-50 text-amber-700 border-amber-200',
                'has_download' => false,
                'download_url' => null,
                'action_url' => route('asesi.assessment.instrument.view', [$assessment, 'IA03']),
            ],
            'IA05' => [
                'code' => 'FR.IA.05',
                'title' => 'Pertanyaan Tertulis Pilihan Ganda (CBT)',
                'desc' => 'Ujian teori pilihan ganda terstandar BNSP. Sistem CBT mengoreksi secara otomatis.',
                'badge' => 'Ujian CBT',
                'badge_color' => 'bg-purple-50 text-purple-700 border-purple-200',
                'has_download' => false,
                'download_url' => null,
                'action_url' => route('asesi.assessment.instrument.ia05', $assessment),
            ],
            'IA08' => [
                'code' => 'FR.IA.08',
                'title' => 'Ceklis Verifikasi Portofolio (CVP)',
                'desc' => 'Unggah dokumen bukti portofolio kerja/pelatihan Anda (1 - 3 berkas) untuk diverifikasi sesuai aturan bukti VATM.',
                'badge' => 'Portofolio',
                'badge_color' => 'bg-teal-50 text-teal-700 border-teal-200',
                'has_download' => false,
                'download_url' => null,
                'action_url' => route('asesi.assessment.instrument.view', [$assessment, 'IA08']),
            ],
            'IA09' => [
                'code' => 'FR.IA.09',
                'title' => 'Pertanyaan Wawancara (PW)',
                'desc' => 'Wawancara langsung tatap muka dengan Asesor untuk klarifikasi bukti portofolio dan pemahaman teknis unjuk kerja.',
                'badge' => 'Wawancara',
                'badge_color' => 'bg-violet-50 text-violet-700 border-violet-200',
                'has_download' => false,
                'download_url' => null,
                'action_url' => route('asesi.assessment.instrument.view', [$assessment, 'IA09']),
            ],
        ];

        $instruments = [];
        foreach ($assignedCodes as $code) {
            $codeUpper = strtoupper($code);
            $meta = $catalog[$codeUpper] ?? [
                'code' => "FR.{$codeUpper}",
                'title' => "Instrumen FR.{$codeUpper}",
                'desc' => "Pelaksanaan instrumen FR.{$codeUpper} pada jadwal asesmen kompetensi.",
                'badge' => "Instrumen {$codeUpper}",
                'badge_color' => 'bg-slate-50 text-slate-700 border-slate-200',
                'has_download' => false,
                'download_url' => null,
                'action_url' => route('asesi.assessment.instrument.view', [$assessment, $codeUpper]),
            ];

            $resp = $responses[$codeUpper] ?? null;

            if ($codeUpper === 'IA05') {
                $isDone = (bool) ($resp && ($resp->score !== null || $resp->asesi_signed_at !== null));
                $meta['status_label'] = ($isDone || $schedule->is_closed) ? '✓ Selesai CBT' : 'Ujian Teori CBT';
                $meta['status_badge_class'] = ($isDone || $schedule->is_closed) ? 'text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200' : 'text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-200';
                $meta['progress_percent'] = ($isDone || $schedule->is_closed) ? 100 : 0;
                $meta['progress_bar_color'] = ($isDone || $schedule->is_closed) ? 'bg-emerald-600' : 'bg-purple-600';
                $meta['progress_text'] = ($isDone || $schedule->is_closed) ? '1 / 1 Selesai' : '0 / 1 Belum Ujian';
                $meta['action_label'] = ($isDone || $schedule->is_closed) ? 'Lihat Hasil CBT' : 'Mulai Kerjakan CBT';
                $meta['action_button_class'] = ($isDone || $schedule->is_closed) ? 'bg-slate-800 hover:bg-slate-900 text-white' : 'bg-brand-600 hover:bg-brand-700 text-white';
            } elseif ($codeUpper === 'IA02') {
                $hasSigned = (bool) $resp?->asesi_signed_at || (bool) $resp?->assessor_signed_at;
                $meta['status_label'] = 'Praktik Demonstrasi';
                $meta['status_badge_class'] = 'text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200';
                $meta['progress_percent'] = 100;
                $meta['progress_bar_color'] = 'bg-emerald-600';
                $meta['progress_text'] = 'Instruksi Kerja Praktik';
                $meta['action_label'] = 'Lihat Praktik Demonstrasi';
                $meta['action_button_class'] = $schedule->is_closed ? 'bg-slate-800 hover:bg-slate-900 text-white' : 'bg-brand-600 hover:bg-brand-700 text-white';
            } elseif ($codeUpper === 'IA01') {
                $hasEvaluated = (bool) $resp?->assessor_signed_at || ! empty($resp?->assessor_notes);
                $meta['status_label'] = ($hasEvaluated || $schedule->is_closed) ? '✓ Telah Dievaluasi Asesor' : 'Observasi Praktik';
                $meta['status_badge_class'] = ($hasEvaluated || $schedule->is_closed) ? 'text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200' : 'text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200';
                $meta['progress_percent'] = ($hasEvaluated || $schedule->is_closed) ? 100 : 0;
                $meta['progress_bar_color'] = ($hasEvaluated || $schedule->is_closed) ? 'bg-emerald-600' : 'bg-slate-400';
                $meta['progress_text'] = ($hasEvaluated || $schedule->is_closed) ? '1 / 1 Telah Dievaluasi' : '0 / 1 Evaluasi Asesor';
                $meta['action_label'] = 'Lihat Evaluasi Asesor';
                $meta['action_button_class'] = ($hasEvaluated || $schedule->is_closed) ? 'bg-slate-800 hover:bg-slate-900 text-white' : 'bg-slate-700 hover:bg-slate-800 text-white';
            } elseif ($codeUpper === 'IA01B') {
                $hasEvidence = ! empty($resp?->data_json['evidence_image_path']);
                $isRevisionRequested = ! empty($resp?->data_json['revision_requested']) || ($resp?->data_json['revision_status'] ?? '') === 'REVISION_REQUESTED';
                $isSignedByAssessor = ! empty($resp?->assessor_signed_at);
                $isResubmitted = ($resp?->data_json['revision_status'] ?? '') === 'RESUBMITTED';

                if ($isRevisionRequested && ! $schedule->is_closed) {
                    $meta['status_label'] = '⚠️ Perlu Revisi Berkas';
                    $meta['status_badge_class'] = 'text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200';
                    $meta['progress_percent'] = 50;
                    $meta['progress_bar_color'] = 'bg-rose-500';
                    $meta['progress_text'] = 'Perlu Unggah Ulang';
                    $meta['action_label'] = 'Unggah Berkas Revisi';
                    $meta['action_button_class'] = 'bg-rose-600 hover:bg-rose-700 text-white';
                } elseif ($isResubmitted && ! $isSignedByAssessor) {
                    $meta['status_label'] = 'Revisi Menunggu Asesor';
                    $meta['status_badge_class'] = 'text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200';
                    $meta['progress_percent'] = 75;
                    $meta['progress_bar_color'] = 'bg-blue-600';
                    $meta['progress_text'] = 'Menunggu Evaluasi';
                    $meta['action_label'] = 'Lihat Lembar Pemeriksaan';
                    $meta['action_button_class'] = 'bg-brand-600 hover:bg-brand-700 text-white';
                } else {
                    $meta['status_label'] = ($hasEvidence || $schedule->is_closed) ? '✓ Lembar Fisik Diunggah' : 'Unggah Lembar Fisik';
                    $meta['status_badge_class'] = ($hasEvidence || $schedule->is_closed) ? 'text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200' : 'text-cyan-700 bg-cyan-50 px-2 py-0.5 rounded border border-cyan-200';
                    $meta['progress_percent'] = ($hasEvidence || $schedule->is_closed) ? 100 : 0;
                    $meta['progress_bar_color'] = ($hasEvidence || $schedule->is_closed) ? 'bg-emerald-600' : 'bg-blue-500';
                    $meta['progress_text'] = ($hasEvidence || $schedule->is_closed) ? '1 / 1 Terunggah' : '0 / 1 Belum Diunggah';
                    $meta['action_label'] = ($hasEvidence || $schedule->is_closed) ? 'Lihat Lembar Pemeriksaan' : 'Unggah Lembar Fisik';
                    $meta['action_button_class'] = ($hasEvidence || $schedule->is_closed) ? 'bg-slate-800 hover:bg-slate-900 text-white' : 'bg-brand-600 hover:bg-brand-700 text-white';
                }
            } elseif ($codeUpper === 'IA03') {
                $hasEvaluated = (bool) $resp?->assessor_signed_at || ! empty($resp?->assessor_notes);
                $meta['status_label'] = ($hasEvaluated || $schedule->is_closed) ? '✓ Telah Dievaluasi Asesor' : 'Tanya Jawab (PMO)';
                $meta['status_badge_class'] = ($hasEvaluated || $schedule->is_closed) ? 'text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200' : 'text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200';
                $meta['progress_percent'] = ($hasEvaluated || $schedule->is_closed) ? 100 : 0;
                $meta['progress_bar_color'] = ($hasEvaluated || $schedule->is_closed) ? 'bg-emerald-600' : 'bg-slate-400';
                $meta['progress_text'] = ($hasEvaluated || $schedule->is_closed) ? '1 / 1 Telah Dievaluasi' : '0 / 1 Evaluasi Asesor';
                $meta['action_label'] = 'Lihat Evaluasi Asesor';
                $meta['action_button_class'] = ($hasEvaluated || $schedule->is_closed) ? 'bg-slate-800 hover:bg-slate-900 text-white' : 'bg-slate-700 hover:bg-slate-800 text-white';
            } elseif ($codeUpper === 'IA08') {
                $portfolios = $resp?->data_json['portfolios'] ?? [];
                $hasPortfolios = ! empty($portfolios);
                $isSignedByAssessor = ! empty($resp?->assessor_signed_at);

                if ($isSignedByAssessor) {
                    $meta['status_label'] = '✓ Telah Diverifikasi Asesor';
                    $meta['status_badge_class'] = 'text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200';
                    $meta['progress_percent'] = 100;
                    $meta['progress_bar_color'] = 'bg-emerald-600';
                    $meta['progress_text'] = count($portfolios).' Berkas Diverifikasi';
                    $meta['action_label'] = 'Lihat Hasil Verifikasi';
                    $meta['action_button_class'] = 'bg-slate-800 hover:bg-slate-900 text-white';
                } elseif ($hasPortfolios) {
                    $meta['status_label'] = 'Menunggu Verifikasi Asesor';
                    $meta['status_badge_class'] = 'text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200';
                    $meta['progress_percent'] = 75;
                    $meta['progress_bar_color'] = 'bg-blue-600';
                    $meta['progress_text'] = count($portfolios).' Berkas Diunggah';
                    $meta['action_label'] = 'Lihat / Kelola Portofolio';
                    $meta['action_button_class'] = 'bg-brand-600 hover:bg-brand-700 text-white';
                } else {
                    $meta['status_label'] = 'Belum Diunggah Asesi';
                    $meta['status_badge_class'] = 'text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200';
                    $meta['progress_percent'] = 0;
                    $meta['progress_bar_color'] = 'bg-amber-500';
                    $meta['progress_text'] = '0 / 1-3 Berkas Portofolio';
                    $meta['action_label'] = 'Unggah Portofolio (1-3 Berkas)';
                    $meta['action_button_class'] = 'bg-brand-600 hover:bg-brand-700 text-white';
                }
            } elseif ($codeUpper === 'IA09') {
                $hasEvaluated = (bool) $resp?->assessor_signed_at;
                $meta['status_label'] = ($hasEvaluated || $schedule->is_closed) ? '✓ Selesai Wawancara' : 'Sesi Wawancara';
                $meta['status_badge_class'] = ($hasEvaluated || $schedule->is_closed) ? 'text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200' : 'text-violet-700 bg-violet-50 px-2 py-0.5 rounded border border-violet-200';
                $meta['progress_percent'] = ($hasEvaluated || $schedule->is_closed) ? 100 : 0;
                $meta['progress_bar_color'] = ($hasEvaluated || $schedule->is_closed) ? 'bg-emerald-600' : 'bg-slate-400';
                $meta['progress_text'] = ($hasEvaluated || $schedule->is_closed) ? '1 / 1 Telah Selesai' : '0 / 1 Tatap Muka Asesor';
                $meta['action_label'] = ($hasEvaluated || $schedule->is_closed) ? 'Lihat Hasil Wawancara' : 'Persiapan Wawancara';
                $meta['action_button_class'] = ($hasEvaluated || $schedule->is_closed) ? 'bg-slate-800 hover:bg-slate-900 text-white' : 'bg-slate-700 hover:bg-slate-800 text-white';
            } else {
                $hasEvaluated = (bool) $resp?->assessor_signed_at;
                $meta['status_label'] = $hasEvaluated ? '✓ Selesai' : 'Belum Selesai';
                $meta['status_badge_class'] = $hasEvaluated ? 'text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200' : 'text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200';
                $meta['progress_percent'] = $hasEvaluated ? 100 : 0;
                $meta['progress_bar_color'] = 'bg-slate-400';
                $meta['progress_text'] = $hasEvaluated ? '1 / 1 Selesai' : '0 / 1 Belum Selesai';
                $meta['action_label'] = 'Lihat Instrumen';
                $meta['action_button_class'] = 'bg-brand-600 hover:bg-brand-700 text-white';
            }

            $instruments[] = $meta;
        }

        // AK.03 Container Box (Umpan Balik Asesi) ditampilkan langsung di Ruang Uji
        if ($assessment->hasAk01Completed() || $assessment->canAsesiAccessAk03()) {
            $ak03Item = $assessment->ak03Item;
            $isAk03Unlocked = $assessment->canAsesiAccessAk03();

            if ($ak03Item) {
                $instruments[] = [
                    'code' => 'FR.AK.03',
                    'title' => 'FR.AK.03 Umpan Balik dan Catatan Asesmen',
                    'desc' => 'Kuesioner evaluasi dan umpan balik pelaksanaan asesmen dari asesi terhadap asesor dan TUK.',
                    'badge' => 'Umpan Balik',
                    'badge_color' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'status_label' => '✓ Umpan Balik Terkirim',
                    'status_badge_class' => 'text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200',
                    'progress_percent' => 100,
                    'progress_bar_color' => 'bg-emerald-600',
                    'progress_text' => '1 / 1 Terkirim',
                    'action_label' => 'Lihat Umpan Balik',
                    'action_button_class' => 'bg-slate-800 hover:bg-slate-900 text-white',
                    'action_url' => route('asesi.assessment.ak03', $assessment),
                    'is_locked' => false,
                ];
            } elseif ($isAk03Unlocked) {
                $instruments[] = [
                    'code' => 'FR.AK.03',
                    'title' => 'FR.AK.03 Umpan Balik dan Catatan Asesmen',
                    'desc' => 'Kuesioner evaluasi dan umpan balik pelaksanaan asesmen dari asesi terhadap asesor dan TUK.',
                    'badge' => 'Umpan Balik',
                    'badge_color' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'status_label' => 'Siap Diisi',
                    'status_badge_class' => 'text-emerald-700 bg-emerald-100 font-bold px-2 py-0.5 rounded border border-emerald-300 animate-pulse',
                    'progress_percent' => 0,
                    'progress_bar_color' => 'bg-emerald-500',
                    'progress_text' => '0 / 1 Siap Diisi',
                    'action_label' => 'Isi Umpan Balik Sekarang',
                    'action_button_class' => 'bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-md ring-2 ring-emerald-500/20',
                    'action_url' => route('asesi.assessment.ak03', $assessment),
                    'is_locked' => false,
                ];
            } else {
                $instruments[] = [
                    'code' => 'FR.AK.03',
                    'title' => 'FR.AK.03 Umpan Balik dan Catatan Asesmen',
                    'desc' => 'Kuesioner evaluasi dan umpan balik pelaksanaan asesmen dari asesi terhadap asesor dan TUK.',
                    'badge' => 'Umpan Balik',
                    'badge_color' => 'bg-slate-50 text-slate-600 border-slate-200',
                    'status_label' => '🔒 Terkunci',
                    'status_badge_class' => 'text-slate-500 bg-slate-100 px-2 py-0.5 rounded border border-slate-200',
                    'progress_percent' => 0,
                    'progress_bar_color' => 'bg-slate-300',
                    'progress_text' => 'Terkunci (Selesaikan Asesmen)',
                    'action_label' => 'Terkunci (Belum Aktif)',
                    'action_button_class' => 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed',
                    'action_url' => '#',
                    'is_locked' => true,
                ];
            }
        }

        return view('asesi.ruang-uji.hub', compact('assessment', 'schedule', 'instruments', 'responses'));
    }

    /**
     * Check if the candidate can access a specific instrument.
     * Requires the assessor to have completed FR.AK.01, and the instrument to be assigned.
     */
    private function canAsesiAccessInstrument(Assessment $assessment, string $code): bool
    {
        if (! $assessment->hasAk01Completed()) {
            return false;
        }

        return $assessment->hasAssignedInstrument($code);
    }

    public function downloadTemplate(Assessment $assessment, string $code, SchemeInstrumentService $instrumentService)
    {
        abort_if($assessment->asesi_id !== auth()->id() && auth()->user()->role !== 'ASESOR' && auth()->user()->role !== 'ADMIN_LSP', 403);

        if ($assessment->asesi_id === auth()->id() && ! $assessment->hasAssignedInstrument($code)) {
            return back()->with('error', 'Instrumen FR.'.strtoupper($code).' belum ditetapkan untuk asesmen ini.');
        }

        $path = $instrumentService->getInstrumentTemplatePath($assessment->schedule->scheme, $code);

        if (! $path || ! file_exists($path)) {
            return back()->with('error', 'File template PDF resmi untuk instrumen FR.'.strtoupper($code).' belum tersedia.');
        }

        $cleanSchemeName = str_replace(['/', '\\'], '-', $assessment->schedule->scheme->name);
        $filename = 'FR.'.strtoupper($code).' - '.$cleanSchemeName.'.pdf';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function ia05(Assessment $assessment): View|RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        if (! $this->canAsesiAccessInstrument($assessment, 'IA05')) {
            return redirect()->route('asesi.assessment.ruang-uji', $assessment)
                ->with('warning', 'Instrumen CBT (FR.IA.05) belum ditetapkan oleh asesor pada formulir persetujuan asesmen (FR.AK.01).');
        }

        $assessment->load('schedule.scheme', 'iaResponses');
        $questionIds = $assessment->schedule ? $assessment->schedule->getCbtQuestionIds() : [];
        $questions = ! empty($questionIds)
            ? QuestionBank::whereIn('id', $questionIds)->get()
            : QuestionBank::where('scheme_id', $assessment->schedule->scheme_id)->get();

        $existingResp = $assessment->iaResponses->firstWhere('instrument_code', 'IA05');
        $isSubmitted = (bool) ($existingResp && ($existingResp->asesi_signed_at !== null || $existingResp->score !== null)) || (bool) ($assessment->schedule?->is_closed);

        $draftAnswers = [];
        $draftDoubtful = [];
        if ($existingResp && ! empty($existingResp->data_json)) {
            $data = is_array($existingResp->data_json)
                ? $existingResp->data_json
                : (json_decode($existingResp->data_json, true) ?: []);

            if (isset($data['answers']) && is_array($data['answers'])) {
                $draftAnswers = $data['answers'];
                $draftDoubtful = $data['doubtful'] ?? [];
            } else {
                $draftAnswers = $data;
            }
        }

        return view('asesi.ruang-uji.ia05', compact('assessment', 'questions', 'existingResp', 'isSubmitted', 'draftAnswers', 'draftDoubtful'));
    }

    public function autosaveIa05(Assessment $assessment, Request $request): JsonResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        if ($assessment->schedule && $assessment->schedule->is_closed) {
            return response()->json(['error' => 'Jadwal telah ditutup.'], 403);
        }

        if ($assessment->apl_status !== 'APPROVED') {
            return response()->json(['error' => 'APL belum disetujui.'], 403);
        }

        // Do not overwrite if already submitted
        $existing = AssessmentIaResponse::where('assessment_id', $assessment->id)
            ->where('instrument_code', 'IA05')
            ->whereNotNull('asesi_signed_at')
            ->first();

        if ($existing) {
            return response()->json(['status' => 'already_submitted'], 200);
        }

        $answers = $request->input('answers', []);
        $doubtful = $request->input('doubtful', []);

        $response = AssessmentIaResponse::firstOrNew([
            'assessment_id' => $assessment->id,
            'instrument_code' => 'IA05',
        ]);

        $currentData = is_array($response->data_json)
            ? $response->data_json
            : (json_decode($response->data_json ?? '[]', true) ?: []);

        $currentData['answers'] = is_array($answers) ? $answers : [];
        $currentData['doubtful'] = is_array($doubtful) ? $doubtful : [];
        $currentData['status'] = 'DRAFT';

        $response->data_json = $currentData;
        $response->save();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Jawaban tersimpan otomatis.',
            'saved_at' => now()->toIso8601String(),
        ]);
    }

    public function submitIa05(Assessment $assessment, Request $request, AutoGradeService $grader): RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        if ($assessment->schedule && $assessment->schedule->is_closed) {
            return back()->with('error', 'Jadwal asesmen sudah resmi ditutup.');
        }

        if ($assessment->apl_status !== 'APPROVED') {
            return back()->with('error', 'Ujian CBT hanya dapat diikuti setelah permohonan APL Anda disetujui.');
        }

        // Idempotency: prevent resubmission or resetting score to zero on double click/refresh
        $existing = AssessmentIaResponse::where('assessment_id', $assessment->id)
            ->where('instrument_code', 'IA05')
            ->whereNotNull('asesi_signed_at')
            ->first();

        if ($existing) {
            return redirect()->route('asesi.assessment.ruang-uji', $assessment)
                ->with('info', "Jawaban ujian CBT (IA.05) Anda telah tersimpan sebelumnya dengan skor: {$existing->score}.");
        }

        if (! $assessment->hasAssignedInstrument('IA05')) {
            return redirect()->route('asesi.assessment.ruang-uji', $assessment)
                ->with('warning', 'Instrumen CBT (FR.IA.05) tidak ditetapkan untuk asesmen ini.');
        }

        $rawAnswers = $request->input('answers', []);
        $draftAnswers = [];
        $existingDraft = AssessmentIaResponse::where('assessment_id', $assessment->id)
            ->where('instrument_code', 'IA05')
            ->first();

        if ($existingDraft && ! empty($existingDraft->data_json)) {
            $data = is_array($existingDraft->data_json)
                ? $existingDraft->data_json
                : (json_decode($existingDraft->data_json, true) ?: []);
            $draftAnswers = $data['answers'] ?? (isset($data[0]) ? [] : $data);
        }

        $answers = array_replace(is_array($draftAnswers) ? $draftAnswers : [], is_array($rawAnswers) ? $rawAnswers : []);

        $result = DB::transaction(function () use ($assessment, $answers, $grader) {
            $graded = $grader->grade($assessment, $answers);
            $assessment->update(['last_active_step' => 'ruang-uji']);

            return $graded;
        });

        return redirect()->route('asesi.assessment.ruang-uji', $assessment)
            ->with('success', 'Jawaban CBT berhasil dikirim.');
    }

    public function iaView(Assessment $assessment, $code, SchemeInstrumentService $instrumentService): View|RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        $codeUpper = strtoupper($code);
        if (! $this->canAsesiAccessInstrument($assessment, $codeUpper)) {
            return redirect()->route('asesi.assessment.ruang-uji', $assessment)
                ->with('warning', "Instrumen FR.{$codeUpper} belum ditetapkan oleh asesor pada formulir persetujuan asesmen (FR.AK.01).");
        }

        $assessment->load('schedule.scheme.competencyUnits.elements.kuks', 'iaResponses', 'assessor');
        $response = $assessment->iaResponses->firstWhere('instrument_code', $codeUpper);
        $scheme = $assessment->schedule->scheme;
        $reportSheetItems = $instrumentService->getReportSheetItems($scheme);
        $tpdData = $instrumentService->getTpdData($scheme);
        $pmoQuestions = $instrumentService->getPmoQuestions($scheme);
        $units = $scheme->competencyUnits;

        $interviewQuestions = collect();
        if ($codeUpper === 'IA09') {
            $interviewQuestions = InterviewQuestion::where('scheme_id', $scheme->id)
                ->active()
                ->ordered()
                ->with('competencyUnit')
                ->get();
        }

        return view('asesi.ruang-uji.ia-view', compact('assessment', 'code', 'codeUpper', 'response', 'scheme', 'reportSheetItems', 'tpdData', 'pmoQuestions', 'units', 'interviewQuestions'));
    }

    public function uploadPortfolio(Assessment $assessment, Request $request): RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        if ($assessment->schedule && $assessment->schedule->is_closed) {
            return back()->with('error', 'Jadwal asesmen sudah resmi ditutup.');
        }

        if (! $assessment->hasAssignedInstrument('IA08')) {
            return redirect()->route('asesi.assessment.ruang-uji', $assessment)
                ->with('warning', 'Instrumen FR.IA.08 tidak ditetapkan untuk asesmen ini.');
        }

        $response = AssessmentIaResponse::firstOrNew([
            'assessment_id' => $assessment->id,
            'instrument_code' => 'IA08',
        ]);

        if (! empty($response->assessor_signed_at)) {
            return back()->with('error', 'Penilaian verifikasi portofolio sudah disahkan oleh asesor dan tidak dapat diubah lagi.');
        }

        $dataJson = is_array($response->data_json)
            ? $response->data_json
            : (json_decode($response->data_json ?? '[]', true) ?: []);

        $portfolios = $dataJson['portfolios'] ?? [];
        if (count($portfolios) >= 3) {
            return back()->with('error', 'Maksimal 3 dokumen bukti portofolio yang dapat diunggah sesuai ketentuan asesmen.');
        }

        $request->validate([
            'portfolio_type' => ['required', 'string', 'max:150'],
            'portfolio_title' => ['required', 'string', 'max:255'],
            'portfolio_file' => ['required', 'file', 'mimes:pdf,jpeg,jpg,png,webp', 'max:15360'],
        ], [
            'portfolio_type.required' => 'Pilih jenis dokumen portofolio.',
            'portfolio_title.required' => 'Tuliskan nama/deskripsi ringkas dokumen portofolio.',
            'portfolio_file.required' => 'Pilih berkas dokumen portofolio terlebih dahulu.',
            'portfolio_file.mimes' => 'Format berkas harus berupa dokumen PDF (.pdf) atau gambar (JPG/PNG).',
            'portfolio_file.max' => 'Ukuran berkas portofolio maksimal 15 MB.',
        ]);

        $path = $request->file('portfolio_file')->store('ia08_evidence', 'local');

        $portfolios[] = [
            'id' => (string) Str::uuid(),
            'type' => $request->input('portfolio_type'),
            'title' => $request->input('portfolio_title'),
            'path' => $path,
            'original_name' => $request->file('portfolio_file')->getClientOriginalName(),
            'file_size' => $request->file('portfolio_file')->getSize(),
            'uploaded_at' => now()->toIso8601String(),
        ];

        $dataJson['portfolios'] = $portfolios;
        $response->data_json = $dataJson;

        $signature = $assessment->asesi_signature ?? auth()->user()->profile?->signature_data;
        if ($signature && empty($response->asesi_signature)) {
            $response->asesi_signature = $signature;
            $response->asesi_signed_at = now();
        }

        $response->save();

        return back()->with('success', 'Dokumen portofolio berhasil diunggah.');
    }

    public function deletePortfolio(Assessment $assessment, string $portfolioId): RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        if ($assessment->schedule && $assessment->schedule->is_closed) {
            return back()->with('error', 'Jadwal asesmen sudah resmi ditutup.');
        }

        $response = AssessmentIaResponse::where('assessment_id', $assessment->id)
            ->where('instrument_code', 'IA08')
            ->first();

        if (! $response) {
            return back()->with('error', 'Data respons instrumen IA08 tidak ditemukan.');
        }

        if (! empty($response->assessor_signed_at)) {
            return back()->with('error', 'Penilaian verifikasi portofolio sudah disahkan oleh asesor dan tidak dapat diubah lagi.');
        }

        $dataJson = is_array($response->data_json)
            ? $response->data_json
            : (json_decode($response->data_json ?? '[]', true) ?: []);

        $portfolios = $dataJson['portfolios'] ?? [];
        $itemToDelete = collect($portfolios)->firstWhere('id', $portfolioId);

        if ($itemToDelete && ! empty($itemToDelete['path'])) {
            Storage::disk('local')->delete($itemToDelete['path']);
        }

        $updatedPortfolios = array_values(array_filter($portfolios, fn ($p) => ($p['id'] ?? '') !== $portfolioId));
        $dataJson['portfolios'] = $updatedPortfolios;
        $response->data_json = $dataJson;
        $response->save();

        return back()->with('success', 'Dokumen portofolio berhasil dihapus.');
    }

    public function submitFeedback(Assessment $assessment, $code, Request $request): RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        if ($assessment->schedule && $assessment->schedule->is_closed) {
            return back()->with('error', 'Jadwal asesmen sudah resmi ditutup.');
        }

        $codeUpper = strtoupper($code);
        if (! $assessment->hasAssignedInstrument($codeUpper)) {
            return redirect()->route('asesi.assessment.ruang-uji', $assessment)
                ->with('warning', "Instrumen FR.{$codeUpper} tidak ditetapkan untuk asesmen ini.");
        }

        $response = AssessmentIaResponse::firstOrNew([
            'assessment_id' => $assessment->id,
            'instrument_code' => $codeUpper,
        ]);

        $dataJson = is_array($response->data_json)
            ? $response->data_json
            : (json_decode($response->data_json ?? '[]', true) ?: []);

        if ($request->hasFile('evidence_image')) {
            $request->validate([
                'evidence_image' => 'required|file|mimes:jpeg,jpg,png,webp,pdf|max:10240',
            ]);
            $folder = strtolower($code) === 'ia01b' ? 'ia01b_evidence' : 'ia02_evidence';
            $path = $request->file('evidence_image')->store($folder, 'local');

            $isRevision = ! empty($dataJson['revision_requested']) || ($dataJson['revision_status'] ?? '') === 'REVISION_REQUESTED';
            if ($isRevision && ! empty($dataJson['evidence_image_path'])) {
                $history = $dataJson['evidence_history'] ?? [];
                $history[] = [
                    'path' => $dataJson['evidence_image_path'],
                    'original_name' => $dataJson['evidence_original_name'] ?? 'Berkas Sebelumnya',
                    'uploaded_at' => $dataJson['evidence_uploaded_at'] ?? null,
                    'revision_notes' => $dataJson['revision_notes'] ?? null,
                    'replaced_at' => now()->toIso8601String(),
                ];
                $dataJson['evidence_history'] = $history;
            }

            $dataJson['evidence_image_path'] = $path;
            $dataJson['evidence_uploaded_at'] = now()->toIso8601String();
            $dataJson['evidence_original_name'] = $request->file('evidence_image')->getClientOriginalName();

            if ($isRevision) {
                $dataJson['revision_requested'] = false;
                $dataJson['revision_status'] = 'RESUBMITTED';
                $dataJson['revision_resubmitted_at'] = now()->toIso8601String();
            }
        }

        $response->data_json = $dataJson;

        if ($request->filled('asesi_feedback')) {
            $response->asesi_feedback = $request->input('asesi_feedback');
        }

        $request->validate([
            'signature_data' => ['nullable', 'string', new ValidSignature],
            'asesi_signature' => ['nullable', 'string', new ValidSignature],
        ]);

        $signature = $request->input('signature_data')
            ?? $request->input('asesi_signature')
            ?? $assessment->asesi_signature
            ?? auth()->user()->profile?->signature_data;
        if ($signature) {
            $response->asesi_signature = $signature;
            $response->asesi_signed_at = now();
            if (empty($assessment->asesi_signature)) {
                $assessment->update([
                    'asesi_signature' => $signature,
                    'asesi_signed_at' => now(),
                ]);
            }
        }

        $response->save();

        return back()->with('success', 'Data tanggapan instrumen FR.'.$codeUpper.' berhasil disimpan.');
    }

    public function uploadEvidence(Assessment $assessment, $code, Request $request): RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        if ($assessment->schedule && $assessment->schedule->is_closed) {
            return back()->with('error', 'Jadwal asesmen sudah resmi ditutup.');
        }

        $codeUpper = strtoupper($code);
        if (! $assessment->hasAssignedInstrument($codeUpper)) {
            return redirect()->route('asesi.assessment.ruang-uji', $assessment)
                ->with('warning', "Instrumen FR.{$codeUpper} tidak ditetapkan untuk asesmen ini.");
        }

        $request->validate([
            'evidence_image' => 'required|file|mimes:pdf,jpeg,jpg,png,webp|max:15360',
        ], [
            'evidence_image.required' => 'Pilih berkas dokumen PDF Report Sheet terlebih dahulu.',
            'evidence_image.mimes' => 'Format berkas harus berupa dokumen PDF (.pdf).',
            'evidence_image.max' => 'Ukuran berkas maksimal 15 MB.',
        ]);

        $codeUpper = strtoupper($code);
        $folder = strtolower($code) === 'ia01b' ? 'ia01b_evidence' : 'ia02_evidence';
        $path = $request->file('evidence_image')->store($folder, 'local');

        $response = AssessmentIaResponse::firstOrNew([
            'assessment_id' => $assessment->id,
            'instrument_code' => $codeUpper,
        ]);

        $dataJson = is_array($response->data_json)
            ? $response->data_json
            : (json_decode($response->data_json ?? '[]', true) ?: []);

        $isRevision = ! empty($dataJson['revision_requested']) || ($dataJson['revision_status'] ?? '') === 'REVISION_REQUESTED';

        if ($isRevision && ! empty($dataJson['evidence_image_path'])) {
            $history = $dataJson['evidence_history'] ?? [];
            $history[] = [
                'path' => $dataJson['evidence_image_path'],
                'original_name' => $dataJson['evidence_original_name'] ?? 'Berkas Sebelumnya',
                'uploaded_at' => $dataJson['evidence_uploaded_at'] ?? null,
                'revision_notes' => $dataJson['revision_notes'] ?? null,
                'replaced_at' => now()->toIso8601String(),
            ];
            $dataJson['evidence_history'] = $history;
        }

        $dataJson['evidence_image_path'] = $path;
        $dataJson['evidence_uploaded_at'] = now()->toIso8601String();
        $dataJson['evidence_original_name'] = $request->file('evidence_image')->getClientOriginalName();

        if ($isRevision) {
            $dataJson['revision_requested'] = false;
            $dataJson['revision_status'] = 'RESUBMITTED';
            $dataJson['revision_resubmitted_at'] = now()->toIso8601String();
        }

        $response->data_json = $dataJson;
        $response->save();

        $successMessage = $isRevision
            ? 'Berkas revisi lembar Report Sheet berhasil diunggah ulang dan siap diperiksa kembali oleh asesor.'
            : 'Foto bukti lembar kerja fisik berhasil diunggah.';

        return back()->with('success', $successMessage);
    }

    public function finish(Assessment $assessment): RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        if ($assessment->schedule && $assessment->schedule->is_closed) {
            return back()->with('error', 'Jadwal asesmen sudah resmi ditutup.');
        }

        DB::transaction(function () use ($assessment) {
            $assessment->update([
                'status' => 'COMPLETED',
                'completed_at' => now(),
            ]);
        });

        return redirect()->route('asesi.assessment.ak03', $assessment)
            ->with('success', 'Asesmen teknis selesai. Harap mengisi formulir FR.AK.03 (Umpan Balik Asesi).');
    }

    public function showAk03(Assessment $assessment): View|RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        if (! $assessment->canAsesiAccessAk03()) {
            return redirect()->route('asesi.assessment.ruang-uji', $assessment)
                ->with('warning', 'Formulir FR.AK.03 (Umpan Balik Asesi) masih terkunci. Anda harus menyelesaikan seluruh rangkaian asesmen di Ruang Uji terlebih dahulu.');
        }

        $assessment->load('schedule.scheme', 'assessor', 'ak03Item');
        $ak03 = $assessment->ak03Item;

        return view('asesi.ruang-uji.ak03', compact('assessment', 'ak03'));
    }

    public function submitAk03(Assessment $assessment, Request $request): RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);

        if ($assessment->ak03Item) {
            return redirect()->route('asesi.dashboard')
                ->with('info', 'Formulir FR.AK.03 Umpan Balik Asesi sudah pernah dikirim dan tidak dapat diubah kembali.');
        }

        $validated = $request->validate([
            'ratings' => 'required|array|min:10',
            'ratings.*.value' => 'required|in:YA,TIDAK',
            'ratings.*.comment' => 'nullable|string|max:500',
            'general_notes' => 'nullable|string|max:1000',
        ], [
            'ratings.required' => 'Seluruh butir evaluasi FR.AK.03 wajib diisi.',
            'ratings.min' => 'Seluruh 10 butir pernyataan evaluasi wajib dipilih.',
            'ratings.*.value.required' => 'Setiap butir pertanyaan evaluasi wajib dipilih (Ya atau Tidak).',
        ]);

        if (! $assessment->canAsesiAccessAk03()) {
            return redirect()->route('asesi.assessment.ruang-uji', $assessment)
                ->with('error', 'Formulir FR.AK.03 (Umpan Balik Asesi) belum dapat dikirim karena rangkaian asesmen belum selesai.');
        }

        DB::transaction(function () use ($assessment, $validated) {
            app(AkWorkflowService::class)->submitAk03Feedback($assessment, $validated['ratings'], $validated['general_notes'] ?? null);
        });

        return redirect()->route('asesi.dashboard')
            ->with('success', 'Formulir FR.AK.03 Umpan Balik Asesi berhasil dikirim.');
    }

    public function showAppeal(Assessment $assessment): View
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);
        abort_unless($assessment->canAppeal(), 403, 'Akses pengajuan banding tidak tersedia atau tidak diizinkan untuk asesi ini.');

        $assessment->load('schedule.scheme.competencyUnits', 'assessor', 'appeals');
        $units = $assessment->schedule->scheme->competencyUnits;
        $appeal = $assessment->latestAppeal;

        $workflowService = app(AkWorkflowService::class);
        $appealWindowDays = $workflowService->getAppealWindowDays();
        $baseDate = $workflowService->getAppealBaseDate($assessment);
        $deadline = $workflowService->calculateAppealDeadline($baseDate);
        $isWithinDeadline = $workflowService->isWithinAppealDeadline($assessment);

        return view('asesi.ruang-uji.ak04-appeal', compact('assessment', 'units', 'appeal', 'appealWindowDays', 'deadline', 'isWithinDeadline'));
    }

    public function submitAppeal(Assessment $assessment, Request $request): RedirectResponse
    {
        abort_if($assessment->asesi_id !== auth()->id(), 403);
        abort_unless($assessment->canAppeal(), 403, 'Akses pengajuan banding tidak tersedia atau tidak diizinkan untuk asesi ini.');

        if ($assessment->appeals()->exists()) {
            return redirect()->route('asesi.dashboard')
                ->with('info', 'Permohonan banding asesmen (FR.AK.04) sudah pernah diajukan dan tidak dapat diubah kembali.');
        }

        $request->validate([
            'appeal_reasons' => 'required|string|min:10',
            'contested_units' => 'nullable|array',
            'is_process_explained' => 'nullable',
            'is_discussed_with_assessor' => 'nullable',
            'involves_other_person' => 'nullable',
            'other_person_name' => 'nullable|string|max:255',
        ]);

        $isProcessExplained = $request->has('is_process_explained')
            ? filter_var($request->input('is_process_explained'), FILTER_VALIDATE_BOOLEAN)
            : true;
        $isDiscussedWithAssessor = $request->has('is_discussed_with_assessor')
            ? filter_var($request->input('is_discussed_with_assessor'), FILTER_VALIDATE_BOOLEAN)
            : true;
        $involvesOtherPerson = $request->has('involves_other_person')
            ? filter_var($request->input('involves_other_person'), FILTER_VALIDATE_BOOLEAN)
            : false;
        $otherPersonName = $request->input('other_person_name');
        $appealReasons = $request->input('appeal_reasons');
        $contestedUnits = $request->input('contested_units', []);

        DB::transaction(function () use ($assessment, $appealReasons, $contestedUnits, $isProcessExplained, $isDiscussedWithAssessor, $involvesOtherPerson, $otherPersonName) {
            app(AkWorkflowService::class)->submitAppeal(
                $assessment,
                $appealReasons,
                $contestedUnits,
                null,
                $isProcessExplained,
                $isDiscussedWithAssessor,
                $involvesOtherPerson,
                $otherPersonName
            );
        });

        return redirect()->route('asesi.dashboard')
            ->with('success', 'Pengajuan Banding Asesmen (FR.AK.04) berhasil dikirimkan ke Admin LSP.');
    }
}
