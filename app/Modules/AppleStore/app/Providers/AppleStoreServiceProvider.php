<?php

namespace Modules\AppleStore\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AppIcon\Contracts\AppleIconProvider;
use Modules\AppleStore\Adapters\AppleStoreIconAdapter;

class AppleStoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AppleIconProvider::class, AppleStoreIconAdapter::class);
    }
}
