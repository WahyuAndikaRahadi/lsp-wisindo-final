@props([
    'currentStep' => 'apl',
    'assessment' => null,
])

@php
    $normalizedCurrent = strtolower((string) $currentStep);
    if (in_array($normalizedCurrent, ['ak01', 'ak07', 'ia01', 'ia01b', 'ia02', 'ia03', 'ia05', 'ruang-uji', 'ruang_uji'])) {
        $normalizedCurrent = 'ruang-uji';
    }

    $allSteps = [
        [
            'code' => 'apl',
            'title' => 'FR.APL.01 & 02',
            'subtitle' => 'Pendaftaran & Asesmen Mandiri',
            'badge' => 'Tahap 1',
            'url' => $assessment ? route('asesi.assessment.apl', $assessment) : null,
        ],
        [
            'code' => 'ruang-uji',
            'title' => 'Ruang Uji',
            'subtitle' => 'Pelaksanaan Asesmen Kompetensi',
            'badge' => 'Tahap 2',
            'url' => ($assessment && $assessment->apl_status === 'APPROVED') ? route('asesi.assessment.ruang-uji', $assessment) : null,
        ],
        [
            'code' => 'finish',
            'title' => 'Selesai & Rekomendasi',
            'subtitle' => 'Hasil Akhir Asesmen Mandiri',
            'badge' => 'Tahap 3',
            'url' => null,
        ],
    ];

    $stepCodes = array_column($allSteps, 'code');
    $currentIndex = 0;
    foreach ($stepCodes as $idx => $code) {
        if ($code === $normalizedCurrent) {
            $currentIndex = $idx;
            break;
        }
    }

    $aplApproved = $assessment ? ($assessment->apl_status === 'APPROVED') : true;
    $isAssessmentCompleted = $assessment ? ($assessment->status === 'COMPLETED') : false;
    if ($isAssessmentCompleted) {
        $currentIndex = 2;
    }
@endphp

<div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
        <div>
            <h3 class="font-bold text-slate-800 text-sm">Alur Asesmen Kompetensi</h3>
            <p class="text-xs text-slate-500">Tahapan proses sertifikasi LSP PrO Indo</p>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 bg-brand-50 text-brand-700 rounded-full">
            Tahap {{ $currentIndex + 1 }} dari {{ count($allSteps) }}
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($allSteps as $idx => $step)
            @php
                $isCurrent = ($idx === $currentIndex) && !$isAssessmentCompleted;
                $isCompleted = ($idx < $currentIndex) || ($isAssessmentCompleted && $idx <= $currentIndex);
                $isLocked = ($idx > $currentIndex) && !$isAssessmentCompleted;

                // Special lock: if APL not approved, all subsequent steps are locked
                if (!$aplApproved && $idx > 0) {
                    $isLocked = true;
                    if ($idx === $currentIndex) {
                        $isCurrent = false;
                    }
                }
            @endphp

            <div class="relative p-4 rounded-xl border transition {{ $isCurrent ? 'bg-indigo-50/70 border-indigo-300 ring-2 ring-indigo-200' : ($isCompleted ? 'bg-emerald-50/50 border-emerald-200' : 'bg-slate-50 border-slate-200 opacity-75') }}">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        @if($isCompleted)
                            <div class="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs font-bold shadow-xs">
                                ✓
                            </div>
                        @elseif($isCurrent)
                            <div class="w-6 h-6 rounded-full bg-brand-600 text-white flex items-center justify-center text-xs font-bold shadow-xs animate-pulse">
                                {{ $idx + 1 }}
                            </div>
                        @else
                            <div class="w-6 h-6 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center text-xs font-bold">
                                {{ $idx + 1 }}
                            </div>
                        @endif
                        <span class="text-xs font-bold {{ $isCurrent ? 'text-brand-900 font-extrabold' : ($isCompleted ? 'text-emerald-900' : 'text-slate-600') }}">
                            {{ $step['title'] }}
                        </span>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $isCurrent ? 'bg-brand-100 text-brand-800' : ($isCompleted ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600') }}">
                        {{ $step['badge'] }}
                    </span>
                </div>
                <p class="text-xs text-slate-600 mb-2">
                    {{ $step['subtitle'] }}
                </p>
                <div class="flex items-center justify-between pt-2 border-t {{ $isCurrent ? 'border-indigo-200' : ($isCompleted ? 'border-emerald-200' : 'border-slate-200') }}">
                    @if($isCompleted)
                        <span class="text-[11px] font-semibold text-emerald-700 flex items-center gap-1">
                            <span>✓ Selesai</span>
                        </span>
                        @if(!empty($step['url']))
                            <a href="{{ $step['url'] }}" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 underline">
                                Buka Formulir &rarr;
                            </a>
                        @endif
                    @elseif($isCurrent)
                        <span class="text-[11px] font-bold text-brand-600 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-brand-600 animate-ping"></span>
                            Sedang Berlangsung
                        </span>
                    @else
                        <span class="text-[11px] text-slate-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Terkunci
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
