@extends('layouts.app', ['title' => 'Proxy Role / Masuk Sebagai Pengguna'])

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Proxy Role / Masuk Sebagai Pengguna</h1>
        <p class="text-sm text-slate-500">Beralih peran secara langsung untuk menguji alur sistem atau membantu pengguna</p>
    </div>

    {{-- Filter / Search Tabs --}}
    @php
        $currentRole = request('role', 'ALL');
        $hasFilter = request()->filled('search') || (request()->filled('role') && request('role') !== 'ALL');
    @endphp
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
        <form method="GET" action="{{ route('admin.proxy.index') }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            <input type="hidden" name="role" id="proxyRoleInput" value="{{ $currentRole }}">

            <div class="flex items-center space-x-2 overflow-x-auto pb-1 md:pb-0">
                <a href="{{ route('admin.proxy.index', array_merge(request()->except('role', 'page'), ['role' => 'ALL'])) }}"
                   class="px-3.5 py-2 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $currentRole === 'ALL' ? 'bg-brand-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Semua Pengguna
                </a>
                <a href="{{ route('admin.proxy.index', array_merge(request()->except('role', 'page'), ['role' => 'ASESOR'])) }}"
                   class="px-3.5 py-2 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $currentRole === 'ASESOR' ? 'bg-brand-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Asesor Saja
                </a>
                <a href="{{ route('admin.proxy.index', array_merge(request()->except('role', 'page'), ['role' => 'ASESI'])) }}"
                   class="px-3.5 py-2 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $currentRole === 'ASESI' ? 'bg-brand-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Asesi Saja
                </a>
                <a href="{{ route('admin.proxy.index', array_merge(request()->except('role', 'page'), ['role' => 'ADMIN_LSP'])) }}"
                   class="px-3.5 py-2 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $currentRole === 'ADMIN_LSP' ? 'bg-brand-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Admin LSP
                </a>
                <a href="{{ route('admin.proxy.index', array_merge(request()->except('role', 'page'), ['role' => 'TUK_ADMIN'])) }}"
                   class="px-3.5 py-2 rounded-lg text-xs font-bold transition whitespace-nowrap {{ $currentRole === 'TUK_ADMIN' ? 'bg-brand-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Admin TUK
                </a>
            </div>

            <div class="flex items-center gap-2 w-full md:w-auto">
                <div class="relative flex-1 md:w-72">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..."
                           class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <button type="submit" class="px-3.5 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-lg shadow-sm transition flex items-center justify-center gap-1 shrink-0" title="Cari">
                    <span>Cari</span>
                </button>
                @if($hasFilter)
                    <a href="{{ route('admin.proxy.index') }}" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-bold transition flex items-center justify-center border border-slate-200 shrink-0" title="Reset Pencarian & Filter">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Users Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3">Nama Lengkap</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Role</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Aksi Proxy</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users ?? [] as $user)
                        @php
                            $roleBadge = match($user->role) {
                                'SUPER_ADMIN' => 'bg-purple-100 text-purple-700 border-purple-200',
                                'ADMIN_LSP' => 'bg-blue-100 text-blue-700 border-blue-200',
                                'ASESOR' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                'ASESI' => 'bg-amber-100 text-amber-700 border-amber-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-900">{{ $user->full_name }}</div>
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-slate-600">{{ $user->email }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full border {{ $roleBadge }}">
                                    {{ $user->role }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Aktif
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($user->id !== auth()->id())
                                    <form action="{{ route('proxy.switch', $user->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3.5 py-1.5 text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white rounded-lg shadow-sm transition inline-flex items-center space-x-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                            </svg>
                                            <span>Masuk Sebagai Pengguna Ini</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400 italic">Akun Anda Saat Ini</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500 text-xs">
                                @if($hasFilter)
                                    Tidak ada data pengguna yang sesuai dengan pencarian atau filter. <a href="{{ route('admin.proxy.index') }}" class="text-brand-600 font-bold hover:underline">Reset filter</a>
                                @else
                                    Tidak ada data pengguna untuk proxy.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($users) && method_exists($users, 'links'))
            <div class="p-4 border-t border-slate-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
