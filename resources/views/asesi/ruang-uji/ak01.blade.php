@extends('layouts.app')

@section('title', 'FR.AK.01 - Persetujuan Asesmen dan Kerahasiaan')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6">
    {{-- Header & Navigasi Terstruktur --}}
    <div class="space-y-3 pb-2 border-b border-slate-200/80">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('asesi.assessment.ruang-uji', $assessment) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 hover:text-brand-800 transition py-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Ruang Uji</span>
            </a>
            @if($assessment->ak01_agreed_at)
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    ✓ Disetujui
                </span>
            @endif
        </div>
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-black text-slate-900 tracking-tight leading-snug">
                FR.AK.01. Persetujuan Asesmen dan Kerahasiaan
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 leading-relaxed max-w-2xl">
                Kesepakatan pelaksanaan asesmen dan perlindungan kerahasiaan materi uji
            </p>
        </div>
    </div>

    @if($assessment->schedule?->is_closed)
        <div class="p-4 bg-emerald-50 border-2 border-emerald-300 rounded-xl text-emerald-900 flex flex-col sm:flex-row items-start sm:items-center gap-3 shadow-xs">
            <span class="text-xl shrink-0">🔒</span>
            <div>
                <div class="font-bold text-sm">Jadwal Ditutup</div>
                <p class="text-xs text-emerald-800 mt-0.5 leading-relaxed">Persetujuan FR.AK.01 dalam mode lihat (read-only).</p>
            </div>
        </div>
    @endif

    {{-- Form AK01 Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="bg-gradient-to-r from-slate-800 to-indigo-900 px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between text-white gap-3">
            <div>
                <h2 class="text-base sm:text-lg font-bold">FR.AK.01. PERSETUJUAN ASESMEN DAN KERAHASIAAN</h2>
                <p class="text-xs text-slate-300 mt-0.5">Persetujuan asesmen dan kerahasiaan materi uji</p>
            </div>
            <a href="{{ route('asesi.assessment.ruang-uji', $assessment) }}" class="self-start sm:self-auto px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-bold rounded-lg border border-white/20 transition">
                ← Ruang Uji
            </a>
        </div>

        <form action="{{ route('asesi.assessment.ak01.sign', $assessment) }}" method="POST" class="p-4 sm:p-6 space-y-6">
            @csrf

            @if($assessment->ak01_agreed_at)
                <div class="p-4 bg-emerald-50 border border-emerald-300 text-emerald-800 rounded-lg text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-bold shrink-0">✓</span>
                        <div>
                            <span class="font-bold">FR.AK.01 Disetujui:</span>
                            <span class="text-emerald-700">Telah ditandatangani pada {{ \Carbon\Carbon::parse($assessment->ak01_agreed_at)->translatedFormat('d F Y, H:i') }} WIB.</span>
                        </div>
                    </div>
                    <a href="{{ route('asesi.assessment.ruang-uji', $assessment) }}" class="shrink-0 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition shadow-xs">
                        Ke Ruang Uji &rarr;
                    </a>
                </div>
            @endif

            <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-sm grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <span class="text-xs text-slate-500 block">Skema Sertifikasi</span>
                    <span class="font-bold text-slate-800">{{ $assessment->schedule->scheme->name }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block">Nomor Skema</span>
                    <span class="font-bold text-slate-800">{{ $assessment->schedule->scheme->code }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block">Tempat Uji Kompetensi (TUK)</span>
                    <span class="font-semibold text-slate-700">{{ $assessment->schedule->tuk_name }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block">Tanggal Pelaksanaan</span>
                    <span class="font-semibold text-slate-700">{{ \Carbon\Carbon::parse($assessment->schedule->date)->translatedFormat('d F Y') }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block">Nama Asesi</span>
                    <span class="font-semibold text-slate-700">{{ auth()->user()->full_name }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-500 block">Asesor Ditugaskan</span>
                    <span class="font-semibold text-slate-700">{{ $assessment->assessor?->full_name ?? 'Tim Asesor LSP' }}</span>
                </div>
            </div>

            {{-- Bukti Yang Akan Dikumpulkan --}}
            <div class="border border-slate-200 rounded-lg p-4 bg-white">
                <h3 class="text-sm font-bold text-slate-800 mb-2">Bukti yang akan dikumpulkan:</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs text-slate-700">
                    <label class="flex items-center gap-2"><input type="checkbox" checked disabled class="rounded text-indigo-600"> Verifikasi Portofolio</label>
                    <label class="flex items-center gap-2"><input type="checkbox" checked disabled class="rounded text-indigo-600"> Observasi Langsung</label>
                    <label class="flex items-center gap-2"><input type="checkbox" checked disabled class="rounded text-indigo-600"> Pertanyaan Lisan (PMO)</label>
                    <label class="flex items-center gap-2"><input type="checkbox" checked disabled class="rounded text-indigo-600"> Pertanyaan Tertulis (CBT)</label>
                    <label class="flex items-center gap-2"><input type="checkbox" checked disabled class="rounded text-indigo-600"> Tugas Demonstrasi</label>
                    <label class="flex items-center gap-2"><input type="checkbox" checked disabled class="rounded text-indigo-600"> Umpan Balik Asesmen</label>
                </div>
            </div>

            {{-- Klausul Persetujuan --}}
            <div class="space-y-4 text-xs text-slate-600 leading-relaxed bg-amber-50/60 border border-amber-200 p-4 rounded-lg">
                <p class="font-semibold text-slate-800">Pernyataan Asesi:</p>
                <p>1. Bahwa saya telah mendapatkan penjelasan terkait hak dan prosedur banding asesmen dari asesor.</p>
                <p>2. Saya setuju mengikuti asesmen dengan pemahaman bahwa informasi yang dikumpulkan hanya digunakan untuk pengembangan profesional dan hanya dapat diakses oleh pihak yang berwenang.</p>
                <p>3. Saya menyatakan akan mematuhi seluruh tata tertib dan memelihara kerahasiaan materi uji kompetensi.</p>

                <div class="pt-2">
                    <label class="flex items-start gap-2 cursor-pointer font-medium text-slate-800">
                        <input type="checkbox" required class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500" @checked((bool)$assessment->ak01_agreed_at) @disabled((bool)$assessment->ak01_agreed_at)>
                        <span>Saya telah membaca, memahami, dan menyetujui seluruh klausul di atas.</span>
                    </label>
                </div>
            </div>

            {{-- Tanda Tangan & Konfirmasi --}}
            @php
                $effectiveAsesiSig = $assessment->asesi_signature ?? auth()->user()->profile?->signature_data;
            @endphp
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 pt-4 border-t border-slate-200">
                <div class="w-full md:w-auto">
                    @if($effectiveAsesiSig)
                        <input type="hidden" name="signature_data" value="{{ $effectiveAsesiSig }}">
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex items-center gap-3">
                            <div class="w-32 h-14 bg-white border border-slate-200 rounded-lg p-1.5 flex items-center justify-center shrink-0">
                                <img src="{{ $effectiveAsesiSig }}" alt="Tanda Tangan Asesi" class="max-h-full max-w-full object-contain">
                            </div>
                            <div>
                                <span class="text-xs font-bold text-slate-900 block">Tanda Tangan Terverifikasi</span>
                                <span class="text-[11px] text-emerald-700 font-semibold flex items-center gap-1 mt-0.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    Tersemat dari APL
                                </span>
                            </div>
                        </div>
                    @else
                        <input type="hidden" name="signature_data" value="">
                        <div class="text-xs text-slate-500">Tanda tangan diverifikasi dari akun asesi.</div>
                    @endif
                </div>

                <div class="w-full md:w-auto flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <a href="{{ route('asesi.assessment.ruang-uji', $assessment) }}" class="w-full sm:w-auto px-5 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider rounded-xl border border-slate-300 transition text-center">
                        ← Ruang Uji
                    </a>
                    @if(!$assessment->ak01_agreed_at)
                        @if($assessment->schedule?->is_closed)
                            <button type="button" disabled class="w-full sm:w-auto px-6 py-3.5 bg-slate-200 text-slate-500 font-bold text-xs uppercase tracking-wider rounded-xl cursor-not-allowed border border-slate-300 text-center">
                                Jadwal Ditutup (Read-Only)
                            </button>
                        @else
                            <button type="submit" class="w-full sm:w-auto px-8 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-md transition flex items-center justify-center gap-2">
                                <span>Setujui &amp; Masuk</span>
                                <span>→</span>
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
