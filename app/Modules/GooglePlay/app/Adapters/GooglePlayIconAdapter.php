<?php

namespace Modules\GooglePlay\Adapters;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\AppIcon\Contracts\GooglePlayIconProvider;

class GooglePlayIconAdapter implements GooglePlayIconProvider
{
    public function fetchIconUrl(string $bundleId): ?string
    {
        try {
            $response = Http::timeout((int) config('googleplay.timeout'))->get(
                (string) config('googleplay.details_url'),
                [
                    'id' => $bundleId,
                ],
            );
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $this->extractOgImageUrl($response->body());
    }

    private function extractOgImageUrl(string $html): ?string
    {
        if (preg_match('/<meta\s+property="og:image"\s+content="([^"]+)"/i', $html, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
