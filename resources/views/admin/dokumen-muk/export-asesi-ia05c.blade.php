@extends('layouts.print-muk', [
    'title' => 'FR.IA.05.C Lembar Jawaban Pilihan Ganda - ' . $asesi->full_name,
    'docCode' => 'FR.IA.05.C',
    'docTitle' => 'DPT - LEMBAR JAWABAN PILIHAN GANDA'
])

@section('print-content')
@php
    $cbtResponse = $iaResponses->firstWhere('instrument_code', 'IA05');
    $savedData = is_array($cbtResponse?->data_json)
        ? $cbtResponse->data_json
        : (json_decode($cbtResponse?->data_json ?? '[]', true) ?: []);

    $answers = $savedData['answers'] ?? [];
    $breakdown = $savedData['breakdown'] ?? [];
    $score = (float) ($cbtResponse?->score ?? 0);
    $passingGrade = (float) ($scheme->passing_grade_theory ?? 70.0);
    $isPassed = $score >= $passingGrade;

    // Retrieve the exact questions the candidate worked on
    $questionIds = !empty($breakdown)
        ? array_keys($breakdown)
        : (!empty($answers) ? array_keys($answers) : ($schedule->getCbtQuestionIds() ?: []));

    if (empty($questionIds)) {
        $questionIds = $questionBanks->pluck('id')->take(30)->toArray();
    }

    $questionModels = \App\Models\QuestionBank::whereIn('id', $questionIds)->get()->keyBy('id');
    $totalQuestions = count($questionIds);

    // Build standard 30 rows matching BNSP master (15 left, 15 right)
    $gridRows = [];
    for ($i = 0; $i < 30; $i++) {
        $qNum = $i + 1;
        if ($i < $totalQuestions) {
            $qid = $questionIds[$i];
            $bData = $breakdown[$qid] ?? null;
            $userAns = $bData['answer'] ?? ($answers[$qid] ?? null);
            $userLetter = '';
            if ($userAns !== null && $userAns !== '') {
                $cleaned = strtoupper(trim((string) $userAns));
                if (in_array($cleaned, ['A', 'B', 'C', 'D'], true)) {
                    $userLetter = $cleaned;
                } elseif (strlen($cleaned) > 0 && in_array($cleaned[0], ['A', 'B', 'C', 'D'], true)) {
                    $userLetter = $cleaned[0];
                }
            }

            $correctAns = $bData['correct_answer'] ?? ($questionModels->get($qid)?->correct_answer ?? null);
            if ($correctAns !== null) {
                $correctAns = strtoupper(trim((string) $correctAns));
            }

            $isCorrect = null;
            if (isset($bData['is_correct'])) {
                $isCorrect = (bool) $bData['is_correct'];
            } elseif ($userLetter !== '' && $correctAns !== null) {
                $isCorrect = ($userLetter === $correctAns);
            } elseif ($userLetter === '') {
                $isCorrect = false;
            }

            $gridRows[$qNum] = [
                'num' => $qNum,
                'letter' => $userLetter,
                'is_correct' => $isCorrect,
                'is_active' => true,
            ];
        } else {
            $gridRows[$qNum] = [
                'num' => $qNum,
                'letter' => '',
                'is_correct' => null,
                'is_active' => false,
            ];
        }
    }
@endphp

@include('admin.dokumen-muk.partials.meta-box')

<div class="mb-2 text-xs font-bold text-slate-800">
    Lembar Jawaban Pertanyaan Tertulis – Pilihan Ganda :
</div>

