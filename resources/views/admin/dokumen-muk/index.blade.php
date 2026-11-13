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
        <form action="{{ route('admin.dokumen-muk.index') }}" method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 text-xs">
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
                    <a href="{{ route('admin.dokumen-muk.index') }}" class="px-3.5 py-2.5 text-slate-600 hover:bg-slate-100 rounded-lg transition text-xs border border-slate-200 whitespace-nowrap">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Schedules Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between items-start gap-1">
            <h2 class="text-sm font-bold text-slate-900">
                Daftar Jadwal Asesmen ({{ $schedules->total() }})
            </h2>
            @if(!empty($search))
                <span class="text-xs text-slate-500">Hasil pencarian untuk: <strong class="text-slate-800">"{{ $search }}"</strong></span>
            @endif
        </div>

        @if($schedules->isEmpty())
            <div class="p-12 text-center">
                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <div class="text-slate-600 font-semibold text-sm">Tidak ada jadwal asesmen ditemukan</div>
                <p class="text-xs text-slate-400 mt-1">
                    @if(!empty($search))
                        Tidak ditemukan jadwal dengan kata kunci pencarian "{{ $search }}". Silakan coba kata kunci lain.
                    @else
                        Belum ada jadwal asesmen tersedia untuk ekspor dokumen MUK.
                    @endif
                </p>
                @if(!empty($search))
                    <a href="{{ route('admin.dokumen-muk.index') }}" class="inline-block mt-4 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition">
                        Reset Pencarian
                    </a>
                @endif
            </div>
        @else
            {{-- Mobile Cards (< md) --}}
            <div class="block md:hidden divide-y divide-slate-100">
                @foreach($schedules as $sch)
                    <div class="p-4 space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-md bg-brand-50 text-brand-700 border border-brand-100">
                                {{ $sch->code }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                {{ $sch->assessments_count }} Asesi
                            </span>
                        </div>

                        <div>
                            <h3 class="font-bold text-slate-900 text-sm leading-snug">
                                {{ $sch->name ?? $sch->scheme?->name }}
                            </h3>
                            <p class="text-xs text-brand-600 font-medium mt-0.5">
                                {{ $sch->scheme?->name }} ({{ $sch->scheme?->code }})
                            </p>
                        </div>

                        <div class="text-xs text-slate-600 space-y-1.5 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-slate-500">TUK: <strong class="text-slate-800">{{ $sch->tuk_name ?: '-' }}</strong></span>
                                <span>{{ \Carbon\Carbon::parse($sch->date)->isoFormat('D MMM Y') }}</span>
                            </div>
                            <div class="text-[11px] pt-1 border-t border-slate-200/60 text-slate-600">
                                <span class="text-slate-400">Asesor: </span>
                                @if($sch->assessors->isNotEmpty())
                                    <strong class="text-slate-800">{{ $sch->assessors->pluck('full_name')->join(', ') }}</strong>
                                @else
                                    <span class="italic text-slate-400">Belum ditugaskan</span>
                                @endif
                            </div>
                        </div>

                        <div class="pt-1">
                            <a href="{{ route('admin.dokumen-muk.show', $sch) }}"
                               class="w-full px-4 py-2.5 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 min-h-[42px]">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span>Lihat Berkas Dokumen MUK</span>
                                <svg class="w-3.5 h-3.5 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Desktop Table (md+) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-3.5">Kode</th>
                            <th class="px-6 py-3.5">Nama Jadwal & Skema</th>
                            <th class="px-6 py-3.5">TUK & Waktu</th>
                            <th class="px-6 py-3.5">Tim Asesor</th>
                            <th class="px-6 py-3.5 text-center">Peserta</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($schedules as $sch)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-6 py-4 align-top whitespace-nowrap">
                                    <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-md bg-brand-50 text-brand-700 border border-brand-100">
                                        {{ $sch->code }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <div class="font-bold text-slate-900 text-sm">
                                        {{ $sch->name ?? $sch->scheme?->name }}
                                    </div>
                                    <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-1.5 flex-wrap">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-brand-500"></span>
                                        <span>{{ $sch->scheme?->name }}</span>
                                        <span class="text-slate-400">({{ $sch->scheme?->code }})</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <div class="font-semibold text-slate-800">{{ $sch->tuk_name ?: '-' }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        {{ \Carbon\Carbon::parse($sch->date)->isoFormat('D MMMM Y') }}
                                        @if($sch->start_time)
                                            &bull; {{ substr($sch->start_time, 0, 5) }} - {{ substr($sch->end_time, 0, 5) }} WIB
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    @if($sch->assessors->isNotEmpty())
                                        <div class="font-medium text-slate-700">
                                            {{ $sch->assessors->pluck('full_name')->join(', ') }}
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic">Belum ditugaskan</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 align-top text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $sch->assessments_count }} Asesi
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-top text-right whitespace-nowrap">
                                    <a href="{{ route('admin.dokumen-muk.show', $sch) }}"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm hover:shadow transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <span>Dokumen MUK</span>
                                        <svg class="w-3 h-3 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($schedules->hasPages())
                <div class="px-6 py-4 border-t border-slate-200">
                    {{ $schedules->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection