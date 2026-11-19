<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-4 sm:px-6 py-3.5 sm:py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between items-start gap-1">
        <div>
            <h2 class="text-sm font-bold text-slate-900">Daftar Peserta Asesmen ({{ $assessments->total() ?? $assessments->count() }})</h2>
            <p class="text-xs text-slate-500">Status verifikasi APL oleh asesor, progres ujian, dan hasil rekomendasi asesmen</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="hidden md:table-header-group bg-slate-100 text-slate-700 font-bold border-b border-slate-200 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-center w-10">No</th>
                    <th class="px-4 py-3 text-left">Nama Asesi & Kontak</th>
                    <th class="px-3 py-3 text-left">Asesor Penguji</th>
                    <th class="px-3 py-3 text-center">Status APL (Asesor)</th>
                    <th class="px-3 py-3 text-center">Hasil Asesmen</th>
                    <th class="px-3 py-3 text-center">Banding (AK.04)</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($assessments as $idx => $assessment)
                    @php
                        $itemNumber = method_exists($assessments, 'firstItem') ? ($assessments->firstItem() + $idx) : ($idx + 1);
                        $asesorBadge = match($assessment->apl_status ?? 'DRAFT') {
                            'APPROVED' => 'bg-emerald-50 text-emerald-700 border-emerald-300',
                            'REVISION_REQUESTED' => 'bg-amber-50 text-amber-700 border-amber-300',
                            'REJECTED' => 'bg-rose-50 text-rose-700 border-rose-300',
                            'SUBMITTED' => 'bg-blue-50 text-blue-700 border-blue-300',
                            default => 'bg-slate-100 text-slate-600 border-slate-300',
                        };
                        $hasilBadge = match($assessment->final_result ?? 'BELUM_SELESAI') {
                            'KOMPETEN' => 'bg-emerald-100 text-emerald-800 border-emerald-400 font-black',
                            'BELUM_KOMPETEN' => 'bg-rose-100 text-rose-800 border-rose-400 font-black',
                            default => 'bg-slate-100 text-slate-500 border-slate-300',
                        };
                        $appeal = $assessment->latestAppeal;
                    @endphp
                    <tr class="flex flex-col md:table-row p-4 md:p-0 hover:bg-slate-50/60 transition space-y-3 md:space-y-0">
                        <td class="hidden md:table-cell px-4 py-3 text-center text-slate-500 font-mono">{{ $itemNumber }}</td>
                        <td class="px-0 md:px-4 py-0 md:py-3">
                            <div class="flex items-center justify-between md:hidden pb-2 border-b border-slate-100">
                                <span class="font-mono text-xs font-bold text-slate-500">#{{ $itemNumber }}</span>
                                <div class="flex items-center gap-1.5 flex-wrap justify-end">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $asesorBadge }}">
                                        APL: {{ str_replace('_', ' ', $assessment->apl_status ?? 'DRAFT') }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $hasilBadge }}">
                                        {{ $assessment->final_result ?? ($assessment->status === 'COMPLETED' ? 'KOMPETEN' : 'BELUM SELESAI') }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-2 md:mt-0">
                                <span class="font-bold text-slate-900 block text-sm">{{ $assessment->asesi->full_name }}</span>
                                <span class="text-slate-500 font-mono text-[11px]">{{ $assessment->asesi->email }} &bull; NIK: {{ $assessment->asesi->profile?->nik ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="px-0 md:px-3 py-0 md:py-3">
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider md:hidden mb-1">Asesor Penguji:</label>
                            @if($jadwal->assessors->isNotEmpty())
                                <form action="{{ route('admin.assessment.assign-assessor', $assessment) }}" method="POST" class="space-y-1">
                                    @csrf
                                    <select name="assessor_id" onchange="this.form.submit()" class="text-xs py-2 md:py-1 px-2.5 border border-slate-300 rounded-lg bg-white text-slate-800 font-medium focus:ring-1 focus:ring-brand-500 w-full md:max-w-[180px]">
                                        <option value="">-- Pilih Asesor --</option>
                                        @foreach($jadwal->assessors as $asesor)
                                            <option value="{{ $asesor->id }}" @selected($assessment->assessor_id == $asesor->id)>
                                                {{ $asesor->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($assessment->assessor)
                                        <span class="text-[10px] text-slate-400 font-mono block">{{ $assessment->assessor->assessor_registration_number ?? '' }}</span>
                                    @endif
                                </form>
                            @else
                                <span class="font-semibold text-slate-800 block text-xs">{{ $assessment->assessor?->full_name ?? '-' }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $assessment->assessor?->assessor_registration_number ?? '' }}</span>
                            @endif
                        </td>
                        <td class="hidden md:table-cell px-3 py-3 text-center">
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold border {{ $asesorBadge }}">
                                {{ str_replace('_', ' ', $assessment->apl_status ?? 'DRAFT') }}
                            </span>
                        </td>
                        <td class="hidden md:table-cell px-3 py-3 text-center">
                            <span class="px-2 py-0.5 rounded text-[11px] border {{ $hasilBadge }}">
                                {{ $assessment->final_result ?? ($assessment->status === 'COMPLETED' ? 'KOMPETEN' : 'BELUM SELESAI') }}
                            </span>
                        </td>
                        <td class="px-0 md:px-3 py-0 md:py-3 md:text-center">
                            @if($appeal)
                                <div class="flex items-center justify-between md:justify-center text-xs">
                                    <span class="md:hidden text-slate-500">Status Banding:</span>
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold border bg-purple-50 text-purple-700 border-purple-300">
                                        {{ $appeal->status }}
                                    </span>
                                </div>
                            @else
                                <span class="hidden md:inline text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-0 md:px-4 py-2 md:py-3 text-left md:text-right whitespace-nowrap">
                            <div class="flex items-center justify-between md:justify-end pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
                                <span class="text-xs font-semibold text-slate-500 md:hidden">Keluarkan Asesi:</span>
                                <form action="{{ route('admin.jadwal.remove-candidate', ['jadwal' => $jadwal->id, 'assessment' => $assessment->id]) }}" method="POST" class="inline"
                                      data-confirm="PERINGATAN: Mengeluarkan asesi {{ $assessment->asesi->full_name }} akan menghapus seluruh data pengerjaan asesmen, jawaban instrumen, dan berkas unggahan asesi pada jadwal ini. Asesi juga akan dihapus dari Berita Acara dan dokumen MUK. Lanjutkan?"
                                      data-confirm-title="Keluarkan Asesi dari Jadwal"
                                      data-confirm-ok="Ya, Keluarkan Asesi"
                                      data-confirm-danger="true">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:text-rose-900 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition" title="Keluarkan asesi dari jadwal ini">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"/>
                                        </svg>
                                        <span>Keluarkan</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-slate-400 italic">
                            Belum ada asesi yang terdaftar pada jadwal ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($assessments, 'links'))
        <div class="p-4 border-t border-slate-200">
            {{ $assessments->links() }}
        </div>
    @endif
</div>
