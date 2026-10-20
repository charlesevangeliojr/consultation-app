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
        View::composer('admin.layout', function ($view): void {
            $count = 0;
            if (auth()->user()?->canAccess('accounts', 'view')) {
                $count = \App\Models\User::whereHas('role', fn ($query) => $query
                    ->whereRaw('LOWER(name) LIKE ?', ['%teacher%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%faculty%']))
                    ->whereDoesntHave('teacher', fn ($query) => $query->where('consultation_approved', true))
                    ->count();
            }
            $view->with('pendingTeacherApprovalCount', $count);
        });
        View::composer('layouts.consultations', function ($view): void {
            $view->with('portalNavigationBadges', auth()->user()
                ? \App\Support\PortalNavigationBadges::forUser(auth()->user()) : []);
        });
    }
}
