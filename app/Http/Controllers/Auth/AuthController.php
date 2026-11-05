<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->status !== 'ACTIVE') {
                Auth::logout();

                return back()->withErrors(['email' => 'Akun dinonaktifkan atau disuspend.']);
            }

            return $this->redirectBasedOnRole($user);
        }

        return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'phone.regex' => 'Nomor WhatsApp / telepon hanya boleh berupa angka.',
        ]);

        $user = User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'ASESI',
            'status' => 'ACTIVE',
        ]);

        return redirect()->route('login')->with('success', 'Anda sudah mendaftar, silahkan login.');
    }

    public function logout(Request $request)
    {
        if ($request->session()->has('admin_impersonator_id')) {
            return redirect()->back()->with('warning', 'Anda sedang dalam mode proxy. Silakan gunakan tombol Kembali ke sesi Admin terlebih dahulu.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function proxySwitchBack(Request $request)
    {
        if ($originalAdminId = session('admin_impersonator_id')) {
            session()->forget('admin_impersonator_id');
            $admin = User::find($originalAdminId);
            if ($admin && in_array($admin->role, ['SUPER_ADMIN', 'ADMIN_LSP'], true)) {
                $request->session()->regenerate();
                Auth::login($admin);

                return redirect()->route('admin.proxy.index')->with('success', 'Kembali ke sesi Admin.');
            }
        }

        return redirect()->route('login');
    }

    private function redirectBasedOnRole(User $user)
    {
        return match ($user->role) {
            'SUPER_ADMIN', 'ADMIN_LSP' => redirect()->route('admin.dashboard'),
            'ASESOR' => redirect()->route('asesor.jadwal.index'),
            'ASESI' => redirect()->route('asesi.dashboard'),
            'KOMITE_TEKNIS' => redirect()->route('admin.dashboard'),
            'TUK_ADMIN' => redirect()->route('admin.dashboard'),
            default => redirect()->route('login'),
        };
    }
}
