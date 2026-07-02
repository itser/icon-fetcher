<?php

namespace Modules\AppIcon\Contracts;

interface AppleIconProvider
{
    public function fetchIconUrl(string $bundleId): ?string;
}
