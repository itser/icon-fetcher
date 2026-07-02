<?php

namespace Modules\GooglePlay\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AppIcon\Contracts\GooglePlayIconProvider;
use Modules\GooglePlay\Adapters\GooglePlayIconAdapter;

class GooglePlayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GooglePlayIconProvider::class, GooglePlayIconAdapter::class);
    }
}
