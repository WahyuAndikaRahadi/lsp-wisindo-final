<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProxyController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::where('role', '!=', 'SUPER_ADMIN');

        if ($request->filled('role') && $request->input('role') !== 'ALL') {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('role')
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.proxy.index', compact('users'));
    }

    public function impersonate(User $user): RedirectResponse
    {
        // Prevent privilege escalation to SUPER_ADMIN
        if ($user->role === 'SUPER_ADMIN') {
            abort(403, 'Tidak diizinkan melakukan impersonasi ke akun Super Admin.');
        }

        // Prevent self-impersonation
        if ($user->id === auth()->id()) {
            return back()->with('warning', 'Tidak dapat melakukan impersonasi ke akun sendiri.');
        }

        // Prevent nested/chained impersonation
        if (session()->has('admin_impersonator_id')) {
            return back()->with('error', 'Selesaikan sesi impersonasi aktif terlebih dahulu.');
        }

        session(['admin_impersonator_id' => auth()->id()]);

        Auth::login($user);

        return match ($user->role) {
            'ASESOR' => redirect()->route('asesor.jadwal.index'),
            'ASESI' => redirect()->route('asesi.dashboard'),
            default => redirect()->route('admin.dashboard'),
        };
    }
}
