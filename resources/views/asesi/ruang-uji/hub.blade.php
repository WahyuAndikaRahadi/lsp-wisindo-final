@extends('layouts.app', ['title' => 'Ruang Uji Asesmen - ' . ($schedule->name ?? $schedule->scheme->name)])

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6">
    {{-- Header & Navigasi Terstruktur --}}
    <div class="space-y-3 pb-2 border-b border-slate-200/80">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('asesi.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 hover:text-brand-800 transition py-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Dashboard</span>
            </a>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                    {{ $schedule->code }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-brand-50 text-brand-700 border border-brand-200">
                    #{{ $assessment->id }}
                </span>
            </div>
        </div>
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-black text-slate-900 tracking-tight leading-snug">
                {{ $schedule->name ?? $schedule->scheme->name }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 leading-relaxed max-w-2xl">
                Ruang pelaksanaan asesmen kompetensi mandiri dan observasi uji
            </p>
        </div>
    </div>

    @if($schedule->is_closed)
        <div class="p-4 bg-emerald-50 border-2 border-emerald-300 rounded-xl text-emerald-900 flex flex-col sm:flex-row items-start sm:items-center gap-3 shadow-xs">
            <span class="text-xl shrink-0">🔒</span>
            <div>
                <div class="font-bold text-sm">Jadwal Asesmen Telah Resmi Ditutup &amp; Dikunci</div>
                <p class="text-xs text-emerald-800 mt-0.5 leading-relaxed">Seluruh pelaksanaan asesmen telah selesai. Ruang uji ini sekarang dalam mode lihat (read-only).</p>
            </div>
        </div>
    @endif

    {{-- Schedule Details Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Skema Sertifikasi</span>
                <p class="text-slate-900 font-bold mt-1 text-sm">{{ $schedule->scheme->name }}</p>
                <span class="font-mono text-xs text-brand-700 bg-brand-50 px-2 py-0.5 rounded border border-brand-200 mt-1 inline-block">
                    {{ $schedule->scheme->code }}
                </span>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Tempat Uji Kompetensi (TUK)</span>
                <p class="text-slate-900 font-bold mt-1 text-sm">{{ $schedule->tuk_name }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ $schedule->code }}</p>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Waktu Pelaksanaan</span>
                <p class="text-slate-900 font-bold mt-1 text-sm">{{ \Carbon\Carbon::parse($schedule->date)->isoFormat('dddd, D MMMM Y') }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }} WIB</p>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Asesor Pembina</span>
                <p class="text-slate-900 font-bold mt-1 text-sm">{{ $assessment->assessor?->full_name ?? 'Tim Asesor LSP' }}</p>
                <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 mt-1 inline-block">
                    Status: {{ $assessment->status === 'COMPLETED' ? 'SELESAI' : 'AKTIF UJI' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Selected Instruments Heading --}}
    <div>
        <h2 class="text-lg font-black text-slate-900 tracking-tight">Instrumen Asesmen Terpilih</h2>
        <p class="text-xs text-slate-500">Pilih dan kerjakan instrumen asesmen kompetensi yang telah ditetapkan untuk Anda</p>
    </div>

    {{-- Grid of Selected Instruments --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($instruments as $inst)
            <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-sm flex flex-col justify-between hover:shadow-md transition">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-xs font-bold px-2.5 py-1 rounded border {{ $inst['badge_color'] }}">
                            {{ $inst['code'] }}
                        </span>
                        <span class="text-xs font-semibold {{ $inst['status_badge_class'] }}">
                            {{ $inst['status_label'] }}
                        </span>
                    </div>

                    <div>
                        <h3 class="text-base font-bold text-slate-900 leading-snug">{{ $inst['title'] }}</h3>
                        <p class="text-xs text-slate-600 mt-1.5 leading-relaxed">{{ $inst['desc'] }}</p>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="pt-2">
                        <div class="flex items-center justify-between text-xs font-semibold mb-1">
                            <span class="text-slate-600">Status Penyelesaian</span>
                            <span class="text-slate-900 font-bold">{{ $inst['progress_text'] }}</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 border border-slate-200 overflow-hidden">
                            <div class="{{ $inst['progress_bar_color'] }} h-2 rounded-full transition-all" style="width: {{ $inst['progress_percent'] }}%"></div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="pt-5 mt-4 border-t border-slate-100 flex items-center">
                    <a href="{{ $inst['action_url'] }}"
                       class="w-full flex items-center justify-center space-x-2 px-4 py-2.5 min-h-[40px] text-xs font-bold {{ $inst['action_button_class'] }} rounded-lg shadow-sm transition">
                        <span>{{ $inst['action_label'] }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl border border-dashed border-slate-300 p-8 sm:p-12 text-center shadow-xs">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">Ruang Uji Belum Memiliki Instrumen Aktif</h3>
                <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto mt-1.5 leading-relaxed">
                    Asesor belum menetapkan instrumen asesmen pada formulir persetujuan (FR.AK.01). Seluruh instrumen unjuk kerja akan otomatis tampil di sini setelah penetapan diselesaikan oleh asesor.
                </p>
                <div class="mt-4 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>Menunggu Penetapan FR.AK.01 oleh Asesor</span>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pasca Asesmen (Hanya jika asesmen telah diselesaikan oleh Asesor) --}}
    @if($assessment->status === 'COMPLETED')
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-sm flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Tahap Pasca Asesmen</span>
                <h4 class="text-base font-bold text-slate-900 mt-0.5">Rangkaian Asesmen Selesai ({{ $assessment->final_result ?? 'KOMPETEN' }})</h4>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">Silakan lengkapi formulir umpan balik proses asesmen (FR.AK.03) atau ajukan banding (FR.AK.04) jika terdapat sanggahan hasil atau ketidaksesuaian prosedur uji.</p>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 shrink-0">
                <a href="{{ route('asesi.assessment.ak03', $assessment) }}" class="px-4 py-2.5 min-h-[40px] inline-flex items-center justify-center bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl shadow-sm transition text-center">
                    <span>FR.AK.03 Umpan Balik</span>
                    <span class="ml-1">&rarr;</span>
                </a>
                <a href="{{ route('asesi.assessment.appeal', $assessment) }}" class="px-4 py-2.5 min-h-[40px] inline-flex items-center justify-center bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold rounded-xl shadow-sm transition gap-1.5">
                    <span>FR.AK.04 Banding</span>
                    @if($assessment->appeals->isNotEmpty())
                        <span class="px-1.5 py-0.5 bg-amber-100 text-amber-800 text-[10px] rounded font-mono font-semibold">Tercatat</span>
                    @endif
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
