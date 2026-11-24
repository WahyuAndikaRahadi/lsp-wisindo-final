@extends('layouts.app', ['title' => 'Admin Dashboard'])

@section('content')
<div class="space-y-6">
    {{-- Header Banner --}}
    <div class="bg-gradient-to-r from-lsp-navy to-lsp-navy-dark rounded-xl p-5 sm:p-6 md:p-8 text-white shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4 border-t-4 border-lsp-red">
        <div>
            <span class="inline-block px-2.5 py-0.5 rounded-full bg-white/10 text-lsp-red border border-lsp-red/30 text-[10px] font-extrabold tracking-wider uppercase mb-2">
                Panel Administrasi LSP PrO Indo
            </span>
            <h1 class="font-sans text-xl sm:text-2xl md:text-3xl font-extrabold tracking-tight">
                Selamat Datang, {{ auth()->user()->full_name }}!
            </h1>
            <p class="text-blue-100/80 text-xs mt-1 max-w-xl">
                Kelola jadwal asesmen, skema sertifikasi, master data pengguna, serta rekap dokumen MUK dan berita acara asesmen.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:space-x-3 shrink-0">
            <a href="{{ route('admin.jadwal.create') }}" class="px-4 py-2.5 bg-lsp-red hover:bg-lsp-red-dark text-white rounded-lg text-xs font-bold shadow-sm transition flex items-center justify-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Buat Jadwal Baru</span>
            </a>
            <a href="{{ route('admin.dokumen-muk.index') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 border border-white/20 text-white rounded-lg text-xs font-semibold transition text-center justify-center">
                Dokumen MUK
            </a>
        </div>
    </div>

    {{-- Active Registration Token Card --}}
    <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-white rounded-xl border border-emerald-200 p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-900">Token Registrasi Asesi Aktif</span>
                    @if(isset($tokenInfo['days_remaining']))
                        <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-full border border-emerald-200">
                            Berlaku {{ $tokenInfo['days_remaining'] }} hari lagi
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Gunakan token ini untuk pendaftaran calon peserta (asesi). Token diacak ulang secara otomatis setiap 7 hari (mingguan).
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto shrink-0">
            <div class="flex items-center gap-2 bg-white px-3.5 py-2 rounded-xl border border-emerald-200 shadow-2xs"
                 x-data="{ copied: false, token: '{{ $registrationToken }}' }">
                <span class="font-mono font-black text-lg text-emerald-700 tracking-wider select-all" x-text="token">
                    {{ $registrationToken }}
                </span>
                <button type="button" 
                        @click="navigator.clipboard.writeText(token); copied = true; setTimeout(() => copied = false, 2000)"
                        class="px-2.5 py-1 text-xs font-bold rounded-lg transition cursor-pointer"
                        :class="copied ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'">
                    <span x-show="!copied">Salin</span>
                    <span x-show="copied" x-cloak>Tersalin!</span>
                </button>
            </div>

            @if(in_array(auth()->user()?->role, ['SUPER_ADMIN', 'ADMIN_LSP']))
                <form action="{{ route('admin.registration-token.rotate') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            onclick="return confirm('Acak ulang token pendaftaran sekarang? Token baru akan langsung aktif dan dibagikan ke asesor.')"
                            class="px-3 py-2 text-xs font-bold text-emerald-800 bg-emerald-100 hover:bg-emerald-200 border border-emerald-300 rounded-xl transition shadow-2xs flex items-center gap-1.5 cursor-pointer"
                            title="Acak ulang token sekarang">
                        <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>Acak Ulang</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Stats Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 rounded-lg bg-lsp-navy/10 text-lsp-navy flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Jadwal</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5">{{ $stats['total_schedules'] ?? 0 }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 rounded-lg bg-lsp-navy/10 text-lsp-navy flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Asesi</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5">{{ $stats['total_asesi'] ?? 0 }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 rounded-lg bg-lsp-red/10 text-lsp-red flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7 7z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Asesor Aktif</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5">{{ $stats['total_asesor'] ?? 0 }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 rounded-lg bg-lsp-red/10 text-lsp-red flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Skema Sertifikasi</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5">{{ $stats['total_schemes'] ?? 0 }}</h3>
            </div>
        </div>
    </div>


    {{-- Recent Schedules & Quick Actions --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Jadwal Asesmen Terkini</h2>
                    <p class="text-xs text-slate-500">Daftar jadwal aktif terbaru dalam sistem</p>
                </div>
                <a href="{{ route('admin.jadwal.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 shrink-0">
                    Lihat Semua &rarr;
                </a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($recentSchedules ?? [] as $sch)
                    <div class="p-4 hover:bg-slate-50/60 transition flex flex-col sm:flex-row sm:items-center sm:justify-between items-start gap-2.5">
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-slate-100 text-slate-700">{{ $sch->code }}</span>
                                <h3 class="text-sm font-bold text-slate-900">{{ $sch->name ?? $sch->scheme->name }}</h3>
                            </div>
                            <div class="text-xs text-slate-500 flex flex-wrap items-center gap-2">
                                <span>TUK: {{ $sch->tuk_name }}</span>
                                <span>&bull;</span>
                                <span>{{ \Carbon\Carbon::parse($sch->date)->isoFormat('D MMM Y') }}</span>
                            </div>
                        </div>
                        <a href="{{ route('admin.jadwal.show', $sch->id) }}" class="text-xs font-bold text-brand-600 hover:text-brand-700 self-end sm:self-auto shrink-0">
                            Kelola &rarr;
                        </a>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-slate-500">
                        Belum ada jadwal yang dibuat.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Quick Actions Panel --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900">Aksi Cepat</h2>
            <div class="space-y-2">
                <a href="{{ route('admin.jadwal.create') }}" class="flex items-center space-x-3 p-3 rounded-lg border border-slate-200 hover:border-lsp-navy hover:bg-lsp-input-bg transition text-xs font-bold text-slate-800">
                    <div class="w-8 h-8 rounded bg-lsp-navy/10 text-lsp-navy flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <span>Buat Jadwal Asesmen</span>
                </a>
                <a href="{{ route('admin.dokumen-muk.index') }}" class="flex items-center space-x-3 p-3 rounded-lg border border-slate-200 hover:border-lsp-navy hover:bg-lsp-input-bg transition text-xs font-bold text-slate-800">
                    <div class="w-8 h-8 rounded bg-lsp-navy/10 text-lsp-navy flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <span>Cetak Dokumen MUK</span>
                </a>
                <a href="{{ route('admin.proxy.index') }}" class="flex items-center space-x-3 p-3 rounded-lg border border-slate-200 hover:border-lsp-red hover:bg-red-50/50 transition text-xs font-bold text-slate-800">
                    <div class="w-8 h-8 rounded bg-lsp-red/10 text-lsp-red flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                    <span>Proxy Login Pengguna</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
