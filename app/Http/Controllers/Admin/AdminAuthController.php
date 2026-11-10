<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /**
     * Menampilkan halaman login khusus Admin / Owner Brownies Kelana.
     */
    public function showLogin(): View|RedirectResponse
    {
        // 1. Jika admin sudah login, arahkan langsung ke dashboard admin
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Memproses autentikasi masuk Admin menggunakan email atau username admin.
     */
    public function login(Request $request): RedirectResponse
    {
        // 1. Validasi input kredensial email/username dan password admin
        $validated = $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email atau username admin wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // 2. Cari akun pengguna dengan role admin berdasarkan email atau username alias
        $loginIdentifier = trim($validated['email']);
        $adminUser = User::query()
            ->where('role', 'admin')
            ->where(function ($query) use ($loginIdentifier): void {
                $query->where('email', $loginIdentifier)
                    ->orWhere('name', $loginIdentifier);

                if (strtolower($loginIdentifier) === 'admin.kelana') {
                    $query->orWhere('email', 'admin@kelana.test');
                }
            })
            ->first();

        // 3. Verifikasi kecocokan password dan status aktif akun admin
        if (! $adminUser || ! Hash::check($validated['password'], $adminUser->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email/username atau password salah. Periksa kembali kredensial Anda.']);
        }

        if (! $adminUser->is_active) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Akun admin sedang dinonaktifkan.');
        }

        // 4. Aktifkan sesi login admin dan regenerasi ID sesi
        Auth::login($adminUser, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Selamat datang di Panel Admin Brownies Kelana.');
    }

    /**
     * Mengakhiri sesi login Admin dan kembali ke halaman login admin.
     */
    public function logout(Request $request): RedirectResponse
    {
        // 1. Keluarkan sesi admin dan bersihkan token CSRF
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('success', 'Anda telah keluar dari Panel Admin.');
    }
}
