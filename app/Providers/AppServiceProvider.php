<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // Otomatis paksa HTTPS jika request berasal dari Cloudflare Tunnel / proxy
        if (
            request()->server('HTTP_X_FORWARDED_PROTO') === 'https' ||
            str_contains(request()->header('host', ''), 'trycloudflare.com') ||
            $this->app->environment('production')
        ) {
            URL::forceScheme('https');
        }

        // Hapus sisa file public/hot (bekas npm run dev) agar remote client tidak mencoba connect ke [::1]:5173
        if (file_exists(public_path('hot')) && (str_contains(request()->header('host', ''), 'trycloudflare.com') || !app()->isLocal())) {
            @unlink(public_path('hot'));
        }
    }
}
