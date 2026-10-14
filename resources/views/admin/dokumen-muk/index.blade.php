@extends('layouts.app', ['title' => 'Dokumen MUK & Berita Acara'])

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Dokumen MUK & Berita Acara</h1>
        <p class="text-sm text-slate-500">Cetak dokumen rekaman MUK, sertifikat, dan berita acara pelaksanaan asesmen</p>
    </div>

    {{-- Schedules List with Accordion --}}
    <div class="space-y-4">
        @forelse($schedules ?? [] as $sch)
            <div x-data="{ open: false }" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                {{-- Schedule Header / Toolbar --}}
                <div class="p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-100">
                    <div class="space-y-1">
                        <div class="flex items-center space-x-2">
                            <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-brand-50 text-brand-700">{{ $sch->code }}</span>
                            <h2 class="text-base font-bold text-slate-900">{{ $sch->name ?? $sch->scheme->name }}</h2>
                        </div>
                        <div class="text-xs text-slate-500 flex items-center space-x-3">
                            <span>TUK: {{ $sch->tuk_name }}</span>
                            <span>&bull;</span>
                            <span>{{ \Carbon\Carbon::parse($sch->date)->isoFormat('D MMMM Y') }}</span>
                            <span>&bull;</span>
                            <span>{{ $sch->assessments->count() }} Asesi Terdaftar</span>
                        </div>
                    </div>

                    {{-- Schedule-level Exports --}}
                    <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                        <a href="{{ route('admin.dokumen-muk.export-sertifikat', $sch->id) }}" class="px-2.5 py-1 text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded border border-emerald-200 transition">
                            Sertifikat
                        </a>
                        <a href="{{ route('admin.dokumen-muk.export-ba', $sch->id) }}" class="px-2.5 py-1 text-xs font-semibold bg-blue-50 text-brand-700 hover:bg-blue-100 rounded border border-blue-200 transition">
                            Berita Acara (BA)
                        </a>
                        <a href="{{ route('admin.dokumen-muk.export-hadir-asesor', $sch->id) }}" class="px-2.5 py-1 text-xs font-semibold bg-purple-50 text-purple-700 hover:bg-purple-100 rounded border border-purple-200 transition">
                            Daftar Hadir Asesor
                        </a>
                        <a href="{{ route('admin.dokumen-muk.export-hadir-asesi', $sch->id) }}" class="px-2.5 py-1 text-xs font-semibold bg-amber-50 text-amber-700 hover:bg-amber-100 rounded border border-amber-200 transition">
                            Daftar Hadir Asesi
                        </a>
                        <a href="{{ route('admin.dokumen-muk.export-excel-biodata', $sch->id) }}" class="px-2.5 py-1 text-xs font-semibold bg-teal-50 text-teal-700 hover:bg-teal-100 rounded border border-teal-200 transition">
                            Excel Data Diri
                        </a>

                        <button type="button" @click="open = !open" class="px-3 py-1 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded transition flex items-center space-x-1">
                            <span x-text="open ? 'Tutup Asesi' : 'Daftar Asesi'"></span>
                            <svg class="w-3.5 h-3.5 transform transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Accordion: Asesi List & Individual PDF Exports --}}
                <div x-show="open" x-cloak class="p-5 bg-slate-50/50">
                    <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-3">Daftar Dokumen MUK Individual Asesi</h3>

                    @if($sch->assessments->isEmpty())
                        <p class="text-xs text-slate-400">Belum ada asesi pada jadwal ini.</p>
                    @else
                        <div class="overflow-x-auto bg-white rounded-lg border border-slate-200">
                            <table class="w-full text-xs">
                                <thead class="bg-slate-50 text-left font-semibold text-slate-600 border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-2.5">No</th>
                                        <th class="px-4 py-2.5">Nama Asesi</th>
                                        <th class="px-4 py-2.5">Status APL</th>
                                        <th class="px-4 py-2.5">Status Asesmen</th>
                                        <th class="px-4 py-2.5 text-right">Download Dokumen PDF Individual</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($sch->assessments as $idx => $assessment)
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="px-4 py-2.5 text-slate-500 font-mono">{{ $idx + 1 }}</td>
                                            <td class="px-4 py-2.5">
                                                <div class="font-bold text-slate-800">{{ $assessment->asesi->full_name }}</div>
                                                <div class="text-[11px] text-slate-400">{{ $assessment->asesi->email }}</div>
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold border bg-slate-50 text-slate-700">
                                                    {{ $assessment->apl_status }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold border bg-slate-50 text-slate-700">
                                                    {{ $assessment->status ?? 'PENDING' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5 text-right">
                                                <div class="flex items-center justify-end space-x-1.5 flex-wrap gap-y-1">
                                                    <a href="{{ route('admin.dokumen-muk.export-individual', ['assessment' => $assessment->id, 'type' => 'apl01']) }}" target="_blank" class="px-2 py-0.5 text-[10px] font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded border border-slate-300">
                                                        APL-01
                                                    </a>
                                                    <a href="{{ route('admin.dokumen-muk.export-individual', ['assessment' => $assessment->id, 'type' => 'apl02']) }}" target="_blank" class="px-2 py-0.5 text-[10px] font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded border border-slate-300">
                                                        APL-02
                                                    </a>
                                                    <a href="{{ route('admin.dokumen-muk.export-individual', ['assessment' => $assessment->id, 'type' => 'mapa']) }}" target="_blank" class="px-2 py-0.5 text-[10px] font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded border border-slate-300">
                                                        MAPA
                                                    </a>
                                                    <a href="{{ route('admin.dokumen-muk.export-individual', ['assessment' => $assessment->id, 'type' => 'ak01']) }}" target="_blank" class="px-2 py-0.5 text-[10px] font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded border border-slate-300">
                                                        AK01
                                                    </a>
                                                    <a href="{{ route('admin.dokumen-muk.export-individual', ['assessment' => $assessment->id, 'type' => 'all']) }}" target="_blank" class="px-2 py-0.5 text-[10px] font-bold bg-brand-600 hover:bg-brand-700 text-white rounded">
                                                        Semua PDF (ZIP/Gabungan)
                                                    </a>
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
        @empty
            <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500 text-sm">
                Belum ada jadwal asesmen tersedia untuk ekspor dokumen MUK.
            </div>
        @endforelse
    </div>
</div>
@endsection
