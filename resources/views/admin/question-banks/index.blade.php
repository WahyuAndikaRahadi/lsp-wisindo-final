@extends('layouts.app', ['title' => 'Bank Soal Asesmen'])

@section('content')
<div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Bank Soal Asesmen</h1>
            <p class="text-sm text-slate-500">Kelola paket bank soal tertulis (CBT) dan butir pertanyaan instrumen MUK</p>
        </div>
        <div>
            <a href="{{ route('admin.question-banks.create') }}" class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-sm font-bold shadow-sm transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Butir Soal</span>
            </a>
        </div>
    </div>

    {{-- 1. Question Banks Package Table --}}
    <div class="space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between items-start gap-1">
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">
                1. Paket Bank Soal per Skema Sertifikasi
            </h2>
            <span class="text-xs text-slate-500">{{ count($questionBanks) }} Paket Terdaftar</span>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            {{-- Mobile Cards (< md) --}}
            <div class="block md:hidden divide-y divide-slate-100">
                @forelse($questionBanks ?? [] as $bank)
                    @php
                        $isSelected = ($selectedSchemeId == $bank->id);
                    @endphp
                    <div class="p-4 space-y-3 {{ $isSelected ? 'bg-brand-50/40' : '' }}">
                        <div class="flex items-start justify-between gap-2">
                            <span class="font-mono text-xs font-bold text-brand-700 bg-brand-50 px-2 py-0.5 rounded border border-brand-200">
                                {{ $bank->code }}
                            </span>
                            <span class="px-2.5 py-0.5 rounded text-[11px] font-bold border bg-emerald-50 text-emerald-800 border-emerald-200">
                                {{ $bank->type }}
                            </span>
                        </div>

                        <div>
                            <h3 class="font-bold text-slate-900 text-sm leading-snug">{{ $bank->title }}</h3>
                            <p class="text-xs text-brand-600 font-medium mt-0.5">{{ $bank->scheme->name ?? '-' }}</p>
                        </div>

                        <div class="flex items-center justify-between text-xs bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                            <span class="text-slate-600">Jumlah Soal:</span>
                            <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-white text-slate-800 border border-slate-200">
                                {{ $bank->questions_count }} Butir
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <a href="{{ route('admin.question-banks.index', ['scheme_id' => $bank->id]) }}#daftar-butir" 
                               class="px-3 py-2 text-xs font-bold text-brand-700 hover:text-brand-900 bg-brand-50 hover:bg-brand-100 border border-brand-200 rounded-lg text-center transition flex items-center justify-center min-h-[38px]">
                                Kelola Soal &darr;
                            </a>
                            <a href="{{ route('admin.question-banks.create', ['scheme_id' => $bank->id]) }}" 
                               class="px-3 py-2 text-xs font-bold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded-lg text-center transition flex items-center justify-center min-h-[38px]">
                                + Tambah Soal
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-500 text-xs">Belum ada bank soal yang tersedia.</div>
                @endforelse
            </div>

            {{-- Desktop Table (md+) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3">Nama Paket / Bank Soal</th>
                            <th class="px-6 py-3">Skema Terkait</th>
                            <th class="px-6 py-3">Tipe Instrumen</th>
                            <th class="px-6 py-3 text-center">Jumlah Butir Soal</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($questionBanks ?? [] as $bank)
                            @php
                                $isSelected = ($selectedSchemeId == $bank->id);
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition {{ $isSelected ? 'bg-brand-50/40' : '' }}">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900">{{ $bank->title }}</div>
                                    <div class="font-mono text-xs text-brand-700 mt-0.5">{{ $bank->code }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600">
                                    <div class="font-semibold text-slate-800">{{ $bank->scheme->name ?? '-' }}</div>
                                    <span class="text-slate-400 font-mono text-[11px]">{{ $bank->scheme->code ?? '' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded text-xs font-bold border bg-emerald-50 text-emerald-800 border-emerald-200">
                                        {{ $bank->type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-1 rounded text-xs font-bold bg-slate-100 text-slate-800">
                                        {{ $bank->questions_count }} Butir
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Aktif
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('admin.question-banks.index', ['scheme_id' => $bank->id]) }}#daftar-butir" 
                                           class="px-2.5 py-1 text-xs font-bold text-brand-700 hover:text-brand-900 bg-brand-50 hover:bg-brand-100 border border-brand-200 rounded transition">
                                            Kelola Soal ({{ $bank->questions_count }}) &darr;
                                        </a>
                                        <a href="{{ route('admin.question-banks.create', ['scheme_id' => $bank->id]) }}" 
                                           class="px-2.5 py-1 text-xs font-semibold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded transition">
                                            + Tambah
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500 text-xs">Belum ada bank soal yang tersedia.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 2. Individual Questions Management Section --}}
    <div id="daftar-butir" class="space-y-4 pt-4 border-t border-slate-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">
                    2. Daftar Butir Soal Uji Tertulis CBT (FR.IA.05)
                </h2>
                <p class="text-xs text-slate-500">
                    @if($selectedSchemeId)
                        Menampilkan butir soal untuk skema: <strong class="text-brand-700">{{ $schemes->firstWhere('id', $selectedSchemeId)->name ?? '' }}</strong>
                    @else
                        Menampilkan seluruh butir soal dari semua skema ({{ $questions->total() }} Butir)
                    @endif
                </p>
            </div>

            {{-- Filter & Search Form --}}
            <form action="{{ route('admin.question-banks.index') }}#daftar-butir" method="GET" data-ajax-filter="questions" data-no-loading="true" class="flex flex-wrap items-center gap-2">
                <select name="scheme_id" onchange="this.form.submit()" class="text-xs px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 focus:ring-brand-500">
                    <option value="">-- Semua Skema Sertifikasi --</option>
                    @foreach($schemes as $s)
                        <option value="{{ $s->id }}" {{ $selectedSchemeId == $s->id ? 'selected' : '' }}>
                            {{ $s->code }} - {{ $s->name }} ({{ $s->question_banks_count }})
                        </option>
                    @endforeach
                </select>

                @if(!empty($units) && $units->isNotEmpty())
                    <select name="unit_id" onchange="this.form.submit()" class="text-xs px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 focus:ring-brand-500 max-w-xs">
                        <option value="">-- Semua Unit Kompetensi --</option>
                        @foreach($units as $u)
                            <option value="{{ $u->id }}" {{ ($selectedUnitId ?? null) == $u->id ? 'selected' : '' }}>
                                {{ $u->code }} - {{ Str::limit($u->title, 35) }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <select name="sort" onchange="this.form.submit()" class="text-xs px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 focus:ring-brand-500">
                    <option value="asc" {{ ($sort ?? 'asc') === 'asc' ? 'selected' : '' }}>Urutan Dokumen MUK (1 &rarr; Terakhir)</option>
                    <option value="desc" {{ ($sort ?? 'asc') === 'desc' ? 'selected' : '' }}>Terbaru (ID Terbesar)</option>
                </select>

                <select name="per_page" onchange="this.form.submit()" class="text-xs px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 focus:ring-brand-500" title="Jumlah butir per halaman">
                    <option value="15" {{ ($perPage ?? 15) == 15 ? 'selected' : '' }}>15 / Hal</option>
                    <option value="25" {{ ($perPage ?? 15) == 25 ? 'selected' : '' }}>25 / Hal</option>
                    <option value="50" {{ ($perPage ?? 15) == 50 ? 'selected' : '' }}>50 / Hal (Semua)</option>
                    <option value="100" {{ ($perPage ?? 15) == 100 ? 'selected' : '' }}>100 / Hal</option>
                </select>

                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari teks soal..."
                        class="text-xs px-3 py-1.5 pl-8 rounded-lg border border-slate-300 bg-white text-slate-700 focus:ring-brand-500 w-36 sm:w-48">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <button type="submit" class="px-3 py-1.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg cursor-pointer">
                    Cari
                </button>

                <a href="{{ route('admin.question-banks.index') }}#daftar-butir" data-ajax-reset="questions" class="px-2.5 py-1.5 text-xs text-slate-600 hover:text-slate-900 bg-slate-100 rounded-lg {{ ($selectedSchemeId || $selectedUnitId || $search || ($sort ?? 'asc') !== 'asc' || ($perPage ?? 15) != ($selectedSchemeId ? 50 : 15)) ? '' : 'hidden' }}">
                    Reset Filter
                </a>
            </form>
        </div>

        {{-- Questions List (Responsive: Cards on Mobile, Table on Desktop) --}}
        <div id="questions-table-container" data-ajax-container="questions" data-skeleton-cols="5" data-skeleton-rows="6">
            @include('admin.question-banks._table')
        </div>
    </div>
</div>
@endsection
