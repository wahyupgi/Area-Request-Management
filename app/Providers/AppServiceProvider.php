<?php

namespace App\Providers;

use App\Models\Memo;
use App\Observers\MemoObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

   public function boot(): void
    {
    if (
        app()->environment('production') ||
        request()->isSecure() ||
        request()->header('X-Forwarded-Proto') === 'https'
    ) {
        URL::forceScheme('https');
    }
    }
}