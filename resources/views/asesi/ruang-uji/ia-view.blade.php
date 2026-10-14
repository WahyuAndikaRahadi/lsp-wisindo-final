@extends('layouts.app')

@section('title', 'Tinjauan Instrumen FR.' . strtoupper($code))

@section('content')
<div class="max-w-4xl mx-auto py-6 space-y-6">
    {{-- Header --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <a href="{{ route('asesi.assessment.ruang-uji', $assessment) }}" class="text-xs text-indigo-600 hover:underline font-medium block mb-1">
                ← Kembali ke Ruang Uji
            </a>
            <h1 class="text-xl font-bold text-slate-800">
                @if(strtoupper($code) === 'IA01')
                    FR.IA.01. CEKLIS OBSERVASI AKTIVITAS PRAKTIK
                @elseif(strtoupper($code) === 'IA01B')
                    FR.IA.01B. LEMBAR PENGUKURAN TEKNIS (REPORT SHEET)
                @elseif(strtoupper($code) === 'IA02')
                    FR.IA.02. TUGAS PRAKTIK DEMONSTRASI
                @elseif(strtoupper($code) === 'IA03')
                    FR.IA.03. PERTANYAAN MENDUKUNG OBSERVASI (PMO)
                @else
                    FR.{{ strtoupper($code) }}
                @endif
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Skema: {{ $assessment->schedule->scheme->name }} | 
                Asesor: {{ $assessment->assessor?->full_name ?? 'Tim Asesor LSP' }}
            </p>
        </div>

        <div>
            @if($response?->asesi_signed_at)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                    ✓ Sudah Ditandatangani
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                    Menunggu Umpan Balik & TTD
                </span>
            @endif
        </div>
    </div>

    {{-- Penilaian oleh Asesor --}}
    @if(strtoupper($code) === 'IA02')
        @php
            $evidencePath = $response?->data_json['evidence_image_path'] ?? null;
            $evidenceTime = $response?->data_json['evidence_uploaded_at'] ?? null;
            $evidenceName = $response?->data_json['evidence_original_name'] ?? 'Lembar_Jawaban_Fisik.jpg';
        @endphp
        {{-- Box Khusus Pengumpulan Lembar Kerja Kertas FR.IA.02 --}}
        <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] overflow-hidden">
            <div class="bg-slate-900 text-white px-6 py-3 border-b-2 border-black flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="text-amber-300 font-bold text-xs uppercase tracking-wider font-mono">FR.IA.02 Paper-Based</span>
                    <span class="text-xs text-slate-300">— Unggah Lembar Jawaban Fisik</span>
                </div>
                @if($evidencePath)
                    <span class="px-2.5 py-0.5 text-[11px] font-bold bg-emerald-500 text-white rounded">
                        ✓ Lembar Kertas Terunggah
                    </span>
                @else
                    <span class="px-2.5 py-0.5 text-[11px] font-bold bg-amber-500 text-slate-900 rounded">
                        Belum Diunggah
                    </span>
                @endif
            </div>

            <div class="p-6 space-y-4">
                <div class="bg-amber-50 border border-amber-300 rounded-lg p-3 text-xs text-amber-900 leading-relaxed">
                    <strong>Petunjuk Uji Praktik:</strong> Instrumen Tugas Praktik Demonstrasi (FR.IA.02) dilaksanakan menggunakan <strong>lembar kerja kertas (paper-based)</strong> di TUK. Selesaikan tugas demonstrasi dan catat hasilnya pada kertas fisik. Foto lembar jawaban Anda dengan pencahayaan yang jelas dan tegak lurus, kemudian unggah pada tombol di bawah agar asesor dapat memeriksa pekerjaan Anda.
                </div>

                @if($evidencePath)
                    <div class="border-2 border-slate-200 rounded-xl p-4 bg-slate-50 flex flex-col md:flex-row items-center gap-4">
                        <div class="w-full md:w-48 h-48 bg-slate-200 rounded-lg overflow-hidden border border-slate-300 flex items-center justify-center relative group">
                            @if(str_ends_with(strtolower($evidencePath), '.pdf'))
                                <div class="text-center p-4">
                                    <span class="text-4xl text-rose-600 block mb-1">📄</span>
                                    <span class="text-[11px] font-bold text-slate-700 block truncate">{{ $evidenceName }}</span>
                                </div>
                            @else
                                <img src="{{ asset('storage/' . $evidencePath) }}" alt="Bukti Jawaban Kertas" class="w-full h-full object-cover">
                            @endif
                            <a href="{{ asset('storage/' . $evidencePath) }}" target="_blank" class="absolute inset-0 bg-black/60 text-white text-xs font-bold flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                🔍 Buka Ukuran Penuh
                            </a>
                        </div>
                        <div class="flex-1 space-y-2 text-xs">
                            <div class="font-bold text-slate-900 text-sm">Foto Lembar Jawaban Kertas Asesi</div>
                            <div class="text-slate-600">
                                Diunggah pada: <span class="font-mono font-semibold">{{ \Carbon\Carbon::parse($evidenceTime)->isoFormat('D MMMM Y, HH:mm') }} WIB</span>
                            </div>
                            <div class="text-slate-500 text-[11px]">
                                File: <span class="font-mono">{{ basename($evidencePath) }}</span>
                            </div>
                            <div class="pt-2 flex items-center gap-2">
                                <a href="{{ asset('storage/' . $evidencePath) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-bold bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition">
                                    Buka Foto di Tab Baru &nearr;
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Form Upload / Ganti Foto --}}
                <form action="{{ route('asesi.assessment.instrument.upload-evidence', [$assessment, strtoupper($code)]) }}" method="POST" enctype="multipart/form-data" class="space-y-3 pt-2">
                    @csrf
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                        {{ $evidencePath ? 'Unggah Ulang / Perbarui Foto Lembar Jawaban:' : 'Pilih Foto Lembar Jawaban Kertas:' }}
                    </label>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                        <input type="file" name="evidence_image" accept="image/*,application/pdf" required
                            class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-2 file:border-black file:text-xs file:font-bold file:bg-white file:text-slate-800 hover:file:bg-slate-100 border border-slate-300 rounded-lg p-1 bg-white cursor-pointer">
                        <button type="submit" class="px-5 py-2.5 text-xs font-black uppercase tracking-wider bg-slate-900 hover:bg-slate-800 text-white rounded-lg border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] transition shrink-0">
                            Unggah Foto Bukti &uarr;
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-500">Format yang didukung: JPG, JPEG, PNG, WEBP, atau scan PDF (Maksimal 10 MB).</p>
                </form>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
        <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">Catatan & Penilaian Asesor</h3>
        
        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-sm">
            @if($response?->assessor_notes)
                <p class="text-slate-800 whitespace-pre-line">{{ $response->assessor_notes }}</p>
                <div class="mt-2 text-xs text-slate-400">
                    Dinilai pada: {{ $response->assessor_signed_at ? \Carbon\Carbon::parse($response->assessor_signed_at)->format('d/m/Y H:i') : '-' }}
                </div>
            @else
                <p class="text-slate-500 italic text-xs">Asesor belum menginputkan catatan evaluasi untuk instrumen ini.</p>
            @endif
        </div>

        @if($response?->data_json)
            <div class="border border-slate-200 rounded-lg p-4 bg-white">
                <h4 class="text-xs font-bold text-slate-700 mb-2">Detail Poin Penilaian yang Direkam:</h4>
                <div class="text-xs font-mono bg-slate-50 p-3 rounded border border-slate-200 overflow-x-auto text-slate-600 max-h-48">
                    @foreach($response->data_json as $key => $item)
                        <div class="py-0.5"><span class="font-bold text-slate-800">{{ $key }}:</span> {{ is_array($item) ? json_encode($item) : $item }}</div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Form Feedback & Tanda Tangan Asesi --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="bg-slate-800 px-6 py-3 text-white">
            <h3 class="text-sm font-bold">Umpan Balik Asesi & Tanda Tangan Digital</h3>
            <p class="text-xs text-slate-300">Berikan catatan tanggapan Anda atas jalannya proses pengujian ini</p>
        </div>

        <form action="{{ route('asesi.assessment.instrument.feedback', [$assessment, strtoupper($code)]) }}" method="POST" class="p-6 space-y-6">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Umpan Balik / Komentar Anda terhadap Asesmen Ini:</label>
                <textarea name="asesi_feedback" rows="3" class="w-full text-xs p-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Contoh: Proses observasi berjalan objektif, arahan asesor jelas...">{{ old('asesi_feedback', $response?->asesi_feedback) }}</textarea>
            </div>

            <div class="flex flex-col md:flex-row items-end justify-between gap-6 pt-4 border-t border-slate-200">
                <div class="w-full md:w-80">
                    <x-signature-pad 
                        name="signature_data" 
                        :value="auth()->user()->profile?->signature_data" 
                        label="Tanda Tangan Asesi" 
                        height="130" 
                    />
                </div>

                <div class="w-full md:w-auto">
                    <button type="submit" class="w-full md:w-auto px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md transition flex items-center justify-center gap-2">
                        <span>Kirim Umpan Balik & Tanda Tangan</span>
                        <span>✓</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
