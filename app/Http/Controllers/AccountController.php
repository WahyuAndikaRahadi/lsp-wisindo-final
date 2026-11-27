<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * Show account settings (login email and password management).
     */
    public function edit(): View|RedirectResponse
    {
        $user = auth()->user();

        // Admin & Super Admin manage account credentials via admin.profile.edit
        if ($user->isAdmin()) {
            return redirect()->route('admin.profile.edit');
        }

        return view('account.edit', compact('user'));
    }

    /**
     * Update login email and/or password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.profile.edit');
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'current_password' => 'nullable|required_with:new_password|current_password',
            'new_password' => ['nullable', 'confirmed', Password::min(8)],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email ini sudah digunakan oleh akun lain.',
            'current_password.required_with' => 'Password saat ini wajib diisi untuk mengubah password baru.',
            'current_password.current_password' => 'Password saat ini yang Anda masukkan salah.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'new_password.min' => 'Password baru minimal harus terdiri dari 8 karakter.',
        ]);

        $userData = [
            'email' => $validated['email'],
        ];

        if (! empty($validated['new_password'])) {
            $userData['password'] = Hash::make($validated['new_password']);
        }

        $user->update($userData);

        return redirect()->route('account.edit')->with('success', 'Pengaturan akun (email & password) berhasil diperbarui.');
    }
}
