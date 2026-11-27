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
    $scheme = $schedule->scheme ?? $assessment->schedule?->scheme;
    $skemaJudul = $scheme->name ?? 'Maintenance Gasoline Engine Injection System';
    $skemaNomor = $scheme->code ?? 'SKM/LSP-WISINDO/2026';
    $ak01Data = $assessment->ak01_data ?? $schedule->ak01_data ?? [];
    $tukTipe = ($ak01Data['tuk_type'] ?? ($schedule->tuk_type ?? 'Sewaktu')) . '*';
    $tukNama = $schedule->tuk_name ?? 'TUK Mandiri';
    $asesorNama = $assessor?->full_name ?? $assessor?->name ?? 'Drs. Budi Santoso, M.Par.';
    $asesiNama = $asesi->full_name ?? $asesi->name ?? 'Nama Asesi';

    $date = !empty($schedule?->date) ? \Carbon\Carbon::parse($schedule->date) : now();
    $hariTgl = $date->locale('id')->translatedFormat('l, d F Y');
    $startTime = !empty($schedule?->start_time) ? \Carbon\Carbon::parse($schedule->start_time)->format('H.i') : '08.00';
    $endTime = !empty($schedule?->end_time) ? \Carbon\Carbon::parse($schedule->end_time)->format('H.i') : '12.00';
    $waktu = "{$startTime} - {$endTime} WIB";

    $tglTtd = $assessment->ak01_agreed_at
        ? \Carbon\Carbon::parse($assessment->ak01_agreed_at)->locale('id')->translatedFormat('d F Y')
        : $date->locale('id')->translatedFormat('d F Y');
    $tglAsesor = $assessment->assessor_signed_at
        ? \Carbon\Carbon::parse($assessment->assessor_signed_at)->locale('id')->translatedFormat('d F Y')
        : $tglTtd;
    $tglAsesi = $assessment->asesi_signed_at
        ? \Carbon\Carbon::parse($assessment->asesi_signed_at)->locale('id')->translatedFormat('d F Y')
        : $tglTtd;

    $evidence = $ak01Data['evidence'] ?? [
        'Hasil Verifikasi Portofolio',
        'Hasil Observasi Langsung',
        'Hasil Pertanyaan Lisan',
        'Hasil Pertanyaan Tertulis',
    ];
    $hasPorto = in_array('Hasil Verifikasi Portofolio', $evidence, true);
    $hasReviu = in_array('Hasil Reviu Produk', $evidence, true);
    $hasObs = in_array('Hasil Observasi Langsung', $evidence, true);
    $hasTerk = in_array('Hasil Kegiatan Terstruktur', $evidence, true);
    $hasLisan = in_array('Hasil Pertanyaan Lisan', $evidence, true);
    $hasTulis = in_array('Hasil Pertanyaan Tertulis', $evidence, true);
    $hasWwn = in_array('Hasil Pertanyaan Wawancara', $evidence, true);
    $hasLain = in_array('Lainnya', $evidence, true) || !empty($ak01Data['other_evidence']);

    $asesiSig = $assessment->asesi_signature ?? $asesi->profile?->signature_data;
    $assessorSig = $assessment->assessor_signature ?? $assessor?->profile?->signature_data;
@endphp

