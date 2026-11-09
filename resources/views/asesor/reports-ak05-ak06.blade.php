@extends('layouts.app', ['title' => 'FR.AK.05 & FR.AK.06 Laporan & Tinjauan Asesmen'])

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Unified Top Lifecycle Navigation --}}
    @include('asesor.partials.schedule-nav', ['schedule' => $schedule, 'active' => 'reports'])

    @php
        $isClosedForMe = $schedule->isClosedForAssessor();
    @endphp

    @if($isClosedForMe)
        <div class="p-4 bg-emerald-50 border border-emerald-300 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-emerald-900">
            <div class="flex items-center space-x-2.5">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <div>
                    <strong class="font-bold block text-sm">{{ $schedule->is_closed ? 'Jadwal Telah Resmi Ditutup & Dikunci' : 'Laporan Asesmen Binaan Anda Telah Disimpan & Ditutup' }}</strong>
                    <span>{{ $schedule->is_closed ? 'Jadwal ditutup pada ' . ($schedule->closed_at ? \Carbon\Carbon::parse($schedule->closed_at)->isoFormat('dddd, D MMMM Y, HH:mm') : '-') . ' WIB. Dokumen laporan dalam mode lihat (read-only).' : 'Seluruh asesi binaan Anda telah selesai dinilai dan laporan tersimpan permanen dalam mode lihat (read-only).' }}</span>
                </div>
            </div>
            <span class="font-bold font-mono text-xs bg-emerald-100 text-emerald-800 px-3 py-1 rounded-lg border border-emerald-300 shrink-0">STATUS: {{ $schedule->is_closed ? 'FINISHED' : 'BINAAN SELESAI' }}</span>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">Laporan & Tinjauan Proses Asesmen</h1>
            <p class="text-xs text-slate-500">Kompilasi FR.AK.05 (Laporan Hasil) dan FR.AK.06 (Meninjau Proses Asesmen) sebelum penutupan jadwal uji</p>
        </div>
    </div>

    {{-- Ringkasan Jadwal --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm text-xs">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <span class="text-slate-500 block">Jadwal:</span>
                <span class="font-bold text-slate-900">{{ $schedule->name }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">Skema:</span>
                <span class="font-bold text-slate-900">{{ $schedule->scheme->name }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">TUK:</span>
                <span class="font-bold text-slate-900">{{ $schedule->tuk_name }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">Jumlah Asesi Binaan:</span>
                <span class="font-bold text-brand-700">{{ $myAssessments->count() }} Orang ({{ $myAssessments->where('status', 'COMPLETED')->count() }} Selesai)</span>
                @if($schedule->assessments->count() > $myAssessments->count())
                    <span class="text-[10px] text-slate-400 block mt-0.5 font-normal">
                        (Total Jadwal: {{ $schedule->assessments->count() }} Orang lintas {{ $schedule->assessors->count() }} Asesor)
                    </span>
                @endif
            </div>
        </div>
    </div>

    @php
        $assessorId = auth()->id();
        $ak05Data = $schedule->ak05_report['by_assessor'][$assessorId] ?? $schedule->ak05_report ?? [];
        $ak06Data = $schedule->ak06_review['by_assessor'][$assessorId] ?? $schedule->ak06_review ?? [];
    @endphp

    <form action="{{ route('asesor.schedule.reports.submit', $schedule->id) }}" method="POST" class="space-y-6"
        x-data="{
            ak05Positif: '{{ addslashes(old('ak05_aspek_positif', $ak05Data['aspek_positif'] ?? '')) }}',
            ak05Negatif: '{{ addslashes(old('ak05_aspek_negatif', $ak05Data['aspek_negatif'] ?? '')) }}',
            ak05Rekomendasi: '{{ addslashes(old('ak05_rekomendasi', $ak05Data['rekomendasi'] ?? '')) }}',
            ak06ValidNotes: '{{ addslashes(old('ak06_valid_notes', $ak06Data['valid_notes'] ?? '')) }}',
            ak06ReliabelNotes: '{{ addslashes(old('ak06_reliabel_notes', $ak06Data['reliabel_notes'] ?? '')) }}',
            ak06FleksibelNotes: '{{ addslashes(old('ak06_fleksibel_notes', $ak06Data['fleksibel_notes'] ?? '')) }}',
            ak06AdilNotes: '{{ addslashes(old('ak06_adil_notes', $ak06Data['adil_notes'] ?? '')) }}',
            ak06Catatan: '{{ addslashes(old('ak06_catatan_peninjau', $ak06Data['catatan_peninjau'] ?? '')) }}',
            useDraft() {
                this.ak05Positif = 'Sarana dan prasarana bengkel TUK lengkap, ketersediaan alat ukur dan APD sesuai standar K3, asesi hadir tepat waktu.';
                this.ak05Negatif = 'Tidak ditemukan kendala signifikan selama proses asesmen berlangsung.';
                this.ak05Rekomendasi = 'Mempertahankan konsistensi kalibrasi peralatan tangan dan SST serta peningkatan durasi praktik mandiri.';
                this.ak06ValidNotes = 'Seluruh bukti dikumpulkan mengacu langsung pada KUK SKKNI.';
                this.ak06ReliabelNotes = 'Rubrik penilaian dan kunci jawaban diterapkan secara seragam dan konsisten.';
                this.ak06FleksibelNotes = 'Formulir penyesuaian wajar (FR.AK.07) telah difasilitasi jika asesi membutuhkan.';
                this.ak06AdilNotes = 'Semua asesi mendapat kesempatan dan hak yang setara tanpa bias ras/gender/latar belakang.';
                this.ak06Catatan = 'Proses sertifikasi kompetensi telah mematuhi SOP BNSP dan standar mutu LSP PrO Indo.';
            }
        }">
        @csrf

        {{-- FR.AK.05 Laporan Asesmen --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4">
            <div class="border-b border-slate-200 pb-3 flex items-center justify-between">
                <div>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase">FR.AK.05</span>
                    <h2 class="text-base font-bold text-slate-900 mt-1">Laporan Asesmen</h2>
                    <p class="text-xs text-slate-500">Evaluasi pelaksanaan asesmen kompetensi di Tempat Uji Kompetensi</p>
                </div>
                @if(! $isClosedForMe)
                    <button type="button" @click="useDraft()" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Gunakan Draf Saran
                    </button>
                @endif
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">1. Aspek Positif Pelaksanaan Asesmen:</label>
                    <textarea name="ak05_aspek_positif" x-model="ak05Positif" rows="2" required @disabled($isClosedForMe) class="w-full p-3 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}" placeholder="Tuliskan aspek positif pelaksanaan asesmen di TUK..."></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">2. Aspek Negatif / Kendala Teknis Lapangan:</label>
                    <textarea name="ak05_aspek_negatif" x-model="ak05Negatif" rows="2" required @disabled($isClosedForMe) class="w-full p-3 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}" placeholder="Tuliskan kendala teknis atau catatan khusus..."></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">3. Rekomendasi Peningkatan Mutu Asesmen:</label>
                    <textarea name="ak05_rekomendasi" x-model="ak05Rekomendasi" rows="2" required @disabled($isClosedForMe) class="w-full p-3 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}" placeholder="Tuliskan rekomendasi peningkatan mutu..."></textarea>
                </div>
            </div>
        </div>

        {{-- FR.AK.06 Tinjauan Proses Asesmen --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4">
            <div class="border-b border-slate-200 pb-3">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 uppercase">FR.AK.06</span>
                <h2 class="text-base font-bold text-slate-900 mt-1">Meninjau Proses Asesmen (Quality Review)</h2>
                <p class="text-xs text-slate-500">Verifikasi pemenuhan 4 Prinsip Asesmen BNSP (Valid, Reliabel, Fleksibel, Adil)</p>
            </div>

            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-2 text-left w-28">Prinsip Asesmen</th>
                            <th class="px-3 py-2 text-center w-24">Kesesuaian</th>
                            <th class="px-3 py-2 text-left">Catatan & Komentar Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="px-3 py-2.5 font-bold text-slate-800">Validitas (Valid)</td>
                            <td class="px-3 py-2.5 text-center">
                                <select name="ak06_valid" @disabled($isClosedForMe) class="text-xs rounded-lg border border-slate-300 bg-white font-bold text-emerald-700 py-1.5 px-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs cursor-pointer">
                                    <option value="YA" selected>YA</option>
                                    <option value="TIDAK">TIDAK</option>
                                </select>
                            </td>
                            <td class="px-3 py-2.5">
                                <input type="text" name="ak06_valid_notes" x-model="ak06ValidNotes" required @disabled($isClosedForMe) placeholder="Catatan kesesuaian prinsip validitas..." class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2.5 font-bold text-slate-800">Reliabilitas (Reliable)</td>
                            <td class="px-3 py-2.5 text-center">
                                <select name="ak06_reliabel" @disabled($isClosedForMe) class="text-xs rounded-lg border border-slate-300 bg-white font-bold text-emerald-700 py-1.5 px-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs cursor-pointer">
                                    <option value="YA" selected>YA</option>
                                    <option value="TIDAK">TIDAK</option>
                                </select>
                            </td>
                            <td class="px-3 py-2.5">
                                <input type="text" name="ak06_reliabel_notes" x-model="ak06ReliabelNotes" required @disabled($isClosedForMe) placeholder="Catatan kesesuaian reliabilitas..." class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2.5 font-bold text-slate-800">Fleksibilitas (Flexible)</td>
                            <td class="px-3 py-2.5 text-center">
                                <select name="ak06_fleksibel" @disabled($isClosedForMe) class="text-xs rounded-lg border border-slate-300 bg-white font-bold text-emerald-700 py-1.5 px-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs cursor-pointer">
                                    <option value="YA" selected>YA</option>
                                    <option value="TIDAK">TIDAK</option>
                                </select>
                            </td>
                            <td class="px-3 py-2.5">
                                <input type="text" name="ak06_fleksibel_notes" x-model="ak06FleksibelNotes" required @disabled($isClosedForMe) placeholder="Catatan fleksibilitas..." class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2.5 font-bold text-slate-800">Keadilan (Fair)</td>
                            <td class="px-3 py-2.5 text-center">
                                <select name="ak06_adil" @disabled($isClosedForMe) class="text-xs rounded-lg border border-slate-300 bg-white font-bold text-emerald-700 py-1.5 px-2.5 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs cursor-pointer">
                                    <option value="YA" selected>YA</option>
                                    <option value="TIDAK">TIDAK</option>
                                </select>
                            </td>
                            <td class="px-3 py-2.5">
                                <input type="text" name="ak06_adil_notes" x-model="ak06AdilNotes" required @disabled($isClosedForMe) placeholder="Catatan keadilan..." class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pt-2 text-xs">
                <label class="block font-bold text-slate-700 mb-1">Catatan Keseluruhan Peninjau / Lead Asesor:</label>
                <textarea name="ak06_catatan_peninjau" x-model="ak06Catatan" rows="2" required @disabled($isClosedForMe) class="w-full p-3 text-xs rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 focus:outline-none transition shadow-2xs {{ $isClosedForMe ? 'bg-slate-50 text-slate-700' : '' }}" placeholder="Tuliskan catatan keseluruhan peninjauan proses asesmen..."></textarea>
            </div>
        </div>

        {{-- Mandatory Confirmation Checkbox --}}
        @if(! $isClosedForMe)
            <div class="p-4 bg-amber-50/70 border border-amber-200 rounded-xl">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="confirm_draft_review" value="1" required class="mt-0.5 rounded border-amber-400 text-brand-600 focus:ring-brand-500">
                    <div class="text-xs text-amber-900">
                        <span class="font-bold block">Konfirmasi Integritas Peninjauan:</span>
                        Saya menyatakan telah memeriksa, meninjau, dan memastikan seluruh isi laporan evaluasi FR.AK.05 dan FR.AK.06 sesuai dengan fakta aktual pelaksanaan asesmen di Tempat Uji Kompetensi.
                    </div>
                </label>
            </div>
        @endif

        <div class="flex items-center justify-between pt-4 border-t border-slate-200">
            <a href="{{ route('asesor.schedule.ruang-uji', $schedule->id) }}" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                Kembali
            </a>
            @if($isClosedForMe)
                <div class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    @if($schedule->is_closed)
                        <span>Jadwal Telah Resmi Ditutup & Dikunci (Read-Only)</span>
                    @else
                        <span>Laporan Binaan Telah Disimpan (Mode Selesai)</span>
                    @endif
                </div>
            @else
                <button type="submit" class="px-6 py-2.5 min-h-[40px] text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm transition inline-flex items-center justify-center"
                        onclick="return confirm('PERINGATAN KONFIRMASI LAPORAN ASESMEN:\n\n1. Seluruh rekaman penilaian dan rekomendasi asesi binaan Anda akan disimpan.\n2. Jika seluruh asesi pada jadwal ini telah selesai, jadwal akan resmi ditutup (FINISHED).\n3. Berita Acara dan Dokumen MUK siap diverifikasi untuk audit BNSP.\n\nApakah Anda yakin ingin menyimpan laporan FR.AK.05 & FR.AK.06 ini?');">
                    Simpan &amp; Selesaikan Asesmen
                </button>
            @endif
        </div>
    </form>
</div>
@endsection
