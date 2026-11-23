@props([
    'schedule',
    'active' => 'ruang-uji',
])

@php
    $schId = $schedule->id;
    $navItems = [
        [
            'key' => 'apl',
            'label' => '1. Verifikasi APL',
            'route' => route('asesor.schedule.instrument', ['schedule' => $schId, 'code' => 'apl']),
            'desc' => 'APL.01 & APL.02',
        ],
        [
            'key' => 'mapa',
            'label' => '2. FR.MAPA.01',
            'route' => route('asesor.schedule.mapa', $schId),
            'desc' => 'Perencanaan Asesmen',
        ],
        [
            'key' => 'ak04',
            'label' => '3. FR.AK.04',
            'route' => route('asesor.schedule.ak04', $schId),
            'desc' => 'Pengenalan Banding',
        ],
        [
            'key' => 'ak01',
            'label' => '4. FR.AK.01',
            'route' => route('asesor.schedule.ak01', $schId),
            'desc' => 'Persetujuan & Bukti',
        ],
        [
            'key' => 'ak07',
            'label' => '5. FR.AK.07',
            'route' => route('asesor.schedule.ak07', $schId),
            'desc' => 'Penyesuaian Wajar',
        ],
        [
            'key' => 'ruang-uji',
            'label' => '6. Ruang Uji',
            'route' => route('asesor.schedule.ruang-uji', $schId),
            'desc' => 'MUK Praktik & CBT',
        ],
        [
            'key' => 'ak02',
            'label' => '7. FR.AK.02',
            'route' => route('asesor.schedule.ak02', $schId),
            'desc' => 'Rekaman & Rekomendasi',
        ],
        [
            'key' => 'reports',
            'label' => '8. Laporan Asesmen',
            'route' => route('asesor.schedule.reports', $schId),
            'desc' => 'AK.05 & AK.06 Laporan',
        ],
    ];
@endphp

<div class="bg-white rounded-xl border border-slate-200 shadow-xs p-2 overflow-x-auto">
    <div class="flex items-center space-x-1.5 min-w-max">
        <a href="{{ route('asesor.jadwal.index') }}" 
           class="inline-flex items-center space-x-1 px-3 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition mr-1 border-r border-slate-200 pr-3"
           title="Kembali ke Daftar Jadwal">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span class="hidden sm:inline">Jadwal</span>
        </a>

        @php
            $isClosedForMe = $schedule->isClosedForAssessor();
        @endphp

        @if($isClosedForMe)
            <span class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-300 mr-1.5 shrink-0" title="{{ $schedule->is_closed ? 'Jadwal telah ditutup dan dikunci resmi. Seluruh formulir dalam mode lihat (read-only).' : 'Asesmen telah selesai dan ditutup. Seluruh formulir dalam mode lihat (read-only).' }}">
                <svg class="w-3.5 h-3.5 mr-1 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>{{ $schedule->is_closed ? 'Jadwal Ditutup' : 'Selesai (Read-Only)' }}</span>
            </span>
        @endif

        @foreach($navItems as $item)
            @php
                $isUnlocked = $schedule->isStepUnlocked($item['key']);
                $isActive = ($active === $item['key']);
            @endphp
            @if($isUnlocked)
                <a href="{{ $item['route'] }}" 
                   class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-bold transition {{ $isActive ? 'bg-brand-600 text-white shadow-xs' : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100 bg-white' }}">
                    <span>{{ $item['label'] }}</span>
                    <span class="hidden md:inline text-[10px] ml-1.5 {{ $isActive ? 'text-brand-100' : 'text-slate-400' }} font-normal">({{ $item['desc'] }})</span>
                </a>
            @else
                <span class="inline-flex items-center px-3 py-2 rounded-lg text-xs font-semibold text-slate-400 bg-slate-50 border border-slate-200 cursor-not-allowed select-none opacity-70"
                      title="Tahap ini terkunci. Selesaikan tahap sebelumnya untuk membuka."
                      data-locked-step="{{ $item['key'] }}"
                      data-href="{{ $item['route'] }}">
                    <svg class="w-3.5 h-3.5 mr-1 text-slate-400 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                    <span>{{ $item['label'] }}</span>
                    <span class="hidden md:inline text-[10px] ml-1 text-slate-400 font-normal">({{ $item['desc'] }})</span>
                    <span class="text-[10px] ml-1.5 font-bold text-amber-700 bg-amber-100 px-1.5 py-0.5 rounded">Terkunci</span>
                </span>
            @endif
        @endforeach
    </div>
</div>
