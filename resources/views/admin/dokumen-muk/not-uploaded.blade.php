@extends('layouts.app', ['title' => 'Dokumen Belum Diunggah'])

@section('content')
<div class="min-h-[60vh] flex flex-col items-center justify-center text-center p-6 max-w-lg mx-auto">
    <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-4 border border-amber-200 shadow-xs">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
    </div>

    <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-amber-100 text-amber-800 mb-2">
        {{ $documentCode ?? 'FR.IA.01B' }}
    </span>
    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Dokumen Belum Diunggah</h1>
    <p class="text-xs text-slate-600 mt-2 mb-6 leading-relaxed">
        Asesi <strong class="text-slate-900">{{ $assessment->asesi->full_name }}</strong> belum mengunggah dokumen fisik / foto lembar kerja <strong>{{ $documentName ?? 'FR.IA.01B (Report Sheet / Lembar Pemeriksaan Teknis)' }}</strong> ke sistem.
    </p>

    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-xs text-left w-full space-y-1.5 mb-6 text-slate-600">
        <div><span class="text-slate-400">Nama Asesi:</span> <strong class="text-slate-800">{{ $assessment->asesi->full_name }}</strong></div>
        @if(!empty($assessment->asesi->profile?->nik))
            <div><span class="text-slate-400">NIK:</span> <span class="font-mono text-slate-700">{{ $assessment->asesi->profile->nik }}</span></div>
        @endif
        <div><span class="text-slate-400">Skema:</span> <span class="font-medium text-slate-700">{{ $assessment->schedule->scheme->name ?? '-' }}</span></div>
        <div><span class="text-slate-400">Jadwal:</span> <span class="font-medium text-slate-700">{{ $assessment->schedule->name ?? '-' }} ({{ $assessment->schedule->code ?? '-' }})</span></div>
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.dokumen-muk.index') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg shadow-sm transition">
            Kembali ke Daftar Dokumen MUK
        </a>
        <button type="button" onclick="window.close()" class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-lg border border-slate-300 transition">
            Tutup Tab
        </button>
    </div>
</div>
@endsection

