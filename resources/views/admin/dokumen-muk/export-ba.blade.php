@extends('layouts.print-muk')

@section('title', 'Berita Acara Pelaksanaan Uji Kompetensi - ' . $schedule->code)
@section('doc-title', 'Berita Acara Pelaksanaan Uji Kompetensi')

@section('content')
<div style="font-size: 10pt; line-height: 1.4;">
    {{-- Header Title --}}
    <div style="text-align: center; margin-bottom: 25px;">
        <h2 style="font-size: 12pt; font-weight: bold; text-decoration: underline; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">
            BERITA ACARA PELAKSANAAN UJI KOMPETENSI
        </h2>
    </div>

    {{-- Opening Paragraph --}}
    <div style="text-align: justify; margin-bottom: 18px;">
        Pada hari ini, Hari/Tanggal : <strong>{{ \Carbon\Carbon::parse($schedule->date)->translatedFormat('l') }} / {{ \Carbon\Carbon::parse($schedule->date)->translatedFormat('d F Y') }}</strong> &nbsp;&nbsp; Waktu : <strong>Pkl. {{ \Carbon\Carbon::parse($schedule->start_time)->format('H.i') }} s/d {{ \Carbon\Carbon::parse($schedule->end_time)->format('H.i') }}</strong> bertempat di <strong>{{ $schedule->tuk_name }}</strong>, telah dilaksanakan proses asesmen Skema Sertifikasi <strong>{{ $scheme->name }}</strong> dengan perincian sebagai berikut :
    </div>

    @php
        $groups = (!empty($assessorGroups) && count($assessorGroups) > 1) ? $assessorGroups : [
            [
                'assessor' => $leadAssessor,
                'assessments' => $assessments,
            ]
        ];
        $totalGroups = count($groups);
    @endphp

    @foreach($groups as $gIdx => $grp)
        @php
            $currentAssessor = $grp['assessor'] ?? $leadAssessor;
            $grpAssessments = $grp['assessments'] ?? collect();
            $grpTotal = $grpAssessments->count();
            $grpKompeten = $grpAssessments->filter(fn($a) => in_array($a->final_result, ['K', 'KOMPETEN'], true) || ($a->final_result === null && $a->status === 'COMPLETED'))->count();
            $grpBelumKompeten = $grpAssessments->filter(fn($a) => in_array($a->final_result, ['BK', 'BELUM_KOMPETEN'], true))->count();
            $grpTotalTerbilang = \App\Services\MukExportService::terbilang($grpTotal);
            $grpKompetenTerbilang = \App\Services\MukExportService::terbilang($grpKompeten);
            $grpBelumKompetenTerbilang = \App\Services\MukExportService::terbilang($grpBelumKompeten);
        @endphp

        <div style="{{ $gIdx > 0 ? 'page-break-before: always; margin-top: 30px;' : '' }}">
            @if($totalGroups > 1 && $currentAssessor)
                <div style="text-align: center; font-size: 9pt; font-weight: bold; color: #475569; margin-bottom: 12px;">
                    (Asesor: {{ $currentAssessor->full_name }})
                </div>
            @endif

            {{-- Summary Statistics --}}
            <div style="margin-bottom: 20px; line-height: 1.8;">
                <table style="width: 100%; border-collapse: collapse; font-size: 10pt;">
                    <tr>
                        <td style="width: 48%;">Jumlah kandidat yang mengikuti</td>
                        <td style="width: 3%; text-align: center;">:</td>
                        <td style="width: 49%;"><strong>{{ $grpTotal }}</strong> ( <em>{{ ucwords($grpTotalTerbilang) }}</em> ) orang</td>
                    </tr>
                    <tr>
                        <td>Jumlah kandidat yang dinyatakan Kompeten</td>
                        <td style="text-align: center;">:</td>
                        <td><strong>{{ $grpKompeten }}</strong> ( <em>{{ ucwords($grpKompetenTerbilang) }}</em> ) orang</td>
                    </tr>
                    <tr>
                        <td>Jumlah kandidat yang dinyatakan Belum Kompeten</td>
                        <td style="text-align: center;">:</td>
                        <td><strong>{{ $grpBelumKompeten }}</strong> ( <em>{{ ucwords($grpBelumKompetenTerbilang) }}</em> ) orang</td>
                    </tr>
                </table>
            </div>

            {{-- Assessment Candidates Table --}}
            <table class="bnsp-table" style="width: 100%; font-size: 9.5pt;">
                <thead>
                    <tr>
                        <th style="width: 5%;">No.</th>
                        <th style="width: 35%;">Nama Peserta</th>
                        <th style="width: 15%;">Hasil Asesmen*)</th>
                        <th style="width: 25%;">Rekomendasi/ Tindak Lanjut</th>
                        <th style="width: 20%;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $rowCount = max(10, count($grpAssessments));
                    @endphp
                    @for($i = 0; $i < $rowCount; $i++)
                        @php
                            $assessment = $grpAssessments[$i] ?? null;
                            if ($assessment) {
                                if ($assessment->final_result === 'TIDAK_HADIR') {
                                    $res = 'Tidak Hadir';
                                    $rekomendasi = $assessment->final_notes ?: 'Peserta Tidak Hadir';
                                    $keterangan = '-';
                                } elseif (in_array($assessment->final_result, ['K', 'KOMPETEN'], true) || ($assessment->final_result === null && $assessment->status === 'COMPLETED')) {
                                    $res = 'K';
                                    $rekomendasi = 'direkomendasikan';
                                    $keterangan = 'Kompeten';
                                } elseif (in_array($assessment->final_result, ['BK', 'BELUM_KOMPETEN'], true)) {
                                    $res = 'BK';
                                    $rekomendasi = 'belum direkomendasikan';
                                    $keterangan = 'belum kompeten';
                                } else {
                                    $res = ($assessment->status === 'IN_PROGRESS') ? '-' : 'BELUM_KOMPETEN';
                                    $rekomendasi = 'belum direkomendasikan';
                                    $keterangan = 'belum kompeten';
                                }
                            } else {
                                $res = '';
                                $rekomendasi = '';
                                $keterangan = '';
                            }
                        @endphp
                        <tr>
                            <td style="text-align: center; height: 26px;">{{ $i + 1 }}</td>
                            <td>{{ $assessment?->asesi->full_name ?? '' }}</td>
                            <td style="text-align: center; font-weight: bold;">
                                @if($res === 'K')
                                    <span style="color: #047857;">K</span>
                                @elseif($res === 'BK')
                                    <span style="color: #b91c1c;">BK</span>
                                @elseif($res === 'Tidak Hadir')
                                    <span style="color: #64748b; font-size: 8pt;">Tidak Hadir</span>
                                @else
                                    {{ $res ?? '' }}
                                @endif
                            </td>
                            <td>
                                {{ $rekomendasi }}
                            </td>
                            <td>
                                {{ $keterangan }}
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>

            {{-- Footnote & Closing text --}}
            <div style="font-size: 9pt; margin-top: 4px; margin-bottom: 14px;">
                <em>Keterangan : *) Diisi dengan K (Kompeten) atau BK (Belum Kompeten)</em>
            </div>

            <div style="text-align: justify; margin-bottom: 24px;">
                Demikian berita acara ini dibuat dengan sebenarnya, untuk digunakan sebagaimana mestinya.
            </div>

            {{-- Signatures Block --}}
            <div class="sig-container" style="page-break-inside: avoid;">
                <div style="text-align: right; margin-bottom: 15px;">
                    {{ $schedule->tuk_city ?? 'Karawang' }}, {{ \Carbon\Carbon::parse($schedule->date)->translatedFormat('d F Y') }}
                </div>
                <div class="sig-grid">
                    <div class="sig-col" style="padding-left: 0;">
                        <div style="font-weight: bold; margin-bottom: 6px;">Asesor</div>
                        <table style="width: 100%; border-collapse: collapse; font-size: 9.5pt;">
                            <tr>
                                <td style="width: 28%;">Nama</td>
                                <td style="width: 4%;">:</td>
                                <td style="width: 68%;"><strong>{{ $currentAssessor?->full_name ?? $currentAssessor?->name ?? '........................................' }}</strong></td>
                            </tr>
                            <tr>
                                <td>No. Reg</td>
                                <td>:</td>
                                <td>{{ $currentAssessor?->assessor_registration_number ?? $currentAssessor?->profile?->met_number ?? '........................................' }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding-top: 6px;">Tanda tangan :</td>
                            </tr>
                        </table>
                        <div class="sig-box" style="margin-top: 8px;">
                            @if($currentAssessor?->profile?->signature_path)
                                <img src="{{ asset('storage/' . $currentAssessor->profile->signature_path) }}" class="sig-img" alt="TTD Asesor">
                            @elseif($grpAssessments->firstWhere('assessor_signature', '!=', null)?->assessor_signature)
                                <img src="{{ $grpAssessments->firstWhere('assessor_signature', '!=', null)->assessor_signature }}" class="sig-img" alt="TTD Asesor">
                            @elseif($assessments->first()?->assessor_signature)
                                <img src="{{ $assessments->first()->assessor_signature }}" class="sig-img" alt="TTD Asesor">
                            @else
                                <div style="height: 50px;"></div>
                            @endif
                            <div style="border-bottom: 1px dotted #000; width: 85%; margin-top: 4px;"></div>
                        </div>
                    </div>

                    <div class="sig-col" style="padding-right: 0;">
                        <div style="font-weight: bold; margin-bottom: 6px;">Penanggungjawab kegiatan</div>
                        <table style="width: 100%; border-collapse: collapse; font-size: 9.5pt;">
                            <tr>
                                <td style="width: 28%;">Nama</td>
                                <td style="width: 4%;">:</td>
                                <td style="width: 68%;"><strong>{{ $schedule->pic_tuk_name ?? '........................................' }}</strong></td>
                            </tr>
                            <tr>
                                <td>Jabatan</td>
                                <td>:</td>
                                <td>{{ $schedule->pic_tuk_position ?? 'Penanggung Jawab TUK' }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="padding-top: 6px;">Tanda tangan :</td>
                            </tr>
                        </table>
                        <div class="sig-box" style="margin-top: 8px;">
                            @if($schedule->pic_tuk_signature)
                                <img src="{{ $schedule->pic_tuk_signature }}" class="sig-img" alt="TTD Penanggung Jawab">
                            @else
                                <div style="height: 50px;"></div>
                            @endif
                            <div style="border-bottom: 1px dotted #000; width: 85%; margin-top: 4px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
