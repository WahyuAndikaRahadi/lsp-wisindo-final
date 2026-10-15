@extends('layouts.app', ['title' => 'FR.IA.03 - Pertanyaan Mendukung Observasi'])

@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia03']) }}" class="text-xs text-brand-600 hover:text-brand-700 font-bold">&larr; Kembali ke Daftar Asesi IA.03</a>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">FR.IA.03 – Pertanyaan Mendukung Observasi</h1>
        <p class="text-sm text-slate-500">Asesi: <strong>{{ $assessment->asesi->full_name }}</strong> | Skema: <strong>{{ $assessment->schedule->scheme->name }}</strong></p>
    </div>

    <form action="{{ route('asesor.ruang-uji.ia03.store', $assessment->id) }}" method="POST" class="space-y-6">
        @csrf

        {{-- Oral Questions Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" x-data="{
            setAllQuestions(val) {
                const target = val.toLowerCase();
                document.querySelectorAll(`.q-radio-${target}`).forEach(el => {
                    el.checked = true;
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                });
            }
        }">
            <div class="px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Daftar Pertanyaan Lisan</h2>
                    <p class="text-xs text-slate-500">Nilai tanggapan lisan asesi terhadap pertanyaan terkait unjuk kerja</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="setAllQuestions('M')" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        Semua M (Memuaskan)
                    </button>
                    <button type="button" @click="setAllQuestions('TM')" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Semua TM
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3 w-16">No</th>
                            <th class="px-6 py-3">Daftar Pertanyaan & Kunci Jawaban</th>
                            <th class="px-6 py-3 text-center w-40">Hasil (Memuaskan / Tidak)</th>
                            <th class="px-6 py-3 w-48">Tanggapan / Catatan Asesi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($questions ?? [] as $i => $q)
                            @php
                                $val = $savedAnswers[$q->id]['result'] ?? old("questions.{$q->id}.result");
                                $response = $savedAnswers[$q->id]['response'] ?? old("questions.{$q->id}.response");
                            @endphp
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-3 text-slate-500 font-mono text-xs">{{ $i + 1 }}</td>
                                <td class="px-6 py-3 text-slate-800">
                                    <div class="font-medium text-sm">{{ $q->question }}</div>
                                    @if(isset($q->expected_answer))
                                        <div class="text-xs text-slate-500 mt-1 bg-slate-50 p-2 rounded border border-slate-100">
                                            <span class="font-semibold text-slate-600">Kunci / Jawaban yang Diharapkan:</span> {{ $q->expected_answer }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-3">
                                        <label class="p-2 inline-flex items-center cursor-pointer">
                                            <input type="radio" name="questions[{{ $q->id }}][result]" value="M" required class="q-radio q-radio-m text-emerald-600 focus:ring-emerald-500 w-5 h-5 cursor-pointer" {{ $val === 'M' ? 'checked' : '' }}>
                                            <span class="ml-1.5 text-xs font-bold text-emerald-700">M</span>
                                        </label>
                                        <label class="p-2 inline-flex items-center cursor-pointer">
                                            <input type="radio" name="questions[{{ $q->id }}][result]" value="TM" required class="q-radio q-radio-tm text-rose-600 focus:ring-rose-500 w-5 h-5 cursor-pointer" {{ $val === 'TM' ? 'checked' : '' }}>
                                            <span class="ml-1.5 text-xs font-bold text-rose-700">TM</span>
                                        </label>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">M: Memuaskan | TM: Tidak</div>
                                </td>
                                <td class="px-6 py-3">
                                    <textarea name="questions[{{ $q->id }}][response]" rows="2" placeholder="Catatan tanggapan asesi..." class="w-full px-2.5 py-1 text-xs rounded border border-slate-300 focus:ring-1 focus:ring-brand-500 focus:outline-none">{{ $response }}</textarea>
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

        {{-- Notes & Assessor Signature --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900">Umpan Balik PMO</h2>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Catatan Asesor</label>
                <textarea name="notes" rows="3" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Catatan umum mengenai penguasaan pengetahuan lisan asesi...">{{ $savedNotes ?? old('notes') }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-200">
                <x-signature-pad name="assessor_signature" :value="$savedSignature ?? null" label="Tanda Tangan Asesor" :required="true" />
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia03']) }}" class="px-5 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">Batal</a>
            <button type="submit" class="px-6 py-2.5 text-sm font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition">
                Simpan Penilaian IA03
            </button>
        </div>
    </form>
</div>
@endsection
