<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\PickupLocation;
use App\Models\ShippingZone;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Menampilkan seluruh pengaturan operasional toko, jadwal, pickup, ongkir Midtrans & keamanan.
     */
    public function index(): View
    {
        // 1. Muat pengaturan toko aktif beserta daftar libur, pickup, dan zona ongkir
        $storeSetting = StoreSetting::current();
        $holidays = Holiday::query()->orderBy('holiday_date')->get();
        $pickupLocations = PickupLocation::query()->orderBy('sort_order')->orderBy('id')->get();
        $shippingZones = ShippingZone::query()->orderBy('fee')->get();
        $adminUser = Auth::user();

        return view('admin.settings.index', compact(
            'storeSetting',
            'holidays',
            'pickupLocations',
            'shippingZones',
            'adminUser'
        ));
    }

    /**
     * Memperbarui identitas toko, kontak WhatsApp/email, Instagram, dan koordinat alamat utama.
     */
    public function updateStore(Request $request): RedirectResponse
    {
        // 1. Validasi data profil toko dan titik lokasi dapur utama
        $validated = $request->validate([
            'store_name' => ['required', 'string', 'max:150'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'about_story' => ['nullable', 'string', 'max:2000'],
            'whatsapp_number' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150'],
            'instagram_url' => ['nullable', 'string', 'max:255'],
            'store_address' => ['required', 'string', 'max:1000'],
            'store_latitude' => ['nullable', 'numeric'],
            'store_longitude' => ['nullable', 'numeric'],
            'google_maps_url' => ['nullable', 'string', 'max:500'],
        ]);

        // 2. Simpan perubahan ke tabel pengaturan toko
        $storeSetting = $this->resolvePersistentSetting();
        $storeSetting->update($validated);

        return back()->with('success', 'Informasi toko dan kontak WhatsApp berhasil diperbarui.');
    }

    /**
     * Memperbarui jam operasional mingguan, aturan H-1, kuota harian, dan slot jam.
     */
    public function updateSchedule(Request $request): RedirectResponse
    {
        // 1. Validasi aturan pre-order, kuota harian, dan jam operasional per hari
        $validated = $request->validate([
            'preorder_min_days' => ['required', 'integer', 'min:0', 'max:14'],
            'daily_quota' => ['required', 'integer', 'min:1', 'max:500'],
            'delivery_slots_text' => ['required', 'string', 'max:500'],
            'days' => ['required', 'array'],
        ]);

        // 2. Format jam operasional mingguan (Senin-Minggu)
        $dayLabels = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu',
        ];

        $operatingHours = [];
        foreach ($dayLabels as $key => $label) {
            $dayInput = $validated['days'][$key] ?? [];
            $isOpen = ! empty($dayInput['is_open']);
            $operatingHours[$key] = [
                'label' => $label,
                'is_open' => $isOpen,
                'open' => $isOpen ? ($dayInput['open'] ?? '09:00') : null,
                'close' => $isOpen ? ($dayInput['close'] ?? '16:00') : null,
            ];
        }

        // 3. Pecah daftar slot jam kirim/ambil berdasarkan baris baru atau koma
        $slots = collect(preg_split('/[\r\n,]+/', $validated['delivery_slots_text']) ?: [])
            ->map(fn (string $slot): string => trim($slot))
            ->filter()
            ->values()
            ->all();

        if (empty($slots)) {
            $slots = ['09.00-11.00 WIB', '13.00-15.00 WIB'];
        }

        $storeSetting = $this->resolvePersistentSetting();
        $storeSetting->update([
            'preorder_min_days' => (int) $validated['preorder_min_days'],
            'daily_quota' => (int) $validated['daily_quota'],
            'operating_hours' => $operatingHours,
            'delivery_slots' => $slots,
        ]);

        return back()->with('success', 'Jadwal operasional, kuota harian, dan slot waktu berhasil disimpan.');
    }

    /**
     * Menambahkan tanggal hari libur khusus agar kalender checkout menutup pemesanan.
     */
    public function storeHoliday(Request $request): RedirectResponse
    {
        // 1. Validasi tanggal libur dan nama keterangan hari libur
        $validated = $request->validate([
            'holiday_date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:150'],
        ], [
            'holiday_date.required' => 'Tanggal libur wajib dipilih.',
            'name.required' => 'Nama keterangan hari libur wajib diisi.',
        ]);

        if (Holiday::query()->whereDate('holiday_date', $validated['holiday_date'])->exists()) {
            return back()
                ->withErrors(['holiday_date' => 'Tanggal libur tersebut sudah terdaftar.'])
                ->withInput();
        }

        Holiday::create([
            'holiday_date' => $validated['holiday_date'],
            'name' => trim($validated['name']),
            'is_active' => true,
        ]);

        return back()->with('success', 'Hari libur berhasil ditambahkan.');
    }

    /**
     * Menghapus tanggal hari libur yang sudah tidak berlaku.
     */
    public function destroyHoliday(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return back()->with('success', 'Hari libur berhasil dihapus.');
    }

    /**
     * Menambahkan titik lokasi pengambilan mandiri (Pickup) baru.
     */
    public function storePickup(Request $request): RedirectResponse
    {
        // 1. Validasi data nama, alamat lengkap, dan tautan Google Maps lokasi pickup
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:500'],
            'maps_url' => ['nullable', 'string', 'max:500'],
            'operating_info' => ['nullable', 'string', 'max:150'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $nextOrder = ((int) PickupLocation::query()->max('sort_order')) + 1;

        PickupLocation::create([
            'name' => trim($validated['name']),
            'address' => trim($validated['address']),
            'maps_url' => trim((string) ($validated['maps_url'] ?? '')) ?: null,
            'operating_info' => trim((string) ($validated['operating_info'] ?? '')) ?: 'Sesuai jadwal pengambilan · Gratis ongkir',
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'is_active' => true,
            'sort_order' => $nextOrder,
        ]);

        return back()->with('success', 'Lokasi pickup baru berhasil ditambahkan.');
    }

    /**
     * Memperbarui detail lokasi pickup atau mengaktifkan/menonaktifkan titik pengambilan.
     */
    public function updatePickup(Request $request, PickupLocation $pickup): RedirectResponse
    {
        // 1. Jika hanya toggle status aktif, balikkan nilai is_active
        if ($request->boolean('toggle_only')) {
            $pickup->update(['is_active' => ! $pickup->is_active]);

            return back()->with('success', 'Status lokasi pickup '.$pickup->name.' diperbarui.');
        }

        // 2. Validasi perubahan lengkap informasi lokasi pickup
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:500'],
            'maps_url' => ['nullable', 'string', 'max:500'],
            'operating_info' => ['nullable', 'string', 'max:150'],
        ]);

        $pickup->update([
            'name' => trim($validated['name']),
            'address' => trim($validated['address']),
            'maps_url' => trim((string) ($validated['maps_url'] ?? '')) ?: null,
            'operating_info' => trim((string) ($validated['operating_info'] ?? '')) ?: null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $pickup->is_active,
        ]);

        return back()->with('success', 'Lokasi pickup '.$pickup->name.' berhasil diperbarui.');
    }

    /**
     * Menghapus titik lokasi pickup dari daftar pengambilan.
     */
    public function destroyPickup(PickupLocation $pickup): RedirectResponse
    {
        $pickupName = $pickup->name;
        $pickup->delete();

        return back()->with('success', 'Lokasi pickup '.$pickupName.' berhasil dihapus.');
    }

    /**
     * Memperbarui metode perhitungan ongkir aktif (Flat, Zona, atau Jarak) beserta tarifnya.
     */
    public function updateShipping(Request $request): RedirectResponse
    {
        // 1. Validasi metode ongkir tunggal yang aktif dan seluruh parameter tarif
        $validated = $request->validate([
            'shipping_method' => ['required', 'in:flat,zone,distance'],
            'flat_shipping_fee' => ['required', 'integer', 'min:0'],
            'distance_base_km' => ['required', 'numeric', 'min:0.5', 'max:50'],
            'distance_base_fee' => ['required', 'integer', 'min:0'],
            'distance_per_km_fee' => ['required', 'integer', 'min:0'],
            'distance_max_km' => ['required', 'numeric', 'min:1', 'max:100'],
        ]);

        // 2. Simpan metode ongkir aktif ke pengaturan toko
        $storeSetting = $this->resolvePersistentSetting();
        $storeSetting->update([
            'shipping_method' => $validated['shipping_method'],
            'flat_shipping_fee' => (int) $validated['flat_shipping_fee'],
            'distance_base_km' => (float) $validated['distance_base_km'],
            'distance_base_fee' => (int) $validated['distance_base_fee'],
            'distance_per_km_fee' => (int) $validated['distance_per_km_fee'],
            'distance_max_km' => (float) $validated['distance_max_km'],
        ]);

        return back()->with('success', 'Pengaturan metode ongkos kirim berhasil diperbarui.');
    }

    /**
     * Menambahkan zona wilayah pengiriman beserta tarif ongkirnya.
     */
    public function storeZone(Request $request): RedirectResponse
    {
        // 1. Validasi nama zona, deskripsi kecamatan, dan tarif ongkos kirim
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'area_description' => ['nullable', 'string', 'max:300'],
            'fee' => ['required', 'integer', 'min:0'],
        ]);

        ShippingZone::create([
            'name' => trim($validated['name']),
            'area_description' => trim((string) ($validated['area_description'] ?? '')) ?: null,
            'fee' => (int) $validated['fee'],
            'is_active' => true,
        ]);

        return back()->with('success', 'Zona ongkir berhasil ditambahkan.');
    }

    /**
     * Menghapus zona wilayah pengiriman yang tidak lagi digunakan.
     */
    public function destroyZone(ShippingZone $zone): RedirectResponse
    {
        $zone->delete();

        return back()->with('success', 'Zona ongkir berhasil dihapus.');
    }

    /**
     * Memperbarui batas waktu pembayaran Midtrans (timeout) dan status otomatis setelah kedaluwarsa.
     */
    public function updatePaymentTimeout(Request $request): RedirectResponse
    {
        // 1. Validasi durasi timeout menit dan status akhir saat kedaluwarsa
        $validated = $request->validate([
            'payment_timeout_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'auto_cancel_status' => ['required', 'in:kedaluwarsa,dibatalkan'],
        ]);

        $storeSetting = $this->resolvePersistentSetting();
        $storeSetting->update($validated);

        return back()->with('success', 'Batas waktu pembayaran Midtrans berhasil diperbarui.');
    }

    /**
     * Memperbarui password akun Admin yang sedang masuk dengan pemeriksaan password lama.
     */
    public function updateAdminPassword(Request $request): RedirectResponse
    {
        $adminUser = Auth::user();

        // 1. Validasi password lama, password baru minimal 8 karakter, dan konfirmasinya
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.required' => 'Password lama wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama dengan password baru.',
            'password.different' => 'Password baru harus berbeda dari password lama.',
        ]);

        // 2. Pastikan password lama cocok dengan akun admin saat ini
        if (! Hash::check($validated['current_password'], $adminUser->password)) {
            return back()
                ->withErrors(['current_password' => 'Password lama tidak sesuai.'])
                ->withInput();
        }

        // 3. Simpan hash password baru ke akun admin
        $adminUser->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password admin berhasil diubah. Gunakan password baru saat login berikutnya.');
    }

    /**
     * Mengambil baris StoreSetting pertama yang tersimpan di database atau membuatnya jika belum ada.
     */
    protected function resolvePersistentSetting(): StoreSetting
    {
        $existing = StoreSetting::query()->first();
        if ($existing) {
            return $existing;
        }

        return StoreSetting::create([
            'store_name' => 'Brownies Kelana',
            'store_address' => 'Grand Royal Regency, Cluster Flamboyan E3-16, Jl Gadung, Wage, Taman, Sidoarjo.',
        ]);
    }
}
