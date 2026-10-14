@extends('layouts.app', ['title' => 'FR.IA.01B - Lembar Pemeriksaan Teknis (Report Sheet)'])

@section('content')
<div class="space-y-6">
    <div>
        <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia01b']) }}" class="text-xs text-lsp-navy hover:text-lsp-red font-bold">&larr; Kembali ke Daftar Asesi IA.01B</a>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">FR.IA.01B – Lembar Hasil Pengukuran & Pemeriksaan Teknis (Report Sheet)</h1>
        <p class="text-sm text-slate-500">Asesi: <strong>{{ $assessment->asesi->full_name }}</strong> | Skema: <strong>{{ $assessment->schedule->scheme->name }}</strong></p>
    </div>

    <form action="{{ route('asesor.ruang-uji.ia01b.store', $assessment->id) }}" method="POST" class="space-y-6">
        @csrf

        {{-- Inspection Items Table --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                <span class="text-xs font-bold text-lsp-navy font-mono">FORMULIR TEKNIS SPESIFIK SKEMA</span>
                <h2 class="text-base font-bold text-slate-900">Item Pengukuran & Lembar Kerja Asesi</h2>
                <p class="text-xs text-slate-500 mt-0.5">Verifikasi hasil pengukuran fisik dan kondisi komponen yang dikerjakan oleh asesi</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100/70 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3 w-16">No</th>
                            <th class="px-6 py-3">Item Pemeriksaan / Bagian Diukur</th>
                            <th class="px-6 py-3 w-64">Spesifikasi Standar</th>
                            <th class="px-6 py-3 w-48">Hasil Pengukuran Asesi</th>
                            <th class="px-6 py-3 text-center w-36">Verifikasi (Sesuai / Tidak)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($reportSheetItems ?? [] as $idx => $item)
                            @php
                                $val = $savedAnswers[$idx]['result'] ?? old("items.{$idx}.result");
                                $measured = $savedAnswers[$idx]['measured'] ?? old("items.{$idx}.measured");
                            @endphp
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-3 text-slate-500 font-mono text-xs">{{ $idx + 1 }}</td>
                                <td class="px-6 py-3 text-slate-800 font-medium">
                                    {{ $item['item'] }}
                                </td>
                                <td class="px-6 py-3 text-slate-600 text-xs">
                                    <span class="bg-slate-100 px-2.5 py-1 rounded border border-slate-200 inline-block">
                                        {{ $item['standard'] ?? 'Workshop Manual' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    <input type="text" name="items[{{ $idx }}][measured]" value="{{ $measured }}" placeholder="Nilai / kondisi..." class="w-full px-2.5 py-1 text-xs rounded border border-slate-300 focus:ring-1 focus:ring-lsp-navy focus:outline-none">
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-3">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="radio" name="items[{{ $idx }}][result]" value="SESUAI" required class="text-emerald-600 focus:ring-emerald-500 w-4 h-4" {{ $val === 'SESUAI' ? 'checked' : '' }}>
                                            <span class="ml-1 text-xs font-semibold text-emerald-700">Sesuai</span>
                                        </label>
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="radio" name="items[{{ $idx }}][result]" value="TIDAK" required class="text-rose-600 focus:ring-rose-500 w-4 h-4" {{ $val === 'TIDAK' ? 'checked' : '' }}>
                                            <span class="ml-1 text-xs font-semibold text-rose-700">Tidak</span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-slate-500 text-xs">Tidak ada item lembar ukur khusus untuk skema ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Notes & Assessor Signature --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900">Umpan Balik & Rekomendasi Hasil Kerja Teknis</h2>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Catatan Asesor</label>
                <textarea name="notes" rows="3" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-lsp-navy focus:outline-none" placeholder="Catatan ketepatan toleransi ukur dan kualitas hasil kerja asesi...">{{ $savedNotes ?? old('notes') }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-200">
                <x-signature-pad name="assessor_signature" :value="$savedSignature ?? null" label="Tanda Tangan Asesor" :required="true" />
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia01b']) }}" class="px-5 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">Batal</a>
            <button type="submit" class="px-6 py-2.5 text-sm font-bold bg-lsp-navy hover:bg-brand-700 text-white rounded-lg shadow-sm transition">
                Simpan Penilaian FR.IA.01B
            </button>
        </div>
    </form>
</div>
@endsection
