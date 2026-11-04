@php
    $jadwal = $jadwal ?? $schedule;
    $assessments = $assessments ?? $jadwal->assessments ?? collect();
    $selectedAssessmentId = $selectedAssessmentId ?? request('assessment');

    $itemsDef = [
        1 => [
            'title' => 'Keterbatasan asesi terhadap persyaratan bahasa, literasi, numerasi.',
            'options' => [
                'Memerlukan dukungan pembaca, penerjemah, pelayan, penulis untuk merekam jawaban asesi.',
                'Melakukan asesmen verbal (gunakan pertanyaan lisan/pertanyaan wawancara) dengan dilengkapi gambar diagram dan bentuk-bentuk visual.',
                'Menggunakan Hasil produksi.',
                'Menggunakan Ceklis observasi/demonstrasi.',
                'Menggunakan daftar instruksi terstruktur.',
            ],
        ],
        2 => [
            'title' => 'Penyediaan dukungan pembaca, penerjemah, pelayan, penulis.',
            'options' => [
                'Menggunakan pertanyaan lisan dengan dilengkapi gambar diagram dan bentuk-bentuk visual.',
                'Menggunakan pertanyaan wawancara dengan dilengkapi gambar diagram dan bentuk-bentuk visual.',
            ],
        ],
        3 => [
            'title' => 'Penggunaan teknologi adaptif atau peralatan khusus. (Tidak dapat menggunakan teknologi adaptif, misal: mengoperasikan komputer dan printer, peralatan digital dsb).',
            'options' => [
                'Ceklis observasi/demonstrasi',
                'Demonstrasi',
                'Pertanyaan lisan',
                'Pertanyaan tertulis',
                'Pertanyaan wawancara',
                'Daftar instruksi terstruktur',
                'Ceklis verifikasi portofolio',
                'Menggunakan dukungan operator komputer',
            ],
        ],
        4 => [
            'title' => 'Pelaksanaan asesmen secara fleksibel karena alasan keletihan atau keperluan pengobatan.',
            'options' => [
                'Menggunakan juru tulis.',
                'Menggunakan kameramen perekam video/audio.',
                'Memperbolehkan periode waktu yang lebih panjang untuk menyelesaikan tugas pekerjaan dalam asesmen.',
                'Melakukan tugas pekerjaan dalam asesmen dengan waktu lebih pendek.',
                'Menggunakan instruksi-instruksi spesifik pada proyek yang dapat dilakukan pada berbagai tingkatan.',
            ],
        ],
        5 => [
            'title' => 'Penyediaan peralatan asesmen berupa braille, audio/video-tape.',
            'options' => [
                'Menggunakan pertanyaan lisan.',
                'Menggunakan pertanyaan wawancara.',
            ],
        ],
        6 => [
            'title' => 'Penyesuaian tempat fisik/lingkungan asesmen.',
            'options' => [
                'Pertanyaan lisan.',
                'Pertanyaan tulis.',
                'Pertanyaan wawancara.',
                'Ceklis Verifikasi portofolio.',
                'Ceklis reviu produk.',
                'Daftar instruksi terstruktur.',
            ],
        ],
        7 => [
            'title' => 'Pertimbangan umur/usia lanjut/gender asesi. (Adanya perbedaan usia dengan asesor yang lebih muda).',
            'options' => [
                'Menggunakan studi kasus / daftar instruksi terstruktur.',
                'Menggunakan instrumen asesmen dengan huruf normal jangan terlalu kecil.',
                'Menggunakan asesor dengan jenis kelamin yang sama dengan asesi.',
                'Menggunakan instrumen asesmen yang sama walaupun berbeda jenis kelamin (tidak membedakan jenis kelamin).',
            ],
        ],
        8 => [
            'title' => 'Pertimbangan budaya/tradisi/agama.',
            'options' => [
                'Menggunakan studi kasus daftar instruksi terstruktur.',
                'Menggunakan asesor tanpa pertimbangan budaya/tradisi/agama.',
                'Menggunakan instrumen asesmen yang sama walaupun berbeda budaya/tradisi/agama.',
            ],
        ],
    ];
@endphp
@extends('layouts.app', ['title' => 'FR.AK.07 - Penyesuaian yang Wajar dan Beralasan'])

