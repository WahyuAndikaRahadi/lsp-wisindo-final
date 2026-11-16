<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    @php
        $hasFilter = request()->filled('search') || request()->filled('scheme_id') || request()->filled('start_date') || request()->filled('end_date') || !empty($search) || !empty($schemeId) || !empty($startDate) || !empty($endDate);
    @endphp
    <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between items-start gap-1">
        <h2 class="text-sm font-bold text-slate-900">
            Daftar Jadwal Asesmen ({{ $schedules->total() }})
        </h2>
        @if($hasFilter)
            <span class="text-xs text-slate-500">Menampilkan hasil penyaringan filter jadwal</span>
        @endif
    </div>

    @if($schedules->isEmpty())
        <div class="p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <div class="text-slate-600 font-semibold text-sm">Tidak ada jadwal asesmen ditemukan</div>
            <p class="text-xs text-slate-400 mt-1">
                @if($hasFilter)
                    Tidak ditemukan jadwal dengan kriteria filter atau pencarian Anda. Silakan coba kriteria lain.
                @else
                    Belum ada jadwal asesmen tersedia untuk ekspor dokumen MUK.
                @endif
            </p>
            @if($hasFilter)
                <a href="{{ route('admin.dokumen-muk.index') }}" data-ajax-reset="muk" class="inline-block mt-4 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition">
                    Reset Filter
                </a>
            @endif
        </div>
    @else
        {{-- Mobile Cards (< md) --}}
        <div class="block md:hidden divide-y divide-slate-100">
            @foreach($schedules as $sch)
                <div class="p-4 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-md bg-brand-50 text-brand-700 border border-brand-100">
                            {{ $sch->code }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            {{ $sch->assessments_count }} Asesi
                        </span>
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
                        <div class="text-[11px] pt-1 border-t border-slate-200/60 text-slate-600">
                            <span class="text-slate-500">Asesor Penguji:</span>
                            <div class="font-medium text-slate-800 mt-0.5">
                                @if($sch->assessors->isNotEmpty())
                                    {{ $sch->assessors->pluck('full_name')->join(', ') }}
                                @else
                                    <span class="text-slate-400 italic">Belum ditugaskan</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="pt-1">
                        <a href="{{ route('admin.dokumen-muk.show', $sch) }}"
                           class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition min-h-[42px]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Kelola & Cetak Dokumen MUK</span>
                            <svg class="w-3.5 h-3.5 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Desktop Table (md+) --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Kode & Jadwal Asesmen</th>
                        <th class="px-6 py-3.5">Skema Sertifikasi</th>
                        <th class="px-6 py-3.5">TUK & Tanggal</th>
                        <th class="px-6 py-3.5">Asesor Penguji</th>
                        <th class="px-6 py-3.5 text-center">Jumlah Asesi</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($schedules as $sch)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-3.5 align-middle">
                                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-brand-50 text-brand-700 border border-brand-100 inline-block mb-1">
                                    {{ $sch->code }}
                                </span>
                                <div class="font-bold text-slate-900 leading-snug">
                                    {{ $sch->name ?? $sch->scheme?->name }}
                                </div>
                            </td>
                            <td class="px-6 py-3.5 align-middle text-xs">
                                <div class="font-semibold text-slate-900 leading-snug">
                                    {{ $sch->scheme?->name }}
                                </div>
                                <span class="text-slate-400 font-mono text-[11px]">{{ $sch->scheme?->code }}</span>
                            </td>
                            <td class="px-6 py-3.5 align-middle text-xs text-slate-600 whitespace-nowrap">
                                <div class="font-medium text-slate-900">{{ $sch->tuk_name ?: '-' }}</div>
                                <div class="text-slate-400 mt-0.5">{{ \Carbon\Carbon::parse($sch->date)->isoFormat('D MMMM Y') }}</div>
                            </td>
                            <td class="px-6 py-3.5 align-middle text-xs">
                                @if($sch->assessors->isEmpty())
                                    <span class="text-slate-400 italic text-[11px]">Belum ada asesor</span>
                                @elseif($sch->assessors->count() <= 3)
                                    <div class="flex flex-col gap-1 max-w-[280px]">
                                        @foreach($sch->assessors as $asesor)
                                            <div class="inline-flex items-center gap-1.5 text-xs leading-tight">
                                                <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shrink-0"></span>
                                                <span class="font-medium text-slate-800 truncate" title="{{ $asesor->full_name }}">{{ $asesor->full_name }}</span>
                                                @if($asesor->assessor_registration_number)
                                                    <span class="text-[10px] font-mono text-slate-400 shrink-0">({{ $asesor->assessor_registration_number }})</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="flex flex-col gap-1 max-w-[280px]">
                                        @foreach($sch->assessors->take(2) as $asesor)
                                            <div class="inline-flex items-center gap-1.5 text-xs leading-tight">
                                                <span class="w-1.5 h-1.5 rounded-full bg-brand-500 shrink-0"></span>
                                                <span class="font-medium text-slate-800 truncate" title="{{ $asesor->full_name }}">{{ $asesor->full_name }}</span>
                                                @if($asesor->assessor_registration_number)
                                                    <span class="text-[10px] font-mono text-slate-400 shrink-0">({{ $asesor->assessor_registration_number }})</span>
                                                @endif
                                            </div>
                                        @endforeach
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 w-fit" title="{{ $sch->assessors->pluck('full_name')->join(', ') }}">
                                            +{{ $sch->assessors->count() - 2 }} Asesor Lainnya
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 align-middle text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $sch->assessments_count > 0 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $sch->assessments_count }} Asesi
                                </span>
                            </td>
                            <td class="px-6 py-3.5 align-middle text-right whitespace-nowrap">
                                <a href="{{ route('admin.dokumen-muk.show', $sch) }}"
                                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm hover:shadow transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span>Dokumen MUK</span>
                                    <svg class="w-3 h-3 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($schedules->hasPages())
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $schedules->links() }}
            </div>
        @endif
    @endif
</div>
