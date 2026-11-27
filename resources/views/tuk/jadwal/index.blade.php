@extends('layouts.app', ['title' => 'Jadwal Asesmen TUK - LSP Wisindo'])

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                Jadwal Asesmen Jarak Jauh (AJJ) - {{ $tuk->name }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Daftar jadwal sesi asesmen virtual, tautan meeting Zoom / Google Meet, dan peserta asesi terdaftar.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                TUK Terverifikasi
            </span>
        </div>
    </div>

    @if($schedules->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm space-y-3">
            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-2xl">
                📅
            </div>
            <h3 class="text-sm font-bold text-slate-700">Belum Ada Jadwal Asesmen</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">
                Admin LSP belum menugaskan sesi jadwal asesmen jarak jauh ke TUK Anda. Jadwal akan otomatis muncul di sini begitu dibuat oleh Admin LSP.
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($schedules as $schedule)
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow transition flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-2 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs font-black text-brand-700 bg-brand-50 px-2.5 py-0.5 rounded border border-brand-200">
                                {{ $schedule->code }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $schedule->status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                {{ $schedule->status }}
                            </span>
                            <span class="text-sm font-bold text-slate-900">{{ $schedule->name }}</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600">
                            <div>
                                Skema: <strong class="text-slate-800">{{ $schedule->scheme?->name }}</strong>
                            </div>
                            <div>
                                Tanggal: <strong>{{ \Carbon\Carbon::parse($schedule->date)->isoFormat('D MMMM Y') }}</strong>
                                ({{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }} WIB)
                            </div>
                            <div>
                                Asesor Penugasan: <strong>{{ $schedule->assessors->pluck('full_name')->join(', ') ?: '-' }}</strong>
                            </div>
                            <div>
                                Total Asesi Terdaftar: <strong class="text-brand-700 font-bold">{{ $schedule->assessments->count() }} orang</strong>
                            </div>
                        </div>
                    </div>

                    {{-- Actions: Zoom Button & Detail --}}
                    <div class="flex flex-wrap items-center gap-2.5 shrink-0 border-t md:border-t-0 pt-3 md:pt-0">
                        @if(!empty($schedule->meeting_link))
                            <a href="{{ $schedule->meeting_link }}" target="_blank" rel="noopener noreferrer"
                               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition hover:shadow">
                                <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <span>Buka Ruang Zoom / Meet ↗</span>
                            </a>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 text-slate-400 rounded-xl text-xs font-medium border border-slate-200 italic">
                                Link meeting belum disematkan
                            </span>
                        @endif

                        <a href="{{ route('tuk.jadwal.show', $schedule) }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl transition">
                            <span>Detail &amp; Asesi</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            @endforeach

            <div class="pt-4">
                {{ $schedules->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
