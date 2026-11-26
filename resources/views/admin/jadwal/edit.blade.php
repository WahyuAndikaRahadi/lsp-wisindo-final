@extends('layouts.app', ['title' => 'Edit Jadwal: ' . $jadwal->name])

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <div>
        <a href="{{ route('admin.jadwal.show', $jadwal->id) }}" class="text-xs text-lsp-navy hover:underline font-semibold">&larr; Kembali ke Detail Jadwal</a>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">Edit Jadwal Asesmen (AJJ)</h1>
        <p class="text-sm text-slate-500">Perbarui data pelaksanaan asesmen, TUK, atau link video meeting AJJ.</p>
    </div>

    <form action="{{ route('admin.jadwal.update', $jadwal->id) }}" method="POST" class="space-y-6" x-data="{
        selectedSchemeId: '{{ old('scheme_id', $jadwal->scheme_id) }}',
        selectedTukId: '{{ old('tuk_id', $jadwal->tuk_id) }}',
        showManualTuk: false,
        verifiedTuks: {{ Js::from($verifiedTuks ?? []) }},
        selectedProvince: '{{ old('kode_provinsi', $jadwal->kode_provinsi ?? '32') }}',
        selectedCity: '{{ old('kode_kota', $jadwal->kode_kota ?? '') }}',

        get availableTuks() {
            if (!this.selectedSchemeId) return [];
            return this.verifiedTuks.filter(t => {
                return t.schemes && t.schemes.some(s => String(s.id) === String(this.selectedSchemeId));
            });
        },
        get currentTuk() {
            return this.verifiedTuks.find(t => String(t.id) === String(this.selectedTukId));
        },
        get filteredCities() {
            if (!this.selectedProvince) return [];
            return this.allCities.filter(c => String(c.province_code) === String(this.selectedProvince));
        },
        onSchemeChange(schemeId) {
            this.selectedSchemeId = schemeId;
            if (this.selectedTukId && !this.availableTuks.some(t => String(t.id) === String(this.selectedTukId))) {
                this.selectedTukId = '';
            }
        },
        onTukChange(tukId) {
            this.selectedTukId = tukId;
        }
    }">
        @csrf
        @method('PUT')

        {{-- Basic Information & Scheme / TUK Link --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-5">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900">1. Skema &amp; TUK Asesmen Jarak Jauh (AJJ)</h2>
                <span class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    SOP AJJ Wisindo
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Nama Jadwal Asesmen <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" required value="{{ old('name', $jadwal->name) }}" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">
                    @error('name')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Skema Sertifikasi --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Skema Sertifikasi <span class="text-rose-500">*</span>
                    </label>
                    <select name="scheme_id" required @change="onSchemeChange($event.target.value)" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs sm:text-sm font-semibold focus:ring-1 focus:ring-lsp-navy bg-white">
                        @foreach($schemes ?? [] as $scheme)
                            <option value="{{ $scheme->id }}" {{ old('scheme_id', $jadwal->scheme_id) == $scheme->id ? 'selected' : '' }}>
                                {{ $scheme->name }} ({{ $scheme->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('scheme_id')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Dropdown Pemilihan TUK Terverifikasi Berdasarkan Skema --}}
            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200 space-y-3">
                <div>
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-1">
                        Tempat Uji Kompetensi (TUK Terverifikasi AJJ)
                    </label>
                    <select name="tuk_id" @change="onTukChange($event.target.value)"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs sm:text-sm font-semibold focus:ring-1 focus:ring-lsp-navy bg-white">
                        <option value="">-- Tetap Gunakan Data TUK Saat Ini / Pilih TUK Baru --</option>
                        <template x-for="tuk in availableTuks" :key="tuk.id">
                            <option :value="tuk.id" :selected="String(tuk.id) === String(selectedTukId)"
                                    x-text="tuk.name + ' (' + tuk.type + ' - ' + (tuk.nama_kota || tuk.address) + ')'"></option>
                        </template>
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Pilih TUK terverifikasi untuk memperbarui data alamat, wilayah, dan PIC TUK secara otomatis.
                    </p>
                </div>

                {{-- TUK Info Card when selected --}}
                {{-- TUK Info Card when selected (Data automatically linked from TUK profile) --}}
                <div x-show="currentTuk" x-cloak class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                TUK TERVERIFIKASI
                            </span>
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900" x-text="currentTuk?.name"></h4>
                            <span class="text-xs font-mono text-slate-500" x-text="'(' + currentTuk?.code + ')'"></span>
                        </div>
                        <span class="text-[11px] text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded font-semibold border border-emerald-200">
                            Terhubung Otomatis
                        </span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs text-slate-600 pt-2 border-t border-slate-200">
                        <div>
                            <strong class="text-slate-700 block text-[11px] uppercase tracking-wider mb-0.5">Alamat Lengkap:</strong>
                            <span x-text="currentTuk?.address || '-'" class="leading-relaxed"></span>
                        </div>
                        <div>
                            <strong class="text-slate-700 block text-[11px] uppercase tracking-wider mb-0.5">Wilayah:</strong>
                            <span x-text="(currentTuk?.nama_kota ? currentTuk?.nama_kota + ', ' : '') + (currentTuk?.nama_provinsi || '-')"></span>
                        </div>
                        <div>
                            <strong class="text-slate-700 block text-[11px] uppercase tracking-wider mb-0.5">Penanggung Jawab (PIC):</strong>
                            <span x-text="(currentTuk?.pic_name || '-') + ' (' + (currentTuk?.pic_position || 'Kepala TUK') + ')'"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Input Link Video Meeting AJJ (Zoom / Google Meet) --}}
            <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-200/80 space-y-2">
                <div class="flex items-center space-x-2">
                    <span class="p-1.5 rounded-lg bg-indigo-600 text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </span>
                    <div>
                        <label class="block text-xs font-bold text-indigo-950 uppercase tracking-wider">
                            Link Meeting AJJ (Zoom / Google Meet)
                        </label>
                        <p class="text-[11px] text-indigo-700">Link pertemuan online video conference untuk pelaksanaan Asesmen Jarak Jauh.</p>
                    </div>
                </div>
                <input type="url" name="meeting_link" value="{{ old('meeting_link', $jadwal->meeting_link) }}"
                       placeholder="https://us02web.zoom.us/j/... atau https://meet.google.com/..."
                       class="w-full px-3.5 py-2.5 rounded-lg border border-indigo-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy font-mono bg-white">
                <p class="text-[11px] text-slate-500">
                    &bull; Link ini akan otomatis muncul sebagai tombol <strong>"Masuk Ruang Zoom / Meet AJJ"</strong> di akun Asesi, Asesor, dan Admin TUK.
                </p>
                @error('meeting_link')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Asesor yang Ditugaskan --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Asesor yang Ditugaskan (Multi-select) <span class="text-rose-500">*</span>
                </label>
                @php
                    $selectedAssessorIds = old('assessor_ids', $jadwal->assessors->pluck('id')->toArray());
                @endphp
                <div class="border border-slate-200 rounded-lg p-3 max-h-44 overflow-y-auto space-y-1.5 bg-slate-50">
                    @forelse($assessors ?? [] as $asesor)
                        <label class="flex items-center justify-between text-xs text-slate-700 cursor-pointer hover:bg-white p-2 rounded-lg border border-transparent hover:border-slate-200 transition">
                            <div class="flex items-center space-x-2.5 min-w-0">
                                <input type="checkbox" name="assessor_ids[]" value="{{ $asesor->id }}" class="rounded border-slate-300 text-lsp-navy focus:ring-lsp-navy shrink-0" {{ in_array($asesor->id, $selectedAssessorIds) ? 'checked' : '' }}>
                                <div class="min-w-0">
                                    <span class="font-semibold text-slate-800">{{ $asesor->full_name }}</span>
                                    <span class="text-slate-400 font-mono text-[11px] block sm:inline sm:ml-1">{{ $asesor->assessor_registration_number ? "({$asesor->assessor_registration_number})" : "({$asesor->email})" }}</span>
                                </div>
                            </div>
                        </label>
                    @empty
                        <p class="text-xs text-slate-400">Tidak ada data asesor terdaftar.</p>
                    @endforelse
                </div>
                @error('assessor_ids')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Date and Time --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900 pb-2 border-b border-slate-100">2. Jadwal Pelaksanaan &amp; Kuota Peserta</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Mulai <span class="text-rose-500">*</span></label>
                        <input type="date" name="date" required value="{{ old('date', \Carbon\Carbon::parse($jadwal->date)->format('Y-m-d')) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">
                        @error('date') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Selesai</label>
                        <input type="date" name="end_date" value="{{ old('end_date', $jadwal->end_date ? \Carbon\Carbon::parse($jadwal->end_date)->format('Y-m-d') : '') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">
                        @error('end_date') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jam Mulai <span class="text-rose-500">*</span></label>
                        <input type="time" name="start_time" required value="{{ old('start_time', substr($jadwal->start_time ?? '08:00', 0, 5)) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">
                        @error('start_time') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jam Selesai <span class="text-rose-500">*</span></label>
                        <input type="time" name="end_time" required value="{{ old('end_time', substr($jadwal->end_time ?? '16:00', 0, 5)) }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">
                        @error('end_time') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Perkiraan Kuota Peserta</label>
                    <input type="number" name="estimated_candidates" min="0" value="{{ old('estimated_candidates', $jadwal->estimated_candidates ?? 0) }}" placeholder="Contoh: 20" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">
                    @error('estimated_candidates') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Schedule Code & Notes --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900 pb-2 border-b border-slate-100">3. Kode Masuk &amp; Catatan</h2>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Jadwal</label>
                <input type="text" readonly value="{{ $jadwal->code }}" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs sm:text-sm font-mono uppercase bg-slate-100 text-slate-600 cursor-not-allowed">
                <p class="text-[11px] text-slate-400 mt-1">Kode jadwal bersifat unik dan tidak dapat diubah setelah dibuat.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan</label>
                <textarea name="notes" rows="3" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-lsp-navy">{{ old('notes', $jadwal->notes) }}</textarea>
                @error('notes') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.jadwal.show', $jadwal->id) }}" class="px-5 py-2.5 text-xs sm:text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">Batal</a>
            <button type="submit" class="px-6 py-2.5 text-xs sm:text-sm font-bold bg-lsp-navy hover:bg-slate-900 text-white rounded-lg shadow-sm transition">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection
