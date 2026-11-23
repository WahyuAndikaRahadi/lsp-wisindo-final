@extends('layouts.print-muk')

@section('title', 'FR.MAPA.01 - ' . $scheme->name)
@section('doc-title', 'FR.MAPA.01 Merencanakan Aktivitas dan Proses Asesmen')

@section('content')
@php
    $mapa = $schedule->mapa01_data ?? [];
    $ak01 = $schedule->ak01_data ?? [];

    $pendekatan = $mapa['pendekatan_asesi'] ?? 'Hasil pelatihan dan / atau pendidikan, dimana Kurikulum dan fasilitas praktek mampu telusur terhadap standar kompetensi';
    $tujuan = $mapa['tujuan_asesmen'] ?? 'Sertifikasi';
    $lingkungan = $mapa['konteks_lingkungan'] ?? 'Tempat kerja simulasi';
    $peluang = $mapa['peluang_bukti'] ?? 'Tersedia';
    $pelaksana = $mapa['pelaksana_asesmen'] ?? 'Lembaga Sertifikasi Profesi Praktisi Otomotif Indonesia (LSP-PrO Indo)';
    $orangRelevan = !empty($mapa['orang_relevan']) ? (is_array($mapa['orang_relevan']) ? implode(', ', $mapa['orang_relevan']) : $mapa['orang_relevan']) : 'Asesor Kompetensi, Master Asesor / Komite Teknis LSP PrO Indo, Penanggung Jawab TUK';
    $tolokUkur = $mapa['standar_tolok_ukur'] ?? 'Standar Kompetensi Kerja Nasional Indonesia (SKKNI) Bidang Otomotif sesuai Lisensi BNSP';

    $evidenceSummary = $ak01['evidence_types_summary'] ?? 'L dan T';
@endphp

