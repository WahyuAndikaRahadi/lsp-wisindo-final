@extends('layouts.print-muk')

@section('title', 'FR.AK.02 Rekaman Asesmen - ' . ($asesi->full_name ?? 'Wahyu Andika Rahadi'))
@section('doc-title', 'FR.AK.02 Rekaman Asesmen Kompetensi')

@push('styles')
<style>
    .ak02-page {
        font-family: Calibri, "Liberation Sans", Arial, sans-serif;
        font-size: 11pt;
        color: #000;
        line-height: 1.35;
        width: 100%;
        max-width: 195mm;
        margin: 0 auto;
    }
    .header-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #000;
        margin-bottom: 12px;
    }
    .header-table td {
        border: 1px solid #000;
        padding: 5px 10px;
        vertical-align: middle;
    }
    .header-logo-cell {
        width: 105px;
        text-align: center;
        padding: 4px !important;
    }
    .header-logo-img {
        max-height: 48px;
        max-width: 80px;
        object-fit: contain;
    }
    .header-title-cell {
        font-family: Tahoma, "Liberation Sans", sans-serif;
        font-size: 11pt;
        font-weight: bold;
        padding-left: 14px;
    }
    .doc-main-title {
        font-size: 12pt;
        font-weight: bold;
        margin: 10px 0 8px 0;
        text-align: left;
    }
    .master-ak02-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #000;
        font-size: 10.5pt;
    }
    .master-ak02-table th, .master-ak02-table td {
        border: 1px solid #000;
        padding: 4px 6px;
        vertical-align: middle;
    }
    .vertical-header-th {
        height: 85px;
        width: 32px;
        position: relative;
        vertical-align: bottom !important;
        text-align: center;
        padding: 4px 2px !important;
    }
    .vertical-header-text {
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        white-space: nowrap;
        font-size: 9pt;
        font-weight: bold;
        display: inline-block;
        margin: 0 auto;
        letter-spacing: -0.2px;
    }
    .cb-box-small {
        display: inline-block;
        width: 12px;
        height: 12px;
        border: 1px solid #000;
        text-align: center;
        line-height: 11px;
        font-size: 9pt;
        font-weight: bold;
        vertical-align: middle;
        margin-right: 4px;
    }
    .signature-table-ak02 {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #000;
        font-size: 11pt;
        margin-top: 10px;
    }
    .signature-table-ak02 td {
        border: 1px solid #000;
        padding: 5px 8px;
        vertical-align: middle;
    }
    .continuation-box {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #000;
        margin-bottom: 12px;
        height: 60px;
    }
    .continuation-box td {
        border: 1px solid #000;
        padding: 6px;
    }
</style>
@endpush

