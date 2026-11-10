<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    /**
     * Menampilkan halaman pemberitahuan verifikasi email.
     */
    public function notice(Request $request): View|RedirectResponse
    {
        // 1. Jika email sudah terverifikasi, arahkan langsung ke dashboard
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-email', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Memproses klik tautan verifikasi email bertanda tangan.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        // 1. Tandai email pengguna terverifikasi apabila belum selesai
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->markEmailAsVerified();
        }

        return redirect()->route('verification.verified')
            ->with('success', 'Email kamu berhasil diverifikasi.');
    }

    /**
     * Menampilkan halaman konfirmasi bahwa email telah terverifikasi.
     */
    public function verified(Request $request): View
    {
        return view('auth.email-verified', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Mengirim ulang tautan verifikasi email ke pengguna.
     */
    public function resend(Request $request): RedirectResponse
    {
        // 1. Pastikan pengguna belum terverifikasi sebelum mengirim ulang email
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        // 2. Kirim notifikasi email verifikasi baru
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Tautan verifikasi baru telah dikirim ke '.$request->user()->email.'.');
    }
}
