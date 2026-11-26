@extends('layouts.app', ['title' => 'Data & Dokumen Verifikasi TUK - LSP Wisindo'])

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                Data &amp; Verifikasi TUK Asesmen Jarak Jauh (AJJ)
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Lengkapi profil TUK, skema yang diajukan, dan bukti verifikasi media (foto &amp; video) sesuai SOP LSP P3 Wisata Indonesia.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $tuk->status_badge }}">
                {{ $tuk->status_label }}
            </span>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs sm:text-sm font-semibold flex items-center gap-2 shadow-2xs">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('warning'))
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs sm:text-sm font-semibold flex items-start gap-2 shadow-2xs">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    @if($tuk->verification_status === 'REVISION' && !empty($tuk->verification_notes))
        <div class="rounded-2xl bg-rose-50 border-2 border-rose-300 p-5 text-rose-950 shadow-sm space-y-2">
            <div class="flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-full bg-rose-600 text-white flex items-center justify-center font-bold text-sm shrink-0">⚠️</span>
                <div>
                    <h3 class="text-sm sm:text-base font-black text-rose-900">Catatan Revisi dari Admin LSP:</h3>
                    <p class="text-xs text-rose-800">Mohon perbaiki isian data atau berkas yang belum memenuhi standar sebelum mengirimkan ulang.</p>
                </div>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-rose-200 text-xs text-slate-800 leading-relaxed font-mono">
                {{ $tuk->verification_notes }}
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs space-y-1">
            <span class="font-bold block">Terdapat kesalahan pada isian form:</span>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('tuk.data.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Bagian 1: Data Umum TUK --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Bagian 1</span>
                <h2 class="text-base font-bold text-slate-900">Informasi Umum Tempat Uji Kompetensi (TUK)</h2>
                <p class="text-xs text-slate-500 mt-0.5">Data ini digunakan untuk pengesahan Berita Acara (BA) dan administrasi asesmen.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama TUK <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $tuk->name) }}" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"
                           placeholder="Contoh: TUK Mandiri Wisata Nusantara">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tipe / Jenis TUK <span class="text-rose-500">*</span></label>
                    <select name="type" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <option value="SEWAKTU" @selected(old('type', $tuk->type) === 'SEWAKTU')>TUK Sewaktu</option>
                        <option value="TEMPAT_KERJA" @selected(old('type', $tuk->type) === 'TEMPAT_KERJA')>TUK Tempat Kerja</option>
                        <option value="MANDIRI" @selected(old('type', $tuk->type) === 'MANDIRI')>TUK Mandiri</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Lengkap TUK <span class="text-rose-500">*</span></label>
                    <textarea name="address" rows="2" required
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"
                              placeholder="Alamat jalan, gedung, nomor, RT/RW, kelurahan, kecamatan">{{ old('address', $tuk->address) }}</textarea>
                </div>

                {{-- Wilayah: Dropdown Provinsi & Kota/Kabupaten via API --}}
                <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4"
                     x-data="wilayahDropdown({
                        initialProvCode: '{{ old('kode_provinsi', $tuk->kode_provinsi) }}',
                        initialProvName: '{{ old('nama_provinsi', $tuk->nama_provinsi) }}',
                        initialCityCode: '{{ old('kode_kota', $tuk->kode_kota) }}',
                        initialCityName: '{{ old('nama_kota', $tuk->nama_kota) }}'
                     })">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Provinsi <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="selectedProvCode"
                                @change="onProvinceChange()"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none bg-white">
                            <option value="">-- Pilih Provinsi --</option>
                            <template x-for="p in provinces" :key="p.code">
                                <option :value="p.code" x-text="p.name" :selected="String(p.code) === String(selectedProvCode)"></option>
                            </template>
                        </select>
                        <input type="hidden" name="kode_provinsi" :value="selectedProvCode">
                        <input type="hidden" name="nama_provinsi" :value="selectedProvName">
                        <p class="text-[11px] text-slate-400 mt-1" x-show="loadingProvinces">Memuat daftar provinsi...</p>
                        @error('kode_provinsi') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Kota / Kabupaten <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="selectedCityCode"
                                @change="onCityChange()"
                                :disabled="!selectedProvCode || loadingCities"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none bg-white disabled:bg-slate-100 disabled:text-slate-400">
                            <option value="" x-text="loadingCities ? 'Memuat daftar kota...' : (!selectedProvCode ? '-- Pilih Provinsi Dahulu --' : '-- Pilih Kota / Kabupaten --')"></option>
                            <template x-for="c in cities" :key="c.code">
                                <option :value="c.code" x-text="c.name" :selected="String(c.code) === String(selectedCityCode)"></option>
                            </template>
                        </select>
                        <input type="hidden" name="kode_kota" :value="selectedCityCode">
                        <input type="hidden" name="nama_kota" :value="selectedCityName">
                        <p class="text-[11px] text-slate-400 mt-1" x-show="loadingCities">Memuat daftar kota/kabupaten...</p>
                        @error('kode_kota') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kode Pos</label>
                    <input type="text" name="postal_code" value="{{ old('postal_code', $tuk->postal_code) }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"
                           placeholder="Contoh: 10340">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Telepon / WhatsApp TUK <span class="text-rose-500">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone', $tuk->phone) }}" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"
                           placeholder="021-... atau 08...">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email TUK</label>
                    <input type="email" name="email" value="{{ old('email', $tuk->email) }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"
                           placeholder="email@tuk.com">
                </div>
            </div>

            {{-- PIC TUK --}}
            <div class="pt-3 border-t border-slate-100">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">Penanggung Jawab (PIC) TUK</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Nama Lengkap PIC <span class="text-rose-500">*</span></label>
                        <input type="text" name="pic_name" value="{{ old('pic_name', $tuk->pic_name) }}" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"
                               placeholder="Nama PIC TUK">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Jabatan PIC <span class="text-rose-500">*</span></label>
                        <input type="text" name="pic_position" value="{{ old('pic_position', $tuk->pic_position) }}" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"
                               placeholder="Contoh: Manajer Operasional TUK">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">No. HP / WA PIC <span class="text-rose-500">*</span></label>
                        <input type="text" name="pic_phone" value="{{ old('pic_phone', $tuk->pic_phone) }}" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none"
                               placeholder="081234567890">
                    </div>
                </div>
            </div>
        </div>

        {{-- Bagian 2: Pilihan Skema Sertifikasi (Checkboxes) --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Bagian 2</span>
                <h2 class="text-base font-bold text-slate-900">Skema Sertifikasi yang Diajukan TUK <span class="text-rose-500">*</span></h2>
                <p class="text-xs text-slate-500 mt-0.5">Centang skema-skema yang siap diselenggarakan oleh TUK Anda untuk Asesmen Jarak Jauh (AJJ).</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($schemes as $scheme)
                    @php
                        $isChecked = in_array($scheme->id, old('scheme_ids', $selectedSchemeIds));
                    @endphp
                    <label class="flex items-start gap-3 p-3.5 rounded-xl border transition cursor-pointer {{ $isChecked ? 'border-brand-500 bg-brand-50/40 ring-1 ring-brand-500' : 'border-slate-200 bg-slate-50/50 hover:bg-slate-50' }}">
                        <input type="checkbox" name="scheme_ids[]" value="{{ $scheme->id }}"
                               class="mt-1 w-4 h-4 text-brand-600 rounded border-slate-300 focus:ring-brand-500 cursor-pointer"
                               @checked($isChecked)>
                        <div class="flex-1 text-xs">
                            <span class="font-bold text-slate-900 block">{{ $scheme->name }}</span>
                            <span class="text-[11px] text-slate-500 font-mono">{{ $scheme->code }} &bull; {{ $scheme->competencyUnits->count() }} Unit Kompetensi</span>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Bagian 3: Bukti Verifikasi Media AJJ (SOP Verifikasi TUK) --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 sm:p-6 shadow-sm space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Bagian 3</span>
                <h2 class="text-base font-bold text-slate-900">Dokumen &amp; Media Verifikasi AJJ</h2>
                <p class="text-xs text-slate-500 mt-0.5">Unggah bukti kelayakan fisik dan koneksi internet TUK. Setiap kategori foto maksimal 3 berkas (format JPG/PNG/WEBP, maks 5 MB per berkas). Anda dapat menambahkan beberapa foto sekaligus, mempratinjau, maupun menghapus foto yang tidak diperlukan.</p>
            </div>

            @php
                $photoCategories = [
                    [
                        'field' => 'room_photos',
                        'title' => '1. Foto Ruang TUK (1 - 3 Foto)',
                        'desc' => 'Foto tata letak meja uji, pencahayaan, dan ruangan tempat asesi mengikuti asesmen.',
                        'photos' => $tuk->room_photos ?? [],
                    ],
                    [
                        'field' => 'internet_data_photos',
                        'title' => '2. Foto Paket Data Internet (1 - 3 Foto)',
                        'desc' => 'Tangkapan layar/bukti paket data internet cadangan (modem/tethering kuota aktif).',
                        'photos' => $tuk->internet_data_photos ?? [],
                    ],
                    [
                        'field' => 'internet_connection_photos',
                        'title' => '3. Foto Koneksi Internet / Speedtest (1 - 3 Foto)',
                        'desc' => 'Tangkapan layar hasil uji kecepatan internet (Speedtest) minimal 10–20 Mbps stabil.',
                        'photos' => $tuk->internet_connection_photos ?? [],
                    ],
                    [
                        'field' => 'equipment_photos',
                        'title' => '4. Foto Peralatan / Perlengkapan Sesuai Skema (1 - 3 Foto)',
                        'desc' => 'Foto peralatan dapur/restoran/front office yang tersedia sesuai skema yang diajukan.',
                        'photos' => $tuk->equipment_photos ?? [],
                    ],
                ];
            @endphp

            @foreach($photoCategories as $cat)
                <div class="space-y-3 p-4 sm:p-5 rounded-2xl border border-slate-200 bg-slate-50/60"
                     x-data="tukPhotoUploader('{{ $cat['field'] }}', {{ Js::from(array_map(fn($p) => ['path' => $p, 'url' => Storage::url($p)], $cat['photos'])) }}, 3)">
                    
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <label class="text-xs sm:text-sm font-bold text-slate-900 block">{{ $cat['title'] }}</label>
                            <p class="text-[11px] text-slate-500">{{ $cat['desc'] }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold transition"
                                  :class="totalCount >= 3 ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-slate-200 text-slate-700'">
                                <span x-text="totalCount"></span> / 3 Foto
                            </span>
                        </div>
                    </div>

                    {{-- Hidden inputs for surviving existing photos --}}
                    <template x-for="(item, idx) in existing" :key="item.path">
                        <input type="hidden" name="existing_{{ $cat['field'] }}[]" :value="item.path">
                    </template>

                    {{-- Grid of Photo Thumbnails (Existing + Newly Selected) --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                        {{-- Existing Uploaded Photos --}}
                        <template x-for="(item, idx) in existing" :key="item.path">
                            <div class="relative group rounded-xl overflow-hidden border border-slate-200 bg-white shadow-2xs aspect-square flex flex-col justify-between">
                                <img :src="item.url" class="w-full h-full object-cover">
                                <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 text-[9px] font-bold bg-slate-900/70 text-white rounded backdrop-blur-xs">
                                    Tersimpan
                                </span>
                                <button type="button" @click="removeExisting(idx)"
                                        class="absolute top-1.5 right-1.5 p-1 rounded-full bg-rose-600 hover:bg-rose-700 text-white shadow transition cursor-pointer"
                                        title="Hapus foto ini">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </template>

                        {{-- Newly Selected Photos Preview --}}
                        <template x-for="(item, idx) in newFiles" :key="idx">
                            <div class="relative group rounded-xl overflow-hidden border-2 border-brand-500 bg-white shadow-2xs aspect-square flex flex-col justify-between">
                                <img :src="item.preview" class="w-full h-full object-cover">
                                <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 text-[9px] font-bold bg-brand-600 text-white rounded backdrop-blur-xs">
                                    Baru
                                </span>
                                <button type="button" @click="removeNew(idx)"
                                        class="absolute top-1.5 right-1.5 p-1 rounded-full bg-rose-600 hover:bg-rose-700 text-white shadow transition cursor-pointer"
                                        title="Batalkan foto ini">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                <div class="absolute bottom-0 inset-x-0 bg-slate-900/75 text-[10px] text-white px-1.5 py-0.5 truncate text-center" x-text="item.name"></div>
                            </div>
                        </template>

                        {{-- Add Photo Button / Tile (Visible if totalCount < 3) --}}
                        <div x-show="canAdd" class="aspect-square">
                            <label class="w-full h-full flex flex-col items-center justify-center border-2 border-dashed border-slate-300 hover:border-brand-500 hover:bg-brand-50/30 rounded-xl cursor-pointer transition p-2 text-center group">
                                <div class="w-8 h-8 rounded-full bg-slate-100 group-hover:bg-brand-100 text-slate-500 group-hover:text-brand-600 flex items-center justify-center mb-1 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </div>
                                <span class="text-[11px] font-bold text-slate-700 group-hover:text-brand-700 leading-tight">Tambah Foto</span>
                                <span class="text-[9px] text-slate-400 mt-0.5">JPG / PNG / WEBP</span>
                                <input type="file" :id="'input_' + fieldName" :name="fieldName + '[]'" multiple accept="image/jpeg,image/png,image/webp,image/jpg"
                                       @change="onFileSelect($event)" class="hidden">
                            </label>
                        </div>
                    </div>
                    @error($cat['field'])
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            {{-- 5. Video Ruang TUK (Walkthrough) --}}
            <div class="space-y-3 p-4 sm:p-5 rounded-2xl border border-slate-200 bg-slate-50/60"
                 x-data="{ hasExistingVideo: {{ !empty($tuk->room_video_path) ? 'true' : 'false' }}, deleteVideo: false }">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <label class="text-xs sm:text-sm font-bold text-slate-900 block">5. Video Ruang TUK (Walkthrough Singkat)</label>
                        <p class="text-[11px] text-slate-500">Video durasi 1 - 3 menit memperlihatkan seluruh sudut ruang TUK, kamera, dan kesiapan fasilitas (Maks. 50 MB, format MP4/WebM).</p>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Maks 50 MB</span>
                </div>

                @if(!empty($tuk->room_video_path))
                    <div x-show="!deleteVideo" class="p-3 bg-white rounded-xl border border-slate-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-emerald-700 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Video Ruang TUK Saat Ini
                            </span>
                            <button type="button" @click="deleteVideo = true" class="text-xs text-rose-600 hover:text-rose-800 font-bold flex items-center gap-1 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Hapus Video
                            </button>
                        </div>
                        <video controls class="max-w-md w-full max-h-56 rounded-lg border border-slate-200 bg-black">
                            <source src="{{ Storage::url($tuk->room_video_path) }}">
                            Browser Anda tidak mendukung pemutar video HTML5.
                        </video>
                    </div>
                    <input type="hidden" name="delete_room_video" :value="deleteVideo ? '1' : '0'">
                    <div x-show="deleteVideo" class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800 flex items-center justify-between">
                        <span>Video akan dihapus saat form disimpan. Anda dapat memilih berkas video baru di bawah jika ingin menggantinya.</span>
                        <button type="button" @click="deleteVideo = false" class="font-bold underline text-amber-900 cursor-pointer">Batal Hapus</button>
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        {{ !empty($tuk->room_video_path) ? 'Unggah Video Baru (Opsional, menggantikan video sebelumnya):' : 'Pilih Berkas Video Ruangan:' }}
                    </label>
                    <input type="file" name="room_video" accept="video/mp4,video/webm,video/quicktime,video/mkv"
                           class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-200 file:text-slate-800 hover:file:bg-slate-300 cursor-pointer">
                </div>
                @error('room_video')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('tuk.dashboard') }}" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 text-xs font-bold transition">
                Kembali ke Dashboard
            </a>
            <button type="submit" class="px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-sm hover:shadow transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>Simpan &amp; Kirim Verifikasi ke Admin LSP</span>
            </button>
        </div>
    </form>
</div>
@endsection
