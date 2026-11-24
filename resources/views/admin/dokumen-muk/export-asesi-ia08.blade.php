@extends('layouts.print-muk')

@section('title', 'FR.IA.08 Ceklis Verifikasi Portofolio - ' . $asesi->full_name)
@section('doc-title', 'FR.IA.08 Ceklis Verifikasi Portofolio')

@section('content')
@php
    $ia08Response = $iaResponses->firstWhere('instrument_code', 'IA08');
    $savedData = is_array($ia08Response?->data_json) ? $ia08Response->data_json : (json_decode($ia08Response?->data_json ?? '[]', true) ?: []);
    $portfolios = $savedData['portfolios'] ?? [];
    $vatmData = $savedData['vatm'] ?? [];
    $clarificationElements = $savedData['clarification_elements'] ?? [];
    $additionalEvidence = $savedData['additional_evidence'] ?? '';
    $recommendation = $savedData['recommendation'] ?? 'K';
    $remediationUnit = $savedData['remediation_unit'] ?? '';
    $remediationElement = $savedData['remediation_element'] ?? '';
    $remediationKuk = $savedData['remediation_kuk'] ?? '';

    $defaultPortfolios = [
        ['type' => 'Surat Pengalaman Kerja', 'title' => 'Surat Pengalaman Kerja minimal 2 tahun di bidang otomotif'],
        ['type' => 'Sertifikat Kompetensi', 'title' => 'Sertifikat Pelatihan / Kompetensi Teknis Otomotif'],
        ['type' => 'Work Order', 'title' => 'Work Order (Surat Perintah Kerja) perbaikan kendaraan'],
    ];
    $displayPortfolios = !empty($portfolios) ? $portfolios : $defaultPortfolios;
@endphp

