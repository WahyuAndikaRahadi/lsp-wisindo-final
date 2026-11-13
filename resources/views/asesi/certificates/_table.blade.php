@if($certificates->isEmpty())
    {{-- Empty State --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm space-y-4">
        <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto border border-amber-200">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <div class="max-w-md mx-auto space-y-1">
            <h3 class="text-base font-bold text-slate-800">Belum Ada Sertifikat yang Diterbitkan</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                @if(request()->filled('search'))
                    Tidak ditemukan sertifikat dengan kata kunci pencarian "{{ request('search') }}". <a href="{{ route('asesi.certificates.index') }}" data-ajax-reset="asesi-certificates" class="text-brand-600 font-bold hover:underline">Reset</a>
                @else
                    Sertifikat akan muncul di sini setelah Anda dinyatakan Kompeten (K) dan berkas diterbitkan oleh Admin LSP.
                @endif
            </p>
        </div>
        <div class="pt-2">
            <a href="{{ route('asesi.jadwal.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-brand-700 bg-brand-50 hover:bg-brand-100 rounded-lg transition border border-brand-200">
                <span>Lihat Progres Jadwal Uji</span>
                <span>&rarr;</span>
            </a>
        </div>
    </div>
@else
    {{-- Certificates Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($certificates as $cert)
            @php
                $isExpired = $cert->isExpired();
            @endphp
            <div class="bg-white rounded-2xl border-2 {{ $isExpired ? 'border-slate-300' : 'border-emerald-500/80 shadow-md' }} overflow-hidden flex flex-col justify-between transition hover:shadow-lg">
                {{-- Card Header --}}
                <div class="p-5 {{ $isExpired ? 'bg-slate-100/70 border-b border-slate-200' : 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white' }}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] font-extrabold uppercase tracking-widest {{ $isExpired ? 'text-slate-500' : 'text-emerald-100' }}">
                            BNSP
                        </span>
                        @if($isExpired)
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-slate-200 text-slate-700">
                                Kadaluarsa
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 text-[10px] font-extrabold rounded-full bg-white/20 text-white backdrop-blur-xs border border-white/30 uppercase tracking-wider">
                                ✓ Aktif Berlaku
                            </span>
                        @endif
                    </div>
                    <h2 class="text-lg font-black tracking-tight mt-2 {{ $isExpired ? 'text-slate-800' : 'text-white' }}">
                        {{ $cert->scheme->name ?? 'Sertifikat Kompetensi' }}
                    </h2>
                    <p class="text-xs font-mono {{ $isExpired ? 'text-slate-500' : 'text-emerald-100' }} mt-0.5">
                        {{ $cert->scheme->code ?? '' }}
                    </p>
                </div>

                {{-- Card Body --}}
                <div class="p-5 space-y-4 text-xs flex-1">
                    <div class="grid grid-cols-2 gap-3 pb-3 border-b border-slate-100">
                        <div>
                            <span class="text-slate-400 block font-semibold">Nomor Sertifikat:</span>
                            <span class="font-mono font-bold text-slate-900 text-xs select-all">{{ $cert->certificate_number }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-semibold">Nomor Blanko BNSP:</span>
                            <span class="font-mono font-bold text-slate-900 text-xs select-all">{{ $cert->blanko_number }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-slate-600">
                        <div>
                            <span class="text-slate-400 block">Diterbitkan Pada:</span>
                            <span class="font-semibold text-slate-800">{{ \Carbon\Carbon::parse($cert->issued_date)->isoFormat('D MMMM Y') }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Berlaku Hingga:</span>
                            <span class="font-semibold text-slate-800">{{ \Carbon\Carbon::parse($cert->valid_until)->isoFormat('D MMMM Y') }}</span>
                        </div>
                    </div>

                    <div class="pt-2 text-slate-500 text-[11px]">
                        <span>Tempat Uji Kompetensi: <strong>{{ $cert->schedule->tuk_name ?? ($cert->schedule->tuk->name ?? 'TUK Mandiri') }}</strong></span>
                    </div>
                </div>

                {{-- Card Actions --}}
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5">
                    <button type="button"
                        @click="openPreview('{{ route('asesi.certificates.preview', $cert->id) }}', '{{ addslashes($cert->scheme->name ?? 'Sertifikat') }}')"
                        class="w-full sm:w-auto px-4 py-2.5 min-h-[40px] text-xs font-bold text-slate-700 bg-white hover:bg-slate-100 border border-slate-300 rounded-lg shadow-2xs transition flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <span>Preview Modal</span>
                    </button>

                    <a href="{{ route('asesi.certificates.download', $cert->id) }}"
                        class="w-full sm:w-auto px-4 py-2.5 min-h-[40px] text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Unduh PDF Resmi</span>
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    @if(method_exists($certificates, 'hasPages') && $certificates->hasPages())
        <div class="pt-4">
            {{ $certificates->links() }}
        </div>
    @endif
@endif
