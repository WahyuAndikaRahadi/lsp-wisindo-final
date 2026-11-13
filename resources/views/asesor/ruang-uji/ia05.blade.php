@extends('layouts.app', ['title' => 'FR.IA.05 - Hasil CBT'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia05']) }}" class="text-xs text-brand-600 hover:text-brand-700 font-bold">&larr; Kembali ke Daftar Asesi IA.05</a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">FR.IA.05 – Hasil Ujian Tertulis Pilihan Ganda (CBT)</h1>
            <p class="text-sm text-slate-500">Asesi: <strong>{{ $assessment->asesi->full_name }}</strong> | Skema: <strong>{{ $assessment->schedule->scheme->name }}</strong></p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dokumen-muk.asesi-dynamic', ['assessment' => $assessment->id, 'type' => 'ia05c']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-cyan-50 text-cyan-800 border border-cyan-300 hover:bg-cyan-100 transition shadow-xs"
               title="Lihat Lembar Jawaban FR.IA.05.C">
                <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Dokumen FR.IA.05.C</span>
            </a>
        </div>
    </div>

    {{-- Score Overview Card --}}
    @php
        $totalQuestions = count($breakdown ?? []);
        $correctAnswers = collect($breakdown ?? [])->where('is_correct', true)->count();
        $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 1) : (float) ($response?->score ?? $assessment->ia05_score ?? 0);
        $passingGrade = (float) ($scheme?->passing_grade_theory ?? \App\Models\Setting::get('cbt_passing_grade', 75.0));
        $isPassed = $score >= $passingGrade;
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 rounded-lg {{ $isPassed ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }} flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Nilai Akhir CBT</p>
                <h3 class="text-2xl font-black {{ $isPassed ? 'text-emerald-600' : 'text-rose-600' }} mt-0.5">{{ $score }}%</h3>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 rounded-lg bg-blue-50 text-brand-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Jawaban Benar</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5">{{ $correctAnswers }} / {{ $totalQuestions }}</h3>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 rounded-lg {{ $isPassed ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }} flex items-center justify-center shrink-0">
                <span class="font-bold text-lg">{{ $isPassed ? 'K' : 'BK' }}</span>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Status Kelulusan</p>
                <h3 class="text-base font-bold {{ $isPassed ? 'text-emerald-700' : 'text-rose-700' }} mt-0.5">
                    {{ $isPassed ? 'Kompeten' : 'Belum Kompeten' }}
                </h3>
            </div>
        </div>
    </div>

    {{-- Answer Breakdown Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-base font-bold text-slate-900">Rincian Jawaban Soal CBT</h2>
            <p class="text-xs text-slate-500">Rincian pilihan asesi per butir soal</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="hidden md:table-header-group bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3 w-16 text-center">No</th>
                        <th class="px-6 py-3">Pertanyaan</th>
                        <th class="px-6 py-3 text-center w-28">Jawaban Asesi</th>
                        <th class="px-6 py-3 text-center w-28">Kunci Jawaban</th>
                        <th class="px-6 py-3 text-center w-28">Hasil</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($breakdown ?? [] as $i => $item)
                        @php
                            $qId = is_object($item) ? ($item->question_id ?? $i) : ($item['question_id'] ?? $i);
                            $qText = is_object($item) ? ($item->question ?? '') : ($item['question'] ?? '');
                            $qImg = is_object($item) ? ($item->question_image ?? null) : ($item['question_image'] ?? null);
                            $userAns = is_object($item)
                                ? ($item->answer ?? $item->user_answer ?? null)
                                : ($item['answer'] ?? $item['user_answer'] ?? null);
                            if ($userAns === null && isset($savedAnswers[$qId])) {
                                $userAns = $savedAnswers[$qId];
                            }
                            if ($userAns !== null && $userAns !== '') {
                                $userAns = strtoupper(trim((string) $userAns));
                            }
                            $correctAns = is_object($item)
                                ? ($item->correct_answer ?? null)
                                : ($item['correct_answer'] ?? null);
                            if ($correctAns !== null && $correctAns !== '') {
                                $correctAns = strtoupper(trim((string) $correctAns));
                            }
                            $isCorrect = is_object($item)
                                ? ($item->is_correct ?? false)
                                : ($item['is_correct'] ?? false);
                        @endphp
                        <tr class="flex flex-col md:table-row p-4 md:p-0 hover:bg-slate-50/50 transition space-y-2.5 md:space-y-0">
                            <td class="hidden md:table-cell px-6 py-3 text-slate-500 font-mono text-xs text-center font-bold">{{ $loop->iteration }}</td>
                            <td class="px-0 md:px-6 py-0 md:py-3 text-slate-800">
                                <div class="flex items-start gap-2">
                                    <span class="md:hidden font-mono text-xs font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded shrink-0">#{{ $loop->iteration }}</span>
                                    <div class="font-medium text-xs sm:text-sm leading-relaxed">{{ $qText }}</div>
                                </div>
                                @if($qImg)
                                    <div class="mt-2 inline-block">
                                        <a href="{{ asset('storage/' . $qImg) }}" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-700 border border-slate-300 transition">
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span>Lihat Gambar Soal</span>
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td class="px-0 md:px-6 py-0 md:py-3 md:text-center font-mono font-bold text-xs">
                                <div class="flex items-center justify-between md:justify-center p-2.5 md:p-0 bg-slate-50 md:bg-transparent rounded-lg border md:border-0 border-slate-200">
                                    <div class="flex items-center gap-3 md:hidden">
                                        <div class="flex items-center gap-1 text-[11px] font-sans text-slate-600">
                                            <span>Jawaban:</span>
                                            <span class="font-mono font-bold px-1.5 py-0.5 rounded {{ $isCorrect ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">{{ $userAns ?: '-' }}</span>
                                        </div>
                                        <div class="flex items-center gap-1 text-[11px] font-sans text-slate-600">
                                            <span>Kunci:</span>
                                            <span class="font-mono font-bold px-1.5 py-0.5 rounded bg-slate-200 text-slate-800">{{ $correctAns ?: '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="md:hidden">
                                        @if($isCorrect)
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">✓ Benar</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-300">✕ Salah</span>
                                        @endif
                                    </div>
                                    {{-- Desktop Jawaban --}}
                                    <div class="hidden md:block">
                                        @if(!empty($userAns))
                                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-bold {{ $isCorrect ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300' }}">
                                                {{ $userAns }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 font-normal italic">-</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="hidden md:table-cell px-6 py-3 text-center font-mono font-bold text-xs text-slate-600">
                                @if(!empty($correctAns))
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-300">
                                        {{ $correctAns }}
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="hidden md:table-cell px-6 py-3 text-center">
                                @if($isCorrect)
                                    <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Benar</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">Salah</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-slate-500 text-xs">Belum ada rincian jawaban CBT.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
