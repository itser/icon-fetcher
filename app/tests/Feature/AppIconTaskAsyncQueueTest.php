<?php

namespace Tests\Feature;

use App\Shared\DTO\IconFetchResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Modules\AppIcon\Jobs\ProcessAppIconTaskJob;
use Tests\TestCase;

class AppIconTaskAsyncQueueTest extends TestCase
{
    use RefreshDatabase;

    private const BUNDLE_ID = 'com.zhiliaoapp.musically';

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('queue.default', 'redis');
    }

    public function test_post_returns_202_and_dispatches_job_when_queue_is_redis(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/app-icons/tasks', [
            'bundle_id' => self::BUNDLE_ID,
        ]);

        $response->assertAccepted()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.bundle_id', self::BUNDLE_ID)
            ->assertJsonPath('data.apple_icon_url', null)
            ->assertJsonPath('data.google_icon_url', null);

        $taskId = $response->json('data.id');

        Queue::assertPushed(ProcessAppIconTaskJob::class, function (ProcessAppIconTaskJob $job) use ($taskId): bool {
            return $job->taskId === $taskId;
        });
    }

    public function test_get_returns_pending_task_after_async_post(): void
    {
        Queue::fake();

        $taskId = $this->postJson('/api/v1/app-icons/tasks', [
            'bundle_id' => self::BUNDLE_ID,
        ])->assertAccepted()->json('data.id');

        $this->getJson("/api/v1/app-icons/tasks/{$taskId}")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.apple_icon_url', null)
            ->assertJsonPath('data.google_icon_url', null);
    }

    public function test_post_returns_200_from_cache_without_dispatching_job_when_queue_is_redis(): void
    {
        Queue::fake();

        Cache::put(
            'app-icon:'.self::BUNDLE_ID,
            new IconFetchResult(
                'https://example.com/apple.png',
                'https://example.com/google.png',
                [],
            ),
            3600,
        );

        $response = $this->postJson('/api/v1/app-icons/tasks', [
            'bundle_id' => self::BUNDLE_ID,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.apple_icon_url', 'https://example.com/apple.png')
            ->assertJsonPath('data.google_icon_url', 'https://example.com/google.png')
            ->assertJsonPath('data.errors', []);

        Queue::assertNothingPushed();
    }
}
