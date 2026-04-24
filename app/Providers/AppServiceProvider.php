<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use App\Models\PersonalAccessToken;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ✅ FIX: prevent Sanctum recursion
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}