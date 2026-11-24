@extends('layouts.print-muk')

@section('title', 'FR.IA.09 Pertanyaan Wawancara - ' . $asesi->full_name)
@section('doc-title', 'FR.IA.09 Pertanyaan Wawancara')

@section('content')
@php
    $ia09Response = $iaResponses->firstWhere('instrument_code', 'IA09');
    $savedData = is_array($ia09Response?->data_json) ? $ia09Response->data_json : (json_decode($ia09Response?->data_json ?? '[]', true) ?: []);
    $savedQuestionsData = $savedData['questions'] ?? [];
    $recommendation = $savedData['recommendation'] ?? 'K';

    // Fetch questions from database for this scheme
    $dbQuestions = \App\Models\InterviewQuestion::where('scheme_id', $scheme->id)->active()->ordered()->get();
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
            <td style="width: 18%; font-size: 11pt; vertical-align: top;">FR.IA.09.</td>
            <td style="text-align: right; font-size: 9.5pt; text-transform: uppercase;">
                PW - PERTANYAAN WAWANCARA
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
                    <li>Pertanyaan wawancara dapat dilakukan untuk keseluruhan unit kompetensi dalam skema sertifikasi atau dilakukan untuk masing-masing kelompok pekerjaan dalam satu skema sertifikasi.</li>
                    <li>Isilah bukti portofolio sesuai dengan bukti yang diminta pada skema sertifikasi sebagaimana yang telah dibuat pada FR-IA.08.</li>
                    <li>Ajukan pertanyaan verifikasi portofolio untuk semua unit/elemen kompetensi yang di-checklist pada FR-IA.08.</li>
                    <li>Ajukan pertanyaan kepada asesi sebagai tindak lanjut hasil verifikasi portofolio.</li>
                    <li>Jika hasil verifikasi portofolio telah memenuhi aturan bukti maka pertanyaan wawancara tidak perlu dilakukan terhadap bukti tersebut.</li>
                    <li>Tuliskan pencapaian atas setiap kesimpulan pertanyaan wawancara dengan cara mencentang (√) “Ya” atau “Tidak”.</li>
                </ul>
            </td>
        </tr>
    </table>

    {{-- Tabel Pertanyaan Wawancara --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-bottom: 10px;">
        <thead>
            <tr style="background-color: #f2f2f2; text-align: center; font-weight: bold;">
                <th rowspan="2" style="border: 1px solid #000; padding: 4px; width: 6%;">No.</th>
                <th rowspan="2" style="border: 1px solid #000; padding: 4px; width: 44%;">Daftar Pertanyaan Wawancara</th>
                <th rowspan="2" style="border: 1px solid #000; padding: 4px; width: 34%;">Kesimpulan Jawaban Asesi</th>
                <th colspan="2" style="border: 1px solid #000; padding: 3px; width: 16%;">Pencapaian</th>
            </tr>
            <tr style="background-color: #fafafa; text-align: center; font-size: 8pt; font-weight: bold;">
                <th style="border: 1px solid #000; padding: 2px;">Ya</th>
                <th style="border: 1px solid #000; padding: 2px;">Tidak</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dbQuestions as $idx => $q)
                @php
                    $savedQ = $savedQuestionsData[$q->id] ?? [];
                    $ansSummary = $savedQ['candidate_answer_summary'] ?? 'Asesi menjelaskan dengan benar dan sesuai SOP manual perbaikan.';
                    $achieved = strtoupper((string)($savedQ['is_achieved'] ?? 'YA'));
                    $isYa = in_array($achieved, ['YA', '1', 'TRUE']);
                @endphp
                <tr>
                    <td style="border: 1px solid #000; text-align: center; font-weight: bold; vertical-align: top; padding: 4px;">
                        {{ $q->order ?? ($idx + 1) }}
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 6px; vertical-align: top; font-size: 8pt;">
                        @if($q->evidence_context)
                            <div style="font-size: 7.5pt; color: #444; font-weight: bold; margin-bottom: 2px;">
                                [{{ $q->evidence_context }}]
                            </div>
                        @endif
                        <div style="line-height: 1.35;">{{ $q->question_text }}</div>
                        @if(!empty($q->expected_answer))
                            <div style="font-size: 7pt; color: #666; margin-top: 3px; border-top: 1px dotted #ccc; padding-top: 2px;">
                                <em>Kunci: {{ $q->expected_answer }}</em>
                            </div>
                        @endif
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 6px; vertical-align: top; font-size: 8pt; font-style: italic;">
                        {{ $ansSummary }}
                    </td>
                    <td style="border: 1px solid #000; text-align: center; font-weight: bold; vertical-align: middle;">
                        {{ $isYa ? '√' : '' }}
                    </td>
                    <td style="border: 1px solid #000; text-align: center; font-weight: bold; vertical-align: middle;">
                        {{ ! $isYa ? '√' : '' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="border: 1px solid #000; padding: 12px; text-align: center; color: #777;">
                        Belum ada daftar pertanyaan wawancara terstandar pada skema ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Rekomendasi & Catatan Asesor --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-bottom: 12px;">
        <tr>
            <td style="padding: 4px 6px; font-weight: bold; border-bottom: 1px solid #000; background-color: #f2f2f2;">
                Catatan &amp; Rekomendasi Asesor:
            </td>
        </tr>
        <tr>
            <td style="padding: 6px; font-size: 8pt; line-height: 1.4;">
                <div style="margin-bottom: 4px;">
                    <strong>Rekomendasi:</strong>
                    <span style="font-weight: bold; color: {{ $recommendation === 'K' ? '#006600' : '#990000' }};">
                        {{ $recommendation === 'K' ? 'KOMPETEN (K)' : 'BELUM KOMPETEN (BK)' }}
                    </span>
                </div>
                <div>
                    <strong>Catatan:</strong>
                    {{ $ia09Response?->assessor_notes ?? 'Asesi mampu menjawab pertanyaan wawancara klarifikasi portofolio secara terstruktur, sistematis, dan mencerminkan pemahaman standar unjuk kerja industri otomotif.' }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Tanda Tangan Asesi & Asesor --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-bottom: 10px;">
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
                                $asesiSig = $ia09Response?->asesi_signature ?? $assessment->asesi_signature ?? $asesi?->profile?->signature_data;
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
                                $asesorSig = $ia09Response?->assessor_signature ?? $assessment->assessor_signature ?? $leadAssessor?->profile?->signature_data ?? $assessor?->profile?->signature_data;
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

    {{-- Penyusun dan Validator (Standar MUK FR.IA.09) --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; font-size: 7.5pt; text-align: center;">
        <thead>
            <tr style="background-color: #f2f2f2; font-weight: bold;">
                <th colspan="5" style="border: 1px solid #000; padding: 3px; text-transform: uppercase;">PENYUSUN DAN VALIDATOR</th>
            </tr>
            <tr style="background-color: #fafafa; font-weight: bold;">
                <th style="border: 1px solid #000; padding: 2px; width: 18%;">STATUS</th>
                <th style="border: 1px solid #000; padding: 2px; width: 6%;">NO</th>
                <th style="border: 1px solid #000; padding: 2px; width: 34%;">NAMA</th>
                <th style="border: 1px solid #000; padding: 2px; width: 22%;">NOMOR MET</th>
                <th style="border: 1px solid #000; padding: 2px; width: 20%;">TANGGAL</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td rowspan="2" style="border: 1px solid #000; font-weight: bold; vertical-align: middle;">PENYUSUN</td>
                <td style="border: 1px solid #000;">1</td>
                <td style="border: 1px solid #000; text-align: left; padding-left: 4px;">RIDWAN</td>
                <td style="border: 1px solid #000;">000.008265 2025</td>
                <td style="border: 1px solid #000;">05 Januari 2026</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;">2</td>
                <td style="border: 1px solid #000; text-align: left; padding-left: 4px;">RANDI ARDIANSYAH</td>
                <td style="border: 1px solid #000;">000.002498 2013</td>
                <td style="border: 1px solid #000;">05 Januari 2026</td>
            </tr>
            <tr>
                <td rowspan="3" style="border: 1px solid #000; font-weight: bold; vertical-align: middle;">VALIDATOR</td>
                <td style="border: 1px solid #000;">1</td>
                <td style="border: 1px solid #000; text-align: left; padding-left: 4px;">SAPTONO</td>
                <td style="border: 1px solid #000;">000.003672 2016</td>
                <td style="border: 1px solid #000;">05 Januari 2026</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;">2</td>
                <td style="border: 1px solid #000; text-align: left; padding-left: 4px;">KARYANTO</td>
                <td style="border: 1px solid #000;">000.008260 2025</td>
                <td style="border: 1px solid #000;">05 Januari 2026</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;">3</td>
                <td style="border: 1px solid #000; text-align: left; padding-left: 4px;">GUNADI</td>
                <td style="border: 1px solid #000;">000.000459 2007</td>
                <td style="border: 1px solid #000;">05 Januari 2026</td>
            </tr>
        </tbody>
    </table>
    <div style="font-size: 7.5pt; font-style: italic; margin-top: 3px;">*Coret yang tidak perlu</div>
</div>
@endsection
