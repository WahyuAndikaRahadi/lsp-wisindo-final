@extends('layouts.print-muk', [
    'title' => 'FR.MAPA.02 Peta Instrumen Asesmen - ' . $scheme->name,
    'docCode' => 'FR.MAPA.02',
    'docTitle' => 'PETA INSTRUMEN ASESMEN HASIL PENDEKATAN ASESMEN DAN PERENCANAAN ASESMEN'
])

@section('print-content')
@include('admin.dokumen-muk.partials.meta-box')

@php
    $effectiveInstruments = $schedule->getEffectiveInstruments() ?? [];
    $mapaData = $schedule->mapa01_data ?? [];
    $pendekatan = strtolower($mapaData['pendekatan_asesi'] ?? '');

    // Resolve Potensi Asesi index (1 to 5)
    $potensiAsesi = 1;
    if (str_contains($pendekatan, 'otodidak') || str_contains($pendekatan, 'mandiri')) {
        $potensiAsesi = 5;
    } elseif (str_contains($pendekatan, 'pekerja berpengalaman')) {
        $potensiAsesi = str_contains($pendekatan, 'belum') ? 4 : 3;
    } elseif (str_contains($pendekatan, 'pelatihan') || str_contains($pendekatan, 'pendidikan')) {
        $potensiAsesi = str_contains($pendekatan, 'belum') ? 2 : 1;
    }

    $instruments = [
        ['code' => '01', 'full_code' => 'IA01', 'title' => 'FR.IA.01. Ceklis Observasi Aktivitas Praktik (CL)', 'type' => 'Langsung (L)'],
        ['code' => '02', 'full_code' => 'IA02', 'title' => 'FR.IA.02. Tugas Praktik Demonstrasi (TPD)', 'type' => 'Langsung (L)'],
        ['code' => '03', 'full_code' => 'IA03', 'title' => 'FR.IA.03. Pertanyaan Mendukung Observasi (PMO)', 'type' => 'Tidak Langsung (TL)'],
        ['code' => '04A', 'full_code' => 'IA04A', 'title' => 'FR.IA.04.A. DIT - Perangkat Asesmen Proyek Singkat', 'type' => 'Langsung (L)'],
        ['code' => '04B', 'full_code' => 'IA04B', 'title' => 'FR.IA.04.B. DIT - Perangkat Asesmen Kegiatan Terstruktur', 'type' => 'Langsung (L)'],
        ['code' => '05', 'full_code' => 'IA05', 'title' => 'FR.IA.05. DPT - Pertanyaan Tertulis Pilihan Ganda', 'type' => 'Tambahan (T)'],
        ['code' => '06', 'full_code' => 'IA06', 'title' => 'FR.IA.06. DPT - Pertanyaan Tertulis Esai', 'type' => 'Tambahan (T)'],
        ['code' => '07', 'full_code' => 'IA07', 'title' => 'FR.IA.07. DPL - Pertanyaan Lisan', 'type' => 'Tambahan (T)'],
        ['code' => '08', 'full_code' => 'IA08', 'title' => 'FR.IA.08. CVP - Ceklis Verifikasi Portofolio', 'type' => 'Tidak Langsung (TL)'],
        ['code' => '09', 'full_code' => 'IA09', 'title' => 'FR.IA.09. PW - Pertanyaan Wawancara', 'type' => 'Tidak Langsung (TL)'],
        ['code' => '10', 'full_code' => 'IA10', 'title' => 'FR.IA.10. VPK - Verifikasi Pihak Ketiga', 'type' => 'Tidak Langsung (TL)'],
        ['code' => '11', 'full_code' => 'IA11', 'title' => 'FR.IA.11. CRP - Ceklis Reviu Produk', 'type' => 'Langsung (L)'],
    ];

    $normInstruments = collect($effectiveInstruments)->map(fn($v) => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $v)))->all();
    $isInstSelected = function($inst) use ($normInstruments) {
        $c = $inst['full_code'];
        $code = $inst['code'];
        if (in_array($c, $normInstruments, true)) return true;
        if ($code === '05' && (in_array('IA05A', $normInstruments, true) || in_array('IA05B', $normInstruments, true) || in_array('IA05C', $normInstruments, true))) return true;
        if ($code === '04A' && in_array('IA04', $normInstruments, true)) return true;
        if ($code === '04B' && in_array('IA04', $normInstruments, true)) return true;
        return false;
    };
@endphp

{{-- Instrument Mapping Description --}}
<p class="text-[11px] text-slate-700 mb-2 italic">
    *) Diisi berdasarkan hasil penentuan pendekatan asesmen dan perencanaan asesmen untuk setiap kelompok unit kompetensi sesuai instrumen FR.IA yang dipilih di FR.AK.01.
</p>

