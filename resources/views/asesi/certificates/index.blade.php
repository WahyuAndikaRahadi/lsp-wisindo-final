@extends('layouts.app', ['title' => 'Sertifikat Saya'])

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6" x-data="{
    showPreviewModal: false,
    previewUrl: '',
    previewTitle: '',

    openPreview(url, title) {
        this.previewUrl = url;
        this.previewTitle = title;
        this.showPreviewModal = true;
    }
}">
    {{-- Header & Navigasi Terstruktur --}}
    <div class="space-y-3 pb-2 border-b border-slate-200/80">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('asesi.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 hover:text-brand-800 transition py-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Dashboard</span>
            </a>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold shadow-xs">
                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>{{ $certificates->total() }} Sertifikat</span>
            </span>
        </div>
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-black text-slate-900 tracking-tight leading-snug">
                Sertifikat Saya
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 leading-relaxed max-w-2xl">
                Daftar sertifikat kompetensi BNSP yang telah Anda raih di LSP PrO Indo.
            </p>
        </div>
    </div>

    {{-- Search Filter --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
        <form action="{{ route('asesi.certificates.index') }}" method="GET" data-ajax-filter="asesi-certificates" data-no-loading="true" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 text-xs">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor sertifikat, nomor blanko, atau nama skema..."
                    class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none text-slate-900 placeholder:text-slate-400 transition shadow-2xs">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg transition text-xs shadow-sm flex items-center justify-center gap-1.5 whitespace-nowrap min-h-[38px]">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span>Cari</span>
                </button>

                <a href="{{ route('asesi.certificates.index') }}" data-ajax-reset="asesi-certificates" class="px-3.5 py-2.5 text-slate-600 hover:bg-slate-100 rounded-lg transition text-xs border border-slate-200 whitespace-nowrap min-h-[38px] flex items-center justify-center {{ request()->filled('search') ? '' : 'hidden' }}">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Certificates Table Container --}}
    <div id="asesi-certificates-table-container" data-ajax-container="asesi-certificates" data-skeleton-cols="2" data-skeleton-rows="4">
        @include('asesi.certificates._table')
    </div>

    {{-- MODAL PREVIEW PDF ASESI --}}
    <div x-show="showPreviewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(15, 23, 42, 0.75);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl max-w-4xl w-full p-5 shadow-2xl space-y-3" @click.away="showPreviewModal = false">
                <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span x-text="previewTitle"></span>
                    </h3>
                    <button type="button" @click="showPreviewModal = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
                </div>

                <div class="w-full bg-slate-100 rounded-lg overflow-hidden border border-slate-200" style="height: 70vh;">
                    <iframe :src="previewUrl" class="w-full h-full border-0"></iframe>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between items-start gap-2 pt-2">
                    <span class="text-[11px] text-slate-500">Sertifikat Resmi BNSP - LSP Praktisi Otomotif Indonesia</span>
                    <button type="button" @click="showPreviewModal = false" class="w-full sm:w-auto px-4 py-1.5 text-xs font-semibold bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-lg text-center">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
