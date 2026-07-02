<?php

namespace Modules\AppIcon\Contracts;

interface GooglePlayIconProvider
{
    public function fetchIconUrl(string $bundleId): ?string;
}
