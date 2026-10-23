@extends('layouts.app', ['title' => 'FR.AK.01 - Persetujuan Asesmen dan Kerahasiaan'])

@section('content')
@php
    $jadwal = $schedule;
    $assessments = $assessments ?? $jadwal->assessments ?? collect();
    $selectedAssessmentId = $selectedAssessmentId ?? request('assessment');
    $savedEvidence = $savedEvidence ?? ['Hasil Observasi Langsung', 'Hasil Pertanyaan Tertulis'];
    $savedOtherEvidence = $savedOtherEvidence ?? '';
    $savedTukType = $savedTukType ?? 'Sewaktu';
@endphp

<div class="space-y-6 max-w-5xl mx-auto pb-12">
    {{-- Header & Top Action Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $jadwal->id, 'code' => 'apl']) }}" class="text-xs text-brand-600 hover:text-brand-700 font-bold inline-flex items-center gap-1">
                <span>&larr;</span> Kembali ke Daftar Verifikasi APL
            </a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">FR.AK.01. PERSETUJUAN ASESMEN DAN KERAHASIAAN</h1>
            <p class="text-xs text-slate-500 font-medium">Lembaga Sertifikasi Profesi Praktisi Otomotif (LSP-PrO Indo)</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('asesor.schedule.ak07', $jadwal->id) }}" class="inline-flex items-center px-4 py-2 text-xs font-bold text-slate-700 bg-white border-2 border-black rounded-lg shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] hover:bg-slate-50 transition">
                Lewati & Langsung ke FR.AK.07 &rarr;
            </a>
        </div>
    </div>

    {{-- Info Box (Pengisian Opsional) --}}
    <div class="bg-blue-50 border-2 border-blue-400 rounded-xl p-4 text-xs text-blue-900 shadow-sm flex items-start gap-3">
        <span class="text-base text-blue-600 font-black">ℹ</span>
        <div>
            <span class="font-bold">Formulir Bersifat Opsional:</span> Jika Anda tidak mengubah apa pun pada pilihan bukti yang dikumpulkan, nilai default yang disimpan otomatis adalah <strong>"Hasil Observasi Langsung"</strong> dan <strong>"Hasil Pertanyaan Tertulis"</strong>. Anda dapat langsung mengklik tombol <strong>"Simpan FR.AK.01 & Lanjut ke FR.AK.07"</strong> atau <strong>"Lewati & Langsung ke FR.AK.07"</strong>.
        </div>
    </div>

    <form action="{{ route('asesor.schedule.ak01.store', $jadwal->id) }}" method="POST" class="space-y-6">
        @csrf

        {{-- BNSP Document Container (Bordered Style) --}}
        <div class="bg-white border-2 border-black shadow-[3px_3px_0_0_rgba(0,0,0,0.9)] overflow-hidden text-slate-900 font-sans">
            
            {{-- Top Header Box --}}
            <div class="grid grid-cols-1 md:grid-cols-12 border-b-2 border-black">
                <div class="md:col-span-3 p-4 flex items-center justify-center border-b md:border-b-0 md:border-r-2 border-black bg-white">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 border-2 border-rose-600 rounded flex flex-col items-center justify-center p-1 relative">
                            <span class="text-[10px] font-black text-blue-800 tracking-tighter leading-none">✓ LSP</span>
                            <div class="bg-rose-600 text-white text-[6px] font-bold px-0.5 mt-0.5 rounded-xs leading-tight text-center">
                                PRO INDO
                            </div>
                        </div>
                        <div>
                            <span class="text-xs font-black tracking-wider text-rose-700 block">LSP-PrO Indo</span>
                            <span class="text-[9px] text-slate-500 uppercase tracking-widest block font-bold">Praktisi Otomotif</span>
                        </div>
                    </div>
                </div>
                <div class="md:col-span-9 p-4 flex items-center justify-center text-center bg-slate-50">
                    <span class="text-sm md:text-base font-bold uppercase tracking-tight text-slate-900">
                        Lembaga Sertifikasi Profesi Praktisi Otomotif LSP-PrO Indo
                    </span>
                </div>
            </div>

            {{-- Document Title Bar --}}
            <div class="p-3 bg-white border-b-2 border-black">
                <h2 class="text-sm md:text-base font-black text-slate-900 uppercase">
                    FR.AK.01. PERSETUJUAN ASESMEN DAN KERAHASIAAN
                </h2>
            </div>

            {{-- Row 1: Statement --}}
            <div class="p-3 bg-slate-50 text-xs border-b-2 border-black leading-relaxed">
                Persetujuan Asesmen ini untuk menjamin bahwa Asesi telah diberi arahan secara rinci tentang perencanaan dan proses asesmen
            </div>

            {{-- Row 2: Skema Sertifikasi --}}
            <div class="grid grid-cols-1 md:grid-cols-12 border-b border-black text-xs">
                <div class="md:col-span-4 p-3 font-semibold bg-white border-b md:border-b-0 md:border-r border-black flex items-center">
                    Skema Sertifikasi (KKNI/Okupasi/Klaster)
                </div>
                <div class="md:col-span-8 p-3 space-y-1.5 bg-white">
                    <div class="flex items-start">
                        <span class="w-16 font-semibold shrink-0">Judul</span>
                        <span class="mr-2">:</span>
                        <span class="font-bold italic text-slate-900">{{ $jadwal->scheme->name ?? '-' }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-16 font-semibold shrink-0">Nomor</span>
                        <span class="mr-2">:</span>
                        <span class="font-mono font-bold text-slate-800">{{ $jadwal->scheme->code ?? '-' }}</span>
                    </div>
                </div>
            </div>

            {{-- Row 3: TUK --}}
            <div class="grid grid-cols-1 md:grid-cols-12 border-b border-black text-xs">
                <div class="md:col-span-4 p-3 font-semibold bg-white border-b md:border-b-0 md:border-r border-black flex items-center">
                    TUK
                </div>
                <div class="md:col-span-8 p-3 flex flex-wrap items-center gap-4 bg-white">
                    <span class="mr-1">:</span>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="tuk_type" value="Sewaktu" {{ $savedTukType === 'Sewaktu' ? 'checked' : '' }} class="text-brand-600 focus:ring-brand-500">
                        <span>Sewaktu</span>
                    </label>
                    <span class="text-slate-400">/</span>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="tuk_type" value="Tempat Kerja" {{ $savedTukType === 'Tempat Kerja' ? 'checked' : '' }} class="text-brand-600 focus:ring-brand-500">
                        <span>Tempat Kerja</span>
                    </label>
                    <span class="text-slate-400">/</span>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="tuk_type" value="Mandiri" {{ $savedTukType === 'Mandiri' ? 'checked' : '' }} class="text-brand-600 focus:ring-brand-500">
                        <span>Mandiri*</span>
                    </label>
                    <span class="text-slate-500 font-mono text-[11px] ml-2">({{ $jadwal->tuk_name }})</span>
                </div>
            </div>

            {{-- Row 4: Nama Asesor --}}
            <div class="grid grid-cols-1 md:grid-cols-12 border-b border-black text-xs">
                <div class="md:col-span-4 p-3 font-semibold bg-white border-b md:border-b-0 md:border-r border-black flex items-center">
                    Nama Asesor
                </div>
                <div class="md:col-span-8 p-3 flex items-center bg-white font-bold text-slate-900">
                    <span class="mr-2 font-normal">:</span>
                    <span>{{ auth()->user()->full_name }}</span>
                    @if(auth()->user()->profile?->no_registrasi)
                        <span class="ml-2 font-mono text-[11px] font-normal text-slate-600">({{ auth()->user()->profile->no_registrasi }})</span>
                    @endif
                </div>
            </div>

            {{-- Row 5: Nama Asesi (Batch Selection / Asesi yang Diberikan Penyesuaian) --}}
            <div class="grid grid-cols-1 md:grid-cols-12 border-b border-black text-xs">
                <div class="md:col-span-4 p-3 font-semibold bg-white border-b md:border-b-0 md:border-r border-black">
                    <span class="block">Nama Asesi</span>
                    <span class="text-[10px] text-slate-500 block font-normal mt-0.5">Asesi yang Diberikan Penyesuaian (Pilih Asesi):</span>
                </div>
                <div class="md:col-span-8 p-3 bg-white space-y-2">
                    <div class="flex items-center justify-between pb-1.5 border-b border-slate-200">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-800">
                            <input type="checkbox" id="check-all-asesi" checked class="rounded border-slate-400 text-brand-600 focus:ring-brand-500 w-4 h-4">
                            <span>Pilih Semua Asesi Terdaftar ({{ $assessments->count() }} Orang)</span>
                        </label>
                        <span class="text-[10px] text-slate-500 italic">* Centang asesi yang akan disetujui persetujuannya</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-56 overflow-y-auto p-1">
                        @foreach($assessments as $assessment)
                            <label class="flex items-start space-x-2 p-2 rounded border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                                <input type="checkbox" name="assessment_ids[]" value="{{ $assessment->id }}"
                                    class="asesi-item-checkbox rounded border-slate-400 text-brand-600 focus:ring-brand-500 w-4 h-4 mt-0.5 shrink-0"
                                    {{ (empty(old('assessment_ids')) || in_array($assessment->id, old('assessment_ids', [])) || (string)$selectedAssessmentId === (string)$assessment->id) ? 'checked' : '' }}>
                                <div class="truncate text-xs">
                                    <div class="font-bold text-slate-900 truncate">{{ $assessment->asesi->full_name }}</div>
                                    <div class="text-[10px] text-slate-500 truncate">
                                        {{ $assessment->asesi->email }}
                                        @if(!empty($assessment->asesi->profile?->nik))
                                            &bull; NIK: {{ $assessment->asesi->profile->nik }}
                                        @endif
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Row 6: Bukti yang akan dikumpulkan --}}
            <div class="grid grid-cols-1 md:grid-cols-12 border-b border-black text-xs">
                <div class="md:col-span-4 p-3 font-semibold bg-white border-b md:border-b-0 md:border-r border-black flex items-start">
                    <div>
                        <span class="font-bold block text-slate-900">Bukti yang akan dikumpulkan :</span>
                        <p class="text-[10px] text-slate-500 font-normal mt-1 leading-relaxed">
                            Pilihlah instrumen bukti uji kompetensi yang akan digunakan. Nilai default adalah Observasi Langsung & Pertanyaan Tertulis.
                        </p>
                    </div>
                </div>
                <div class="md:col-span-8 p-3 bg-white">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2.5">
                        {{-- Column 1 --}}
                        <div class="space-y-2.5">
                            <label class="flex items-center space-x-2 cursor-pointer hover:text-brand-700">
                                <input type="checkbox" name="evidence[]" value="Hasil Verifikasi Portofolio"
                                    class="rounded border-slate-400 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                    {{ in_array('Hasil Verifikasi Portofolio', $savedEvidence) ? 'checked' : '' }}>
                                <span class="font-medium">Hasil Verifikasi Portofolio</span>
                            </label>

                            <label class="flex items-center space-x-2 cursor-pointer hover:text-brand-700">
                                <input type="checkbox" name="evidence[]" value="Hasil Observasi Langsung"
                                    class="rounded border-slate-400 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                    {{ in_array('Hasil Observasi Langsung', $savedEvidence) ? 'checked' : '' }}>
                                <span class="font-bold text-slate-900">Hasil Observasi Langsung</span>
                                <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded border border-emerald-200">Default</span>
                            </label>

                            <label class="flex items-center space-x-2 cursor-pointer hover:text-brand-700">
                                <input type="checkbox" name="evidence[]" value="Hasil Pertanyaan Lisan"
                                    class="rounded border-slate-400 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                    {{ in_array('Hasil Pertanyaan Lisan', $savedEvidence) ? 'checked' : '' }}>
                                <span class="font-medium">Hasil Pertanyaan Lisan</span>
                            </label>

                            <div class="pt-1">
                                <label class="flex items-center space-x-2 cursor-pointer hover:text-brand-700">
                                    <input type="checkbox" name="evidence[]" value="Lainnya"
                                        id="checkbox-lainnya"
                                        class="rounded border-slate-400 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                        {{ (in_array('Lainnya', $savedEvidence) || !empty($savedOtherEvidence)) ? 'checked' : '' }}>
                                    <span class="font-medium">Lainnya :</span>
                                </label>
                                <input type="text" name="other_evidence" value="{{ old('other_evidence', $savedOtherEvidence) }}"
                                    placeholder="Tuliskan bukti lainnya bila ada..."
                                    class="mt-1.5 w-full text-xs rounded border-slate-300 focus:border-brand-500 focus:ring-brand-500 px-2.5 py-1">
                            </div>
                        </div>

                        {{-- Column 2 --}}
                        <div class="space-y-2.5">
                            <label class="flex items-center space-x-2 cursor-pointer hover:text-brand-700">
                                <input type="checkbox" name="evidence[]" value="Hasil Reviu Produk"
                                    class="rounded border-slate-400 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                    {{ in_array('Hasil Reviu Produk', $savedEvidence) ? 'checked' : '' }}>
                                <span class="font-medium">Hasil Reviu Produk</span>
                            </label>

                            <label class="flex items-center space-x-2 cursor-pointer hover:text-brand-700">
                                <input type="checkbox" name="evidence[]" value="Hasil Kegiatan Terstruktur"
                                    class="rounded border-slate-400 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                    {{ in_array('Hasil Kegiatan Terstruktur', $savedEvidence) ? 'checked' : '' }}>
                                <span class="font-medium">Hasil Kegiatan Terstruktur</span>
                            </label>

                            <label class="flex items-center space-x-2 cursor-pointer hover:text-brand-700">
                                <input type="checkbox" name="evidence[]" value="Hasil Pertanyaan Tertulis"
                                    class="rounded border-slate-400 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                    {{ in_array('Hasil Pertanyaan Tertulis', $savedEvidence) ? 'checked' : '' }}>
                                <span class="font-bold text-slate-900">Hasil Pertanyaan Tertulis</span>
                                <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded border border-emerald-200">Default</span>
                            </label>

                            <label class="flex items-center space-x-2 cursor-pointer hover:text-brand-700">
                                <input type="checkbox" name="evidence[]" value="Hasil Pertanyaan Wawancara"
                                    class="rounded border-slate-400 text-brand-600 focus:ring-brand-500 w-4 h-4"
                                    {{ in_array('Hasil Pertanyaan Wawancara', $savedEvidence) ? 'checked' : '' }}>
                                <span class="font-medium">Hasil Pertanyaan Wawancara</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Row 7: Pelaksanaan Asesmen Disepakati Pada --}}
            <div class="grid grid-cols-1 md:grid-cols-12 border-b border-black text-xs">
                <div class="md:col-span-4 p-3 font-semibold bg-white border-b md:border-b-0 md:border-r border-black flex items-center">
                    Pelaksanaan asesmen disepakati pada:
                </div>
                <div class="md:col-span-8 p-3 space-y-1.5 bg-white">
                    <div class="flex items-center">
                        <span class="w-28 font-semibold shrink-0">Hari/ Tanggal</span>
                        <span class="mr-2">:</span>
                        <span class="font-bold text-slate-900">{{ \Carbon\Carbon::parse($jadwal->date)->isoFormat('dddd, D MMMM Y') }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-28 font-semibold shrink-0">Waktu</span>
                        <span class="mr-2">:</span>
                        <span class="font-bold text-slate-900">{{ substr($jadwal->start_time, 0, 5) }} - {{ substr($jadwal->end_time, 0, 5) }} WIB</span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-28 font-semibold shrink-0">TUK</span>
                        <span class="mr-2">:</span>
                        <span class="font-bold text-slate-900">{{ $jadwal->tuk_name }}</span>
                    </div>
                </div>
            </div>

            {{-- Row 8: Pernyataan Asesi (Hak & Banding) --}}
            <div class="p-3 border-b border-black text-xs bg-white">
                <span class="font-bold block text-slate-900 mb-0.5">Asesi :</span>
                <p class="leading-relaxed text-slate-700">
                    Bahwa saya telah mendapatkan penjelasan terkait hak dan prosedur banding asesmen dari asesor.
                </p>
            </div>

            {{-- Row 9: Pernyataan Asesor (Kerahasiaan) --}}
            <div class="p-3 border-b border-black text-xs bg-white">
                <span class="font-bold block text-slate-900 mb-0.5">Asesor :</span>
                <p class="leading-relaxed text-slate-700">
                    Menyatakan tidak akan membuka hasil pekerjaan yang saya peroleh karena penugasan saya sebagai Asesor dalam pekerjaan Asesmen kepada siapapun atau organisasi apapun selain kepada pihak yang berwenang sehubungan dengan kewajiban saya sebagai Asesor yang ditugaskan oleh LSP.
                </p>
            </div>

            {{-- Row 10: Persetujuan Asesi (Pengembangan Profesi) --}}
            <div class="p-3 border-b-2 border-black text-xs bg-white">
                <span class="font-bold block text-slate-900 mb-0.5">Asesi :</span>
                <p class="leading-relaxed text-slate-700">
                    Saya setuju mengikuti asesmen dengan pemahaman bahwa informasi yang dikumpulkan hanya digunakan untuk pengembangan profesional dan hanya dapat diakses oleh orang tertentu saja.
                </p>
            </div>

            {{-- Row 11: Signatures Section --}}
            <div class="grid grid-cols-1 md:grid-cols-2 text-xs">
                {{-- Left: Asesor Signature --}}
                <div class="p-4 border-b md:border-b-0 md:border-r-2 border-black flex flex-col justify-between space-y-3 bg-white">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-900">Tanda tangan Asesor :</span>
                            <span class="text-slate-600">Tanggal : <strong>{{ \Carbon\Carbon::parse($jadwal->date)->isoFormat('D MMMM Y') }}</strong></span>
                        </div>

                        <div class="mt-2 p-2 border border-slate-200 rounded-lg bg-slate-50 flex items-center justify-center min-h-[90px]">
                            @if(auth()->user()->profile?->signature_data)
                                <img src="{{ auth()->user()->profile->signature_data }}" alt="TTD Asesor" class="max-h-20 object-contain">
                            @else
                                <div class="text-center py-2 text-amber-700">
                                    <p class="text-[11px] font-semibold">Tanda tangan profil belum tersimpan.</p>
                                    <p class="text-[10px] text-slate-500">Anda dapat menyimpan master TTD pada menu Biodata & TTD Asesor.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="text-center font-bold text-slate-900 pt-1">
                        ( {{ auth()->user()->full_name }} )
                    </div>
                </div>

                {{-- Right: Asesi Signature --}}
                <div class="p-4 flex flex-col justify-between space-y-3 bg-white">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-900">Tanda tangan Asesi :</span>
                            <span class="text-slate-600">Tanggal : <strong>{{ \Carbon\Carbon::parse($jadwal->date)->isoFormat('D MMMM Y') }}</strong></span>
                        </div>

                        <div class="mt-2 p-2 border border-slate-200 rounded-lg bg-slate-50 flex flex-col items-center justify-center min-h-[90px] text-center text-slate-500">
                            <svg class="w-6 h-6 text-slate-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                            <span class="text-[11px] font-semibold text-slate-700">Ditandatangani Secara Mandiri oleh Asesi</span>
                            <span class="text-[10px] text-slate-500">Melalui portal akun asesi masing-masing sebelum masuk ke materi uji</span>
                        </div>
                    </div>

                    <div class="text-center font-bold text-slate-900 pt-1">
                        ( Seluruh Asesi Terpilih )
                    </div>
                </div>
            </div>

            {{-- Footer Note --}}
            <div class="p-2.5 bg-slate-100 border-t-2 border-black text-[11px] text-slate-600 italic">
                * Coret yang tidak perlu
            </div>
        </div>

        {{-- Bottom Action Buttons --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $jadwal->id, 'code' => 'apl']) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-800">
                &larr; Kembali ke Verifikasi APL
            </a>

            <div class="flex items-center space-x-3 w-full sm:w-auto">
                <a href="{{ route('asesor.schedule.ak07', $jadwal->id) }}" class="w-full sm:w-auto text-center px-5 py-3 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition shadow-sm">
                    Lewati & Langsung ke FR.AK.07 &rarr;
                </a>

                <button type="submit" class="w-full sm:w-auto px-7 py-3 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm transition flex items-center justify-center space-x-2">
                    <span>Simpan FR.AK.01 & Lanjut ke FR.AK.07</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkAll = document.getElementById('check-all-asesi');
        const checkboxes = document.querySelectorAll('.asesi-item-checkbox');

        if (checkAll) {
            checkAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = checkAll.checked);
            });

            checkboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    const allChecked = Array.from(checkboxes).every(c => c.checked);
                    checkAll.checked = allChecked;
                });
            });
        }
    });
</script>
@endsection
