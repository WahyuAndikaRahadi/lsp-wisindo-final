@extends('layouts.print-muk')

@section('title', 'FR.MAPA.01 - ' . $scheme->name)
@section('doc-title', 'FR.MAPA.01 Merencanakan Aktivitas dan Proses Asesmen')

@section('content')
@php
    $mapaData = $schedule->mapa01_data ?? [];
    $ak01Data = $schedule->ak01_data ?? [];
    $tukType = $ak01Data['tuk_type'] ?? ($schedule->tuk_type ?? 'Sewaktu');
    $evidence = $ak01Data['evidence'] ?? ['Hasil Observasi Langsung', 'Hasil Pertanyaan Tertulis'];
    $evidenceTypes = $ak01Data['evidence_types'] ?? ['L', 'T'];
    $selectedInstruments = $schedule->getEffectiveInstruments();

    $hasPortofolio = in_array('Hasil Verifikasi Portofolio', $evidence, true) || in_array('IA08', $selectedInstruments, true);
    $hasWawancara = in_array('Hasil Pertanyaan Wawancara', $evidence, true) || in_array('IA09', $selectedInstruments, true) || in_array('IA07', $selectedInstruments, true);
    $hasObservasi = in_array('Hasil Observasi Langsung', $evidence, true) || in_array('IA01', $selectedInstruments, true);
    $hasTertulis = in_array('Hasil Pertanyaan Tertulis', $evidence, true) || in_array('IA05', $selectedInstruments, true) || in_array('IA06', $selectedInstruments, true);
    $hasLisan = in_array('Hasil Pertanyaan Lisan', $evidence, true) || in_array('IA03', $selectedInstruments, true);
    $hasTerstruktur = in_array('Hasil Kegiatan Terstruktur', $evidence, true) || in_array('IA02', $selectedInstruments, true);

    $metodeSummaryParts = [];
    if ($hasObservasi) $metodeSummaryParts[] = 'CL (Ceklis Observasi) [L]';
    if ($hasTerstruktur) $metodeSummaryParts[] = 'DIT (Daftar Instruksi Terstruktur) [L]';
    if ($hasPortofolio) $metodeSummaryParts[] = 'CVP (Ceklis Verifikasi Portofolio) [TL]';
    if ($hasTertulis) $metodeSummaryParts[] = 'DPT (Daftar Pertanyaan Tertulis) [T]';
    if ($hasLisan) $metodeSummaryParts[] = 'DPL (Daftar Pertanyaan Lisan) [T]';
    if ($hasWawancara) $metodeSummaryParts[] = 'PW (Pertanyaan Wawancara) [T]';
    if (empty($metodeSummaryParts)) {
        $metodeSummaryParts = ['CL (Ceklis Observasi) [L]', 'DPT (Daftar Pertanyaan Tertulis) [T]'];
    }
    $metodeSummary = implode(', ', $metodeSummaryParts);

    $pPend = strtolower($mapaData['pendekatan_asesi'] ?? '');
    $isOpt1 = str_contains($pPend, 'kurikulum dan fasilitas') || (str_contains($pPend, 'mampu telusur') && !str_contains($pPend, 'belum') && !str_contains($pPend, 'industri')) || empty($pPend);
    $isOpt2 = str_contains($pPend, 'kurikulum belum') && !str_contains($pPend, 'industri');
    $isOpt3 = str_contains($pPend, 'industri') && str_contains($pPend, 'mampu telusur') && !str_contains($pPend, 'belum');
    $isOpt4 = str_contains($pPend, 'industri') && str_contains($pPend, 'belum berbasis');
    $isOpt5 = str_contains($pPend, 'mandiri') || str_contains($pPend, 'otodidak');

    $pTuj = strtolower($mapaData['tujuan_asesmen'] ?? 'sertifikasi');
    $isNyata = ($tukType === 'Tempat Kerja') || str_contains(strtolower($mapaData['konteks_lingkungan'] ?? ''), 'nyata');
    $peluangBukti = strtolower($mapaData['peluang_bukti'] ?? 'tersedia');
    $pelaksanaAsesmen = $mapaData['pelaksana_asesmen'] ?? 'Lembaga Sertifikasi Profesi P3 Wisata Indonesia (LSP Wisindo)';
    $standarTolokUkur = $mapaData['standar_tolok_ukur'] ?? 'Standar Kompetensi Kerja Nasional Indonesia (SKKNI) Bidang Pariwisata & Perhotelan sesuai Lisensi BNSP';
