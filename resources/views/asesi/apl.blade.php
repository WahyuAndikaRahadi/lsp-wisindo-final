@extends('layouts.app')

@section('title', 'FR.APL.01 & FR.APL.02 - Pendaftaran & Asesmen Mandiri')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 py-4 sm:py-6">
    {{-- Header & Navigasi Terstruktur --}}
    <div class="space-y-3 pb-2 border-b border-slate-200/80">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('asesi.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 hover:text-brand-800 transition py-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Dashboard</span>
            </a>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                FR.APL.01 &amp; FR.APL.02
            </span>
        </div>
    </div>

    @if($assessment->schedule?->is_closed)
        <div class="p-4 bg-emerald-50 border-2 border-emerald-300 rounded-xl text-emerald-900 flex flex-col sm:flex-row items-start sm:items-center gap-3 shadow-xs">
            <span class="text-xl shrink-0">🔒</span>
            <div>
                <div class="font-bold text-sm">Jadwal Asesmen Telah Resmi Ditutup &amp; Dikunci</div>
                <p class="text-xs text-emerald-800 mt-0.5 leading-relaxed">Seluruh berkas permohonan APL dan asesmen mandiri dalam jadwal ini berada dalam mode lihat (read-only).</p>
            </div>
        </div>
    @endif

    {{-- Info Header Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                Skema: {{ $assessment->schedule->scheme->code }}
            </span>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800 mt-2 leading-snug">{{ $assessment->schedule->scheme->name }}</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1.5 leading-relaxed">
                TUK: <span class="font-medium text-slate-700">{{ $assessment->schedule->tuk_name }}</span> &bull; 
                Tanggal Uji: <span class="font-medium text-slate-700">{{ \Carbon\Carbon::parse($assessment->schedule->date)->translatedFormat('d F Y') }}</span>
            </p>
        </div>
        <div class="shrink-0">
            @if($assessment->apl_status === 'APPROVED')
                <div class="text-right">
                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold bg-emerald-500 text-white shadow-sm">
                        ✓ Disetujui Asesor
                    </span>
                    <a href="{{ route('asesi.assessment.ruang-uji', $assessment) }}" class="mt-2 inline-block text-sm text-indigo-600 hover:underline font-medium">
                        Lanjut ke Ruang Uji →
                    </a>
                </div>
            @elseif($assessment->apl_status === 'SUBMITTED')
                <div class="text-right">
                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold bg-amber-500 text-white shadow-sm">
                        ⏳ Menunggu Verifikasi Asesor
                    </span>
                    <p class="text-xs text-slate-500 mt-1">Form terkunci sampai diverifikasi</p>
                </div>
            @elseif($assessment->apl_status === 'REVISION_REQUESTED')
                <div class="bg-rose-50 border border-rose-200 rounded-lg p-3 text-right">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-rose-600 text-white">
                        Perlu Revisi
                    </span>
                    <p class="text-xs text-rose-700 mt-1 font-medium">Catatan Asesor:</p>
                    <p class="text-xs text-slate-700 italic">{{ $assessment->apl_revision_notes }}</p>
                </div>
            @else
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold bg-slate-100 text-slate-700">
                    Draft Permohonan
                </span>
            @endif
        </div>
    </div>

    @if($errors->any())
        <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-700 rounded-lg text-sm">
            <p class="font-bold">Mohon periksa kembali formulir Anda:</p>
            <ul class="list-disc pl-5 mt-1 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('asesi.assessment.apl.store', $assessment) }}" method="POST" enctype="multipart/form-data" class="space-y-8" id="aplForm">
        @csrf

        {{-- BAGIAN 1: FR.APL.01 DATA PEMOHON --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between text-white gap-3">
                <div>
                    <h2 class="text-base sm:text-lg font-bold">FR.APL.01. PERMOHONAN SERTIFIKASI KOMPETENSI</h2>
                    <p class="text-xs text-slate-300 mt-0.5">Bagian 1: Rincian Data Pribadi &amp; Administratif (Auto-fetch)</p>
                </div>
                <a href="{{ route('asesi.profile.edit') }}" class="self-start sm:self-auto text-xs bg-white/10 hover:bg-white/20 text-white px-3 py-1.5 rounded-lg border border-white/20 transition flex items-center gap-1.5">
                    <span>✎ Edit Profil</span>
                </a>
            </div>

            <div class="p-4 sm:p-6">
                @php
                    $isProfileComplete = $profile && !empty($profile->nik) && !empty($profile->tempat_lahir) && !empty($profile->tanggal_lahir) && !empty($profile->jenis_kelamin) && !empty($profile->alamat_rumah) && !empty($profile->kode_pos) && !empty($profile->pendidikan_terakhir) && !empty($profile->jabatan) && !empty(auth()->user()->phone);
                    $isAplLocked = $assessment->apl_status === 'SUBMITTED';
                @endphp

                @if(! $isProfileComplete)
                    <div class="mb-5 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-xs">
                        <div class="flex items-start gap-2.5">
                            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <div>
                                <p class="text-xs font-bold text-amber-900">Perhatian: Data Profil FR.APL.01 Belum Lengkap</p>
                                <p class="text-xs text-amber-700 mt-0.5">Seluruh data identitas pribadi dan jabatan posisi di APL.01 wajib diisi lengkap sebelum mengajukan permohonan asesmen.</p>
                            </div>
                        </div>
                        <a href="{{ route('asesi.profile.edit') }}" class="shrink-0 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-lg transition shadow-xs flex items-center gap-1">
                            <span>Lengkapi Profil</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                @endif

                {{-- a. Data Pribadi --}}
                <div class="mb-6 bg-slate-50 p-5 rounded-xl border border-slate-200 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                        <div>
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">a. Data Pribadi</h3>
                            <p class="text-xs text-slate-500">Nilai data otomatis diambil dari profil akun Anda dan dapat diperbarui langsung.</p>
                        </div>
                        <span class="text-[11px] font-semibold text-brand-700 bg-brand-50 px-2.5 py-1 rounded-full border border-brand-200">
                            Auto-fetch Profil
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Nama Lengkap (Sesuai KTP) <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="full_name" required value="{{ old('full_name', auth()->user()->full_name) }}"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                                @disabled($isAplLocked)>
                            @error('full_name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Nomor Induk Kependudukan (NIK) <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="nik" maxlength="16" inputmode="numeric" required value="{{ old('nik', $profile?->nik) }}"
                                placeholder="16 digit NIK KTP (angka)"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                                @disabled($isAplLocked)>
                            @error('nik') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Jenis Kelamin <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex items-center space-x-6 h-[42px] px-3 bg-white border border-slate-300 rounded-lg text-sm">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="jenis_kelamin" value="L" {{ old('jenis_kelamin', $profile?->jenis_kelamin) === 'L' ? 'checked' : '' }} required
                                        class="text-brand-600 focus:ring-brand-500" @disabled($isAplLocked)>
                                    <span class="ml-2 text-slate-700 font-medium">Laki-laki</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio" name="jenis_kelamin" value="P" {{ old('jenis_kelamin', $profile?->jenis_kelamin) === 'P' ? 'checked' : '' }} required
                                        class="text-brand-600 focus:ring-brand-500" @disabled($isAplLocked)>
                                    <span class="ml-2 text-slate-700 font-medium">Perempuan</span>
                                </label>
                            </div>
                            @error('jenis_kelamin') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Tempat Lahir <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="tempat_lahir" required value="{{ old('tempat_lahir', $profile?->tempat_lahir) }}"
                                placeholder="Kota / Kabupaten Lahir"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                                @disabled($isAplLocked)>
                            @error('tempat_lahir') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Tanggal Lahir <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" name="tanggal_lahir" required
                                value="{{ old('tanggal_lahir', $profile?->tanggal_lahir ? \Carbon\Carbon::parse($profile->tanggal_lahir)->format('Y-m-d') : '') }}"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                                @disabled($isAplLocked)>
                            @error('tanggal_lahir') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Kebangsaan <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="kebangsaan" required value="{{ old('kebangsaan', $profile?->kebangsaan ?? 'Indonesia') }}"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                                @disabled($isAplLocked)>
                            @error('kebangsaan') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Pendidikan Terakhir <span class="text-rose-500">*</span>
                            </label>
                            <select name="pendidikan_terakhir" required class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" @disabled($isAplLocked)>
                                <option value="">-- Pilih Pendidikan --</option>
                                @foreach(['SD', 'SMP', 'SMA/Sederajat', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'] as $opt)
                                    <option value="{{ $opt }}" {{ old('pendidikan_terakhir', $profile?->pendidikan_terakhir) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                            @error('pendidikan_terakhir') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Alamat Rumah Tinggal <span class="text-rose-500">*</span>
                            </label>
                            <textarea name="alamat_rumah" rows="2" required
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                                @disabled($isAplLocked)>{{ old('alamat_rumah', $profile?->alamat_rumah) }}</textarea>
                            @error('alamat_rumah') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Kode Pos <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="kode_pos" maxlength="10" inputmode="numeric" required value="{{ old('kode_pos', $profile?->kode_pos) }}"
                                placeholder="Contoh: 17530"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                                @disabled($isAplLocked)>
                            @error('kode_pos') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Nomor HP / WhatsApp <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="phone" required inputmode="numeric" maxlength="15" value="{{ old('phone', auth()->user()->phone) }}"
                                placeholder="Contoh: 081234567890"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                                @disabled($isAplLocked)>
                            @error('phone') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Telepon Rumah (Opsional)
                            </label>
                            <input type="text" name="no_telepon_rumah" inputmode="numeric" maxlength="15" value="{{ old('no_telepon_rumah', $profile?->no_telepon_rumah) }}"
                                placeholder="Contoh: 0218901234"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                                @disabled($isAplLocked)>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Email Asesi
                            </label>
                            <input type="email" disabled value="{{ auth()->user()->email }}"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 bg-slate-100 text-slate-500 text-sm">
                        </div>
                    </div>
                </div>

                {{-- b. Data Pekerjaan / Sekolah / Institusi --}}
                <div class="mb-6 bg-slate-50 p-5 rounded-xl border border-slate-200 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                        <div>
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">b. Data Pekerjaan / Sekolah / Institusi</h3>
                            <p class="text-xs text-slate-500">Nilai data otomatis diambil dari profil akun Anda dan dapat diperbarui langsung.</p>
                        </div>
                        <span class="text-[11px] font-semibold text-brand-700 bg-brand-50 px-2.5 py-1 rounded-full border border-brand-200">
                            Auto-fetch Profil
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Nama Institusi / Sekolah / Perusahaan (Opsional)
                            </label>
                            <input type="text" name="nama_institusi_perusahaan" value="{{ old('nama_institusi_perusahaan', $profile?->nama_institusi_perusahaan) }}"
                                placeholder="Contoh: SMK Negeri 1 Cikarang Barat / Bengkel Auto"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                                @disabled($isAplLocked)>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Jabatan / Posisi / Status <span class="text-rose-500">*</span>
                            </label>
                            @php
                                $jobOptions = [
                                    'Pelajar / Siswa',
                                    'Mahasiswa',
                                    'Karyawan Swasta / Teknisi / Mekanik',
                                    'Pegawai Negeri / BUMN',
                                    'Wirausaha / Pemilik Usaha',
                                    'Belum Bekerja / Fresh Graduate',
                                    'Lainnya',
                                ];
                                $currentJabatan = old('jabatan', $profile?->jabatan);
                            @endphp
                            <select name="jabatan" required class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" @disabled($isAplLocked)>
                                <option value="">-- Pilih Jabatan / Posisi / Status --</option>
                                @foreach($jobOptions as $opt)
                                    <option value="{{ $opt }}" {{ $currentJabatan === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                                @if($currentJabatan && !in_array($currentJabatan, $jobOptions))
                                    <option value="{{ $currentJabatan }}" selected>{{ $currentJabatan }}</option>
                                @endif
                            </select>
                            @error('jabatan') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Alamat Kantor / Sekolah (Opsional)
                            </label>
                            <textarea name="alamat_kantor" rows="2"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                                @disabled($isAplLocked)>{{ old('alamat_kantor', $profile?->alamat_kantor) }}</textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                No. Telepon Kantor / Sekolah (Opsional)
                            </label>
                            <input type="text" name="no_telepon_kantor" inputmode="numeric" maxlength="15" value="{{ old('no_telepon_kantor', $profile?->no_telepon_kantor) }}"
                                placeholder="Contoh: 0218905678"
                                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none"
                                @disabled($isAplLocked)>
                        </div>
                    </div>
                </div>

                {{-- Bagian 2 : Data Sertifikasi & Tujuan Asesmen --}}
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Bagian 2: Data Sertifikasi</h3>
                        <span class="text-[11px] text-slate-400">Skema & Tujuan Asesmen</span>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-xs text-slate-500 block">Judul Skema Sertifikasi</span>
                                <span class="font-semibold text-slate-800">{{ $assessment->schedule->scheme->name }}</span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500 block">Nomor Skema Sertifikasi</span>
                                <span class="font-semibold text-slate-800 font-mono">{{ $assessment->schedule->scheme->code }}</span>
                            </div>
                        </div>

                        <div class="border-t border-slate-200/80 pt-3">
                            <label for="tujuan_asesmen" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                <span>Tujuan Asesmen <span class="text-rose-500">*</span></span>
                                <span class="text-[11px] text-slate-500 font-normal lowercase">Pilih tujuan asesmen sesuai permohonan</span>
                            </label>
                            @php
                                $tujuanOptions = [
                                    'Sertifikasi',
                                    'Pengakuan Kompetensi Terkini (PKT)',
                                    'Rekognisi Pembelajaran Lampau (RPL)',
                                    'Lainnya',
                                ];
                                $selectedTujuan = old('tujuan_asesmen', $assessment->tujuan_asesmen ?? 'Sertifikasi');
                                $isAplLocked = $assessment->apl_status === 'SUBMITTED';
                            @endphp
                            <select name="tujuan_asesmen" id="tujuan_asesmen" required class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" @disabled($isAplLocked)>
                                @foreach($tujuanOptions as $opt)
                                    <option value="{{ $opt }}" @selected($selectedTujuan === $opt)>{{ $opt }}</option>
                                @endforeach
                            </select>
                            @if($isAplLocked)
                                <input type="hidden" name="tujuan_asesmen" value="{{ $selectedTujuan }}">
                            @endif
                            @error('tujuan_asesmen') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Pemilihan Asesor Penguji --}}
                @php
                    $scheduleAssessors = $assessment->schedule->assessors ?? collect();
                @endphp
                @if($scheduleAssessors->isNotEmpty())
                    <div class="mt-4 p-4 rounded-xl border border-indigo-100 bg-indigo-50/50 mb-6">
                        <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                            <span>Pilih Asesor Penguji <span class="text-rose-500">*</span></span>
                            <span class="text-[11px] text-slate-500 font-normal lowercase">Asesor yang akan memverifikasi berkas & menguji Anda</span>
                        </label>
                        <select name="assessor_id" required class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" @disabled($assessment->apl_status === 'SUBMITTED')>
                            <option value="">-- Pilih Salah Satu Asesor Penguji --</option>
                            @foreach($scheduleAssessors as $asesor)
                                <option value="{{ $asesor->id }}" {{ old('assessor_id', $assessment->assessor_id) == $asesor->id || $scheduleAssessors->count() === 1 ? 'selected' : '' }}>
                                    {{ $asesor->full_name }} (No. Reg: {{ $asesor->assessor_registration_number ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                        @error('assessor_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- BUKTI PERSYARATAN UPLOAD SECTION (IMAGE 2 STYLE) --}}
                <div class="mt-6">
                    <h3 class="text-base font-bold text-slate-800 mb-1">Upload Bukti Kelengkapan Pemohon</h3>
                    <p class="text-xs text-slate-500 mb-4">Unggah dokumen pendukung untuk diverifikasi oleh asesor kompetensi.</p>

                    @php
                        $existingProofs = $assessment->proofs->keyBy('proof_type');
                    @endphp

                    {{-- Bukti Persyaratan Dasar (Minimal 1 dari 4) --}}
                    <div class="mb-6 p-4 rounded-xl border border-indigo-100 bg-indigo-50/50">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-indigo-900 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs flex items-center justify-center font-bold">1</span>
                                Bukti Persyaratan Dasar
                            </h4>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-800">
                                Wajib pilih & upload minimal 1 opsi
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Opsi A --}}
                            <div class="bg-white p-3 rounded-lg border border-slate-200">
                                <label class="block text-xs font-bold text-slate-700 mb-1">A. Ijazah SMK Otomotif</label>
                                <input type="file" name="proofs[IJAZAH_SMK]" accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" @disabled($assessment->apl_status === 'SUBMITTED')>
                                @if(isset($existingProofs['IJAZAH_SMK']))
                                    <span class="text-xs text-emerald-600 font-medium mt-1 inline-block">✓ Terunggah: {{ $existingProofs['IJAZAH_SMK']->original_filename }}</span>
                                @endif
                            </div>

                            {{-- Opsi B --}}
                            <div class="bg-white p-3 rounded-lg border border-slate-200">
                                <label class="block text-xs font-bold text-slate-700 mb-1">B. Surat Keterangan Siswa Aktif SMK</label>
                                <input type="file" name="proofs[SURAT_SISWA_AKTIF]" accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" @disabled($assessment->apl_status === 'SUBMITTED')>
                                @if(isset($existingProofs['SURAT_SISWA_AKTIF']))
                                    <span class="text-xs text-emerald-600 font-medium mt-1 inline-block">✓ Terunggah: {{ $existingProofs['SURAT_SISWA_AKTIF']->original_filename }}</span>
                                @endif
                            </div>

                            {{-- Opsi C --}}
                            <div class="bg-white p-3 rounded-lg border border-slate-200">
                                <label class="block text-xs font-bold text-slate-700 mb-1">C. Ijazah SMA + Sertifikat Pelatihan Otomotif</label>
                                <input type="file" name="proofs[IJAZAH_SMA_SERTIFIKAT]" accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" @disabled($assessment->apl_status === 'SUBMITTED')>
                                @if(isset($existingProofs['IJAZAH_SMA_SERTIFIKAT']))
                                    <span class="text-xs text-emerald-600 font-medium mt-1 inline-block">✓ Terunggah: {{ $existingProofs['IJAZAH_SMA_SERTIFIKAT']->original_filename }}</span>
                                @endif
                            </div>

                            {{-- Opsi D --}}
                            <div class="bg-white p-3 rounded-lg border border-slate-200">
                                <label class="block text-xs font-bold text-slate-700 mb-1">D. Surat Pengalaman Kerja Bengkel Otomotif</label>
                                <input type="file" name="proofs[PENGALAMAN_KERJA]" accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" @disabled($assessment->apl_status === 'SUBMITTED')>
                                @if(isset($existingProofs['PENGALAMAN_KERJA']))
                                    <span class="text-xs text-emerald-600 font-medium mt-1 inline-block">✓ Terunggah: {{ $existingProofs['PENGALAMAN_KERJA']->original_filename }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Bukti Administratif (Wajib Keduanya) --}}
                    <div class="p-4 rounded-xl border border-amber-100 bg-amber-50/50">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-amber-900 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-amber-600 text-white text-xs flex items-center justify-center font-bold">2</span>
                                Bukti Administratif
                            </h4>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800">
                                Wajib diunggah keduanya
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-white p-3 rounded-lg border border-slate-200">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Fotokopi KTP / Identitas Diri <span class="text-rose-500">*</span></label>
                                <input type="file" name="proofs[KTP]" accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100" @disabled($assessment->apl_status === 'SUBMITTED')>
                                @if(isset($existingProofs['KTP']))
                                    <span class="text-xs text-emerald-600 font-medium mt-1 inline-block">✓ Terunggah: {{ $existingProofs['KTP']->original_filename }}</span>
                                @endif
                            </div>

                            <div class="bg-white p-3 rounded-lg border border-slate-200">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Pas Foto 3x4 (Latar Belakang Merah) <span class="text-rose-500">*</span></label>
                                <input type="file" name="proofs[PAS_FOTO]" accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100" @disabled($assessment->apl_status === 'SUBMITTED')>
                                @if(isset($existingProofs['PAS_FOTO']))
                                    <span class="text-xs text-emerald-600 font-medium mt-1 inline-block">✓ Terunggah: {{ $existingProofs['PAS_FOTO']->original_filename }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- BAGIAN 2: FR.APL.02 ASESMEN MANDIRI --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden" x-data="{
            setAll(val) {
                document.querySelectorAll('input.radio-kuk-' + val).forEach(el => el.checked = true);
            },
            setDefaultProof(val) {
                if (!val) return;
                document.querySelectorAll('select.select-proof').forEach(el => el.value = val);
            }
        }">
            <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 px-4 sm:px-6 py-4 flex flex-col md:flex-row md:items-center justify-between text-white gap-4">
                <div>
                    <h2 class="text-base sm:text-lg font-bold">FR.APL.02. ASESMEN MANDIRI</h2>
                    <p class="text-xs text-indigo-200 mt-0.5">Panduan Asesmen Mandiri: Nilai kesiapan Anda pada setiap Kriteria Unjuk Kerja (KUK)</p>
                </div>

                @if($assessment->apl_status !== 'SUBMITTED')
                    {{-- Fast Actions --}}
                    <div class="flex items-center gap-2 flex-wrap">
                        <button type="button" @click="setAll('K')" class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg text-xs font-bold shadow-sm transition">
                            ✓ Semua K (Kompeten)
                        </button>
                        <button type="button" @click="setAll('BK')" class="px-3 py-1.5 bg-rose-500 hover:bg-rose-600 text-white rounded-lg text-xs font-bold shadow-sm transition">
                            ✕ Semua BK
                        </button>
                        <select @change="setDefaultProof($event.target.value)" class="px-3 py-1.5 bg-white text-slate-800 rounded-lg text-xs font-medium border border-indigo-200 shadow-2xs focus:ring-2 focus:ring-indigo-300 focus:outline-none cursor-pointer">
                            <option value="">-- Set Bukti Semua --</option>
                            <option value="IJAZAH_SMK">Ijazah SMK Otomotif</option>
                            <option value="SURAT_SISWA_AKTIF">Surat Keterangan Siswa Aktif</option>
                            <option value="IJAZAH_SMA_SERTIFIKAT">Ijazah + Sertifikat</option>
                            <option value="PENGALAMAN_KERJA">Surat Pengalaman Kerja</option>
                        </select>
                    </div>
                @else
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/20 text-white rounded-lg text-xs font-semibold">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Formulir Terkunci &amp; Terkirim (Sedang Diverifikasi Asesor)
                        </span>
                    </div>
                @endif
            </div>

            <div class="p-4 sm:p-6 overflow-x-auto">
                @php
                    $existingApl02 = $assessment->apl02Items->keyBy('kuk_id');
                @endphp

                <table class="w-full min-w-[700px] text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 border-b border-slate-200">
                            <th class="p-3 w-12 text-center">No</th>
                            <th class="p-3">Unit Kompetensi / Elemen / Kriteria Unjuk Kerja (KUK)</th>
                            <th class="p-3 w-20 text-center bg-emerald-50 text-emerald-800">K</th>
                            <th class="p-3 w-20 text-center bg-rose-50 text-rose-800">BK</th>
                            <th class="p-3 w-64">Bukti Pendukung Relevan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @php $kukCounter = 1; @endphp
                        @foreach($assessment->schedule->scheme->competencyUnits as $unit)
                            <tr class="bg-slate-50 font-bold text-slate-800">
                                <td colspan="5" class="p-2.5 bg-slate-100 text-slate-800">
                                    Unit {{ $loop->iteration }}: {{ $unit->code }} - {{ $unit->title }}
                                </td>
                            </tr>
                            @foreach($unit->elements as $element)
                                <tr class="bg-white text-slate-600 font-semibold italic">
                                    <td class="p-2 text-center text-slate-400">#</td>
                                    <td colspan="4" class="p-2 pl-4">
                                        Elemen {{ $loop->iteration }}: {{ $element->title }}
                                    </td>
                                </tr>
                                @foreach($element->kuks as $kuk)
                                    @php
                                        $savedItem = $existingApl02[$kuk->id] ?? null;
                                        $val = $savedItem?->assessment_value ?? 'K';
                                        $proof = $savedItem?->selected_proof_type ?? 'IJAZAH_SMK';
                                    @endphp
                                    <tr class="hover:bg-indigo-50/30 transition">
                                        <td class="p-3 text-center text-slate-500 font-mono">{{ $kukCounter++ }}</td>
                                        <td class="p-3 pl-6">
                                            <span class="font-bold text-slate-700 mr-1">{{ $kuk->code_number }}</span>
                                            <span class="text-slate-600">{{ $kuk->statement }}</span>
                                        </td>
                                        <td class="p-3 text-center bg-emerald-50/50">
                                            <input type="radio" name="kuks[{{ $kuk->id }}][value]" value="K" class="radio-kuk-K text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer" @checked($val === 'K') @disabled($assessment->apl_status === 'SUBMITTED')>
                                        </td>
                                        <td class="p-3 text-center bg-rose-50/50">
                                            <input type="radio" name="kuks[{{ $kuk->id }}][value]" value="BK" class="radio-kuk-BK text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer" @checked($val === 'BK') @disabled($assessment->apl_status === 'SUBMITTED')>
                                        </td>
                                        <td class="p-3">
                                            <select name="kuks[{{ $kuk->id }}][proof_type]" class="select-proof w-full text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white text-slate-800 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs" @disabled($assessment->apl_status === 'SUBMITTED')>
                                                <option value="IJAZAH_SMK" @selected($proof === 'IJAZAH_SMK')>Ijazah SMK Otomotif</option>
                                                <option value="SURAT_SISWA_AKTIF" @selected($proof === 'SURAT_SISWA_AKTIF')>Surat Keterangan Siswa Aktif</option>
                                                <option value="IJAZAH_SMA_SERTIFIKAT" @selected($proof === 'IJAZAH_SMA_SERTIFIKAT')>Ijazah SMA + Sertifikat Pelatihan</option>
                                                <option value="PENGALAMAN_KERJA" @selected($proof === 'PENGALAMAN_KERJA')>Surat Pengalaman Kerja</option>
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Signature Section & Save Button --}}
            <div class="p-4 sm:p-6 bg-slate-50 border-t border-slate-200 flex flex-col md:flex-row items-stretch md:items-end justify-between gap-6">
                <div class="w-full max-w-full sm:max-w-[340px]">
                    <x-signature-pad 
                        name="signature_data" 
                        :value="$assessment->asesi_signature ?? $profile?->signature_data ?? old('signature_data')" 
                        :profileSignature="$profile?->signature_data"
                        label="Tanda Tangan Pemohon (Asesi)" 
                        :required="true"
                        :height="140" 
                        :disabled="$assessment->apl_status === 'SUBMITTED' || (bool) $assessment->schedule?->is_closed"
                    />
                </div>

                <div class="w-full md:w-auto flex flex-col items-stretch sm:items-end gap-2.5">
                    @if($assessment->schedule?->is_closed)
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full">
                            <a href="{{ route('asesi.assessment.ruang-uji', $assessment) }}" class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider rounded-xl border border-slate-300 transition text-center">
                                Kembali ke Ruang Uji →
                            </a>
                            <button type="button" disabled class="px-6 py-3 bg-slate-200 text-slate-500 font-bold text-xs uppercase tracking-wider rounded-xl cursor-not-allowed flex items-center justify-center gap-2 border border-slate-300 shadow-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                <span>Jadwal Telah Ditutup (Read-Only)</span>
                            </button>
                        </div>
                    @elseif($assessment->apl_status === 'APPROVED')
                        <div class="mb-2 p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-left w-full md:max-w-md">
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input type="checkbox" name="statement_integrity" value="1" checked required class="mt-0.5 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs text-emerald-950 leading-relaxed">
                                    <strong>Pernyataan Pemohon (Asesi):</strong> Data permohonan APL dan komitmen kerahasiaan telah diverifikasi asesor. Anda dapat memperbarui data berkas ini dan menyimpannya tanpa membatalkan status verifikasi.
                                </span>
                            </label>
                        </div>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
                            <a href="{{ route('asesi.assessment.ruang-uji', $assessment) }}" class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider rounded-xl border border-slate-300 transition text-center">
                                Kembali ke Ruang Uji →
                            </a>
                            <button type="submit" class="px-7 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl shadow-md transition flex items-center justify-center gap-2">
                                <span>Simpan Perubahan APL</span>
                                <span>✓</span>
                            </button>
                        </div>
                    @elseif($assessment->apl_status === 'SUBMITTED')
                        <button type="button" disabled class="w-full sm:w-auto px-6 py-3 bg-slate-400 text-white font-bold text-xs rounded-xl shadow-md cursor-not-allowed text-center">
                            Menunggu Verifikasi Asesor
                        </button>
                    @else
                        <div class="mb-2 p-3.5 bg-indigo-50/80 border border-indigo-200 rounded-xl text-left w-full md:max-w-md">
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input type="checkbox" name="statement_integrity" value="1" required class="mt-0.5 rounded border-indigo-300 text-brand-600 focus:ring-brand-500 w-4 h-4 shrink-0">
                                <span class="text-xs text-indigo-950 leading-relaxed">
                                    <strong>Pernyataan Pemohon (Asesi):</strong> Saya berkomitmen untuk mematuhi seluruh tata tertib asesmen, menjaga kerahasiaan materi uji kompetensi, serta mematuhi seluruh ketentuan asesmen LSP PrO Indo.
                                </span>
                            </label>
                        </div>
                        <button type="submit" class="w-full sm:w-auto px-8 py-3.5 min-h-[44px] bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl shadow-md transition transform active:scale-95 flex items-center justify-center gap-2">
                            <span>Simpan &amp; Ajukan Permohonan</span>
                            <span>→</span>
                        </button>
                        <p class="text-xs text-slate-400 text-center sm:text-right">Pastikan dokumen bukti telah dipilih dengan benar.</p>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
