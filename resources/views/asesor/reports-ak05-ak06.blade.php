@extends('layouts.app', ['title' => 'FR.AK.05 & FR.AK.06 Laporan & Tinjauan Asesmen'])

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('asesor.jadwal.asesi', $schedule->id) }}" class="text-xs text-brand-600 hover:text-brand-700 font-semibold">&larr; Kembali ke Jadwal</a>
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
                <span class="text-slate-500 block">Jumlah Asesi:</span>
                <span class="font-bold text-brand-700">{{ $schedule->assessments->count() }} Orang ({{ $schedule->assessments->where('status', 'COMPLETED')->count() }} Selesai)</span>
            </div>
        </div>
    </div>

    <form action="{{ route('asesor.schedule.reports.submit', $schedule->id) }}" method="POST" class="space-y-6">
        @csrf

        {{-- FR.AK.05 Laporan Asesmen --}}
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4">
            <div class="border-b border-slate-200 pb-3">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 uppercase">FR.AK.05</span>
                <h2 class="text-base font-bold text-slate-900 mt-1">Laporan Asesmen</h2>
                <p class="text-xs text-slate-500">Evaluasi pelaksanaan asesmen kompetensi di Tempat Uji Kompetensi</p>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">1. Aspek Positif Pelaksanaan Asesmen:</label>
                    <textarea name="ak05_aspek_positif" rows="2" class="w-full text-xs rounded-lg border-slate-300 focus:ring-brand-500">{{ $schedule->ak05_report['aspek_positif'] ?? 'Sarana dan prasarana bengkel TUK lengkap, ketersediaan alat ukur dan APD sesuai standar K3, asesi hadir tepat waktu.' }}</textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">2. Aspek Negatif / Kendala Teknis Lapangan:</label>
                    <textarea name="ak05_aspek_negatif" rows="2" class="w-full text-xs rounded-lg border-slate-300 focus:ring-brand-500">{{ $schedule->ak05_report['aspek_negatif'] ?? 'Tidak ditemukan kendala signifikan selama proses asesmen berlangsung.' }}</textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">3. Rekomendasi Peningkatan Mutu Asesmen:</label>
                    <textarea name="ak05_rekomendasi" rows="2" class="w-full text-xs rounded-lg border-slate-300 focus:ring-brand-500">{{ $schedule->ak05_report['rekomendasi'] ?? 'Mempertahankan konsistensi kalibrasi peralatan tangan dan SST serta peningkatan durasi praktik mandiri.' }}</textarea>
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
                                <select name="ak06_valid" class="text-xs rounded border-slate-300 font-bold text-emerald-700">
                                    <option value="YA" selected>YA</option>
                                    <option value="TIDAK">TIDAK</option>
                                </select>
                            </td>
                            <td class="px-3 py-2.5">
                                <input type="text" name="ak06_valid_notes" value="Seluruh bukti dikumpulkan mengacu langsung pada KUK SKKNI." class="w-full text-xs rounded border-slate-300">
                            </td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2.5 font-bold text-slate-800">Reliabilitas (Reliable)</td>
                            <td class="px-3 py-2.5 text-center">
                                <select name="ak06_reliabel" class="text-xs rounded border-slate-300 font-bold text-emerald-700">
                                    <option value="YA" selected>YA</option>
                                    <option value="TIDAK">TIDAK</option>
                                </select>
                            </td>
                            <td class="px-3 py-2.5">
                                <input type="text" name="ak06_reliabel_notes" value="Rubrik penilaian dan kunci jawaban diterapkan secara seragam dan konsisten." class="w-full text-xs rounded border-slate-300">
                            </td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2.5 font-bold text-slate-800">Fleksibilitas (Flexible)</td>
                            <td class="px-3 py-2.5 text-center">
                                <select name="ak06_fleksibel" class="text-xs rounded border-slate-300 font-bold text-emerald-700">
                                    <option value="YA" selected>YA</option>
                                    <option value="TIDAK">TIDAK</option>
                                </select>
                            </td>
                            <td class="px-3 py-2.5">
                                <input type="text" name="ak06_fleksibel_notes" value="Formulir penyesuaian wajar (FR.AK.07) telah difasilitasi jika asesi membutuhkan." class="w-full text-xs rounded border-slate-300">
                            </td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2.5 font-bold text-slate-800">Keadilan (Fair)</td>
                            <td class="px-3 py-2.5 text-center">
                                <select name="ak06_adil" class="text-xs rounded border-slate-300 font-bold text-emerald-700">
                                    <option value="YA" selected>YA</option>
                                    <option value="TIDAK">TIDAK</option>
                                </select>
                            </td>
                            <td class="px-3 py-2.5">
                                <input type="text" name="ak06_adil_notes" value="Semua asesi mendapat kesempatan dan hak yang setara tanpa bias ras/gender/latar belakang." class="w-full text-xs rounded border-slate-300">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pt-2 text-xs">
                <label class="block font-bold text-slate-700 mb-1">Catatan Keseluruhan Peninjau / Lead Asesor:</label>
                <textarea name="ak06_catatan_peninjau" rows="2" class="w-full text-xs rounded-lg border-slate-300 focus:ring-brand-500">{{ $schedule->ak06_review['catatan_peninjau'] ?? 'Proses sertifikasi kompetensi telah mematuhi SOP BNSP dan standar mutu LSP PrO Indo.' }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-200">
            <a href="{{ route('asesor.jadwal.asesi', $schedule->id) }}" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                Kembali
            </a>
            <button type="submit" class="px-6 py-2.5 text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 rounded-lg shadow-sm transition">
                Simpan Laporan & Tutup Jadwal Asesmen (Final)
            </button>
        </div>
    </form>
</div>
@endsection
