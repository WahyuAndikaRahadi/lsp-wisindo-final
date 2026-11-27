@extends('layouts.app', ['title' => 'Detail Skema - ' . $scheme->name])

@section('content')
<div class="space-y-6 max-w-5xl mx-auto" x-data="{
    // Modal states
    showAddJobGroupModal: false,
    showEditJobGroupModal: false,
    editJobGroupData: { id: null, sequence: 1, name: '' },

    showAddUnitModal: false,
    showEditUnitModal: false,
    editUnitData: { id: null, code: '', title: '', standard_reference: 'SKKNI', sequence: 1, job_group_id: '', job_group_sequence: '' },
    
    showAddElementModal: false,
    addElementData: { unit_id: null, unit_code: '', title: '' },
    showEditElementModal: false,
    editElementData: { id: null, title: '' },

    showAddKukModal: false,
    addKukData: { element_id: null, element_title: '', code_number: '', statement: '' },
    showEditKukModal: false,
    editKukData: { id: null, code_number: '', statement: '' },

    previewSigModal: false,
    previewSigData: { name: '', no_met: '', signature: '', role: '' },

    openPreviewSig(data, role) {
        this.previewSigData = { name: data.name, no_met: data.no_met, signature: data.signature, role: role };
        this.previewSigModal = true;
    },

    openEditJobGroup(jg) {
        this.editJobGroupData = { ...jg };
        this.showEditJobGroupModal = true;
    },
    openEditUnit(unit) {
        this.editUnitData = {
            ...unit,
            job_group_id: unit.job_group_id ?? '',
            job_group_sequence: unit.job_group_sequence ?? ''
        };
        this.showEditUnitModal = true;
    },
    openAddElement(unit) {
        this.addElementData = { unit_id: unit.id, unit_code: unit.code, title: '' };
        this.showAddElementModal = true;
    },
    openEditElement(element) {
        this.editElementData = { id: element.id, title: element.title };
        this.showEditElementModal = true;
    },
    openAddKuk(element) {
        this.addKukData = { element_id: element.id, element_title: element.title, code_number: '', statement: '' };
        this.showAddKukModal = true;
    },
    openEditKuk(kuk) {
        this.editKukData = { id: kuk.id, code_number: kuk.code_number, statement: kuk.statement };
        this.showEditKukModal = true;
    }
}">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('admin.schemes.index') }}" class="text-xs text-brand-600 hover:text-brand-700 font-semibold">&larr; Kembali ke Daftar Skema</a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">{{ $scheme->name }}</h1>
            <div class="flex items-center space-x-3 text-xs text-slate-500 mt-1">
                <span class="font-mono font-bold text-brand-700 bg-brand-50 px-2 py-0.5 rounded">{{ $scheme->code }}</span>
                <span>Jenis: {{ $scheme->scheme_type ?? 'Klaster' }}</span>
                <span>Passing Grade: {{ $scheme->passing_grade_theory ?? 70 }}%</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $scheme->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                    {{ $scheme->is_active ? 'AKTIF' : 'NON-AKTIF' }}
                </span>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <button type="button" @click="showAddUnitModal = true" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-sm transition flex items-center space-x-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Unit</span>
            </button>
            <a href="{{ route('admin.schemes.edit', $scheme->id) }}" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                Edit Skema
            </a>
        </div>
    </div>

    {{-- Scheme Description --}}
    @if($scheme->description)
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm text-xs text-slate-700 leading-relaxed">
            <span class="font-bold text-slate-900 block mb-1">Deskripsi Skema:</span>
            {{ $scheme->description }}
        </div>
    @endif

    {{-- Tim Penyusun & Validator Dokumen MUK --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 sm:px-6 py-3.5 sm:py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between items-start gap-2">
            <div>
                <h2 class="text-sm font-bold text-slate-900">
                    Tim Penyusun & Validator Dokumen MUK
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Asesor yang bertanggung jawab menyusun dan memvalidasi materi uji kompetensi (MAPA, AK, IA).</p>
            </div>
            <a href="{{ route('admin.schemes.edit', $scheme->id) }}" class="text-xs text-brand-600 hover:text-brand-800 font-bold flex items-center space-x-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                <span>Kelola Tim MUK</span>
            </a>
        </div>

        <div class="p-5 sm:p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Column 1: Tim Penyusun --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Tim Penyusun MUK</h3>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-brand-50 text-brand-700 border border-brand-200">
                        {{ $scheme->penyusun->count() }} Orang
                    </span>
                </div>

                <div class="space-y-2.5">
                    @forelse($scheme->penyusun as $idx => $person)
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:border-slate-300 transition">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ $idx + 1 }}
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-slate-900 truncate">{{ $person->name }}</h4>
                                    <p class="text-[11px] font-mono text-slate-500 truncate mt-0.5">
                                        {{ $person->no_met ? $person->no_met : 'Belum ada No. MET' }}
                                    </p>
                                </div>
                            </div>
                            <div class="shrink-0 self-end sm:self-center">
                                @if($person->signature)
                                    <div @click="openPreviewSig({ name: '{{ addslashes($person->name) }}', no_met: '{{ addslashes($person->no_met ?? '') }}', signature: '{{ $person->signature }}' }, 'Penyusun')"
                                         title="Klik untuk melihat pratinjau penuh"
                                         class="h-20 w-48 sm:h-24 sm:w-56 bg-white border border-slate-200 hover:border-brand-400 rounded-xl p-2 flex items-center justify-center shadow-2xs hover:shadow-md transition cursor-zoom-in group relative">
                                        <img src="{{ $person->signature }}" alt="TTD {{ $person->name }}" class="h-full w-full object-contain filter contrast-125">
                                        <span class="absolute bottom-1 right-1 opacity-0 group-hover:opacity-100 transition text-[9px] font-bold text-brand-600 bg-brand-50 px-1 py-0.5 rounded border border-brand-200">Perbesar</span>
                                    </div>
                                @else
                                    <span class="text-[10px] text-slate-400 italic">Tanpa TTD</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center border border-dashed border-slate-200 rounded-lg text-slate-400 text-xs italic">
                            Belum ada Tim Penyusun yang ditambahkan.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Column 2: Tim Validator --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Tim Validator MUK</h3>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ $scheme->validators->count() }} Orang
                    </span>
                </div>

                <div class="space-y-2.5">
                    @forelse($scheme->validators as $idx => $person)
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:border-slate-300 transition">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ $idx + 1 }}
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-slate-900 truncate">{{ $person->name }}</h4>
                                    <p class="text-[11px] font-mono text-slate-500 truncate mt-0.5">
                                        {{ $person->no_met ? $person->no_met : 'Belum ada No. MET' }}
                                    </p>
                                </div>
                            </div>
                            <div class="shrink-0 self-end sm:self-center">
                                @if($person->signature)
                                    <div @click="openPreviewSig({ name: '{{ addslashes($person->name) }}', no_met: '{{ addslashes($person->no_met ?? '') }}', signature: '{{ $person->signature }}' }, 'Validator')"
                                         title="Klik untuk melihat pratinjau penuh"
                                         class="h-20 w-48 sm:h-24 sm:w-56 bg-white border border-slate-200 hover:border-emerald-400 rounded-xl p-2 flex items-center justify-center shadow-2xs hover:shadow-md transition cursor-zoom-in group relative">
                                        <img src="{{ $person->signature }}" alt="TTD {{ $person->name }}" class="h-full w-full object-contain filter contrast-125">
                                        <span class="absolute bottom-1 right-1 opacity-0 group-hover:opacity-100 transition text-[9px] font-bold text-emerald-700 bg-emerald-50 px-1 py-0.5 rounded border border-emerald-200">Perbesar</span>
                                    </div>
                                @else
                                    <span class="text-[10px] text-slate-400 italic">Tanpa TTD</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center border border-dashed border-slate-200 rounded-lg text-slate-400 text-xs italic">
                            Belum ada Tim Validator yang ditambahkan.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Kelompok Pekerjaan (MAPA & IA.01) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 sm:px-6 py-3.5 sm:py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between items-start gap-2">
            <div>
                <h2 class="text-sm font-bold text-slate-900">
                    Kelompok Pekerjaan Skema / MAPA ({{ $scheme->jobGroups->count() }} Kelompok)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Pengelompokan unit kompetensi berurutan untuk dokumen FR.MAPA.01, FR.MAPA.02, dan FR.IA.01 (terpisah dari urutan unit APL.01/APL.02).</p>
            </div>
            <button type="button" @click="showAddJobGroupModal = true" class="text-xs text-brand-600 hover:text-brand-800 font-bold flex items-center space-x-1 shrink-0">
                <span>+ Tambah Kelompok Pekerjaan</span>
            </button>
        </div>

        <div class="p-4 sm:p-5 space-y-3">
            @forelse($scheme->jobGroups as $jg)
                <div class="rounded-xl border border-slate-200 bg-slate-50/40 overflow-hidden" x-data="{ openGroup: false }">
                    <div class="px-4 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-white border-b border-slate-100">
                        <div class="flex items-center gap-2.5 cursor-pointer flex-1" @click="openGroup = !openGroup">
                            <span class="px-2 py-0.5 text-[11px] font-bold rounded bg-indigo-50 text-indigo-700 border border-indigo-200 shrink-0">
                                Kelompok {{ $jg->sequence }}
                            </span>
                            <h3 class="text-xs sm:text-sm font-bold text-slate-900">{{ $jg->name }}</h3>
                            <span class="text-[11px] text-slate-500 font-medium shrink-0">({{ $jg->competencyUnits->count() }} Unit)</span>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button type="button" @click="openEditJobGroup({ id: {{ $jg->id }}, sequence: {{ $jg->sequence }}, name: '{{ addslashes($jg->name) }}' })" class="px-2 py-1 text-[11px] font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded transition">
                                Edit
                            </button>
                            <form action="{{ route('admin.schemes.job-groups.destroy', $jg->id) }}" method="POST"
                                  data-confirm="Hapus Kelompok Pekerjaan '{{ $jg->name }}'? Unit kompetensi di dalamnya tidak akan terhapus."
                                  data-confirm-title="Hapus Kelompok Pekerjaan"
                                  data-confirm-danger="true"
                                  class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2 py-1 text-[11px] font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded border border-rose-200 transition">
                                    Hapus
                                </button>
                            </form>
                            <button type="button" @click="openGroup = !openGroup" class="text-xs text-brand-600 hover:underline font-semibold ml-1">
                                <span x-show="!openGroup">Lihat Unit &darr;</span>
                                <span x-show="openGroup">Tutup &uarr;</span>
                            </button>
                        </div>
                    </div>

                    <div x-show="openGroup" x-cloak class="divide-y divide-slate-100 bg-slate-50/40">
                        @forelse($jg->competencyUnits as $jgIdx => $jgUnit)
                            <div class="px-4 py-2 text-xs flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="font-mono font-bold text-slate-500 w-6 shrink-0">{{ $jgUnit->job_group_sequence ?? ($jgIdx + 1) }}.</span>
                                    <span class="px-1.5 py-0.5 font-mono font-bold bg-white border border-slate-200 text-slate-800 rounded shrink-0">{{ $jgUnit->code }}</span>
                                    <span class="text-slate-800">{{ $jgUnit->title }}</span>
                                </div>
                                <span class="text-[10px] font-mono text-slate-500 bg-white px-1.5 py-0.5 rounded border border-slate-200 shrink-0">
                                    Urutan APL: #{{ $jgUnit->sequence }}
                                </span>
                            </div>
                        @empty
                            <div class="px-4 py-3 text-xs text-slate-400 italic">Belum ada unit kompetensi di kelompok ini.</div>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-slate-500 text-xs border border-dashed border-slate-200 rounded-lg">
                    Belum ada Kelompok Pekerjaan yang dikonfigurasi untuk skema ini.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Competency Units & Elements List --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 sm:px-6 py-3.5 sm:py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between items-start gap-2">
            <div>
                <h2 class="text-sm font-bold text-slate-900">
                    Daftar Unit Kompetensi Standar SKKNI ({{ $scheme->competencyUnits->count() }} Unit)
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Diurutkan sesuai kemasan resmi FR.APL.01 &amp; FR.APL.02.</p>
            </div>
            <button type="button" @click="showAddUnitModal = true" class="text-xs text-brand-600 hover:text-brand-800 font-bold flex items-center space-x-1">
                <span>+ Tambah Unit Kompetensi</span>
            </button>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($scheme->competencyUnits as $index => $unit)
                <div class="p-4 sm:p-5 hover:bg-slate-50/50 transition" x-data="{ open: false }">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                        <div class="space-y-1 cursor-pointer flex-1" @click="open = !open">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2 py-0.5 text-[11px] font-mono font-bold bg-slate-900 text-white rounded">#{{ $unit->sequence }}</span>
                                <span class="px-2 py-0.5 text-xs font-mono font-bold bg-slate-100 text-slate-800 rounded">{{ $unit->code }}</span>
                                <h3 class="text-sm font-bold text-slate-900">{{ $unit->title }}</h3>
                            </div>
                            <div class="text-xs text-slate-500 flex flex-wrap items-center gap-2">
                                <span>{{ $unit->elements->count() }} Elemen Kompetensi</span>
                                <span>&bull;</span>
                                <span>Ref: {{ $unit->standard_reference ?? 'SKKNI' }}</span>
                                @if($unit->jobGroup)
                                    <span>&bull;</span>
                                    <span class="px-2 py-0.5 text-[10px] font-semibold bg-indigo-50 text-indigo-700 rounded border border-indigo-200">
                                        KP {{ $unit->jobGroup->sequence }}: {{ $unit->jobGroup->name }} (No. {{ $unit->job_group_sequence ?? '-' }})
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5 shrink-0 self-start sm:self-auto pt-1 sm:pt-0">
                            <button type="button" @click="openAddElement({ id: {{ $unit->id }}, code: '{{ addslashes($unit->code) }}' })" class="px-2 py-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded border border-emerald-200 transition">
                                + Elemen
                            </button>
                            <button type="button" @click="openEditUnit({ id: {{ $unit->id }}, code: '{{ addslashes($unit->code) }}', title: '{{ addslashes($unit->title) }}', standard_reference: '{{ addslashes($unit->standard_reference ?? 'SKKNI') }}', sequence: {{ (int) $unit->sequence }}, job_group_id: '{{ $unit->job_group_id ?? '' }}', job_group_sequence: '{{ $unit->job_group_sequence ?? '' }}' })" class="px-2 py-1 text-[11px] font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded transition">
                                Edit Unit
                            </button>
                            <form action="{{ route('admin.schemes.units.destroy', $unit->id) }}" method="POST"
                                  data-confirm="Apakah Anda yakin ingin menghapus unit kompetensi ini berserta seluruh elemen dan KUK-nya?"
                                  data-confirm-title="Hapus Unit Kompetensi"
                                  data-confirm-danger="true"
                                  class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2 py-1 text-[11px] font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded border border-rose-200 transition">
                                    Hapus
                                </button>
                            </form>
                            <button type="button" @click="open = !open" class="text-xs text-brand-600 hover:underline font-semibold ml-1">
                                <span x-show="!open">Detail &darr;</span>
                                <span x-show="open">Tutup &uarr;</span>
                            </button>
                        </div>
                    </div>

                    {{-- Elements & KUK Accordion --}}
                    <div x-show="open" x-cloak class="mt-4 pt-4 border-t border-slate-100 space-y-3">
                        @forelse($unit->elements as $elIndex => $element)
                            <div class="bg-slate-50 rounded-lg p-3.5 text-xs space-y-2 border border-slate-200">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between items-start gap-2 pb-2 border-b border-slate-200">
                                    <h4 class="font-bold text-slate-800">
                                        Elemen {{ $elIndex + 1 }}: {{ $element->title ?? $element->name }}
                                    </h4>
                                    <div class="flex flex-wrap items-center gap-1.5 self-start sm:self-auto">
                                        <button type="button" @click="openAddKuk({ id: {{ $element->id }}, title: '{{ addslashes($element->title) }}' })" class="px-2 py-0.5 text-[10px] font-bold text-emerald-700 bg-white border border-emerald-300 rounded hover:bg-emerald-50">
                                            + Tambah KUK
                                        </button>
                                        <button type="button" @click="openEditElement({ id: {{ $element->id }}, title: '{{ addslashes($element->title) }}' })" class="px-2 py-0.5 text-[10px] font-semibold text-slate-600 bg-white border border-slate-300 rounded hover:bg-slate-100">
                                            Edit
                                        </button>
                                        <form action="{{ route('admin.schemes.elements.destroy', $element->id) }}" method="POST"
                                              data-confirm="Hapus elemen kompetensi ini?"
                                              data-confirm-title="Hapus Elemen Kompetensi"
                                              data-confirm-danger="true"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2 py-0.5 text-[10px] font-semibold text-rose-600 bg-white border border-rose-200 rounded hover:bg-rose-50">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <ul class="space-y-1.5 text-slate-700 pt-1">
                                    @forelse($element->kuks as $kuk)
                                        <li class="flex items-start justify-between gap-3 p-1.5 hover:bg-white rounded transition">
                                            <div class="flex items-start space-x-2">
                                                <span class="font-mono text-[11px] font-bold text-slate-700 bg-white px-1.5 py-0.5 rounded border border-slate-200 shrink-0">
                                                    {{ $kuk->code_number ?? $kuk->criteria_number }}
                                                </span>
                                                <span class="leading-relaxed">{{ $kuk->statement }}</span>
                                            </div>
                                            <div class="flex items-center space-x-1 shrink-0">
                                                <button type="button" @click="openEditKuk({ id: {{ $kuk->id }}, code_number: '{{ addslashes($kuk->code_number) }}', statement: '{{ addslashes($kuk->statement) }}' })" class="text-[10px] font-semibold text-slate-500 hover:text-slate-800">
                                                    Edit
                                                </button>
                                                <span class="text-slate-300">&bull;</span>
                                                <form action="{{ route('admin.schemes.kuks.destroy', $kuk->id) }}" method="POST"
                                                      data-confirm="Hapus KUK {{ $kuk->code_number }}?"
                                                      data-confirm-title="Hapus Kriteria Unjuk Kerja (KUK)"
                                                      data-confirm-danger="true"
                                                      class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-[10px] font-semibold text-rose-500 hover:text-rose-700">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </li>
                                    @empty
                                        <li class="italic text-slate-400 py-1">Belum ada butir KUK terdaftar.</li>
                                    @endforelse
                                </ul>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 italic">Belum ada elemen kompetensi pada unit ini.</p>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-slate-500 text-xs">
                    Belum ada unit kompetensi yang terhubung ke skema ini.
                </div>
            @endforelse
        </div>
    </div>

    {{-- MODAL 1: Tambah Unit Kompetensi --}}
    <div x-show="showAddUnitModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(15, 23, 42, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl space-y-4" @click.away="showAddUnitModal = false">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-base font-bold text-slate-900">Tambah Unit Kompetensi</h3>
                    <button type="button" @click="showAddUnitModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form action="{{ route('admin.schemes.units.store', $scheme->id) }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Kode Unit <span class="text-rose-500">*</span></label>
                            <input type="text" name="code" required placeholder="Contoh: I.55HDR00.177.3" class="w-full px-3.5 py-2 text-xs font-mono rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Urutan APL</label>
                            <input type="number" name="sequence" min="1" value="{{ ($scheme->competencyUnits->max('sequence') ?? 0) + 1 }}" class="w-full px-3.5 py-2 text-xs font-mono rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Judul Unit Kompetensi <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" required placeholder="Contoh: Melaksanakan Prosedur Keamanan Makanan" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Referensi Standar</label>
                        <input type="text" name="standard_reference" value="SKKNI No.125 Tahun 2024" placeholder="SKKNI / Standar Khusus" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Kelompok Pekerjaan (MAPA)</label>
                            <select name="job_group_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                                <option value="">-- Belum Dikelompokkan --</option>
                                @foreach($scheme->jobGroups as $jg)
                                    <option value="{{ $jg->id }}">KP {{ $jg->sequence }}: {{ $jg->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">No. di KP</label>
                            <input type="number" name="job_group_sequence" min="1" placeholder="Auto" class="w-full px-3.5 py-2 text-xs font-mono rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                        </div>
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t">
                        <button type="button" @click="showAddUnitModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg">Simpan Unit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL 2: Edit Unit Kompetensi --}}
    <div x-show="showEditUnitModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(15, 23, 42, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl space-y-4" @click.away="showEditUnitModal = false">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-base font-bold text-slate-900">Edit Unit Kompetensi</h3>
                    <button type="button" @click="showEditUnitModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'{{ url('/admin/units') }}/' + editUnitData.id" method="POST" class="space-y-4 text-xs">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Kode Unit <span class="text-rose-500">*</span></label>
                            <input type="text" name="code" required x-model="editUnitData.code" class="w-full px-3.5 py-2 text-xs font-mono rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Urutan APL</label>
                            <input type="number" name="sequence" min="1" x-model="editUnitData.sequence" class="w-full px-3.5 py-2 text-xs font-mono rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Judul Unit Kompetensi <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" required x-model="editUnitData.title" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Referensi Standar</label>
                        <input type="text" name="standard_reference" x-model="editUnitData.standard_reference" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Kelompok Pekerjaan (MAPA)</label>
                            <select name="job_group_id" x-model="editUnitData.job_group_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                                <option value="">-- Belum Dikelompokkan --</option>
                                @foreach($scheme->jobGroups as $jg)
                                    <option value="{{ $jg->id }}">KP {{ $jg->sequence }}: {{ $jg->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">No. di KP</label>
                            <input type="number" name="job_group_sequence" min="1" x-model="editUnitData.job_group_sequence" placeholder="Auto" class="w-full px-3.5 py-2 text-xs font-mono rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                        </div>
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t">
                        <button type="button" @click="showEditUnitModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: Tambah Kelompok Pekerjaan --}}
    <div x-show="showAddJobGroupModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(15, 23, 42, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl space-y-4" @click.away="showAddJobGroupModal = false">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-base font-bold text-slate-900">Tambah Kelompok Pekerjaan</h3>
                    <button type="button" @click="showAddJobGroupModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form action="{{ route('admin.schemes.job-groups.store', $scheme->id) }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Urutan Kelompok <span class="text-rose-500">*</span></label>
                        <input type="number" name="sequence" min="1" required value="{{ ($scheme->jobGroups->max('sequence') ?? 0) + 1 }}" class="w-full px-3.5 py-2 text-xs font-mono rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Kelompok Pekerjaan <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required placeholder="Contoh: Keselamatan, Kesehatan Kerja & P3K" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t">
                        <button type="button" @click="showAddJobGroupModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg">Simpan Kelompok</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: Edit Kelompok Pekerjaan --}}
    <div x-show="showEditJobGroupModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(15, 23, 42, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl space-y-4" @click.away="showEditJobGroupModal = false">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-base font-bold text-slate-900">Edit Kelompok Pekerjaan</h3>
                    <button type="button" @click="showEditJobGroupModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'{{ url('/admin/job-groups') }}/' + editJobGroupData.id" method="POST" class="space-y-4 text-xs">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Urutan Kelompok <span class="text-rose-500">*</span></label>
                        <input type="number" name="sequence" min="1" required x-model="editJobGroupData.sequence" class="w-full px-3.5 py-2 text-xs font-mono rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Kelompok Pekerjaan <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required x-model="editJobGroupData.name" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t">
                        <button type="button" @click="showEditJobGroupModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL 3: Tambah Elemen Kompetensi --}}
    <div x-show="showAddElementModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(15, 23, 42, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl space-y-4" @click.away="showAddElementModal = false">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-base font-bold text-slate-900">Tambah Elemen Kompetensi</h3>
                    <button type="button" @click="showAddElementModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'{{ url('/admin/units') }}/' + addElementData.unit_id + '/elements'" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <span class="block text-slate-500 mb-1">Unit Terkait: <strong class="font-mono text-slate-800" x-text="addElementData.unit_code"></strong></span>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Judul Elemen Kompetensi <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" required x-model="addElementData.title" placeholder="Contoh: Menyiapkan alat dan perlengkapan servis" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t">
                        <button type="button" @click="showAddElementModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg">Simpan Elemen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL 4: Edit Elemen Kompetensi --}}
    <div x-show="showEditElementModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(15, 23, 42, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl space-y-4" @click.away="showEditElementModal = false">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-base font-bold text-slate-900">Edit Elemen Kompetensi</h3>
                    <button type="button" @click="showEditElementModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'{{ url('/admin/elements') }}/' + editElementData.id" method="POST" class="space-y-4 text-xs">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Judul Elemen Kompetensi <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" required x-model="editElementData.title" class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t">
                        <button type="button" @click="showEditElementModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL 5: Tambah Kriteria Unjuk Kerja (KUK) --}}
    <div x-show="showAddKukModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(15, 23, 42, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl space-y-4" @click.away="showAddKukModal = false">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-base font-bold text-slate-900">Tambah Kriteria Unjuk Kerja (KUK)</h3>
                    <button type="button" @click="showAddKukModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'{{ url('/admin/elements') }}/' + addKukData.element_id + '/kuks'" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <span class="block text-slate-500 mb-1">Elemen: <strong class="text-slate-800" x-text="addKukData.element_title"></strong></span>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nomor Butir KUK <span class="text-rose-500">*</span></label>
                        <input type="text" name="code_number" required placeholder="Contoh: 1.1 atau 2.3" class="w-full px-3.5 py-2 text-xs font-mono rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pernyataan Unjuk Kerja (KUK) <span class="text-rose-500">*</span></label>
                        <textarea name="statement" rows="3" required placeholder="Peralatan dan perlengkapan servis diidentifikasi sesuai kebutuhan instruksi kerja." class="w-full p-3 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs"></textarea>
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t">
                        <button type="button" @click="showAddKukModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg">Simpan KUK</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL 6: Edit Kriteria Unjuk Kerja (KUK) --}}
    <div x-show="showEditKukModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(15, 23, 42, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl space-y-4" @click.away="showEditKukModal = false">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-base font-bold text-slate-900">Edit KUK</h3>
                    <button type="button" @click="showEditKukModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'{{ url('/admin/kuks') }}/' + editKukData.id" method="POST" class="space-y-4 text-xs">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nomor Butir KUK <span class="text-rose-500">*</span></label>
                        <input type="text" name="code_number" required x-model="editKukData.code_number" class="w-full px-3.5 py-2 text-xs font-mono rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pernyataan Unjuk Kerja (KUK) <span class="text-rose-500">*</span></label>
                        <textarea name="statement" rows="3" required x-model="editKukData.statement" class="w-full p-3 text-xs rounded-xl border border-slate-300 bg-white text-slate-900 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs"></textarea>
                    </div>
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t">
                        <button type="button" @click="showEditKukModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL PREVIEW TTD DIGITAL BESAR --}}
    <div x-show="previewSigModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(15, 23, 42, 0.65);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.away="previewSigModal = false">
                <div class="flex items-center justify-between border-b pb-3">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-brand-600" x-text="'Tanda Tangan Digital Tim ' + previewSigData.role"></span>
                        <h3 class="text-base font-bold text-slate-900" x-text="previewSigData.name"></h3>
                        <p class="text-xs font-mono text-slate-500" x-text="previewSigData.no_met || 'Belum ada No. MET'"></p>
                    </div>
                    <button type="button" @click="previewSigModal = false" class="text-slate-400 hover:text-slate-600 text-2xl font-bold leading-none">&times;</button>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-6 flex items-center justify-center min-h-[220px]">
                    <img :src="previewSigData.signature" :alt="'TTD ' + previewSigData.name" class="max-h-60 max-w-full object-contain filter contrast-125">
                </div>
                <div class="flex justify-end pt-2">
                    <button type="button" @click="previewSigModal = false" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-lg shadow-sm">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