@section('content')
<div class="space-y-6 max-w-7xl mx-auto" 
     x-data="{
        modalOpen: false,
        selectedAsesi: null,
        openModal(data) {
            this.selectedAsesi = data;
            this.modalOpen = true;
        },
        closeModal() {
            this.modalOpen = false;
            this.selectedAsesi = null;
        }
     }">
    {{-- Unified Top Lifecycle Navigation --}}
    @include('asesor.partials.schedule-nav', ['schedule' => $jadwal, 'active' => 'ak07'])

    @if($jadwal->is_closed)
        <div class="p-3.5 bg-emerald-50 border border-emerald-300 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-emerald-900">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span><strong>Jadwal Telah Resmi Ditutup & Dikunci:</strong> Dokumen penyesuaian asesmen FR.AK.07 dalam mode lihat (read-only).</span>
            </div>
            <span class="font-bold font-mono text-[11px] bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded border border-emerald-300 shrink-0">STATUS: FINISHED</span>
        </div>
    @endif

    {{-- Header Navigation --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">FR.AK.07. CEKLIS PENYESUAIAN YANG WAJAR DAN BERALASAN</h1>
            <p class="text-xs text-slate-500 font-medium">Lembaga Sertifikasi Profesi Praktisi Otomotif (LSP-PrO Indo)</p>
        </div>
    </div>

    {{-- Info Box (Pengisian Opsional) --}}
    <div class="bg-blue-50 border-2 border-blue-400 rounded-xl p-4 text-xs text-blue-900 shadow-sm flex items-start gap-3">
        <span class="text-base text-blue-600 font-black">ℹ</span>
        <div>
            <span class="font-bold">Formulir Bersifat Opsional:</span> Pengisian FR.AK07 hanya dilakukan apabila terdapat asesi yang membutuhkan penyesuaian wajar berdasarkan keterbatasan karakteristik atau kondisi khusus. Apabila tidak ada penyesuaian khusus, formulir dapat dibiarkan kosong (default) dan Anda dapat langsung klik tombol <strong>"Simpan Penyesuaian FR.AK.07"</strong>.
        </div>
    </div>

    <form action="{{ route('asesor.ak07.store', $jadwal->id) }}" method="POST" class="space-y-6">
        @csrf

        {{-- Meta Table --}}
        <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] overflow-hidden">
            <div class="bg-slate-900 text-white px-6 py-3 border-b-2 border-black flex items-center justify-between">
                <span class="text-xs font-mono font-bold tracking-wider uppercase text-amber-300">Data Asesmen & Identifikasi Jadwal</span>
                <span class="text-xs text-slate-300">{{ $jadwal->code }}</span>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="border border-slate-200 rounded-lg p-3 bg-slate-50">
                        <span class="text-slate-500 font-semibold block mb-0.5">Skema Sertifikasi:</span>
                        <div class="font-bold text-slate-900 text-sm">{{ $jadwal->scheme->name ?? '-' }}</div>
                        <div class="font-mono text-slate-600 mt-0.5">Nomor: {{ $jadwal->scheme->code ?? '-' }}</div>
                    </div>
                    <div class="border border-slate-200 rounded-lg p-3 bg-slate-50">
                        <span class="text-slate-500 font-semibold block mb-0.5">Tempat Uji Kompetensi (TUK):</span>
                        <div class="font-bold text-slate-900 text-sm">{{ $jadwal->tuk_name ?? 'Sewaktu / Tempat Kerja / Mandiri' }}</div>
                        <div class="text-slate-600 mt-0.5">Tanggal: {{ \Carbon\Carbon::parse($jadwal->date)->isoFormat('D MMMM Y') }}</div>
                    </div>
                </div>

                {{-- Pilih Asesi Sasaran (Batch) --}}
                <div class="mt-4 pt-4 border-t border-slate-200">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                            Asesi yang Diberikan Penyesuaian (Pilih Asesi):
                        </label>
                        <span class="text-[11px] font-semibold text-slate-500">
                            {{ $assessments->count() }} Asesi Binaan Anda
                        </span>
                    </div>

                    @if($assessments->isEmpty())
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-xs">
                            Belum ada asesi yang ditugaskan kepada Anda pada jadwal ini.
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach($assessments as $assessment)
                                @php
                                    $ak07Items = $assessment->ak07Items ?? collect();
                                    $isAk07Approved = $ak07Items->isNotEmpty();
                                    if ($isAk07Approved) {
                                        $categories = $ak07Items->pluck('adjustment_category')->unique()->values()->all();
                                        $details = $ak07Items->pluck('adjustment_detail')->filter()->values()->all();
                                        $categorySummary = implode(', ', $categories);
                                        $detailSummary = implode('; ', $details);
                                    } else {
                                        $categorySummary = 'Belum Ditetapkan';
                                        $detailSummary = 'Data penyesuaian belum diisi/ditetapkan.';
                                    }
                                @endphp
                                <div class="p-3 rounded-xl border-2 transition shadow-2xs flex flex-col justify-between {{ $isAk07Approved ? 'bg-emerald-50/80 border-emerald-300 hover:border-emerald-400' : 'bg-amber-50/90 border-amber-300 hover:border-amber-400' }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <label class="flex items-start space-x-2.5 {{ $jadwal->is_closed ? 'cursor-default' : 'cursor-pointer' }} flex-1 min-w-0">
                                            <input type="checkbox" name="assessment_ids[]" value="{{ $assessment->id }}"
                                                class="rounded border-slate-400 text-brand-600 focus:ring-brand-500 w-4 h-4 mt-0.5 shrink-0"
                                                @disabled($jadwal->is_closed)
                                                {{ (empty(old('assessment_ids')) || in_array($assessment->id, old('assessment_ids', [])) || (string)$selectedAssessmentId === (string)$assessment->id) ? 'checked' : '' }}>
                                            <div class="truncate text-xs">
                                                <div class="font-bold text-slate-900 truncate flex items-center gap-1.5">
                                                    <span>{{ $assessment->asesi->full_name }}</span>
                                                </div>
                                                <div class="text-[11px] text-slate-500 truncate mt-0.5">
                                                    {{ $assessment->asesi->email }}
                                                    @if(!empty($assessment->asesi->profile?->nik))
                                                        &bull; <span class="font-mono">NIK: {{ $assessment->asesi->profile->nik }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </label>
                                        @if($isAk07Approved)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 shrink-0">
                                                ✓ Ditetapkan
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300 shrink-0">
                                                Belum Ditetapkan
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Trigger Modal Button --}}
                                    <div class="mt-2.5 pt-2 border-t {{ $isAk07Approved ? 'border-emerald-200/80' : 'border-amber-200/80' }} flex items-center justify-between">
                                        <span class="text-[10px] font-medium {{ $isAk07Approved ? 'text-emerald-700' : 'text-amber-800' }}">
                                            {{ $isAk07Approved ? 'Data FR.AK.07 tersimpan' : 'Data masih kosong' }}
                                        </span>
                                        <button type="button" 
                                                @click="openModal({
                                                    name: '{{ addslashes($assessment->asesi->full_name) }}',
                                                    email: '{{ addslashes($assessment->asesi->email) }}',
                                                    nik: '{{ addslashes($assessment->asesi->profile?->nik ?? '-') }}',
                                                    isApproved: {{ $isAk07Approved ? 'true' : 'false' }},
                                                    category: '{{ addslashes($categorySummary) }}',
                                                    detail: '{{ addslashes($detailSummary) }}',
                                                    tukName: '{{ addslashes($jadwal->tuk_name) }}',
                                                    scheduleDate: '{{ \Carbon\Carbon::parse($jadwal->date)->isoFormat('D MMMM Y') }}'
                                                })"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold rounded-lg transition shadow-2xs {{ $isAk07Approved ? 'text-emerald-900 bg-white hover:bg-emerald-100 border border-emerald-300' : 'text-amber-900 bg-white hover:bg-amber-100 border border-amber-300' }}"
                                                title="Buka rincian penyesuaian yang wajar asesi ini">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <span>Rincian Penyesuaian</span>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Panduan Bagi Asesor Box --}}
        <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] p-6 space-y-3">
            <h2 class="text-sm font-black text-slate-900 uppercase tracking-wide border-b border-slate-200 pb-2">
                PANDUAN BAGI ASESOR
            </h2>
            <ul class="text-xs text-slate-700 space-y-1.5 list-disc list-inside leading-relaxed">
                <li>Formulir ini dapat digunakan (sebelum pra asesmen, saat pelaksanaan pra asesmen, setelah pra asesmen) jika ada asesi yang mempunyai keterbatasan sesuai karakteristik yang dimilikinya sehingga diperlukan penyesuaian yang wajar dan beralasan atau jika rencana asesmen dan perangkat asesmen tidak sesuai dengan acuan pembanding, potensi asesi dan konteks asesi atau jika asesi merasa keletihan, sakit, serta jika kondisi alam, listrik padam, dll.</li>
                <li>Berilah tanda centang pada kolom potensi asesi jika relevan.</li>
                <li>Jika diperlukan penyesuaian, pilih <strong>Ya</strong> pada kolom dan beri tanda centang pada opsi modifikasi di kolom keterangan (pilihan boleh lebih dari satu).</li>
                <li>Jika tidak diperlukan penyesuaian, biarkan opsi pada pilihan <strong>Tidak</strong> atau kosongkan.</li>
            </ul>

            {{-- Potensi Asesi --}}
            <div class="mt-4 pt-4 border-t border-slate-200">
                <span class="text-xs font-bold text-slate-900 uppercase tracking-wider block mb-2">Potensi Asesi:</span>
                <div class="space-y-2 text-xs">
                    @php
                        $potensiList = [
                            'Hasil pelatihan dan / atau pendidikan, dimana Kurikulum dan fasilitas praktek mampu telusur terhadap standar kompetensi.',
                            'Hasil pelatihan dan / atau pendidikan, dimana kurikulum belum berbasis kompetensi.',
                            'Pekerja berpengalaman, dimana berasal dari industri/tempat kerja yang dalam operasionalnya mampu telusur dengan standar kompetensi.',
                            'Pekerja berpengalaman, dimana berasal dari industri/tempat kerja yang dalam operasionalnya belum berbasis kompetensi.',
                            'Pelatihan / belajar mandiri atau otodidak.',
                        ];
                    @endphp
                    @foreach($potensiList as $pIndex => $pText)
                        <label class="flex items-start space-x-2.5 {{ $jadwal->is_closed ? 'cursor-default' : 'cursor-pointer' }}">
                            <input type="checkbox" name="potensi[]" value="{{ $pText }}" @disabled($jadwal->is_closed) class="mt-0.5 rounded border-slate-400 text-brand-600 focus:ring-brand-500 w-4 h-4">
                            <span class="text-slate-800">{{ $pText }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Tabel 8 Karakteristik Asesi Sesuai Dokumen MUK PDF --}}
        <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] overflow-hidden">
            <div class="bg-slate-900 text-white px-6 py-3 border-b-2 border-black flex items-center justify-between">
                <h2 class="text-xs font-black uppercase tracking-wider text-amber-300">
                    Mengidentifikasi Persyaratan Modifikasi dan Kontekstualisasi (Karakteristik Asesi)
                </h2>
                <span class="text-xs text-slate-300 font-mono">8 Indikator Karakteristik</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-100 border-b-2 border-black text-slate-900 font-black uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 w-12 text-center border-r border-slate-300">No</th>
                            <th class="px-4 py-3 w-2/5 border-r border-slate-300">Mengidentifikasi Persyaratan Modifikasi & Kontekstualisasi</th>
                            <th class="px-4 py-3 w-36 text-center border-r border-slate-300">Diperlukan Penyesuaian?</th>
                            <th class="px-4 py-3">Keterangan & Bentuk Penyesuaian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($itemsDef as $idx => $def)
                            <tr class="hover:bg-slate-50/70 transition align-top">
                                <td class="px-4 py-4 text-center font-bold font-mono border-r border-slate-200 text-slate-700">
                                    {{ $idx }}
                                    <input type="hidden" name="items[{{ $idx }}][title]" value="{{ $def['title'] }}">
                                </td>
                                <td class="px-4 py-4 border-r border-slate-200 text-slate-900 font-medium leading-relaxed">
                                    {{ $def['title'] }}
                                </td>
                                <td class="px-4 py-4 border-r border-slate-200 text-center">
                                    <div class="flex items-center justify-center space-x-3">
                                        <label class="inline-flex items-center space-x-1 {{ $jadwal->is_closed ? 'cursor-default' : 'cursor-pointer' }}">
                                            <input type="radio" name="items[{{ $idx }}][needed]" value="YA" @disabled($jadwal->is_closed) class="text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                            <span class="font-bold text-emerald-800">Ya</span>
                                        </label>
                                        <label class="inline-flex items-center space-x-1 {{ $jadwal->is_closed ? 'cursor-default' : 'cursor-pointer' }}">
                                            <input type="radio" name="items[{{ $idx }}][needed]" value="TIDAK" checked @disabled($jadwal->is_closed) class="text-slate-600 focus:ring-slate-500 w-4 h-4">
                                            <span class="text-slate-700">Tidak</span>
                                        </label>
                                    </div>
                                </td>
                                <td class="px-4 py-4 space-y-2">
                                    <div class="space-y-1.5">
                                        @foreach($def['options'] as $opt)
                                            <label class="flex items-start space-x-2 text-slate-700 {{ $jadwal->is_closed ? 'cursor-default' : 'cursor-pointer' }}">
                                                <input type="checkbox" name="items[{{ $idx }}][options][]" value="{{ $opt }}" @disabled($jadwal->is_closed) class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-3.5 h-3.5">
                                                <span>{{ $opt }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <div class="pt-1">
                                        <input type="text" name="items[{{ $idx }}][custom_note]" placeholder="Keterangan lain / catatan penyesuaian spesifik..." @disabled($jadwal->is_closed) class="w-full text-xs px-2.5 py-1.5 rounded border border-slate-300 focus:ring-1 focus:ring-brand-500 focus:outline-none bg-white">
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Hasil Kesepakatan Asesor dan Asesi --}}
        <div class="bg-white rounded-xl border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] p-6 space-y-4">
            <h2 class="text-sm font-black text-slate-900 uppercase tracking-wide border-b border-slate-200 pb-2">
                HASIL KESEPAKATAN ANTARA ASESOR DAN ASESI
            </h2>
            <p class="text-xs text-slate-600">
                Tentukan apakah diperlukan penyesuaian berdasarkan hasil identifikasi di atas. Jika diperlukan penyesuaian, tuliskan rincian kesepakatan:
            </p>

            <div class="flex items-center space-x-6 text-xs pt-1">
                <span class="font-bold text-slate-800">Status Kesepakatan Penyesuaian:</span>
                <label class="inline-flex items-center space-x-1.5 {{ $jadwal->is_closed ? 'cursor-default' : 'cursor-pointer' }}">
                    <input type="radio" name="agreement_status" value="YA" @disabled($jadwal->is_closed) class="text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                    <span class="font-bold text-emerald-800">Diperlukan Penyesuaian</span>
                </label>
                <label class="inline-flex items-center space-x-1.5 {{ $jadwal->is_closed ? 'cursor-default' : 'cursor-pointer' }}">
                    <input type="radio" name="agreement_status" value="TIDAK" checked @disabled($jadwal->is_closed) class="text-slate-600 focus:ring-slate-500 w-4 h-4">
                    <span class="font-medium text-slate-700">Tidak Diperlukan Penyesuaian (Standar Baku)</span>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs pt-2">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">1) Acuan Pembanding Asesmen:</label>
                    <input type="text" name="agreement_benchmark" placeholder="Contoh: SKKNI No. 097/2018..." @disabled($jadwal->is_closed) class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-brand-500 focus:outline-none bg-slate-50">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">2) Metode Asesmen yang Disepakati:</label>
                    <input type="text" name="agreement_method" placeholder="Contoh: Observasi Demonstrasi & Wawancara..." @disabled($jadwal->is_closed) class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-brand-500 focus:outline-none bg-slate-50">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">3) Instrumen Asesmen Disepakati:</label>
                    <input type="text" name="agreement_instrument" placeholder="Contoh: FR.IA.01, FR.IA.02..." @disabled($jadwal->is_closed) class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-brand-500 focus:outline-none bg-slate-50">
                </div>
            </div>

            <div class="pt-2 text-xs">
                <label class="block font-semibold text-slate-700 mb-1">4) Catatan Tambahan / Uraian Penyesuaian:</label>
                <textarea name="agreement_notes" rows="2" placeholder="Catatan kesepakatan penyesuaian khusus (jika ada)..." @disabled($jadwal->is_closed) class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-brand-500 focus:outline-none bg-slate-50"></textarea>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2">
            <a href="{{ route('asesor.schedule.ak01', $jadwal->id) }}" class="px-5 py-2.5 text-xs font-bold text-slate-700 bg-white border-2 border-slate-300 rounded-lg hover:bg-slate-50 transition">Kembali ke FR.AK.01</a>
            <div class="flex items-center space-x-3 w-full sm:w-auto justify-end">
                @if($jadwal->is_closed)
                    <button type="button" disabled class="px-6 py-2.5 text-xs font-black uppercase tracking-wider bg-slate-200 text-slate-500 rounded-lg cursor-not-allowed">
                        Jadwal Telah Ditutup (Read-Only)
                    </button>
                @else
                    <button type="submit" class="px-6 py-2.5 text-xs font-black uppercase tracking-wider bg-brand-600 hover:bg-brand-700 text-white rounded-lg border-2 border-black shadow-[2px_2px_0_0_rgba(0,0,0,0.9)] transition">
                        Simpan Penyesuaian FR.AK.07
                    </button>
                @endif
            </div>
        </div>
                    Simpan Penyesuaian FR.AK.07
                </button>
            </div>
        </div>
    </form>

    {{-- Modal Rincian Penyesuaian FR.AK.07 Asesi --}}
    <div x-show="modalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4"
         @keydown.escape.window="closeModal()">
        <div @click.away="closeModal()" 
             class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden border border-slate-200 flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-150">
            {{-- Header Modal --}}
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center space-x-2.5">
                    <span class="px-2.5 py-0.5 rounded text-[11px] font-mono font-bold bg-amber-400 text-slate-950">
                        FR.AK.07
                    </span>
                    <h3 class="text-sm font-bold tracking-wide uppercase text-white">
                        Rincian Penyesuaian yang Wajar (FR.AK.07)
                    </h3>
                </div>
                <button type="button" @click="closeModal()" class="text-slate-400 hover:text-white p-1 rounded-lg transition" title="Tutup Modal">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body Modal --}}
            <div class="p-6 overflow-y-auto space-y-4 text-xs text-slate-700 divide-y divide-slate-100">
                {{-- 1. Identitas & Status --}}
                <div class="pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h4 class="text-base font-black text-slate-900" x-text="selectedAsesi?.name"></h4>
                        <p class="text-xs text-slate-500 font-mono mt-0.5" x-text="selectedAsesi?.email"></p>
                        <template x-if="selectedAsesi?.nik && selectedAsesi?.nik !== '-'">
                            <span class="text-[11px] text-slate-600 font-mono block mt-0.5" x-text="'NIK: ' + selectedAsesi?.nik"></span>
                        </template>
                    </div>
                    <div>
                        <template x-if="selectedAsesi?.isApproved">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-2xs">
                                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                ✓ Sudah Ditetapkan
                            </span>
                        </template>
                        <template x-if="!selectedAsesi?.isApproved">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 shadow-2xs">
                                <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                                Belum Ditetapkan (Draf)
                            </span>
                        </template>
                    </div>
                </div>

                {{-- 2. Detail Penyesuaian --}}
                <div class="pt-3 space-y-2">
                    <div class="font-bold text-slate-900 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-brand-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Kategori & Rincian Penyesuaian
                    </div>
                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 space-y-2">
                        <div>
                            <span class="text-slate-500 font-medium block text-[11px]">Kategori Penyesuaian:</span>
                            <span class="font-bold text-slate-900 text-xs block mt-0.5" x-text="selectedAsesi?.category"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 font-medium block text-[11px]">Keterangan / Akomodasi Khusus:</span>
                            <div class="text-xs text-slate-800 font-medium bg-white p-2.5 rounded-lg border border-slate-200 mt-1 leading-relaxed" x-text="selectedAsesi?.detail"></div>
                        </div>
                    </div>
                </div>

                {{-- 3. Informasi Pelaksanaan TUK --}}
                <div class="pt-3 space-y-2">
                    <div class="font-bold text-slate-900 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-brand-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Tempat Uji Kompetensi (TUK)
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <span class="text-slate-500 font-medium block text-[11px]">Nama TUK:</span>
                            <span class="font-bold text-slate-900 text-xs" x-text="selectedAsesi?.tukName"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 font-medium block text-[11px]">Tanggal Pelaksanaan:</span>
                            <span class="font-medium text-slate-800 text-xs" x-text="selectedAsesi?.scheduleDate"></span>
                        </div>
                    </div>
                </div>

                {{-- 4. Panduan Update --}}
                <div class="pt-3">
                    <div class="bg-blue-50/80 border border-blue-200 rounded-xl p-3 text-[11px] text-blue-900 leading-relaxed flex items-start gap-2">
                        <svg class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <span class="font-bold">Panduan Pembaruan:</span> Jika asesi ini membutuhkan penyesuaian khusus atau perubahan modifikasi, centang asesi pada daftar di halaman ini, pilih karakteristik pada tabel panduan, lalu klik tombol <strong>"Simpan Penyesuaian FR.AK.07"</strong>.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer Modal --}}
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end">
                <button type="button" @click="closeModal()" class="px-4 py-2 text-xs font-bold text-slate-700 bg-white hover:bg-slate-100 border border-slate-300 rounded-lg shadow-2xs transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