{{-- Answer Grid (2 Columns: 1-15 and 16-30 matching master BNSP) --}}
<div class="grid grid-cols-2 gap-4 text-xs mb-4">
    {{-- Kolom Kiri 1 - 15 --}}
    <table class="w-full border border-black border-collapse text-xs">
        <thead>
            <tr class="bg-slate-200 border-b border-black text-center font-bold">
                <th rowspan="2" class="p-1 border-r border-black w-8">No.</th>
                <th rowspan="2" class="p-1 border-r border-black w-20">Jawaban</th>
                <th colspan="2" class="p-1 border-b border-black">Pencapaian</th>
            </tr>
            <tr class="bg-slate-100 border-b border-black text-center font-bold text-[11px]">
                <th class="p-1 border-r border-black w-14">Ya</th>
                <th class="p-1 w-14">Tidak</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 1; $i <= 15; $i++)
                @php
                    $row = $gridRows[$i];
                @endphp
                <tr class="border-b border-black hover:bg-slate-50">
                    <td class="p-1 text-center font-mono font-bold border-r border-black">{{ $i }}.</td>
                    <td class="p-1 text-center font-bold font-mono border-r border-black text-sm">
                        {{ $row['letter'] ?: '-' }}
                    </td>
                    <td class="p-1 text-center border-r border-black">
                        @if($row['is_active'])
                            <div class="inline-flex items-center justify-center w-4 h-4 border border-black text-xs font-black {{ $row['is_correct'] === true ? 'bg-black text-white' : '' }}">
                                {{ $row['is_correct'] === true ? '✓' : '' }}
                            </div>
                        @else
                            <div class="inline-flex items-center justify-center w-4 h-4 border border-slate-300"></div>
                        @endif
                    </td>
                    <td class="p-1 text-center">
                        @if($row['is_active'])
                            <div class="inline-flex items-center justify-center w-4 h-4 border border-black text-xs font-black {{ $row['is_correct'] === false ? 'bg-black text-white' : '' }}">
                                {{ $row['is_correct'] === false ? '✓' : '' }}
                            </div>
                        @else
                            <div class="inline-flex items-center justify-center w-4 h-4 border border-slate-300"></div>
                        @endif
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>

    {{-- Kolom Kanan 16 - 30 --}}
    <table class="w-full border border-black border-collapse text-xs">
        <thead>
            <tr class="bg-slate-200 border-b border-black text-center font-bold">
                <th rowspan="2" class="p-1 border-r border-black w-8">No.</th>
                <th rowspan="2" class="p-1 border-r border-black w-20">Jawaban</th>
                <th colspan="2" class="p-1 border-b border-black">Pencapaian</th>
            </tr>
            <tr class="bg-slate-100 border-b border-black text-center font-bold text-[11px]">
                <th class="p-1 border-r border-black w-14">Ya</th>
                <th class="p-1 w-14">Tidak</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 16; $i <= 30; $i++)
                @php
                    $row = $gridRows[$i];
                @endphp
                <tr class="border-b border-black hover:bg-slate-50">
                    <td class="p-1 text-center font-mono font-bold border-r border-black">{{ $i }}.</td>
                    <td class="p-1 text-center font-bold font-mono border-r border-black text-sm">
                        {{ $row['letter'] ?: '-' }}
                    </td>
                    <td class="p-1 text-center border-r border-black">
                        @if($row['is_active'])
                            <div class="inline-flex items-center justify-center w-4 h-4 border border-black text-xs font-black {{ $row['is_correct'] === true ? 'bg-black text-white' : '' }}">
                                {{ $row['is_correct'] === true ? '✓' : '' }}
                            </div>
                        @else
                            <div class="inline-flex items-center justify-center w-4 h-4 border border-slate-300"></div>
                        @endif
                    </td>
                    <td class="p-1 text-center">
                        @if($row['is_active'])
                            <div class="inline-flex items-center justify-center w-4 h-4 border border-black text-xs font-black {{ $row['is_correct'] === false ? 'bg-black text-white' : '' }}">
                                {{ $row['is_correct'] === false ? '✓' : '' }}
                            </div>
                        @else
                            <div class="inline-flex items-center justify-center w-4 h-4 border border-slate-300"></div>
                        @endif
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>
</div>