<div class="ak01-container">
    {{-- 1. Header Box (Table 1) --}}
    <table class="header-table">
        <tr>
            <td class="header-logo-cell">
                <img src="{{ asset('storage/assets/logo.png') }}" onerror="this.onerror=null; this.src='{{ asset('images/logo.png') }}';" class="header-logo-img" alt="Logo LSP">
            </td>
            <td class="header-title-cell">
                Lembaga Sertifikasi Profesi P3 Wisata Indonesia (LSP Wisindo)
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
            <td style="font-style: italic;">{{ $skemaJudul }}</td>
        </tr>

        {{-- Row 2: Skema Nomor --}}
        <tr>
            <td>Nomor</td>
            <td style="text-align: center;">:</td>
            <td>{{ $skemaNomor }}</td>
        </tr>

        {{-- Row 3: TUK --}}
        <tr>
            <td colspan="2">TUK</td>
            <td style="text-align: center;">:</td>
            <td>{{ $tukTipe }}</td>
        </tr>

        {{-- Row 4: Nama Asesor --}}
        <tr>
            <td colspan="2">Nama Asesor</td>
            <td style="text-align: center;">:</td>
            <td style="font-weight: bold;">{{ $asesorNama }}</td>
        </tr>

        {{-- Row 5: Nama Asesi --}}
        <tr>
            <td colspan="2">Nama Asesi</td>
            <td style="text-align: center;">:</td>
            <td style="font-weight: bold;">{{ $asesiNama }}</td>
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
                            <span class="cb-box">{!! $hasPorto ? '&#10003;' : '&nbsp;' !!}</span> Hasil Verifikasi Portofolio
                        </td>
                        <td style="width: 50%;">
                            <span class="cb-box">{!! $hasReviu ? '&#10003;' : '&nbsp;' !!}</span> Hasil Reviu Produk
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="cb-box">{!! $hasObs ? '&#10003;' : '&nbsp;' !!}</span> Hasil Observasi Langsung
                        </td>
                        <td>
                            <span class="cb-box">{!! $hasTerk ? '&#10003;' : '&nbsp;' !!}</span> Hasil Kegiatan Terstruktur
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="cb-box">{!! $hasLisan ? '&#10003;' : '&nbsp;' !!}</span> Hasil Pertanyaan Lisan
                        </td>
                        <td>
                            <span class="cb-box">{!! $hasTulis ? '&#10003;' : '&nbsp;' !!}</span> Hasil Pertanyaan Tertulis
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top;">
                            <span class="cb-box">{!! $hasLain ? '&#10003;' : '&nbsp;' !!}</span> Lainnya ……
                        </td>
                        <td style="vertical-align: top;">
                            <span class="cb-box">{!! $hasWwn ? '&#10003;' : '&nbsp;' !!}</span> Hasil Pertanyaan Wawancara
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
                        <td style="font-weight: bold;">{{ $hariTgl }}</td>
                    </tr>
                    <tr>
                        <td>Waktu</td>
                        <td style="text-align: center;">:</td>
                        <td style="font-weight: bold;">{{ $waktu }}</td>
                    </tr>
                    <tr>
                        <td>TUK</td>
                        <td style="text-align: center;">:</td>
                        <td style="font-weight: bold;">{{ $tukNama }}</td>
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

        {{-- Row 16: Tanda Tangan Section (2 Symmetrical Columns with Date Underneath) --}}
        <tr>
            <td colspan="4" style="padding: 10px 8px;">
                <table style="width: 100%; border-collapse: collapse; border: none;">
                    <tr>
                        <td style="width: 50%; border: none; vertical-align: top; text-align: center; padding: 0 10px;">
                            <div style="font-size: 10.5pt; font-weight: bold; margin-bottom: 2px; text-align: left;">
                                Tanda tangan Asesor :
                            </div>
                            <div style="height: 48px; display: flex; align-items: center; justify-content: center; margin: 2px 0;">
                                @if(!empty($assessorSig))
                                    <img src="{{ $assessorSig }}" style="max-height: 48px; max-width: 140px; object-fit: contain; filter: contrast(1.8) brightness(0.8);">
                                @else
                                    <div style="color: #94a3b8; font-style: italic; font-size: 8.5pt;">(Belum ditandatangani)</div>
                                @endif
                            </div>
                            <div style="font-weight: bold; font-size: 10pt;">
                                ( {{ $asesorNama }} )
                            </div>
                            <div style="font-size: 9pt; color: #1e293b; margin-top: 3px;">
                                Tanggal : {{ $tglAsesor }}
                            </div>
                        </td>
                        <td style="width: 50%; border: none; vertical-align: top; text-align: center; padding: 0 10px; border-left: 1px solid #000;">
                            <div style="font-size: 10.5pt; font-weight: bold; margin-bottom: 2px; text-align: left;">
                                Tanda tangan Asesi :
                            </div>
                            <div style="height: 48px; display: flex; align-items: center; justify-content: center; margin: 2px 0;">
                                @if(!empty($asesiSig))
                                    <img src="{{ $asesiSig }}" style="max-height: 48px; max-width: 140px; object-fit: contain; filter: contrast(1.8) brightness(0.8);">
                                @else
                                    <div style="color: #94a3b8; font-style: italic; font-size: 8.5pt;">(Belum ditandatangani)</div>
                                @endif
                            </div>
                            <div style="font-weight: bold; font-size: 10pt;">
                                ( {{ $asesiNama }} )
                            </div>
                            <div style="font-size: 9pt; color: #1e293b; margin-top: 3px;">
                                Tanggal : {{ $tglAsesi }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Note below table --}}
    <div class="note-footer">
        * Coret yang tidak perlu
    </div>
</div>
@endsection
