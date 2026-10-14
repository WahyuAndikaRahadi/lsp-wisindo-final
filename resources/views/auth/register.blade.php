@extends('layouts.app', ['title' => 'Pendaftaran Akun Peserta'])

@section('content')
<div class="w-full flex items-center justify-center my-auto">
    <!-- Main Split-Card Container -->
    <div class="w-full max-w-4xl bg-white shadow-xl flex flex-col md:flex-row overflow-hidden relative border border-slate-200 rounded-sm">
        
        <!-- Top Horizontal Dual Stripe (Spanning Entire Card: 65% Navy, 35% Red) -->
        <div class="absolute top-0 left-0 right-0 w-full flex h-1 z-10">
            <div class="w-3/5 md:w-[65%] bg-lsp-navy"></div>
            <div class="w-2/5 md:w-[35%] bg-lsp-red"></div>
        </div>

        <!-- Left Panel: Info & Branding (40%) -->
        <div class="w-full md:w-[40%] p-8 md:p-10 flex flex-col justify-between border-b md:border-b-0 md:border-r border-slate-200 bg-[#F8FAFC]">
            <div>
                <!-- Official Logo -->
                <div class="mb-6">
                    <img src="{{ asset('images/logo-lsp-wisindo.png') }}" 
                         alt="LSP Praktisi Otomotif Indonesia" 
                         class="h-20 w-auto object-contain">
                </div>

                <h1 class="font-serif text-2xl md:text-3xl font-bold text-lsp-title tracking-tight mb-2">
                    LSP PrO Indo
                </h1>
                <p class="text-xs text-lsp-muted leading-relaxed">
                    Pendaftaran mandiri peserta (asesi) sertifikasi profesi otomotif standar BNSP.
                </p>
            </div>

            <div class="mt-8 pt-6 border-t border-slate-200">
                <span class="text-[10px] font-extrabold text-lsp-red uppercase tracking-widest block mb-1">
                    SERTIFIKASI PROFESI
                </span>
                <p class="text-[11px] text-lsp-muted leading-relaxed">
                    Pastikan data nama dan NIK yang Anda isi sesuai kartu tanda penduduk (KTP).
                </p>
            </div>
        </div>

        <!-- Right Panel: Form (60%) -->
        <div class="w-full md:w-[60%] p-8 md:p-10 bg-white flex flex-col justify-center">
            <span class="text-[10px] font-extrabold text-lsp-red uppercase tracking-wider block mb-1">
                REGISTRASI PESERTA
            </span>
            <h2 class="font-serif text-2xl font-bold text-lsp-title mb-1">
                Buat Akun Baru
            </h2>
            <p class="text-xs text-lsp-muted mb-6">
                Isi data di bawah untuk membuat akun peserta asesmen.
            </p>

            <form method="POST" action="{{ route('register') }}" class="space-y-3.5">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-800 mb-1" for="full_name">
                        Nama Lengkap Sesuai KTP <span class="text-lsp-red">*</span>
                    </label>
                    <input id="full_name" type="text" name="full_name" value="{{ old('full_name') }}" required autofocus
                        class="w-full px-3.5 py-2 bg-lsp-input-bg border border-slate-300 focus:border-lsp-navy text-xs rounded text-slate-900 focus:outline-none focus:ring-1 focus:ring-lsp-navy transition @error('full_name') border-lsp-red ring-1 ring-lsp-red @enderror"
                        placeholder="Contoh: Budi Santoso" />
                    @error('full_name')
                        <p class="mt-1 text-[11px] text-lsp-red font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-800 mb-1" for="email">
                        Alamat Email Aktif <span class="text-lsp-red">*</span>
                    </label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required
                        class="w-full px-3.5 py-2 bg-lsp-input-bg border border-slate-300 focus:border-lsp-navy text-xs rounded text-slate-900 focus:outline-none focus:ring-1 focus:ring-lsp-navy transition @error('email') border-lsp-red ring-1 ring-lsp-red @enderror"
                        placeholder="budi@example.com" />
                    @error('email')
                        <p class="mt-1 text-[11px] text-lsp-red font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-800 mb-1" for="phone">
                        Nomor Telepon / WhatsApp
                    </label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                        class="w-full px-3.5 py-2 bg-lsp-input-bg border border-slate-300 focus:border-lsp-navy text-xs rounded text-slate-900 focus:outline-none focus:ring-1 focus:ring-lsp-navy transition @error('phone') border-lsp-red ring-1 ring-lsp-red @enderror"
                        placeholder="081234567890" />
                    @error('phone')
                        <p class="mt-1 text-[11px] text-lsp-red font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-800 mb-1" for="password">
                            Kata Sandi <span class="text-lsp-red">*</span>
                        </label>
                        <input id="password" type="password" name="password" required
                            class="w-full px-3.5 py-2 bg-lsp-input-bg border border-slate-300 focus:border-lsp-navy text-xs rounded text-slate-900 focus:outline-none focus:ring-1 focus:ring-lsp-navy transition @error('password') border-lsp-red ring-1 ring-lsp-red @enderror"
                            placeholder="Min. 6 karakter" />
                        @error('password')
                            <p class="mt-1 text-[11px] text-lsp-red font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-800 mb-1" for="password_confirmation">
                            Konfirmasi Sandi <span class="text-lsp-red">*</span>
                        </label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                            class="w-full px-3.5 py-2 bg-lsp-input-bg border border-slate-300 focus:border-lsp-navy text-xs rounded text-slate-900 focus:outline-none focus:ring-1 focus:ring-lsp-navy transition"
                            placeholder="Ulangi sandi" />
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full py-2.5 px-4 bg-lsp-navy hover:bg-lsp-navy-dark text-white text-xs font-semibold rounded shadow-sm transition-colors duration-150">
                        Daftar Sebagai Peserta
                    </button>
                </div>
            </form>

            <!-- Footer Link -->
            <div class="mt-6 pt-4 border-t border-slate-200 text-center">
                <p class="text-xs text-slate-500">
                    Sudah memiliki akun? 
                    <a href="{{ route('login') }}" class="text-lsp-navy font-bold hover:underline ml-1">
                        Masuk di sini
                    </a>
                </p>
            </div>
        </div>

    </div>
</div>
@endsection
