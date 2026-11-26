<?php

namespace App\Http\Controllers\Tuk;

use App\Http\Controllers\Controller;
use App\Models\Scheme;
use App\Models\Tuk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DataTukController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $tuk = $user->tuk;

        if (! $tuk) {
            $tuk = Tuk::create([
                'name' => 'TUK '.$user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'pic_name' => $user->full_name,
                'pic_phone' => $user->phone,
                'is_active' => true,
                'verification_status' => 'UNSUBMITTED',
            ]);
            $user->update(['tuk_id' => $tuk->id]);
        }

        $schemes = Scheme::active()->orderBy('name')->get();
        $selectedSchemeIds = $tuk->schemes()->pluck('m_schemes.id')->toArray();

        return view('tuk.data.edit', compact('tuk', 'schemes', 'selectedSchemeIds'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tuk = $user->tuk;

        if (! $tuk) {
            return redirect()->route('tuk.data.edit')->with('error', 'Data TUK tidak ditemukan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:SEWAKTU,TEMPAT_KERJA,MANDIRI',
            'address' => 'required|string|max:500',
            'kode_provinsi' => 'nullable|string|max:10',
            'nama_provinsi' => 'nullable|string|max:100',
            'kode_kota' => 'nullable|string|max:10',
            'nama_kota' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:100',
            'pic_name' => 'required|string|max:255',
            'pic_phone' => 'required|string|max:30',
            'pic_position' => 'required|string|max:100',
            'scheme_ids' => 'required|array|min:1',
            'scheme_ids.*' => 'exists:m_schemes,id',
            'room_photos.*' => 'nullable|image|max:5120',
            'internet_data_photos.*' => 'nullable|image|max:5120',
            'internet_connection_photos.*' => 'nullable|image|max:5120',
            'equipment_photos.*' => 'nullable|image|max:5120',
            'room_video' => 'nullable|file|mimes:mp4,webm,mov,quicktime,mkv|max:51200',
        ], [
            'name.required' => 'Nama TUK wajib diisi.',
            'address.required' => 'Alamat lengkap TUK wajib diisi.',
            'phone.required' => 'Nomor telepon TUK wajib diisi.',
            'pic_name.required' => 'Nama penanggung jawab (PIC) wajib diisi.',
            'pic_phone.required' => 'Nomor HP/WA penanggung jawab (PIC) wajib diisi.',
            'pic_position.required' => 'Jabatan penanggung jawab (PIC) wajib diisi.',
            'scheme_ids.required' => 'Pilih minimal satu skema sertifikasi yang diajukan oleh TUK.',
            'scheme_ids.min' => 'Pilih minimal satu skema sertifikasi yang diajukan oleh TUK.',
            'room_video.max' => 'Ukuran berkas video ruangan maksimal adalah 50 MB.',
        ]);

        // Process file uploads
        $roomPhotos = $this->handleMultipleUploads($request, 'room_photos', $tuk->room_photos ?? []);
        $internetDataPhotos = $this->handleMultipleUploads($request, 'internet_data_photos', $tuk->internet_data_photos ?? []);
        $internetConnPhotos = $this->handleMultipleUploads($request, 'internet_connection_photos', $tuk->internet_connection_photos ?? []);
        $equipmentPhotos = $this->handleMultipleUploads($request, 'equipment_photos', $tuk->equipment_photos ?? []);

        $roomVideoPath = $tuk->room_video_path;
        if ($request->boolean('delete_room_video')) {
            if ($roomVideoPath && Storage::disk('public')->exists($roomVideoPath)) {
                Storage::disk('public')->delete($roomVideoPath);
            }
            $roomVideoPath = null;
        }
        if ($request->hasFile('room_video')) {
            if ($roomVideoPath && Storage::disk('public')->exists($roomVideoPath)) {
                Storage::disk('public')->delete($roomVideoPath);
            }
            $roomVideoPath = $request->file('room_video')->store('tuk_evidence/videos', 'public');
        }

        $tuk->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'address' => $validated['address'],
            'kode_provinsi' => $validated['kode_provinsi'] ?? null,
            'nama_provinsi' => $validated['nama_provinsi'] ?? null,
            'kode_kota' => $validated['kode_kota'] ?? null,
            'nama_kota' => $validated['nama_kota'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'pic_name' => $validated['pic_name'],
            'pic_phone' => $validated['pic_phone'],
            'pic_position' => $validated['pic_position'],
            'room_photos' => $roomPhotos,
            'internet_data_photos' => $internetDataPhotos,
            'internet_connection_photos' => $internetConnPhotos,
            'equipment_photos' => $equipmentPhotos,
            'room_video_path' => $roomVideoPath,
            'verification_status' => 'PENDING_VERIFICATION',
        ]);

        // Sync schemes (preserve is_verified if already verified by admin, else default true for submission)
        $existingPivot = $tuk->schemes()->pluck('is_verified', 'm_schemes.id')->toArray();
        $syncData = [];
        foreach ($validated['scheme_ids'] as $schemeId) {
            $syncData[$schemeId] = [
                'is_verified' => $existingPivot[$schemeId] ?? true,
            ];
        }
        $tuk->schemes()->sync($syncData);

        return redirect()->route('tuk.data.edit')
            ->with('success', 'Data TUK dan berkas verifikasi AJJ berhasil diperbarui dan dikirim ke Admin LSP untuk diverifikasi.');
    }

    /**
     * Helper to process multiple image uploads up to 3 files.
     * Supports removing previously uploaded files and appending new uploads.
     */
    private function handleMultipleUploads(Request $request, string $field, array $existing): array
    {
        // 1. Surviving existing files specified by user
        $surviving = $request->input('existing_'.$field, []);
        if (! is_array($surviving)) {
            $surviving = [];
        }

        // Delete existing files that were removed by the user
        foreach ($existing as $oldPath) {
            if (! in_array($oldPath, $surviving, true)) {
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

        $current = array_values(array_intersect($existing, $surviving));

        // 2. Append newly uploaded files up to max 3
        if ($request->hasFile($field)) {
            $files = $request->file($field);
            if (! is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {
                if (count($current) >= 3) {
                    break;
                }
                if ($file->isValid()) {
                    $path = $file->store('tuk_evidence/photos', 'public');
                    $current[] = $path;
                }
            }
        }

        return array_slice($current, 0, 3);
    }
}
