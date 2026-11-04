@php
    $isCompleted = (bool) $response?->assessor_signed_at;
    $isScheduleClosed = (bool) ($assessment->schedule?->is_closed);
    $asesiSig = $response?->asesi_signature ?? $assessment->asesi_signature ?? $assessment->asesi?->profile?->signature_data;
    $asesiSignedAt = $response?->asesi_signed_at ?? ($asesiSig ? ($assessment->asesi_signed_at ?? $assessment->schedule->date) : null);
    $assessorSig = $response?->assessor_signature ?? auth()->user()->profile?->signature_data;
    $assessorSignedAt = $response?->assessor_signed_at ?? now();
@endphp
@extends('layouts.app', ['title' => 'FR.IA.02 - Tugas Praktik Demonstrasi'])

@section('content')
<div class="space-y-6 max-w-5xl mx-auto pb-16" x-data="{ isEditing: {{ ($isCompleted || $isScheduleClosed) ? 'false' : 'true' }} }">
    @if($isScheduleClosed)
        <div class="p-4 bg-emerald-50 border-2 border-emerald-300 rounded-xl text-emerald-900 flex items-center gap-3 shadow-xs">
            <span class="text-xl">🔒</span>
            <div>
                <div class="font-bold text-sm">Jadwal Asesmen Telah Resmi Ditutup &amp; Dikunci</div>
                <p class="text-xs text-emerald-800 mt-0.5">Seluruh instrumen pada jadwal ini berada dalam mode lihat (read-only). Tanda tangan dan status demonstrasi tidak dapat diubah lagi.</p>
            </div>
        </div>
    @endif

    {{-- Breadcrumb Navigation --}}
    <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500">
        <a href="{{ route('asesor.jadwal.index') }}" class="text-brand-600 hover:text-brand-700 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Jadwal Asesmen</span>
        </a>
        <span class="text-slate-300">/</span>
        <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia02']) }}" class="text-brand-600 hover:text-brand-700">Daftar Asesi IA.02</a>
        <span class="text-slate-300">/</span>
        <span class="text-slate-800 font-bold truncate max-w-xs">{{ $assessment->asesi->full_name }}</span>
    </div>

    {{-- Main Header Card --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                <span class="px-2.5 py-0.5 rounded text-[11px] font-mono font-bold bg-amber-100 text-amber-900 border border-amber-300">
                    FR.IA.02
                </span>
                <span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-brand-50 text-brand-800 border border-brand-200">
                    Tugas Praktik Demonstrasi
                </span>
                @if($isCompleted)
                    <span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1">
                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Telah Ditandatangani</span>
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                        Menunggu Tanda Tangan Asesor
                    </span>
                @endif
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">FR.IA.02. Tugas Praktik Demonstrasi</h1>
            <p class="text-xs text-slate-500 mt-1">
                Asesi: <strong class="text-slate-900">{{ $assessment->asesi->full_name }}</strong>
                @if(!empty($assessment->asesi->profile?->nik))
                    &bull; <span class="font-mono">NIK: {{ $assessment->asesi->profile->nik }}</span>
                @endif
                &bull; Skema: <strong class="text-slate-800">{{ $assessment->schedule->scheme->name }}</strong>
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            @if($isCompleted && ! $isScheduleClosed)
                <button type="button" x-show="!isEditing" @click="isEditing = true" class="px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg shadow-xs transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    <span>Ubah Tanda Tangan</span>
                </button>
                <button type="button" x-show="isEditing" @click="isEditing = false" style="display: none;" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg border border-slate-300 transition">
                    Batal
                </button>
            @endif
            <a href="{{ route('asesi.assessment.instrument.download-template', [$assessment, 'IA02']) }}" target="_blank" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg border border-slate-300 shadow-xs transition inline-flex items-center gap-1.5" title="Unduh Format Soal & Template PDF Resmi BNSP">
                <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Unduh PDF Resmi</span>
            </a>
        </div>
    </div>

    @if($isCompleted)
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shrink-0">✓</span>
                <div>
                    <div class="text-xs font-bold text-emerald-950">Dokumen FR.IA.02 Telah Disetujui &amp; Ditandatangani</div>
                    <p class="text-[11px] text-emerald-800">Tanda tangan asesor dan asesi telah terverifikasi secara sah untuk pelaksanaan tugas unjuk kerja demonstrasi ini.</p>
                </div>
            </div>
            @if($response?->assessor_signed_at)
                <div class="text-right text-[11px] text-emerald-700 font-mono font-semibold shrink-0">
                    Ditandatangani: {{ \Carbon\Carbon::parse($response->assessor_signed_at)->isoFormat('D MMM Y, HH:mm') }} WIB
                </div>
            @endif
        </div>
    @endif

    <form action="{{ route('asesor.ruang-uji.ia02.store', $assessment->id) }}" method="POST" class="space-y-6">
        @csrf

        {{-- Metadata Asesmen Grid --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Informasi Pelaksanaan Uji Demonstrasi</span>
                </span>
                <span class="text-[11px] font-mono text-slate-500">
                    Jadwal: {{ $assessment->schedule->code }}
                </span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-100 text-xs">
                {{-- Col 1: Skema & TUK --}}
                <div class="p-4 space-y-3">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Skema Sertifikasi</span>
                        <div class="font-bold text-slate-900 mt-0.5 leading-snug">{{ $assessment->schedule->scheme->name }}</div>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded font-mono font-bold text-[10px] bg-brand-50 text-brand-700 border border-brand-200">
                            {{ $assessment->schedule->scheme->code }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Tempat Uji Kompetensi (TUK)</span>
                        <div class="font-bold text-slate-800 mt-0.5">{{ $assessment->schedule->tuk_name }}</div>
                        <span class="text-[11px] text-slate-500">(Sewaktu / Tempat Kerja / Mandiri*)</span>
                    </div>
                </div>

                {{-- Col 2: Asesi & Asesor --}}
                <div class="p-4 space-y-3">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Asesi (Peserta Uji)</span>
                        <div class="font-bold text-slate-900 mt-0.5">{{ $assessment->asesi->full_name }}</div>
                        <div class="text-[11px] text-slate-500 font-mono mt-0.5">
                            NIK: {{ $assessment->asesi->profile?->nik ?? '-' }}
                        </div>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Asesor Penguji</span>
                        <div class="font-bold text-slate-900 mt-0.5">{{ $assessment->assessor?->full_name ?? auth()->user()->full_name }}</div>
                        <div class="text-[11px] text-slate-500 font-mono mt-0.5">
                            No. Reg: {{ $assessment->assessor?->profile?->no_reg ?? auth()->user()->profile?->no_reg ?? auth()->user()->assessor_registration_number ?? '-' }}
                        </div>
                    </div>
                </div>

                {{-- Col 3: Waktu & Status --}}
                <div class="p-4 space-y-3">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Tanggal Pelaksanaan</span>
                        <div class="font-bold text-slate-900 mt-0.5">
                            {{ \Carbon\Carbon::parse($assessment->schedule->date)->translatedFormat('l, d F Y') }}
                        </div>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                            {{ $assessment->schedule->start_time ?? '08:00' }} - {{ $assessment->schedule->end_time ?? '17:00' }} WIB
                        </div>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Status Dokumen</span>
                        <div class="mt-1">
                            @if($isCompleted)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Telah Disetujui</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                    <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Menunggu Pengesahan</span>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Document Content Card --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
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

            <div class="p-6 md:p-8 bg-white space-y-6">
                @if(!empty($ia02Html))
                    {!! $ia02Html !!}
                @else
                    <div class="bg-slate-50 rounded-lg border border-slate-200 p-5 font-mono text-xs text-slate-800 leading-relaxed whitespace-pre-wrap">
{{ $ia02Text }}
                    </div>
                @endif
            </div>

            {{-- Signature Section --}}
            <div class="border-t border-slate-200 bg-slate-50/50">
                <div class="px-6 py-3.5 bg-slate-100/80 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-800">Persetujuan &amp; Tanda Tangan Dokumen FR.IA.02</span>
                        <p class="text-[11px] text-slate-500">Persetujuan kedua belah pihak atas pelaksanaan tugas praktik demonstrasi</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200 text-xs">
                    {{-- ASESI --}}
                    <div class="p-6 flex flex-col justify-between space-y-4 bg-white">
                        <div>
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 uppercase tracking-wider">PESERTA UJI (ASESI)</span>
                                <span class="text-slate-500 text-[11px] font-mono">{{ \Carbon\Carbon::parse($assessment->schedule->date)->translatedFormat('d F Y') }}</span>
                            </div>

                            <div class="mt-3">
                                <div class="text-slate-600">Nama Asesi: <strong class="text-slate-900">{{ $assessment->asesi->full_name }}</strong></div>
                            </div>

                            <div class="mt-2.5 p-3 border border-slate-200 rounded-lg bg-slate-50/70 flex flex-col items-center justify-center min-h-[120px]">
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

                        <div class="text-center font-bold text-slate-900 pt-2 border-t border-slate-100">
                            ( {{ $assessment->asesi->full_name }} )
                        </div>
                    </div>

                    {{-- ASESOR --}}
                    <div class="p-6 flex flex-col justify-between space-y-4 bg-white">
                        <div>
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-brand-50 text-brand-700 uppercase tracking-wider">ASESOR KOMPETENSI</span>
                                <span class="text-slate-500 text-[11px] font-mono">{{ \Carbon\Carbon::parse($assessment->schedule->date)->translatedFormat('d F Y') }}</span>
                            </div>

                            <div class="mt-3 space-y-0.5">
                                <div class="text-slate-600">Nama Asesor: <strong class="text-slate-900">{{ $assessment->assessor?->full_name ?? auth()->user()->full_name }}</strong></div>
                                <div class="text-slate-600 text-[11px]">No. Reg: <span class="font-mono text-slate-800">{{ $assessment->assessor?->profile?->no_reg ?? auth()->user()->profile?->no_reg ?? auth()->user()->assessor_registration_number ?? '-' }}</span></div>
                            </div>

                            <div class="mt-2.5">
                                <div x-show="!isEditing && '{{ !empty($assessorSig) }}' === '1'" class="p-3 border border-slate-200 rounded-lg bg-slate-50/70 flex flex-col items-center justify-center min-h-[120px]">
                                    @if($assessorSig)
                                        <img src="{{ $assessorSig }}" alt="TTD Asesor" class="max-h-20 object-contain">
                                        <span class="text-[10px] text-emerald-700 font-semibold mt-1">✓ Tanda Tangan Asesor Tersimpan</span>
                                    @endif
                                </div>

                                <div x-show="isEditing || '{{ empty($assessorSig) }}' === '1'" style="{{ $isCompleted ? 'display: none;' : '' }}">
                                    <x-signature-pad name="assessor_signature" :value="$assessorSig ?? null" label="Tanda Tangan Asesor" :required="! $isScheduleClosed" :disabled="$isScheduleClosed" />
                                </div>
                            </div>
                        </div>

                        <div class="text-center font-bold text-slate-900 pt-2 border-t border-slate-100">
                            ( {{ $assessment->assessor?->full_name ?? auth()->user()->full_name }} )
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Footer Bar --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-4 flex items-center justify-between gap-4">
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia02']) }}" class="px-4 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition inline-flex items-center gap-1.5">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Daftar Asesi</span>
            </a>

            @if($isScheduleClosed)
                <button type="button" disabled class="px-6 py-2.5 text-xs font-bold text-slate-500 bg-slate-200 border border-slate-300 rounded-lg cursor-not-allowed flex items-center justify-center space-x-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>Jadwal Telah Ditutup (Read-Only)</span>
                </button>
            @else
                <button type="submit" class="px-6 py-2.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm transition flex items-center justify-center space-x-2">
                    <span>{{ $isCompleted ? 'Perbarui Tanda Tangan FR.IA.02' : 'Simpan & Tanda Tangani FR.IA.02' }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
            @endif
        </div>
    </form>
</div>
@endsection
