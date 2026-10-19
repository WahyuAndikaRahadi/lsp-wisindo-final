@extends('layouts.app')

@section('title', 'Tinjauan Instrumen FR.' . strtoupper($code))

@section('content')
<div class="max-w-4xl mx-auto py-6 space-y-6">
    {{-- Header --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <a href="{{ route('asesi.assessment.ruang-uji', $assessment) }}" class="text-xs text-indigo-600 hover:underline font-medium block mb-1">
                ← Kembali ke Ruang Uji
            </a>
            <h1 class="text-xl font-bold text-slate-800">
                @if(strtoupper($code) === 'IA01')
                    FR.IA.01. CEKLIS OBSERVASI AKTIVITAS PRAKTIK
                @elseif(strtoupper($code) === 'IA01B')
                    FR.IA.01B. LEMBAR PENGUKURAN TEKNIS (REPORT SHEET)
                @elseif(strtoupper($code) === 'IA02')
                    FR.IA.02. TUGAS PRAKTIK DEMONSTRASI
                @elseif(strtoupper($code) === 'IA03')
                    FR.IA.03. PERTANYAAN MENDUKUNG OBSERVASI (PMO)
                @else
                    FR.{{ strtoupper($code) }}
                @endif
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Skema: {{ $assessment->schedule->scheme->name }} | 
                Asesor: {{ $assessment->assessor?->full_name ?? 'Tim Asesor LSP' }}
            </p>
        </div>

        <div>
            @if($response?->asesi_signed_at)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                    ✓ Sudah Ditandatangani
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                    Menunggu Umpan Balik & TTD
                </span>
            @endif
        </div>
    </div>

    {{-- Penilaian oleh Asesor --}}
    @if(strtoupper($code) === 'IA02')
        @php
            $evidencePath = $response?->data_json['evidence_image_path'] ?? null;
            $evidenceTime = $response?->data_json['evidence_uploaded_at'] ?? null;
            $evidenceName = $response?->data_json['evidence_original_name'] ?? 'Lembar_Jawaban_Fisik.jpg';
            $evidenceUrl = ($response && $evidencePath) ? route('secure-file.evidence', $response) : '#';
        @endphp
        {{-- Box Khusus Pengumpulan Lembar Kerja Kertas FR.IA.02 --}}
        <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] overflow-hidden">
            <div class="bg-slate-900 text-white px-6 py-3 border-b-2 border-black flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="text-amber-300 font-bold text-xs uppercase tracking-wider font-mono">FR.IA.02 Paper-Based</span>
                    <span class="text-xs text-slate-300">— Unggah Lembar Jawaban Fisik</span>
                </div>
                @if($evidencePath)
                    <span class="px-2.5 py-0.5 text-[11px] font-bold bg-emerald-500 text-white rounded">
                        ✓ Lembar Kertas Terunggah
                    </span>
                @else
                    <span class="px-2.5 py-0.5 text-[11px] font-bold bg-amber-500 text-slate-900 rounded">
                        Belum Diunggah
                    </span>
                @endif
            </div>

            <div class="p-6 space-y-4">
                {{-- Template Download Card --}}
                <div class="bg-indigo-50 border-2 border-indigo-200 rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-[#1E2B6E] text-white flex items-center justify-center font-bold text-lg shrink-0">
                            📄
                        </div>
                        <div>
                            <div class="text-xs font-bold text-slate-900">Template Lembar Uji Praktik FR.IA.02 (PDF Resmi BNSP)</div>
                            <p class="text-[11px] text-slate-600">Unduh lembar soal & instruksi demonstrasi resmi untuk dicetak atau dibaca di TUK.</p>
                        </div>
                    </div>
                    <a href="{{ route('asesi.assessment.instrument.download-template', [$assessment, 'IA02']) }}" class="px-4 py-2 bg-[#1E2B6E] hover:bg-[#152055] text-white text-xs font-black rounded-lg border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] transition shrink-0 inline-flex items-center gap-2">
                        <span>📥 Unduh Template PDF</span>
                    </a>
                </div>

                <div class="bg-amber-50 border border-amber-300 rounded-lg p-3 text-xs text-amber-900 leading-relaxed">
                    <strong>Petunjuk Uji Praktik:</strong> Instrumen Tugas Praktik Demonstrasi (FR.IA.02) dilaksanakan menggunakan <strong>lembar kerja kertas (paper-based)</strong> di TUK. Selesaikan tugas demonstrasi dan catat hasilnya pada kertas fisik. Foto lembar jawaban Anda dengan pencahayaan yang jelas dan tegak lurus, kemudian unggah pada tombol di bawah agar asesor dapat memeriksa pekerjaan Anda.
                </div>

                {{-- Skenario & Langkah Kerja TPD --}}
                @if(! empty($tpdData))
                    <div class="border border-slate-200 rounded-lg p-4 bg-slate-50 space-y-3 text-xs">
                        <div>
                            <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px] block">Skenario Uji Demonstrasi:</span>
                            <p class="text-slate-700 mt-1 leading-relaxed">{{ $tpdData['scenario'] ?? '-' }}</p>
                        </div>
                        @if(! empty($tpdData['tasks']))
                            <div>
                                <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px] block">Langkah & Instruksi Kerja:</span>
                                <ol class="list-decimal list-inside space-y-1 mt-1 text-slate-700">
                                    @foreach($tpdData['tasks'] as $t)
                                        <li>{{ is_array($t) ? ($t['instruction'] ?? '') : $t }}</li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                    </div>
                @endif

                @if($evidencePath)
                    <div class="border-2 border-slate-200 rounded-xl p-4 bg-slate-50 flex flex-col md:flex-row items-center gap-4">
                        <div class="w-full md:w-48 h-48 bg-slate-200 rounded-lg overflow-hidden border border-slate-300 flex items-center justify-center relative group">
                            @if(str_ends_with(strtolower($evidencePath), '.pdf'))
                                <div class="text-center p-4">
                                    <span class="text-4xl text-rose-600 block mb-1">📄</span>
                                    <span class="text-[11px] font-bold text-slate-700 block truncate">{{ $evidenceName }}</span>
                                </div>
                            @else
                                <img src="{{ $evidenceUrl }}" alt="Bukti Jawaban Kertas" class="w-full h-full object-cover">
                            @endif
                            <a href="{{ $evidenceUrl }}" target="_blank" class="absolute inset-0 bg-black/60 text-white text-xs font-bold flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                🔍 Buka Ukuran Penuh
                            </a>
                        </div>
                        <div class="flex-1 space-y-2 text-xs">
                            <div class="font-bold text-slate-900 text-sm">Foto Lembar Jawaban Kertas Asesi</div>
                            <div class="text-slate-600">
                                Diunggah pada: <span class="font-mono font-semibold">{{ \Carbon\Carbon::parse($evidenceTime)->isoFormat('D MMMM Y, HH:mm') }} WIB</span>
                            </div>
                            <div class="text-slate-500 text-[11px]">
                                File: <span class="font-mono">{{ basename($evidencePath) }}</span>
                            </div>
                            <div class="pt-2 flex items-center gap-2">
                                <a href="{{ $evidenceUrl }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-bold bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition">
                                    Buka Foto di Tab Baru &nearr;
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Form Upload / Ganti Foto --}}
                <form action="{{ route('asesi.assessment.instrument.upload-evidence', [$assessment, strtoupper($code)]) }}" method="POST" enctype="multipart/form-data" class="space-y-3 pt-2">
                    @csrf
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                        {{ $evidencePath ? 'Unggah Ulang / Perbarui Foto Lembar Jawaban:' : 'Pilih Foto Lembar Jawaban Kertas:' }}
                    </label>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                        <input type="file" name="evidence_image" accept="image/*,application/pdf" required
                            class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-2 file:border-black file:text-xs file:font-bold file:bg-white file:text-slate-800 hover:file:bg-slate-100 border border-slate-300 rounded-lg p-1 bg-white cursor-pointer">
                        <button type="submit" class="px-5 py-2.5 text-xs font-black uppercase tracking-wider bg-slate-900 hover:bg-slate-800 text-white rounded-lg border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] transition shrink-0">
                            Unggah Foto Bukti &uarr;
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-500">Format yang didukung: JPG, JPEG, PNG, WEBP, atau scan PDF (Maksimal 10 MB).</p>
                </form>
            </div>
        </div>
    @endif

    {{-- Detail Data Instrumen / Hasil Penilaian --}}
    @if(strtoupper($code) === 'IA01B')
        @php
            $rawItems = $response?->data_json['items'] ?? (is_array($response?->data_json) && isset($response->data_json[0]) ? $response->data_json : []);
            $totalCount = count($rawItems);
            $sesuaiCount = collect($rawItems)->filter(fn($i) => is_array($i) && strtoupper($i['result'] ?? '') === 'SESUAI')->count();
            $tidakCount = collect($rawItems)->filter(fn($i) => is_array($i) && strtoupper($i['result'] ?? '') === 'TIDAK')->count();
            $percent = $totalCount > 0 ? round(($sesuaiCount / $totalCount) * 100) : 0;
        @endphp

        {{-- Download Template FR.IA.01B --}}
        <div class="bg-indigo-50 border-2 border-indigo-200 rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[#1E2B6E] text-white flex items-center justify-center font-bold text-lg shrink-0">
                    📄
                </div>
                <div>
                    <div class="text-xs font-bold text-slate-900">Format Lembar Pemeriksaan FR.IA.01B (PDF Resmi BNSP)</div>
                    <p class="text-[11px] text-slate-600">Unduh lembar kerja teknis spesifikasi Report Sheet resmi untuk skema ini.</p>
                </div>
            </div>
            <a href="{{ route('asesi.assessment.instrument.download-template', [$assessment, 'IA01B']) }}" class="px-4 py-2 bg-[#1E2B6E] hover:bg-[#152055] text-white text-xs font-black rounded-lg border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] transition shrink-0 inline-flex items-center gap-2">
                <span>📥 Unduh Report Sheet PDF</span>
            </a>
        </div>

        {{-- Ringkasan Statistik IA.01B --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white p-4 rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)]">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Item Ukur</div>
                <div class="text-2xl font-black text-slate-900 mt-1 font-mono">{{ $totalCount }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">Pemeriksaan teknis</div>
            </div>
            <div class="bg-emerald-50 p-4 rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)]">
                <div class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">Sesuai Standar</div>
                <div class="text-2xl font-black text-emerald-700 mt-1 font-mono">{{ $sesuaiCount }}</div>
                <div class="text-[10px] text-emerald-600 mt-0.5">Memenuhi toleransi</div>
            </div>
            <div class="bg-rose-50 p-4 rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)]">
                <div class="text-[11px] font-bold text-rose-800 uppercase tracking-wider">Tidak Sesuai</div>
                <div class="text-2xl font-black text-rose-700 mt-1 font-mono">{{ $tidakCount }}</div>
                <div class="text-[10px] text-rose-600 mt-0.5">Di luar toleransi</div>
            </div>
            <div class="bg-indigo-50 p-4 rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)]">
                <div class="text-[11px] font-bold text-indigo-800 uppercase tracking-wider">Tingkat Kesesuaian</div>
                <div class="text-2xl font-black text-indigo-700 mt-1 font-mono">{{ $percent }}%</div>
                <div class="text-[10px] text-indigo-600 mt-0.5">Ketercapaian spesifikasi</div>
            </div>
        </div>

        {{-- Tabel Rinci Pengukuran Teknis FR.IA.01B --}}
        <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] overflow-hidden">
            <div class="bg-slate-900 text-white px-6 py-4 border-b-2 border-black flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <span class="text-amber-300 font-bold text-xs uppercase tracking-wider font-mono">FR.IA.01B REPORT SHEET</span>
                    <h2 class="text-base font-bold text-white">Lembar Pemeriksaan & Hasil Pengukuran Teknis</h2>
                    <p class="text-xs text-slate-300">Hasil pengukuran aktual komponen mesin/otomotif oleh asesi dan verifikasi oleh asesor</p>
                </div>
                <div class="shrink-0">
                    <span class="px-3 py-1 rounded text-xs font-bold font-mono {{ $percent >= 80 ? 'bg-emerald-500 text-white' : 'bg-amber-400 text-slate-900' }}">
                        {{ $percent }}% COMPLIANT
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-100 text-slate-700 uppercase tracking-wider font-bold border-b-2 border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-center w-12">No</th>
                            <th class="px-4 py-3 min-w-[200px]">Item Pemeriksaan / Bagian Diukur</th>
                            <th class="px-4 py-3 w-48">Spesifikasi Standar</th>
                            <th class="px-4 py-3 w-44">Hasil Pengukuran Asesi</th>
                            <th class="px-4 py-3 text-center w-36">Verifikasi Asesor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($rawItems as $idx => $item)
                            @php
                                $itemInfo = $reportSheetItems[$idx] ?? null;
                                $itemTitle = is_array($itemInfo) ? ($itemInfo['item'] ?? "Item #".($idx+1)) : (is_array($item) && isset($item['item']) ? $item['item'] : "Item Pemeriksaan #".($idx+1));
                                $itemStd = is_array($itemInfo) ? ($itemInfo['standard'] ?? 'Workshop Manual') : (is_array($item) && isset($item['standard']) ? $item['standard'] : 'Workshop Manual');
                                $measured = is_array($item) ? ($item['measured'] ?? null) : null;
                                $result = is_array($item) ? strtoupper($item['result'] ?? '') : '';
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-4 py-3 text-center font-mono font-bold text-slate-500">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-800">
                                    {{ $itemTitle }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-0.5 rounded bg-slate-100 border border-slate-200 font-mono text-[11px] text-slate-700">
                                        {{ $itemStd }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if(!empty($measured))
                                        <span class="inline-block px-2.5 py-1 rounded bg-blue-50 border border-blue-200 font-mono text-[11px] font-bold text-blue-900">
                                            {{ $measured }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">(Tidak tercatat)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($result === 'SESUAI')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            <span>✓</span> <span>SESUAI</span>
                                        </span>
                                    @elseif($result === 'TIDAK')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                            <span>✗</span> <span>TIDAK</span>
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-[11px] italic">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500 text-xs">
                                    Belum ada data rekaman item pengukuran FR.IA.01B.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif(strtoupper($code) === 'IA01')
        @php
            $kukData = $response?->data_json['kuks'] ?? [];
            $kCount = collect($kukData)->filter(fn($v) => (is_array($v) ? ($v['result'] ?? '') : $v) === 'K')->count();
            $bkCount = collect($kukData)->filter(fn($v) => (is_array($v) ? ($v['result'] ?? '') : $v) === 'BK')->count();
            $totalKuk = count($kukData);
        @endphp

        {{-- Download Template FR.IA.01 --}}
        <div class="bg-indigo-50 border-2 border-indigo-200 rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[#1E2B6E] text-white flex items-center justify-center font-bold text-lg shrink-0">
                    📄
                </div>
                <div>
                    <div class="text-xs font-bold text-slate-900">Format Ceklis Observasi FR.IA.01 (PDF Resmi BNSP)</div>
                    <p class="text-[11px] text-slate-600">Unduh lembar ceklis observasi unjuk kerja untuk rujukan standar kompetensi.</p>
                </div>
            </div>
            <a href="{{ route('asesi.assessment.instrument.download-template', [$assessment, 'IA01']) }}" class="px-4 py-2 bg-[#1E2B6E] hover:bg-[#152055] text-white text-xs font-black rounded-lg border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] transition shrink-0 inline-flex items-center gap-2">
                <span>📥 Unduh Lembar PDF</span>
            </a>
        </div>

        @if(! empty($kukData))
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-white p-4 rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)]">
                    <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Kriteria (KUK)</div>
                    <div class="text-2xl font-black text-slate-900 mt-1 font-mono">{{ $totalKuk }}</div>
                </div>
                <div class="bg-emerald-50 p-4 rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)]">
                    <div class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">Kompeten (K)</div>
                    <div class="text-2xl font-black text-emerald-700 mt-1 font-mono">{{ $kCount }}</div>
                </div>
                <div class="bg-rose-50 p-4 rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)]">
                    <div class="text-[11px] font-bold text-rose-800 uppercase tracking-wider">Belum Kompeten (BK)</div>
                    <div class="text-2xl font-black text-rose-700 mt-1 font-mono">{{ $bkCount }}</div>
                </div>
            </div>
        @else
            <div class="bg-amber-50 border border-amber-300 rounded-xl p-4 text-xs text-amber-900">
                <strong>Informasi:</strong> Asesor sedang dalam proses penilaian observasi langsung di TUK. Hasil observasi per elemen dan KUK akan tampil di bawah ini setelah asesor menyelesaikan penilaian.
            </div>
        @endif

        <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] overflow-hidden">
            <div class="bg-slate-900 text-white px-6 py-3 border-b-2 border-black">
                <span class="text-amber-300 font-bold text-xs uppercase tracking-wider font-mono">FR.IA.01 Ceklis Observasi</span>
                <h3 class="text-sm font-bold text-white">Ringkasan Hasil Observasi Aktivitas Praktik</h3>
            </div>
            <div class="p-6 space-y-4">
                @foreach($units ?? [] as $unit)
                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        <div class="bg-slate-100 px-4 py-2 border-b border-slate-200 flex justify-between items-center">
                            <div>
                                <span class="font-mono text-[11px] font-bold text-indigo-700">{{ $unit->code }}</span>
                                <h4 class="text-xs font-bold text-slate-800">{{ $unit->title }}</h4>
                            </div>
                        </div>
                        <div class="divide-y divide-slate-100 text-xs">
                            @foreach($unit->elements as $element)
                                @foreach($element->kuks as $kuk)
                                    @php
                                        $val = $kukData[$kuk->id]['result'] ?? ($kukData[$kuk->id] ?? null);
                                    @endphp
                                    <div class="px-4 py-2 flex items-center justify-between hover:bg-slate-50">
                                        <div class="pr-4">
                                            <span class="font-mono text-[11px] text-slate-500 font-bold mr-2">{{ $kuk->code }}</span>
                                            <span class="text-slate-700">{{ $kuk->criteria }}</span>
                                        </div>
                                        <div class="shrink-0">
                                            @if($val === 'K')
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">Kompeten (K)</span>
                                            @elseif($val === 'BK')
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">Belum (BK)</span>
                                            @else
                                                <span class="text-slate-400 text-[10px] italic">-</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @elseif(strtoupper($code) === 'IA03')
        @php
            $qData = $response?->data_json['questions'] ?? [];
        @endphp

        {{-- Download Template FR.IA.03 --}}
        <div class="bg-indigo-50 border-2 border-indigo-200 rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[#1E2B6E] text-white flex items-center justify-center font-bold text-lg shrink-0">
                    📄
                </div>
                <div>
                    <div class="text-xs font-bold text-slate-900">Daftar Pertanyaan Observasi FR.IA.03 (PDF Resmi BNSP)</div>
                    <p class="text-[11px] text-slate-600">Unduh lembar pertanyaan pendukung observasi resmi untuk skema ini.</p>
                </div>
            </div>
            <a href="{{ route('asesi.assessment.instrument.download-template', [$assessment, 'IA03']) }}" class="px-4 py-2 bg-[#1E2B6E] hover:bg-[#152055] text-white text-xs font-black rounded-lg border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] transition shrink-0 inline-flex items-center gap-2">
                <span>📥 Unduh Soal PMO PDF</span>
            </a>
        </div>

        @if(empty($qData))
            <div class="bg-amber-50 border border-amber-300 rounded-xl p-4 text-xs text-amber-900">
                <strong>Informasi:</strong> Sesi wawancara dan klarifikasi teknis (PMO) dilakukan secara lisan oleh Asesor. Hasil penilaian jawaban Anda akan tampil di bawah ini setelah asesor menyelesaikan input.
            </div>
        @endif

        <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] overflow-hidden">
            <div class="bg-slate-900 text-white px-6 py-3 border-b-2 border-black">
                <span class="text-amber-300 font-bold text-xs uppercase tracking-wider font-mono">FR.IA.03 Pertanyaan Mendukung Observasi (PMO)</span>
                <h3 class="text-sm font-bold text-white">Hasil Tanya Jawab Teknis Lisan / Wawancara</h3>
            </div>
            <div class="divide-y divide-slate-200">
                @foreach($pmoQuestions ?? [] as $idx => $q)
                    @php
                        $qRes = $qData[$q['number'] ?? ($idx + 1)] ?? ($qData[$idx] ?? []);
                        $resStatus = is_array($qRes) ? ($qRes['result'] ?? '') : $qRes;
                    @endphp
                    <div class="p-4 space-y-2 text-xs">
                        <div class="flex items-start justify-between gap-3">
                            <div class="font-bold text-slate-800">
                                <span class="font-mono text-indigo-700 mr-1">Pertanyaan #{{ $loop->iteration }}:</span>
                                {{ $q['question'] }}
                            </div>
                            <div class="shrink-0">
                                @if(strtoupper($resStatus) === 'MEMUASKAN' || strtoupper($resStatus) === 'YA')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">✓ Memuaskan</span>
                                @elseif(strtoupper($resStatus) === 'TIDAK MEMUASKAN' || strtoupper($resStatus) === 'TIDAK')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">✗ Tidak Memuaskan</span>
                                @else
                                    <span class="text-slate-400 text-[10px] italic">Belum Dinilai</span>
                                @endif
                            </div>
                        </div>
                        @if(!empty($q['expected_answer']))
                            <div class="bg-slate-50 p-2.5 rounded border border-slate-200 text-slate-600">
                                <span class="font-bold text-slate-700">Kunci Jawaban yang Diharapkan:</span> {{ $q['expected_answer'] }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @elseif(strtoupper($code) === 'IA02' && isset($response?->data_json['tasks']))
        <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] overflow-hidden">
            <div class="bg-slate-900 text-white px-6 py-3 border-b-2 border-black">
                <span class="text-amber-300 font-bold text-xs uppercase tracking-wider font-mono">FR.IA.02 Penilaian Tugas Praktik Demonstrasi</span>
                <h3 class="text-sm font-bold text-white">Daftar Poin Capaian Instruksi Kerja</h3>
            </div>
            <div class="divide-y divide-slate-200 text-xs">
                @foreach($response->data_json['tasks'] as $tId => $task)
                    <div class="p-4 flex items-center justify-between gap-4 hover:bg-slate-50">
                        <div>
                            <span class="font-bold text-slate-800">Tugas #{{ $loop->iteration }}</span>
                            @if(isset($task['note']) && !empty($task['note']))
                                <div class="text-slate-500 text-[11px] mt-0.5">Catatan: {{ $task['note'] }}</div>
                            @endif
                        </div>
                        <div class="shrink-0">
                            @if(strtoupper($task['result'] ?? '') === 'YA')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">✓ Tercapai (YA)</span>
                            @elseif(strtoupper($task['result'] ?? '') === 'TIDAK')
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">✗ Tidak (TIDAK)</span>
                            @else
                                <span class="text-slate-400 text-[10px] italic">-</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Catatan & Penilaian Asesor --}}
    <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Evaluasi Asesor</span>
                <h3 class="text-base font-bold text-slate-900">Catatan Rekomendasi & Hasil Asesmen</h3>
            </div>
            @if($response?->assessor_signed_at)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-emerald-100 text-emerald-800 text-xs font-bold border border-emerald-300">
                    <span>✓</span> Telah Diverifikasi Asesor
                </span>
            @endif
        </div>
        
        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-xs leading-relaxed">
            @if($response?->assessor_notes)
                <div class="text-slate-800 whitespace-pre-line text-sm">{{ $response->assessor_notes }}</div>
                <div class="mt-3 pt-2 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between">
                    <span>Asesor: <strong>{{ $assessment->assessor?->full_name ?? 'Asesor Kompetensi LSP' }}</strong></span>
                    <span>Waktu Penilaian: <strong>{{ \Carbon\Carbon::parse($response->assessor_signed_at)->isoFormat('D MMMM Y, HH:mm') }} WIB</strong></span>
                </div>
            @else
                <p class="text-slate-500 italic">Asesor belum menginputkan catatan evaluasi tertulis untuk instrumen ini.</p>
            @endif
        </div>

        @if($response?->assessor_signature)
            <div class="pt-2 flex items-center gap-4">
                <div class="border border-slate-200 rounded p-2 bg-white max-w-xs">
                    <div class="text-[10px] font-bold text-slate-400 uppercase mb-1">Tanda Tangan Digital Asesor:</div>
                    <img src="{{ $response->assessor_signature }}" alt="Tanda Tangan Asesor" class="max-h-20 object-contain">
                </div>
            </div>
        @endif
    </div>

    {{-- Form Feedback & Tanda Tangan Asesi --}}
    <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] overflow-hidden">
        <div class="bg-slate-900 px-6 py-4 text-white border-b-2 border-black flex items-center justify-between">
            <div>
                <span class="text-amber-300 font-bold text-xs uppercase tracking-wider font-mono">Tanda Tangan Asesi</span>
                <h3 class="text-base font-bold text-white">Umpan Balik Asesi & Tanda Tangan Digital</h3>
                <p class="text-xs text-slate-300">Berikan catatan tanggapan Anda atas jalannya proses pengujian ini</p>
            </div>
            @if($response?->asesi_signed_at)
                <span class="px-2.5 py-1 text-xs font-bold bg-emerald-500 text-white rounded">
                    ✓ Sudah Ditandatangani
                </span>
            @endif
        </div>

        @if($response?->asesi_signed_at && $response?->asesi_signature)
            <div class="p-6 bg-slate-50 space-y-4">
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="text-xs font-bold text-slate-400 uppercase">Umpan Balik yang Diberikan:</div>
                        <div class="text-sm text-slate-800 italic">
                            "{{ $response->asesi_feedback ?: 'Saya menyetujui seluruh rangkaian proses asesmen yang telah dilaksanakan.' }}"
                        </div>
                        <div class="text-[11px] text-slate-400 mt-1">
                            Ditandatangani pada: <span class="font-mono font-semibold">{{ \Carbon\Carbon::parse($response->asesi_signed_at)->isoFormat('D MMMM Y, HH:mm') }} WIB</span>
                        </div>
                    </div>
                    <div class="border border-slate-200 rounded p-2 bg-slate-50 shrink-0">
                        <div class="text-[10px] font-bold text-slate-400 uppercase mb-1">Tanda Tangan Anda:</div>
                        <img src="{{ $response->asesi_signature }}" alt="Tanda Tangan Asesi" class="max-h-20 object-contain">
                    </div>
                </div>

                {{-- Option to update feedback or re-sign --}}
                <div x-data="{ openEdit: false }" class="pt-2">
                    <button type="button" @click="openEdit = !openEdit" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 underline">
                        Perbarui Umpan Balik atau Tanda Tangan Ulang &darr;
                    </button>
                    <div x-show="openEdit" class="mt-4 pt-4 border-t border-slate-200" style="display: none;">
                        <form action="{{ route('asesi.assessment.instrument.feedback', [$assessment, strtoupper($code)]) }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-2">Umpan Balik / Komentar Anda:</label>
                                <textarea name="asesi_feedback" rows="3" class="w-full text-xs p-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">{{ old('asesi_feedback', $response?->asesi_feedback) }}</textarea>
                            </div>
                            <div class="flex flex-col md:flex-row items-end justify-between gap-6">
                                <div class="w-full md:w-80">
                                    <x-signature-pad 
                                        name="signature_data" 
                                        :value="$response->asesi_signature" 
                                        :profileSignature="auth()->user()->profile?->signature_data"
                                        label="Tanda Tangan Baru (Opsional)" 
                                        height="130" 
                                    />
                                </div>
                                <button type="submit" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-lg border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)]">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <form action="{{ route('asesi.assessment.instrument.feedback', [$assessment, strtoupper($code)]) }}" method="POST" class="p-6 space-y-6">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Umpan Balik / Komentar Anda terhadap Asesmen Ini:</label>
                    <textarea name="asesi_feedback" rows="3" class="w-full text-xs p-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Contoh: Proses pemeriksaan teknis berjalan objektif, arahan dan instruksi asesor jelas...">{{ old('asesi_feedback', $response?->asesi_feedback) }}</textarea>
                </div>

                <div class="flex flex-col md:flex-row items-end justify-between gap-6 pt-4 border-t border-slate-200">
                    <div class="w-full md:w-80">
                        <x-signature-pad 
                            name="signature_data" 
                            :value="old('signature_data')" 
                            :profileSignature="auth()->user()->profile?->signature_data" 
                            label="Tanda Tangan Digital Asesi" 
                            height="130" 
                        />
                    </div>

                    <div class="w-full md:w-auto">
                        <button type="submit" class="w-full md:w-auto px-6 py-3 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] transition flex items-center justify-center gap-2">
                            <span>Kirim Umpan Balik & Tanda Tangan</span>
                            <span>✓</span>
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
