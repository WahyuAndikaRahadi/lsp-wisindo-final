@php
    $jadwals = $jadwals ?? $schedules ?? collect();
@endphp

@if($jadwals->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 p-8 text-center shadow-sm">
        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-slate-700">Belum Ada Penugasan</h3>
        <p class="text-xs text-slate-500 mt-1">
            @if(request()->filled('search') || (request()->filled('status') && request('status') !== 'all') || request()->filled('start_date') || request()->filled('end_date') || (request()->filled('sort') && request('sort') !== 'date_desc'))
                Tidak ada jadwal asesmen yang cocok dengan kriteria filter atau pencarian Anda. <a href="{{ route('asesor.jadwal.index') }}" data-ajax-reset="asesor-jadwal" class="text-brand-600 font-bold hover:underline">Reset Filter</a>
            @else
                Anda belum ditugaskan pada jadwal asesmen mana pun.
            @endif
        </p>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @foreach($jadwals as $jadwal)
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between hover:shadow-md transition">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded bg-brand-50 text-brand-700 font-mono text-xs font-bold">
                            {{ $jadwal->code }}
                        </span>
                        @php
                            $isClosedForMe = $jadwal->isClosedForAssessor();
                            $statusBadge = $isClosedForMe || $jadwal->is_closed || in_array($jadwal->status, ['COMPLETED', 'FINISHED'])
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                : match($jadwal->status ?? 'ACTIVE') {
                                    'CANCELLED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-blue-50 text-blue-700 border-blue-200',
                                };
                            $statusLabel = $isClosedForMe && ! $jadwal->is_closed
                                ? 'SELESAI'
                                : ($jadwal->is_closed || in_array($jadwal->status, ['COMPLETED', 'FINISHED']) ? 'SELESAI' : ($jadwal->status ?? 'AKTIF'));
                        @endphp
                        <span class="px-2.5 py-0.5 rounded text-xs font-semibold border {{ $statusBadge }}">
                            {{ $statusLabel }}
                        </span>
                    </div>

                    <div>
                        <h3 class="text-base font-bold text-slate-900 leading-snug">
                            {{ $jadwal->name ?? $jadwal->scheme->name }}
                        </h3>
                        <p class="text-xs text-brand-600 font-medium mt-0.5">
                            Skema: {{ $jadwal->scheme->name }} ({{ $jadwal->scheme->code }})
                        </p>
                    </div>

                    <div class="space-y-1.5 text-xs text-slate-600 pt-2 border-t border-slate-100">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span>TUK: <strong class="text-slate-800">{{ $jadwal->tuk_name }}</strong></span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ $jadwal->formatted_date_range }}</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>{{ substr($jadwal->start_time, 0, 5) }} - {{ substr($jadwal->end_time, 0, 5) }} WIB</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            @php
                                $myCandidatesCount = $jadwal->assessorAssessments()->count();
                                $allCandidatesCount = $jadwal->assessments->count();
                            @endphp
                            <span>Jumlah Asesi: <strong class="text-slate-800">{{ $myCandidatesCount }} Asesi</strong> @if($allCandidatesCount > $myCandidatesCount)<span class="text-slate-400 font-normal">({{ $allCandidatesCount }} total jadwal)</span>@endif</span>
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-slate-100 flex items-center gap-2">
                    <a href="{{ route('asesor.schedule.surat-tugas', $jadwal->id) }}" class="inline-flex items-center justify-center px-3.5 py-2.5 min-h-[42px] text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg shadow-2xs transition shrink-0" title="Lihat Surat Tugas Asesor">
                        <svg class="w-4 h-4 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Surat Tugas</span>
                    </a>
                    @cannot('enter-schedule', $jadwal)
                        <button type="button" disabled
                                class="flex-1 inline-flex items-center justify-center px-4 py-2.5 min-h-[42px] text-xs font-bold uppercase tracking-wider bg-slate-100 text-slate-400 border border-slate-200 rounded-lg cursor-not-allowed transition select-none"
                                title="Silakan simpan tanda tangan digital pada profil Anda terlebih dahulu untuk masuk ke jadwal">
                            <svg class="w-4 h-4 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m7-7V7a5 5 0 00-10 0v4h10zM5 11h14a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2z"/>
                            </svg>
                            <span>TTD Profil Diperlukan</span>
                        </button>
                    @else
                        @if($isClosedForMe)
                            <a href="{{ route('asesor.schedule.ruang-uji', $jadwal->id) }}" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 min-h-[42px] text-xs font-bold uppercase tracking-wider bg-slate-800 hover:bg-slate-900 text-white rounded-lg shadow-sm transition">
                                <span>Lihat Ruang Uji</span>
                                <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        @else
                            <a href="{{ route('asesor.schedule.ruang-uji', $jadwal->id) }}" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 min-h-[42px] text-xs font-bold uppercase tracking-wider bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition">
                                <span>Masuk Ruang Uji</span>
                                <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        @endif
                    @endcannot
                </div>
            </div>
        @endforeach
    </div>

    @if(method_exists($jadwals, 'hasPages') && $jadwals->hasPages())
        <div class="pt-4">
            {{ $jadwals->links() }}
        </div>
    @endif
@endif
