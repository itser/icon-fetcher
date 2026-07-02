<?php

namespace Modules\AppleStore\Adapters;

use Modules\AppIcon\Contracts\AppleIconProvider;

class AppleStoreIconAdapter implements AppleIconProvider
{
    public function fetchIconUrl(string $bundleId): ?string
    {
        return null;
    }
}
