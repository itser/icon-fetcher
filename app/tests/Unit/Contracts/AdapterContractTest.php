<?php

namespace Tests\Unit\Contracts;

use Modules\AppIcon\Contracts\AppleIconProvider;
use Modules\AppIcon\Contracts\GooglePlayIconProvider;
use Modules\AppleStore\Adapters\AppleStoreIconAdapter;
use Modules\GooglePlay\Adapters\GooglePlayIconAdapter;
use Tests\TestCase;

class AdapterContractTest extends TestCase
{
    public function test_apple_icon_provider_resolves_to_apple_store_icon_adapter(): void
    {
        $provider = $this->app->make(AppleIconProvider::class);

        $this->assertInstanceOf(AppleStoreIconAdapter::class, $provider);
    }

    public function test_google_play_icon_provider_resolves_to_google_play_icon_adapter(): void
    {
        $provider = $this->app->make(GooglePlayIconProvider::class);

        $this->assertInstanceOf(GooglePlayIconAdapter::class, $provider);
    }
}
