@extends('layouts.app', ['title' => 'Unggah Sertifikat - ' . $schedule->name])

@section('content')
<div class="space-y-6 max-w-6xl mx-auto" x-data="{
    showUploadModal: false,
    showPreviewModal: false,
    previewUrl: '',
    previewTitle: '',

    formData: {
        assessment_id: null,
        asesi_name: '',
        certificate_number: '',
        blanko_number: '',
        issued_date: '{{ now()->toDateString() }}',
        valid_until: '{{ now()->addYears(3)->toDateString() }}',
        is_edit: false
    },

    openUpload(assessment, cert = null) {
        this.formData.assessment_id = assessment.id;
        this.formData.asesi_name = assessment.asesi ? assessment.asesi.full_name : 'Asesi';
        
        if (cert) {
            this.formData.certificate_number = cert.certificate_number;
            this.formData.blanko_number = cert.blanko_number;
            this.formData.issued_date = cert.issued_date.substring(0, 10);
            this.formData.valid_until = cert.valid_until.substring(0, 10);
            this.formData.is_edit = true;
        } else {
            this.formData.certificate_number = '';
            this.formData.blanko_number = '';
            this.formData.issued_date = '{{ now()->toDateString() }}';
            this.formData.valid_until = '{{ now()->addYears(3)->toDateString() }}';
            this.formData.is_edit = false;
        }
        this.showUploadModal = true;
    },

    openPreview(url, title) {
        this.previewUrl = url;
        this.previewTitle = title;
        this.showPreviewModal = true;
    }
}">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('admin.certificates.index') }}" class="text-xs text-brand-600 hover:text-brand-700 font-semibold">&larr; Kembali ke Daftar Jadwal Sertifikat</a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">Upload Sertifikat Kompetensi Asesi</h1>
            <p class="text-xs text-slate-500">Khusus asesi yang telah dinyatakan <strong>KOMPETEN (K)</strong> pada jadwal asesmen ini.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.jadwal.show', $schedule->id) }}" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">
                Lihat Jadwal &rarr;
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-900 flex items-center gap-3 shadow-sm">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <p class="text-sm font-semibold">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Schedule Details Card --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm text-xs">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <span class="text-slate-400 block font-semibold">Nama Jadwal:</span>
                <span class="font-bold text-slate-900 text-sm">{{ $schedule->name }}</span>
                <span class="block font-mono text-[11px] text-brand-700">{{ $schedule->code }}</span>
            </div>
            <div>
                <span class="text-slate-400 block font-semibold">Skema Sertifikasi:</span>
                <span class="font-bold text-slate-900">{{ $schedule->scheme->name ?? '-' }}</span>
                <span class="block font-mono text-[11px] text-slate-500">{{ $schedule->scheme->code ?? '' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block font-semibold">TUK / Pelaksanaan:</span>
                <span class="font-bold text-slate-900">{{ $schedule->tuk_name ?? ($schedule->tuk->name ?? '-') }}</span>
                <span class="block text-slate-500">{{ \Carbon\Carbon::parse($schedule->date)->isoFormat('dddd, D MMMM Y') }}</span>
            </div>
            <div>
                <span class="text-slate-400 block font-semibold">Total Asesi Kompeten (K):</span>
                <span class="font-bold text-emerald-700 text-sm">{{ $competentAssessments->count() }} Asesi</span>
            </div>
        </div>
    </div>

    {{-- Competent Candidates Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">
                Daftar Asesi Kompeten Siap Terbit Sertifikat ({{ $competentAssessments->count() }} Orang)
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-100 text-left text-slate-600 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-center w-12">No</th>
                        <th class="px-4 py-3">Nama Asesi &amp; NIK</th>
                        <th class="px-4 py-3 text-center">Keputusan</th>
                        <th class="px-4 py-3">Status Sertifikat</th>
                        <th class="px-4 py-3">No. Sertifikat &amp; Blanko</th>
                        <th class="px-4 py-3">Masa Berlaku</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($competentAssessments as $index => $asm)
                        @php
                            $cert = $asm->certificate;
                            $asesi = $asm->asesi;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-4 py-3 text-center font-mono text-slate-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-900">{{ $asesi?->full_name ?? '-' }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">
                                    NIK: {{ $asesi?->profile?->nik ?? '-' }} &bull; {{ $asesi?->email }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    ✓ KOMPETEN
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($cert)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full font-bold bg-emerald-50 text-emerald-700 border border-emerald-300">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Telah Terbit
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full font-bold bg-amber-50 text-amber-700 border border-amber-300">
                                        Belum Diunggah
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($cert)
                                    <div class="font-mono font-bold text-slate-900">{{ $cert->certificate_number }}</div>
                                    <div class="text-[11px] font-mono text-slate-500">Blanko: {{ $cert->blanko_number }}</div>
                                @else
                                    <span class="text-slate-400 italic">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($cert)
                                    <div class="text-slate-800">Dibuat: {{ \Carbon\Carbon::parse($cert->issued_date)->isoFormat('D MMM Y') }}</div>
                                    <div class="text-[11px] text-slate-500">Berlaku s/d: {{ \Carbon\Carbon::parse($cert->valid_until)->isoFormat('D MMM Y') }}</div>
                                @else
                                    <span class="text-slate-400 italic">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-1.5">
                                    @if($cert)
                                        <button type="button" 
                                            @click="openPreview('{{ route('admin.certificates.preview', $cert->id) }}', 'Sertifikat - {{ addslashes($asesi?->full_name) }}')"
                                            class="px-2 py-1 text-slate-700 bg-slate-100 hover:bg-slate-200 rounded font-semibold transition" title="Preview PDF">
                                            Preview
                                        </button>
                                        <a href="{{ route('admin.certificates.download', $cert->id) }}" 
                                            class="px-2 py-1 text-brand-700 bg-brand-50 hover:bg-brand-100 rounded font-semibold transition" title="Unduh PDF">
                                            Unduh
                                        </a>
                                        <button type="button" 
                                            @click="openUpload({{ json_encode($asm) }}, {{ json_encode($cert) }})"
                                            class="px-2 py-1 text-amber-700 bg-amber-50 hover:bg-amber-100 rounded font-semibold transition">
                                            Edit
                                        </button>
                                        <form action="{{ route('admin.certificates.destroy', $cert->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus sertifikat ini?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1 text-rose-600 bg-rose-50 hover:bg-rose-100 rounded font-semibold transition">
                                                Hapus
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" 
                                            @click="openUpload({{ json_encode($asm) }})"
                                            class="px-3 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                            <span>Unggah Sertifikat (PDF)</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-500 text-xs">
                                Tidak ada asesi yang berstatus KOMPETEN (K) pada jadwal asesmen ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL UPLOAD / EDIT SERTIFIKAT --}}
    <div x-show="showUploadModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(15, 23, 42, 0.55);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.away="showUploadModal = false">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900" x-text="formData.is_edit ? 'Perbarui Sertifikat Kompetensi' : 'Unggah Sertifikat Kompetensi Resmi'"></h3>
                    <button type="button" @click="showUploadModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                </div>

                <form action="{{ route('admin.certificates.store', $schedule->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                    @csrf
                    <input type="hidden" name="assessment_id" :value="formData.assessment_id">

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <span class="text-slate-500 block">Nama Asesi (Pemegang Sertifikat):</span>
                        <span class="font-bold text-sm text-slate-900" x-text="formData.asesi_name"></span>
                        <span class="block text-slate-500 mt-0.5">Skema: <strong>{{ $schedule->scheme->name ?? '-' }}</strong></span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nomor Sertifikat <span class="text-rose-500">*</span></label>
                            <input type="text" name="certificate_number" required x-model="formData.certificate_number"
                                placeholder="Contoh: No. 74100 2412 0000001 2026"
                                class="w-full px-3.5 py-2 text-xs font-mono rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nomor Blanko BNSP <span class="text-rose-500">*</span></label>
                            <input type="text" name="blanko_number" required x-model="formData.blanko_number"
                                placeholder="Contoh: BL-2026-004512"
                                class="w-full px-3.5 py-2 text-xs font-mono rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Dibuat Pada (Tanggal Terbit) <span class="text-rose-500">*</span></label>
                            <input type="date" name="issued_date" required x-model="formData.issued_date"
                                class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Masa Berlaku Hingga <span class="text-rose-500">*</span></label>
                            <input type="date" name="valid_until" required x-model="formData.valid_until"
                                class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                            <span class="text-[10px] text-slate-400 mt-0.5 block">Standar BNSP: 3 Tahun</span>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            Berkas Sertifikat PDF Resmi <span x-show="!formData.is_edit" class="text-rose-500">*</span>
                        </label>
                        <input type="file" name="certificate_file" accept="application/pdf" :required="!formData.is_edit"
                            class="w-full text-xs rounded-xl border border-slate-300 bg-white p-1.5 text-slate-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 transition shadow-2xs">
                        <span class="text-[11px] text-slate-400 mt-1 block">Format file: Dokumen PDF (Maksimal 10 MB).</span>
                    </div>

                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="showUploadModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-sm">
                            Simpan &amp; Terbitkan Sertifikat &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL PREVIEW PDF --}}
    <div x-show="showPreviewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(15, 23, 42, 0.75);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl max-w-4xl w-full p-5 shadow-2xl space-y-3" @click.away="showPreviewModal = false">
                <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span x-text="previewTitle"></span>
                    </h3>
                    <button type="button" @click="showPreviewModal = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
                </div>

                <div class="w-full bg-slate-100 rounded-lg overflow-hidden border border-slate-200" style="height: 70vh;">
                    <iframe :src="previewUrl" class="w-full h-full border-0"></iframe>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <span class="text-[11px] text-slate-500">Preview Dokumen PDF Resmi LSP PrO Indo / BNSP</span>
                    <button type="button" @click="showPreviewModal = false" class="px-4 py-1.5 text-xs font-semibold bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-lg">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
