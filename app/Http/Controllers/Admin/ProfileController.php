<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        $isSuperAdmin = $user->role === 'SUPER_ADMIN';
        $superAdmin = $isSuperAdmin ? $user : User::where('role', 'SUPER_ADMIN')->first();

        // Effective master signature (always managed by Super Admin)
        $masterSignature = $superAdmin?->profile?->signature_data;
        $profile = $user->profile;

        // Ensure Admin LSP profile is synced with Super Admin master signature if available
        if (! $isSuperAdmin && $masterSignature && ($profile?->signature_data !== $masterSignature)) {
            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                ['signature_data' => $masterSignature]
            );
            $profile = $user->fresh()->profile;
        }

        $effectiveSignature = $isSuperAdmin ? ($profile?->signature_data) : ($masterSignature ?? $profile?->signature_data);

        return view('admin.profile', compact('user', 'profile', 'isSuperAdmin', 'superAdmin', 'masterSignature', 'effectiveSignature'));
    }

    /**
     * Update admin profile, password, and signature.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $isSuperAdmin = $user->role === 'SUPER_ADMIN';

        $rules = [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'nik' => ['nullable', 'string', 'max:16', 'regex:/^[0-9]+$/'],
            'jabatan' => 'nullable|string|max:100',
            'nama_institusi_perusahaan' => 'nullable|string|max:255',
            'current_password' => 'nullable|required_with:new_password|current_password',
            'new_password' => ['nullable', 'confirmed', Password::min(8)],
        ];

        if ($isSuperAdmin) {
            $rules['signature_data'] = 'nullable|string';
        }

        $validated = $request->validate($rules, [
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

        if ($isSuperAdmin) {
            $signatureData = $validated['signature_data'] ?? $user->profile?->signature_data;

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nik' => $validated['nik'] ?? null,
                    'jabatan' => $validated['jabatan'] ?? 'Super Admin LSP',
                    'nama_institusi_perusahaan' => $validated['nama_institusi_perusahaan'] ?? 'LSP P3 Wisata Indonesia',
                    'signature_data' => $signatureData,
                ]
            );

            // Automatically sync Super Admin's signature to all Admin LSP profiles
            if (! empty($signatureData)) {
                $adminIds = User::where('role', 'ADMIN_LSP')->pluck('id');
                foreach ($adminIds as $adminId) {
                    UserProfile::updateOrCreate(
                        ['user_id' => $adminId],
                        ['signature_data' => $signatureData]
                    );
                }
            }
        } else {
            // Admin LSP: preserve master signature from Super Admin
            $superAdmin = User::where('role', 'SUPER_ADMIN')->first();
            $masterSignature = $superAdmin?->profile?->signature_data ?? $user->profile?->signature_data;

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nik' => $validated['nik'] ?? null,
                    'jabatan' => $validated['jabatan'] ?? 'Admin LSP',
                    'nama_institusi_perusahaan' => $validated['nama_institusi_perusahaan'] ?? 'LSP P3 Wisata Indonesia',
                    'signature_data' => $masterSignature,
                ]
            );
        }

        $message = $isSuperAdmin
            ? 'Pengaturan profil, password, dan master tanda tangan digital lembaga berhasil disimpan.'
            : 'Pengaturan profil dan kata sandi Admin berhasil disimpan.';

        return redirect()->route('admin.profile.edit')
            ->with('success', $message);
    }
}
