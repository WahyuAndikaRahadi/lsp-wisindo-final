@extends('layouts.app', ['title' => 'Dokumen MUK & Berita Acara'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Dokumen MUK & Berita Acara</h1>
            <p class="text-sm text-slate-500">Pilih jadwal asesmen untuk mencetak dokumen MUK, berita acara, dan berkas individual asesi</p>
        </div>
    </div>

    {{-- Search Bar --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
        <form action="{{ route('admin.dokumen-muk.index') }}" method="GET" data-ajax-filter="muk" data-no-loading="true" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 text-xs">
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
                <button type="submit" class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg transition text-xs shadow-sm flex items-center justify-center gap-1.5 whitespace-nowrap">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span>Cari Jadwal</span>
                </button>

                @if(!empty($search))
                    <a href="{{ route('admin.dokumen-muk.index') }}" data-ajax-reset="muk" class="px-3.5 py-2.5 text-slate-600 hover:bg-slate-100 rounded-lg transition text-xs border border-slate-200 whitespace-nowrap">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Schedules Table --}}
    <div id="muk-table-container" data-ajax-container="muk" data-skeleton-cols="6" data-skeleton-rows="5">
        @include('admin.dokumen-muk._table')
    </div>
</div>
@endsection