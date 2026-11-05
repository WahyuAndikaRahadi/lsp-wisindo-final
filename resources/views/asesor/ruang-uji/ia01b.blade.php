@php
    $isCompleted = (bool) $response;
    $isScheduleClosed = (bool) ($assessment->schedule?->isClosedForAssessor());
@endphp
@extends('layouts.app', ['title' => 'FR.IA.01B - Lembar Pemeriksaan Teknis (Report Sheet)'])

@section('content')
<div class="space-y-6" x-data="{
    isEditing: {{ ($isCompleted || $isScheduleClosed) ? 'false' : 'true' }},
    setAllItems(val) {
        if (!this.isEditing) return;
        const targetVal = (val === 'K' || val === 'SESUAI') ? 'sesuai' : 'tidak';
        document.querySelectorAll(`.item-radio-${targetVal}`).forEach(el => {
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
                <p class="text-xs text-emerald-800 mt-0.5">Seluruh instrumen pada jadwal ini berada dalam mode lihat (read-only). Data pengukuran dan penilaian tidak dapat diubah lagi.</p>
            </div>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia01b']) }}" class="text-xs text-brand-600 hover:text-brand-700 font-bold">&larr; Kembali ke Daftar Asesi IA.01B</a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">FR.IA.01B – Lembar Hasil Pengukuran &amp; Pemeriksaan Teknis (Report Sheet)</h1>
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

        {{-- Inspeksi Bukti Lembar Fisik Unggahan Asesi (Report Sheet FR.IA.01B) --}}
        @php
            $evidencePath = $response?->data_json['evidence_image_path'] ?? null;
            $evidenceTime = $response?->data_json['evidence_uploaded_at'] ?? null;
            $evidenceName = $response?->data_json['evidence_original_name'] ?? 'Report_Sheet_Fisik.jpg';
            $evidenceUrl = ($response && $evidencePath) ? route('secure-file.evidence', $response) : '#';
        @endphp
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="bg-slate-900 text-white px-6 py-3.5 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center space-x-2.5">
                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-amber-400 text-slate-950">
                        FR.IA.01B
                    </span>
                    <span class="text-xs font-bold uppercase tracking-wider text-white">Lembar Pemeriksaan Teknis Fisik (Report Sheet Unggahan Asesi)</span>
                </div>
                @if($evidencePath)
                    <span class="px-2.5 py-0.5 text-[11px] font-bold bg-emerald-500 text-white rounded">
                        ✓ Lembar Fisik Telah Diunggah Asesi
                    </span>
                @else
                    <span class="px-2.5 py-0.5 text-[11px] font-bold bg-amber-500 text-slate-900 rounded">
                        Menunggu Unggahan Asesi
                    </span>
                @endif
            </div>

            <div class="p-6">
                @if($evidencePath)
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        {{-- Preview Box --}}
                        <div class="lg:col-span-5 bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-3">
                            <div class="relative group rounded-lg overflow-hidden border border-slate-300 bg-white aspect-4/3 flex items-center justify-center">
                                @if(str_ends_with(strtolower($evidencePath), '.pdf'))
                                    <div class="text-center p-6 space-y-2">
                                        <span class="text-4xl text-rose-600 font-black block">📄</span>
                                        <div class="text-xs font-bold text-slate-800 truncate max-w-[200px]">{{ $evidenceName }}</div>
                                        <div class="text-[11px] text-slate-500">Dokumen PDF Report Sheet</div>
                                    </div>
                                @else
                                    <img src="{{ $evidenceUrl }}" alt="Report Sheet Fisik Asesi" class="w-full h-full object-contain cursor-pointer transition hover:scale-105" onclick="window.open('{{ $evidenceUrl }}', '_blank')">
                                @endif
                            </div>
                            <div class="pt-1">
                                <a href="{{ $evidenceUrl }}" target="_blank" class="w-full text-center px-4 py-2.5 text-xs font-bold uppercase tracking-wider bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition inline-flex items-center justify-center gap-1.5">
                                    <span>🔍 Buka Lembar Penuh (Tab Baru)</span>
                                    <span>&nearr;</span>
                                </a>
                            </div>
                        </div>

                        {{-- Metadata & Instruction --}}
                        <div class="lg:col-span-7 space-y-4 text-xs">
                            <div class="border border-slate-200 rounded-lg p-4 bg-slate-50/80 space-y-2">
                                <div class="font-bold text-slate-900 text-sm">Informasi Berkas Unggahan Asesi:</div>
                                <div class="text-slate-700">Nama Asesi: <strong class="text-slate-900">{{ $assessment->asesi->full_name }}</strong></div>
                                <div class="text-slate-700">Waktu Unggah: <span class="font-mono font-semibold text-slate-900">{{ \Carbon\Carbon::parse($evidenceTime)->isoFormat('D MMMM Y, HH:mm') }} WIB</span></div>
                                <div class="text-slate-700">Nama File: <span class="font-mono text-slate-700">{{ $evidenceName }}</span></div>
                            </div>

                            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-950 text-xs leading-relaxed space-y-1">
                                <div class="font-bold flex items-center gap-1.5 text-emerald-900">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Verifikasi Hasil Pengukuran Fisik:</span>
                                </div>
                                <p class="text-emerald-900">
                                    Asesi telah mengunggah lembar pemeriksaan teknis (Report Sheet) fisik di samping. Silakan teliti isian pengukuran teknis yang dibuat asesi dan lakukan penandatanganan verifikasi Anda di bawah.
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="p-8 rounded-xl border-2 border-dashed border-amber-300 bg-amber-50/50 text-center space-y-3">
                        <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center mx-auto">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div class="max-w-md mx-auto">
                            <h3 class="text-sm font-bold text-amber-950">Menunggu Asesi Mengunggah Lembar Report Sheet Fisik</h3>
                            <p class="text-xs text-amber-800 mt-1 leading-relaxed">
                                Asesi <strong>{{ $assessment->asesi->full_name }}</strong> belum mengunggah foto / scan berkas lembar kerja pemeriksaan fisik (FR.IA.01B). Asesi dapat mengunggahnya secara mandiri melalui akun portal asesi.
                            </p>
                            <p class="text-[11px] text-slate-500 mt-2">
                                Setelah berkas diunggah oleh asesi, dokumen akan langsung tampil di sini untuk Anda periksa dan tandatangani.
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

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
                <div class="max-w-[340px]">
                    <x-signature-pad name="assessor_signature" :value="$savedSignature ?? null" label="Tanda Tangan Asesor" :required="! $isScheduleClosed" :disabled="$isScheduleClosed" :height="140" />
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia01b']) }}" class="px-5 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">Kembali</a>
            @if($isScheduleClosed)
                <button type="button" disabled class="px-6 py-2.5 text-sm font-bold bg-slate-200 text-slate-500 border border-slate-300 rounded-lg cursor-not-allowed flex items-center gap-2 shadow-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>Jadwal Telah Ditutup (Read-Only)</span>
                </button>
            @else
                <button type="button" x-show="!isEditing" @click="isEditing = true" class="px-6 py-2.5 text-sm font-bold bg-amber-500 hover:bg-amber-600 text-white rounded-lg shadow-sm transition">
                    ✎ Edit Penilaian
                </button>
                <button type="submit" x-show="isEditing" class="px-6 py-2.5 text-sm font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition">
                    {{ $isCompleted ? 'Simpan Perubahan Penilaian' : 'Simpan Penilaian FR.IA.01B' }}
                </button>
            @endif
        </div>
    </form>
</div>
@endsection