{{-- Result & Achievement Box --}}
<table class="w-full text-xs border border-black mb-4 border-collapse">
    <tr class="bg-slate-100 font-bold border-b border-black">
        <td class="p-2 w-1/3 border-r border-black">Hasil Pencapaian Skor CBT / Tertulis</td>
        <td class="p-2 w-1/3 border-r border-black text-center">Passing Grade (KKM)</td>
        <td class="p-2 w-1/3 text-center">Keputusan Uji Tertulis</td>
    </tr>
    <tr>
        <td class="p-3 border-r border-black">
            <span class="text-xl font-black block">{{ number_format($score, 1) }} / 100</span>
            <span class="text-[10px] text-slate-500">Skor capaian jawaban benar</span>
        </td>
        <td class="p-3 border-r border-black text-center">
            <span class="text-lg font-bold block">{{ number_format($passingGrade, 1) }}</span>
            <span class="text-[10px] text-slate-500">Standar minimal kelulusan teori</span>
        </td>
        <td class="p-3 text-center bg-slate-50">
            <span class="inline-block px-3 py-1 rounded border border-black font-black text-sm {{ $isPassed ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                {{ $isPassed ? 'MEMENUHI (KOMPETEN)' : 'BELUM MEMENUHI' }}
            </span>
        </td>
    </tr>
</table>

{{-- Feedback & Signature Box (Page 2 style) --}}
<div class="border border-black p-4 text-xs">
    <div class="mb-4 pb-3 border-b border-slate-200">
        <div class="font-bold text-slate-900 mb-1">Umpan Balik untuk Asesi:</div>
        <p class="text-slate-700 leading-relaxed">
            Aspek pengetahuan seluruh unit pada kelompok pekerjaan yang diujikan
            <strong>({{ $isPassed ? 'TERCAPAI' : 'BELUM TERCAPAI' }})*</strong>.
        </p>
        @if(!empty($cbtResponse?->assessor_notes))
            <p class="text-slate-600 mt-1 italic">
                Catatan Asesor: {{ $cbtResponse->assessor_notes }}
            </p>
        @endif
    </div>

    @php
        $asesiSig = $profile?->signature_data ?: ($assessment->asesi_signature ?: ($cbtResponse?->asesi_signature ?: $iaResponses->pluck('asesi_signature')->filter()->first()));
        $assessorSig = $assessor?->profile?->signature_data ?: ($assessment->assessor_signature ?: ($cbtResponse?->assessor_signature ?: $iaResponses->pluck('assessor_signature')->filter()->first()));
        $assessorReg = $assessor?->assessor_registration_number ?? ($assessor?->profile?->met_reg_number ?? ($assessor?->profile?->no_reg ?? '-'));
    @endphp

    <div class="grid grid-cols-2 gap-8 text-xs">
        <div>
            <p class="font-bold text-slate-800 mb-1">Peserta Uji (Asesi):</p>
            <div class="h-16 flex items-center">
                @if($asesiSig)
                    <img src="{{ $asesiSig }}" alt="TTD Asesi" class="max-h-14">
                @else
                    <span class="text-[10px] text-slate-400 italic">[Tanda Tangan Digital]</span>
                @endif
            </div>
            <div class="border-b border-black w-48 mb-1"></div>
            <p class="font-bold">{{ $asesi->full_name }}</p>
            <p class="text-[10px] text-slate-500 font-mono">NIK: {{ $profile?->nik ?? '-' }}</p>
        </div>
        <div>
            <p class="font-bold text-slate-800 mb-1">Asesor Kompetensi:</p>
            <div class="h-16 flex items-center">
                @if($assessorSig)
                    <img src="{{ $assessorSig }}" alt="TTD Asesor" class="max-h-14">
                @else
                    <span class="text-[10px] text-slate-400 italic">[Tanda Tangan Digital]</span>
                @endif
            </div>
            <div class="border-b border-black w-48 mb-1"></div>
            <p class="font-bold">{{ $assessor->full_name ?? '-' }}</p>
            <p class="text-[10px] text-slate-500 font-mono">No. MET: {{ $assessorReg }}</p>
        </div>
    </div>
</div>
@endsection
