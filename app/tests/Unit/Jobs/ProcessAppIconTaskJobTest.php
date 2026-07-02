<?php

namespace Tests\Unit\Jobs;

use Mockery;
use Modules\AppIcon\Jobs\ProcessAppIconTaskJob;
use Modules\AppIcon\Models\AppIconTask;
use Modules\AppIcon\Services\AppIconTaskService;
use Tests\TestCase;

class ProcessAppIconTaskJobTest extends TestCase
{
    public function test_handle_delegates_to_service_execute(): void
    {
        $task = new AppIconTask;
        $task->id = 1;

        $service = Mockery::mock(AppIconTaskService::class);
        $service->shouldReceive('execute')
            ->once()
            ->with(1)
            ->andReturn($task);

        (new ProcessAppIconTaskJob(1))->handle($service);
    }
}
