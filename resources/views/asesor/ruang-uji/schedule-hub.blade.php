@extends('layouts.app', ['title' => 'Ruang Uji Asesmen - ' . ($schedule->name ?? $schedule->scheme->name)])

@section('content')
<div class="space-y-6">
    {{-- Unified Top Lifecycle Navigation --}}
    @include('asesor.partials.schedule-nav', ['schedule' => $schedule, 'active' => 'ruang-uji'])

    @if($schedule->is_closed)
        <div class="p-4 bg-emerald-50 border border-emerald-300 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-emerald-900">
            <div class="flex items-center space-x-2.5">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <div>
                    <strong class="font-bold block text-sm">Jadwal Telah Resmi Ditutup & Dikunci</strong>
                    <span>Seluruh pengujian dan penilaian telah selesai. Anda dapat memeriksa lembar hasil penilaian seluruh peserta.</span>
                </div>
            </div>
            <span class="font-bold font-mono text-xs bg-emerald-100 text-emerald-800 px-3 py-1 rounded-lg border border-emerald-300 shrink-0">STATUS: FINISHED</span>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ $schedule->name ?? $schedule->scheme->name }}</h1>
            <p class="text-sm text-slate-500">Pilih instrumen asesmen untuk memeriksa dan menilai seluruh peserta uji</p>
        </div>
        <div class="flex items-center space-x-2">
            <span class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                Kode Jadwal: {{ $schedule->code }}
            </span>
        </div>
    </div>

    {{-- Schedule Details Card --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Skema Sertifikasi</span>
                <p class="text-slate-900 font-bold mt-1 text-sm">{{ $schedule->scheme->name }}</p>
                <span class="font-mono text-xs text-brand-700 bg-brand-50 px-2 py-0.5 rounded border border-brand-200 mt-1 inline-block">
                    {{ $schedule->scheme->code }}
                </span>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Tempat Uji Kompetensi (TUK)</span>
                <p class="text-slate-900 font-bold mt-1 text-sm">{{ $schedule->tuk_name }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ $schedule->code }}</p>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Waktu Pelaksanaan</span>
                <p class="text-slate-900 font-bold mt-1 text-sm">{{ \Carbon\Carbon::parse($schedule->date)->isoFormat('dddd, D MMMM Y') }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }} WIB</p>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Peserta Uji (Asesi)</span>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ $totalAsesi }} <span class="text-xs font-semibold text-slate-500">Orang</span></p>
                <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 mt-1 inline-block">
                    Status: {{ $schedule->status }}
                </span>
            </div>
        </div>
    </div>

    {{-- Selected Instruments Heading --}}
    <div>
        <h2 class="text-lg font-black text-slate-900 tracking-tight">Instrumen Asesmen Terpilih</h2>
        <p class="text-xs text-slate-500">Klik instrumen di bawah untuk memeriksa dan memberi penilaian pada daftar asesi</p>
    </div>

    {{-- Grid of Selected Instruments --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($instruments as $inst)
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex flex-col justify-between hover:shadow-md transition">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-xs font-bold px-2.5 py-1 rounded border {{ $inst['badge_color'] }}">
                            {{ $inst['code'] }}
                        </span>
                        <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                            {{ $inst['badge'] }}
                        </span>
                    </div>

                    <div>
                        <h3 class="text-base font-bold text-slate-900 leading-snug">{{ $inst['title'] }}</h3>
                        <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">{{ $inst['desc'] }}</p>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="pt-2">
                        <div class="flex items-center justify-between text-xs font-semibold mb-1">
                            <span class="text-slate-600">Progres Penilaian</span>
                            <span class="text-slate-900 font-bold">{{ $inst['completed_count'] }} / {{ $inst['total_asesi'] }} Asesi</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 border border-slate-200 overflow-hidden">
                            <div class="bg-brand-600 h-2 rounded-full transition-all" style="width: {{ $inst['percentage'] }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="pt-5 mt-4 border-t border-slate-100">
                    <a href="{{ route('asesor.schedule.instrument', ['schedule' => $schedule->id, 'code' => $inst['route_code']]) }}"
                       class="w-full flex items-center justify-center space-x-2 px-4 py-2.5 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition">
                        <span>Buka Penilaian Instrumen</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl border border-slate-200 p-8 text-center shadow-sm">
                <p class="text-sm text-slate-500">Tidak ada instrumen yang terdaftar untuk jadwal ini.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
