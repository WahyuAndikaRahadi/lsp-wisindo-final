        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            @if($assessments->isEmpty())
                <div class="p-8 text-center">
                    <div class="w-12 h-12 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Tidak ada asesi ditemukan</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        @if(!empty($search))
                            Tidak ada data asesi yang cocok dengan kata kunci "{{ $search }}".
                        @else
                            Belum ada asesi yang terdaftar pada jadwal asesmen ini.
                        @endif
                    </p>
                    @if(!empty($search))
                        <a href="{{ route('admin.dokumen-muk.show', $schedule) }}" class="inline-block mt-3 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition">
                            Reset Pencarian
                        </a>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto min-h-[360px]">
                    <table class="w-full text-xs">
                        <thead class="hidden md:table-header-group bg-slate-50 text-left font-semibold text-slate-600 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 whitespace-nowrap w-12">No</th>
                                <th class="px-4 py-3 whitespace-nowrap">Nama Asesi</th>
                                <th class="px-4 py-3 whitespace-nowrap">Asesor</th>
                                <th class="px-4 py-3 whitespace-nowrap">Hasil Akhir</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Dokumen MUK</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($assessments as $idx => $assessment)
                                @php
                                    $openUp = $assessments->count() > 3 && ($loop->last || $loop->iteration >= $assessments->count() - 1);
                                @endphp

                                <tr class="flex flex-col md:table-row p-4 md:p-0 hover:bg-slate-50/70 transition space-y-2.5 md:space-y-0">
                                    <td class="hidden md:table-cell px-4 py-3 text-slate-500 font-mono whitespace-nowrap">
                                        {{ $assessments->firstItem() ? $assessments->firstItem() + $idx : $idx + 1 }}
                                    </td>
                                    <td class="px-0 md:px-4 py-0 md:py-3">
                                        <div class="flex items-center justify-between md:hidden pb-1 border-b border-slate-100">
                                            <span class="font-mono text-xs font-bold text-slate-500">
                                                #{{ $assessments->firstItem() ? $assessments->firstItem() + $idx : $idx + 1 }}
                                            </span>
                                            <div>
                                                @if(in_array($assessment->final_result, ['K', 'KOMPETEN'], true))
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">KOMPETEN (K)</span>
                                                @elseif(in_array($assessment->final_result, ['BK', 'BELUM_KOMPETEN'], true))
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">BELUM KOMPETEN (BK)</span>
                                                @elseif($assessment->final_result === 'TIDAK_HADIR')
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700 border border-slate-300">TIDAK HADIR</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">{{ $assessment->status ?? 'PENDING' }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mt-1 md:mt-0">
                                            <div class="font-bold text-slate-800 text-xs sm:text-sm">{{ $assessment->asesi?->full_name }}</div>
                                            <div class="text-[11px] text-slate-400 font-mono">
                                                {{ $assessment->asesi?->email }} &bull; NIK: {{ $assessment->asesi?->profile?->nik ?? '-' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-0 md:px-4 py-0 md:py-3 text-slate-600 whitespace-nowrap text-xs">
                                        <span class="text-slate-400 md:hidden">Asesor: </span>
                                        <strong class="text-slate-800 md:font-normal">{{ $assessment->assessor?->full_name ?? '-' }}</strong>
                                    </td>
                                    <td class="hidden md:table-cell px-4 py-3 whitespace-nowrap">
                                        @if(in_array($assessment->final_result, ['K', 'KOMPETEN'], true))
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">KOMPETEN (K)</span>
                                        @elseif(in_array($assessment->final_result, ['BK', 'BELUM_KOMPETEN'], true))
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">BELUM KOMPETEN (BK)</span>
                                        @elseif($assessment->final_result === 'TIDAK_HADIR')
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700 border border-slate-300">TIDAK HADIR</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">{{ $assessment->status ?? 'PENDING' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-0 md:px-4 py-0 md:py-3 text-left md:text-right whitespace-nowrap pt-1 md:pt-0">
                                        <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'all-muk']) }}" 
                                               target="_blank" 
                                               class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold rounded-md bg-emerald-600 hover:bg-emerald-700 text-white shadow-2xs transition min-h-[32px]" 
                                               title="Cetak & Satukan Seluruh Dokumen MUK Asesi (1 File PDF)">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                                </svg>
                                                <span>Print Semua MUK</span>
                                            </a>

                                            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ak02']) }}" 
                                               target="_blank" 
                                               class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-md bg-brand-50 hover:bg-brand-100 text-brand-700 border border-brand-200 transition min-h-[32px]" 
                                               title="Lihat FR.AK.02 Rekaman Asesmen Langsung">
                                                AK.02
                                            </a>

                                            <div class="relative inline-block text-left" x-data="{ open: false }">
                                                <button type="button" 
                                                        @click="open = !open" 
                                                        @click.outside="open = false"
                                                        @keydown.escape.stop="open = false"
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-md bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 shadow-2xs transition min-h-[32px]"
                                                        :class="{ 'bg-slate-100 ring-2 ring-brand-500/20 border-brand-500 text-brand-700': open }">
                                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                    <span>Semua Dokumen</span>
                                                    <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                    </svg>
                                                </button>

                                                {{-- Dropdown Menu --}}
                                                <div x-show="open" 
                                                     x-cloak
                                                     x-transition:enter="transition ease-out duration-150"
                                                     x-transition:enter-start="transform opacity-0 scale-95"
                                                     x-transition:enter-end="transform opacity-100 scale-100"
                                                     x-transition:leave="transition ease-in duration-100"
                                                     x-transition:leave-start="transform opacity-100 scale-100"
                                                     x-transition:leave-end="transform opacity-0 scale-95"
                                                     class="absolute right-0 {{ $openUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5' }} w-72 sm:w-80 rounded-xl bg-white shadow-xl border border-slate-200 z-50 divide-y divide-slate-100 text-left focus:outline-none"
                                                     style="display: none;">
                                                    
                                                    {{-- Dropdown Header --}}
                                                    <div class="px-3.5 py-2.5 bg-slate-50 {{ $openUp ? 'rounded-b-xl border-t order-last' : 'rounded-t-xl border-b' }} border-slate-100">
                                                        <div class="font-bold text-slate-800 text-xs truncate">{{ $assessment->asesi?->full_name }}</div>
                                                        <div class="text-[11px] text-slate-500">Pilih dokumen MUK untuk dibuka / dicetak:</div>
                                                    </div>

                                                    {{-- Scrollable List of Groups --}}
                                                    <div class="p-2 space-y-3 max-h-[320px] overflow-y-auto">
                                                        {{-- Group 0: Print Seluruh MUK --}}
                                                        <div class="p-1 bg-emerald-50/90 rounded-lg border border-emerald-200">
                                                            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'all-muk']) }}" target="_blank"
                                                               class="flex items-center justify-between px-2.5 py-2 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white transition text-xs font-bold shadow-2xs">
                                                                <div class="flex items-center gap-2 overflow-hidden text-left">
                                                                    <svg class="w-4 h-4 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                                                    </svg>
                                                                    <div class="truncate">
                                                                        <div>Print Semua MUK (1 File)</div>
                                                                        <div class="text-[10px] font-normal text-emerald-100">Satukan APL, MAPA, AK, FRIA, dll</div>
                                                                    </div>
                                                                </div>
                                                                <svg class="w-3.5 h-3.5 text-emerald-200 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                            </a>
                                                        </div>

                                                        {{-- Group 1: Permohonan & Persetujuan --}}
                                                        <div>
                                                            <div class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                                Permohonan & Persetujuan
                                                            </div>
                                                            <div class="space-y-0.5">
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'apl01']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.APL.01</span>
                                                                        <span class="truncate">Permohonan Sertifikasi</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'apl02']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.APL.02</span>
                                                                        <span class="truncate">Asesmen Mandiri</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'mapa01']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.MAPA.01</span>
                                                                        <span class="truncate">Merencanakan Aktivitas</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.fixed', ['schedule' => $schedule->id, 'type' => 'mapa02']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.MAPA.02</span>
                                                                        <span class="truncate">Peta Instrumen Asesmen</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ak01']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.AK.01</span>
                                                                        <span class="truncate">Persetujuan Asesmen</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ak07']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.AK.07</span>
                                                                        <span class="truncate">Penyesuaian Yang Wajar</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                            </div>
                                                        </div>

                                                        {{-- Group 2: Instrumen Asesmen (IA) - Dinamis Berdasarkan FR.AK.01 --}}
                                                        @php
                                                            $assignedIas = $assessment->getAssignedInstruments();
                                                        @endphp
                                                        <div class="pt-1 border-t border-slate-100">
                                                            <div class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                                                                <span>Instrumen Asesmen (IA)</span>
                                                                <span class="text-[9px] font-normal text-slate-400 lowercase">dinamis</span>
                                                            </div>
                                                            <div class="space-y-0.5">
                                                                @if(in_array('IA01', $assignedIas, true))
                                                                    <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ia01']) }}" target="_blank"
                                                                       class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                        <div class="flex items-center gap-2 overflow-hidden">
                                                                            <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.IA.01</span>
                                                                            <span class="truncate">Ceklis Observasi</span>
                                                                        </div>
                                                                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                    </a>
                                                                @endif


                                                                @if(in_array('IA02', $assignedIas, true))
                                                                    <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ia02']) }}" target="_blank"
                                                                       class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                        <div class="flex items-center gap-2 overflow-hidden">
                                                                            <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.IA.02</span>
                                                                            <span class="truncate">Tugas Praktik Demonstrasi</span>
                                                                        </div>
                                                                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                    </a>
                                                                @endif

                                                                @if(in_array('IA03', $assignedIas, true))
                                                                    <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ia03']) }}" target="_blank"
                                                                       class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                        <div class="flex items-center gap-2 overflow-hidden">
                                                                            <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.IA.03</span>
                                                                            <span class="truncate">Pertanyaan Lisan</span>
                                                                        </div>
                                                                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                    </a>
                                                                @endif

                                                                @if(in_array('IA05', $assignedIas, true))
                                                                    <a href="{{ route('admin.dokumen-muk.asesi-fixed', ['assessment' => $assessment->id, 'type' => 'ia05a']) }}" target="_blank"
                                                                       class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                        <div class="flex items-center gap-2 overflow-hidden">
                                                                            <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-cyan-50 text-cyan-700 border border-cyan-200 shrink-0">FR.IA.05.A</span>
                                                                            <span class="truncate">Lembar Soal (Master PDF)</span>
                                                                        </div>
                                                                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                    </a>
                                                                    <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ia05c']) }}" target="_blank"
                                                                       class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                        <div class="flex items-center gap-2 overflow-hidden">
                                                                            <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-cyan-50 text-cyan-700 border border-cyan-200 shrink-0">FR.IA.05.C</span>
                                                                            <span class="truncate">Lembar Jawaban Asesi</span>
                                                                        </div>
                                                                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                    </a>
                                                                @endif

                                                                @if(in_array('IA08', $assignedIas, true))
                                                                    <a href="{{ route('admin.dokumen-muk.export-asesi', ['assessment' => $assessment->id, 'type' => 'ia08']) }}" target="_blank"
                                                                       class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                        <div class="flex items-center gap-2 overflow-hidden">
                                                                            <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 border border-purple-200 shrink-0">FR.IA.08</span>
                                                                            <span class="truncate">Ceklis Verifikasi Portofolio (CVP)</span>
                                                                        </div>
                                                                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                    </a>
                                                                @endif

                                                                @if(in_array('IA09', $assignedIas, true))
                                                                    <a href="{{ route('admin.dokumen-muk.export-asesi', ['assessment' => $assessment->id, 'type' => 'ia09']) }}" target="_blank"
                                                                       class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                        <div class="flex items-center gap-2 overflow-hidden">
                                                                            <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">FR.IA.09</span>
                                                                            <span class="truncate">Pertanyaan Wawancara (PW)</span>
                                                                        </div>
                                                                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                    </a>
                                                                @endif

                                                                @if(empty($assignedIas))
                                                                    <div class="px-2.5 py-2 text-center text-[11px] text-slate-400 italic">
                                                                        Belum ada instrumen FR.IA yang dipilih di FR.AK.01
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        {{-- Group 3: Rekaman, Evaluasi & Banding --}}
                                                        <div class="pt-1 border-t border-slate-100">
                                                            <div class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                                Rekaman, Evaluasi & Banding
                                                            </div>
                                                            <div class="space-y-0.5">
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ak02']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-brand-50 text-brand-700 border border-brand-200 shrink-0">FR.AK.02</span>
                                                                        <span class="truncate">Rekaman Asesmen</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ak03']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.AK.03</span>
                                                                        <span class="truncate">Umpan Balik Asesi</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ak04']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">FR.AK.04</span>
                                                                        <span class="truncate">Banding Asesmen</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                                <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'pernyataan']) }}" target="_blank"
                                                                   class="flex items-center justify-between px-2.5 py-1.5 rounded-lg hover:bg-brand-50 text-slate-700 hover:text-brand-700 transition group text-xs">
                                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                                        <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 group-hover:bg-brand-100 group-hover:text-brand-700 text-slate-600 shrink-0">PERNYATAAN</span>
                                                                        <span class="truncate">Surat Pernyataan Asesi</span>
                                                                    </div>
                                                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($assessments->hasPages())
                    <div class="px-4 py-3 border-t border-slate-200">
                        {{ $assessments->links() }}
                    </div>
                @endif
            @endif
        </div>
