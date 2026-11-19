@extends('layouts.app')

@section('title', 'Tinjauan Instrumen FR.' . strtoupper($code))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6"
     x-data="{
         modalOpen: false,
         modalSrc: '',
         modalTitle: '',
         modalType: 'pdf',
         viewDocument(src, title, type = 'pdf') {
             this.modalSrc = src;
             this.modalTitle = title;
             this.modalType = type;
             this.modalOpen = true;
         }
     }">
    @php
        $evidencePathHeader = $response?->data_json['evidence_image_path'] ?? null;
        $hasEvaluatedHeader = (bool) $response?->assessor_signed_at || ! empty($response?->assessor_notes) || ! empty($response?->data_json['kuks']) || ! empty($response?->data_json['questions']);
    @endphp

    {{-- Header & Navigasi Terstruktur --}}
    <div class="space-y-3 pb-2 border-b border-slate-200/80">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('asesi.assessment.ruang-uji', $assessment) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 hover:text-brand-800 transition py-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Ruang Uji</span>
            </a>
            <div>
                @if(strtoupper($code) === 'IA01B')
                    @php
                        $isRevisionRequestedHeader = ! empty($response?->data_json['revision_requested']) || ($response?->data_json['revision_status'] ?? '') === 'REVISION_REQUESTED';
                    @endphp
                    @if($isRevisionRequestedHeader)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                            ⚠️ Wajib Revisi Berkas
                        </span>
                    @elseif($evidencePathHeader)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            ✓ Berkas Terunggah
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-200">
                            Wajib Unggah
                        </span>
                    @endif
                @else
                    @if($hasEvaluatedHeader)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            ✓ Diverifikasi Asesor
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            Menunggu Asesor
                        </span>
                    @endif
                @endif
            </div>
        </div>
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-snug">
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
            <p class="text-xs sm:text-sm text-slate-500 mt-1 leading-relaxed">
                Skema: <strong class="text-slate-800">{{ $assessment->schedule->scheme->name }}</strong> &bull; 
                Asesor: <strong class="text-slate-800">{{ $assessment->assessor?->full_name ?? 'Tim Asesor LSP' }}</strong>
            </p>
        </div>
    </div>

    @if($assessment->schedule?->is_closed)
        <div class="p-4 bg-emerald-50 border-2 border-emerald-300 rounded-2xl text-emerald-900 flex flex-col sm:flex-row items-start sm:items-center gap-3 shadow-xs">
            <span class="text-xl shrink-0">🔒</span>
            <div>
                <div class="font-bold text-sm">Jadwal Asesmen Telah Resmi Ditutup &amp; Dikunci</div>
                <p class="text-xs text-emerald-800 mt-0.5 leading-relaxed">Seluruh rangkaian uji telah selesai. Ruang uji ini sekarang dalam mode lihat (read-only).</p>
            </div>
        </div>
    @endif

    {{-- Ruang Unggah Khusus Lembar Kerja Fisik FR.IA.01B (Report Sheet) --}}
    @if(strtoupper($code) === 'IA01B')
        @php
            $evidencePath = $response?->data_json['evidence_image_path'] ?? null;
            $evidenceTime = $response?->data_json['evidence_uploaded_at'] ?? null;
            $evidenceName = $response?->data_json['evidence_original_name'] ?? 'Report_Sheet_Fisik.jpg';
            $evidenceUrl = ($response && $evidencePath) ? route('secure-file.evidence', $response) : '#';
            $isRevisionRequested = ! empty($response?->data_json['revision_requested']) || ($response?->data_json['revision_status'] ?? '') === 'REVISION_REQUESTED';
            $revisionNotes = $response?->data_json['revision_notes'] ?? null;
            $revisionTime = $response?->data_json['revision_requested_at'] ?? null;
            $revisionAssessor = $response?->data_json['revision_requested_by_name'] ?? ($assessment->assessor?->full_name ?? 'Asesor');
            $isResubmitted = ($response?->data_json['revision_status'] ?? '') === 'RESUBMITTED';
            $resubmittedTime = $response?->data_json['revision_resubmitted_at'] ?? null;
        @endphp

        {{-- Banner Permintaan Revisi dari Asesor --}}
        @if($isRevisionRequested)
            <div class="bg-rose-50 border-2 border-rose-300 rounded-2xl p-4 sm:p-5 text-rose-950 space-y-3 shadow-xs">
                <div class="flex items-start gap-3">
                    <span class="w-8 h-8 rounded-full bg-rose-600 text-white flex items-center justify-center font-bold text-sm shrink-0">⚠️</span>
                    <div class="space-y-1.5 flex-1">
                        <div class="text-xs font-bold uppercase tracking-wider text-rose-800">Perhatian: Asesor Meminta Revisi Lembar Report Sheet</div>
                        <h4 class="text-sm sm:text-base font-bold text-slate-900">Lembar Report Sheet Perlu Diperbaiki &amp; Diunggah Ulang</h4>
                        <p class="text-xs text-rose-900 leading-relaxed">
                            Asesor telah memeriksa berkas lembar report sheet yang Anda unggah sebelumnya dan meminta Anda untuk melakukan revisi dengan catatan instruksi berikut:
                        </p>
                        <div class="mt-2 p-3.5 bg-white rounded-xl border border-rose-200 text-xs text-slate-800 whitespace-pre-line shadow-2xs">
                            <span class="font-bold text-rose-700 block mb-1">Catatan Asesor ({{ $revisionAssessor }}):</span>
                            {{ $revisionNotes }}
                        </div>
                        @if($revisionTime)
                            <p class="text-[11px] text-rose-700 font-mono mt-1">Diminta pada: {{ \Carbon\Carbon::parse($revisionTime)->isoFormat('D MMMM Y, HH:mm') }} WIB</p>
                        @endif
                    </div>
                </div>
                <div class="p-3 bg-rose-100/70 rounded-xl text-xs text-rose-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span><strong>Langkah Perbaikan:</strong> Silakan perbaiki lembar fisik Anda sesuai catatan asesor di atas, foto/scan ulang secara jelas dan tegak lurus, lalu unggah kembali melalui form di bawah.</span>
                </div>
            </div>
        @elseif($isResubmitted)
            <div class="bg-blue-50 border-2 border-blue-300 rounded-2xl p-4 text-blue-950 flex items-center gap-3 shadow-xs">
                <span class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm shrink-0">✓</span>
                <div>
                    <div class="text-xs font-bold text-blue-950">Berkas Hasil Revisi Telah Berhasil Diunggah Ulang</div>
                    <p class="text-xs text-blue-800 mt-0.5">Berkas revisi Anda telah tersimpan dan saat ini sedang menunggu verifikasi serta penilaian ulang dari Asesor.</p>
                    @if($resubmittedTime)
                        <div class="text-[11px] text-blue-600 font-mono mt-0.5">Waktu unggah: {{ \Carbon\Carbon::parse($resubmittedTime)->isoFormat('D MMMM Y, HH:mm') }} WIB</div>
                    @endif
                </div>
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="bg-slate-900 text-white px-4 sm:px-6 py-3.5 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center space-x-2">
                    <span class="text-amber-400 font-bold text-xs uppercase tracking-wider font-mono">FR.IA.01B Report Sheet</span>
                    <span class="text-xs text-slate-300">— Ruang Unggah Lembar Pemeriksaan Teknis Fisik</span>
                </div>
                @if($isRevisionRequested)
                    <span class="self-start sm:self-auto px-2.5 py-0.5 text-[11px] font-bold bg-rose-500 text-white rounded">
                        ⚠️ Menunggu Revisi Anda
                    </span>
                @elseif($isResubmitted)
                    <span class="self-start sm:self-auto px-2.5 py-0.5 text-[11px] font-bold bg-blue-500 text-white rounded">
                        ✓ Revisi Terunggah
                    </span>
                @elseif($evidencePath)
                    <span class="self-start sm:self-auto px-2.5 py-0.5 text-[11px] font-bold bg-emerald-500 text-white rounded">
                        ✓ Lembar Fisik Terunggah
                    </span>
                @else
                    <span class="self-start sm:self-auto px-2.5 py-0.5 text-[11px] font-bold bg-amber-400 text-slate-950 rounded font-semibold">
                        Wajib Diunggah
                    </span>
                @endif
            </div>

            <div class="p-4 sm:p-6 space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-slate-700 leading-relaxed">
                    <strong>Petunjuk:</strong> Selesaikan pemeriksaan teknis pada unit kendaraan di bengkel TUK dan catat seluruh hasil pengukuran pada formulir Report Sheet fisik. Ambil foto atau pindai berkas fisik lembar Report Sheet tersebut secara jelas dan tegak lurus, kemudian unggah melalui tombol di bawah agar asesor dapat memverifikasi hasil pekerjaan Anda.
                </div>

                @if($evidencePath)
                    @php
                        $isPdfEvidence = str_ends_with(strtolower($evidencePath), '.pdf');
                        $evidenceDocType = $isPdfEvidence ? 'pdf' : (preg_match('/\.(docx?)$/i', $evidencePath) ? 'doc' : 'image');
                    @endphp
                    <div class="border border-slate-200 rounded-xl p-4 {{ $isRevisionRequested ? 'bg-rose-50/40 border-rose-200' : 'bg-slate-50' }} flex flex-col md:flex-row items-center gap-4">
                        <div class="w-full md:w-48 h-48 bg-slate-200 rounded-lg overflow-hidden border border-slate-300 flex items-center justify-center relative group cursor-pointer"
                             @click="viewDocument('{{ $evidenceUrl }}', '{{ addslashes($evidenceName) }}', '{{ $evidenceDocType }}')">
                            @if($isPdfEvidence)
                                <div class="text-center p-4">
                                    <span class="text-4xl text-rose-600 block mb-1">📄</span>
                                    <span class="text-[11px] font-bold text-slate-700 block truncate max-w-[160px] mx-auto">{{ $evidenceName }}</span>
                                    <span class="text-[10px] text-slate-500">PDF Report Sheet</span>
                                </div>
                            @elseif($evidenceDocType === 'doc')
                                <div class="text-center p-4">
                                    <span class="text-4xl text-blue-600 block mb-1">📝</span>
                                    <span class="text-[11px] font-bold text-slate-700 block truncate max-w-[160px] mx-auto">{{ $evidenceName }}</span>
                                    <span class="text-[10px] text-slate-500">Dokumen Report Sheet</span>
                                </div>
                            @else
                                <img src="{{ $evidenceUrl }}" alt="Bukti Jawaban Kertas" class="w-full h-full object-cover">
                            @endif
                            <div class="absolute inset-0 bg-black/60 text-white text-xs font-bold flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                🔍 Pratinjau Dokumen
                            </div>
                        </div>
                        <div class="flex-1 space-y-2 text-xs">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900 text-sm">Foto Lembar Report Sheet Fisik Asesi</span>
                                @if($isRevisionRequested)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-200 text-rose-900 border border-rose-300">
                                        Perlu Direvisi
                                    </span>
                                @endif
                            </div>
                            <div class="text-slate-600">
                                Diunggah pada: <span class="font-mono font-semibold">{{ \Carbon\Carbon::parse($evidenceTime)->isoFormat('D MMMM Y, HH:mm') }} WIB</span>
                            </div>
                            <div class="text-slate-500 text-[11px]">
                                Berkas: <span class="font-mono">{{ basename($evidencePath) }}</span>
                            </div>
                            <div class="pt-2 flex flex-wrap items-center gap-2">
                                <button type="button" @click="viewDocument('{{ $evidenceUrl }}', '{{ addslashes($evidenceName) }}', '{{ $evidenceDocType }}')" class="inline-flex items-center px-3.5 py-1.5 text-xs font-bold bg-brand-600 text-white rounded-lg hover:bg-brand-700 shadow-sm transition gap-1.5">
                                    <span>🔍 Pratinjau Dokumen (Modal)</span>
                                </button>
                                <a href="{{ $evidenceUrl }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition gap-1">
                                    <span>Tab Baru</span>
                                    <span>&nearr;</span>
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
                    {{-- Form Upload / Ganti Foto / Revisi --}}
                    <form action="{{ route('asesi.assessment.instrument.upload-evidence', [$assessment, 'IA01B']) }}" method="POST" enctype="multipart/form-data" class="space-y-3 pt-2 {{ $isRevisionRequested ? 'p-4 rounded-xl border-2 border-dashed border-rose-300 bg-rose-50/30' : '' }}">
                        @csrf
                        <label class="block text-xs font-bold {{ $isRevisionRequested ? 'text-rose-800' : 'text-slate-800' }} uppercase tracking-wider">
                            @if($isRevisionRequested)
                                Unggah Berkas Baru Hasil Revisi (Menggantikan Berkas Sebelumnya):
                            @elseif($evidencePath)
                                Unggah Ulang / Perbarui Berkas Report Sheet Fisik:
                            @else
                                Pilih Berkas Foto / Scan PDF Lembar Report Sheet:
                            @endif
                        </label>
                        <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900 mb-2">
                            <p class="font-bold text-amber-950 mb-0.5">⚠️ Tips Upload via HP / Android (Atasi Error "File tidak diketahui / lokasi file dipindahkan"):</p>
                            <p class="text-amber-800 leading-relaxed">Foto lembar fisik terlebih dahulu memakai kamera HP Anda. Saat menekan tombol pilih file, buka melalui menu <strong>"Galeri"</strong> atau <strong>"File Manager"</strong> (<strong>hindari</strong> memilih dari tab <em>"Terbaru / Recent"</em>).</p>
                        </div>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <input type="file" name="evidence_image" accept="image/jpeg,image/png,image/jpg,image/webp,application/pdf,.jpg,.jpeg,.png,.webp,.pdf" required
                                class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-100 file:text-slate-800 hover:file:bg-slate-200 border border-slate-300 rounded-lg p-1 bg-white cursor-pointer">
                            <button type="submit" class="px-5 py-2.5 text-xs font-bold uppercase tracking-wider {{ $isRevisionRequested ? 'bg-rose-600 hover:bg-rose-700' : 'bg-brand-600 hover:bg-brand-700' }} text-white rounded-lg shadow-sm transition shrink-0 cursor-pointer">
                                {{ $isRevisionRequested ? 'Unggah Berkas Revisi ↑' : 'Unggah Berkas Fisik ↑' }}
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-500">Format yang didukung: JPG, JPEG, PNG, WEBP, atau scan PDF (Maksimal 15 MB).</p>
                    </form>
                @endif
            </div>
        </div>
    @endif

    {{-- Tinjauan Tugas Praktik Demonstrasi FR.IA.02 (Hanya Instruksi Praktik, Tanpa Form Upload) --}}
    @if(strtoupper($code) === 'IA02')
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-6 space-y-3">
            <div class="flex items-center space-x-2 border-b border-slate-100 pb-3">
                <span class="text-amber-600 font-bold text-xs uppercase tracking-wider font-mono">FR.IA.02</span>
                <h3 class="text-base font-bold text-slate-900">Tugas Praktik Demonstrasi</h3>
            </div>
            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                Instrumen ini memuat skenario dan instruksi pelaksanaan tugas praktik demonstrasi di Tempat Uji Kompetensi (TUK). Pelaksanaan instruksi unjuk kerja diobservasi langsung oleh Asesor. Dokumen ini disahkan bersama oleh Asesor dan Asesi.
            </p>
        </div>
    @endif

    {{-- Catatan & Penilaian Asesor (Hanya untuk instrumen selain IA.02) --}}
    @if(strtoupper($code) !== 'IA02')
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 pb-3 gap-2">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Evaluasi Asesor</span>
                    <h3 class="text-base font-bold text-slate-900">Catatan Rekomendasi & Hasil Asesmen</h3>
                </div>
                @if($response?->assessor_signed_at)
                    <span class="self-start sm:self-auto inline-flex items-center gap-1 px-2.5 py-1 rounded bg-emerald-50 text-emerald-800 text-xs font-bold border border-emerald-200">
                        <span>✓</span> Telah Diverifikasi Asesor
                    </span>
                @endif
            </div>
            
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs leading-relaxed">
                @if($response?->assessor_notes)
                    <div class="text-slate-800 whitespace-pre-line text-sm">{{ $response->assessor_notes }}</div>
                    <div class="mt-3 pt-2 border-t border-slate-200 text-[11px] text-slate-500 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-1">
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
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Tanda Tangan Asesor</span>
                <span class="text-xs text-slate-700 font-semibold">{{ $assessment->assessor?->full_name ?? 'Asesor Kompetensi LSP' }}</span>
                @if($response->assessor_signed_at)
                    <span class="text-[11px] text-slate-400 block mt-0.5">Tercatat: {{ \Carbon\Carbon::parse($response->assessor_signed_at)->isoFormat('D MMMM Y, HH:mm') }} WIB</span>
                @endif
            </div>
            <div class="w-48 h-20 border border-slate-200 rounded-xl p-2 bg-white shrink-0 flex items-center justify-center">
                <img src="{{ $response->assessor_signature }}" alt="Tanda Tangan Asesor" class="w-full h-full object-contain">
            </div>
        </div>
    @endif

    {{-- Tanda Tangan Asesi (Disematkan Otomatis) --}}
    @php
        $effectiveAsesiSig = $response?->asesi_signature ?? $assessment->asesi_signature ?? auth()->user()->profile?->signature_data;
    @endphp
    @if($effectiveAsesiSig)
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Tanda Tangan Asesi</span>
                <span class="text-xs text-slate-600">Disematkan dari data permohonan sertifikasi Anda</span>
            </div>
            <div class="w-48 h-20 border border-slate-200 rounded-xl p-2 bg-slate-50 shrink-0 flex items-center justify-center">
                <img src="{{ $effectiveAsesiSig }}" alt="Tanda Tangan Asesi" class="w-full h-full object-contain">
            </div>
        </div>
    @endif

    {{-- Lightbox Modal Pratinjau Dokumen Report Sheet --}}
    <div x-show="modalOpen" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4"
         @keydown.escape.window="modalOpen = false">
        <div class="relative w-full max-w-5xl bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200 flex flex-col max-h-[92vh]"
             @click.away="modalOpen = false">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between items-start gap-2.5 px-4 sm:px-6 py-3.5 sm:py-4 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="text-xs font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-brand-100 text-brand-700" x-text="modalType.toUpperCase()"></span>
                    <h3 class="text-sm font-bold text-slate-800 truncate" x-text="modalTitle"></h3>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a :href="modalSrc" target="_blank" download class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">
                        <span>Unduh / Buka Tab Baru ↗</span>
                    </a>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="p-4 bg-slate-900/5 flex-1 flex items-center justify-center overflow-auto min-h-[350px]">
                <template x-if="modalType === 'image'">
                    <img :src="modalSrc" class="max-h-[78vh] w-auto max-w-full rounded shadow object-contain">
                </template>
                <template x-if="modalType === 'pdf'">
                    <iframe :src="modalSrc" class="w-full h-[78vh] rounded border border-slate-300 bg-white shadow-inner"></iframe>
                </template>
                <template x-if="modalType === 'doc'">
                    <div class="text-center p-8 bg-white rounded-xl border border-slate-200 shadow-sm max-w-md mx-auto space-y-4">
                        <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mx-auto text-3xl font-black">
                            📄
                        </div>
                        <div>
                            <div class="font-bold text-slate-800 text-sm" x-text="modalTitle"></div>
                            <p class="text-xs text-slate-500 mt-1">Dokumen Microsoft Word / Office tidak dapat dipratinjau langsung di dalam browser.</p>
                        </div>
                        <a :href="modalSrc" target="_blank" download class="inline-flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                            <span>Unduh File Sekarang</span>
                            <span>&darr;</span>
                        </a>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
