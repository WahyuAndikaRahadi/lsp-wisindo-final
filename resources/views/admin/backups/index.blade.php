@extends('layouts.app', ['title' => 'Backup Database Sistem'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-amber-100 text-amber-900 border border-amber-300">
                    SUPER ADMIN
                </span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Backup Database Sistem</h1>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Pencadangan snapshot data operasional LSP PrO Indo berformat SQL standar untuk mitigasi bencana (Disaster Recovery).
            </p>
        </div>

        {{-- Quick Actions --}}
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.backups.export-live') }}"
               class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg text-xs font-bold shadow-2xs transition inline-flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Download Langsung (.sql)</span>
            </a>

            <form action="{{ route('admin.backups.store') }}" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit]').innerHTML = 'Memproses Backup...';">
                @csrf
                <button type="submit"
                        class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-xs font-bold shadow-sm transition inline-flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    <span>Buat Backup Baru</span>
                </button>
            </form>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Stat 1: Database Name --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Basis Data Aktif</span>
                <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-base font-black text-slate-900 font-mono truncate" title="{{ $stats['database'] }}">
                {{ $stats['database'] }}
            </div>
            <div class="text-[11px] text-slate-400 mt-0.5 uppercase tracking-wide">
                Driver: <strong class="text-slate-600">{{ $stats['connection'] }}</strong>
            </div>
        </div>

        {{-- Stat 2: Total Tables --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Tabel</span>
                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-xl font-black text-slate-900 font-mono">
                {{ $stats['tables_count'] }} <span class="text-xs font-normal text-slate-500">Tabel</span>
            </div>
            <div class="text-[11px] text-emerald-600 mt-0.5 font-medium">
                Struktur &amp; data tercakup lengkap
            </div>
        </div>

        {{-- Stat 3: Total Backups Stored --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Arsip Tersimpan</span>
                <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-xl font-black text-slate-900 font-mono">
                {{ $stats['backups_count'] }} <span class="text-xs font-normal text-slate-500">File</span>
            </div>
            <div class="text-[11px] text-slate-500 mt-0.5">
                Penggunaan disk: <strong class="text-slate-800">{{ $stats['total_space_formatted'] }}</strong>
            </div>
        </div>

        {{-- Stat 4: Last Backup Time --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Backup Terakhir</span>
                <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-2 text-xs font-black text-slate-900 truncate">
                {{ $stats['last_backup_at'] ? $stats['last_backup_at']->setTimezone(config('app.timezone', 'Asia/Jakarta'))->isoFormat('D MMMM Y') : 'Belum Ada' }}
            </div>
            <div class="text-[11px] text-slate-500 mt-0.5 font-mono">
                {{ $stats['last_backup_at'] ? $stats['last_backup_at']->setTimezone(config('app.timezone', 'Asia/Jakarta'))->isoFormat('HH:mm:ss [WIB]') : 'Klik Buat Backup Baru' }}
            </div>
        </div>
    </div>

    {{-- Security & Restore Guide Notice --}}
    <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 text-xs text-slate-700 shadow-2xs flex items-start gap-3">
        <div class="w-6 h-6 rounded-full bg-brand-50 text-brand-700 flex items-center justify-center shrink-0 mt-0.5 font-bold">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="space-y-1 leading-relaxed">
            <span class="font-bold text-slate-900">Petunjuk Pemulihan Data (Restorasi):</span>
            <p>
                File backup yang dihasilkan berformat <strong>SQL murni (ANSI/MySQL standard)</strong>. Jika terjadi kendala server atau migrasi data, file ini dapat direstore secara instan melalui <strong>phpMyAdmin</strong> (tab <em>Import</em>), panel hosting (<em>Restore Database</em>), atau melalui terminal MySQL CLI:
            </p>
            <code class="block font-mono text-[11px] bg-slate-900 text-amber-300 px-3 py-1.5 rounded-lg select-all">
                mysql -u username -p {{ $stats['database'] }} &lt; lsp_proindo_backup_YYYY-MM-DD.sql
            </code>
        </div>
    </div>

    {{-- Backup Files Table / Cards --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide">Daftar Arsip Backup (.sql)</h2>
                <p class="text-xs text-slate-500 mt-0.5">Disimpan di direktori privat sistem yang aman dan tidak dapat diakses publik.</p>
            </div>
            <span class="text-xs text-slate-500 font-medium">
                Total: <strong>{{ $backups->count() }}</strong> file
            </span>
        </div>

        @if($backups->isEmpty())
            <div class="p-12 text-center">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Belum ada file backup tersimpan</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Klik tombol <strong>"Buat Backup Baru"</strong> di atas untuk membuat salinan data pertama sistem LSP.
                </p>
            </div>
        @else
            {{-- Desktop Table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">#</th>
                            <th class="py-3 px-4">Nama File Backup</th>
                            <th class="py-3 px-4">Ukuran</th>
                            <th class="py-3 px-4">Waktu Pembuatan</th>
                            <th class="py-3 px-4 text-right pr-6">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($backups as $index => $b)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 text-center font-mono text-slate-400">
                                    {{ $index + 1 }}
                                </td>
                                <td class="py-3 px-4 font-mono font-bold text-slate-900 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span>{{ $b['filename'] }}</span>
                                </td>
                                <td class="py-3 px-4 text-slate-700 font-semibold font-mono">
                                    {{ $b['size_formatted'] }}
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    <div>{{ $b['created_at']->setTimezone(config('app.timezone', 'Asia/Jakarta'))->isoFormat('dddd, D MMMM Y') }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ $b['created_at']->setTimezone(config('app.timezone', 'Asia/Jakarta'))->isoFormat('HH:mm:ss [WIB]') }} ({{ $b['created_at']->diffForHumans() }})</div>
                                </td>
                                <td class="py-3 px-4 text-right pr-6">
                                    <div class="inline-flex items-center gap-1.5">
                                        {{-- Download Button --}}
                                        <a href="{{ route('admin.backups.download', $b['filename']) }}"
                                           class="px-2.5 py-1 text-xs font-bold text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200 rounded-md transition inline-flex items-center gap-1"
                                           title="Unduh file .sql ini">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            <span>Download</span>
                                        </a>

                                        {{-- Delete Button --}}
                                        <form action="{{ route('admin.backups.destroy', $b['filename']) }}" method="POST"
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus file backup {{ $b['filename'] }} ini? Tindakan ini tidak dapat dibatalkan.');"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-1.5 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-md transition cursor-pointer"
                                                    title="Hapus file backup">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Card List --}}
            <div class="block md:hidden divide-y divide-slate-100">
                @foreach($backups as $index => $b)
                    <div class="p-4 space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <span class="font-mono text-xs font-bold text-slate-900 break-all block">{{ $b['filename'] }}</span>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    {{ $b['created_at']->setTimezone(config('app.timezone', 'Asia/Jakarta'))->isoFormat('D MMM Y, HH:mm [WIB]') }} &bull; <strong class="text-slate-800 font-mono">{{ $b['size_formatted'] }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                            <span class="text-[10px] text-slate-400 font-mono">#{{ $index + 1 }}</span>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.backups.download', $b['filename']) }}"
                                   class="px-3 py-1 text-xs font-bold text-brand-700 bg-brand-50 border border-brand-200 rounded-md transition inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    <span>Download</span>
                                </a>

                                <form action="{{ route('admin.backups.destroy', $b['filename']) }}" method="POST"
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus backup {{ $b['filename'] }}?');"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-200 rounded-md transition">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
