<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Menampilkan halaman login pelanggan.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Memproses autentikasi login pelanggan.
     */
    public function login(Request $request): RedirectResponse
    {
        // 1. Validasi data email dan kata sandi dari form
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $guestSessionId = $request->session()->getId();

        // 2. Cocokkan kredensial pengguna
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Email atau kata sandi salah.']);
        }

        // 3. Periksa status aktif akun sebelum melanjutkan sesi
        $loggedInUser = Auth::user();
        if (! $loggedInUser->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->with('error', 'Akun Anda sedang dinonaktifkan. Hubungi CS WhatsApp untuk bantuan.');
        }

        // 4. Gabungkan keranjang belanja tamu ke keranjang akun pelanggan
        $this->mergeGuestCart($guestSessionId, $loggedInUser);
        $request->session()->regenerate();

        // 5. Arahkan sesuai role pengguna
        if ($loggedInUser->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Selamat datang kembali, Admin.');
        }

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Selamat datang kembali, '.$loggedInUser->name.'!');
    }

    /**
     * Menampilkan halaman pendaftaran akun pelanggan baru.
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Memproses pendaftaran akun pelanggan baru.
     */
    public function register(Request $request): RedirectResponse
    {
        // 1. Validasi data pendaftaran pelanggan baru
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:25'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'agree_terms' => ['accepted'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'phone.required' => 'Nomor HP/WhatsApp wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'agree_terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan.',
        ]);

        $guestSessionId = $request->session()->getId();

        // 2. Hasilkan kode pelanggan unik dengan format CUS-XXXX
        $latestNumericCode = (int) User::query()
            ->whereNotNull('customer_code')
            ->get(['customer_code'])
            ->map(function (User $user): int {
                preg_match('/(\d+)$/', (string) $user->customer_code, $matches);

                return isset($matches[1]) ? (int) $matches[1] : 0;
            })
            ->max();
        $nextNumber = max(47, $latestNumericCode + 1);
        $customerCode = 'CUS-'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);

        // 3. Simpan pengguna baru sebagai pelanggan aktif
        $newUser = User::create([
            'customer_code' => $customerCode,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role' => 'customer',
            'is_active' => true,
            'password' => Hash::make($validated['password']),
        ]);

        // 4. Kirim event verifikasi email dan aktifkan sesi login
        event(new Registered($newUser));
        Auth::login($newUser);

        // 5. Pindahkan item keranjang tamu ke akun baru
        $this->mergeGuestCart($guestSessionId, $newUser);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')
            ->with('success', 'Pendaftaran berhasil! Tautan verifikasi telah dikirim ke email Anda.');
    }

    /**
     * Mengakhiri sesi login pengguna.
     */
    public function logout(Request $request): RedirectResponse
    {
        // 1. Hapus autentikasi pengguna dan reset token sesi
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('success', 'Anda telah keluar dari akun Brownies Kelana.');
    }

    /**
     * Memindahkan item keranjang tamu ke keranjang akun pengguna yang baru login.
     */
    protected function mergeGuestCart(string $guestSessionId, User $user): void
    {
        $guestCart = Cart::query()
            ->with('items')
            ->where('session_id', $guestSessionId)
            ->whereNull('user_id')
            ->first();

        if (! $guestCart) {
            return;
        }

        $userCart = Cart::query()->firstOrCreate([
            'user_id' => $user->id,
        ]);

        foreach ($guestCart->items as $guestItem) {
            $existingItem = CartItem::query()
                ->where('cart_id', $userCart->id)
                ->where('product_id', $guestItem->product_id)
                ->where('product_variant_id', $guestItem->product_variant_id)
                ->where('custom_note', $guestItem->custom_note)
                ->first();

            if ($existingItem) {
                $existingItem->update([
                    'quantity' => min(50, $existingItem->quantity + $guestItem->quantity),
                ]);
                $guestItem->delete();
            } else {
                $guestItem->update([
                    'cart_id' => $userCart->id,
                ]);
            }
        }

        if ($guestCart->order_note && ! $userCart->order_note) {
            $userCart->update(['order_note' => $guestCart->order_note]);
        }

        $guestCart->delete();
    }
}
