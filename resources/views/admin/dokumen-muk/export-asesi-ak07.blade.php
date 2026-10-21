@extends('layouts.print-muk')

@section('title', 'FR.AK.07 Penyesuaian yang Wajar - ' . $asesi->full_name)
@section('doc-title', 'FR.AK.07 Ceklis Penyesuaian yang Wajar dan Beralasan')

@section('content')
<div style="font-size: 8.5pt; line-height: 1.35; font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif;">
    {{-- Header Kop Box --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-bottom: 10px;">
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

    {{-- Title --}}
    <table style="width: 100%; margin-bottom: 8px; font-weight: bold; font-size: 10.5pt;">
        <tr>
            <td style="width: 25%;">FR.AK.07.</td>
            <td style="text-align: right;">CEKLIS PENYESUAIAN YANG WAJAR dan BERALASAN</td>
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
            <td style="border-bottom: 1px solid #000; padding: 4px 8px; font-weight: bold;">
                PANDUAN BAGI ASESOR
            </td>
        </tr>
        <tr>
            <td style="padding: 6px 8px; line-height: 1.45;">
                <ul style="margin: 0 0 0 16px; padding: 0;">
                    <li>Formulir ini dapat digunakan (sebelum pra asesmen, saat pelaksanaan pra asesmen, setelah pra asesmen)* jika ada asesi yang mempunyai keterbatasan sesuai karakteristik yang dimilikinya sehinga diperlukan penyesuaian yang wajar dan beralasan atau jika rencana asesmen dan perangkat asesmen tidak sesuai dengan acuan pembanding, potensi asesi dan konteks asesi atau ika asesi merasa keletihan, sakit, serta jika kondisi alam, listrik padam,……..</li>
                    <li>Coretlah pada tanda * yang tidak sesuai.</li>
                    <li>Berilah tanda &radic; pada kotak &lsquo;&square;&rsquo; pada kolom potensi asesi</li>
                    <li>Jika jawaban Ya pada tanda ** berilah tanda &radic; di kotak &lsquo;&square;&rsquo; kolom Ya dan selanjutanya pada kolom keterangan berilah tanda &radic; di kotak &lsquo;&square;&rsquo; yang tersedia, pilihan boleh lebih dari satu.</li>
                    <li>atau jika jawaban Tidak pada tanda ** berilah tanda &radic; di kotak &lsquo;&square;&rsquo; kolom tidak, dan tidak perlu memberi tanda tanda &radic; di kotak &lsquo;&square;&rsquo; kolom keterangan.</li>
                </ul>
            </td>
        </tr>
    </table>

    {{-- Potensi Asesi Table --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-bottom: 10px; font-size: 8.5pt;">
        <tr>
            <td style="width: 25%; font-weight: bold; border-right: 1px solid #000; padding: 6px; vertical-align: middle; text-align: center;" rowspan="5">
                Potensi Asesi
            </td>
            <td style="width: 5%; text-align: center; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 3px;">
                <span class="check-box">&radic;</span>
            </td>
            <td style="border-bottom: 1px solid #000; padding: 4px 6px;">
                Hasil pelatihan dan / atau pendidikan, dimana Kurikulum dan fasilitaspraktek mampu telusur terhadap standar kompetensi
            </td>
        </tr>
        <tr>
            <td style="text-align: center; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 3px;">
                <span class="check-box">&nbsp;</span>
            </td>
            <td style="border-bottom: 1px solid #000; padding: 4px 6px;">
                Hasil pelatihan dan / atau pendidikan, dimana kurikulum belum berbasiskompetensi.
            </td>
        </tr>
        <tr>
            <td style="text-align: center; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 3px;">
                <span class="check-box">&nbsp;</span>
            </td>
            <td style="border-bottom: 1px solid #000; padding: 4px 6px;">
                Pekerja berpengalaman, dimana berasal dari industri/tempat kerja yangdalam operasionalnya mampu telusur dengan standar kompetensi
            </td>
        </tr>
        <tr>
            <td style="text-align: center; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 3px;">
                <span class="check-box">&nbsp;</span>
            </td>
            <td style="border-bottom: 1px solid #000; padding: 4px 6px;">
                Pekerja berpengalaman, dimana berasal dari industri/tempat kerja yangdalam operasionalnya belum berbasis kompetensi.
            </td>
        </tr>
        <tr>
            <td style="text-align: center; border-right: 1px solid #000; padding: 3px;">
                <span class="check-box">&nbsp;</span>
            </td>
            <td style="padding: 4px 6px;">
                Pelatihan / belajar mandiri atau otodidak.
            </td>
        </tr>
    </table>

    {{-- Checklist Table (All 8 BNSP Standard Items) --}}
    @php
        $ak07 = $assessment->ak07Items->first();
        $hasAdjustment = !empty($ak07);
    @endphp

    <table class="bnsp-table" style="width: 100%; border: 1px solid #000; border-collapse: collapse; font-size: 8.5pt;">
        <thead>
            <tr>
                <th style="width: 5%; border: 1px solid #000; padding: 4px; text-align: center;" rowspan="2">No</th>
                <th style="width: 38%; border: 1px solid #000; padding: 4px; text-align: left;" rowspan="2">
                    Mengidentifikasi Persyaratan Modifikasi dan Kontekstualisasi (karakteristik asesi):
                </th>
                <th style="width: 16%; border: 1px solid #000; padding: 4px; text-align: center;" colspan="2">
                    Diperlukan penyesuaian)**
                </th>
                <th style="width: 41%; border: 1px solid #000; padding: 4px; text-align: left;" colspan="2">
                    Keterangan
                </th>
            </tr>
            <tr>
                <th style="width: 8%; border: 1px solid #000; padding: 2px; text-align: center;">Ya</th>
                <th style="width: 8%; border: 1px solid #000; padding: 2px; text-align: center;">Tidak</th>
                <th style="width: 6%; border: 1px solid #000; padding: 2px; text-align: center;">&nbsp;</th>
                <th style="border: 1px solid #000; padding: 2px 4px; text-align: left;">Bentuk Penyesuaian</th>
            </tr>
        </thead>
        <tbody>
            {{-- Item 1 --}}
            <tr style="page-break-inside: avoid;">
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="6">1</td>
                <td style="border: 1px solid #000; vertical-align: top; padding: 4px 6px;" rowspan="6">
                    Keterbatasan asesi terhadap persyaratan bahasa, literasi, numerasi.
                </td>
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="6">
                    <span class="check-box">&nbsp;</span>
                </td>
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="6">
                    <span class="check-box">&radic;</span>
                </td>
                <td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td>
                <td style="border: 1px solid #000; padding: 3px 5px;">Memerlukan dukungan pembaca, penerjemah, pelayan, penulis. untuk merekam jawaban asesi.</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td>
                <td style="border: 1px solid #000; padding: 3px 5px;">Melakukan asesmen verbal (gunakan pertanyaan lisan/pertanyaan wawancara) dengan dilengkapi gambar diagram dan bentuk-benbentuk visual.</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td>
                <td style="border: 1px solid #000; padding: 3px 5px;">Menggunakan Hasil produksi</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td>
                <td style="border: 1px solid #000; padding: 3px 5px;">Mengunakan Ceklis observasi/demonstrasi.</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td>
                <td style="border: 1px solid #000; padding: 3px 5px;">Menggunakan daftar instruksi terstruktur.</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td>
                <td style="border: 1px solid #000; padding: 3px 5px;">…………………………………………………………</td>
            </tr>

            {{-- Item 2 --}}
            <tr style="page-break-inside: avoid;">
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="3">2</td>
                <td style="border: 1px solid #000; vertical-align: top; padding: 4px 6px;" rowspan="3">
                    Penyediaan dukungan pembaca, penerjemah, pelayan, penulis.
                </td>
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="3">
                    <span class="check-box">&nbsp;</span>
                </td>
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="3">
                    <span class="check-box">&radic;</span>
                </td>
                <td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td>
                <td style="border: 1px solid #000; padding: 3px 5px;">Menggunakan pertanyaan lisan dengan dilengkapi gambar diagram dan bentuk-bentuk visual.</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td>
                <td style="border: 1px solid #000; padding: 3px 5px;">Menggunakan pertanyaan wawancara dengan dilengkapi gambar diagram dan bentuk-bentuk visual.</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td>
                <td style="border: 1px solid #000; padding: 3px 5px;">…………………………………………………………</td>
            </tr>

            {{-- Item 3 --}}
            <tr style="page-break-inside: avoid;">
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="8">3</td>
                <td style="border: 1px solid #000; vertical-align: top; padding: 4px 6px;" rowspan="8">
                    Penggunaan teknologi adaptif atau peralatan khusus. (Tidak dapat menggunakan teknologi adaptif (misal: mengoperasikan komputer dan printer, peralatan digital dsb).
                </td>
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="8">
                    <span class="check-box">&nbsp;</span>
                </td>
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="8">
                    <span class="check-box">&radic;</span>
                </td>
                <td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td>
                <td style="border: 1px solid #000; padding: 3px 5px;">Ceklis observasi/demonstrasi Demonstrasi.</td>
            </tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Pertanyaan lisan</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Pertanyaan tertulis.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Pertanyaan wawancara.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Daftar instruksi terstruktur.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Ceklis verifikasi portofolio.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Menggunakan dukungan operator komputer.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">…………………………………………………………</td></tr>

            {{-- Item 4 --}}
            <tr style="page-break-inside: avoid;">
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="6">4</td>
                <td style="border: 1px solid #000; vertical-align: top; padding: 4px 6px;" rowspan="6">
                    Pelaksanaan asesmen secara fleksibel karena alasan keletihan atau keperluan pengobatan.
                </td>
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="6">
                    <span class="check-box">&nbsp;</span>
                </td>
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="6">
                    <span class="check-box">&radic;</span>
                </td>
                <td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td>
                <td style="border: 1px solid #000; padding: 3px 5px;">Menggunakan juru tulis.</td>
            </tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Menggunakan kamaramen perekam vidio/ataudio.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Memperbolehkan periode waktu yang lebih panjang untuk menyelesaikan tugas pekrejaan dalam asesmen.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Melakukan tugas pekerjaan dalam asesmen dengan waktu lebih pendek.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Menggunakan instruksi-instruksi spesifik pada proyek yang dapat dilakukan pada berbagai tingkatan.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">…………………………………………………………</td></tr>

            {{-- Item 5 --}}
            <tr style="page-break-inside: avoid;">
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="3">5</td>
                <td style="border: 1px solid #000; vertical-align: top; padding: 4px 6px;" rowspan="3">
                    Penyediaan peralatan asesmen berupa braille, audio/video-tape.
                </td>
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="3">
                    <span class="check-box">&nbsp;</span>
                </td>
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="3">
                    <span class="check-box">&radic;</span>
                </td>
                <td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td>
                <td style="border: 1px solid #000; padding: 3px 5px;">Menggunakan pertanyaan lisan.</td>
            </tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Menggunakan pertanyaan wawancara.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">…………………………………………………………</td></tr>

            {{-- Item 6 --}}
            <tr style="page-break-inside: avoid;">
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="7">6</td>
                <td style="border: 1px solid #000; vertical-align: top; padding: 4px 6px;" rowspan="7">
                    Penyesuaian tempat fisik/lingkungan asesmen
                </td>
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="7">
                    <span class="check-box">&nbsp;</span>
                </td>
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="7">
                    <span class="check-box">&radic;</span>
                </td>
                <td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td>
                <td style="border: 1px solid #000; padding: 3px 5px;">Pertanyaan lisan.</td>
            </tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Pertanyaan tulis.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Pertanyaan wawancara.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Ceklis Verifikasi portofolio.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Ceklis reviu produk.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Daftar instruksi terstruktur.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">…………………………………………………………</td></tr>

            {{-- Item 7 --}}
            <tr style="page-break-inside: avoid;">
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="5">7</td>
                <td style="border: 1px solid #000; vertical-align: top; padding: 4px 6px;" rowspan="5">
                    Pertimbangan umur/usia lanjut/gender asesi. (Adanya perbedaan usia dengan asesor yang lebih muda).
                </td>
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="5">
                    <span class="check-box">&nbsp;</span>
                </td>
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="5">
                    <span class="check-box">&radic;</span>
                </td>
                <td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td>
                <td style="border: 1px solid #000; padding: 3px 5px;">Menggunakan studi kasus/daftar instruksi terstrukut</td>
            </tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Menggunakan instrumen asesmen dengan huruf normal jangan terlalu kecil.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Menggunakan asesor dengan jenis kelamin yang sama dengan asesi.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Menggunakan instrumen asesmen yang sama walaupun berbeda jenis kelamain (tidak boleh memberi tanda tambahan pada instrumen asesmen yang digunakan dengan tujuan untuk membedakan jenis kelamin).</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">…………………………………………………………</td></tr>

            {{-- Item 8 --}}
            <tr style="page-break-inside: avoid;">
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="4">8</td>
                <td style="border: 1px solid #000; vertical-align: top; padding: 4px 6px;" rowspan="4">
                    Pertimbangan budaya/tradisi/agama.
                </td>
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="4">
                    <span class="check-box">&nbsp;</span>
                </td>
                <td style="border: 1px solid #000; text-align: center; vertical-align: top; padding: 4px;" rowspan="4">
                    <span class="check-box">&radic;</span>
                </td>
                <td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td>
                <td style="border: 1px solid #000; padding: 3px 5px;">Menggunakan studi kasus daftar instruksi terstrukut</td>
            </tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Menggunakan asesor tanpa pertimbangan budaya/tradisi/agama.</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">Menggunakan instrumen asesmen yang sama walaupun berbeda budaya/tradisi/agama).</td></tr>
            <tr><td style="border: 1px solid #000; text-align: center; padding: 2px;"><span class="check-box">&nbsp;</span></td><td style="border: 1px solid #000; padding: 3px 5px;">…………………………………………………………</td></tr>
        </tbody>
    </table>

    {{-- Agreement Box --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; margin-top: 15px; margin-bottom: 12px; font-size: 8.5pt; page-break-inside: avoid;">
        <tr>
            <td style="padding: 8px 12px; line-height: 1.5;">
                <div style="font-weight: bold; margin-bottom: 3px;">
                    Hasil kesepakatan antara asesi dengan asesor (Diperlukan penyesuaian atau tidak)*
                </div>
                <div>Jika diperlukan penyesuaian maka, tuliskan kesepakatan dibawah ini:</div>
                <div style="margin-left: 10px; margin-top: 8px;">
                    <div style="margin-bottom: 6px;">1) Acuan Pembanding Asesmen: ( tuliskan nama acuan pembanding yang disepakati)</div>
                    <div style="margin-bottom: 6px;">2) Metode Asesmen: ( tuliskan nama metode yang disepakati )</div>
                    <div>3) Instrumen Asesmen: (kode formulir instrumen asesmen yang disepakati )</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- 2x2 Signatures Table --}}
    <table style="width: 100%; border: 1px solid #000; border-collapse: collapse; font-size: 8.5pt; page-break-inside: avoid;">
        <tr>
            <td style="width: 50%; border-right: 1px solid #000; border-bottom: 1px solid #000; padding: 8px; vertical-align: top;">
                <div style="font-weight: bold;">Nama Asesor :</div>
                <div style="margin-top: 3px;">{{ $assessor?->full_name ?? $assessor?->name ?? '-' }}</div>
            </td>
            <td style="width: 50%; border-bottom: 1px solid #000; padding: 8px; vertical-align: top;">
                <div style="font-weight: bold; margin-bottom: 4px;">Tanggal dan Tanda Tangan Asesor:</div>
                <div style="margin-top: 4px;">
                    @if($assessor?->profile?->signature_path)
                        <img src="{{ asset('storage/' . $assessor->profile->signature_path) }}" style="max-height: 35px; max-width: 110px; vertical-align: middle;" alt="TTD Asesor">
                    @elseif($assessment->assessor_signature)
                        <img src="{{ $assessment->assessor_signature }}" style="max-height: 35px; max-width: 110px; vertical-align: middle;" alt="TTD Asesor">
                    @else
                        [Tanda Tangan Digital]
                    @endif
                    &nbsp;- {{ \Carbon\Carbon::parse($schedule->date ?? now())->translatedFormat('d/m/Y') }}
                </div>
            </td>
        </tr>
        <tr>
            <td style="width: 50%; border-right: 1px solid #000; padding: 8px; vertical-align: top;">
                <div style="font-weight: bold;">Nama Asesi:</div>
                <div style="margin-top: 3px;">{{ $asesi->full_name }}</div>
            </td>
            <td style="width: 50%; padding: 8px; vertical-align: top;">
                <div style="font-weight: bold; margin-bottom: 4px;">Tanggal dan Tanda Tangan Asesi:</div>
                <div style="margin-top: 4px;">
                    @if($assessment->asesi_signature)
                        <img src="{{ $assessment->asesi_signature }}" style="max-height: 35px; max-width: 110px; vertical-align: middle;" alt="TTD Asesi">
                    @else
                        [Tanda Tangan Digital]
                    @endif
                    &nbsp;- {{ \Carbon\Carbon::parse($schedule->date ?? now())->translatedFormat('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>

    <div style="font-style: italic; font-size: 7.5pt; color: #475569; margin-top: 6px;">
        Modul Melaksanakan Asesmen hal 32-35
    </div>
</div>
@endsection
