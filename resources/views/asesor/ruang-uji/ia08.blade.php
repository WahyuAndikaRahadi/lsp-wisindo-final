@php
    $isCompleted = (bool) ($response?->assessor_signed_at || ! empty($response?->assessor_signature));
    $isScheduleClosed = (bool) ($assessment->schedule?->isClosedForAssessor());
    $savedData = $savedData ?? (is_array($response?->data_json) ? $response->data_json : (json_decode($response?->data_json ?? '[]', true) ?: []));
    $portfolios = $portfolios ?? $savedData['portfolios'] ?? [];
    $vatmData = $savedVatm ?? $savedData['vatm'] ?? [];
    $clarificationElements = $savedClarificationElements ?? $savedData['clarification_elements'] ?? [];
    $recommendation = $savedRecommendation ?? $savedData['recommendation'] ?? 'K';
    $remediation = $savedRemediation ?? [
        'unit' => $savedData['remediation_unit'] ?? '',
        'element' => $savedData['remediation_element'] ?? '',
        'kuk' => $savedData['remediation_kuk'] ?? '',
    ];
@endphp
@extends('layouts.app', ['title' => 'FR.IA.08 - Ceklis Verifikasi Portofolio'])

@section('content')
<div class="space-y-6" x-data="{
    isEditing: {{ ($isCompleted || $isScheduleClosed) ? 'false' : 'true' }},
    recommendation: '{{ old('recommendation', $recommendation) }}',
    setAllVatm(val) {
        if (!this.isEditing) return;
        const target = val.toLowerCase();
        document.querySelectorAll(`.vatm-radio-${target}`).forEach(el => {
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
                <p class="text-xs text-emerald-800 mt-0.5">Seluruh instrumen pada jadwal ini berada dalam mode lihat (read-only). Data verifikasi portofolio tidak dapat diubah lagi.</p>
            </div>
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia08']) }}" class="text-xs text-brand-600 hover:text-brand-700 font-bold">&larr; Kembali ke Daftar Asesi IA.08</a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">FR.IA.08 – Ceklis Verifikasi Portofolio (CVP)</h1>
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
                    <div class="text-xs font-bold text-emerald-950">Verifikasi FR.IA.08 Telah Selesai &amp; Tersimpan</div>
                    <p class="text-[11px] text-emerald-800" x-show="!isEditing">Hasil verifikasi portofolio dan rekomendasi tersimpan resmi. Klik tombol 'Edit Penilaian' untuk memperbarui.</p>
                    <p class="text-[11px] text-amber-800 font-semibold" x-show="isEditing" style="display: none;">Mode Pengeditan Aktif. Silakan sesuaikan verifikasi aturan bukti VATM dan rekomendasi.</p>
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
    <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-4 text-xs text-blue-950 space-y-1.5 leading-relaxed shadow-2xs">
        <div class="font-bold uppercase tracking-wider text-blue-900 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>PANDUAN BAGI ASESOR</span>
        </div>
        <ul class="list-disc pl-5 space-y-1 text-slate-700">
            <li>Verifikasi portofolio dapat dilakukan untuk keseluruhan unit kompetensi dalam skema sertifikasi atau dilakukan untuk masing-masing kelompok pekerjaan dalam satu skema sertifikasi.</li>
            <li>Isilah bukti portofolio sesuai ketentuan bukti berkualitas dan relevan dengan standar kompetensi kerja sebagaimana yang telah disepakati pada rekaman asesmen mandiri.</li>
            <li>Lakukan verifikasi portofolio berdasarkan aturan bukti (<strong>VATM</strong>: Valid, Asli, Terkini, Memadai). Berikan tanda centang (✓) pada kolom yang sesuai.</li>
            <li>Jika hasil verifikasi dokumen portofolio belum memenuhi aturan bukti maka asesor melanjutkan dengan metode tanya jawab pertanyaan wawancara (FR.IA.09) dan/atau verifikasi pihak ketiga.</li>
        </ul>
    </div>

    <form action="{{ route('asesor.ruang-uji.ia08.store', $assessment->id) }}" method="POST" class="space-y-6">
        @csrf

        {{-- Section 1: Dokumen Portofolio & Verifikasi VATM --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50">
                <div>
                    <h2 class="text-base font-bold text-slate-900">1. Bukti Portofolio &amp; Aturan Bukti (VATM)</h2>
                    <p class="text-xs text-slate-500">Verifikasi dokumen portofolio asesi berdasarkan aturan bukti Valid (V), Asli (A), Terkini (T), dan Memadai (M)</p>
                </div>
                <div class="flex items-center gap-2" x-show="isEditing">
                    <button type="button" @click="setAllVatm('ya')" class="px-3.5 py-1.5 min-h-[36px] bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        Semua Ya
                    </button>
                    <button type="button" @click="setAllVatm('tidak')" class="px-3.5 py-1.5 min-h-[36px] bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Semua Tidak
                    </button>
                </div>
            </div>

            @if(empty($portfolios))
                <div class="p-8 text-center bg-amber-50/50 border-b border-slate-200">
                    <div class="w-12 h-12 bg-amber-100 text-amber-700 rounded-full flex items-center justify-center mx-auto mb-3 font-bold text-xl">
                        📁
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Asesi Belum Mengunggah Dokumen Portofolio</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                        Asesi dapat mengunggah 1 hingga 3 berkas portofolio (seperti Surat Pengalaman Kerja, Sertifikat Pelatihan/Kompetensi, SPK/Work Order, atau Log Book) melalui akun asesi di Ruang Uji.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-slate-50 text-slate-700 uppercase tracking-wider font-bold border-b border-slate-200">
                            <tr>
                                <th rowspan="2" class="px-4 py-3 text-center border-r border-slate-200 w-12">No</th>
                                <th rowspan="2" class="px-4 py-3 text-left border-r border-slate-200">Bukti Portofolio Asesi</th>
                                <th colspan="2" class="px-2 py-1.5 text-center border-r border-slate-200 bg-teal-50/60 text-teal-900">Valid (V)</th>
                                <th colspan="2" class="px-2 py-1.5 text-center border-r border-slate-200 bg-sky-50/60 text-sky-900">Asli (A)</th>
                                <th colspan="2" class="px-2 py-1.5 text-center border-r border-slate-200 bg-amber-50/60 text-amber-900">Terkini (T)</th>
                                <th colspan="2" class="px-2 py-1.5 text-center bg-indigo-50/60 text-indigo-900">Memadai (M)</th>
                            </tr>
                            <tr class="text-[10px] border-b border-slate-200">
                                <th class="px-2 py-1 text-center border-r border-slate-200 bg-teal-50/30 text-emerald-700 w-12">Ya</th>
                                <th class="px-2 py-1 text-center border-r border-slate-200 bg-teal-50/30 text-rose-700 w-12">Tidak</th>
                                <th class="px-2 py-1 text-center border-r border-slate-200 bg-sky-50/30 text-emerald-700 w-12">Ya</th>
                                <th class="px-2 py-1 text-center border-r border-slate-200 bg-sky-50/30 text-rose-700 w-12">Tidak</th>
                                <th class="px-2 py-1 text-center border-r border-slate-200 bg-amber-50/30 text-emerald-700 w-12">Ya</th>
                                <th class="px-2 py-1 text-center border-r border-slate-200 bg-amber-50/30 text-rose-700 w-12">Tidak</th>
                                <th class="px-2 py-1 text-center border-r border-slate-200 bg-indigo-50/30 text-emerald-700 w-12">Ya</th>
                                <th class="px-2 py-1 text-center bg-indigo-50/30 text-rose-700 w-12">Tidak</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($portfolios as $idx => $p)
                                @php
                                    $pId = $p['id'] ?? $idx;
                                    $vVal = $vatmData[$pId]['v'] ?? ($vatmData[$idx]['v'] ?? 'YA');
                                    $aVal = $vatmData[$pId]['a'] ?? ($vatmData[$idx]['a'] ?? 'YA');
                                    $tVal = $vatmData[$pId]['t'] ?? ($vatmData[$idx]['t'] ?? 'YA');
                                    $mVal = $vatmData[$pId]['m'] ?? ($vatmData[$idx]['m'] ?? 'YA');
                                    $viewUrl = route('secure-file.evidence', ['response' => $response?->id ?? 0, 'portfolio_index' => $idx]);
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-4 py-3 text-center font-bold text-slate-500 font-mono border-r border-slate-200 align-top">
                                        {{ $idx + 1 }}
                                    </td>
                                    <td class="px-4 py-3 border-r border-slate-200 align-top">
                                        <div class="space-y-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                {{ $p['type'] ?? 'Portofolio' }}
                                            </span>
                                            <div class="font-bold text-slate-900 text-xs">
                                                {{ $p['title'] ?? ($p['original_name'] ?? 'Berkas Portofolio') }}
                                            </div>
                                            <div class="text-[11px] text-slate-500 flex items-center gap-2 pt-0.5">
                                                <span>{{ $p['original_name'] ?? '-' }}</span>
                                                @if(!empty($response))
                                                    <a href="{{ $viewUrl }}" target="_blank" class="inline-flex items-center gap-1 font-bold text-brand-600 hover:text-brand-800 hover:underline">
                                                        <span>Buka Berkas</span>
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    {{-- Valid --}}
                                    <td class="px-2 py-2 text-center border-r border-slate-200 align-middle">
                                        <input type="radio" name="vatm[{{ $pId }}][v]" value="YA" :disabled="!isEditing" class="vatm-radio-ya text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer" {{ $vVal === 'YA' ? 'checked' : '' }}>
                                    </td>
                                    <td class="px-2 py-2 text-center border-r border-slate-200 align-middle">
                                        <input type="radio" name="vatm[{{ $pId }}][v]" value="TIDAK" :disabled="!isEditing" class="vatm-radio-tidak text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer" {{ $vVal === 'TIDAK' ? 'checked' : '' }}>
                                    </td>
                                    {{-- Asli --}}
                                    <td class="px-2 py-2 text-center border-r border-slate-200 align-middle">
                                        <input type="radio" name="vatm[{{ $pId }}][a]" value="YA" :disabled="!isEditing" class="vatm-radio-ya text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer" {{ $aVal === 'YA' ? 'checked' : '' }}>
                                    </td>
                                    <td class="px-2 py-2 text-center border-r border-slate-200 align-middle">
                                        <input type="radio" name="vatm[{{ $pId }}][a]" value="TIDAK" :disabled="!isEditing" class="vatm-radio-tidak text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer" {{ $aVal === 'TIDAK' ? 'checked' : '' }}>
                                    </td>
                                    {{-- Terkini --}}
                                    <td class="px-2 py-2 text-center border-r border-slate-200 align-middle">
                                        <input type="radio" name="vatm[{{ $pId }}][t]" value="YA" :disabled="!isEditing" class="vatm-radio-ya text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer" {{ $tVal === 'YA' ? 'checked' : '' }}>
                                    </td>
                                    <td class="px-2 py-2 text-center border-r border-slate-200 align-middle">
                                        <input type="radio" name="vatm[{{ $pId }}][t]" value="TIDAK" :disabled="!isEditing" class="vatm-radio-tidak text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer" {{ $tVal === 'TIDAK' ? 'checked' : '' }}>
                                    </td>
                                    {{-- Memadai --}}
                                    <td class="px-2 py-2 text-center border-r border-slate-200 align-middle">
                                        <input type="radio" name="vatm[{{ $pId }}][m]" value="YA" :disabled="!isEditing" class="vatm-radio-ya text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer" {{ $mVal === 'YA' ? 'checked' : '' }}>
                                    </td>
                                    <td class="px-2 py-2 text-center align-middle">
                                        <input type="radio" name="vatm[{{ $pId }}][m]" value="TIDAK" :disabled="!isEditing" class="vatm-radio-tidak text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer" {{ $mVal === 'TIDAK' ? 'checked' : '' }}>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Section 2: Klarifikasi Wawancara (Sesuai MUK) --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h2 class="text-base font-bold text-slate-900">2. Tindak Lanjut Hasil Verifikasi Bukti</h2>
                <p class="text-xs text-slate-500">
                    Sebagai tindak lanjut dari hasil verifikasi bukti, substansi materi di bawah ini (nomor elemen yang dicentang) <strong>harus diklarifikasi selama wawancara (FR.IA.09)</strong>:
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 text-slate-700 uppercase tracking-wider font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-center border-r border-slate-200 w-16">Klarifikasi Wawancara</th>
                            <th class="px-4 py-3 text-left border-r border-slate-200 w-48">No. Unit Kompetensi</th>
                            <th class="px-4 py-3 text-left border-r border-slate-200 w-36">No. Elemen</th>
                            <th class="px-4 py-3 text-left">Materi / Substansi Wawancara / KUK</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($units ?? [] as $uIdx => $unit)
                            @foreach($unit->elements as $eIdx => $elem)
                                @php
                                    $elemKey = (string) $elem->id;
                                    $isClarified = in_array($elemKey, (array) old('clarification_elements', $clarificationElements));
                                @endphp
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="px-4 py-3 text-center border-r border-slate-200 align-top">
                                        <input type="checkbox" name="clarification_elements[]" value="{{ $elem->id }}" :disabled="!isEditing"
                                            class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4 cursor-pointer"
                                            {{ $isClarified ? 'checked' : '' }}>
                                    </td>
                                    <td class="px-4 py-3 border-r border-slate-200 align-top">
                                        <div class="font-mono font-bold text-slate-900">{{ $unit->code }}</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">{{ $unit->title }}</div>
                                    </td>
                                    <td class="px-4 py-3 border-r border-slate-200 align-top font-semibold text-slate-800">
                                        Elemen {{ $eIdx + 1 }}
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="font-bold text-slate-900 mb-1">{{ $elem->name }}</div>
                                        @if($elem->kuks->isNotEmpty())
                                            <div class="text-[11px] text-slate-600 space-y-0.5 border-l-2 border-slate-200 pl-2 mt-1">
                                                @foreach($elem->kuks as $kuk)
                                                    <div><span class="font-mono font-semibold">{{ $kuk->order }}</span> {{ $kuk->criteria }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-slate-500 text-xs">Belum ada unit kompetensi pada skema ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Section 3: Bukti Tambahan yang Diperlukan --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-3">
            <h2 class="text-base font-bold text-slate-900">3. Bukti Tambahan yang Diperlukan</h2>
            <p class="text-xs text-slate-500">Bukti tambahan diperlukan pada unit / elemen kompetensi sebagai berikut (bila ada):</p>
            <textarea name="additional_evidence" rows="3" :disabled="!isEditing" placeholder="Tuliskan catatan bukti tambahan yang diperlukan..."
                class="w-full text-xs rounded-lg border border-slate-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 px-3.5 py-2.5 bg-white text-slate-900">{{ old('additional_evidence', $savedAdditionalEvidence) }}</textarea>
        </div>

        {{-- Section 4: Rekomendasi Asesor & Remediasi --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900">4. Rekomendasi Asesor</h2>
            <div class="space-y-3">
                <label class="flex items-start space-x-3 p-3.5 rounded-xl border-2 cursor-pointer transition"
                    :class="recommendation === 'K' ? 'border-emerald-500 bg-emerald-50/50' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="recommendation" value="K" x-model="recommendation" :disabled="!isEditing" class="text-emerald-600 focus:ring-emerald-500 mt-0.5">
                    <div class="text-xs">
                        <div class="font-bold text-emerald-950">KOMPETEN (K)</div>
                        <p class="text-slate-600 mt-0.5">Asesi telah memenuhi pencapaian seluruh kriteria unjuk kerja, direkomendasikan <strong>KOMPETEN</strong>.</p>
                    </div>
                </label>

                <label class="flex items-start space-x-3 p-3.5 rounded-xl border-2 cursor-pointer transition"
                    :class="recommendation === 'BK' ? 'border-rose-500 bg-rose-50/50' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="recommendation" value="BK" x-model="recommendation" :disabled="!isEditing" class="text-rose-600 focus:ring-rose-500 mt-0.5">
                    <div class="text-xs">
                        <div class="font-bold text-rose-950">BELUM KOMPETEN (BK)</div>
                        <p class="text-slate-600 mt-0.5">Asesi belum memenuhi pencapaian seluruh kriteria unjuk kerja, direkomendasikan <strong>OBSERVASI LANGSUNG / KEGIATAN TERSTRUKTUR</strong>.</p>
                    </div>
                </label>
            </div>

            {{-- Remediation Section (Shown if BK) --}}
            <div x-show="recommendation === 'BK'" x-transition class="p-4 bg-rose-50/60 rounded-xl border border-rose-200 space-y-3 text-xs">
                <div class="font-bold text-rose-900 uppercase tracking-wider text-[11px]">Rekomendasi Remediasi / Uji Lanjut:</div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kelompok Pekerjaan Unit:</label>
                        <input type="text" name="remediation_unit" value="{{ old('remediation_unit', $remediation['unit']) }}" :disabled="!isEditing" placeholder="Contoh: Unit 009 Melaksanakan Diagnosis..." class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Elemen:</label>
                        <input type="text" name="remediation_element" value="{{ old('remediation_element', $remediation['element']) }}" :disabled="!isEditing" placeholder="Contoh: Elemen 2 Menggunakan komponen..." class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Kriteria Unjuk Kerja (KUK):</label>
                        <input type="text" name="remediation_kuk" value="{{ old('remediation_kuk', $remediation['kuk']) }}" :disabled="!isEditing" placeholder="Contoh: KUK 2.1, 2.2..." class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan Asesor (Opsional)</label>
                <textarea name="assessor_notes" rows="2" :disabled="!isEditing" placeholder="Catatan evaluasi tambahan..." class="w-full text-xs rounded-lg border border-slate-300 px-3 py-2 bg-white">{{ old('assessor_notes', $savedNotes) }}</textarea>
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
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia08']) }}" class="px-5 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">
                Kembali
            </a>
            <button type="submit" class="px-6 py-2.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-md transition flex items-center gap-2">
                <span>Simpan Verifikasi Portofolio (FR.IA.08)</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </button>
        </div>
    </form>
</div>
@endsection
