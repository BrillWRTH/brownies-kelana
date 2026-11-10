<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Memastikan pengguna yang mengakses rute admin adalah admin aktif.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Periksa apakah pengguna sudah login dan memiliki role admin
        if (! $request->user() || ! $request->user()->isAdmin()) {
            return redirect()->route('admin.login')
                ->with('error', 'Silakan masuk menggunakan akun Admin Brownies Kelana.');
        }

        // 2. Pastikan akun admin dalam kondisi aktif
        if (! $request->user()->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->with('error', 'Akun admin Anda sedang dinonaktifkan.');
        }

        return $next($request);
    }
}
