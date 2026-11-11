@php
    $isCompleted = (bool) ($response?->assessor_signed_at || ! empty($response?->assessor_signature));
    $isScheduleClosed = (bool) ($assessment->schedule?->isClosedForAssessor());
@endphp
@extends('layouts.app', ['title' => 'FR.IA.01 - Ceklis Observasi'])

@section('content')
<div class="space-y-6" x-data="{
    isEditing: {{ ($isCompleted || $isScheduleClosed) ? 'false' : 'true' }},
    setAllKuk(val) {
        if (!this.isEditing) return;
        const targetVal = val.toLowerCase();
        document.querySelectorAll(`.kuk-radio-${targetVal}`).forEach(el => {
            el.checked = true;
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
    },
    setUnitKuk(unitId, val) {
        if (!this.isEditing) return;
        const targetVal = val.toLowerCase();
        document.querySelectorAll(`.kuk-radio-${targetVal}[data-unit-id='${unitId}']`).forEach(el => {
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
                <p class="text-xs text-emerald-800 mt-0.5">Seluruh instrumen pada jadwal ini berada dalam mode lihat (read-only). Data observasi dan penilaian tidak dapat diubah lagi.</p>
            </div>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia01']) }}" class="text-xs text-brand-600 hover:text-brand-700 font-bold">&larr; Kembali ke Daftar Asesi IA.01</a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">FR.IA.01 – Ceklis Observasi Aktivitas di Tempat Kerja</h1>
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
                    <div class="text-xs font-bold text-emerald-950">Penilaian FR.IA.01 Telah Selesai &amp; Tersimpan</div>
                    <p class="text-[11px] text-emerald-800" x-show="!isEditing">Field penilaian dalam mode terkunci (read-only). Klik tombol 'Edit Penilaian' untuk mengubah.</p>
                    <p class="text-[11px] text-amber-800 font-semibold" x-show="isEditing" style="display: none;">Mode Pengeditan Aktif. Silakan sesuaikan hasil observasi KUK di bawah.</p>
                </div>
            </div>
            @if($response?->assessor_signed_at)
                <div class="text-right text-[11px] text-emerald-700 font-mono">
                    Dinilai: {{ \Carbon\Carbon::parse($response->assessor_signed_at)->isoFormat('D MMM Y, HH:mm') }} WIB
                </div>
            @endif
        </div>
    @endif

    <form action="{{ route('asesor.ruang-uji.ia01.store', $assessment->id) }}" method="POST" class="space-y-6">
        @csrf

        {{-- Single Table for All Units with Universal Fast Actions at Top --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            {{-- Bagian Atas Table: Header & Tombol Universal Semua K / Semua BK --}}
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Daftar Ceklis Observasi Unit Kompetensi</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Observasi aktivitas unjuk kerja asesi pada seluruh unit kompetensi dan kriteria unjuk kerja</p>
                </div>
                <div class="flex items-center gap-2.5 flex-wrap" x-show="isEditing">
                    <span class="text-xs font-semibold text-slate-500 mr-1 hidden sm:inline">Aksi Cepat Seluruh KUK:</span>
                    <button type="button" @click="setAllKuk('K')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        Semua K
                    </button>
                    <button type="button" @click="setAllKuk('BK')" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white rounded-lg text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Semua BK
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100/70 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 w-16 text-center whitespace-nowrap">KUK</th>
                            <th class="px-4 py-3 min-w-[200px]">Elemen &amp; Kriteria Unjuk Kerja (KUK)</th>
                            <th class="px-3 py-3 text-center w-16 sm:w-20 whitespace-nowrap" title="Kompeten">K</th>
                            <th class="px-3 py-3 text-center w-16 sm:w-20 whitespace-nowrap" title="Belum Kompeten">BK</th>
                            <th class="px-4 py-3 w-44">Penilaian / Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($units ?? [] as $uIdx => $unit)
                            {{-- Baris Pemisah Unit Kompetensi --}}
                            <tr class="bg-slate-100 border-t-2 border-b border-slate-200">
                                <td colspan="5" class="px-4 py-3.5 bg-slate-100">
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-xs font-bold text-brand-700 font-mono bg-brand-50 border border-brand-200 px-2.5 py-1 rounded shadow-2xs">
                                            {{ $unit->code }}
                                        </span>
                                        <span class="text-sm font-bold text-slate-900">
                                            {{ $unit->title }}
                                        </span>
                                    </div>
                                </td>
                            </tr>

                            @foreach($unit->elements as $element)
                                @if(!empty($element->title))
                                    <tr class="bg-slate-50/60 border-b border-slate-100">
                                        <td class="px-4 py-2 text-center text-slate-400 font-mono text-xs">#</td>
                                        <td colspan="4" class="px-4 py-2 text-xs font-bold text-slate-700">
                                            Elemen: {{ $element->title }}
                                        </td>
                                    </tr>
                                @endif
                                @foreach($element->kuks as $kuk)
                                    @php
                                        $savedValue = $savedAnswers[$kuk->id]['result'] ?? old("kuks.{$kuk->id}.result");
                                        $savedNote = $savedAnswers[$kuk->id]['note'] ?? old("kuks.{$kuk->id}.note");
                                    @endphp
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-4 py-3 text-slate-600 font-mono text-xs font-bold text-center whitespace-nowrap">{{ $kuk->code_number }}</td>
                                        <td class="px-4 py-3 text-slate-800">
                                            <div class="text-sm leading-relaxed">{{ $kuk->statement }}</div>
                                        </td>
                                        <td class="px-3 py-3 text-center whitespace-nowrap">
                                            <label class="inline-flex items-center cursor-pointer">
                                                <input type="radio" name="kuks[{{ $kuk->id }}][result]" value="K" data-unit-id="{{ $unit->id }}" required :disabled="!isEditing" class="kuk-radio kuk-radio-k text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer" {{ $savedValue === 'K' ? 'checked' : '' }}>
                                                <span class="ml-1 text-xs font-semibold text-emerald-700">K</span>
                                            </label>
                                        </td>
                                        <td class="px-3 py-3 text-center whitespace-nowrap">
                                            <label class="inline-flex items-center cursor-pointer">
                                                <input type="radio" name="kuks[{{ $kuk->id }}][result]" value="BK" data-unit-id="{{ $unit->id }}" required :disabled="!isEditing" class="kuk-radio kuk-radio-bk text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer" {{ $savedValue === 'BK' ? 'checked' : '' }}>
                                                <span class="ml-1 text-xs font-semibold text-rose-700">BK</span>
                                            </label>
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="text" name="kuks[{{ $kuk->id }}][note]" value="{{ $savedNote }}" :disabled="!isEditing" placeholder="Catatan..." class="w-full px-2.5 py-1 text-xs rounded border border-slate-300 focus:ring-1 focus:ring-brand-500 focus:outline-none">
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500 text-sm">
                                    Tidak ada data unit/KUK untuk skema ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- General Notes --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900">Umpan Balik dan Catatan Umum</h2>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Catatan Asesor</label>
                <textarea name="general_notes" rows="3" :disabled="!isEditing" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Catatan umum atau rekomendasi untuk asesi...">{{ $savedGeneralNotes ?? old('general_notes') }}</textarea>
            </div>
        </div>

        {{-- Assessor Signature --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Tanda Tangan Asesor</h2>
                <p class="text-xs text-slate-500 mt-0.5">Pengesahan hasil observasi unjuk kerja praktik FR.IA.01</p>
            </div>
            <div class="w-full max-w-full sm:max-w-[340px]">
                <x-signature-pad name="assessor_signature" :value="$savedSignature ?? null" label="Tanda Tangan Asesor" :required="! $isScheduleClosed" :disabled="$isScheduleClosed" :height="140" />
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2.5">
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia01']) }}" class="px-5 py-2.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition min-h-[40px] inline-flex items-center justify-center">Kembali</a>
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
                    {{ $isCompleted ? 'Simpan Perubahan Penilaian' : 'Simpan Penilaian IA01' }}
                </button>
            @endif
        </div>
    </form>
</div>
@endsection
