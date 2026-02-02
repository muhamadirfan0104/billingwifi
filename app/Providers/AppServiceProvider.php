<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use Dompdf\Options;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */

public function register(): void
{
    $this->app->bind('dompdf.options', function () {
        $options = new Options();

        // Paksa public path + root akses file lokal
        $options->set('public_path', base_path('public'));
        $options->set('chroot', base_path());
        $options->set('isRemoteEnabled', true); // kalau ada asset via URL

        return $options;
    });
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
