@php
    $hasFilter = request()->filled('search') || request()->filled('scheme_id') || request()->filled('start_date') || request()->filled('end_date');
@endphp

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200">
        <h2 class="text-base font-bold text-slate-900">Semua Jadwal ({{ $schedules->total() ?? $schedules->count() }})</h2>
    </div>

    @if($schedules->isEmpty())
        <div class="p-8 text-center text-slate-500 text-sm">
            @if($hasFilter)
                Tidak ada jadwal yang sesuai dengan filter pencarian. <a href="{{ route('admin.jadwal.index') }}" data-ajax-reset="jadwal" class="text-brand-600 font-bold hover:underline">Reset filter</a>
            @else
                Belum ada jadwal yang dibuat. Klik tombol di atas untuk membuat jadwal baru.
            @endif
        </div>
    @else
        {{-- Mobile Card List (< md) --}}
        <div class="block md:hidden divide-y divide-slate-100">
            @foreach($schedules as $sch)
                <div class="p-4 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            <span class="font-mono text-xs font-bold text-brand-700 bg-brand-50 px-2 py-0.5 rounded border border-brand-200">
                                {{ $sch->code }}
                            </span>
                            @if($sch->is_closed)
                                <span class="text-[10px] font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                                    Ditutup
                                </span>
                            @else
                                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                    Aktif
                                </span>
                            @endif
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            {{ $sch->assessments_count ?? $sch->assessments->count() }} Asesi
                        </span>
                    </div>

                    <div>
                        <h3 class="font-bold text-slate-900 text-sm leading-snug">{{ $sch->name ?? $sch->scheme->name }}</h3>
                        <p class="text-xs text-brand-600 font-medium mt-0.5">{{ $sch->scheme->name }}</p>
                    </div>

                    <div class="text-xs text-slate-600 space-y-1.5 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-500">TUK: <strong class="text-slate-800">{{ $sch->tuk_name }}</strong></span>
                        </div>
                        <div class="text-[11px] text-slate-500 pt-1 border-t border-slate-200/60 flex items-center justify-between">
                            <span>{{ \Carbon\Carbon::parse($sch->date)->isoFormat('D MMM Y') }}</span>
                            <span class="text-slate-600 font-mono">{{ substr($sch->start_time, 0, 5) }} - {{ substr($sch->end_time, 0, 5) }} WIB</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 pt-1">
                        <a href="{{ route('admin.jadwal.show', $sch->id) }}" class="px-2.5 py-2 text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg text-center transition flex items-center justify-center gap-1 min-h-[40px]">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>Peserta</span>
                        </a>
                        <a href="{{ route('admin.jadwal.edit', $sch->id) }}" class="px-2.5 py-2 text-xs font-bold text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200 rounded-lg text-center transition flex items-center justify-center gap-1 min-h-[40px]">
                            <svg class="w-3.5 h-3.5 text-brand-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Edit</span>
                        </a>
                        <form action="{{ route('admin.jadwal.destroy', $sch->id) }}" method="POST" class="inline w-full"
                              data-confirm="PERINGATAN KONSEKUENSI PENGHAPUSAN JADWAL:

Jika jadwal memiliki peserta atau riwayat asesmen aktif, status jadwal akan dinonaktifkan (FINISHED) untuk melindungi data audit BNSP. Jika kosong, jadwal akan dihapus.

Apakah Anda yakin ingin melanjutkan?"
                              data-confirm-title="Konfirmasi Penghapusan Jadwal"
                              data-confirm-ok="Ya, Lanjutkan Hapus"
                              data-confirm-danger="true">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full px-2.5 py-2 text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg text-center transition flex items-center justify-center gap-1 min-h-[40px]">
                                <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                <span>Hapus</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Desktop Table (md+) --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3">Kode</th>
                        <th class="px-6 py-3">Nama Jadwal / Skema</th>
                        <th class="px-6 py-3">TUK</th>
                        <th class="px-6 py-3">Tanggal & Waktu</th>
                        <th class="px-6 py-3">Asesi</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($schedules as $sch)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4">
                                <div class="font-mono font-bold text-xs text-brand-700">{{ $sch->code }}</div>
                                @if($sch->is_closed)
                                    <span class="inline-flex items-center text-[10px] font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200 mt-1">
                                        Ditutup
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 mt-1">
                                        Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900">{{ $sch->name ?? $sch->scheme->name }}</div>
                                <div class="text-xs text-slate-500">{{ $sch->scheme->name }}</div>
                            </td>
                            <td class="px-6 py-4 text-slate-700">{{ $sch->tuk_name }}</td>
                            <td class="px-6 py-4 text-xs text-slate-600">
                                <div>{{ \Carbon\Carbon::parse($sch->date)->isoFormat('D MMMM Y') }}</div>
                                <div class="text-slate-400">{{ substr($sch->start_time, 0, 5) }} - {{ substr($sch->end_time, 0, 5) }} WIB</div>
                            </td>
                            <td class="px-6 py-4 text-xs font-semibold text-slate-700">
                                {{ $sch->assessments_count ?? $sch->assessments->count() }} Asesi
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.jadwal.show', $sch->id) }}" class="px-3 py-1 text-xs font-semibold text-emerald-700 hover:text-emerald-900 bg-emerald-50 rounded">
                                        Peserta
                                    </a>
                                    <a href="{{ route('admin.jadwal.edit', $sch->id) }}" class="px-3 py-1 text-xs font-semibold text-brand-600 hover:text-brand-800 bg-brand-50 rounded">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.jadwal.destroy', $sch->id) }}" method="POST" class="inline"
                                          data-confirm="PERINGATAN KONSEKUENSI PENGHAPUSAN JADWAL:

Jika jadwal memiliki peserta atau riwayat asesmen aktif, status jadwal akan dinonaktifkan (FINISHED) untuk melindungi data audit BNSP. Jika kosong, jadwal akan dihapus.

Apakah Anda yakin ingin melanjutkan?"
                                          data-confirm-title="Konfirmasi Penghapusan Jadwal"
                                          data-confirm-ok="Ya, Lanjutkan Hapus"
                                          data-confirm-danger="true">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1 text-xs font-semibold text-rose-600 hover:text-rose-800 bg-rose-50 rounded">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(method_exists($schedules, 'links'))
            <div class="p-4 border-t border-slate-200">
                {{ $schedules->links() }}
            </div>
        @endif
    @endif
</div>
