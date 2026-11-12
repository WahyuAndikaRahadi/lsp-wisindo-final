@extends('layouts.app', ['title' => 'FR.AK.05 & FR.AK.06 Laporan & Tinjauan Asesmen'])

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Unified Top Lifecycle Navigation --}}
    @include('asesor.partials.schedule-nav', ['schedule' => $schedule, 'active' => 'reports'])

    @php
        $isClosedForMe = $schedule->isClosedForAssessor();
    @endphp

    @if($isClosedForMe)
        <div class="p-4 bg-emerald-50 border border-emerald-300 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-emerald-900">
            <div class="flex items-center space-x-2.5">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <div>
                    <strong class="font-bold block text-sm">{{ $schedule->is_closed ? 'Jadwal Telah Resmi Ditutup & Dikunci' : 'Laporan Asesmen Binaan Anda Telah Disimpan & Ditutup' }}</strong>
                    <span>{{ $schedule->is_closed ? 'Jadwal ditutup pada ' . ($schedule->closed_at ? \Carbon\Carbon::parse($schedule->closed_at)->isoFormat('dddd, D MMMM Y, HH:mm') : '-') . ' WIB. Dokumen laporan dalam mode lihat (read-only).' : 'Seluruh asesi binaan Anda telah selesai dinilai dan laporan tersimpan permanen dalam mode lihat (read-only).' }}</span>
                </div>
            </div>
            <span class="font-bold font-mono text-xs bg-emerald-100 text-emerald-800 px-3 py-1 rounded-lg border border-emerald-300 shrink-0">STATUS: {{ $schedule->is_closed ? 'FINISHED' : 'BINAAN SELESAI' }}</span>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">Laporan & Tinjauan Proses Asesmen</h1>
            <p class="text-xs text-slate-500">Kompilasi FR.AK.05 (Laporan Hasil) dan FR.AK.06 (Meninjau Proses Asesmen) sebelum penutupan jadwal uji</p>
        </div>
    </div>

    {{-- Ringkasan Jadwal --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm text-xs">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <span class="text-slate-500 block">Jadwal:</span>
                <span class="font-bold text-slate-900">{{ $schedule->name }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">Skema:</span>
                <span class="font-bold text-slate-900">{{ $schedule->scheme->name }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">TUK:</span>
                <span class="font-bold text-slate-900">{{ $schedule->tuk_name }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">Jumlah Asesi Binaan:</span>
                <span class="font-bold text-brand-700">{{ $myAssessments->count() }} Orang ({{ $myAssessments->where('status', 'COMPLETED')->count() }} Selesai)</span>
                @if($schedule->assessments->count() > $myAssessments->count())
                    <span class="text-[10px] text-slate-400 block mt-0.5 font-normal">
                        (Total Jadwal: {{ $schedule->assessments->count() }} Orang lintas {{ $schedule->assessors->count() }} Asesor)
                    </span>
                @endif
            </div>
        </div>
    </div>

    @php
        $assessorId = auth()->id();
        $ak05Data = $schedule->ak05_report['by_assessor'][$assessorId] ?? $schedule->ak05_report ?? [];
        $ak06Data = $schedule->ak06_review['by_assessor'][$assessorId] ?? $schedule->ak06_review ?? [];

        $existingPenolakanIds = old('ak05_penolakan_asesi_ids', $ak05Data['penolakan_assessment_ids'] ?? $myAssessments->where('has_rejection', true)->pluck('id')->map(fn($v) => (string)$v)->all() ?? []);
        if (!is_array($existingPenolakanIds)) {
            $existingPenolakanIds = [];
        }
        $existingPenolakanIds = array_map('strval', $existingPenolakanIds);
        $initialHasPenolakan = old('ak05_has_penolakan', (!empty($ak05Data['has_penolakan']) || !empty($existingPenolakanIds)) ? '1' : '0');
        $initialPenolakanCatatan = old('ak05_penolakan_catatan', $ak05Data['penolakan_catatan'] ?? $ak05Data['rejection_notes'] ?? '');
        if ($initialPenolakanCatatan === 'Tidak ada penolakan dari asesi atas keputusan asesmen.') {
            $initialPenolakanCatatan = '';
        }
    @endphp

    <form action="{{ route('asesor.schedule.reports.submit', $schedule->id) }}" method="POST" class="space-y-6"
        x-data="{
            ak05Positif: '{{ addslashes(old('ak05_aspek_positif', $ak05Data['aspek_positif'] ?? '')) }}',
            ak05Negatif: '{{ addslashes(old('ak05_aspek_negatif', $ak05Data['aspek_negatif'] ?? '')) }}',
            ak05Rekomendasi: '{{ addslashes(old('ak05_rekomendasi', $ak05Data['rekomendasi'] ?? '')) }}',
            hasPenolakan: '{{ $initialHasPenolakan }}',
            selectedPenolakan: {{ json_encode($existingPenolakanIds) }},
            penolakanCatatan: '{{ addslashes($initialPenolakanCatatan) }}',
            showPenolakanModal: false,
            allCandidateIds: {{ json_encode($myAssessments->pluck('id')->map(fn($id) => (string)$id)->values()->all()) }},
            toggleAllPenolakan(e) {
                if (e.target.checked) {
                    this.selectedPenolakan = [...this.allCandidateIds];
                } else {
                    this.selectedPenolakan = [];
                }
            },
            isCandidateSelected(id) {
                return this.selectedPenolakan.includes(String(id));
            },
            toggleCandidate(id) {
                id = String(id);
                const idx = this.selectedPenolakan.indexOf(id);
                if (idx > -1) {
                    this.selectedPenolakan.splice(idx, 1);
                } else {
                    this.selectedPenolakan.push(id);
                }
            },
            onPenolakanChange(val) {
                this.hasPenolakan = val;
                if (val === '1' && this.selectedPenolakan.length === 0) {
                    this.showPenolakanModal = true;
                }
            },
            ak06ValidNotes: '{{ addslashes(old('ak06_valid_notes', $ak06Data['valid_notes'] ?? '')) }}',
            ak06ReliabelNotes: '{{ addslashes(old('ak06_reliabel_notes', $ak06Data['reliabel_notes'] ?? '')) }}',
            ak06FleksibelNotes: '{{ addslashes(old('ak06_fleksibel_notes', $ak06Data['fleksibel_notes'] ?? '')) }}',
            ak06AdilNotes: '{{ addslashes(old('ak06_adil_notes', $ak06Data['adil_notes'] ?? '')) }}',
            ak06Catatan: '{{ addslashes(old('ak06_catatan_peninjau', $ak06Data['catatan_peninjau'] ?? '')) }}',
            useDraft() {
                this.ak05Positif = 'Sarana dan prasarana bengkel TUK lengkap, ketersediaan alat ukur dan APD sesuai standar K3, asesi hadir tepat waktu.';
                this.ak05Negatif = 'Tidak ditemukan kendala signifikan selama proses asesmen berlangsung.';
                this.ak05Rekomendasi = 'Mempertahankan konsistensi kalibrasi peralatan tangan dan SST serta peningkatan durasi praktik mandiri.';
                this.ak06ValidNotes = 'Seluruh bukti dikumpulkan mengacu langsung pada KUK SKKNI.';
                this.ak06ReliabelNotes = 'Rubrik penilaian dan kunci jawaban diterapkan secara seragam dan konsisten.';
                this.ak06FleksibelNotes = 'Formulir penyesuaian wajar (FR.AK.07) telah difasilitasi jika asesi membutuhkan.';
                this.ak06AdilNotes = 'Semua asesi mendapat kesempatan dan hak yang setara tanpa bias ras/gender/latar belakang.';
                this.ak06Catatan = 'Proses sertifikasi kompetensi telah mematuhi SOP BNSP dan standar mutu LSP PrO Indo.';
            }
        }">
        @csrf

        {{-- FR.AK.05 Laporan Asesmen --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4">
            <div class="border-b border-slate-200 pb-3 flex items-center justify-between">
                <div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase">FR.AK.05</span>
                    <h2 class="text-base font-bold text-slate-900 mt-1">Laporan Asesmen</h2>
                    <p class="text-xs text-slate-500">Evaluasi pelaksanaan asesmen kompetensi di Tempat Uji Kompetensi</p>
                </div>
                @if(! $isClosedForMe)
                    <button type="button" @click="useDraft()" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Gunakan Draf Saran
                    </button>
                @endif
            </div>

            <div class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">1. Aspek Positif Pelaksanaan Asesmen:</label>
                    <textarea name="ak05_aspek_positif" x-model="ak05Positif" rows="2" required @disabled($isClosedForMe) class="w-full p-3 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}" placeholder="Tuliskan aspek positif pelaksanaan asesmen di TUK..."></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">2. Aspek Negatif / Kendala Teknis Lapangan:</label>
                    <textarea name="ak05_aspek_negatif" x-model="ak05Negatif" rows="2" required @disabled($isClosedForMe) class="w-full p-3 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}" placeholder="Tuliskan kendala teknis atau catatan khusus..."></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">3. Rekomendasi Peningkatan Mutu Asesmen:</label>
                    <textarea name="ak05_rekomendasi" x-model="ak05Rekomendasi" rows="2" required @disabled($isClosedForMe) class="w-full p-3 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}" placeholder="Tuliskan rekomendasi peningkatan mutu..."></textarea>
                </div>

                {{-- Section 4: Pencatatan Penolakan Hasil Asesmen --}}
                <div class="pt-3 border-t border-slate-100 space-y-3">
                    <div>
                        <label class="block font-bold text-slate-800 mb-0.5">4. Pencatatan Penolakan Hasil Asesmen (Permohonan Banding Asesi):</label>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Pilih apakah terdapat penolakan atau keberatan dari asesi binaan atas rekomendasi hasil akhir asesmen. Asesi yang dicatat menolak akan diberikan hak pengajuan banding resmi (FR.AK.04) di akun mereka.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-center gap-3 p-3 rounded-xl border {{ $isClosedForMe ? 'cursor-default' : 'cursor-pointer' }} transition"
                               :class="hasPenolakan === '0' ? 'bg-emerald-50/70 border-emerald-400 ring-1 ring-emerald-400' : 'bg-white border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="ak05_has_penolakan" value="0"
                                   x-model="hasPenolakan"
                                   @change="onPenolakanChange('0')"
                                   @disabled($isClosedForMe)
                                   class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                            <div>
                                <span class="block font-bold text-xs text-slate-800">Tidak Ada Penolakan (Default)</span>
                                <span class="block text-[11px] text-slate-500">Seluruh asesi binaan menerima keputusan hasil asesmen dengan baik.</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-xl border {{ $isClosedForMe ? 'cursor-default' : 'cursor-pointer' }} transition"
                               :class="hasPenolakan === '1' ? 'bg-rose-50/70 border-rose-400 ring-1 ring-rose-400' : 'bg-white border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="ak05_has_penolakan" value="1"
                                   x-model="hasPenolakan"
                                   @change="onPenolakanChange('1')"
                                   @disabled($isClosedForMe)
                                   class="w-4 h-4 text-rose-600 focus:ring-rose-500 cursor-pointer">
                            <div>
                                <span class="block font-bold text-xs text-rose-800">Ada Penolakan / Pengajuan Banding</span>
                                <span class="block text-[11px] text-slate-500">Terdapat asesi yang menyatakan keberatan dan mengajukan banding.</span>
                            </div>
                        </label>
                    </div>

                    {{-- Detail Block when 'Ada Penolakan' is active --}}
                    <div x-show="hasPenolakan === '1'" x-cloak class="p-4 bg-rose-50/40 border border-rose-200 rounded-xl space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-rose-200/80">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                    <strong class="text-xs font-bold text-rose-900">Daftar Asesi yang Mengajukan Penolakan / Banding:</strong>
                                </div>
                                <span class="text-[11px] text-rose-700 block mt-0.5" x-text="selectedPenolakan.length + ' dari {{ $myAssessments->count() }} asesi binaan dipilih'"></span>
                            </div>
                            @if(! $isClosedForMe)
                                <button type="button" @click="showPenolakanModal = true"
                                        class="px-3 py-1.5 bg-white hover:bg-rose-50 text-rose-700 border border-rose-300 rounded-lg text-xs font-bold shadow-2xs transition inline-flex items-center gap-1.5 self-start cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    <span>Pilih / Kelola Asesi Penolakan</span>
                                </button>
                            @endif
                        </div>

                        {{-- Candidate Chips / Empty State --}}
                        <div class="space-y-2">
                            <template x-if="selectedPenolakan.length === 0">
                                <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-[11px] flex items-center justify-between">
                                    <span>⚠️ Anda belum memilih asesi yang mengajukan penolakan. Klik tombol di atas untuk memilih asesi binaan.</span>
                                    @if(! $isClosedForMe)
                                        <button type="button" @click="showPenolakanModal = true" class="underline font-bold ml-2 cursor-pointer">Pilih Sekarang</button>
                                    @endif
                                </div>
                            </template>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2" x-show="selectedPenolakan.length > 0">
                                @foreach($myAssessments as $assessment)
                                    <div x-show="isCandidateSelected('{{ $assessment->id }}')"
                                         class="p-2.5 bg-white rounded-lg border border-rose-200 shadow-2xs flex items-center justify-between text-xs">
                                        <div class="min-w-0 pr-2">
                                            <strong class="text-slate-900 block truncate">{{ $assessment->asesi->full_name }}</strong>
                                            <span class="text-[10px] text-slate-500 block truncate">
                                                NIK: {{ $assessment->asesi->profile->nik ?? '-' }} &bull; Rekomendasi: <strong>{{ $assessment->final_result ?? 'Belum Dinilai' }}</strong>
                                            </span>
                                        </div>
                                        <span class="px-2 py-0.5 bg-rose-100 text-rose-800 rounded text-[10px] font-bold shrink-0">Banding</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Catatan / Keterangan Penolakan Hasil Asesmen:</label>
                            <textarea name="ak05_penolakan_catatan" x-model="penolakanCatatan" rows="2" @disabled($isClosedForMe)
                                      class="w-full p-2.5 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}"
                                      placeholder="Tuliskan catatan alasan penolakan atau keberatan yang disampaikan asesi..."></textarea>
                        </div>

                        {{-- Hidden inputs for selected candidate IDs --}}
                        <template x-for="cId in selectedPenolakan" :key="cId">
                            <input type="hidden" name="ak05_penolakan_asesi_ids[]" :value="cId">
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- FR.AK.06 Tinjauan Proses Asesmen --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4">
            <div class="border-b border-slate-200 pb-3">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 uppercase">FR.AK.06</span>
                <h2 class="text-base font-bold text-slate-900 mt-1">Meninjau Proses Asesmen (Quality Review)</h2>
                <p class="text-xs text-slate-500">Verifikasi pemenuhan 4 Prinsip Asesmen BNSP (Valid, Reliabel, Fleksibel, Adil)</p>
            </div>

            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-2 text-left w-28">Prinsip Asesmen</th>
                            <th class="px-3 py-2 text-center w-24">Kesesuaian</th>
                            <th class="px-3 py-2 text-left">Catatan & Komentar Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="px-3 py-2.5 font-bold text-slate-800">Validitas (Valid)</td>
                            <td class="px-3 py-2.5 text-center">
                                <select name="ak06_valid" @disabled($isClosedForMe) class="text-xs rounded-lg border border-slate-300 bg-white font-bold text-emerald-700 py-1.5 px-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs cursor-pointer">
                                    <option value="YA" selected>YA</option>
                                    <option value="TIDAK">TIDAK</option>
                                </select>
                            </td>
                            <td class="px-3 py-2.5">
                                <input type="text" name="ak06_valid_notes" x-model="ak06ValidNotes" required @disabled($isClosedForMe) placeholder="Catatan kesesuaian prinsip validitas..." class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2.5 font-bold text-slate-800">Reliabilitas (Reliable)</td>
                            <td class="px-3 py-2.5 text-center">
                                <select name="ak06_reliabel" @disabled($isClosedForMe) class="text-xs rounded-lg border border-slate-300 bg-white font-bold text-emerald-700 py-1.5 px-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs cursor-pointer">
                                    <option value="YA" selected>YA</option>
                                    <option value="TIDAK">TIDAK</option>
                                </select>
                            </td>
                            <td class="px-3 py-2.5">
                                <input type="text" name="ak06_reliabel_notes" x-model="ak06ReliabelNotes" required @disabled($isClosedForMe) placeholder="Catatan kesesuaian reliabilitas..." class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2.5 font-bold text-slate-800">Fleksibilitas (Flexible)</td>
                            <td class="px-3 py-2.5 text-center">
                                <select name="ak06_fleksibel" @disabled($isClosedForMe) class="text-xs rounded-lg border border-slate-300 bg-white font-bold text-emerald-700 py-1.5 px-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs cursor-pointer">
                                    <option value="YA" selected>YA</option>
                                    <option value="TIDAK">TIDAK</option>
                                </select>
                            </td>
                            <td class="px-3 py-2.5">
                                <input type="text" name="ak06_fleksibel_notes" x-model="ak06FleksibelNotes" required @disabled($isClosedForMe) placeholder="Catatan fleksibilitas..." class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2.5 font-bold text-slate-800">Keadilan (Fair)</td>
                            <td class="px-3 py-2.5 text-center">
                                <select name="ak06_adil" @disabled($isClosedForMe) class="text-xs rounded-lg border border-slate-300 bg-white font-bold text-emerald-700 py-1.5 px-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs cursor-pointer">
                                    <option value="YA" selected>YA</option>
                                    <option value="TIDAK">TIDAK</option>
                                </select>
                            </td>
                            <td class="px-3 py-2.5">
                                <input type="text" name="ak06_adil_notes" x-model="ak06AdilNotes" required @disabled($isClosedForMe) placeholder="Catatan keadilan..." class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pt-2 text-xs">
                <label class="block font-bold text-slate-700 mb-1">Catatan Keseluruhan Peninjau / Lead Asesor:</label>
                <textarea name="ak06_catatan_peninjau" x-model="ak06Catatan" rows="2" required @disabled($isClosedForMe) class="w-full p-3 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}" placeholder="Tuliskan catatan keseluruhan peninjauan proses asesmen..."></textarea>
            </div>
        </div>

        {{-- Mandatory Confirmation Checkbox --}}
        @if(! $isClosedForMe)
            <div class="p-4 bg-amber-50/70 border border-amber-200 rounded-xl">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="confirm_draft_review" value="1" required class="mt-0.5 rounded border-amber-400 text-brand-600 focus:ring-brand-500">
                    <div class="text-xs text-amber-900">
                        <span class="font-bold block">Konfirmasi Integritas Peninjauan:</span>
                        Saya menyatakan telah memeriksa, meninjau, dan memastikan seluruh isi laporan evaluasi FR.AK.05 dan FR.AK.06 sesuai dengan fakta aktual pelaksanaan asesmen di Tempat Uji Kompetensi.
                    </div>
                </label>
            </div>
        @endif

        <div class="flex items-center justify-between pt-4 border-t border-slate-200">
            <a href="{{ route('asesor.schedule.ruang-uji', $schedule->id) }}" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                Kembali
            </a>
            @if($isClosedForMe)
                <div class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    @if($schedule->is_closed)
                        <span>Jadwal Telah Resmi Ditutup & Dikunci (Read-Only)</span>
                    @else
                        <span>Laporan Binaan Telah Disimpan (Mode Selesai)</span>
                    @endif
                </div>
            @else
                <button type="submit" class="px-6 py-2.5 min-h-[40px] text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm transition inline-flex items-center justify-center"
                        data-confirm="PERINGATAN KONFIRMASI LAPORAN ASESMEN:

1. Seluruh rekaman penilaian dan rekomendasi asesi binaan Anda akan disimpan.
2. Jika seluruh asesi pada jadwal ini telah selesai, jadwal akan resmi ditutup (FINISHED).
3. Berita Acara dan Dokumen MUK siap diverifikasi untuk audit BNSP.

Apakah Anda yakin ingin menyimpan laporan FR.AK.05 & FR.AK.06 ini?"
                        data-confirm-title="Konfirmasi Laporan Asesmen"
                        data-confirm-ok="Ya, Simpan & Selesaikan"
                        data-loading-text="Menyimpan laporan FR.AK.05 & FR.AK.06...">
                    Simpan &amp; Selesaikan Asesmen
                </button>
            @endif
        </div>

        {{-- Modal Pemilihan Asesi yang Mengajukan Penolakan / Banding --}}
        <div x-show="showPenolakanModal" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
            <div @click.away="showPenolakanModal = false"
                 class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/80 flex items-center justify-between">
                    <div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 uppercase">FR.AK.05</span>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 mt-0.5">Pencatatan Penolakan Hasil Asesmen</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Pilih asesi yang menyatakan penolakan atas hasil rekomendasi untuk diberikan hak pengajuan banding resmi (FR.AK.04).</p>
                    </div>
                    <button type="button" @click="showPenolakanModal = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-4 sm:p-5 overflow-y-auto space-y-3.5">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-800">
                            <input type="checkbox"
                                   @change="toggleAllPenolakan($event)"
                                   :checked="selectedPenolakan.length === allCandidateIds.length && allCandidateIds.length > 0"
                                   class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 w-4 h-4">
                            <span>Pilih Semua Asesi Binaan ({{ $myAssessments->count() }} Orang)</span>
                        </label>
                        <span class="text-[11px] text-slate-400 italic">Centang asesi yang menolak</span>
                    </div>

                    @if($myAssessments->isEmpty())
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg text-slate-500 text-xs text-center">
                            Tidak ada asesi binaan yang terdaftar pada jadwal ini.
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-[50vh] overflow-y-auto pr-1">
                            @foreach($myAssessments as $assessment)
                                <div class="p-3 rounded-xl border-2 transition shadow-2xs flex flex-col justify-between cursor-pointer"
                                     :class="isCandidateSelected('{{ $assessment->id }}') ? 'bg-rose-50/90 border-rose-400' : 'bg-slate-50 border-slate-200 hover:border-slate-300'"
                                     @click="toggleCandidate('{{ $assessment->id }}')">
                                    <div class="flex items-start justify-between gap-2">
                                        <label class="flex items-start space-x-2.5 cursor-pointer flex-1 min-w-0" @click.stop>
                                            <input type="checkbox"
                                                   :value="'{{ $assessment->id }}'"
                                                   :checked="isCandidateSelected('{{ $assessment->id }}')"
                                                   @change="toggleCandidate('{{ $assessment->id }}')"
                                                   class="rounded border-slate-400 text-rose-600 focus:ring-rose-500 w-4 h-4 mt-0.5 shrink-0">
                                            <div class="truncate text-xs">
                                                <div class="font-bold text-slate-900 truncate">
                                                    {{ $assessment->asesi->full_name }}
                                                </div>
                                                <div class="text-[11px] text-slate-500 truncate mt-0.5">
                                                    {{ $assessment->asesi->email }}
                                                    @if(!empty($assessment->asesi->profile?->nik))
                                                        &bull; <span class="font-mono">NIK: {{ $assessment->asesi->profile->nik }}</span>
                                                    @endif
                                                </div>
                                                <div class="text-[11px] font-semibold mt-1">
                                                    Rekomendasi: <strong>{{ $assessment->final_result ?? 'Belum Dinilai' }}</strong>
                                                </div>
                                            </div>
                                        </label>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                                              :class="isCandidateSelected('{{ $assessment->id }}') ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-slate-200 text-slate-600'">
                                            <span x-text="isCandidateSelected('{{ $assessment->id }}') ? '✓ Menolak' : 'Tidak Menolak'"></span>
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="px-5 py-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                    <span class="text-xs text-slate-600">
                        <strong class="text-rose-700" x-text="selectedPenolakan.length"></strong> asesi dipilih mengajukan penolakan.
                    </span>
                    <button type="button" @click="showPenolakanModal = false"
                            class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-xs font-bold shadow-sm transition cursor-pointer">
                        Selesai Memilih
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
