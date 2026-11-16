@extends('layouts.app', ['title' => 'Kelola Sertifikat Kompetensi'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Kelola Sertifikat Kompetensi</h1>
            <p class="text-sm text-slate-500">Penerbitan dan pengunggahan berkas fisik sertifikat BNSP untuk asesi yang dinyatakan Kompeten (K)</p>
        </div>
    </div>

    {{-- Filter & Search Bar --}}
    @php
        $hasFilter = request()->filled('search') || request()->filled('scheme_id') || request()->filled('start_date') || request()->filled('end_date') || !empty($search) || !empty($schemeId) || !empty($startDate) || !empty($endDate);
    @endphp
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
        <form method="GET" action="{{ route('admin.certificates.index') }}" data-ajax-filter="certificates" data-no-loading="true" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            {{-- Search Bar: Nama Jadwal / Kode / TUK --}}
            <div class="lg:col-span-3">
                <label class="block text-xs font-bold text-slate-700 mb-1">Cari Jadwal</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search', $search ?? '') }}" placeholder="Cari nama, kode, atau TUK..."
                        class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            {{-- Filter Skema --}}
            <div class="lg:col-span-3">
                <label class="block text-xs font-bold text-slate-700 mb-1">Skema Sertifikasi</label>
                <select name="scheme_id" class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white">
                    <option value="">-- Semua Skema --</option>
                    @foreach($schemes ?? [] as $s)
                        <option value="{{ $s->id }}" {{ (string) request('scheme_id', $schemeId ?? '') === (string) $s->id ? 'selected' : '' }}>
                            {{ $s->code }} - {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Rentang Tanggal Mulai --}}
            <div class="lg:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date', $startDate ?? '') }}"
                    class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>

            {{-- Rentang Tanggal Akhir --}}
            <div class="lg:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date', $endDate ?? '') }}"
                    class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>

            {{-- Action Buttons --}}
            <div class="lg:col-span-2 flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 px-3.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-lg shadow-sm transition flex items-center justify-center gap-1.5 shrink-0" title="Terapkan Filter">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <span>Filter</span>
                </button>
                <a href="{{ route('admin.certificates.index') }}" data-ajax-reset="certificates" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-800 rounded-lg text-xs font-bold transition flex items-center justify-center border border-slate-200 shrink-0 {{ $hasFilter ? '' : 'hidden' }}" title="Reset Filter">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
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
