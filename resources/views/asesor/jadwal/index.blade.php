@extends('layouts.app', ['title' => 'Jadwal Penugasan Asesor'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Jadwal Penugasan</h1>
            <p class="text-sm text-slate-500">Daftar jadwal asesmen yang ditugaskan kepada Anda</p>
        </div>
    </div>

    {{-- Token Registrasi Asesi (For Assessor in Field) --}}
    @if(isset($registrationToken))
        <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-white rounded-xl border border-emerald-200 p-3.5 sm:p-4 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-emerald-900">Token Registrasi Asesi:</span>
                        @if(isset($tokenInfo['days_remaining']))
                            <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full border border-emerald-200">
                                Berlaku {{ $tokenInfo['days_remaining'] }} hari lagi
                            </span>
                        @endif
                    </div>
                    <p class="text-[11px] text-slate-500">
                        Bagikan token ini kepada calon asesi yang membuat akun di lokasi asesmen. Token ini akan direset otomatis setiap 7 hari.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 self-start sm:self-auto shrink-0 bg-white px-3 py-1.5 rounded-lg border border-emerald-200 shadow-2xs"
                 x-data="{ copied: false, token: '{{ $registrationToken }}' }">
                <span class="font-mono font-black text-base text-emerald-700 tracking-wider select-all" x-text="token">
                    {{ $registrationToken }}
                </span>
                <button type="button" 
                        @click="navigator.clipboard.writeText(token); copied = true; setTimeout(() => copied = false, 2000)"
                        class="px-2.5 py-1 text-xs font-bold rounded transition"
                        :class="copied ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'">
                    <span x-show="!copied">Salin</span>
                    <span x-show="copied" x-cloak>Tersalin!</span>
                </button>
            </div>
        </div>
    @endif

    {{-- Banner Reminder if Asesor Signature is Not Set --}}
    @php
        $asesorProfile = auth()->user()->profile;
        $hasSignature = !empty($asesorProfile?->signature_data);
    @endphp
    @if(!$hasSignature)
        <div class="rounded-xl bg-amber-50 border border-amber-200 p-4 text-amber-900 flex items-start gap-3 shadow-sm">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="flex-1 text-sm">
                <h4 class="font-bold text-amber-900">Tanda Tangan Digital Belum Disimpan!</h4>
                <p class="mt-0.5 text-xs text-amber-800">
                    Anda belum menyimpan master tanda tangan digital. Setel tanda tangan Anda sekarang agar otomatis digunakan saat menandatangani persetujuan AK.01 dan rubrik instrumen uji.
                </p>
                <div class="mt-2.5">
                    <a href="{{ route('asesor.profile.edit') }}" class="inline-flex items-center px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold shadow-xs transition">
                        Atur Biodata & TTD Sekarang &rarr;
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- Quick Filter Chips --}}
    @php
        $currentStatus = request('status', 'all');
        $hasFilter = request()->filled('search') 
            || (request()->filled('status') && request('status') !== 'all') 
            || request()->filled('start_date') 
            || request()->filled('end_date')
            || (request()->filled('sort') && request('sort') !== 'date_desc');
    @endphp

    <div class="flex items-center gap-2 overflow-x-auto text-xs pb-1">
        <a href="{{ route('asesor.jadwal.index', array_merge(request()->except(['status', 'page']), ['status' => 'all'])) }}"
           class="px-3.5 py-1.5 rounded-lg border font-bold transition whitespace-nowrap {{ $currentStatus === 'all' || empty($currentStatus) ? 'bg-slate-900 text-white border-slate-900 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            Semua Jadwal ({{ $counts['total'] ?? $schedules->total() }})
        </a>
        <a href="{{ route('asesor.jadwal.index', array_merge(request()->except(['status', 'page']), ['status' => 'active'])) }}"
           class="px-3.5 py-1.5 rounded-lg border font-bold transition whitespace-nowrap flex items-center gap-1.5 {{ $currentStatus === 'active' ? 'bg-blue-600 text-white border-blue-600 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            <span class="w-2 h-2 rounded-full {{ $currentStatus === 'active' ? 'bg-white' : 'bg-blue-500' }}"></span>
            <span>Jadwal Aktif ({{ $counts['active'] ?? 0 }})</span>
        </a>
        <a href="{{ route('asesor.jadwal.index', array_merge(request()->except(['status', 'page']), ['status' => 'completed'])) }}"
           class="px-3.5 py-1.5 rounded-lg border font-bold transition whitespace-nowrap flex items-center gap-1.5 {{ in_array($currentStatus, ['completed', 'finished', 'selesai']) ? 'bg-emerald-600 text-white border-emerald-600 shadow-xs' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
            <span class="w-2 h-2 rounded-full {{ in_array($currentStatus, ['completed', 'finished', 'selesai']) ? 'bg-white' : 'bg-emerald-500' }}"></span>
            <span>Jadwal Selesai ({{ $counts['completed'] ?? 0 }})</span>
        </a>
    </div>

    {{-- Filter, Search & Sort Form --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
        <form action="{{ route('asesor.jadwal.index') }}" method="GET" data-ajax-filter="asesor-jadwal" data-no-loading="true" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end text-xs">
                {{-- Search Bar: Nama Jadwal, Kode, TUK --}}
                <div class="col-span-1 sm:col-span-2 lg:col-span-4">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Cari Jadwal</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama jadwal, kode, atau TUK..."
                            class="w-full pl-9 pr-3.5 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none text-slate-900 placeholder:text-slate-400 transition shadow-2xs">
                    </div>
                </div>

                {{-- Status Filter --}}
                <div class="col-span-1 sm:col-span-1 lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Status</label>
                    <select name="status" class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none bg-white text-slate-800 shadow-2xs">
                        <option value="all" {{ request('status', 'all') === 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="completed" {{ in_array(request('status'), ['completed', 'finished', 'selesai']) ? 'selected' : '' }}>Selesai</option>
                    </select>
                </div>

                {{-- Dari Tanggal --}}
                <div class="col-span-1 sm:col-span-1 lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                        class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none bg-white text-slate-800 shadow-2xs">
                </div>

                {{-- Sampai Tanggal --}}
                <div class="col-span-1 sm:col-span-1 lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                        class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none bg-white text-slate-800 shadow-2xs">
                </div>

                {{-- Urutkan (Sort) --}}
                <div class="col-span-1 sm:col-span-1 lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Urutan</label>
                    <select name="sort" class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none bg-white text-slate-800 shadow-2xs">
                        <option value="date_desc" {{ request('sort', 'date_desc') === 'date_desc' ? 'selected' : '' }}>Tanggal Terbaru</option>
                        <option value="date_asc" {{ request('sort') === 'date_asc' ? 'selected' : '' }}>Tanggal Terlama</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Nama Jadwal (A-Z)</option>
                        <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>Nama Jadwal (Z-A)</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                <div class="text-slate-500 text-[11px]">
                    @if($hasFilter)
                        <span>Menampilkan hasil penyortiran & filter</span>
                    @else
                        <span>Menampilkan seluruh jadwal penugasan</span>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('asesor.jadwal.index') }}" data-ajax-reset="asesor-jadwal" class="px-3.5 py-1.5 text-slate-600 hover:bg-slate-100 rounded-lg transition text-xs border border-slate-200 whitespace-nowrap min-h-[34px] flex items-center justify-center {{ $hasFilter ? '' : 'hidden' }}">
                        Reset Filter
                    </a>

                    <button type="submit" class="px-4 py-1.5 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg transition text-xs shadow-sm flex items-center justify-center gap-1.5 whitespace-nowrap min-h-[34px]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        <span>Terapkan Filter</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Schedules List --}}
    <div id="asesor-jadwal-table-container" data-ajax-container="asesor-jadwal" data-skeleton-cols="2" data-skeleton-rows="4">
        @include('asesor.jadwal._table')
    </div>

    {{-- Modal Surat Tugas Asesor --}}
    @include('asesor.partials.surat-tugas-modal')
</div>
@endsection
