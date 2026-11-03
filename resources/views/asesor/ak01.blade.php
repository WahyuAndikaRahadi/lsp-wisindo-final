@extends('layouts.app', ['title' => 'FR.AK.01 - Persetujuan Asesmen dan Kerahasiaan'])

@section('content')
@php
    $jadwal = $schedule;
    $assessments = $assessments ?? $jadwal->assessments ?? collect();
    $selectedAssessmentId = $selectedAssessmentId ?? request('assessment');
    $savedEvidence = $savedEvidence ?? ['Hasil Observasi Langsung', 'Hasil Pertanyaan Tertulis'];
    $savedOtherEvidence = $savedOtherEvidence ?? '';
    $savedTukType = $savedTukType ?? 'Sewaktu';
@endphp

<div class="space-y-6 max-w-5xl mx-auto pb-12">
    {{-- Header & Top Action Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold mb-1">
                <a href="{{ route('asesor.jadwal.index') }}" class="text-brand-600 hover:text-brand-700">&larr; Jadwal</a>
                <span class="text-slate-300">/</span>
                <a href="{{ route('asesor.schedule.instrument', ['schedule' => $jadwal->id, 'code' => 'apl']) }}" class="text-brand-600 hover:text-brand-700">Verifikasi APL</a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-800">FR.AK.01</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">FR.AK.01. Persetujuan Asesmen dan Kerahasiaan</h1>
            <p class="text-xs text-slate-500 font-medium">Lembaga Sertifikasi Profesi Praktisi Otomotif (LSP PrO Indo)</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('asesor.schedule.ak07', $jadwal->id) }}" class="inline-flex items-center px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 transition">
                <span>Lewati ke FR.AK.07</span>
                <svg class="w-3.5 h-3.5 ml-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
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
                                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md cursor-pointer transition {{ $savedTukType === 'Sewaktu' ? 'bg-white shadow-xs font-bold text-brand-700' : 'text-slate-600 hover:text-slate-900' }}">
                                    <input type="radio" name="tuk_type" value="Sewaktu" {{ $savedTukType === 'Sewaktu' ? 'checked' : '' }} class="text-brand-600 focus:ring-brand-500">
                                    <span>Sewaktu</span>
                                </label>
                                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md cursor-pointer transition {{ $savedTukType === 'Tempat Kerja' ? 'bg-white shadow-xs font-bold text-brand-700' : 'text-slate-600 hover:text-slate-900' }}">
                                    <input type="radio" name="tuk_type" value="Tempat Kerja" {{ $savedTukType === 'Tempat Kerja' ? 'checked' : '' }} class="text-brand-600 focus:ring-brand-500">
                                    <span>Tempat Kerja</span>
                                </label>
                                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md cursor-pointer transition {{ $savedTukType === 'Mandiri' ? 'bg-white shadow-xs font-bold text-brand-700' : 'text-slate-600 hover:text-slate-900' }}">
                                    <input type="radio" name="tuk_type" value="Mandiri" {{ $savedTukType === 'Mandiri' ? 'checked' : '' }} class="text-brand-600 focus:ring-brand-500">
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
                        <p class="text-[11px] text-slate-500 font-normal mt-1 leading-relaxed">
                            Pilih asesi yang akan diberikan persetujuan asesmen. Asesi terpilih akan terdaftar resmi pada dokumen persetujuan ini.
                        </p>
                    </div>
                    <div class="md:col-span-8 p-4 md:p-5 bg-white space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-800">
                                <input type="checkbox" id="check-all-asesi" checked class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4">
                                <span>Pilih Semua Asesi Binaan ({{ $assessments->count() }} Orang)</span>
                            </label>
                            <span class="text-[11px] text-slate-400 italic">Centang asesi yang disetujui</span>
                        </div>

                        @if($assessments->isEmpty())
                            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-xs">
                                Belum ada asesi yang ditugaskan kepada Anda pada jadwal ini.
                            </div>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-64 overflow-y-auto pr-1">
                                @foreach($assessments as $assessment)
                                    <label class="flex items-start space-x-2.5 p-2.5 rounded-lg border border-slate-200 hover:border-brand-300 hover:bg-slate-50/80 cursor-pointer transition bg-white shadow-xs">
                                        <input type="checkbox" name="assessment_ids[]" value="{{ $assessment->id }}"
                                            class="asesi-item-checkbox rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4 mt-0.5 shrink-0"
                                            {{ (empty(old('assessment_ids')) || in_array($assessment->id, old('assessment_ids', [])) || (string)$selectedAssessmentId === (string)$assessment->id) ? 'checked' : '' }}>
                                        <div class="truncate text-xs">
                                            <div class="font-bold text-slate-900 truncate">{{ $assessment->asesi->full_name }}</div>
                                            <div class="text-[11px] text-slate-500 truncate mt-0.5">
                                                {{ $assessment->asesi->email }}
                                                @if(!empty($assessment->asesi->profile?->nik))
                                                    &bull; <span class="font-mono">NIK: {{ $assessment->asesi->profile->nik }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </label>
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
                                <label class="flex items-center space-x-2.5 p-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                                    <input type="checkbox" name="evidence[]" value="Hasil Verifikasi Portofolio"
                                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                        {{ in_array('Hasil Verifikasi Portofolio', $savedEvidence) ? 'checked' : '' }}>
                                    <span class="font-medium text-slate-800">Hasil Verifikasi Portofolio</span>
                                </label>

                                <label class="flex items-center justify-between p-2.5 rounded-lg border border-brand-200 bg-brand-50/30 hover:bg-brand-50/60 cursor-pointer transition">
                                    <div class="flex items-center space-x-2.5">
                                        <input type="checkbox" name="evidence[]" value="Hasil Observasi Langsung"
                                            class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                            {{ in_array('Hasil Observasi Langsung', $savedEvidence) ? 'checked' : '' }}>
                                        <span class="font-bold text-slate-900">Hasil Observasi Langsung</span>
                                    </div>
                                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">Default</span>
                                </label>

                                <label class="flex items-center space-x-2.5 p-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                                    <input type="checkbox" name="evidence[]" value="Hasil Pertanyaan Lisan"
                                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                        {{ in_array('Hasil Pertanyaan Lisan', $savedEvidence) ? 'checked' : '' }}>
                                    <span class="font-medium text-slate-800">Hasil Pertanyaan Lisan</span>
                                </label>

                                <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50/40 space-y-2">
                                    <label class="flex items-center space-x-2.5 cursor-pointer">
                                        <input type="checkbox" name="evidence[]" value="Lainnya"
                                            id="checkbox-lainnya"
                                            class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                            {{ (in_array('Lainnya', $savedEvidence) || !empty($savedOtherEvidence)) ? 'checked' : '' }}>
                                        <span class="font-medium text-slate-800">Lainnya :</span>
                                    </label>
                                    <input type="text" name="other_evidence" value="{{ old('other_evidence', $savedOtherEvidence) }}"
                                        placeholder="Tuliskan bukti lainnya bila ada..."
                                        class="w-full text-xs rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500 px-3 py-1.5 shadow-xs bg-white">
                                </div>
                            </div>

                            {{-- Column 2 --}}
                            <div class="space-y-2">
                                <label class="flex items-center space-x-2.5 p-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                                    <input type="checkbox" name="evidence[]" value="Hasil Reviu Produk"
                                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                        {{ in_array('Hasil Reviu Produk', $savedEvidence) ? 'checked' : '' }}>
                                    <span class="font-medium text-slate-800">Hasil Reviu Produk</span>
                                </label>

                                <label class="flex items-center space-x-2.5 p-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                                    <input type="checkbox" name="evidence[]" value="Hasil Kegiatan Terstruktur"
                                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                        {{ in_array('Hasil Kegiatan Terstruktur', $savedEvidence) ? 'checked' : '' }}>
                                    <span class="font-medium text-slate-800">Hasil Kegiatan Terstruktur</span>
                                </label>

                                <label class="flex items-center justify-between p-2.5 rounded-lg border border-brand-200 bg-brand-50/30 hover:bg-brand-50/60 cursor-pointer transition">
                                    <div class="flex items-center space-x-2.5">
                                        <input type="checkbox" name="evidence[]" value="Hasil Pertanyaan Tertulis"
                                            class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                            {{ in_array('Hasil Pertanyaan Tertulis', $savedEvidence) ? 'checked' : '' }}>
                                        <span class="font-bold text-slate-900">Hasil Pertanyaan Tertulis</span>
                                    </div>
                                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">Default</span>
                                </label>

                                <label class="flex items-center space-x-2.5 p-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                                    <input type="checkbox" name="evidence[]" value="Hasil Pertanyaan Wawancara"
                                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                        {{ in_array('Hasil Pertanyaan Wawancara', $savedEvidence) ? 'checked' : '' }}>
                                    <span class="font-medium text-slate-800">Hasil Pertanyaan Wawancara</span>
                                </label>
                            </div>
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

                            <div class="mt-2.5 p-3 border border-slate-200 rounded-lg bg-slate-50/70 flex items-center justify-center min-h-[100px]">
                                @if(auth()->user()?->profile?->signature_data)
                                    <img src="{{ auth()->user()->profile->signature_data }}" alt="TTD Asesor" class="max-h-20 object-contain">
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

                            <div class="mt-2.5 p-3 border border-slate-200 rounded-lg bg-slate-50/70 flex flex-col items-center justify-center min-h-[100px] text-center text-slate-500">
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
                <a href="{{ route('asesor.schedule.ak07', $jadwal->id) }}" class="w-full sm:w-auto text-center px-5 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-sm">
                    Lewati & Langsung ke FR.AK.07 &rarr;
                </a>

                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm transition flex items-center justify-center space-x-2">
                    <span>Simpan FR.AK.01 & Lanjut ke FR.AK.07</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </div>
        </div>
    </form>
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
