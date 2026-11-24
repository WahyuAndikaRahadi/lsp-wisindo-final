@extends('layouts.app', ['title' => 'Bank Soal Wawancara (FR.IA.09)'])

@section('content')
<div class="space-y-6" x-data="{
    modalOpen: false,
    isEdit: false,
    formAction: '{{ route('admin.interview-questions.store') }}',
    formMethod: 'POST',
    questionData: {
        id: null,
        order: '',
        evidence_context: 'Sesuai dengan bukti no. …… yang Anda ajukan',
        question_text: '',
        expected_answer: '',
        is_active: true
    },
    openAddModal() {
        this.isEdit = false;
        this.formAction = '{{ route('admin.interview-questions.store') }}';
        this.formMethod = 'POST';
        this.questionData = {
            id: null,
            order: '{{ ($questions->max('order') ?? 0) + 1 }}',
            evidence_context: 'Sesuai dengan bukti no. …… yang Anda ajukan',
            question_text: '',
            expected_answer: '',
            is_active: true
        };
        this.modalOpen = true;
    },
    openEditModal(q) {
        this.isEdit = true;
        this.formAction = '/admin/interview-questions/' + q.id;
        this.formMethod = 'POST';
        this.questionData = {
            id: q.id,
            order: q.order,
            evidence_context: q.evidence_context || 'Sesuai dengan bukti no. …… yang Anda ajukan',
            question_text: q.question_text,
            expected_answer: q.expected_answer || '',
            is_active: Boolean(q.is_active)
        };
        this.modalOpen = true;
    }
}">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Bank Soal Wawancara (FR.IA.09)</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Kelola daftar pertanyaan wawancara terstruktur pendukung portofolio per skema sertifikasi.</p>
        </div>

        @if($selectedScheme)
            <button type="button" 
                    @click="openAddModal()"
                    class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-xs font-bold transition shadow-xs flex items-center justify-center gap-1.5 cursor-pointer shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Pertanyaan</span>
            </button>
        @endif
    </div>

    {{-- Scheme Tabs --}}
    <div class="border-b border-slate-200 overflow-x-auto">
        <nav class="flex space-x-2 pb-2 min-w-max">
            @foreach($schemes as $sch)
                @php
                    $isActive = $selectedScheme && $selectedScheme->id === $sch->id;
                    $qCount = $sch->interview_questions_count;
                @endphp
                <a href="{{ route('admin.interview-questions.index', ['scheme_id' => $sch->id]) }}"
                   class="px-3.5 py-2 rounded-lg text-xs font-bold transition flex items-center gap-2 {{ $isActive ? 'bg-brand-50 text-brand-900 border border-brand-300 shadow-2xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    <span>{{ $sch->name }}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] {{ $qCount > 0 ? ($isActive ? 'bg-brand-600 text-white' : 'bg-slate-200 text-slate-700') : 'bg-slate-100 text-slate-400' }}">
                        {{ $qCount }}
                    </span>
                </a>
            @endforeach
        </nav>
    </div>

    @if($selectedScheme)
        {{-- Selected Scheme Info Bar --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-brand-50 border border-brand-200 text-brand-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">{{ $selectedScheme->name }}</h2>
                    <span class="text-[11px] font-mono text-slate-500">{{ $selectedScheme->code }} &bull; {{ $questions->count() }} Pertanyaan Terdaftar</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-500">Status Modul FR.IA.09:</span>
                @if($questions->count() > 0)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        ✓ Siap Digunakan
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-200">
                        Belum Dikonfigurasi
                    </span>
                @endif
            </div>
        </div>

        {{-- Questions List --}}
        @if($questions->count() > 0)
            <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                <div class="divide-y divide-slate-100">
                    @foreach($questions as $q)
                        <div class="p-4 sm:p-5 hover:bg-slate-50/70 transition space-y-2">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3">
                                    <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 font-bold font-mono text-xs flex items-center justify-center shrink-0 border border-slate-200 mt-0.5">
                                        {{ $q->order }}
                                    </span>
                                    <div class="space-y-1">
                                        <div class="text-[11px] font-bold text-brand-700 uppercase tracking-wider">
                                            {{ $q->evidence_context ?: 'Sesuai dengan bukti no. …… yang Anda ajukan' }}
                                        </div>
                                        <p class="text-xs sm:text-sm font-medium text-slate-900 leading-relaxed">
                                            {{ $q->question_text }}
                                        </p>
                                        @if($q->expected_answer)
                                            <div class="mt-2 p-2.5 bg-slate-50 rounded-lg border border-slate-200 text-xs text-slate-600">
                                                <span class="font-bold text-slate-700 block mb-0.5">Panduan / Kunci Jawaban:</span>
                                                <p class="leading-relaxed">{{ $q->expected_answer }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button type="button" 
                                            @click="openEditModal({{ json_encode($q) }})"
                                            class="p-2 text-slate-500 hover:text-brand-600 hover:bg-brand-50 rounded-lg transition"
                                            title="Edit Pertanyaan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    <form action="{{ route('admin.interview-questions.destroy', $q->id) }}" method="POST" onsubmit="return confirm('Hapus pertanyaan wawancara ini?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                                title="Hapus Pertanyaan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            {{-- Empty State --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-8 sm:p-12 text-center space-y-4 shadow-2xs">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-200">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <div class="max-w-md mx-auto space-y-1">
                    <h3 class="text-base font-bold text-slate-900">Bank Soal Wawancara Belum Tersedia</h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Skema <strong>{{ $selectedScheme->name }}</strong> belum memiliki daftar pertanyaan wawancara (FR.IA.09). Anda dapat menambahkan pertanyaan secara manual.
                    </p>
                </div>
                <div>
                    <button type="button" 
                            @click="openAddModal()"
                            class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-xs font-bold transition shadow-xs inline-flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah Pertanyaan Pertama</span>
                    </button>
                </div>
            </div>
        @endif
    @endif

    {{-- Add/Edit Modal --}}
    <div x-show="modalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-2xs flex items-center justify-center p-4">
        
        <div @click.away="modalOpen = false"
             class="bg-white rounded-2xl shadow-xl max-w-lg w-full border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            
            <form :action="formAction" method="POST" class="p-6 space-y-4">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="scheme_id" value="{{ $selectedScheme?->id }}">

                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900" x-text="isEdit ? 'Edit Pertanyaan Wawancara' : 'Tambah Pertanyaan Wawancara'"></h3>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nomor Urut</label>
                        <input type="number" name="order" x-model="questionData.order" min="1" required
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Konteks Bukti Portofolio</label>
                        <input type="text" name="evidence_context" x-model="questionData.evidence_context" required
                               placeholder="Contoh: Sesuai dengan bukti no. …… yang Anda ajukan"
                               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Teks Pertanyaan Wawancara <span class="text-rose-500">*</span></label>
                        <textarea name="question_text" x-model="questionData.question_text" rows="3" required
                                  placeholder="Tuliskan pertanyaan wawancara..."
                                  class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none leading-relaxed"></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Panduan / Kunci Jawaban Asesor (Opsional)</label>
                        <textarea name="expected_answer" x-model="questionData.expected_answer" rows="2"
                                  placeholder="Panduan substansi yang diharapkan dijelaskan oleh asesi..."
                                  class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none leading-relaxed"></textarea>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" name="is_active" id="modal_is_active" x-model="questionData.is_active" value="1"
                               class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4">
                        <label for="modal_is_active" class="font-semibold text-slate-700">Aktifkan pertanyaan ini dalam instrumen</label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg text-xs transition shadow-2xs cursor-pointer">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
