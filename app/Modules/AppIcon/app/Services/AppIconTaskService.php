<?php

namespace Modules\AppIcon\Services;

use Modules\AppIcon\Models\AppIconTask;

class AppIconTaskService
{
    public function createAndFetch(string $bundleId): AppIconTask
    {
        abort(501, 'Not implemented');
    }

    public function find(int $id): ?AppIconTask
    {
        return AppIconTask::query()->find($id);
    }
}
