<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureActiveUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 1. Kecualikan webhook Midtrans dari pemeriksaan CSRF token
        $middleware->validateCsrfTokens(except: [
            'midtrans/callback',
        ]);

        // 2. Daftarkan alias middleware role admin dan status akun aktif
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'active' => EnsureActiveUser::class,
        ]);

        // 3. Arahkan tamu yang belum login ke halaman login Admin atau Pelanggan sesuai prefix URL
        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
