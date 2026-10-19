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
        <div class="flex items-center space-x-2 flex-wrap gap-y-2">
            @if($allApproved)
                <a href="{{ route('asesor.ak07.create', $jadwal->id) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white rounded-lg shadow-xs transition" title="FR.AK.07 Penyesuaian Wajar">
                    1. FR.AK.07
                </a>
                <a href="{{ route('asesor.schedule.ak01', $jadwal->id) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-xs transition" title="FR.AK.01 Persetujuan Asesmen">
                    2. FR.AK.01
                </a>
                <a href="{{ route('asesor.schedule.ruang-uji', $jadwal->id) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-xs transition">
                    3. Ruang Uji &rarr;
                </a>
                <a href="{{ route('asesor.schedule.reports', $jadwal->id) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg shadow-xs transition" title="FR.AK.05 & FR.AK.06">
                    4. Tutup Jadwal
                </a>
            @else
                <div class="inline-flex items-center px-3.5 py-2 text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200 rounded-lg shadow-xs">
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
                            <th class="px-4 py-3 text-center w-10">No</th>
                            <th class="px-4 py-3">Nama Asesi</th>
                            <th class="px-3 py-3 text-center">Status APL</th>
                            <th class="px-3 py-3 text-center">Hasil Asesmen</th>
                            <th class="px-3 py-3 text-center">Banding (AK.04)</th>
                            <th class="px-4 py-3 text-right">Aksi Asesor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($assessments as $i => $assessment)
                            @php
                                $aplBadge = match($assessment->apl_status ?? 'DRAFT') {
                                    'APPROVED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'SUBMITTED' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'REVISION_REQUESTED' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'REJECTED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-slate-50 text-slate-600 border-slate-200',
                                };
                                $hasilBadge = match($assessment->final_result ?? 'BELUM_SELESAI') {
                                    'KOMPETEN' => 'bg-emerald-100 text-emerald-800 border-emerald-400 font-black',
                                    'BELUM_KOMPETEN' => 'bg-rose-100 text-rose-800 border-rose-400 font-black',
                                    default => 'bg-slate-100 text-slate-600 border-slate-200',
                                };
                                $appeal = $assessment->latestAppeal;
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-4 py-3 text-slate-500 font-mono text-xs text-center">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $assessment->asesi->full_name }}</div>
                                    <div class="text-xs text-slate-500">{{ $assessment->asesi->email }}</div>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-semibold border {{ $aplBadge }}">
                                        {{ str_replace('_', ' ', $assessment->apl_status ?? 'DRAFT') }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="px-2 py-0.5 rounded text-[11px] border {{ $hasilBadge }}">
                                        {{ $assessment->final_result ?? ($assessment->status === 'COMPLETED' ? 'KOMPETEN' : 'BELUM SELESAI') }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if($appeal)
                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold border bg-purple-50 text-purple-700 border-purple-300">
                                            {{ $appeal->status }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-xs">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end space-x-1.5 flex-wrap gap-y-1">
                                        @if(in_array($assessment->apl_status, ['SUBMITTED', 'REVISION_REQUESTED']))
                                            <a href="{{ route('asesor.review-apl', $assessment->id) }}" class="px-2.5 py-1 text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white rounded border border-amber-600 shadow-xs transition">
                                                Review APL
                                            </a>
                                        @elseif($assessment->apl_status === 'APPROVED')
                                            <a href="{{ route('asesor.assessment.ruang-uji', $assessment->id) }}" class="px-2.5 py-1 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded shadow-xs transition">
                                                Ruang Uji
                                            </a>
                                            <a href="{{ route('asesor.assessment.ak02', $assessment->id) }}" class="px-2.5 py-1 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded shadow-xs transition" title="Rekaman Asesmen Kompetensi">
                                                FR.AK.02
                                            </a>
                                        @elseif($assessment->apl_status === 'REJECTED')
                                            <span class="px-2 py-0.5 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-300 rounded">
                                                Ditolak
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 text-xs font-medium text-slate-500 bg-slate-50 border border-slate-200 rounded">
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
