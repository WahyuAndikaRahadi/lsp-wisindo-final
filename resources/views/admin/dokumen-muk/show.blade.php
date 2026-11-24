@extends('layouts.app', ['title' => 'Dokumen MUK: ' . ($schedule->name ?? $schedule->scheme?->name)])

@section('content')
<div class="space-y-6" x-data="{
    exportModalOpen: false,
    sumberAnggaran: '{{ $schedule->kode_sumber_anggaran ?: '1' }}',
    kementerianCode: '{{ $schedule->kode_kementerian ?: '11' }}',
    kemenSearch: '',
    kemenOpen: false,
    kementerianList: {{ Js::from(\App\Services\BnspReferenceService::getKementerianList()) }},
    get filteredKemen() {
        if (!this.kemenSearch.trim()) return this.kementerianList;
        let q = this.kemenSearch.toLowerCase();
        return this.kementerianList.filter(k => k.name.toLowerCase().includes(q) || String(k.code).includes(q));
    },
    get selectedKemenName() {
        let match = this.kementerianList.find(k => String(k.code) === String(this.kementerianCode));
        return match ? match.name : 'Pilih Kementerian / Instansi';
    },
    selectKemen(k) {
        this.kementerianCode = String(k.code);
        this.kemenOpen = false;
        this.kemenSearch = '';
    }
}">
    {{-- Header & Breadcrumbs --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('admin.dokumen-muk.index') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-brand-600 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span>Kembali ke Daftar Jadwal</span>
                </a>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                <span>Dokumen MUK & Berita Acara</span>
                <span class="font-mono text-xs font-bold px-2.5 py-1 rounded bg-brand-50 text-brand-700 border border-brand-100">
                    {{ $schedule->code }}
                </span>
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
                {{ $schedule->name ?? $schedule->scheme?->name }} &bull; {{ $schedule->scheme?->name }}
            </p>
        </div>
    </div>

    {{-- Schedule Details Card --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Informasi Jadwal Asesmen</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block mb-0.5">Skema Sertifikasi:</span>
                <span class="font-bold text-slate-800">{{ $schedule->scheme?->name ?? '-' }}</span>
                <span class="text-slate-500 block text-[11px]">Kode: {{ $schedule->scheme?->code ?? '-' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">TUK & Waktu Pelaksanaan:</span>
                <span class="font-bold text-slate-800">{{ $schedule->tuk_name ?: '-' }}</span>
                <span class="text-slate-500 block text-[11px]">
                    {{ \Carbon\Carbon::parse($schedule->date)->isoFormat('D MMMM Y') }}
                    @if($schedule->start_time)
                        ({{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }} WIB)
                    @endif
                </span>
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Tim Asesor:</span>
                @if($schedule->assessors->isNotEmpty())
                    <span class="font-bold text-slate-800">{{ $schedule->assessors->pluck('full_name')->join(', ') }}</span>
                @else
                    <span class="text-slate-400 italic">Belum ditugaskan</span>
                @endif
                @if($schedule->pic_name)
                    <span class="text-slate-500 block text-[11px]">Penanggung Jawab: {{ $schedule->pic_name }}</span>
                @endif
            </div>
            <div>
                <span class="text-slate-400 block mb-0.5">Total Asesi:</span>
                <span class="font-bold text-slate-800 text-sm">{{ $schedule->assessments()->count() }} Peserta</span>
                <span class="text-slate-500 block text-[11px]">Status: {{ $schedule->status ?? 'ACTIVE' }}</span>
            </div>
        </div>
    </div>

    {{-- Schedule-level Exports Section --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Ekspor Dokumen Jadwal Asesmen</h2>
            <p class="text-xs text-slate-500">Cetak berita acara, daftar hadir, laporan asesmen, dan perangkat instrumen MUK</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Berita Acara & Kehadiran (Primary) --}}
            <a href="{{ route('admin.dokumen-muk.dynamic', ['schedule' => $schedule->id, 'type' => 'ba']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-xs transition min-h-[38px]">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Berita Acara (BA)</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.export-sertifikat', $schedule->id) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow-xs transition min-h-[38px]">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Sertifikat</span>
            </a>

            <button type="button" @click="exportModalOpen = true"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg shadow-2xs transition min-h-[38px] cursor-pointer"
               title="Ekspor Rekap Permohonan Blangko BNSP (Excel)">
                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Permohonan Blangko (Excel)</span>
            </button>

            <div class="h-5 w-px bg-slate-200 hidden sm:block mx-1"></div>

            {{-- Dokumen Laporan & Pelaksanaan --}}
            <a href="{{ route('admin.dokumen-muk.dynamic', ['schedule' => $schedule->id, 'type' => 'hadir-asesor']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg shadow-2xs transition min-h-[38px]">
                <span>Hadir Asesor</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.dynamic', ['schedule' => $schedule->id, 'type' => 'ba']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg shadow-2xs transition min-h-[38px]">
                <span>Hadir Asesi</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.dynamic', ['schedule' => $schedule->id, 'type' => 'ak05']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg shadow-2xs transition min-h-[38px]">
                <span>FR.AK.05 (Laporan)</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.dynamic', ['schedule' => $schedule->id, 'type' => 'ak06']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg shadow-2xs transition min-h-[38px]">
                <span>FR.AK.06 (Tinjauan)</span>
            </a>

            {{-- Fixed Master PDF --}}
            <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'cover']) }}" target="_blank"
               class="inline-flex items-center gap-1 px-2.5 py-2 text-xs font-medium bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-lg border border-slate-200 transition min-h-[38px]" title="Fixed Master PDF: Cover MUK">
                <span>Cover</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'daftar-periksa']) }}" target="_blank"
               class="inline-flex items-center gap-1 px-2.5 py-2 text-xs font-medium bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-lg border border-slate-200 transition min-h-[38px]" title="Fixed Master PDF: Daftar Periksa">
                <span>Periksa</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.dynamic', ['schedule' => $schedule->id, 'type' => 'mapa01']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 rounded-lg shadow-2xs transition min-h-[38px]" title="FR.MAPA.01 Merencanakan Aktivitas dan Proses Asesmen (Data Asesor)">
                <span>FR.MAPA.01</span>
            </a>

            <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'mapa02']) }}" target="_blank"
               class="inline-flex items-center gap-1 px-2.5 py-2 text-xs font-medium bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-lg border border-slate-200 transition min-h-[38px]" title="Fixed Master PDF: FR.MAPA.02">
                <span>MAPA.02</span>
            </a>

            @php
                $effectiveScheduleIas = $schedule->getEffectiveInstruments();
            @endphp

            @if(in_array('IA02', $effectiveScheduleIas, true))
                <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'ia02']) }}" target="_blank"
                   class="inline-flex items-center gap-1 px-2.5 py-2 text-xs font-medium bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-lg border border-slate-200 transition min-h-[38px]" title="Fixed Master PDF: FR.IA.02 Tugas Praktik Demonstrasi">
                    <span>IA.02</span>
                </a>
            @endif

            @if(in_array('IA05', $effectiveScheduleIas, true))
                <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'ia05b']) }}" target="_blank"
                   class="inline-flex items-center gap-1 px-2.5 py-2 text-xs font-medium bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-lg border border-slate-200 transition min-h-[38px]" title="Fixed Master PDF: FR.IA.05.B Kunci Jawaban">
                    <span>IA.05.B Kunci</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Assessees Section --}}
    <div class="space-y-4">
        {{-- Search Assessees Bar --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <form action="{{ route('admin.dokumen-muk.show', $schedule) }}" method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 text-xs">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari asesi berdasarkan nama, email, atau NIK..."
                        class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none text-slate-900 placeholder:text-slate-400 transition shadow-2xs">
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg transition text-xs shadow-sm flex items-center justify-center gap-1.5 whitespace-nowrap min-h-[38px]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>Cari Asesi</span>
                    </button>
                    @if(!empty($search))
                        <a href="{{ route('admin.dokumen-muk.show', $schedule) }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-lg transition text-xs flex items-center justify-center whitespace-nowrap min-h-[38px]">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Assessees Table Card --}}
        <div id="muk-peserta-table-container" data-ajax-container="muk-peserta" data-skeleton-cols="5" data-skeleton-rows="6">
            @include('admin.dokumen-muk._peserta_table')
        </div>
    </div>

    {{-- Modal Ekspor Excel Permohonan Blangko BNSP --}}
    <div x-show="exportModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="exportModalOpen" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" 
                 @click="exportModalOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="exportModalOpen" 
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                
                <form action="{{ route('admin.dokumen-muk.export-excel-biodata', $schedule->id) }}" method="GET" target="_blank" @submit="exportModalOpen = false">
                    <input type="hidden" name="kode_sumber_anggaran" :value="sumberAnggaran">
                    <input type="hidden" name="kode_kementerian" :value="kementerianCode">

                    <div class="px-6 py-5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg shrink-0">
                                📊
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900" id="modal-title">Ekspor Permohonan Blangko BNSP</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Atur parameter Sumber Anggaran &amp; Kementerian sebelum mengunduh Excel.</p>
                            </div>
                        </div>
                        <button type="button" @click="exportModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-5 text-xs">
                        {{-- Sumber Anggaran --}}
                        <div>
                            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Sumber Anggaran <span class="text-rose-500">*</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach(\App\Services\BnspReferenceService::getSumberAnggaranList() as $sa)
                                    <label class="flex items-start gap-2.5 p-2.5 rounded-xl border border-slate-200 hover:border-brand-500 cursor-pointer transition"
                                           :class="sumberAnggaran === '{{ $sa['code'] }}' ? 'border-brand-600 bg-brand-50/50 text-brand-900 font-bold' : 'text-slate-700'">
                                        <input type="radio" name="temp_sumber" value="{{ $sa['code'] }}" x-model="sumberAnggaran" class="mt-0.5 text-brand-600 focus:ring-brand-500">
                                        <div class="leading-tight">
                                            <span class="block text-[11px] font-mono text-slate-400">Kode {{ $sa['code'] }}</span>
                                            <span class="text-xs">{{ $sa['name'] }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Kementerian / Lembaga --}}
                        <div class="relative">
                            <label class="block font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Instansi Pemberi Anggaran / Kementerian <span class="text-rose-500">*</span>
                            </label>
                            <button type="button" @click="kemenOpen = !kemenOpen"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-xs flex items-center justify-between text-left focus:ring-2 focus:ring-brand-500 focus:outline-none cursor-pointer">
                                <div class="truncate">
                                    <span class="font-mono font-bold text-brand-700 bg-brand-50 px-1.5 py-0.5 rounded text-[10px] mr-1" x-text="'[' + kementerianCode + ']'"></span>
                                    <span x-text="selectedKemenName" class="font-medium text-slate-800"></span>
                                </div>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            {{-- Dropdown Searchable --}}
                            <div x-show="kemenOpen" @click.away="kemenOpen = false" x-transition
                                 class="absolute z-50 left-0 right-0 mt-1 bg-white rounded-xl border border-slate-200 shadow-2xl overflow-hidden max-h-56 flex flex-col">
                                <div class="p-2 border-b border-slate-100 bg-slate-50">
                                    <input type="text" x-model="kemenSearch" placeholder="Cari nama kementerian / kode (158 instansi)..." 
                                           class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-brand-500"
                                           @click.stop>
                                </div>
                                <div class="overflow-y-auto divide-y divide-slate-100 flex-1">
                                    <template x-for="k in filteredKemen" :key="k.code">
                                        <button type="button" @click="selectKemen(k)"
                                                class="w-full px-3.5 py-2 text-left text-xs hover:bg-brand-50 hover:text-brand-700 transition flex items-center justify-between cursor-pointer">
                                            <span x-text="k.name" class="font-medium text-slate-800 truncate mr-2"></span>
                                            <span x-text="'Kode ' + k.code" class="text-[10px] font-mono text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded shrink-0"></span>
                                        </button>
                                    </template>
                                    <div x-show="filteredKemen.length === 0" class="p-3 text-center text-xs text-slate-400">
                                        Kementerian tidak ditemukan
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Notice: Kode Jadwal Dikosongkan --}}
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-xs flex items-start gap-2.5">
                            <span class="text-base shrink-0">ℹ️</span>
                            <div class="leading-relaxed">
                                <strong>Ketentuan Kolom Kode Jadwal:</strong> Sesuai format BNSP, kolom <em>KODE JADWAL</em> sengaja dikosongkan agar Anda dapat mengisinya secara cepat dengan kode jadwal resmi dari portal BNSP.
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button" @click="exportModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Unduh Berkas Excel (.xls)</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
