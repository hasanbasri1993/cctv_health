<?php

namespace App\Providers;

use App\Contracts\HikvisionISAPIServiceInterface;
use App\Services\HikvisionISAPIService;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(HikvisionISAPIServiceInterface::class, HikvisionISAPIService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
