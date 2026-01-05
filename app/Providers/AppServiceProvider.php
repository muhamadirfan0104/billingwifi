<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;

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
        Paginator::useBootstrapFive();

        // ✅ Carbon bahasa Indonesia
        Carbon::setLocale('id');

        // ✅ PHP locale (untuk translatedFormat)
        setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'id');
    }
}
