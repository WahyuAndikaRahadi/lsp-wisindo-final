@extends('layouts.app', ['title' => 'Edit Butir Soal CBT'])

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <div>
        <a href="{{ route('admin.question-banks.show', $questionBank->id) }}" class="text-xs text-brand-600 hover:text-brand-700 font-semibold">&larr; Kembali ke Detail Soal</a>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">Edit Soal CBT (FR.IA.05)</h1>
        <p class="text-sm text-slate-500">Perbarui pertanyaan tertulis dan pilihan jawaban</p>
    </div>

    <form action="{{ route('admin.question-banks.update', $questionBank->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900">Skema & Unit Kompetensi</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Skema Sertifikasi <span class="text-rose-500">*</span></label>
                    <select name="scheme_id" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none bg-white">
                        @foreach($schemes ?? [] as $scheme)
                            <option value="{{ $scheme->id }}" {{ old('scheme_id', $questionBank->scheme_id) == $scheme->id ? 'selected' : '' }}>
                                {{ $scheme->name }} ({{ $scheme->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('scheme_id')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Unit Kompetensi (Opsional)</label>
                    <select name="competency_unit_id" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none bg-white">
                        <option value="">-- Umum / Seluruh Skema --</option>
                        @foreach($schemes ?? [] as $scheme)
                            @foreach($scheme->competencyUnits ?? [] as $unit)
                                <option value="{{ $unit->id }}" {{ old('competency_unit_id', $questionBank->competency_unit_id) == $unit->id ? 'selected' : '' }}>
                                    [{{ $scheme->code }}] {{ $unit->code }} - {{ $unit->title }}
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                    @error('competency_unit_id')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Teks Pertanyaan / Soal <span class="text-rose-500">*</span></label>
                <textarea name="question_text" rows="4" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">{{ old('question_text', $questionBank->question_text) }}</textarea>
                @error('question_text')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Gambar Pendukung Soal (Opsional)</label>
                @if($questionBank->question_image)
                    <div class="mb-3 p-3 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-between gap-4">
                        <div class="flex items-center space-x-3">
                            <img src="{{ $questionBank->image_url ?? asset('storage/' . $questionBank->question_image) }}" alt="Preview Soal" class="h-20 w-auto object-contain rounded border border-slate-300">
                            <span class="text-xs text-slate-600 font-mono">{{ $questionBank->question_image }}</span>
                        </div>
                        <label class="inline-flex items-center space-x-1.5 text-xs font-semibold text-rose-700 cursor-pointer">
                            <input type="checkbox" name="remove_image" value="1" class="rounded border-rose-300 text-rose-600 focus:ring-rose-500">
                            <span>Hapus Gambar</span>
                        </label>
                    </div>
                @endif
                <input type="file" name="question_image" accept="image/*" class="w-full px-4 py-2 rounded-lg border border-slate-300 text-sm bg-white file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                <p class="text-xs text-slate-400 mt-1">Unggah file baru jika ingin mengganti gambar sebelumnya. Format: JPG, PNG, WEBP (maks. 2MB).</p>
                @error('question_image')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-3 pt-2 border-t border-slate-100">
                <h3 class="text-sm font-bold text-slate-800">Pilihan Jawaban & Kunci</h3>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pilihan A <span class="text-rose-500">*</span></label>
                    <input type="text" name="option_a" required value="{{ old('option_a', $questionBank->option_a) }}" class="w-full px-4 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('option_a')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pilihan B <span class="text-rose-500">*</span></label>
                    <input type="text" name="option_b" required value="{{ old('option_b', $questionBank->option_b) }}" class="w-full px-4 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('option_b')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pilihan C <span class="text-rose-500">*</span></label>
                    <input type="text" name="option_c" required value="{{ old('option_c', $questionBank->option_c) }}" class="w-full px-4 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('option_c')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Pilihan D <span class="text-rose-500">*</span></label>
                    <input type="text" name="option_d" required value="{{ old('option_d', $questionBank->option_d) }}" class="w-full px-4 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('option_d')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Kunci Jawaban Benar <span class="text-rose-500">*</span></label>
                    <div class="flex items-center space-x-6">
                        @foreach(['A', 'B', 'C', 'D'] as $choice)
                            <label class="flex items-center space-x-2 text-sm font-bold text-slate-800 cursor-pointer">
                                <input type="radio" name="correct_answer" value="{{ $choice }}" required {{ old('correct_answer', $questionBank->correct_answer) == $choice ? 'checked' : '' }} class="text-brand-600 focus:ring-brand-500">
                                <span>Pilihan {{ $choice }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('correct_answer')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.question-banks.show', $questionBank->id) }}" class="px-5 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">Batal</a>
            <button type="submit" class="px-6 py-2.5 text-sm font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection
