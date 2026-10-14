<?php

namespace App\Http\Controllers\Asesi;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $profile = auth()->user()->profile;

        return view('asesi.profile', compact('profile'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nik' => 'nullable|string|max:20',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|in:L,P',
            'kebangsaan' => 'nullable|string|max:50',
            'alamat_rumah' => 'nullable|string',
            'kode_pos' => 'nullable|string|max:10',
            'no_telepon_rumah' => 'nullable|string|max:30',
            'no_telepon_kantor' => 'nullable|string|max:30',
            'pendidikan_terakhir' => 'nullable|string|max:50',
            'nama_institusi_perusahaan' => 'nullable|string|max:255',
            'jabatan' => 'nullable|string|max:100',
            'alamat_kantor' => 'nullable|string',
            'signature_data' => 'nullable|string',
        ]);

        UserProfile::updateOrCreate(
            ['user_id' => auth()->id()],
            $validated
        );

        return redirect()->route('asesi.profile.edit')->with('success', 'Data diri berhasil disimpan.');
    }
}
