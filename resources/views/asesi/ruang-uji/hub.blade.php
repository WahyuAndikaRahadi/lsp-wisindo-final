@extends('layouts.app')

@section('title', 'Ruang Uji Kompetensi - LSP PrO Indo')

@section('content')
<div class="max-w-5xl mx-auto py-6 space-y-6">
    {{-- Stepper Component --}}
    <x-progress-stepper currentStep="{{ $assessment->last_active_step ?? 'ruang-uji' }}" :assessment="$assessment" />

    {{-- Header Info --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded bg-indigo-50 text-indigo-700">Ruang Uji Asesi</span>
            <h1 class="text-2xl font-bold text-slate-800 mt-2">{{ $assessment->schedule->scheme->name }}</h1>
            <p class="text-xs text-slate-500 mt-1">
                TUK: <span class="font-medium text-slate-700">{{ $assessment->schedule->tuk_name }}</span> | 
                Kode Jadwal: <span class="font-mono text-slate-700 font-bold">{{ $assessment->schedule->code }}</span>
            </p>
        </div>

        @if($assessment->status === 'COMPLETED')
            <div class="px-4 py-2 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800 text-sm font-bold flex items-center gap-2">
                <span>✓ Asesmen Telah Selesai</span>
            </div>
        @endif
    </div>

    @php
        $selectedInstruments = $assessment->schedule->selected_instruments ?? ['AK01', 'IA01', 'IA02', 'IA03', 'IA05'];
        if (is_string($selectedInstruments)) {
            $selectedInstruments = json_decode($selectedInstruments, true) ?: [];
        }
        $responses = $assessment->iaResponses->keyBy('instrument_code');
    @endphp

    {{-- Daftar Tombol Instrumen Asesmen --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- FR.IA.05 CBT --}}
        @if(in_array('IA05', $selectedInstruments))
            @php
                $ia05Resp = $responses['IA05'] ?? null;
                $isDone05 = (bool) $ia05Resp;
            @endphp
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-indigo-300 transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold px-2 py-0.5 rounded {{ $isDone05 ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800' }}">
                            {{ $isDone05 ? '✓ Sudah Dikerjakan' : 'Dikerjakan Asesi' }}
                        </span>
                        @if($isDone05)
                            <span class="text-xs font-bold text-slate-700">Skor: {{ $ia05Resp->score ?? '-' }}</span>
                        @endif
                    </div>
                    <h3 class="text-base font-bold text-slate-800">FR.IA.05. Pertanyaan Tertulis (CBT)</h3>
                    <p class="text-xs text-slate-500 mt-1">Ujian teori pilihan ganda. Sistem akan mengoreksi nilai secara otomatis.</p>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100">
                    <a href="{{ route('asesi.assessment.instrument.ia05', $assessment) }}" class="inline-flex items-center justify-center w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition shadow-sm">
                        {{ $isDone05 ? 'Tinjau Hasil CBT' : 'Mulai Ujian Teori (CBT) →' }}
                    </a>
                </div>
            </div>
        @endif

        {{-- FR.IA.01 Observasi Praktik (View & Sign) --}}
        @if(in_array('IA01', $selectedInstruments))
            @php
                $ia01Resp = $responses['IA01'] ?? null;
                $hasSigned01 = (bool) $ia01Resp?->asesi_signed_at;
            @endphp
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-indigo-300 transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold px-2 py-0.5 rounded {{ $hasSigned01 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                            {{ $hasSigned01 ? '✓ Ditandatangani' : 'View & Tanda Tangan' }}
                        </span>
                        <span class="text-xs text-slate-400">Dinilai Asesor</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">FR.IA.01. Ceklis Observasi Praktik</h3>
                    <p class="text-xs text-slate-500 mt-1">Hasil pengamatan unjuk kerja oleh asesor di bengkel kerja TUK.</p>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100">
                    <a href="{{ route('asesi.assessment.instrument.view', [$assessment, 'IA01']) }}" class="inline-flex items-center justify-center w-full px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg transition shadow-sm">
                        {{ $hasSigned01 ? 'Lihat Ceklis Observasi' : 'Beri Umpan Balik & TTD →' }}
                    </a>
                </div>
            </div>
        @endif

        {{-- FR.IA.01B Report Sheet (Input & View) --}}
        @php
            $ia01bResp = $responses['IA01B'] ?? null;
            $hasSigned01b = (bool) $ia01bResp?->asesi_signed_at;
        @endphp
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-indigo-300 transition flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold px-2 py-0.5 rounded {{ $hasSigned01b ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800' }}">
                        {{ $hasSigned01b ? '✓ Sudah Diisi' : 'Lembar Kerja Teknis' }}
                    </span>
                    <span class="text-xs text-slate-400">Pengukuran Fisik</span>
                </div>
                <h3 class="text-base font-bold text-slate-800">FR.IA.01B. Lembar Kerja Pengukuran (Report Sheet)</h3>
                <p class="text-xs text-slate-500 mt-1">Formulir isian data hasil ukur komponen spesifik sesuai skema uji.</p>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100">
                <a href="{{ route('asesi.assessment.instrument.view', [$assessment, 'IA01B']) }}" class="inline-flex items-center justify-center w-full px-4 py-2 bg-lsp-navy hover:bg-lsp-navy-dark text-white text-xs font-bold rounded-lg transition shadow-sm">
                    {{ $hasSigned01b ? 'Lihat Lembar Kerja' : 'Isi Lembar Kerja Report Sheet →' }}
                </a>
            </div>
        </div>

        {{-- FR.IA.02 Tugas Demonstrasi (View & Sign) --}}
        @if(in_array('IA02', $selectedInstruments))
            @php
                $ia02Resp = $responses['IA02'] ?? null;
                $hasSigned02 = (bool) $ia02Resp?->asesi_signed_at;
            @endphp
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-indigo-300 transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold px-2 py-0.5 rounded {{ $hasSigned02 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                            {{ $hasSigned02 ? '✓ Ditandatangani' : 'View & Tanda Tangan' }}
                        </span>
                        <span class="text-xs text-slate-400">Dinilai Asesor</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">FR.IA.02. Tugas Praktik Demonstrasi</h3>
                    <p class="text-xs text-slate-500 mt-1">Panduan instruksi kerja praktik dan verifikasi demonstrasi.</p>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100">
                    <a href="{{ route('asesi.assessment.instrument.view', [$assessment, 'IA02']) }}" class="inline-flex items-center justify-center w-full px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg transition shadow-sm">
                        {{ $hasSigned02 ? 'Lihat Tugas Demonstrasi' : 'Beri Umpan Balik & TTD →' }}
                    </a>
                </div>
            </div>
        @endif

        {{-- FR.IA.03 Pertanyaan PMO (View & Sign) --}}
        @if(in_array('IA03', $selectedInstruments))
            @php
                $ia03Resp = $responses['IA03'] ?? null;
                $hasSigned03 = (bool) $ia03Resp?->asesi_signed_at;
            @endphp
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-indigo-300 transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold px-2 py-0.5 rounded {{ $hasSigned03 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                            {{ $hasSigned03 ? '✓ Ditandatangani' : 'View & Tanda Tangan' }}
                        </span>
                        <span class="text-xs text-slate-400">Dinilai Asesor</span>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">FR.IA.03. Pertanyaan Mendukung Observasi (PMO)</h3>
                    <p class="text-xs text-slate-500 mt-1">Tanya jawab klarifikasi lisan saat proses observasi praktik.</p>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100">
                    <a href="{{ route('asesi.assessment.instrument.view', [$assessment, 'IA03']) }}" class="inline-flex items-center justify-center w-full px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg transition shadow-sm">
                        {{ $hasSigned03 ? 'Lihat Pertanyaan PMO' : 'Beri Umpan Balik & TTD →' }}
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- Tombol Selesaikan Asesmen --}}
    @if($assessment->status !== 'COMPLETED')
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h4 class="text-sm font-bold text-slate-800">Selesaikan Seluruh Rangkaian Uji</h4>
                <p class="text-xs text-slate-500">Jika Anda telah mengerjakan ujian CBT dan menandatangani seluruh instrumen, klik tombol di samping.</p>
            </div>
            <form action="{{ route('asesi.assessment.finish', $assessment) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyelesaikan proses uji kompetensi ini?')">
                @csrf
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                    Selesaikan Asesmen Mandiri ✓
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
