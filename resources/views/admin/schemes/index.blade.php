@extends('layouts.app', ['title' => 'Daftar Skema Sertifikasi'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Skema Sertifikasi</h1>
            <p class="text-sm text-slate-500">Daftar skema sertifikasi kompetensi standar BNSP</p>
        </div>
        <div>
            <a href="{{ route('admin.schemes.create') }}" class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-sm font-bold shadow-sm transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Skema</span>
            </a>
        </div>
    </div>

    {{-- Schemes List (Responsive: Cards on Mobile, Table on Desktop) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        {{-- Mobile Cards (< md) --}}
        <div class="block md:hidden divide-y divide-slate-100">
            @forelse($schemes ?? [] as $scheme)
                <div class="p-4 space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-mono text-xs font-bold text-brand-700 bg-brand-50 px-2 py-0.5 rounded border border-brand-200">
                            {{ $scheme->code }}
                        </span>
                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Aktif
                        </span>
                    </div>

                    <div>
                        <h3 class="font-bold text-slate-900 text-sm leading-snug">{{ $scheme->name }}</h3>
                        @if(isset($scheme->description))
                            <p class="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed">{{ $scheme->description }}</p>
                        @endif
                    </div>

                    <div class="flex items-center justify-between text-xs bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                        <span class="text-slate-600 font-medium">Bidang: <strong>{{ $scheme->category ?? 'Otomotif' }}</strong></span>
                        <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-white text-slate-700 border border-slate-200">
                            {{ $scheme->competency_units_count ?? $scheme->units_count ?? ($scheme->competencyUnits ? $scheme->competencyUnits->count() : 0) }} Unit
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <a href="{{ route('admin.schemes.show', $scheme->id) }}" class="px-3 py-2 text-xs font-bold text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200 rounded-lg text-center transition flex items-center justify-center min-h-[38px]">
                            Detail Unit
                        </a>
                        <a href="{{ route('admin.schemes.edit', $scheme->id) }}" class="px-3 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded-lg text-center transition flex items-center justify-center min-h-[38px]">
                            Edit Skema
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-slate-500 text-xs">Belum ada skema sertifikasi yang dibuat.</div>
            @endforelse
        </div>

        {{-- Desktop Table (md+) --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3">Kode Skema</th>
                        <th class="px-6 py-3">Nama Skema</th>
                        <th class="px-6 py-3">Kategori / Bidang</th>
                        <th class="px-6 py-3 text-center">Unit Kompetensi</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($schemes ?? [] as $scheme)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4 font-mono font-bold text-xs text-brand-700">
                                {{ $scheme->code }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900">{{ $scheme->name }}</div>
                                @if(isset($scheme->description))
                                    <div class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $scheme->description }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600">
                                {{ $scheme->category ?? 'Otomotif' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700">
                                    {{ $scheme->competency_units_count ?? $scheme->units_count ?? ($scheme->competencyUnits ? $scheme->competencyUnits->count() : 0) }} Unit
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Aktif
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.schemes.show', $scheme->id) }}" class="px-2.5 py-1 text-xs font-semibold text-brand-600 hover:text-brand-800 bg-brand-50 rounded">
                                        Detail
                                    </a>
                                    <a href="{{ route('admin.schemes.edit', $scheme->id) }}" class="px-2.5 py-1 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-slate-100 rounded">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500 text-xs">Belum ada skema sertifikasi yang dibuat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($schemes) && method_exists($schemes, 'links'))
            <div class="p-4 border-t border-slate-200">
                {{ $schemes->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
