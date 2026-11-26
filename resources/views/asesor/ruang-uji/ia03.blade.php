@php
    $isCompleted = (bool) ($response?->assessor_signed_at || ! empty($response?->assessor_signature));
    $isScheduleClosed = (bool) ($assessment->schedule?->isClosedForAssessor());
    $savedData = $savedData ?? (is_array($response?->data_json) ? $response->data_json : (json_decode($response?->data_json ?? '[]', true) ?: []));
    $recommendation = $savedRecommendation ?? $savedData['recommendation'] ?? 'K';
@endphp
@extends('layouts.app', ['title' => 'FR.IA.03 - Pertanyaan Mendukung Observasi'])

@section('content')
<div class="space-y-6" x-data="{
    isEditing: {{ ($isCompleted || $isScheduleClosed) ? 'false' : 'true' }},
    recommendation: '{{ old('recommendation', $recommendation) }}',
    setAllQuestions(val) {
        if (!this.isEditing) return;
        const target = val.toLowerCase();
        document.querySelectorAll(`.q-radio-${target}`).forEach(el => {
            el.checked = true;
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
        if (target === 'm') {
            this.recommendation = 'K';
        } else if (target === 'tm') {
            this.recommendation = 'BK';
        }
    }
}">
    @if($isScheduleClosed)
        <div class="p-4 bg-emerald-50 border-2 border-emerald-300 rounded-xl text-emerald-900 flex items-center gap-3 shadow-xs">
            <span class="text-xl">🔒</span>
            <div>
                <div class="font-bold text-sm">Jadwal Asesmen Telah Resmi Ditutup &amp; Dikunci</div>
                <p class="text-xs text-emerald-800 mt-0.5">Seluruh instrumen pada jadwal ini berada dalam mode lihat (read-only). Data pertanyaan dan penilaian tidak dapat diubah lagi.</p>
            </div>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia03']) }}" class="text-xs text-brand-600 hover:text-brand-700 font-bold">&larr; Kembali ke Daftar Asesi IA.03</a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">FR.IA.03 – Pertanyaan Mendukung Observasi</h1>
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
                    <div class="text-xs font-bold text-emerald-950">Penilaian FR.IA.03 Telah Selesai &amp; Tersimpan</div>
                    <p class="text-[11px] text-emerald-800" x-show="!isEditing">Field penilaian dalam mode terkunci (read-only). Klik tombol 'Edit Penilaian' untuk mengubah.</p>
                    <p class="text-[11px] text-amber-800 font-semibold" x-show="isEditing" style="display: none;">Mode Pengeditan Aktif. Silakan sesuaikan hasil tanya jawab lisan dan catatan di bawah.</p>
                </div>
            </div>
            @if($response?->assessor_signed_at)
                <div class="text-right text-[11px] text-emerald-700 font-mono">
                    Dinilai: {{ \Carbon\Carbon::parse($response->assessor_signed_at)->isoFormat('D MMM Y, HH:mm') }} WIB
                </div>
            @endif
        </div>
    @endif

    <form action="{{ route('asesor.ruang-uji.ia03.store', $assessment->id) }}" method="POST" class="space-y-6">
        @csrf

        {{-- Oral Questions Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Daftar Pertanyaan Lisan</h2>
                    <p class="text-xs text-slate-500">Nilai tanggapan lisan asesi terhadap pertanyaan terkait unjuk kerja</p>
                </div>
                <div class="flex items-center gap-2" x-show="isEditing">
                    <button type="button" @click="setAllQuestions('M')" class="px-3.5 py-1.5 min-h-[36px] bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        Semua M
                    </button>
                    <button type="button" @click="setAllQuestions('TM')" class="px-3.5 py-1.5 min-h-[36px] bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Semua TM
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="hidden md:table-header-group bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3 w-16 text-center">No</th>
                            <th class="px-6 py-3">Daftar Pertanyaan &amp; Kunci Jawaban</th>
                            <th class="px-6 py-3 text-center w-40">Hasil (Memadai / Tidak)</th>
                            <th class="px-6 py-3 w-64">Tanggapan / Catatan Asesi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($questions ?? [] as $i => $q)
                            @php
                                $val = $savedAnswers[$q->id]['result'] ?? old("questions.{$q->id}.result");
                                $qResponse = $savedAnswers[$q->id]['response'] ?? old("questions.{$q->id}.response");
                            @endphp
                            <tr class="flex flex-col md:table-row p-4 md:p-0 hover:bg-slate-50/50 transition space-y-2.5 md:space-y-0">
                                <td class="hidden md:table-cell px-6 py-3 text-slate-500 font-mono text-xs text-center font-bold">{{ $i + 1 }}</td>
                                <td class="px-0 md:px-6 py-0 md:py-3 text-slate-800">
                                    <div class="flex items-start gap-2">
                                        <span class="md:hidden font-mono text-xs font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded shrink-0">#{{ $i + 1 }}</span>
                                        <div class="font-medium text-xs sm:text-sm leading-relaxed">{{ $q->question }}</div>
                                    </div>
                                    @if(!empty($q->expected_answer))
                                        <div class="text-xs text-slate-600 mt-2 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                            <span class="font-bold text-slate-700">Kunci / Jawaban yang Diharapkan:</span> {{ $q->expected_answer }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-0 md:px-6 py-0 md:py-3 text-left md:text-center">
                                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider md:hidden mb-1.5">Penilaian Asesor:</label>
                                    <div class="grid grid-cols-2 gap-2 md:flex md:items-center md:justify-center md:space-x-3">
                                        <label class="flex items-center justify-center p-2 md:p-0 bg-emerald-50 md:bg-transparent rounded-lg md:rounded-none border md:border-0 border-emerald-200 cursor-pointer min-h-[38px] md:min-h-0">
                                            <input type="radio" name="questions[{{ $q->id }}][result]" value="M" required :disabled="!isEditing" class="q-radio q-radio-m text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer" {{ $val === 'M' ? 'checked' : '' }}>
                                            <span class="ml-1.5 text-xs font-bold text-emerald-800 md:text-emerald-700">Memadai (M)</span>
                                        </label>
                                        <label class="flex items-center justify-center p-2 md:p-0 bg-rose-50 md:bg-transparent rounded-lg md:rounded-none border md:border-0 border-rose-200 cursor-pointer min-h-[38px] md:min-h-0">
                                            <input type="radio" name="questions[{{ $q->id }}][result]" value="TM" required :disabled="!isEditing" class="q-radio q-radio-tm text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer" {{ $val === 'TM' ? 'checked' : '' }}>
                                            <span class="ml-1.5 text-xs font-bold text-rose-800 md:text-rose-700">Tidak (TM)</span>
                                        </label>
                                    </div>
                                    <div class="hidden md:block text-[10px] text-slate-400 mt-0.5">M: Memadai | TM: Tidak</div>
                                </td>
                                <td class="px-0 md:px-6 py-0 md:py-3">
                                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider md:hidden mb-1">Tanggapan / Catatan Asesi:</label>
                                    <textarea name="questions[{{ $q->id }}][response]" rows="2" :disabled="!isEditing" placeholder="Catatan tanggapan asesi..." class="w-full px-3 py-2 md:py-1 text-xs rounded-lg border border-slate-300 focus:ring-1 focus:ring-brand-500 focus:outline-none bg-white">{{ $qResponse }}</textarea>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-slate-500 text-xs">Belum ada daftar pertanyaan lisan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Section: Rekomendasi Hasil Pertanyaan Mendukung Observasi --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900">Rekomendasi Hasil Pertanyaan Mendukung Observasi</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex items-start space-x-3 p-3.5 rounded-xl border-2 cursor-pointer transition"
                    :class="recommendation === 'K' ? 'border-emerald-500 bg-emerald-50/50' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="recommendation" value="K" x-model="recommendation" :disabled="!isEditing" class="text-emerald-600 focus:ring-emerald-500 mt-0.5">
                    <div class="text-xs">
                        <div class="font-bold text-emerald-950">KOMPETEN (K)</div>
                        <p class="text-slate-600 mt-0.5">Asesi mampu menjawab pertanyaan lisan pendukung observasi dengan memuaskan dan memenuhi standar.</p>
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
        </div>

        {{-- Notes & Assessor Signature --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900">Umpan Balik PMO</h2>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Catatan Asesor</label>
                <textarea name="notes" rows="3" :disabled="!isEditing" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Catatan umum mengenai penguasaan pengetahuan lisan asesi...">{{ $savedNotes ?? old('notes') }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-200">
                <div class="w-full max-w-full sm:max-w-[340px]">
                    <x-signature-pad name="assessor_signature" :value="$savedSignature ?? null" label="Tanda Tangan Asesor" :required="! $isScheduleClosed" :disabled="$isScheduleClosed" :height="140" />
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2.5">
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia03']) }}" class="px-5 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition min-h-[40px] inline-flex items-center justify-center">Kembali</a>
            @if($isScheduleClosed)
                <button type="button" disabled class="px-6 py-2.5 text-xs font-bold bg-slate-200 text-slate-500 border border-slate-300 rounded-lg cursor-not-allowed flex items-center justify-center gap-2 shadow-none min-h-[40px]">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>Jadwal Telah Ditutup (Read-Only)</span>
                </button>
            @else
                @if($isCompleted)
                    <button type="button" x-show="!isEditing" @click="isEditing = true" class="px-6 py-2.5 text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white rounded-lg shadow-sm transition min-h-[40px] inline-flex items-center justify-center">
                        ✎ Edit Penilaian
                    </button>
                @endif
                <button type="submit" x-show="isEditing" class="px-6 py-2.5 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition min-h-[40px] inline-flex items-center justify-center">
                    {{ $isCompleted ? 'Simpan Perubahan Penilaian' : 'Simpan Penilaian IA03' }}
                </button>
            @endif
        </div>
    </form>
</div>
@endsection