{{-- Instruments Matrix Table --}}
<table class="w-full text-xs border border-black mb-4 border-collapse">
    <thead>
        <tr class="bg-slate-100 border-b border-black text-center font-bold">
            <th class="p-2 border-r border-black w-8" rowspan="2">No</th>
            <th class="p-2 border-r border-black text-left" rowspan="2">Instrumen Asesmen (FR.IA)</th>
            <th class="p-2 border-r border-black w-28" rowspan="2">Bukti-bukti</th>
            <th class="p-1 border-black text-center" colspan="5">Potensi Asesi **</th>
        </tr>
        <tr class="bg-slate-200 border-b border-black text-center font-bold">
            @for($col = 1; $col <= 5; $col++)
                <th class="p-1 border-r border-black w-10 {{ $potensiAsesi === $col ? 'bg-amber-100 text-brand-900 font-extrabold' : '' }}">
                    {{ $col }}
                </th>
            @endfor
        </tr>
    </thead>
    <tbody>
        @foreach($instruments as $idx => $inst)
            @php
                $selected = $isInstSelected($inst);
            @endphp
            <tr class="border-b border-black {{ $selected ? 'bg-emerald-50/40' : '' }}">
                <td class="p-1.5 text-center font-mono border-r border-black">{{ $idx + 1 }}</td>
                <td class="p-1.5 border-r border-black leading-tight {{ $selected ? 'font-semibold text-slate-900' : 'text-slate-600' }}">
                    {{ $inst['title'] }}
                </td>
                <td class="p-1.5 text-center border-r border-black text-[11px] text-slate-600">
                    {{ $inst['type'] }}
                </td>
                @for($col = 1; $col <= 5; $col++)
                    @php
                        $isChecked = ($col === $potensiAsesi) && $selected;
                    @endphp
                    <td class="p-1 text-center font-bold text-sm border-r border-black {{ $col === $potensiAsesi ? 'bg-amber-50/50' : '' }}">
                        @if($isChecked)
                            <span class="inline-block px-1 py-0.2 text-emerald-700 font-extrabold">✓</span>
                        @else
                            <span class="text-slate-300 font-normal">☐</span>
                        @endif
                    </td>
                @endfor
            </tr>
        @endforeach
    </tbody>
</table>

{{-- Instrument Potency Table --}}
<div class="mb-4 border border-black p-3 bg-slate-50 text-[11px]">
    <span class="font-bold block mb-1">**) Keterangan Kategori Potensi Asesi (Pedoman Standar BNSP):</span>
    <ol class="list-decimal pl-5 space-y-1 text-slate-700">
        <li class="{{ $potensiAsesi === 1 ? 'font-bold text-brand-800' : '' }}">
            <strong>Hasil pelatihan/pendidikan (berbasis kompetensi):</strong> Lulusan SMK otomotif/teknik atau lembaga pelatihan yang kurikulum dan fasilitas praktiknya sudah sesuai dengan standar SKKNI.
        </li>
        <li class="{{ $potensiAsesi === 2 ? 'font-bold text-brand-800' : '' }}">
            <strong>Hasil pelatihan/pendidikan (belum berbasis kompetensi):</strong> Lulusan pendidikan atau pelatihan umum yang kurikulumnya belum mengacu langsung pada standar kompetensi kerja.
        </li>
        <li class="{{ $potensiAsesi === 3 ? 'font-bold text-brand-800' : '' }}">
            <strong>Pekerja berpengalaman (tempat kerja berbasis kompetensi):</strong> Mekanik aktif dari bengkel resmi atau industri yang SOP kerjanya sudah selaras dengan standar kompetensi.
        </li>
        <li class="{{ $potensiAsesi === 4 ? 'font-bold text-brand-800' : '' }}">
            <strong>Pekerja berpengalaman (tempat kerja belum berbasis kompetensi):</strong> Mekanik berpengalaman dari bengkel umum atau industri yang operasionalnya belum terdokumentasi sesuai standar kompetensi.
        </li>
        <li class="{{ $potensiAsesi === 5 ? 'font-bold text-brand-800' : '' }}">
            <strong>Pelatihan/belajar mandiri (otodidak):</strong> Peserta yang menguasai keahlian mekanik secara mandiri tanpa pendidikan formal maupun pengalaman kerja terstruktur.
        </li>
    </ol>
</div>

{{-- Signature Section --}}
<div class="border border-black p-4 mt-6">
    <div class="grid grid-cols-2 gap-8 text-xs">
        <div>
            <p class="font-bold text-slate-800 mb-1">Penyusun / Asesor Pengembang:</p>
            <p class="text-[11px] text-slate-600 mb-12">LSP Praktisi Otomotif Indonesia</p>
            <div class="border-b border-black w-48 mb-1"></div>
            <p class="font-bold">RIDWAN</p>
            <p class="text-[10px] text-slate-500 font-mono">No. Reg. 000.008265 2025</p>
        </div>
        <div>
            <p class="font-bold text-slate-800 mb-1">Validator / Komite Teknis:</p>
            <p class="text-[11px] text-slate-600 mb-12">LSP Praktisi Otomotif Indonesia</p>
            <div class="border-b border-black w-48 mb-1"></div>
            <p class="font-bold">GUNADI / KARYANTO</p>
            <p class="text-[10px] text-slate-500 font-mono">No. Reg. 000.000459 2007</p>
        </div>
    </div>
</div>
@endsection
