@php
    $isCompleted = (bool) ($response?->assessor_signed_at || ! empty($response?->assessor_signature));
    $isScheduleClosed = (bool) ($assessment->schedule?->isClosedForAssessor());
    $savedData = $savedData ?? (is_array($response?->data_json) ? $response->data_json : (json_decode($response?->data_json ?? '[]', true) ?: []));
    $savedQuestionsData = $savedData['questions'] ?? [];
    $recommendation = $savedData['recommendation'] ?? 'K';
@endphp
@extends('layouts.app', ['title' => 'FR.IA.09 - Pertanyaan Wawancara'])

@section('content')
<div class="space-y-6" x-data="{
    isEditing: {{ ($isCompleted || $isScheduleClosed) ? 'false' : 'true' }},
    recommendation: '{{ old('recommendation', $recommendation) }}',
    setAllAchieved(val) {
        if (!this.isEditing) return;
        const target = val.toLowerCase();
        document.querySelectorAll(`.achieve-radio-${target}`).forEach(el => {
            el.checked = true;
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }
}">
    @if($isScheduleClosed)
        <div class="p-4 bg-emerald-50 border-2 border-emerald-300 rounded-xl text-emerald-900 flex items-center gap-3 shadow-xs">
            <span class="text-xl">🔒</span>
            <div>
                <div class="font-bold text-sm">Jadwal Asesmen Telah Resmi Ditutup &amp; Dikunci</div>
                <p class="text-xs text-emerald-800 mt-0.5">Seluruh instrumen pada jadwal ini berada dalam mode lihat (read-only). Data wawancara tidak dapat diubah lagi.</p>
            </div>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia09']) }}" class="text-xs text-brand-600 hover:text-brand-700 font-bold">&larr; Kembali ke Daftar Asesi IA.09</a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">FR.IA.09 – Pertanyaan Wawancara (PW)</h1>
            <p class="text-sm text-slate-500">Asesi: <strong>{{ $assessment->asesi->full_name }}</strong> | Skema: <strong>{{ $assessment->schedule->scheme->name }}</strong></p>
        </div>

        @if($isCompleted && ! $isScheduleClosed)
            <div class="flex items-center gap-2">
                <button type="button" x-show="!isEditing" @click="isEditing = true" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg shadow-xs transition flex items-center gap-1.5">
                    <span>✎ Edit Penilaian</span>
                </button>
                <button type="button" x-show="isEditing" @click="isEditing = false" style="display: none;" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold rounded-lg transition">
                    Batal Edit
                </button>
            </div>
        @endif
    </div>

    @if($isCompleted)
        <div class="bg-emerald-50 border-2 border-emerald-300 rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shrink-0">✓</span>
                <div>
                    <div class="text-xs font-bold text-emerald-950">Wawancara FR.IA.09 Telah Selesai &amp; Tersimpan</div>
                    <p class="text-[11px] text-emerald-800" x-show="!isEditing">Hasil wawancara dan kesimpulan pencapaian tersimpan resmi. Klik 'Edit Penilaian' untuk memperbarui.</p>
                    <p class="text-[11px] text-amber-800 font-semibold" x-show="isEditing" style="display: none;">Mode Pengeditan Aktif. Silakan sesuaikan kesimpulan jawaban asesi dan pencapaian.</p>
                </div>
            </div>
            @if($response?->assessor_signed_at)
                <div class="text-right text-[11px] text-emerald-700 font-mono">
                    Dinilai: {{ \Carbon\Carbon::parse($response->assessor_signed_at)->isoFormat('D MMM Y, HH:mm') }} WIB
                </div>
            @endif
        </div>
    @endif

    {{-- Panduan Bagi Asesor (Sesuai MUK) --}}
    <div class="bg-violet-50/70 border border-violet-200 rounded-xl p-4 text-xs text-violet-950 space-y-1.5 leading-relaxed shadow-2xs">
        <div class="font-bold uppercase tracking-wider text-violet-900 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-violet-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>PANDUAN BAGI ASESOR</span>
        </div>
        <ul class="list-disc pl-5 space-y-1 text-slate-700">
            <li>Pertanyaan wawancara dapat dilakukan untuk keseluruhan unit kompetensi dalam skema sertifikasi atau dilakukan untuk masing-masing kelompok pekerjaan dalam satu skema sertifikasi.</li>
            <li>Isilah bukti portofolio sesuai dengan bukti yang diminta pada skema sertifikasi sebagaimana yang telah dibuat pada FR-IA.08.</li>
            <li>Ajukan pertanyaan verifikasi portofolio untuk semua unit/elemen kompetensi yang di-checklist pada FR-IA.08.</li>
            <li>Ajukan pertanyaan kepada asesi sebagai tindak lanjut hasil verifikasi portofolio.</li>
            <li>Jika hasil verifikasi portofolio telah memenuhi aturan bukti maka pertanyaan wawancara tidak perlu dilakukan terhadap bukti tersebut.</li>
            <li>Tuliskan pencapaian atas setiap kesimpulan pertanyaan wawancara dengan cara mencentang (✓) “Ya” atau “Tidak”.</li>
        </ul>
    </div>

    <form action="{{ route('asesor.ruang-uji.ia09.store', $assessment->id) }}" method="POST" class="space-y-6">
        @csrf

        {{-- Table Pertanyaan Wawancara --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Daftar Pertanyaan Wawancara</h2>
                    <p class="text-xs text-slate-500">Ajukan pertanyaan langsung kepada asesi, catat kesimpulan jawaban, dan tentukan pencapaian (Ya / Tidak)</p>
                </div>
                @if($interviewQuestions->isNotEmpty())
                    <div class="flex items-center gap-2" x-show="isEditing">
                        <button type="button" @click="setAllAchieved('ya')" class="px-3.5 py-1.5 min-h-[36px] bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            Semua Ya
                        </button>
                        <button type="button" @click="setAllAchieved('tidak')" class="px-3.5 py-1.5 min-h-[36px] bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                            Semua Tidak
                        </button>
                    </div>
                @endif
            </div>

            @if($interviewQuestions->isEmpty())
                <div class="p-8 text-center bg-slate-50 border-b border-slate-200 space-y-3">
                    <div class="w-12 h-12 bg-amber-100 text-amber-700 rounded-full flex items-center justify-center mx-auto text-xl font-bold">
                        💬
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Bank Soal Wawancara Belum Tersedia untuk Skema Ini</h3>
                        <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                            Soal wawancara terstandar untuk skema <strong>{{ $assessment->schedule->scheme->name }}</strong> belum ditambahkan ke sistem. Admin LSP dapat menginput bank soal wawancara melalui menu <span class="font-bold text-slate-700">Bank Soal Wawancara (IA09)</span>.
                        </p>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-slate-700 uppercase tracking-wider font-bold border-b border-slate-200">
                            <tr>
                                <th rowspan="2" class="px-4 py-3 text-center border-r border-slate-200 w-12">No</th>
                                <th rowspan="2" class="px-4 py-3 text-left border-r border-slate-200 w-44">Konteks Bukti / Unit</th>
                                <th rowspan="2" class="px-4 py-3 text-left border-r border-slate-200">Daftar Pertanyaan Wawancara &amp; Kunci Jawaban</th>
                                <th rowspan="2" class="px-4 py-3 text-left border-r border-slate-200 w-64">Kesimpulan Jawaban Asesi</th>
                                <th colspan="2" class="px-2 py-1.5 text-center bg-violet-50/60 text-violet-900 border-b border-slate-200 w-28">Pencapaian</th>
                            </tr>
                            <tr class="text-[10px] border-b border-slate-200">
                                <th class="px-2 py-1 text-center border-r border-slate-200 bg-emerald-50 text-emerald-800 w-14">Ya</th>
                                <th class="px-2 py-1 text-center bg-rose-50 text-rose-800 w-14">Tidak</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($interviewQuestions as $idx => $q)
                                @php
                                    $savedQ = $savedQuestionsData[$q->id] ?? [];
                                    $ansSummary = $savedQ['candidate_answer_summary'] ?? (old("questions.{$q->id}.candidate_answer_summary") ?? '');
                                    $achieved = $savedQ['is_achieved'] ?? (old("questions.{$q->id}.is_achieved") ?? 'YA');
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-4 py-3 text-center font-bold text-slate-500 font-mono border-r border-slate-200 align-top">
                                        {{ $q->order ?? ($idx + 1) }}
                                    </td>
                                    <td class="px-4 py-3 border-r border-slate-200 align-top">
                                        @if($q->evidence_context)
                                            <div class="font-semibold text-brand-700 bg-brand-50 px-2 py-1 rounded text-[11px] mb-1.5 leading-snug">
                                                {{ $q->evidence_context }}
                                            </div>
                                        @endif
                                        @if($q->competencyUnit)
                                            <div class="font-mono font-bold text-slate-800 text-[10px]">{{ $q->competencyUnit->code }}</div>
                                            <div class="text-[10px] text-slate-500 leading-tight mt-0.5">{{ $q->competencyUnit->title }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 border-r border-slate-200 align-top">
                                        <div class="font-medium text-slate-900 text-xs sm:text-[13px] leading-relaxed whitespace-pre-line mb-2">
                                            {{ $q->question_text }}
                                        </div>
                                        @if(!empty($q->expected_answer))
                                            <div class="p-2.5 rounded-lg bg-slate-50 text-[11px] text-slate-600 border border-slate-200 leading-relaxed">
                                                <span class="font-bold text-slate-700 block mb-0.5">Kunci / Jawaban yang Diharapkan:</span>
                                                {{ $q->expected_answer }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 border-r border-slate-200 align-top">
                                        <textarea name="questions[{{ $q->id }}][candidate_answer_summary]" rows="3" :disabled="!isEditing"
                                            placeholder="Tuliskan kesimpulan tanggapan asesi..."
                                            class="w-full text-xs rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 px-2.5 py-1.5 bg-white">{{ $ansSummary }}</textarea>
                                    </td>
                                    <td class="px-2 py-2 text-center border-r border-slate-200 align-middle">
                                        <input type="radio" name="questions[{{ $q->id }}][is_achieved]" value="YA" :disabled="!isEditing"
                                            class="achieve-radio-ya text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer"
                                            {{ $achieved === 'YA' ? 'checked' : '' }}>
                                    </td>
                                    <td class="px-2 py-2 text-center align-middle">
                                        <input type="radio" name="questions[{{ $q->id }}][is_achieved]" value="TIDAK" :disabled="!isEditing"
                                            class="achieve-radio-tidak text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer"
                                            {{ $achieved === 'TIDAK' ? 'checked' : '' }}>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Section: Rekomendasi Asesor & Catatan --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900">Rekomendasi Hasil Wawancara</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex items-start space-x-3 p-3.5 rounded-xl border-2 cursor-pointer transition"
                    :class="recommendation === 'K' ? 'border-emerald-500 bg-emerald-50/50' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="recommendation" value="K" x-model="recommendation" :disabled="!isEditing" class="text-emerald-600 focus:ring-emerald-500 mt-0.5">
                    <div class="text-xs">
                        <div class="font-bold text-emerald-950">KOMPETEN (K)</div>
                        <p class="text-slate-600 mt-0.5">Asesi mampu menjawab pertanyaan wawancara dengan memadai dan memuaskan.</p>
                    </div>
                </label>

                <label class="flex items-start space-x-3 p-3.5 rounded-xl border-2 cursor-pointer transition"
                    :class="recommendation === 'BK' ? 'border-rose-500 bg-rose-50/50' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="recommendation" value="BK" x-model="recommendation" :disabled="!isEditing" class="text-rose-600 focus:ring-rose-500 mt-0.5">
                    <div class="text-xs">
                        <div class="font-bold text-rose-950">BELUM KOMPETEN (BK)</div>
                        <p class="text-slate-600 mt-0.5">Tanggapan asesi belum memenuhi standar unjuk kerja yang dipersyaratkan.</p>
                    </div>
                </label>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Asesor Terhadap Hasil Wawancara</label>
                <textarea name="assessor_notes" rows="3" :disabled="!isEditing" placeholder="Tuliskan catatan umum mengenai jalannya sesi wawancara..."
                    class="w-full text-xs rounded-lg border border-slate-300 px-3.5 py-2.5 bg-white">{{ old('assessor_notes', $savedNotes) }}</textarea>
            </div>
        </div>

        {{-- Signatures Section --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Asesor Signature --}}
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-900 block">Tanda Tangan Asesor</span>
                        <span class="text-[11px] text-slate-500">{{ auth()->user()->full_name }}</span>
                    </div>
                    <span class="text-[10px] font-mono text-slate-400">No. Reg: {{ auth()->user()->profile?->no_reg ?? auth()->user()->assessor_registration_number ?? '-' }}</span>
                </div>
                <div class="h-28 border border-slate-200 rounded-xl bg-slate-50 flex items-center justify-center p-2">
                    @if(auth()->user()?->profile?->signature_data)
                        <img src="{{ auth()->user()->profile->signature_data }}" alt="TTD Asesor" class="max-h-full max-w-full object-contain">
                        <input type="hidden" name="assessor_signature" value="{{ auth()->user()->profile->signature_data }}">
                    @else
                        <span class="text-xs text-amber-700 italic">Tanda tangan profil belum tersimpan di menu profil.</span>
                    @endif
                </div>
            </div>

            {{-- Asesi Signature --}}
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-900 block">Tanda Tangan Asesi</span>
                        <span class="text-[11px] text-slate-500">{{ $assessment->asesi->full_name }}</span>
                    </div>
                    <span class="text-[10px] text-slate-400">Disematkan otomatis dari sistem</span>
                </div>
                <div class="h-28 border border-slate-200 rounded-xl bg-slate-50 flex items-center justify-center p-2">
                    @php
                        $asesiSig = $response?->asesi_signature ?? $assessment->asesi_signature ?? $assessment->asesi?->profile?->signature_data;
                    @endphp
                    @if($asesiSig)
                        <img src="{{ $asesiSig }}" alt="TTD Asesi" class="max-h-full max-w-full object-contain">
                    @else
                        <span class="text-xs text-slate-400 italic">Tanda tangan asesi akan disematkan saat konfirmasi.</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="flex items-center justify-end gap-3 pt-2" x-show="isEditing">
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia09']) }}" class="px-5 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">
                Kembali
            </a>
            <button type="submit" class="px-6 py-2.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-md transition flex items-center gap-2">
                <span>Simpan Pertanyaan Wawancara (FR.IA.09)</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </button>
        </div>
    </form>
</div>
@endsection
