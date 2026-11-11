@extends('layouts.admin')

@section('title', 'Pengaturan Toko & Operasional — Panel Admin')
@section('kicker', 'PENGATURAN TOKO, ONGKIR & KEAMANAN')

@section('content')
    @php
        $dayDefinitions = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu',
        ];
        $hours = $storeSetting->operating_hours ?? [];
        $slotsText = implode("\n", $storeSetting->delivery_slots ?? ['09.00-11.00 WIB', '13.00-15.00 WIB']);
    @endphp

    <div class="mx-auto max-w-6xl space-y-6">
        <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
            <h1 class="text-xl font-bold text-[#28211c]">Pengaturan Operasional Brownies Kelana</h1>
            <p class="mt-1 text-xs text-[#796b62]">
                Kelola identitas UMKM, jam buka &amp; libur H-1, titik pickup, metode ongkir aktif, batas bayar Midtrans, serta keamanan password admin.
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-12">
            {{-- 1. Profil Toko, WhatsApp & Titik Koordinat Dapur --}}
            <form method="POST" action="{{ route('admin.settings.store.update') }}"
                class="space-y-4 rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs lg:col-span-6">
                @csrf
                @method('PUT')
                <h2 class="text-sm font-bold text-[#52311a]">1. Identitas Toko &amp; Alamat Dapur Pusat</h2>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="store_name" class="kelana-label">Nama Toko *</label>
                        <input id="store_name" name="store_name" type="text" value="{{ old('store_name', $storeSetting->store_name) }}" required class="kelana-input text-xs">
                    </div>
                    <div>
                        <label for="tagline" class="kelana-label">Tagline Brand</label>
                        <input id="tagline" name="tagline" type="text" value="{{ old('tagline', $storeSetting->tagline) }}" class="kelana-input text-xs">
                    </div>
                    <div>
                        <label for="whatsapp_number" class="kelana-label">Nomor WhatsApp Admin *</label>
                        <input id="whatsapp_number" name="whatsapp_number" type="text" value="{{ old('whatsapp_number', $storeSetting->whatsapp_number) }}" required class="kelana-input text-xs">
                    </div>
                    <div>
                        <label for="email" class="kelana-label">Email Resmi Toko *</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $storeSetting->email) }}" required class="kelana-input text-xs">
                    </div>
                </div>

                <div>
                    <label for="instagram_url" class="kelana-label">Tautan Instagram</label>
                    <input id="instagram_url" name="instagram_url" type="text" value="{{ old('instagram_url', $storeSetting->instagram_url) }}" class="kelana-input text-xs">
                </div>

                <div>
                    <label for="store_address" class="kelana-label">Alamat Lengkap Dapur Utama *</label>
                    <textarea id="store_address" name="store_address" rows="2" required class="kelana-input text-xs">{{ old('store_address', $storeSetting->store_address) }}</textarea>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="store_latitude" class="kelana-label">Latitude Dapur (Basis Jarak)</label>
                        <input id="store_latitude" name="store_latitude" type="text" value="{{ old('store_latitude', $storeSetting->store_latitude) }}" class="kelana-input text-xs">
                    </div>
                    <div>
                        <label for="store_longitude" class="kelana-label">Longitude Dapur (Basis Jarak)</label>
                        <input id="store_longitude" name="store_longitude" type="text" value="{{ old('store_longitude', $storeSetting->store_longitude) }}" class="kelana-input text-xs">
                    </div>
                </div>

                <div>
                    <label for="google_maps_url" class="kelana-label">Tautan Google Maps Dapur</label>
                    <input id="google_maps_url" name="google_maps_url" type="text" value="{{ old('google_maps_url', $storeSetting->google_maps_url) }}" class="kelana-input text-xs">
                </div>

                <div>
                    <label for="about_story" class="kelana-label">Cerita Singkat UMKM (Tentang Kami)</label>
                    <textarea id="about_story" name="about_story" rows="2" class="kelana-input text-xs">{{ old('about_story', $storeSetting->about_story) }}</textarea>
                </div>

                <button type="submit" class="btn-kelana-primary px-5 py-2 text-xs">Simpan Identitas Toko</button>
            </form>

            {{-- 2. Batas Pembayaran Midtrans & Keamanan Password Admin --}}
            <div class="space-y-6 lg:col-span-6">
                <form method="POST" action="{{ route('admin.settings.payment.update') }}"
                    class="space-y-4 rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    @csrf
                    @method('PUT')
                    <h2 class="text-sm font-bold text-[#52311a]">2. Batas Waktu Pembayaran Midtrans</h2>
                    <p class="text-xs text-[#796b62]">
                        Pembayaran 100% dikelola otomatis melalui Midtrans (QRIS, Transfer Bank VA, GoPay, Kartu).
                    </p>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="payment_timeout_minutes" class="kelana-label">Batas Bayar (Menit) *</label>
                            <input id="payment_timeout_minutes" name="payment_timeout_minutes" type="number" min="5" max="1440"
                                value="{{ old('payment_timeout_minutes', $storeSetting->payment_timeout_minutes) }}" required class="kelana-input text-xs">
                        </div>
                        <div>
                            <label for="auto_cancel_status" class="kelana-label">Status Otomatis Setelah Timeout *</label>
                            <select id="auto_cancel_status" name="auto_cancel_status" required class="kelana-input text-xs">
                                <option value="kedaluwarsa" @selected($storeSetting->auto_cancel_status === 'kedaluwarsa')>Kedaluwarsa</option>
                                <option value="dibatalkan" @selected($storeSetting->auto_cancel_status === 'dibatalkan')>Dibatalkan</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn-kelana-primary px-5 py-2 text-xs">Simpan Pengaturan Midtrans</button>
                </form>

                <form method="POST" action="{{ route('admin.settings.security.update') }}"
                    class="space-y-3 rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs">
                    @csrf
                    @method('PUT')
                    <h2 class="text-sm font-bold text-[#52311a]">3. Keamanan &amp; Ganti Password Admin</h2>
                    <p class="text-xs text-[#796b62]">Akun Admin aktif saat ini: <strong>{{ $adminUser->email }}</strong></p>

                    <div>
                        <label for="current_password" class="kelana-label">Password Lama *</label>
                        <input id="current_password" name="current_password" type="password" required class="kelana-input text-xs">
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="password" class="kelana-label">Password Baru (min 8) *</label>
                            <input id="password" name="password" type="password" required class="kelana-input text-xs">
                        </div>
                        <div>
                            <label for="password_confirmation" class="kelana-label">Konfirmasi Password Baru *</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required class="kelana-input text-xs">
                        </div>
                    </div>

                    <button type="submit" class="btn-kelana-outline px-5 py-2 text-xs">Perbarui Password Admin</button>
                </form>
            </div>
        </div>

        {{-- 4. Jadwal Operasional, H-1, Kuota & Hari Libur --}}
        <div class="grid gap-6 lg:grid-cols-12">
            <form method="POST" action="{{ route('admin.settings.schedule.update') }}"
                class="space-y-4 rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs lg:col-span-7">
                @csrf
                @method('PUT')
                <h2 class="text-sm font-bold text-[#52311a]">4. Jam Operasional, Aturan Pre-Order H-1 &amp; Kuota Harian</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="preorder_min_days" class="kelana-label">Minimal Hari Pre-Order (H-*) *</label>
                        <input id="preorder_min_days" name="preorder_min_days" type="number" min="0" max="14"
                            value="{{ old('preorder_min_days', $storeSetting->preorder_min_days) }}" required class="kelana-input text-xs">
                    </div>
                    <div>
                        <label for="daily_quota" class="kelana-label">Kuota Pesanan Maksimal per Hari *</label>
                        <input id="daily_quota" name="daily_quota" type="number" min="1" max="500"
                            value="{{ old('daily_quota', $storeSetting->daily_quota) }}" required class="kelana-input text-xs">
                    </div>
                </div>

                <div>
                    <label for="delivery_slots_text" class="kelana-label">Daftar Slot Jam Kirim / Pickup (satu per baris) *</label>
                    <textarea id="delivery_slots_text" name="delivery_slots_text" rows="2" required class="kelana-input text-xs">{{ old('delivery_slots_text', $slotsText) }}</textarea>
                </div>

                <div class="space-y-2 border-t border-[#efe7dc] pt-3">
                    <p class="text-xs font-bold text-[#52311a]">Jam Operasional Mingguan</p>
                    @foreach($dayDefinitions as $dayKey => $dayLabel)
                        @php
                            $cfg = $hours[$dayKey] ?? ['is_open' => true, 'open' => '09:00', 'close' => '16:00'];
                        @endphp
                        <div class="grid items-center gap-2 rounded-xl bg-[#fdfbf6] px-3 py-2 text-xs sm:grid-cols-12">
                            <label class="flex items-center gap-2 font-bold sm:col-span-4">
                                <input type="checkbox" name="days[{{ $dayKey }}][is_open]" value="1" @checked($cfg['is_open'] ?? false)
                                    class="rounded border-[#b18950] text-[#52311a]">
                                <span>{{ $dayLabel }}</span>
                            </label>
                            <div class="flex items-center gap-2 sm:col-span-8">
                                <input type="text" name="days[{{ $dayKey }}][open]" value="{{ $cfg['open'] ?? '09:00' }}"
                                    placeholder="09:00" class="kelana-input py-1 text-xs">
                                <span>s/d</span>
                                <input type="text" name="days[{{ $dayKey }}][close]" value="{{ $cfg['close'] ?? '16:00' }}"
                                    placeholder="16:00" class="kelana-input py-1 text-xs">
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="submit" class="btn-kelana-primary px-5 py-2 text-xs">Simpan Jadwal &amp; Kuota</button>
            </form>

            {{-- Hari Libur Khusus --}}
            <div class="space-y-4 rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs lg:col-span-5">
                <h2 class="text-sm font-bold text-[#52311a]">5. Kalender Hari Libur Khusus</h2>
                <p class="text-xs text-[#796b62]">Tanggal libur akan otomatis dikunci dari kalender checkout pelanggan.</p>

                <form method="POST" action="{{ route('admin.settings.holidays.store') }}" class="space-y-3 rounded-xl bg-[#fdfbf6] p-3.5">
                    @csrf
                    <div>
                        <label for="holiday_date" class="kelana-label">Tanggal Libur *</label>
                        <input id="holiday_date" name="holiday_date" type="date" required class="kelana-input py-1.5 text-xs">
                    </div>
                    <div>
                        <label for="holiday_name" class="kelana-label">Keterangan Hari Libur *</label>
                        <input id="holiday_name" name="name" type="text" required placeholder="Contoh: Libur Lebaran / Dapur Renovasi" class="kelana-input py-1.5 text-xs">
                    </div>
                    <button type="submit" class="btn-kelana-primary w-full py-1.5 text-xs">+ Tambah Hari Libur</button>
                </form>

                <div class="space-y-2">
                    @forelse($holidays as $holiday)
                        <div class="flex items-center justify-between rounded-xl border border-[#efe7dc] px-3 py-2 text-xs">
                            <div>
                                <p class="font-bold text-[#28211c]">{{ $holiday->holiday_date->locale('id')->isoFormat('D MMM YYYY') }}</p>
                                <p class="text-[11px] text-[#796b62]">{{ $holiday->name }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.settings.holidays.destroy', $holiday) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[11px] font-bold text-red-700 hover:underline">Hapus</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-xs text-[#796b62]">Belum ada tanggal libur khusus terdaftar.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- 6. Metode Perhitungan Ongkir & Zona Wilayah --}}
        <div class="grid gap-6 lg:grid-cols-12">
            <form method="POST" action="{{ route('admin.settings.shipping.update') }}"
                class="space-y-4 rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs lg:col-span-7">
                @csrf
                @method('PUT')
                <h2 class="text-sm font-bold text-[#52311a]">6. Pengaturan Metode Ongkos Kirim</h2>

                <div class="grid gap-2.5 sm:grid-cols-3 text-xs font-bold">
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-[#d6c5b3] bg-[#fdfbf6] p-3">
                        <input type="radio" name="shipping_method" value="flat" @checked($storeSetting->shipping_method === 'flat') class="shipping-mode-radio text-[#52311a]">
                        <span>Tarif Flat</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-[#d6c5b3] bg-[#fdfbf6] p-3">
                        <input type="radio" name="shipping_method" value="zone" @checked($storeSetting->shipping_method === 'zone') class="shipping-mode-radio text-[#52311a]">
                        <span>Per Zona Wilayah</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-[#d6c5b3] bg-[#fdfbf6] p-3">
                        <input type="radio" name="shipping_method" value="distance" @checked($storeSetting->shipping_method === 'distance') class="shipping-mode-radio text-[#52311a]">
                        <span>Hitung Jarak (Km)</span>
                    </label>
                </div>

                <div class="rounded-xl border border-[#efe7dc] bg-[#fdfbf6] p-4">
                    <label for="flat_shipping_fee" class="kelana-label">Tarif Flat Nasional/Lokal (Rp) *</label>
                    <input id="flat_shipping_fee" name="flat_shipping_fee" type="number" min="0"
                        value="{{ old('flat_shipping_fee', $storeSetting->flat_shipping_fee) }}" required class="kelana-input text-xs">
                </div>

                <div class="rounded-xl border border-[#efe7dc] bg-[#fdfbf6] p-4 space-y-3">
                    <p class="text-xs font-bold text-[#52311a]">Rumus Tarif Jarak Pengiriman (Peta Leaflet + Haversine Server)</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="distance_base_km" class="kelana-label">Jarak Dasar Termasuk (Km) *</label>
                            <input id="distance_base_km" name="distance_base_km" type="number" step="0.5" min="0.5"
                                value="{{ old('distance_base_km', $storeSetting->distance_base_km) }}" required class="kelana-input text-xs">
                        </div>
                        <div>
                            <label for="distance_base_fee" class="kelana-label">Ongkir Dasar (Rp) *</label>
                            <input id="distance_base_fee" name="distance_base_fee" type="number" min="0"
                                value="{{ old('distance_base_fee', $storeSetting->distance_base_fee) }}" required class="kelana-input text-xs">
                        </div>
                        <div>
                            <label for="distance_per_km_fee" class="kelana-label">Tambahan per Km Berikutnya (Rp) *</label>
                            <input id="distance_per_km_fee" name="distance_per_km_fee" type="number" min="0"
                                value="{{ old('distance_per_km_fee', $storeSetting->distance_per_km_fee) }}" required class="kelana-input text-xs">
                        </div>
                        <div>
                            <label for="distance_max_km" class="kelana-label">Batas Jangkauan Maksimal (Km) *</label>
                            <input id="distance_max_km" name="distance_max_km" type="number" step="0.5" min="1"
                                value="{{ old('distance_max_km', $storeSetting->distance_max_km) }}" required class="kelana-input text-xs">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-kelana-primary px-5 py-2 text-xs">Simpan Metode &amp; Tarif Ongkir</button>
            </form>

            {{-- Daftar Zona Wilayah Pengiriman --}}
            <div class="space-y-4 rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs lg:col-span-5">
                <h2 class="text-sm font-bold text-[#52311a]">Daftar Zona Pengiriman</h2>

                <form method="POST" action="{{ route('admin.settings.zones.store') }}" class="space-y-2.5 rounded-xl bg-[#fdfbf6] p-3.5">
                    @csrf
                    <input type="text" name="name" required placeholder="Nama Zona (misal: Sidoarjo Barat)" class="kelana-input py-1.5 text-xs">
                    <input type="text" name="area_description" placeholder="Kecamatan cakupan..." class="kelana-input py-1.5 text-xs">
                    <input type="number" min="0" name="fee" required placeholder="Ongkir (Rp)" class="kelana-input py-1.5 text-xs">
                    <button type="submit" class="btn-kelana-primary w-full py-1.5 text-xs">+ Tambah Zona Ongkir</button>
                </form>

                <div class="space-y-2">
                    @foreach($shippingZones as $zone)
                        <div class="flex items-start justify-between gap-2 rounded-xl border border-[#efe7dc] px-3 py-2.5 text-xs">
                            <div>
                                <p class="font-bold text-[#28211c]">{{ $zone->name }} — Rp {{ number_format($zone->fee, 0, ',', '.') }}</p>
                                <p class="text-[11px] text-[#796b62]">{{ $zone->area_description }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.settings.zones.destroy', $zone) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[11px] font-bold text-red-700 hover:underline">Hapus</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 7. Lokasi Pengambilan (Pickup) --}}
        <div class="rounded-2xl border border-[#e8dfd3] bg-white p-5 shadow-xs space-y-5">
            <div>
                <h2 class="text-sm font-bold text-[#52311a]">7. Lokasi Pengambilan Mandiri (Pickup · Gratis Ongkir)</h2>
                <p class="mt-1 text-xs text-[#796b62]">Tambahkan, ubah, atau nonaktifkan titik lokasi pickup yang muncul saat checkout.</p>
            </div>

            <form method="POST" action="{{ route('admin.settings.pickups.store') }}" class="grid gap-3 rounded-xl bg-[#fdfbf6] p-4 sm:grid-cols-12">
                @csrf
                <div class="sm:col-span-3">
                    <input type="text" name="name" required placeholder="Nama Titik Pickup *" class="kelana-input py-2 text-xs">
                </div>
                <div class="sm:col-span-4">
                    <input type="text" name="address" required placeholder="Alamat Lengkap *" class="kelana-input py-2 text-xs">
                </div>
                <div class="sm:col-span-3">
                    <input type="text" name="operating_info" placeholder="Info Jam / Catatan" class="kelana-input py-2 text-xs">
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="btn-kelana-primary w-full py-2 text-xs">+ Tambah Pickup</button>
                </div>
            </form>

            <div class="grid gap-4 md:grid-cols-3">
                @foreach($pickupLocations as $pickup)
                    <div class="rounded-xl border border-[#efe7dc] p-4 text-xs space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold {{ $pickup->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">
                                {{ $pickup->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('admin.settings.pickups.update', $pickup) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="toggle_only" value="1">
                                    <button type="submit" class="text-[11px] font-bold text-[#52311a] hover:underline">
                                        {{ $pickup->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.settings.pickups.destroy', $pickup) }}"
                                    onsubmit="return confirm('Hapus lokasi pickup {{ $pickup->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[11px] font-bold text-red-700 hover:underline">Hapus</button>
                                </form>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.settings.pickups.update', $pickup) }}" class="space-y-2">
                            @csrf
                            @method('PUT')
                            <input type="text" name="name" value="{{ $pickup->name }}" required class="kelana-input py-1.5 text-xs font-bold">
                            <textarea name="address" rows="2" required class="kelana-input py-1.5 text-xs">{{ $pickup->address }}</textarea>
                            <input type="text" name="operating_info" value="{{ $pickup->operating_info }}" class="kelana-input py-1.5 text-xs">
                            <input type="text" name="maps_url" value="{{ $pickup->maps_url }}" placeholder="URL Google Maps" class="kelana-input py-1.5 text-xs">
                            <button type="submit" class="btn-kelana-outline w-full py-1.5 text-[11px]">Simpan Perubahan</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