@endphp
<div style="font-size: 8.5pt; line-height: 1.35;">
    {{-- Header Kop --}}
    <div class="header-kop">
        <div class="title-lsp">Lembaga Sertifikasi Profesi P3 Wisata Indonesia (LSP Wisindo)</div>
        <div class="doc-code">FR.MAPA.01. MERENCANAKAN AKTIVITAS DAN PROSES ASESMEN</div>
    </div>

    {{-- Meta Grid --}}
    <table class="meta-grid" style="border: 1px solid #000; margin-bottom: 10px;">
        <tr>
            <td class="label" style="border-right: 1px solid #000; width: 25%; font-weight: bold;" rowspan="2">
                Skema Sertifikasi<br><span style="font-size: 7.5pt; font-weight: normal;">(KKNI/Okupasi/Klaster)</span>
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
            <td colspan="3" style="padding-left: 6px;">
                @if($tukType === 'Sewaktu')
                    <strong>Sewaktu</strong> / <del>Tempat Kerja</del> / <del>Mandiri</del>*
                @elseif($tukType === 'Tempat Kerja')
                    <del>Sewaktu</del> / <strong>Tempat Kerja</strong> / <del>Mandiri</del>*
                @elseif($tukType === 'Mandiri')
                    <del>Sewaktu</del> / <del>Tempat Kerja</del> / <strong>Mandiri</strong>*
                @else
                    <strong>{{ $tukType }}</strong>
                @endif
                (<strong>{{ $schedule->tuk_name }}</strong>)
            </td>
        </tr>
    </table>

    {{-- 1. Pendekatan Asesmen --}}
    <div style="background: #f1f5f9; border: 1px solid #000; border-bottom: none; padding: 4px 6px; font-weight: bold; font-size: 9pt;">
        1. Menentukan Pendekatan Asesmen
    </div>
    <table class="bnsp-table" style="margin-top: 0; font-size: 8pt;">
        <tr>
            <td style="width: 25%; font-weight: bold; background: #fafafa;">1.1. Asesi</td>
            <td style="padding: 4px 6px;">
                <div><span class="check-box">{!! $isOpt1 ? '&radic;' : '&nbsp;' !!}</span> Hasil pelatihan dan / atau pendidikan, kurikulum dan fasilitas praktek mampu telusur terhadap standar kompetensi</div>
                <div><span class="check-box">{!! $isOpt2 ? '&radic;' : '&nbsp;' !!}</span> Hasil pelatihan / pendidikan, kurikulum belum berbasis kompetensi</div>
                <div><span class="check-box">{!! $isOpt3 ? '&radic;' : '&nbsp;' !!}</span> Pekerja berpengalaman dari industri yang operasionalnya mampu telusur dengan standar kompetensi</div>
                <div><span class="check-box">{!! $isOpt4 ? '&radic;' : '&nbsp;' !!}</span> Pekerja berpengalaman dari industri yang operasionalnya belum berbasis kompetensi</div>
                <div><span class="check-box">{!! $isOpt5 ? '&radic;' : '&nbsp;' !!}</span> Pelatihan / belajar mandiri atau otodidak</div>
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; background: #fafafa;">1.2. Tujuan Asesmen</td>
            <td style="padding: 4px 6px;">
                <span class="check-box">{!! str_contains($pTuj, 'sertifikasi') ? '&radic;' : '&nbsp;' !!}</span> Sertifikasi &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="check-box">{!! str_contains($pTuj, 'pkt') || str_contains($pTuj, 'terkini') ? '&radic;' : '&nbsp;' !!}</span> PKT &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="check-box">{!! str_contains($pTuj, 'rpl') || str_contains($pTuj, 'lampau') ? '&radic;' : '&nbsp;' !!}</span> RPL &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="check-box">{!! str_contains($pTuj, 'lainnya') ? '&radic;' : '&nbsp;' !!}</span> Lainnya
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; background: #fafafa;">1.3. Konteks Asesmen</td>
            <td style="padding: 4px 6px;">
                <div><strong>Lingkungan:</strong>
                    <span class="check-box">{!! $isNyata ? '&radic;' : '&nbsp;' !!}</span> Tempat kerja nyata &nbsp;&nbsp;
                    <span class="check-box">{!! ! $isNyata ? '&radic;' : '&nbsp;' !!}</span> Tempat kerja simulasi / Workshop TUK
                </div>
                <div><strong>Peluang mengumpulkan bukti:</strong>
                    <span class="check-box">{!! str_contains($peluangBukti, 'tersedia') ? '&radic;' : '&nbsp;' !!}</span> Tersedia &nbsp;&nbsp;
                    <span class="check-box">{!! str_contains($peluangBukti, 'terbatas') ? '&radic;' : '&nbsp;' !!}</span> Terbatas
                </div>
                <div><strong>Pelaksana Asesmen:</strong> {{ $pelaksanaAsesmen }}</div>
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; background: #fafafa;">1.4. Orang yang relevan</td>
            <td style="padding: 4px 6px;">
                @if(!empty($mapaData['orang_relevan']))
                    {{ implode(', ', (array) $mapaData['orang_relevan']) }}
                @else
                    Asesor Kompetensi, Master Asesor / Komite Teknis LSP PrO Indo, Penanggung Jawab TUK
                @endif
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; background: #fafafa;">1.5. Tolok Ukur Asesmen</td>
            <td style="padding: 4px 6px;">
                {{ $standarTolokUkur }}
            </td>
        </tr>
    </table>

    {{-- 2. Rencana Asesmen --}}
    <div style="background: #f1f5f9; border: 1px solid #000; border-bottom: none; padding: 4px 6px; font-weight: bold; font-size: 9pt; margin-top: 10px;" class="avoid-break">
        2. Rencana Asesmen Berdasarkan Unit Kompetensi
    </div>
    <table class="bnsp-table" style="margin-top: 0; font-size: 7.5pt;">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 25%;">Kode Unit</th>
                <th style="width: 45%;">Judul Unit Kompetensi</th>
                <th style="width: 25%;">Bukti &amp; Metode yang Digunakan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($scheme->competencyUnits as $idx => $u)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td style="font-family: monospace; font-weight: bold;">{{ $u->code }}</td>
                    <td>{{ $u->title }}</td>
                    <td style="font-size: 7pt; color: #334155; font-weight: 600;">
                        {{ $metodeSummary }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Signatures --}}
    <div class="sig-container avoid-break" style="margin-top: 15px;">
        <div class="sig-grid">
            <div class="sig-col" style="padding-left: 0; width: 50%;">
                <div style="font-weight: bold; font-size: 8.5pt;">Penyusun / Asesor :</div>
                <div class="sig-box">
                    @if($leadAssessor?->profile?->signature_path)
                        <img src="{{ asset('storage/' . $leadAssessor->profile->signature_path) }}" class="sig-img" alt="TTD Asesor">
                    @elseif($assessments->first()?->assessor_signature)
                        <img src="{{ $assessments->first()->assessor_signature }}" class="sig-img" alt="TTD Asesor">
                    @else
                        <div style="height: 35px;"></div>
                    @endif
                    <div style="border-bottom: 1px dotted #000; width: 85%;"></div>
                </div>
                <div style="font-size: 8pt;">
                    <strong>{{ $leadAssessor?->full_name ?? $leadAssessor?->name ?? '........................................' }}</strong><br>
                    No. MET : {{ $leadAssessor?->profile?->met_number ?? '-' }}
                </div>
            </div>

            <div class="sig-col" style="padding-right: 0; width: 50%;">
                <div style="font-weight: bold; font-size: 8.5pt;">Divalidasi oleh Komite Skema / Master Asesor :</div>
                <div class="sig-box">
                    <div style="height: 35px;"></div>
                    <div style="border-bottom: 1px dotted #000; width: 85%;"></div>
                </div>
                <div style="font-size: 8pt;">
                    <strong>Komite Skema Sertifikasi LSP PrO Indo</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
