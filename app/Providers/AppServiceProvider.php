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
        $this->app->bind(\App\Contracts\PushTransport::class, \App\Support\WebPushTransport::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (['push-status' => 30, 'push-subscribe' => 10, 'push-test' => 2, 'notification-summary' => 60] as $name => $limit) {
            \Illuminate\Support\Facades\RateLimiter::for($name, fn (\Illuminate\Http\Request $request) => \Illuminate\Cache\RateLimiting\Limit::perMinute($limit)->by((string) $request->user()?->id));
        }
        \App\Models\ConsultationAppointment::observe(\App\Observers\ConsultationPushObserver::class);
        \App\Models\Teacher::observe(\App\Observers\TeacherPushObserver::class);
        View::composer(['admin.layout', 'layouts.consultations'], function ($view): void {
            $count = auth()->check() && \Illuminate\Support\Facades\Schema::hasTable('account_notifications')
                ? \App\Models\AccountNotification::where('user_id', auth()->id())->whereNull('read_at')->count() : 0;
            $view->with('notificationUnreadCount', $count);
        });
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
