@if($assessments->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 p-8 text-center shadow-sm">
        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <h3 class="text-sm font-bold text-slate-700">Belum Terdaftar di Jadwal Mana Pun</h3>
        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
            @if(request()->filled('search'))
                Tidak ada jadwal yang sesuai dengan pencarian "{{ request('search') }}". <a href="{{ route('asesi.jadwal.index') }}" data-ajax-reset="asesi-jadwal" class="text-brand-600 font-bold hover:underline">Reset</a>
            @else
                Silakan minta kode jadwal kepada pengelola TUK atau asesor Anda dan masukkan pada kolom di atas.
            @endif
        </p>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @foreach($assessments as $item)
            @php
                $sch = $item->schedule;
                $isRejected = ($item->apl_status === 'REJECTED' || $item->admin_status === 'REJECTED');
                $aplStatus = $isRejected ? 'REJECTED' : $item->apl_status;

                $aplBadge = match($aplStatus) {
                    'APPROVED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'SUBMITTED' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'REVISION_REQUESTED' => 'bg-amber-50 text-amber-700 border-amber-200',
                    'REJECTED' => 'bg-rose-50 text-rose-700 border-rose-200',
                    default => 'bg-slate-50 text-slate-600 border-slate-200',
                };

                $isResume = ($aplStatus === 'APPROVED');
                $actionUrl = route('asesi.assessment.apl', $item->id);

                if ($aplStatus === 'APPROVED') {
                    $actionUrl = route('asesi.assessment.ruang-uji', $item->id);
                }
            @endphp

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-6 flex flex-col justify-between hover:shadow-md transition">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded bg-brand-50 text-brand-700 font-mono text-xs font-bold">
                            {{ $sch->code }}
                        </span>
                        @if($sch->is_closed)
                            <span class="px-2.5 py-0.5 rounded text-xs font-bold border bg-emerald-50 text-emerald-800 border-emerald-300">
                                🔒 Selesai
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded text-xs font-semibold border {{ $aplBadge }}">
                                APL: {{ str_replace('_', ' ', $aplStatus) }}
                            </span>
                        @endif
                    </div>

                    <div>
                        <h3 class="text-base font-bold text-slate-900 leading-snug">
                            {{ $sch->name ?? $sch->scheme->name }}
                        </h3>
                        <p class="text-xs text-brand-600 font-medium mt-0.5">
                            Skema: {{ $sch->scheme->name }} ({{ $sch->scheme->code }})
                        </p>
                    </div>

                    <div class="space-y-1.5 text-xs text-slate-600 pt-2 border-t border-slate-100">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span>TUK: <strong class="text-slate-800">{{ $sch->tuk_name }}</strong></span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ \Carbon\Carbon::parse($sch->date)->isoFormat('dddd, D MMMM Y') }}</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>{{ substr($sch->start_time, 0, 5) }} - {{ substr($sch->end_time, 0, 5) }} WIB ({{ $sch->duration_minutes ?? '-' }} Menit)</span>
                        </div>
                        @if($item->assessor)
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7 7z"/>
                                </svg>
                                <span>Asesor: <strong class="text-slate-800">{{ $item->assessor->full_name }}</strong></span>
                            </div>
                        @endif
                    </div>

                    @if(($aplStatus === 'REVISION_REQUESTED' || $isRejected) && $item->apl_revision_notes && ! $sch->is_closed)
                        <div class="p-2.5 rounded bg-rose-50 border border-rose-200 text-xs text-rose-900">
                            <strong class="font-bold">{{ $isRejected ? 'Alasan Penolakan Asesor:' : 'Catatan Revisi Asesor:' }}</strong>
                            <p class="mt-0.5">{{ $item->apl_revision_notes }}</p>
                        </div>
                    @endif
                </div>

                <div class="mt-5 pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between items-start gap-2.5">
                    <span class="text-[11px] text-slate-400">
                        Terdaftar: {{ $item->created_at ? $item->created_at->diffForHumans() : '-' }}
                    </span>

                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        @if(!empty($sch->meeting_link))
                            <a href="{{ $sch->meeting_link }}" target="_blank" rel="noopener noreferrer"
                               class="w-full sm:w-auto px-3 py-2 text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg shadow-2xs transition inline-flex items-center justify-center gap-1.5 text-center"
                               title="Masuk Ruang Video Meeting AJJ">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <span>Zoom / Meet ↗</span>
                            </a>
                        @endif

                        @if($isRejected)
                            <a href="{{ route('asesi.assessment.apl', $item->id) }}" class="w-full sm:w-auto px-4 py-2 text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-300 rounded-lg shadow-xs transition inline-flex items-center justify-center gap-1 text-center">
                                <span>✕ Ditolak</span>
                            </a>
                        @elseif($sch->is_closed || $item->status === 'COMPLETED')
                            <a href="{{ route('asesi.assessment.ruang-uji', $item->id) }}" class="w-full sm:w-auto px-4 py-2 text-xs font-bold bg-slate-800 hover:bg-slate-900 text-white rounded-lg transition inline-flex items-center justify-center gap-1.5 text-center">
                                <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <span>Ruang Uji &rarr;</span>
                            </a>
                        @elseif($isResume)
                            <a href="{{ $actionUrl }}" class="w-full sm:w-auto px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg shadow-sm transition text-center">
                                Lanjut Uji &rarr;
                            </a>
                        @elseif($aplStatus === 'REVISION_REQUESTED')
                            <a href="{{ route('asesi.assessment.apl', $item->id) }}" class="w-full sm:w-auto px-4 py-2 text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white rounded-lg shadow-sm transition text-center">
                                Revisi APL &rarr;
                            </a>
                        @else
                            <a href="{{ route('asesi.assessment.apl', $item->id) }}" class="w-full sm:w-auto px-4 py-2 text-xs font-bold bg-brand-600 hover:bg-brand-700 text-white rounded-lg shadow-sm transition text-center">
                                Isi APL &rarr;
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if(method_exists($assessments, 'hasPages') && $assessments->hasPages())
        <div class="pt-4">
            {{ $assessments->links() }}
        </div>
    @endif
@endif
