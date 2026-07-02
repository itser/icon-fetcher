<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AppIconTaskApiTest extends TestCase
{
    use RefreshDatabase;

    private const BUNDLE_ID = 'com.zhiliaoapp.musically';

    private const APPLE_ICON_URL = 'https://is1-ssl.mzstatic.com/image/thumb/example/512x512bb.jpg';

    private const GOOGLE_ICON_URL = 'https://play-lh.googleusercontent.com/abc123=s180-rw';

    public function test_post_creates_task_and_returns_completed_with_icon_urls(): void
    {
        $this->fakeBothStoresSuccess();

        $response = $this->postJson('/api/v1/app-icons/tasks', [
            'bundle_id' => self::BUNDLE_ID,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.bundle_id', self::BUNDLE_ID)
            ->assertJsonPath('data.apple_icon_url', self::APPLE_ICON_URL)
            ->assertJsonPath('data.google_icon_url', self::GOOGLE_ICON_URL)
            ->assertJsonPath('data.errors', []);
    }

    public function test_post_returns_422_for_invalid_bundle_id(): void
    {
        $response = $this->postJson('/api/v1/app-icons/tasks', [
            'bundle_id' => 'not-a-valid-bundle-id',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['bundle_id']);
    }

    public function test_get_returns_completed_task_with_icon_urls(): void
    {
        $this->fakeBothStoresSuccess();

        $taskId = $this->postJson('/api/v1/app-icons/tasks', [
            'bundle_id' => self::BUNDLE_ID,
        ])->json('data.id');

        $response = $this->getJson("/api/v1/app-icons/tasks/{$taskId}");

        $response->assertOk()
            ->assertJsonPath('data.id', $taskId)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.bundle_id', self::BUNDLE_ID)
            ->assertJsonPath('data.apple_icon_url', self::APPLE_ICON_URL)
            ->assertJsonPath('data.google_icon_url', self::GOOGLE_ICON_URL)
            ->assertJsonPath('data.errors', []);
    }

    public function test_get_returns_partial_success_with_errors(): void
    {
        Http::fake([
            'itunes.apple.com/lookup*' => Http::response(
                file_get_contents(base_path('tests/Fixtures/Apple/itunes_lookup_success.json')),
            ),
            'play.google.com/store/apps/details*' => Http::response('Not Found', 404),
        ]);

        $createResponse = $this->postJson('/api/v1/app-icons/tasks', [
            'bundle_id' => self::BUNDLE_ID,
        ]);

        $createResponse->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.apple_icon_url', self::APPLE_ICON_URL)
            ->assertJsonPath('data.google_icon_url', null);

        $this->assertNotEmpty($createResponse->json('data.errors'));

        $response = $this->getJson('/api/v1/app-icons/tasks/'.$createResponse->json('data.id'));

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.apple_icon_url', self::APPLE_ICON_URL)
            ->assertJsonPath('data.google_icon_url', null);

        $this->assertNotEmpty($response->json('data.errors'));
    }

    public function test_get_returns_404_for_unknown_task_id(): void
    {
        $response = $this->getJson('/api/v1/app-icons/tasks/999999');

        $response->assertNotFound();
    }

    public function test_get_returns_empty_list_when_no_tasks(): void
    {
        $response = $this->getJson('/api/v1/app-icons/tasks');

        $response->assertOk()
            ->assertJson(['data' => []]);
    }

    public function test_get_returns_list_of_tasks(): void
    {
        $this->fakeBothStoresSuccess();

        $this->postJson('/api/v1/app-icons/tasks', [
            'bundle_id' => self::BUNDLE_ID,
        ])->assertOk();

        $response = $this->getJson('/api/v1/app-icons/tasks');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.bundle_id', self::BUNDLE_ID)
            ->assertJsonPath('data.0.status', 'completed')
            ->assertJsonPath('data.0.apple_icon_url', self::APPLE_ICON_URL)
            ->assertJsonPath('data.0.google_icon_url', self::GOOGLE_ICON_URL);
    }

    private function fakeBothStoresSuccess(): void
    {
        Http::fake([
            'itunes.apple.com/lookup*' => Http::response(
                file_get_contents(base_path('tests/Fixtures/Apple/itunes_lookup_success.json')),
            ),
            'play.google.com/store/apps/details*' => Http::response(
                file_get_contents(base_path('tests/Fixtures/Google/play_store_success.html')),
            ),
        ]);
    }
}
