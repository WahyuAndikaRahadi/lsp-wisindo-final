@extends('layouts.print-muk')

@section('title', 'Surat Tugas Asesor - ' . ($schedule->surat_tugas_number ?: $schedule->name))
@section('doc-title', 'Surat Tugas Asesor')

@section('content')
<div style="font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; color: #000; line-height: 1.45; font-size: 10.5pt;">
    {{-- Kop Surat Banner --}}
    <div style="margin-bottom: 8px; text-align: center;">
        @if(file_exists(public_path('images/kop-surat-lsp-proindo.png')))
            <img src="{{ asset('images/kop-surat-lsp-proindo.png') }}" alt="Kop Surat LSP PrO Indo" style="width: 100%; max-height: 120px; object-fit: contain;">
        @else
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 6px;">
                <tr>
                    <td style="width: 90px; text-align: center; vertical-align: middle;">
                        <img src="{{ asset('images/logo-lsp-proindo.png') }}" style="max-height: 65px; max-width: 90px; object-fit: contain;" alt="Logo LSP">
                    </td>
                    <td style="text-align: center; vertical-align: middle; padding: 0 10px;">
                        <div style="font-size: 13pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">LEMBAGA SERTIFIKASI PROFESI</div>
                        <div style="font-size: 11pt; font-weight: bold; color: #1e3a8a;">PRAKTISI OTOMOTIF INDONESIA (LSP P3 PrO INDO)</div>
                        <div style="font-size: 8pt; color: #334155; margin-top: 2px;">
                            Ruko Blessing Center, Jl. Siput Raya No 38 Kayuringin Jaya, Kota Bekasi, Jawa Barat 17144<br>
                            Telp: 081285577080 | Email: lsp.proindo@gmail.com | Website: lspproindo.id
                        </div>
                    </td>
                </tr>
            </table>
            <div style="border-bottom: 2px solid #000; margin-top: 4px;"></div>
            <div style="border-bottom: 1px solid #000; margin-top: 1.5px; margin-bottom: 14px;"></div>
        @endif
    </div>

    {{-- Title Surat --}}
    <div style="text-align: center; margin-top: 16px; margin-bottom: 18px;">
        <div style="font-size: 13pt; font-weight: bold; text-decoration: underline; letter-spacing: 0.5px;">SURAT TUGAS ASESOR</div>
        <div style="font-size: 10.5pt; font-weight: bold; margin-top: 3px;">
            Nomor : {{ $schedule->surat_tugas_number ?: '---/ST/PRO-INDO/INT/' . date('m/Y') }}
        </div>
    </div>

    {{-- Pembuka --}}
    <p style="text-align: justify; margin-bottom: 12px;">
        Dalam rangka pelaksanaan uji kompetensi di Lembaga Sertifikasi Profesi Pihak Ketiga Praktisi Otomotif Indonesia (LSP P3 PrO Indo) yang akan diselenggarakan:
    </p>

    {{-- Jadwal & Lokasi Pelaksanaan --}}
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 10.5pt;">
        <tr>
            <td style="width: 25%; padding: 3px 0; vertical-align: top;">Hari, Tanggal</td>
            <td style="width: 3%; padding: 3px 0; vertical-align: top; text-align: center;">:</td>
            <td style="width: 72%; padding: 3px 0; vertical-align: top; font-weight: bold;">
                {{ $schedule->formatted_date_range }}
            </td>
        </tr>
        <tr>
            <td style="padding: 3px 0; vertical-align: top;">Waktu</td>
            <td style="padding: 3px 0; vertical-align: top; text-align: center;">:</td>
            <td style="padding: 3px 0; vertical-align: top;">
                {{ $schedule->formatted_time_range }}
            </td>
        </tr>
        <tr>
            <td style="padding: 3px 0; vertical-align: top;">Nama TUK</td>
            <td style="padding: 3px 0; vertical-align: top; text-align: center;">:</td>
            <td style="padding: 3px 0; vertical-align: top; font-weight: bold;">
                {{ $schedule->tuk_name }}
            </td>
        </tr>
        <tr>
            <td style="padding: 3px 0; vertical-align: top;">Alamat TUK</td>
            <td style="padding: 3px 0; vertical-align: top; text-align: center;">:</td>
            <td style="padding: 3px 0; vertical-align: top;">
                {{ $schedule->effective_tuk_address }}
            </td>
        </tr>
        <tr>
            <td style="padding: 3px 0; vertical-align: top;">Skema Sertifikasi</td>
            <td style="padding: 3px 0; vertical-align: top; text-align: center;">:</td>
            <td style="padding: 3px 0; vertical-align: top; font-weight: bold;">
                {{ $schedule->scheme?->name }} ({{ $schedule->scheme?->code }})
            </td>
        </tr>
        <tr>
            <td style="padding: 3px 0; vertical-align: top;">Jumlah Peserta</td>
            <td style="padding: 3px 0; vertical-align: top; text-align: center;">:</td>
            <td style="padding: 3px 0; vertical-align: top;">
                {{ $schedule->effective_candidate_count }} siswa
            </td>
        </tr>
    </table>

    {{-- Penugasan Asesor --}}
    <p style="margin-bottom: 8px;">Saya menugaskan:</p>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 10.5pt;">
        <tr>
            <td style="width: 25%; padding: 3px 0; vertical-align: top;">Nama</td>
            <td style="width: 3%; padding: 3px 0; vertical-align: top; text-align: center;">:</td>
            <td style="width: 72%; padding: 3px 0; vertical-align: top;">
                @if($assessors->count() > 0)
                    <table style="width: 100%; border-collapse: collapse;">
                        @foreach($assessors as $idx => $asesor)
                            <tr>
                                <td style="width: 55%; padding: 2px 0; font-weight: bold; vertical-align: top;">
                                    {{ $idx + 1 }}. {{ $asesor->full_name ?? $asesor->name }}
                                </td>
                                <td style="width: 45%; padding: 2px 0; vertical-align: top;">
                                    No. Reg MET : <span style="font-family: monospace; font-size: 10pt;">{{ $asesor->assessor_registration_number ?: '-' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                @else
                    <span style="color: #64748b; font-style: italic;">(Belum ada asesor yang ditugaskan)</span>
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding: 4px 0; vertical-align: top;">Jabatan</td>
            <td style="padding: 4px 0; vertical-align: top; text-align: center;">:</td>
            <td style="padding: 4px 0; vertical-align: top; font-weight: bold;">
                Asesor Kompetensi BNSP
            </td>
        </tr>
        <tr>
            <td style="padding: 4px 0; vertical-align: top;">Untuk</td>
            <td style="padding: 4px 0; vertical-align: top; text-align: center;">:</td>
            <td style="padding: 4px 0; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 20px; vertical-align: top; padding: 2px 0;">a.</td>
                        <td style="vertical-align: top; padding: 2px 0; text-align: justify;">
                            Merencanakan dan mengorganisasikan, melaksanakan asesmen dan merekomendasikan hasil asesmen.
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding: 2px 0;">b.</td>
                        <td style="vertical-align: top; padding: 2px 0; text-align: justify;">
                            Melaporkan pelaksanaan tugas ini kepada Ketua Lembaga Sertifikasi Profesi Pihak Ketiga Praktisi Otomotif Indonesia (LSP P3 PrO Indo).
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding: 2px 0;">c.</td>
                        <td style="vertical-align: top; padding: 2px 0; text-align: justify;">
                            Melaksanakan tugas ini dengan penuh rasa tanggung jawab.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Penutup --}}
    <p style="text-align: justify; margin-bottom: 24px;">
        Demikian surat tugas ini disampaikan, atas perhatian dan kehadirannya saya sampaikan terimakasih.
    </p>

    {{-- Tanda Tangan Direktur --}}
    <div style="width: 100%; margin-top: 25px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 55%;"></td>
                <td style="width: 45%; text-align: left;">
                    <div>{{ $schedule->letter_city ?? 'Bekasi' }}, {{ $schedule->formatted_surat_tugas_date }}</div>
                    <div style="font-weight: bold; margin-top: 2px;">Direktur LSP P3 PrO Indo</div>
                    <div style="height: 75px;">
                        {{-- Ruang stempel & ttd --}}
                    </div>
                    <div style="font-weight: bold; text-decoration: underline; letter-spacing: 0.5px;">
                        {{ $schedule->director_name ?? 'RIDWAN' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection
