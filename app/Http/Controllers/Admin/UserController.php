<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('assessor_registration_number', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        if ($request->ajax()) {
            return view('admin.users._table', compact('users'));
        }

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $schemes = Scheme::active()->orderBy('name')->get();

        return view('admin.users.create', compact('schemes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
            'role' => 'required|in:SUPER_ADMIN,ADMIN_LSP,ASESOR,ASESI,TUK_ADMIN',
            'status' => 'required|in:ACTIVE,INACTIVE,SUSPENDED',
            'assessor_registration_number' => 'nullable|string|max:50',
            'scheme_ids' => 'nullable|array',
            'scheme_ids.*' => 'exists:m_schemes,id',
        ], [
            'phone.regex' => 'Nomor telepon/WhatsApp hanya boleh berupa angka.',
        ]);

        $schemeIds = $validated['scheme_ids'] ?? [];
        unset($validated['scheme_ids']);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        if ($user->role === 'ASESOR' && ! empty($schemeIds)) {
            $user->schemes()->sync($schemeIds);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil dibuat.');
    }

    public function show(User $user): View
    {
        $user->load('profile');

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $schemes = Scheme::active()->orderBy('name')->get();
        $selectedSchemeIds = $user->schemes()->pluck('m_schemes.id')->toArray();

        return view('admin.users.edit', compact('user', 'schemes', 'selectedSchemeIds'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'password' => ['nullable', 'string', Password::min(8)->letters()->numbers()],
            'role' => 'required|in:SUPER_ADMIN,ADMIN_LSP,ASESOR,ASESI,TUK_ADMIN',
            'status' => 'required|in:ACTIVE,INACTIVE,SUSPENDED',
            'assessor_registration_number' => 'nullable|string|max:50',
            'scheme_ids' => 'nullable|array',
            'scheme_ids.*' => 'exists:m_schemes,id',
        ], [
            'phone.regex' => 'Nomor telepon/WhatsApp hanya boleh berupa angka.',
        ]);

        $schemeIds = $validated['scheme_ids'] ?? [];
        unset($validated['scheme_ids']);

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        if ($user->role === 'ASESOR') {
            $user->schemes()->sync($schemeIds);
        } else {
            $user->schemes()->detach();
        }

        return redirect()->route('admin.users.show', $user->id)
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->assessments()->exists() || $user->assessorAssessments()->exists() || $user->assignedSchedules()->exists()) {
            $user->update(['status' => 'INACTIVE']);

            return redirect()->route('admin.users.index')
                ->with('warning', 'Pengguna tidak dapat dihapus permanen karena memiliki riwayat asesmen atau jadwal. Status pengguna telah dinonaktifkan.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil dihapus.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        abort_unless(auth()->user()->isRole('SUPER_ADMIN'), 403);

        $defaultPassword = 'wisindo123';
        $user->update([
            'password' => Hash::make($defaultPassword),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Password untuk pengguna '{$user->full_name}' ({$user->email}) berhasil direset menjadi '{$defaultPassword}'.");
    }
}
