@extends('layouts.app', ['title' => 'Umpan & Banding: ' . ($schedule->name ?? $schedule->scheme?->name)])

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ 
    activeTab: '{{ $activeTab ?? 'feedbacks' }}',
    detailModalOpen: false,
    selectedAsesi: null
}">
    {{-- Top Navigation & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/80">
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                <a href="{{ route('admin.appeals.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-brand-600 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Kembali ke Daftar Jadwal</span>
                </a>
                <span class="text-slate-300">&bull;</span>
                <span class="text-xs text-slate-400">Pusat Umpan &amp; Banding Asesi</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <span>{{ $schedule->name ?? $schedule->scheme?->name }}</span>
                <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-md bg-brand-50 text-brand-700 border border-brand-100">
                    {{ $schedule->code }}
                </span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                {{ $schedule->scheme?->name }} ({{ $schedule->scheme?->code }}) &bull; TUK: <strong>{{ $schedule->tuk_name ?: '-' }}</strong>
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200 shadow-2xs">
                <span>Status Jadwal: <strong>{{ $schedule->is_closed ? 'SELESAI (DITUTUP)' : ($schedule->status ?? 'ACTIVE') }}</strong></span>
            </span>
        </div>
    </div>

    {{-- Schedule Details & Overview Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Informasi Jadwal &amp; Partisipasi Asesi</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block mb-0.5">Skema Sertifikasi:</span>
                <span class="font-bold text-slate-800 text-sm block leading-snug">{{ $schedule->scheme?->name ?? '-' }}</span>
                <span class="text-slate-500 block text-[11px] mt-0.5">Kode: {{ $schedule->scheme?->code ?? '-' }}</span>
            </div>

            <div>
                <span class="text-slate-400 block mb-0.5">TUK &amp; Tanggal Uji:</span>
                <span class="font-bold text-slate-800 text-sm block leading-snug">{{ $schedule->tuk_name ?: '-' }}</span>
                <span class="text-slate-500 block text-[11px] mt-0.5">
                    {{ \Carbon\Carbon::parse($schedule->date)->isoFormat('D MMMM Y') }}
                    @if($schedule->start_time)
                        ({{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }} WIB)
                    @endif
                </span>
            </div>

            {{-- Metric: Umpan Balik (FR.AK.03) --}}
            <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-100">
                <span class="text-slate-500 block text-[11px] font-semibold mb-1">Umpan Balik (FR.AK.03):</span>
                @php
                    $pct = $schedule->assessments_count > 0 ? round(($schedule->ak03_items_count / $schedule->assessments_count) * 100) : 0;
                @endphp
                <div class="flex items-center justify-between gap-2">
                    <span class="text-base font-black text-slate-900">
                        {{ $schedule->ak03_items_count }}/{{ $schedule->assessments_count }} Asesi
                    </span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $pct >= 100 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $pct }}%
                    </span>
                </div>
                <div class="w-full bg-slate-200 h-1.5 rounded-full mt-2 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-300 {{ $pct >= 100 ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $pct }}%"></div>
                </div>
            </div>

            {{-- Metric: Banding (FR.AK.04) --}}
            <div class="bg-slate-50/80 p-3.5 rounded-xl border border-slate-100">
                <span class="text-slate-500 block text-[11px] font-semibold mb-1">Pengajuan Banding (FR.AK.04):</span>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-base font-black text-slate-900">
                        {{ $schedule->appeals_count }} Pengajuan
                    </span>
                    @if($schedule->pending_appeals_count > 0)
                        <span class="text-xs font-black px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 border border-rose-300 animate-pulse">
                            {{ $schedule->pending_appeals_count }} Perlu Putusan
                        </span>
                    @else
                        <span class="text-xs font-medium text-slate-400">
                            Nihil Tertunda
                        </span>
                    @endif
                </div>
                <span class="text-[11px] text-slate-400 block mt-1">Tim Asesor: {{ $schedule->assessors->pluck('full_name')->join(', ') ?: '-' }}</span>
            </div>
        </div>
    </div>

    {{-- Tabs Switcher --}}
    <div class="flex items-center gap-2 border-b border-slate-200 overflow-x-auto">
        <button type="button" 
                @click="activeTab = 'feedbacks'"
                :class="activeTab === 'feedbacks' ? 'border-brand-600 text-brand-700 font-extrabold' : 'border-transparent text-slate-500 hover:text-slate-800 font-medium'"
                class="py-3 px-4 border-b-2 text-xs sm:text-sm flex items-center gap-2 transition whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            <span>Umpan Balik &amp; Evaluasi Asesi (FR.AK.03)</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700">
                {{ $schedule->ak03_items_count }}/{{ $schedule->assessments_count }} Asesi
            </span>
        </button>

        <button type="button" 
                @click="activeTab = 'appeals'"
                :class="activeTab === 'appeals' ? 'border-brand-600 text-brand-700 font-extrabold' : 'border-transparent text-slate-500 hover:text-slate-800 font-medium'"
                class="py-3 px-4 border-b-2 text-xs sm:text-sm flex items-center gap-2 transition whitespace-nowrap">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
            <span>Pengajuan Banding Asesmen (FR.AK.04)</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $schedule->pending_appeals_count > 0 ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-slate-100 text-slate-700' }}">
                {{ $schedule->appeals_count }}
                @if($schedule->pending_appeals_count > 0)
                    ({{ $schedule->pending_appeals_count }} Perlu Putusan)
                @endif
            </span>
        </button>
    </div>

    {{-- TAB 1: FR.AK.03 UMPAN BALIK ASESI --}}
    <div x-show="activeTab === 'feedbacks'" class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-slate-50/70">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">
                        Rekapitulasi Formulir FR.AK.03 Asesi
                    </h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Daftar seluruh asesi dalam jadwal ini beserta status dan rincian evaluasi pengalaman asesmen yang diisi.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold px-3 py-1 rounded-full {{ $schedule->ak03_items_count >= $schedule->assessments_count && $schedule->assessments_count > 0 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300' }}">
                        {{ $schedule->ak03_items_count }}/{{ $schedule->assessments_count }} Asesi Mengisi
                    </span>
                </div>
            </div>

            @if($schedule->assessments->isEmpty())
                <div class="p-12 text-center text-slate-400 text-xs">
                    Belum ada asesi yang terdaftar pada jadwal ini.
                </div>
            @else
                {{-- Desktop Table --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 text-slate-500 uppercase tracking-wider text-[11px] border-b border-slate-200">
                                <th class="px-5 py-3 font-bold w-12 text-center">No</th>
                                <th class="px-5 py-3 font-bold">Nama Asesi &amp; Kontak</th>
                                <th class="px-5 py-3 font-bold">Asesor Penguji</th>
                                <th class="px-5 py-3 font-bold text-center">Hasil Asesmen</th>
                                <th class="px-5 py-3 font-bold text-center">Status FR.AK.03</th>
                                <th class="px-5 py-3 font-bold">Catatan Asesi</th>
                                <th class="px-5 py-3 font-bold text-right">Rincian Jawaban</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($schedule->assessments as $idx => $assessment)
                                @php
                                    $ak03 = $assessment->ak03Item;
                                    $ratings = $ak03?->ratings_json ?? [];
                                    $yesCount = 0;
                                    $noCount = 0;
                                    foreach($ratings as $r) {
                                        if(($r['value'] ?? '') === 'YA') $yesCount++;
                                        if(($r['value'] ?? '') === 'TIDAK') $noCount++;
                                    }
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-5 py-3.5 text-center text-slate-400 font-medium">
                                        {{ $idx + 1 }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="font-bold text-slate-900">{{ $assessment->asesi?->full_name ?? '-' }}</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">{{ $assessment->asesi?->email }} &bull; {{ $assessment->asesi?->phone ?: '-' }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-700">
                                        {{ $assessment->assessor?->full_name ?? 'Tim Asesor LSP' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if($assessment->final_result === 'KOMPETEN')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                KOMPETEN (K)
                                            </span>
                                        @elseif($assessment->final_result === 'BELUM_KOMPETEN')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300">
                                                BELUM KOMPETEN (BK)
                                            </span>
                                        @elseif($assessment->final_result === 'TIDAK_HADIR')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                                TIDAK HADIR
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-500">
                                                {{ $assessment->final_result ?: 'BELUM SELESAI' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if($ak03)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                <span>Sudah Mengisi</span>
                                            </span>
                                            <div class="text-[10px] text-slate-400 mt-0.5">
                                                {{ \Carbon\Carbon::parse($ak03->created_at)->isoFormat('D MMM Y, HH:mm') }}
                                            </div>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-500">
                                                Belum Mengisi
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-600 max-w-xs">
                                        @if($ak03 && $ak03->general_notes)
                                            <p class="truncate italic text-[11px]" title="{{ $ak03->general_notes }}">
                                                "{{ $ak03->general_notes }}"
                                            </p>
                                        @elseif($ak03)
                                            <span class="text-slate-400 italic text-[11px]">-</span>
                                        @else
                                            <span class="text-slate-300 text-[11px]">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        @if($ak03)
                                            <button type="button" 
                                                    @click="selectedAsesi = {{ json_encode([
                                                        'name' => $assessment->asesi?->full_name,
                                                        'assessor' => $assessment->assessor?->full_name,
                                                        'date' => \Carbon\Carbon::parse($ak03->created_at)->isoFormat('D MMMM Y, HH:mm'),
                                                        'notes' => $ak03->general_notes,
                                                        'ratings' => $ak03->ratings_json ?? [],
                                                        'yesCount' => $yesCount,
                                                        'noCount' => $noCount
                                                    ]) }}; detailModalOpen = true"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg transition shadow-2xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <span>Lihat ({{ $yesCount }}/10 YA)</span>
                                            </button>
                                        @else
                                            <span class="text-slate-300 text-xs">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Cards --}}
                <div class="block md:hidden divide-y divide-slate-100">
                    @foreach($schedule->assessments as $assessment)
                        @php
                            $ak03 = $assessment->ak03Item;
                            $ratings = $ak03?->ratings_json ?? [];
                            $yesCount = 0;
                            $noCount = 0;
                            foreach($ratings as $r) {
                                if(($r['value'] ?? '') === 'YA') $yesCount++;
                                if(($r['value'] ?? '') === 'TIDAK') $noCount++;
                            }
                        @endphp
                        <div class="p-4 space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm">{{ $assessment->asesi?->full_name ?? '-' }}</h4>
                                    <span class="text-[11px] text-slate-500">{{ $assessment->assessor?->full_name ?? 'Asesor LSP' }}</span>
                                </div>
                                @if($ak03)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        ✓ Sudah Mengisi
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-500">
                                        Belum Mengisi
                                    </span>
                                @endif
                            </div>

                            @if($ak03 && $ak03->general_notes)
                                <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-100 text-[11px] text-slate-600 italic">
                                    "{{ $ak03->general_notes }}"
                                </div>
                            @endif

                            @if($ak03)
                                <button type="button" 
                                        @click="selectedAsesi = {{ json_encode([
                                            'name' => $assessment->asesi?->full_name,
                                            'assessor' => $assessment->assessor?->full_name,
                                            'date' => \Carbon\Carbon::parse($ak03->created_at)->isoFormat('D MMMM Y, HH:mm'),
                                            'notes' => $ak03->general_notes,
                                            'ratings' => $ak03->ratings_json ?? [],
                                            'yesCount' => $yesCount,
                                            'noCount' => $noCount
                                        ]) }}; detailModalOpen = true"
                                        class="w-full flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg transition shadow-2xs">
                                    <span>Lihat Rincian Jawaban 10 Komponen</span>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- TAB 2: FR.AK.04 PENGAJUAN BANDING ASESMEN --}}
    <div x-show="activeTab === 'appeals'" x-cloak class="space-y-4">
        @if($scheduleAppeals->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs space-y-3">
                <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center border border-emerald-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800">Tidak Ada Pengajuan Banding Pada Jadwal Ini</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">
                    Seluruh peserta asesmen menerima hasil keputusan asesmen secara penuh atau belum ada pengajuan sanggahan FR.AK.04.
                </p>
            </div>
        @else
            @include('admin.appeals._appeals_table', ['appeals' => $scheduleAppeals])
        @endif
    </div>

    {{-- MODAL DETAIL 10 KOMPONEN FR.AK.03 ASESI --}}
    <div x-show="detailModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
        <div @click.away="detailModalOpen = false" class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-3xl w-full max-h-[90vh] flex flex-col overflow-hidden my-6">
            {{-- Modal Header --}}
            <div class="p-4 sm:p-5 border-b border-slate-200 bg-slate-50/80 flex items-start justify-between gap-3 shrink-0">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-200">
                            FR.AK.03
                        </span>
                        <span class="text-xs text-slate-400">&bull; Lembar Evaluasi Asesi</span>
                    </div>
                    <h3 class="text-base sm:text-lg font-black text-slate-900 mt-1" x-text="'Umpan Balik: ' + (selectedAsesi ? selectedAsesi.name : '')"></h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        Asesor: <strong x-text="selectedAsesi ? (selectedAsesi.assessor || 'Tim Asesor LSP') : '-'"></strong> &bull;
                        Diisi: <span x-text="selectedAsesi ? selectedAsesi.date : '-'"></span>
                    </p>
                </div>
                <button type="button" @click="detailModalOpen = false" class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-200/60 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-4 sm:p-6 overflow-y-auto space-y-4 text-xs">
                {{-- Score Summary Banner --}}
                <div class="flex items-center justify-between p-3.5 rounded-xl bg-indigo-50/70 border border-indigo-100">
                    <span class="font-bold text-indigo-950">Hasil Evaluasi 10 Komponen:</span>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white font-black text-xs" x-text="(selectedAsesi ? selectedAsesi.yesCount : 0) + ' YA'"></span>
                        <span class="px-2.5 py-1 rounded-lg bg-rose-600 text-white font-black text-xs" x-text="(selectedAsesi ? selectedAsesi.noCount : 0) + ' TIDAK'"></span>
                    </div>
                </div>

                {{-- Table 10 Criteria --}}
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider text-[10px] font-bold border-b border-slate-200">
                                <th class="px-3.5 py-2.5 w-10 text-center">No</th>
                                <th class="px-3.5 py-2.5">Komponen Standar Evaluasi BNSP</th>
                                <th class="px-3.5 py-2.5 text-center w-24">Respon</th>
                                <th class="px-3.5 py-2.5">Catatan Asesi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($ak03Statements as $num => $statement)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-3.5 py-2.5 text-center font-bold text-slate-400">{{ $num }}</td>
                                    <td class="px-3.5 py-2.5 text-slate-800 font-medium leading-relaxed">{{ $statement }}</td>
                                    <td class="px-3.5 py-2.5 text-center">
                                        <template x-if="selectedAsesi && selectedAsesi.ratings && selectedAsesi.ratings['{{ $num }}']">
                                            <span>
                                                <template x-if="selectedAsesi.ratings['{{ $num }}'].value === 'YA'">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">YA</span>
                                                </template>
                                                <template x-if="selectedAsesi.ratings['{{ $num }}'].value === 'TIDAK'">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300">TIDAK</span>
                                                </template>
                                            </span>
                                        </template>
                                        <template x-if="!selectedAsesi || !selectedAsesi.ratings || !selectedAsesi.ratings['{{ $num }}']">
                                            <span class="text-slate-300">-</span>
                                        </template>
                                    </td>
                                    <td class="px-3.5 py-2.5 text-slate-600 italic">
                                        <span x-text="(selectedAsesi && selectedAsesi.ratings && selectedAsesi.ratings['{{ $num }}'] && selectedAsesi.ratings['{{ $num }}'].notes) ? selectedAsesi.ratings['{{ $num }}'].notes : '-'"></span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Catatan Umum / Masukan --}}
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                    <span class="font-bold text-slate-800 block text-[11px]">Catatan / Masukan Tambahan Asesi:</span>
                    <p class="text-slate-700 italic bg-white p-2.5 rounded-lg border border-slate-200" x-text="(selectedAsesi && selectedAsesi.notes) ? selectedAsesi.notes : 'Tidak ada catatan tambahan.'"></p>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="p-4 border-t border-slate-200 bg-slate-50/70 flex items-center justify-end shrink-0">
                <button type="button" @click="detailModalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-100 rounded-xl transition shadow-2xs">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
