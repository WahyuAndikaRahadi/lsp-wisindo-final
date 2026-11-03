@php
    $isCompleted = (bool) $response?->assessor_signed_at;
    $asesiSig = $response?->asesi_signature ?? $assessment->asesi_signature ?? $assessment->asesi?->profile?->signature_data;
    $asesiSignedAt = $response?->asesi_signed_at ?? ($asesiSig ? ($assessment->asesi_signed_at ?? $assessment->schedule->date) : null);
    $assessorSig = $response?->assessor_signature ?? auth()->user()->profile?->signature_data;
    $assessorSignedAt = $response?->assessor_signed_at ?? now();
@endphp
@extends('layouts.app', ['title' => 'FR.IA.02 - Tugas Praktik Demonstrasi'])

@section('content')
<div class="space-y-6 max-w-5xl mx-auto pb-12" x-data="{ isEditing: {{ $isCompleted ? 'false' : 'true' }} }">
    {{-- Top Navigation & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold mb-1">
                <a href="{{ route('asesor.jadwal.index') }}" class="text-brand-600 hover:text-brand-700">&larr; Jadwal</a>
                <span class="text-slate-300">/</span>
                <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia02']) }}" class="text-brand-600 hover:text-brand-700">Daftar Asesi IA.02</a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-800">{{ $assessment->asesi->full_name }}</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">FR.IA.02. Tugas Praktik Demonstrasi</h1>
            <p class="text-xs text-slate-500 font-medium">
                Asesi: <strong class="text-slate-900">{{ $assessment->asesi->full_name }}</strong>
                @if(!empty($assessment->asesi->profile?->nik))
                    &bull; <span class="font-mono">NIK: {{ $assessment->asesi->profile->nik }}</span>
                @endif
                &bull; Skema: <strong class="text-slate-800">{{ $assessment->schedule->scheme->name }}</strong>
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if($isCompleted)
                <button type="button" x-show="!isEditing" @click="isEditing = true" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg shadow-sm transition flex items-center gap-1.5">
                    <span>✎ Ubah Tanda Tangan</span>
                </button>
                <button type="button" x-show="isEditing" @click="isEditing = false" style="display: none;" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold rounded-lg transition">
                    Batal
                </button>
            @endif
            <a href="{{ route('asesi.assessment.instrument.download-template', [$assessment, 'IA02']) }}" target="_blank" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg border border-slate-300 shadow-sm transition inline-flex items-center gap-1.5" title="Unduh Lembar Soal & Template PDF Resmi BNSP">
                <span>📥 Unduh PDF Resmi</span>
            </a>
        </div>
    </div>

    @if($isCompleted)
        <div class="rounded-xl bg-emerald-50 border border-emerald-300 p-4 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shrink-0">✓</span>
                <div>
                    <div class="text-xs font-bold text-emerald-950">Dokumen FR.IA.02 Telah Disetujui &amp; Ditandatangani</div>
                    <p class="text-[11px] text-emerald-800">Tanda tangan asesor dan asesi telah tervalidasi pada instrumen tugas praktik demonstrasi ini.</p>
                </div>
            </div>
            @if($response?->assessor_signed_at)
                <div class="text-right text-[11px] text-emerald-700 font-mono">
                    Ditandatangani: {{ \Carbon\Carbon::parse($response->assessor_signed_at)->isoFormat('D MMM Y, HH:mm') }} WIB
                </div>
            @endif
        </div>
    @endif

    <form action="{{ route('asesor.ruang-uji.ia02.store', $assessment->id) }}" method="POST" class="space-y-6">
        @csrf

        {{-- Main Document Container --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden text-slate-900 font-sans">
            {{-- Document Title Header --}}
            <div class="px-6 py-4 bg-slate-900 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800">
                <div class="flex items-center space-x-2.5">
                    <span class="px-2.5 py-0.5 rounded text-[11px] font-mono font-bold bg-amber-400 text-slate-950">
                        FR.IA.02
                    </span>
                    <h2 class="text-sm font-bold tracking-wide uppercase text-white">
                        Tugas Praktik Demonstrasi (TPD)
                    </h2>
                </div>
                <div class="text-xs text-slate-400 font-mono">
                    {{ $assessment->schedule->scheme->code }} &bull; {{ $assessment->schedule->code }}
                </div>
            </div>

            {{-- Metadata Table Header (As in Master PDF) --}}
            <div class="divide-y divide-slate-200 text-xs border-b border-slate-200">
                <div class="grid grid-cols-1 md:grid-cols-12 bg-white">
                    <div class="md:col-span-4 p-3.5 md:p-4 font-semibold text-slate-700 bg-slate-50/60 md:border-r border-slate-200 flex items-center">
                        Skema Sertifikasi (KKNI/Okupasi/Klaster)
                    </div>
                    <div class="md:col-span-8 p-3.5 md:p-4 space-y-1.5 bg-white">
                        <div class="flex items-start">
                            <span class="w-20 font-medium text-slate-500 shrink-0">Judul</span>
                            <span class="mr-2 text-slate-400">:</span>
                            <span class="font-bold text-slate-900">{{ $assessment->schedule->scheme->name }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-20 font-medium text-slate-500 shrink-0">Nomor</span>
                            <span class="mr-2 text-slate-400">:</span>
                            <span class="font-mono font-bold text-brand-700 bg-brand-50 px-2 py-0.5 rounded border border-brand-200">
                                {{ $assessment->schedule->scheme->code }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 bg-white">
                    <div class="md:col-span-4 p-3.5 md:p-4 font-semibold text-slate-700 bg-slate-50/60 md:border-r border-slate-200 flex items-center">
                        TUK
                    </div>
                    <div class="md:col-span-8 p-3.5 md:p-4 bg-white flex items-center">
                        <span class="w-20 font-medium text-slate-500 shrink-0">Nama TUK</span>
                        <span class="mr-2 text-slate-400">:</span>
                        <span class="font-bold text-slate-900">{{ $assessment->schedule->tuk_name }}</span>
                        <span class="ml-2 text-slate-500 text-[11px]">(Sewaktu / Tempat Kerja / Mandiri*)</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 bg-white">
                    <div class="md:col-span-4 p-3.5 md:p-4 font-semibold text-slate-700 bg-slate-50/60 md:border-r border-slate-200 flex items-center">
                        Nama Asesor
                    </div>
                    <div class="md:col-span-8 p-3.5 md:p-4 bg-white flex items-center">
                        <span class="w-20 font-medium text-slate-500 shrink-0">Asesor</span>
                        <span class="mr-2 text-slate-400">:</span>
                        <span class="font-bold text-slate-900">{{ $assessment->assessor?->full_name ?? auth()->user()->full_name }}</span>
                        <span class="ml-2 font-mono text-slate-500 text-[11px]">
                            ({{ $assessment->assessor?->profile?->no_reg ?? auth()->user()->profile?->no_reg ?? auth()->user()->assessor_registration_number ?? 'MET.000.001234 2022' }})
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 bg-white">
                    <div class="md:col-span-4 p-3.5 md:p-4 font-semibold text-slate-700 bg-slate-50/60 md:border-r border-slate-200 flex items-center">
                        Nama Asesi
                    </div>
                    <div class="md:col-span-8 p-3.5 md:p-4 bg-white flex items-center">
                        <span class="w-20 font-medium text-slate-500 shrink-0">Asesi</span>
                        <span class="mr-2 text-slate-400">:</span>
                        <span class="font-bold text-slate-900">{{ $assessment->asesi->full_name }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 bg-white">
                    <div class="md:col-span-4 p-3.5 md:p-4 font-semibold text-slate-700 bg-slate-50/60 md:border-r border-slate-200 flex items-center">
                        Tanggal Pelaksanaan
                    </div>
                    <div class="md:col-span-8 p-3.5 md:p-4 bg-white flex items-center">
                        <span class="w-20 font-medium text-slate-500 shrink-0">Tanggal</span>
                        <span class="mr-2 text-slate-400">:</span>
                        <span class="font-bold text-slate-900">{{ \Carbon\Carbon::parse($assessment->schedule->date)->translatedFormat('d F Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- Formatted Document Content --}}
            <div class="p-6 md:p-8 bg-white border-b border-slate-200">
                @if(!empty($ia02Html))
                    {!! $ia02Html !!}
                @else
                    <div class="bg-slate-50 rounded-lg border border-slate-200 p-5 font-mono text-xs text-slate-800 leading-relaxed whitespace-pre-wrap">
{{ $ia02Text }}
                    </div>
                @endif
            </div>

            {{-- Signature Section (Requires Only Signatures of Assessor and Assessee) --}}
            <div class="border-t border-slate-200 bg-white">
                <div class="px-6 py-3 bg-slate-50 border-b border-slate-200">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Persetujuan &amp; Tanda Tangan Dokumen FR.IA.02</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 text-xs">
                    {{-- Left Column: ASESI --}}
                    <div class="p-6 flex flex-col justify-between space-y-4">
                        <div>
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                <span class="font-bold text-slate-900 uppercase tracking-wide">ASESI :</span>
                                <span class="text-slate-500 text-[11px]">Tanggal: <strong>{{ \Carbon\Carbon::parse($assessment->schedule->date)->translatedFormat('d F Y') }}</strong></span>
                            </div>

                            <div class="mt-3">
                                <div class="text-slate-600 mb-1">Nama Asesi: <strong class="text-slate-900">{{ $assessment->asesi->full_name }}</strong></div>
                            </div>

                            {{-- Asesi Signature Preview --}}
                            <div class="mt-2.5 p-3 border border-slate-200 rounded-lg bg-slate-50/70 flex flex-col items-center justify-center min-h-[110px]">
                                @if($asesiSig)
                                    <img src="{{ $asesiSig }}" alt="TTD Asesi" class="max-h-20 object-contain">
                                    <span class="text-[10px] text-emerald-700 font-semibold mt-1">✓ Tanda Tangan Asesi Terverifikasi</span>
                                @else
                                    <div class="text-center py-2 text-slate-500">
                                        <svg class="w-6 h-6 text-slate-400 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        <p class="text-[11px] font-semibold text-slate-700">Ditandatangani secara mandiri oleh asesi</p>
                                        <p class="text-[10px] text-slate-400">Melalui akun asesi saat asesmen di TUK</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="text-center font-bold text-slate-900 pt-1">
                            ( {{ $assessment->asesi->full_name }} )
                        </div>
                    </div>

                    {{-- Right Column: ASESOR --}}
                    <div class="p-6 flex flex-col justify-between space-y-4">
                        <div>
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                <span class="font-bold text-slate-900 uppercase tracking-wide">ASESOR :</span>
                                <span class="text-slate-500 text-[11px]">Tanggal: <strong>{{ \Carbon\Carbon::parse($assessment->schedule->date)->translatedFormat('d F Y') }}</strong></span>
                            </div>

                            <div class="mt-3 space-y-0.5">
                                <div class="text-slate-600">Nama Asesor: <strong class="text-slate-900">{{ $assessment->assessor?->full_name ?? auth()->user()->full_name }}</strong></div>
                                <div class="text-slate-600 text-[11px]">No. Reg: <span class="font-mono text-slate-800">{{ $assessment->assessor?->profile?->no_reg ?? auth()->user()->profile?->no_reg ?? auth()->user()->assessor_registration_number ?? '-' }}</span></div>
                            </div>

                            {{-- Assessor Signature Pad / Preview --}}
                            <div class="mt-2.5">
                                <div x-show="!isEditing && '{{ !empty($assessorSig) }}' === '1'" class="p-3 border border-slate-200 rounded-lg bg-slate-50/70 flex flex-col items-center justify-center min-h-[110px]">
                                    @if($assessorSig)
                                        <img src="{{ $assessorSig }}" alt="TTD Asesor" class="max-h-20 object-contain">
                                        <span class="text-[10px] text-emerald-700 font-semibold mt-1">✓ Tanda Tangan Asesor Tersimpan</span>
                                    @endif
                                </div>

                                <div x-show="isEditing || '{{ empty($assessorSig) }}' === '1'" style="{{ $isCompleted ? 'display: none;' : '' }}">
                                    <x-signature-pad name="assessor_signature" :value="$assessorSig ?? null" label="Tanda Tangan Asesor" :required="true" />
                                </div>
                            </div>
                        </div>

                        <div class="text-center font-bold text-slate-900 pt-1">
                            ( {{ $assessment->assessor?->full_name ?? auth()->user()->full_name }} )
                        </div>
                    </div>
                </div>
            </div>

            {{-- Optional Notes Box --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan Asesor (Opsional):</label>
                <textarea name="notes" rows="2" :disabled="!isEditing" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none bg-white" placeholder="Catatan opsional terkait pelaksanaan demonstrasi...">{{ $savedNotes ?? old('notes') }}</textarea>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-between gap-4 pt-2">
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia02']) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 inline-flex items-center gap-1">
                <span>&larr;</span> Kembali ke Daftar Asesi
            </a>

            <div class="flex items-center space-x-3">
                <button type="submit" class="px-6 py-2.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm transition flex items-center justify-center space-x-2">
                    <span>{{ $isCompleted ? 'Perbarui Tanda Tangan FR.IA.02' : 'Simpan & Tanda Tangani FR.IA.02' }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
