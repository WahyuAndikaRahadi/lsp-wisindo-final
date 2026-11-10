@extends('layouts.app', ['title' => 'Kelola Jadwal Asesmen'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Kelola Jadwal Asesmen</h1>
            <p class="text-sm text-slate-500">Daftar semua jadwal asesmen sertifikasi BNSP</p>
        </div>
        <div>
            <a href="{{ route('admin.jadwal.create') }}" class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-sm font-bold shadow-sm transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Buat Jadwal Baru</span>
            </a>
        </div>
    </div>

    {{-- Filter & Search Bar --}}
    @php
        $hasFilter = request()->filled('search') || request()->filled('scheme_id') || request()->filled('start_date') || request()->filled('end_date');
    @endphp
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
        <form method="GET" action="{{ route('admin.jadwal.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            {{-- Search Bar: Nama Jadwal / Kode --}}
            <div class="lg:col-span-3">
                <label class="block text-xs font-bold text-slate-700 mb-1">Cari Jadwal</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau kode..."
                        class="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            {{-- Filter Skema --}}
            <div class="lg:col-span-3">
                <label class="block text-xs font-bold text-slate-700 mb-1">Skema Sertifikasi</label>
                <select name="scheme_id" class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-white">
                    <option value="">-- Semua Skema --</option>
                    @foreach($schemes ?? [] as $scheme)
                        <option value="{{ $scheme->id }}" {{ (string) request('scheme_id') === (string) $scheme->id ? 'selected' : '' }}>
                            {{ $scheme->code }} - {{ $scheme->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Rentang Tanggal Mulai --}}
            <div class="lg:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}"
                    class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>

            {{-- Rentang Tanggal Akhir --}}
            <div class="lg:col-span-2">
                <label class="block text-xs font-bold text-slate-700 mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}"
                    class="w-full py-2 px-3 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
            </div>

            {{-- Action Buttons --}}
            <div class="lg:col-span-2 flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 px-3.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-lg shadow-sm transition flex items-center justify-center gap-1.5 shrink-0" title="Terapkan Filter">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <span>Filter</span>
                </button>
                @if($hasFilter)
                    <a href="{{ route('admin.jadwal.index') }}" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-800 rounded-lg text-xs font-bold transition flex items-center justify-center border border-slate-200 shrink-0" title="Reset Filter">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Schedules Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-base font-bold text-slate-900">Semua Jadwal ({{ $schedules->total() ?? $schedules->count() }})</h2>
        </div>

        @if($schedules->isEmpty())
            <div class="p-8 text-center text-slate-500 text-sm">
                @if($hasFilter)
                    Tidak ada jadwal yang sesuai dengan filter pencarian. <a href="{{ route('admin.jadwal.index') }}" class="text-brand-600 font-bold hover:underline">Reset filter</a>
                @else
                    Belum ada jadwal yang dibuat. Klik tombol di atas untuk membuat jadwal baru.
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
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
                                <td class="px-6 py-4 font-mono font-bold text-xs text-brand-700">
                                    {{ $sch->code }}
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
</div>
@endsection
