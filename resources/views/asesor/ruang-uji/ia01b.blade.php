@php
    $isCompleted = (bool) ($response?->assessor_signed_at || ! empty($response?->assessor_signature));
    $isScheduleClosed = (bool) ($assessment->schedule?->isClosedForAssessor());
    $isRevisionRequested = ! empty($response?->data_json['revision_requested']) || ($response?->data_json['revision_status'] ?? '') === 'REVISION_REQUESTED';
    $revisionNotes = $response?->data_json['revision_notes'] ?? null;
    $revisionTime = $response?->data_json['revision_requested_at'] ?? null;
    $revisionByName = $response?->data_json['revision_requested_by_name'] ?? ($assessment->assessor?->full_name ?? 'Asesor');
    $isResubmitted = ($response?->data_json['revision_status'] ?? '') === 'RESUBMITTED';
    $resubmittedTime = $response?->data_json['revision_resubmitted_at'] ?? null;
    $evidenceHistory = $response?->data_json['evidence_history'] ?? [];
@endphp
@extends('layouts.app', ['title' => 'FR.IA.01B - Lembar Pemeriksaan Teknis (Report Sheet)'])

@section('content')
<div class="space-y-6" x-data="{
    isEditing: {{ ($isCompleted || $isScheduleClosed) ? 'false' : 'true' }},
    modalOpen: false,
    modalSrc: '',
    modalTitle: '',
    modalType: 'pdf',
    revisionModalOpen: false,
    viewDocument(src, title, type = 'pdf') {
        this.modalSrc = src;
        this.modalTitle = title;
        this.modalType = type;
        this.modalOpen = true;
    },
    setAllItems(val) {
        if (!this.isEditing) return;
        const targetVal = (val === 'K' || val === 'SESUAI') ? 'sesuai' : 'tidak';
        document.querySelectorAll(`.item-radio-${targetVal}`).forEach(el => {
            el.checked = true;
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }
}">
    @if($isScheduleClosed)
        <div class="p-4 bg-emerald-50 border-2 border-emerald-300 rounded-xl text-emerald-900 flex items-center gap-3 shadow-xs">
            <span class="text-xl">🔒</span>
            <div>
                <div class="font-bold text-sm">Jadwal Asesmen Telah Resmi Ditutup &amp; Dikunci</div>
                <p class="text-xs text-emerald-800 mt-0.5">Seluruh instrumen pada jadwal ini berada dalam mode lihat (read-only). Data pengukuran dan penilaian tidak dapat diubah lagi.</p>
            </div>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia01b']) }}" class="text-xs text-brand-600 hover:text-brand-700 font-bold">&larr; Kembali ke Daftar Asesi IA.01B</a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">FR.IA.01B – Lembar Hasil Pengukuran &amp; Pemeriksaan Teknis (Report Sheet)</h1>
            <p class="text-sm text-slate-500">Asesi: <strong>{{ $assessment->asesi->full_name }}</strong> | Skema: <strong>{{ $assessment->schedule->scheme->name }}</strong></p>
        </div>

        @if($isCompleted && ! $isScheduleClosed)
            <div class="flex items-center gap-2">
                <button type="button" x-show="!isEditing" @click="isEditing = true" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg shadow-xs transition flex items-center gap-1.5">
                    <span>✎ Edit Penilaian</span>
                </button>
                <button type="button" x-show="isEditing" @click="isEditing = false" style="display: none;" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold rounded-lg transition">
                    Batal Edit
                </button>
            </div>
        @endif
    </div>

    @if($isCompleted)
        <div class="bg-emerald-50 border-2 border-emerald-300 rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shrink-0">✓</span>
                <div>
                    <div class="text-xs font-bold text-emerald-950">Penilaian FR.IA.01B Telah Selesai &amp; Tersimpan</div>
                    <p class="text-[11px] text-emerald-800" x-show="!isEditing">Field penilaian dalam mode terkunci (read-only). Klik tombol 'Edit Penilaian' untuk mengubah.</p>
                    <p class="text-[11px] text-amber-800 font-semibold" x-show="isEditing" style="display: none;">Mode Pengeditan Aktif. Silakan sesuaikan hasil pengukuran teknis dan catatan di bawah.</p>
                </div>
            </div>
            @if($response?->assessor_signed_at)
                <div class="text-right text-[11px] text-emerald-700 font-mono">
                    Dinilai: {{ \Carbon\Carbon::parse($response->assessor_signed_at)->isoFormat('D MMM Y, HH:mm') }} WIB
                </div>
            @endif
        </div>
    @endif

    {{-- Banner Status Revisi --}}
    @if($isRevisionRequested)
        <div class="bg-rose-50 border-2 border-rose-300 rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xs">
            <div class="flex items-start gap-3">
                <span class="w-8 h-8 rounded-full bg-rose-600 text-white flex items-center justify-center font-bold text-sm shrink-0">⚠️</span>
                <div class="space-y-1">
                    <div class="text-xs font-bold text-rose-950 uppercase tracking-wide">Status: Menunggu Pengunggahan Revisi dari Asesi</div>
                    <p class="text-xs text-rose-900 leading-relaxed">
                        Anda telah meminta asesi <strong>{{ $assessment->asesi->full_name }}</strong> untuk merevisi berkas Report Sheet fisik ini.
                    </p>
                    <div class="mt-2 text-xs bg-white/95 p-3 rounded-lg border border-rose-200 text-slate-800 shadow-2xs">
                        <strong class="text-rose-700 block mb-0.5">Catatan Instruksi Revisi untuk Asesi:</strong>
                        <span class="whitespace-pre-line">{{ $revisionNotes }}</span>
                    </div>
                    @if($revisionTime)
                        <div class="text-[11px] text-rose-700 font-mono mt-1">
                            Diminta pada: {{ \Carbon\Carbon::parse($revisionTime)->isoFormat('D MMMM Y, HH:mm') }} WIB &bull; Oleh: {{ $revisionByName }}
                        </div>
                    @endif
                </div>
            </div>
            @if(! $isScheduleClosed)
                <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                    <button type="button" @click="revisionModalOpen = true" class="px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-bold transition shadow-xs cursor-pointer">
                        Ubah Catatan Revisi
                    </button>
                    <form action="{{ route('asesor.assessment.ia01b.cancel-revision', $assessment->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan permintaan revisi ini?');">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 bg-white hover:bg-slate-100 text-rose-700 border border-rose-300 rounded-lg text-xs font-bold transition shadow-2xs cursor-pointer">
                            Batalkan Revisi
                        </button>
                    </form>
                </div>
            @endif
        </div>
    @elseif($isResubmitted)
        <div class="bg-blue-50 border-2 border-blue-300 rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm shrink-0">✓</span>
                <div>
                    <div class="text-xs font-bold text-blue-950 uppercase tracking-wide">Berkas Hasil Revisi Telah Diunggah Asesi</div>
                    <p class="text-xs text-blue-800 mt-0.5 leading-relaxed">
                        Asesi telah mengunggah berkas perbaikan terbaru. Silakan periksa kembali hasil pengukuran fisik di bawah dan simpan penilaian akhir Anda.
                    </p>
                    @if($resubmittedTime)
                        <div class="text-[11px] text-blue-700 font-mono mt-1">
                            Diunggah ulang pada: {{ \Carbon\Carbon::parse($resubmittedTime)->isoFormat('D MMMM Y, HH:mm') }} WIB
                        </div>
                    @endif
                </div>
            </div>
            <span class="px-3 py-1.5 text-xs font-bold bg-blue-600 text-white rounded-lg shrink-0 shadow-xs">
                Menunggu Penilaian Ulang
            </span>
        </div>
    @endif

    <form action="{{ route('asesor.ruang-uji.ia01b.store', $assessment->id) }}" method="POST" class="space-y-6">
        @csrf

        {{-- Inspeksi Bukti Lembar Fisik Unggahan Asesi (Report Sheet FR.IA.01B) --}}
        @php
            $evidencePath = $response?->data_json['evidence_image_path'] ?? null;
            $evidenceTime = $response?->data_json['evidence_uploaded_at'] ?? null;
            $evidenceName = $response?->data_json['evidence_original_name'] ?? 'Report_Sheet_Fisik.jpg';
            $evidenceUrl = ($response && $evidencePath) ? route('secure-file.evidence', $response) : '#';
        @endphp
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="bg-slate-900 text-white px-6 py-3.5 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center space-x-2.5">
                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-amber-400 text-slate-950">
                        FR.IA.01B
                    </span>
                    <span class="text-xs font-bold uppercase tracking-wider text-white">Lembar Pemeriksaan Teknis Fisik (Report Sheet Unggahan Asesi)</span>
                </div>
                @if($isRevisionRequested)
                    <span class="px-2.5 py-0.5 text-[11px] font-bold bg-rose-500 text-white rounded">
                        ⚠️ Menunggu Revisi Asesi
                    </span>
                @elseif($isResubmitted)
                    <span class="px-2.5 py-0.5 text-[11px] font-bold bg-blue-500 text-white rounded">
                        ✓ Berkas Revisi Diunggah Asesi
                    </span>
                @elseif($evidencePath)
                    <span class="px-2.5 py-0.5 text-[11px] font-bold bg-emerald-500 text-white rounded">
                        ✓ Lembar Fisik Telah Diunggah Asesi
                    </span>
                @else
                    <span class="px-2.5 py-0.5 text-[11px] font-bold bg-amber-500 text-slate-900 rounded">
                        Menunggu Unggahan Asesi
                    </span>
                @endif
            </div>

            <div class="p-6">
                @if($evidencePath)
                    @php
                        $isPdf = str_ends_with(strtolower($evidencePath), '.pdf');
                        $fileType = $isPdf ? 'pdf' : (preg_match('/\.(docx?)$/i', $evidencePath) ? 'doc' : 'image');
                    @endphp
                    <div class="flex flex-col md:flex-row items-center gap-6 p-4 bg-slate-50 rounded-xl border border-slate-200">
                        {{-- Preview Box --}}
                        <div class="w-full md:w-64 h-48 bg-white rounded-lg overflow-hidden border border-slate-300 flex items-center justify-center relative group cursor-pointer shrink-0 shadow-xs"
                             @click="viewDocument('{{ $evidenceUrl }}', '{{ addslashes($evidenceName) }}', '{{ $fileType }}')">
                            @if($isPdf)
                                <div class="text-center p-4">
                                    <span class="text-4xl text-rose-600 font-black block mb-1">📄</span>
                                    <div class="text-xs font-bold text-slate-800 truncate max-w-[200px]">{{ $evidenceName }}</div>
                                    <div class="text-[11px] text-slate-500">Dokumen PDF Report Sheet</div>
                                </div>
                            @elseif($fileType === 'doc')
                                <div class="text-center p-4">
                                    <span class="text-4xl text-blue-600 font-black block mb-1">📝</span>
                                    <div class="text-xs font-bold text-slate-800 truncate max-w-[200px]">{{ $evidenceName }}</div>
                                    <div class="text-[11px] text-slate-500">Dokumen Word / Docx</div>
                                </div>
                            @else
                                <img src="{{ $evidenceUrl }}" alt="Report Sheet Fisik Asesi" class="w-full h-full object-contain transition duration-200 group-hover:scale-105">
                            @endif
                            <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <span class="px-3 py-1.5 bg-white/95 text-slate-900 rounded-lg text-xs font-bold shadow">🔍 Perbesar Dokumen</span>
                            </div>
                        </div>

                        {{-- Actions & File Info --}}
                        <div class="flex-1 space-y-3 w-full">
                            <div>
                                <div class="text-sm font-bold text-slate-900 truncate">{{ $evidenceName }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    Diunggah pada <span class="font-mono font-semibold text-slate-700">{{ \Carbon\Carbon::parse($evidenceTime)->isoFormat('D MMMM Y, HH:mm') }} WIB</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2.5 pt-1">
                                <button type="button" @click="viewDocument('{{ $evidenceUrl }}', '{{ addslashes($evidenceName) }}', '{{ $fileType }}')" class="px-4 py-2 text-xs font-bold uppercase tracking-wider bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                                    <span>🔍 Pratinjau Dokumen</span>
                                </button>
                                <a href="{{ $evidenceUrl }}" target="_blank" class="px-4 py-2 text-xs font-bold uppercase tracking-wider bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-lg shadow-xs transition inline-flex items-center justify-center gap-1">
                                    <span>Buka Tab Baru ↗</span>
                                </a>
                                @if(! $isScheduleClosed)
                                    <button type="button" @click="revisionModalOpen = true" class="px-4 py-2 text-xs font-bold uppercase tracking-wider {{ $isRevisionRequested ? 'bg-amber-500 hover:bg-amber-600' : 'bg-rose-600 hover:bg-rose-700' }} text-white rounded-lg shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span>{{ $isRevisionRequested ? 'Ubah Catatan Revisi' : 'Minta Revisi / Unggah Ulang' }}</span>
                                    </button>
                                @endif
                            </div>

                            @if(count($evidenceHistory) > 0)
                                <div class="pt-2 border-t border-slate-200" x-data="{ openHistory: false }">
                                    <button type="button" @click="openHistory = !openHistory" class="text-xs font-bold text-slate-600 hover:text-brand-600 inline-flex items-center gap-1.5 cursor-pointer">
                                        <span>📁 Riwayat Berkas Sebelumnya ({{ count($evidenceHistory) }})</span>
                                        <span class="text-[10px] text-slate-400 font-mono" x-text="openHistory ? '▲ Sembunyikan' : '▼ Buka'"></span>
                                    </button>
                                    <div x-show="openHistory" x-cloak class="mt-2 space-y-1.5">
                                        @foreach($evidenceHistory as $hIdx => $hItem)
                                            @php
                                                $hUrl = route('secure-file.evidence', ['response' => $response, 'history_index' => $hIdx]);
                                                $hIsPdf = str_ends_with(strtolower($hItem['path'] ?? ''), '.pdf');
                                                $hType = $hIsPdf ? 'pdf' : (preg_match('/\.(docx?)$/i', $hItem['path'] ?? '') ? 'doc' : 'image');
                                            @endphp
                                            <div class="p-2 bg-white rounded-lg border border-slate-200 text-[11px] flex items-center justify-between gap-2">
                                                <div class="min-w-0 flex-1">
                                                    <span class="font-bold text-slate-800">{{ $hItem['original_name'] ?? 'Berkas Revisi #'.($hIdx + 1) }}</span>
                                                    <span class="text-slate-400 mx-1">&bull;</span>
                                                    <span class="text-slate-500">Diunggah: {{ !empty($hItem['uploaded_at']) ? \Carbon\Carbon::parse($hItem['uploaded_at'])->isoFormat('D MMM Y, HH:mm') : '-' }}</span>
                                                    @if(!empty($hItem['revision_notes']))
                                                        <div class="text-rose-700 italic text-[10px] mt-0.5">Catatan: {{ $hItem['revision_notes'] }}</div>
                                                    @endif
                                                </div>
                                                <button type="button" @click="viewDocument('{{ $hUrl }}', '{{ addslashes($hItem['original_name'] ?? 'Berkas Sebelumnya') }}', '{{ $hType }}')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded text-slate-700 text-[10px] font-bold cursor-pointer">
                                                    Lihat
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="p-8 rounded-xl border-2 border-dashed border-amber-300 bg-amber-50/50 text-center space-y-3">
                        <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center mx-auto">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div class="max-w-md mx-auto space-y-1">
                            <h3 class="text-sm font-bold text-amber-950">Menunggu Asesi Mengunggah Lembar Report Sheet Fisik</h3>
                            <p class="text-xs text-amber-800 mt-1 leading-relaxed">
                                Asesi <strong>{{ $assessment->asesi->full_name }}</strong> belum mengunggah foto / scan berkas lembar kerja pemeriksaan fisik (FR.IA.01B). Asesi dapat mengunggahnya secara mandiri melalui akun portal asesi.
                            </p>
                            <p class="text-[11px] text-slate-500 mt-2">
                                Setelah berkas diunggah oleh asesi, dokumen akan langsung tampil di sini untuk Anda periksa dan tandatangani.
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Single Table for All Units with Universal Fast Actions at Top --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            {{-- Bagian Atas Table: Header & Tombol Universal Semua Sesuai / Semua Tidak --}}
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold text-brand-700 font-mono">FORMULIR TEKNIS SPESIFIK SKEMA</span>
                    <h2 class="text-base font-bold text-slate-900">Item Pengukuran &amp; Lembar Kerja Asesi</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Verifikasi hasil pengukuran fisik dan kondisi komponen yang dikerjakan oleh asesi</p>
                </div>
                <div class="flex items-center gap-2.5 flex-wrap" x-show="isEditing">
                    <span class="text-xs font-semibold text-slate-500 mr-1 hidden sm:inline">Aksi Cepat Verifikasi:</span>
                    <button type="button" @click="setAllItems('SESUAI')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        Semua Sesuai
                    </button>
                    <button type="button" @click="setAllItems('TIDAK')" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white rounded-lg text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Semua Tidak
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="hidden md:table-header-group bg-slate-100/70 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3 w-16 text-center">No</th>
                            <th class="px-6 py-3">Item Pemeriksaan / Bagian Diukur</th>
                            <th class="px-6 py-3 w-64">Spesifikasi Standar</th>
                            <th class="px-6 py-3 w-48">Hasil Pengukuran Asesi</th>
                            <th class="px-6 py-3 text-center w-44">Verifikasi (Sesuai / Tidak)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @php
                            $lastUnitCode = null;
                        @endphp
                        @forelse($reportSheetItems ?? [] as $idx => $item)
                            @php
                                $val = $savedAnswers[$idx]['result'] ?? old("items.{$idx}.result");
                                $measured = $savedAnswers[$idx]['measured'] ?? old("items.{$idx}.measured");

                                preg_match('/([A-Z0-9]+\.[A-Z0-9]+\.[A-Z0-9]+\.[A-Z0-9]+)/', $item['item'], $m);
                                $matchedCode = $m[1] ?? null;
                                $matchedUnit = $matchedCode ? ($units->firstWhere('code', $matchedCode)) : null;
                            @endphp

                            @if($matchedUnit && $matchedCode !== $lastUnitCode)
                                @php $lastUnitCode = $matchedCode; @endphp
                                <tr class="bg-slate-100/90 border-t-2 border-b border-slate-200">
                                    <td colspan="5" class="px-4 sm:px-6 py-3.5 bg-slate-100">
                                        <div class="flex items-center gap-2.5">
                                            <span class="text-xs font-bold text-brand-700 font-mono bg-brand-50 border border-brand-200 px-2.5 py-1 rounded shadow-2xs">
                                                {{ $matchedUnit->code }}
                                            </span>
                                            <span class="text-xs sm:text-sm font-bold text-slate-900 leading-snug">
                                                {{ $matchedUnit->title }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endif

                            <tr class="flex flex-col md:table-row p-4 md:p-0 hover:bg-slate-50/50 transition space-y-2.5 md:space-y-0">
                                <td class="hidden md:table-cell px-6 py-3 text-slate-500 font-mono text-xs text-center font-bold">{{ $idx + 1 }}</td>
                                <td class="px-0 md:px-6 py-0 md:py-3 text-slate-800 font-medium">
                                    <div class="flex items-start gap-2">
                                        <span class="md:hidden font-mono text-xs font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded shrink-0">#{{ $idx + 1 }}</span>
                                        <div class="text-xs sm:text-sm leading-relaxed">{{ $item['item'] }}</div>
                                    </div>
                                    <div class="md:hidden mt-1.5 text-xs text-slate-500">
                                        <span class="text-slate-400">Standar:</span>
                                        <span class="bg-slate-100 px-2 py-0.5 rounded border border-slate-200 font-mono font-medium text-slate-700">
                                            {{ $item['standard'] ?? 'Workshop Manual' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="hidden md:table-cell px-6 py-3 text-slate-600 text-xs">
                                    <span class="bg-slate-100 px-2.5 py-1 rounded border border-slate-200 inline-block font-mono">
                                        {{ $item['standard'] ?? 'Workshop Manual' }}
                                    </span>
                                </td>
                                <td class="px-0 md:px-6 py-0 md:py-3">
                                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider md:hidden mb-1">Hasil Pengukuran Asesi:</label>
                                    <input type="text" name="items[{{ $idx }}][measured]" value="{{ $measured }}" :disabled="!isEditing" placeholder="Nilai / kondisi hasil ukur..." class="w-full px-3 py-2 md:py-1 text-xs rounded-lg border border-slate-300 focus:ring-1 focus:ring-brand-500 focus:outline-none bg-white">
                                </td>
                                <td class="px-0 md:px-6 py-0 md:py-3 text-left md:text-center">
                                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider md:hidden mb-1.5">Verifikasi Asesor:</label>
                                    <div class="grid grid-cols-2 gap-2 md:flex md:items-center md:justify-center md:space-x-3">
                                        <label class="flex items-center justify-center p-2 md:p-0 bg-emerald-50 md:bg-transparent rounded-lg md:rounded-none border md:border-0 border-emerald-200 cursor-pointer min-h-[38px] md:min-h-0">
                                            <input type="radio" name="items[{{ $idx }}][result]" value="SESUAI" required :disabled="!isEditing" class="item-radio item-radio-sesuai text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer" {{ $val === 'SESUAI' ? 'checked' : '' }}>
                                            <span class="ml-1.5 md:ml-1 text-xs font-bold text-emerald-800 md:text-emerald-700">Sesuai</span>
                                        </label>
                                        <label class="flex items-center justify-center p-2 md:p-0 bg-rose-50 md:bg-transparent rounded-lg md:rounded-none border md:border-0 border-rose-200 cursor-pointer min-h-[38px] md:min-h-0">
                                            <input type="radio" name="items[{{ $idx }}][result]" value="TIDAK" required :disabled="!isEditing" class="item-radio item-radio-tidak text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer" {{ $val === 'TIDAK' ? 'checked' : '' }}>
                                            <span class="ml-1.5 md:ml-1 text-xs font-bold text-rose-800 md:text-rose-700">Tidak</span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500 text-sm">Tidak ada item lembar ukur khusus untuk skema ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Notes & Assessor Signature --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900">Umpan Balik &amp; Rekomendasi Hasil Kerja Teknis</h2>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Catatan Asesor</label>
                <textarea name="notes" rows="3" :disabled="!isEditing" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Catatan ketepatan toleransi ukur dan kualitas hasil kerja asesi...">{{ $savedNotes ?? old('notes') }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-200">
                <div class="max-w-[340px]">
                    <x-signature-pad name="assessor_signature" :value="$savedSignature ?? null" label="Tanda Tangan Asesor" :required="! $isScheduleClosed" :disabled="$isScheduleClosed" :height="140" />
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia01b']) }}" class="px-5 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">Kembali</a>
            @if($isScheduleClosed)
                <button type="button" disabled class="px-6 py-2.5 text-sm font-bold bg-slate-200 text-slate-500 border border-slate-300 rounded-lg cursor-not-allowed flex items-center gap-2 shadow-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>Jadwal Telah Ditutup (Read-Only)</span>
                </button>
            @else
                @if($isCompleted)
                    <button type="button" x-show="!isEditing" @click="isEditing = true" class="px-6 py-2.5 text-sm font-bold bg-amber-500 hover:bg-amber-600 text-white rounded-lg shadow-sm transition">
                        ✎ Edit Penilaian
                    </button>
                @endif
                <button type="submit" x-show="isEditing" class="px-6 py-2.5 text-sm font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition">
                    {{ $isCompleted ? 'Simpan Perubahan Penilaian' : 'Simpan Penilaian FR.IA.01B' }}
                </button>
            @endif
        </div>
    </form>

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

    {{-- Modal Permintaan Revisi Lembar Report Sheet --}}
    <div x-show="revisionModalOpen" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4"
         @keydown.escape.window="revisionModalOpen = false">
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200"
             @click.away="revisionModalOpen = false">
            <form action="{{ route('asesor.assessment.ia01b.request-revision', $assessment->id) }}" method="POST">
                @csrf
                <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center font-bold text-xs">⚠️</span>
                        <h3 class="text-sm font-bold">Minta Revisi Lembar Report Sheet (FR.IA.01B)</h3>
                    </div>
                    <button type="button" @click="revisionModalOpen = false" class="text-slate-400 hover:text-white transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-900 leading-relaxed">
                        Dengan meminta revisi, asesi <strong>{{ $assessment->asesi->full_name }}</strong> akan diberitahu pada akun portalnya untuk memperbaiki dan mengunggah ulang foto / scan berkas Report Sheet fisik ini.
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                            Catatan / Instruksi Perbaikan untuk Asesi <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="revision_notes" rows="4" required class="w-full text-xs rounded-lg border border-slate-300 p-3 focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Tuliskan alasan mengapa lembar kerja perlu direvisi (misal: Foto buram pada bagian toleransi pengukuran rem, data pengukuran belum lengkap, formulir salah halaman, dsb)...">{{ $revisionNotes ?? '' }}</textarea>
                        <p class="text-[11px] text-slate-500">Catatan ini akan tampil secara jelas di portal asesi saat membuka instrumen FR.IA.01B.</p>
                    </div>
                </div>

                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2.5">
                    <button type="button" @click="revisionModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white rounded-lg shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                        <span>Kirim Permintaan Revisi</span>
                        <span>&rarr;</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
