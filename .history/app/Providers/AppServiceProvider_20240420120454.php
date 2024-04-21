<?php

namespace App\Providers;
Passport::loadKeysFrom(__DIR__.'/../secrets/oauth');
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
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
        Schema::defaultStringLength(191);
        Passport::loadKeysFrom(__DIR__.'/../secrets/oauth');
    }
}