@section('content')
@php
    $dummySkemaJudul = $scheme->name ?? 'Maintenance Gasoline Engine Injection System';
    $dummySkemaNomor = $scheme->code ?? 'SKM/LSP-WISINDO/2026';
    $dummyTukTipe = 'Sewaktu/Tempat Kerja/Mandiri*';
    $dummyAsesorNama = $assessor?->full_name ?? $assessor?->name ?? 'Drs. Budi Santoso, M.T.';
    $dummyNoReg = $assessor?->profile?->met_number ?? 'MET.000.001234 2022';
    $dummyAsesiNama = $asesi->full_name ?? $asesi->name ?? 'Wahyu Andika Rahadi';
    $dummyTglMulai = !empty($schedule?->date) ? \Carbon\Carbon::parse($schedule->date)->format('d/m/Y') . ' 08:30' : '19/10/2026 08:30';
    $dummyTglSelesai = !empty($schedule?->date) ? \Carbon\Carbon::parse($schedule->date)->format('d/m/Y') . ' 12:00' : '19/10/2026 12:00';
    $dummyTglTtd = !empty($schedule?->date) ? \Carbon\Carbon::parse($schedule->date)->format('d/m/Y') : '19/10/2026';
    $dummyTindakLanjut = $assessment->assessor_follow_up ?? 'Tidak ada tindakan perbaikan yang diperlukan. Seluruh unit kompetensi telah terpenuhi sesuai standar SKKNI.';
    $dummyKomentar = $assessment->assessor_notes ?? 'Asesi menunjukkan pemahaman dan keterampilan kerja yang sangat baik dalam perawatan sistem injeksi bensin.';

    $defaultUnitItems = [
        ['title' => 'Melaksanakan Keselamatan dan Kesehatan Kerja', 'obs' => true, 'port' => true, 'p3' => false, 'lisan' => true, 'tulis' => true, 'proyek' => false, 'lain' => false],
        ['title' => 'Menggunakan Peralatan dan Perlengkapan Tempat Kerja', 'obs' => true, 'port' => false, 'p3' => true, 'lisan' => true, 'tulis' => false, 'proyek' => false, 'lain' => false],
        ['title' => 'Membaca Gambar Teknik', 'obs' => false, 'port' => true, 'p3' => false, 'lisan' => false, 'tulis' => true, 'proyek' => true, 'lain' => false],
        ['title' => 'Menggunakan Alat Ukur', 'obs' => true, 'port' => true, 'p3' => false, 'lisan' => true, 'tulis' => false, 'proyek' => false, 'lain' => false],
        ['title' => 'Melaksanakan Diagnosis', 'obs' => true, 'port' => false, 'p3' => true, 'lisan' => false, 'tulis' => true, 'proyek' => true, 'lain' => false],
        ['title' => 'Melakukan Perawatan Berkala Engine', 'obs' => true, 'port' => true, 'p3' => false, 'lisan' => true, 'tulis' => true, 'proyek' => false, 'lain' => false],
        ['title' => 'Memelihara Baterai', 'obs' => true, 'port' => false, 'p3' => false, 'lisan' => true, 'tulis' => true, 'proyek' => false, 'lain' => true],
        ['title' => 'Memperbaiki Kerusakan Pada Sistem Pengaman Kelistrikan', 'obs' => true, 'port' => true, 'p3' => true, 'lisan' => false, 'tulis' => true, 'proyek' => false, 'lain' => false],
        ['title' => 'Memperbaiki Sistem Manajemen Engine', 'obs' => true, 'port' => false, 'p3' => false, 'lisan' => true, 'tulis' => true, 'proyek' => true, 'lain' => false],
    ];
    $schemeUnits = $scheme->competencyUnits ?? collect();
@endphp

