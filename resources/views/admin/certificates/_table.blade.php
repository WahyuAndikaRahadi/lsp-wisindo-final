<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    {{-- Mobile Cards (< md) --}}
    <div class="block md:hidden divide-y divide-slate-100">
        @forelse($schedules as $sch)
            @php
                $isComplete = ($sch->competent_candidates > 0 && $sch->uploaded_certificates_count >= $sch->competent_candidates);
            @endphp
            <div class="p-4 space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <span class="font-mono text-xs font-bold text-brand-700 bg-brand-50 px-2 py-0.5 rounded border border-brand-200">
                        {{ $sch->code }}
                    </span>
                    <div>
                        @if($sch->competent_candidates == 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600">
                                Belum ada asesi K
                            </span>
                        @elseif($isComplete)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-300">
                                ✓ Lengkap ({{ $sch->uploaded_certificates_count }}/{{ $sch->competent_candidates }})
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-300">
                                {{ $sch->uploaded_certificates_count }}/{{ $sch->competent_candidates }} Terbit
                            </span>
                        @endif
                    </div>
                </div>

                <div>
                    <h3 class="font-bold text-slate-900 text-sm leading-snug">{{ $sch->name }}</h3>
                    <p class="text-xs text-brand-600 font-medium mt-0.5">{{ $sch->scheme->name ?? '-' }}</p>
                </div>

                <div class="text-xs text-slate-600 space-y-1 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                        <span>TUK: <strong class="text-slate-700">{{ $sch->tuk_name ?? ($sch->tuk->name ?? '-') }}</strong></span>
                        <span>{{ \Carbon\Carbon::parse($sch->date)->isoFormat('D MMM Y') }}</span>
                    </div>
                    <div class="text-[11px] text-slate-700 font-semibold pt-1 border-t border-slate-200/60 flex items-center justify-between">
                        <span>Asesi Kompeten:</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $sch->competent_candidates > 0 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                            {{ $sch->competent_candidates }} Asesi (K)
                        </span>
                    </div>
                </div>

                <div class="pt-1">
                    <a href="{{ route('admin.certificates.schedule', $sch->id) }}" 
                       class="w-full px-4 py-2.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5 min-h-[42px]">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <span>Kelola Sertifikat</span>
                    </a>
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-slate-500 text-xs">
                Tidak ada jadwal asesmen yang ditemukan.
            </div>
        @endforelse
    </div>

    {{-- Desktop Table (md+) --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3">Jadwal Asesmen</th>
                    <th class="px-5 py-3">Skema Sertifikasi</th>
                    <th class="px-5 py-3">TUK / Tanggal</th>
                    <th class="px-5 py-3 text-center whitespace-nowrap">Asesi Kompeten (K)</th>
                    <th class="px-5 py-3 text-center whitespace-nowrap">Sertifikat Terunggah</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($schedules as $sch)
                    @php
                        $isComplete = ($sch->competent_candidates > 0 && $sch->uploaded_certificates_count >= $sch->competent_candidates);
                    @endphp
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-5 py-4">
                            <div class="font-bold text-slate-900 leading-snug">{{ $sch->name }}</div>
                            <span class="font-mono text-xs text-brand-700">{{ $sch->code }}</span>
                        </td>
                        <td class="px-5 py-4 text-xs text-slate-700">
                            <div class="font-semibold leading-snug">{{ $sch->scheme->name ?? '-' }}</div>
                            <span class="text-slate-400 font-mono text-[11px]">{{ $sch->scheme->code ?? '' }}</span>
                        </td>
                        <td class="px-5 py-4 text-xs text-slate-600 whitespace-nowrap">
                            <div>{{ $sch->tuk_name ?? ($sch->tuk->name ?? '-') }}</div>
                            <span class="text-slate-400">{{ \Carbon\Carbon::parse($sch->date)->isoFormat('D MMMM Y') }}</span>
                        </td>
                        <td class="px-5 py-4 text-center whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap {{ $sch->competent_candidates > 0 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                {{ $sch->competent_candidates }} Asesi (K)
                            </span>
                        </td>
                        <td class="px-5 py-4 text-center whitespace-nowrap">
                            @if($sch->competent_candidates == 0)
                                <span class="text-xs text-slate-400">Belum ada asesi K</span>
                            @elseif($isComplete)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-300 whitespace-nowrap">
                                    ✓ Lengkap ({{ $sch->uploaded_certificates_count }}/{{ $sch->competent_candidates }})
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-300 whitespace-nowrap">
                                    {{ $sch->uploaded_certificates_count }} / {{ $sch->competent_candidates }} Terbit
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right whitespace-nowrap">
                            <a href="{{ route('admin.certificates.schedule', $sch->id) }}" 
                               class="px-3 py-1.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-xs transition inline-flex items-center gap-1.5 whitespace-nowrap min-h-[36px]">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                <span>Upload &amp; Kelola Sertifikat</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500 text-xs">
                            Tidak ada jadwal asesmen yang ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($schedules->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $schedules->links() }}
        </div>
    @endif
</div>
