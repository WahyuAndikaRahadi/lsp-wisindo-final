@extends('layouts.app', ['title' => 'FR.AK.03 Umpan Balik dan Catatan Asesmen'])

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6">
    {{-- Header & Navigasi Terstruktur --}}
    <div class="space-y-3 pb-2 border-b border-slate-200/80">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('asesi.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 hover:text-brand-800 transition py-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Dashboard</span>
            </a>
            @if($ak03)
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    ✓ Selesai
                </span>
            @endif
        </div>
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-black text-slate-900 tracking-tight leading-snug">
                FR.AK.03 Umpan Balik Asesmen
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 leading-relaxed max-w-2xl">
                Evaluasi asesi terhadap pelaksanaan uji kompetensi
            </p>
        </div>
    </div>

    @if($ak03)
        <div class="p-4 bg-emerald-50 border-2 border-emerald-300 rounded-2xl text-emerald-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0">✓</span>
                <div>
                    <span class="font-bold text-sm block">FR.AK.03 Telah Terkirim</span>
                    <span class="text-xs text-emerald-800 mt-0.5 leading-relaxed">Tersimpan pada {{ \Carbon\Carbon::parse($ak03->created_at)->isoFormat('D MMMM Y, HH:mm') }} WIB.</span>
                </div>
            </div>
            <a href="{{ route('asesi.dashboard') }}" class="w-full sm:w-auto shrink-0 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl transition shadow-xs text-center">
                Ke Dashboard &rarr;
            </a>
        </div>
    @endif

    {{-- Info Card --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm text-xs">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <span class="text-slate-500 block">Skema:</span>
                <span class="font-bold text-slate-900">{{ $assessment->schedule->scheme->name }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">TUK:</span>
                <span class="font-bold text-slate-900">{{ $assessment->schedule->tuk_name }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">Asesi:</span>
                <span class="font-bold text-slate-900">{{ $assessment->asesi->full_name }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">Asesor:</span>
                <span class="font-bold text-slate-900">{{ $assessment->assessor->full_name ?? 'Asesor LSP' }}</span>
            </div>
        </div>
    </div>

    @php
        $items = [
            1 => 'Saya mendapatkan penjelasan yang cukup memadai mengenai proses asesmen / uji kompetensi.',
            2 => 'Saya diberikan kesempatan untuk mempelajari standar kompetensi yang akan diujikan dan menilai diri sendiri terhadap pencapaiannya.',
            3 => 'Asesor memberikan kesempatan untuk mendiskusikan / menegosiasikan metoda, instrumen, dan sumber asesmen serta jadwal uji.',
            4 => 'Asesor berusaha menggali seluruh bukti pendukung yang sesuai dengan latar belakang pelatihan dan pengalaman saya.',
            5 => 'Saya sepenuhnya diberikan kesempatan untuk mendemonstrasikan kompetensi yang saya miliki selama asesmen berlangsung.',
            6 => 'Saya mendapatkan penjelasan yang memadai mengenai keputusan asesmen (K / BK).',
            7 => 'Asesor memberikan umpan balik yang membangun dan mendukung setelah asesmen serta arahan tindak lanjutnya.',
            8 => 'Asesor bersama saya mempelajari semua dokumen asesmen serta menandatanganinya secara transparan.',
            9 => 'Saya mendapatkan jaminan kerahasiaan hasil asesmen serta penjelasan mengenai penanganan berkas portofolio.',
            10 => 'Asesor menggunakan keterampilan komunikasi yang efektif, santun, dan profesional selama asesmen.',
        ];
        $savedRatings = $ak03?->ratings_json ?? [];
    @endphp

    <form action="{{ route('asesi.assessment.ak03.submit', $assessment->id) }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-4 sm:px-6 py-4 bg-slate-50 border-b border-slate-200">
                <h2 class="text-sm font-bold text-slate-900">Komponen Penilaian Asesmen</h2>
                <p class="text-xs text-slate-500 mt-0.5">Pilih YA jika sesuai atau TIDAK jika tidak terpenuhi</p>
            </div>

            <div class="p-3 sm:p-0 overflow-x-auto md:overflow-x-visible">
                <table class="w-full md:min-w-[650px] text-xs">
                    <thead class="hidden md:table-header-group bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-center w-12">No</th>
                            <th class="px-4 py-3 text-left">Pernyataan Evaluasi</th>
                            <th class="px-3 py-3 text-center w-28">Pilihan</th>
                            <th class="px-4 py-3 text-left w-64">Catatan (Opsional)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-0 md:divide-y divide-slate-100 block md:table-row-group space-y-3 md:space-y-0">
                        @foreach($items as $no => $text)
                            @php
                                $val = $savedRatings[$no]['value'] ?? null;
                                $comment = $savedRatings[$no]['comment'] ?? '';
                            @endphp
                            <tr class="flex flex-col bg-white rounded-xl border border-slate-200 p-3.5 shadow-2xs gap-2.5 md:table-row md:border-0 md:p-0 md:shadow-none md:hover:bg-slate-50/60 transition">
                                <td class="hidden md:table-cell px-4 py-3 text-center text-slate-500 font-mono">{{ $no }}</td>
                                <td class="w-full md:table-cell p-0 md:px-4 md:py-3 text-slate-800 leading-relaxed">
                                    <div class="flex items-start gap-2">
                                        <span class="md:hidden inline-flex items-center justify-center px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono font-bold text-[10px] shrink-0 mt-0.5">#{{ $no }}</span>
                                        <span>{{ $text }}</span>
                                    </div>
                                </td>
                                <td class="w-full md:w-28 md:table-cell p-0 md:px-3 md:py-3 text-center">
                                    <div class="grid grid-cols-2 gap-2 md:inline-flex md:items-center md:space-x-2">
                                        <label class="flex items-center justify-center gap-1.5 p-2 rounded-lg border border-emerald-200 bg-emerald-50/70 hover:bg-emerald-100 text-emerald-800 font-bold text-xs cursor-pointer md:border-0 md:p-0 md:bg-transparent min-h-[38px] md:min-h-0 {{ $ak03 ? 'cursor-default opacity-80' : '' }}">
                                            <input type="radio" name="ratings[{{ $no }}][value]" value="YA" {{ $val === 'YA' ? 'checked' : '' }} @disabled((bool) $ak03) required class="text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                            <span>Ya</span>
                                        </label>
                                        <label class="flex items-center justify-center gap-1.5 p-2 rounded-lg border border-rose-200 bg-rose-50/70 hover:bg-rose-100 text-rose-800 font-bold text-xs cursor-pointer md:border-0 md:p-0 md:bg-transparent min-h-[38px] md:min-h-0 {{ $ak03 ? 'cursor-default opacity-80' : '' }}">
                                            <input type="radio" name="ratings[{{ $no }}][value]" value="TIDAK" {{ $val === 'TIDAK' ? 'checked' : '' }} @disabled((bool) $ak03) required class="text-rose-600 focus:ring-rose-500 w-4 h-4">
                                            <span>Tidak</span>
                                        </label>
                                    </div>
                                </td>
                                <td class="w-full md:w-64 md:table-cell p-0 md:px-4 md:py-3">
                                    <div class="space-y-1">
                                        <span class="text-[11px] font-semibold text-slate-500 md:hidden block">Catatan (opsional):</span>
                                        <input type="text" name="ratings[{{ $no }}][comment]" value="{{ $comment }}" @disabled((bool) $ak03) class="w-full text-xs rounded-xl border border-slate-300 bg-white px-3.5 py-2 md:py-1.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs @if($ak03) bg-slate-50 text-slate-600 @endif" placeholder="Tulis catatan jika ada">
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 sm:p-6 border-t border-slate-200 bg-slate-50/50 space-y-2">
                <label class="block text-xs font-bold text-slate-700">Catatan / Komentar Tambahan (opsional):</label>
                <textarea name="general_notes" rows="2" @disabled((bool) $ak03) class="w-full p-3 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs @if($ak03) bg-slate-50 text-slate-600 @endif" placeholder="Kesan, pesan, atau masukan untuk pelaksanaan asesmen...">{{ $ak03?->general_notes ?? '' }}</textarea>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-1">
            <a href="{{ route('asesi.dashboard') }}" class="w-full sm:w-auto px-4 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-900 border border-slate-200 rounded-xl bg-white text-center transition">
                &larr; Kembali ke Dashboard
            </a>
            @if($ak03)
                <button type="button" disabled class="w-full sm:w-auto px-6 py-2.5 text-xs font-bold text-slate-500 bg-slate-200 border border-slate-300 rounded-xl cursor-not-allowed flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>Umpan Balik Terkirim (Terkunci)</span>
                </button>
            @else
                <button type="submit" class="w-full sm:w-auto px-6 py-3 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-xl shadow-sm transition flex items-center justify-center gap-2">
                    <span>Kirim Umpan Balik</span>
                    <span>✓</span>
                </button>
            @endif
        </div>
    </form>
</div>
@endsection
