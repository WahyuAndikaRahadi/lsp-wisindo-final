@extends('layouts.app')

@section('title', 'FR.AK.01 - Persetujuan Asesmen dan Kerahasiaan')

@section('content')
<div class="max-w-4xl mx-auto py-6 space-y-6">
    {{-- Stepper Component --}}
    <x-progress-stepper currentStep="ruang-uji" :assessment="$assessment" />

    {{-- Form AK01 Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="bg-gradient-to-r from-slate-800 to-indigo-900 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between text-white gap-3">
            <div>
                <h1 class="text-xl font-bold">FR.AK.01. PERSETUJUAN ASESMEN DAN KERAHASIAAN</h1>
                <p class="text-xs text-slate-300 mt-1">Kesepakatan pelaksanaan asesmen dan perlindungan kerahasiaan materi uji</p>
            </div>
            <a href="{{ route('asesi.assessment.ruang-uji', $assessment) }}" class="shrink-0 px-3 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-bold rounded border border-white/20 transition">
                ← Ruang Uji
            </a>
        </div>

        <form action="{{ route('asesi.assessment.ak01.sign', $assessment) }}" method="POST" class="p-6 space-y-6">
            @csrf

            @if($assessment->ak01_agreed_at)
                <div class="p-4 bg-emerald-50 border border-emerald-300 text-emerald-800 rounded-lg text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-bold shrink-0">✓</span>
                        <div>
                            <span class="font-bold">FR.AK.01 Telah Disetujui:</span>
                            <span class="text-emerald-700">Persetujuan asesmen dan kerahasiaan telah Anda tandatangani pada {{ \Carbon\Carbon::parse($assessment->ak01_agreed_at)->translatedFormat('d F Y, H:i') }} WIB.</span>
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
            <div class="flex flex-col md:flex-row items-end justify-between gap-6 pt-4 border-t border-slate-200">
                <div class="w-full md:w-80">
                    <x-signature-pad 
                        name="signature_data" 
                        :value="$assessment->asesi_signature ?? old('signature_data')" 
                        :profileSignature="auth()->user()->profile?->signature_data"
                        label="Tanda Tangan Persetujuan Asesi" 
                        height="140" 
                        :disabled="(bool)$assessment->ak01_agreed_at"
                    />
                </div>

                <div class="w-full md:w-auto flex items-center gap-3">
                    <a href="{{ route('asesi.assessment.ruang-uji', $assessment) }}" class="px-5 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider rounded-xl border border-slate-300 transition">
                        ← Kembali ke Ruang Uji
                    </a>
                    @if(!$assessment->ak01_agreed_at)
                        <button type="submit" class="w-full md:w-auto px-8 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-md transition flex items-center justify-center gap-2">
                            <span>Setujui & Masuk Ruang Uji</span>
                            <span>→</span>
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
