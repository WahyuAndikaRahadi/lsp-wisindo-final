<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show the admin profile & digital signature settings page.
     */
    public function edit(): View
    {
        $user = auth()->user();
        $profile = $user->profile;

        return view('admin.profile', compact('user', 'profile'));
    }

    /**
     * Update admin profile, password, and signature.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'nik' => ['nullable', 'string', 'max:16', 'regex:/^[0-9]+$/'],
            'jabatan' => 'nullable|string|max:100',
            'nama_institusi_perusahaan' => 'nullable|string|max:255',
            'signature_data' => 'nullable|string',
            'current_password' => 'nullable|required_with:new_password|current_password',
            'new_password' => ['nullable', 'confirmed', Password::min(8)],
        ], [
            'email.unique' => 'Alamat email ini sudah terdaftar pada pengguna lain.',
            'phone.regex' => 'Nomor telepon/WhatsApp hanya boleh berupa deretan angka.',
            'nik.regex' => 'NIK hanya boleh berupa deretan angka tanpa spasi.',
            'current_password.current_password' => 'Password saat ini yang Anda masukkan tidak sesuai.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'new_password.min' => 'Password baru minimal harus terdiri dari 8 karakter.',
        ]);

        $userData = [
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $user->phone,
        ];

        if (! empty($validated['new_password'])) {
            $userData['password'] = Hash::make($validated['new_password']);
        }

        $user->update($userData);

        UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nik' => $validated['nik'] ?? null,
                'jabatan' => $validated['jabatan'] ?? 'Admin LSP',
                'nama_institusi_perusahaan' => $validated['nama_institusi_perusahaan'] ?? 'LSP Praktisi Otomotif Indonesia',
                'signature_data' => $validated['signature_data'] ?? $user->profile?->signature_data,
            ]
        );

        return redirect()->route('admin.profile.edit')
            ->with('success', 'Pengaturan profil, password, dan tanda tangan digital Admin berhasil disimpan.');
    }
}
