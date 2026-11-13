@extends('layouts.app', ['title' => 'Dokumen MUK: ' . ($schedule->name ?? $schedule->scheme?->name)])

@section('content')
<div class="space-y-6">
    {{-- Header & Breadcrumbs --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('admin.dokumen-muk.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-brand-600 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Kembali ke Daftar Jadwal</span>
                </a>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <span>Dokumen MUK & Berita Acara</span>
                <span class="font-mono text-xs font-bold px-2.5 py-1 rounded bg-brand-50 text-brand-700 border border-brand-100">
                    {{ $schedule->code }}
                </span>
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
                {{ $schedule->name ?? $schedule->scheme?->name }} &bull; {{ $schedule->scheme?->name }}
            </p>
        </div>
    </div>

    {{-- Schedule Details Card --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Informasi Jadwal Asesmen</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block mb-0.5">Skema Sertifikasi:</span>
                <span class="font-bold text-slate-800">{{ $schedule->scheme?->name ?? '-' }}</span>
                <span class="text-slate-500 block text-[11px]">Kode: {{ $schedule->scheme?->code ?? '-' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">TUK & Waktu Pelaksanaan:</span>
                <span class="font-bold text-slate-800">{{ $schedule->tuk_name ?: '-' }}</span>
                <span class="text-slate-500 block text-[11px]">
                    {{ \Carbon\Carbon::parse($schedule->date)->isoFormat('D MMMM Y') }}
                    @if($schedule->start_time)
                        ({{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }} WIB)
                    @endif
                </span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Tim Asesor:</span>
                @if($schedule->assessors->isNotEmpty())
                    <span class="font-bold text-slate-800">{{ $schedule->assessors->pluck('full_name')->join(', ') }}</span>
                @else
                    <span class="text-slate-400 italic">Belum ditugaskan</span>
                @endif
                @if($schedule->pic_name)
                    <span class="text-slate-500 block text-[11px]">Penanggung Jawab: {{ $schedule->pic_name }}</span>
                @endif
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Total Asesi:</span>
                <span class="font-bold text-slate-800 text-sm">{{ $schedule->assessments()->count() }} Peserta</span>
                <span class="text-slate-500 block text-[11px]">Status: {{ $schedule->status ?? 'ACTIVE' }}</span>
            </div>
        </div>
    </div>

    {{-- Schedule-level Exports Section --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Ekspor Dokumen Jadwal Asesmen</h2>
            <p class="text-xs text-slate-500">Cetak berita acara, daftar hadir, laporan asesmen, dan perangkat instrumen MUK</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Berita Acara & Kehadiran (Primary) --}}
            <a href="{{ route('admin.dokumen-muk.dynamic', ['schedule' => $schedule->id, 'type' => 'ba']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-xs transition min-h-[38px]">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Berita Acara (BA)</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.export-sertifikat', $schedule->id) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow-xs transition min-h-[38px]">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Sertifikat</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.export-excel-biodata', $schedule->id) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg shadow-2xs transition min-h-[38px]">
                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Excel Biodata</span>
            </a>

            <div class="h-5 w-px bg-slate-200 hidden sm:block mx-1"></div>

            {{-- Dokumen Laporan & Pelaksanaan --}}
            <a href="{{ route('admin.dokumen-muk.dynamic', ['schedule' => $schedule->id, 'type' => 'hadir-asesor']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg shadow-2xs transition min-h-[38px]">
                <span>Hadir Asesor</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.dynamic', ['schedule' => $schedule->id, 'type' => 'ba']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg shadow-2xs transition min-h-[38px]">
                <span>Hadir Asesi</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.export', ['schedule' => $schedule->id, 'type' => 'ak05']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg shadow-2xs transition min-h-[38px]">
                <span>FR.AK.05 (Laporan)</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.export', ['schedule' => $schedule->id, 'type' => 'ak06']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg shadow-2xs transition min-h-[38px]">
                <span>FR.AK.06 (Tinjauan)</span>
            </a>

            {{-- Fixed Master PDF --}}
            <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'cover']) }}" target="_blank"
               class="inline-flex items-center gap-1 px-2.5 py-2 text-xs font-medium bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-lg border border-slate-200 transition min-h-[38px]" title="Fixed Master PDF: Cover MUK">
                <span>Cover</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'daftar-periksa']) }}" target="_blank"
               class="inline-flex items-center gap-1 px-2.5 py-2 text-xs font-medium bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-lg border border-slate-200 transition min-h-[38px]" title="Fixed Master PDF: Daftar Periksa">
                <span>Periksa</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'mapa01']) }}" target="_blank"
               class="inline-flex items-center gap-1 px-2.5 py-2 text-xs font-medium bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-lg border border-slate-200 transition min-h-[38px]" title="Fixed Master PDF: FR.MAPA.01">
                <span>MAPA.01</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'mapa02']) }}" target="_blank"
               class="inline-flex items-center gap-1 px-2.5 py-2 text-xs font-medium bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-lg border border-slate-200 transition min-h-[38px]" title="Fixed Master PDF: FR.MAPA.02">
                <span>MAPA.02</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'ia02']) }}" target="_blank"
               class="inline-flex items-center gap-1 px-2.5 py-2 text-xs font-medium bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-lg border border-slate-200 transition min-h-[38px]" title="Fixed Master PDF: FR.IA.02 Tugas Praktik Demonstrasi">
                <span>IA.02</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'ia05b']) }}" target="_blank"
               class="inline-flex items-center gap-1 px-2.5 py-2 text-xs font-medium bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-lg border border-slate-200 transition min-h-[38px]" title="Fixed Master PDF: FR.IA.05.B Kunci Jawaban">
                <span>IA.05.B Kunci</span>
            </a>
        </div>
    </div>

    {{-- Assessees Section --}}
    <div class="space-y-4">
        {{-- Search Assessees Bar --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <form action="{{ route('admin.dokumen-muk.show', $schedule) }}" method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 text-xs">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari asesi berdasarkan nama, email, atau NIK..."
                        class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none text-slate-900 placeholder:text-slate-400 transition shadow-2xs">
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg transition text-xs shadow-sm flex items-center justify-center gap-1.5 whitespace-nowrap min-h-[38px]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Cari Asesi</span>
                    </button>
                    @if(!empty($search))
                        <a href="{{ route('admin.dokumen-muk.show', $schedule) }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-lg transition text-xs flex items-center justify-center whitespace-nowrap min-h-[38px]">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Assessees Table Card --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            @if($assessments->isEmpty())
                <div class="p-8 text-center">
                    <div class="w-12 h-12 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Tidak ada asesi ditemukan</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        @if(!empty($search))
                            Tidak ada data asesi yang cocok dengan kata kunci "{{ $search }}".
                        @else
                            Belum ada asesi yang terdaftar pada jadwal asesmen ini.
                        @endif
                    </p>
                    @if(!empty($search))
                        <a href="{{ route('admin.dokumen-muk.show', $schedule) }}" class="inline-block mt-3 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition">
                            Reset Pencarian
                        </a>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto min-h-[360px]">
                    <table class="w-full text-xs">
                        <thead class="hidden md:table-header-group bg-slate-50 text-left font-semibold text-slate-600 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 whitespace-nowrap w-12">No</th>
                                <th class="px-4 py-3 whitespace-nowrap">Nama Asesi</th>
                                <th class="px-4 py-3 whitespace-nowrap">Asesor</th>
                                <th class="px-4 py-3 whitespace-nowrap">Hasil Akhir</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Dokumen MUK</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($assessments as $idx => $assessment)
                                @php
                                    $ia01bResp = $assessment->iaResponses?->first(fn($r) => strtoupper((string)$r->instrument_code) === 'IA01B');
                                    $ia01bData = is_array($ia01bResp?->data_json) ? $ia01bResp->data_json : (json_decode($ia01bResp?->data_json ?? '[]', true) ?: []);
                                    $hasIa01b = !empty($ia01bData['evidence_image_path'] ?? $ia01bData['file_path'] ?? $ia01bData['uploaded_file'] ?? null);
                                    $openUp = $assessments->count() > 3 && ($loop->last || $loop->iteration >= $assessments->count() - 1);
                                @endphp
                                <tr class="flex flex-col md:table-row p-4 md:p-0 hover:bg-slate-50/70 transition space-y-2.5 md:space-y-0">
                                    <td class="hidden md:table-cell px-4 py-3 text-slate-500 font-mono whitespace-nowrap">
                                        {{ $assessments->firstItem() ? $assessments->firstItem() + $idx : $idx + 1 }}
                                    </td>
                                    <td class="px-0 md:px-4 py-0 md:py-3">
                                        <div class="flex items-center justify-between md:hidden pb-1 border-b border-slate-100">
                                            <span class="font-mono text-xs font-bold text-slate-500">
                                                #{{ $assessments->firstItem() ? $assessments->firstItem() + $idx : $idx + 1 }}
                                            </span>
                                            <div>
                                                @if(in_array($assessment->final_result, ['K', 'KOMPETEN'], true))
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">KOMPETEN (K)</span>
                                                @elseif(in_array($assessment->final_result, ['BK', 'BELUM_KOMPETEN'], true))
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">BELUM KOMPETEN (BK)</span>
                                                @elseif($assessment->final_result === 'TIDAK_HADIR')
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700 border border-slate-300">TIDAK HADIR</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">{{ $assessment->status ?? 'PENDING' }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mt-1 md:mt-0">
                                            <div class="font-bold text-slate-800 text-xs sm:text-sm">{{ $assessment->asesi?->full_name }}</div>
                                            <div class="text-[11px] text-slate-400 font-mono">
                                                {{ $assessment->asesi?->email }} &bull; NIK: {{ $assessment->asesi?->profile?->nik ?? '-' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-0 md:px-4 py-0 md:py-3 text-slate-600 whitespace-nowrap text-xs">
                                        <span class="text-slate-400 md:hidden">Asesor: </span>
                                        <strong class="text-slate-800 md:font-normal">{{ $assessment->assessor?->full_name ?? '-' }}</strong>
                                    </td>
                                    <td class="hidden md:table-cell px-4 py-3 whitespace-nowrap">
                                        @if(in_array($assessment->final_result, ['K', 'KOMPETEN'], true))
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">KOMPETEN (K)</span>
                                        @elseif(in_array($assessment->final_result, ['BK', 'BELUM_KOMPETEN'], true))
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">BELUM KOMPETEN (BK)</span>
                                        @elseif($assessment->final_result === 'TIDAK_HADIR')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700 border border-slate-300">TIDAK HADIR</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">{{ $assessment->status ?? 'PENDING' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-0 md:px-4 py-0 md:py-3 text-left md:text-right whitespace-nowrap pt-1 md:pt-0">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ak02']) }}" 
                                               target="_blank" 
                                               class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-md bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 transition min-h-[32px]" 
                                               title="Lihat FR.AK.02 Rekaman Asesmen Langsung">
                                                AK.02
                                            </a>

                                            <div class="relative inline-block text-left" x-data="{ open: false }">
                                                <button type="button" 
                                                        @click="open = !open" 
                                                        @click.outside="open = false"
                                                        @keydown.escape.stop="open = false"
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-md bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 shadow-2xs transition min-h-[32px]"
                                                        :class="{ 'bg-slate-100 ring-2 ring-brand-500/20 border-brand-500 text-brand-700': open }">
                                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                    <span>Semua Dokumen</span>
                                                    <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                    </svg>
                                                </button>

                                                {{-- Dropdown Menu --}}
                                                <div x-show="open" 
                                                     x-cloak
                                                     x-transition:enter="transition ease-out duration-150"
                                                     x-transition:enter-start="transform opacity-0 scale-95"
                                                     x-transition:enter-end="transform opacity-100 scale-100"
                                                     x-transition:leave="transition ease-in duration-100"
                                                     x-transition:leave-start="transform opacity-100 scale-100"
                                                     x-transition:leave-end="transform opacity-0 scale-95"
                                                     class="absolute right-0 {{ $openUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5' }} w-72 sm:w-80 rounded-xl bg-white shadow-xl border border-slate-200 z-50 divide-y divide-slate-100 text-left focus:outline-none"
                                                     style="display: none;">
                                                    
                                                    {{-- Dropdown Header --}}
                                                    <div class="px-3.5 py-2.5 bg-slate-50 {{ $openUp ? 'rounded-b-xl border-t order-last' : 'rounded-t-xl border-b' }} border-slate-100">
                                                        <div class="font-bold text-slate-800 text-xs truncate">{{ $assessment->asesi?->full_name }}</div>
                                                        <div class="text-[11px] text-slate-500">Pilih dokumen MUK untuk dibuka / dicetak:</div>
                                                    </div>

                                                    {{-- Scrollable List of Groups --}}
                                                    <div class="p-2 space-y-3 max-h-[290px] overflow-y-auto">
                                                        {{-- Group 1: Permohonan & Persetujuan --}}
                                                        <div>
                                                            <div class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                                Permohonan & Persetujuan
                                                            </div>
                                                            <div class="space-y-0.5">
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'apl01']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.APL.01</span>
                                                                        <span class="truncate">Permohonan Sertifikasi</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'apl02']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.APL.02</span>
                                                                        <span class="truncate">Asesmen Mandiri</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ak01']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.AK.01</span>
                                                                        <span class="truncate">Persetujuan Asesmen</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ak07']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.AK.07</span>
                                                                        <span class="truncate">Penyesuaian Yang Wajar</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                            </div>
                                                        </div>

                                                        {{-- Group 2: Instrumen Asesmen (IA) --}}
                                                        <div class="pt-1 border-t border-slate-100">
                                                            <div class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                                Instrumen Asesmen (IA)
                                                            </div>
                                                            <div class="space-y-0.5">
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ia01']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.IA.01</span>
                                                                        <span class="truncate">Ceklis Observasi</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ia01b']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.IA.01B</span>
                                                                        <span class="truncate">Report Sheet</span>
                                                                        @if($hasIa01b)
                                                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-semibold bg-emerald-100 text-emerald-700">Bukti Ada</span>
                                                                        @endif
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ia02']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.IA.02</span>
                                                                        <span class="truncate">Tugas Praktik Demonstrasi</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ia03']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.IA.03</span>
                                                                        <span class="truncate">Pertanyaan Lisan</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-fixed', ['assessment' => $assessment->id, 'type' => 'ia05a']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-cyan-50 text-cyan-700 border border-cyan-200 shrink-0">FR.IA.05.A</span>
                                                                        <span class="truncate">Lembar Soal (Master PDF)</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ia05c']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-cyan-50 text-cyan-700 border border-cyan-200 shrink-0">FR.IA.05.C</span>
                                                                        <span class="truncate">Lembar Jawaban Asesi</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                            </div>
                                                        </div>

                                                        {{-- Group 3: Rekaman, Evaluasi & Banding --}}
                                                        <div class="pt-1 border-t border-slate-100">
                                                            <div class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                                Rekaman, Evaluasi & Banding
                                                            </div>
                                                            <div class="space-y-0.5">
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ak02']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-brand-50 text-brand-700 border border-brand-200 shrink-0">FR.AK.02</span>
                                                                        <span class="truncate">Rekaman Asesmen</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ak03']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.AK.03</span>
                                                                        <span class="truncate">Umpan Balik Asesi</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ak04']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.AK.04</span>
                                                                        <span class="truncate">Banding Asesmen</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'pernyataan']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">PERNYATAAN</span>
                                                                        <span class="truncate">Surat Pernyataan Asesi</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($assessments->hasPages())
                    <div class="px-4 py-3 border-t border-slate-200">
                        {{ $assessments->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
