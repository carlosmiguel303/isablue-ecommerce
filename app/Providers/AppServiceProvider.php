<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('layouts.*', function ($view) {
            $view->with('navCategories', Category::where('active', true)->orderBy('sort_order')->take(12)->get());
            $view->with('shopSettings', Setting::pluck('value', 'key'));
        });

        // Barra de promoción con cuenta regresiva: se usa en el layout y en las ofertas del inicio.
        View::composer(['layouts.store', 'home'], function ($view) {
            $s = Setting::pluck('value', 'key');
            $endsAt = ! empty($s['promo_ends_at']) ? strtotime($s['promo_ends_at']) : null;
            $active = ($s['promo_enabled'] ?? '0') === '1' && ! empty($s['promo_text']) && (! $endsAt || $endsAt > time());
            $view->with('promoActive', $active);
            $view->with('promoEndsAt', $active && $endsAt ? date('c', $endsAt) : null);
        });

        View::composer('layouts.store', function ($view) {
            $s = Setting::pluck('value', 'key');
            $popup = null;
            if (($s['popup_enabled'] ?? '0') === '1' && ! empty($s['popup_product_id'])) {
                $popup = Product::publicCatalog()->with('images')->find($s['popup_product_id']);
            }
            $view->with('popupProduct', $popup?->available ? $popup : null);
        });

        View::composer('layouts.admin', function ($view) {
            $view->with('awaitingYape', Order::where('status', 'pending')->whereIn('payment_status', ['pending', 'review'])->count());
        });
    }
}
