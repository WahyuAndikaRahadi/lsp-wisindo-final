@extends('layouts.app')

@section('title', 'Tinjauan Instrumen FR.' . strtoupper($code))

@section('content')
<div class="max-w-4xl mx-auto py-6 space-y-6">
    @if($assessment->schedule?->is_closed)
        <div class="p-4 bg-emerald-50 border-2 border-emerald-300 rounded-xl text-emerald-900 flex items-center gap-3 shadow-xs">
            <span class="text-xl">🔒</span>
            <div>
                <div class="font-bold text-sm">Jadwal Asesmen Telah Resmi Ditutup &amp; Dikunci</div>
                <p class="text-xs text-emerald-800 mt-0.5">Seluruh rangkaian uji telah selesai. Ruang uji ini sekarang dalam mode lihat (read-only).</p>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <a href="{{ route('asesi.assessment.ruang-uji', $assessment) }}" class="text-xs text-brand-600 hover:underline font-semibold block mb-1">
                &larr; Kembali ke Ruang Uji
            </a>
            <h1 class="text-xl font-bold text-slate-900">
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
            @php
                $evidencePathHeader = $response?->data_json['evidence_image_path'] ?? null;
                $hasEvaluatedHeader = (bool) $response?->assessor_signed_at || ! empty($response?->assessor_notes) || ! empty($response?->data_json['kuks']) || ! empty($response?->data_json['questions']);
            @endphp
            @if(strtoupper($code) === 'IA01B')
                @if($evidencePathHeader)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        ✓ Berkas Fisik Terunggah
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-200">
                        Wajib Unggah Berkas
                    </span>
                @endif
            @else
                @if($hasEvaluatedHeader)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        ✓ Telah Diverifikasi Asesor
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        Menunggu Penilaian Asesor
                    </span>
                @endif
            @endif
        </div>
    </div>

    {{-- Ruang Unggah Khusus Lembar Kerja Fisik FR.IA.01B (Report Sheet) --}}
    @if(strtoupper($code) === 'IA01B')
        @php
            $evidencePath = $response?->data_json['evidence_image_path'] ?? null;
            $evidenceTime = $response?->data_json['evidence_uploaded_at'] ?? null;
            $evidenceName = $response?->data_json['evidence_original_name'] ?? 'Report_Sheet_Fisik.jpg';
            $evidenceUrl = ($response && $evidencePath) ? route('secure-file.evidence', $response) : '#';
        @endphp
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="bg-slate-900 text-white px-6 py-3.5 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="text-amber-400 font-bold text-xs uppercase tracking-wider font-mono">FR.IA.01B Report Sheet</span>
                    <span class="text-xs text-slate-300">— Ruang Unggah Lembar Pemeriksaan Teknis Fisik</span>
                </div>
                @if($evidencePath)
                    <span class="px-2.5 py-0.5 text-[11px] font-bold bg-emerald-500 text-white rounded">
                        ✓ Lembar Fisik Terunggah
                    </span>
                @else
                    <span class="px-2.5 py-0.5 text-[11px] font-bold bg-amber-400 text-slate-950 rounded font-semibold">
                        Wajib Diunggah
                    </span>
                @endif
            </div>

            <div class="p-6 space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-slate-700 leading-relaxed">
                    <strong>Petunjuk:</strong> Selesaikan pemeriksaan teknis pada unit kendaraan di bengkel TUK dan catat seluruh hasil pengukuran pada formulir Report Sheet fisik. Ambil foto atau pindai berkas fisik lembar Report Sheet tersebut secara jelas dan tegak lurus, kemudian unggah melalui tombol di bawah agar asesor dapat memverifikasi hasil pekerjaan Anda.
                </div>

                @if($evidencePath)
                    <div class="border border-slate-200 rounded-xl p-4 bg-slate-50 flex flex-col md:flex-row items-center gap-4">
                        <div class="w-full md:w-48 h-48 bg-slate-200 rounded-lg overflow-hidden border border-slate-300 flex items-center justify-center relative group">
                            @if(str_ends_with(strtolower($evidencePath), '.pdf'))
                                <div class="text-center p-4">
                                    <span class="text-4xl text-rose-600 block mb-1">📄</span>
                                    <span class="text-[11px] font-bold text-slate-700 block truncate">{{ $evidenceName }}</span>
                                </div>
                            @else
                                <img src="{{ $evidenceUrl }}" alt="Bukti Jawaban Kertas" class="w-full h-full object-cover">
                            @endif
                            <a href="{{ $evidenceUrl }}" target="_blank" class="absolute inset-0 bg-black/60 text-white text-xs font-bold flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                🔍 Buka Ukuran Penuh
                            </a>
                        </div>
                        <div class="flex-1 space-y-2 text-xs">
                            <div class="font-bold text-slate-900 text-sm">Foto Lembar Report Sheet Fisik Asesi</div>
                            <div class="text-slate-600">
                                Diunggah pada: <span class="font-mono font-semibold">{{ \Carbon\Carbon::parse($evidenceTime)->isoFormat('D MMMM Y, HH:mm') }} WIB</span>
                            </div>
                            <div class="text-slate-500 text-[11px]">
                                Berkas: <span class="font-mono">{{ basename($evidencePath) }}</span>
                            </div>
                            <div class="pt-2 flex items-center gap-2">
                                <a href="{{ $evidenceUrl }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-bold bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition">
                                    Buka Berkas di Tab Baru &nearr;
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                @if($assessment->schedule?->is_closed)
                    <div class="p-3.5 bg-slate-100 border border-slate-200 rounded-lg text-slate-600 text-xs flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>Jadwal asesmen telah resmi ditutup dan dikunci. Pengunggahan berkas fisik tidak lagi tersedia (mode lihat).</span>
                    </div>
                @else
                    {{-- Form Upload / Ganti Foto --}}
                    <form action="{{ route('asesi.assessment.instrument.upload-evidence', [$assessment, 'IA01B']) }}" method="POST" enctype="multipart/form-data" class="space-y-3 pt-2">
                        @csrf
                        <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                            {{ $evidencePath ? 'Unggah Ulang / Perbarui Berkas Report Sheet Fisik:' : 'Pilih Berkas Foto / Scan PDF Lembar Report Sheet:' }}
                        </label>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <input type="file" name="evidence_image" accept="image/*,application/pdf" required
                                class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-800 hover:file:bg-slate-200 border border-slate-300 rounded-lg p-1 bg-white cursor-pointer">
                            <button type="submit" class="px-5 py-2.5 text-xs font-bold uppercase tracking-wider bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition shrink-0">
                                Unggah Berkas Fisik &uarr;
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-500">Format yang didukung: JPG, JPEG, PNG, WEBP, atau scan PDF (Maksimal 10 MB).</p>
                    </form>
                @endif
            </div>
        </div>
    @endif

    {{-- Tinjauan Tugas Praktik Demonstrasi FR.IA.02 (Hanya Instruksi Praktik, Tanpa Form Upload) --}}
    @if(strtoupper($code) === 'IA02')
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-3">
            <div class="flex items-center space-x-2 border-b border-slate-100 pb-3">
                <span class="text-amber-600 font-bold text-xs uppercase tracking-wider font-mono">FR.IA.02</span>
                <h3 class="text-base font-bold text-slate-900">Tugas Praktik Demonstrasi</h3>
            </div>
            <p class="text-xs text-slate-600 leading-relaxed">
                Instrumen ini memuat skenario dan instruksi pelaksanaan tugas praktik demonstrasi di Tempat Uji Kompetensi (TUK). Pelaksanaan instruksi unjuk kerja diobservasi langsung oleh Asesor. Dokumen ini disahkan bersama oleh Asesor dan Asesi.
            </p>
        </div>
    @endif

    {{-- Catatan & Penilaian Asesor (Hanya untuk instrumen selain IA.02) --}}
    @if(strtoupper($code) !== 'IA02')
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Evaluasi Asesor</span>
                    <h3 class="text-base font-bold text-slate-900">Catatan Rekomendasi & Hasil Asesmen</h3>
                </div>
                @if($response?->assessor_signed_at)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-200">
                        <span>✓</span> Telah Diverifikasi Asesor
                    </span>
                @endif
            </div>
            
            <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-xs leading-relaxed">
                @if($response?->assessor_notes)
                    <div class="text-slate-800 whitespace-pre-line text-sm">{{ $response->assessor_notes }}</div>
                    <div class="mt-3 pt-2 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between">
                        <span>Asesor: <strong>{{ $assessment->assessor?->full_name ?? 'Asesor Kompetensi LSP' }}</strong></span>
                        <span>Waktu Penilaian: <strong>{{ \Carbon\Carbon::parse($response->assessor_signed_at)->isoFormat('D MMMM Y, HH:mm') }} WIB</strong></span>
                    </div>
                @else
                    <p class="text-slate-500 italic">Asesor belum menginputkan catatan evaluasi tertulis untuk instrumen ini.</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Tanda Tangan Asesor --}}
    @if($response?->assessor_signature)
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Tanda Tangan Asesor</span>
                <span class="text-xs text-slate-700 font-semibold">{{ $assessment->assessor?->full_name ?? 'Asesor Kompetensi LSP' }}</span>
                @if($response->assessor_signed_at)
                    <span class="text-[11px] text-slate-400 block mt-0.5">Tercatat: {{ \Carbon\Carbon::parse($response->assessor_signed_at)->isoFormat('D MMMM Y, HH:mm') }} WIB</span>
                @endif
            </div>
            <div class="border border-slate-200 rounded-lg p-2 bg-white shrink-0">
                <img src="{{ $response->assessor_signature }}" alt="Tanda Tangan Asesor" class="max-h-14 object-contain">
            </div>
        </div>
    @endif

    {{-- Tanda Tangan Asesi (Disematkan Otomatis) --}}
    @php
        $effectiveAsesiSig = $response?->asesi_signature ?? $assessment->asesi_signature ?? auth()->user()->profile?->signature_data;
    @endphp
    @if($effectiveAsesiSig)
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Tanda Tangan Asesi</span>
                <span class="text-xs text-slate-600">Disematkan dari data permohonan sertifikasi Anda</span>
            </div>
            <div class="border border-slate-200 rounded-lg p-2 bg-slate-50 shrink-0">
                <img src="{{ $effectiveAsesiSig }}" alt="Tanda Tangan Asesi" class="max-h-14 object-contain">
            </div>
        </div>
    @endif
</div>
@endsection
