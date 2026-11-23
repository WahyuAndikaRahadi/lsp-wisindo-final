@extends('layouts.app', ['title' => 'FR.AK.02 Rekaman Asesmen Kompetensi - ' . ($schedule->name ?? $schedule->scheme->name)])

@section('content')
<div class="space-y-6">
    {{-- Unified Top Lifecycle Navigation --}}
    @include('asesor.partials.schedule-nav', ['schedule' => $schedule, 'active' => 'ak02'])

    @php
        $isClosedForMe = $schedule->isClosedForAssessor();
    @endphp

    @if($isClosedForMe)
        <div class="p-3.5 bg-emerald-50 border border-emerald-300 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-emerald-900">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span><strong>{{ $schedule->is_closed ? 'Jadwal Telah Ditutup (Read-Only):' : 'Penetapan Asesi Selesai (Read-Only):' }}</strong> Seluruh rekaman dalam mode lihat (read-only).</span>
            </div>
            <span class="font-bold font-mono text-[11px] bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded border border-emerald-300 shrink-0">STATUS: {{ $schedule->is_closed ? 'FINISHED' : 'SELESAI' }}</span>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3 mt-1">
                <span class="font-mono text-xs font-bold px-2.5 py-1 rounded border border-teal-200 bg-teal-50 text-teal-800">
                    FR.AK.02
                </span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">FR.AK.02. Rekaman Asesmen Kompetensi</h1>
            </div>
            <p class="text-xs text-slate-600 mt-1">Penetapan rekomendasi hasil akhir (Kompeten / Belum Kompeten) untuk setiap asesi</p>
        </div>
    </div>

    {{-- Meta Stats Bar --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Daftar Asesi</span>
            <div class="text-2xl font-black text-slate-900 mt-0.5">
                {{ $asesiList->count() }} Orang
                @if($schedule->assessments->count() > $asesiList->count())
                    <span class="text-xs text-slate-400 font-normal">({{ $schedule->assessments->count() }} total jadwal)</span>
                @endif
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Sudah Ditetapkan</span>
            <div class="text-2xl font-black text-emerald-600 mt-0.5">{{ $asesiList->where('is_completed', true)->count() }} Asesi</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">Belum Direkam</span>
            <div class="text-2xl font-black text-amber-600 mt-0.5">{{ $asesiList->where('is_completed', false)->count() }} Asesi</div>
        </div>
    </div>

    @if($asesiList->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500 text-sm shadow-sm">
            Belum ada asesi terdaftar pada jadwal ini.
        </div>
    @else
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            {{-- Table Header Info --}}
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-base font-black text-slate-900">Daftar Rekaman Asesmen Kompetensi (FR.AK.02)</h2>
                    <p class="text-xs text-slate-500">Pilih asesi untuk menentukan rekomendasi hasil asesmen kompetensi</p>
                </div>
                <div class="text-xs font-bold text-slate-700 bg-white px-3 py-1 rounded border border-slate-300 self-start sm:self-auto">
                    Skema: {{ $schedule->scheme->code ?? '-' }}
                </div>
            </div>

            {{-- Table Container --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="hidden md:table-header-group bg-slate-50 text-left text-xs font-bold text-slate-700 uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3.5 w-16 text-center whitespace-nowrap">No.</th>
                            <th class="px-6 py-3.5 min-w-[220px] whitespace-nowrap">Data Peserta Asesi</th>
                            <th class="px-6 py-3.5 text-center min-w-[180px] whitespace-nowrap">Rekomendasi Hasil Akhir</th>
                            <th class="px-6 py-3.5 text-right w-44 whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($asesiList as $idx => $item)
                            @php
                                $asm = $item->assessment;
                                $cbtResp = $asm->iaResponses->firstWhere('instrument_code', 'IA05');
                                $isK = in_array($asm->final_result, ['KOMPETEN', 'K'], true);
                                $isBk = in_array($asm->final_result, ['BELUM_KOMPETEN', 'BK'], true);
                            @endphp
                            <tr class="flex flex-col md:table-row p-4 md:p-0 hover:bg-slate-50/70 transition space-y-3 md:space-y-0">
                                <td class="hidden md:table-cell px-6 py-4 text-center font-mono font-bold text-xs text-slate-500 whitespace-nowrap">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="px-0 md:px-6 py-0 md:py-4">
                                    <div class="flex items-start justify-between md:justify-start gap-3">
                                        <div class="flex items-start space-x-3">
                                            <div class="w-9 h-9 rounded-full bg-slate-800 text-white font-bold flex items-center justify-center text-xs shrink-0 border border-slate-600 mt-0.5 md:mt-0">
                                                {{ strtoupper(substr($item->asesi->full_name ?? 'A', 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900 text-sm leading-snug">{{ $item->asesi->full_name }}</div>
                                                <div class="text-xs text-slate-500 font-mono mt-0.5">
                                                    NIK: {{ $item->asesi->profile?->nik ?? '-' }}
                                                </div>
                                                <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                                    @if($cbtResp)
                                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded border border-indigo-200 bg-indigo-50 text-indigo-700">
                                                            Skor CBT: {{ $cbtResp->score }}%
                                                        </span>
                                                    @endif
                                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        ✓ Asesi Ditugaskan
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="md:hidden font-mono text-xs font-bold text-slate-400">#{{ $idx + 1 }}</span>
                                    </div>
                                </td>
                                <td class="px-0 md:px-6 py-0 md:py-4 md:text-center whitespace-nowrap">
                                    <div class="flex items-center justify-between md:justify-center">
                                        <span class="md:hidden text-xs font-bold text-slate-500 uppercase tracking-wider">Rekomendasi Hasil Akhir:</span>
                                        @if($asm->apl_status === 'APPROVED')
                                            @if($isK)
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                    <span>Kompeten (K)</span>
                                                </span>
                                            @elseif($isBk)
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                                    <span>Belum Kompeten (BK)</span>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                    <span>Belum Direkam</span>
                                                </span>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                <span>APL Belum Disetujui</span>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-0 md:px-6 py-0 md:py-4 text-left md:text-right whitespace-nowrap pt-1 md:pt-0">
                                    <div class="flex items-center justify-start md:justify-end">
                                        @if($asm->apl_status === 'APPROVED')
                                            <a href="{{ $item->inspect_url }}" 
                                               class="w-full md:w-auto inline-flex items-center justify-center px-4 py-2 min-h-[38px] text-xs font-bold bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white rounded-lg shadow-sm transition gap-1.5">
                                                <span>Tentukan rekomendasi</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </a>
                                        @else
                                            <button type="button" disabled 
                                                    class="w-full md:w-auto inline-flex items-center justify-center px-3 py-2 min-h-[38px] text-xs font-semibold text-slate-400 bg-slate-100 border border-slate-200 rounded-lg cursor-not-allowed">
                                                <span>APL Belum Disetujui</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
