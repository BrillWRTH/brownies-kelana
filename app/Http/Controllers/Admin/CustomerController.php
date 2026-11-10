<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Menampilkan direktori pelanggan beserta pencarian, filter status aktif, dan statistik order.
     */
    public function index(Request $request): View
    {
        // 1. Ambil filter pencarian teks, status aktif akun, dan verifikasi email
        $searchKeyword = trim((string) $request->query('q', ''));
        $statusFilter = trim((string) $request->query('status', 'semua'));
        $verifiedFilter = trim((string) $request->query('verified', 'semua'));

        $customersQuery = User::query()
            ->where('role', 'customer')
            ->withCount('orders')
            ->withSum(['orders as total_spent' => function ($query): void {
                $query->where('payment_status', 'lunas');
            }], 'total_amount')
            ->latest('id');

        if ($searchKeyword !== '') {
            $customersQuery->where(function ($query) use ($searchKeyword): void {
                $query->where('name', 'like', '%'.$searchKeyword.'%')
                    ->orWhere('email', 'like', '%'.$searchKeyword.'%')
                    ->orWhere('phone', 'like', '%'.$searchKeyword.'%')
                    ->orWhere('customer_code', 'like', '%'.$searchKeyword.'%');
            });
        }

        if ($statusFilter === 'aktif') {
            $customersQuery->where('is_active', true);
        } elseif ($statusFilter === 'nonaktif') {
            $customersQuery->where('is_active', false);
        }

        if ($verifiedFilter === 'ya') {
            $customersQuery->whereNotNull('email_verified_at');
        } elseif ($verifiedFilter === 'belum') {
            $customersQuery->whereNull('email_verified_at');
        }

        $customers = $customersQuery->paginate(15)->withQueryString();

        return view('admin.customers.index', compact(
            'customers',
            'searchKeyword',
            'statusFilter',
            'verifiedFilter'
        ));
    }

    /**
     * Menampilkan profil detail pelanggan, pencocokan nomor HP WhatsApp, dan riwayat transaksi.
     */
    public function show(User $customer): View
    {
        // 1. Pastikan target pengguna adalah pelanggan
        if ($customer->isAdmin()) {
            abort(404);
        }

        $customer->load(['orders.items', 'reviews.product']);

        // 2. Cari pesanan manual WhatsApp tanpa user_id yang memiliki kecocokan nomor telepon ternormalisasi
        $normalizedPhone = User::normalizePhoneNumber($customer->phone);
        $matchedGuestOrders = collect();
        if ($normalizedPhone !== '') {
            $matchedGuestOrders = Order::query()
                ->whereNull('user_id')
                ->latest()
                ->get()
                ->filter(fn (Order $order): bool => User::normalizePhoneNumber($order->customer_phone) === $normalizedPhone);
        }

        // 3. Bangun tautan WhatsApp bantuan pemulihan akun & komunikasi langsung
        $waDigits = preg_replace('/[^0-9]/', '', (string) $customer->phone) ?? '';
        if (str_starts_with($waDigits, '0')) {
            $waDigits = '62'.substr($waDigits, 1);
        }
        $waHelpMessage = 'Halo Kak '.$customer->name.' ('.$customer->customer_code.'), kami dari tim Admin Brownies Kelana siap membantu terkait akun dan pesanan Anda.';
        $whatsappRecoveryUrl = $waDigits !== '' ? 'https://wa.me/'.$waDigits.'?text='.urlencode($waHelpMessage) : '#';

        $recoveryLogs = UserNotification::query()
            ->where('user_id', $customer->id)
            ->where('category', 'sistem')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.customers.show', compact(
            'customer',
            'normalizedPhone',
            'matchedGuestOrders',
            'whatsappRecoveryUrl',
            'recoveryLogs'
        ));
    }

    /**
     * Mengaktifkan kembali atau menonaktifkan akun pelanggan dengan alasan tercatat.
     */
    public function toggleActive(Request $request, User $customer): RedirectResponse
    {
        // 1. Lindungi akun admin agar tidak dinonaktifkan dari modul pelanggan
        if ($customer->isAdmin()) {
            abort(403);
        }

        // 2. Nonaktifkan dengan alasan wajib, atau aktifkan kembali secara bersih
        if ($customer->is_active) {
            $validated = $request->validate([
                'deactivated_reason' => ['required', 'string', 'min:4', 'max:255'],
            ], [
                'deactivated_reason.required' => 'Alasan penonaktifan akun wajib diisi.',
            ]);

            $customer->update([
                'is_active' => false,
                'deactivated_reason' => trim($validated['deactivated_reason']),
                'deactivated_at' => now(),
                'remember_token' => null,
            ]);

            return back()->with('success', 'Akun pelanggan '.$customer->name.' berhasil dinonaktifkan.');
        }

        $customer->update([
            'is_active' => true,
            'deactivated_reason' => null,
            'deactivated_at' => null,
        ]);

        return back()->with('success', 'Akun pelanggan '.$customer->name.' berhasil diaktifkan kembali.');
    }

    /**
     * Mencatat aktivitas bantuan pemulihan akun pelanggan via WhatsApp manual admin.
     */
    public function logRecovery(Request $request, User $customer): RedirectResponse
    {
        // 1. Validasi catatan bantuan pemulihan dari admin
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        // 2. Simpan catatan ke riwayat notifikasi sistem akun pelanggan
        $noteText = trim((string) ($validated['note'] ?? ''))
            ?: 'Admin menghubungi pelanggan via WhatsApp untuk verifikasi dan bantuan pemulihan akun.';

        UserNotification::create([
            'user_id' => $customer->id,
            'audience' => 'customer',
            'category' => 'sistem',
            'channel' => 'whatsapp_manual',
            'title' => 'Bantuan Pemulihan Akun',
            'message' => $noteText,
            'delivery_status' => 'dicatat_admin',
            'action_url' => route('profile.show'),
        ]);

        return back()->with('success', 'Catatan bantuan pemulihan akun berhasil disimpan.');
    }
}
