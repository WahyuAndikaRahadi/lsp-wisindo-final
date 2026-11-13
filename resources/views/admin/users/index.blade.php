@extends('layouts.app', ['title' => 'Kelola Pengguna'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Kelola Pengguna</h1>
            <p class="text-sm text-slate-500">Daftar semua pengguna terdaftar dalam sistem LSP</p>
        </div>
        <div>
            <a href="{{ route('admin.users.create') }}" class="px-4 py-2.5 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-sm font-bold shadow-sm transition flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tambah Pengguna</span>
            </a>
        </div>
    </div>

    {{-- Filter & Pencarian Pengguna --}}
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.users.index') }}" data-ajax-filter="users" data-no-loading="true" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            {{-- Search Box --}}
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama, email, nomor HP, atau No. Reg Asesor..."
                       class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-1 focus:ring-brand-500 focus:border-brand-500">
            </div>

            {{-- Role Dropdown --}}
            <div class="w-full sm:w-48">
                <select name="role" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-1 focus:ring-brand-500 focus:border-brand-500 font-medium">
                    <option value="">Semua Peran / Role</option>
                    <option value="ASESI" @selected(request('role') === 'ASESI')>Asesi (Peserta)</option>
                    <option value="ASESOR" @selected(request('role') === 'ASESOR')>Asesor Penguji</option>
                    <option value="ADMIN_LSP" @selected(request('role') === 'ADMIN_LSP')>Admin LSP</option>
                    <option value="SUPER_ADMIN" @selected(request('role') === 'SUPER_ADMIN')>Super Admin</option>
                    <option value="KOMITE_TEKNIS" @selected(request('role') === 'KOMITE_TEKNIS')>Komite Teknis</option>
                </select>
            </div>

            {{-- Filter & Reset Buttons --}}
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-2xs">
                    <span>Filter</span>
                </button>
                <a href="{{ route('admin.users.index') }}" data-ajax-reset="users" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition flex items-center justify-center {{ request()->hasAny(['search', 'role', 'status']) ? '' : 'hidden' }}">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Users List (Responsive: Cards on Mobile, Table on Desktop) --}}
    <div id="users-table-container" data-ajax-container="users" data-skeleton-cols="6" data-skeleton-rows="6">
        @include('admin.users._table')
    </div>
</div>
@endsection
