<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scheme;
use App\Models\Tuk;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TukController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tuk::with(['admins', 'schemes', 'schedules']);

        if ($request->filled('status')) {
            $query->where('verification_status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('pic_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $tuks = $query->latest()->paginate(15)->withQueryString();

        return view('admin.tuks.index', compact('tuks'));
    }

    public function create(): View
    {
        $schemes = Scheme::active()->orderBy('name')->get();

        return view('admin.tuks.create', compact('schemes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:100|unique:users,email',
            'admin_password' => 'required|string|min:6',
            'admin_full_name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:50|unique:m_tuks,code',
            'type' => 'nullable|in:SEWAKTU,TEMPAT_KERJA,MANDIRI',
            'address' => 'nullable|string|max:500',
            'kode_provinsi' => 'nullable|string|max:10',
            'nama_provinsi' => 'nullable|string|max:100',
            'kode_kota' => 'nullable|string|max:10',
            'nama_kota' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'pic_name' => 'nullable|string|max:255',
            'pic_phone' => 'nullable|string|max:30',
            'pic_position' => 'nullable|string|max:100',
            'scheme_ids' => 'nullable|array',
            'scheme_ids.*' => 'exists:m_schemes,id',
        ], [
            'name.required' => 'Nama TUK wajib diisi.',
            'admin_email.required' => 'Email login untuk Admin TUK wajib diisi.',
            'admin_email.email' => 'Format email admin TUK tidak valid.',
            'admin_email.unique' => 'Email admin TUK sudah terdaftar.',
            'admin_password.required' => 'Kata sandi login admin TUK wajib diisi.',
            'admin_password.min' => 'Kata sandi login admin TUK minimal 6 karakter.',
        ]);

        DB::transaction(function () use ($validated, &$tuk) {
            $code = ! empty($validated['code'])
                ? strtoupper(trim($validated['code']))
                : ('TUK-WIS-'.strtoupper(substr(md5(uniqid()), 0, 4)));

            $tuk = Tuk::create([
                'name' => $validated['name'],
                'code' => $code,
                'type' => $validated['type'] ?? 'SEWAKTU',
                'address' => $validated['address'] ?? null,
                'kode_provinsi' => $validated['kode_provinsi'] ?? null,
                'nama_provinsi' => $validated['nama_provinsi'] ?? null,
                'kode_kota' => $validated['kode_kota'] ?? null,
                'nama_kota' => $validated['nama_kota'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'pic_name' => $validated['pic_name'] ?? null,
                'pic_phone' => $validated['pic_phone'] ?? null,
                'pic_position' => $validated['pic_position'] ?? null,
                'verification_status' => 'UNSUBMITTED',
                'verified_at' => null,
                'verified_by' => null,
                'is_active' => true,
            ]);

            if (! empty($validated['scheme_ids'])) {
                $sync = [];
                foreach ($validated['scheme_ids'] as $sid) {
                    $sync[$sid] = ['is_verified' => false];
                }
                $tuk->schemes()->sync($sync);
            }

            $adminFullName = ($validated['admin_full_name'] ?? null) ?: (($validated['pic_name'] ?? null) ?: 'Admin '.$tuk->name);

            User::create([
                'full_name' => $adminFullName,
                'email' => strtolower(trim($validated['admin_email'])),
                'phone' => $validated['pic_phone'] ?? ($validated['phone'] ?? null),
                'password' => Hash::make($validated['admin_password']),
                'role' => 'TUK_ADMIN',
                'status' => 'ACTIVE',
                'is_active' => true,
                'tuk_id' => $tuk->id,
            ]);
        });

        return redirect()->route('admin.tuks.index')
            ->with('success', "Tempat Uji Kompetensi {$tuk->name} dan akun login Admin TUK ({$validated['admin_email']}) berhasil dibuat. Pihak TUK dapat login untuk melengkapi data dan berkas verifikasi AJJ.");
    }

    public function edit(Tuk $tuk): View
    {
        $schemes = Scheme::active()->orderBy('name')->get();
        $selectedSchemeIds = $tuk->schemes()->pluck('m_schemes.id')->toArray();
        $adminUser = $tuk->admins()->first();

        return view('admin.tuks.edit', compact('tuk', 'schemes', 'selectedSchemeIds', 'adminUser'));
    }

    public function update(Request $request, Tuk $tuk): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:50', Rule::unique('m_tuks')->ignore($tuk->id)],
            'type' => 'nullable|in:SEWAKTU,TEMPAT_KERJA,MANDIRI',
            'address' => 'nullable|string|max:500',
            'kode_provinsi' => 'nullable|string|max:10',
            'nama_provinsi' => 'nullable|string|max:100',
            'kode_kota' => 'nullable|string|max:10',
            'nama_kota' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'pic_name' => 'nullable|string|max:255',
            'pic_phone' => 'nullable|string|max:30',
            'pic_position' => 'nullable|string|max:100',
            'verification_status' => 'required|in:UNSUBMITTED,PENDING_VERIFICATION,VERIFIED,REVISION',
            'scheme_ids' => 'nullable|array',
            'scheme_ids.*' => 'exists:m_schemes,id',
            'admin_full_name' => 'nullable|string|max:255',
            'admin_email' => 'nullable|email',
            'admin_password' => 'nullable|string|min:6',
        ]);

        DB::transaction(function () use ($validated, $tuk) {
            $tuk->update([
                'name' => $validated['name'],
                'code' => $validated['code'] ?: $tuk->code,
                'type' => $validated['type'] ?? $tuk->type,
                'address' => $validated['address'] ?? $tuk->address,
                'kode_provinsi' => $validated['kode_provinsi'] ?? $tuk->kode_provinsi,
                'nama_provinsi' => $validated['nama_provinsi'] ?? $tuk->nama_provinsi,
                'kode_kota' => $validated['kode_kota'] ?? $tuk->kode_kota,
                'nama_kota' => $validated['nama_kota'] ?? $tuk->nama_kota,
                'postal_code' => $validated['postal_code'] ?? $tuk->postal_code,
                'phone' => $validated['phone'] ?? $tuk->phone,
                'email' => $validated['email'] ?? $tuk->email,
                'pic_name' => $validated['pic_name'] ?? $tuk->pic_name,
                'pic_phone' => $validated['pic_phone'] ?? $tuk->pic_phone,
                'pic_position' => $validated['pic_position'] ?? $tuk->pic_position,
                'verification_status' => $validated['verification_status'],
            ]);

            // Sync schemes
            $sync = [];
            foreach ($validated['scheme_ids'] ?? [] as $sid) {
                $sync[$sid] = ['is_verified' => true];
            }
            $tuk->schemes()->sync($sync);

            // Create or update Admin TUK user
            if (! empty($validated['admin_email'])) {
                $admin = $tuk->admins()->first();
                if ($admin) {
                    $adminData = [
                        'full_name' => $validated['admin_full_name'] ?: $admin->full_name,
                        'email' => $validated['admin_email'],
                    ];
                    if (! empty($validated['admin_password'])) {
                        $adminData['password'] = Hash::make($validated['admin_password']);
                    }
                    $admin->update($adminData);
                } else {
                    User::create([
                        'full_name' => $validated['admin_full_name'] ?: 'Admin '.$tuk->name,
                        'email' => $validated['admin_email'],
                        'phone' => $validated['pic_phone'] ?: $validated['phone'],
                        'password' => Hash::make($validated['admin_password'] ?? '@Kompeten86'),
                        'role' => 'TUK_ADMIN',
                        'status' => 'ACTIVE',
                        'tuk_id' => $tuk->id,
                    ]);
                }
            }
        });

        return redirect()->route('admin.tuks.index')
            ->with('success', "Data TUK {$tuk->name} berhasil diperbarui.");
    }

    public function review(Tuk $tuk): View
    {
        $tuk->load(['admins', 'schemes']);
        $allSchemes = Scheme::active()->orderBy('name')->get();
        $tukSchemeIds = $tuk->schemes()->pluck('m_schemes.id')->toArray();

        return view('admin.tuks.review', compact('tuk', 'allSchemes', 'tukSchemeIds'));
    }

    public function verify(Request $request, Tuk $tuk): RedirectResponse
    {
        $validated = $request->validate([
            'scheme_ids' => 'required|array|min:1',
            'scheme_ids.*' => 'exists:m_schemes,id',
            'admin_notes' => 'nullable|string|max:1000',
        ], [
            'scheme_ids.required' => 'Pilih minimal satu skema yang disetujui untuk TUK ini.',
        ]);

        DB::transaction(function () use ($validated, $tuk) {
            $sync = [];
            foreach ($validated['scheme_ids'] as $sid) {
                $sync[$sid] = ['is_verified' => true];
            }
            $tuk->schemes()->sync($sync);

            $tuk->update([
                'verification_status' => 'VERIFIED',
                'verification_notes' => $validated['admin_notes'] ?? 'Telah diverifikasi dan memenuhi standar Asesmen Jarak Jauh (AJJ).',
                'verified_at' => now(),
                'verified_by' => auth()->id(),
            ]);
        });

        return redirect()->route('admin.tuks.index')
            ->with('success', "TUK {$tuk->name} berhasil diverifikasi dan disahkan untuk Asesmen Jarak Jauh.");
    }

    public function rejectRevision(Request $request, Tuk $tuk): RedirectResponse
    {
        $validated = $request->validate([
            'verification_notes' => 'required|string|min:5|max:1000',
        ], [
            'verification_notes.required' => 'Catatan revisi wajib diisi agar Admin TUK mengetahui hal yang perlu diperbaiki.',
        ]);

        $tuk->update([
            'verification_status' => 'REVISION',
            'verification_notes' => $validated['verification_notes'],
        ]);

        return redirect()->route('admin.tuks.index')
            ->with('warning', "Status TUK {$tuk->name} diubah menjadi 'Perlu Revisi'. Catatan telah dikirim ke TUK.");
    }

    public function destroy(Tuk $tuk): RedirectResponse
    {
        if ($tuk->schedules()->exists()) {
            return redirect()->route('admin.tuks.index')
                ->with('error', "TUK {$tuk->name} tidak dapat dihapus karena telah memiliki jadwal asesmen.");
        }

        $tuk->admins()->delete();
        $tuk->schemes()->detach();
        $tuk->delete();

        return redirect()->route('admin.tuks.index')
            ->with('success', 'TUK berhasil dihapus.');
    }
}
