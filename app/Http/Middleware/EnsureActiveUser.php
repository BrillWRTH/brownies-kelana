<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    /**
     * Memastikan akun pelanggan yang sedang login tidak dalam status nonaktif.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Keluarkan paksa apabila akun pengguna telah dinonaktifkan oleh admin
        if ($request->user() && ! $request->user()->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Akun Anda sedang dinonaktifkan. Hubungi CS WhatsApp Brownies Kelana untuk bantuan.');
        }

        return $next($request);
    }
}
