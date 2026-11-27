@extends('layouts.print-muk')

@section('title', 'FR.APL.02 - ' . $asesi->full_name)
@section('doc-title', 'FR.APL.02 Asesmen Mandiri')

@section('content')
<div style="font-size: 9pt; line-height: 1.35;">
    {{-- Header Kop Box --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-bottom: 10px;">
        <tr>
            <td style="width: 110px; text-align: center; vertical-align: middle; border-right: 1px solid #000; padding: 4px;">
                <img src="{{ asset('images/logo-lsp-wisindo.png') }}" style="max-height: 52px; max-width: 95px; object-fit: contain;" alt="Logo LSP Wisindo">
            </td>
            <td style="text-align: center; vertical-align: middle; padding: 6px 12px;">
                <span style="font-size: 11pt; font-family: Tahoma, Arial, sans-serif;">
                    <strong>L</strong>embaga <strong>S</strong>ertifikasi <strong>P</strong>rofesi <strong>P3</strong> <strong>W</strong>isata <strong>I</strong>ndonesia &nbsp;<strong>LSP Wisindo</strong>
                </span>
            </td>
        </tr>
    </table>

    {{-- Title --}}
    <div style="font-weight: bold; font-size: 10pt; margin-bottom: 8px; font-family: Arial, sans-serif;">
        FR.APL.02. ASESMEN MANDIRI
    </div>

    {{-- Meta Grid: Skema Table --}}
    <table class="meta-grid" style="border: 1px solid #000; margin-bottom: 10px; border-collapse: collapse;">
        <tr>
            <td class="label" style="border-right: 1px solid #000; width: 25%; font-weight: bold; padding: 4px 6px;" rowspan="2">
                Skema Sertifikasi<br><span style="font-size: 8pt; font-weight: normal;">(KKNI/Okupasi/Klaster)</span>
            </td>
            <td style="width: 12%; padding: 3px 6px; font-weight: bold;">Judul</td>
            <td class="colon" style="width: 2%; text-align: center;">:</td>
            <td class="value" style="padding: 3px 6px; font-weight: bold;">{{ $scheme->name }}</td>
        </tr>
        <tr>
            <td style="padding: 3px 6px; font-weight: bold;">Nomor</td>
            <td class="colon" style="text-align: center;">:</td>
            <td class="value" style="padding: 3px 6px; font-weight: bold;">{{ $scheme->code }}</td>
        </tr>
    </table>

    {{-- Panduan Asesmen Mandiri Box --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-bottom: 12px; font-size: 8.5pt;">
        <tr>
            <td style="border-bottom: 1px solid #000; padding: 4px 8px; font-weight: bold;">
                PANDUAN ASESMEN MANDIRI
            </td>
        </tr>
        <tr>
            <td style="padding: 6px 8px; line-height: 1.4;">
                <strong>Instruksi:</strong>
                <ul style="margin: 2px 0 0 16px; padding: 0;">
                    <li>Baca setiap pertanyaan di kolom sebelah kiri</li>
                    <li>Beri tanda centang (&radic;) pada kotak jika Anda yakin dapat melakukan tugas yang dijelaskan.</li>
                    <li>Isi kolom di sebelah kanan dengan menuliskan bukti yang relevan anda miliki untuk menunjukkan bahwa anda melakukan pekerjaan</li>
                </ul>
            </td>
        </tr>
    </table>

    {{-- Units Loop --}}
    @php
        $savedApl02 = $assessment->apl02Items->keyBy('kuk_id');
        $proofLabels = [
            'IJAZAH_SMK' => 'Ijazah SMK Otomotif',
            'SURAT_SISWA_AKTIF' => 'Surat Keterangan Siswa Aktif',
            'IJAZAH_SMA_SERTIFIKAT' => 'Ijazah SMA + Sertifikat Pelatihan',
            'PENGALAMAN_KERJA' => 'Surat Pengalaman Kerja',
            'KTP' => 'Fotokopi KTP',
            'PAS_FOTO' => 'Pas Foto 3x4',
        ];
        $candidateBasicProof = $assessment->proofs
            ->whereIn('proof_type', ['IJAZAH_SMK', 'SURAT_SISWA_AKTIF', 'IJAZAH_SMA_SERTIFIKAT', 'PENGALAMAN_KERJA'])
            ->first()?->proof_type;
        $defaultProof = $candidateBasicProof ? ($proofLabels[$candidateBasicProof] ?? 'Ijazah SMK Otomotif') : 'Ijazah SMK Otomotif';
    @endphp

    @foreach($scheme->competencyUnits as $uIdx => $unit)
        <div class="unit-block" style="margin-bottom: 14px; page-break-inside: auto;">
            {{-- 2-row unit header matching master template --}}
            <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; font-size: 8.5pt; margin-bottom: 0;">
                <tr>
                    <td style="width: 25%; font-weight: bold; border-right: 1px solid #000; padding: 4px 6px; vertical-align: middle;" rowspan="2">
                        Unit Kompetensi {{ $uIdx + 1 }}
                    </td>
                    <td style="width: 14%; font-weight: bold; padding: 3px 6px; border-bottom: 1px solid #000;">Kode Unit</td>
                    <td style="width: 2%; text-align: center; border-bottom: 1px solid #000;">:</td>
                    <td style="font-weight: bold; padding: 3px 6px; border-bottom: 1px solid #000;">{{ $unit->code }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; padding: 3px 6px;">Judul Unit</td>
                    <td style="text-align: center;">:</td>
                    <td style="font-weight: bold; padding: 3px 6px;">{{ $unit->title }}</td>
                </tr>
            </table>

            {{-- Elements Table --}}
            <table class="bnsp-table" style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-top: 0; font-size: 8.5pt;">
                <thead>
                    <tr style="background-color: #fff;">
                        <th style="width: 58%; border: 1px solid #000; padding: 4px 6px; text-align: left;">Dapatkah Saya ................?</th>
                        <th style="width: 7%; border: 1px solid #000; padding: 4px 6px; text-align: center;">K</th>
                        <th style="width: 7%; border: 1px solid #000; padding: 4px 6px; text-align: center;">BK</th>
                        <th style="width: 28%; border: 1px solid #000; padding: 4px 6px; text-align: left;">Bukti yang relevan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($unit->elements as $elemIdx => $elem)
                        @php
                            $elemKuks = $elem->kuks;
                            $allK = true;
                            $elemProof = null;
                            foreach ($elemKuks as $k) {
                                $item = $savedApl02->get($k->id);
                                if ($item && !($item->is_competent ?? true)) {
                                    $allK = false;
                                }
                                if ($item && !empty($item->selected_proof_type) && !$elemProof) {
                                    $elemProof = $proofLabels[$item->selected_proof_type] ?? $item->selected_proof_type;
                                }
                            }
                            $proofText = $elemProof ?? $defaultProof;
                        @endphp
                        <tr style="page-break-inside: avoid;">
                            <td style="border: 1px solid #000; padding: 5px 8px; vertical-align: top; line-height: 1.35;">
                                <div style="font-weight: bold;">
                                    {{ $loop->iteration }}. Elemen: {{ $elem->name }}
                                </div>
                                <div style="font-style: italic; margin-top: 2px;">
                                    &bull; Kriteria Unjuk Kerja:
                                </div>
                                <div style="margin-left: 10px; margin-top: 2px;">
                                    @foreach($elemKuks as $kuk)
                                        <div style="margin-bottom: 1px;">
                                            <strong>{{ $kuk->code }}</strong> {{ $kuk->statement }}
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td style="border: 1px solid #000; text-align: center; vertical-align: middle;">
                                <span class="check-box">{!! $allK ? '&radic;' : '&nbsp;' !!}</span>
                            </td>
                            <td style="border: 1px solid #000; text-align: center; vertical-align: middle;">
                                <span class="check-box">{!! !$allK ? '&radic;' : '&nbsp;' !!}</span>
                            </td>
                            <td style="border: 1px solid #000; padding: 4px 6px; vertical-align: middle; font-size: 8pt; color: #1e293b;">
                                {{ $proofText }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    {{-- Assessor Recommendation & Signatures (Matching Master BNSP Table) --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-top: 15px; font-size: 8.5pt; page-break-inside: avoid;">
        <tr>
            <td style="width: 45%; border-right: 1px solid #000; padding: 8px; vertical-align: top; line-height: 1.4;">
                <div style="font-weight: bold; margin-bottom: 6px;">Rekomendasi Asesor :</div>
                <div>Asesmen dapat / <span style="text-decoration: line-through;">tidak dapat</span> dilanjutkan *)</div>
                <div style="margin-top: 12px; font-size: 7.5pt; color: #475569;">
                    *) Coret yang tidak perlu
                </div>
            </td>
            <td style="width: 55%; padding: 0; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt;">
                    <tr>
                        <td style="width: 32%; padding: 4px 6px; border-bottom: 1px solid #000; border-right: 1px solid #000;">Nama</td>
                        <td style="padding: 4px 6px; border-bottom: 1px solid #000; font-weight: bold;">: {{ $asesi->full_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 6px; border-bottom: 1px solid #000; border-right: 1px solid #000; vertical-align: top;">
                            Tanda tangan/<br>Tanggal
                        </td>
                        <td style="padding: 4px 6px; border-bottom: 1px solid #000; vertical-align: middle; text-align: center;">
                            @php
                                $asesiSig = $assessment->asesi_signature ?? $asesi?->profile?->signature_data;
                            @endphp
                            <div style="min-height: 28px; display: flex; align-items: center; justify-content: center;">
                                @if($asesiSig)
                                    <img src="{{ $asesiSig }}" style="max-height: 32px; max-width: 100px; filter: contrast(1.5) brightness(0.85);" alt="TTD Asesi">
                                @else
                                    <span style="color: #64748b; font-style: italic; font-size: 8pt;">[Tanda Tangan Digital]</span>
                                @endif
                            </div>
                            <div style="font-size: 8pt; color: #334155; margin-top: 2px;">
                                {{ \Carbon\Carbon::parse($assessment->registered_at ?? ($schedule?->date ?? now()))->translatedFormat('d F Y') }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding: 4px 6px; font-weight: bold; border-bottom: 1px solid #000;">
                            Ditinjau Oleh Asesor :
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 6px; border-bottom: 1px solid #000; border-right: 1px solid #000;">Nama :</td>
                        <td style="padding: 4px 6px; border-bottom: 1px solid #000; font-weight: bold;">: {{ $assessor?->full_name ?? $assessor?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 6px; border-bottom: 1px solid #000; border-right: 1px solid #000;">No. Reg:</td>
                        <td style="padding: 4px 6px; border-bottom: 1px solid #000; font-weight: bold;">: {{ $assessor?->profile?->no_reg ?? ($assessor?->profile?->met_number ?? ($assessor?->assessor_registration_number ?? 'MET.000.001234 2022')) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 6px; border-right: 1px solid #000; vertical-align: top;">
                            Tanda tangan/<br>Tanggal
                        </td>
                        <td style="padding: 4px 6px; vertical-align: middle; text-align: center;">
                            @php
                                $asesorSig = $assessment->assessor_signature ?? $assessor?->profile?->signature_data;
                            @endphp
                            <div style="min-height: 28px; display: flex; align-items: center; justify-content: center;">
                                @if($asesorSig)
                                    <img src="{{ $asesorSig }}" style="max-height: 32px; max-width: 100px; filter: contrast(1.5) brightness(0.85);" alt="TTD Asesor">
                                @elseif($assessor?->profile?->signature_path)
                                    <img src="{{ asset('storage/' . $assessor->profile->signature_path) }}" style="max-height: 32px; max-width: 100px; filter: contrast(1.5) brightness(0.85);" alt="TTD Asesor">
                                @else
                                    <span style="color: #64748b; font-style: italic; font-size: 8pt;">[Tanda Tangan Digital]</span>
                                @endif
                            </div>
                            <div style="font-size: 8pt; color: #334155; margin-top: 2px;">
                                {{ \Carbon\Carbon::parse($assessment->assessor_signed_at ?? ($schedule?->date ?? now()))->translatedFormat('d F Y') }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
@endsection
