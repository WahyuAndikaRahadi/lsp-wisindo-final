@extends('layouts.app', ['title' => 'Kelola Sertifikat Kompetensi'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Kelola Sertifikat Kompetensi</h1>
            <p class="text-sm text-slate-500">Penerbitan dan pengunggahan berkas fisik sertifikat BNSP untuk asesi yang dinyatakan Kompeten (K)</p>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
        <form action="{{ route('admin.certificates.index') }}" method="GET" class="flex flex-wrap items-center gap-3 text-xs">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama jadwal, kode, atau TUK..."
                    class="w-full text-xs rounded-lg border-slate-300 focus:ring-brand-500">
            </div>

            <div class="min-w-[200px]">
                <select name="scheme_id" class="w-full text-xs rounded-lg border-slate-300 focus:ring-brand-500 bg-white">
                    <option value="">-- Semua Skema --</option>
                    @foreach($schemes as $s)
                        <option value="{{ $s->id }}" {{ $schemeId == $s->id ? 'selected' : '' }}>
                            {{ $s->code }} - {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg transition">
                Filter Jadwal
            </button>

            @if($search || $schemeId)
                <a href="{{ route('admin.certificates.index') }}" class="px-3 py-2 text-slate-600 hover:bg-slate-100 rounded-lg">
                    Reset
                </a>
            @endif
        </form>
    </div>

    {{-- Schedules Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3">Jadwal Asesmen</th>
                        <th class="px-6 py-3">Skema Sertifikasi</th>
                        <th class="px-6 py-3">TUK / Tanggal</th>
                        <th class="px-6 py-3 text-center">Asesi Kompeten (K)</th>
                        <th class="px-6 py-3 text-center">Sertifikat Terunggah</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($schedules as $sch)
                        @php
                            $isComplete = ($sch->competent_candidates > 0 && $sch->uploaded_certificates_count >= $sch->competent_candidates);
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900">{{ $sch->name }}</div>
                                <span class="font-mono text-xs text-brand-700">{{ $sch->code }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-700">
                                <div class="font-semibold">{{ $sch->scheme->name ?? '-' }}</div>
                                <span class="text-slate-400 font-mono text-[11px]">{{ $sch->scheme->code ?? '' }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600">
                                <div>{{ $sch->tuk_name ?? ($sch->tuk->name ?? '-') }}</div>
                                <span class="text-slate-400">{{ \Carbon\Carbon::parse($sch->date)->isoFormat('D MMMM Y') }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 rounded text-xs font-bold {{ $sch->competent_candidates > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $sch->competent_candidates }} Asesi (K)
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($sch->competent_candidates == 0)
                                    <span class="text-xs text-slate-400">Belum ada asesi K</span>
                                @elseif($isComplete)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-300">
                                        ✓ Lengkap ({{ $sch->uploaded_certificates_count }}/{{ $sch->competent_candidates }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-300">
                                        {{ $sch->uploaded_certificates_count }} / {{ $sch->competent_candidates }} Terbit
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.certificates.schedule', $sch->id) }}" 
                                   class="px-3.5 py-1.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm transition inline-flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    <span>Upload &amp; Kelola Sertifikat</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500 text-xs">
                                Tidak ada jadwal asesmen yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($schedules->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $schedules->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
