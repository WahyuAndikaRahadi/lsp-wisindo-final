@extends('layouts.app', ['title' => 'Detail Jadwal TUK - LSP Wisindo'])

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6">
    {{-- Breadcrumb / Back --}}
    <div>
        <a href="{{ route('tuk.jadwal.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 hover:text-brand-800 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Kembali ke Daftar Jadwal TUK</span>
        </a>
    </div>

    {{-- Schedule Card Header --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-mono text-xs font-black text-brand-700 bg-brand-50 px-2.5 py-0.5 rounded border border-brand-200">
                        {{ $schedule->code }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $schedule->status === 'ACTIVE' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                        {{ $schedule->status }}
                    </span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                    {{ $schedule->name }}
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Skema Sertifikasi: <strong class="text-slate-800">{{ $schedule->scheme?->name }}</strong>
                </p>
            </div>

            @if(!empty($schedule->meeting_link))
                <a href="{{ $schedule->meeting_link }}" target="_blank"
                   class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs sm:text-sm rounded-xl shadow-sm transition hover:shadow shrink-0">
                    <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    <span>Masuk Ruang Zoom / Meet AJJ ↗</span>
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Waktu Pelaksanaan</span>
                <p class="font-bold text-slate-800">{{ \Carbon\Carbon::parse($schedule->date)->isoFormat('dddd, D MMMM Y') }}</p>
                <p class="text-slate-600 mt-0.5">{{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }} WIB</p>
            </div>

            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Asesor Penugasan</span>
                <p class="font-bold text-slate-800">{{ $schedule->assessors->pluck('full_name')->join(', ') ?: '-' }}</p>
                <p class="text-slate-500 mt-0.5">Asesor Penguji LSP Wisindo</p>
            </div>

            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Tautan Virtual AJJ</span>
                @if(!empty($schedule->meeting_link))
                    <a href="{{ $schedule->meeting_link }}" target="_blank" class="font-bold text-blue-600 hover:underline truncate block">
                        {{ $schedule->meeting_link }}
                    </a>
                    <span class="text-[10px] text-emerald-600 font-semibold block mt-0.5">✓ Tautan Siap Digunakan</span>
                @else
                    <span class="text-slate-400 italic">Belum diinput oleh admin</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Candidates Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm sm:text-base font-bold text-slate-900">Daftar Asesi / Peserta Uji Terdaftar</h2>
                <p class="text-xs text-slate-500 mt-0.5">Total: {{ $schedule->assessments->count() }} orang peserta</p>
            </div>
        </div>

        @if($schedule->assessments->isEmpty())
            <div class="p-8 text-center text-slate-500 text-xs">
                Belum ada asesi yang terdaftar pada jadwal ini.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-center w-12">No</th>
                            <th class="px-4 py-3">Nama Asesi</th>
                            <th class="px-4 py-3">Email &amp; Kontak</th>
                            <th class="px-4 py-3">Status APL</th>
                            <th class="px-4 py-3">Status Asesmen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($schedule->assessments as $idx => $assessment)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-4 py-3.5 text-center font-bold text-slate-400">{{ $idx + 1 }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="font-bold text-slate-900 block text-xs sm:text-sm">{{ $assessment->asesi?->full_name }}</span>
                                    <span class="text-[11px] text-slate-400 font-mono">NIK: {{ $assessment->asesi?->profile?->nik ?: '-' }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-slate-600">
                                    <div>{{ $assessment->asesi?->email }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $assessment->asesi?->phone ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $assessment->apl_status === 'APPROVED' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $assessment->apl_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $assessment->status === 'COMPLETED' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ $assessment->status }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
