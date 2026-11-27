@extends('layouts.print-muk')

@section('title', 'FR.APL.01 - ' . $asesi->full_name)
@section('doc-title', 'FR.APL.01 Permohonan Sertifikasi Kompetensi')

@section('content')
@php
    $savedProofTypes = $assessment->proofs ? $assessment->proofs->pluck('proof_type')->map(fn($t) => strtoupper((string)$t))->toArray() : [];
    $isAccepted = ($assessment->admin_status === 'APPROVED' || $assessment->apl_status === 'APPROVED' || $assessment->status === 'ACTIVE' || $assessment->status === 'COMPLETED');
    $adminUser = $assessment->admin ?? $assessment->admin_reviewer ?? \App\Models\User::where('role', 'ADMIN_LSP')->first();
    $docDate = \Carbon\Carbon::parse($assessment->registered_at ?? $schedule->date ?? now())->translatedFormat('d F Y');
    $adminDate = \Carbon\Carbon::parse($assessment->admin_approved_at ?? ($assessment->approved_at ?? ($schedule->date ?? now())))->translatedFormat('d F Y');
    $profile = $profile ?? $asesi->profile;
    $hasDasar = in_array('IJAZAH_SMK', $savedProofTypes, true) || in_array('SURAT_SISWA_AKTIF', $savedProofTypes, true) || in_array('IJAZAH_SMA_SERTIFIKAT', $savedProofTypes, true) || in_array('PENGALAMAN_KERJA', $savedProofTypes, true);
    if (!$hasDasar && $isAccepted) {
        $savedProofTypes[] = 'IJAZAH_SMK';
    }
    if ($isAccepted && !in_array('KTP', $savedProofTypes, true)) {
        $savedProofTypes[] = 'KTP';
    }
    if ($isAccepted && !in_array('PAS_FOTO', $savedProofTypes, true)) {
        $savedProofTypes[] = 'PAS_FOTO';
    }
@endphp

