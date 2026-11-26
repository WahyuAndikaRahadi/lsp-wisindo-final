@extends('layouts.app', ['title' => 'Edit Tempat Uji Kompetensi: ' . $tuk->name])

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.tuks.index') }}" class="hover:text-lsp-navy font-semibold">&larr; Kembali ke Daftar TUK</a>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Edit Data TUK</h1>
            <p class="text-xs sm:text-sm text-slate-500">{{ $tuk->name }} ({{ $tuk->code }})</p>
        </div>
        <div>
            <a href="{{ route('admin.tuks.review', $tuk) }}" class="px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span>Review Berkas AJJ</span>
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.tuks.update', $tuk) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Status Verifikasi Box --}}
        <div class="bg-indigo-50/70 border border-indigo-200 rounded-xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-xs font-bold text-indigo-900 uppercase tracking-wider">Status Verifikasi Asesmen Jarak Jauh (AJJ)</h3>
                <p class="text-xs text-indigo-700 mt-0.5">Hanya TUK berstatus Terverifikasi yang dapat dipilih saat pembuatan jadwal uji asesmen.</p>
            </div>
            <div class="w-full sm:w-auto">
                <select name="verification_status" class="w-full sm:w-60 px-3 py-2 bg-white border border-indigo-300 rounded-lg text-xs font-bold text-slate-800 focus:ring-1 focus:ring-lsp-navy">
                    <option value="UNSUBMITTED" {{ old('verification_status', $tuk->verification_status) === 'UNSUBMITTED' ? 'selected' : '' }}>Belum Diajukan (Draft)</option>
                    <option value="PENDING_VERIFICATION" {{ old('verification_status', $tuk->verification_status) === 'PENDING_VERIFICATION' ? 'selected' : '' }}>Menunggu Verifikasi Admin</option>
                    <option value="VERIFIED" {{ old('verification_status', $tuk->verification_status) === 'VERIFIED' ? 'selected' : '' }}>Terverifikasi AJJ (Aktif)</option>
                    <option value="REVISION" {{ old('verification_status', $tuk->verification_status) === 'REVISION' ? 'selected' : '' }}>Perlu Revisi Dokumen</option>
                </select>
            </div>
        </div>

        {{-- 1. Identitas TUK --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-100 flex items-center space-x-2">
                <span class="w-6 h-6 rounded-full bg-lsp-navy text-white text-xs flex items-center justify-center font-bold">1</span>
                <span>Identitas &amp; Alamat Tempat Uji Kompetensi (TUK)</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama TUK <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required value="{{ old('name', $tuk->name) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">
                    @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kode TUK</label>
                    <input type="text" name="code" value="{{ old('code', $tuk->code) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm uppercase font-mono focus:ring-1 focus:ring-lsp-navy">
                    @error('code') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis TUK <span class="text-rose-500">*</span></label>
                    <select name="type" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">
                        <option value="SEWAKTU" {{ old('type', $tuk->type) === 'SEWAKTU' ? 'selected' : '' }}>TUK Sewaktu</option>
                        <option value="TEMPAT_KERJA" {{ old('type', $tuk->type) === 'TEMPAT_KERJA' ? 'selected' : '' }}>TUK Tempat Kerja</option>
                        <option value="MANDIRI" {{ old('type', $tuk->type) === 'MANDIRI' ? 'selected' : '' }}>TUK Mandiri</option>
                    </select>
                    @error('type') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Telepon TUK</label>
                    <input type="text" name="phone" value="{{ old('phone', $tuk->phone) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">
                    @error('phone') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email Resmi TUK</label>
                    <input type="email" name="email" value="{{ old('email', $tuk->email) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">
                    @error('email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Lengkap TUK</label>
                <textarea name="address" rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">{{ old('address', $tuk->address) }}</textarea>
                @error('address') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Wilayah Dropdown via API --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4"
                 x-data="wilayahDropdown({
                    initialProvCode: '{{ old('kode_provinsi', $tuk->kode_provinsi ?: '32') }}',
                    initialProvName: '{{ old('nama_provinsi', $tuk->nama_provinsi) }}',
                    initialCityCode: '{{ old('kode_kota', $tuk->kode_kota) }}',
                    initialCityName: '{{ old('nama_kota', $tuk->nama_kota) }}'
                 })">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Provinsi</label>
                    <select x-model="selectedProvCode" @change="onProvinceChange()" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy bg-white">
                        <option value="">-- Pilih Provinsi --</option>
                        <template x-for="p in provinces" :key="p.code">
                            <option :value="p.code" :selected="String(p.code) === String(selectedProvCode)" x-text="p.name"></option>
                        </template>
                    </select>
                    <input type="hidden" name="kode_provinsi" :value="selectedProvCode">
                    <input type="hidden" name="nama_provinsi" :value="selectedProvName">
                    <p class="text-[10px] text-slate-400 mt-0.5" x-show="loadingProvinces">Memuat provinsi...</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kabupaten / Kota</label>
                    <select x-model="selectedCityCode" @change="onCityChange()" :disabled="!selectedProvCode || loadingCities" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy bg-white disabled:bg-slate-100 disabled:text-slate-400">
                        <option value="" x-text="loadingCities ? 'Memuat kota...' : (!selectedProvCode ? '-- Pilih Provinsi Dahulu --' : '-- Pilih Kota / Kab --')"></option>
                        <template x-for="c in cities" :key="c.code">
                            <option :value="c.code" :selected="String(c.code) === String(selectedCityCode)" x-text="c.name"></option>
                        </template>
                    </select>
                    <input type="hidden" name="kode_kota" :value="selectedCityCode">
                    <input type="hidden" name="nama_kota" :value="selectedCityName">
                    <p class="text-[10px] text-slate-400 mt-0.5" x-show="loadingCities">Memuat kota...</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Pos</label>
                    <input type="text" name="postal_code" value="{{ old('postal_code', $tuk->postal_code) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">
                </div>
            </div>
        </div>

        {{-- 2. Penanggung Jawab TUK (PIC) --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-100 flex items-center space-x-2">
                <span class="w-6 h-6 rounded-full bg-lsp-navy text-white text-xs flex items-center justify-center font-bold">2</span>
                <span>Penanggung Jawab / PIC TUK</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap PIC</label>
                    <input type="text" name="pic_name" value="{{ old('pic_name', $tuk->pic_name) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">
                    @error('pic_name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jabatan PIC</label>
                    <input type="text" name="pic_position" value="{{ old('pic_position', $tuk->pic_position) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">
                    @error('pic_position') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">No. WhatsApp / HP PIC</label>
                    <input type="text" name="pic_phone" value="{{ old('pic_phone', $tuk->pic_phone) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">
                    @error('pic_phone') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- 3. Skema Sertifikasi yang Diajukan/Diverifikasi --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-full bg-lsp-navy text-white text-xs flex items-center justify-center font-bold">3</span>
                    <span>Pilihan Skema Sertifikasi (AJJ)</span>
                </div>
                <span class="text-[11px] text-slate-400 font-normal">Centang skema yang disahkan untuk TUK ini</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($schemes as $scheme)
                    <label class="flex items-start space-x-3 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                        <input type="checkbox" name="scheme_ids[]" value="{{ $scheme->id }}"
                               {{ in_array($scheme->id, old('scheme_ids', $selectedSchemeIds)) ? 'checked' : '' }}
                               class="mt-1 h-4 w-4 rounded border-slate-300 text-lsp-navy focus:ring-lsp-navy">
                        <div>
                            <div class="font-bold text-slate-900 text-xs">{{ $scheme->name }}</div>
                            <div class="text-[11px] text-slate-500 font-mono">{{ $scheme->code }} &bull; {{ $scheme->units_count ?? $scheme->units()->count() }} Unit</div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- 4. Akun Login Admin TUK --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-full bg-lsp-navy text-white text-xs flex items-center justify-center font-bold">4</span>
                    <span>Akun Login Admin TUK</span>
                </div>
                <span class="text-[11px] text-slate-400 font-normal">Kelola akses portal TUK</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Admin</label>
                    <input type="text" name="admin_full_name" value="{{ old('admin_full_name', $adminUser?->full_name) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email Login Admin TUK</label>
                    <input type="email" name="admin_email" value="{{ old('admin_email', $adminUser?->email) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">
                    @error('admin_email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Password Baru (Opsional)</label>
                    <input type="password" name="admin_password" placeholder="Kosongkan jika tidak diubah" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">
                    @error('admin_password') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Submit Buttons --}}
        <div class="flex items-center justify-end space-x-3 pt-2">
            <a href="{{ route('admin.tuks.index') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs sm:text-sm font-semibold transition">
                Batal
            </a>
            <button type="submit" class="px-5 py-2.5 bg-lsp-navy hover:bg-slate-900 text-white rounded-lg text-xs sm:text-sm font-bold shadow-sm transition">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection
