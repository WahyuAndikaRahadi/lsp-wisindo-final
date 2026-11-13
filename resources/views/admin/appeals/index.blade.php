@extends('layouts.app', ['title' => 'Pusat Umpan & Banding Asesi (FR.AK.03 & FR.AK.04)'])

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ activeTab: '{{ $activeTab ?? 'schedules' }}' }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/80">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-purple-100 text-purple-800 border border-purple-200">
                    Aspirasi &amp; Advokasi Asesi
                </span>
                <span class="text-xs text-slate-400">&bull; Standar BNSP</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">
                Pusat Umpan &amp; Banding Asesi
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
                Pantau rekapitulasi umpan balik asesi (FR.AK.03) dan pengajuan sanggahan hasil asesmen (FR.AK.04) berbasis jadwal asesmen secara transparan dan akuntabel.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200 shadow-2xs">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>{{ $counts['schedules_total'] }} Jadwal &bull; {{ $counts['appeals_total'] }} Banding &bull; {{ $counts['feedbacks_total'] }} Umpan Balik</span>
            </span>
        </div>
    </div>

    {{-- Stats Counters Grid --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Total Jadwal</span>
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-slate-900 mt-2">{{ $counts['schedules_total'] }}</div>
            <p class="text-[11px] text-slate-400 mt-1">{{ $counts['schedules_with_appeals'] }} jadwal terdapat banding</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Menunggu Putusan</span>
                <span class="w-2.5 h-2.5 rounded-full {{ $counts['appeals_pending'] > 0 ? 'bg-amber-500 animate-pulse' : 'bg-slate-300' }}"></span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-slate-900 mt-2">{{ $counts['appeals_pending'] }}</div>
            <p class="text-[11px] text-slate-400 mt-1">Sanggahan perlu direspon</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-purple-700">Total Banding</span>
                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-slate-900 mt-2">{{ $counts['appeals_total'] }}</div>
            <p class="text-[11px] text-slate-400 mt-1">{{ $counts['appeals_accepted'] }} Uji Ulang &bull; {{ $counts['appeals_rejected'] }} Tetap</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-700">Umpan Balik Masuk</span>
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-slate-900 mt-2">{{ $counts['feedbacks_total'] }}</div>
            <p class="text-[11px] text-slate-400 mt-1">Kuesioner FR.AK.03 terisi</p>
        </div>
    </div>

    {{-- Tabs Switcher --}}
    <div class="flex items-center gap-2 border-b border-slate-200 overflow-x-auto">
        <button type="button" 
                @click="activeTab = 'schedules'"
                :class="activeTab === 'schedules' ? 'border-brand-600 text-brand-700 font-extrabold' : 'border-transparent text-slate-500 hover:text-slate-800 font-medium'"
                class="py-3 px-4 border-b-2 text-xs sm:text-sm flex items-center gap-2 transition whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Daftar Jadwal Asesmen (Per Jadwal)</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">
                {{ $counts['schedules_total'] }}
            </span>
        </button>

        <button type="button" 
                @click="activeTab = 'appeals'"
                :class="activeTab === 'appeals' ? 'border-brand-600 text-brand-700 font-extrabold' : 'border-transparent text-slate-500 hover:text-slate-800 font-medium'"
                class="py-3 px-4 border-b-2 text-xs sm:text-sm flex items-center gap-2 transition whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
            <span>Semua Pengajuan Banding (Global)</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $counts['appeals_pending'] > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">
                {{ $counts['appeals_total'] }}
                @if($counts['appeals_pending'] > 0)
                    ({{ $counts['appeals_pending'] }} Baru)
                @endif
            </span>
        </button>
    </div>

    {{-- TAB 1: LISTING JADWAL ASESMEN (PER JADWAL) --}}
    <div x-show="activeTab === 'schedules'" class="space-y-4">
        {{-- Search & Quick Filter Bar --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs space-y-3">
            <form action="{{ route('admin.appeals.index') }}" method="GET" data-ajax-filter="schedules" data-no-loading="true" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 text-xs">
                <input type="hidden" name="tab" value="schedules">
                <input type="hidden" name="filter" value="{{ $filter ?? 'all' }}" id="schedule-filter-input">

                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari jadwal berdasarkan nama, kode, skema, atau TUK..."
                        class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none text-slate-900 placeholder:text-slate-400 transition shadow-2xs">
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg transition text-xs shadow-xs flex items-center justify-center gap-1.5 whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Cari Jadwal</span>
                    </button>

                    @if(!empty($search) || (isset($filter) && $filter !== 'all'))
                        <a href="{{ route('admin.appeals.index') }}" data-ajax-reset="schedules" class="px-3.5 py-2.5 text-slate-600 hover:bg-slate-100 rounded-lg transition text-xs border border-slate-200 whitespace-nowrap">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            {{-- Filter Chips --}}
            <div class="flex items-center gap-2 overflow-x-auto pt-1 text-xs">
                <a href="{{ route('admin.appeals.index', array_merge(request()->query(), ['filter' => 'all'])) }}"
                   class="px-3 py-1.5 rounded-lg border font-bold transition whitespace-nowrap {{ ($filter ?? 'all') === 'all' ? 'bg-slate-900 text-white border-slate-900' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100' }}">
                    Semua Jadwal ({{ $counts['schedules_total'] }})
                </a>

                <a href="{{ route('admin.appeals.index', array_merge(request()->query(), ['filter' => 'has_appeals'])) }}"
                   class="px-3 py-1.5 rounded-lg border font-bold transition whitespace-nowrap {{ ($filter ?? '') === 'has_appeals' ? 'bg-purple-600 text-white border-purple-600' : 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100' }}">
                    Ada Banding ({{ $counts['schedules_with_appeals'] }})
                </a>

                @if($counts['appeals_pending'] > 0)
                    <a href="{{ route('admin.appeals.index', array_merge(request()->query(), ['filter' => 'pending_appeals'])) }}"
                       class="px-3 py-1.5 rounded-lg border font-bold transition whitespace-nowrap {{ ($filter ?? '') === 'pending_appeals' ? 'bg-rose-600 text-white border-rose-600' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100 animate-pulse' }}">
                        Perlu Putusan Banding ({{ $counts['appeals_pending'] }})
                    </a>
                @endif

                <a href="{{ route('admin.appeals.index', array_merge(request()->query(), ['filter' => 'feedbacks_incomplete'])) }}"
                   class="px-3 py-1.5 rounded-lg border font-bold transition whitespace-nowrap {{ ($filter ?? '') === 'feedbacks_incomplete' ? 'bg-amber-600 text-white border-amber-600' : 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100' }}">
                    Umpan Balik Belum Lengkap
                </a>

                <a href="{{ route('admin.appeals.index', array_merge(request()->query(), ['filter' => 'feedbacks_complete'])) }}"
                   class="px-3 py-1.5 rounded-lg border font-bold transition whitespace-nowrap {{ ($filter ?? '') === 'feedbacks_complete' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' }}">
                    Umpan Balik Lengkap (100%)
                </a>
            </div>
        </div>

        {{-- Schedules AJAX Container --}}
        <div id="schedules-table-container" data-ajax-container="schedules" data-skeleton-cols="6" data-skeleton-rows="5">
            @include('admin.appeals._schedules_table')
        </div>
    </div>

    {{-- TAB 2: SEMUA PENGAJUAN BANDING (GLOBAL) --}}
    <div x-show="activeTab === 'appeals'" x-cloak class="space-y-4">
        <div id="appeals-table-container" data-ajax-container="appeals" data-skeleton-cols="4" data-skeleton-rows="4">
            @include('admin.appeals._appeals_table')
        </div>
    </div>
</div>
@endsection
