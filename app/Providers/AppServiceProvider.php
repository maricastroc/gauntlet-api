<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // The API docs are intentionally public (portfolio project), so allow
        // access everywhere. Local is already allowed upstream by Scramble.
        Gate::define('viewApiDocs', fn ($user = null) => true);
    }
}
