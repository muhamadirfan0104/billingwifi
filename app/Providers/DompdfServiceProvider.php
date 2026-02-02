<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DompdfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->resolving('dompdf.options', function ($options) {
            $options->set('public_path', base_path('public'));
            $options->set('chroot', base_path());
        });
    }
}
