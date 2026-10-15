@extends('layouts.app', ['title' => '404 - Halaman Tidak Ditemukan'])

@section('content')
<div class="min-h-[60vh] flex flex-col items-center justify-center text-center p-6 max-w-md mx-auto">
    <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-4 border border-amber-200">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>

    <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-amber-100 text-amber-800 mb-2">Error 404</span>
    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Halaman Tidak Ditemukan</h1>
    <p class="text-xs text-slate-500 mt-2 mb-6 leading-relaxed">
        Tautan yang Anda tuju mungkin sudah kedaluwarsa, telah dihapus, atau alamat URL yang dimasukkan salah.
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
