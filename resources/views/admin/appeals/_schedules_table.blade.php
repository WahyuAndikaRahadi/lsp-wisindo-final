<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between items-start gap-2">
        <div class="flex items-center gap-2">
            <h2 class="text-sm font-bold text-slate-900">
                Daftar Jadwal Asesmen ({{ $schedules->total() }})
            </h2>
            @if(isset($filter) && $filter !== 'all')
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                    Filter: {{ str_replace('_', ' ', $filter) }}
                </span>
            @endif
        </div>
        @if(!empty($search))
            <span class="text-xs text-slate-500">Hasil pencarian untuk: <strong class="text-slate-800">"{{ $search }}"</strong></span>
        @endif
    </div>

    @if($schedules->isEmpty())
        <div class="p-12 text-center space-y-3">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="text-slate-700 font-bold text-sm">Tidak Ada Jadwal Asesmen Ditemukan</div>
            <p class="text-xs text-slate-400 max-w-sm mx-auto">
                @if(!empty($search))
                    Tidak ditemukan jadwal asesmen dengan kata kunci "{{ $search }}". Silakan gunakan kata kunci lain atau reset pencarian.
                @else
                    Belum ada jadwal asesmen yang sesuai dengan filter yang dipilih.
                @endif
            </p>
            @if(!empty($search) || (isset($filter) && $filter !== 'all'))
                <a href="{{ route('admin.appeals.index') }}" data-ajax-reset="schedules" class="inline-block px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition">
                    Reset Filter &amp; Pencarian
                </a>
            @endif
        </div>
    @else
        {{-- Mobile Cards Layout (< md) --}}
        <div class="block md:hidden divide-y divide-slate-100">
            @foreach($schedules as $sch)
                <div class="p-4 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-md bg-brand-50 text-brand-700 border border-brand-100">
                            {{ $sch->code }}
                        </span>
                        
                        {{-- Banding Badge Mobile --}}
                        @if($sch->pending_appeals_count > 0)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-rose-100 text-rose-800 border border-rose-300 animate-pulse">
                                <span>⚠️ {{ $sch->appeals_count }} Banding</span>
                                <span class="bg-rose-600 text-white px-1.5 py-0.2 rounded-full text-[9px]">{{ $sch->pending_appeals_count }} Baru</span>
                            </span>
                        @elseif($sch->appeals_count > 0)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                {{ $sch->appeals_count }} Banding
                            </span>
                        @else
                            <span class="text-[11px] text-slate-400">0 Banding</span>
                        @endif
                    </div>

                    <div>
                        <h3 class="font-bold text-slate-900 text-sm leading-snug">
                            {{ $sch->name ?? $sch->scheme?->name }}
                        </h3>
                        <p class="text-xs text-brand-600 font-medium mt-0.5">
                            {{ $sch->scheme?->name }} ({{ $sch->scheme?->code }})
                        </p>
                    </div>

                    <div class="text-xs text-slate-600 space-y-1.5 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500">TUK: <strong class="text-slate-800">{{ $sch->tuk_name ?: '-' }}</strong></span>
                            <span>{{ \Carbon\Carbon::parse($sch->date)->isoFormat('D MMM Y') }}</span>
                        </div>
                        <div class="text-[11px] pt-1 border-t border-slate-200/60 flex items-center justify-between">
                            <span class="text-slate-500">Umpan Balik (FR.AK.03):</span>
                            @if($sch->assessments_count > 0 && $sch->ak03_items_count >= $sch->assessments_count)
                                <span class="font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded">
                                    ✓ {{ $sch->ak03_items_count }}/{{ $sch->assessments_count }} Asesi
                                </span>
                            @elseif($sch->ak03_items_count > 0)
                                <span class="font-bold text-amber-700 bg-amber-100/80 px-2 py-0.5 rounded">
                                    {{ $sch->ak03_items_count }}/{{ $sch->assessments_count }} Asesi
                                </span>
                            @else
                                <span class="text-slate-500 font-medium">
                                    0/{{ $sch->assessments_count }} Asesi
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="pt-1">
                        <a href="{{ route('admin.appeals.show', $sch->id) }}" 
                           class="w-full flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg transition shadow-2xs">
                            <span>Buka Umpan &amp; Banding</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Desktop Table (>= md) --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 text-slate-500 uppercase tracking-wider text-[11px] border-b border-slate-200">
                        <th class="px-5 py-3 font-bold">Kode &amp; Jadwal Asesmen</th>
                        <th class="px-5 py-3 font-bold">Skema &amp; TUK</th>
                        <th class="px-5 py-3 font-bold">Asesor Penguji</th>
                        <th class="px-5 py-3 font-bold text-center">Umpan Balik (FR.AK.03)</th>
                        <th class="px-5 py-3 font-bold text-center">Banding (FR.AK.04)</th>
                        <th class="px-5 py-3 font-bold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($schedules as $sch)
                        <tr class="hover:bg-slate-50/70 transition">
                            {{-- 1. Kode & Jadwal --}}
                            <td class="px-5 py-4 align-middle">
                                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-brand-50 text-brand-700 border border-brand-100">
                                    {{ $sch->code }}
                                </span>
                                <div class="font-bold text-slate-900 mt-1 max-w-xs truncate" title="{{ $sch->name }}">
                                    {{ $sch->name ?? $sch->scheme?->name }}
                                </div>
                                <span class="text-[11px] text-slate-400">Total: {{ $sch->assessments_count }} Peserta</span>
                            </td>

                            {{-- 2. Skema & TUK --}}
                            <td class="px-5 py-4 align-middle">
                                <div class="font-semibold text-slate-800 max-w-xs truncate" title="{{ $sch->scheme?->name }}">
                                    {{ $sch->scheme?->name ?? '-' }}
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1.5">
                                    <span>TUK: <strong>{{ $sch->tuk_name ?: '-' }}</strong></span>
                                    <span>&bull;</span>
                                    <span>{{ \Carbon\Carbon::parse($sch->date)->isoFormat('D MMM Y') }}</span>
                                </div>
                            </td>

                            {{-- 3. Asesor Penguji --}}
                            <td class="px-5 py-4 align-middle text-slate-700">
                                @if($sch->assessors->isNotEmpty())
                                    <div class="font-medium text-slate-800 max-w-[200px] truncate" title="{{ $sch->assessors->pluck('full_name')->join(', ') }}">
                                        {{ $sch->assessors->pluck('full_name')->join(', ') }}
                                    </div>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Belum ditugaskan</span>
                                @endif
                            </td>

                            {{-- 4. Umpan Balik (FR.AK.03): Ratio "30/30 asesi" --}}
                            <td class="px-5 py-4 align-middle text-center">
                                @if($sch->assessments_count > 0 && $sch->ak03_items_count >= $sch->assessments_count)
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-2xs">
                                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span>{{ $sch->ak03_items_count }}/{{ $sch->assessments_count }} Asesi</span>
                                    </span>
                                    <div class="text-[10px] text-emerald-700 font-bold mt-0.5">Lengkap (100%)</div>
                                @elseif($sch->ak03_items_count > 0)
                                    @php
                                        $pct = $sch->assessments_count > 0 ? round(($sch->ak03_items_count / $sch->assessments_count) * 100) : 0;
                                    @endphp
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                        {{ $sch->ak03_items_count }}/{{ $sch->assessments_count }}
                                    </span>
                                    <div class="text-[10px] text-amber-700 font-semibold mt-0.5">{{ $pct }}% Mengisi</div>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                        0/{{ $sch->assessments_count }} Asesi
                                    </span>
                                    <div class="text-[10px] text-slate-400 mt-0.5">Belum ada</div>
                                @endif
                            </td>

                            {{-- 5. Banding (FR.AK.04): Count --}}
                            <td class="px-5 py-4 align-middle text-center">
                                @if($sch->pending_appeals_count > 0)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-300 shadow-2xs">
                                        <span>{{ $sch->appeals_count }} Banding</span>
                                    </span>
                                    <div class="text-[10px] text-rose-700 font-bold mt-0.5">Perlu Respon Putusan</div>
                                @elseif($sch->appeals_count > 0)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                        {{ $sch->appeals_count }} Banding
                                    </span>
                                    <div class="text-[10px] text-purple-700 font-medium mt-0.5">Sudah Diputuskan</div>
                                @else
                                    <span class="text-xs text-slate-400">0 Banding</span>
                                @endif
                            </td>

                            {{-- 6. Aksi --}}
                            <td class="px-5 py-4 align-middle text-right">
                                <a href="{{ route('admin.appeals.show', $sch->id) }}" 
                                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg transition shadow-2xs">
                                    <span>Buka Detail</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $schedules->links() }}
        </div>
    @endif
</div>
