<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    {{-- Mobile Cards (< md) --}}
    <div class="block md:hidden divide-y divide-slate-100">
        @forelse($questions as $index => $q)
            <div class="p-4 space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <span class="font-mono text-xs font-bold text-slate-500">
                        #{{ $questions->firstItem() + $index }} <span class="text-[10px] text-slate-400 font-normal">(ID: {{ $q->id }})</span>
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        Kunci: {{ $q->correct_answer }}
                    </span>
                </div>

                <div class="space-y-2">
                    <p class="font-semibold text-slate-900 text-xs sm:text-sm leading-relaxed">{{ $q->question_text }}</p>
                    
                    @if($q->question_image || $q->option_a_image || $q->option_b_image || $q->option_c_image || $q->option_d_image)
                        <div class="flex items-center gap-2 flex-wrap">
                            @if($q->question_image)
                                <a href="{{ $q->image_url ?? asset('storage/' . $q->question_image) }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-purple-700 bg-purple-50 px-2 py-1 rounded border border-purple-200 hover:bg-purple-100">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span>Lihat Gambar Soal</span>
                                </a>
                            @endif
                            @if($q->option_a_image || $q->option_b_image || $q->option_c_image || $q->option_d_image)
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-teal-700 bg-teal-50 px-2 py-1 rounded border border-teal-200">
                                    Opsi Bergambar
                                </span>
                            @endif
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1 text-[11px] text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                        <span class="{{ $q->correct_answer === 'A' ? 'font-bold text-emerald-700' : '' }}">A: {{ Str::limit($q->option_a, 50) }}</span>
                        <span class="{{ $q->correct_answer === 'B' ? 'font-bold text-emerald-700' : '' }}">B: {{ Str::limit($q->option_b, 50) }}</span>
                        <span class="{{ $q->correct_answer === 'C' ? 'font-bold text-emerald-700' : '' }}">C: {{ Str::limit($q->option_c, 50) }}</span>
                        <span class="{{ $q->correct_answer === 'D' ? 'font-bold text-emerald-700' : '' }}">D: {{ Str::limit($q->option_d, 50) }}</span>
                    </div>
                </div>

                <div class="text-[11px] text-slate-500 pt-1 border-t border-slate-100">
                    <span class="block"><strong>Unit:</strong> {{ $q->competencyUnit?->code ?? '-' }}</span>
                    <span class="text-slate-400 truncate block">{{ $q->competencyUnit?->title ?? '-' }}</span>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <a href="{{ route('admin.question-banks.show', $q->id) }}" class="flex-1 px-3 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg text-center transition flex items-center justify-center min-h-[38px]">
                        Detail
                    </a>
                    <a href="{{ route('admin.question-banks.edit', $q->id) }}" class="flex-1 px-3 py-2 text-xs font-bold text-brand-700 bg-brand-50 hover:bg-brand-100 rounded-lg text-center transition flex items-center justify-center min-h-[38px]">
                        Edit
                    </a>
                    <form action="{{ route('admin.question-banks.destroy', $q->id) }}" method="POST"
                          data-confirm="Apakah Anda yakin ingin menghapus butir soal ini?"
                          data-confirm-title="Hapus Butir Soal"
                          data-confirm-danger="true"
                          class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-3 py-2 text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-lg text-center transition flex items-center justify-center min-h-[38px]">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-slate-500 text-xs">
                Tidak ada butir soal yang sesuai dengan kriteria pencarian / filter.
            </div>
        @endforelse
    </div>

    {{-- Desktop Table (md+) --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-left font-semibold text-slate-600 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 w-12 text-center">No</th>
                    <th class="px-6 py-3 w-2/5">Pertanyaan & Pilihan Jawaban</th>
                    <th class="px-4 py-3">Unit Kompetensi</th>
                    <th class="px-3 py-3 text-center">Kunci</th>
                    <th class="px-6 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($questions as $index => $q)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-4 py-3 text-center font-mono font-bold text-slate-500">
                            {{ $questions->firstItem() + $index }}
                            <span class="block text-[10px] text-slate-400 font-normal">#{{ $q->id }}</span>
                        </td>
                        <td class="px-6 py-3 space-y-1.5">
                            <div class="font-semibold text-slate-900 text-xs leading-relaxed">
                                {{ Str::limit($q->question_text, 140) }}
                            </div>

                            @if($q->question_image || $q->option_a_image || $q->option_b_image || $q->option_c_image || $q->option_d_image)
                                <div class="flex items-center gap-2 pt-0.5">
                                    @if($q->question_image)
                                        <a href="{{ $q->image_url ?? asset('storage/' . $q->question_image) }}" target="_blank" class="inline-flex items-center gap-1 text-[10px] font-bold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200 hover:bg-purple-100">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span>Gambar Soal</span>
                                        </a>
                                    @endif
                                    @if($q->option_a_image || $q->option_b_image || $q->option_c_image || $q->option_d_image)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-teal-700 bg-teal-50 px-1.5 py-0.5 rounded border border-teal-200">
                                            Opsi Bergambar
                                        </span>
                                    @endif
                                </div>
                            @endif

                            <div class="text-[11px] text-slate-500 grid grid-cols-2 gap-x-2 gap-y-0.5 pt-0.5">
                                <span class="{{ $q->correct_answer === 'A' ? 'font-bold text-emerald-700' : '' }}">A: {{ Str::limit($q->option_a, 35) }}</span>
                                <span class="{{ $q->correct_answer === 'B' ? 'font-bold text-emerald-700' : '' }}">B: {{ Str::limit($q->option_b, 35) }}</span>
                                <span class="{{ $q->correct_answer === 'C' ? 'font-bold text-emerald-700' : '' }}">C: {{ Str::limit($q->option_c, 35) }}</span>
                                <span class="{{ $q->correct_answer === 'D' ? 'font-bold text-emerald-700' : '' }}">D: {{ Str::limit($q->option_d, 35) }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            <span class="font-mono text-[11px] text-brand-700 font-semibold block">{{ $q->competencyUnit?->code ?? '-' }}</span>
                            <span class="text-[11px] text-slate-500 line-clamp-1">{{ $q->competencyUnit?->title ?? '-' }}</span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                {{ $q->correct_answer }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right">
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
