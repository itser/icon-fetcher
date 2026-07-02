<?php

namespace Modules\AppleStore\Adapters;

use Illuminate\Support\Facades\Http;
use Modules\AppIcon\Contracts\AppleIconProvider;

class AppleStoreIconAdapter implements AppleIconProvider
{
    public function fetchIconUrl(string $bundleId): ?string
    {
        $response = Http::timeout((int) config('applestore.timeout'))->get(
            (string) config('applestore.lookup_url'),
            [
                'bundleId' => $bundleId,
            ],
        );

        if (! $response->successful()) {
            return null;
        }

        $results = $response->json('results', []);

        if ($results === []) {
            return null;
        }

        return $results[0]['artworkUrl512'] ?? null;
    }
}
