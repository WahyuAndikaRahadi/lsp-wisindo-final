@extends('layouts.app', ['title' => '500 - Terjadi Kendala Server'])

@section('content')
<div class="min-h-[60vh] flex flex-col items-center justify-center text-center p-6 max-w-md mx-auto">
    <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-4 border border-rose-200">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
    </div>

    <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-rose-100 text-rose-800 mb-2">Error 500</span>
    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Terjadi Kendala pada Sistem</h1>
    <p class="text-xs text-slate-500 mt-2 mb-6 leading-relaxed">
        Terjadi kendala saat memproses permintaan Anda. Tim teknis LSP telah mencatat kendala ini. Silakan coba kembali beberapa saat lagi.
    </p>

    @auth
        @php
            $dashUrl = match(auth()->user()->role) {
                'SUPER_ADMIN', 'ADMIN_LSP' => route('admin.dashboard'),
                'ASESOR' => route('asesor.jadwal.index'),
                'ASESI' => route('asesi.dashboard'),
                default => url('/'),
            };
        @endphp
        <a href="{{ $dashUrl }}" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
            Kembali ke Dashboard Utama
        </a>
    @else
        <a href="{{ route('login') }}" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
            Kembali ke Halaman Login
        </a>
    @endauth
</div>
@endsection
