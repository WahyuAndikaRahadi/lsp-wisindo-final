@extends('layouts.app', ['title' => 'Review Jadwal & Peserta - ' . $jadwal->name])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('admin.jadwal.index') }}" class="text-xs text-brand-600 hover:text-brand-700 font-semibold">&larr; Kembali ke Daftar Jadwal</a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">{{ $jadwal->name }}</h1>
            <div class="flex items-center space-x-3 text-xs text-slate-500 mt-1">
                <span class="font-mono font-bold text-brand-700 bg-brand-50 px-2 py-0.5 rounded">{{ $jadwal->code }}</span>
                <span>{{ $jadwal->scheme->name }}</span>
                <span>TUK: {{ $jadwal->tuk_name }}</span>
                <span>{{ \Carbon\Carbon::parse($jadwal->date)->isoFormat('D MMMM Y') }}</span>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('admin.dokumen-muk.index') }}" class="px-3.5 py-2 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg shadow-xs transition">
                Pusat Dokumen MUK &rarr;
            </a>
        </div>
    </div>

    {{-- Review Asesi Table (4 Status Components) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Tabel Status & Review Peserta Uji ({{ $jadwal->assessments->count() }})</h2>
                <p class="text-xs text-slate-500">Pantau 4 komponen status: Approval Admin, Approval Asesor, Hasil Asesmen, dan Status Banding</p>
            </div>
            @php
                $unapproved = $jadwal->assessments->where('admin_status', '!=', 'APPROVED');
                $workflowSvc = app(\App\Services\AkWorkflowService::class);
                $eligibleCount = 0;
                $ineligibleCount = 0;
                foreach ($unapproved as $asm) {
                    if ($workflowSvc->isCandidateDocumentComplete($asm)) {
                        $eligibleCount++;
                    } else {
                        $ineligibleCount++;
                    }
                }
            @endphp
            @if($unapproved->count() > 0)
                <form action="{{ route('admin.jadwal.batch-review-registration', $jadwal->id) }}" method="POST"
                    onsubmit="return confirm('Konfirmasi Persetujuan Berkas Pendaftar:\n\n- {{ $eligibleCount }} peserta berkas LENGKAP akan disetujui.\n- {{ $ineligibleCount }} peserta berkas BELUM LENGKAP akan DILEWATI.\n\nLanjutkan proses persetujuan?')">
                    @csrf
                    <input type="hidden" name="admin_status" value="APPROVED">
                    <button type="submit" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        <span>Setujui Berkas Lengkap ({{ $eligibleCount }})</span>
                        @if($ineligibleCount > 0)
                            <span class="px-1.5 py-0.5 rounded text-[10px] bg-emerald-800 text-emerald-100" title="{{ $ineligibleCount }} peserta belum melengkapi berkas">{{ $ineligibleCount }} dilewati</span>
                        @endif
                    </button>
                </form>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-center w-10">No</th>
                        <th class="px-4 py-3 text-left">Nama Asesi & Kontak</th>
                        <th class="px-3 py-3 text-center">1. Approval Admin</th>
                        <th class="px-3 py-3 text-center">2. Approval Asesor</th>
                        <th class="px-3 py-3 text-center">3. Hasil Asesmen</th>
                        <th class="px-3 py-3 text-center">4. Banding (AK.04)</th>
                        <th class="px-4 py-3 text-right">Aksi Admin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($jadwal->assessments as $idx => $assessment)
                        @php
                            $adminBadge = match($assessment->admin_status ?? 'PENDING') {
                                'APPROVED' => 'bg-emerald-50 text-emerald-700 border-emerald-300',
                                'REVISION' => 'bg-amber-50 text-amber-700 border-amber-300',
                                'REJECTED' => 'bg-rose-50 text-rose-700 border-rose-300',
                                default => 'bg-slate-100 text-slate-600 border-slate-300',
                            };
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
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3 text-center text-slate-500 font-mono">{{ $idx + 1 }}</td>
                            <td class="px-4 py-3">
                                <span class="font-bold text-slate-900 block text-sm">{{ $assessment->asesi->full_name }}</span>
                                <span class="text-slate-500 font-mono text-[11px]">{{ $assessment->asesi->email }} | NIK: {{ $assessment->asesi->profile?->nik ?? '-' }}</span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold border {{ $adminBadge }}">
                                    {{ $assessment->admin_status ?? 'PENDING' }}
                                </span>
                                @if($assessment->admin_reviewed_at || $assessment->admin_approved_at)
                                    <div class="text-[10px] text-slate-400 mt-1 leading-tight">
                                        {{ \Carbon\Carbon::parse($assessment->admin_reviewed_at ?? $assessment->admin_approved_at)->format('d/m/y H:i') }}
                                        @if($assessment->adminReviewer)
                                            <span class="block text-slate-500 font-medium">oleh {{ $assessment->adminReviewer->name }}</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold border {{ $asesorBadge }}">
                                    {{ str_replace('_', ' ', $assessment->apl_status ?? 'DRAFT') }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[11px] border {{ $hasilBadge }}">
                                    {{ $assessment->final_result ?? ($assessment->status === 'COMPLETED' ? 'KOMPETEN' : 'BELUM SELESAI') }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($appeal)
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold border bg-purple-50 text-purple-700 border-purple-300">
                                        {{ $appeal->status }}
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form action="{{ route('admin.assessment.review-registration', $assessment->id) }}" method="POST" class="inline-flex items-center space-x-1">
                                    @csrf
                                    @if(($assessment->admin_status ?? 'PENDING') !== 'APPROVED')
                                        <button type="submit" name="admin_status" value="APPROVED" class="px-2.5 py-1 text-[11px] font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded shadow-xs" title="Setujui pendaftaran">
                                            ✓ Setujui
                                        </button>
                                    @endif
                                    @if(($assessment->admin_status ?? 'PENDING') !== 'REVISION')
                                        <button type="submit" name="admin_status" value="REVISION" class="px-2 py-1 text-[11px] font-bold text-amber-800 bg-amber-100 hover:bg-amber-200 border border-amber-300 rounded shadow-xs" title="Minta perbaikan berkas"
                                                onclick="return confirm('Minta perbaikan berkas asesi {{ addslashes($assessment->asesi->full_name) }}? Status pendaftaran akan diubah menjadi REVISION dan asesi diminta memperbaiki berkas.');">
                                            Revisi
                                        </button>
                                    @endif
                                    @if(($assessment->admin_status ?? 'PENDING') !== 'REJECTED')
                                        <button type="submit" name="admin_status" value="REJECTED" class="px-2 py-1 text-[11px] font-bold text-rose-800 bg-rose-100 hover:bg-rose-200 border border-rose-300 rounded shadow-xs" title="Tolak pendaftaran"
                                                onclick="return confirm('PERINGATAN KONSEKUENSI PENOLAKAN ASESI:\n\nMenolak berkas asesi akan membatalkan hak asesi untuk mengikuti proses asesmen pada jadwal ini.\n\nApakah Anda yakin ingin MENOLAK asesi {{ addslashes($assessment->asesi->full_name) }}?');">
                                            Tolak
                                        </button>
                                    @endif
                                </form>
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
    </div>
</div>
@endsection