{{-- ========================================== PAGE 1 ========================================== --}}
<div class="ak02-page">
    {{-- Header Kop Box --}}
    <table class="header-table">
        <tr>
            <td class="header-logo-cell">
                <img src="{{ asset('storage/assets/logo.png') }}" onerror="this.onerror=null; this.src='{{ asset('images/logo.png') }}';" class="header-logo-img" alt="Logo LSP">
            </td>
            <td class="header-title-cell">
                Lembaga Sertifikasi Profesi P3 Wisata Indonesia &nbsp;LSP Wisindo
            </td>
        </tr>
    </table>

    {{-- Title --}}
    <div class="doc-main-title">
        FR.AK.02. REKAMAN ASESMEN KOMPETENSI
    </div>

    {{-- Single Unified Table on Page 1 --}}
    <table class="master-ak02-table">
        {{-- Row 0: Skema Judul --}}
        <tr>
            <td rowspan="2" style="width: 25%; vertical-align: middle;">
                Skema Sertifikasi<br>
                <span>(KKNI/Okupasi/Klaster)</span>
            </td>
            <td style="width: 10%;">Judul</td>
            <td style="width: 3%; text-align: center;">:</td>
            <td colspan="7" style="font-style: italic;">{{ $dummySkemaJudul }}</td>
        </tr>

        {{-- Row 1: Skema Nomor --}}
        <tr>
            <td>Nomor</td>
            <td style="text-align: center;">:</td>
            <td colspan="7">{{ $dummySkemaNomor }}</td>
        </tr>

        {{-- Row 2: TUK --}}
        <tr>
            <td colspan="2">TUK</td>
            <td style="text-align: center;">:</td>
            <td colspan="7">{{ $dummyTukTipe }}</td>
        </tr>

        {{-- Row 3: Nama Asesor --}}
        <tr>
            <td colspan="2">Nama Asesor</td>
            <td style="text-align: center;">:</td>
            <td colspan="7" style="font-weight: bold;">{{ $dummyAsesorNama }}</td>
        </tr>

        {{-- Row 4: Nama Asesi --}}
        <tr>
            <td colspan="2">Nama Asesi</td>
            <td style="text-align: center;">:</td>
            <td colspan="7" style="font-weight: bold;">{{ $dummyAsesiNama }}</td>
        </tr>

        {{-- Row 5: Tanggal Asesmen Mulai --}}
        <tr>
            <td rowspan="2" style="vertical-align: middle;">Tanggal Asesmen</td>
            <td>Mulai</td>
            <td style="text-align: center;">:</td>
            <td colspan="7">{{ $dummyTglMulai }}</td>
        </tr>

        {{-- Row 6: Tanggal Asesmen Selesai --}}
        <tr>
            <td>Selesai</td>
            <td style="text-align: center;">:</td>
            <td colspan="7">{{ $dummyTglSelesai }}</td>
        </tr>

        {{-- Row 7: Instruction Row --}}
        <tr>
            <td colspan="10" style="padding: 7px 8px; font-size: 10pt;">
                Beri tanda centang (&radic;) di kolom yang sesuai untuk mencerminkan bukti yang diperoleh untuk menentukan Kompetensi Asesi untuk setiap Unit Kompetensi.
            </td>
        </tr>

        {{-- Row 8: Unit Kompetensi & Rotated Evidence Column Headers --}}
        <tr style="background-color: #fafafa;">
            <th style="width: 48%; text-align: center; vertical-align: middle; font-size: 11pt;">
                Unit Kompetensi
            </th>
            <th class="vertical-header-th">
                <span class="vertical-header-text">Observasi Demonstrasi</span>
            </th>
            <th class="vertical-header-th">
                <span class="vertical-header-text">Portofolio</span>
            </th>
            <th class="vertical-header-th" style="width: 58px;">
                <span class="vertical-header-text">Pernyataan Pihak Ketiga / Pertanyaan Wawancara</span>
            </th>
            <th class="vertical-header-th">
                <span class="vertical-header-text">Pertanyaan Lisan</span>
            </th>
            <th class="vertical-header-th">
                <span class="vertical-header-text">Pertanyaan Tertulis</span>
            </th>
            <th class="vertical-header-th">
                <span class="vertical-header-text">Proyek Kerja</span>
            </th>
            <th class="vertical-header-th">
                <span class="vertical-header-text">Lainnya</span>
            </th>
        </tr>

        {{-- Rows 9 to 17: 9 Competency Units --}}
        @php
            $ak02Indexed = isset($ak02Items) ? $ak02Items->keyBy('competency_unit_id') : collect();
        @endphp
        @if($schemeUnits->count() > 0)
            @foreach($schemeUnits as $idx => $u)
                @php
                    $item = $ak02Indexed->get($u->id);
                    $methods = $item?->methods_json ?? [];
                    $dummyFallback = $defaultUnitItems[$idx % count($defaultUnitItems)];
                    $isObs = $item ? (! empty($methods['observasi_demonstrasi']) || $item->is_observasi) : $dummyFallback['obs'];
                    $isPort = $item ? (! empty($methods['portofolio']) || $item->is_portofolio) : $dummyFallback['port'];
                    $isP3 = $item ? (! empty($methods['pihak_ketiga']) || $item->is_pihak_ketiga) : $dummyFallback['p3'];
                    $isLisan = $item ? (! empty($methods['lisan']) || $item->is_lisan) : $dummyFallback['lisan'];
                    $isTulis = $item ? (! empty($methods['tulis']) || $item->is_tulis) : $dummyFallback['tulis'];
                    $isProyek = $item ? (! empty($methods['proyek']) || $item->is_proyek) : $dummyFallback['proyek'];
                    $isLain = $item ? (! empty($methods['lainnya']) || $item->is_lainnya) : $dummyFallback['lain'];
                @endphp
                <tr>
                    <td style="padding: 4px 6px; font-size: 10pt;">
                        {{ $u->title }}
                    </td>
                    <td style="text-align: center; font-weight: bold; font-size: 11pt;">{!! $isObs ? '&#10003;' : '' !!}</td>
                    <td style="text-align: center; font-weight: bold; font-size: 11pt;">{!! $isPort ? '&#10003;' : '' !!}</td>
                    <td style="text-align: center; font-weight: bold; font-size: 11pt;">{!! $isP3 ? '&#10003;' : '' !!}</td>
                    <td style="text-align: center; font-weight: bold; font-size: 11pt;">{!! $isLisan ? '&#10003;' : '' !!}</td>
                    <td style="text-align: center; font-weight: bold; font-size: 11pt;">{!! $isTulis ? '&#10003;' : '' !!}</td>
                    <td style="text-align: center; font-weight: bold; font-size: 11pt;">{!! $isProyek ? '&#10003;' : '' !!}</td>
                    <td style="text-align: center; font-weight: bold; font-size: 11pt;">{!! $isLain ? '&#10003;' : '' !!}</td>
                </tr>
            @endforeach
        @else
            @foreach($defaultUnitItems as $u)
                <tr>
                    <td style="padding: 4px 6px; font-size: 10pt;">
                        {{ $u['title'] }}
                    </td>
                    <td style="text-align: center; font-weight: bold; font-size: 11pt;">{!! $u['obs'] ? '&#10003;' : '' !!}</td>
                    <td style="text-align: center; font-weight: bold; font-size: 11pt;">{!! $u['port'] ? '&#10003;' : '' !!}</td>
                    <td style="text-align: center; font-weight: bold; font-size: 11pt;">{!! $u['p3'] ? '&#10003;' : '' !!}</td>
                    <td style="text-align: center; font-weight: bold; font-size: 11pt;">{!! $u['lisan'] ? '&#10003;' : '' !!}</td>
                    <td style="text-align: center; font-weight: bold; font-size: 11pt;">{!! $u['tulis'] ? '&#10003;' : '' !!}</td>
                    <td style="text-align: center; font-weight: bold; font-size: 11pt;">{!! $u['proyek'] ? '&#10003;' : '' !!}</td>
                    <td style="text-align: center; font-weight: bold; font-size: 11pt;">{!! $u['lain'] ? '&#10003;' : '' !!}</td>
                </tr>
            @endforeach
        @endif

        {{-- Row 18: Rekomendasi hasil asesmen --}}
        <tr>
            <td style="vertical-align: middle;">
                Rekomendasi hasil asesmen
            </td>
            <td colspan="7" style="padding: 6px 10px; font-size: 14pt; font-weight: bold;">
                <span class="cb-box-small" style="width: 14px; height: 14px; line-height: 13px; font-size: 11pt;">&#10003;</span>
                Kompeten &nbsp;/&nbsp;
                <span class="cb-box-small" style="width: 14px; height: 14px; line-height: 13px; font-size: 11pt;">&nbsp;</span>
                Belum kompeten
            </td>
        </tr>

        {{-- Row 19: Tindak lanjut yang dibutuhkan --}}
        <tr>
            <td style="vertical-align: top;">
                <strong>Tindak lanjut yang dibutuhkan</strong><br>
                <span style="font-size: 9.5pt;">(Masukkan pekerjaan tambahan dan asesmen yang diperlukan untuk mencapai kompetensi)</span>
            </td>
            <td colspan="7" style="vertical-align: top; padding: 6px 8px; font-size: 10pt;">
                {{ $dummyTindakLanjut }}
            </td>
        </tr>

        {{-- Row 20: Komentar/ Observasi oleh asesor --}}
        <tr>
            <td style="vertical-align: top;">
                Komentar/ Observasi oleh asesor
            </td>
            <td colspan="7" style="vertical-align: top; padding: 6px 8px; font-size: 10pt; min-height: 40px;">
                {{ $dummyKomentar }}
            </td>
        </tr>
    </table>
</div>

<div class="page-break" style="page-break-after: always; break-after: page;"></div>

{{-- ========================================== PAGE 2 ========================================== --}}
<div class="ak02-page" style="margin-top: 20px;">
    {{-- Header Kop Box --}}
    <table class="header-table">
        <tr>
            <td class="header-logo-cell">
                <img src="{{ asset('storage/assets/logo.png') }}" onerror="this.onerror=null; this.src='{{ asset('images/logo.png') }}';" class="header-logo-img" alt="Logo LSP">
            </td>
            <td class="header-title-cell">
                Lembaga Sertifikasi Profesi P3 Wisata Indonesia &nbsp;LSP Wisindo
            </td>
        </tr>
    </table>

    {{-- Continuation Box from Page 1 --}}
    <table class="continuation-box">
        <tr>
            <td style="width: 48%;"></td>
            <td></td>
        </tr>
    </table>

    {{-- Signature Table on Page 2 --}}
    <table class="signature-table-ak02">
        {{-- Asesi Section --}}
        <tr>
            <td colspan="3" style="font-weight: bold; background-color: #fafafa; padding: 5px 8px;">
                Asesi :
            </td>
        </tr>
        <tr>
            <td style="width: 32%;">Nama</td>
            <td style="width: 3%; text-align: center;">:</td>
            <td style="font-weight: bold;">{{ $dummyAsesiNama }}</td>
        </tr>
        <tr>
            <td>
                Tanda tangan<br>
                dan Tanggal
            </td>
            <td style="text-align: center;">:</td>
            <td style="font-style: italic; font-size: 10pt;">
                [Tanda Tangan Digital] &nbsp; - &nbsp; {{ $dummyTglTtd }}
            </td>
        </tr>

        {{-- Asesor Section --}}
        <tr>
            <td colspan="3" style="font-weight: bold; background-color: #fafafa; padding: 5px 8px;">
                Asesor :
            </td>
        </tr>
        <tr>
            <td>Nama</td>
            <td style="text-align: center;">:</td>
            <td style="font-weight: bold;">{{ $dummyAsesorNama }}</td>
        </tr>
        <tr>
            <td>No. Reg</td>
            <td style="text-align: center;">:</td>
            <td>{{ $dummyNoReg }}</td>
        </tr>
        <tr>
            <td>
                Tanda tangan<br>
                dan Tanggal
            </td>
            <td style="text-align: center;">:</td>
            <td style="font-style: italic; font-size: 10pt;">
                [Tanda Tangan Digital] &nbsp; - &nbsp; {{ $dummyTglTtd }}
            </td>
        </tr>
    </table>
</div>

<div class="page-break" style="page-break-after: always; break-after: page;"></div>

{{-- ========================================== PAGE 3 ========================================== --}}
<div class="ak02-page" style="margin-top: 36px;">
    {{-- Header Kop Box --}}
    <table class="header-table" style="margin-bottom: 24px;">
        <tr>
            <td class="header-logo-cell" style="padding: 6px !important;">
                <img src="{{ asset('storage/assets/logo.png') }}" onerror="this.onerror=null; this.src='{{ asset('images/logo.png') }}';" class="header-logo-img" alt="Logo LSP">
            </td>
            <td class="header-title-cell" style="vertical-align: middle;">
                Lembaga Sertifikasi Profesi P3 Wisata Indonesia &nbsp;LSP Wisindo
            </td>
        </tr>
    </table>

    <div style="font-size: 11pt; line-height: 1.6; padding-left: 14px;">
        <div style="font-weight: bold; margin-bottom: 8px;">LAMPIRAN DOKUMEN:</div>
        <div style="margin-bottom: 4px;">1. Dokumen APL 01 peserta</div>
        <div style="margin-bottom: 4px;">2. Dokumen APL 02 peserta</div>
        <div style="margin-bottom: 4px;">3. Bukti-bukti berkualitas peserta</div>
        <div style="margin-bottom: 4px;">4. Tinjauan proses asesmen</div>
    </div>
</div>
@endsection
