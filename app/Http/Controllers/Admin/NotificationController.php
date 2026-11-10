<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Models\StoreSetting;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Menampilkan Inbox Notifikasi Operasional Admin dari kejadian sistem & pesanan.
     */
    public function index(Request $request): View
    {
        // 1. Tandai notifikasi tunggal sudah dibaca apabila parameter ?read= dikirim
        if ($request->filled('read')) {
            $target = UserNotification::query()
                ->where('audience', 'admin')
                ->find((int) $request->query('read'));
            if ($target && ! $target->read_at) {
                $target->update(['read_at' => now()]);
            }
        }

        // 2. Saring daftar notifikasi admin berdasarkan kategori dan status baca
        $categoryFilter = trim((string) $request->query('category', 'semua'));
        $unreadOnly = $request->boolean('unread');

        $baseQuery = UserNotification::query()->where('audience', 'admin');
        $unreadCount = (clone $baseQuery)->whereNull('read_at')->count();

        $notificationsQuery = (clone $baseQuery)->with('order')->latest();
        if (in_array($categoryFilter, ['pesanan', 'sistem'], true)) {
            $notificationsQuery->where('category', $categoryFilter);
        }
        if ($unreadOnly) {
            $notificationsQuery->whereNull('read_at');
        }

        $notifications = $notificationsQuery->paginate(15)->withQueryString();

        return view('admin.notifications.index', compact(
            'notifications',
            'unreadCount',
            'categoryFilter',
            'unreadOnly'
        ));
    }

    /**
     * Menandai seluruh notifikasi di Inbox Admin sebagai sudah dibaca.
     */
    public function markAllRead(): RedirectResponse
    {
        UserNotification::query()
            ->where('audience', 'admin')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi Inbox Admin ditandai sudah dibaca.');
    }

    /**
     * Menampilkan daftar template teks notifikasi pesanan & sistem untuk diedit Admin.
     */
    public function templates(): View
    {
        $templates = NotificationTemplate::query()
            ->orderBy('category')
            ->orderBy('id')
            ->get();

        return view('admin.notifications.templates', compact('templates'));
    }

    /**
     * Memperbarui judul, isi pesan placeholder, kanal, dan status aktif template notifikasi.
     */
    public function updateTemplate(Request $request, NotificationTemplate $template): RedirectResponse
    {
        // 1. Validasi perubahan teks dan kanal pada template notifikasi
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'message_template' => ['required', 'string', 'max:1000'],
            'channels' => ['nullable', 'array'],
            'channels.*' => ['in:dashboard,whatsapp'],
        ], [
            'title.required' => 'Judul template wajib diisi.',
            'message_template.required' => 'Isi pesan template wajib diisi.',
        ]);

        // 2. Simpan pembaruan template notifikasi ke database
        $template->update([
            'title' => trim($validated['title']),
            'message_template' => trim($validated['message_template']),
            'channels' => $validated['channels'] ?? ['dashboard'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Template "'.$template->title.'" berhasil diperbarui.');
    }

    /**
     * Menampilkan halaman Kirim Notifikasi Khusus ke pelanggan via Dashboard atau WhatsApp Manual.
     */
    public function create(): View
    {
        $customers = User::query()
            ->where('role', 'customer')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $orders = Order::query()->latest()->take(25)->get();
        $templates = NotificationTemplate::query()->where('is_active', true)->orderBy('id')->get();
        $storeSetting = StoreSetting::current();

        return view('admin.notifications.create', compact(
            'customers',
            'orders',
            'templates',
            'storeSetting'
        ));
    }

    /**
     * Mengirim notifikasi dashboard atau mencatat pengiriman pesan WhatsApp ke pelanggan.
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validasi target penerima, kategori, kanal pengiriman, dan isi notifikasi
        $validated = $request->validate([
            'target_type' => ['required', 'in:single,broadcast'],
            'user_id' => ['required_if:target_type,single', 'nullable', 'exists:users,id'],
            'order_id' => ['nullable', 'exists:orders,id'],
            'category' => ['required', 'in:pesanan,sistem'],
            'channel' => ['required', 'in:dashboard,whatsapp_manual'],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:1000'],
        ], [
            'user_id.required_if' => 'Pilih pelanggan tujuan untuk notifikasi personal.',
            'title.required' => 'Judul notifikasi wajib diisi.',
            'message.required' => 'Isi pesan notifikasi wajib diisi.',
        ]);

        $order = ! empty($validated['order_id']) ? Order::find((int) $validated['order_id']) : null;
        $actionUrl = $order ? route('orders.show', $order->order_number) : route('notifications.index');
        $deliveryStatus = $validated['channel'] === 'whatsapp_manual' ? 'dicatat_admin' : 'terkirim';

        // 2. Kirim ke satu pelanggan terpilih atau siaran ke seluruh pelanggan aktif
        $waLaunchUrl = null;
        if ($validated['target_type'] === 'broadcast') {
            $recipients = User::query()
                ->where('role', 'customer')
                ->where('is_active', true)
                ->get();

            foreach ($recipients as $recipient) {
                $renderedMessage = $this->renderDynamicTokens($validated['message'], $recipient, $order);
                UserNotification::create([
                    'user_id' => $recipient->id,
                    'order_id' => $order?->id,
                    'audience' => 'customer',
                    'category' => $validated['category'],
                    'channel' => $validated['channel'],
                    'title' => trim($validated['title']),
                    'message' => $renderedMessage,
                    'delivery_status' => $deliveryStatus,
                    'action_url' => $actionUrl,
                ]);
            }

            $successText = 'Notifikasi berhasil dikirim ke '.$recipients->count().' pelanggan aktif.';
        } else {
            $recipient = User::query()->findOrFail((int) $validated['user_id']);
            $renderedMessage = $this->renderDynamicTokens($validated['message'], $recipient, $order);

            UserNotification::create([
                'user_id' => $recipient->id,
                'order_id' => $order?->id,
                'audience' => 'customer',
                'category' => $validated['category'],
                'channel' => $validated['channel'],
                'title' => trim($validated['title']),
                'message' => $renderedMessage,
                'delivery_status' => $deliveryStatus,
                'action_url' => $actionUrl,
            ]);

            if ($validated['channel'] === 'whatsapp_manual' && $recipient->phone) {
                $digits = preg_replace('/[^0-9]/', '', $recipient->phone) ?? '';
                if (str_starts_with($digits, '0')) {
                    $digits = '62'.substr($digits, 1);
                }
                if ($digits !== '') {
                    $waLaunchUrl = 'https://wa.me/'.$digits.'?text='.urlencode($renderedMessage);
                }
            }

            $successText = 'Notifikasi berhasil dikirim ke '.$recipient->name.'.';
        }

        return redirect()->route('admin.notifications.history')
            ->with('success', $successText)
            ->with('wa_launch_url', $waLaunchUrl);
    }

    /**
     * Menampilkan riwayat seluruh notifikasi yang telah dikirim kepada pelanggan.
     */
    public function history(Request $request): View
    {
        $categoryFilter = trim((string) $request->query('category', 'semua'));
        $channelFilter = trim((string) $request->query('channel', 'semua'));
        $searchKeyword = trim((string) $request->query('q', ''));

        $logsQuery = UserNotification::query()
            ->where('audience', 'customer')
            ->with(['user', 'order'])
            ->latest();

        if (in_array($categoryFilter, ['pesanan', 'sistem'], true)) {
            $logsQuery->where('category', $categoryFilter);
        }

        if (in_array($channelFilter, ['dashboard', 'whatsapp_manual'], true)) {
            $logsQuery->where('channel', $channelFilter);
        }

        if ($searchKeyword !== '') {
            $logsQuery->where(function ($query) use ($searchKeyword): void {
                $query->where('title', 'like', '%'.$searchKeyword.'%')
                    ->orWhere('message', 'like', '%'.$searchKeyword.'%')
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$searchKeyword.'%'));
            });
        }

        $logs = $logsQuery->paginate(15)->withQueryString();

        return view('admin.notifications.history', compact(
            'logs',
            'categoryFilter',
            'channelFilter',
            'searchKeyword'
        ));
    }

    /**
     * Mengganti placeholder {nama_pelanggan} dan {nomor_pesanan} pada pesan manual admin.
     */
    protected function renderDynamicTokens(string $message, User $recipient, ?Order $order): string
    {
        return str_replace(
            ['{nama_pelanggan}', '{nomor_pesanan}'],
            [$recipient->name, $order?->order_number ?? '-'],
            trim($message)
        );
    }
}
