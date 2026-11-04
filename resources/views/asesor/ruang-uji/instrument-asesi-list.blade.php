@extends('layouts.app', ['title' => $meta['code'] . ' - ' . $meta['title']])

@section('content')
<div class="space-y-6">
    {{-- Unified Top Lifecycle Navigation --}}
    @include('asesor.partials.schedule-nav', ['schedule' => $schedule, 'active' => $meta['route_code'] === 'apl' ? 'apl' : 'ruang-uji'])

    @if($schedule->is_closed)
        <div class="p-3.5 bg-emerald-50 border border-emerald-300 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-emerald-900">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span><strong>Jadwal Telah Resmi Ditutup & Dikunci:</strong> Seluruh berkas dan instrumen dalam mode lihat (read-only). Anda tetap dapat memeriksa lembar kerja dan rekaman penilaian peserta.</span>
            </div>
            <span class="font-bold font-mono text-[11px] bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded border border-emerald-300 shrink-0">STATUS: FINISHED</span>
        </div>
    @endif

    {{-- Header & Back Navigation --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3 mt-1">
                <span class="font-mono text-xs font-black px-2.5 py-1 rounded border-2 border-black {{ $meta['badge_color'] }}">
                    {{ $meta['code'] }}
                </span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ $meta['title'] }}</h1>
            </div>
            <p class="text-xs text-slate-600 mt-1">{{ $meta['desc'] }}</p>
        </div>

        <div class="flex items-center space-x-2">
            @if($meta['route_code'] !== 'apl')
                <a href="{{ route('asesor.schedule.ruang-uji', $schedule->id) }}" class="px-4 py-2 text-xs font-semibold bg-white text-slate-800 border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 transition">
                    Kembali ke Ruang Uji
                </a>
            @endif
        </div>
    </div>

    {{-- Meta Stats Bar --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Peserta Asesi</span>
            <div class="text-2xl font-black text-slate-900 mt-0.5">{{ $asesiList->count() }} Orang</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Sudah Dinilai / Selesai</span>
            <div class="text-2xl font-black text-emerald-600 mt-0.5">{{ $asesiList->where('is_completed', true)->count() }} Asesi</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">Belum Dinilai</span>
            <div class="text-2xl font-black text-amber-600 mt-0.5">{{ $asesiList->where('is_completed', false)->count() }} Asesi</div>
        </div>
    </div>

    {{-- Asesi List Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-base font-black text-slate-900">Daftar Asesi untuk {{ $meta['code'] }}</h2>
                <p class="text-xs text-slate-500">Pilih asesi untuk memeriksa lembar hasil kerja dan memberikan penilaian</p>
            </div>
            <div class="text-xs font-bold text-slate-700 bg-white px-3 py-1 rounded border border-slate-300">
                Skema: {{ $schedule->scheme->code }}
            </div>
        </div>

        @if($asesiList->isEmpty())
            <div class="p-8 text-center text-slate-500 text-sm">
                Belum ada asesi terdaftar pada jadwal ini.
            </div>
        @else
            @if($meta['route_code'] === 'apl' && ! $schedule->is_closed)
                <form action="{{ route('asesor.schedule.apl.bulk-review', $schedule->id) }}" method="POST" id="bulkAplForm">
                    @csrf
                    <div class="px-6 py-3 bg-amber-50/80 border-b border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                            <span class="font-bold text-amber-950">Verifikasi Cepat / Bulk Approval APL:</span>
                            <span class="text-amber-800">Centang asesi yang memenuhi syarat untuk langsung disetujui sekaligus.</span>
                        </div>
                        <div class="flex items-center space-x-2 shrink-0">
                            <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menyetujui seluruh APL asesi yang dipilih?')" 
                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold rounded-lg shadow-sm transition inline-flex items-center space-x-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Setujui APL Terpilih (Bulk Approve)</span>
                            </button>
                        </div>
                    </div>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100 text-left text-xs font-bold text-slate-700 uppercase tracking-wider border-b-2 border-black">
                        <tr>
                            @if($meta['route_code'] === 'apl' && ! $schedule->is_closed)
                                <th class="px-4 py-3 w-10 text-center">
                                    <input type="checkbox" id="checkAllApl" checked class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4 cursor-pointer" title="Pilih Semua">
                                </th>
                            @endif
                            <th class="px-6 py-3 w-16 text-center">No</th>
                            <th class="px-6 py-3">Data Peserta Asesi</th>
                            @if($meta['route_code'] !== 'apl')
                                <th class="px-6 py-3 text-center">Status APL</th>
                            @endif
                            <th class="px-6 py-3 text-center">{{ $meta['route_code'] === 'apl' ? 'Status Verifikasi APL' : 'Status Penilaian ' . $meta['code'] }}</th>
                            <th class="px-6 py-3 text-right">{{ $meta['route_code'] === 'apl' ? 'Aksi Verifikasi' : 'Aksi Penilaian' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-2 divide-slate-200">
                        @foreach($asesiList as $idx => $item)
                            <tr class="hover:bg-slate-50/80 transition">
                                @if($meta['route_code'] === 'apl' && ! $schedule->is_closed)
                                    <td class="px-4 py-4 text-center">
                                        @if($item->is_my_candidate)
                                            <input type="checkbox" name="assessment_ids[]" value="{{ $item->assessment->id }}"
                                                class="apl-item-checkbox rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4 cursor-pointer"
                                                checked>
                                        @else
                                            <span class="text-slate-300 text-xs">-</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="px-6 py-4 text-center font-mono font-bold text-xs text-slate-600">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-full bg-slate-900 text-white font-bold flex items-center justify-center text-xs shrink-0 border border-black">
                                            {{ strtoupper(substr($item->asesi->full_name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 text-sm">{{ $item->asesi->full_name }}</div>
                                            <div class="text-xs text-slate-500">
                                                {{ $item->asesi->email }}
                                                @if(!empty($item->asesi->profile?->nik))
                                                    &bull; <span class="font-mono">NIK: {{ $item->asesi->profile->nik }}</span>
                                                @endif
                                            </div>
                                            @if($item->is_my_candidate)
                                                <span class="inline-flex items-center text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 mt-1">
                                                    ✓ Plottingan Anda
                                                </span>
                                            @else
                                                <span class="inline-flex items-center text-[10px] font-medium text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200 mt-1">
                                                    Asesor: {{ $item->assessment->assessor?->full_name ?? 'Lain' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                @if($meta['route_code'] !== 'apl')
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2.5 py-1 rounded text-xs font-bold border border-slate-300 {{ $item->assessment->apl_status === 'APPROVED' ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-600' }}">
                                            {{ str_replace('_', ' ', $item->assessment->apl_status) }}
                                        </span>
                                    </td>
                                @endif
                                <td class="px-6 py-4 text-center">
                                    <div class="inline-flex flex-col items-center space-y-1">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $item->status_class }}">
                                            {{ $item->status_label }}
                                        </span>
                                        @if($item->score_label)
                                            <span class="text-[11px] font-bold text-slate-600">
                                                {{ $item->score_label }}
                                            </span>
                                        @endif
                                        @if($meta['code'] === 'IA02')
                                            @if($item->has_evidence)
                                                <span class="text-[10px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-300">
                                                    ✓ Lembar Kertas Ada
                                                </span>
                                            @else
                                                <span class="text-[10px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-300">
                                                    Lembar Kertas Belum Diunggah
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($item->is_my_candidate)
                                        <a href="{{ $item->inspect_url }}"
                                           class="inline-flex items-center space-x-1.5 px-4 py-2 text-xs font-bold {{ $item->is_completed || $schedule->is_closed ? 'bg-slate-900 hover:bg-slate-800 text-white' : 'bg-brand-600 hover:bg-brand-700 text-white' }} rounded-lg shadow-sm transition">
                                            <span>
                                                @if($schedule->is_closed)
                                                    {{ $meta['route_code'] === 'apl' ? 'Lihat Berkas APL' : 'Lihat Penilaian' }}
                                                @elseif($meta['route_code'] === 'apl')
                                                    {{ $item->is_completed ? 'Tinjau Verifikasi APL' : 'Verifikasi APL' }}
                                                @else
                                                    {{ $item->is_completed ? 'Tinjau / Ubah Penilaian' : 'Periksa Hasil Asesi' }}
                                                @endif
                                            </span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    @else
                                        <button type="button" disabled 
                                            class="inline-flex items-center space-x-1.5 px-3.5 py-2 text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200 rounded-lg cursor-not-allowed select-none" 
                                            title="Asesi ini di-plot kepada {{ $item->assessment->assessor?->full_name ?? 'rekan asesor lain' }}. Anda tidak dapat menilai asesi ini.">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            <span>Terkunci (Asesor Lain)</span>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($meta['route_code'] === 'apl' && ! $schedule->is_closed)
                </form>
            @endif
        @endif
    </div>

    @if($meta['route_code'] === 'apl')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const checkAll = document.getElementById('checkAllApl');
                const itemCheckboxes = document.querySelectorAll('.apl-item-checkbox');

                if (checkAll) {
                    checkAll.addEventListener('change', function() {
                        itemCheckboxes.forEach(cb => cb.checked = checkAll.checked);
                    });

                    itemCheckboxes.forEach(cb => {
                        cb.addEventListener('change', function() {
                            const allChecked = Array.from(itemCheckboxes).every(c => c.checked);
                            checkAll.checked = allChecked;
                        });
                    });
                }
            });
        </script>
    @endif
</div>
@endsection
