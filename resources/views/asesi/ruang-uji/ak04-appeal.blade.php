@extends('layouts.app', ['title' => 'FR.AK.04 Pengajuan Banding Asesmen'])

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6">
    {{-- Header & Navigasi Terstruktur --}}
    <div class="space-y-3 pb-2 border-b border-slate-200/80">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('asesi.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-brand-600 hover:text-brand-800 transition py-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Kembali ke Dashboard</span>
            </a>
            @if($appeal)
                <span class="px-2.5 py-0.5 text-xs font-bold rounded-full border {{ $appeal->status === 'ACCEPTED' ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : ($appeal->status === 'REJECTED' ? 'bg-rose-50 text-rose-700 border-rose-300' : 'bg-purple-50 text-purple-700 border-purple-300') }}">
                    Status Banding: {{ $appeal->status }} (Terkunci)
                </span>
            @endif
        </div>
        <div>
            <h1 class="text-xl sm:text-2xl md:text-3xl font-black text-slate-900 tracking-tight leading-snug">
                FR.AK.04 Pengajuan Banding Asesmen
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 leading-relaxed max-w-2xl">
                Formulir resmi sanggahan hasil keputusan asesmen kompetensi
            </p>
        </div>
    </div>

    @if($appeal)
        <div class="p-4 bg-emerald-50 border-2 border-emerald-300 rounded-2xl text-emerald-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0">✓</span>
                <div>
                    <span class="font-bold text-sm block">Formulir FR.AK.04 Permohonan Banding Telah Terkirim (Terkunci)</span>
                    <span class="text-xs text-emerald-800 mt-0.5 leading-relaxed">Tersimpan pada {{ \Carbon\Carbon::parse($appeal->created_at)->isoFormat('D MMMM Y, HH:mm') }} WIB. Seluruh isian sanggahan telah tersimpan permanen dan tidak dapat diubah kembali.</span>
                </div>
            </div>
            <a href="{{ route('asesi.dashboard') }}" class="w-full sm:w-auto shrink-0 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl transition shadow-xs text-center">
                Ke Dashboard &rarr;
            </a>
        </div>
    @endif

    {{-- Info Card --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-sm text-xs">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
            <div>
                <span class="text-slate-500 block">Skema Sertifikasi:</span>
                <span class="font-bold text-slate-900">{{ $assessment->schedule->scheme->name }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">Asesor Penilai:</span>
                <span class="font-bold text-slate-900">{{ $assessment->assessor->full_name ?? '-' }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">Keputusan Asesmen Saat Ini:</span>
                <span class="font-bold {{ $assessment->final_result === 'KOMPETEN' ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ $assessment->final_result ?? 'BELUM SELESAI' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Appeal Deadline Information Banner --}}
    <div class="p-4 rounded-2xl border {{ $isWithinDeadline ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-rose-50 border-rose-200 text-rose-900' }} text-xs space-y-1.5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <span class="font-bold flex items-center gap-1.5">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Batas Pengajuan Banding: {{ $appealWindowDays }} Hari Kerja (hingga {{ $deadline->isoFormat('dddd, D MMMM Y') }})</span>
            </span>
            <span class="self-start sm:self-auto text-[10px] uppercase font-extrabold px-2.5 py-0.5 rounded-full {{ $isWithinDeadline ? 'bg-amber-200 text-amber-800' : 'bg-rose-200 text-rose-800' }}">
                {{ $isWithinDeadline ? 'Tenggat Terbuka' : 'Tenggat Berakhir' }}
            </span>
        </div>
        <p class="text-[11px] {{ $isWithinDeadline ? 'text-amber-800' : 'text-rose-800' }} leading-relaxed">
            * Catatan Regulasi: Batas {{ $appealWindowDays }} hari kerja dihitung hari kerja resmi (Senin-Jumat, mengabaikan Sabtu dan Minggu). Batas 3 hari kerja diambil dari praktik umum BNSP, belum dikonfirmasi dengan SOP LSP Wisindo
        </p>
    </div>

    @if($appeal && $appeal->status !== 'SUBMITTED')
        {{-- Decision Result Box --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-sm space-y-3 text-xs">
            <h3 class="text-sm font-bold text-slate-900">Keputusan Banding dari Admin LSP</h3>
            <div class="p-4 rounded-xl {{ $appeal->status === 'ACCEPTED' ? 'bg-emerald-50 border border-emerald-200' : 'bg-rose-50 border border-rose-200' }}">
                <p class="font-bold {{ $appeal->status === 'ACCEPTED' ? 'text-emerald-900' : 'text-rose-900' }}">
                    Status Keputusan: {{ $appeal->status === 'ACCEPTED' ? 'BANDING DITERIMA' : 'BANDING DITOLAK' }}
                </p>
                <p class="mt-1 text-slate-700 leading-relaxed">{{ $appeal->decision_notes ?? 'Tidak ada catatan tambahan.' }}</p>
                <p class="text-[11px] text-slate-500 mt-2">Ditetapkan tanggal: {{ \Carbon\Carbon::parse($appeal->decision_date)->isoFormat('D MMMM Y') }}</p>
            </div>
        </div>
    @endif

    <form action="{{ route('asesi.assessment.appeal.submit', $assessment->id) }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-sm space-y-5 text-xs">
            <div>
                <h2 class="text-sm sm:text-base font-bold text-slate-900">Formulir Permohonan Banding (FR.AK.04)</h2>
                <p class="text-[11px] sm:text-xs text-slate-500 mt-0.5 leading-relaxed">Sesuai standar BNSP, lengkapi pertanyaan klarifikasi dan rincian alasan banding di bawah ini.</p>
            </div>

            {{-- 1. Pertanyaan Klarifikasi Proses Banding BNSP --}}
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 font-bold text-slate-800 flex items-center justify-between">
                    <span>1. Proses Banding (Jawablah dengan Ya atau Tidak):</span>
                    <span class="text-[11px] font-normal text-slate-500">Wajib Dipilih</span>
                </div>
                <div class="p-3 sm:p-0 overflow-x-auto md:overflow-x-visible">
                    <table class="w-full md:min-w-[450px] text-xs">
                    <thead class="hidden md:table-header-group bg-slate-100/70 text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-bold">Pertanyaan Klarifikasi</th>
                            <th class="px-3 py-2.5 text-center w-24 font-bold">YA</th>
                            <th class="px-3 py-2.5 text-center w-24 font-bold">TIDAK</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-0 md:divide-y divide-slate-100 block md:table-row-group space-y-3 md:space-y-0">
                        <tr class="flex flex-wrap bg-white rounded-xl border border-slate-200 p-3.5 shadow-2xs gap-y-2 md:table-row md:border-0 md:p-0 md:shadow-none md:hover:bg-slate-50/50">
                            <td class="w-full md:w-auto md:table-cell p-0 md:px-4 md:py-3 text-slate-800 font-medium">
                                Apakah Proses Banding telah dijelaskan kepada Anda?
                            </td>
                            <td class="w-1/2 pr-1 md:pr-0 md:w-24 md:table-cell p-0 md:px-3 md:py-3 text-center">
                                <label class="flex items-center justify-center gap-1.5 p-2 rounded-lg border border-emerald-200 bg-emerald-50/70 hover:bg-emerald-100 text-emerald-800 font-bold text-xs cursor-pointer md:border-0 md:p-0 md:bg-transparent min-h-[38px] md:min-h-0 {{ $appeal ? 'cursor-default opacity-80' : '' }}">
                                    <input type="radio" name="is_process_explained" value="1" {{ old('is_process_explained', $appeal?->is_process_explained ?? true) ? 'checked' : '' }} @disabled((bool) $appeal) required class="text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                    <span>Ya</span>
                                </label>
                            </td>
                            <td class="w-1/2 pl-1 md:pl-0 md:w-24 md:table-cell p-0 md:px-3 md:py-3 text-center">
                                <label class="flex items-center justify-center gap-1.5 p-2 rounded-lg border border-rose-200 bg-rose-50/70 hover:bg-rose-100 text-rose-800 font-bold text-xs cursor-pointer md:border-0 md:p-0 md:bg-transparent min-h-[38px] md:min-h-0 {{ $appeal ? 'cursor-default opacity-80' : '' }}">
                                    <input type="radio" name="is_process_explained" value="0" {{ !old('is_process_explained', $appeal?->is_process_explained ?? true) ? 'checked' : '' }} @disabled((bool) $appeal) required class="text-rose-600 focus:ring-rose-500 w-4 h-4">
                                    <span>Tidak</span>
                                </label>
                            </td>
                        </tr>
                        <tr class="flex flex-wrap bg-white rounded-xl border border-slate-200 p-3.5 shadow-2xs gap-y-2 md:table-row md:border-0 md:p-0 md:shadow-none md:hover:bg-slate-50/50">
                            <td class="w-full md:w-auto md:table-cell p-0 md:px-4 md:py-3 text-slate-800 font-medium">
                                Apakah Anda telah mendiskusikan Banding dengan Asesor?
                            </td>
                            <td class="w-1/2 pr-1 md:pr-0 md:w-24 md:table-cell p-0 md:px-3 md:py-3 text-center">
                                <label class="flex items-center justify-center gap-1.5 p-2 rounded-lg border border-emerald-200 bg-emerald-50/70 hover:bg-emerald-100 text-emerald-800 font-bold text-xs cursor-pointer md:border-0 md:p-0 md:bg-transparent min-h-[38px] md:min-h-0 {{ $appeal ? 'cursor-default opacity-80' : '' }}">
                                    <input type="radio" name="is_discussed_with_assessor" value="1" {{ old('is_discussed_with_assessor', $appeal?->is_discussed_with_assessor ?? true) ? 'checked' : '' }} @disabled((bool) $appeal) required class="text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                    <span>Ya</span>
                                </label>
                            </td>
                            <td class="w-1/2 pl-1 md:pl-0 md:w-24 md:table-cell p-0 md:px-3 md:py-3 text-center">
                                <label class="flex items-center justify-center gap-1.5 p-2 rounded-lg border border-rose-200 bg-rose-50/70 hover:bg-rose-100 text-rose-800 font-bold text-xs cursor-pointer md:border-0 md:p-0 md:bg-transparent min-h-[38px] md:min-h-0 {{ $appeal ? 'cursor-default opacity-80' : '' }}">
                                    <input type="radio" name="is_discussed_with_assessor" value="0" {{ !old('is_discussed_with_assessor', $appeal?->is_discussed_with_assessor ?? true) ? 'checked' : '' }} @disabled((bool) $appeal) required class="text-rose-600 focus:ring-rose-500 w-4 h-4">
                                    <span>Tidak</span>
                                </label>
                            </td>
                        </tr>
                        <tr class="flex flex-wrap bg-white rounded-xl border border-slate-200 p-3.5 shadow-2xs gap-y-2 md:table-row md:border-0 md:p-0 md:shadow-none md:hover:bg-slate-50/50">
                            <td class="w-full md:w-auto md:table-cell p-0 md:px-4 md:py-3 text-slate-800 font-medium">
                                Apakah Anda mau melibatkan "orang lain" membantu Anda dalam Proses Banding?
                            </td>
                            <td class="w-1/2 pr-1 md:pr-0 md:w-24 md:table-cell p-0 md:px-3 md:py-3 text-center">
                                <label class="flex items-center justify-center gap-1.5 p-2 rounded-lg border border-emerald-200 bg-emerald-50/70 hover:bg-emerald-100 text-emerald-800 font-bold text-xs cursor-pointer md:border-0 md:p-0 md:bg-transparent min-h-[38px] md:min-h-0 {{ $appeal ? 'cursor-default opacity-80' : '' }}">
                                    <input type="radio" name="involves_other_person" value="1" {{ old('involves_other_person', $appeal?->involves_other_person ?? false) ? 'checked' : '' }} @disabled((bool) $appeal) required class="text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                                    <span>Ya</span>
                                </label>
                            </td>
                            <td class="w-1/2 pl-1 md:pl-0 md:w-24 md:table-cell p-0 md:px-3 md:py-3 text-center">
                                <label class="flex items-center justify-center gap-1.5 p-2 rounded-lg border border-rose-200 bg-rose-50/70 hover:bg-rose-100 text-rose-800 font-bold text-xs cursor-pointer md:border-0 md:p-0 md:bg-transparent min-h-[38px] md:min-h-0 {{ $appeal ? 'cursor-default opacity-80' : '' }}">
                                    <input type="radio" name="involves_other_person" value="0" {{ !old('involves_other_person', $appeal?->involves_other_person ?? false) ? 'checked' : '' }} @disabled((bool) $appeal) required class="text-rose-600 focus:ring-rose-500 w-4 h-4">
                                    <span>Tidak</span>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                    </table>
                </div>
                <div class="p-3 bg-slate-50/60 border-t border-slate-200">
                    <label class="block font-semibold text-slate-700 mb-1">Nama Orang Lain / Pendamping (jika memilih Ya pada pertanyaan di atas):</label>
                    <input type="text" name="other_person_name" value="{{ old('other_person_name', $appeal?->other_person_name) }}" @disabled((bool) $appeal) class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs @if($appeal) bg-slate-100 text-slate-600 @else bg-white @endif" placeholder="Nama perwakilan / pembimbing / saksi pendamping...">
                </div>
            </div>

            {{-- 2. Alasan Pengajuan Banding --}}
            <div>
                <label class="block font-bold text-slate-800 mb-1">2. Alasan &amp; Uraian Pengajuan Banding (*):</label>
                <p class="text-[11px] text-slate-500 mb-1.5 leading-relaxed">Jelaskan secara rinci alasan ketidaksetujuan terhadap keputusan, bukti yang diabaikan, atau ketidaksesuaian prosedur pelaksanaan asesmen (misal: asesor tidak profesional, bermain HP saat observasi praktik, dsb).</p>
                <textarea name="appeal_reasons" rows="4" required @disabled((bool) $appeal) class="w-full p-3 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs leading-relaxed @if($appeal) bg-slate-50 text-slate-600 @else bg-white @endif" placeholder="Tuliskan alasan pengajuan banding secara jelas dan terperinci...">{{ old('appeal_reasons', $appeal?->appeal_reasons) }}</textarea>
                @error('appeal_reasons')
                    <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            {{-- 3. Unit Kompetensi yang Dipersoalkan --}}
            <div>
                <label class="block font-bold text-slate-800 mb-1">3. Unit Kompetensi yang Dipersoalkan / Disanggah (Pilih yang relevan):</label>
                <p class="text-[11px] text-slate-500 mb-2 leading-relaxed">Pilih satu atau lebih unit kompetensi yang menjadi materi sanggahan banding Anda.</p>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-slate-200 rounded-lg p-3 bg-slate-50">
                    @php $contested = old('contested_units', $appeal?->contested_units ?? []); @endphp
                    @foreach($units as $u)
                        <label class="flex items-start space-x-2 {{ $appeal ? 'cursor-default' : 'cursor-pointer' }} hover:bg-white p-1 rounded transition">
                            <input type="checkbox" name="contested_units[]" value="{{ $u->code ?? $u->id }}" {{ in_array($u->code ?? $u->id, $contested) || in_array($u->id, $contested) ? 'checked' : '' }} @disabled((bool) $appeal) class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span class="text-slate-800">
                                <strong class="font-mono text-slate-900">{{ $u->code }}</strong> - {{ $u->title }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <a href="{{ route('asesi.dashboard') }}" class="w-full sm:w-auto px-4 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-900 border border-slate-200 rounded-xl bg-white text-center transition">
                    &larr; Kembali ke Dashboard
                </a>
                @if($appeal)
                    <button type="button" disabled class="w-full sm:w-auto px-6 py-2.5 text-xs font-bold text-slate-500 bg-slate-200 border border-slate-300 rounded-xl cursor-not-allowed flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>Permohonan Banding Telah Terkirim (Terkunci)</span>
                    </button>
                @elseif(! $isWithinDeadline)
                    <div class="w-full sm:w-auto text-center sm:text-right">
                        <span class="text-xs text-rose-600 font-bold block mb-1">Tenggat pengajuan banding telah berakhir.</span>
                        <button type="button" disabled class="w-full sm:w-auto px-6 py-2.5 text-xs font-bold text-white bg-slate-400 rounded-xl cursor-not-allowed">
                            Batas Waktu Habis
                        </button>
                    </div>
                @else
                    <button type="submit" class="w-full sm:w-auto px-6 py-3 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-sm transition flex items-center justify-center gap-1.5">
                        <span>Kirim Pengajuan Banding (FR.AK.04)</span>
                        <span>&rarr;</span>
                    </button>
                @endif
            </div>
        </div>
    </form>
</div>
@endsection
