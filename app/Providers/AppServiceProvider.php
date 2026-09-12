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
        if (config('app.env') === 'production' 
            || request()->header('x-forwarded-proto') === 'https' 
            || request()->server('HTTP_X_FORWARDED_PROTO') === 'https'
            || str_contains(request()->getHost(), 'vercel.app')) {
            URL::forceScheme('https');
        }

        \Illuminate\Support\Facades\Event::listen(\Illuminate\Database\Events\ConnectionEstablished::class, function ($event) {
            try {
                $event->connection->getPdo()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
            } catch (\Throwable $e) {}
        });
    }
}
