<?php

namespace Modules\AppIcon\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\AppIcon\Services\AppIconTaskService;

class ProcessAppIconTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $taskId,
    ) {}

    public function handle(AppIconTaskService $service): void
    {
        $service->execute($this->taskId);
    }
}
