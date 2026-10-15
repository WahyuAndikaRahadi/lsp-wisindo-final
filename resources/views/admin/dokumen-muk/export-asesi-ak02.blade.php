@extends('layouts.print-muk')

@section('title', 'FR.AK.02 Rekaman Asesmen - ' . $asesi->full_name)
@section('doc-title', 'FR.AK.02 Rekaman Asesmen Kompetensi')

@section('content')
<div style="font-size: 9.5pt; line-height: 1.35;">
    {{-- Header Kop --}}
    <div class="header-kop">
        <div class="title-lsp">Lembaga Sertifikasi Profesi Praktisi Otomotif LSP-PrO Indo</div>
        <div class="doc-code">FR.AK.02. REKAMAN ASESMEN KOMPETENSI</div>
    </div>

    {{-- Meta Grid --}}
    <table class="meta-grid" style="border: 1px solid #000; margin-bottom: 12px;">
        <tr>
            <td class="label" style="border-right: 1px solid #000; width: 25%; font-weight: bold;" rowspan="2">
                Skema Sertifikasi<br><span style="font-size: 8.5pt; font-weight: normal;">(KKNI/Okupasi/Klaster)</span>
            </td>
            <td style="width: 12%; padding-left: 6px;">Judul</td>
            <td class="colon">:</td>
            <td class="value">{{ $scheme->name }}</td>
        </tr>
        <tr>
            <td style="padding-left: 6px;">Nomor</td>
            <td class="colon">:</td>
            <td class="value">{{ $scheme->code }}</td>
        </tr>
        <tr style="border-top: 1px solid #000;">
            <td class="label" style="border-right: 1px solid #000; font-weight: bold;">TUK</td>
            <td colspan="3" style="padding-left: 6px;">Sewaktu/Tempat Kerja/Mandiri* (<strong>{{ $schedule->tuk_name }}</strong>)</td>
        </tr>
        <tr style="border-top: 1px solid #000;">
            <td class="label" style="border-right: 1px solid #000; font-weight: bold;">Nama Asesor</td>
            <td colspan="3" style="padding-left: 6px; font-weight: bold;">{{ $assessor?->full_name ?? $assessor?->name ?? '-' }}</td>
        </tr>
        <tr style="border-top: 1px solid #000;">
            <td class="label" style="border-right: 1px solid #000; font-weight: bold;">Nama Asesi</td>
            <td colspan="3" style="padding-left: 6px; font-weight: bold;">{{ $asesi->full_name }}</td>
        </tr>
        <tr style="border-top: 1px solid #000;">
            <td class="label" style="border-right: 1px solid #000; font-weight: bold;" rowspan="2">Tanggal Asesmen</td>
            <td style="padding-left: 6px;">Mulai</td>
            <td class="colon">:</td>
            <td>{{ \Carbon\Carbon::parse($schedule->date)->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td style="padding-left: 6px;">Selesai</td>
            <td class="colon">:</td>
            <td>{{ \Carbon\Carbon::parse($schedule->date)->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    <div style="font-size: 9pt; margin-bottom: 8px;">
        Beri tanda centang (&radic;) di kolom yang sesuai untuk mencerminkan bukti yang diperoleh untuk menentukan Kompetensi Asesi untuk setiap Unit Kompetensi.
    </div>

    {{-- Matrix Table --}}
    <table class="bnsp-table" style="font-size: 8.5pt;">
        <thead>
            <tr>
                <th style="width: 36%;" rowspan="2">Unit Kompetensi</th>
                <th colspan="8">Bukti yang Diperoleh</th>
            </tr>
            <tr style="font-size: 8pt;">
                <th style="width: 8%;">Observasi Demonstrasi</th>
                <th style="width: 8%;">Portofolio</th>
                <th style="width: 8%;">Pernyataan Pihak Ketiga</th>
                <th style="width: 8%;">Pertanyaan Wawancara</th>
                <th style="width: 8%;">Pertanyaan Lisan</th>
                <th style="width: 8%;">Pertanyaan Tertulis</th>
                <th style="width: 8%;">Proyek Kerja</th>
                <th style="width: 8%;">Lainnya</th>
            </tr>
        </thead>
        <tbody>
            @php
                $ak02Indexed = $ak02Items->keyBy('competency_unit_id');
                $units = $scheme->competencyUnits;
            @endphp
            @forelse($units as $unit)
                @php
                    $item = $ak02Indexed->get($unit->id);
                    $isObs = $item ? $item->is_observasi : true;
                    $isPorto = $item ? $item->is_portofolio : false;
                    $isPihakKetiga = $item ? $item->is_pihak_ketiga : false;
                    $isWawancara = $item ? $item->is_lisan : false;
                    $isLisan = $item ? $item->is_lisan : false;
                    $isTulis = $item ? $item->is_tulis : true;
                    $isProyek = $item ? $item->is_proyek : false;
                    $isLainnya = $item ? $item->is_lainnya : false;
                @endphp
                <tr>
                    <td style="padding: 4px 6px;">
                        <div style="font-weight: bold; font-size: 8pt; color: #1e293b;">{{ $unit->code }}</div>
                        <div>{{ $unit->title }}</div>
                    </td>
                    <td style="text-align: center; vertical-align: middle; font-weight: bold;">{!! $isObs ? '&radic;' : '' !!}</td>
                    <td style="text-align: center; vertical-align: middle; font-weight: bold;">{!! $isPorto ? '&radic;' : '' !!}</td>
                    <td style="text-align: center; vertical-align: middle; font-weight: bold;">{!! $isPihakKetiga ? '&radic;' : '' !!}</td>
                    <td style="text-align: center; vertical-align: middle; font-weight: bold;">{!! $isWawancara ? '&radic;' : '' !!}</td>
                    <td style="text-align: center; vertical-align: middle; font-weight: bold;">{!! $isLisan ? '&radic;' : '' !!}</td>
                    <td style="text-align: center; vertical-align: middle; font-weight: bold;">{!! $isTulis ? '&radic;' : '' !!}</td>
                    <td style="text-align: center; vertical-align: middle; font-weight: bold;">{!! $isProyek ? '&radic;' : '' !!}</td>
                    <td style="text-align: center; vertical-align: middle; font-weight: bold;">{!! $isLainnya ? '&radic;' : '' !!}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 10px;">Unit kompetensi belum diatur pada skema ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Result Section --}}
    <table class="meta-grid" style="border: 1px solid #000; margin-top: 10px;">
        <tr>
            <td style="width: 32%; border-right: 1px solid #000; font-weight: bold; padding: 6px;">
                Rekomendasi hasil asesmen
            </td>
            <td colspan="2" style="padding: 6px; font-size: 10pt;">
                @php
                    $isK = in_array($assessment->final_result, ['K', 'KOMPETEN']) || ($assessment->final_result === null && $assessment->status === 'COMPLETED');
                    $isBK = in_array($assessment->final_result, ['BK', 'BELUM_KOMPETEN']);
                @endphp
                <span class="check-box">{!! $isK ? '&radic;' : '&nbsp;' !!}</span> <strong>Kompeten</strong>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <span class="check-box">{!! $isBK ? '&radic;' : '&nbsp;' !!}</span> <strong>Belum kompeten</strong>
            </td>
        </tr>
        <tr style="border-top: 1px solid #000;">
            <td style="border-right: 1px solid #000; font-weight: bold; padding: 6px; vertical-align: top;">
                Tindak lanjut yang dibutuhkan<br>
                <span style="font-size: 8pt; font-weight: normal; color: #475569;">
                    (Masukkan pekerjaan tambahan dan asesmen yang diperlukan untuk mencapai kompetensi)
                </span>
            </td>
            <td colspan="2" style="padding: 6px; min-height: 40px; vertical-align: top;">
                {{ $assessment->assessor_follow_up ?? ($isK ? 'Tidak ada tindak lanjut, asesi direkomendasikan menerima sertifikat kompetensi.' : 'Perlu dilakukan uji ulang pada unit yang belum kompeten.') }}
            </td>
        </tr>
        <tr style="border-top: 1px solid #000;">
            <td style="border-right: 1px solid #000; font-weight: bold; padding: 6px; vertical-align: top;">
                Komentar/ Observasi oleh asesor
            </td>
            <td colspan="2" style="padding: 6px; min-height: 40px; vertical-align: top;">
                {{ $assessment->assessor_notes ?? 'Asesi mendemonstrasikan seluruh instruksi kerja dengan baik sesuai SOP.' }}
            </td>
        </tr>
    </table>

    {{-- Signatures --}}
    <div class="sig-container avoid-break" style="margin-top: 15px;">
        <div class="sig-grid">
            <div class="sig-col" style="padding-left: 0; width: 50%;">
                <div style="font-weight: bold; margin-bottom: 4px;">Asesi :</div>
                <table style="width: 100%; border-collapse: collapse; font-size: 9pt;">
                    <tr>
                        <td style="width: 32%;">Nama</td>
                        <td style="width: 4%;">:</td>
                        <td style="width: 64%;"><strong>{{ $asesi->full_name }}</strong></td>
                    </tr>
                    <tr>
                        <td style="padding-top: 4px;">Tanda tangan &amp; Tanggal</td>
                        <td style="padding-top: 4px;">:</td>
                        <td style="padding-top: 4px;">{{ \Carbon\Carbon::parse($schedule->date)->translatedFormat('d F Y') }}</td>
                    </tr>
                </table>
                <div class="sig-box">
                    @if($assessment->asesi_signature)
                        <img src="{{ $assessment->asesi_signature }}" class="sig-img" alt="TTD Asesi">
                    @else
                        <div style="height: 45px;"></div>
                    @endif
                    <div style="border-bottom: 1px dotted #000; width: 85%;"></div>
                </div>
            </div>

            <div class="sig-col" style="padding-right: 0; width: 50%;">
                <div style="font-weight: bold; margin-bottom: 4px;">Asesor :</div>
                <table style="width: 100%; border-collapse: collapse; font-size: 9pt;">
                    <tr>
                        <td style="width: 32%;">Nama</td>
                        <td style="width: 4%;">:</td>
                        <td style="width: 64%;"><strong>{{ $assessor?->full_name ?? $assessor?->name ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <td>No. Reg</td>
                        <td>:</td>
                        <td>{{ $assessor?->profile?->met_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding-top: 4px;">Tanda tangan &amp; Tanggal</td>
                        <td style="padding-top: 4px;">:</td>
                        <td style="padding-top: 4px;">{{ \Carbon\Carbon::parse($schedule->date)->translatedFormat('d F Y') }}</td>
                    </tr>
                </table>
                <div class="sig-box">
                    @if($assessor?->profile?->signature_path)
                        <img src="{{ asset('storage/' . $assessor->profile->signature_path) }}" class="sig-img" alt="TTD Asesor">
                    @elseif($assessment->assessor_signature)
                        <img src="{{ $assessment->assessor_signature }}" class="sig-img" alt="TTD Asesor">
                    @else
                        <div style="height: 45px;"></div>
                    @endif
                    <div style="border-bottom: 1px dotted #000; width: 85%;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Lampiran Dokumen List --}}
    <div style="margin-top: 18px; font-size: 8.5pt; line-height: 1.5;" class="avoid-break">
        <strong>LAMPIRAN DOKUMEN:</strong><br>
        1. Dokumen APL 01 peserta<br>
        2. Dokumen APL 02 peserta<br>
        3. Bukti-bukti berkualitas peserta<br>
        4. Tinjauan proses asesi
    </div>
</div>
@endsection
