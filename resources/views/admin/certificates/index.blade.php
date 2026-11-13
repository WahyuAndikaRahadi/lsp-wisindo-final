@extends('layouts.app', ['title' => 'Kelola Sertifikat Kompetensi'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Kelola Sertifikat Kompetensi</h1>
            <p class="text-sm text-slate-500">Penerbitan dan pengunggahan berkas fisik sertifikat BNSP untuk asesi yang dinyatakan Kompeten (K)</p>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
        <form action="{{ route('admin.certificates.index') }}" method="GET" data-ajax-filter="certificates" data-no-loading="true" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 text-xs">
            <div class="relative flex-1 min-w-[220px]">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama jadwal, kode, atau TUK..."
                    class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
            </div>

            <div class="min-w-[220px]">
                <select name="scheme_id" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs cursor-pointer">
                    <option value="">-- Semua Skema Sertifikasi --</option>
                    @foreach($schemes as $s)
                        <option value="{{ $s->id }}" {{ $schemeId == $s->id ? 'selected' : '' }}>
                            {{ $s->code }} - {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="submit" class="flex-1 sm:flex-initial px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl transition shadow-xs flex items-center justify-center gap-1.5 whitespace-nowrap min-h-[40px]">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <span>Filter</span>
                </button>

                <a href="{{ route('admin.certificates.index') }}" data-ajax-reset="certificates" class="px-3.5 py-2.5 text-slate-600 hover:bg-slate-100 rounded-xl border border-slate-200 transition font-medium whitespace-nowrap min-h-[40px] inline-flex items-center justify-center {{ ($search || $schemeId) ? '' : 'hidden' }}">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Schedules List (Responsive: Cards on Mobile, Table on Desktop) --}}
    <div id="certificates-table-container" data-ajax-container="certificates" data-skeleton-cols="6" data-skeleton-rows="6">
        @include('admin.certificates._table')
    </div>
</div>
@endsection
