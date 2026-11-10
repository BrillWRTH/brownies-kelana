<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * Menampilkan halaman permintaan lupa kata sandi.
     */
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Mengirim tautan reset kata sandi ke email terdaftar.
     */
    public function sendLink(Request $request): RedirectResponse
    {
        // 1. Validasi alamat email yang dikirimkan
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        // 2. Kirim tautan reset password menggunakan broker Laravel
        $status = Password::sendResetLink($validated);

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withErrors(['email' => __($status)]);
        }

        return redirect()->route('password.sent')
            ->with('reset_email', $validated['email']);
    }

    /**
     * Menampilkan halaman konfirmasi bahwa email reset kata sandi telah terkirim.
     */
    public function sentNotice(): View
    {
        return view('auth.email-sent', [
            'email' => session('reset_email', 'email kamu'),
        ]);
    }

    /**
     * Menampilkan formulir atur ulang kata sandi baru berdasarkan token.
     */
    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Memproses penyimpanan kata sandi baru pengguna.
     */
    public function reset(Request $request): RedirectResponse
    {
        // 1. Validasi token, email, dan kata sandi baru minimal 8 karakter
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        // 2. Jalankan pembaruan kata sandi melalui password broker
        $status = Password::reset($validated, function ($user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->setRememberToken(Str::random(60));

            $user->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        return redirect()->route('password.success')
            ->with('reset_email', $validated['email']);
    }

    /**
     * Menampilkan halaman sukses setelah kata sandi diperbarui.
     */
    public function successNotice(): View
    {
        return view('auth.reset-success', [
            'email' => session('reset_email', 'akun Anda'),
        ]);
    }
}
