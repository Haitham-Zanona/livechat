<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Unsplash\HttpClient;

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
        HttpClient::init([
            'applicationId' => config('unsplash.applicationId'),
            'secret' => config('unsplash.secret'),
            'callbackUrl' => config('unsplash.redirectUri'),
            'utmSource' => config('unsplash.utmSource'),
        ]);

    }
}
