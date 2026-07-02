<?php

namespace Modules\AppIcon\Repositories;

use Modules\AppIcon\Enums\AppIconTaskStatus;
use Modules\AppIcon\Models\AppIconTask;

class AppIconTaskRepository
{
    /**
     * @param  array{bundle_id: string, status: AppIconTaskStatus, apple_icon_url?: ?string, google_icon_url?: ?string, errors?: array<string, string>}  $attributes
     */
    public function create(array $attributes): AppIconTask
    {
        return AppIconTask::query()->create($attributes);
    }

    public function find(int $id): ?AppIconTask
    {
        return AppIconTask::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(AppIconTask $task, array $attributes): AppIconTask
    {
        $task->update($attributes);

        return $task->refresh();
    }
}
