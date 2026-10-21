@php
    $isCompleted = (bool) $response;
@endphp
@extends('layouts.app', ['title' => 'FR.IA.01B - Lembar Pemeriksaan Teknis (Report Sheet)'])

@section('content')
<div class="space-y-6" x-data="{
    isEditing: {{ $isCompleted ? 'false' : 'true' }},
    setAllItems(val) {
        if (!this.isEditing) return;
        const targetVal = (val === 'K' || val === 'SESUAI') ? 'sesuai' : 'tidak';
        document.querySelectorAll(`.item-radio-${targetVal}`).forEach(el => {
            el.checked = true;
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }
}">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia01b']) }}" class="text-xs text-brand-600 hover:text-brand-700 font-bold">&larr; Kembali ke Daftar Asesi IA.01B</a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">FR.IA.01B – Lembar Hasil Pengukuran &amp; Pemeriksaan Teknis (Report Sheet)</h1>
            <p class="text-sm text-slate-500">Asesi: <strong>{{ $assessment->asesi->full_name }}</strong> | Skema: <strong>{{ $assessment->schedule->scheme->name }}</strong></p>
        </div>

        @if($isCompleted)
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
                    <div class="text-xs font-bold text-emerald-950">Penilaian FR.IA.01B Telah Selesai &amp; Tersimpan</div>
                    <p class="text-[11px] text-emerald-800" x-show="!isEditing">Field penilaian dalam mode terkunci (read-only). Klik tombol 'Edit Penilaian' untuk mengubah.</p>
                    <p class="text-[11px] text-amber-800 font-semibold" x-show="isEditing" style="display: none;">Mode Pengeditan Aktif. Silakan sesuaikan hasil pengukuran teknis dan catatan di bawah.</p>
                </div>
            </div>
            @if($response?->assessor_signed_at)
                <div class="text-right text-[11px] text-emerald-700 font-mono">
                    Dinilai: {{ \Carbon\Carbon::parse($response->assessor_signed_at)->isoFormat('D MMM Y, HH:mm') }} WIB
                </div>
            @endif
        </div>
    @endif

    <form action="{{ route('asesor.ruang-uji.ia01b.store', $assessment->id) }}" method="POST" class="space-y-6">
        @csrf

        {{-- Single Table for All Units with Universal Fast Actions at Top --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            {{-- Bagian Atas Table: Header & Tombol Universal Semua Sesuai / Semua Tidak --}}
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold text-brand-700 font-mono">FORMULIR TEKNIS SPESIFIK SKEMA</span>
                    <h2 class="text-base font-bold text-slate-900">Item Pengukuran &amp; Lembar Kerja Asesi</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Verifikasi hasil pengukuran fisik dan kondisi komponen yang dikerjakan oleh asesi</p>
                </div>
                <div class="flex items-center gap-2.5 flex-wrap" x-show="isEditing">
                    <span class="text-xs font-semibold text-slate-500 mr-1 hidden sm:inline">Aksi Cepat Verifikasi:</span>
                    <button type="button" @click="setAllItems('SESUAI')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        Semua Sesuai
                    </button>
                    <button type="button" @click="setAllItems('TIDAK')" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white rounded-lg text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Semua Tidak
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100/70 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3 w-16 text-center">No</th>
                            <th class="px-6 py-3">Item Pemeriksaan / Bagian Diukur</th>
                            <th class="px-6 py-3 w-64">Spesifikasi Standar</th>
                            <th class="px-6 py-3 w-48">Hasil Pengukuran Asesi</th>
                            <th class="px-6 py-3 text-center w-44">Verifikasi (Sesuai / Tidak)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @php
                            $lastUnitCode = null;
                        @endphp
                        @forelse($reportSheetItems ?? [] as $idx => $item)
                            @php
                                $val = $savedAnswers[$idx]['result'] ?? old("items.{$idx}.result");
                                $measured = $savedAnswers[$idx]['measured'] ?? old("items.{$idx}.measured");

                                preg_match('/([A-Z0-9]+\.[A-Z0-9]+\.[A-Z0-9]+\.[A-Z0-9]+)/', $item['item'], $m);
                                $matchedCode = $m[1] ?? null;
                                $matchedUnit = $matchedCode ? ($units->firstWhere('code', $matchedCode)) : null;
                            @endphp

                            @if($matchedUnit && $matchedCode !== $lastUnitCode)
                                @php $lastUnitCode = $matchedCode; @endphp
                                <tr class="bg-slate-100/90 border-t-2 border-b border-slate-200">
                                    <td colspan="5" class="px-6 py-3.5 bg-slate-100">
                                        <div class="flex items-center gap-2.5">
                                            <span class="text-xs font-bold text-brand-700 font-mono bg-brand-50 border border-brand-200 px-2.5 py-1 rounded shadow-2xs">
                                                {{ $matchedUnit->code }}
                                            </span>
                                            <span class="text-sm font-bold text-slate-900">
                                                {{ $matchedUnit->title }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endif

                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-3 text-slate-500 font-mono text-xs text-center font-bold">{{ $idx + 1 }}</td>
                                <td class="px-6 py-3 text-slate-800 font-medium">
                                    {{ $item['item'] }}
                                </td>
                                <td class="px-6 py-3 text-slate-600 text-xs">
                                    <span class="bg-slate-100 px-2.5 py-1 rounded border border-slate-200 inline-block font-mono">
                                        {{ $item['standard'] ?? 'Workshop Manual' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    <input type="text" name="items[{{ $idx }}][measured]" value="{{ $measured }}" :disabled="!isEditing" placeholder="Nilai / kondisi..." class="w-full px-2.5 py-1 text-xs rounded border border-slate-300 focus:ring-1 focus:ring-brand-500 focus:outline-none">
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-3">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="radio" name="items[{{ $idx }}][result]" value="SESUAI" required :disabled="!isEditing" class="item-radio item-radio-sesuai text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer" {{ $val === 'SESUAI' ? 'checked' : '' }}>
                                            <span class="ml-1 text-xs font-semibold text-emerald-700">Sesuai</span>
                                        </label>
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="radio" name="items[{{ $idx }}][result]" value="TIDAK" required :disabled="!isEditing" class="item-radio item-radio-tidak text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer" {{ $val === 'TIDAK' ? 'checked' : '' }}>
                                            <span class="ml-1 text-xs font-semibold text-rose-700">Tidak</span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500 text-sm">Tidak ada item lembar ukur khusus untuk skema ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Notes & Assessor Signature --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900">Umpan Balik &amp; Rekomendasi Hasil Kerja Teknis</h2>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Catatan Asesor</label>
                <textarea name="notes" rows="3" :disabled="!isEditing" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Catatan ketepatan toleransi ukur dan kualitas hasil kerja asesi...">{{ $savedNotes ?? old('notes') }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-200">
                <x-signature-pad name="assessor_signature" :value="$savedSignature ?? null" label="Tanda Tangan Asesor" :required="true" />
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia01b']) }}" class="px-5 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">Kembali</a>
            <button type="button" x-show="!isEditing" @click="isEditing = true" class="px-6 py-2.5 text-sm font-bold bg-amber-500 hover:bg-amber-600 text-white rounded-lg shadow-sm transition">
                ✎ Edit Penilaian
            </button>
            <button type="submit" x-show="isEditing" class="px-6 py-2.5 text-sm font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition">
                {{ $isCompleted ? 'Simpan Perubahan Penilaian' : 'Simpan Penilaian FR.IA.01B' }}
            </button>
        </div>
    </form>
</div>
@endsection
