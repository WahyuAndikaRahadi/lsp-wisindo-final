@extends('layouts.app', ['title' => 'Verifikasi Fasilitas AJJ: ' . $tuk->name])

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{ activeModalImg: null }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.tuks.index') }}" class="hover:text-lsp-navy font-semibold">&larr; Kembali ke Daftar TUK</a>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Verifikasi Kelayakan AJJ</h1>
            <p class="text-xs sm:text-sm text-slate-500">Pemeriksaan bukti foto, video walkthrough, dan perlengkapan TUK sesuai SOP Wisindo Asesmen Jarak Jauh.</p>
        </div>
        <div>
            @if($tuk->verification_status === 'VERIFIED')
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    <svg class="w-3.5 h-3.5 mr-1.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    Terverifikasi AJJ
                </span>
            @elseif($tuk->verification_status === 'PENDING_VERIFICATION')
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 animate-pulse">
                    Menunggu Verifikasi Admin
                </span>
            @elseif($tuk->verification_status === 'REVISION')
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                    Perlu Revisi Dokumen
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                    Belum Diajukan
                </span>
            @endif
        </div>
    </div>

    {{-- Catatan Verifikasi Sebelumnya (Jika Ada) --}}
    @if($tuk->verification_notes)
        <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-xl">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-amber-500 mr-2 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <h3 class="text-xs font-bold text-amber-900 uppercase">Catatan Verifikasi Terakhir</h3>
                    <p class="text-xs text-amber-800 mt-1 whitespace-pre-line">{{ $tuk->verification_notes }}</p>
                    @if($tuk->verified_at)
                        <p class="text-[10px] text-amber-600 mt-1">Diverifikasi pada: {{ $tuk->verified_at->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Profil TUK Singkat --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-100">Profil &amp; Identitas TUK</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mt-3 text-xs">
            <div>
                <span class="text-slate-400 block">Nama TUK</span>
                <span class="font-bold text-slate-900 text-sm">{{ $tuk->name }}</span>
                <span class="font-mono text-[11px] text-slate-500 block">{{ $tuk->code }} ({{ $tuk->type }})</span>
            </div>
            <div>
                <span class="text-slate-400 block">Alamat</span>
                <span class="font-medium text-slate-800">{{ $tuk->address ?: '-' }}</span>
                <span class="text-slate-500 block">{{ $tuk->nama_kota ? $tuk->nama_kota . ', ' : '' }}{{ $tuk->nama_provinsi ?: '' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block">Penanggung Jawab (PIC)</span>
                <span class="font-bold text-slate-900">{{ $tuk->pic_name ?: '-' }}</span>
                <span class="text-slate-500 block">{{ $tuk->pic_position ?: 'Penanggung Jawab' }} &bull; {{ $tuk->pic_phone ?: '-' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block">Akun Admin TUK</span>
                @if($tuk->admins->isNotEmpty())
                    <span class="font-semibold text-slate-900">{{ $tuk->admins->first()->full_name }}</span>
                    <span class="text-slate-500 block font-mono">{{ $tuk->admins->first()->email }}</span>
                @else
                    <span class="text-amber-600 italic">Belum dibuat</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Bukti Fasilitas Asesmen Jarak Jauh (AJJ) --}}
    <div class="space-y-4">
        <h2 class="text-base font-black text-slate-900">Bukti Kelayakan Asesmen Jarak Jauh (AJJ)</h2>

        {{-- 1. Foto Ruang TUK --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">1. Foto Ruang Uji TUK</h3>
                    <p class="text-xs text-slate-500">Tampilan fisik ruangan, meja, dan layout tempat asesi melakukan asesmen (1-3 foto).</p>
                </div>
                <span class="text-xs font-mono font-bold px-2 py-0.5 rounded {{ count($tuk->room_photos ?? []) > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                    {{ count($tuk->room_photos ?? []) }} Foto
                </span>
            </div>

            @if(!empty($tuk->room_photos))
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach($tuk->room_photos as $idx => $photo)
                        <div class="group relative rounded-lg border border-slate-200 overflow-hidden bg-slate-100 aspect-video flex items-center justify-center cursor-pointer"
                             @click="activeModalImg = '{{ Storage::url($photo) }}'">
                            <img src="{{ Storage::url($photo) }}" alt="Foto Ruang TUK {{ $idx + 1 }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-200">
                            <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs font-bold">
                                <span>Lihat Penuh &rarr;</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-6 text-center text-slate-400 bg-slate-50 rounded-lg text-xs italic">
                    Belum ada foto ruang TUK yang diunggah.
                </div>
            @endif
        </div>

        {{-- 2. Foto Paket Data Internet & 3. Koneksi Internet --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Foto Paket Data Internet --}}
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">2. Foto Paket Data Internet</h3>
                        <p class="text-xs text-slate-500">Bukti kuota/paket data aktif cadangan (1-3 foto).</p>
                    </div>
                    <span class="text-xs font-mono font-bold px-2 py-0.5 rounded {{ count($tuk->internet_data_photos ?? []) > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                        {{ count($tuk->internet_data_photos ?? []) }} Foto
                    </span>
                </div>

                @if(!empty($tuk->internet_data_photos))
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($tuk->internet_data_photos as $idx => $photo)
                            <div class="group relative rounded-lg border border-slate-200 overflow-hidden bg-slate-100 aspect-video flex items-center justify-center cursor-pointer"
                                 @click="activeModalImg = '{{ Storage::url($photo) }}'">
                                <img src="{{ Storage::url($photo) }}" alt="Paket Data {{ $idx + 1 }}" class="w-full h-full object-cover group-hover:scale-105 transition">
                                <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-[11px] font-bold">
                                    Lihat &rarr;
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-slate-400 bg-slate-50 rounded-lg text-xs italic">
                        Belum ada foto paket data.
                    </div>
                @endif
            </div>

            {{-- Foto Koneksi Internet / Speedtest --}}
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">3. Foto Koneksi Internet</h3>
                        <p class="text-xs text-slate-500">Screenshot speedtest / router / status koneksi (1-3 foto).</p>
                    </div>
                    <span class="text-xs font-mono font-bold px-2 py-0.5 rounded {{ count($tuk->internet_connection_photos ?? []) > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                        {{ count($tuk->internet_connection_photos ?? []) }} Foto
                    </span>
                </div>

                @if(!empty($tuk->internet_connection_photos))
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($tuk->internet_connection_photos as $idx => $photo)
                            <div class="group relative rounded-lg border border-slate-200 overflow-hidden bg-slate-100 aspect-video flex items-center justify-center cursor-pointer"
                                 @click="activeModalImg = '{{ Storage::url($photo) }}'">
                                <img src="{{ Storage::url($photo) }}" alt="Koneksi {{ $idx + 1 }}" class="w-full h-full object-cover group-hover:scale-105 transition">
                                <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-[11px] font-bold">
                                    Lihat &rarr;
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-slate-400 bg-slate-50 rounded-lg text-xs italic">
                        Belum ada foto koneksi internet.
                    </div>
                @endif
            </div>
        </div>

        {{-- 4. Foto Peralatan / Perlengkapan Sesuai Skema --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">4. Foto Peralatan &amp; Perlengkapan TUK Sesuai Skema</h3>
                    <p class="text-xs text-slate-500">Perangkat komputer, headset/mic, printer, alat praktik kerja pariwisata/hotel (1-3 foto).</p>
                </div>
                <span class="text-xs font-mono font-bold px-2 py-0.5 rounded {{ count($tuk->equipment_photos ?? []) > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                    {{ count($tuk->equipment_photos ?? []) }} Foto
                </span>
            </div>

            @if(!empty($tuk->equipment_photos))
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach($tuk->equipment_photos as $idx => $photo)
                        <div class="group relative rounded-lg border border-slate-200 overflow-hidden bg-slate-100 aspect-video flex items-center justify-center cursor-pointer"
                             @click="activeModalImg = '{{ Storage::url($photo) }}'">
                            <img src="{{ Storage::url($photo) }}" alt="Peralatan {{ $idx + 1 }}" class="w-full h-full object-cover group-hover:scale-105 transition">
                            <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs font-bold">
                                <span>Lihat Penuh &rarr;</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-6 text-center text-slate-400 bg-slate-50 rounded-lg text-xs italic">
                    Belum ada foto peralatan yang diunggah.
                </div>
            @endif
        </div>

        {{-- 5. Video Ruang TUK (Walkthrough) --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900">5. Video Walkthrough Ruangan TUK</h3>
                <p class="text-xs text-slate-500">Video 360&deg; / keliling ruangan memperlihatkan sudut ruang uji dan bebas dari gangguan luar.</p>
            </div>

            @if($tuk->room_video_path)
                <div class="max-w-xl mx-auto rounded-xl overflow-hidden border border-slate-300 bg-black">
                    <video controls preload="metadata" class="w-full max-h-96">
                        <source src="{{ Storage::url($tuk->room_video_path) }}">
                        Browser Anda tidak mendukung tag video HTML5.
                    </video>
                </div>
            @else
                <div class="p-6 text-center text-slate-400 bg-slate-50 rounded-lg text-xs italic">
                    Belum ada video ruangan yang diunggah oleh TUK.
                </div>
            @endif
        </div>
    </div>

    {{-- Form Verifikasi & Pengesahan Skema --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-6">
        <h2 class="text-base font-black text-slate-900 pb-3 border-b border-slate-100 flex items-center space-x-2">
            <svg class="w-5 h-5 text-lsp-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Keputusan Verifikasi TUK Asesmen Jarak Jauh (AJJ)</span>
        </h2>

        {{-- Form Verifikasi (Setujui) --}}
        <form method="POST" action="{{ route('admin.tuks.verify', $tuk) }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">
                    Skema Sertifikasi yang Disetujui untuk TUK Ini: <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($allSchemes as $scheme)
                        <label class="flex items-start space-x-3 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer transition">
                            <input type="checkbox" name="scheme_ids[]" value="{{ $scheme->id }}"
                                   {{ in_array($scheme->id, old('scheme_ids', $tukSchemeIds)) ? 'checked' : '' }}
                                   class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <div class="font-bold text-slate-900 text-xs">{{ $scheme->name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">{{ $scheme->code }} &bull; {{ $scheme->units_count ?? $scheme->units()->count() }} Unit</div>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('scheme_ids') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Persetujuan Verifikasi (Opsional)</label>
                <textarea name="admin_notes" rows="2" placeholder="Telah diperiksa dan memenuhi persyaratan sarana dan prasarana AJJ BNSP..." class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs sm:text-sm focus:ring-1 focus:ring-emerald-500">{{ old('admin_notes') }}</textarea>
            </div>

            <div class="pt-2">
                <button type="submit" onclick="return confirm('Apakah Anda yakin menyetujui dan memverifikasi TUK ini untuk Asesmen Jarak Jauh?')" class="w-full sm:w-auto px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs sm:text-sm font-bold shadow-sm transition flex items-center justify-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Setujui &amp; Sahkan Verifikasi TUK (AJJ)</span>
                </button>
            </div>
        </form>

        {{-- Form Minta Revisi --}}
        <div class="pt-6 border-t border-slate-100">
            <h3 class="text-xs font-bold text-rose-700 uppercase tracking-wider mb-2">Atau Kembalikan untuk Revisi</h3>
            <form method="POST" action="{{ route('admin.tuks.reject-revision', $tuk) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Revisi untuk Admin TUK <span class="text-rose-500">*</span></label>
                    <textarea name="verification_notes" required rows="3" placeholder="Sebutkan berkas atau foto yang kurang jelas / perlu diganti..." class="w-full px-3 py-2 rounded-lg border border-rose-300 text-xs sm:text-sm focus:ring-1 focus:ring-rose-500">{{ old('verification_notes') }}</textarea>
                    @error('verification_notes') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <button type="submit" onclick="return confirm('Kirim instruksi revisi ke Admin TUK?')" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center space-x-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Minta Revisi Dokumen AJJ</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Preview Image Lightbox --}}
    <div x-show="activeModalImg" x-cloak class="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-4" @click.self="activeModalImg = null">
        <div class="relative max-w-4xl max-h-[90vh] bg-white rounded-xl overflow-hidden shadow-2xl">
            <button type="button" @click="activeModalImg = null" class="absolute top-3 right-3 bg-slate-900/70 hover:bg-slate-900 text-white p-2 rounded-full focus:outline-none z-10">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <img :src="activeModalImg" alt="Preview Gambar" class="max-h-[85vh] w-auto object-contain">
        </div>
    </div>
</div>
@endsection
