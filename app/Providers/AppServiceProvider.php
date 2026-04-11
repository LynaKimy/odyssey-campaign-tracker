<?php

namespace App\Providers;

use App\Models\User;
use App\Services\DeepLClient;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DeepLClient::class, function () {
            return new DeepLClient(
                apiKey: config('services.deepl.api_key', ''),
                baseUrl: config('services.deepl.base_url', 'https://api-free.deepl.com/v2'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('admin', function (User $user): bool {
            return $user->isAdmin();
        });
    }
}
