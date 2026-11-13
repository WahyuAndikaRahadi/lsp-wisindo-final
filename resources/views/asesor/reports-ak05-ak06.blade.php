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
                    <strong class="font-bold block text-sm">{{ $schedule->is_closed ? 'Jadwal Ditutup' : 'Laporan Asesmen Telah Disimpan' }}</strong>
                    <span>{{ $schedule->is_closed ? 'Jadwal ditutup pada ' . ($schedule->closed_at ? \Carbon\Carbon::parse($schedule->closed_at)->isoFormat('dddd, D MMMM Y, HH:mm') : '-') . ' WIB. Dokumen dalam mode lihat (read-only).' : 'Laporan asesmen telah disimpan dalam mode lihat (read-only).' }}</span>
                </div>
            </div>
            <span class="font-bold font-mono text-xs bg-emerald-100 text-emerald-800 px-3 py-1 rounded-lg border border-emerald-300 shrink-0">STATUS: {{ $schedule->is_closed ? 'FINISHED' : 'SELESAI' }}</span>
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
                <span class="text-slate-500 block">Jumlah Asesi:</span>
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
        $initialTemplateType = old('ak06_template_type', $ak06Data['template_type'] ?? '');
        $initialAk06Catatan = old('ak06_catatan_peninjau', $ak06Data['catatan_peninjau'] ?? '');

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
            ak05Positif: '{{ addslashes(old('ak05_aspek_positif', $ak05Data['aspek_positif'] ?? 'Tidak ada')) }}',
            ak05Negatif: '{{ addslashes(old('ak05_aspek_negatif', $ak05Data['aspek_negatif'] ?? 'Tidak ada')) }}',
            ak05Rekomendasi: '{{ addslashes(old('ak05_rekomendasi', $ak05Data['rekomendasi'] ?? 'Tidak ada')) }}',
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
            selectedTemplate: '{{ $initialTemplateType }}',
            showPreviewModal: false,
            previewTemplate: '{{ $initialTemplateType ?: 'langsung' }}',
            ak06Catatan: '{{ addslashes($initialAk06Catatan) }}',
            get previewDocUrl() {
                return this.previewTemplate === 'tidak_langsung'
                    ? '{{ asset('AK06/24 FR.AK.06 Meninjau Proses Asesmen (Tidak Langsung).pdf') }}'
                    : '{{ asset('AK06/24 FR.AK.06 Meninjau Proses Asesmen (Langsung).pdf') }}';
            },
            get previewDocxUrl() {
                return this.previewTemplate === 'tidak_langsung'
                    ? '{{ asset('AK06/24 FR.AK.06 Meninjau Proses Asesmen (Tidak Langsung).docx') }}'
                    : '{{ asset('AK06/24 FR.AK.06 Meninjau Proses Asesmen (Langsung).docx') }}';
            },
            openPreview(type) {
                this.previewTemplate = type;
                this.showPreviewModal = true;
            },
            selectAndClosePreview(type) {
                this.selectedTemplate = type;
                this.showPreviewModal = false;
            },
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
            </div>

            <div class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">1. Aspek Positif Pelaksanaan Asesmen:</label>
                    <textarea name="ak05_aspek_positif" x-model="ak05Positif" rows="2" required @disabled($isClosedForMe) class="w-full p-3 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}" placeholder="Tuliskan aspek positif pelaksanaan asesmen di TUK...">{{ old('ak05_aspek_positif', $ak05Data['aspek_positif'] ?? 'Tidak ada') }}</textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">2. Aspek Negatif / Kendala Teknis Lapangan:</label>
                    <textarea name="ak05_aspek_negatif" x-model="ak05Negatif" rows="2" required @disabled($isClosedForMe) class="w-full p-3 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}" placeholder="Tuliskan kendala teknis atau catatan khusus...">{{ old('ak05_aspek_negatif', $ak05Data['aspek_negatif'] ?? 'Tidak ada') }}</textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">3. Rekomendasi Peningkatan Mutu Asesmen:</label>
                    <textarea name="ak05_rekomendasi" x-model="ak05Rekomendasi" rows="2" required @disabled($isClosedForMe) class="w-full p-3 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}" placeholder="Tuliskan rekomendasi peningkatan mutu...">{{ old('ak05_rekomendasi', $ak05Data['rekomendasi'] ?? 'Tidak ada') }}</textarea>
                </div>

                {{-- Section 4: Pencatatan Penolakan Hasil Asesmen --}}
                <div class="pt-3 border-t border-slate-100 space-y-3">
                    <div>
                        <label class="block font-bold text-slate-800 mb-0.5">4. Pencatatan Penolakan Hasil Asesmen (Permohonan Banding Asesi):</label>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Pilih apakah terdapat penolakan atau keberatan dari asesi atas rekomendasi hasil akhir asesmen. Asesi yang dicatat menolak akan diberikan hak pengajuan banding resmi (FR.AK.04) di akun mereka.
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
                                <span class="block text-[11px] text-slate-500">Seluruh asesi menerima keputusan hasil asesmen dengan baik.</span>
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
                                <span class="text-[11px] text-rose-700 block mt-0.5" x-text="selectedPenolakan.length + ' dari {{ $myAssessments->count() }} asesi dipilih'"></span>
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
                                    <span>⚠️ Anda belum memilih asesi yang mengajukan penolakan. Klik tombol di atas untuk memilih asesi.</span>
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
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-5">
            <div class="border-b border-slate-200 pb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 uppercase">FR.AK.06</span>
                    <h2 class="text-base font-bold text-slate-900 mt-1">Meninjau Proses Asesmen (Quality Review)</h2>
                    <p class="text-xs text-slate-500">Pilih salah satu format FR.AK.06 yang digunakan (Langsung atau Tidak Langsung). Format dokumen ini bersifat baku sesuai standar BNSP.</p>
                </div>
                <div class="text-xs text-rose-600 font-semibold flex items-center gap-1.5 shrink-0" x-show="!selectedTemplate">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>* Wajib memilih salah satu opsi</span>
                </div>
            </div>

            @error('ak06_template_type')
                <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ $message }}</span>
                </div>
            @enderror

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Opsi 1: FR.AK.06 Langsung --}}
                <div class="rounded-2xl border-2 p-5 transition flex flex-col justify-between cursor-pointer relative shadow-2xs"
                     :class="selectedTemplate === 'langsung' ? 'border-brand-600 bg-brand-50/40 ring-2 ring-brand-500/20' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50'"
                     @click="if (!{{ $isClosedForMe ? 'true' : 'false' }}) { selectedTemplate = 'langsung'; }">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <label class="flex items-start gap-3 cursor-pointer flex-1" @click.stop>
                                <input type="radio" name="ak06_template_type" value="langsung"
                                       x-model="selectedTemplate"
                                       @disabled($isClosedForMe)
                                       required
                                       class="mt-1 w-4 h-4 text-brand-600 border-slate-300 focus:ring-brand-500 cursor-pointer">
                                <div>
                                    <div class="text-sm font-bold text-slate-900 leading-snug">
                                        FR.AK.06 Meninjau Proses Asesmen (Langsung)
                                    </div>
                                    <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        Bukti Langsung (L / CL)
                                    </span>
                                </div>
                            </label>
                            <span class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 text-xs font-bold transition"
                                  :class="selectedTemplate === 'langsung' ? 'bg-brand-600 text-white shadow-xs' : 'bg-slate-100 text-slate-400'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </span>
                        </div>

                        <p class="text-xs text-slate-600 leading-relaxed">
                            Instrumen peninjauan untuk asesmen berbasis <strong>observasi langsung, demonstrasi praktik, dan tugas praktik</strong>.
                        </p>

                        <div class="bg-slate-50/80 rounded-lg p-2.5 border border-slate-100 text-[11px] text-slate-600 space-y-1">
                            <div class="font-semibold text-slate-700 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Instrumen Terkait:
                            </div>
                            <div class="flex flex-wrap gap-1 font-mono text-[10px]">
                                <span class="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-slate-700">FR.IA.01 (Ceklis Observasi)</span>
                                <span class="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-slate-700">FR.IA.02 (Tugas Praktik)</span>
                                <span class="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-slate-700">FR.IA.03 (Pertanyaan Lisan)</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 mt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                    @click.stop="openPreview('langsung')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 transition border border-brand-200 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <span>Pratinjau Dokumen</span>
                            </button>
                            <a href="{{ asset('AK06/24 FR.AK.06 Meninjau Proses Asesmen (Langsung).docx') }}" download
                               @click.stop
                               class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition border border-slate-200 cursor-pointer" title="Unduh File Asli .docx">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <span>.docx</span>
                            </a>
                        </div>

                        <button type="button"
                                x-show="!{{ $isClosedForMe ? 'true' : 'false' }}"
                                @click.stop="selectedTemplate = 'langsung'"
                                class="text-xs font-bold transition px-2.5 py-1 rounded-md cursor-pointer"
                                :class="selectedTemplate === 'langsung' ? 'text-brand-700 font-extrabold' : 'text-slate-500 hover:text-slate-800'">
                            <span x-text="selectedTemplate === 'langsung' ? '✓ Terpilih' : 'Pilih Opsi'"></span>
                        </button>
                    </div>
                </div>

                {{-- Opsi 2: FR.AK.06 Tidak Langsung --}}
                <div class="rounded-2xl border-2 p-5 transition flex flex-col justify-between cursor-pointer relative shadow-2xs"
                     :class="selectedTemplate === 'tidak_langsung' ? 'border-brand-600 bg-brand-50/40 ring-2 ring-brand-500/20' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50'"
                     @click="if (!{{ $isClosedForMe ? 'true' : 'false' }}) { selectedTemplate = 'tidak_langsung'; }">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <label class="flex items-start gap-3 cursor-pointer flex-1" @click.stop>
                                <input type="radio" name="ak06_template_type" value="tidak_langsung"
                                       x-model="selectedTemplate"
                                       @disabled($isClosedForMe)
                                       required
                                       class="mt-1 w-4 h-4 text-brand-600 border-slate-300 focus:ring-brand-500 cursor-pointer">
                                <div>
                                    <div class="text-sm font-bold text-slate-900 leading-snug">
                                        FR.AK.06 Meninjau Proses Asesmen (Tidak Langsung)
                                    </div>
                                    <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        Bukti Tidak Langsung (TL / CLO &amp; T / PW)
                                    </span>
                                </div>
                            </label>
                            <span class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 text-xs font-bold transition"
                                  :class="selectedTemplate === 'tidak_langsung' ? 'bg-brand-600 text-white shadow-xs' : 'bg-slate-100 text-slate-400'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </span>
                        </div>

                        <p class="text-xs text-slate-600 leading-relaxed">
                            Instrumen peninjauan untuk asesmen berbasis <strong>verifikasi portofolio (CLO) dan pertanyaan wawancara (PW)</strong>.
                        </p>

                        <div class="bg-slate-50/80 rounded-lg p-2.5 border border-slate-100 text-[11px] text-slate-600 space-y-1">
                            <div class="font-semibold text-slate-700 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                Instrumen Terkait:
                            </div>
                            <div class="flex flex-wrap gap-1 font-mono text-[10px]">
                                <span class="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-slate-700">FR.IA.08 (Verifikasi Portofolio)</span>
                                <span class="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-slate-700">FR.IA.09 (Pertanyaan Wawancara)</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 mt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                    @click.stop="openPreview('tidak_langsung')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 transition border border-brand-200 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <span>Pratinjau Dokumen</span>
                            </button>
                            <a href="{{ asset('AK06/24 FR.AK.06 Meninjau Proses Asesmen (Tidak Langsung).docx') }}" download
                               @click.stop
                               class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition border border-slate-200 cursor-pointer" title="Unduh File Asli .docx">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <span>.docx</span>
                            </a>
                        </div>

                        <button type="button"
                                x-show="!{{ $isClosedForMe ? 'true' : 'false' }}"
                                @click.stop="selectedTemplate = 'tidak_langsung'"
                                class="text-xs font-bold transition px-2.5 py-1 rounded-md cursor-pointer"
                                :class="selectedTemplate === 'tidak_langsung' ? 'text-brand-700 font-extrabold' : 'text-slate-500 hover:text-slate-800'">
                            <span x-text="selectedTemplate === 'tidak_langsung' ? '✓ Terpilih' : 'Pilih Opsi'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Catatan Keseluruhan Peninjau / Lead Asesor --}}
            <div class="pt-2 text-xs">
                <label class="block font-bold text-slate-700 mb-1">Catatan / Komentar Lead Asesor (Opsional pada Dokumen Cetak):</label>
                <textarea name="ak06_catatan_peninjau" x-model="ak06Catatan" rows="2" @disabled($isClosedForMe) class="w-full p-3 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}" placeholder="Proses asesmen telah memenuhi seluruh standar mutu penjaminan asesmen BNSP."></textarea>
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
                        <span>Laporan Telah Disimpan (Selesai)</span>
                    @endif
                </div>
            @else
                <button type="submit" class="px-6 py-2.5 min-h-[40px] text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm transition inline-flex items-center justify-center"
                        data-confirm="PERINGATAN KONFIRMASI LAPORAN ASESMEN:

