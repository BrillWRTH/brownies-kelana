<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Holiday;
use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderLog;
use App\Models\Payment;
use App\Models\PickupLocation;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\ShippingZone;
use App\Models\StoreSetting;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Mengisi data awal realistis UMKM Brownies Kelana untuk kebutuhan demonstrasi.
     */
    public function run(): void
    {
        // 1. Buat akun Admin dan akun contoh Pelanggan
        $admin = User::create([
            'customer_code' => null,
            'name' => 'Admin Kelana',
            'email' => 'admin@kelana.test',
            'phone' => '081231858544',
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $sinta = User::create([
            'customer_code' => 'CUS-0046',
            'name' => 'Sinta Maharani',
            'email' => 'sinta@example.com',
            'phone' => '081234567890',
            'secondary_phone' => '081398765432',
            'saved_address' => 'Jl. Gayungsari Barat No. 18, Gayungan, Surabaya, Jawa Timur.',
            'saved_address_detail' => 'Rumah nomor 18, pagar putih. Mohon hubungi penerima saat tiba.',
            'saved_latitude' => -7.3318500,
            'saved_longitude' => 112.7244100,
            'role' => 'customer',
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $hilmip = User::create([
            'customer_code' => 'CUS-0043',
            'name' => 'Hilmip',
            'email' => 'hilmip@example.com',
            'phone' => '081234567891',
            'saved_address' => 'Jl. Ketintang Baru No. 12, Gayungan, Surabaya.',
            'role' => 'customer',
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $nabila = User::create([
            'customer_code' => 'CUS-0045',
            'name' => 'Nabila Putri',
            'email' => 'nabila@example.com',
            'phone' => '081245678901',
            'saved_address' => 'Jl. Ketintang Madya No. 25, Karah, Kec. Jambangan, Surabaya.',
            'role' => 'customer',
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $sari = User::create([
            'customer_code' => 'CUS-0044',
            'name' => 'Sari Wulandari',
            'email' => 'sari@example.com',
            'phone' => '081356789012',
            'saved_address' => 'Jl. Jemursari Raya No. 40, Wonocolo, Surabaya.',
            'role' => 'customer',
            'is_active' => true,
            'email_verified_at' => null,
            'password' => Hash::make('password'),
        ]);

        // 2. Pengaturan Toko, Operasional, Kuota & Ongkir
        StoreSetting::create([
            'store_name' => 'Brownies Kelana',
            'tagline' => 'DIBUAT UNTUK DINIKMATI',
            'about_story' => 'Berdiri sejak tahun 2018, Brownies Kelana hadir untuk menemani setiap momen spesial Anda dengan aneka kue yang lezat dan berkualitas. Kami percaya bahwa setiap gigitan memiliki cerita dan setiap hidangan manis dapat menghadirkan kebahagiaan.',
            'whatsapp_number' => '+62 812-3185-8544',
            'email' => 'brownieskelana@gmail.com',
            'instagram_url' => 'https://www.instagram.com/brownieskelana',
            'e_catalog_url' => '/produk',
            'store_address' => 'Grand Royal Regency, Cluster Flamboyan E3-16, Jl Gadung, Wage, Taman, Sidoarjo.',
            'store_latitude' => -7.3728210,
            'store_longitude' => 112.7196810,
            'google_maps_url' => 'https://maps.app.goo.gl/R6A3crmefCNtaakAA',
            'preorder_min_days' => 1,
            'daily_quota' => 50,
            'shipping_method' => 'flat',
            'flat_shipping_fee' => 15000,
            'distance_base_km' => 3.00,
            'distance_base_fee' => 10000,
            'distance_per_km_fee' => 2500,
            'distance_max_km' => 25.00,
            'payment_timeout_minutes' => 30,
            'auto_cancel_status' => 'kedaluwarsa',
            'operating_hours' => [
                'monday' => ['label' => 'Senin', 'is_open' => true, 'open' => '09:00', 'close' => '16:00'],
                'tuesday' => ['label' => 'Selasa', 'is_open' => true, 'open' => '09:00', 'close' => '16:00'],
                'wednesday' => ['label' => 'Rabu', 'is_open' => false, 'open' => null, 'close' => null],
                'thursday' => ['label' => 'Kamis', 'is_open' => false, 'open' => null, 'close' => null],
                'friday' => ['label' => 'Jumat', 'is_open' => true, 'open' => '08:00', 'close' => '16:00'],
                'saturday' => ['label' => 'Sabtu', 'is_open' => true, 'open' => '08:00', 'close' => '16:00'],
                'sunday' => ['label' => 'Minggu', 'is_open' => false, 'open' => null, 'close' => null],
            ],
            'delivery_slots' => [
                '09.00-11.00 WIB',
                '13.00-15.00 WIB',
            ],
        ]);

        // 3. Lokasi Pickup Resmi
        $pickupGrandRoyal = PickupLocation::create([
            'name' => 'Grand Royal Regency',
            'address' => 'Grand Royal Regency, Cluster Flamboyan E3-16, Jl Gadung, Wage, Taman, Sidoarjo.',
            'maps_url' => 'https://maps.app.goo.gl/R6A3crmefCNtaakAA',
            'operating_info' => 'Sesuai jam operasional · Gratis ongkir',
            'latitude' => -7.3728210,
            'longitude' => 112.7196810,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        PickupLocation::create([
            'name' => 'Bank bjb Kanwil V',
            'address' => 'Bank bjb Kanwil V (Sebelah hotel Deka) Jl. Mayjen HR. Muhammad No.22, Surabaya',
            'maps_url' => 'https://g.co/kgs/6Zqoaib',
            'operating_info' => 'Sesuai jadwal pengambilan · Gratis ongkir',
            'latitude' => -7.2852410,
            'longitude' => 112.7014790,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $pickupDarmo = PickupLocation::create([
            'name' => 'Darmo',
            'address' => 'Jl. Raya Darmo No.112, Darmo, Kec. Wonokromo, Surabaya.',
            'maps_url' => 'https://g.co/kgs/NdgkvM',
            'operating_info' => 'Sesuai jadwal pengambilan · Gratis ongkir',
            'latitude' => -7.2886250,
            'longitude' => 112.7385800,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // 4. Zona Ongkir Wilayah
        ShippingZone::create([
            'name' => 'Taman, Wage & Sidoarjo Utara',
            'area_description' => 'Kecamatan Taman, Waru, Sedati, Sukodono',
            'fee' => 10000,
            'is_active' => true,
        ]);
        ShippingZone::create([
            'name' => 'Sidoarjo Kota & Surabaya Selatan',
            'area_description' => 'Gayungan, Jambangan, Wonocolo, Rungkut, Buduran',
            'fee' => 15000,
            'is_active' => true,
        ]);
        ShippingZone::create([
            'name' => 'Surabaya Pusat & Surabaya Barat',
            'area_description' => 'Tegalsari, Genteng, Gubeng, Dukuh Pakis, Wiyung',
            'fee' => 20000,
            'is_active' => true,
        ]);
        ShippingZone::create([
            'name' => 'Surabaya Timur & Surabaya Utara',
            'area_description' => 'Mulyorejo, Sukolilo, Kenjeran, Krembangan',
            'fee' => 25000,
            'is_active' => true,
        ]);

        // 5. Hari Libur Nasional
        Holiday::create(['holiday_date' => '2026-12-25', 'name' => 'Hari Raya Natal', 'is_active' => true]);
        Holiday::create(['holiday_date' => '2027-01-01', 'name' => 'Tahun Baru Masehi 2027', 'is_active' => true]);

        // 6. Kategori Produk
        $catBrownies = Category::create(['name' => 'Brownies', 'slug' => 'brownies', 'description' => 'Aneka brownies panggang premium kaya cokelat.', 'image' => 'images/kelana/prod-brownies-panggang.png', 'sort_order' => 1]);
        $catBolu = Category::create(['name' => 'Bolu', 'slug' => 'bolu', 'description' => 'Bolu klasik lembut untuk camilan keluarga.', 'image' => 'images/kelana/prod-bolu-pisang.png', 'sort_order' => 2]);
        $catBrookies = Category::create(['name' => 'Brookies', 'slug' => 'brookies', 'description' => 'Perpaduan brownies fudgy dan cookie renyah.', 'image' => 'images/kelana/prod-brookies-stack.png', 'sort_order' => 3]);
        $catCake = Category::create(['name' => 'Cake', 'slug' => 'cake', 'description' => 'Basque burnt cheesecake dan signature cake.', 'image' => 'images/kelana/prod-cheesecake.png', 'sort_order' => 4]);
        $catDimsum = Category::create(['name' => 'Dimsum', 'slug' => 'dimsum', 'description' => 'Sajian gurih pilihan dan jajanan K3Mart.', 'image' => 'images/kelana/prod-dimsum-mentai.png', 'sort_order' => 5]);

        // 7. Produk & Varian & Galeri
        $productsData = [
            [
                'category' => $catBrownies,
                'code' => 'PRD-001',
                'name' => 'Brownies Original',
                'slug' => 'brownies-original',
                'tagline' => 'Pilihan terbaik yang tak pernah salah!',
                'description' => 'Brownies panggang dengan rasa cokelat yang kaya dan tekstur lembut fudgy. Pilihan manis untuk dinikmati sendiri maupun bersama orang terdekat.',
                'allergen' => 'Susu, telur, gandum (gluten), dark chocolate',
                'base_price' => 120000,
                'available' => true,
                'featured' => true,
                'k3mart' => false,
                'weekend' => false,
                'custom_note' => true,
                'variants' => [['Reguler', 120000], ['Premium', 135000]],
                'images' => ['images/kelana/prod-brownies-panggang.png', 'images/kelana/hero-brownies.png', 'images/kelana/prod-brownies-cup.png'],
            ],
            [
                'category' => $catBrownies,
                'code' => 'PRD-002',
                'name' => 'Fudgy Brownies Shiny Crust',
                'slug' => 'fudgy-brownies-shiny-crust',
                'tagline' => 'Shiny crust renyah di luar, lumer di dalam',
                'description' => 'Brownies panggang signature dengan permukaan shiny crust tipis mengkilap dan isian cokelat pekat berlapis topping almond dan choco chip.',
                'allergen' => 'Susu, telur, gandum (gluten), kacang almond',
                'base_price' => 85000,
                'available' => true,
                'featured' => true,
                'k3mart' => false,
                'weekend' => false,
                'custom_note' => true,
                'variants' => [['Reguler 20x10', 85000], ['Premium 20x20', 145000]],
                'images' => ['images/kelana/hero-brownies.png', 'images/kelana/auth-brownies.png'],
            ],
            [
                'category' => $catBrownies,
                'code' => 'PRD-003',
                'name' => 'Brownies Crispy Cup',
                'slug' => 'brownies-crispy-cup',
                'tagline' => 'Praktis dinikmati kapan saja',
                'description' => 'Potongan brownies cokelat pekat dalam kemasan cup praktis dengan taburan cokelat parut, pas untuk teman minum kopi dan hadiah.',
                'allergen' => 'Susu, telur, gandum (gluten)',
                'base_price' => 40000,
                'available' => true,
                'featured' => false,
                'k3mart' => false,
                'weekend' => false,
                'custom_note' => false,
                'variants' => [['Single Cup', 40000], ['Bundle 3 Cup', 110000]],
                'images' => ['images/kelana/prod-brownies-cup.png'],
            ],
            [
                'category' => $catBolu,
                'code' => 'PRD-004',
                'name' => 'Bolu Pisang Isi Coklat',
                'slug' => 'bolu-pisang-isi-coklat',
                'tagline' => 'Harum pisang matang berpadu isian cokelat leleh',
                'description' => 'Bolu panggang beraroma pisang asli dengan tekstur lembut dan isian cokelat lumer di bagian tengah.',
                'allergen' => 'Telur, gandum (gluten), susu',
                'base_price' => 85000,
                'available' => true,
                'featured' => true,
                'k3mart' => false,
                'weekend' => false,
                'custom_note' => false,
                'variants' => [['Reguler', 85000], ['Premium', 105000]],
                'images' => ['images/kelana/prod-bolu-pisang.png'],
            ],
            [
                'category' => $catBolu,
                'code' => 'PRD-005',
                'name' => 'Bolu Marmer Klasik',
                'slug' => 'bolu-marmer-klasik',
                'tagline' => 'Tekstur empuk dengan motif marmer cokelat butter',
                'description' => 'Bolu jadul resep rumahan dengan mentega pilihan dan motif cokelat klasik yang selalu jadi favorit keluarga.',
                'allergen' => 'Telur, gandum (gluten), produk susu',
                'base_price' => 95000,
                'available' => true,
                'featured' => false,
                'k3mart' => false,
                'weekend' => false,
                'custom_note' => true,
                'variants' => [['Loyang 20cm', 95000], ['Loyang 24cm', 125000]],
                'images' => ['images/kelana/prod-bolu-marmer.png'],
            ],
            [
                'category' => $catBrookies,
                'code' => 'PRD-006',
                'name' => 'Brookies Choco Chunk',
                'slug' => 'brookies-choco-chunk',
                'tagline' => 'Renyah di tepi, fudgy di tengah',
                'description' => 'Kombinasi sempurna adonan soft cookie mentega dan fudgy dark chocolate dengan potongan choco chunk melimpah.',
                'allergen' => 'Susu, telur, gandum (gluten), cokelat',
                'base_price' => 75000,
                'available' => true,
                'featured' => true,
                'k3mart' => false,
                'weekend' => false,
                'custom_note' => false,
                'variants' => [['Box Isi 4', 75000], ['Box Isi 8', 140000]],
                'images' => ['images/kelana/prod-brookies-stack.png', 'images/kelana/prod-brookies-hand.png'],
            ],
            [
                'category' => $catCake,
                'code' => 'PRD-007',
                'name' => 'Basque Burnt Cheesecake',
                'slug' => 'basque-burnt-cheesecake',
                'tagline' => 'Pilihan terbaik yang tak pernah salah!',
                'description' => 'Premium cream cheese lembut yang meleleh di lidah dengan lapisan atas karamel eksotis yang harum dan beraroma mewah.',
                'allergen' => 'Keju (susu), telur, krim',
                'base_price' => 44000,
                'available' => true,
                'featured' => true,
                'k3mart' => false,
                'weekend' => false,
                'custom_note' => false,
                'variants' => [['Slice / Personal 12cm', 44000], ['Whole 18cm', 165000]],
                'images' => ['images/kelana/prod-cheesecake.png', 'images/kelana/prod-matcha-cheesecake.png'],
            ],
            [
                'category' => $catCake,
                'code' => 'PRD-008',
                'name' => 'Cake Cokelat Signature',
                'slug' => 'cake-cokelat-signature',
                'tagline' => 'Kue cokelat lembut untuk perayaan spesial',
                'description' => 'Kue cokelat lembut dengan tekstur padat yang pas dan ganache cokelat mengkilap. Cocok untuk pesanan harian, ulang tahun, atau kirim ke pelanggan.',
                'allergen' => 'Susu, telur, gandum (gluten)',
                'base_price' => 120000,
                'available' => true,
                'featured' => false,
                'k3mart' => false,
                'weekend' => false,
                'custom_note' => true,
                'variants' => [['Reguler', 120000], ['Premium', 145000]],
                'images' => ['images/kelana/prod-cake-coklat.png'],
            ],
            [
                'category' => $catCake,
                'code' => 'PRD-009',
                'name' => 'Matcha Burnt Cheesecake',
                'slug' => 'matcha-burnt-cheesecake',
                'tagline' => 'Perpaduan matcha Jepang dan cream cheese lembut',
                'description' => 'Basque burnt cheesecake varian ceremonial matcha dengan rasa seimbang antara manis gurih keju dan aroma teh hijau.',
                'allergen' => 'Susu, telur, krim',
                'base_price' => 48000,
                'available' => false,
                'featured' => false,
                'k3mart' => false,
                'weekend' => false,
                'custom_note' => false,
                'variants' => [['Personal 12cm', 48000], ['Whole 18cm', 175000]],
                'images' => ['images/kelana/prod-matcha-cheesecake.png'],
            ],
            [
                'category' => $catDimsum,
                'code' => 'PRD-010',
                'name' => 'Dimsum Ayam Mentai',
                'slug' => 'dimsum-ayam-mentai',
                'tagline' => 'Gurih lembut berbalut saus mentai bakar',
                'description' => 'Dimsum ayam udang padat dengan topping saus mentai creamy yang dibakar hangat menggunakan torch.',
                'allergen' => 'Udang, ayam, telur, kecap kedelai, wijen',
                'base_price' => 45000,
                'available' => true,
                'featured' => true,
                'k3mart' => false,
                'weekend' => false,
                'custom_note' => false,
                'variants' => [['Box 10 pcs', 45000], ['Party Tray 20 pcs', 85000]],
                'images' => ['images/kelana/prod-dimsum-mentai.png'],
            ],
            [
                'category' => $catDimsum,
                'code' => 'PRD-011',
                'name' => 'Sushi Crab Bake',
                'slug' => 'sushi-crab-bake',
                'tagline' => 'Sisi lain dari Kelana · Spesial K3Mart Corner',
                'description' => 'Nasi sushi panggang berlapis crabstick creamy mentai, nori crispy, dan tobiko. Tersedia eksklusif akhir pekan.',
                'allergen' => 'Ikan, kepiting, telur, kedelai, wijen',
                'base_price' => 42000,
                'available' => true,
                'featured' => false,
                'k3mart' => true,
                'weekend' => true,
                'custom_note' => false,
                'variants' => [['Personal Tray', 42000]],
                'images' => ['images/kelana/k3-sushi-crab-bake.png'],
            ],
            [
                'category' => $catBolu,
                'code' => 'PRD-012',
                'name' => 'Goguma Ppang',
                'slug' => 'goguma-ppang',
                'tagline' => 'Roti ubi ungu ala Korea lembut berlapis ubi manis',
                'description' => 'Roti mochi ubi ungu dengan isian pasta ubi cilembu lembut dan kulit tepung ubi ungu yang kenyal harum.',
                'allergen' => 'Gandum (gluten), susu, telur',
                'base_price' => 35000,
                'available' => true,
                'featured' => false,
                'k3mart' => true,
                'weekend' => true,
                'custom_note' => false,
                'variants' => [['Isi 2 pcs', 35000]],
                'images' => ['images/kelana/k3-goguma-ppang.png'],
            ],
            [
                'category' => $catCake,
                'code' => 'PRD-013',
                'name' => 'Donut Mochi',
                'slug' => 'donut-mochi',
                'tagline' => 'Kenyal lembut aneka glaze favorit di K3Mart',
                'description' => 'Donat tekstur mochi kenyal dengan pilihan glaze cokelat klasik, matcha, dan vanila.',
                'allergen' => 'Gandum (gluten), susu, telur',
                'base_price' => 48000,
                'available' => true,
                'featured' => false,
                'k3mart' => true,
                'weekend' => true,
                'custom_note' => false,
                'variants' => [['Box Isi 4', 48000]],
                'images' => ['images/kelana/k3-donut-mochi.png'],
            ],
        ];

        $productMap = [];
        foreach ($productsData as $item) {
            $product = Product::create([
                'category_id' => $item['category']->id,
                'product_code' => $item['code'],
                'name' => $item['name'],
                'slug' => $item['slug'],
                'tagline' => $item['tagline'],
                'description' => $item['description'],
                'allergen_info' => $item['allergen'],
                'base_price' => $item['base_price'],
                'is_available' => $item['available'],
                'is_active' => true,
                'is_featured' => $item['featured'],
                'is_k3mart' => $item['k3mart'],
                'is_weekend_only' => $item['weekend'],
                'allows_custom_note' => $item['custom_note'],
                'custom_note_max_chars' => 30,
                'thumbnail' => $item['images'][0],
            ]);

            foreach ($item['variants'] as $idx => [$vName, $vPrice]) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'name' => $vName,
                    'price' => $vPrice,
                    'is_available' => $item['available'],
                    'sort_order' => $idx + 1,
                ]);
            }

            foreach ($item['images'] as $idx => $imgPath) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $imgPath,
                    'is_primary' => $idx === 0,
                    'sort_order' => $idx + 1,
                ]);
            }

            $productMap[$item['code']] = $product;
        }

        // 8. Template Notifikasi Pesanan & Sistem
        $templates = [
            ['pesanan', 'menunggu_pembayaran', 'Menunggu Pembayaran', 'Halo {nama_pelanggan}, pesanan {nomor_pesanan} telah dibuat. Selesaikan pembayaran Midtrans sebelum batas waktu agar segera diproses.', ['dashboard', 'whatsapp']],
            ['pesanan', 'lunas', 'Pembayaran Lunas', 'Halo {nama_pelanggan}, pembayaran untuk pesanan {nomor_pesanan} telah diterima lunas. Pesanan Anda segera kami siapkan untuk jadwal {jadwal}.', ['dashboard', 'whatsapp']],
            ['pesanan', 'diproses', 'Pesanan Diproses', 'Halo {nama_pelanggan}, pesanan {nomor_pesanan} sedang dalam tahap pembuatan oleh dapur Brownies Kelana.', ['dashboard', 'whatsapp']],
            ['pesanan', 'dikemas', 'Pesanan Dikemas', 'Halo {nama_pelanggan}, pesanan {nomor_pesanan} sudah dikemas rapi dan siap dikirim/diambil pada jadwal {jadwal}.', ['dashboard', 'whatsapp']],
            ['pesanan', 'dalam_pengiriman', 'Dalam Pengiriman', 'Halo {nama_pelanggan}, pesanan {nomor_pesanan} sedang dalam perjalanan menuju alamat tujuan. Mohon pastikan penerima dapat dihubungi.', ['dashboard', 'whatsapp']],
            ['pesanan', 'siap_diambil', 'Siap Diambil', 'Halo {nama_pelanggan}, pesanan {nomor_pesanan} sudah siap diambil di lokasi pickup yang Anda pilih. Tunjukkan nomor pesanan kepada petugas.', ['dashboard', 'whatsapp']],
            ['pesanan', 'sampai_tujuan', 'Sampai Tujuan', 'Terima kasih {nama_pelanggan}! Pesanan {nomor_pesanan} telah sampai tujuan. Jangan lupa bagikan ulasan Anda di dashboard.', ['dashboard', 'whatsapp']],
            ['pesanan', 'sudah_diambil', 'Sudah Diambil', 'Terima kasih {nama_pelanggan}! Pesanan {nomor_pesanan} telah diambil. Semoga momen manis Anda selalu berkesan.', ['dashboard', 'whatsapp']],
            ['pesanan', 'kedaluwarsa', 'Pesanan Kedaluwarsa', 'Pesanan {nomor_pesanan} dibatalkan otomatis karena melewati batas waktu pembayaran 30 menit.', ['dashboard']],
            ['pesanan', 'dibatalkan', 'Pesanan Dibatalkan', 'Halo {nama_pelanggan}, pesanan {nomor_pesanan} telah dibatalkan. Alasan: {alasan_batal}.', ['dashboard', 'whatsapp']],
            ['sistem', 'jadwal_libur', 'Informasi Jadwal Operasional', 'Halo pelanggan setia Brownies Kelana, kami informasikan penyesuaian jadwal operasional layanan toko. Terima kasih atas pengertiannya.', ['dashboard']],
            ['sistem', 'bantuan_akun', 'Bantuan Akun Pelanggan', 'Halo {nama_pelanggan}, tim Admin Brownies Kelana siap membantu pemeriksaan atau pemulihan akun Anda.', ['whatsapp']],
        ];

        foreach ($templates as [$cat, $key, $title, $msg, $channels]) {
            NotificationTemplate::create([
                'category' => $cat,
                'trigger_key' => $key,
                'title' => $title,
                'message_template' => $msg,
                'channels' => $channels,
                'is_active' => true,
            ]);
        }

        // 9. Pesanan Contoh untuk Demonstrasi Semua Status (BC-1040 s/d BC-1045)
        $prodOriginal = $productMap['PRD-001'];
        $varOriginalReg = $prodOriginal->variants()->first();
        $prodCake = $productMap['PRD-008'];
        $varCakeReg = $prodCake->variants()->first();
        $prodDimsum = $productMap['PRD-010'];
        $varDimsum10 = $prodDimsum->variants()->first();

        $tomorrow = now()->addDays(2)->toDateString();

        // BC-1040: Pending Payment (Aktif di Dashboard Sinta)
        $orderPending = Order::create([
            'order_number' => 'BC-1040',
            'user_id' => $sinta->id,
            'source' => 'website',
            'customer_name' => $sinta->name,
            'customer_email' => $sinta->email,
            'customer_phone' => $sinta->phone,
            'delivery_type' => 'delivery',
            'recipient_name' => 'Dina Maharani',
            'recipient_phone' => '081344556677',
            'recipient_secondary_phone' => $sinta->phone,
            'delivery_address' => 'Jl. Ketintang Madya No. 25, Karah, Kec. Jambangan, Surabaya.',
            'delivery_address_detail' => 'Rumah pagar cokelat, depan taman. Hubungi HP kedua jika penerima tidak dapat dihubungi.',
            'delivery_latitude' => -7.3198200,
            'delivery_longitude' => 112.7165400,
            'shipping_method_snapshot' => 'flat',
            'maps_url' => 'https://maps.google.com/?q=-7.3198200,112.7165400',
            'scheduled_date' => $tomorrow,
            'scheduled_time_slot' => '09.00-11.00 WIB',
            'subtotal' => 120000,
            'shipping_fee' => 15000,
            'total_amount' => 135000,
            'customer_note' => 'Tanpa kartu ucapan. Terima kasih.',
            'order_status' => 'menunggu_pembayaran',
            'payment_status' => 'belum_dibayar',
            'payment_deadline_at' => now()->addMinutes(28),
        ]);
        OrderItem::create([
            'order_id' => $orderPending->id,
            'product_id' => $prodOriginal->id,
            'product_variant_id' => $varOriginalReg->id,
            'product_name' => $prodOriginal->name,
            'variant_name' => $varOriginalReg->name,
            'unit_price' => 120000,
            'quantity' => 1,
            'subtotal' => 120000,
        ]);
        Payment::create([
            'order_id' => $orderPending->id,
            'gateway' => 'midtrans',
            'payment_method' => 'midtrans_snap',
            'channel_label' => 'Midtrans Snap',
            'midtrans_order_id' => 'BC-1040-1001',
            'amount' => 135000,
            'status' => 'pending',
            'expires_at' => $orderPending->payment_deadline_at,
        ]);
        OrderLog::create([
            'order_id' => $orderPending->id,
            'user_id' => $sinta->id,
            'actor_name' => $sinta->name,
            'event_type' => 'created',
            'title' => 'Pesanan Dibuat',
            'description' => 'Pesanan dibuat melalui checkout website dan menunggu pembayaran Midtrans.',
        ]);

        // BC-1041: Diproses (Dimsum Sinta)
        $orderDiproses = Order::create([
            'order_number' => 'BC-1041',
            'user_id' => $sinta->id,
            'source' => 'website',
            'customer_name' => $sinta->name,
            'customer_email' => $sinta->email,
            'customer_phone' => $sinta->phone,
            'delivery_type' => 'delivery',
            'recipient_name' => $sinta->name,
            'recipient_phone' => $sinta->phone,
            'delivery_address' => $sinta->saved_address,
            'delivery_address_detail' => $sinta->saved_address_detail,
            'delivery_latitude' => -7.3318500,
            'delivery_longitude' => 112.7244100,
            'shipping_method_snapshot' => 'flat',
            'maps_url' => 'https://maps.google.com/?q=-7.3318500,112.7244100',
            'scheduled_date' => $tomorrow,
            'scheduled_time_slot' => '09.00-11.00 WIB',
            'subtotal' => 45000,
            'shipping_fee' => 15000,
            'total_amount' => 60000,
            'order_status' => 'diproses',
            'payment_status' => 'lunas',
            'paid_at' => now()->subHours(3),
        ]);
        OrderItem::create([
            'order_id' => $orderDiproses->id,
            'product_id' => $prodDimsum->id,
            'product_variant_id' => $varDimsum10->id,
            'product_name' => $prodDimsum->name,
            'variant_name' => $varDimsum10->name,
            'unit_price' => 45000,
            'quantity' => 1,
            'subtotal' => 45000,
        ]);
        Payment::create([
            'order_id' => $orderDiproses->id,
            'gateway' => 'midtrans',
            'payment_method' => 'qris',
            'channel_label' => 'Midtrans · QRIS',
            'midtrans_order_id' => 'BC-1041-1002',
            'transaction_id' => 'MT-QRIS-1041',
            'amount' => 60000,
            'status' => 'settlement',
            'paid_at' => now()->subHours(3),
        ]);
        OrderLog::create(['order_id' => $orderDiproses->id, 'user_id' => $sinta->id, 'actor_name' => $sinta->name, 'event_type' => 'created', 'title' => 'Pesanan Dibuat', 'description' => 'Pembayaran diterima melalui Midtrans QRIS.']);
        OrderLog::create(['order_id' => $orderDiproses->id, 'user_id' => $admin->id, 'actor_name' => $admin->name, 'event_type' => 'status_changed', 'title' => 'Pesanan Diproses', 'description' => 'Pesanan dimsum mentai sedang dalam tahap proses pembuatan.']);

        // BC-1042: Sampai Tujuan + Review (Sinta)
        $orderDoneSinta = Order::create([
            'order_number' => 'BC-1042',
            'user_id' => $sinta->id,
            'source' => 'website',
            'customer_name' => $sinta->name,
            'customer_email' => $sinta->email,
            'customer_phone' => $sinta->phone,
            'delivery_type' => 'delivery',
            'recipient_name' => 'Dina Maharani',
            'recipient_phone' => '081344556677',
            'delivery_address' => 'Jl. Ketintang Madya No. 25, Karah, Kec. Jambangan, Surabaya.',
            'delivery_address_detail' => 'Rumah pagar cokelat, depan taman.',
            'shipping_method_snapshot' => 'flat',
            'scheduled_date' => now()->subDays(2)->toDateString(),
            'scheduled_time_slot' => '09.00-11.00 WIB',
            'subtotal' => 120000,
            'shipping_fee' => 15000,
            'total_amount' => 135000,
            'order_status' => 'sampai_tujuan',
            'payment_status' => 'lunas',
            'paid_at' => now()->subDays(3),
            'courier_name' => 'Arif Setiawan',
            'courier_phone' => '081288990011',
            'courier_vehicle_plate' => 'L 2318 BQ',
            'delivery_proof_path' => 'images/kelana/hero-brownies.png',
            'received_by' => 'Dina Maharani',
            'delivered_at' => now()->subDays(2)->setTime(10, 30),
        ]);
        OrderItem::create([
            'order_id' => $orderDoneSinta->id,
            'product_id' => $prodOriginal->id,
            'product_variant_id' => $varOriginalReg->id,
            'product_name' => $prodOriginal->name,
            'variant_name' => $varOriginalReg->name,
            'unit_price' => 120000,
            'quantity' => 1,
            'subtotal' => 120000,
        ]);
        Payment::create([
            'order_id' => $orderDoneSinta->id,
            'gateway' => 'midtrans',
            'payment_method' => 'bca_va',
            'channel_label' => 'Midtrans · BCA Virtual Account',
            'midtrans_order_id' => 'BC-1042-1003',
            'va_bank' => 'bca',
            'transaction_id' => 'MT-BCA-1042',
            'amount' => 135000,
            'status' => 'settlement',
            'paid_at' => now()->subDays(3),
        ]);
        OrderLog::create(['order_id' => $orderDoneSinta->id, 'user_id' => $admin->id, 'actor_name' => $admin->name, 'event_type' => 'status_changed', 'title' => 'Sampai Tujuan', 'description' => 'Diterima oleh Dina Maharani via kurir Arif Setiawan (L 2318 BQ).']);

        // BC-1043: Sudah Diambil Pickup (Hilmip + Review Pending)
        $orderHilmip = Order::create([
            'order_number' => 'BC-1043',
            'user_id' => $hilmip->id,
            'source' => 'website',
            'customer_name' => $hilmip->name,
            'customer_email' => $hilmip->email,
            'customer_phone' => $hilmip->phone,
            'delivery_type' => 'pickup',
            'pickup_location_id' => $pickupGrandRoyal->id,
            'recipient_name' => $hilmip->name,
            'recipient_phone' => $hilmip->phone,
            'shipping_method_snapshot' => 'pickup',
            'maps_url' => $pickupGrandRoyal->maps_url,
            'scheduled_date' => now()->subDay()->toDateString(),
            'scheduled_time_slot' => '13.00-15.00 WIB',
            'subtotal' => 120000,
            'shipping_fee' => 0,
            'total_amount' => 120000,
            'customer_note' => 'Tulisan kue: Selamat Ulang Tahun ke-21',
            'order_status' => 'sudah_diambil',
            'payment_status' => 'lunas',
            'paid_at' => now()->subDays(2),
            'received_by' => 'Hilmip',
            'delivered_at' => now()->subDay()->setTime(15, 0),
        ]);
        OrderItem::create([
            'order_id' => $orderHilmip->id,
            'product_id' => $prodCake->id,
            'product_variant_id' => $varCakeReg->id,
            'product_name' => $prodCake->name,
            'variant_name' => $varCakeReg->name,
            'unit_price' => 120000,
            'quantity' => 1,
            'subtotal' => 120000,
            'custom_note' => 'Selamat Ulang Tahun ke-21',
        ]);
        Payment::create([
            'order_id' => $orderHilmip->id,
            'gateway' => 'midtrans',
            'payment_method' => 'gopay',
            'channel_label' => 'Midtrans · GoPay',
            'midtrans_order_id' => 'BC-1043-1004',
            'transaction_id' => 'MT-GOPAY-1043',
            'amount' => 120000,
            'status' => 'settlement',
            'paid_at' => now()->subDays(2),
        ]);

        // BC-1044: WhatsApp Manual Order + Featured Home Review (Nabila Putri)
        $orderNabila = Order::create([
            'order_number' => 'BC-1044',
            'user_id' => $nabila->id,
            'source' => 'whatsapp',
            'customer_name' => $nabila->name,
            'customer_email' => $nabila->email,
            'customer_phone' => $nabila->phone,
            'delivery_type' => 'delivery',
            'recipient_name' => $nabila->name,
            'recipient_phone' => $nabila->phone,
            'delivery_address' => $nabila->saved_address,
            'shipping_method_snapshot' => 'flat',
            'scheduled_date' => now()->subDays(4)->toDateString(),
            'scheduled_time_slot' => '09.00-11.00 WIB',
            'subtotal' => 240000,
            'shipping_fee' => 15000,
            'total_amount' => 255000,
            'order_status' => 'sampai_tujuan',
            'payment_status' => 'lunas',
            'paid_at' => now()->subDays(5),
            'received_by' => 'Nabila Putri',
            'delivered_at' => now()->subDays(4)->setTime(10, 15),
        ]);
        OrderItem::create([
            'order_id' => $orderNabila->id,
            'product_id' => $prodOriginal->id,
            'product_variant_id' => $varOriginalReg->id,
            'product_name' => $prodOriginal->name,
            'variant_name' => $varOriginalReg->name,
            'unit_price' => 120000,
            'quantity' => 2,
            'subtotal' => 240000,
        ]);
        Payment::create([
            'order_id' => $orderNabila->id,
            'gateway' => 'midtrans',
            'payment_method' => 'qris',
            'channel_label' => 'Midtrans · QRIS',
            'midtrans_order_id' => 'BC-1044-1005',
            'transaction_id' => 'MT-QRIS-1044',
            'amount' => 255000,
            'status' => 'settlement',
            'paid_at' => now()->subDays(5),
        ]);

        // BC-1045: Dibatalkan (Sinta)
        $orderBatal = Order::create([
            'order_number' => 'BC-1045',
            'user_id' => $sinta->id,
            'source' => 'website',
            'customer_name' => $sinta->name,
            'customer_email' => $sinta->email,
            'customer_phone' => $sinta->phone,
            'delivery_type' => 'pickup',
            'pickup_location_id' => $pickupDarmo->id,
            'recipient_name' => $sinta->name,
            'recipient_phone' => $sinta->phone,
            'shipping_method_snapshot' => 'pickup',
            'scheduled_date' => now()->subDays(6)->toDateString(),
            'scheduled_time_slot' => '13.00-15.00 WIB',
            'subtotal' => 120000,
            'shipping_fee' => 0,
            'total_amount' => 120000,
            'order_status' => 'dibatalkan',
            'payment_status' => 'dibatalkan',
            'cancelled_at' => now()->subDays(7),
            'cancelled_by' => 'customer',
            'cancellation_reason' => 'Ingin mengubah pesanan',
        ]);
        OrderItem::create([
            'order_id' => $orderBatal->id,
            'product_id' => $prodOriginal->id,
            'product_variant_id' => $varOriginalReg->id,
            'product_name' => $prodOriginal->name,
            'variant_name' => $varOriginalReg->name,
            'unit_price' => 120000,
            'quantity' => 1,
            'subtotal' => 120000,
        ]);

        // 10. Ulasan Pelanggan (Tampil di Landing Page & Antrean Moderasi)
        Review::create([
            'order_id' => $orderNabila->id,
            'user_id' => $nabila->id,
            'product_id' => $prodOriginal->id,
            'rating' => 5,
            'comment' => 'Rotinya bener-bener super empuk dan lembut banget! Pas digigit langsung lumer di mulut, bahkan setelah disimpan sampai besoknya pun teksturnya tetap gemoy dan gak bikin seret.',
            'moderation_status' => 'disetujui',
            'is_featured_home' => true,
            'admin_reply' => 'Terima kasih Kak Nabila! Senang sekali brownies kami menemani momen spesial kakak.',
            'admin_replied_at' => now()->subDays(3),
        ]);

        Review::create([
            'order_id' => $orderDoneSinta->id,
            'user_id' => $sinta->id,
            'product_id' => $prodOriginal->id,
            'rating' => 5,
            'comment' => 'Rasa manisnya pas banget, gak bikin enek! Isian cokelat dan toppingnya melimpah dari ujung ke ujung, selalu fresh from the oven.',
            'moderation_status' => 'disetujui',
            'is_featured_home' => true,
        ]);

        Review::create([
            'order_id' => $orderHilmip->id,
            'user_id' => $hilmip->id,
            'product_id' => $prodCake->id,
            'rating' => 5,
            'comment' => 'Cake lembut, tulisan ulang tahunnya rapi banget dan kemasan sangat aman sampai rumah!',
            'moderation_status' => 'menunggu_persetujuan',
            'is_featured_home' => false,
        ]);

        // 11. Notifikasi Dashboard Pelanggan & Inbox Admin
        UserNotification::create([
            'user_id' => $sinta->id,
            'order_id' => $orderDiproses->id,
            'audience' => 'customer',
            'category' => 'pesanan',
            'channel' => 'dashboard',
            'title' => 'Pesanan Diproses',
            'message' => 'Pesanan Dimsum Ayam Mentai (BC-1041) sedang dalam tahap proses pembuatan.',
            'delivery_status' => 'terkirim',
            'action_url' => route('orders.show', $orderDiproses->order_number),
        ]);

        UserNotification::create([
            'user_id' => $sinta->id,
            'order_id' => null,
            'audience' => 'customer',
            'category' => 'sistem',
            'channel' => 'dashboard',
            'title' => 'Informasi Penting',
            'message' => 'Pemesanan menerapkan sistem Pre-order H-1 dengan kuota maksimal 50 pesanan per hari. Terima kasih atas pengertiannya.',
            'delivery_status' => 'terkirim',
            'action_url' => route('orders.guide'),
        ]);

        UserNotification::create([
            'user_id' => null,
            'order_id' => $orderPending->id,
            'audience' => 'admin',
            'category' => 'pesanan',
            'channel' => 'dashboard',
            'title' => 'Pesanan Baru Masuk',
            'message' => 'BC-1040 · Sinta Maharani · Brownies Original × 1 · Rp 135.000 (Menunggu Pembayaran Midtrans).',
            'delivery_status' => 'terkirim',
            'action_url' => route('admin.orders.show', $orderPending),
        ]);

        UserNotification::create([
            'user_id' => null,
            'order_id' => $orderHilmip->id,
            'audience' => 'admin',
            'category' => 'pesanan',
            'channel' => 'dashboard',
            'title' => 'Ulasan Baru dari Pelanggan',
            'message' => 'Hilmip mengulas pesanan BC-1043 (Cake Cokelat Signature). Menunggu persetujuan moderasi.',
            'delivery_status' => 'terkirim',
            'action_url' => route('admin.reviews.index'),
        ]);
    }
}
