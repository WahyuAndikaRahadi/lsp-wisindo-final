@extends('layouts.app', ['title' => 'FR.AK.04 - Pengenalan Banding Asesmen'])

@section('content')
@php
    $jadwal = $schedule;
    $isClosedForMe = $jadwal->isClosedForAssessor();
    $isAlreadyBriefed = !empty($jadwal->ak04_briefed_at);
@endphp

<div class="space-y-6 max-w-5xl mx-auto pb-12">
    {{-- Top Lifecycle Navigation --}}
    @include('asesor.partials.schedule-nav', ['schedule' => $jadwal, 'active' => 'ak04'])

    @if($isClosedForMe)
        <div class="p-3.5 bg-emerald-50 border border-emerald-300 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-emerald-900">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span><strong>{{ $jadwal->is_closed ? 'Jadwal Ditutup:' : 'Asesmen Selesai:' }}</strong> Dokumen FR.AK.04 dalam mode lihat (read-only).</span>
            </div>
            <span class="font-bold font-mono text-[11px] bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded border border-emerald-300 shrink-0">STATUS: READ-ONLY</span>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">FR.AK.04. PENGENALAN BANDING ASESMEN</h1>
            <p class="text-xs text-slate-500 font-medium">Lembaga Sertifikasi Profesi Praktisi Otomotif (LSP PrO Indo)</p>
        </div>
    </div>

    {{-- Info Box / Briefing Guidance --}}
    <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 text-xs text-slate-700 shadow-2xs flex items-start gap-3">
        <div class="w-6 h-6 rounded-full bg-brand-50 text-brand-700 flex items-center justify-center shrink-0 mt-0.5 font-bold">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="leading-relaxed">
            <strong class="text-slate-900 block mb-0.5">Pengenalan Hak Banding kepada Asesi (Pratinjau / View-Only):</strong>
            Formulir di bawah ini merupakan formulir yang dapat diakses oleh asesi jika mereka menyanggah atau tidak menerima rekomendasi hasil keputusan asesmen. Sesuai prosedur BNSP, Asesor bertugas memperkenalkan hak dan alur pengajuan banding ini kepada asesi sebelum proses asesmen dimulai.
        </div>
    </div>

    {{-- View-Only Form Preview Card --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden opacity-95">
        <div class="px-5 py-3.5 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
            <div class="flex items-center space-x-2">
                <span class="font-mono text-xs font-bold text-amber-400 bg-slate-800 px-2 py-0.5 rounded">FR.AK.04</span>
                <span class="text-xs text-slate-300 font-medium">Pratinjau Formulir Banding Asesmen (Sisi Asesi)</span>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 bg-slate-800/80 px-2.5 py-0.5 rounded border border-slate-700">Mode Pratinjau / View-Only</span>
        </div>

        <div class="p-5 sm:p-6 space-y-6">
            {{-- Info Table --}}
            <div class="border border-slate-200 rounded-lg p-3 bg-slate-50 text-xs grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <span class="text-slate-500 block">Skema Sertifikasi:</span>
                    <span class="font-bold text-slate-900">{{ $jadwal->scheme?->name }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">Tempat Uji Kompetensi (TUK):</span>
                    <span class="font-bold text-slate-900">{{ $jadwal->tuk_name }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block">Asesor Pelaksana:</span>
                    <span class="font-bold text-slate-900">{{ auth()->user()?->full_name ?? 'Asesor Kompetensi' }}</span>
                </div>
            </div>

            {{-- 1. Pertanyaan Klarifikasi Proses Banding --}}
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 font-bold text-xs text-slate-800 flex items-center justify-between">
                    <span>1. Proses Banding (Pertanyaan Klarifikasi Standar BNSP):</span>
                    <span class="text-[11px] font-normal text-slate-500">Pratinjau</span>
                </div>
                <div class="p-0 overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-100 text-slate-600 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-2 text-left font-bold">Pertanyaan Klarifikasi</th>
                                <th class="px-3 py-2 text-center w-20 font-bold">YA</th>
                                <th class="px-3 py-2 text-center w-20 font-bold">TIDAK</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="px-4 py-3 text-slate-700 font-medium">Apakah Proses Banding telah dijelaskan kepada Anda?</td>
                                <td class="px-3 py-3 text-center"><input type="radio" disabled checked class="text-emerald-600 w-4 h-4 cursor-not-allowed"></td>
                                <td class="px-3 py-3 text-center"><input type="radio" disabled class="w-4 h-4 cursor-not-allowed text-slate-300"></td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 text-slate-700 font-medium">Apakah Anda telah mendiskusikan Banding dengan Asesor?</td>
                                <td class="px-3 py-3 text-center"><input type="radio" disabled checked class="text-emerald-600 w-4 h-4 cursor-not-allowed"></td>
                                <td class="px-3 py-3 text-center"><input type="radio" disabled class="w-4 h-4 cursor-not-allowed text-slate-300"></td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 text-slate-700 font-medium">Apakah Anda mau melibatkan "orang lain" membantu Anda dalam Proses Banding?</td>
                                <td class="px-3 py-3 text-center"><input type="radio" disabled class="w-4 h-4 cursor-not-allowed text-slate-300"></td>
                                <td class="px-3 py-3 text-center"><input type="radio" disabled checked class="text-rose-600 w-4 h-4 cursor-not-allowed"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-3 bg-slate-50/60 border-t border-slate-200">
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nama Orang Lain / Pendamping (jika ada):</label>
                    <input type="text" disabled placeholder="Diisi oleh asesi jika melibatkan pendamping..." class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-slate-100 text-slate-500 cursor-not-allowed">
                </div>
            </div>

            {{-- 2. Alasan Banding --}}
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-800">2. Alasan &amp; Uraian Pengajuan Banding:</label>
                <p class="text-[11px] text-slate-500 leading-relaxed">Kolom yang akan diisi asesi untuk merinci dasar sanggahan terhadap hasil keputusan asesmen.</p>
                <textarea rows="3" disabled class="w-full p-3 text-xs rounded-lg border border-slate-300 bg-slate-100 text-slate-500 cursor-not-allowed" placeholder="Asesi akan menuliskan rincian alasan banding di sini..."></textarea>
            </div>

            {{-- 3. Unit Kompetensi yang Dipersoalkan --}}
            <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-800">3. Unit Kompetensi yang Dipersoalkan / Disanggah:</label>
                <p class="text-[11px] text-slate-500 leading-relaxed">Daftar unit yang dapat dicentang asesi saat mengajukan sanggahan.</p>
                <div class="space-y-1.5 max-h-40 overflow-y-auto border border-slate-200 rounded-lg p-2.5 bg-slate-50">
                    @foreach($units as $u)
                        <div class="flex items-start space-x-2 text-xs text-slate-600 p-1">
                            <input type="checkbox" disabled class="mt-0.5 rounded border-slate-300 text-slate-400 cursor-not-allowed">
                            <span><strong class="font-mono text-slate-800">{{ $u->code }}</strong> - {{ $u->title }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Asesor Confirmation Action Form --}}
    <form action="{{ route('asesor.schedule.ak04.brief', $jadwal->id) }}" method="POST" class="bg-white rounded-xl border border-slate-200 p-5 shadow-2xs space-y-4">
        @csrf

        <div class="flex items-start space-x-3">
            <input type="checkbox" name="is_briefed" id="is_briefed" value="1"
                   @disabled($isClosedForMe)
                   {{ $isAlreadyBriefed ? 'checked' : '' }}
                   required
                   class="mt-1 rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4 cursor-pointer shrink-0">
            <label for="is_briefed" class="text-xs text-slate-800 leading-relaxed cursor-pointer select-none">
                <strong class="text-slate-900 block font-bold">Pernyataan Konfirmasi Asesor:</strong>
                Saya menyatakan telah memperkenalkan, menerangkan, dan memastikan seluruh asesi memahami hak serta prosedur pengajuan banding asesmen (<strong class="font-semibold">FR.AK.04</strong>) sebelum proses asesmen dilaksanakan.
            </label>
        </div>
        @error('is_briefed')
            <p class="text-xs text-rose-600 font-semibold">{{ $message }}</p>
        @enderror

        <div class="pt-3 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
            <a href="{{ route('asesor.schedule.mapa', $jadwal->id) }}"
               class="w-full sm:w-auto px-4 py-2.5 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg transition text-center">
                &larr; Kembali ke FR.MAPA.01
            </a>

            @if(!$isClosedForMe)
                <button type="submit"
                        class="w-full sm:w-auto px-6 py-2.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <span>Konfirmasi &amp; Lanjut ke FR.AK.01</span>
                    <span>&rarr;</span>
                </button>
            @else
                <a href="{{ route('asesor.schedule.ak01', $jadwal->id) }}"
                   class="w-full sm:w-auto px-6 py-2.5 text-xs font-bold text-white bg-slate-800 hover:bg-slate-900 rounded-lg transition flex items-center justify-center gap-1.5">
                    <span>Lanjut ke FR.AK.01 (Read-Only)</span>
                    <span>&rarr;</span>
                </a>
            @endif
        </div>
    </form>
</div>
@endsection
