@extends('layouts.app', ['title' => 'Tambah Tempat Uji Kompetensi (TUK)'])

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.tuks.index') }}" class="hover:text-lsp-navy font-semibold">&larr; Kembali ke Daftar TUK</a>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Tambah TUK Baru</h1>
            <p class="text-xs sm:text-sm text-slate-500">Daftarkan TUK dan buatkan akun login Admin TUK. Data lanjutan akan dilengkapi mandiri oleh pihak TUK.</p>
        </div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('admin.tuks.store') }}" class="space-y-5">
        @csrf

        {{-- DATA UTAMA & AKUN LOGIN (HANYA YANG DIBUTUHKAN) --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 sm:p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-full bg-lsp-navy text-white text-xs flex items-center justify-center font-bold">1</span>
                    <h2 class="text-sm font-bold text-slate-900">Identitas TUK &amp; Akun Login</h2>
                </div>
                <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full">
                    Wajib Diisi
                </span>
            </div>

            <div class="space-y-4">
                {{-- Nama Lengkap TUK --}}
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1">
                        Nama Lengkap TUK <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" required value="{{ old('name') }}"
                           placeholder="Contoh: TUK Mandiri Hotel Grand Cikarang"
                           class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs sm:text-sm text-slate-800 focus:ring-1 focus:ring-lsp-navy focus:border-lsp-navy @error('name') border-rose-400 ring-1 ring-rose-400 @enderror">
                    <p class="text-[11px] text-slate-400 mt-1">Nama resmi lembaga, hotel, atau Tempat Uji Kompetensi.</p>
                    @error('name') <p class="text-xs text-rose-600 mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                {{-- Grid: Email & Password --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Email Login Admin TUK --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1">
                            Email Login Admin TUK <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" name="admin_email" required value="{{ old('admin_email') }}"
                               placeholder="contoh: admin.tuk@hotelgrand.com"
                               class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs sm:text-sm text-slate-800 focus:ring-1 focus:ring-lsp-navy focus:border-lsp-navy @error('admin_email') border-rose-400 ring-1 ring-rose-400 @enderror">
                        <p class="text-[11px] text-slate-400 mt-1">Digunakan untuk login ke portal TUK.</p>
                        @error('admin_email') <p class="text-xs text-rose-600 mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>

                    {{-- Password Login Admin TUK --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1">
                            Kata Sandi (Password) <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" name="admin_password" required
                               placeholder="Minimal 6 karakter"
                               class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs sm:text-sm text-slate-800 focus:ring-1 focus:ring-lsp-navy focus:border-lsp-navy @error('admin_password') border-rose-400 ring-1 ring-rose-400 @enderror">
                        <p class="text-[11px] text-slate-400 mt-1">Kata sandi awal akun Admin TUK.</p>
                        @error('admin_password') <p class="text-xs text-rose-600 mt-1 font-medium">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Nama Pengelola / PIC Akun (Opsional) --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Nama Pengelola / Admin Akun <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <input type="text" name="admin_full_name" value="{{ old('admin_full_name') }}"
                           placeholder="Contoh: Budi Santoso (opsional, jika kosong otomatis: Admin [Nama TUK])"
                           class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs sm:text-sm text-slate-800 focus:ring-1 focus:ring-lsp-navy focus:border-lsp-navy">
                </div>
            </div>

            {{-- Callout Box: Informasi Alur Mandiri TUK --}}
            <div class="p-4 rounded-xl bg-indigo-50/70 border border-indigo-100 flex items-start space-x-3 text-xs text-indigo-900 mt-3">
                <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="space-y-1 leading-relaxed">
                    <p class="font-bold text-indigo-950 text-xs sm:text-sm">Pihak TUK yang Akan Melengkapi Data Mandiri</p>
                    <p class="text-indigo-800 text-[11px] sm:text-xs">
                        Admin LSP cukup mendaftarkan nama TUK dan akun login di atas. Pihak TUK selanjutnya dapat login secara mandiri untuk melengkapi:
                    </p>
                    <ul class="list-disc list-inside text-indigo-900 text-[11px] sm:text-xs space-y-0.5 pt-0.5 font-medium">
                        <li>Identitas alamat lengkap, provinsi, dan kota</li>
                        <li>Narahubung teknis &amp; PIC Tempat Uji Kompetensi</li>
                        <li>Pilihan skema sertifikasi yang diajukan</li>
                        <li>Unggah berkas foto ruangan, video, koneksi internet, dan perlengkapan AJJ</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- OPSI TAMBAHAN (KODE & JENIS TUK - OPSIONAL) --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 shadow-xs space-y-3" x-data="{ showAdvanced: false }">
            <div class="flex items-center justify-between cursor-pointer select-none" @click="showAdvanced = !showAdvanced">
                <div class="flex items-center space-x-2">
                    <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-500 text-xs flex items-center justify-center font-bold">2</span>
                    <div>
                        <h2 class="text-xs sm:text-sm font-bold text-slate-700">Kode &amp; Jenis TUK <span class="text-slate-400 font-normal">(Opsional)</span></h2>
                        <p class="text-[11px] text-slate-400">Atur kode manual atau jenis TUK jika diperlukan.</p>
                    </div>
                </div>
                <button type="button" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center space-x-1">
                    <span x-text="showAdvanced ? 'Tutup' : 'Atur Opsional'"></span>
                    <svg class="w-4 h-4 transition-transform duration-200" :class="showAdvanced ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>

            <div x-show="showAdvanced" x-collapse class="space-y-4 pt-3 border-t border-slate-100">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kode TUK (Opsional)</label>
                        <input type="text" name="code" value="{{ old('code') }}"
                               placeholder="Otomatis (contoh: TUK-WIS-001)"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm uppercase font-mono focus:ring-1 focus:ring-lsp-navy">
                        <p class="text-[10px] text-slate-400 mt-1">Kosongkan untuk pembuatan kode otomatis oleh sistem.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis TUK</label>
                        <select name="type" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy bg-white">
                            <option value="SEWAKTU" {{ old('type', 'SEWAKTU') === 'SEWAKTU' ? 'selected' : '' }}>TUK Sewaktu (Standar)</option>
                            <option value="TEMPAT_KERJA" {{ old('type') === 'TEMPAT_KERJA' ? 'selected' : '' }}>TUK Tempat Kerja</option>
                            <option value="MANDIRI" {{ old('type') === 'MANDIRI' ? 'selected' : '' }}>TUK Mandiri</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit Buttons --}}
        <div class="flex items-center justify-end space-x-3 pt-2">
            <a href="{{ route('admin.tuks.index') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs sm:text-sm font-semibold transition">
                Batal
            </a>
            <button type="submit" class="px-5 py-2.5 bg-lsp-navy hover:bg-slate-900 text-white rounded-lg text-xs sm:text-sm font-bold shadow-xs transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>Simpan &amp; Daftarkan TUK</span>
            </button>
        </div>
    </form>
</div>
@endsection
