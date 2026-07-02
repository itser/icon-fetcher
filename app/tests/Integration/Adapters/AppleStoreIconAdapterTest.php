<?php

namespace Tests\Integration\Adapters;

use Illuminate\Support\Facades\Http;
use Modules\AppleStore\Adapters\AppleStoreIconAdapter;
use Tests\TestCase;

class AppleStoreIconAdapterTest extends TestCase
{
    private const BUNDLE_ID = 'com.zhiliaoapp.musically';

    private const ICON_URL = 'https://is1-ssl.mzstatic.com/image/thumb/example/512x512bb.jpg';

    public function test_fetch_icon_url_returns_url_from_itunes_lookup_fixture(): void
    {
        Http::fake([
            'itunes.apple.com/lookup*' => Http::response(
                file_get_contents(base_path('tests/Fixtures/Apple/itunes_lookup_success.json')),
            ),
        ]);

        $adapter = new AppleStoreIconAdapter;

        $this->assertSame(self::ICON_URL, $adapter->fetchIconUrl(self::BUNDLE_ID));
    }

    public function test_fetch_icon_url_returns_null_when_itunes_lookup_is_empty(): void
    {
        Http::fake([
            'itunes.apple.com/lookup*' => Http::response(
                file_get_contents(base_path('tests/Fixtures/Apple/itunes_lookup_empty.json')),
            ),
        ]);

        $adapter = new AppleStoreIconAdapter;

        $this->assertNull($adapter->fetchIconUrl(self::BUNDLE_ID));
    }
}
