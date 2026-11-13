<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 1. Route Publik (Landing Page, Katalog Produk, & Halaman Informasi)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/tentang-kami', [HomeController::class, 'about'])->name('about');
Route::get('/cara-pemesanan', [HomeController::class, 'guide'])->name('orders.guide');
Route::get('/pengiriman-pengambilan', [HomeController::class, 'shippingInfo'])->name('shipping.info');
Route::get('/informasi-alergen', [HomeController::class, 'allergenInfo'])->name('allergen.info');
Route::get('/kebijakan-privasi', [HomeController::class, 'privacy'])->name('privacy');
Route::get('/syarat-ketentuan', [HomeController::class, 'terms'])->name('terms');

Route::get('/produk', [ProductController::class, 'index'])->name('products.index');
Route::get('/produk/{product:slug}', [ProductController::class, 'show'])->name('products.show');

// Keranjang belanja publik (mendukung tamu & pelanggan login)
Route::get('/keranjang', [CartController::class, 'index'])->name('cart.index');
Route::post('/keranjang/item', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/keranjang/item/{cartItem}', [CartController::class, 'update'])->name('cart.items.update');
Route::patch('/keranjang/catatan', [CartController::class, 'updateNote'])->name('cart.note.update');
Route::delete('/keranjang/item/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');

// Webhook Midtrans (tanpa CSRF & auth session)
Route::post('/midtrans/callback', [PaymentController::class, 'callback'])->name('midtrans.callback');

/*
|--------------------------------------------------------------------------
| 2. Route Autentikasi Pelanggan (Masuk, Daftar, Verifikasi, Reset Sandi)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function (): void {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:15,1')->name('login.attempt');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register.store');

    Route::get('/lupa-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/lupa-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:10,1')->name('password.email');
    Route::get('/email-reset-terkirim', [PasswordResetController::class, 'sentNotice'])->name('password.sent');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');
    Route::get('/reset-berhasil', [PasswordResetController::class, 'successNotice'])->name('password.success');
});

Route::post('/keluar', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/verifikasi-email', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::get('/verifikasi-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::get('/email-terverifikasi', [EmailVerificationController::class, 'verified'])
        ->name('verification.verified');
    Route::post('/verifikasi-email/kirim-ulang', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| 3. Route Pelanggan (Checkout, Pembayaran Midtrans, Dashboard & Pesanan)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/cek-ongkir', [CheckoutController::class, 'checkShipping'])->middleware('throttle:45,1')->name('checkout.check-shipping');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:15,1')->name('checkout.store');

    Route::get('/pembayaran/{order}', [PaymentController::class, 'show'])->name('payments.show');
    Route::match(['GET', 'POST'], '/pembayaran/{order}/cek-status', [PaymentController::class, 'checkStatus'])->middleware('throttle:20,1')->name('payments.check-status');
    Route::get('/pembayaran/{order}/status/{state}', [PaymentController::class, 'result'])->name('payments.result');

    Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/notifikasi', [CustomerDashboardController::class, 'notifications'])->name('notifications.index');
    Route::post('/notifikasi/baca-semua', [CustomerDashboardController::class, 'markAllNotificationsRead'])->name('notifications.read-all');
    Route::post('/notifikasi/{notification}/baca', [CustomerDashboardController::class, 'markNotificationRead'])->name('notifications.read');

    Route::get('/profil', [CustomerDashboardController::class, 'profile'])->name('profile.show');
    Route::get('/profil/edit', [CustomerDashboardController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profil', [CustomerDashboardController::class, 'updateProfile'])->name('profile.update');

    Route::get('/pesanan/{orderNumber}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/pesanan/{order}/batal', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/pesanan/{order}/ulasan', [OrderController::class, 'storeReview'])->name('orders.reviews.store');
});

/*
|--------------------------------------------------------------------------
| 4. Route Admin / Owner Panel (/admin)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:15,1')->name('login.attempt');

    Route::middleware(['auth', 'admin'])->group(function (): void {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/ekspor-pesanan', [AdminDashboardController::class, 'exportCsv'])->name('orders.export');

        // Pengelolaan Pesanan & Rekap Produksi
        Route::get('/rekap-produksi', [AdminOrderController::class, 'productionRecap'])->name('orders.recap');
        Route::get('/rekap-produksi/download', [AdminOrderController::class, 'downloadProductionRecap'])->name('orders.recap.download');
        Route::get('/orders/{order}/nota', [AdminOrderController::class, 'printReceipt'])->name('orders.receipt');
        Route::post('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::post('/orders/{order}/kurir', [AdminOrderController::class, 'updateCourier'])->name('orders.update-courier');
        Route::post('/orders/{order}/batal', [AdminOrderController::class, 'cancelOrder'])->name('orders.cancel');
        Route::post('/orders/{order}/tandai-lunas', [AdminOrderController::class, 'markManualPaid'])->name('orders.mark-paid');
        Route::resource('orders', AdminOrderController::class);

        // Pengelolaan Produk
        Route::post('/products/{product}/toggle-status', [AdminProductController::class, 'toggleStatus'])->name('products.toggle-status');
        Route::delete('/product-images/{image}', [AdminProductController::class, 'deleteImage'])->name('product-images.destroy');
        Route::resource('products', AdminProductController::class);

        // Moderasi Ulasan
        Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
        Route::get('/reviews/{review}', [AdminReviewController::class, 'show'])->name('reviews.show');
        Route::post('/reviews/{review}/setujui', [AdminReviewController::class, 'approve'])->name('reviews.approve');
        Route::post('/reviews/{review}/sembunyikan', [AdminReviewController::class, 'hide'])->name('reviews.hide');
        Route::post('/reviews/{review}/balas', [AdminReviewController::class, 'reply'])->name('reviews.reply');
        Route::post('/reviews/{review}/home', [AdminReviewController::class, 'toggleHome'])->name('reviews.toggle-home');

        // Manajemen Pelanggan
        Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');
        Route::post('/customers/{customer}/toggle-aktif', [AdminCustomerController::class, 'toggleActive'])->name('customers.toggle-active');
        Route::post('/customers/{customer}/pemulihan', [AdminCustomerController::class, 'logRecovery'])->name('customers.recovery');

        // Notifikasi Admin (Inbox, Template, Kirim Khusus, Riwayat)
        Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/baca-semua', [AdminNotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::get('/notifications/template', [AdminNotificationController::class, 'templates'])->name('notifications.templates');
        Route::put('/notifications/template/{template}', [AdminNotificationController::class, 'updateTemplate'])->name('notifications.templates.update');
        Route::get('/notifications/kirim', [AdminNotificationController::class, 'create'])->name('notifications.create');
        Route::post('/notifications/kirim', [AdminNotificationController::class, 'store'])->name('notifications.store');
        Route::get('/notifications/riwayat', [AdminNotificationController::class, 'history'])->name('notifications.history');

        // Pengaturan Toko & Operasional
        Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
        Route::put('/settings/toko', [AdminSettingController::class, 'updateStore'])->name('settings.store.update');
        Route::put('/settings/jadwal', [AdminSettingController::class, 'updateSchedule'])->name('settings.schedule.update');
        Route::post('/settings/libur', [AdminSettingController::class, 'storeHoliday'])->name('settings.holidays.store');
        Route::delete('/settings/libur/{holiday}', [AdminSettingController::class, 'destroyHoliday'])->name('settings.holidays.destroy');
        Route::post('/settings/pickup', [AdminSettingController::class, 'storePickup'])->name('settings.pickups.store');
        Route::put('/settings/pickup/{pickup}', [AdminSettingController::class, 'updatePickup'])->name('settings.pickups.update');
        Route::delete('/settings/pickup/{pickup}', [AdminSettingController::class, 'destroyPickup'])->name('settings.pickups.destroy');
        Route::put('/settings/ongkir', [AdminSettingController::class, 'updateShipping'])->name('settings.shipping.update');
        Route::post('/settings/zona', [AdminSettingController::class, 'storeZone'])->name('settings.zones.store');
        Route::delete('/settings/zona/{zone}', [AdminSettingController::class, 'destroyZone'])->name('settings.zones.destroy');
        Route::put('/settings/pembayaran', [AdminSettingController::class, 'updatePaymentTimeout'])->name('settings.payment.update');
        Route::put('/settings/keamanan', [AdminSettingController::class, 'updateAdminPassword'])->name('settings.security.update');
    });
});
