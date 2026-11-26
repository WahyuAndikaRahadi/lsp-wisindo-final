<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    {{-- Mobile Cards (< md) --}}
    <div class="block md:hidden divide-y divide-slate-100">
        @forelse($users ?? [] as $user)
            @php
                $roleBadge = match($user->role) {
                    'SUPER_ADMIN' => 'bg-purple-100 text-purple-700 border-purple-200',
                    'ADMIN_LSP' => 'bg-blue-100 text-blue-700 border-blue-200',
                    'ASESOR' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    'ASESI' => 'bg-amber-100 text-amber-700 border-amber-200',
                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                };
                $statusBadge = match($user->status ?? 'ACTIVE') {
                    'ACTIVE' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'INACTIVE' => 'bg-slate-100 text-slate-700 border-slate-200',
                    'SUSPENDED' => 'bg-rose-50 text-rose-700 border-rose-200',
                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                };
                $statusLabel = match($user->status ?? 'ACTIVE') {
                    'ACTIVE' => 'Aktif',
                    'INACTIVE' => 'Nonaktif',
                    'SUSPENDED' => 'Ditangguhkan',
                    default => $user->status,
                };
            @endphp
            <div class="p-4 space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center space-x-3 min-w-0">
                        <div class="w-9 h-9 rounded-full bg-slate-800 text-white font-bold flex items-center justify-center text-xs shrink-0">
                            {{ strtoupper(substr($user->full_name, 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-bold text-slate-900 text-sm leading-snug truncate">{{ $user->full_name }}</h3>
                            <p class="text-xs font-mono text-slate-500 truncate">{{ $user->email }}</p>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded border {{ $statusBadge }} shrink-0">
                        {{ $statusLabel }}
                    </span>
                </div>

                <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-100">
                    <span class="px-2.5 py-0.5 text-[11px] font-semibold rounded-full border {{ $roleBadge }}">
                        {{ $user->role }}
                    </span>
                    <span class="text-[11px] text-slate-400">
                        Dibuat {{ $user->created_at?->diffForHumans() ?? '-' }}
                    </span>
                </div>

                <div class="flex items-center gap-2 pt-1 flex-wrap">
                    <a href="{{ route('admin.users.edit', $user->id) }}" class="flex-1 px-3 py-2 text-xs font-bold text-brand-700 bg-brand-50 hover:bg-brand-100 border border-brand-200 rounded-lg text-center transition flex items-center justify-center min-h-[38px]">
                        Edit
                    </a>
                    @if(auth()->user()->isRole('SUPER_ADMIN'))
                        <form action="{{ route('admin.users.reset-password', $user->id) }}" method="POST" class="flex-1"
                              data-confirm="PERINGATAN RESET PASSWORD:

Kata sandi akun {{ $user->full_name }} ({{ $user->email }}) akan direset kembali ke kata sandi default: 'wisindo123'.

Apakah Anda yakin ingin melanjutkan?"
                              data-confirm-title="Konfirmasi Reset Kata Sandi"
                              data-confirm-ok="Ya, Reset Password"
                              data-confirm-danger="false">
                            @csrf
                            <button type="submit" class="w-full px-3 py-2 text-xs font-bold text-amber-700 hover:text-amber-900 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-lg text-center transition min-h-[38px]" title="Reset password ke default 'wisindo123'">
                                Reset Sandi
                            </button>
                        </form>
                    @endif
                    @if($user->id !== auth()->id())
                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="flex-1"
                              data-confirm="PERINGATAN KONSEKUENSI PENGHAPUSAN PENGGUNA:

Jika pengguna ini memiliki riwayat asesmen atau jadwal, status akun akan dinonaktifkan (INACTIVE) untuk menjaga integritas data audit BNSP. Jika tidak ada riwayat, data akan dihapus permanen.

Apakah Anda yakin ingin melanjutkan?"
                              data-confirm-title="Konfirmasi Penghapusan Pengguna"
                              data-confirm-ok="Ya, Lanjutkan Hapus"
                              data-confirm-danger="true">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full px-3 py-2 text-xs font-bold text-rose-700 hover:text-rose-900 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg text-center transition min-h-[38px]">
                                Hapus
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-slate-500 text-xs">Belum ada pengguna terdaftar.</div>
        @endforelse
    </div>

    {{-- Desktop Table (md+) --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-3">Nama Pengguna</th>
                    <th class="px-6 py-3">Email</th>
                    <th class="px-6 py-3">Peran / Role</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Terdaftar</th>
                    <th class="px-6 py-3 text-right">Aksi</th>
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
                            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $roleBadge }}">
                                {{ $user->role }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusBadge = match($user->status ?? 'ACTIVE') {
                                    'ACTIVE' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'INACTIVE' => 'bg-slate-100 text-slate-700 border-slate-200',
                                    'SUSPENDED' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                                };
                                $statusLabel = match($user->status ?? 'ACTIVE') {
                                    'ACTIVE' => 'Aktif',
                                    'INACTIVE' => 'Nonaktif',
                                    'SUSPENDED' => 'Ditangguhkan',
                                    default => $user->status,
                                };
                            @endphp
                            <span class="px-2 py-0.5 text-xs font-semibold rounded border {{ $statusBadge }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500">
                            {{ $user->created_at?->diffForHumans() ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="{{ route('admin.users.edit', $user->id) }}" class="px-2.5 py-1 text-xs font-semibold text-brand-600 hover:text-brand-800 bg-brand-50 rounded">
                                    Edit
                                </a>
                                @if(auth()->user()->isRole('SUPER_ADMIN'))
                                    <form action="{{ route('admin.users.reset-password', $user->id) }}" method="POST" class="inline"
                                          data-confirm="PERINGATAN RESET PASSWORD:

Kata sandi akun {{ $user->full_name }} ({{ $user->email }}) akan direset kembali ke kata sandi default: 'wisindo123'.

Apakah Anda yakin ingin melanjutkan?"
                                          data-confirm-title="Konfirmasi Reset Kata Sandi"
                                          data-confirm-ok="Ya, Reset Password"
                                          data-confirm-danger="false">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold text-amber-700 hover:text-amber-900 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded transition" title="Reset password ke default 'wisindo123'">
                                            Reset Sandi
                                        </button>
                                    </form>
                                @endif
                                @if($user->id !== auth()->id())
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline"
                                          data-confirm="PERINGATAN KONSEKUENSI PENGHAPUSAN PENGGUNA:

Jika pengguna ini memiliki riwayat asesmen atau jadwal, status akun akan dinonaktifkan (INACTIVE) untuk menjaga integritas data audit BNSP. Jika tidak ada riwayat, data akan dihapus permanen.

Apakah Anda yakin ingin melanjutkan?"
                                          data-confirm-title="Konfirmasi Penghapusan Pengguna"
                                          data-confirm-ok="Ya, Lanjutkan Hapus"
                                          data-confirm-danger="true">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold text-rose-600 hover:text-rose-800 bg-rose-50 rounded">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500 text-xs">Belum ada pengguna terdaftar.</td>
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
