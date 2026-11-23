@extends('layouts.app', ['title' => 'FR.AK.02 Rekaman Asesmen Kompetensi'])

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    {{-- Unified Top Lifecycle Navigation --}}
    @include('asesor.partials.schedule-nav', ['schedule' => $assessment->schedule, 'active' => 'ak02'])

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between items-start gap-2.5">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">FR.AK.02 Rekaman Asesmen Kompetensi (Detail)</h1>
            <p class="text-xs text-slate-500">Rekapitulasi bukti dan penetapan rekomendasi hasil asesmen kompetensi per unit</p>
        </div>
        <div class="flex items-center space-x-2 shrink-0 self-start sm:self-auto">
            <span class="px-3 py-1 text-xs font-bold rounded-lg border {{ in_array($assessment->final_result, ['KOMPETEN', 'K']) ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : (in_array($assessment->final_result, ['BELUM_KOMPETEN', 'BK']) ? 'bg-rose-50 text-rose-700 border-rose-300' : 'bg-amber-50 text-amber-700 border-amber-300') }}">
                Status: {{ $assessment->final_result ?? 'BELUM SELESAI' }}
            </span>
        </div>
    </div>

    {{-- Info Card --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div>
                <span class="text-slate-500 font-medium">Skema Sertifikasi:</span>
                <span class="font-bold text-slate-900 ml-2">{{ $assessment->schedule->scheme->name }} ({{ $assessment->schedule->scheme->code }})</span>
            </div>
            <div>
                <span class="text-slate-500 font-medium">TUK:</span>
                <span class="font-bold text-slate-900 ml-2">{{ $assessment->schedule->tuk_name }}</span>
            </div>
            <div>
                <span class="text-slate-500 font-medium">Nama Asesi:</span>
                <span class="font-bold text-slate-900 ml-2">{{ $assessment->asesi->full_name }} ({{ $assessment->asesi->profile?->nik ?? '-' }})</span>
            </div>
            <div>
                <span class="text-slate-500 font-medium">Nama Asesor:</span>
                <span class="font-bold text-slate-900 ml-2">{{ auth()->user()->full_name }} (No. MET: {{ auth()->user()->assessor_registration_number ?? '-' }})</span>
            </div>
        </div>
    </div>

    {{-- Form AK.02 --}}
    <form action="{{ route('asesor.assessment.ak02.update', $assessment->id) }}" method="POST" class="space-y-6">
        @csrf

        {{-- Units Table with Methods Checkboxes --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" x-data="{
            toggleMethod(name) {
                const boxes = document.querySelectorAll(`input[name*='[${name}]']`);
                const allChecked = Array.from(boxes).every(b => b.checked);
                boxes.forEach(b => b.checked = !allChecked);
            }
        }">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Unit Kompetensi & Metode Asesmen yang Digunakan</h2>
                    <p class="text-xs text-slate-500">Beri tanda centang pada metode pengumpulan bukti yang diperoleh untuk menentukan kompetensi asesi</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <button type="button" @click="toggleMethod('observasi_demonstrasi')" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded text-xs font-medium transition cursor-pointer">
                        + Observasi
                    </button>
                    <button type="button" @click="toggleMethod('tulis')" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded text-xs font-medium transition cursor-pointer">
                        + Tulis (DPT)
                    </button>
                    <button type="button" @click="toggleMethod('lisan')" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded text-xs font-medium transition cursor-pointer">
                        + Lisan (PW)
                    </button>
                    <button type="button" @click="toggleMethod('portofolio')" class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded text-xs font-medium transition cursor-pointer">
                        + Portofolio
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="hidden md:table-header-group bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-center w-10">No</th>
                            <th class="px-4 py-3 text-left min-w-[200px]">Kode & Judul Unit Kompetensi</th>
                            <th class="px-2 py-3 text-center">Observasi Praktik</th>
                            <th class="px-2 py-3 text-center">Portofolio</th>
                            <th class="px-2 py-3 text-center">Lisan (PW)</th>
                            <th class="px-2 py-3 text-center">Tulis (DPT)</th>
                            <th class="px-2 py-3 text-center">Pihak Ketiga</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($units as $idx => $unit)
                            @php
                                $item = $ak02Items->get($unit->id);
                                $methods = $item?->methods_json ?? [];
                            @endphp
                            <tr class="flex flex-col md:table-row p-4 md:p-0 hover:bg-slate-50/60 transition space-y-3 md:space-y-0">
                                <td class="hidden md:table-cell px-4 py-3 text-center text-slate-500 font-mono">{{ $idx + 1 }}</td>
                                <td class="px-0 md:px-4 py-0 md:py-3">
                                    <input type="hidden" name="units[{{ $unit->id }}][exists]" value="1">
                                    <div class="flex items-start gap-2">
                                        <span class="md:hidden font-mono font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded text-[11px] shrink-0">#{{ $idx + 1 }}</span>
                                        <div>
                                            <span class="font-mono font-bold text-brand-700 text-xs block">{{ $unit->code }}</span>
                                            <span class="font-bold md:font-normal text-slate-900 md:text-slate-700 text-xs sm:text-sm leading-snug">{{ $unit->title }}</span>
                                        </div>
                                    </div>

                                    {{-- Mobile Checkboxes for Methods (< md) --}}
                                    <div class="block md:hidden mt-3 p-2.5 bg-slate-50 rounded-lg border border-slate-200/80 space-y-2">
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Metode Asesmen Terpilih:</div>
                                        <div class="grid grid-cols-2 gap-2 text-xs">
                                            <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                                                <input type="checkbox" name="units[{{ $unit->id }}][observasi_demonstrasi]" value="1" {{ !empty($methods['observasi_demonstrasi']) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                                <span class="text-[11px]">Observasi Praktik</span>
                                            </label>
                                            <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                                                <input type="checkbox" name="units[{{ $unit->id }}][portofolio]" value="1" {{ !empty($methods['portofolio']) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                                <span class="text-[11px]">Portofolio</span>
                                            </label>
                                            <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                                                <input type="checkbox" name="units[{{ $unit->id }}][lisan]" value="1" {{ !empty($methods['lisan']) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                                <span class="text-[11px]">Lisan (PW)</span>
                                            </label>
                                            <label class="flex items-center space-x-2 text-slate-700 cursor-pointer">
                                                <input type="checkbox" name="units[{{ $unit->id }}][tulis]" value="1" {{ !empty($methods['tulis']) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                                <span class="text-[11px]">Tulis (DPT)</span>
                                            </label>
                                            <label class="flex items-center space-x-2 text-slate-700 cursor-pointer col-span-2">
                                                <input type="checkbox" name="units[{{ $unit->id }}][pihak_ketiga]" value="1" {{ !empty($methods['pihak_ketiga']) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                                <span class="text-[11px]">Verifikasi Pihak Ketiga</span>
                                            </label>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden md:table-cell px-2 py-3 text-center">
                                    <label class="p-2 inline-flex items-center justify-center cursor-pointer">
                                        <input type="checkbox" name="units[{{ $unit->id }}][observasi_demonstrasi]" value="1" {{ !empty($methods['observasi_demonstrasi']) ? 'checked' : '' }} class="w-5 h-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
                                    </label>
                                </td>
                                <td class="hidden md:table-cell px-2 py-3 text-center">
                                    <label class="p-2 inline-flex items-center justify-center cursor-pointer">
                                        <input type="checkbox" name="units[{{ $unit->id }}][portofolio]" value="1" {{ !empty($methods['portofolio']) ? 'checked' : '' }} class="w-5 h-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
                                    </label>
                                </td>
                                <td class="hidden md:table-cell px-2 py-3 text-center">
                                    <label class="p-2 inline-flex items-center justify-center cursor-pointer">
                                        <input type="checkbox" name="units[{{ $unit->id }}][lisan]" value="1" {{ !empty($methods['lisan']) ? 'checked' : '' }} class="w-5 h-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
                                    </label>
                                </td>
                                <td class="hidden md:table-cell px-2 py-3 text-center">
                                    <label class="p-2 inline-flex items-center justify-center cursor-pointer">
                                        <input type="checkbox" name="units[{{ $unit->id }}][tulis]" value="1" {{ !empty($methods['tulis']) ? 'checked' : '' }} class="w-5 h-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
                                    </label>
                                </td>
                                <td class="hidden md:table-cell px-2 py-3 text-center">
                                    <label class="p-2 inline-flex items-center justify-center cursor-pointer">
                                        <input type="checkbox" name="units[{{ $unit->id }}][pihak_ketiga]" value="1" {{ !empty($methods['pihak_ketiga']) ? 'checked' : '' }} class="w-5 h-5 rounded border-slate-300 text-brand-600 focus:ring-brand-500 cursor-pointer">
                                    </label>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Final Recommendation & Notes --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4 text-xs">
            <h3 class="text-sm font-bold text-slate-900">Rekomendasi Hasil Asesmen Akhir</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 mb-2">Rekomendasi Hasil Asesmen (*):</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-data="{
                        selected: '{{ old('final_result', in_array($assessment->final_result, ['KOMPETEN', 'K']) ? 'KOMPETEN' : (in_array($assessment->final_result, ['BELUM_KOMPETEN', 'BK']) ? 'BELUM_KOMPETEN' : '')) }}'
                    }">
                        <label class="relative flex items-center p-3 rounded-xl border-2 cursor-pointer transition select-none has-[:checked]:bg-emerald-50 has-[:checked]:border-emerald-500 has-[:checked]:ring-2 has-[:checked]:ring-emerald-500/20"
                               :class="selected === 'KOMPETEN' ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-500/20' : 'bg-white border-slate-200 hover:bg-slate-50 hover:border-slate-300'">
                            <input type="radio" 
                                   name="final_result" 
                                   value="KOMPETEN" 
                                   x-model="selected"
                                   {{ in_array(old('final_result', $assessment->final_result), ['KOMPETEN', 'K']) ? 'checked' : '' }} 
                                   required 
                                   @disabled($assessment->schedule?->is_closed) 
                                   class="w-4 h-4 text-emerald-600 border-slate-300 focus:ring-emerald-500 cursor-pointer">
                            <div class="ml-2.5 flex items-center gap-1.5 flex-wrap">
                                <span class="text-xs font-bold text-emerald-700 flex items-center gap-1">
                                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Kompeten (K)</span>
                                </span>
                                <span class="text-[11px] font-semibold text-emerald-600">/ Competent</span>
                            </div>
                        </label>

                        <label class="relative flex items-center p-3 rounded-xl border-2 cursor-pointer transition select-none has-[:checked]:bg-rose-50 has-[:checked]:border-rose-500 has-[:checked]:ring-2 has-[:checked]:ring-rose-500/20"
                               :class="selected === 'BELUM_KOMPETEN' ? 'bg-rose-50 border-rose-500 ring-2 ring-rose-500/20' : 'bg-white border-slate-200 hover:bg-slate-50 hover:border-slate-300'">
                            <input type="radio" 
                                   name="final_result" 
                                   value="BELUM_KOMPETEN" 
                                   x-model="selected"
                                   {{ in_array(old('final_result', $assessment->final_result), ['BELUM_KOMPETEN', 'BK']) ? 'checked' : '' }} 
                                   required 
                                   @disabled($assessment->schedule?->is_closed) 
                                   class="w-4 h-4 text-rose-600 border-slate-300 focus:ring-rose-500 cursor-pointer">
                            <div class="ml-2.5 flex items-center gap-1.5 flex-wrap">
                                <span class="text-xs font-bold text-rose-700 flex items-center gap-1">
                                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Belum Kompeten (BK)</span>
                                </span>
                                <span class="text-[11px] font-semibold text-rose-600">/ Not Competent</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tindak Lanjut yang Dibutuhkan:</label>
                    <input type="text" name="follow_up_action" value="{{ old('follow_up_action', $assessment->follow_up_action ?? '') }}" @disabled($assessment->schedule?->is_closed) class="w-full text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none px-3.5 py-2.5 transition shadow-2xs {{ $assessment->schedule?->is_closed ? 'bg-slate-50 text-slate-700' : '' }}" placeholder="Pekerjaan tambahan jika BK atau usulan sertifikat jika K">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Komentar / Catatan Asesor:</label>
                <textarea name="final_notes" rows="3" @disabled($assessment->schedule?->is_closed) class="w-full p-3 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $assessment->schedule?->is_closed ? 'bg-slate-50 text-slate-700' : '' }}" placeholder="Catatan objektif mengenai unjuk kerja asesi...">{{ old('final_notes', $assessment->final_notes ?? '') }}</textarea>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between items-stretch gap-2.5 pt-4 border-t border-slate-200">
                <a href="{{ route('asesor.schedule.ak02', $assessment->schedule_id) }}" class="w-full sm:w-auto px-4 py-2 min-h-[40px] inline-flex items-center justify-center text-xs font-semibold text-slate-600 hover:text-slate-900 border border-slate-200 sm:border-0 rounded-lg text-center">
                    Kembali
                </a>
                @if($assessment->schedule?->is_closed)
                    <button type="button" disabled class="w-full sm:w-auto px-6 py-2.5 min-h-[40px] text-xs font-bold text-slate-500 bg-slate-200 rounded-lg cursor-not-allowed text-center">
                        Jadwal Telah Ditutup (Read-Only)
                    </button>
                @else
                    <button type="submit" class="w-full sm:w-auto px-6 py-2.5 min-h-[40px] text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm transition text-center">
                        Simpan Rekaman FR.AK.02
                    </button>
                @endif
            </div>
        </div>
    </form>
</div>
@endsection
