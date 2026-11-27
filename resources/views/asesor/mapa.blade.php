@extends('layouts.app', ['title' => 'FR.MAPA.01 - Merencanakan Aktivitas dan Proses Asesmen'])

@section('content')
@php
    $jadwal = $schedule;
    $assessments = $assessments ?? $jadwal->assessments ?? collect();
    $selectedAssessmentId = $selectedAssessmentId ?? request('assessment');
    $isClosedForMe = $jadwal->isClosedForAssessor();

    // Default values (Wisindo: kosongan tanpa bawaan input)
    $savedPendekatan = old('pendekatan_asesi', $mapaData['pendekatan_asesi'] ?? '');
    $savedTujuan = old('tujuan_asesmen', $mapaData['tujuan_asesmen'] ?? '');
    $savedLingkungan = old('konteks_lingkungan', $mapaData['konteks_lingkungan'] ?? '');
    $savedPeluang = old('peluang_bukti', $mapaData['peluang_bukti'] ?? '');
    $savedHubungan = old('hubungan_standar', $mapaData['hubungan_standar'] ?? '');
    $savedPelaksana = old('pelaksana_asesmen', $mapaData['pelaksana_asesmen'] ?? '');
    $savedOrangRelevan = old('orang_relevan', $mapaData['orang_relevan'] ?? []);
    if (!is_array($savedOrangRelevan)) {
        $savedOrangRelevan = [];
    }

    $savedStandarIndustri = old('standar_industri_opt', isset($mapaData['standar_industri'])
        ? array_keys(array_filter($mapaData['standar_industri'], fn($item) => !empty($item['checked'])))
        : []);
    if (!is_array($savedStandarIndustri)) {
        $savedStandarIndustri = [];
    }

    $savedStandarKompetensiVal = old('standar_kompetensi_val', $mapaData['standar_industri']['standar_kompetensi']['text'] ?? '');
    $savedSpesifikasiKinerjaVal = old('spesifikasi_kinerja_val', $mapaData['standar_industri']['spesifikasi_kinerja']['text'] ?? '');
    $savedSpesifikasiProdukVal = old('spesifikasi_produk_val', $mapaData['standar_industri']['spesifikasi_produk']['text'] ?? '');
    $savedPedomanKhususVal = old('pedoman_khusus_val', $mapaData['standar_industri']['pedoman_khusus']['text'] ?? '');

    $savedStandar = old('standar_tolok_ukur', $mapaData['standar_tolok_ukur'] ?? '');

    // Sub 3 Defaults
    $hasKarakteristik = old('has_karakteristik', !empty($mapaData['has_karakteristik']) ? '1' : '0');
    $karakteristikDetail = old('karakteristik_detail', $mapaData['karakteristik_detail'] ?? '');

    $hasKontekstualisasi = old('has_kontekstualisasi', !empty($mapaData['has_kontekstualisasi']) ? '1' : '0');
    $kontekstualisasiDetail = old('kontekstualisasi_detail', $mapaData['kontekstualisasi_detail'] ?? '');

    $hasSaran = old('has_saran', !empty($mapaData['has_saran']) ? '1' : '0');
    $saranDetail = old('saran_detail', $mapaData['saran_detail'] ?? '');

    $hasPenyesuaian = old('has_penyesuaian', !empty($mapaData['has_penyesuaian']) ? '1' : '0');
    $penyesuaianDetail = old('penyesuaian_detail', $mapaData['penyesuaian_detail'] ?? '');

    $hasTerintegrasi = old('has_terintegrasi', isset($mapaData['has_terintegrasi']) ? ($mapaData['has_terintegrasi'] ? '1' : '0') : '0');
    $terintegrasiDetail = old('terintegrasi_detail', $mapaData['terintegrasi_detail'] ?? '');
    $savedIntegratedTasks = old('integrated_tasks', $mapaData['integrated_tasks'] ?? ($defaultIntegratedTasks ?? []));
    if (!is_array($savedIntegratedTasks)) {
        $savedIntegratedTasks = [];
    }
@endphp

