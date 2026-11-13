@extends('layouts.app', ['title' => 'Pengaturan Profil & Tanda Tangan Admin'])

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Pengaturan Profil & Tanda Tangan Admin</h1>
            <p class="text-sm text-slate-500">
                Kelola informasi akun, kata sandi, dan master tanda tangan digital Anda untuk pengesahan formulir FR.APL.01 serta dokumen MUK resmi lainnya.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-900 flex items-center gap-3 shadow-sm">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <p class="text-sm font-semibold">{{ session('success') }}</p>
        </div>
    @endif

    @php
        $p = $profile ?? new \App\Models\UserProfile();
    @endphp

    <form action="{{ route('admin.profile.update') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Section 1: Biodata Akun Admin --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-6 space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-800">1. Data Akun & Identitas Admin</h2>
                <p class="text-xs text-slate-400">Informasi identitas pejabat / staf administrasi LSP PrO Indo</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Nama Lengkap Beserta Gelar <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="full_name" required value="{{ old('full_name', $user->full_name) }}"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('full_name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Email Resmi Admin <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" name="email" required value="{{ old('email', $user->email) }}"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Nomor Handphone / WhatsApp
                    </label>
                    <input type="text" name="phone" inputmode="numeric" maxlength="15" value="{{ old('phone', $user->phone) }}"
                        placeholder="Contoh: 081234567890"
                        class="numeric-only w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('phone') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Jabatan / Peran di LSP
                    </label>
                    <input type="text" name="jabatan" value="{{ old('jabatan', $p->jabatan ?? 'Admin LSP') }}"
                        placeholder="Contoh: Manajer Sertifikasi / Admin LSP"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('jabatan') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Nama Institusi / Lembaga
                    </label>
                    <input type="text" name="nama_institusi_perusahaan" value="{{ old('nama_institusi_perusahaan', $p->nama_institusi_perusahaan ?? 'LSP Praktisi Otomotif Indonesia') }}"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('nama_institusi_perusahaan') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Section 2: Ganti Password (Opsional) --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-6 space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-800">2. Keamanan &amp; Kata Sandi</h2>
                <p class="text-xs text-slate-400">Kosongkan bagian ini jika Anda tidak ingin mengubah password</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Password Saat Ini
                    </label>
                    <x-password-input name="current_password" placeholder="••••••••"
                        class="px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" />
                    @error('current_password') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Password Baru
                    </label>
                    <x-password-input name="new_password" placeholder="Minimal 8 karakter"
                        class="px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" />
                    @error('new_password') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Konfirmasi Password Baru
                    </label>
                    <x-password-input name="new_password_confirmation" placeholder="Ulangi password baru"
                        class="px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" />
                </div>
            </div>
        </div>

        {{-- Section 3: Master Tanda Tangan Digital Admin --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-6 space-y-4">
            <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between items-start gap-2.5">
                <div class="min-w-0 flex-1">
                    <h2 class="text-base font-bold text-slate-800">3. Master Tanda Tangan Digital Admin LSP</h2>
                    <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                        Tanda tangan digital ini akan secara otomatis disematkan pada dokumen verifikasi permohonan <strong>FR.APL.01</strong> dan lembar pengesahan berkas resmi BNSP.
                    </p>
                </div>
                @if($p->signature_data)
                    <span class="inline-flex shrink-0 items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 self-start sm:self-auto">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        TTD Tersimpan
                    </span>
                @else
                    <span class="inline-flex shrink-0 items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full bg-amber-50 text-amber-700 border border-amber-200 self-start sm:self-auto">
                        Belum Diatur
                    </span>
                @endif
            </div>

            @if($p->signature_data)
                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl flex flex-col sm:flex-row items-center sm:items-start gap-4">
                    <div class="w-full sm:w-[340px] max-w-full h-[140px] bg-white rounded-xl border border-slate-300 shadow-xs p-3 flex items-center justify-center shrink-0">
                        <img src="{{ $p->signature_data }}" alt="Pratinjau Tanda Tangan Admin" class="w-full h-full object-contain">
                    </div>
                    <div class="text-xs text-slate-600 space-y-1">
                        <p class="font-bold text-slate-800 text-sm">Tanda Tangan Aktif Saat Ini</p>
                        <p class="leading-relaxed">Tanda tangan di samping tersimpan sebagai tanda tangan master Anda dan siap digunakan otomatis pada dokumen sertifikasi resmi BNSP.</p>
                    </div>
                </div>
            @endif

            <div class="w-full max-w-full sm:max-w-[340px]">
                <x-signature-pad 
                    name="signature_data" 
                    :value="old('signature_data', $p->signature_data)"
                    label="Goreskan Tanda Tangan Digital Baru / Perbarui"
                    :height="140"
                />
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 sm:gap-3 pt-2">
            <a href="{{ route('admin.dashboard') }}" class="w-full sm:w-auto text-center px-5 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">
                Batal
            </a>
            <button type="submit" class="w-full sm:w-auto text-center px-6 py-2.5 bg-lsp-navy hover:bg-lsp-navy-dark text-white font-bold text-xs uppercase tracking-wider rounded-lg shadow-sm transition">
                Simpan Profil &amp; Tanda Tangan
            </button>
        </div>
    </form>
</div>
@endsection
