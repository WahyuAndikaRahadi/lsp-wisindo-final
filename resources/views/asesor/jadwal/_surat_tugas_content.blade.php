<div class="surat-tugas-wrapper" style="font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; color: #000; line-height: 1.45; font-size: 10pt; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important;">
    @php
        $kopSuratPath = public_path('images/kop-surat-lsp-proindo.png');
        $kopSuratBase64 = file_exists($kopSuratPath) 
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($kopSuratPath)) 
            : asset('images/kop-surat-lsp-proindo.png');

        $logoPath = public_path('images/logo-lsp-proindo.png');
        $logoBase64 = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : asset('images/logo-lsp-proindo.png');

        $ttdDirekturPath = public_path('images/ttd-direktur-stempel-lsp.png');
        $ttdDirekturBase64 = file_exists($ttdDirekturPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($ttdDirekturPath))
            : null;
    @endphp

    {{-- Kop Surat Banner (Presisi, Rapi & Berwarna Resmi Sesuai Dokumen Asli) --}}
    <div style="margin-bottom: 12px; text-align: center;">
        @if($kopSuratBase64)
            <img src="{{ $kopSuratBase64 }}" alt="Kop Surat LSP PrO Indo" style="width: 100%; height: auto; display: block; margin: 0 auto;">
            {{-- Dual brand accent stripe under banner for crisp, vibrant framing --}}
            <div style="display: flex; width: 100%; height: 3.5px; margin-top: 3px; margin-bottom: 12px;">
                <div style="width: 70%; background-color: #1E2B6E;"></div>
                <div style="width: 30%; background-color: #CE181E;"></div>
            </div>
        @else
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px;">
                <tr>
                    <td style="width: 85px; text-align: center; vertical-align: middle; padding-right: 12px;">
                        <img src="{{ $logoBase64 }}" style="max-height: 65px; max-width: 85px; object-fit: contain;" alt="Logo LSP">
                    </td>
                    <td style="text-align: center; vertical-align: middle; padding: 0;">
                        <div style="font-size: 13pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #1E2B6E; line-height: 1.2;">
                            LEMBAGA SERTIFIKASI PROFESI
                        </div>
                        <div style="font-size: 11pt; font-weight: bold; color: #CE181E; letter-spacing: 0.5px; margin-top: 2px; text-transform: uppercase; line-height: 1.2;">
                            PRAKTISI OTOMOTIF INDONESIA (LSP PrO INDO)
                        </div>
                        <div style="font-size: 8pt; font-weight: bold; color: #1E2B6E; margin-top: 2px;">
                            Lisensi BNSP: No. KEP.1444/BNSP/VIII/2021
                        </div>
                        <div style="font-size: 7.5pt; color: #334155; margin-top: 2px; line-height: 1.35;">
                            Ruko Blessing Center, Jl. Siput Raya No 38 Kayuringin Jaya, Kota Bekasi, Jawa Barat 17144<br>
                            Telp: 0812-8557-7080 &nbsp;|&nbsp; Email: lsp.proindo@gmail.com &nbsp;|&nbsp; Website: lspproindo.id
                        </div>
                    </td>
                </tr>
            </table>
            <div style="display: flex; width: 100%; height: 3.5px; margin-top: 5px; margin-bottom: 12px;">
                <div style="width: 70%; background-color: #1E2B6E;"></div>
                <div style="width: 30%; background-color: #CE181E;"></div>
            </div>
        @endif
    </div>

    {{-- Title Surat --}}
    <div style="text-align: center; margin-top: 10px; margin-bottom: 16px;">
        <div style="font-size: 11pt; font-weight: bold; font-style: italic; text-decoration: underline; letter-spacing: 0.5px; color: #000;">
            SURAT TUGAS ASESOR
        </div>
        <div style="font-size: 10pt; margin-top: 2px; color: #000;">
            Nomor : {{ $schedule->surat_tugas_number ?: $schedule->generateDefaultSuratTugasNumber() }}
        </div>
    </div>

    {{-- Pembuka --}}
    <p style="text-align: justify; margin-bottom: 12px; margin-top: 0; line-height: 1.45;">
        Dalam rangka pelaksanaan uji kompetensi di Lembaga Sertifikasi Profesi Pihak Ketiga Praktisi Otomotif Indonesia (LSP P3 PrO Indo) yang akan diselenggarakan:
    </p>

    {{-- Jadwal & Lokasi Pelaksanaan --}}
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 10pt;">
        <tr>
            <td style="width: 20%; padding: 3px 0; vertical-align: top;">Hari, Tanggal</td>
            <td style="width: 3%; padding: 3px 0; vertical-align: top; text-align: center;">:</td>
            <td style="width: 77%; padding: 3px 0; vertical-align: top;">
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
                {{ $schedule->scheme?->name }}
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
    <p style="margin-bottom: 8px; margin-top: 14px;">Saya menugaskan:</p>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 10pt;">
        <tr>
            <td style="width: 20%; padding: 3px 0; vertical-align: top;">Nama</td>
            <td style="width: 3%; padding: 3px 0; vertical-align: top; text-align: center;">:</td>
            <td style="width: 77%; padding: 3px 0; vertical-align: top;">
                @php
                    $asesorList = $assessors ?? $schedule->assessors;
                @endphp
                @if($asesorList->count() > 0)
                    <table style="width: 100%; border-collapse: collapse;">
                        @foreach($asesorList as $idx => $asesor)
                            <tr>
                                <td style="width: 45%; padding: 1.5px 0; vertical-align: top;">
                                    {{ $idx + 1 }}. {{ $asesor->full_name ?? $asesor->name }}
                                </td>
                                <td style="width: 55%; padding: 1.5px 0; vertical-align: top;">
                                    No. Reg MET &nbsp;:&nbsp; {{ $asesor->assessor_registration_number ?: '-' }}
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
            <td style="padding: 3px 0; vertical-align: top;">Jabatan</td>
            <td style="padding: 3px 0; vertical-align: top; text-align: center;">:</td>
            <td style="padding: 3px 0; vertical-align: top;">
                Asesor Kompetensi BNSP
            </td>
        </tr>
        <tr>
            <td style="padding: 3px 0; vertical-align: top;">Untuk</td>
            <td style="padding: 3px 0; vertical-align: top; text-align: center;">:</td>
            <td style="padding: 3px 0; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 22px; vertical-align: top; padding: 1.5px 0;">a.</td>
                        <td style="vertical-align: top; padding: 1.5px 0; text-align: justify;">
                            Merencanakan dan mengorganisasikan, melaksanakan asesmen dan merekomendasikan hasil asesmen.
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 22px; vertical-align: top; padding: 1.5px 0;">b.</td>
                        <td style="vertical-align: top; padding: 1.5px 0; text-align: justify;">
                            Melaporkan pelaksanaan tugas ini kepada Ketua Lembaga Sertifikasi Profesi Pihak Ketiga Praktisi Otomotif Indonesia (LSP P3 PrO Indo)
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 22px; vertical-align: top; padding: 1.5px 0;">c.</td>
                        <td style="vertical-align: top; padding: 1.5px 0; text-align: justify;">
                            Melaksanakan tugas ini dengan penuh rasa tanggung jawab
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Penutup --}}
    <p style="text-align: justify; margin-top: 14px; margin-bottom: 22px; line-height: 1.45;">
        Demikian surat tugas ini disampaikan, atas perhatian dan kehadirannya saya sampaikan terimakasih.
    </p>

    {{-- Tanda Tangan Direktur (Pakem: Direktur LSP P3 PrO Indo, TTD + Stempel Resmi, RIDWAN) --}}
    <div style="width: 100%; margin-top: 20px; page-break-inside: avoid;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 58%;"></td>
                <td style="width: 42%; text-align: left;">
                    <div style="line-height: 1.3;">{{ $schedule->letter_city ?? 'Bekasi' }}, {{ $schedule->formatted_surat_tugas_date }}</div>
                    <div style="margin-top: 4px; line-height: 1.3;">Direktur LSP P3 PrO Indo</div>
                    <div style="min-height: 85px; height: 90px; display: flex; align-items: center; justify-content: flex-start; margin: 3px 0;">
                        @if($ttdDirekturBase64)
                            <img src="{{ $ttdDirekturBase64 }}" style="max-height: 90px; max-width: 190px; object-fit: contain; margin-left: -6px;" alt="Tanda Tangan dan Stempel Direktur">
                        @elseif($schedule->effective_director_signature)
                            <img src="{{ $schedule->effective_director_signature }}" style="max-height: 85px; max-width: 180px; object-fit: contain;" alt="Tanda Tangan Direktur">
                        @endif
                    </div>
                    <div style="font-weight: bold; text-decoration: underline; letter-spacing: 0.5px;">
                        {{ $schedule->effective_director_name }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>
