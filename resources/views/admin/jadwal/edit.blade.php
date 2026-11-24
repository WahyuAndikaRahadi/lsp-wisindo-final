@extends('layouts.app', ['title' => 'Edit Jadwal Asesmen - ' . $jadwal->code])

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <div>
        <a href="{{ route('admin.jadwal.show', $jadwal->id) }}" class="text-xs text-brand-600 hover:text-brand-700 font-semibold">&larr; Kembali ke Detail Jadwal</a>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">Edit Jadwal Asesmen</h1>
        <p class="text-sm text-slate-500">Perbarui data pelaksanaan asesmen untuk kode <span class="font-mono font-bold text-brand-700">{{ $jadwal->code }}</span></p>
    </div>

    <form action="{{ route('admin.jadwal.update', $jadwal->id) }}" method="POST" class="space-y-6" x-data="{
        code: '{{ old('code', $jadwal->code) }}'
    }">
        @csrf
        @method('PUT')

        {{-- Basic Information --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900">Informasi Dasar Jadwal</h2>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Jadwal <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required value="{{ old('name', $jadwal->name) }}" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                @error('name')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Skema Sertifikasi <span class="text-rose-500">*</span></label>
                    <select name="scheme_id" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none bg-white">
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

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Tempat Uji Kompetensi (Nama TUK) <span class="text-rose-500">*</span></label>
                    <input type="text" name="tuk_name" required value="{{ old('tuk_name', $jadwal->tuk_name) }}" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('tuk_name')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Alamat TUK</label>
                <input type="text" name="tuk_address" value="{{ old('tuk_address', $jadwal->tuk_address) }}" placeholder="Contoh: Jl. Umar Wirahadikusumah KM. 25 Darmaraja" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                <p class="text-xs text-slate-400 mt-1">Dicantumkan pada lembar Surat Tugas Asesor.</p>
                @error('tuk_address')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Asesor yang Ditugaskan (Multi-select) <span class="text-rose-500">*</span></label>
                @php
                    $selectedAssessorIds = old('assessor_ids', $jadwal->assessors->pluck('id')->toArray());
                @endphp
                <div class="border border-slate-200 rounded-lg p-3 max-h-40 overflow-y-auto space-y-1.5">
                    @forelse($assessors ?? [] as $asesor)
                        <label class="flex items-center space-x-2.5 text-sm text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded">
                            <input type="checkbox" name="assessor_ids[]" value="{{ $asesor->id }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500" {{ in_array($asesor->id, $selectedAssessorIds) ? 'checked' : '' }}>
                            <span>{{ $asesor->full_name }}</span>
                            <span class="text-xs text-slate-400">({{ $asesor->email }})</span>
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
            <h2 class="text-base font-bold text-slate-900">Jadwal Pelaksanaan & Kuota Peserta</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Tanggal Mulai <span class="text-rose-500">*</span></label>
                    <input type="date" name="date" required value="{{ old('date', \Carbon\Carbon::parse($jadwal->date)->format('Y-m-d')) }}" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('date')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Tanggal Selesai</label>
                    <input type="date" name="end_date" value="{{ old('end_date', $jadwal->end_date ? \Carbon\Carbon::parse($jadwal->end_date)->format('Y-m-d') : '') }}" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <p class="text-xs text-slate-400 mt-1">Kosongkan jika jadwal hanya 1 hari.</p>
                    @error('end_date')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Jam Mulai <span class="text-rose-500">*</span></label>
                    <input type="time" name="start_time" required value="{{ old('start_time', substr($jadwal->start_time ?? '08:00', 0, 5)) }}" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('start_time')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Jam Selesai <span class="text-rose-500">*</span></label>
                    <input type="time" name="end_time" required value="{{ old('end_time', substr($jadwal->end_time ?? '16:00', 0, 5)) }}" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('end_time')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100">
                <label class="block text-sm font-semibold text-slate-700 mb-1">Perkiraan Jumlah Peserta (Siswa)</label>
                <div class="max-w-xs">
                    <input type="number" name="estimated_candidates" min="0" value="{{ old('estimated_candidates', $jadwal->estimated_candidates ?? 0) }}" placeholder="Contoh: 88" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>
                <p class="text-xs text-slate-500 mt-1">Dicantumkan pada Surat Tugas Asesor (contoh: <em>88 siswa</em>). Jika diisi 0/kosong, sistem akan otomatis mencantumkan jumlah riil asesi terdaftar.</p>
                @error('estimated_candidates')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Surat Tugas Asesor --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Kelengkapan Surat Tugas Asesor</h2>
                    <p class="text-xs text-slate-500">Konfigurasi dokumen resmi penugasan asesor untuk jadwal ini</p>
                </div>
                <a href="{{ route('admin.jadwal.surat-tugas', $jadwal->id) }}" target="_blank" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 transition inline-flex items-center gap-1">
                    <span>Lihat Pratinjau Dokumen &rarr;</span>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nomor Surat Tugas</label>
                    <input type="text" name="surat_tugas_number" value="{{ old('surat_tugas_number', $jadwal->surat_tugas_number) }}" placeholder="Contoh: 022/ST/PRO-INDO/INT/IX/2026" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none font-mono">
                    <p class="text-xs text-slate-400 mt-1">Biarkan kosong agar sistem membuat nomor otomatis standar.</p>
                    @error('surat_tugas_number')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Tanggal Penerbitan Surat Tugas</label>
                    <input type="date" name="surat_tugas_date" value="{{ old('surat_tugas_date', $jadwal->surat_tugas_date ? \Carbon\Carbon::parse($jadwal->surat_tugas_date)->format('Y-m-d') : '') }}" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <p class="text-xs text-slate-400 mt-1">Default otomatis mengikuti tanggal jadwal jika dikosongkan.</p>
                    @error('surat_tugas_date')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Kota Penerbitan Surat</label>
                    <input type="text" name="letter_city" value="{{ old('letter_city', $jadwal->letter_city ?? 'Bekasi') }}" placeholder="Contoh: Bekasi" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('letter_city')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Direktur LSP (Penandatangan)</label>
                    <input type="text" name="director_name" value="{{ old('director_name', $jadwal->director_name ?? 'RIDWAN') }}" placeholder="Contoh: RIDWAN" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('director_name')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Schedule Code & Notes --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900">Kode Jadwal & Catatan</h2>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Kode Jadwal</label>
                <input type="text" readonly value="{{ $jadwal->code }}" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm font-mono uppercase bg-slate-100 text-slate-600 cursor-not-allowed">
                <p class="text-xs text-slate-400 mt-1">Kode jadwal bersifat unik dan tidak dapat diubah setelah dibuat.</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Catatan Tambahan</label>
                <textarea name="notes" rows="3" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">{{ old('notes', $jadwal->notes) }}</textarea>
                @error('notes')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.jadwal.show', $jadwal->id) }}" class="px-5 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">Batal</a>
            <button type="submit" class="px-6 py-2.5 text-sm font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection
