@extends('layouts.app', ['title' => 'Aspirasi & Suara Asesi (FR.AK.04 & FR.AK.03)'])

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ activeTab: '{{ $activeTab ?? 'appeals' }}' }">
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
                Pusat Banding &amp; Umpan Balik Asesi
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
                Kelola permohonan sanggahan hasil asesmen (FR.AK.04) dan rekapitulasi evaluasi pengalaman uji asesi (FR.AK.03) secara transparan dan akuntabel.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200 shadow-2xs">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>{{ $counts['appeals_total'] }} Banding &bull; {{ $counts['feedbacks_total'] }} Umpan Balik</span>
            </span>
        </div>
    </div>

    {{-- Stats Counters Grid --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Menunggu Putusan</span>
                <span class="w-2.5 h-2.5 rounded-full {{ $counts['appeals_pending'] > 0 ? 'bg-amber-500 animate-pulse' : 'bg-slate-300' }}"></span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-slate-900 mt-2">{{ $counts['appeals_pending'] }}</div>
            <p class="text-[11px] text-slate-400 mt-1">Sanggahan perlu ditindaklanjuti</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Banding Diterima</span>
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-slate-900 mt-2">{{ $counts['appeals_accepted'] }}</div>
            <p class="text-[11px] text-slate-400 mt-1">Sanggahan dikabulkan Komite</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-rose-700">Banding Ditolak</span>
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-slate-900 mt-2">{{ $counts['appeals_rejected'] }}</div>
            <p class="text-[11px] text-slate-400 mt-1">Keputusan awal asesmen tetap</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-700">Umpan Balik Masuk</span>
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
            </div>
            <div class="text-2xl sm:text-3xl font-black text-slate-900 mt-2">{{ $counts['feedbacks_total'] }}</div>
            <p class="text-[11px] text-slate-400 mt-1">Survei kenyamanan &amp; mutu asesmen</p>
        </div>
    </div>

    {{-- Tabs Switcher --}}
    <div class="flex items-center gap-2 border-b border-slate-200 overflow-x-auto">
        <button type="button" 
                @click="activeTab = 'appeals'"
                :class="activeTab === 'appeals' ? 'border-brand-600 text-brand-700 font-extrabold' : 'border-transparent text-slate-500 hover:text-slate-800 font-medium'"
                class="py-3 px-4 border-b-2 text-xs sm:text-sm flex items-center gap-2 transition whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
            <span>Pengajuan Banding Asesmen (FR.AK.04)</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $counts['appeals_pending'] > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">
                {{ $counts['appeals_total'] }}
                @if($counts['appeals_pending'] > 0)
                    ({{ $counts['appeals_pending'] }} Baru)
                @endif
            </span>
        </button>

        <button type="button" 
                @click="activeTab = 'feedbacks'"
                :class="activeTab === 'feedbacks' ? 'border-brand-600 text-brand-700 font-extrabold' : 'border-transparent text-slate-500 hover:text-slate-800 font-medium'"
                class="py-3 px-4 border-b-2 text-xs sm:text-sm flex items-center gap-2 transition whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            <span>Umpan Balik &amp; Catatan Asesi (FR.AK.03)</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600">
                {{ $counts['feedbacks_total'] }}
            </span>
        </button>
    </div>

    {{-- TAB 1: FR.AK.04 BANDING ASESMEN --}}
    <div x-show="activeTab === 'appeals'" x-cloak class="space-y-5">
        <div id="appeals-table-container" data-ajax-container="appeals" data-skeleton-cols="4" data-skeleton-rows="4">
            @include('admin.appeals._appeals_table')
        </div>
    </div>

    {{-- TAB 2: FR.AK.03 UMPAN BALIK ASESI --}}
    <div x-show="activeTab === 'feedbacks'" x-cloak class="space-y-5">
        <div id="feedbacks-table-container" data-ajax-container="feedbacks" data-skeleton-cols="4" data-skeleton-rows="4">
            @include('admin.appeals._feedbacks_table')
        </div>
    </div>
</div>
@endsection
