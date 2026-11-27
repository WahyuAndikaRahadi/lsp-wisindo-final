@extends('layouts.app', ['title' => 'Pengaturan Akun'])

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            @php
                $backRoute = match($user->role) {
                    'ASESI' => route('asesi.dashboard'),
                    'ASESOR' => route('asesor.jadwal.index'),
                    'TUK_ADMIN' => route('tuk.dashboard'),
                    default => route('admin.dashboard')
                };
            @endphp
            <a href="{{ $backRoute }}" class="text-xs text-brand-600 hover:text-brand-700 font-semibold inline-flex items-center gap-1 mb-1">
                &larr; Kembali ke Dashboard
            </a>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Pengaturan Akun Login</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Kelola kredensial akun Anda (alamat email dan kata sandi untuk masuk ke sistem).</p>
        </div>
        <div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200">
                Peran: {{ str_replace('_', ' ', $user->role) }}
            </span>
        </div>
    </div>

    <!-- Form Akun -->
    <form action="{{ route('account.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Bagian 1: Email Login -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6 space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-700 flex items-center justify-center font-bold text-xs shrink-0 border border-brand-200">
                        <svg class="w-4 h-4 text-brand-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Alamat Email Login</h2>
                        <p class="text-[11px] text-slate-500">Email ini digunakan sebagai username utama untuk masuk ke sistem.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-1">
                <label for="email" class="block text-xs font-bold text-slate-700">
                    Alamat Email <span class="text-rose-500">*</span>
                </label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="w-full px-3.5 py-2.5 text-xs sm:text-sm rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs @error('email') border-rose-400 ring-1 ring-rose-400 @enderror">
                @error('email')
                    <p class="text-xs text-rose-600 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Bagian 2: Ganti Password Login -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6 space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center font-bold text-xs shrink-0 border border-amber-200">
                        <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Ubah Kata Sandi (Password)</h2>
                        <p class="text-[11px] text-slate-500">Kosongkan seluruh kolom di bawah ini jika Anda tidak ingin mengubah password.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="space-y-1">
                    <label class="block text-xs font-bold text-slate-700">Password Saat Ini</label>
                    <x-password-input name="current_password" placeholder="Masukkan password yang saat ini aktif..." class="w-full px-3.5 py-2 text-xs sm:text-sm rounded-xl border border-slate-300 bg-white" />
                    @error('current_password')
                        <p class="text-xs text-rose-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">Password Baru</label>
                        <x-password-input name="new_password" placeholder="Minimal 8 karakter..." class="w-full px-3.5 py-2 text-xs sm:text-sm rounded-xl border border-slate-300 bg-white" />
                        @error('new_password')
                            <p class="text-xs text-rose-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700">Konfirmasi Password Baru</label>
                        <x-password-input name="new_password_confirmation" placeholder="Ulangi password baru..." class="w-full px-3.5 py-2 text-xs sm:text-sm rounded-xl border border-slate-300 bg-white" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi Simpan -->
        <div class="flex items-center justify-between pt-2">
            <a href="{{ $backRoute }}" class="px-4 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-100 text-slate-700 text-xs font-bold transition">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-lsp-navy hover:bg-brand-700 text-white text-xs sm:text-sm font-bold shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Simpan Perubahan Akun</span>
            </button>
        </div>
    </form>

    <!-- Info Pemisah: Akses Menu Biodata -->
    @php
        $biodataRoute = match($user->role) {
            'ASESI' => route('asesi.profile.edit'),
            'ASESOR' => route('asesor.profile.edit'),
            'TUK_ADMIN' => route('tuk.data.edit'),
            default => null
        };
        $biodataLabel = match($user->role) {
            'ASESI' => 'Biodata & TTD Digital',
            'ASESOR' => 'Biodata & TTD Asesor',
            'TUK_ADMIN' => 'Data & Verifikasi TUK',
            default => 'Biodata'
        };
    @endphp

    @if($biodataRoute)
        <div class="p-4 rounded-xl border border-brand-200 bg-brand-50/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-brand-900">
            <div class="flex items-start gap-2.5">
                <span class="text-base shrink-0">💡</span>
                <div>
                    <span class="font-bold block text-slate-900">Ingin mengubah data identitas pribadi atau tanda tangan?</span>
                    <p class="text-slate-600 mt-0.5">Pengaturan akun di halaman ini khusus untuk email & kata sandi login. Untuk NIK, alamat, pendidikan, pekerjaan, dan tanda tangan digital, silakan kelola pada halaman Biodata.</p>
                </div>
            </div>
            <a href="{{ $biodataRoute }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-brand-100 text-brand-700 border border-brand-300 rounded-lg font-bold text-xs shrink-0 transition shadow-2xs self-start sm:self-auto">
                <span>Buka {{ $biodataLabel }}</span>
                <span>&rarr;</span>
            </a>
        </div>
    @endif

</div>
@endsection
