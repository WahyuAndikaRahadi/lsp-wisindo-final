@extends('layouts.app', ['title' => 'Detail Jadwal & Asesi - ' . $jadwal->name])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('admin.jadwal.index') }}" class="text-xs text-brand-600 hover:text-brand-700 font-semibold">&larr; Kembali ke Daftar Jadwal</a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">{{ $jadwal->name }}</h1>
            <div class="flex items-center space-x-3 text-xs text-slate-500 mt-1">
                <span class="font-mono font-bold text-brand-700 bg-brand-50 px-2 py-0.5 rounded">{{ $jadwal->code }}</span>
                @if($jadwal->is_closed)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-300">
                        Ditutup (Read-Only)
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-300">
                        Jadwal Aktif
                    </span>
                @endif
                <span class="font-mono font-bold text-brand-600">{{ $jadwal->code }}</span>
                <span>{{ $jadwal->scheme->name }}</span>
                <span>TUK: {{ $jadwal->tuk_name }}</span>
                <span>{{ $jadwal->formatted_date_range }}</span>
                <span>Peserta: {{ $jadwal->effective_candidate_count }} Siswa</span>
                @if($jadwal->surat_tugas_number)
                    <span class="font-mono text-slate-600">No. ST: {{ $jadwal->surat_tugas_number }}</span>
                @endif
            </div>
        </div>
        <div class="flex items-center space-x-2 flex-wrap gap-y-2">
            <a href="{{ route('admin.jadwal.surat-tugas', $jadwal->id) }}" target="_blank" class="px-3.5 py-2 text-xs font-bold text-brand-800 bg-brand-50 hover:bg-brand-100 border border-brand-300 rounded-lg shadow-xs transition flex items-center gap-1.5" title="Lihat Dokumen Surat Tugas Asesor">
                <svg class="w-3.5 h-3.5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Surat Tugas Asesor</span>
            </a>
            @if(in_array(auth()->user()?->role, ['SUPER_ADMIN', 'ADMIN_LSP']))
                <form action="{{ route('admin.jadwal.toggle-lock', $jadwal->id) }}" method="POST" class="inline">
                    @csrf
                    @if($jadwal->is_closed)
                        <button type="submit" 
                                onclick="return confirm('Buka kembali jadwal ini? Seluruh data pengerjaan, nilai, dan tanda tangan tetap aman tersimpan, dan formulir akan dapat ditinjau/diedit kembali.')"
                                class="px-3.5 py-2 text-xs font-bold text-amber-900 bg-amber-50 hover:bg-amber-100 border border-amber-300 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                                title="Buka gembok jadwal untuk perbaikan/peninjauan formulir">
                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                            <span>Buka Kembali Jadwal</span>
                        </button>
                    @else
                        <button type="submit" 
                                onclick="return confirm('Kunci dan tutup jadwal asesmen ini secara resmi? Seluruh formulir asesi dan asesor akan dikunci ke mode lihat (read-only).')"
                                class="px-3.5 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                                title="Tutup dan kunci resmi jadwal asesmen">
                            <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span>Kunci &amp; Tutup Jadwal</span>
                        </button>
                    @endif
                </form>
            @endif
            <a href="{{ route('admin.certificates.schedule', $jadwal->id) }}" class="px-3.5 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 rounded-lg shadow-xs transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Kelola Sertifikat</span>
            </a>
            <a href="{{ route('admin.dokumen-muk.index') }}" class="px-3.5 py-2 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg shadow-xs transition">
                Pusat Dokumen MUK &rarr;
            </a>
        </div>
    </div>

    {{-- Search & Filter Asesi --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.jadwal.show', $jadwal->id) }}" data-ajax-filter="peserta" data-no-loading="true" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama asesi, email, atau NIK..."
                       class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-1 focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-2xs">
                    <span>Cari</span>
                </button>
                <a href="{{ route('admin.jadwal.show', $jadwal->id) }}" data-ajax-reset="peserta" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition flex items-center justify-center {{ request()->filled('search') ? '' : 'hidden' }}">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Review Asesi Table --}}
    <div id="peserta-table-container" data-ajax-container="peserta" data-skeleton-cols="6" data-skeleton-rows="5">
        @include('admin.jadwal._peserta_table')
    </div>
</div>
@endsection
