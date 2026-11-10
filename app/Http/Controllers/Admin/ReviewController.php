<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * Menampilkan antrean moderasi dan daftar ulasan pelanggan Brownies Kelana.
     */
    public function index(Request $request): View
    {
        // 1. Ambil filter status moderasi, rating, dan pencarian teks
        $statusFilter = trim((string) $request->query('status', 'semua'));
        $ratingFilter = trim((string) $request->query('rating', ''));
        $searchKeyword = trim((string) $request->query('q', ''));

        $counts = [
            'all' => Review::query()->count(),
            'pending' => Review::query()->where('moderation_status', 'menunggu_persetujuan')->count(),
            'approved' => Review::query()->where('moderation_status', 'disetujui')->count(),
            'hidden' => Review::query()->where('moderation_status', 'disembunyikan')->count(),
            'home' => Review::query()->where('moderation_status', 'disetujui')->where('is_featured_home', true)->count(),
        ];

        // 2. Bangun query ulasan dengan relasi pelanggan, pesanan, dan produk
        $reviewsQuery = Review::query()
            ->with(['user', 'order', 'product'])
            ->latest();

        if ($statusFilter === 'menunggu_persetujuan') {
            $reviewsQuery->where('moderation_status', 'menunggu_persetujuan');
        } elseif ($statusFilter === 'disetujui') {
            $reviewsQuery->where('moderation_status', 'disetujui');
        } elseif ($statusFilter === 'disembunyikan') {
            $reviewsQuery->where('moderation_status', 'disembunyikan');
        } elseif ($statusFilter === 'home') {
            $reviewsQuery->where('moderation_status', 'disetujui')->where('is_featured_home', true);
        }

        if ($ratingFilter !== '') {
            $reviewsQuery->where('rating', (int) $ratingFilter);
        }

        if ($searchKeyword !== '') {
            $reviewsQuery->where(function ($query) use ($searchKeyword): void {
                $query->where('comment', 'like', '%'.$searchKeyword.'%')
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$searchKeyword.'%'))
                    ->orWhereHas('product', fn ($prodQuery) => $prodQuery->where('name', 'like', '%'.$searchKeyword.'%'));
            });
        }

        $reviews = $reviewsQuery->paginate(12)->withQueryString();

        return view('admin.reviews.index', compact(
            'reviews',
            'counts',
            'statusFilter',
            'ratingFilter',
            'searchKeyword'
        ));
    }

    /**
     * Menampilkan halaman detail lengkap satu ulasan pelanggan beserta pesanan terkait.
     */
    public function show(Review $review): View
    {
        $review->load(['order.items', 'user', 'product']);

        return view('admin.reviews.show', compact('review'));
    }

    /**
     * Menyetujui ulasan pelanggan agar tampil secara publik pada detail produk.
     */
    public function approve(Review $review): RedirectResponse
    {
        // 1. Perbarui status moderasi menjadi disetujui dan hapus alasan sembunyi sebelumnya
        $review->update([
            'moderation_status' => 'disetujui',
            'hide_reason' => null,
        ]);

        return back()->with('success', 'Ulasan berhasil disetujui untuk tampil.');
    }

    /**
     * Menyembunyikan ulasan dengan alasan moderasi wajib dan melepas penayangan di Home.
     */
    public function hide(Request $request, Review $review): RedirectResponse
    {
        // 1. Validasi alasan penyembunyian ulasan secara wajib
        $validated = $request->validate([
            'hide_reason' => ['required', 'string', 'min:4', 'max:255'],
        ], [
            'hide_reason.required' => 'Alasan menyembunyikan ulasan wajib diisi.',
        ]);

        // 2. Ubah status moderasi menjadi disembunyikan dan nonaktifkan tampil di Home
        $review->update([
            'moderation_status' => 'disembunyikan',
            'is_featured_home' => false,
            'hide_reason' => trim($validated['hide_reason']),
        ]);

        return back()->with('success', 'Ulasan berhasil disembunyikan dengan catatan alasan.');
    }

    /**
     * Menyimpan balasan resmi Admin/Owner pada ulasan pelanggan dan mengirim notifikasi.
     */
    public function reply(Request $request, Review $review): RedirectResponse
    {
        // 1. Validasi isi balasan admin terhadap ulasan pelanggan
        $validated = $request->validate([
            'admin_reply' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'admin_reply.required' => 'Isi balasan admin wajib diisi.',
        ]);

        // 2. Simpan balasan ke tabel ulasan dan beri notifikasi ke dashboard pelanggan
        $review->update([
            'admin_reply' => trim($validated['admin_reply']),
            'admin_replied_at' => now(),
        ]);

        if ($review->user_id && $review->order) {
            UserNotification::create([
                'user_id' => $review->user_id,
                'order_id' => $review->order_id,
                'audience' => 'customer',
                'category' => 'pesanan',
                'channel' => 'dashboard',
                'title' => 'Admin Membalas Ulasanmu',
                'message' => 'Brownies Kelana membalas ulasanmu pada pesanan '.$review->order->order_number.': "'.mb_substr(trim($validated['admin_reply']), 0, 80).'"',
                'delivery_status' => 'terkirim',
                'action_url' => route('orders.show', $review->order->order_number),
            ]);
        }

        return back()->with('success', 'Balasan ulasan berhasil disimpan.');
    }

    /**
     * Mengaktifkan atau menonaktifkan penayangan ulasan pilihan di halaman utama (Home).
     */
    public function toggleHome(Review $review): RedirectResponse
    {
        // 1. Pastikan hanya ulasan yang sudah disetujui yang dapat tampil di halaman Home
        if ($review->moderation_status !== 'disetujui') {
            return back()->with('error', 'Setujui ulasan terlebih dahulu sebelum menampilkannya di halaman utama.');
        }

        // 2. Balik status tampil di halaman utama (maksimal fleksibel)
        $review->update([
            'is_featured_home' => ! $review->is_featured_home,
        ]);

        $statusLabel = $review->is_featured_home ? 'ditampilkan di halaman utama Home.' : 'dilepas dari halaman utama Home.';

        return back()->with('success', 'Ulasan berhasil '.$statusLabel);
    }
}
