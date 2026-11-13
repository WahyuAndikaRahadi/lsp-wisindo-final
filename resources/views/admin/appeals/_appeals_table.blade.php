        @if($appeals->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-xs space-y-3">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-sm font-bold text-slate-800">Belum Ada Pengajuan Banding</h3>
                <p class="text-xs text-slate-400 max-w-sm mx-auto">
                    Seluruh peserta asesmen menerima keputusan asesmen atau belum ada sanggahan FR.AK.04 yang masuk ke sistem.
                </p>
            </div>
        @else
            <div class="space-y-5">
                @foreach($appeals as $appeal)
                    @php
                        $sch = $appeal->assessment->schedule;
                        $scheme = $sch?->scheme;
                        $schemeUnits = $scheme?->competencyUnits?->keyBy('code') ?? collect();
                        $schemeUnitsById = $scheme?->competencyUnits?->keyBy('id') ?? collect();
                        $contested = $appeal->contested_units ?? [];
                        $ak03Item = $appeal->assessment->ak03Item;
                    @endphp

                    <div class="bg-white rounded-2xl border {{ $appeal->status === 'SUBMITTED' ? 'border-amber-300 shadow-sm ring-1 ring-amber-100' : 'border-slate-200 shadow-xs' }} overflow-hidden">
                        {{-- Card Top Header (Brand Light Theme) --}}
                        <div class="p-4 sm:p-5 bg-slate-50/90 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm sm:text-base font-black text-slate-900 tracking-tight">
                                        {{ $appeal->assessment->asesi->full_name }}
                                    </span>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-brand-50 text-brand-700 border border-brand-200">
                                        Asesi #{{ $appeal->assessment->id }}
                                    </span>
                                    @if($appeal->assessment->asesi->phone)
                                        <span class="text-xs text-slate-500 font-medium">&bull; WA/Telp: {{ $appeal->assessment->asesi->phone }}</span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                    Skema: <strong class="text-slate-900 font-bold">{{ $scheme->name ?? '-' }}</strong> (<span class="font-mono text-brand-700 font-semibold">{{ $scheme->code ?? '-' }}</span>) &bull; 
                                    Jadwal: <span class="font-medium text-slate-800">{{ $sch->name ?? '-' }} ({{ $sch->code ?? '-' }})</span>
                                </p>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                @if($appeal->status === 'SUBMITTED')
                                    <span class="px-3 py-1.5 text-xs font-bold rounded-lg uppercase tracking-wider bg-amber-50 text-amber-800 border border-amber-300 inline-flex items-center gap-1.5 shadow-2xs">
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                        </span>
                                        <span>MENUNGGU KEPUTUSAN</span>
                                    </span>
                                @elseif($appeal->status === 'ACCEPTED')
                                    <span class="px-3 py-1.5 text-xs font-bold rounded-lg uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-300 inline-flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span>BANDING DITERIMA</span>
                                    </span>
                                @elseif($appeal->status === 'REJECTED')
                                    <span class="px-3 py-1.5 text-xs font-bold rounded-lg uppercase tracking-wider bg-rose-50 text-rose-800 border border-rose-300 inline-flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                        <span>BANDING DITOLAK</span>
                                    </span>
                                @else
                                    <span class="px-3 py-1.5 text-xs font-bold rounded-lg uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-300">
                                        {{ $appeal->status }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Card Meta & Context --}}
                        <div class="p-4 sm:p-6 space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                                <div>
                                    <span class="text-slate-400 block font-semibold uppercase tracking-wider text-[10px]">Tanggal Pengajuan:</span>
                                    <span class="font-bold text-slate-800 text-xs">{{ \Carbon\Carbon::parse($appeal->appeal_date)->isoFormat('dddd, D MMMM Y') }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block font-semibold uppercase tracking-wider text-[10px]">Asesor Penguji Awal:</span>
                                    <span class="font-bold text-slate-800 text-xs">{{ $appeal->assessment->assessor?->full_name ?? 'Tim Asesor LSP' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-400 block font-semibold uppercase tracking-wider text-[10px]">Keputusan Awal Asesor:</span>
                                    <span class="font-bold text-xs {{ $appeal->assessment->final_result === 'KOMPETEN' ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ $appeal->assessment->final_result ?? 'BELUM SELESAI' }}
                                    </span>
                                </div>
                            </div>

                            {{-- 1. BNSP Preliminary Clarification Questions --}}
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <div class="px-4 py-2 bg-slate-100/70 border-b border-slate-200 text-xs font-bold text-slate-700">
                                    1. Klarifikasi Prosedur Banding (Pedoman BNSP FR.AK.04)
                                </div>
                                <div class="divide-y divide-slate-100 text-xs">
                                    <div class="p-3 flex items-center justify-between gap-3">
                                        <span class="text-slate-700">Apakah proses banding telah dijelaskan kepada Asesi oleh Asesor?</span>
                                        @if($appeal->is_process_explained)
                                            <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold shrink-0">✓ YA</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-bold shrink-0">✕ TIDAK</span>
                                        @endif
                                    </div>
                                    <div class="p-3 flex items-center justify-between gap-3">
                                        <span class="text-slate-700">Apakah Asesi telah mendiskusikan sanggahan ini terlebih dahulu dengan Asesor?</span>
                                        @if($appeal->is_discussed_with_assessor)
                                            <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold shrink-0">✓ YA</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-bold shrink-0">✕ TIDAK</span>
                                        @endif
                                    </div>
                                    <div class="p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <span class="text-slate-700">Apakah Asesi melibatkan pihak lain / pendamping dalam proses banding?</span>
                                        <div class="flex items-center gap-2 shrink-0">
                                            @if($appeal->involves_other_person)
                                                <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold">✓ YA</span>
                                                <span class="text-slate-600 font-medium">(Pendamping: <strong>{{ $appeal->other_person_name ?: '-' }}</strong>)</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-bold">✕ TIDAK</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 2. Alasan & Uraian Banding Asesi --}}
                            <div class="p-4 rounded-xl bg-amber-50/70 border border-amber-200 text-xs space-y-1.5">
                                <div class="flex items-center gap-2 text-amber-900 font-bold">
                                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                    <span>2. Alasan &amp; Uraian Sanggahan Banding Asesi:</span>
                                </div>
                                <div class="p-3 bg-white rounded-lg border border-amber-200/80 text-slate-800 text-xs sm:text-sm font-medium leading-relaxed whitespace-pre-line">
                                    {{ $appeal->appeal_reasons }}
                                </div>
                            </div>

                            {{-- 3. Unit Kompetensi yang Disanggah --}}
                            <div class="space-y-1.5 text-xs">
                                <span class="font-bold text-slate-700 block">3. Unit Kompetensi yang Disanggah:</span>
                                @if(!empty($contested))
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($contested as $uVal)
                                            @php
                                                $matchedUnit = $schemeUnits->get($uVal) ?? $schemeUnitsById->get($uVal);
                                            @endphp
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 text-xs">
                                                <strong class="font-mono text-rose-950">{{ $matchedUnit?->code ?? $uVal }}</strong>
                                                <span>{{ $matchedUnit?->title ? '— ' . $matchedUnit->title : '' }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">Tidak ada unit spesifik yang dipilih (sanggahan terhadap keseluruhan penilaian).</span>
                                @endif
                            </div>

                            {{-- 4. Cross-Reference Umpan Balik FR.AK.03 Asesi --}}
                            @if($ak03Item)
                                <div class="p-3.5 rounded-xl bg-indigo-50/70 border border-indigo-200 text-xs space-y-2">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between items-start gap-1">
                                        <span class="font-bold text-indigo-950 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            <span>Cross-Check: Asesi Mengisi FR.AK.03 (Umpan Balik)</span>
                                        </span>
                                        <button type="button" @click="activeTab = 'feedbacks'" class="text-[11px] font-bold text-indigo-700 hover:underline shrink-0">
                                            Buka di Tab Umpan Balik &rarr;
                                        </button>
                                    </div>
                                    @if($ak03Item->general_notes)
                                        <p class="text-slate-700 italic bg-white p-2.5 rounded-lg border border-indigo-100">
                                            "{{ $ak03Item->general_notes }}"
                                        </p>
                                    @endif
                                </div>
                            @endif

                            {{-- 5. Decision Area / Action Form --}}
                            @if($appeal->status === 'SUBMITTED' || $appeal->status === 'UNDER_REVIEW')
                                <div class="pt-4 border-t border-slate-200" x-data="{ actionType: 'REASSESSMENT' }">
                                    <div class="bg-slate-50 p-4 sm:p-5 rounded-xl border border-slate-200 space-y-4">
                                        <div>
                                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                                                Putuskan Sanggahan Banding (Admin / Komite Banding LSP)
                                            </h4>
                                            <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                                Sesuai SOP Komite Banding LSP, pilih opsi tindak lanjut resmi terhadap pengajuan asesi ini (Opsi 3).
                                            </p>
                                        </div>

                                        <form action="{{ route('admin.appeals.decide', $appeal->id) }}" method="POST" class="space-y-4">
                                            @csrf
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                                <div>
                                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                                        Ketua Tim Banding (Opsional):
                                                    </label>
                                                    <input type="text" 
                                                           name="appeals_committee_chair" 
                                                           class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs" 
                                                           placeholder="Nama Ketua Tim Banding..." 
                                                           value="{{ old('appeals_committee_chair', $appeal->appeals_committee_chair) }}">
                                                </div>

                                                <div class="sm:col-span-2">
                                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                                        Catatan Putusan / Rekomendasi Tindak Lanjut (*):
                                                    </label>
                                                    <input type="text" 
                                                           name="decision_notes" 
                                                           required
                                                           class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs" 
                                                           placeholder="Contoh: Banding diterima. Direkomendasikan asesmen ulang untuk unit terkait dengan asesor independen...">
                                                </div>
                                            </div>

                                            {{-- Opsi 3: Pilihan Tindak Lanjut Komite Banding --}}
                                            <div class="p-3.5 bg-white rounded-xl border border-slate-200 space-y-2.5">
                                                <div class="flex items-center justify-between">
                                                    <label class="block text-[11px] font-bold text-slate-800 uppercase tracking-wider">
                                                        Pilihan Tindak Lanjut Bila Banding Diterima (Opsi 3):
                                                    </label>
                                                    <span class="text-[10px] text-slate-400 font-medium">Berlaku bila tombol "Terima Banding" ditekan</span>
                                                </div>

                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5 text-xs">
                                                    <label class="relative flex flex-col p-3 rounded-xl border cursor-pointer transition"
                                                           :class="actionType === 'REASSESSMENT' ? 'border-brand-500 bg-brand-50/50 ring-1 ring-brand-500' : 'border-slate-200 hover:bg-slate-50'">
                                                        <div class="flex items-center gap-2">
                                                            <input type="radio" name="action_type" value="REASSESSMENT" x-model="actionType" class="text-brand-600 focus:ring-brand-500">
                                                            <span class="font-bold text-slate-900">1. Asesmen Ulang</span>
                                                        </div>
                                                        <span class="text-[11px] text-slate-500 mt-1 leading-snug">
                                                            Jadwalkan uji ulang untuk unit kompetensi yang disanggah dengan asesor pengganti.
                                                        </span>
                                                    </label>

                                                    <label class="relative flex flex-col p-3 rounded-xl border cursor-pointer transition"
                                                           :class="actionType === 'OVERRIDE_KOMPETEN' ? 'border-emerald-500 bg-emerald-50/50 ring-1 ring-emerald-500' : 'border-slate-200 hover:bg-slate-50'">
                                                        <div class="flex items-center gap-2">
                                                            <input type="radio" name="action_type" value="OVERRIDE_KOMPETEN" x-model="actionType" class="text-emerald-600 focus:ring-emerald-500">
                                                            <span class="font-bold text-emerald-950">2. Tetapkan KOMPETEN</span>
                                                        </div>
                                                        <span class="text-[11px] text-slate-500 mt-1 leading-snug">
                                                            Komite menganulir rekomendasi awal dan langsung menetapkan asesi <strong>KOMPETEN</strong>.
                                                        </span>
                                                    </label>

                                                    <label class="relative flex flex-col p-3 rounded-xl border cursor-pointer transition"
                                                           :class="actionType === 'ADMINISTRATIVE' ? 'border-indigo-500 bg-indigo-50/50 ring-1 ring-indigo-500' : 'border-slate-200 hover:bg-slate-50'">
                                                        <div class="flex items-center gap-2">
                                                            <input type="radio" name="action_type" value="ADMINISTRATIVE" x-model="actionType" class="text-indigo-600 focus:ring-indigo-500">
                                                            <span class="font-bold text-slate-900">3. Mediasi Administratif</span>
                                                        </div>
                                                        <span class="text-[11px] text-slate-500 mt-1 leading-snug">
                                                            Klarifikasi / mediasi komite teknis tanpa langsung mengubah status kelulusan.
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2.5 pt-1">
                                                <button type="submit" name="decision" value="ACCEPTED" class="px-5 py-2.5 min-h-[40px] text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition flex items-center justify-center gap-1.5">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    <span>Terima Banding</span>
                                                </button>
                                                <button type="submit" name="decision" value="REJECTED" class="px-5 py-2.5 min-h-[40px] text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs transition flex items-center justify-center gap-1.5">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    <span>Tolak Banding</span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @else
                                {{-- Already Decided Box --}}
                                <div class="pt-4 border-t border-slate-200">
                                    <div class="p-4 rounded-xl {{ $appeal->status === 'ACCEPTED' ? 'bg-emerald-50 border border-emerald-200' : 'bg-rose-50 border border-rose-200' }} text-xs space-y-2">
                                        <div class="flex items-center justify-between flex-wrap gap-2">
                                            <span class="font-extrabold text-sm {{ $appeal->status === 'ACCEPTED' ? 'text-emerald-900' : 'text-rose-900' }}">
                                                Putusan: {{ $appeal->status === 'ACCEPTED' ? '✓ BANDING DITERIMA (DIKABULKAN)' : '✕ BANDING DITOLAK' }}
                                            </span>
                                            <span class="text-slate-500 text-[11px]">
                                                Ditetapkan: {{ $appeal->decision_date ? \Carbon\Carbon::parse($appeal->decision_date)->isoFormat('D MMMM Y') : '-' }}
                                            </span>
                                        </div>
                                        <p class="text-slate-800 leading-relaxed font-medium">
                                            <strong>Catatan Putusan:</strong> {{ $appeal->decision_notes ?? '-' }}
                                        </p>
                                        @if($appeal->assessment->follow_up_action)
                                            <p class="text-slate-700 text-[11px] bg-white/70 p-2 rounded-lg border {{ $appeal->status === 'ACCEPTED' ? 'border-emerald-200' : 'border-rose-200' }}">
                                                <strong>Tindak Lanjut Sistem:</strong> {{ $appeal->assessment->follow_up_action }}
                                            </p>
                                        @endif
                                        @if($appeal->appeals_committee_chair)
                                            <p class="text-slate-700">
                                                <strong>Ketua Tim Banding:</strong> {{ $appeal->appeals_committee_chair }}
                                            </p>
                                        @endif
                                        <div class="text-[11px] text-slate-500 pt-1 border-t {{ $appeal->status === 'ACCEPTED' ? 'border-emerald-200' : 'border-rose-200' }}">
                                            Diputuskan oleh: <strong>{{ $appeal->decider->full_name ?? 'Admin LSP' }}</strong>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="pt-2">
                    {{ $appeals->links() }}
                </div>
            </div>
        @endif
