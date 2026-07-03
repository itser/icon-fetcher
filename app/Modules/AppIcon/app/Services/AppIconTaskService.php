<?php

namespace Modules\AppIcon\Services;

use App\Shared\DTO\IconFetchResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\AppIcon\Contracts\AppleIconProvider;
use Modules\AppIcon\Contracts\GooglePlayIconProvider;
use Modules\AppIcon\Enums\AppIconTaskStatus;
use Modules\AppIcon\Jobs\ProcessAppIconTaskJob;
use Modules\AppIcon\Models\AppIconTask;
use Modules\AppIcon\Repositories\AppIconTaskRepository;

class AppIconTaskService
{
    public function __construct(
        private readonly AppIconTaskRepository $repository,
        private readonly AppleIconProvider $appleProvider,
        private readonly GooglePlayIconProvider $googleProvider,
    ) {}

    public function create(string $bundleId): AppIconTask
    {
        return $this->repository->create([
            'bundle_id' => $bundleId,
            'status' => AppIconTaskStatus::Pending,
            'apple_icon_url' => null,
            'google_icon_url' => null,
            'errors' => [],
        ]);
    }

    public function submit(string $bundleId): AppIconTask
    {
        $cached = Cache::get($this->cacheKey($bundleId));

        if ($cached instanceof IconFetchResult) {
            return $this->createCompleted($bundleId, $cached);
        }

        $task = $this->create($bundleId);

        ProcessAppIconTaskJob::dispatch($task->id);

        return $this->repository->find($task->id) ?? $task;
    }

    private function createCompleted(string $bundleId, IconFetchResult $result): AppIconTask
    {
        return $this->repository->create([
            'bundle_id' => $bundleId,
            'status' => AppIconTaskStatus::Completed,
            'apple_icon_url' => $result->appleIconUrl,
            'google_icon_url' => $result->googleIconUrl,
            'errors' => $result->errors,
        ]);
    }

    public function createAndFetch(string $bundleId): AppIconTask
    {
        return $this->submit($bundleId);
    }

    public function find(int $id): ?AppIconTask
    {
        return $this->repository->find($id);
    }

    /**
     * @return Collection<int, AppIconTask>
     */
    public function list(): Collection
    {
        return $this->repository->all();
    }

    public function execute(int $taskId): AppIconTask
    {
        $task = $this->repository->find($taskId);

        if ($task === null) {
            throw new \RuntimeException("App icon task [{$taskId}] not found.");
        }

        $this->repository->update($task, [
            'status' => AppIconTaskStatus::Processing,
        ]);

        $result = $this->fetchIcons($task->bundle_id);

        return $this->repository->update($task, [
            'status' => AppIconTaskStatus::Completed,
            'apple_icon_url' => $result->appleIconUrl,
            'google_icon_url' => $result->googleIconUrl,
            'errors' => $result->errors,
        ]);
    }

    private function fetchIcons(string $bundleId): IconFetchResult
    {
        return Cache::remember(
            $this->cacheKey($bundleId),
            (int) config('appicon.cache_ttl'),
            fn () => $this->fetchIconsFromProviders($bundleId),
        );
    }

    private function fetchIconsFromProviders(string $bundleId): IconFetchResult
    {
        $appleIconUrl = $this->appleProvider->fetchIconUrl($bundleId);
        $googleIconUrl = $this->googleProvider->fetchIconUrl($bundleId);

        $errors = [];

        if ($appleIconUrl === null) {
            $errors['apple'] = 'Icon not found';
        }

        if ($googleIconUrl === null) {
            $errors['google'] = 'Icon not found';
        }

        return new IconFetchResult($appleIconUrl, $googleIconUrl, $errors);
    }

    private function cacheKey(string $bundleId): string
    {
        return 'app-icon:'.$bundleId;
    }
}
