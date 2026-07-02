<?php

namespace Modules\GooglePlay\Adapters;

use Modules\AppIcon\Contracts\GooglePlayIconProvider;

class GooglePlayIconAdapter implements GooglePlayIconProvider
{
    public function fetchIconUrl(string $bundleId): ?string
    {
        return null;
    }
}
