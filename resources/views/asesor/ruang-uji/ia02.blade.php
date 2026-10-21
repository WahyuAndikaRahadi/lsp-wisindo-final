@php
    $isCompleted = (bool) $response;
@endphp
@extends('layouts.app', ['title' => 'FR.IA.02 - Tugas Praktik Demonstrasi'])

@section('content')
<div class="space-y-6" x-data="{ isEditing: {{ $isCompleted ? 'false' : 'true' }} }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia02']) }}" class="text-xs text-brand-600 hover:text-brand-700 font-bold">&larr; Kembali ke Daftar Asesi IA.02</a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">FR.IA.02 – Tugas Praktik Demonstrasi</h1>
            <p class="text-sm text-slate-500">Asesi: <strong>{{ $assessment->asesi->full_name }}</strong> | Skema: <strong>{{ $assessment->schedule->scheme->name }}</strong></p>
        </div>

        @if($isCompleted)
            <div class="flex items-center gap-2">
                <button type="button" x-show="!isEditing" @click="isEditing = true" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg shadow-xs transition flex items-center gap-1.5">
                    <span>✎ Edit Penilaian</span>
                </button>
                <button type="button" x-show="isEditing" @click="isEditing = false" style="display: none;" class="px-3.5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold rounded-lg transition">
                    Batal Edit
                </button>
            </div>
        @endif
    </div>

    @if($isCompleted)
        <div class="bg-emerald-50 border-2 border-emerald-300 rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shrink-0">✓</span>
                <div>
                    <div class="text-xs font-bold text-emerald-950">Penilaian FR.IA.02 Telah Selesai &amp; Tersimpan</div>
                    <p class="text-[11px] text-emerald-800" x-show="!isEditing">Field penilaian dalam mode terkunci (read-only). Klik tombol 'Edit Penilaian' untuk mengubah.</p>
                    <p class="text-[11px] text-amber-800 font-semibold" x-show="isEditing" style="display: none;">Mode Pengeditan Aktif. Silakan sesuaikan capaian dan catatan demonstrasi di bawah.</p>
                </div>
            </div>
            @if($response?->assessor_signed_at)
                <div class="text-right text-[11px] text-emerald-700 font-mono">
                    Dinilai: {{ \Carbon\Carbon::parse($response->assessor_signed_at)->isoFormat('D MMM Y, HH:mm') }} WIB
                </div>
            @endif
        </div>
    @endif

    <form action="{{ route('asesor.ruang-uji.ia02.store', $assessment->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Skenario & Waktu Box --}}
        <div class="bg-gradient-to-r from-slate-900 to-lsp-navy text-white rounded-xl p-6 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-white/10 pb-4 mb-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-lsp-red bg-white/10 px-2.5 py-1 rounded">Skenario Praktik Demonstrasi</span>
                    <h2 class="text-lg font-bold text-white mt-1">{{ $scheme->name }}</h2>
                </div>
                <div class="bg-white/10 px-4 py-2 rounded-lg border border-white/20 text-center">
                    <div class="text-[10px] text-slate-300 uppercase tracking-wider font-semibold">Alokasi Waktu</div>
                    <div class="text-base font-black text-amber-300">{{ $tpdData['time_limit'] ?? '30 Menit' }}</div>
                </div>
            </div>
            <p class="text-sm text-slate-200 leading-relaxed">{{ $tpdData['scenario'] ?? 'Laksanakan instruksi kerja praktik unjuk kerja sesuai standar operasional prosedur bengkel resmi dan manual service.' }}</p>

            @if(!empty($tpdData['equipment']))
                <div class="mt-4 pt-4 border-t border-white/10">
                    <span class="text-xs font-bold text-amber-300">Peralatan dan Bahan Uji:</span>
                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2 text-xs text-slate-300">
                        @foreach($tpdData['equipment'] as $eq)
                            <li class="flex items-center gap-2">
                                <span class="text-lsp-red font-bold">•</span> {{ $eq }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Inspeksi Lembar Jawaban Kertas Asesi (Paper-Based FR.IA.02) --}}
        @php
            $evidencePath = $response?->data_json['evidence_image_path'] ?? null;
            $evidenceTime = $response?->data_json['evidence_uploaded_at'] ?? null;
            $evidenceName = $response?->data_json['evidence_original_name'] ?? 'Lembar_Jawaban_Fisik.jpg';
            $evidenceUrl = ($response && $evidencePath) ? route('secure-file.evidence', $response) : '#';
        @endphp
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="bg-slate-900 text-white px-6 py-3 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-mono font-bold tracking-wider uppercase text-amber-300">Pemeriksaan Bukti Lembar Fisik</span>
                    <span class="text-xs text-slate-300">— Tugas Praktik Demonstrasi (FR.IA.02)</span>
                </div>
                @if($evidencePath)
                    <span class="px-2.5 py-0.5 text-[11px] font-bold bg-emerald-500 text-white rounded">
                        ✓ Lembar Jawaban Terunggah
                    </span>
                @else
                    <span class="px-2.5 py-0.5 text-[11px] font-bold bg-amber-500 text-slate-900 rounded">
                        Belum Ada Unggahan Foto
                    </span>
                @endif
            </div>

            <div class="p-6">
                @if($evidencePath)
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        {{-- Preview Box --}}
                        <div class="lg:col-span-5 bg-slate-100 rounded-xl p-3 border border-slate-300 space-y-3">
                            <div class="relative group rounded-lg overflow-hidden border border-slate-300 bg-white aspect-4/3 flex items-center justify-center">
                                @if(str_ends_with(strtolower($evidencePath), '.pdf'))
                                    <div class="text-center p-6 space-y-2">
                                        <span class="text-4xl text-rose-600 font-black block">📄</span>
                                        <div class="text-xs font-bold text-slate-800">{{ $evidenceName }}</div>
                                        <div class="text-[11px] text-slate-500">Dokumen PDF Lembar Jawaban</div>
                                    </div>
                                @else
                                    <img src="{{ $evidenceUrl }}" alt="Lembar Jawaban Kertas Asesi" class="w-full h-full object-contain cursor-pointer transition hover:scale-105" onclick="window.open('{{ $evidenceUrl }}', '_blank')">
                                @endif
                            </div>
                            <div class="flex items-center justify-between gap-2 pt-1">
                                <a href="{{ $evidenceUrl }}" target="_blank" class="w-full text-center px-4 py-2 text-xs font-bold uppercase tracking-wider bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition">
                                    🔍 Buka Lembar Penuh (Tab Baru) &nearr;
                                </a>
                            </div>
                        </div>

                        {{-- Metadata --}}
                        <div class="lg:col-span-7 space-y-3 text-xs">
                            <div class="border border-slate-200 rounded-lg p-3 bg-slate-50 space-y-1.5">
                                <div class="font-bold text-slate-900 text-sm">Informasi Unggahan Asesi:</div>
                                <div class="text-slate-700">Nama Asesi: <strong class="text-slate-900">{{ $assessment->asesi->full_name }}</strong></div>
                                <div class="text-slate-700">Waktu Unggah: <span class="font-mono font-semibold text-slate-900">{{ \Carbon\Carbon::parse($evidenceTime)->isoFormat('D MMMM Y, HH:mm') }} WIB</span></div>
                                <div class="text-slate-700">Nama File: <span class="font-mono text-slate-700">{{ $evidenceName }}</span></div>
                            </div>
                            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-blue-900 text-xs">
                                Periksa kesesuaian langkah dan hasil kerja fisik asesi pada foto lembar kerja di samping dengan poin capaian unjuk kerja di bawah.
                            </div>
                        </div>
                    </div>
                @else
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-600 flex items-center gap-2.5">
                        <span class="text-slate-400 font-bold text-sm">ℹ️</span>
                        <span>Asesi belum mengunggah foto lembar kerja fisik. Asesor dapat melakukan evaluasi langsung berdasarkan observasi praktik demonstrasi di TUK.</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Demonstration Tasks --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" x-data="{
            setAllTasks(val) {
                const target = val.toLowerCase();
                document.querySelectorAll(`.task-radio-${target}`).forEach(el => {
                    el.checked = true;
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                });
            }
        }">
            <div class="px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Lembar Penilaian Praktik Demonstrasi</h2>
                    <p class="text-xs text-slate-500">Beri penilaian pencapaian untuk setiap instruksi tugas/demonstrasi</p>
                </div>
                <div class="flex items-center gap-2" x-show="isEditing">
                    <button type="button" @click="setAllTasks('YA')" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        Semua Ya
                    </button>
                    <button type="button" @click="setAllTasks('TIDAK')" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Semua Tidak
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3 w-16">No</th>
                            <th class="px-6 py-3">Instruksi Kerja / Tugas Praktik</th>
                            <th class="px-6 py-3 text-center w-40">Pencapaian (Ya / Tidak)</th>
                            <th class="px-6 py-3 w-48">Catatan Pengamatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($tasks ?? [] as $i => $task)
                            @php
                                $val = $savedAnswers[$task->id]['result'] ?? old("tasks.{$task->id}.result");
                                $note = $savedAnswers[$task->id]['note'] ?? old("tasks.{$task->id}.note");
                            @endphp
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-3 text-slate-500 font-mono text-xs">{{ $i + 1 }}</td>
                                <td class="px-6 py-3 text-slate-800">
                                    <div class="font-medium text-sm">{{ $task->title ?? $task->instruction }}</div>
                                    @if(isset($task->standard))
                                        <div class="text-xs text-slate-500 mt-0.5">Standar Kinerja: {{ $task->standard }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <div class="flex items-center justify-center space-x-4">
                                        <label class="p-2 inline-flex items-center cursor-pointer">
                                            <input type="radio" name="tasks[{{ $task->id }}][result]" value="YA" required :disabled="!isEditing" class="task-radio task-radio-ya text-emerald-600 focus:ring-emerald-500 w-5 h-5 cursor-pointer" {{ $val === 'YA' ? 'checked' : '' }}>
                                            <span class="ml-1.5 text-xs font-bold text-emerald-700">Ya</span>
                                        </label>
                                        <label class="p-2 inline-flex items-center cursor-pointer">
                                            <input type="radio" name="tasks[{{ $task->id }}][result]" value="TIDAK" required :disabled="!isEditing" class="task-radio task-radio-tidak text-rose-600 focus:ring-rose-500 w-5 h-5 cursor-pointer" {{ $val === 'TIDAK' ? 'checked' : '' }}>
                                            <span class="ml-1.5 text-xs font-bold text-rose-700">Tidak</span>
                                        </label>
                                    </div>
                                </td>
                                <td class="px-6 py-3">
                                    <input type="text" name="tasks[{{ $task->id }}][note]" value="{{ $note }}" :disabled="!isEditing" placeholder="Catatan..." class="w-full px-2.5 py-1.5 text-xs rounded border border-slate-300 focus:ring-1 focus:ring-brand-500 focus:outline-none">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-slate-500 text-xs">Belum ada tugas praktik yang dikonfigurasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Notes & Assessor Signature --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900">Umpan Balik Praktik Demonstrasi</h2>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Catatan Asesor</label>
                <textarea name="notes" rows="3" :disabled="!isEditing" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Catatan mengenai pelaksanaan demonstrasi praktis...">{{ $savedNotes ?? old('notes') }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-200">
                <x-signature-pad name="assessor_signature" :value="$savedSignature ?? null" label="Tanda Tangan Asesor" :required="true" />
            </div>
        </div>

        <div class="flex items-center justify-end space-x-3">
            <a href="{{ route('asesor.schedule.instrument', ['schedule' => $assessment->schedule_id, 'code' => 'ia02']) }}" class="px-5 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">Kembali</a>
            <button type="button" x-show="!isEditing" @click="isEditing = true" class="px-6 py-2.5 text-sm font-bold bg-amber-500 hover:bg-amber-600 text-white rounded-lg shadow-sm transition">
                ✎ Edit Penilaian
            </button>
            <button type="submit" x-show="isEditing" class="px-6 py-2.5 text-sm font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition">
                {{ $isCompleted ? 'Simpan Perubahan Penilaian' : 'Simpan Penilaian IA02' }}
            </button>
        </div>
    </form>
</div>
@endsection