<div class="space-y-6 max-w-5xl mx-auto pb-12"
     x-data="{
        hasKarakteristik: '{{ $hasKarakteristik }}',
        hasKontekstualisasi: '{{ $hasKontekstualisasi }}',
        hasSaran: '{{ $hasSaran }}',
        hasPenyesuaian: '{{ $hasPenyesuaian }}',
        hasTerintegrasi: '{{ $hasTerintegrasi }}',
        tasks: {{ json_encode($savedIntegratedTasks) }},
        modalOpen: false,
        selectedAsesi: null,
        openModal(data) {
            this.selectedAsesi = data;
            this.modalOpen = true;
        },
        closeModal() {
            this.modalOpen = false;
            this.selectedAsesi = null;
        },
        addTask() {
            this.tasks.push('');
        },
        removeTask(index) {
            this.tasks.splice(index, 1);
        }
     }">

    {{-- Top Lifecycle Navigation --}}
    @include('asesor.partials.schedule-nav', ['schedule' => $jadwal, 'active' => 'mapa'])

    @if($isClosedForMe)
        <div class="p-3.5 bg-emerald-50 border border-emerald-300 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-emerald-900">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span><strong>{{ $jadwal->is_closed ? 'Jadwal Ditutup:' : 'Asesmen Selesai:' }}</strong> Dokumen FR.MAPA.01 dalam mode lihat (read-only).</span>
            </div>
            <span class="font-bold font-mono text-[11px] bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded border border-emerald-300 shrink-0">STATUS: READ-ONLY</span>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">FR.MAPA.01. MERENCANAKAN AKTIVITAS DAN PROSES ASESMEN</h1>
            <p class="text-xs text-slate-500 font-medium">LSP Pariwisata dan Perhotelan Wisindo (LSP Wisindo)</p>
        </div>
    </div>

    {{-- Info Box --}}
    <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 text-xs text-slate-700 shadow-2xs flex items-start gap-3">
        <div class="w-6 h-6 rounded-full bg-brand-50 text-brand-700 flex items-center justify-center shrink-0 mt-0.5 font-bold">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="leading-relaxed">
            <span class="font-bold text-slate-900">Perencanaan Asesmen Kolektif:</span> Tentukan pendekatan asesmen, tolok ukur, dan persyaratan modifikasi kontekstualisasi untuk asesi yang Anda ampu pada jadwal ini. Data yang disimpan akan terhubung langsung ke dokumen resmi FR.MAPA.01.
        </div>
    </div>

    <form action="{{ route('asesor.schedule.mapa.store', $jadwal->id) }}" method="POST" class="space-y-6">
        @csrf

        {{-- Meta Box --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <span class="font-mono text-xs font-bold text-amber-400 bg-slate-800 px-2 py-0.5 rounded">FR.MAPA.01</span>
                <span class="text-xs text-slate-300 font-medium">Skema: {{ $jadwal->scheme?->name }} ({{ $jadwal->scheme?->code }})</span>
            </div>

            {{-- 1. Menentukan Pendekatan Asesmen --}}
            <div class="p-5 space-y-5 border-b border-slate-200">
                <div class="border-b border-slate-200 pb-2">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide">1. Menentukan Pendekatan Asesmen</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Pilih pendekatan calon asesi, tujuan asesmen, dan konteks pelaksanaan.</p>
                </div>

                {{-- 1.1 Asesi --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-800">
                        1.1. Calon Asesi <span class="text-rose-500">*</span>
                    </label>
                    <select name="pendekatan_asesi" @disabled($isClosedForMe) required class="w-full text-xs rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none transition">
                        <option value="" disabled {{ empty($savedPendekatan) ? 'selected' : '' }}>-- Pilih Pendekatan Asesi --</option>
                        @foreach($pendekatanOptions as $opt)
                            <option value="{{ $opt }}" {{ $savedPendekatan === $opt ? 'selected' : '' }}>
                                {{ $opt }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 1.2 Tujuan Asesmen & Lingkungan --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-800">
                            1.2. Tujuan Asesmen <span class="text-rose-500">*</span>
                        </label>
                        <select name="tujuan_asesmen" @disabled($isClosedForMe) required class="w-full text-xs rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none transition">
                            <option value="" disabled {{ empty($savedTujuan) ? 'selected' : '' }}>-- Pilih Tujuan Asesmen --</option>
                            @foreach($tujuanOptions as $opt)
                                <option value="{{ $opt }}" {{ $savedTujuan === $opt ? 'selected' : '' }}>
                                    {{ $opt }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-800">
                            1.3. Konteks Asesmen: Lingkungan <span class="text-rose-500">*</span>
                        </label>
                        <select name="konteks_lingkungan" @disabled($isClosedForMe) required class="w-full text-xs rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none transition">
                            <option value="" disabled {{ empty($savedLingkungan) ? 'selected' : '' }}>-- Pilih Lingkungan --</option>
                            @foreach($lingkunganOptions as $opt)
                                <option value="{{ $opt }}" {{ $savedLingkungan === $opt ? 'selected' : '' }}>
                                    {{ $opt }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Peluang Bukti & Hubungan Standar --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-800">
                            Peluang untuk Mengumpulkan Bukti <span class="text-rose-500">*</span>
                        </label>
                        <select name="peluang_bukti" @disabled($isClosedForMe) required class="w-full text-xs rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none transition">
                            <option value="" disabled {{ empty($savedPeluang) ? 'selected' : '' }}>-- Pilih Peluang Bukti --</option>
                            @foreach($peluangBuktiOptions as $opt)
                                <option value="{{ $opt }}" {{ $savedPeluang === $opt ? 'selected' : '' }}>
                                    {{ $opt }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-800">
                            Hubungan antara Standar Kompetensi dan: <span class="text-rose-500">*</span>
                        </label>
                        <select name="hubungan_standar" @disabled($isClosedForMe) required class="w-full text-xs rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none transition">
                            <option value="" disabled {{ empty($savedHubungan) ? 'selected' : '' }}>-- Pilih Hubungan Standar --</option>
                            @foreach($hubunganStandarOptions as $opt)
                                <option value="{{ $opt }}" {{ $savedHubungan === $opt ? 'selected' : '' }}>
                                    {{ $opt }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Siapa Pelaksana Asesmen --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-800">
                        Siapa yang Melakukan Asesmen / RPL <span class="text-rose-500">*</span>
                    </label>
                    <select name="pelaksana_asesmen" @disabled($isClosedForMe) required class="w-full text-xs rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none transition">
                        <option value="" disabled {{ empty($savedPelaksana) ? 'selected' : '' }}>-- Pilih Pelaksana Asesmen --</option>
                        @foreach($pelaksanaOptions as $opt)
                            <option value="{{ $opt }}" {{ $savedPelaksana === $opt ? 'selected' : '' }}>
                                {{ $opt }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Standar Industri atau Tempat Kerja (Checkboxes) --}}
                <div class="space-y-2.5 pt-4 border-t border-slate-200">
                    <div>
                        <label class="block text-xs font-bold text-slate-900">
                            Standar Industri atau Tempat Kerja
                        </label>
                        <p class="text-[11px] text-slate-500">Pilih tolok ukur standar yang digunakan sebagai acuan asesmen.</p>
                    </div>

                    <div class="space-y-2.5 bg-slate-50/60 p-3 sm:p-4 rounded-xl border border-slate-200">
                        {{-- 1. Standar Kompetensi --}}
                        <div class="flex flex-col gap-2 p-2.5 rounded-lg border border-slate-200 bg-white shadow-2xs">
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-800 cursor-pointer">
                                <input type="checkbox" name="standar_industri_opt[]" value="standar_kompetensi"
                                       @disabled($isClosedForMe)
                                       {{ in_array('standar_kompetensi', $savedStandarIndustri) ? 'checked' : '' }}
                                       class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4 shrink-0">
                                <span>Standar Kompetensi:</span>
                            </label>
                            <div class="pl-6">
                                <input type="text" name="standar_kompetensi_val" value="{{ $savedStandarKompetensiVal }}"
                                       placeholder="Contoh: SKKNI Bidang Hotel dan Restoran"
                                       @disabled($isClosedForMe)
                                       class="w-full text-xs rounded-md border border-slate-300 bg-white px-3 py-1.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none transition">
                            </div>
                        </div>

                        {{-- 2. Kriteria asesmen dari kurikulum pelatihan --}}
                        <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 bg-white cursor-pointer hover:bg-slate-50 transition text-xs shadow-2xs">
                            <input type="checkbox" name="standar_industri_opt[]" value="kurikulum_pelatihan"
                                   @disabled($isClosedForMe)
                                   {{ in_array('kurikulum_pelatihan', $savedStandarIndustri) ? 'checked' : '' }}
                                   class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4 shrink-0">
                            <span class="text-slate-800 font-semibold">Kriteria asesmen dari kurikulum pelatihan</span>
                        </label>

                        {{-- 3. Spesifikasi kinerja suatu perusahaan atau industri --}}
                        <div class="flex flex-col gap-2 p-2.5 rounded-lg border border-slate-200 bg-white shadow-2xs">
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-800 cursor-pointer">
                                <input type="checkbox" name="standar_industri_opt[]" value="spesifikasi_kinerja"
                                       @disabled($isClosedForMe)
                                       {{ in_array('spesifikasi_kinerja', $savedStandarIndustri) ? 'checked' : '' }}
                                       class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4 shrink-0">
                                <span>Spesifikasi kinerja suatu perusahaan atau industri:</span>
                            </label>
                            <div class="pl-6">
                                <input type="text" name="spesifikasi_kinerja_val" value="{{ $savedSpesifikasiKinerjaVal }}"
                                       placeholder="Contoh: Standard Operating Procedure (SOP) Hotel"
                                       @disabled($isClosedForMe)
                                       class="w-full text-xs rounded-md border border-slate-300 bg-white px-3 py-1.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none transition">
                            </div>
                        </div>

                        {{-- 4. Spesifikasi Produk --}}
                        <div class="flex flex-col gap-2 p-2.5 rounded-lg border border-slate-200 bg-white shadow-2xs">
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-800 cursor-pointer">
                                <input type="checkbox" name="standar_industri_opt[]" value="spesifikasi_produk"
                                       @disabled($isClosedForMe)
                                       {{ in_array('spesifikasi_produk', $savedStandarIndustri) ? 'checked' : '' }}
                                       class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4 shrink-0">
                                <span>Spesifikasi Produk:</span>
                            </label>
                            <div class="pl-6">
                                <input type="text" name="spesifikasi_produk_val" value="{{ $savedSpesifikasiProdukVal }}"
                                       placeholder="Tuliskan spesifikasi produk bila ada..."
                                       @disabled($isClosedForMe)
                                       class="w-full text-xs rounded-md border border-slate-300 bg-white px-3 py-1.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none transition">
                            </div>
                        </div>

                        {{-- 5. Pedoman khusus --}}
                        <div class="flex flex-col gap-2 p-2.5 rounded-lg border border-slate-200 bg-white shadow-2xs">
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-800 cursor-pointer">
                                <input type="checkbox" name="standar_industri_opt[]" value="pedoman_khusus"
                                       @disabled($isClosedForMe)
                                       {{ in_array('pedoman_khusus', $savedStandarIndustri) ? 'checked' : '' }}
                                       class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4 shrink-0">
                                <span>Pedoman khusus:</span>
                            </label>
                            <div class="pl-6">
                                <input type="text" name="pedoman_khusus_val" value="{{ $savedPedomanKhususVal }}"
                                       placeholder="Tuliskan pedoman khusus bila ada..."
                                       @disabled($isClosedForMe)
                                       class="w-full text-xs rounded-md border border-slate-300 bg-white px-3 py-1.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none transition">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Konfirmasi Orang yang Relevan (Checkbox) --}}
                <div class="space-y-2 pt-4 border-t border-slate-200">
                    <label class="block text-xs font-bold text-slate-800">
                        Konfirmasi dengan Orang yang Relevan:
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        @foreach($orangRelevanOptions as $idx => $opt)
                            <label class="flex items-center space-x-2.5 p-2.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 transition cursor-pointer text-xs shadow-2xs">
                                <input type="checkbox" name="orang_relevan[]" value="{{ $opt }}"
                                       @disabled($isClosedForMe)
                                       {{ in_array($opt, $savedOrangRelevan) ? 'checked' : '' }}
                                       class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4">
                                <span class="text-slate-800">{{ $opt }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- 2. Mempersiapkan Rencana Asesmen --}}
            <div class="p-5 space-y-2 border-b border-slate-200 bg-slate-50/50">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide">2. Mempersiapkan Rencana Asesmen</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Rincian unit kompetensi, instrumen, dan bukti asesmen ditentukan di FR.AK.01.</p>
                    </div>
                    <span class="text-[11px] font-bold text-slate-700 bg-slate-200/70 border border-slate-300 px-2.5 py-1 rounded">Ditentukan di FR.AK.01</span>
                </div>
            </div>

            {{-- 3. Mengidentifikasi Persyaratan Modifikasi dan Kontekstualisasi --}}
            <div class="p-5 space-y-5">
                <div class="border-b border-slate-200 pb-2">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide">3. Mengidentifikasi Persyaratan Modifikasi dan Kontekstualisasi</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Identifikasi penyesuaian khusus terhadap karakteristik asesi, tempat kerja, atau perangkat asesmen.</p>
                </div>

                {{-- 3.1. a. Karakteristik Asesi --}}
                <div class="p-4 rounded-xl border border-slate-200 space-y-3 bg-white">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <span class="text-xs font-bold text-slate-900 block">3.1. a. Karakteristik Asesi:</span>
                            <span class="text-[11px] text-slate-500 italic block mt-0.5">Ada / tidak ada* karakteristik khusus Asesi</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold cursor-pointer transition border"
                                   :class="hasKarakteristik === '0' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'">
                                <input type="radio" name="has_karakteristik" value="0" x-model="hasKarakteristik" @disabled($isClosedForMe) class="sr-only">
                                <span>Tidak Ada</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold cursor-pointer transition border"
                                   :class="hasKarakteristik === '1' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'">
                                <input type="radio" name="has_karakteristik" value="1" x-model="hasKarakteristik" @disabled($isClosedForMe) class="sr-only">
                                <span>Ada</span>
                            </label>
                        </div>
                    </div>
                    <div x-show="hasKarakteristik === '1'" x-cloak class="pt-2 border-t border-slate-100">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Jika Ada, tuliskan:</label>
                        <textarea name="karakteristik_detail" rows="2" @disabled($isClosedForMe)
                                  placeholder="Contoh: Asesi memerlukan pendampingan visual atau instruksi kerja dalam bahasa daerah..."
                                  class="w-full text-xs rounded-lg border border-slate-300 bg-white p-3 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none transition">{{ $karakteristikDetail }}</textarea>
                    </div>
                </div>

                {{-- 3.1. b. Kebutuhan kontekstualisasi terkait tempat kerja --}}
                <div class="p-4 rounded-xl border border-slate-200 space-y-3 bg-white">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <span class="text-xs font-bold text-slate-900 block">b. Kebutuhan kontekstualisasi terkait tempat kerja:</span>
                            <span class="text-[11px] text-slate-500 italic block mt-0.5">Ada / tidak ada* kebutuhan kontekstualisasi</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold cursor-pointer transition border"
                                   :class="hasKontekstualisasi === '0' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'">
                                <input type="radio" name="has_kontekstualisasi" value="0" x-model="hasKontekstualisasi" @disabled($isClosedForMe) class="sr-only">
                                <span>Tidak Ada</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold cursor-pointer transition border"
                                   :class="hasKontekstualisasi === '1' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'">
                                <input type="radio" name="has_kontekstualisasi" value="1" x-model="hasKontekstualisasi" @disabled($isClosedForMe) class="sr-only">
                                <span>Ada</span>
                            </label>
                        </div>
                    </div>
                    <div x-show="hasKontekstualisasi === '1'" x-cloak class="pt-2 border-t border-slate-100">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Jika Ada, tuliskan:</label>
                        <textarea name="kontekstualisasi_detail" rows="2" @disabled($isClosedForMe)
                                  placeholder="Contoh: Menggunakan SOP dan format checklist operasional hotel tempat asesi bekerja..."
                                  class="w-full text-xs rounded-lg border border-slate-300 bg-white p-3 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none transition">{{ $kontekstualisasiDetail }}</textarea>
                    </div>
                </div>

                {{-- 3.2. Saran yang diberikan oleh paket pelatihan atau pengembang pelatihan --}}
                <div class="p-4 rounded-xl border border-slate-200 space-y-3 bg-white">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <span class="text-xs font-bold text-slate-900 block">3.2. Saran yang diberikan oleh paket pelatihan atau pengembang pelatihan:</span>
                            <span class="text-[11px] text-slate-500 italic block mt-0.5">Ada / tidak ada* saran</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold cursor-pointer transition border"
                                   :class="hasSaran === '0' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'">
                                <input type="radio" name="has_saran" value="0" x-model="hasSaran" @disabled($isClosedForMe) class="sr-only">
                                <span>Tidak Ada</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold cursor-pointer transition border"
                                   :class="hasSaran === '1' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'">
                                <input type="radio" name="has_saran" value="1" x-model="hasSaran" @disabled($isClosedForMe) class="sr-only">
                                <span>Ada</span>
                            </label>
                        </div>
                    </div>
                    <div x-show="hasSaran === '1'" x-cloak class="pt-2 border-t border-slate-100">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Jika Ada, tuliskan:</label>
                        <textarea name="saran_detail" rows="2" @disabled($isClosedForMe)
                                  placeholder="Tuliskan catatan saran paket pelatihan bila ada..."
                                  class="w-full text-xs rounded-lg border border-slate-300 bg-white p-3 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none transition">{{ $saranDetail }}</textarea>
                    </div>
                </div>

                {{-- 3.3. Penyesuaian perangkat asesmen terkait kebutuhan kontekstualisasi --}}
                <div class="p-4 rounded-xl border border-slate-200 space-y-3 bg-white">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <span class="text-xs font-bold text-slate-900 block">3.3. Penyesuaian perangkat asesmen terkait kebutuhan kontekstualisasi:</span>
                            <span class="text-[11px] text-slate-500 italic block mt-0.5">Ada / tidak ada* penyesuaian perangkat</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold cursor-pointer transition border"
                                   :class="hasPenyesuaian === '0' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'">
                                <input type="radio" name="has_penyesuaian" value="0" x-model="hasPenyesuaian" @disabled($isClosedForMe) class="sr-only">
                                <span>Tidak Ada</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold cursor-pointer transition border"
                                   :class="hasPenyesuaian === '1' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'">
                                <input type="radio" name="has_penyesuaian" value="1" x-model="hasPenyesuaian" @disabled($isClosedForMe) class="sr-only">
                                <span>Ada</span>
                            </label>
                        </div>
                    </div>
                    <div x-show="hasPenyesuaian === '1'" x-cloak class="pt-2 border-t border-slate-100">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Jika ada tuliskan:</label>
                        <textarea name="penyesuaian_detail" rows="2" @disabled($isClosedForMe)
                                  placeholder="Tuliskan penyesuaian instruksi kerja atau alat asesmen..."
                                  class="w-full text-xs rounded-lg border border-slate-300 bg-white p-3 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none transition">{{ $penyesuaianDetail }}</textarea>
                    </div>
                </div>

                {{-- 3.4. Peluang untuk kegiatan asesmen terintegrasi dan mencatat setiap perubahan yang diperlukan untuk alat asesmen --}}
                <div class="p-4 rounded-xl border border-slate-200 space-y-3 bg-white">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <span class="text-xs font-bold text-slate-900 block">3.4. Peluang untuk kegiatan asesmen terintegrasi dan mencatat setiap perubahan yang diperlukan untuk alat asesmen:</span>
                            <span class="text-[11px] text-slate-500 italic block mt-0.5">Ada / tidak ada* peluang</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold cursor-pointer transition border"
                                   :class="hasTerintegrasi === '0' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'">
                                <input type="radio" name="has_terintegrasi" value="0" x-model="hasTerintegrasi" @disabled($isClosedForMe) class="sr-only">
                                <span>Tidak Ada</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold cursor-pointer transition border"
                                   :class="hasTerintegrasi === '1' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'">
                                <input type="radio" name="has_terintegrasi" value="1" x-model="hasTerintegrasi" @disabled($isClosedForMe) class="sr-only">
                                <span>Ada</span>
                            </label>
                        </div>
                    </div>

                    <div x-show="hasTerintegrasi === '1'" x-cloak class="pt-3 border-t border-slate-100 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-800">Jika Ada, tuliskan kegiatan / unit terintegrasi:</span>
                            @if(!$isClosedForMe)
                                <button type="button" @click="addTask()" class="px-2.5 py-1 text-xs font-bold text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200 rounded-lg transition inline-flex items-center gap-1 cursor-pointer">
                                    <span>+ Tambah Baris</span>
                                </button>
                            @endif
                        </div>

                        <div class="space-y-2">
                            <template x-for="(task, index) in tasks" :key="index">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-slate-400 font-mono w-5 text-right" x-text="(index + 1) + '.'"></span>
                                    <input type="text" :name="'integrated_tasks[]'" x-model="tasks[index]"
                                           @disabled($isClosedForMe)
                                           placeholder="Tuliskan nama kegiatan terintegrasi..."
                                           class="flex-1 text-xs rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 focus:outline-none transition">
                                    @if(!$isClosedForMe)
                                        <button type="button" @click="removeTask(index)" title="Hapus baris ini"
                                                class="p-2 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg transition border border-transparent hover:border-rose-200 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Footer Note Section 3 --}}
                <div class="pt-2 text-[11px] text-slate-500 italic">
                    * Coret yang tidak perlu
                </div>
            </div>

            {{-- 4. Pemilihan Asesi (Kolektif / Individual) --}}
            <div class="p-5 border-t border-slate-200 bg-slate-50/50 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 pb-2 border-b border-slate-200">
                    <div>
                        <span class="text-xs font-bold text-slate-900 block">Terapkan Perencanaan MAPA.01 untuk Asesi:</span>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Pilih asesi yang akan diterapkan perencanaan MAPA.01 ini. Klik tombol <strong>Rincian Asesi</strong> untuk meninjau data masing-masing asesi dalam jendela popup modal tanpa reload halaman.
                        </p>
                    </div>
                    <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-800 cursor-pointer shrink-0">
                        <input type="checkbox" id="check-all-mapa-asesi" checked @disabled($isClosedForMe)
                               class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4"
                               onclick="document.querySelectorAll('.mapa-asesi-item').forEach(el => el.checked = this.checked)">
                        <span>Pilih Semua ({{ $assessments->count() }})</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 max-h-80 overflow-y-auto pr-1">
                    @foreach($assessments as $asm)
                        @php
                            $isMapaApproved = !empty($asm->mapa01_data) || !empty($schedule->mapa01_data);
                            $candMapa = $asm->mapa01_data ?? $schedule->mapa01_data ?? [];
                            $isChecked = empty(old('assessment_ids')) 
                                ? true
                                : in_array($asm->id, old('assessment_ids', []));

                            $candTolokParts = [];
                            if (!empty($candMapa['standar_industri']['standar_kompetensi']['checked'])) {
                                $candTolokParts[] = 'Standar Kompetensi: ' . ($candMapa['standar_industri']['standar_kompetensi']['text'] ?? 'SKKNI');
                            }
                            if (!empty($candMapa['standar_industri']['kurikulum_pelatihan']['checked'])) {
                                $candTolokParts[] = 'Kurikulum Pelatihan';
                            }
                            if (!empty($candMapa['standar_industri']['spesifikasi_kinerja']['checked'])) {
                                $candTolokParts[] = 'Kinerja: ' . ($candMapa['standar_industri']['spesifikasi_kinerja']['text'] ?? 'Repair Manual');
                            }
                            if (!empty($candMapa['standar_industri']['spesifikasi_produk']['checked']) && !empty($candMapa['standar_industri']['spesifikasi_produk']['text'])) {
                                $candTolokParts[] = 'Produk: ' . $candMapa['standar_industri']['spesifikasi_produk']['text'];
                            }
                            if (!empty($candMapa['standar_industri']['pedoman_khusus']['checked']) && !empty($candMapa['standar_industri']['pedoman_khusus']['text'])) {
                                $candTolokParts[] = 'Pedoman: ' . $candMapa['standar_industri']['pedoman_khusus']['text'];
                            }
                            $candTolokStr = !empty($candTolokParts) ? implode('; ', $candTolokParts) : ($candMapa['standar_tolok_ukur'] ?? 'Standar Kompetensi: SKKNI');
                        @endphp
                        <div class="p-3 rounded-xl border-2 transition shadow-2xs flex flex-col justify-between {{ $isMapaApproved ? 'bg-emerald-50/80 border-emerald-300 hover:border-emerald-400' : 'bg-amber-50/90 border-amber-300 hover:border-amber-400' }}">
                            <div class="flex items-start justify-between gap-2">
                                <label class="flex items-start space-x-2.5 {{ $isClosedForMe ? 'cursor-default' : 'cursor-pointer' }} flex-1 min-w-0">
                                    <input type="checkbox" name="assessment_ids[]" value="{{ $asm->id }}"
                                           class="mapa-asesi-item rounded border-slate-400 text-brand-600 focus:ring-brand-500 w-4 h-4 mt-0.5 shrink-0"
                                           @disabled($isClosedForMe)
                                           {{ $isChecked ? 'checked' : '' }}>
                                    <div class="truncate text-xs">
                                        <span class="font-bold text-slate-900 block truncate">{{ $asm->asesi?->full_name }}</span>
                                        <span class="text-[10px] text-slate-500 block font-mono mt-0.5">NIK: {{ $asm->asesi?->profile?->nik ?? '-' }}</span>
                                    </div>
                                </label>
                                @if($isMapaApproved)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 shrink-0">
                                        ✓ Disetujui
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300 shrink-0">
                                        Belum Disetujui
                                    </span>
                                @endif
                            </div>

                            <div class="mt-2.5 pt-2 border-t {{ $isMapaApproved ? 'border-emerald-200/80' : 'border-amber-200/80' }} flex items-center justify-between text-[11px]">
                                <span class="text-[10px] font-medium {{ $isMapaApproved ? 'text-emerald-700' : 'text-amber-800' }}">
                                    {{ $isMapaApproved ? 'Data FR.MAPA.01 tersimpan' : 'Data masih kosong' }}
                                </span>
                                <button type="button" 
                                        @click="openModal({
                                            name: '{{ addslashes($asm->asesi?->full_name ?? '-') }}',
                                            email: '{{ addslashes($asm->asesi?->email ?? '-') }}',
                                            nik: '{{ addslashes($asm->asesi?->profile?->nik ?? '-') }}',
                                            isApproved: {{ $isMapaApproved ? 'true' : 'false' }},
                                            pendekatan: '{{ addslashes($candMapa['pendekatan_asesi'] ?? $savedPendekatan) }}',
                                            tujuan: '{{ addslashes($candMapa['tujuan_asesmen'] ?? $savedTujuan) }}',
                                            lingkungan: '{{ addslashes($candMapa['konteks_lingkungan'] ?? $savedLingkungan) }}',
                                            peluang: '{{ addslashes($candMapa['peluang_bukti'] ?? $savedPeluang) }}',
                                            hubungan: '{{ addslashes($candMapa['hubungan_standar'] ?? $savedHubungan) }}',
                                            pelaksana: '{{ addslashes($candMapa['pelaksana_asesmen'] ?? $savedPelaksana) }}',
                                            orangRelevan: {{ json_encode($candMapa['orang_relevan'] ?? $savedOrangRelevan) }},
                                            tolokUkur: '{{ addslashes($candTolokStr) }}',
                                            hasKarakteristik: {{ (!empty($candMapa['has_karakteristik']) || (!isset($candMapa['has_karakteristik']) && $hasKarakteristik === '1')) ? 'true' : 'false' }},
                                            karakteristikDetail: '{{ addslashes($candMapa['karakteristik_detail'] ?? $karakteristikDetail) }}',
                                            hasKontekstualisasi: {{ (!empty($candMapa['has_kontekstualisasi']) || (!isset($candMapa['has_kontekstualisasi']) && $hasKontekstualisasi === '1')) ? 'true' : 'false' }},
                                            kontekstualisasiDetail: '{{ addslashes($candMapa['kontekstualisasi_detail'] ?? $kontekstualisasiDetail) }}',
                                            hasSaran: {{ (!empty($candMapa['has_saran']) || (!isset($candMapa['has_saran']) && $hasSaran === '1')) ? 'true' : 'false' }},
                                            saranDetail: '{{ addslashes($candMapa['saran_detail'] ?? $saranDetail) }}',
                                            hasPenyesuaian: {{ (!empty($candMapa['has_penyesuaian']) || (!isset($candMapa['has_penyesuaian']) && $hasPenyesuaian === '1')) ? 'true' : 'false' }},
                                            penyesuaianDetail: '{{ addslashes($candMapa['penyesuaian_detail'] ?? $penyesuaianDetail) }}',
                                            hasTerintegrasi: {{ (!empty($candMapa['has_terintegrasi']) || (!isset($candMapa['has_terintegrasi']) && $hasTerintegrasi === '1')) ? 'true' : 'false' }},
                                            integratedTasks: {{ json_encode(!empty($candMapa['integrated_tasks']) ? $candMapa['integrated_tasks'] : $savedIntegratedTasks) }}
                                        })"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold rounded-lg transition shadow-2xs {{ $isMapaApproved ? 'text-emerald-900 bg-white hover:bg-emerald-100 border border-emerald-300' : 'text-amber-900 bg-white hover:bg-amber-100 border border-amber-300' }}"
                                        title="Buka rincian perencanaan FR.MAPA.01 asesi ini">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span>Rincian Asesi</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Footer Action Bar --}}
            <div class="p-5 bg-white border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                <a href="{{ route('asesor.schedule.instrument', ['schedule' => $jadwal->id, 'code' => 'apl']) }}"
                   class="w-full sm:w-auto px-4 py-2.5 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg transition text-center">
                    &larr; Kembali ke Verifikasi APL
                </a>

                @if(!$isClosedForMe)
                    <button type="submit"
                            class="w-full sm:w-auto px-6 py-2.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm transition flex items-center justify-center gap-1.5 cursor-pointer">
                        <span>Simpan FR.MAPA.01 &amp; Lanjut ke FR.AK.04</span>
                        <span>&rarr;</span>
                    </button>
                @else
                    <a href="{{ route('asesor.schedule.ak04', $jadwal->id) }}"
                       class="w-full sm:w-auto px-6 py-2.5 text-xs font-bold text-white bg-slate-800 hover:bg-slate-900 rounded-lg transition flex items-center justify-center gap-1.5">
                        <span>Lanjut ke FR.AK.04 (Read-Only)</span>
                        <span>&rarr;</span>
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- Modal Rincian FR.MAPA.01 Asesi --}}
    <div x-show="modalOpen" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4"
         @keydown.escape.window="closeModal()">
        <div @click.away="closeModal()" 
             class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden border border-slate-200 flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-150">
            {{-- Header Modal --}}
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center space-x-2.5">
                    <span class="px-2.5 py-0.5 rounded text-[11px] font-mono font-bold bg-amber-400 text-slate-950">
                        FR.MAPA.01
                    </span>
                    <h3 class="text-sm font-bold tracking-wide uppercase text-white">
                        Rincian Perencanaan MAPA Asesi
                    </h3>
                </div>
                <button type="button" @click="closeModal()" class="text-slate-400 hover:text-white p-1 rounded-lg transition" title="Tutup Modal">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body Modal --}}
            <div class="p-5 sm:p-6 overflow-y-auto space-y-4 text-xs text-slate-700 divide-y divide-slate-100">
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
                                ✓ Sudah Disetujui
                            </span>
                        </template>
                        <template x-if="!selectedAsesi?.isApproved">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 shadow-2xs">
                                <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                                Belum Disetujui
                            </span>
                        </template>
                    </div>
                </div>

                {{-- 2. Pendekatan Asesmen --}}
                <div class="pt-3 space-y-1.5">
                    <div class="font-bold text-slate-900 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-brand-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        1. Pendekatan Calon Asesi
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <span class="font-semibold text-slate-800 text-xs leading-relaxed" x-text="selectedAsesi?.pendekatan"></span>
                    </div>
                </div>

                {{-- 3. Tujuan & Konteks Lingkungan --}}
                <div class="pt-3 space-y-2">
                    <div class="font-bold text-slate-900 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-brand-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Tujuan &amp; Konteks Asesmen
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <span class="text-slate-500 font-medium block text-[11px]">Tujuan Asesmen:</span>
                            <span class="font-bold text-slate-900 text-xs" x-text="selectedAsesi?.tujuan"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 font-medium block text-[11px]">Konteks Asesmen:</span>
                            <span class="font-bold text-brand-700 text-xs px-2 py-0.5 bg-brand-50 border border-brand-200 rounded-md inline-block mt-0.5" x-text="selectedAsesi?.lingkungan"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 font-medium block text-[11px]">Peluang Mengumpulkan Bukti:</span>
                            <span class="font-medium text-slate-800 text-xs" x-text="selectedAsesi?.peluang"></span>
                        </div>
                        <div>
                            <span class="text-slate-500 font-medium block text-[11px]">Hubungan Standar:</span>
                            <span class="font-medium text-slate-800 text-xs" x-text="selectedAsesi?.hubungan"></span>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="text-slate-500 font-medium block text-[11px]">Pelaksana Asesmen / RPL:</span>
                            <span class="font-semibold text-slate-900 text-xs" x-text="selectedAsesi?.pelaksana"></span>
                        </div>
                    </div>
                </div>

                {{-- 4. Tolok Ukur & Konfirmasi Orang Relevan --}}
                <div class="pt-3 space-y-2">
                    <div class="font-bold text-slate-900 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-brand-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Tolok Ukur Standar &amp; Konfirmasi
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-2">
                        <div>
                            <span class="text-slate-500 font-medium block text-[11px]">Standar Industri / Tempat Kerja:</span>
                            <span class="font-semibold text-slate-800 text-xs leading-relaxed" x-text="selectedAsesi?.tolokUkur"></span>
                        </div>
                        <template x-if="selectedAsesi?.orangRelevan && selectedAsesi?.orangRelevan.length > 0">
                            <div class="pt-2 border-t border-slate-200">
                                <span class="text-slate-500 font-medium block text-[11px] mb-1">Konfirmasi dengan Orang yang Relevan:</span>
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="(or, idx) in selectedAsesi?.orangRelevan" :key="idx">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[11px] font-medium bg-white border border-slate-200 text-slate-800 shadow-2xs" x-text="or"></span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- 5. Modifikasi dan Kontekstualisasi (Section 3) --}}
                <div class="pt-3 space-y-2">
                    <div class="font-bold text-slate-900 uppercase tracking-wider text-[11px] flex items-center gap-1.5 text-brand-700">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        Persyaratan Modifikasi &amp; Kontekstualisasi
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-2.5">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-1">
                            <span class="font-bold text-slate-900 text-[11px]">3.1.a. Karakteristik Asesi:</span>
                            <span class="text-xs break-words max-w-sm" :class="selectedAsesi?.hasKarakteristik ? 'text-amber-700 font-bold' : 'text-slate-600'" x-text="selectedAsesi?.hasKarakteristik ? ('Ada: ' + selectedAsesi?.karakteristikDetail) : 'Tidak Ada'"></span>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-1 pt-1 border-t border-slate-200">
                            <span class="font-bold text-slate-900 text-[11px]">3.1.b. Kebutuhan Kontekstualisasi:</span>
                            <span class="text-xs break-words max-w-sm" :class="selectedAsesi?.hasKontekstualisasi ? 'text-amber-700 font-bold' : 'text-slate-600'" x-text="selectedAsesi?.hasKontekstualisasi ? ('Ada: ' + selectedAsesi?.kontekstualisasiDetail) : 'Tidak Ada'"></span>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-1 pt-1 border-t border-slate-200">
                            <span class="font-bold text-slate-900 text-[11px]">3.2. Saran Paket Pelatihan:</span>
                            <span class="text-xs break-words max-w-sm" :class="selectedAsesi?.hasSaran ? 'text-amber-700 font-bold' : 'text-slate-600'" x-text="selectedAsesi?.hasSaran ? ('Ada: ' + selectedAsesi?.saranDetail) : 'Tidak Ada'"></span>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-1 pt-1 border-t border-slate-200">
                            <span class="font-bold text-slate-900 text-[11px]">3.3. Penyesuaian Perangkat:</span>
                            <span class="text-xs break-words max-w-sm" :class="selectedAsesi?.hasPenyesuaian ? 'text-amber-700 font-bold' : 'text-slate-600'" x-text="selectedAsesi?.hasPenyesuaian ? ('Ada: ' + selectedAsesi?.penyesuaianDetail) : 'Tidak Ada'"></span>
                        </div>
                        <template x-if="selectedAsesi?.hasTerintegrasi && selectedAsesi?.integratedTasks && selectedAsesi?.integratedTasks.length > 0">
                            <div class="pt-2 border-t border-slate-200">
                                <span class="font-bold text-emerald-800 text-[11px] block mb-1">3.4. Kegiatan Asesmen Terintegrasi (Ada):</span>
                                <ul class="list-disc list-inside space-y-0.5 text-slate-700 text-[11px]">
                                    <template x-for="(task, tIdx) in selectedAsesi?.integratedTasks" :key="tIdx">
                                        <li x-text="task"></li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Catatan Panduan --}}
                <div class="pt-3">
                    <div class="bg-blue-50/80 border border-blue-200 rounded-xl p-3 text-[11px] text-blue-900 leading-relaxed flex items-start gap-2">
                        <svg class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <span class="font-bold">Panduan Penerapan:</span> Centang kotak nama asesi pada kartu di bawah jika ingin menerapkan atau memperbarui pengaturan formulir ini untuk asesi tersebut, lalu klik <strong>"Simpan FR.MAPA.01 &amp; Lanjut ke FR.AK.04"</strong>.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer Modal --}}
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-end">
                <button type="button" @click="closeModal()" class="px-4 py-2 text-xs font-bold text-slate-700 bg-white hover:bg-slate-100 border border-slate-300 rounded-lg shadow-2xs transition cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
