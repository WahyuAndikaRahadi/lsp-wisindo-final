@extends('layouts.app', ['title' => 'FR.AK.02 Rekaman Asesmen Kompetensi - ' . ($schedule->name ?? $schedule->scheme->name)])

@section('content')
<div class="space-y-6">
    {{-- Unified Top Lifecycle Navigation --}}
    @include('asesor.partials.schedule-nav', ['schedule' => $schedule, 'active' => 'ak02'])

    @php
        $isClosedForMe = $schedule->isClosedForAssessor();
    @endphp

    @if($isClosedForMe)
        <div class="p-3.5 bg-emerald-50 border border-emerald-300 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-emerald-900">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span><strong>{{ $schedule->is_closed ? 'Jadwal Ditutup:' : 'Penetapan Asesi Selesai:' }}</strong> Seluruh rekaman dalam mode lihat (read-only).</span>
            </div>
            <span class="font-bold font-mono text-[11px] bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded border border-emerald-300 shrink-0">STATUS: {{ $schedule->is_closed ? 'FINISHED' : 'SELESAI' }}</span>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3 mt-1">
                <span class="font-mono text-xs font-bold px-2.5 py-1 rounded border border-teal-200 bg-teal-50 text-teal-800">
                    FR.AK.02
                </span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">FR.AK.02. Rekaman Asesmen Kompetensi</h1>
            </div>
            <p class="text-xs text-slate-600 mt-1">Penetapan rekomendasi hasil akhir (Kompeten / Belum Kompeten) kolektif dan individual untuk peserta uji</p>
        </div>
    </div>

    {{-- Meta Stats Bar --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Daftar Asesi</span>
            <div class="text-2xl font-black text-slate-900 mt-0.5">
                {{ $asesiList->count() }} Orang
                @if($schedule->assessments->count() > $asesiList->count())
                    <span class="text-xs text-slate-400 font-normal">({{ $schedule->assessments->count() }} total jadwal)</span>
                @endif
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Sudah Ditetapkan</span>
            <div class="text-2xl font-black text-emerald-600 mt-0.5">{{ $asesiList->where('is_completed', true)->count() }} Asesi</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">Belum Direkam</span>
            <div class="text-2xl font-black text-amber-600 mt-0.5">{{ $asesiList->where('is_completed', false)->count() }} Asesi</div>
        </div>
    </div>

    @if($asesiList->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500 text-sm shadow-sm">
            Belum ada asesi terdaftar pada jadwal ini.
        </div>
    @else
        {{-- Main Bulk Evaluation Form Container --}}
        <form action="{{ route('asesor.schedule.ak02.bulk', $schedule->id) }}" method="POST" id="bulkAk02Form" class="space-y-6">
            @csrf

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                {{-- Form Header & Quick Bulk Action Toolbar --}}
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 space-y-4">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-black text-slate-900">Penetapan Hasil Rekaman Asesmen Kolektif (FR.AK.02)</h2>
                            <p class="text-xs text-slate-500">Tentukan rekomendasi Kompeten (K) atau Belum Kompeten (BK) secara massal atau per asesi</p>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <button type="button" id="btnSetAllK" class="px-3.5 py-1.5 min-h-[36px] bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Semua K</span>
                            </button>
                            <button type="button" id="btnSetAllBk" class="px-3.5 py-1.5 min-h-[36px] bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span>Semua BK</span>
                            </button>
                        </div>
                    </div>

                    {{-- Global Methods Preset --}}
                    <div class="p-3 bg-white rounded-lg border border-slate-200 text-xs flex flex-col md:flex-row md:items-center justify-between gap-2.5">
                        <span class="font-bold text-slate-800 shrink-0">Metode Pengumpulan Bukti Terpilih (Kolektif):</span>
                        <div class="flex items-center gap-4 flex-wrap text-slate-700">
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="methods[observasi_demonstrasi]" value="1" checked class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4">
                                <span>Observasi Praktik</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="methods[tulis]" value="1" checked class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4">
                                <span>Pertanyaan Tulis (CBT)</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="methods[lisan]" value="1" checked class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4">
                                <span>Pertanyaan Lisan (PMO)</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" name="methods[portofolio]" value="1" checked class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4">
                                <span>Verifikasi Portofolio</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Table Container --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-bold text-slate-700 uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 w-10 text-center whitespace-nowrap">
                                    <input type="checkbox" id="checkAllAk02" checked class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4 cursor-pointer" title="Pilih Semua Asesi">
                                </th>
                                <th class="px-4 py-3 w-12 text-center whitespace-nowrap">No</th>
                                <th class="px-5 py-3 min-w-[200px] whitespace-nowrap">Data Peserta Asesi</th>
                                <th class="px-5 py-3 text-center min-w-[170px] whitespace-nowrap">Rekomendasi Hasil Akhir</th>
                                <th class="px-5 py-3 min-w-[240px]">Tindak Lanjut &amp; Catatan Asesor</th>
                                <th class="px-4 py-3 text-right w-24 whitespace-nowrap">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($asesiList as $idx => $item)
                                @php
                                    $asm = $item->assessment;
                                    $cbtResp = $asm->iaResponses->firstWhere('instrument_code', 'IA05');
                                    $currentResult = $asm->final_result ?? 'KOMPETEN';
                                    $isK = in_array($currentResult, ['KOMPETEN', 'K'], true);
                                    $defaultNotes = $asm->final_notes ?: ($isK ? 'Asesi menunjukkan unjuk kerja optimal sesuai standar skema' : 'Terdapat kriteria unjuk kerja yang belum terpenuhi');
                                    $defaultFollowUp = $asm->follow_up_action ?: ($isK ? 'Diusulkan penerbitan sertifikat kompetensi BNSP' : 'Perlu asesmen ulang pada unit yang belum kompeten');
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition align-top">
                                    <td class="px-4 py-4 text-center whitespace-nowrap">
                                        @if($asm->apl_status === 'APPROVED')
                                            <input type="checkbox" class="ak02-row-checkbox rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4 cursor-pointer mt-1" checked data-asm-id="{{ $asm->id }}">
                                        @else
                                            <span class="text-slate-300 text-xs">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-center font-mono font-bold text-xs text-slate-500 whitespace-nowrap">
                                        {{ $idx + 1 }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-slate-900 text-sm">{{ $item->asesi->full_name }}</div>
                                        <div class="text-xs text-slate-500 font-mono mt-0.5">
                                            NIK: {{ $item->asesi->profile?->nik ?? '-' }}
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                            @if($cbtResp)
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded border border-indigo-200 bg-indigo-50 text-indigo-700">
                                                    Skor CBT: {{ $cbtResp->score }}%
                                                </span>
                                            @endif
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                ✓ Asesi Ditugaskan
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-center whitespace-nowrap">
                                        @if($asm->apl_status === 'APPROVED' && ! $isClosedForMe)
                                            <div class="inline-flex rounded-lg border border-slate-300 p-1 bg-slate-100 gap-1 result-button-group" data-asm-id="{{ $asm->id }}">
                                                <button type="button" 
                                                        class="px-2.5 sm:px-3 py-1.5 rounded-md text-xs font-bold transition btn-k min-h-[34px] {{ $isK ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-200' }}"
                                                        onclick="setCandidateResult({{ $asm->id }}, 'KOMPETEN')">
                                                    <span class="hidden sm:inline">Kompeten </span>(K)
                                                </button>
                                                <button type="button" 
                                                        class="px-2.5 sm:px-3 py-1.5 rounded-md text-xs font-bold transition btn-bk min-h-[34px] {{ !$isK ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-700 hover:bg-slate-200' }}"
                                                        onclick="setCandidateResult({{ $asm->id }}, 'BELUM_KOMPETEN')">
                                                    <span class="hidden sm:inline">Belum </span>(BK)
                                                </button>
                                            </div>
                                            <input type="hidden" name="results[{{ $asm->id }}][final_result]" id="input_result_{{ $asm->id }}" value="{{ $isK ? 'KOMPETEN' : 'BELUM_KOMPETEN' }}">
                                        @elseif($asm->apl_status === 'APPROVED')
                                            <span class="px-2.5 py-1 rounded text-xs font-bold border {{ $isK ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-rose-50 text-rose-700 border-rose-300' }}">
                                                {{ $isK ? 'KOMPETEN (K)' : 'BELUM KOMPETEN (BK)' }}
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded text-xs font-bold border {{ $item->status_class }}">
                                                {{ $item->status_label }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 space-y-2">
                                        @if($asm->apl_status === 'APPROVED')
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-600 mb-0.5">Tindak Lanjut:</label>
                                                <input type="text" name="results[{{ $asm->id }}][follow_up_action]" id="input_follow_up_{{ $asm->id }}" value="{{ $defaultFollowUp }}" @disabled($isClosedForMe) class="w-full text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none py-1.5 px-3 transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}">
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-slate-600 mb-0.5">Catatan Asesor:</label>
                                                <input type="text" name="results[{{ $asm->id }}][final_notes]" id="input_notes_{{ $asm->id }}" value="{{ $defaultNotes }}" @disabled($isClosedForMe) class="w-full text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none py-1.5 px-3 transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}">
                                            </div>
                                        @else
                                            <div class="text-xs text-amber-600 italic">
                                                APL Belum Disetujui
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-right whitespace-nowrap">
                                        <a href="{{ $item->inspect_url }}" target="_blank" class="inline-flex items-center px-2.5 py-1.5 min-h-[32px] text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg shadow-xs transition" title="Buka formulir rincian unit per unit">
                                            <span>Unit</span>
                                            <svg class="w-3 h-3 ml-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Action Bar at Bottom of Container --}}
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <span class="text-xs text-slate-500">
                        {{ $isClosedForMe ? 'Asesmen telah ditutup. Seluruh data rekaman tersimpan permanen.' : 'Klik tombol simpan untuk merekam hasil FR.AK.02 seluruh asesi sekaligus.' }}
                    </span>
                    @if($isClosedForMe)
                        <button type="button" disabled 
                                class="px-6 py-2.5 min-h-[40px] bg-slate-200 text-slate-500 font-bold text-xs uppercase tracking-wider rounded-xl cursor-not-allowed">
                            {{ $schedule->is_closed ? 'Jadwal Telah Ditutup (Read-Only)' : 'Asesmen Selesai (Read-Only)' }}
                        </button>
                    @else
                        <button type="submit" 
                                data-confirm="Apakah Anda yakin ingin menyimpan seluruh penetapan rekaman FR.AK.02 untuk asesi yang ditugaskan?"
                                data-confirm-title="Konfirmasi Rekaman FR.AK.02"
                                data-confirm-ok="Ya, Simpan Seluruhnya"
                                data-loading-text="Menyimpan seluruh penetapan FR.AK.02..."
                                class="px-6 py-2.5 min-h-[40px] bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white font-bold text-xs uppercase tracking-wider rounded-xl shadow-md transition flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span><span class="hidden sm:inline">Simpan Seluruh Rekaman FR.AK.02 (Bulk Save)</span><span class="sm:hidden">Simpan FR.AK.02</span></span>
                        </button>
                    @endif
                </div>
            </div>
        </form>
    @endif
</div>

<script>
    function setCandidateResult(asmId, result) {
        const input = document.getElementById('input_result_' + asmId);
        if (!input) return;
        input.value = result;

        const group = document.querySelector(`.result-button-group[data-asm-id="${asmId}"]`);
        if (!group) return;

        const btnK = group.querySelector('.btn-k');
        const btnBk = group.querySelector('.btn-bk');
        const followUpInput = document.getElementById('input_follow_up_' + asmId);
        const notesInput = document.getElementById('input_notes_' + asmId);

        if (result === 'KOMPETEN') {
            btnK.className = 'px-3 py-1.5 rounded-md text-xs font-bold transition btn-k bg-emerald-600 text-white shadow-xs';
            btnBk.className = 'px-3 py-1.5 rounded-md text-xs font-bold transition btn-bk text-slate-700 hover:bg-slate-200';
            if (followUpInput && (!followUpInput.value || followUpInput.value.includes('belum kompeten'))) {
                followUpInput.value = 'Diusulkan penerbitan sertifikat kompetensi BNSP';
            }
            if (notesInput && (!notesInput.value || notesInput.value.includes('belum terpenuhi'))) {
                notesInput.value = 'Asesi menunjukkan unjuk kerja optimal sesuai standar skema';
            }
        } else {
            btnK.className = 'px-3 py-1.5 rounded-md text-xs font-bold transition btn-k text-slate-700 hover:bg-slate-200';
            btnBk.className = 'px-3 py-1.5 rounded-md text-xs font-bold transition btn-bk bg-rose-600 text-white shadow-xs';
            if (followUpInput && (!followUpInput.value || followUpInput.value.includes('Diusulkan'))) {
                followUpInput.value = 'Perlu asesmen ulang pada unit yang belum kompeten';
            }
            if (notesInput && (!notesInput.value || notesInput.value.includes('optimal'))) {
                notesInput.value = 'Terdapat kriteria unjuk kerja yang belum terpenuhi';
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const btnSetAllK = document.getElementById('btnSetAllK');
        const btnSetAllBk = document.getElementById('btnSetAllBk');
        const checkAll = document.getElementById('checkAllAk02');
        const rowCheckboxes = document.querySelectorAll('.ak02-row-checkbox');

        if (checkAll) {
            checkAll.addEventListener('change', function() {
                rowCheckboxes.forEach(cb => cb.checked = checkAll.checked);
            });
        }

        if (btnSetAllK) {
            btnSetAllK.addEventListener('click', function() {
                rowCheckboxes.forEach(cb => {
                    const asmId = cb.getAttribute('data-asm-id');
                    if (asmId) setCandidateResult(asmId, 'KOMPETEN');
                });
            });
        }

        if (btnSetAllBk) {
            btnSetAllBk.addEventListener('click', function() {
                rowCheckboxes.forEach(cb => {
                    const asmId = cb.getAttribute('data-asm-id');
                    if (asmId) setCandidateResult(asmId, 'BELUM_KOMPETEN');
                });
            });
        }
    });
</script>
@endsection
