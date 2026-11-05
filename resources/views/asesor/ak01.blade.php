@extends('layouts.app', ['title' => 'FR.AK.01 - Persetujuan Asesmen dan Kerahasiaan'])

@section('content')
@php
    $jadwal = $schedule;
    $assessments = $assessments ?? $jadwal->assessments ?? collect();
    $selectedAssessmentId = $selectedAssessmentId ?? request('assessment');
    $savedEvidence = $savedEvidence ?? ['Hasil Observasi Langsung', 'Hasil Pertanyaan Tertulis'];
    $savedOtherEvidence = $savedOtherEvidence ?? '';
    $savedTukType = $savedTukType ?? 'Sewaktu';

    $instrumentCatalog = [
        'IA01' => ['code' => 'FR.IA.01', 'title' => 'Ceklis Observasi Aktivitas Praktik', 'desc' => 'Observasi langsung demonstrasi keterampilan kerja asesi.'],
        'IA01B' => ['code' => 'FR.IA.01.B', 'title' => 'Ceklis Persiapan Tempat Uji', 'desc' => 'Verifikasi kelaikan peralatan, bahan, dan K3 pada TUK.'],
        'IA02' => ['code' => 'FR.IA.02', 'title' => 'Tugas Praktik Demonstrasi', 'desc' => 'Instruksi kerja terstruktur dan unjuk kerja praktik.'],
        'IA03' => ['code' => 'FR.IA.03', 'title' => 'Pertanyaan Lisan Pendukung', 'desc' => 'Daftar pertanyaan lisan klarifikasi kompetensi di tempat uji.'],
        'IA05' => ['code' => 'FR.IA.05', 'title' => 'Pertanyaan Tertulis Pilihan Ganda (CBT)', 'desc' => 'Ujian teori objektif CBT 20 butir acak per skema jadwal.'],
        'IA06' => ['code' => 'FR.IA.06', 'title' => 'Pertanyaan Tertulis Esai', 'desc' => 'Daftar pertanyaan tertulis uraian/studi kasus.'],
        'IA07' => ['code' => 'FR.IA.07', 'title' => 'Pertanyaan Lisan Wawancara', 'desc' => 'Wawancara pendukung pembuktian portofolio.'],
        'IA11' => ['code' => 'FR.IA.11', 'title' => 'Ceklis Meninjau Instrumen', 'desc' => 'Verifikasi dan peninjauan mutu perangkat asesmen.'],
    ];

    $scheduleEffectiveInstruments = array_values(array_filter(
        $jadwal->getEffectiveInstruments(),
        fn ($c) => str_starts_with(strtoupper($c), 'IA')
    ));
    $instrumentsList = [];
    foreach ($scheduleEffectiveInstruments as $code) {
        if (isset($instrumentCatalog[$code])) {
            $instrumentsList[] = [
                'code' => $instrumentCatalog[$code]['code'] ?? "FR.{$code}",
                'title' => $instrumentCatalog[$code]['title'] ?? "Instrumen FR.{$code}",
                'desc' => $instrumentCatalog[$code]['desc'] ?? 'Instrumen asesmen kompetensi yang digunakan pada jadwal ini.',
            ];
        }
    }
@endphp

