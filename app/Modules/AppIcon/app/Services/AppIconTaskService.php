<?php

namespace Modules\AppIcon\Services;

use App\Shared\DTO\IconFetchResult;
use Modules\AppIcon\Contracts\AppleIconProvider;
use Modules\AppIcon\Contracts\GooglePlayIconProvider;
use Modules\AppIcon\Enums\AppIconTaskStatus;
use Modules\AppIcon\Models\AppIconTask;
use Modules\AppIcon\Repositories\AppIconTaskRepository;

class AppIconTaskService
{
    public function __construct(
        private readonly AppIconTaskRepository $repository,
        private readonly AppleIconProvider $appleProvider,
        private readonly GooglePlayIconProvider $googleProvider,
    ) {}

    public function createAndFetch(string $bundleId): AppIconTask
    {
        $task = $this->repository->create([
            'bundle_id' => $bundleId,
            'status' => AppIconTaskStatus::Pending,
            'apple_icon_url' => null,
            'google_icon_url' => null,
            'errors' => [],
        ]);

        return $this->execute($task->id);
    }

    public function find(int $id): ?AppIconTask
    {
        return $this->repository->find($id);
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
}
