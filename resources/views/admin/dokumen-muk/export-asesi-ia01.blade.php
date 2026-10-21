@extends('layouts.print-muk')

@section('title', 'FR.IA.01 Ceklis Observasi Praktik - ' . $asesi->full_name)
@section('doc-title', 'FR.IA.01 Ceklis Observasi Aktivitas Praktik')

@section('content')
@php
    $ia01Response = $iaResponses->firstWhere('instrument_code', 'IA01');
    $savedKuks = $ia01Response?->data_json['kuks'] ?? [];
    $allUnits = $scheme->competencyUnits->sortBy('sequence');

    // Group units according to BNSP standard clusters if 9 units exist
    if ($allUnits->count() >= 9) {
        $kelompok1Units = $allUnits->filter(fn($u) => $u->sequence <= 5);
        $kelompok2Units = $allUnits->filter(fn($u) => $u->sequence == 6);
        $kelompok3Units = $allUnits->filter(fn($u) => $u->sequence >= 7);
    } else {
        $kelompok1Units = $allUnits;
        $kelompok2Units = collect();
        $kelompok3Units = collect();
    }

    $isKompeten = true;
    if (!empty($savedKuks)) {
        foreach ($savedKuks as $item) {
            $val = strtoupper((string)($item['result'] ?? 'K'));
            if (in_array($val, ['BK', 'TIDAK', '0', 'FALSE'])) {
                $isKompeten = false;
                break;
            }
        }
    }
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
            <td style="width: 18%; font-size: 11pt; vertical-align: top;">FR.IA.01.</td>
            <td style="text-align: right; font-size: 9.5pt; text-transform: uppercase;">
                CL - CEKLIS OBSERVASI AKTIVITAS DI TEMPAT KERJA ATAU TEMPAT KERJA SIMULASI
            </td>
        </tr>
    </table>

    {{-- Skema Metadata Table --}}
    <table class="meta-grid" style="border: 1px solid #000; margin-bottom: 4px; border-collapse: collapse; width: 100%; font-size: 8.5pt;">
        <tr>
            <td class="label" style="border-right: 1px solid #000; width: 25%; font-weight: bold; padding: 3px 6px;" rowspan="2">
                Skema Sertifikasi<br><span style="font-size: 7.5pt; font-weight: normal;">(<span style="text-decoration: line-through;">KKNI/Okupasi/</span>Klaster)</span>
            </td>
            <td style="width: 12%; padding: 3px 6px; font-weight: bold;">Judul</td>
            <td class="colon" style="width: 2%; text-align: center;">:</td>
            <td class="value" style="padding: 3px 6px; font-style: italic;">{{ $scheme->name }}</td>
        </tr>
        <tr>
            <td style="padding: 3px 6px; font-weight: bold;">Nomor</td>
            <td class="colon" style="text-align: center;">:</td>
            <td class="value" style="padding: 3px 6px;">{{ $scheme->code }}</td>
        </tr>
        <tr style="border-top: 1px solid #000;">
            <td style="border-right: 1px solid #000; padding: 3px 6px; font-weight: bold;">TUK</td>
            <td colspan="3" style="padding: 3px 6px;">Sewaktu/Tempat Kerja/Mandiri* ({{ $schedule->tuk_name ?? 'Sewaktu' }})</td>
        </tr>
        <tr style="border-top: 1px solid #000;">
            <td style="border-right: 1px solid #000; padding: 3px 6px; font-weight: bold;">Nama Asesor</td>
            <td colspan="3" style="padding: 3px 6px;">{{ $assessor?->full_name ?? $assessor?->name ?? '-' }}</td>
        </tr>
        <tr style="border-top: 1px solid #000;">
            <td style="border-right: 1px solid #000; padding: 3px 6px; font-weight: bold;">Nama Asesi</td>
            <td colspan="3" style="padding: 3px 6px;">{{ $asesi->full_name }}</td>
        </tr>
        <tr style="border-top: 1px solid #000;">
            <td style="border-right: 1px solid #000; padding: 3px 6px; font-weight: bold;">Tanggal</td>
            <td colspan="3" style="padding: 3px 6px;">{{ \Carbon\Carbon::parse($schedule->date ?? now())->translatedFormat('d F Y') }}</td>
        </tr>
    </table>
    <div style="font-size: 7.5pt; margin-bottom: 8px;">*Coret yang tidak perlu</div>

    {{-- Panduan Bagi Asesor Box --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-bottom: 10px; font-size: 8.5pt;">
        <tr>
            <td style="border-bottom: 1px solid #000; padding: 4px 8px; font-weight: bold; background-color: #f8fafc;">
                PANDUAN BAGI ASESOR
            </td>
        </tr>
        <tr>
            <td style="padding: 6px 8px; line-height: 1.45;">
                <ul style="margin: 0 0 0 16px; padding: 0;">
                    <li>Lengkapi nama unit kompetensi, elemen, dan kriteria unjuk kerja sesuai kolom dalam tabel.</li>
                    <li>Isilah standar industri atau tempat kerja</li>
                    <li>Beri tanda centang (&radic;) pada kolom &ldquo;YA&rdquo; jika Anda yakin asesi dapat melakukan/ mendemonstrasikan tugas sesuai KUK, atau centang (&radic;) pada kolom &ldquo;Tidak&rdquo; bila sebaliknya.</li>
                    <li>Penilaian Lanjut diisi bila hasil belum dapat disimpulkan, untuk itu gunakan metode lain sehingga keputusan dapat dibuat.</li>
                    <li>Isilah kolom KUK sesuai dengan Unit Kompetensi/ SKKNI</li>
                </ul>
            </td>
        </tr>
    </table>

    {{-- KELOMPOK PEKERJAAN 1: GENERAL SERVICE --}}
    @if($kelompok1Units->isNotEmpty())
        <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-top: 10px; margin-bottom: 8px; font-size: 8pt; page-break-inside: avoid;">
            <thead>
                <tr style="background-color: #f1f5f9; text-align: center; font-weight: bold;">
                    <th style="border: 1px solid #000; padding: 4px; width: 25%;">Kelompok Pekerjaan 1</th>
                    <th style="border: 1px solid #000; padding: 4px; width: 6%;">No</th>
                    <th style="border: 1px solid #000; padding: 4px; width: 25%;">Kode Unit</th>
                    <th style="border: 1px solid #000; padding: 4px;">Judul Unit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kelompok1Units as $idx => $u)
                <tr>
                    @if($loop->first)
                    <td style="border: 1px solid #000; padding: 4px 6px; text-align: center; font-weight: bold; vertical-align: middle;" rowspan="{{ $kelompok1Units->count() }}">
                        Kelompok Pekerjaan 1<br><span style="font-weight: normal; font-size: 7.5pt;">General Service</span>
                    </td>
                    @endif
                    <td style="border: 1px solid #000; padding: 3px 4px; text-align: center;">{{ $idx + 1 }}.</td>
                    <td style="border: 1px solid #000; padding: 3px 6px; font-family: monospace;">{{ $u->code }}</td>
                    <td style="border: 1px solid #000; padding: 3px 6px;">{{ $u->title }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @foreach($kelompok1Units as $idx => $unit)
            @include('admin.dokumen-muk.partials.ia01-unit', ['unit' => $unit, 'unitNumber' => $idx + 1, 'savedKuks' => $savedKuks])
        @endforeach
    @endif

    {{-- KELOMPOK PEKERJAAN 2: ENGINE --}}
    @if($kelompok2Units->isNotEmpty())
        <div style="page-break-before: always;"></div>
        <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-top: 10px; margin-bottom: 8px; font-size: 8pt; page-break-inside: avoid;">
            <thead>
                <tr style="background-color: #f1f5f9; text-align: center; font-weight: bold;">
                    <th style="border: 1px solid #000; padding: 4px; width: 25%;">Kelompok Pekerjaan 2</th>
                    <th style="border: 1px solid #000; padding: 4px; width: 6%;">No.</th>
                    <th style="border: 1px solid #000; padding: 4px; width: 25%;">Kode Unit</th>
                    <th style="border: 1px solid #000; padding: 4px;">Judul Unit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kelompok2Units as $idx => $u)
                <tr>
                    @if($loop->first)
                    <td style="border: 1px solid #000; padding: 4px 6px; text-align: center; font-weight: bold; vertical-align: middle;" rowspan="{{ $kelompok2Units->count() }}">
                        Kelompok Pekerjaan 2<br><span style="font-weight: normal; font-size: 7.5pt;">Engine</span>
                    </td>
                    @endif
                    <td style="border: 1px solid #000; padding: 3px 4px; text-align: center;">{{ $idx + 1 }}.</td>
                    <td style="border: 1px solid #000; padding: 3px 6px; font-family: monospace;">{{ $u->code }}</td>
                    <td style="border: 1px solid #000; padding: 3px 6px;">{{ $u->title }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @foreach($kelompok2Units as $idx => $unit)
            @include('admin.dokumen-muk.partials.ia01-unit', ['unit' => $unit, 'unitNumber' => $idx + 1, 'savedKuks' => $savedKuks])
        @endforeach
    @endif

    {{-- KELOMPOK PEKERJAAN 3: KELISTRIKAN & ENGINE MANAGEMENT --}}
    @if($kelompok3Units->isNotEmpty())
        <div style="page-break-before: always;"></div>
        <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-top: 10px; margin-bottom: 8px; font-size: 8pt; page-break-inside: avoid;">
            <thead>
                <tr style="background-color: #f1f5f9; text-align: center; font-weight: bold;">
                    <th style="border: 1px solid #000; padding: 4px; width: 25%;">Kelompok Pekerjaan 3</th>
                    <th style="border: 1px solid #000; padding: 4px; width: 6%;">No.</th>
                    <th style="border: 1px solid #000; padding: 4px; width: 25%;">Kode Unit</th>
                    <th style="border: 1px solid #000; padding: 4px;">Judul Unit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kelompok3Units as $idx => $u)
                <tr>
                    @if($loop->first)
                    <td style="border: 1px solid #000; padding: 4px 6px; text-align: center; font-weight: bold; vertical-align: middle;" rowspan="{{ $kelompok3Units->count() }}">
                        Kelompok Pekerjaan 3
                    </td>
                    @endif
                    <td style="border: 1px solid #000; padding: 3px 4px; text-align: center;">{{ $idx + 1 }}.</td>
                    <td style="border: 1px solid #000; padding: 3px 6px; font-family: monospace;">{{ $u->code }}</td>
                    <td style="border: 1px solid #000; padding: 3px 6px;">{{ $u->title }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @foreach($kelompok3Units as $idx => $unit)
            @include('admin.dokumen-muk.partials.ia01-unit', ['unit' => $unit, 'unitNumber' => $idx + 1, 'savedKuks' => $savedKuks])
        @endforeach
    @endif

    {{-- Umpan Balik untuk Asesi Box --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-top: 15px; margin-bottom: 10px; font-size: 8.5pt; page-break-inside: avoid;">
        <tr>
            <td style="padding: 8px 10px; min-height: 48px;">
                <div style="font-weight: bold; margin-bottom: 4px;">Umpan Balik untuk asesi:</div>
                <div style="color: #334155; font-style: italic;">
                    {{ $ia01Response?->assessor_notes ?? 'Asesi telah menunjukkan pemenuhan unjuk kerja praktik sesuai standar KUK SKKNI dan SOP industri yang berlaku.' }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Rekomendasi & Dual Signatures (2 Columns) --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; font-size: 8.5pt; page-break-inside: avoid;">
        <tr>
            {{-- Left: Rekomendasi --}}
            <td style="width: 50%; border-right: 1px solid #000; padding: 8px 10px; vertical-align: top;">
                <div style="font-weight: bold; margin-bottom: 8px;">Rekomendasi:</div>
                <div style="margin-bottom: 8px; display: flex; align-items: flex-start;">
                    <span class="check-box" style="margin-top: 2px;">{!! $isKompeten ? '&radic;' : '&nbsp;' !!}</span>
                    <span style="margin-left: 6px;">
                        Asesi telah memenuhi pencapaian seluruh kriteria unjuk kerja, direkomendasikan <strong>KOMPETEN</strong>
                    </span>
                </div>
                <div style="display: flex; align-items: flex-start;">
                    <span class="check-box" style="margin-top: 2px;">{!! !$isKompeten ? '&radic;' : '&nbsp;' !!}</span>
                    <span style="margin-left: 6px;">
                        Asesi belum memenuhi pencapaian seluruh kriteria unjuk kerja, direkomendasikan <strong>BELUM KOMPETEN</strong>
                        <div style="margin-top: 4px; font-size: 8pt; color: #475569;">
                            pada:<br>
                            Kelompok Pekerjaan: &hellip;<br>
                            Unit: &hellip;&hellip;<br>
                            Elemen: &hellip;&hellip;<br>
                            KUK: &hellip;&hellip;&hellip;
                        </div>
                    </span>
                </div>
            </td>

            {{-- Right: Dual Signatures (Asesi & Asesor) --}}
            <td style="width: 50%; padding: 0; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt;">
                    {{-- Asesi --}}
                    <tr>
                        <td colspan="3" style="padding: 6px 10px 2px 10px; font-weight: bold; border-bottom: 1px solid #000;">
                            Asesi
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 35%; padding: 4px 10px;">Nama</td>
                        <td style="width: 3%;">:</td>
                        <td style="padding: 4px 10px; font-weight: bold;">{{ $asesi->full_name }}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #000;">
                        <td style="padding: 4px 10px; vertical-align: middle;">Tanda tangan/ Tanggal</td>
                        <td style="vertical-align: middle;">:</td>
                        <td style="padding: 4px 10px; vertical-align: middle;">
                            @if($assessment->asesi_signature)
                                <img src="{{ $assessment->asesi_signature }}" style="max-height: 35px; max-width: 110px; vertical-align: middle;" alt="TTD Asesi">
                            @else
                                [Ttd Digital]
                            @endif
                            &nbsp;- {{ \Carbon\Carbon::parse($schedule->date ?? now())->translatedFormat('d/m/Y') }}
                        </td>
                    </tr>

                    {{-- Asesor --}}
                    <tr>
                        <td colspan="3" style="padding: 6px 10px 2px 10px; font-weight: bold; border-bottom: 1px solid #000;">
                            Asesor
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 10px;">Nama</td>
                        <td>:</td>
                        <td style="padding: 4px 10px; font-weight: bold;">{{ $assessor?->full_name ?? $assessor?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 10px;">No. Reg</td>
                        <td>:</td>
                        <td style="padding: 4px 10px; font-family: monospace;">{{ $assessor?->profile?->no_reg_asesor ?? $assessor?->profile?->met_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 10px; vertical-align: middle;">Tanda tangan/ Tanggal</td>
                        <td style="vertical-align: middle;">:</td>
                        <td style="padding: 4px 10px; vertical-align: middle;">
                            @if($assessor?->profile?->signature_path)
                                <img src="{{ asset('storage/' . $assessor->profile->signature_path) }}" style="max-height: 35px; max-width: 110px; vertical-align: middle;" alt="TTD Asesor">
                            @elseif($assessment->assessor_signature)
                                <img src="{{ $assessment->assessor_signature }}" style="max-height: 35px; max-width: 110px; vertical-align: middle;" alt="TTD Asesor">
                            @else
                                [Ttd Digital]
                            @endif
                            &nbsp;- {{ \Carbon\Carbon::parse($schedule->date ?? now())->translatedFormat('d/m/Y') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
@endsection