<div style="font-size: 8.5pt; line-height: 1.35; font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif;">
    {{-- Header Kop Box --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-bottom: 8px;">
        <tr>
            <td style="width: 110px; text-align: center; vertical-align: middle; border-right: 1px solid #000; padding: 4px;">
                <img src="{{ asset('images/logo-lsp-proindo.png') }}" style="max-height: 52px; max-width: 95px; object-fit: contain;" alt="Logo LSP">
            </td>
            <td style="text-align: center; vertical-align: middle; padding: 6px 12px;">
                <span style="font-size: 11pt; font-family: Tahoma, Arial, sans-serif;">
                    <strong>L</strong>embaga <strong>S</strong>ertifikasi <strong>P</strong>rofesi <strong>P</strong>raktisi <strong>O</strong>tomotif &nbsp;<strong>LSP-PrO Indo</strong>
                </span>
            </td>
        </tr>
    </table>

    {{-- Form Title --}}
    <table style="width: 100%; margin-bottom: 8px; font-weight: bold;">
        <tr>
            <td style="width: 18%; font-size: 11pt; vertical-align: top;">FR.IA.08.</td>
            <td style="text-align: right; font-size: 9.5pt; text-transform: uppercase;">
                CVP - CEKLIS VERIFIKASI PORTOFOLIO
            </td>
        </tr>
    </table>

    {{-- Skema Metadata Table --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-bottom: 10px;">
        <tr>
            <td rowspan="2" style="width: 35%; border: 1px solid #000; padding: 4px 6px; font-weight: bold; vertical-align: top;">
                Skema Sertifikasi (KKNI/Okupasi/Klaster)
            </td>
            <td style="width: 12%; border: 1px solid #000; padding: 4px 6px;">Judul</td>
            <td style="width: 3%; border: 1px solid #000; padding: 4px 6px; text-align: center;">:</td>
            <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold;">{{ $scheme->name }}</td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; padding: 4px 6px;">Nomor</td>
            <td style="border: 1px solid #000; padding: 4px 6px; text-align: center;">:</td>
            <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold;">{{ $scheme->code }}</td>
        </tr>
        <tr>
            <td colspan="2" style="border: 1px solid #000; padding: 4px 6px; font-weight: bold;">TUK</td>
            <td style="border: 1px solid #000; padding: 4px 6px; text-align: center;">:</td>
            <td style="border: 1px solid #000; padding: 4px 6px;">Sewaktu / Tempat Kerja / Mandiri*</td>
        </tr>
        <tr>
            <td colspan="2" style="border: 1px solid #000; padding: 4px 6px; font-weight: bold;">Nama Asesor</td>
            <td style="border: 1px solid #000; padding: 4px 6px; text-align: center;">:</td>
            <td style="border: 1px solid #000; padding: 4px 6px;">{{ $leadAssessor?->full_name ?? ($assessor?->full_name ?? 'Asesor Kompetensi') }}</td>
        </tr>
        <tr>
            <td colspan="2" style="border: 1px solid #000; padding: 4px 6px; font-weight: bold;">Nama Asesi</td>
            <td style="border: 1px solid #000; padding: 4px 6px; text-align: center;">:</td>
            <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold;">{{ $asesi->full_name }}</td>
        </tr>
        <tr>
            <td colspan="2" style="border: 1px solid #000; padding: 4px 6px; font-weight: bold;">Tanggal</td>
            <td style="border: 1px solid #000; padding: 4px 6px; text-align: center;">:</td>
            <td style="border: 1px solid #000; padding: 4px 6px;">
                {{ \Carbon\Carbon::parse($schedule->date)->isoFormat('D MMMM Y') }}
            </td>
        </tr>
    </table>
    <div style="font-size: 7.5pt; font-style: italic; margin-top: -6px; margin-bottom: 8px;">*Coret yang tidak perlu</div>

    {{-- Panduan Bagi Asesor --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-bottom: 10px;">
        <tr style="background-color: #f2f2f2;">
            <td style="padding: 4px 6px; font-weight: bold; border-bottom: 1px solid #000;">PANDUAN BAGI ASESOR</td>
        </tr>
        <tr>
            <td style="padding: 6px; font-size: 8pt; line-height: 1.4;">
                <ul style="margin: 0; padding-left: 18px;">
                    <li>Verifikasi portofolio dapat dilakukan untuk keseluruhan unit kompetensi dalam skema sertifikasi atau dilakukan untuk masing-masing kelompok pekerjaan dalam satu skema sertifikasi.</li>
                    <li>Isilah bukti portofolio sesuai ketentuan bukti berkualitas dan relevan dengan standar kompetensi kerja sebagaimana yang telah disepakati pada rekaman asesmen mandiri.</li>
                    <li>Lakukan verifikasi portofolio berdasarkan aturan bukti.</li>
                    <li>Berikan hasil verifikasi portofolio dengan memberi centang (√) pada kolom yang sesuai.</li>
                    <li>Jika hasil verifikasi dokumen portofolio belum memenuhi aturan bukti maka asesor melanjutkan dengan metode tanya jawab pertanyaan wawancara dan/atau verifikasi pihak ketiga.</li>
                </ul>
            </td>
        </tr>
    </table>

    {{-- Tabel Bukti Portofolio & Aturan Bukti (VATM) --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-bottom: 10px;">
        <thead>
            <tr style="background-color: #f2f2f2; text-align: center; font-weight: bold;">
                <th rowspan="2" style="border: 1px solid #000; padding: 5px; width: 40%;">Bukti Portofolio</th>
                <th colspan="8" style="border: 1px solid #000; padding: 4px;">Aturan Bukti</th>
            </tr>
            <tr style="background-color: #f2f2f2; text-align: center; font-size: 8pt; font-weight: bold;">
                <th colspan="2" style="border: 1px solid #000; padding: 3px; width: 15%;">Valid</th>
                <th colspan="2" style="border: 1px solid #000; padding: 3px; width: 15%;">Asli</th>
                <th colspan="2" style="border: 1px solid #000; padding: 3px; width: 15%;">Terkini</th>
                <th colspan="2" style="border: 1px solid #000; padding: 3px; width: 15%;">Memadai</th>
            </tr>
            <tr style="background-color: #fafafa; text-align: center; font-size: 7.5pt;">
                <th style="border: 1px solid #000; padding: 2px;">(Nama Dokumen Bukti)</th>
                <th style="border: 1px solid #000; padding: 2px;">Ya</th>
                <th style="border: 1px solid #000; padding: 2px;">Tdk</th>
                <th style="border: 1px solid #000; padding: 2px;">Ya</th>
                <th style="border: 1px solid #000; padding: 2px;">Tdk</th>
                <th style="border: 1px solid #000; padding: 2px;">Ya</th>
                <th style="border: 1px solid #000; padding: 2px;">Tdk</th>
                <th style="border: 1px solid #000; padding: 2px;">Ya</th>
                <th style="border: 1px solid #000; padding: 2px;">Tdk</th>
            </tr>
        </thead>
        <tbody>
            @foreach($displayPortfolios as $idx => $p)
                @php
                    $pId = $p['id'] ?? $idx;
                    $v = $vatmData[$pId]['v'] ?? ($vatmData[$idx]['v'] ?? 'YA');
                    $a = $vatmData[$pId]['a'] ?? ($vatmData[$idx]['a'] ?? 'YA');
                    $t = $vatmData[$pId]['t'] ?? ($vatmData[$idx]['t'] ?? 'YA');
                    $m = $vatmData[$pId]['m'] ?? ($vatmData[$idx]['m'] ?? 'YA');
                @endphp
                <tr>
                    <td style="border: 1px solid #000; padding: 5px 6px;">
                        <strong>{{ $p['type'] ?? 'Portofolio' }}:</strong><br>
                        {{ $p['title'] ?? ($p['original_name'] ?? 'Dokumen Portofolio') }}
                    </td>
                    <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $v === 'YA' ? '√' : '' }}</td>
                    <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $v === 'TIDAK' ? '√' : '' }}</td>
                    <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $a === 'YA' ? '√' : '' }}</td>
                    <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $a === 'TIDAK' ? '√' : '' }}</td>
                    <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $t === 'YA' ? '√' : '' }}</td>
                    <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $t === 'TIDAK' ? '√' : '' }}</td>
                    <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $m === 'YA' ? '√' : '' }}</td>
                    <td style="border: 1px solid #000; text-align: center; font-weight: bold;">{{ $m === 'TIDAK' ? '√' : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Tindak Lanjut Klarifikasi Wawancara --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-bottom: 10px;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th colspan="4" style="border: 1px solid #000; padding: 4px 6px; text-align: left; font-size: 8pt; font-weight: bold;">
                    Sebagai tindak lanjut dari hasil verifikasi bukti, substansi materi di bawah ini (no elemen yg di cek list) harus diklarifikasi selama wawancara:
                </th>
            </tr>
            <tr style="background-color: #fafafa; text-align: center; font-size: 8pt; font-weight: bold;">
                <th style="border: 1px solid #000; padding: 3px; width: 7%;">Cek List</th>
                <th style="border: 1px solid #000; padding: 3px; width: 23%;">No. Unit Kompetensi</th>
                <th style="border: 1px solid #000; padding: 3px; width: 18%;">No. Elemen</th>
                <th style="border: 1px solid #000; padding: 3px; text-align: left;">Materi/substansi wawancara /KUK</th>
            </tr>
        </thead>
        <tbody>
            @foreach($scheme->competencyUnits as $uIdx => $unit)
                @foreach($unit->elements as $eIdx => $elem)
                    @php
                        $elemKey = (string) $elem->id;
                        $isChecked = in_array($elemKey, (array) $clarificationElements);
                    @endphp
                    <tr>
                        <td style="border: 1px solid #000; text-align: center; font-weight: bold; font-size: 10pt;">
                            {{ $isChecked ? '√' : '' }}
                        </td>
                        <td style="border: 1px solid #000; padding: 3px 6px; font-family: monospace;">
                            {{ $unit->code }}
                        </td>
                        <td style="border: 1px solid #000; padding: 3px 6px;">
                            Elemen {{ $eIdx + 1 }}
                        </td>
                        <td style="border: 1px solid #000; padding: 4px 6px; font-size: 8pt; vertical-align: top;">
                            <div style="font-weight: bold; margin-bottom: 2px;">{{ $elem->title ?? $elem->name }}</div>
                            @if($elem->kuks && $elem->kuks->isNotEmpty())
                                <div style="margin-top: 3px; padding-left: 6px; border-left: 1px solid #999; font-size: 7.5pt; color: #333;">
                                    @foreach($elem->kuks as $kIdx => $kuk)
                                        <div style="margin-bottom: 2px;">
                                            <span style="font-family: monospace; font-weight: bold;">{{ $kuk->code_number ?: (($eIdx + 1) . '.' . ($kIdx + 1)) }}</span>
                                            {{ $kuk->statement ?: $kuk->description }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    {{-- Bukti Tambahan --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-bottom: 10px;">
        <tr>
            <td style="padding: 4px 6px; font-weight: bold; border-bottom: 1px solid #000; background-color: #f2f2f2;">
                Bukti tambahan diperlukan pada unit / elemen kompetensi sebagai berikut:
            </td>
        </tr>
        <tr>
            <td style="padding: 6px; min-height: 35px; font-size: 8pt;">
                {{ $additionalEvidence ?: 'Tidak ada bukti tambahan yang dipersyaratkan.' }}
            </td>
        </tr>
    </table>

    {{-- Rekomendasi Asesor --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-bottom: 12px;">
        <tr>
            <td style="padding: 4px 6px; font-weight: bold; border-bottom: 1px solid #000; background-color: #f2f2f2;">
                Rekomendasi Asesor:
            </td>
        </tr>
        <tr>
            <td style="padding: 6px; font-size: 8pt; line-height: 1.5;">
                <div style="margin-bottom: 4px;">
                    <span style="display: inline-block; width: 14px; text-align: center; font-weight: bold; border: 1px solid #000; margin-right: 4px;">
                        {{ $recommendation === 'K' ? '√' : '' }}
                    </span>
                    Asesi telah memenuhi pencapaian seluruh kriteria unjuk kerja, direkomendasikan <strong>KOMPETEN</strong>
                </div>
                <div>
                    <span style="display: inline-block; width: 14px; text-align: center; font-weight: bold; border: 1px solid #000; margin-right: 4px;">
                        {{ $recommendation === 'BK' ? '√' : '' }}
                    </span>
                    Asesi belum memenuhi pencapaian seluruh kriteria unjuk kerja, direkomendasikan <strong>OBSERVASI LANGSUNG/KEGIATAN TERSTRUKTUR*)</strong> pada:
                    <div style="padding-left: 24px; margin-top: 3px;">
                        Kelompok Pekerjaan Unit : {{ $remediationUnit ?: '..................................................................' }}<br>
                        Elemen : {{ $remediationElement ?: '..................................................................' }}<br>
                        KUK : {{ $remediationKuk ?: '..................................................................' }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Tanda Tangan Asesi & Asesor --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse;">
        <tr>
            <td style="width: 50%; border: 1px solid #000; padding: 6px; vertical-align: top;">
                <strong>Asesi :</strong><br>
                <table style="width: 100%; margin-top: 4px;">
                    <tr>
                        <td style="width: 25%;">Nama</td>
                        <td style="width: 5%;">:</td>
                        <td><strong>{{ $asesi->full_name }}</strong></td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top;">Tanda Tangan & Tanggal</td>
                        <td style="vertical-align: top;">:</td>
                        <td>
                            @php
                                $asesiSig = $ia08Response?->asesi_signature ?? $assessment->asesi_signature ?? $asesi?->profile?->signature_data;
                            @endphp
                            @if($asesiSig)
                                <img src="{{ $asesiSig }}" style="max-height: 48px; max-width: 130px; object-fit: contain; display: block;" alt="TTD Asesi">
                            @endif
                            <div style="font-size: 7.5pt; color: #555; margin-top: 2px;">
                                {{ \Carbon\Carbon::parse($schedule->date)->isoFormat('D MMMM Y') }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; border: 1px solid #000; padding: 6px; vertical-align: top;">
                <strong>Asesor :</strong><br>
                <table style="width: 100%; margin-top: 4px;">
                    <tr>
                        <td style="width: 25%;">Nama</td>
                        <td style="width: 5%;">:</td>
                        <td><strong>{{ $leadAssessor?->full_name ?? ($assessor?->full_name ?? 'Asesor Kompetensi') }}</strong></td>
                    </tr>
                    <tr>
                        <td>No. Reg</td>
                        <td>:</td>
                        <td>{{ $leadAssessor?->profile?->no_reg ?? ($assessor?->profile?->no_reg ?? ($leadAssessor?->assessor_registration_number ?? '-')) }}</td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top;">Tanda Tangan & Tanggal</td>
                        <td style="vertical-align: top;">:</td>
                        <td>
                            @php
                                $asesorSig = $ia08Response?->assessor_signature ?? $assessment->assessor_signature ?? $leadAssessor?->profile?->signature_data ?? $assessor?->profile?->signature_data;
                            @endphp
                            @if($asesorSig)
                                <img src="{{ $asesorSig }}" style="max-height: 48px; max-width: 130px; object-fit: contain; display: block;" alt="TTD Asesor">
                            @endif
                            <div style="font-size: 7.5pt; color: #555; margin-top: 2px;">
                                {{ \Carbon\Carbon::parse($schedule->date)->isoFormat('D MMMM Y') }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div style="font-size: 7.5pt; font-style: italic; margin-top: 4px;">*Coret yang tidak perlu</div>
</div>
@endsection
