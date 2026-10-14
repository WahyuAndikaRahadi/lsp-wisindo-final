@php
    $jadwal = $jadwal ?? $schedule;
    $totalAsesi = $assessments->count();
    $approvedAsesi = $assessments->where('apl_status', 'APPROVED')->count();
    $allApproved = $totalAsesi > 0 && $approvedAsesi === $totalAsesi;
@endphp
@extends('layouts.app', ['title' => 'Daftar Asesi - ' . ($jadwal->name ?? $jadwal->scheme->name)])

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('asesor.jadwal.index') }}" class="text-xs text-brand-600 hover:text-brand-700 font-semibold">&larr; Kembali ke Jadwal</a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">{{ $jadwal->name ?? $jadwal->scheme->name }}</h1>
            <div class="flex items-center space-x-3 text-xs text-slate-500 mt-1">
                <span class="font-mono font-bold text-brand-700 bg-brand-50 px-2 py-0.5 rounded">{{ $jadwal->code }}</span>
                <span>{{ \Carbon\Carbon::parse($jadwal->date)->isoFormat('D MMMM Y') }}</span>
                <span>{{ substr($jadwal->start_time, 0, 5) }} - {{ substr($jadwal->end_time, 0, 5) }} WIB</span>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            @if($allApproved)
                <a href="{{ route('asesor.ak07.create', $jadwal->id) }}" class="inline-flex items-center px-4 py-2 text-xs font-black uppercase tracking-wider bg-brand-600 hover:bg-brand-700 text-white rounded-lg border-2 border-slate-900 shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] transition">
                    Lanjut ke FR.AK07 &rarr;
                </a>
            @else
                <div class="inline-flex items-center px-3.5 py-2 text-xs font-bold text-amber-800 bg-amber-50 border-2 border-amber-300 rounded-lg shadow-sm">
                    <span class="mr-2 inline-block w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>Verifikasi APL Berjalan ({{ $approvedAsesi }}/{{ $totalAsesi }} Disetujui)</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Asesi Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-base font-bold text-slate-900">Daftar Asesi ({{ $assessments->count() }})</h2>
            <p class="text-xs text-slate-500">Kelola proses asesmen untuk setiap peserta</p>
        </div>

        @if($assessments->isEmpty())
            <div class="p-8 text-center">
                <p class="text-sm text-slate-500">Belum ada asesi terdaftar pada jadwal ini.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">No</th>
                            <th class="px-6 py-3">Nama Asesi</th>
                            <th class="px-6 py-3">Status APL</th>
                            <th class="px-6 py-3">Status Asesmen</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($assessments as $i => $assessment)
                            @php
                                $aplBadge = match($assessment->apl_status) {
                                    'APPROVED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'SUBMITTED' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'REVISION_REQUESTED' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'REJECTED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-slate-50 text-slate-600 border-slate-200',
                                };
                                $statusBadge = match($assessment->status ?? 'PENDING') {
                                    'COMPLETED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'IN_PROGRESS' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    default => 'bg-slate-50 text-slate-600 border-slate-200',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-4 text-slate-500 font-mono text-xs">{{ $i + 1 }}</td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900">{{ $assessment->asesi->full_name }}</div>
                                    <div class="text-xs text-slate-500">{{ $assessment->asesi->email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold border {{ $aplBadge }}">
                                        {{ str_replace('_', ' ', $assessment->apl_status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold border {{ $statusBadge }}">
                                        {{ str_replace('_', ' ', $assessment->status ?? 'PENDING') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end space-x-2 flex-wrap gap-y-1">
                                        @if(in_array($assessment->apl_status, ['SUBMITTED', 'REVISION_REQUESTED']))
                                            <a href="{{ route('asesor.review-apl', $assessment->id) }}" class="px-3 py-1.5 text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white rounded-lg border border-black shadow-sm transition">
                                                Review APL
                                            </a>
                                        @elseif($assessment->apl_status === 'APPROVED')
                                            <span class="px-2.5 py-1 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-300 rounded">
                                                ✓ Siap Diuji
                                            </span>
                                        @elseif($assessment->apl_status === 'REJECTED')
                                            <span class="px-2.5 py-1 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-300 rounded">
                                                Ditolak
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 text-xs font-medium text-slate-500 bg-slate-50 border border-slate-200 rounded">
                                                Draft
                                            </span>
                                        @endif
                                    </div>
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
