@extends('layouts.app', ['title' => '419 - Sesi Kedaluwarsa'])

@section('content')
<div class="min-h-[60vh] flex flex-col items-center justify-center text-center p-6 max-w-md mx-auto">
    <div class="w-16 h-16 rounded-2xl bg-blue-50 text-brand-600 flex items-center justify-center mb-4 border border-blue-200">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>

    <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-blue-100 text-blue-800 mb-2">Error 419</span>
    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Sesi Halaman Berakhir</h1>
    <p class="text-xs text-slate-500 mt-2 mb-6 leading-relaxed">
        Sesi keamanan Anda telah habis karena tidak ada aktivitas dalam beberapa waktu. Silakan muat ulang halaman atau login kembali.
    </p>

    <div class="flex items-center gap-3">
        <button type="button" onclick="window.location.reload()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
            Muat Ulang Halaman
        </button>
        <a href="{{ route('login') }}" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
            Login Ulang
        </a>
    </div>
</div>
@endsection
