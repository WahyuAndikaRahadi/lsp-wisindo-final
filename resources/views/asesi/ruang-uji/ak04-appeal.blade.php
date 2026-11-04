@extends('layouts.app', ['title' => 'FR.AK.04 Pengajuan Banding Asesmen'])

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    @if($appeal)
        <div class="p-4 bg-emerald-50 border-2 border-emerald-300 rounded-xl text-emerald-900 flex items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-2.5">
                <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0">✓</span>
                <div>
                    <span class="font-bold text-sm block">Formulir FR.AK.04 Permohonan Banding Telah Terkirim (Terkunci)</span>
                    <span class="text-xs text-emerald-800">Tersimpan pada {{ \Carbon\Carbon::parse($appeal->created_at)->isoFormat('D MMMM Y, HH:mm') }} WIB. Seluruh isian sanggahan telah tersimpan permanen dan tidak dapat diubah kembali.</span>
                </div>
            </div>
            <a href="{{ route('asesi.dashboard') }}" class="shrink-0 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg transition shadow-xs">
                Ke Dashboard &rarr;
            </a>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('asesi.dashboard') }}" class="text-xs text-brand-600 hover:text-brand-700 font-semibold">&larr; Kembali ke Dashboard</a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">FR.AK.04 Pengajuan Banding Asesmen</h1>
            <p class="text-xs text-slate-500">Formulir resmi sanggahan hasil keputusan asesmen kompetensi</p>
        </div>
        @if($appeal)
            <span class="px-3 py-1 text-xs font-bold rounded-lg border {{ $appeal->status === 'ACCEPTED' ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : ($appeal->status === 'REJECTED' ? 'bg-rose-50 text-rose-700 border-rose-300' : 'bg-purple-50 text-purple-700 border-purple-300') }}">
                Status Banding: {{ $appeal->status }} (Terkunci)
            </span>
        @endif
    </div>

    {{-- Info Card --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm text-xs">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
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
    <div class="p-4 rounded-xl border {{ $isWithinDeadline ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-rose-50 border-rose-200 text-rose-900' }} text-xs space-y-1">
        <div class="flex items-center justify-between">
            <span class="font-bold flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Batas Pengajuan Banding: {{ $appealWindowDays }} Hari Kerja (hingga {{ $deadline->isoFormat('dddd, D MMMM Y') }})
            </span>
            <span class="text-[10px] uppercase font-extrabold px-2 py-0.5 rounded-full {{ $isWithinDeadline ? 'bg-amber-200 text-amber-800' : 'bg-rose-200 text-rose-800' }}">
                {{ $isWithinDeadline ? 'Tenggat Terbuka' : 'Tenggat Berakhir' }}
            </span>
        </div>
        <p class="text-[11px] {{ $isWithinDeadline ? 'text-amber-800' : 'text-rose-800' }}">
            * Catatan Regulasi: Batas {{ $appealWindowDays }} hari kerja dihitung hari kerja resmi (Senin-Jumat, mengabaikan Sabtu dan Minggu). Batas 3 hari kerja diambil dari praktik umum BNSP, belum dikonfirmasi dengan SOP LSP PrO Indo.
        </p>
    </div>

    @if($appeal && $appeal->status !== 'SUBMITTED')
        {{-- Decision Result Box --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-3 text-xs">
            <h3 class="text-sm font-bold text-slate-900">Keputusan Banding dari Admin LSP</h3>
            <div class="p-4 rounded-lg {{ $appeal->status === 'ACCEPTED' ? 'bg-emerald-50 border border-emerald-200' : 'bg-rose-50 border border-rose-200' }}">
                <p class="font-bold {{ $appeal->status === 'ACCEPTED' ? 'text-emerald-900' : 'text-rose-900' }}">
                    Status Keputusan: {{ $appeal->status === 'ACCEPTED' ? 'BANDING DITERIMA' : 'BANDING DITOLAK' }}
                </p>
                <p class="mt-1 text-slate-700">{{ $appeal->decision_notes ?? 'Tidak ada catatan tambahan.' }}</p>
                <p class="text-[11px] text-slate-500 mt-2">Ditetapkan tanggal: {{ \Carbon\Carbon::parse($appeal->decision_date)->isoFormat('D MMMM Y') }}</p>
            </div>
        </div>
    @endif

    <form action="{{ route('asesi.assessment.appeal.submit', $assessment->id) }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-5 text-xs">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Formulir Permohonan Banding (FR.AK.04)</h2>
                <p class="text-[11px] text-slate-500 mt-0.5">Sesuai standar BNSP, lengkapi pertanyaan klarifikasi dan rincian alasan banding di bawah ini.</p>
            </div>

            {{-- 1. Pertanyaan Klarifikasi Proses Banding BNSP --}}
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 font-bold text-slate-800 flex items-center justify-between">
                    <span>1. Proses Banding (Jawablah dengan Ya atau Tidak):</span>
                    <span class="text-[11px] font-normal text-slate-500">Wajib Dipilih</span>
                </div>
                <table class="w-full text-xs">
                    <thead class="bg-slate-100/70 text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-bold">Pertanyaan Klarifikasi</th>
                            <th class="px-3 py-2.5 text-center w-24 font-bold">YA</th>
                            <th class="px-3 py-2.5 text-center w-24 font-bold">TIDAK</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-4 py-3 text-slate-800">
                                Apakah Proses Banding telah dijelaskan kepada Anda?
                            </td>
                            <td class="px-3 py-3 text-center">
                                <label class="inline-flex items-center gap-1 {{ $appeal ? 'cursor-default' : 'cursor-pointer' }}">
                                    <input type="radio" name="is_process_explained" value="1" {{ old('is_process_explained', $appeal?->is_process_explained ?? true) ? 'checked' : '' }} @disabled((bool) $appeal) required class="text-brand-600 focus:ring-brand-500">
                                    <span class="font-bold text-emerald-700">Ya</span>
                                </label>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <label class="inline-flex items-center gap-1 {{ $appeal ? 'cursor-default' : 'cursor-pointer' }}">
                                    <input type="radio" name="is_process_explained" value="0" {{ !old('is_process_explained', $appeal?->is_process_explained ?? true) ? 'checked' : '' }} @disabled((bool) $appeal) required class="text-rose-600 focus:ring-rose-500">
                                    <span class="font-bold text-rose-700">Tidak</span>
                                </label>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-4 py-3 text-slate-800">
                                Apakah Anda telah mendiskusikan Banding dengan Asesor?
                            </td>
                            <td class="px-3 py-3 text-center">
                                <label class="inline-flex items-center gap-1 {{ $appeal ? 'cursor-default' : 'cursor-pointer' }}">
                                    <input type="radio" name="is_discussed_with_assessor" value="1" {{ old('is_discussed_with_assessor', $appeal?->is_discussed_with_assessor ?? true) ? 'checked' : '' }} @disabled((bool) $appeal) required class="text-brand-600 focus:ring-brand-500">
                                    <span class="font-bold text-emerald-700">Ya</span>
                                </label>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <label class="inline-flex items-center gap-1 {{ $appeal ? 'cursor-default' : 'cursor-pointer' }}">
                                    <input type="radio" name="is_discussed_with_assessor" value="0" {{ !old('is_discussed_with_assessor', $appeal?->is_discussed_with_assessor ?? true) ? 'checked' : '' }} @disabled((bool) $appeal) required class="text-rose-600 focus:ring-rose-500">
                                    <span class="font-bold text-rose-700">Tidak</span>
                                </label>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-4 py-3 text-slate-800">
                                Apakah Anda mau melibatkan "orang lain" membantu Anda dalam Proses Banding?
                            </td>
                            <td class="px-3 py-3 text-center">
                                <label class="inline-flex items-center gap-1 {{ $appeal ? 'cursor-default' : 'cursor-pointer' }}">
                                    <input type="radio" name="involves_other_person" value="1" {{ old('involves_other_person', $appeal?->involves_other_person ?? false) ? 'checked' : '' }} @disabled((bool) $appeal) required class="text-brand-600 focus:ring-brand-500">
                                    <span class="font-bold text-emerald-700">Ya</span>
                                </label>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <label class="inline-flex items-center gap-1 {{ $appeal ? 'cursor-default' : 'cursor-pointer' }}">
                                    <input type="radio" name="involves_other_person" value="0" {{ !old('involves_other_person', $appeal?->involves_other_person ?? false) ? 'checked' : '' }} @disabled((bool) $appeal) required class="text-rose-600 focus:ring-rose-500">
                                    <span class="font-bold text-rose-700">Tidak</span>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="p-3 bg-slate-50/60 border-t border-slate-200">
                    <label class="block font-semibold text-slate-700 mb-1">Nama Orang Lain / Pendamping (jika memilih Ya pada pertanyaan di atas):</label>
                    <input type="text" name="other_person_name" value="{{ old('other_person_name', $appeal?->other_person_name) }}" @disabled((bool) $appeal) class="w-full text-xs rounded-lg border-slate-300 focus:ring-brand-500 @if($appeal) bg-slate-100 text-slate-600 @else bg-white @endif" placeholder="Nama perwakilan / pembimbing / saksi pendamping...">
                </div>
            </div>

            {{-- 2. Alasan Pengajuan Banding --}}
            <div>
                <label class="block font-bold text-slate-800 mb-1">2. Alasan &amp; Uraian Pengajuan Banding (*):</label>
                <p class="text-[11px] text-slate-500 mb-1.5">Jelaskan secara rinci alasan ketidaksetujuan terhadap keputusan, bukti yang diabaikan, atau ketidaksesuaian prosedur pelaksanaan asesmen (misal: asesor tidak profesional, bermain HP saat observasi praktik, dsb).</p>
                <textarea name="appeal_reasons" rows="4" required @disabled((bool) $appeal) class="w-full text-xs rounded-lg border-slate-300 focus:ring-brand-500 leading-relaxed @if($appeal) bg-slate-50 text-slate-600 @endif" placeholder="Tuliskan alasan pengajuan banding secara jelas dan terperinci...">{{ old('appeal_reasons', $appeal?->appeal_reasons) }}</textarea>
                @error('appeal_reasons')
                    <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            {{-- 3. Unit Kompetensi yang Dipersoalkan --}}
            <div>
                <label class="block font-bold text-slate-800 mb-1">3. Unit Kompetensi yang Dipersoalkan / Disanggah (Pilih yang relevan):</label>
                <p class="text-[11px] text-slate-500 mb-2">Pilih satu atau lebih unit kompetensi yang menjadi materi sanggahan banding Anda.</p>
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

            <div class="pt-4 border-t border-slate-200 flex items-center justify-between">
                <a href="{{ route('asesi.dashboard') }}" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                    &larr; Kembali ke Dashboard
                </a>
                @if($appeal)
                    <button type="button" disabled class="px-6 py-2.5 text-xs font-bold text-slate-500 bg-slate-200 border border-slate-300 rounded-lg cursor-not-allowed flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>Permohonan Banding Telah Terkirim (Terkunci)</span>
                    </button>
                @elseif(! $isWithinDeadline)
                    <div class="text-right">
                        <span class="text-xs text-rose-600 font-bold block">Tenggat pengajuan banding telah berakhir.</span>
                        <button type="button" disabled class="mt-1 px-6 py-2.5 text-xs font-bold text-white bg-slate-400 rounded-lg cursor-not-allowed">
                            Batas Waktu Habis
                        </button>
                    </div>
                @else
                    <button type="submit" class="px-6 py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition flex items-center gap-1.5">
                        <span>Kirim Pengajuan Banding (FR.AK.04)</span>
                        <span>&rarr;</span>
                    </button>
                @endif
            </div>
        </div>
    </form>
</div>
@endsection
