@extends('layouts.print-muk', [
    'title' => 'FR.IA.05.A Lembar Soal Uji Tertulis - ' . $asesi->full_name,
    'docCode' => 'FR.IA.05.A',
    'docTitle' => 'DPT - PERTANYAAN TERTULIS PILIHAN GANDA (LEMBAR SOAL)'
])

@section('print-content')
{{-- Metadata Box --}}
<table class="w-full text-xs border border-black mb-4">
    <tr>
        <td class="w-1/3 p-2 font-bold bg-slate-100 border-r border-black align-top">
            Skema Sertifikasi<br>
            <span class="font-normal text-[10px] text-slate-600">(KKNI / Okupasi / Klaster)</span>
        </td>
        <td class="w-2/3 p-2 border-black">
            <div class="grid grid-cols-12 gap-1">
                <span class="col-span-2 font-semibold">Judul</span>
                <span class="col-span-10">: <strong>{{ $scheme->name }}</strong></span>
                <span class="col-span-2 font-semibold">Nomor</span>
                <span class="col-span-10 font-mono">: {{ $scheme->code }}</span>
            </div>
        </td>
    </tr>
    <tr class="border-t border-black">
        <td class="p-2 font-bold bg-slate-100 border-r border-black">TUK</td>
        <td class="p-2 font-bold">{{ $schedule->tuk_name ?? 'Sewaktu / Tempat Kerja / Mandiri' }}</td>
    </tr>
    <tr class="border-t border-black">
        <td class="p-2 font-bold bg-slate-100 border-r border-black">Nama Asesor</td>
        <td class="p-2 font-semibold">{{ $assessor->full_name ?? '-' }} (No. MET: {{ $assessor->profile?->no_reg_asesor ?? '-' }})</td>
    </tr>
    <tr class="border-t border-black">
        <td class="p-2 font-bold bg-slate-100 border-r border-black">Nama Asesi</td>
        <td class="p-2 font-bold">{{ $asesi->full_name }} (NIK: {{ $profile?->nik ?? '-' }})</td>
    </tr>
    <tr class="border-t border-black">
        <td class="p-2 font-bold bg-slate-100 border-r border-black">Tanggal & Waktu</td>
        <td class="p-2">{{ \Carbon\Carbon::parse($schedule->date)->translatedFormat('d F Y') }} | Durasi: 60 Menit</td>
    </tr>
</table>

{{-- Instructions Box --}}
<div class="border border-black p-3 bg-slate-50 text-[11px] mb-4">
    <span class="font-bold block mb-1">Petunjuk Pengerjaan Soal:</span>
    <ul class="list-disc pl-5 space-y-0.5 text-slate-700">
        <li>Bacalah seluruh soal dengan saksama sebelum menentukan pilihan jawaban Anda.</li>
        <li>Pilihlah satu jawaban yang paling tepat dengan memberi tanda silang (X) atau menghitamkan bulatan pada Lembar Jawaban (FR.IA.05.C).</li>
        <li>Dilarang mencoret-coret, melipat, atau merusak lembar soal ini. Seluruh lembar soal dikumpulkan kembali ke Asesor setelah ujian selesai.</li>
    </ul>
</div>

{{-- Questions List --}}
<div class="space-y-4 text-xs">
    @forelse($questionBanks as $idx => $q)
        <div class="p-3 border border-slate-300 rounded bg-white">
            <div class="flex items-start gap-2 mb-2 font-bold">
                <span class="w-6 shrink-0">{{ $idx + 1 }}.</span>
                <span class="text-slate-900 leading-snug">{{ $q->question_text }}</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 pl-8 text-[11px]">
                <div class="p-1 rounded bg-slate-50"><strong>A.</strong> {{ $q->option_a }}</div>
                <div class="p-1 rounded bg-slate-50"><strong>B.</strong> {{ $q->option_b }}</div>
                <div class="p-1 rounded bg-slate-50"><strong>C.</strong> {{ $q->option_c }}</div>
                <div class="p-1 rounded bg-slate-50"><strong>D.</strong> {{ $q->option_d }}</div>
            </div>
        </div>
    @empty
        <div class="p-6 text-center text-slate-400 border border-dashed border-slate-300 italic">
            Daftar pertanyaan tertulis pilihan ganda untuk skema ini terintegrasi dalam modul Computer Based Test (CBT) online.
        </div>
    @endforelse
</div>
@endsection
