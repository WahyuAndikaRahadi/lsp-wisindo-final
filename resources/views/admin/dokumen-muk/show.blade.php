@extends('layouts.app', ['title' => 'Dokumen MUK: ' . ($schedule->name ?? $schedule->scheme?->name)])

@section('content')
<div class="space-y-6">
    {{-- Header & Breadcrumbs --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('admin.dokumen-muk.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-brand-600 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Kembali ke Daftar Jadwal</span>
                </a>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <span>Dokumen MUK & Berita Acara</span>
                <span class="font-mono text-xs font-bold px-2.5 py-1 rounded bg-brand-50 text-brand-700 border border-brand-100">
                    {{ $schedule->code }}
                </span>
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
                {{ $schedule->name ?? $schedule->scheme?->name }} &bull; {{ $schedule->scheme?->name }}
            </p>
        </div>
    </div>

    {{-- Schedule Details Card --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Informasi Jadwal Asesmen</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block mb-0.5">Skema Sertifikasi:</span>
                <span class="font-bold text-slate-800">{{ $schedule->scheme?->name ?? '-' }}</span>
                <span class="text-slate-500 block text-[11px]">Kode: {{ $schedule->scheme?->code ?? '-' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">TUK & Waktu Pelaksanaan:</span>
                <span class="font-bold text-slate-800">{{ $schedule->tuk_name ?: '-' }}</span>
                <span class="text-slate-500 block text-[11px]">
                    {{ \Carbon\Carbon::parse($schedule->date)->isoFormat('D MMMM Y') }}
                    @if($schedule->start_time)
                        ({{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }} WIB)
                    @endif
                </span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Tim Asesor:</span>
                @if($schedule->assessors->isNotEmpty())
                    <span class="font-bold text-slate-800">{{ $schedule->assessors->pluck('full_name')->join(', ') }}</span>
                @else
                    <span class="text-slate-400 italic">Belum ditugaskan</span>
                @endif
                @if($schedule->pic_name)
                    <span class="text-slate-500 block text-[11px]">Penanggung Jawab: {{ $schedule->pic_name }}</span>
                @endif
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Total Asesi:</span>
                <span class="font-bold text-slate-800 text-sm">{{ $schedule->assessments()->count() }} Peserta</span>
                <span class="text-slate-500 block text-[11px]">Status: {{ $schedule->status ?? 'ACTIVE' }}</span>
            </div>
        </div>
    </div>

    {{-- Schedule-level Exports Section --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Ekspor Dokumen Jadwal Asesmen</h2>
            <p class="text-xs text-slate-500">Cetak berita acara, daftar hadir, laporan asesmen, dan perangkat instrumen MUK</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Berita Acara & Kehadiran --}}
            <a href="{{ route('admin.dokumen-muk.dynamic', ['schedule' => $schedule->id, 'type' => 'ba']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Berita Acara (BA)</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.dynamic', ['schedule' => $schedule->id, 'type' => 'hadir-asesor']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-purple-50 text-purple-700 hover:bg-purple-100 rounded-lg border border-purple-200 transition">
                <span>Hadir Asesor</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.dynamic', ['schedule' => $schedule->id, 'type' => 'ba']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-amber-50 text-amber-700 hover:bg-amber-100 rounded-lg border border-amber-200 transition">
                <span>Hadir Asesi</span>
            </a>

            {{-- Laporan & Tinjauan --}}
            <a href="{{ route('admin.dokumen-muk.export', ['schedule' => $schedule->id, 'type' => 'ak05']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-lg border border-rose-200 transition">
                <span>FR.AK.05 (Laporan)</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.export', ['schedule' => $schedule->id, 'type' => 'ak06']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg border border-indigo-200 transition">
                <span>FR.AK.06 (Tinjauan)</span>
            </a>

            {{-- Fixed Master PDF --}}
            <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'cover']) }}" target="_blank"
               class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg border border-slate-300 transition" title="Fixed Master PDF: Cover MUK">
                <span>Cover</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'daftar-periksa']) }}" target="_blank"
               class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg border border-slate-300 transition" title="Fixed Master PDF: Daftar Periksa">
                <span>Periksa</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'mapa01']) }}" target="_blank"
               class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition" title="Fixed Master PDF: FR.MAPA.01">
                <span>MAPA.01</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'mapa02']) }}" target="_blank"
               class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition" title="Fixed Master PDF: FR.MAPA.02">
                <span>MAPA.02</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'ia02']) }}" target="_blank"
               class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg border border-indigo-200 transition" title="Fixed Master PDF: FR.IA.02 Tugas Praktik Demonstrasi">
                <span>IA.02</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'ia05b']) }}" target="_blank"
               class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold bg-cyan-50 text-cyan-700 hover:bg-cyan-100 rounded-lg border border-cyan-200 transition" title="Fixed Master PDF: FR.IA.05.B Kunci Jawaban">
                <span>IA.05.B Kunci</span>
            </a>

            {{-- Sertifikat & Excel --}}
            <a href="{{ route('admin.dokumen-muk.export-sertifikat', $schedule->id) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition">
                <span>Sertifikat</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.export-excel-biodata', $schedule->id) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-teal-50 text-teal-700 hover:bg-teal-100 rounded-lg border border-teal-200 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Excel Biodata</span>
            </a>
        </div>
    </div>

    {{-- Assessees Section --}}
    <div class="space-y-4">
        {{-- Search Assessees Bar --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <form action="{{ route('admin.dokumen-muk.show', $schedule) }}" method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 text-xs">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari asesi berdasarkan nama, email, atau NIK..."
                        class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border-slate-300 focus:ring-brand-500 focus:border-brand-500 text-slate-900 placeholder:text-slate-400">
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg transition text-xs shadow-sm flex items-center justify-center gap-1.5 whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Cari Asesi</span>
                    </button>

                    @if(!empty($search))
                        <a href="{{ route('admin.dokumen-muk.show', $schedule) }}" class="px-3 py-2 text-slate-600 hover:bg-slate-100 rounded-lg transition text-xs border border-slate-200 whitespace-nowrap">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Assessees Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">
                    Daftar Dokumen MUK Individual Asesi ({{ $assessments->total() }})
                </h3>
                @if(!empty($search))
                    <span class="text-xs text-slate-500">Hasil pencarian: <strong class="text-slate-800">"{{ $search }}"</strong></span>
                @endif
            </div>

            @if($assessments->isEmpty())
                <div class="p-10 text-center">
                    <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <div class="text-slate-600 font-semibold text-sm">Tidak ada asesi ditemukan</div>
                    <p class="text-xs text-slate-400 mt-1">
                        @if(!empty($search))
                            Tidak ada asesi yang cocok dengan kata kunci "{{ $search }}".
                        @else
                            Belum ada asesi yang terdaftar pada jadwal ini.
                        @endif
                    </p>
                    @if(!empty($search))
                        <a href="{{ route('admin.dokumen-muk.show', $schedule) }}" class="inline-block mt-3 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition">
                            Reset Pencarian
                        </a>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-left font-semibold text-slate-600 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3">No</th>
                                <th class="px-4 py-3">Nama Asesi</th>
                                <th class="px-4 py-3">Asesor</th>
                                <th class="px-4 py-3">Hasil Akhir</th>
                                <th class="px-4 py-3 text-right">Cetak Dokumen MUK Individual</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($assessments as $idx => $assessment)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 text-slate-500 font-mono">
                                        {{ $assessments->firstItem() ? $assessments->firstItem() + $idx : $idx + 1 }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-800 text-xs">{{ $assessment->asesi?->full_name }}</div>
                                        <div class="text-[11px] text-slate-400">
                                            {{ $assessment->asesi?->email }} &bull; NIK: {{ $assessment->asesi?->profile?->nik ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $assessment->assessor?->full_name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if(in_array($assessment->final_result, ['K', 'KOMPETEN'], true))
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">KOMPETEN (K)</span>
                                        @elseif(in_array($assessment->final_result, ['BK', 'BELUM_KOMPETEN'], true))
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">BELUM KOMPETEN (BK)</span>
                                        @elseif($assessment->final_result === 'TIDAK_HADIR')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700 border border-slate-300">TIDAK HADIR</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">{{ $assessment->status ?? 'PENDING' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end space-x-1 flex-wrap gap-y-1">
                                            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'apl01']) }}" target="_blank" class="px-1.5 py-0.5 text-[10px] font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded border border-slate-300" title="FR.APL.01 Permohonan Sertifikasi">
                                                APL.01
                                            </a>
                                            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'apl02']) }}" target="_blank" class="px-1.5 py-0.5 text-[10px] font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded border border-slate-300" title="FR.APL.02 Asesmen Mandiri">
                                                APL.02
                                            </a>
                                            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ak01']) }}" target="_blank" class="px-1.5 py-0.5 text-[10px] font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded border border-slate-300" title="FR.AK.01 Persetujuan Asesmen">
                                                AK.01
                                            </a>
                                            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ak07']) }}" target="_blank" class="px-1.5 py-0.5 text-[10px] font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 rounded border border-slate-300" title="FR.AK.07 Penyesuaian Yang Wajar">
                                                AK.07
                                            </a>
                                            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ia01']) }}" target="_blank" class="px-1.5 py-0.5 text-[10px] font-medium bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded border border-indigo-200" title="FR.IA.01 Ceklis Observasi">
                                                IA.01
                                            </a>
                                            @php
                                                $ia01bResp = $assessment->iaResponses?->first(fn($r) => strtoupper((string)$r->instrument_code) === 'IA01B');
                                                $ia01bData = is_array($ia01bResp?->data_json) ? $ia01bResp->data_json : (json_decode($ia01bResp?->data_json ?? '[]', true) ?: []);
                                                $hasIa01b = !empty($ia01bData['evidence_image_path'] ?? $ia01bData['file_path'] ?? $ia01bData['uploaded_file'] ?? null);
                                            @endphp
                                            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ia01b']) }}" target="_blank" class="px-1.5 py-0.5 text-[10px] font-medium rounded border {{ $hasIa01b ? 'bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border-indigo-200' : 'bg-slate-100 hover:bg-slate-200 text-slate-400 border-slate-200' }}" title="{{ $hasIa01b ? 'FR.IA.01B Report Sheet (Sudah Diunggah)' : 'FR.IA.01B Report Sheet (Belum Diunggah Asesi)' }}">
                                                IA.01B
                                            </a>
                                            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ia02']) }}" target="_blank" class="px-1.5 py-0.5 text-[10px] font-medium bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded border border-indigo-200" title="FR.IA.02 Tugas Praktik Demonstrasi">
                                                IA.02
                                            </a>
                                            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ia03']) }}" target="_blank" class="px-1.5 py-0.5 text-[10px] font-medium bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded border border-indigo-200" title="FR.IA.03 Pertanyaan Lisan">
                                                IA.03
                                            </a>
                                            <a href="{{ route('admin.dokumen-muk.asesi-fixed', ['assessment' => $assessment->id, 'type' => 'ia05a']) }}" target="_blank" class="px-1.5 py-0.5 text-[10px] font-medium bg-cyan-50 hover:bg-cyan-100 text-cyan-700 rounded border border-cyan-200" title="Fixed Master PDF: FR.IA.05.A Lembar Soal">
                                                IA.05A
                                            </a>
                                            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ia05c']) }}" target="_blank" class="px-1.5 py-0.5 text-[10px] font-medium bg-cyan-50 hover:bg-cyan-100 text-cyan-700 rounded border border-cyan-200" title="FR.IA.05.C LJK Asesi">
                                                IA.05C
                                            </a>
                                            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ak02']) }}" target="_blank" class="px-2 py-0.5 text-[10px] font-bold bg-brand-50 hover:bg-brand-100 text-brand-700 rounded border border-brand-200" title="FR.AK.02 Rekaman Asesmen">
                                                AK.02
                                            </a>
                                            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ak03']) }}" target="_blank" class="px-2 py-0.5 text-[10px] font-medium bg-amber-50 hover:bg-amber-100 text-amber-700 rounded border border-amber-200" title="FR.AK.03 Umpan Balik">
                                                AK.03
                                            </a>
                                            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ak04']) }}" target="_blank" class="px-2 py-0.5 text-[10px] font-medium bg-purple-50 hover:bg-purple-100 text-purple-700 rounded border border-purple-200" title="FR.AK.04 Banding">
                                                AK.04
                                            </a>
                                            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'pernyataan']) }}" target="_blank" class="px-2 py-0.5 text-[10px] font-medium bg-teal-50 hover:bg-teal-100 text-teal-700 rounded border border-teal-200" title="Surat Pernyataan">
                                                Pernyataan
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($assessments->hasPages())
                    <div class="px-4 py-3 border-t border-slate-200">
                        {{ $assessments->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection