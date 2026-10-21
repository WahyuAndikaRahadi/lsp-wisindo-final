@extends('layouts.print-muk')

@section('title', 'FR.AK.01 Persetujuan Asesmen - ' . ($asesi->full_name ?? 'Wahyu Andika Rahadi'))
@section('doc-title', 'FR.AK.01 Persetujuan Asesmen dan Kerahasiaan')

@push('styles')
<style>
    .ak01-container {
        font-family: Calibri, "Liberation Sans", Arial, sans-serif;
        font-size: 11pt;
        color: #000;
        line-height: 1.35;
        margin: 0 auto;
        width: 100%;
        max-width: 190mm;
    }
    .header-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #000;
        margin-bottom: 14px;
    }
    .header-table td {
        border: 1px solid #000;
        padding: 6px 12px;
        vertical-align: middle;
    }
    .header-logo-cell {
        width: 110px;
        text-align: center;
        padding: 4px !important;
    }
    .header-logo-img {
        max-height: 58px;
        max-width: 85px;
        object-fit: contain;
    }
    .header-title-cell {
        font-family: Tahoma, "Liberation Sans", sans-serif;
        font-size: 11pt;
        font-weight: bold;
        padding-left: 16px;
    }
    .doc-main-title {
        font-size: 12pt;
        font-weight: bold;
        margin: 12px 0 10px 0;
        text-align: left;
    }
    .master-ak01-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #000;
        font-size: 11pt;
    }
    .master-ak01-table td {
        border: 1px solid #000;
        padding: 5px 8px;
        vertical-align: top;
    }
    .cb-box {
        display: inline-block;
        width: 13px;
        height: 13px;
        border: 1px solid #000;
        text-align: center;
        line-height: 12px;
        font-size: 10pt;
        font-weight: bold;
        margin-right: 4px;
        vertical-align: middle;
    }
    .sub-table-clean {
        width: 100%;
        border-collapse: collapse;
    }
    .sub-table-clean td {
        border: none !important;
        padding: 3px 2px;
    }
    .note-footer {
        font-size: 11pt;
        font-style: italic;
        margin-top: 6px;
        text-align: left;
    }
</style>
@endpush

@section('content')
@php
    $dummySkemaJudul = $scheme->name ?? 'Maintenance Gasoline Engine Injection System';
    $dummySkemaNomor = $scheme->code ?? '02/LSP PrO Indo/I/2026';
    $dummyTukTipe = 'Sewaktu/Tempat Kerja/Mandiri*';
    $dummyTukNama = $schedule->tuk_name ?? 'TUK Otomotif PrO Indo';
    $dummyAsesorNama = $assessor?->full_name ?? $assessor?->name ?? 'Drs. Budi Santoso, M.T.';
    $dummyAsesiNama = $asesi->full_name ?? $asesi->name ?? 'Wahyu Andika Rahadi';
    $dummyHariTgl = !empty($schedule?->date) ? \Carbon\Carbon::parse($schedule->date)->translatedFormat('l, d F Y') : 'Senin, 19 Oktober 2026';
    $dummyWaktu = (!empty($schedule?->start_time) && !empty($schedule?->end_time))
        ? \Carbon\Carbon::parse($schedule->start_time)->format('H.i') . ' - ' . \Carbon\Carbon::parse($schedule->end_time)->format('H.i') . ' WIB'
        : '08.00 - 12.00 WIB';
    $dummyTglTtd = !empty($schedule?->date) ? \Carbon\Carbon::parse($schedule->date)->translatedFormat('d F Y') : '19 Oktober 2026';
@endphp

<div class="ak01-container">
    {{-- 1. Header Box (Table 1) --}}
    <table class="header-table">
        <tr>
            <td class="header-logo-cell">
                <img src="{{ asset('storage/assets/logo.png') }}" onerror="this.onerror=null; this.src='{{ asset('images/logo.png') }}';" class="header-logo-img" alt="Logo LSP">
            </td>
            <td class="header-title-cell">
                Lembaga Sertifikasi Profesi Praktisi Otomotif LSP-PrO Indo
            </td>
        </tr>
    </table>

    {{-- 2. Document Title --}}
    <div class="doc-main-title">
        FR.AK.01. &nbsp;PERSETUJUAN ASESMEN DAN KERAHASIAAN
    </div>

    {{-- 3. Main Single Unified Table (Table 2) --}}
    <table class="master-ak01-table">
        {{-- Row 0: Persetujuan statement --}}
        <tr>
            <td colspan="4" style="padding: 8px 10px;">
                Persetujuan Asesmen ini untuk menjamin bahwa Asesi telah diberi arahan secara rinci tentang perencanaan dan proses asesmen
            </td>
        </tr>

        {{-- Row 1: Skema Judul --}}
        <tr>
            <td rowspan="2" style="width: 26%; vertical-align: middle;">
                Skema Sertifikasi<br>
                <span>(KKNI/Okupasi/Klaster)</span>
            </td>
            <td style="width: 10%;">Judul</td>
            <td style="width: 3%; text-align: center;">:</td>
            <td style="font-style: italic;">{{ $dummySkemaJudul }}</td>
        </tr>

        {{-- Row 2: Skema Nomor --}}
        <tr>
            <td>Nomor</td>
            <td style="text-align: center;">:</td>
            <td>{{ $dummySkemaNomor }}</td>
        </tr>

        {{-- Row 3: TUK --}}
        <tr>
            <td colspan="2">TUK</td>
            <td style="text-align: center;">:</td>
            <td>{{ $dummyTukTipe }}</td>
        </tr>

        {{-- Row 4: Nama Asesor --}}
        <tr>
            <td colspan="2">Nama Asesor</td>
            <td style="text-align: center;">:</td>
            <td style="font-weight: bold;">{{ $dummyAsesorNama }}</td>
        </tr>

        {{-- Row 5: Nama Asesi --}}
        <tr>
            <td colspan="2">Nama Asesi</td>
            <td style="text-align: center;">:</td>
            <td style="font-weight: bold;">{{ $dummyAsesiNama }}</td>
        </tr>

        {{-- Row 6 to 9: Bukti-bukti yang dikumpulkan --}}
        <tr>
            <td colspan="2" style="vertical-align: middle;">
                Bukti yang akan dikumpulkan :
            </td>
            <td style="text-align: center; vertical-align: top; padding-top: 8px;">
                :<br><br>:<br><br><br>:<br><br>
            </td>
            <td style="padding: 6px 8px;">
                <table class="sub-table-clean">
                    <tr>
                        <td style="width: 50%;">
                            <span class="cb-box">&#10003;</span> Hasil Verifikasi Portofolio
                        </td>
                        <td style="width: 50%;">
                            <span class="cb-box">&nbsp;</span> Hasil Reviu Produk
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="cb-box">&#10003;</span> Hasil Observasi Langsung
                        </td>
                        <td>
                            <span class="cb-box">&nbsp;</span> Hasil Kegiatan Terstruktur
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="cb-box">&#10003;</span> Hasil Pertanyaan Lisan
                        </td>
                        <td>
                            <span class="cb-box">&#10003;</span> Hasil Pertanyaan Tertulis
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top;">
                            <span class="cb-box">&nbsp;</span> Lainnya ……
                        </td>
                        <td style="vertical-align: top;">
                            <span class="cb-box">&nbsp;</span> Hasil Pertanyaan Wawancara
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- Row 10 to 12: Pelaksanaan Asesmen --}}
        <tr>
            <td colspan="2" style="vertical-align: middle;">
                Pelaksanaan asesmen disepakati pada:
            </td>
            <td colspan="2" style="padding: 4px 8px;">
                <table class="sub-table-clean">
                    <tr>
                        <td style="width: 25%;">Hari/ Tanggal</td>
                        <td style="width: 3%; text-align: center;">:</td>
                        <td style="font-weight: bold;">{{ $dummyHariTgl }}</td>
                    </tr>
                    <tr>
                        <td>Waktu</td>
                        <td style="text-align: center;">:</td>
                        <td style="font-weight: bold;">{{ $dummyWaktu }}</td>
                    </tr>
                    <tr>
                        <td>TUK</td>
                        <td style="text-align: center;">:</td>
                        <td style="font-weight: bold;">{{ $dummyTukNama }}</td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- Row 13: Pernyataan Asesi 1 --}}
        <tr>
            <td colspan="4" style="padding: 8px 10px;">
                <strong>Asesi :</strong><br>
                Bahwa saya telah mendapatkan penjelasan terkait hak dan prosedur banding asesmen dari asesor.
            </td>
        </tr>

        {{-- Row 14: Pernyataan Asesor --}}
        <tr>
            <td colspan="4" style="padding: 8px 10px;">
                <strong>Asesor :</strong><br>
                Menyatakan tidak akan membuka hasil pekerjaan yang saya peroleh karena penugasan saya sebagai Asesor dalam pekerjaan <em>Asesmen</em> kepada siapapun atau organisasi apapun selain kepada pihak yang berwenang sehubungan dengan kewajiban saya sebagai Asesor yang ditugaskan oleh LSP.
            </td>
        </tr>

        {{-- Row 15: Pernyataan Asesi 2 --}}
        <tr>
            <td colspan="4" style="padding: 8px 10px;">
                <strong>Asesi :</strong><br>
                Saya setuju mengikuti asesmen dengan pemahaman bahwa informasi yang dikumpulkan hanya digunakan untuk pengembangan profesional dan hanya dapat diakses oleh orang tertentu saja.
            </td>
        </tr>

        {{-- Row 16: Tanda Tangan Section (Stacked vertically exactly like Master) --}}
        <tr>
            <td colspan="4" style="padding: 12px 10px 14px 10px;">
                <div style="margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                        <div>
                            Tanda tangan Asesor : ……………………………
                        </div>
                        <div style="margin-right: 180px;">
                            Tanggal : {{ $dummyTglTtd }}
                        </div>
                    </div>
                    <div style="margin-top: 3px; padding-left: 140px; font-weight: bold; font-size: 10pt;">
                        ( {{ $dummyAsesorNama }} )
                    </div>
                </div>

                <div style="margin-top: 8px;">
                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                        <div>
                            Tanda tangan Asesi &nbsp;: ……………………………
                        </div>
                        <div style="margin-right: 180px;">
                            Tanggal : {{ $dummyTglTtd }}
                        </div>
                    </div>
                    <div style="margin-top: 3px; padding-left: 140px; font-weight: bold; font-size: 10pt;">
                        ( {{ $dummyAsesiNama }} )
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Note below table --}}
    <div class="note-footer">
        * Coret yang tidak perlu
    </div>
</div>
@endsection
