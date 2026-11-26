@extends('layouts.app', ['title' => 'Dashboard TUK - LSP Wisindo'])

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6">
    {{-- Header Banner --}}
    <div class="bg-gradient-to-r from-lsp-navy to-lsp-navy-dark rounded-2xl p-5 sm:p-6 md:p-8 text-white shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4 border-t-4 border-emerald-500">
        <div>
            <span class="inline-block px-2.5 py-0.5 rounded-full bg-white/10 text-emerald-300 border border-emerald-400/30 text-[10px] font-extrabold tracking-wider uppercase mb-2">
                Tempat Uji Kompetensi (TUK)
            </span>
            <h1 class="font-sans text-xl sm:text-2xl md:text-3xl font-extrabold tracking-tight">
                {{ $tuk ? $tuk->name : 'Portal Admin TUK' }}
            </h1>
            <p class="text-blue-100/80 text-xs mt-1 max-w-xl">
                Panel pengelolaan Tempat Uji Kompetensi dan pelaksanaan Asesmen Jarak Jauh (AJJ) LSP P3 Wisata Indonesia.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:space-x-3 shrink-0">
            <a href="{{ route('tuk.data.edit') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 border border-white/20 text-white rounded-lg text-xs font-semibold transition text-center flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span>Kelola Data &amp; Berkas TUK</span>
            </a>
            @if($stats['is_verified'])
                <a href="{{ route('tuk.jadwal.index') }}" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-sm transition flex items-center justify-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>Lihat Jadwal TUK</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Verification Status Callouts --}}
    @if($stats['status'] === 'REVISION')
        <div class="rounded-2xl bg-rose-50 border-2 border-rose-300 p-5 text-rose-950 shadow-sm space-y-2">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-full bg-rose-600 text-white flex items-center justify-center font-bold text-sm shrink-0">⚠️</span>
                <div>
                    <h3 class="text-sm sm:text-base font-black text-rose-900">Perhatian: Berkas Verifikasi TUK Memerlukan Revisi</h3>
                    <p class="text-xs text-rose-800 mt-0.5">Admin LSP telah meninjau pengajuan TUK Anda dan meminta perbaikan berikut:</p>
                </div>
            </div>
            @if(!empty($stats['notes']))
                <div class="sm:ml-10.5 bg-white p-3.5 rounded-xl border border-rose-200 text-xs text-slate-800 leading-relaxed font-mono">
                    {{ $stats['notes'] }}
                </div>
            @endif
            <div class="sm:ml-10.5 pt-1">
                <a href="{{ route('tuk.data.edit') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                    <span>Perbaiki Berkas Sekarang</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>
    @elseif($stats['status'] === 'PENDING_VERIFICATION')
        <div class="rounded-2xl bg-amber-50 border border-amber-200 p-5 text-amber-950 shadow-sm flex items-start gap-3">
            <span class="w-8 h-8 rounded-full bg-amber-600 text-white flex items-center justify-center font-bold text-sm shrink-0">⏳</span>
            <div class="flex-1 text-xs sm:text-sm">
                <h4 class="font-bold text-amber-900">Berkas Verifikasi AJJ Sedang Ditinjau Admin LSP</h4>
                <p class="mt-1 text-amber-800 text-xs leading-relaxed">
                    Data TUK, foto ruang, paket data, speedtest koneksi, foto alat, dan video ruang TUK Anda telah terkirim. Mohon menunggu proses verifikasi dari pihak LSP Wisindo. Menu jadwal akan aktif setelah verifikasi disetujui.
                </p>
            </div>
        </div>
    @elseif($stats['status'] === 'UNSUBMITTED')
        <div class="rounded-2xl bg-blue-50 border border-blue-200 p-5 text-blue-950 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start gap-3">
                <span class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm shrink-0">📋</span>
                <div class="text-xs sm:text-sm">
                    <h4 class="font-bold text-blue-900">Data &amp; Berkas Verifikasi TUK Belum Lengkap</h4>
                    <p class="mt-0.5 text-blue-800 text-xs">
                        Silakan lengkapi profil TUK, pilih skema yang diajukan, dan unggah bukti verifikasi AJJ (foto &amp; video) sesuai SOP.
                    </p>
                </div>
            </div>
            <a href="{{ route('tuk.data.edit') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm shrink-0 transition">
                <span>Lengkapi Sekarang</span>
                <span>&rarr;</span>
            </a>
        </div>
    @else
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 sm:p-5 text-emerald-950 shadow-sm flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shrink-0">✓</span>
                <div>
                    <h4 class="font-bold text-emerald-900 text-xs sm:text-sm">TUK Terverifikasi Resmi untuk Asesmen Jarak Jauh (AJJ)</h4>
                    <p class="text-emerald-800 text-xs mt-0.5">TUK Anda siap menyelenggarakan uji kompetensi virtual dengan tautan meeting terintegrasi.</p>
                </div>
            </div>
            <span class="px-3 py-1 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-full text-xs font-extrabold shrink-0">
                STATUS AKTIF
            </span>
        </div>
    @endif

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm space-y-1">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Status TUK</span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $stats['status_badge'] }}">
                {{ $stats['status_label'] }}
            </span>
        </div>
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm space-y-1">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Skema Terakreditasi</span>
            <div class="text-xl sm:text-2xl font-black text-slate-900">{{ $stats['verified_schemes_count'] }} <span class="text-xs text-slate-400 font-normal">Skema</span></div>
        </div>
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm space-y-1">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Jadwal Asesmen</span>
            <div class="text-xl sm:text-2xl font-black text-slate-900">{{ $stats['total_schedules'] }} <span class="text-xs text-slate-400 font-normal">Sesi</span></div>
        </div>
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm space-y-1">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Sesi Aktif</span>
            <div class="text-xl sm:text-2xl font-black text-emerald-600">{{ $stats['active_schedules'] }} <span class="text-xs text-slate-400 font-normal">Berjalan</span></div>
        </div>
    </div>

    {{-- Verified Schemes Badges --}}
    @if($verifiedSchemes->isNotEmpty())
        <div class="bg-white p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm space-y-3">
            <h3 class="text-xs sm:text-sm font-bold text-slate-800 uppercase tracking-wider">
                Daftar Skema Sertifikasi yang Terverifikasi di TUK Ini
            </h3>
            <div class="flex flex-wrap gap-2">
                @foreach($verifiedSchemes as $vs)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>{{ $vs->name }}</span>
                        <span class="text-[10px] text-slate-500 font-mono">({{ $vs->code }})</span>
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Upcoming Schedules --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm sm:text-base font-bold text-slate-900">Jadwal Uji Asesmen Jarak Jauh (AJJ) Terkini</h3>
                <p class="text-xs text-slate-500 mt-0.5">Sesi asesmen yang dijadwalkan pada TUK Anda</p>
            </div>
            @if($stats['is_verified'])
                <a href="{{ route('tuk.jadwal.index') }}" class="text-xs font-bold text-brand-600 hover:text-brand-800">
                    Lihat Semua &rarr;
                </a>
            @endif
        </div>

        @if($upcomingSchedules->isEmpty())
            <div class="p-8 text-center text-slate-500 text-xs">
                @if(!$stats['is_verified'])
                    Jadwal asesmen akan tampil di sini setelah data TUK Anda diverifikasi oleh Admin LSP.
                @else
                    Belum ada jadwal asesmen yang dijadwalkan untuk TUK ini.
                @endif
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($upcomingSchedules as $sch)
                    <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/60 transition">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-bold text-brand-700 bg-brand-50 px-2 py-0.5 rounded border border-brand-200">
                                    {{ $sch->code }}
                                </span>
                                <span class="text-xs font-bold text-slate-900">{{ $sch->name }}</span>
                            </div>
                            <p class="text-xs text-slate-600">
                                Skema: <strong>{{ $sch->scheme?->name }}</strong> &bull; 
                                Tanggal: <strong>{{ \Carbon\Carbon::parse($sch->date)->isoFormat('D MMMM Y') }}</strong>
                                ({{ substr($sch->start_time, 0, 5) }} - {{ substr($sch->end_time, 0, 5) }} WIB)
                            </p>
                            <p class="text-[11px] text-slate-500">
                                Asesi Terdaftar: <strong>{{ $sch->assessments->count() }} orang</strong>
                            </p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            @if(!empty($sch->meeting_link))
                                <a href="{{ $sch->meeting_link }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition" title="Buka Ruang Zoom / Meet">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <span>Ruang Virtual AJJ ↗</span>
                                </a>
                            @endif
                            <a href="{{ route('tuk.jadwal.show', $sch) }}" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition">
                                Detail Peserta
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