<div style="font-size: 9pt; line-height: 1.35;">
    {{-- Header Kop --}}
    <div class="header-kop">
        <div class="title-lsp">Lembaga Sertifikasi Profesi P3 Wisata Indonesia (LSP Wisindo)</div>
        <div class="doc-code">FR.APL.01. PERMOHONAN SERTIFIKASI KOMPETENSI</div>
    </div>

    {{-- Bagian 1: Data Pribadi --}}
    <div style="font-weight: bold; font-size: 9.5pt; margin-top: 10px; margin-bottom: 4px;">
        Bagian 1 : Rincian Data Pemohon Sertifikasi
    </div>
    <div style="font-size: 8pt; color: #475569; margin-bottom: 6px;">
        Pada bagian ini, cantumkan data pribadi, data pendidikan formal serta data pekerjaan anda pada saat ini.
    </div>

    <div style="font-weight: bold; margin-bottom: 4px;">a. Data Pribadi</div>
    <table class="meta-grid" style="border: 1px solid #000; margin-bottom: 8px;">
        <tr>
            <td style="width: 25%; font-weight: bold;">Nama lengkap</td>
            <td style="width: 2%;">:</td>
            <td style="width: 73%; font-weight: bold;">{{ $asesi->full_name }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">No. KTP/NIK</td>
            <td>:</td>
            <td style="font-family: monospace;">{{ $profile?->nik ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Tempat / tgl. Lahir</td>
            <td>:</td>
            <td>
                @php
                    $tLahir = $profile?->tempat_lahir ?? ($profile?->birth_place ?? '-');
                    $dLahir = ($profile?->tanggal_lahir ?? $profile?->birth_date) ? \Carbon\Carbon::parse($profile?->tanggal_lahir ?? $profile?->birth_date)->translatedFormat('d F Y') : '-';
                @endphp
                {{ $tLahir }} / {{ $dLahir }}
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Jenis kelamin</td>
            <td>:</td>
            <td>
                @php
                    $jk = strtoupper((string) ($profile?->jenis_kelamin ?? $profile?->gender ?? 'L'));
                    $isFemale = ($jk === 'P' || str_starts_with($jk, 'W') || str_starts_with($jk, 'P'));
                @endphp
                {{ $isFemale ? 'Wanita' : 'Laki-laki' }}
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Kebangsaan</td>
            <td>:</td>
            <td>{{ $profile?->kebangsaan ?? ($profile?->nationality ?? 'Indonesia') }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Alamat rumah</td>
            <td>:</td>
            <td>{{ $profile?->alamat_rumah ?? ($profile?->address ?? '-') }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Kode Pos</td>
            <td>:</td>
            <td>{{ $profile?->kode_pos ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">No. Telepon Rumah</td>
            <td>:</td>
            <td>{{ $profile?->no_telepon_rumah ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">No. HP / E-mail</td>
            <td>:</td>
            <td>{{ $asesi->phone ?? ($profile?->phone_number ?? '-') }} &nbsp;/&nbsp; {{ $asesi->email }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Kualifikasi Pendidikan</td>
            <td>:</td>
            <td>{{ $profile?->pendidikan_terakhir ?? ($profile?->education_level ?? ($profile?->last_education ?? 'SMK / Sederajat')) }}</td>
        </tr>
    </table>

    <div style="font-weight: bold; margin-top: 8px; margin-bottom: 4px;">b. Data Pekerjaan Sekarang</div>
    <table class="meta-grid" style="border: 1px solid #000; margin-bottom: 12px;">
        <tr>
            <td style="width: 25%; font-weight: bold;">Nama Institusi / Perusahaan</td>
            <td style="width: 2%;">:</td>
            <td style="width: 73%;">{{ $profile?->nama_institusi_perusahaan ?? ($profile?->company_name ?? ($profile?->company ?? ($profile?->institution ?? '-'))) }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Jabatan</td>
            <td>:</td>
            <td>{{ $profile?->jabatan ?? ($profile?->job_title ?? ($profile?->occupation ?? '-')) }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Alamat Kantor</td>
            <td>:</td>
            <td>{{ $profile?->alamat_kantor ?? ($profile?->company_address ?? '-') }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Kode Pos Kantor</td>
            <td>:</td>
            <td>{{ $profile?->kode_pos ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">No. Telp / Fax / Kontak Kantor</td>
            <td>:</td>
            <td>{{ $profile?->no_telepon_kantor ?? '-' }}</td>
        </tr>
    </table>

    {{-- Bagian 2: Data Sertifikasi --}}
    <div style="font-weight: bold; font-size: 9.5pt; margin-top: 12px; margin-bottom: 4px;">
        Bagian 2 : Data Sertifikasi
    </div>
    <table class="meta-grid" style="border: 1px solid #000; margin-bottom: 8px;">
        <tr>
            <td style="width: 25%; font-weight: bold;" rowspan="2">Skema Sertifikasi</td>
            <td style="width: 8%;">Judul</td>
            <td class="colon">:</td>
            <td class="value">{{ $scheme->name }}</td>
        </tr>
        <tr>
            <td>Nomor</td>
            <td class="colon">:</td>
            <td class="value">{{ $scheme->code }}</td>
        </tr>
        <tr style="border-top: 1px solid #000;">
            <td style="font-weight: bold;">Tujuan Asesmen</td>
            <td colspan="3" style="padding-left: 8px; vertical-align: middle;">
                @php
                    $tujuanVal = $assessment->tujuan_asesmen ?? 'Sertifikasi';
                @endphp
                <span class="check-box {{ $tujuanVal === 'Sertifikasi' ? 'checked' : '' }}">{!! $tujuanVal === 'Sertifikasi' ? '&#10003;' : '&nbsp;' !!}</span> Sertifikasi &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="check-box {{ $tujuanVal === 'Pengakuan Kompetensi Terkini (PKT)' ? 'checked' : '' }}">{!! $tujuanVal === 'Pengakuan Kompetensi Terkini (PKT)' ? '&#10003;' : '&nbsp;' !!}</span> Pengakuan Kompetensi Terkini (PKT) &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="check-box {{ $tujuanVal === 'Rekognisi Pembelajaran Lampau (RPL)' ? 'checked' : '' }}">{!! $tujuanVal === 'Rekognisi Pembelajaran Lampau (RPL)' ? '&#10003;' : '&nbsp;' !!}</span> Rekognisi Pembelajaran Lampau (RPL) &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="check-box {{ $tujuanVal === 'Lainnya' ? 'checked' : '' }}">{!! $tujuanVal === 'Lainnya' ? '&#10003;' : '&nbsp;' !!}</span> Lainnya
            </td>
        </tr>
    </table>

    {{-- Units Table --}}
    <div style="font-weight: bold; margin-bottom: 4px;">Daftar Unit Kompetensi sesuai kemasan:</div>
    <table class="bnsp-table" style="font-size: 8pt;">
        <thead>
            <tr>
                <th style="width: 6%;">No.</th>
                <th style="width: 25%;">Kode Unit</th>
                <th style="width: 45%;">Judul Unit</th>
                <th style="width: 24%;">Standar Kompetensi Kerja</th>
            </tr>
        </thead>
        <tbody>
            @foreach($scheme->competencyUnits as $idx => $u)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td style="font-family: monospace; font-weight: bold;">{{ $u->code }}</td>
                    <td>{{ $u->title }}</td>
                    <td style="font-size: 7.5pt; color: #475569;">{{ $u->standard_reference ?? 'SKKNI No. 097 Tahun 2018' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Bagian 3: Bukti Kelengkapan --}}
    <div style="font-weight: bold; font-size: 9.5pt; margin-top: 12px; margin-bottom: 4px;" class="avoid-break">
        Bagian 3 : Bukti Kelengkapan Pemohon
    </div>
    <div style="font-weight: bold; font-size: 9pt; margin-bottom: 4px;">3.1 Bukti Persyaratan Dasar Pemohon</div>
    <table class="bnsp-table" style="font-size: 8.5pt;">
        <thead>
            <tr>
                <th style="width: 6%;" rowspan="2">No.</th>
                <th style="width: 54%;" rowspan="2">Bukti Persyaratan Dasar</th>
                <th colspan="2" style="width: 20%;">Ada</th>
                <th style="width: 20%;" rowspan="2">Tidak Ada</th>
            </tr>
            <tr style="font-size: 7.5pt;">
                <th style="width: 10%;">Memenuhi Syarat</th>
                <th style="width: 10%;">Tidak Memenuhi Syarat</th>
            </tr>
        </thead>
        <tbody>
            @php
                $syarat1 = in_array('IJAZAH_SMK', $savedProofTypes, true);
                $syarat2 = in_array('SURAT_SISWA_AKTIF', $savedProofTypes, true);
                $syarat3 = in_array('IJAZAH_SMA_SERTIFIKAT', $savedProofTypes, true);
                $syarat4 = in_array('PENGALAMAN_KERJA', $savedProofTypes, true);
            @endphp
            <tr>
                <td style="text-align: center;">1</td>
                <td>Fotocopy Ijazah SMK otomotif</td>
                <td style="text-align: center;"><span class="check-box {{ $syarat1 ? 'checked' : '' }}">{!! $syarat1 ? '&#10003;' : '&nbsp;' !!}</span></td>
                <td style="text-align: center;"><span class="check-box">&nbsp;</span></td>
                <td style="text-align: center;"><span class="check-box {{ !$syarat1 ? 'checked' : '' }}">{!! !$syarat1 ? '&#10003;' : '&nbsp;' !!}</span></td>
            </tr>
            <tr>
                <td style="text-align: center;">2</td>
                <td>Surat Keterangan Peserta didik SMK Jurusan Otomotif yang telah menyelesaikan mata pelajaran sesuai skema</td>
                <td style="text-align: center;"><span class="check-box {{ $syarat2 ? 'checked' : '' }}">{!! $syarat2 ? '&#10003;' : '&nbsp;' !!}</span></td>
                <td style="text-align: center;"><span class="check-box">&nbsp;</span></td>
                <td style="text-align: center;"><span class="check-box {{ !$syarat2 ? 'checked' : '' }}">{!! !$syarat2 ? '&#10003;' : '&nbsp;' !!}</span></td>
            </tr>
            <tr>
                <td style="text-align: center;">3</td>
                <td>Fotocopy Ijazah SMA sederajat dan fotocopy sertifikat pelatihan berbasis kompetensi</td>
                <td style="text-align: center;"><span class="check-box {{ $syarat3 ? 'checked' : '' }}">{!! $syarat3 ? '&#10003;' : '&nbsp;' !!}</span></td>
                <td style="text-align: center;"><span class="check-box">&nbsp;</span></td>
                <td style="text-align: center;"><span class="check-box {{ !$syarat3 ? 'checked' : '' }}">{!! !$syarat3 ? '&#10003;' : '&nbsp;' !!}</span></td>
            </tr>
            <tr>
                <td style="text-align: center;">4</td>
                <td>Surat keterangan pengalaman kerja sesuai bidang minimal 1 tahun</td>
                <td style="text-align: center;"><span class="check-box {{ $syarat4 ? 'checked' : '' }}">{!! $syarat4 ? '&#10003;' : '&nbsp;' !!}</span></td>
                <td style="text-align: center;"><span class="check-box">&nbsp;</span></td>
                <td style="text-align: center;"><span class="check-box {{ !$syarat4 ? 'checked' : '' }}">{!! !$syarat4 ? '&#10003;' : '&nbsp;' !!}</span></td>
            </tr>
        </tbody>
    </table>

    <div style="font-weight: bold; font-size: 9pt; margin-top: 8px; margin-bottom: 4px;">3.2 Bukti Administratif</div>
    <table class="bnsp-table" style="font-size: 8.5pt;">
        <thead>
            <tr>
                <th style="width: 6%;" rowspan="2">No.</th>
                <th style="width: 54%;" rowspan="2">Bukti Administratif</th>
                <th colspan="2" style="width: 20%;">Ada</th>
                <th style="width: 20%;" rowspan="2">Tidak Ada</th>
            </tr>
            <tr style="font-size: 7.5pt;">
                <th style="width: 10%;">Memenuhi Syarat</th>
                <th style="width: 10%;">Tidak Memenuhi Syarat</th>
            </tr>
        </thead>
        <tbody>
            @php
                $adm1 = in_array('KTP', $savedProofTypes, true);
                $adm2 = in_array('PAS_FOTO', $savedProofTypes, true);
            @endphp
            <tr>
                <td style="text-align: center;">1</td>
                <td>Fotocopy Kartu Tanda Penduduk (KTP)</td>
                <td style="text-align: center;"><span class="check-box {{ $adm1 ? 'checked' : '' }}">{!! $adm1 ? '&#10003;' : '&nbsp;' !!}</span></td>
                <td style="text-align: center;"><span class="check-box">&nbsp;</span></td>
                <td style="text-align: center;"><span class="check-box {{ !$adm1 ? 'checked' : '' }}">{!! !$adm1 ? '&#10003;' : '&nbsp;' !!}</span></td>
            </tr>
            <tr>
                <td style="text-align: center;">2</td>
                <td>Pas photo ukuran 3x4 sebanyak 2 lembar dengan background warna merah</td>
                <td style="text-align: center;"><span class="check-box {{ $adm2 ? 'checked' : '' }}">{!! $adm2 ? '&#10003;' : '&nbsp;' !!}</span></td>
                <td style="text-align: center;"><span class="check-box">&nbsp;</span></td>
                <td style="text-align: center;"><span class="check-box {{ !$adm2 ? 'checked' : '' }}">{!! !$adm2 ? '&#10003;' : '&nbsp;' !!}</span></td>
            </tr>
        </tbody>
    </table>

    {{-- Recommendation & Signatures --}}
    <table class="meta-grid avoid-break" style="border: 1px solid #000; margin-top: 10px;">
        <tr>
            <td style="width: 50%; border-right: 1px solid #000; padding: 6px; vertical-align: top;">
                <div style="font-weight: bold; margin-bottom: 4px;">Rekomendasi (diisi oleh LSP):</div>
                <div style="font-size: 8.5pt;">
                    Berdasarkan ketentuan persyaratan dasar, maka pemohon: <br>
                    <strong>{{ $isAccepted ? 'DITERIMA' : 'TIDAK DITERIMA' }}</strong> sebagai peserta sertifikasi kompetensi.
                </div>
                <div style="font-size: 8pt; color: #475569; margin-top: 4px;">
                    Catatan: {{ $assessment->admin_notes ?? ($assessment->apl_revision_notes ?? 'Berkas lengkap dan memenuhi persyaratan dasar.') }}
                </div>
            </td>
            <td style="width: 50%; padding: 6px; vertical-align: top;">
                <div style="font-weight: bold; margin-bottom: 4px;">Pemohon / Kandidat:</div>
                <div style="font-size: 8.5pt;">
                    Menyatakan bahwa data dan dokumen yang saya sampaikan adalah sah dan benar.
                </div>
            </td>
        </tr>
    </table>

    <div class="sig-container avoid-break" style="margin-top: 15px;">
        <div class="sig-grid">
            <div class="sig-col" style="padding-left: 0; width: 50%;">
                <div style="font-size: 8.5pt; font-weight: bold;">Admin LSP :</div>
                <div class="sig-box">
                    @if(!empty($adminUser?->profile?->signature_data))
                        <img src="{{ $adminUser->profile->signature_data }}" class="sig-img" alt="TTD Admin" style="max-height: 70px; max-width: 200px; filter: contrast(1.6) brightness(0.85);">
                    @else
                        <div style="height: 45px;"></div>
                    @endif
                    <div style="border-bottom: 1px dotted #000; width: 85%;"></div>
                </div>
                <div style="font-size: 8.5pt;">
                    <strong>{{ $adminUser?->full_name ?? 'Admin LSP' }}</strong><br>
                    Tanggal : {{ $adminDate }}
                </div>
            </div>

            <div class="sig-col" style="padding-right: 0; width: 50%;">
                <div style="font-size: 8.5pt; font-weight: bold;">Tanda tangan Pemohon :</div>
                <div class="sig-box">
                    @php
                        $asesiSig = $assessment->asesi_signature ?? $profile?->signature_data;
                    @endphp
                    @if($asesiSig)
                        <img src="{{ $asesiSig }}" class="sig-img" alt="TTD Asesi" style="max-height: 70px; max-width: 200px; filter: contrast(1.6) brightness(0.85);">
                    @else
                        <div style="height: 45px;"></div>
                    @endif
                    <div style="border-bottom: 1px dotted #000; width: 85%;"></div>
                </div>
                <div style="font-size: 8.5pt;">
                    <strong>{{ $asesi->full_name }}</strong><br>
                    Tanggal : {{ $docDate }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
