@php
    $isClosedForMe = $schedule->isClosedForAssessor();
    $isApl = $meta['route_code'] === 'apl';
    $isIa02 = $meta['route_code'] === 'ia02';
    $isBulk = ($isApl || $isIa02) && ! $isClosedForMe;
    $hasProfileSig = ! empty(auth()->user()->profile?->signature_data);
    $bulkActionUrl = $isApl ? route('asesor.schedule.apl.bulk-review', $schedule->id) : route('asesor.schedule.ia02.bulk-sign', $schedule->id);
@endphp
@extends('layouts.app', ['title' => $meta['code'] . ' - ' . $meta['title']])

@section('content')
<div class="space-y-6">
    {{-- Unified Top Lifecycle Navigation --}}
    @include('asesor.partials.schedule-nav', ['schedule' => $schedule, 'active' => $meta['route_code'] === 'apl' ? 'apl' : 'ruang-uji'])

    @if($isClosedForMe)
        <div class="p-3.5 bg-emerald-50 border border-emerald-300 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-emerald-900">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span><strong>{{ $schedule->is_closed ? 'Jadwal Ditutup:' : 'Asesmen Telah Selesai:' }}</strong> Seluruh berkas dalam mode lihat (read-only).</span>
            </div>
            <span class="font-bold font-mono text-[11px] bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded border border-emerald-300 shrink-0">STATUS: {{ $schedule->is_closed ? 'FINISHED' : 'SELESAI' }}</span>
        </div>
    @endif

    {{-- Header & Back Navigation --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3 mt-1">
                <span class="font-mono text-xs font-bold px-2.5 py-1 rounded border border-slate-300 {{ $meta['badge_color'] }}">
                    {{ $meta['code'] }}
                </span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ $meta['title'] }}</h1>
            </div>
            <p class="text-xs text-slate-600 mt-1">{{ $meta['desc'] }}</p>
        </div>

        <div class="flex items-center space-x-2">
            @if($meta['route_code'] !== 'apl')
                <a href="{{ route('asesor.schedule.ruang-uji', $schedule->id) }}" class="px-4 py-2 text-xs font-semibold bg-white text-slate-800 border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 transition">
                    Kembali ke Ruang Uji
                </a>
            @endif
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
            <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Sudah Dinilai / Selesai</span>
            <div class="text-2xl font-black text-emerald-600 mt-0.5">{{ $asesiList->where('is_completed', true)->count() }} Asesi</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">Belum Dinilai</span>
            <div class="text-2xl font-black text-amber-600 mt-0.5">{{ $asesiList->where('is_completed', false)->count() }} Asesi</div>
        </div>
    </div>

    {{-- Asesi List Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-base font-black text-slate-900">Daftar Asesi untuk {{ $meta['code'] }}</h2>
                <p class="text-xs text-slate-500">Pilih asesi untuk memeriksa lembar hasil kerja dan memberikan penilaian</p>
            </div>
            <div class="text-xs font-bold text-slate-700 bg-white px-3 py-1 rounded border border-slate-300">
                Skema: {{ $schedule->scheme->code }}
            </div>
        </div>

        @if($asesiList->isEmpty())
            <div class="p-8 text-center text-slate-500 text-sm">
                Belum ada asesi terdaftar pada jadwal ini.
            </div>
        @else
            @if($isBulk)
                <form action="{{ $bulkActionUrl }}" method="POST" id="bulkActionForm" x-data="{
                    showSignModal: false,
                    hasProfileSig: @js($hasProfileSig),
                    getSelectedCount() {
                        return document.querySelectorAll('.bulk-item-checkbox:checked').length;
                    },
                    submitBulkIa02() {
                        const count = this.getSelectedCount();
                        if (count === 0) {
                            Notiflix.Report.warning('Pemberitahuan', 'Silakan pilih setidaknya satu asesi terlebih dahulu.', 'Mengerti');
                            return;
                        }
                        if (this.hasProfileSig) {
                            window.notiflixConfirm(
                                'Konfirmasi Tanda Tangan Massal',
                                'Apakah Anda yakin ingin menandatangani dokumen FR.IA.02 untuk ' + count + ' asesi yang dipilih menggunakan tanda tangan profil Anda?',
                                'Ya, Tanda Tangani',
                                'Batal',
                                function () {
                                    Notiflix.Loading.pulse('Menandatangani FR.IA.02 untuk ' + count + ' asesi...');
                                    document.getElementById('bulkActionForm').submit();
                                },
                                function () {},
                                { okButtonBackground: '#1E2B6E' }
                            );
                        } else {
                            this.showSignModal = true;
                        }
                    }
                }">
                    @csrf

                    @if($isApl)
                        <div class="px-6 py-3.5 bg-amber-50/80 border-b border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                            <div class="flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                                <span class="font-bold text-amber-950">Verifikasi Cepat / Bulk Approval APL:</span>
                                <span class="text-amber-800">Centang asesi yang memenuhi syarat untuk langsung disetujui sekaligus.</span>
                            </div>
                            <div class="flex items-center space-x-2 shrink-0">
                                <button type="submit" 
                                        data-confirm="Apakah Anda yakin ingin menyetujui seluruh APL asesi yang dipilih?"
                                        data-confirm-title="Konfirmasi Persetujuan Massal APL"
                                        data-confirm-ok="Ya, Setujui Semua"
                                        data-loading-text="Menyetujui seluruh APL asesi yang dipilih..."
                                        class="px-4 py-2 min-h-[38px] bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold rounded-lg shadow-sm transition inline-flex items-center space-x-1.5 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Setujui APL Terpilih</span>
                                </button>
                            </div>
                        </div>
                    @elseif($isIa02)
                        <div class="px-6 py-3.5 bg-indigo-50/90 border-b border-indigo-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                            <div class="flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-600 animate-pulse"></span>
                                <div>
                                    <span class="font-bold text-indigo-950">Tanda Tangan Massal / Bulk Sign FR.IA.02</span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3 shrink-0">
                                @if($hasProfileSig)
                                    <span class="hidden md:inline-flex items-center gap-1 text-[11px] text-emerald-700 font-bold bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span>TTD Profil Siap Digunakan</span>
                                    </span>
                                @endif
                                <button type="button" @click="submitBulkIa02()" 
                                        class="px-4 py-2 min-h-[38px] bg-brand-600 hover:bg-brand-700 active:bg-brand-800 text-white font-bold rounded-lg shadow-sm transition inline-flex items-center space-x-1.5 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    <span><span class="hidden sm:inline">Tanda Tangani Terpilih (Bulk Sign)</span><span class="sm:hidden">TTD Terpilih</span></span>
                                </button>
                            </div>
                        </div>

                        @if(! $hasProfileSig)
                            {{-- Modal TTD jika belum memiliki TTD di profil --}}
                            <div x-show="showSignModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(15, 23, 42, 0.6);">
                                <div class="flex items-center justify-center min-h-screen p-4">
                                    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.away="showSignModal = false">
                                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                                            <div>
                                                <h3 class="text-base font-black text-slate-900">Tanda Tangan Asesor (Bulk Sign FR.IA.02)</h3>
                                                <p class="text-xs text-slate-500">Tanda tangan ini akan diterapkan ke seluruh asesi yang Anda centang</p>
                                            </div>
                                            <button type="button" @click="showSignModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                                        </div>

                                        <div class="space-y-4 text-xs">
                                            <x-signature-pad name="assessor_signature" label="Tanda Tangan Digital Asesor" :required="true" :height="140" />

                                            <label class="flex items-center gap-2 cursor-pointer bg-slate-50 p-3 rounded-xl border border-slate-200">
                                                <input type="checkbox" name="save_signature_to_profile" value="1" checked class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4">
                                                <span class="text-slate-700 font-medium">Simpan tanda tangan ini ke profil saya untuk penandatanganan berikutnya</span>
                                            </label>
                                        </div>

                                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                                            <button type="button" @click="showSignModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-xl transition font-medium">
                                                Batal
                                            </button>
                                            <button type="submit" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl shadow-sm transition">
                                                Konfirmasi &amp; Tanda Tangani &rarr;
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold text-slate-700 uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            @if($isBulk)
                                <th class="px-4 py-3 w-10 text-center">
                                    <input type="checkbox" id="checkAllItems" checked class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4 cursor-pointer" title="Pilih Semua">
                                </th>
                            @endif
                            <th class="px-6 py-3 w-16 text-center whitespace-nowrap">No</th>
                            <th class="px-6 py-3 whitespace-nowrap">Data Peserta Asesi</th>
                            @if(! $isApl)
                                <th class="px-6 py-3 text-center whitespace-nowrap">Status APL</th>
                            @endif
                            <th class="px-6 py-3 text-center whitespace-nowrap">{{ $isApl ? 'Status Verifikasi APL' : 'Status ' . $meta['code'] }}</th>
                            <th class="px-6 py-3 text-right whitespace-nowrap">{{ $isApl ? 'Aksi Verifikasi' : 'Aksi' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($asesiList as $idx => $item)
                            <tr class="hover:bg-slate-50/80 transition">
                                @if($isBulk)
                                    <td class="px-4 py-4 text-center">
                                        @if($isIa02 && $item->assessment->apl_status !== 'APPROVED')
                                            <input type="checkbox" disabled class="rounded border-slate-200 text-slate-300 w-4 h-4 cursor-not-allowed opacity-40" title="APL belum disetujui">
                                        @else
                                            <input type="checkbox" name="assessment_ids[]" value="{{ $item->assessment->id }}"
                                                class="bulk-item-checkbox rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-4 h-4 cursor-pointer"
                                                {{ ($isIa02 && $item->is_completed) ? '' : 'checked' }}>
                                        @endif
                                    </td>
                                @endif
                                <td class="px-6 py-4 text-center font-mono font-bold text-xs text-slate-600 whitespace-nowrap">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-full bg-slate-800 text-white font-bold flex items-center justify-center text-xs shrink-0 border border-slate-600">
                                            {{ strtoupper(substr($item->asesi->full_name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 text-sm">{{ $item->asesi->full_name }}</div>
                                            <div class="text-xs text-slate-500">
                                                {{ $item->asesi->email }}
                                                @if(!empty($item->asesi->profile?->nik))
                                                    &bull; <span class="font-mono">NIK: {{ $item->asesi->profile->nik }}</span>
                                                @endif
                                            </div>
                                            <span class="inline-flex items-center text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 mt-1">
                                                ✓ Asesi Ditugaskan
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                @if(! $isApl)
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded text-xs font-bold border border-slate-300 {{ $item->assessment->apl_status === 'APPROVED' ? 'bg-emerald-50 text-emerald-700 border-emerald-300' : 'bg-slate-100 text-slate-600' }}">
                                            {{ str_replace('_', ' ', $item->assessment->apl_status) }}
                                        </span>
                                    </td>
                                @endif
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <div class="inline-flex flex-col items-center space-y-1">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $item->status_class }}">
                                            {{ $item->status_label }}
                                        </span>
                                        @if($item->score_label)
                                            <span class="text-[11px] font-bold text-slate-600">
                                                {{ $item->score_label }}
                                            </span>
                                        @endif
                                        @if($meta['code'] === 'IA02')
                                            @if($item->has_evidence)
                                                <span class="text-[10px] font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-300">
                                                    ✓ Lembar Kertas Ada
                                                </span>
                                            @else
                                                <span class="text-[10px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-300">
                                                    Lembar Kertas Belum Diunggah
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <a href="{{ $item->inspect_url }}"
                                       class="inline-flex items-center space-x-1.5 px-3.5 py-2 text-xs font-bold {{ $item->is_completed || $isClosedForMe ? 'bg-slate-800 hover:bg-slate-900 text-white' : 'bg-brand-600 hover:bg-brand-700 text-white' }} rounded-lg shadow-sm transition min-h-[36px]">
                                        <span>
                                            @if($isClosedForMe)
                                                {{ $isApl ? 'Lihat Berkas APL' : 'Lihat Dokumen' }}
                                            @elseif($isApl)
                                                {{ $item->is_completed ? 'Tinjau APL' : 'Verifikasi APL' }}
                                            @elseif($isIa02)
                                                {{ $item->is_completed ? 'Tinjau TTD' : 'TTD FR.IA.02' }}
                                            @else
                                                {{ $item->is_completed ? 'Tinjau' : 'Periksa' }}
                                            @endif
                                        </span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($isBulk)
                </form>
            @endif
        @endif
    </div>

    @if($isBulk)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const checkAll = document.getElementById('checkAllItems');
                const itemCheckboxes = document.querySelectorAll('.bulk-item-checkbox:not(:disabled)');

                if (checkAll && itemCheckboxes.length > 0) {
                    checkAll.checked = Array.from(itemCheckboxes).every(c => c.checked);

                    checkAll.addEventListener('change', function() {
                        itemCheckboxes.forEach(cb => cb.checked = checkAll.checked);
                    });

                    itemCheckboxes.forEach(cb => {
                        cb.addEventListener('change', function() {
                            const allChecked = Array.from(itemCheckboxes).every(c => c.checked);
                            checkAll.checked = allChecked;
                        });
                    });
                }
            });
        </script>
    @endif
</div>
@endsection