<div class="space-y-6 max-w-5xl mx-auto pb-12" 
     x-data="{
        modalOpen: false,
        selectedAsesi: null,
        openModal(data) {
            this.selectedAsesi = data;
            this.modalOpen = true;
        },
        closeModal() {
            this.modalOpen = false;
            this.selectedAsesi = null;
        }
     }">
    @php
        $isClosedForMe = $jadwal->isClosedForAssessor();
    @endphp

    {{-- Unified Top Lifecycle Navigation --}}
    @include('asesor.partials.schedule-nav', ['schedule' => $jadwal, 'active' => 'ak01'])

    @if($isClosedForMe)
        <div class="p-3.5 bg-emerald-50 border border-emerald-300 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-emerald-900">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span><strong>{{ $jadwal->is_closed ? 'Jadwal Telah Resmi Ditutup & Dikunci:' : 'Asesmen Binaan Anda Telah Ditutup & Selesai:' }}</strong> Dokumen persetujuan asesmen FR.AK.01 dalam mode lihat (read-only).</span>
            </div>
            <span class="font-bold font-mono text-[11px] bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded border border-emerald-300 shrink-0">STATUS: {{ $jadwal->is_closed ? 'FINISHED' : 'BINAAN SELESAI' }}</span>
        </div>
    @endif

    {{-- Header & Top Action Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">FR.AK.01. PERSETUJUAN ASESMEN DAN KERAHASIAAN</h1>
            <p class="text-xs text-slate-500 font-medium">Lembaga Sertifikasi Profesi Praktisi Otomotif (LSP PrO Indo)</p>
        </div>
    </div>

    {{-- Info Notice Callout --}}
    <div class="rounded-xl bg-blue-50/80 border border-blue-200 p-4 text-xs text-blue-900 shadow-sm flex items-start gap-3">
        <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 mt-0.5 font-bold">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="leading-relaxed">
            <span class="font-bold text-blue-950">Formulir Persetujuan Kolektif:</span> Nilai default untuk bukti uji yang dikumpulkan adalah <strong>"Hasil Observasi Langsung"</strong> dan <strong>"Hasil Pertanyaan Tertulis"</strong>. Anda dapat menyesuaikan metode jika diperlukan, memilih daftar asesi yang disetujui, lalu menyimpan untuk melanjutkan alur asesmen.
        </div>
    </div>

    <form action="{{ route('asesor.schedule.ak01.store', $jadwal->id) }}" method="POST" class="space-y-6">
        @csrf

        {{-- Main Document Card --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden text-slate-900 font-sans">
            {{-- Document Card Header Bar --}}
            <div class="px-6 py-4 bg-slate-900 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800">
                <div class="flex items-center space-x-2.5">
                    <span class="px-2.5 py-0.5 rounded text-[11px] font-mono font-bold bg-amber-400 text-slate-950">
                        FR.AK.01
                    </span>
                    <h2 class="text-sm font-bold tracking-wide uppercase text-white">
                        Formulir Persetujuan Asesmen dan Kerahasiaan
                    </h2>
                </div>
                <div class="text-xs text-slate-400 font-mono">
                    {{ $jadwal->code }}
                </div>
            </div>

            {{-- Explanatory Preamble --}}
            <div class="px-6 py-3.5 bg-slate-50/70 border-b border-slate-200 text-xs text-slate-600 leading-relaxed">
                Persetujuan Asesmen ini untuk menjamin bahwa Asesi telah diberi arahan secara rinci tentang perencanaan dan proses asesmen sesuai skema sertifikasi yang diujikan.
            </div>

            <div class="divide-y divide-slate-200 text-xs">
                {{-- Row 1: Skema Sertifikasi --}}
                <div class="grid grid-cols-1 md:grid-cols-12 bg-white">
                    <div class="md:col-span-4 p-4 md:p-5 font-semibold text-slate-700 bg-slate-50/50 md:border-r border-slate-200 flex items-center">
                        Skema Sertifikasi (KKNI/Okupasi/Klaster)
                    </div>
                    <div class="md:col-span-8 p-4 md:p-5 space-y-2 bg-white">
                        <div class="flex items-start">
                            <span class="w-20 font-medium text-slate-500 shrink-0">Judul</span>
                            <span class="mr-2 text-slate-400">:</span>
                            <span class="font-bold text-slate-900">{{ $jadwal->scheme->name ?? '-' }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-20 font-medium text-slate-500 shrink-0">Nomor</span>
                            <span class="mr-2 text-slate-400">:</span>
                            <span class="font-mono font-bold text-brand-700 bg-brand-50 px-2 py-0.5 rounded border border-brand-200">
                                {{ $jadwal->scheme->code ?? '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Row 2: TUK & Tipe TUK --}}
                <div class="grid grid-cols-1 md:grid-cols-12 bg-white">
                    <div class="md:col-span-4 p-4 md:p-5 font-semibold text-slate-700 bg-slate-50/50 md:border-r border-slate-200 flex items-center">
                        Tempat Uji Kompetensi (TUK)
                    </div>
                    <div class="md:col-span-8 p-4 md:p-5 bg-white space-y-3">
                        <div class="flex items-center">
                            <span class="w-20 font-medium text-slate-500 shrink-0">Nama TUK</span>
                            <span class="mr-2 text-slate-400">:</span>
                            <span class="font-bold text-slate-900">{{ $jadwal->tuk_name }}</span>
                        </div>
                        <div class="flex items-center flex-wrap gap-2 pt-1">
                            <span class="w-20 font-medium text-slate-500 shrink-0">Jenis TUK</span>
                            <span class="mr-2 text-slate-400">:</span>
                            <div class="inline-flex items-center bg-slate-100 p-1 rounded-lg border border-slate-200 gap-1">
                                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md {{ $isClosedForMe ? 'cursor-default' : 'cursor-pointer' }} transition {{ $savedTukType === 'Sewaktu' ? 'bg-white shadow-xs font-bold text-brand-700' : 'text-slate-600 hover:text-slate-900' }}">
                                    <input type="radio" name="tuk_type" value="Sewaktu" {{ $savedTukType === 'Sewaktu' ? 'checked' : '' }} @disabled($isClosedForMe) class="text-brand-600 focus:ring-brand-500">
                                    <span>Sewaktu</span>
                                </label>
                                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md {{ $isClosedForMe ? 'cursor-default' : 'cursor-pointer' }} transition {{ $savedTukType === 'Tempat Kerja' ? 'bg-white shadow-xs font-bold text-brand-700' : 'text-slate-600 hover:text-slate-900' }}">
                                    <input type="radio" name="tuk_type" value="Tempat Kerja" {{ $savedTukType === 'Tempat Kerja' ? 'checked' : '' }} @disabled($isClosedForMe) class="text-brand-600 focus:ring-brand-500">
                                    <span>Tempat Kerja</span>
                                </label>
                                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md {{ $isClosedForMe ? 'cursor-default' : 'cursor-pointer' }} transition {{ $savedTukType === 'Mandiri' ? 'bg-white shadow-xs font-bold text-brand-700' : 'text-slate-600 hover:text-slate-900' }}">
                                    <input type="radio" name="tuk_type" value="Mandiri" {{ $savedTukType === 'Mandiri' ? 'checked' : '' }} @disabled($isClosedForMe) class="text-brand-600 focus:ring-brand-500">
                                    <span>Mandiri*</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 3: Nama Asesor --}}
                <div class="grid grid-cols-1 md:grid-cols-12 bg-white">
                    <div class="md:col-span-4 p-4 md:p-5 font-semibold text-slate-700 bg-slate-50/50 md:border-r border-slate-200 flex items-center">
                        Nama Asesor Kompetensi
                    </div>
                    <div class="md:col-span-8 p-4 md:p-5 flex items-center bg-white">
                        <div class="flex items-center space-x-2">
                            <span class="w-8 h-8 rounded-full bg-brand-50 text-brand-700 border border-brand-200 flex items-center justify-center font-bold text-xs shrink-0">
                                {{ strtoupper(substr(auth()->user()?->full_name ?? 'AS', 0, 2)) }}
                            </span>
                            <div>
                                <span class="font-bold text-slate-900 text-sm block">{{ auth()->user()?->full_name ?? 'Asesor LSP PrO Indo' }}</span>
                                <span class="text-slate-500 font-mono text-[11px]">
                                    No. Registrasi: {{ auth()->user()?->assessor_registration_number ?? auth()->user()?->profile?->no_reg ?? 'MET.000.001234 2022' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 4: Nama Asesi (Batch Selection) --}}
                <div class="grid grid-cols-1 md:grid-cols-12 bg-white">
                    <div class="md:col-span-4 p-4 md:p-5 text-slate-700 bg-slate-50/50 md:border-r border-slate-200">
                        <span class="font-bold text-slate-900 block text-sm">Nama Asesi</span>
                        <span class="sr-only">Asesi yang Diberikan Penyesuaian (Pilih Asesi):</span>
                        <p class="text-[11px] text-slate-500 font-normal mt-1 leading-relaxed">
                            Pilih asesi yang akan diberikan persetujuan asesmen. Asesi terpilih akan terdaftar resmi pada dokumen persetujuan ini.
                        </p>
                    </div>
                    <div class="md:col-span-8 p-4 md:p-5 bg-white space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                            <label class="inline-flex items-center gap-2 {{ $isClosedForMe ? 'cursor-default' : 'cursor-pointer' }} text-xs font-bold text-slate-800">
                                <input type="checkbox" id="check-all-asesi" checked @disabled($isClosedForMe) class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4">
                                <span>Pilih Semua Asesi Binaan ({{ $assessments->count() }} Orang)</span>
                            </label>
                            <span class="text-[11px] text-slate-400 italic">Centang asesi yang disetujui</span>
                        </div>

                        @if($assessments->isEmpty())
                            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-xs">
                                Belum ada asesi yang ditugaskan kepada Anda pada jadwal ini.
                            </div>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-80 overflow-y-auto pr-1">
                                @foreach($assessments as $assessment)
                                    @php
                                        $isAk01Approved = ! empty($assessment->ak01_data) || ! empty($assessment->ak01_agreed_at);
                                        $candidateEvidence = $assessment->ak01_data['evidence'] ?? $jadwal->ak01_data['evidence'] ?? $savedEvidence;
                                        $candidateOtherEvidence = $assessment->ak01_data['other_evidence'] ?? $jadwal->ak01_data['other_evidence'] ?? $savedOtherEvidence;
                                        $candidateTukType = $assessment->ak01_data['tuk_type'] ?? $jadwal->ak01_data['tuk_type'] ?? $savedTukType;
                                    @endphp
                                    <div class="p-3 rounded-xl border-2 transition shadow-2xs flex flex-col justify-between {{ $isAk01Approved ? 'bg-emerald-50/80 border-emerald-300 hover:border-emerald-400' : 'bg-amber-50/90 border-amber-300 hover:border-amber-400' }}">
                                        <div class="flex items-start justify-between gap-2">
                                             <label class="flex items-start space-x-2.5 {{ $isClosedForMe ? 'cursor-default' : 'cursor-pointer' }} flex-1 min-w-0">
                                                <input type="checkbox" name="assessment_ids[]" value="{{ $assessment->id }}"
                                                    class="asesi-item-checkbox rounded border-slate-400 text-brand-600 focus:ring-brand-500 w-4 h-4 mt-0.5 shrink-0"
                                                    @disabled($isClosedForMe)
                                                    {{ (empty(old('assessment_ids')) || in_array($assessment->id, old('assessment_ids', [])) || (string)$selectedAssessmentId === (string)$assessment->id) ? 'checked' : '' }}>
                                                <div class="truncate text-xs">
                                                    <div class="font-bold text-slate-900 truncate flex items-center gap-1.5">
                                                        <span>{{ $assessment->asesi->full_name }}</span>
                                                    </div>
                                                    <div class="text-[11px] text-slate-500 truncate mt-0.5">
                                                        {{ $assessment->asesi->email }}
                                                        @if(!empty($assessment->asesi->profile?->nik))
                                                            &bull; <span class="font-mono">NIK: {{ $assessment->asesi->profile->nik }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </label>
                                            @if($isAk01Approved)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 shrink-0">
                                                    ✓ Disetujui
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300 shrink-0">
                                                    Belum Disetujui
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Trigger Modal Button --}}
                                        <div class="mt-2.5 pt-2 border-t {{ $isAk01Approved ? 'border-emerald-200/80' : 'border-amber-200/80' }} flex items-center justify-between">
                                            <span class="text-[10px] font-medium {{ $isAk01Approved ? 'text-emerald-700' : 'text-amber-800' }}">
                                                {{ $isAk01Approved ? 'Data FR.AK.01 tersimpan' : 'Data masih kosong' }}
                                            </span>
                                            @php
                                                $candAssignedCodes = $assessment->getAssignedInstruments();
                                                $candInstList = [];
                                                foreach ($candAssignedCodes as $code) {
                                                    if (isset($instrumentCatalog[$code])) {
                                                        $candInstList[] = [
                                                            'code' => $instrumentCatalog[$code]['code'],
                                                            'title' => $instrumentCatalog[$code]['title'],
                                                            'desc' => $instrumentCatalog[$code]['desc'],
                                                        ];
                                                    }
                                                }
                                            @endphp
                                            <button type="button" 
                                                    @click="openModal({
                                                        name: '{{ addslashes($assessment->asesi->full_name) }}',
                                                        email: '{{ addslashes($assessment->asesi->email) }}',
                                                        nik: '{{ addslashes($assessment->asesi->profile?->nik ?? '-') }}',
                                                        isApproved: {{ $isAk01Approved ? 'true' : 'false' }},
                                                        tukName: '{{ addslashes($jadwal->tuk_name) }}',
                                                        tukType: '{{ addslashes($candidateTukType) }}',
                                                        scheduleDate: '{{ \Carbon\Carbon::parse($jadwal->date)->isoFormat('D MMMM Y') }} ({{ substr($jadwal->start_time, 0, 5) }} - {{ substr($jadwal->end_time, 0, 5) }} WIB)',
                                                        evidence: {{ json_encode($candidateEvidence) }},
                                                        otherEvidence: '{{ addslashes($candidateOtherEvidence) }}',
                                                        instruments: {{ json_encode($candInstList) }}
                                                    })"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold rounded-lg transition shadow-2xs {{ $isAk01Approved ? 'text-emerald-900 bg-white hover:bg-emerald-100 border border-emerald-300' : 'text-amber-900 bg-white hover:bg-amber-100 border border-amber-300' }}"
                                                    title="Buka rincian TUK, bukti yang dikumpulkan, dan instrumen asesi ini">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <span>Rincian Asesi</span>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Row 5: Bukti yang akan dikumpulkan --}}
                <div class="grid grid-cols-1 md:grid-cols-12 bg-white">
                    <div class="md:col-span-4 p-4 md:p-5 text-slate-700 bg-slate-50/50 md:border-r border-slate-200 flex items-start">
                        <div>
                            <span class="font-bold block text-slate-900 text-sm">Bukti yang akan dikumpulkan :</span>
                            <p class="text-[11px] text-slate-500 font-normal mt-1 leading-relaxed">
                                Pilihlah instrumen pengumpulan bukti uji kompetensi yang akan digunakan selama proses asesmen berlangsung.
                            </p>
                        </div>
                    </div>
                    <div class="md:col-span-8 p-4 md:p-5 bg-white">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Column 1 --}}
                            <div class="space-y-2">
                                <label class="flex items-center space-x-2.5 p-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 {{ $isClosedForMe ? 'cursor-default' : 'cursor-pointer' }} transition">
                                    <input type="checkbox" name="evidence[]" value="Hasil Verifikasi Portofolio"
                                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                        @disabled($isClosedForMe)
                                        {{ in_array('Hasil Verifikasi Portofolio', $savedEvidence) ? 'checked' : '' }}>
                                    <span class="font-medium text-slate-800">Hasil Verifikasi Portofolio</span>
                                </label>

                                <label class="flex items-center justify-between p-2.5 rounded-lg border border-brand-200 bg-brand-50/30 hover:bg-brand-50/60 {{ $isClosedForMe ? 'cursor-default' : 'cursor-pointer' }} transition">
                                    <div class="flex items-center space-x-2.5">
                                        <input type="checkbox" name="evidence[]" value="Hasil Observasi Langsung"
                                            class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                            @disabled($isClosedForMe)
                                            {{ in_array('Hasil Observasi Langsung', $savedEvidence) ? 'checked' : '' }}>
                                        <span class="font-bold text-slate-900">Hasil Observasi Langsung</span>
                                    </div>
                                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">Default</span>
                                </label>

                                <label class="flex items-center space-x-2.5 p-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 {{ $isClosedForMe ? 'cursor-default' : 'cursor-pointer' }} transition">
                                    <input type="checkbox" name="evidence[]" value="Hasil Pertanyaan Lisan"
                                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                        @disabled($isClosedForMe)
                                        {{ in_array('Hasil Pertanyaan Lisan', $savedEvidence) ? 'checked' : '' }}>
                                    <span class="font-medium text-slate-800">Hasil Pertanyaan Lisan</span>
                                </label>

                                <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50/40 space-y-2">
                                    <label class="flex items-center space-x-2.5 {{ $isClosedForMe ? 'cursor-default' : 'cursor-pointer' }}">
                                        <input type="checkbox" name="evidence[]" value="Lainnya"
                                            id="checkbox-lainnya"
                                            class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                            @disabled($isClosedForMe)
                                            {{ (in_array('Lainnya', $savedEvidence) || !empty($savedOtherEvidence)) ? 'checked' : '' }}>
                                        <span class="font-medium text-slate-800">Lainnya :</span>
                                    </label>
                                    <input type="text" name="other_evidence" value="{{ old('other_evidence', $savedOtherEvidence) }}"
                                        placeholder="Tuliskan bukti lainnya bila ada..."
                                        @disabled($isClosedForMe)
                                        class="w-full text-xs rounded-xl border border-slate-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none px-3.5 py-2 shadow-2xs bg-white text-slate-900 transition">
                                </div>
                            </div>

                            {{-- Column 2 --}}
                            <div class="space-y-2">
                                <label class="flex items-center justify-between p-2.5 rounded-lg border border-brand-200 bg-brand-50/30 hover:bg-brand-50/60 {{ $isClosedForMe ? 'cursor-default' : 'cursor-pointer' }} transition">
                                    <div class="flex items-center space-x-2.5">
                                        <input type="checkbox" name="evidence[]" value="Hasil Pertanyaan Tertulis"
                                            class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                            @disabled($isClosedForMe)
                                            {{ in_array('Hasil Pertanyaan Tertulis', $savedEvidence) ? 'checked' : '' }}>
                                        <span class="font-bold text-slate-900">Hasil Pertanyaan Tertulis (DPT)</span>
                                    </div>
                                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">Default</span>
                                </label>

                                <div class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 bg-slate-50 opacity-60 cursor-not-allowed" title="Opsi dikunci sesuai standar operasional MUK LSP PrO Indo">
                                    <div class="flex items-center space-x-2.5">
                                        <input type="checkbox" disabled class="rounded border-slate-300 text-slate-400 w-4 h-4 cursor-not-allowed">
                                        <span class="font-medium text-slate-500">Hasil Reviu Produk</span>
                                    </div>
                                    <span class="text-[10px] font-semibold text-slate-500 bg-slate-200/80 px-2 py-0.5 rounded">Terkunci (LSP)</span>
                                </div>

                                <div class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 bg-slate-50 opacity-60 cursor-not-allowed" title="Opsi dikunci sesuai standar operasional MUK LSP PrO Indo">
                                    <div class="flex items-center space-x-2.5">
                                        <input type="checkbox" disabled class="rounded border-slate-300 text-slate-400 w-4 h-4 cursor-not-allowed">
                                        <span class="font-medium text-slate-500">Hasil Kegiatan Terstruktur</span>
                                    </div>
                                    <span class="text-[10px] font-semibold text-slate-500 bg-slate-200/80 px-2 py-0.5 rounded">Terkunci (LSP)</span>
                                </div>

                                <div class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 bg-slate-50 opacity-60 cursor-not-allowed" title="Opsi dikunci sesuai standar operasional MUK LSP PrO Indo">
                                    <div class="flex items-center space-x-2.5">
                                        <input type="checkbox" disabled class="rounded border-slate-300 text-slate-400 w-4 h-4 cursor-not-allowed">
                                        <span class="font-medium text-slate-500">Hasil Pertanyaan Wawancara</span>
                                    </div>
                                    <span class="text-[10px] font-semibold text-slate-500 bg-slate-200/80 px-2 py-0.5 rounded">Terkunci (LSP)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 5b: Pemilihan Instrumen FR.IA yang Digunakan --}}
                <div class="grid grid-cols-1 md:grid-cols-12 bg-white">
                    <div class="md:col-span-4 p-4 md:p-5 text-slate-700 bg-slate-50/50 md:border-r border-slate-200 flex items-start">
                        <div>
                            <span class="font-bold block text-slate-900 text-sm">Instrumen Asesmen (FR.IA) :</span>
                            <p class="text-[11px] text-slate-500 font-normal mt-1 leading-relaxed">
                                Tentukan materi uji dan formulir instrumen FR.IA yang akan diaktifkan di Ruang Uji untuk jadwal ini. Bebas disesuaikan kebutuhan asesmen Anda.
                            </p>
                        </div>
                    </div>
                    <div class="md:col-span-8 p-4 md:p-5 bg-white">
                        @php
                            $availableIa = [
                                'IA01' => ['title' => 'FR.IA.01 - Ceklis Observasi Praktik', 'desc' => 'Observasi aktivitas demonstrasi kerja asesi'],
                                'IA01B' => ['title' => 'FR.IA.01.B - Lembar Kerja / Report Sheet', 'desc' => 'Pengukuran engine, baterai, & kelistrikan EFI'],
                                'IA02' => ['title' => 'FR.IA.02 - Tugas Praktik Demonstrasi', 'desc' => 'Lembar tugas fisik dan verifikasi lembar kerja'],
                                'IA03' => ['title' => 'FR.IA.03 - Pertanyaan Pendukung Observasi (PMO)', 'desc' => 'Daftar pertanyaan lisan konfirmasi praktik'],
                                'IA05' => ['title' => 'FR.IA.05 - Pertanyaan Tertulis (CBT 20 Soal)', 'desc' => 'Ujian teori pilihan ganda terstandar'],
                            ];
                            $currentSelected = $selectedInstruments ?? ['AK01', 'IA01', 'IA01B', 'IA02', 'IA03', 'IA05'];
                        @endphp
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($availableIa as $iaCode => $iaMeta)
                                <label class="flex items-start space-x-3 p-3 rounded-lg border border-slate-200 hover:border-brand-300 hover:bg-slate-50/70 {{ $isClosedForMe ? 'cursor-default' : 'cursor-pointer' }} transition bg-white shadow-xs">
                                    <input type="checkbox" name="selected_instruments[]" value="{{ $iaCode }}"
                                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4 mt-0.5 shrink-0"
                                        @disabled($isClosedForMe)
                                        {{ in_array($iaCode, $currentSelected) ? 'checked' : '' }}>
                                    <div>
                                        <div class="font-bold text-slate-900 text-xs">{{ $iaMeta['title'] }}</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-snug">{{ $iaMeta['desc'] }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Row 6: Jadwal Pelaksanaan Disepakati Pada --}}
                <div class="grid grid-cols-1 md:grid-cols-12 bg-white">
                    <div class="md:col-span-4 p-4 md:p-5 font-semibold text-slate-700 bg-slate-50/50 md:border-r border-slate-200 flex items-center">
                        Pelaksanaan asesmen disepakati pada:
                    </div>
                    <div class="md:col-span-8 p-4 md:p-5 space-y-2 bg-white">
                        <div class="flex items-center">
                            <span class="w-28 font-medium text-slate-500 shrink-0">Hari/ Tanggal</span>
                            <span class="mr-2 text-slate-400">:</span>
                            <span class="font-bold text-slate-900">{{ \Carbon\Carbon::parse($jadwal->date)->isoFormat('dddd, D MMMM Y') }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-28 font-medium text-slate-500 shrink-0">Waktu</span>
                            <span class="mr-2 text-slate-400">:</span>
                            <span class="font-bold text-slate-900">{{ substr($jadwal->start_time, 0, 5) }} - {{ substr($jadwal->end_time, 0, 5) }} WIB</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-28 font-medium text-slate-500 shrink-0">TUK</span>
                            <span class="mr-2 text-slate-400">:</span>
                            <span class="font-bold text-slate-900">{{ $jadwal->tuk_name }}</span>
                        </div>
                    </div>
                </div>

                {{-- Statements Section --}}
                <div class="p-5 bg-slate-50/40 space-y-3.5">
                    {{-- Pernyataan Asesi (Hak & Prosedur Banding) --}}
                    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
                        <span class="text-xs font-bold text-brand-700 uppercase tracking-wider block mb-1">Pernyataan Asesi:</span>
                        <p class="leading-relaxed text-slate-700 text-xs">
                            Bahwa saya telah mendapatkan penjelasan terkait hak dan prosedur banding asesmen dari asesor.
                        </p>
                    </div>

                    {{-- Pernyataan Asesor (Kerahasiaan) --}}
                    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
                        <span class="text-xs font-bold text-brand-700 uppercase tracking-wider block mb-1">Pernyataan Asesor:</span>
                        <p class="leading-relaxed text-slate-700 text-xs">
                            Menyatakan tidak akan membuka hasil pekerjaan yang saya peroleh karena penugasan saya sebagai Asesor dalam pekerjaan Asesmen kepada siapapun atau organisasi apapun selain kepada pihak yang berwenang sehubungan dengan kewajiban saya sebagai Asesor yang ditugaskan oleh LSP.
                        </p>
                    </div>

                    {{-- Persetujuan Asesi (Pengembangan Profesi) --}}
                    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-xs">
                        <span class="text-xs font-bold text-brand-700 uppercase tracking-wider block mb-1">Persetujuan Asesi:</span>
                        <p class="leading-relaxed text-slate-700 text-xs">
                            Saya setuju mengikuti asesmen dengan pemahaman bahwa informasi yang dikumpulkan hanya digunakan untuk pengembangan profesional dan hanya dapat diakses oleh orang tertentu saja.
                        </p>
                    </div>
                </div>

                {{-- Signatures Section --}}
                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 bg-white">
                    {{-- Left: Asesor Signature --}}
                    <div class="p-5 flex flex-col justify-between space-y-3">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 text-xs">Tanda Tangan Asesor</span>
                                <span class="text-slate-500 text-[11px]">Tanggal: <strong>{{ \Carbon\Carbon::parse($jadwal->date)->isoFormat('D MMMM Y') }}</strong></span>
                            </div>

                            <div class="mt-2.5 w-[340px] max-w-full h-[140px] p-3 border border-slate-200 rounded-xl bg-slate-50/70 flex items-center justify-center mx-auto">
                                @if(auth()->user()?->profile?->signature_data)
                                    <img src="{{ auth()->user()->profile->signature_data }}" alt="TTD Asesor" class="w-full h-full object-contain">
                                @else
                                    <div class="text-center py-2 text-amber-700">
                                        <p class="text-[11px] font-semibold">Tanda tangan profil belum tersimpan.</p>
                                        <p class="text-[10px] text-slate-500">Master TTD dapat diatur pada menu profil asesor.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="text-center font-bold text-slate-900 pt-1 text-xs">
                            ( {{ auth()->user()?->full_name ?? 'Asesor' }} )
                        </div>
                    </div>

                    {{-- Right: Asesi Signature --}}
                    <div class="p-5 flex flex-col justify-between space-y-3">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 text-xs">Tanda Tangan Asesi</span>
                                <span class="text-slate-500 text-[11px]">Tanggal: <strong>{{ \Carbon\Carbon::parse($jadwal->date)->isoFormat('D MMMM Y') }}</strong></span>
                            </div>

                            <div class="mt-2.5 w-[340px] max-w-full h-[140px] p-3 border border-slate-200 rounded-xl bg-slate-50/70 flex flex-col items-center justify-center mx-auto text-center text-slate-500">
                                <div class="w-8 h-8 rounded-full bg-slate-200/80 text-slate-600 flex items-center justify-center mb-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </div>
                                <span class="text-[11px] font-bold text-slate-700">Ditandatangani Mandiri oleh Asesi</span>
                                <span class="text-[10px] text-slate-500 mt-0.5">Tervalidasi melalui akun asesi sebelum memulai materi uji</span>
                            </div>
                        </div>

                        <div class="text-center font-bold text-slate-900 pt-1 text-xs">
                            ( Seluruh Asesi Terpilih )
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer Note --}}
            <div class="px-6 py-2.5 bg-slate-50 border-t border-slate-200 text-[11px] text-slate-500 italic">
                * Coret yang tidak perlu
            </div>
        </div>

        {{-- Bottom Action Buttons --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $jadwal->id, 'code' => 'apl']) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 inline-flex items-center gap-1">
                <span>&larr;</span> Kembali ke Verifikasi APL
            </a>

            <div class="flex items-center space-x-3 w-full sm:w-auto">
                @if($isClosedForMe)
                    <button type="button" disabled class="w-full sm:w-auto px-6 py-2.5 text-xs font-bold text-slate-500 bg-slate-200 rounded-lg cursor-not-allowed">
                        {{ $jadwal->is_closed ? 'Jadwal Telah Ditutup (Read-Only)' : 'Asesmen Binaan Telah Selesai (Read-Only)' }}
                    </button>
                @else
                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm transition flex items-center justify-center space-x-2">
                        <span>Simpan Persetujuan FR.AK.01</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                @endif
            </div>
        </div>
    </form>

    {{-- Modal Rincian Persetujuan FR.AK.01 Asesi --}}
    <div x-show="modalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4"
         @keydown.escape.window="closeModal()">
        <div @click.away="closeModal()" 
             class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden border border-slate-200 flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-150">
            {{-- Header Modal --}}
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center space-x-2.5">
                    <span class="px-2.5 py-0.5 rounded text-[11px] font-mono font-bold bg-amber-400 text-slate-950">
                        FR.AK.01
                    </span>
                    <h3 class="text-sm font-bold tracking-wide uppercase text-white">
                        Rincian Persetujuan Asesmen Asesi
                    </h3>
                </div>
                <button type="button" @click="closeModal()" class="text-slate-400 hover:text-white p-1 rounded-lg transition" title="Tutup Modal">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body Modal --}}
            <div class="p-6 overflow-y-auto space-y-4 text-xs text-slate-700 divide-y divide-slate-100">
                {{-- 1. Identitas & Status --}}
                <div class="pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h4 class="text-base font-black text-slate-900" x-text="selectedAsesi?.name"></h4>
                        <p class="text-xs text-slate-500 font-mono mt-0.5" x-text="selectedAsesi?.email"></p>
                        <template x-if="selectedAsesi?.nik && selectedAsesi?.nik !== '-'">
                            <span class="text-[11px] text-slate-600 font-mono block mt-0.5" x-text="'NIK: ' + selectedAsesi?.nik"></span>
                        </template>
                    </div>
                    <div>
                        <template x-if="selectedAsesi?.isApproved">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-2xs">
                                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                ✓ Sudah Disetujui
                            </span>
                        </template>
                        <template x-if="!selectedAsesi?.isApproved">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 shadow-2xs">
                                <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                                Belum Disetujui (Draf)
                            </span>
                        </template>
                    </div>
                </div>

                {{-- 2. Tempat Uji Kompetensi (TUK) --}}
                <div class="pt-3 space-y-2">
                    <div class="font-bold text-slate-900 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-brand-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Tempat Uji Kompetensi (TUK)
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <span class="text-slate-500 font-medium block text-[11px]">Nama TUK:</span>
                            <span class="font-bold text-slate-900 text-xs" x-text="selectedAsesi?.tukName"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 font-medium block text-[11px]">Jenis TUK:</span>
                            <span class="font-bold text-brand-700 text-xs px-2 py-0.5 bg-brand-50 border border-brand-200 rounded-md inline-block mt-0.5" x-text="selectedAsesi?.tukType"></span>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="text-slate-500 font-medium block text-[11px]">Jadwal Pelaksanaan:</span>
                            <span class="font-medium text-slate-800 text-xs" x-text="selectedAsesi?.scheduleDate"></span>
                        </div>
                    </div>
                </div>

                {{-- 3. Bukti yang Dikumpulkan --}}
                <div class="pt-3 space-y-2">
                    <div class="font-bold text-slate-900 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-brand-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Bukti yang Disepakati / Dikumpulkan
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-2">
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="(ev, idx) in selectedAsesi?.evidence" :key="idx">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-800 shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-600"></span>
                                    <span x-text="ev"></span>
                                </span>
                            </template>
                        </div>
                        <template x-if="selectedAsesi?.otherEvidence">
                            <div class="text-[11px] text-slate-600 border-t border-slate-200 pt-1.5">
                                <span class="font-bold">Bukti Tambahan:</span> <span x-text="selectedAsesi?.otherEvidence"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- 4. Instrumen Asesmen (MUK) --}}
                <div class="pt-3 space-y-2">
                    <div class="font-bold text-slate-900 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-brand-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        Instrumen Asesmen yang Digunakan
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <template x-for="(inst, idx) in selectedAsesi?.instruments" :key="idx">
                            <div class="p-2.5 rounded-lg border border-slate-200 bg-white flex items-start gap-2 shadow-2xs">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-brand-50 text-brand-700 border border-brand-200 shrink-0" x-text="inst.code"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-slate-900 text-xs" x-text="inst.title"></div>
                                    <div class="text-[10px] text-slate-500" x-text="inst.desc"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- 5. Catatan Pembaruan --}}
                <div class="pt-3">
                    <div class="bg-blue-50/80 border border-blue-200 rounded-xl p-3 text-[11px] text-blue-900 leading-relaxed flex items-start gap-2">
                        <svg class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <span class="font-bold">Panduan Pembaruan:</span> Jika Anda ingin mengubah bukti yang dikumpulkan, instrumen, atau jenis TUK untuk asesi ini, Anda dapat menyesuaikan opsi pada formulir di halaman ini lalu klik tombol <strong>"Simpan Persetujuan FR.AK.01"</strong>.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer Modal --}}
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end">
                <button type="button" @click="closeModal()" class="px-4 py-2 text-xs font-bold text-slate-700 bg-white hover:bg-slate-100 border border-slate-300 rounded-lg shadow-2xs transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkAll = document.getElementById('check-all-asesi');
        const checkboxes = document.querySelectorAll('.asesi-item-checkbox');

        if (checkAll) {
            checkAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = checkAll.checked);
            });

            checkboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    const allChecked = Array.from(checkboxes).every(c => c.checked);
                    checkAll.checked = allChecked;
                });
            });
        }
    });
</script>
@endsection
