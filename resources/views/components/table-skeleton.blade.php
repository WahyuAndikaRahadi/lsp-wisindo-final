@props([
    'rows' => 5,
    'cols' => 5,
    'type' => 'table' // 'table' or 'card'
])

<div class="animate-pulse space-y-4" aria-busy="true" aria-label="Memuat data...">
    {{-- Mobile Cards Skeleton (< md) --}}
    <div class="block md:hidden divide-y divide-slate-100 bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
        @for($i = 0; $i < $rows; $i++)
            <div class="p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center space-x-3 w-full">
                        <div class="w-9 h-9 rounded-full bg-slate-200 shrink-0"></div>
                        <div class="space-y-1.5 flex-1 min-w-0">
                            <div class="h-3.5 bg-slate-200 rounded w-3/4"></div>
                            <div class="h-2.5 bg-slate-100 rounded w-1/2"></div>
                        </div>
                    </div>
                    <div class="w-16 h-5 bg-slate-100 rounded shrink-0"></div>
                </div>
                <div class="space-y-1.5 pt-1">
                    <div class="h-2.5 bg-slate-100 rounded w-5/6"></div>
                    <div class="h-2.5 bg-slate-100 rounded w-2/3"></div>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                    <div class="w-20 h-3 bg-slate-100 rounded"></div>
                    <div class="flex gap-2">
                        <div class="w-14 h-6 bg-slate-200 rounded"></div>
                        <div class="w-14 h-6 bg-slate-200 rounded"></div>
                    </div>
                </div>
            </div>
        @endfor
    </div>

    {{-- Desktop Table Skeleton (>= md) --}}
    <div class="hidden md:block bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
        <div class="min-w-full">
            {{-- Header Placeholder --}}
            <div class="bg-slate-50 border-b border-slate-200 px-6 py-3.5 grid gap-4 items-center" style="grid-template-columns: repeat({{ $cols }}, minmax(0, 1fr));">
                @for($c = 0; $c < $cols; $c++)
                    <div class="h-3 bg-slate-200 rounded w-2/3"></div>
                @endfor
            </div>

            {{-- Rows Placeholder --}}
            <div class="divide-y divide-slate-100">
                @for($r = 0; $r < $rows; $r++)
                    <div class="px-6 py-4 grid gap-4 items-center" style="grid-template-columns: repeat({{ $cols }}, minmax(0, 1fr));">
                        @for($c = 0; $c < $cols; $c++)
                            <div class="space-y-1.5">
                                <div class="h-3.5 bg-slate-200 rounded {{ $c === 0 ? 'w-4/5' : ($c === $cols - 1 ? 'w-1/2 ml-auto' : 'w-3/5') }}"></div>
                                @if($c === 0)
                                    <div class="h-2.5 bg-slate-100 rounded w-1/2"></div>
                                @endif
                            </div>
                        @endfor
                    </div>
                @endfor
            </div>

            {{-- Pagination Shimmer Footer --}}
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <div class="h-3 bg-slate-200 rounded w-32"></div>
                <div class="flex gap-1.5">
                    <div class="w-7 h-7 bg-slate-200 rounded"></div>
                    <div class="w-7 h-7 bg-slate-200 rounded"></div>
                    <div class="w-7 h-7 bg-slate-200 rounded"></div>
                </div>
            </div>
        </div>
    </div>
</div>
