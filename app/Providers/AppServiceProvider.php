<?php

namespace App\Providers;
use App\Services\UserService;
use laravel\Passport\Passport;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    /*
    public function register(): void
    {
        //
    }
*/
    /*
    public function register()
    {
        $this->app->singleton(UserService::class, function ($app) {
            return new UserService();
        });
    }
    */
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
