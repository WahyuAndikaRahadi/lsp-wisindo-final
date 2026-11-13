@extends('layouts.app', ['title' => 'Jadwal Asesmen'])

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6">
    {{-- Header & Navigasi Terstruktur --}}
    <div class="space-y-3 pb-2 border-b border-slate-200/80">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('asesi.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 hover:text-brand-800 transition py-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Dashboard</span>
            </a>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                Jadwal Uji
            </span>
        </div>
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-black text-slate-900 tracking-tight leading-snug">
                Jadwal Asesmen Kompetensi
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 leading-relaxed max-w-2xl">
                Pilih atau masukkan kode jadwal untuk mengikuti asesmen sertifikasi profesi
            </p>
        </div>
    </div>

    {{-- Join Schedule Form Box --}}
    @php
        $profileComplete = auth()->user()->isProfileComplete();
    @endphp

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-6">
        <div class="max-w-2xl">
            <h2 class="text-sm sm:text-base font-bold text-slate-900">Gabung Jadwal Asesmen Baru</h2>
            <p class="text-xs text-slate-500 mt-1 mb-4 leading-relaxed">
                Masukkan kode verifikasi jadwal (contoh: <code class="px-1.5 py-0.5 bg-slate-100 rounded text-brand-700 font-semibold font-mono">LSP-xxxx</code>) yang diberikan oleh penyelenggara atau sekolah Anda.
            </p>

            @if(! $profileComplete)
                <div class="p-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-amber-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-amber-800">Biodata & Tanda Tangan Belum Lengkap</h3>
                            <p class="text-xs text-amber-700 mt-0.5">Form pendaftaran jadwal dikunci sampai Anda melengkapi seluruh data profil dan tanda tangan digital Anda.</p>
                        </div>
                    </div>
                    <a href="{{ route('asesi.profile.edit') }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-lg transition shrink-0 text-center">
                        Lengkapi Profil & TTD &rarr;
                    </a>
                </div>
            @endif

            <form action="{{ route('asesi.jadwal.join') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <div class="flex-1">
                    <input 
                        type="text" 
                        name="code" 
                        required 
                        @disabled(! $profileComplete)
                        placeholder="Masukkan Kode Jadwal (Misal: LSP-K9P2)" 
                        value="{{ old('code') }}"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 font-mono uppercase tracking-wider text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none placeholder-normal @if(! $profileComplete) bg-slate-100 cursor-not-allowed opacity-60 @endif"
                    >
                    @error('code')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" @disabled(! $profileComplete) class="w-full sm:w-auto px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm rounded-lg shadow-sm transition shrink-0 flex items-center justify-center space-x-2 @if(! $profileComplete) opacity-50 cursor-not-allowed pointer-events-none @endif">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Gabung Jadwal</span>
                </button>
            </form>
        </div>
    </div>

    {{-- List of Enrolled Schedules --}}
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="text-lg font-bold text-slate-900">Jadwal yang Sedang Diikuti</h2>

            {{-- Quick Search Form --}}
            <form action="{{ route('asesi.jadwal.index') }}" method="GET" data-ajax-filter="asesi-jadwal" data-no-loading="true" class="flex items-center gap-2">
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari jadwal yang diikuti..."
                        class="text-xs px-3 py-1.5 pl-8 rounded-lg border border-slate-300 bg-white text-slate-700 focus:ring-brand-500 w-48 sm:w-60">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg">
                    Cari
                </button>
                <a href="{{ route('asesi.jadwal.index') }}" data-ajax-reset="asesi-jadwal" class="px-2.5 py-1.5 text-xs text-slate-600 hover:text-slate-900 bg-slate-100 rounded-lg {{ request()->filled('search') ? '' : 'hidden' }}">
                    Reset
                </a>
            </form>
        </div>

        <div id="asesi-jadwal-table-container" data-ajax-container="asesi-jadwal" data-skeleton-cols="2" data-skeleton-rows="4">
            @include('asesi.jadwal._table')
        </div>
    </div>
</div>
@endsection
