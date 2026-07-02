<?php

namespace Tests\Integration\Adapters;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\GooglePlay\Adapters\GooglePlayIconAdapter;
use Tests\TestCase;

class GooglePlayIconAdapterTest extends TestCase
{
    private const BUNDLE_ID = 'com.zhiliaoapp.musically';

    private const ICON_URL = 'https://play-lh.googleusercontent.com/abc123=s180-rw';

    public function test_fetch_icon_url_returns_url_from_play_store_html_fixture(): void
    {
        Http::fake([
            'play.google.com/store/apps/details*' => Http::response(
                file_get_contents(base_path('tests/Fixtures/Google/play_store_success.html')),
            ),
        ]);

        $adapter = new GooglePlayIconAdapter;

        $this->assertSame(self::ICON_URL, $adapter->fetchIconUrl(self::BUNDLE_ID));
    }

    public function test_fetch_icon_url_returns_null_on_play_store_404(): void
    {
        Http::fake([
            'play.google.com/store/apps/details*' => Http::response('Not Found', 404),
        ]);

        $adapter = new GooglePlayIconAdapter;

        $this->assertNull($adapter->fetchIconUrl(self::BUNDLE_ID));
    }

    public function test_fetch_icon_url_returns_null_on_play_store_timeout(): void
    {
        Http::fake(function (): void {
            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $adapter = new GooglePlayIconAdapter;

        $this->assertNull($adapter->fetchIconUrl(self::BUNDLE_ID));
    }
}
