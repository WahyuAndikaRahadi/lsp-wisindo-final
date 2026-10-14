@extends('layouts.app', ['title' => 'Dashboard Asesi'])

@section('content')
<div class="space-y-6">
    {{-- Header Banner --}}
    <div class="bg-gradient-to-r from-lsp-navy to-lsp-navy-dark rounded-xl p-5 sm:p-6 md:p-8 text-white shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4 border-t-4 border-lsp-red">
        <div>
            <span class="inline-block px-2.5 py-0.5 rounded-full bg-white/10 text-lsp-red border border-lsp-red/30 text-[10px] font-extrabold tracking-wider uppercase mb-2">
                Peserta Asesmen Kompetensi
            </span>
            <h1 class="font-sans text-xl sm:text-2xl md:text-3xl font-extrabold tracking-tight">
                Selamat Datang, {{ auth()->user()->full_name }}!
            </h1>
            <p class="text-blue-100/80 text-xs mt-1 max-w-xl">
                LSP PrO Indo mendigitalisasi proses sertifikasi kompetensi profesi teknisi otomotif Indonesia berstandar BNSP.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:space-x-3 shrink-0">
            <a href="{{ route('asesi.jadwal.index') }}" class="px-4 py-2.5 bg-lsp-red hover:bg-lsp-red-dark text-white rounded-lg text-xs font-bold shadow-sm transition flex items-center justify-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Gabung Jadwal Baru</span>
            </a>
            <a href="{{ route('asesi.profile.edit') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 border border-white/20 text-white rounded-lg text-xs font-semibold transition text-center justify-center">
                Profil Saya
            </a>
        </div>
    </div>

    {{-- Alert: Incomplete Profile / Signature --}}
    @php
        $profile = auth()->user()->profile;
        $hasCompleteProfile = $profile && $profile->nik && $profile->signature_data;
    @endphp
    @if(!$hasCompleteProfile)
        <div class="rounded-xl bg-amber-50 border border-amber-200 p-4.5 text-amber-900 flex items-start space-x-3 shadow-sm">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="flex-1 text-sm">
                <h4 class="font-bold">Biodata atau Tanda Tangan Belum Lengkap!</h4>
                <p class="mt-0.5 text-amber-800 text-xs">
                    Sebelum mengikuti proses asesmen dan mengisi formulir FR.APL.01/02, Anda wajib melengkapi data diri serta tanda tangan digital pada profil akun Anda.
                </p>
                <div class="mt-2.5">
                    <a href="{{ route('asesi.profile.edit') }}" class="inline-flex items-center text-xs font-bold text-amber-950 underline hover:text-black">
                        Lengkapi Data Sekarang &rarr;
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- Stats Cards --}}
    @php
        $assessments = $assessments ?? auth()->user()->scheduleAssessments()->with(['schedule.scheme', 'assessor'])->latest()->get();
        $totalAssessments = $assessments->count();
        $approvedCount = $assessments->where('apl_status', 'APPROVED')->count();
        $completedCount = $assessments->where('status', 'COMPLETED')->count();
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 rounded-lg bg-blue-50 text-brand-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Jadwal Terdaftar</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5">{{ $totalAssessments }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">APL Disetujui</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5">{{ $approvedCount }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Asesmen Selesai</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5">{{ $completedCount }}</h3>
            </div>
        </div>
    </div>

    {{-- Enrolled Schedules Section --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Jadwal Asesmen Terkini</h2>
                <p class="text-xs text-slate-500">Daftar sertifikasi kompetensi yang sedang atau telah Anda ikuti</p>
            </div>
            <a href="{{ route('asesi.jadwal.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">
                Lihat Semua Jadwal &rarr;
            </a>
        </div>

        @if($assessments->isEmpty())
            <div class="p-8 text-center">
                <div class="w-12 h-12 mx-auto mb-3 text-slate-300">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-700">Belum Ada Jadwal Uji</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Masukkan kode jadwal dari sekolah atau penyelenggara untuk mulai mendaftar dan mengikuti asesmen.
                </p>
                <div class="mt-4">
                    <a href="{{ route('asesi.jadwal.index') }}" class="inline-flex items-center px-4 py-2 text-xs font-bold rounded-lg bg-brand-600 text-white hover:bg-brand-700 shadow-sm transition">
                        Gabung Jadwal Sekarang
                    </a>
                </div>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($assessments as $item)
                    @php
                        $sch = $item->schedule;
                        $aplStatusBadge = match($item->apl_status) {
                            'APPROVED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'SUBMITTED' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'REVISION_REQUESTED' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'REJECTED' => 'bg-rose-50 text-rose-700 border-rose-200',
                            default => 'bg-slate-50 text-slate-600 border-slate-200',
                        };

                        // Determine target action url based on progress
                        $isResume = ($item->apl_status === 'APPROVED');
                        $targetUrl = route('asesi.assessment.apl', $item->id);

                        if ($item->apl_status === 'APPROVED') {
                            $targetUrl = match($item->last_active_step) {
                                'ak01' => route('asesi.assessment.ak01', $item->id),
                                'ruang-uji' => route('asesi.assessment.ruang-uji', $item->id),
                                'ia05' => route('asesi.assessment.instrument.ia05', $item->id),
                                default => route('asesi.assessment.ruang-uji', $item->id),
                            };
                        }
                    @endphp
                    <div class="p-4 sm:p-6 hover:bg-slate-50/60 transition flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1.5 flex-1 min-w-0">
                            <div class="flex items-center space-x-2.5 flex-wrap gap-y-1">
                                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                                    {{ $sch->code }}
                                </span>
                                <span class="px-2 py-0.5 rounded text-xs font-semibold border {{ $aplStatusBadge }}">
                                    APL: {{ str_replace('_', ' ', $item->apl_status) }}
                                </span>
                                @if($item->status === 'COMPLETED')
                                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                        SELESAI
                                    </span>
                                @endif
                            </div>

                            <h3 class="text-base font-bold text-slate-900 leading-snug">
                                {{ $sch->name ?? $sch->scheme->name }}
                            </h3>

                            <div class="flex items-center text-xs text-slate-500 space-x-4 flex-wrap gap-y-1">
                                <span class="flex items-center">
                                    <svg class="w-3.5 h-3.5 mr-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    TUK: <strong class="ml-1 text-slate-700">{{ $sch->tuk_name }}</strong>
                                </span>
                                <span class="flex items-center">
                                    <svg class="w-3.5 h-3.5 mr-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    {{ \Carbon\Carbon::parse($sch->date)->isoFormat('D MMMM Y') }} ({{ substr($sch->start_time, 0, 5) }} - {{ substr($sch->end_time, 0, 5) }})
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3 shrink-0 w-full sm:w-auto">
                            @if($item->apl_status === 'REVISION_REQUESTED')
                                <a href="{{ route('asesi.assessment.apl', $item->id) }}" class="w-full sm:w-auto text-center justify-center px-4 py-2 text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white rounded-lg shadow-sm transition">
                                    Perbaiki APL &rarr;
                                </a>
                            @elseif($item->apl_status === 'APPROVED')
                                <a href="{{ $targetUrl }}" class="w-full sm:w-auto text-center justify-center px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow-sm transition">
                                    Lanjutkan Asesmen &rarr;
                                </a>
                            @else
                                <a href="{{ route('asesi.assessment.apl', $item->id) }}" class="w-full sm:w-auto text-center justify-center px-4 py-2 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition">
                                    Masuk Jadwal Uji &rarr;
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