<div style="font-size: 8.5pt; line-height: 1.35;">
    {{-- Header Kop --}}
    <div class="header-kop">
        <div class="title-lsp">Lembaga Sertifikasi Profesi Praktisi Otomotif LSP-PrO Indo</div>
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
            <td colspan="3" style="padding-left: 6px;">Sewaktu/Tempat Kerja/Mandiri* (<strong>{{ $schedule->tuk_name }}</strong>)</td>
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
                <div><span class="check-box">{!! str_contains($pendekatan, 'Kurikulum dan fasilitas') ? '&radic;' : '&nbsp;' !!}</span> Hasil pelatihan dan / atau pendidikan, kurikulum dan fasilitas praktek mampu telusur terhadap standar kompetensi</div>
                <div><span class="check-box">{!! str_contains($pendekatan, 'belum berbasis') ? '&radic;' : '&nbsp;' !!}</span> Hasil pelatihan / pendidikan, kurikulum belum berbasis kompetensi</div>
                <div><span class="check-box">{!! str_contains($pendekatan, 'Pekerja berpengalaman') && str_contains($pendekatan, 'mampu telusur') ? '&radic;' : '&nbsp;' !!}</span> Pekerja berpengalaman dari industri yang operasionalnya mampu telusur dengan standar kompetensi</div>
                <div><span class="check-box">{!! str_contains($pendekatan, 'mandiri') || str_contains($pendekatan, 'otodidak') ? '&radic;' : '&nbsp;' !!}</span> Pelatihan / belajar mandiri atau otodidak</div>
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; background: #fafafa;">1.2. Tujuan Asesmen</td>
            <td style="padding: 4px 6px;">
                <span class="check-box">{!! $tujuan === 'Sertifikasi' ? '&radic;' : '&nbsp;' !!}</span> Sertifikasi &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="check-box">{!! $tujuan === 'Pengakuan Kompetensi Terkini (PKT)' ? '&radic;' : '&nbsp;' !!}</span> PKT &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="check-box">{!! $tujuan === 'Rekognisi Pembelajaran Lampau' ? '&radic;' : '&nbsp;' !!}</span> RPL &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="check-box">{!! !in_array($tujuan, ['Sertifikasi', 'Pengakuan Kompetensi Terkini (PKT)', 'Rekognisi Pembelajaran Lampau']) ? '&radic;' : '&nbsp;' !!}</span> Lainnya
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; background: #fafafa;">1.3. Konteks Asesmen</td>
            <td style="padding: 4px 6px;">
                <div><strong>Lingkungan:</strong> <span class="check-box">{!! $lingkungan === 'Tempat kerja nyata' ? '&radic;' : '&nbsp;' !!}</span> Tempat kerja nyata &nbsp;&nbsp; <span class="check-box">{!! $lingkungan === 'Tempat kerja simulasi' ? '&radic;' : '&nbsp;' !!}</span> Tempat kerja simulasi / Workshop TUK</div>
                <div><strong>Peluang mengumpulkan bukti:</strong> <span class="check-box">{!! $peluang === 'Tersedia' ? '&radic;' : '&nbsp;' !!}</span> Tersedia &nbsp;&nbsp; <span class="check-box">{!! $peluang === 'Terbatas' ? '&radic;' : '&nbsp;' !!}</span> Terbatas</div>
                <div><strong>Pelaksana Asesmen:</strong> {{ $pelaksana }}</div>
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; background: #fafafa;">1.4. Orang yang relevan</td>
            <td style="padding: 4px 6px;">
                {{ $orangRelevan }}
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; background: #fafafa;">1.5. Tolok Ukur Asesmen</td>
            <td style="padding: 4px 6px;">
                {{ $tolokUkur }}
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
                    <td style="font-size: 7pt; color: #334155;">
                        CL (Observasi Langsung), PMO (Wawancara), DPT (CBT Tertulis)<br>
                        <strong>Jenis Bukti: {{ $evidenceSummary }}</strong>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- 3. Persyaratan Modifikasi dan Kontekstualisasi --}}
    <div style="background: #f1f5f9; border: 1px solid #000; border-bottom: none; padding: 4px 6px; font-weight: bold; font-size: 9pt; margin-top: 10px;" class="avoid-break">
        3. Mengidentifikasi Persyaratan Modifikasi dan Kontekstualisasi
    </div>
    <table class="bnsp-table" style="margin-top: 0; font-size: 8pt;">
        <tr>
            <td style="width: 38%; font-weight: bold; background: #fafafa;">3.1. a. Karakteristik Asesi:</td>
            <td style="padding: 4px 6px;">
                @if(!empty($mapa['has_karakteristik']) && !empty($mapa['karakteristik_detail']))
                    <strong>Ada</strong> karakteristik khusus Asesi.<br>
                    <em>Jika Ada, tuliskan:</em> {{ $mapa['karakteristik_detail'] }}
                @else
                    <strong>Tidak ada</strong> karakteristik khusus Asesi
                @endif
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; background: #fafafa;">b. Kebutuhan kontekstualisasi terkait tempat kerja:</td>
            <td style="padding: 4px 6px;">
                @if(!empty($mapa['has_kontekstualisasi']) && !empty($mapa['kontekstualisasi_detail']))
                    <strong>Ada</strong> kebutuhan kontekstualisasi.<br>
                    <em>Jika Ada, tuliskan:</em> {{ $mapa['kontekstualisasi_detail'] }}
                @else
                    <strong>Tidak ada</strong> kebutuhan kontekstualisasi
                @endif
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; background: #fafafa;">3.2. Saran yang diberikan oleh paket pelatihan atau pengembang pelatihan:</td>
            <td style="padding: 4px 6px;">
                @if(!empty($mapa['has_saran']) && !empty($mapa['saran_detail']))
                    <strong>Ada</strong> saran.<br>
                    <em>Jika Ada, tuliskan:</em> {{ $mapa['saran_detail'] }}
                @else
                    <strong>Tidak ada</strong> saran
                @endif
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; background: #fafafa;">3.3. Penyesuaian perangkat asesmen terkait kebutuhan kontekstualisasi:</td>
            <td style="padding: 4px 6px;">
                @if(!empty($mapa['has_penyesuaian']) && !empty($mapa['penyesuaian_detail']))
                    <strong>Ada</strong> penyesuaian perangkat.<br>
                    <em>Jika ada tuliskan:</em> {{ $mapa['penyesuaian_detail'] }}
                @else
                    <strong>Tidak ada</strong> penyesuaian perangkat
                @endif
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; background: #fafafa;">3.4. Peluang untuk kegiatan asesmen terintegrasi dan mencatat setiap perubahan yang diperlukan untuk alat asesmen:</td>
            <td style="padding: 4px 6px;">
                @if(!empty($mapa['has_terintegrasi']) && !empty($mapa['integrated_tasks']))
                    <strong>Ada</strong> peluang.<br>
                    <em>Jika Ada, tuliskan:</em>
                    <ul style="margin: 2px 0 0 15px; padding: 0;">
                        @foreach($mapa['integrated_tasks'] as $task)
                            <li>{{ $task }}</li>
                        @endforeach
                    </ul>
                @else
                    <strong>Tidak ada</strong> peluang
                @endif
            </td>
        </tr>
    </table>
    <div style="font-size: 7pt; font-style: italic; color: #475569; margin-top: 2px;">* Coret yang tidak perlu</div>

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
