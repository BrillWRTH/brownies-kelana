<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\StoreSetting;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    /**
     * Menampilkan dashboard utama pelanggan beserta pesanan aktif dan riwayat transaksi.
     */
    public function index(Request $request): View
    {
        // 1. Ambil akun pelanggan dan periksa batas pembayaran pesanan aktif yang tertunda
        $user = Auth::user();
        $pendingOrders = Order::query()
            ->where('user_id', $user->id)
            ->where('order_status', 'menunggu_pembayaran')
            ->get();

        foreach ($pendingOrders as $pendingOrder) {
            $pendingOrder->expireIfPastDeadline();
        }

        // 2. Ambil 1 pesanan paling aktif (belum selesai/batal/kedaluwarsa) untuk kartu utama
        $activeOrder = Order::query()
            ->with(['items.product', 'pickupLocation', 'latestPayment'])
            ->where('user_id', $user->id)
            ->whereNotIn('order_status', ['sampai_tujuan', 'sudah_diambil', 'dibatalkan', 'kedaluwarsa'])
            ->latest()
            ->first();

        // 3. Filter daftar riwayat pesanan berdasarkan tab status yang dipilih pelanggan
        $statusFilter = trim((string) $request->query('status', 'semua'));
        $ordersQuery = Order::query()
            ->with(['items.product'])
            ->where('user_id', $user->id)
            ->latest();

        if ($statusFilter === 'menunggu_pembayaran') {
            $ordersQuery->where('order_status', 'menunggu_pembayaran');
        } elseif ($statusFilter === 'diproses') {
            $ordersQuery->whereIn('order_status', ['diproses', 'dikemas']);
        } elseif ($statusFilter === 'dikirim') {
            $ordersQuery->whereIn('order_status', ['dalam_pengiriman', 'siap_diambil']);
        } elseif ($statusFilter === 'selesai') {
            $ordersQuery->whereIn('order_status', ['sampai_tujuan', 'sudah_diambil']);
        } elseif ($statusFilter === 'dibatalkan') {
            $ordersQuery->where('order_status', 'dibatalkan');
        } elseif ($statusFilter === 'kedaluwarsa') {
            $ordersQuery->where('order_status', 'kedaluwarsa');
        }

        $orders = $ordersQuery->paginate(8)->withQueryString();
        $storeSetting = StoreSetting::current();

        return view('customer.dashboard', compact(
            'user',
            'activeOrder',
            'orders',
            'statusFilter',
            'storeSetting'
        ));
    }

    /**
     * Menampilkan halaman daftar notifikasi pelanggan dengan filter Semua, Pesanan, dan Sistem.
     */
    public function notifications(Request $request): View
    {
        // 1. Ambil filter kategori notifikasi yang aktif
        $user = Auth::user();
        $categoryFilter = trim((string) $request->query('kategori', 'semua'));

        $baseQuery = UserNotification::query()
            ->where('audience', 'customer')
            ->where('user_id', $user->id);

        $counts = [
            'all' => (clone $baseQuery)->count(),
            'pesanan' => (clone $baseQuery)->where('category', 'pesanan')->count(),
            'sistem' => (clone $baseQuery)->where('category', 'sistem')->count(),
        ];

        $notificationsQuery = (clone $baseQuery)->latest();
        if (in_array($categoryFilter, ['pesanan', 'sistem'], true)) {
            $notificationsQuery->where('category', $categoryFilter);
        }

        $notifications = $notificationsQuery->paginate(12)->withQueryString();
        $storeSetting = StoreSetting::current();

        return view('customer.notifications', compact(
            'user',
            'notifications',
            'categoryFilter',
            'counts',
            'storeSetting'
        ));
    }

    /**
     * Menandai seluruh notifikasi pelanggan sebagai sudah dibaca.
     */
    public function markAllNotificationsRead(): RedirectResponse
    {
        UserNotification::query()
            ->where('audience', 'customer')
            ->where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    /**
     * Menandai satu notifikasi sebagai sudah dibaca lalu mengarahkan ke tautan terkait.
     */
    public function markNotificationRead(UserNotification $notification): RedirectResponse
    {
        if ($notification->user_id !== Auth::id() || $notification->audience !== 'customer') {
            abort(403);
        }

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        if ($notification->action_url) {
            return redirect($notification->action_url);
        }

        return back();
    }

    /**
     * Menampilkan halaman Profil Saya pelanggan.
     */
    public function profile(): View
    {
        return view('customer.profile', [
            'user' => Auth::user(),
            'storeSetting' => StoreSetting::current(),
        ]);
    }

    /**
     * Menampilkan formulir Edit Profil dan Ubah Kata Sandi pelanggan.
     */
    public function editProfile(): View
    {
        return view('customer.profile-edit', [
            'user' => Auth::user(),
            'storeSetting' => StoreSetting::current(),
        ]);
    }

    /**
     * Memperbarui data akun dan kata sandi pelanggan apabila diisi.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // 1. Validasi data profil dasar dan kata sandi opsional
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:25'],
            'secondary_phone' => ['nullable', 'string', 'max:25'],
            'saved_address' => ['nullable', 'string', 'max:1000'],
            'current_password' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
            'phone.required' => 'Nomor telepon/WhatsApp wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        // 2. Jika pengguna ingin mengganti kata sandi, periksa kecocokan kata sandi lama
        if (! empty($validated['password'])) {
            if (empty($validated['current_password']) || ! Hash::check($validated['current_password'], $user->password)) {
                return back()
                    ->withInput()
                    ->withErrors(['current_password' => 'Kata sandi lama tidak sesuai.']);
            }
        }

        // 3. Simpan perubahan data profil pelanggan ke database
        $emailChanged = $validated['email'] !== $user->email;
        $updatePayload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'secondary_phone' => $validated['secondary_phone'] ?? null,
            'saved_address' => $validated['saved_address'] ?? null,
        ];

        if ($emailChanged) {
            $updatePayload['email_verified_at'] = null;
        }

        if (! empty($validated['password'])) {
            $updatePayload['password'] = Hash::make($validated['password']);
        }

        $user->update($updatePayload);

        return redirect()->route('profile.show')
            ->with('success', 'Profil akun berhasil diperbarui.');
    }
}
