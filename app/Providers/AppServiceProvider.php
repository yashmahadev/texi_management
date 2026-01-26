<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

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
        Paginator::useBootstrapFour();

        \Illuminate\Support\Carbon::macro('toAppDate', function () {
            return $this->format(config('app.date_format', 'd-m-Y'));
        });

        \Illuminate\Support\Carbon::macro('toAppDateTime', function () {
            return $this->format(config('app.datetime_format', 'd-m-Y H:i:s'));
        });
    }
}
