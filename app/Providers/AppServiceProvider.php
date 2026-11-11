<?php

namespace App\Providers;

use App\Models\Cart;
use App\Models\StoreSetting;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('id');
        Paginator::defaultView('vendor.pagination.kelana');

        // Bagikan data pengaturan toko, keranjang aktif, dan notifikasi ke seluruh tampilan Blade
        View::composer('*', function ($view): void {
            if (! Schema::hasTable('store_settings')) {
                return;
            }

            $storeSetting = StoreSetting::current();
            $sharedCart = null;

            if (Schema::hasTable('carts') && Schema::hasTable('cart_items')) {
                if (Auth::check()) {
                    $sharedCart = Cart::query()
                        ->with(['items.product.images', 'items.variant'])
                        ->where('user_id', Auth::id())
                        ->first();
                } elseif (session()->isStarted()) {
                    $sharedCart = Cart::query()
                        ->with(['items.product.images', 'items.variant'])
                        ->where('session_id', session()->getId())
                        ->first();
                }
            }

            $customerNotifications = collect();
            $customerUnreadCount = 0;
            $adminUnreadCount = 0;

            if (Schema::hasTable('user_notifications') && Auth::check()) {
                $currentUser = Auth::user();
                if ($currentUser->isAdmin()) {
                    $adminUnreadCount = UserNotification::query()
                        ->where('audience', 'admin')
                        ->whereNull('read_at')
                        ->count();
                } else {
                    $customerNotifications = UserNotification::query()
                        ->where('audience', 'customer')
                        ->where('user_id', $currentUser->id)
                        ->latest()
                        ->take(4)
                        ->get();

                    $customerUnreadCount = UserNotification::query()
                        ->where('audience', 'customer')
                        ->where('user_id', $currentUser->id)
                        ->whereNull('read_at')
                        ->count();
                }
            }

            $view->with([
                'sharedStoreSetting' => $storeSetting,
                'sharedCart' => $sharedCart,
                'sharedCustomerNotifications' => $customerNotifications,
                'sharedCustomerUnreadCount' => $customerUnreadCount,
                'sharedAdminUnreadCount' => $adminUnreadCount,
            ]);
        });
    }
}
