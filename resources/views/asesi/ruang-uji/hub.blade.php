@extends('layouts.app')

@section('title', 'Ruang Uji Kompetensi - LSP PrO Indo')

@section('content')
<div class="max-w-5xl mx-auto py-6 space-y-6">
    {{-- Stepper Component --}}
    <x-progress-stepper currentStep="{{ $assessment->last_active_step ?? 'ruang-uji' }}" :assessment="$assessment" />

    {{-- Header Info --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="h-1.5 w-full flex">
            <div class="bg-[#1E2B6E] w-[65%]"></div>
            <div class="bg-[#CE181E] w-[35%]"></div>
        </div>
        <div class="p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-[#1E2B6E] text-white uppercase tracking-wider font-mono">
                        Ruang Uji Asesi
                    </span>
                    <span class="text-xs font-mono font-bold text-slate-500">
                        {{ $assessment->schedule->code }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-2 font-sans">{{ $assessment->schedule->scheme->name }}</h1>
                <p class="text-xs text-slate-600 mt-1">
                    TUK: <span class="font-bold text-slate-800">{{ $assessment->schedule->tuk_name }}</span> | 
                    Asesor: <span class="font-bold text-slate-800">{{ $assessment->assessor?->full_name ?? 'Tim Asesor LSP' }}</span>
                </p>
            </div>

            @if($assessment->status === 'COMPLETED')
                <div class="px-4 py-2 bg-emerald-50 border border-emerald-300 rounded-lg text-emerald-800 text-xs font-bold uppercase tracking-wider flex items-center gap-2 shadow-xs shrink-0">
                    <span>✓ Asesmen Telah Selesai</span>
                </div>
            @endif
        </div>
    </div>

    @php
        $selectedInstruments = $selectedInstruments ?? $assessment->schedule->getEffectiveInstruments();
        $responses = $responses ?? $assessment->iaResponses->keyBy('instrument_code');
        $scheme = $assessment->schedule->scheme;
    @endphp

    {{-- Petunjuk Alur Ruang Uji --}}
    <div class="bg-blue-50 border-2 border-blue-900/20 rounded-xl p-4 text-xs text-slate-700 leading-relaxed shadow-sm">
        <div class="flex items-start gap-3">
            <span class="text-xl">ℹ️</span>
            <div>
                <span class="font-bold text-slate-900">Petunjuk Ruang Uji Kompetensi:</span>
                <p class="mt-0.5">
                    1. <strong>Instrumen Mandiri (IA.05 & IA.02):</strong> Dikerjakan langsung oleh Anda. Pada FR.IA.02, silakan unduh template PDF demonstrasi, selesaikan tugas di lembar fisik di TUK, lalu foto dan unggah bukti kertasnya. Pada FR.IA.05, kerjakan ujian teori CBT secara langsung.<br>
                    2. <strong>Instrumen Observasi (IA.01, IA.01B, IA.03):</strong> Dinilai langsung oleh Asesor di bengkel TUK. Anda bertugas memeriksa ringkasan hasil penilaian yang direkam asesor, memberikan komentar umpan balik, dan menandatangani secara digital.
                </p>
            </div>
        </div>
    </div>

    {{-- SEKSI 1: INSTRUMEN DIKERJAKAN MANDIRI OLEH ASESI --}}
    <div class="space-y-3">
        <div class="flex items-center gap-2 border-b-2 border-black pb-2">
            <span class="w-3 h-3 rounded-full bg-[#CE181E]"></span>
            <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">
                1. Instrumen Dikerjakan Mandiri oleh Asesi
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- FR.IA.05 CBT --}}
            @if(in_array('IA05', $selectedInstruments))
                @php
                    $ia05Resp = $responses['IA05'] ?? null;
                    $isDone05 = (bool) $ia05Resp;
                    $passingGrade = $scheme->passing_grade_theory ?? 75;
                    $isPassed05 = $isDone05 && ($ia05Resp->score >= $passingGrade);
                @endphp
                <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] p-5 flex flex-col justify-between hover:translate-x-0.5 hover:-translate-y-0.5 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider {{ $isDone05 ? ($isPassed05 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300') : 'bg-indigo-100 text-indigo-800 border border-indigo-200' }}">
                                {{ $isDone05 ? ($isPassed05 ? '✓ Selesai CBT (LULUS)' : '✓ Selesai CBT (BELUM LULUS)') : 'Dikerjakan Asesi' }}
                            </span>
                            @if($isDone05)
                                <span class="text-xs font-black font-mono text-slate-800">Skor: {{ $ia05Resp->score }}%</span>
                            @else
                                <span class="text-[11px] font-mono text-slate-500">Min. {{ $passingGrade }}%</span>
                            @endif
                        </div>
                        <h3 class="text-base font-bold text-slate-900">FR.IA.05. Pertanyaan Tertulis (CBT)</h3>
                        <p class="text-xs text-slate-600 mt-1">Ujian teori pilihan ganda terstandar. Sistem mengoreksi secara otomatis.</p>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-200">
                        <a href="{{ route('asesi.assessment.instrument.ia05', $assessment) }}" class="inline-flex items-center justify-center w-full px-4 py-2.5 bg-[#1E2B6E] hover:bg-[#152055] text-white text-xs font-bold rounded-lg border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] transition">
                            {{ $isDone05 ? 'Tinjau Hasil Ujian CBT →' : 'Mulai Kerjakan Ujian Teori (CBT) →' }}
                        </a>
                    </div>
                </div>
            @endif

            {{-- FR.IA.02 Tugas Praktik Demonstrasi --}}
            @if(in_array('IA02', $selectedInstruments))
                @php
                    $ia02Resp = $responses['IA02'] ?? null;
                    $hasEvidence02 = ! empty($ia02Resp?->data_json['evidence_image_path']);
                    $hasSigned02 = (bool) $ia02Resp?->asesi_signed_at;
                @endphp
                <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] p-5 flex flex-col justify-between hover:translate-x-0.5 hover:-translate-y-0.5 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider {{ $hasEvidence02 ? ($hasSigned02 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-blue-100 text-blue-800 border border-blue-300') : 'bg-amber-100 text-amber-800 border border-amber-300' }}">
                                {{ $hasEvidence02 ? ($hasSigned02 ? '✓ Selesai & Ditandatangani' : '✓ Lembar Fisik Diunggah') : 'Paper-Based (Wajib Foto Bukti)' }}
                            </span>
                            <span class="text-[11px] font-semibold text-slate-500">Unjuk Kerja</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900">FR.IA.02. Tugas Praktik Demonstrasi (TPD)</h3>
                        <p class="text-xs text-slate-600 mt-1">Unduh lembar tugas PDF, selesaikan tugas unjuk kerja di bengkel TUK, lalu unggah foto lembar kerja fisik.</p>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-200 flex flex-col sm:flex-row gap-2">
                        <a href="{{ route('asesi.assessment.instrument.download-template', [$assessment, 'IA02']) }}" class="inline-flex items-center justify-center px-3 py-2 bg-white hover:bg-slate-50 text-slate-800 text-xs font-bold rounded-lg border border-slate-300 transition shrink-0" title="Unduh Lembar Soal & Template PDF Resmi BNSP">
                            📥 Unduh PDF
                        </a>
                        <a href="{{ route('asesi.assessment.instrument.view', [$assessment, 'IA02']) }}" class="inline-flex items-center justify-center flex-1 px-4 py-2 bg-[#CE181E] hover:bg-[#a61318] text-white text-xs font-bold rounded-lg border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] transition">
                            {{ $hasEvidence02 ? 'Buka Bukti Fisik & Tanda Tangan →' : 'Unggah Foto Bukti Kertas →' }}
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- SEKSI 2: INSTRUMEN PENILAIAN ASESOR (VIEW, FEEDBACK & SIGN) --}}
    <div class="space-y-3 pt-2">
        <div class="flex items-center gap-2 border-b-2 border-black pb-2">
            <span class="w-3 h-3 rounded-full bg-[#1E2B6E]"></span>
            <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">
                2. Instrumen Penilaian Asesor (Tinjau Hasil, Umpan Balik & Tanda Tangan)
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- FR.IA.01 Ceklis Observasi Praktik --}}
            @if(in_array('IA01', $selectedInstruments))
                @php
                    $ia01Resp = $responses['IA01'] ?? null;
                    $hasEvaluated01 = (bool) $ia01Resp?->assessor_signed_at || ! empty($ia01Resp?->data_json['kuks']);
                    $hasSigned01 = (bool) $ia01Resp?->asesi_signed_at;
                @endphp
                <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] p-5 flex flex-col justify-between hover:translate-x-0.5 hover:-translate-y-0.5 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider {{ $hasSigned01 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : ($hasEvaluated01 ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-700 border border-slate-300') }}">
                                {{ $hasSigned01 ? '✓ Sudah Ditandatangani' : ($hasEvaluated01 ? 'Menunggu Tanda Tangan' : 'Menunggu Penilaian Asesor') }}
                            </span>
                            <span class="text-[11px] font-semibold text-slate-500">Dinilai Asesor</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900">FR.IA.01. Ceklis Observasi Aktivitas Praktik</h3>
                        <p class="text-xs text-slate-600 mt-1">Hasil pengamatan langsung unjuk kerja seluruh Kriteria Unjuk Kerja (KUK) oleh asesor.</p>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-200 flex flex-col sm:flex-row gap-2">
                        <a href="{{ route('asesi.assessment.instrument.download-template', [$assessment, 'IA01']) }}" class="inline-flex items-center justify-center px-3 py-2 bg-white hover:bg-slate-50 text-slate-800 text-xs font-bold rounded-lg border border-slate-300 transition shrink-0" title="Unduh Format Ceklis Observasi PDF">
                            📥 PDF
                        </a>
                        <a href="{{ route('asesi.assessment.instrument.view', [$assessment, 'IA01']) }}" class="inline-flex items-center justify-center flex-1 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-lg border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] transition">
                            {{ $hasSigned01 ? 'Lihat Ceklis Observasi →' : 'Tinjau Hasil & Tanda Tangan →' }}
                        </a>
                    </div>
                </div>
            @endif

            {{-- FR.IA.01B Report Sheet --}}
            @if(in_array('IA01B', $selectedInstruments))
                @php
                    $ia01bResp = $responses['IA01B'] ?? null;
                    $hasEvaluated01b = (bool) $ia01bResp?->assessor_signed_at || ! empty($ia01bResp?->data_json);
                    $hasSigned01b = (bool) $ia01bResp?->asesi_signed_at;
                @endphp
                <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] p-5 flex flex-col justify-between hover:translate-x-0.5 hover:-translate-y-0.5 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider {{ $hasSigned01b ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : ($hasEvaluated01b ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-700 border border-slate-300') }}">
                                {{ $hasSigned01b ? '✓ Sudah Ditandatangani' : ($hasEvaluated01b ? 'Menunggu Tanda Tangan' : 'Menunggu Verifikasi Asesor') }}
                            </span>
                            <span class="text-[11px] font-semibold text-slate-500">Pengukuran Teknis</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900">FR.IA.01B. Lembar Laporan Pemeriksaan (Report Sheet)</h3>
                        <p class="text-xs text-slate-600 mt-1">Data hasil pengukuran teknis komponen uji dan verifikasi kesesuaian standar spesifikasi servis.</p>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-200 flex flex-col sm:flex-row gap-2">
                        <a href="{{ route('asesi.assessment.instrument.download-template', [$assessment, 'IA01B']) }}" class="inline-flex items-center justify-center px-3 py-2 bg-white hover:bg-slate-50 text-slate-800 text-xs font-bold rounded-lg border border-slate-300 transition shrink-0" title="Unduh Format Report Sheet PDF">
                            📥 PDF
                        </a>
                        <a href="{{ route('asesi.assessment.instrument.view', [$assessment, 'IA01B']) }}" class="inline-flex items-center justify-center flex-1 px-4 py-2 bg-[#1E2B6E] hover:bg-[#152055] text-white text-xs font-bold rounded-lg border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] transition">
                            {{ $hasSigned01b ? 'Lihat Hasil Pengukuran →' : 'Tinjau Report Sheet & Tanda Tangan →' }}
                        </a>
                    </div>
                </div>
            @endif

            {{-- FR.IA.03 Pertanyaan Mendukung Observasi (PMO) --}}
            @if(in_array('IA03', $selectedInstruments))
                @php
                    $ia03Resp = $responses['IA03'] ?? null;
                    $hasEvaluated03 = (bool) $ia03Resp?->assessor_signed_at || ! empty($ia03Resp?->data_json['questions']);
                    $hasSigned03 = (bool) $ia03Resp?->asesi_signed_at;
                @endphp
                <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] p-5 flex flex-col justify-between hover:translate-x-0.5 hover:-translate-y-0.5 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider {{ $hasSigned03 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : ($hasEvaluated03 ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-slate-100 text-slate-700 border border-slate-300') }}">
                                {{ $hasSigned03 ? '✓ Sudah Ditandatangani' : ($hasEvaluated03 ? 'Menunggu Tanda Tangan' : 'Menunggu Sesi Tanya Jawab') }}
                            </span>
                            <span class="text-[11px] font-semibold text-slate-500">Tanya Jawab Lisan</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900">FR.IA.03. Pertanyaan Mendukung Observasi (PMO)</h3>
                        <p class="text-xs text-slate-600 mt-1">Hasil klarifikasi teknis dan tanya jawab lisan saat proses observasi demonstrasi.</p>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-200 flex flex-col sm:flex-row gap-2">
                        <a href="{{ route('asesi.assessment.instrument.download-template', [$assessment, 'IA03']) }}" class="inline-flex items-center justify-center px-3 py-2 bg-white hover:bg-slate-50 text-slate-800 text-xs font-bold rounded-lg border border-slate-300 transition shrink-0" title="Unduh Soal Tanya Jawab PMO PDF">
                            📥 PDF
                        </a>
                        <a href="{{ route('asesi.assessment.instrument.view', [$assessment, 'IA03']) }}" class="inline-flex items-center justify-center flex-1 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-lg border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] transition">
                            {{ $hasSigned03 ? 'Lihat Tanya Jawab PMO →' : 'Tinjau Tanya Jawab & Tanda Tangan →' }}
                        </a>
                    </div>
                </div>
            @endif

            {{-- FR.AK.01 Persetujuan Asesmen (Jika Termasuk Dalam Jadwal) --}}
            @if(in_array('AK01', $selectedInstruments))
                @php
                    $hasSignedAk01 = ! empty($assessment->asesi_signed_at);
                @endphp
                <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] p-5 flex flex-col justify-between hover:translate-x-0.5 hover:-translate-y-0.5 transition">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider {{ $hasSignedAk01 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300' }}">
                                {{ $hasSignedAk01 ? '✓ Sudah Disetujui' : 'Menunggu Persetujuan' }}
                            </span>
                            <span class="text-[11px] font-semibold text-slate-500">Persetujuan & Kerahasiaan</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900">FR.AK.01. Persetujuan Asesmen & Kerahasiaan</h3>
                        <p class="text-xs text-slate-600 mt-1">Kesepakatan jadwal, metode uji, dan jaminan kerahasiaan materi uji kompetensi.</p>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-200 flex flex-col sm:flex-row gap-2">
                        <a href="{{ route('asesi.assessment.instrument.download-template', [$assessment, 'AK01']) }}" class="inline-flex items-center justify-center px-3 py-2 bg-white hover:bg-slate-50 text-slate-800 text-xs font-bold rounded-lg border border-slate-300 transition shrink-0" title="Unduh Lembar AK.01 PDF">
                            📥 PDF
                        </a>
                        <a href="{{ route('asesi.assessment.ak01', $assessment) }}" class="inline-flex items-center justify-center flex-1 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-lg border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] transition">
                            {{ $hasSignedAk01 ? 'Lihat Persetujuan AK.01 →' : 'Tinjau & Tanda Tangani AK.01 →' }}
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Tombol Selesaikan Seluruh Rangkaian Asesmen --}}
    @if($assessment->status !== 'COMPLETED')
        <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-[#1E2B6E] font-mono">Penyelesaian Akhir</span>
                <h4 class="text-base font-bold text-slate-900 font-sans">Selesaikan Seluruh Rangkaian Uji Mandiri</h4>
                <p class="text-xs text-slate-600 mt-0.5">Pastikan Anda telah menyelesaikan ujian teori CBT (IA.05), mengunggah bukti fisik tugas demonstrasi (IA.02), serta menandatangani instrumen observasi.</p>
            </div>
            <form action="{{ route('asesi.assessment.finish', $assessment) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyelesaikan proses uji kompetensi ini?')">
                @csrf
                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg shadow-sm transition shrink-0">
                    Selesaikan Asesmen Mandiri ✓
                </button>
            </form>
        </div>
    @else
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-800 font-mono">Tahap Pasca Asesmen</span>
                <h4 class="text-base font-bold text-slate-900">Rangkaian Asesmen Selesai ({{ $assessment->final_result ?? 'KOMPETEN' }})</h4>
                <p class="text-xs text-slate-600 mt-0.5">Silakan lengkapi umpan balik proses asesmen (FR.AK.03) atau ajukan banding asesmen (FR.AK.04) jika ada sanggahan.</p>
            </div>
            <div class="flex items-center space-x-2 shrink-0">
                <a href="{{ route('asesi.assessment.ak03', $assessment) }}" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                    FR.AK.03 Umpan Balik &rarr;
                </a>
                <a href="{{ route('asesi.assessment.appeal', $assessment) }}" class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold rounded-lg shadow-sm transition">
                    FR.AK.04 Banding
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
