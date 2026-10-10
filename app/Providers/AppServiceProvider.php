<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        View::composer(['welcome', 'admin.*', 'student.*', 'teacher.*', 'layouts.*', 'auth.*'], function ($view): void {
            $view->with('siteSettings', SiteSetting::current());
        });
    }
}
