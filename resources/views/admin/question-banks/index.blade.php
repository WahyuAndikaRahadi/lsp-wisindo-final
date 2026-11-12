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
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">
                1. Paket Bank Soal per Skema Sertifikasi
            </h2>
            <span class="text-xs text-slate-500">{{ count($questionBanks) }} Paket Terdaftar</span>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
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
            <form action="{{ route('admin.question-banks.index') }}#daftar-butir" method="GET" class="flex flex-wrap items-center gap-2">
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

                @if($selectedSchemeId || $selectedUnitId || $search || ($sort ?? 'asc') !== 'asc' || ($perPage ?? 15) != ($selectedSchemeId ? 50 : 15))
                    <a href="{{ route('admin.question-banks.index') }}#daftar-butir" class="px-2.5 py-1.5 text-xs text-slate-600 hover:text-slate-900 bg-slate-100 rounded-lg">
                        Reset Filter
                    </a>
                @endif
            </form>
        </div>

        {{-- Questions Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 text-left text-[11px] font-semibold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-center w-12">No</th>
                            <th class="px-4 py-3">Teks Pertanyaan</th>
                            <th class="px-4 py-3">Skema &amp; Unit</th>
                            <th class="px-3 py-3 text-center w-24">Kunci</th>
                            <th class="px-4 py-3 text-right w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($questions as $index => $q)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-4 py-3 text-center font-mono">
                                    <div class="font-bold text-slate-800 text-xs">
                                        {{ $questions->firstItem() + $index }}
                                    </div>
                                    <span class="text-[10px] text-slate-400 block font-normal">#{{ $q->id }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="space-y-1.5">
                                        <p class="font-semibold text-slate-900 leading-relaxed">{{ $q->question_text }}</p>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            @if($q->question_image)
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    Gambar Soal
                                                </span>
                                                <a href="{{ $q->image_url ?? asset('storage/' . $q->question_image) }}" target="_blank" class="text-[11px] text-brand-600 hover:underline">
                                                    Lihat Gambar &rarr;
                                                </a>
                                            @endif
                                            @if($q->option_a_image || $q->option_b_image || $q->option_c_image || $q->option_d_image)
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-teal-700 bg-teal-50 px-2 py-0.5 rounded border border-teal-200">
                                                    Opsi Bergambar
                                                </span>
                                            @endif
                                        </div>
                                        <div class="grid grid-cols-2 gap-1 text-[11px] text-slate-600 pt-1">
                                            <span class="{{ $q->correct_answer === 'A' ? 'font-bold text-emerald-700' : '' }}">A: {{ Str::limit($q->option_a, 40) }}</span>
                                            <span class="{{ $q->correct_answer === 'B' ? 'font-bold text-emerald-700' : '' }}">B: {{ Str::limit($q->option_b, 40) }}</span>
                                            <span class="{{ $q->correct_answer === 'C' ? 'font-bold text-emerald-700' : '' }}">C: {{ Str::limit($q->option_c, 40) }}</span>
                                            <span class="{{ $q->correct_answer === 'D' ? 'font-bold text-emerald-700' : '' }}">D: {{ Str::limit($q->option_d, 40) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="font-bold text-slate-800">{{ $q->scheme->name ?? '-' }}</div>
                                    <div class="text-[11px] font-mono text-brand-700 mt-0.5">
                                        {{ $q->competencyUnit?->code ?? 'Umum / Seluruh Unit' }}
                                    </div>
                                    @if($q->competencyUnit?->title)
                                        <div class="text-[10px] text-slate-500 leading-tight mt-0.5 max-w-xs">
                                            {{ Str::limit($q->competencyUnit->title, 45) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="w-7 h-7 rounded-full inline-flex items-center justify-center font-bold text-xs bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-xs">
                                        {{ $q->correct_answer }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <a href="{{ route('admin.question-banks.show', $q->id) }}" class="px-2 py-1 text-slate-600 hover:text-slate-900 bg-slate-100 rounded hover:bg-slate-200">
                                            Detail
                                        </a>
                                        <a href="{{ route('admin.question-banks.edit', $q->id) }}" class="px-2 py-1 text-brand-700 hover:text-brand-900 bg-brand-50 rounded hover:bg-brand-100">
                                            Edit
                                        </a>
                                        <form action="{{ route('admin.question-banks.destroy', $q->id) }}" method="POST"
                                              data-confirm="Apakah Anda yakin ingin menghapus butir soal ini?"
                                              data-confirm-title="Hapus Butir Soal"
                                              data-confirm-danger="true"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-1 text-rose-600 hover:text-rose-800 bg-rose-50 rounded hover:bg-rose-100">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500 text-xs">
                                    Tidak ada butir soal yang sesuai dengan kriteria pencarian / filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($questions->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $questions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