1. Seluruh rekaman penilaian dan rekomendasi asesi akan disimpan.
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
                            <span>Pilih Semua Asesi ({{ $myAssessments->count() }})</span>
                        </label>
                        <span class="text-[11px] text-slate-400 italic">Centang asesi yang menolak</span>
                    </div>

                    @if($myAssessments->isEmpty())
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg text-slate-500 text-xs text-center">
                            Tidak ada asesi yang terdaftar pada jadwal ini.
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

        {{-- Modal Pratinjau Format FR.AK.06 (Langsung & Tidak Langsung) --}}
        <div x-show="showPreviewModal" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4">
            <div @click.away="showPreviewModal = false"
                 class="bg-white rounded-2xl max-w-4xl w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[92vh]">
                {{-- Header Modal --}}
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800 uppercase">FR.AK.06</span>
                            <h3 class="text-sm sm:text-base font-bold text-slate-900">Pratinjau Format Dokumen Meninjau Proses Asesmen</h3>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">Tampilan cetak resmi MUK statis sesuai standar pedoman BNSP 301</p>
                    </div>

                    {{-- Tab Switcher --}}
                    <div class="flex items-center gap-1.5 p-1 bg-slate-200/70 rounded-xl">
                        <button type="button"
                                @click="previewTemplate = 'langsung'"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer"
                                :class="previewTemplate === 'langsung' ? 'bg-white text-brand-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                            Format Langsung (L/CL)
                        </button>
                        <button type="button"
                                @click="previewTemplate = 'tidak_langsung'"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer"
                                :class="previewTemplate === 'tidak_langsung' ? 'bg-white text-brand-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                            Format Tidak Langsung (TL/CLO)
                        </button>
                        <button type="button" @click="showPreviewModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 ml-1 rounded-lg cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Modal Body Document Preview from Public --}}
                <div class="p-4 sm:p-5 overflow-y-auto bg-slate-100 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs text-slate-600 bg-white p-3 rounded-xl border border-slate-200">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>Dokumen Asli Master BNSP (Statis): <strong class="text-slate-900" x-text="previewTemplate === 'langsung' ? '24 FR.AK.06 Meninjau Proses Asesmen (Langsung)' : '24 FR.AK.06 Meninjau Proses Asesmen (Tidak Langsung)'"></strong></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <a :href="previewDocUrl" target="_blank" class="px-2.5 py-1 text-[11px] font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded border border-slate-300 transition">
                                Buka di Tab Baru ↗
                            </a>
                            <a :href="previewDocxUrl" download class="px-2.5 py-1 text-[11px] font-bold text-brand-700 bg-brand-50 hover:bg-brand-100 rounded border border-brand-200 transition">
                                Unduh File .docx ↓
                            </a>
                        </div>
                    </div>

                    <div class="w-full bg-white rounded-xl border border-slate-300 overflow-hidden shadow-sm">
                        <iframe :src="previewDocUrl" class="w-full h-[65vh] border-0" title="Pratinjau Dokumen Asli FR.AK.06"></iframe>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-5 py-3 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
                    <span class="text-xs text-slate-600">
                        Format pratinjau: <strong class="text-brand-700" x-text="previewTemplate === 'langsung' ? 'Langsung (L/CL)' : 'Tidak Langsung (TL/CLO)'"></strong>
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="showPreviewModal = false"
                                class="px-4 py-2 border border-slate-300 text-slate-700 hover:bg-slate-100 rounded-lg text-xs font-semibold transition cursor-pointer">
                            Tutup
                        </button>
                        @if(!$isClosedForMe)
                            <button type="button" @click="selectAndClosePreview(previewTemplate)"
                                    class="px-5 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-xs font-bold shadow-sm transition cursor-pointer">
                                <span x-text="selectedTemplate === previewTemplate ? '✓ Format Ini Sedang Terpilih (Selesai)' : 'Gunakan & Pilih Format Ini'"></span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
