@extends('layouts.app', ['title' => 'Jadwal Penugasan Asesor'])

@section('content')
@php
    $jadwals = $jadwals ?? $schedules ?? collect();
@endphp
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Jadwal Penugasan</h1>
        <p class="text-sm text-slate-500">Daftar jadwal asesmen yang ditugaskan kepada Anda</p>
    </div>

    @if($jadwals->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 p-8 text-center shadow-sm">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-sm font-bold text-slate-700">Belum Ada Penugasan</h3>
            <p class="text-xs text-slate-500 mt-1">Anda belum ditugaskan pada jadwal asesmen mana pun.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach($jadwals as $jadwal)
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between hover:shadow-md transition">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-1 rounded bg-brand-50 text-brand-700 font-mono text-xs font-bold">
                                {{ $jadwal->code }}
                            </span>
                            @php
                                $statusBadge = match($jadwal->status ?? 'ACTIVE') {
                                    'COMPLETED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'CANCELLED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-blue-50 text-blue-700 border-blue-200',
                                };
                            @endphp
                            <span class="px-2.5 py-0.5 rounded text-xs font-semibold border {{ $statusBadge }}">
                                {{ $jadwal->status ?? 'AKTIF' }}
                            </span>
                        </div>

                        <div>
                            <h3 class="text-base font-bold text-slate-900 leading-snug">
                                {{ $jadwal->name ?? $jadwal->scheme->name }}
                            </h3>
                            <p class="text-xs text-brand-600 font-medium mt-0.5">
                                Skema: {{ $jadwal->scheme->name }} ({{ $jadwal->scheme->code }})
                            </p>
                        </div>

                        <div class="space-y-1.5 text-xs text-slate-600 pt-2 border-t border-slate-100">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <span>TUK: <strong class="text-slate-800">{{ $jadwal->tuk_name }}</strong></span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>{{ \Carbon\Carbon::parse($jadwal->date)->isoFormat('dddd, D MMMM Y') }}</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>{{ substr($jadwal->start_time, 0, 5) }} - {{ substr($jadwal->end_time, 0, 5) }} WIB</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>Jumlah Asesi: <strong class="text-slate-800">{{ $jadwal->assessments_count ?? $jadwal->assessments->count() }}</strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t border-slate-100">
                        <a href="{{ route('asesor.jadwal.asesi', $jadwal->id) }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-bold uppercase tracking-wider bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition">
                            Masuk Asesmen &rarr;
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
