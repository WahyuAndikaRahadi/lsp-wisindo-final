@extends('layouts.app', ['title' => 'Kelola TUK & Verifikasi AJJ'])

@section('content')
<div class="space-y-5">
    {{-- Top Header Banner --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3.5">
        <div>
            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    SOP Wisindo AJJ
                </span>
                <span class="text-xs text-slate-300">&bull;</span>
                <span class="text-xs text-slate-500 font-medium">Asesmen Jarak Jauh</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1">Tempat Uji Kompetensi (TUK)</h1>
            <p class="text-xs sm:text-sm text-slate-500">Kelola master TUK, akun Admin TUK, dan verifikasi kelayakan fasilitas Asesmen Jarak Jauh (AJJ).</p>
        </div>
        <div class="flex items-center">
            <a href="{{ route('admin.tuks.create') }}" class="w-full sm:w-auto px-4 py-2.5 bg-lsp-navy hover:bg-slate-900 text-white rounded-lg text-xs sm:text-sm font-bold shadow-xs transition flex items-center justify-center space-x-2 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah TUK Baru</span>
            </a>
        </div>
    </div>

    {{-- Filter & Search Card --}}
    <div class="bg-white rounded-xl border border-slate-200 p-3.5 sm:p-4 shadow-xs space-y-3">
        <form method="GET" action="{{ route('admin.tuks.index') }}" class="flex flex-col md:flex-row items-stretch md:items-center gap-2.5">
            {{-- Search Input --}}
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama TUK, kode, alamat, atau nama PIC..."
                       class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-1 focus:ring-lsp-navy focus:border-lsp-navy">
            </div>

            {{-- Status Dropdown and Submit Button --}}
            <div class="flex items-center gap-2">
                <select name="status" onchange="this.form.submit()" class="flex-1 md:w-56 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs sm:text-sm text-slate-700 font-medium focus:outline-none focus:ring-1 focus:ring-lsp-navy">
                    <option value="">Semua Status Verifikasi</option>
                    <option value="PENDING_VERIFICATION" {{ request('status') === 'PENDING_VERIFICATION' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                    <option value="VERIFIED" {{ request('status') === 'VERIFIED' ? 'selected' : '' }}>Terverifikasi AJJ</option>
                    <option value="REVISION" {{ request('status') === 'REVISION' ? 'selected' : '' }}>Perlu Revisi</option>
                    <option value="UNSUBMITTED" {{ request('status') === 'UNSUBMITTED' ? 'selected' : '' }}>Belum Diajukan</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-lsp-navy hover:bg-slate-900 text-white rounded-lg text-xs sm:text-sm font-bold transition flex items-center justify-center gap-1.5 shadow-xs shrink-0">
                    <span>Cari</span>
                </button>

                @if(request()->filled('search') || request()->filled('status'))
                    <a href="{{ route('admin.tuks.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-semibold transition shrink-0" title="Reset filter">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        {{-- Quick Filter Horizontal Scrollable Bar --}}
        <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-1 pt-2 border-t border-slate-100 text-xs whitespace-nowrap">
            <span class="text-slate-400 font-medium text-[11px] mr-1 shrink-0">Quick Filter:</span>
            <a href="{{ route('admin.tuks.index') }}" class="px-2.5 py-1 rounded-md font-semibold shrink-0 transition {{ !request()->filled('status') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Semua
            </a>
            @php
                $pendingCount = \App\Models\Tuk::where('verification_status', 'PENDING_VERIFICATION')->count();
            @endphp
            <a href="{{ route('admin.tuks.index', ['status' => 'PENDING_VERIFICATION']) }}" class="px-2.5 py-1 rounded-md font-semibold shrink-0 transition flex items-center space-x-1.5 {{ request('status') === 'PENDING_VERIFICATION' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200/60' }}">
                <span>Menunggu Verifikasi</span>
                @if($pendingCount > 0)
                    <span class="inline-flex items-center justify-center px-1.5 py-0.2 {{ request('status') === 'PENDING_VERIFICATION' ? 'bg-white text-amber-900' : 'bg-amber-200 text-amber-900' }} rounded-full text-[10px] font-black">{{ $pendingCount }}</span>
                @endif
            </a>
            <a href="{{ route('admin.tuks.index', ['status' => 'VERIFIED']) }}" class="px-2.5 py-1 rounded-md font-semibold shrink-0 transition {{ request('status') === 'VERIFIED' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200/60' }}">
                Terverifikasi AJJ
            </a>
            <a href="{{ route('admin.tuks.index', ['status' => 'REVISION']) }}" class="px-2.5 py-1 rounded-md font-semibold shrink-0 transition {{ request('status') === 'REVISION' ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 text-rose-800 hover:bg-rose-100 border border-rose-200/60' }}">
                Perlu Revisi
            </a>
            <a href="{{ route('admin.tuks.index', ['status' => 'UNSUBMITTED']) }}" class="px-2.5 py-1 rounded-md font-semibold shrink-0 transition {{ request('status') === 'UNSUBMITTED' ? 'bg-slate-700 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Belum Diajukan
            </a>
        </div>
    </div>

    {{-- TUKs Container: Desktop Table (lg+) & Mobile/Tablet Cards (<lg) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        
        {{-- DESKTOP VIEW (TABLE FOR lg SCREENS AND UP) --}}
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] font-bold border-b border-slate-200">
                        <th class="py-3 px-4 min-w-[220px]">Kode &amp; Nama TUK</th>
                        <th class="py-3 px-4 min-w-[200px]">Tipe &amp; Lokasi</th>
                        <th class="py-3 px-4 min-w-[170px]">PIC / Narahubung</th>
                        <th class="py-3 px-4 min-w-[170px]">Skema Terverifikasi</th>
                        <th class="py-3 px-4 min-w-[140px] whitespace-nowrap">Status AJJ</th>
                        <th class="py-3 px-4 min-w-[140px] text-right whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($tuks as $tuk)
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            {{-- Kode & Nama TUK --}}
                            <td class="py-3.5 px-4 align-top">
                                <div class="font-bold text-slate-900 text-sm leading-snug">{{ $tuk->name }}</div>
                                <div class="flex items-center flex-wrap gap-1.5 mt-1">
                                    <span class="font-mono text-[11px] px-1.5 py-0.5 bg-slate-100 text-slate-700 rounded font-semibold">{{ $tuk->code }}</span>
                                    @if($tuk->admins->isNotEmpty())
                                        <span class="text-[11px] text-slate-500 truncate max-w-[180px]" title="Akun Login: {{ $tuk->admins->pluck('email')->join(', ') }}">
                                            &bull; {{ $tuk->admins->first()->email }}
                                        </span>
                                    @else
                                        <span class="text-[11px] text-amber-600 font-medium">&bull; Belum ada akun</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Tipe & Lokasi --}}
                            <td class="py-3.5 px-4 align-top">
                                <div>
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $tuk->type === 'MANDIRI' ? 'bg-purple-50 text-purple-700 border border-purple-200' : ($tuk->type === 'SEWAKTU' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-700') }}">
                                        {{ $tuk->type ?: 'SEWAKTU' }}
                                    </span>
                                </div>
                                @if($tuk->address)
                                    <div class="text-xs text-slate-600 mt-1 line-clamp-2" title="{{ $tuk->address }}">
                                        {{ $tuk->address }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        {{ $tuk->nama_kota ? $tuk->nama_kota . ', ' : '' }}{{ $tuk->nama_provinsi ?: '' }}
                                    </div>
                                @else
                                    <div class="text-[11px] text-slate-400 italic mt-1">
                                        Alamat belum dilengkapi TUK
                                    </div>
                                @endif
                            </td>

                            {{-- PIC / Narahubung --}}
                            <td class="py-3.5 px-4 align-top">
                                @if($tuk->pic_name || $tuk->pic_phone)
                                    <div class="font-semibold text-slate-800">{{ $tuk->pic_name ?: '-' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $tuk->pic_position ?: 'Penanggung Jawab' }}</div>
                                    @if($tuk->pic_phone)
                                        <div class="text-[11px] text-slate-600 font-mono mt-0.5">{{ $tuk->pic_phone }}</div>
                                    @endif
                                @else
                                    <div class="text-[11px] text-slate-400 italic">Belum ada narahubung</div>
                                @endif
                            </td>

                            {{-- Skema Terverifikasi --}}
                            <td class="py-3.5 px-4 align-top">
                                @php
                                    $verifiedSchemes = $tuk->schemes->filter(fn($s) => $s->pivot->is_verified);
                                @endphp
                                @if($verifiedSchemes->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 max-w-xs">
                                        @foreach($verifiedSchemes as $scheme)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200" title="{{ $scheme->name }}">
                                                {{ $scheme->code }}
                                            </span>
                                        @endforeach
                                    </div>
                                    <span class="text-[10px] text-slate-400 mt-1 block font-medium">{{ $verifiedSchemes->count() }} skema aktif</span>
                                @else
                                    <span class="text-[11px] text-slate-400 italic">Belum ada skema</span>
                                @endif
                            </td>

                            {{-- Status AJJ --}}
                            <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                @if($tuk->verification_status === 'VERIFIED')
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Terverifikasi AJJ</span>
                                    </span>
                                @elseif($tuk->verification_status === 'PENDING_VERIFICATION')
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        <svg class="w-3 h-3 text-amber-600 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Menunggu Review</span>
                                    </span>
                                @elseif($tuk->verification_status === 'REVISION')
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        <span>Perlu Revisi</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        <span>Belum Diajukan</span>
                                    </span>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="py-3.5 px-4 align-top text-right whitespace-nowrap">
                                <div class="flex items-center justify-end space-x-1.5">
                                    <a href="{{ route('admin.tuks.review', $tuk) }}" class="px-2.5 py-1.5 {{ $tuk->verification_status === 'PENDING_VERIFICATION' ? 'bg-amber-600 hover:bg-amber-700 text-white font-bold shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold' }} rounded text-xs transition" title="Review Berkas &amp; Fasilitas AJJ">
                                        Review AJJ
                                    </a>
                                    <a href="{{ route('admin.tuks.edit', $tuk) }}" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-semibold transition" title="Edit Data TUK">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.tuks.destroy', $tuk) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus TUK ini? Akun admin TUK terkait juga akan dihapus.')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded transition" title="Hapus TUK">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    <p class="text-sm font-semibold text-slate-600">Tidak ada Tempat Uji Kompetensi yang sesuai.</p>
                                    <p class="text-xs text-slate-400">Coba ubah kata kunci pencarian atau reset filter status.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- MOBILE & TABLET CARDS VIEW (FOR SCREENS < lg / 1024px) --}}
        <div class="block lg:hidden divide-y divide-slate-100">
            @forelse($tuks as $tuk)
                <div class="p-4 sm:p-5 space-y-3.5 hover:bg-slate-50/50 transition-colors">
                    {{-- Card Header: Type, Code, Status --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="font-mono text-xs px-2 py-0.5 bg-slate-100 text-slate-800 font-bold rounded border border-slate-200">
                                {{ $tuk->code }}
                            </span>
                            <span class="text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wider {{ $tuk->type === 'MANDIRI' ? 'bg-purple-50 text-purple-700 border border-purple-200' : ($tuk->type === 'SEWAKTU' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-700') }}">
                                {{ $tuk->type ?: 'SEWAKTU' }}
                            </span>
                        </div>
                        <div>
                            @if($tuk->verification_status === 'VERIFIED')
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1">
                                    <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    <span>Terverifikasi</span>
                                </span>
                            @elseif($tuk->verification_status === 'PENDING_VERIFICATION')
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200 flex items-center gap-1">
                                    <svg class="w-3 h-3 text-amber-600 animate-pulse" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                    <span>Menunggu Review</span>
                                </span>
                            @elseif($tuk->verification_status === 'REVISION')
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                    Perlu Revisi
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                    Belum Diajukan
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- TUK Name & Location --}}
                    <div>
                        <h3 class="font-bold text-slate-900 text-sm sm:text-base leading-snug">{{ $tuk->name }}</h3>
                        <div class="flex items-start gap-1.5 text-xs text-slate-600 mt-1">
                            <svg class="w-3.5 h-3.5 text-slate-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <div>
                                <span>{{ $tuk->address ? $tuk->address : 'Alamat belum dilengkapi TUK' }}</span>
                                @if($tuk->nama_kota || $tuk->nama_provinsi)
                                    <span class="text-slate-400 font-medium block text-[11px]">
                                        {{ $tuk->nama_kota ? $tuk->nama_kota . ', ' : '' }}{{ $tuk->nama_provinsi }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Details Info Box --}}
                    <div class="text-xs text-slate-600 space-y-2 bg-slate-50/90 p-3 rounded-lg border border-slate-100">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <span class="text-slate-500 font-medium">Akun Login:</span>
                            <span class="font-semibold text-slate-800 font-mono text-[11px] truncate max-w-[220px]">
                                {{ $tuk->admins->first()->email ?? 'Belum ada akun' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <span class="text-slate-500 font-medium">PIC / Narahubung:</span>
                            <span class="font-semibold text-slate-800 text-[11px]">
                                {{ $tuk->pic_name ?: '-' }} {{ $tuk->pic_phone ? "({$tuk->pic_phone})" : '' }}
                            </span>
                        </div>
                        <div class="pt-1.5 border-t border-slate-200/60">
                            <span class="text-slate-500 block mb-1 font-medium">Skema Terverifikasi:</span>
                            @php
                                $mobileVSchemes = $tuk->schemes->filter(fn($s) => $s->pivot->is_verified);
                            @endphp
                            @if($mobileVSchemes->isNotEmpty())
                                <div class="flex items-center gap-1 flex-wrap">
                                    @foreach($mobileVSchemes as $s)
                                        <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded text-[10px] font-bold border border-indigo-200">{{ $s->code }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-[11px] text-slate-400 italic">Belum ada skema terverifikasi</span>
                            @endif
                        </div>
                    </div>

                    {{-- Card Action Buttons --}}
                    <div class="flex items-center gap-2 pt-1">
                        <a href="{{ route('admin.tuks.review', $tuk) }}" class="flex-1 py-2 text-center {{ $tuk->verification_status === 'PENDING_VERIFICATION' ? 'bg-amber-600 hover:bg-amber-700 text-white font-bold shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold' }} rounded-lg text-xs transition min-h-[40px] flex items-center justify-center">
                            Review AJJ
                        </a>
                        <a href="{{ route('admin.tuks.edit', $tuk) }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition min-h-[40px] flex items-center justify-center">
                            Edit
                        </a>
                        <form method="POST" action="{{ route('admin.tuks.destroy', $tuk) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus TUK ini? Akun admin TUK terkait juga akan dihapus.')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg border border-slate-200 transition min-h-[40px] flex items-center justify-center" title="Hapus TUK">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-slate-400 text-xs">
                    <p class="font-medium text-slate-600">Tidak ada Tempat Uji Kompetensi yang sesuai.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($tuks->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                {{ $tuks->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
