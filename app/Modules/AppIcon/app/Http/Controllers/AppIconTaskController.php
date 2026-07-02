<?php

namespace Modules\AppIcon\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\AppIcon\Http\Requests\StoreAppIconTaskRequest;
use Modules\AppIcon\Http\Resources\AppIconTaskResource;
use Modules\AppIcon\Services\AppIconTaskService;

class AppIconTaskController extends Controller
{
    public function __construct(
        private readonly AppIconTaskService $service,
    ) {}

    public function store(StoreAppIconTaskRequest $request): AppIconTaskResource
    {
        $task = $this->service->createAndFetch($request->validated('bundle_id'));

        return new AppIconTaskResource($task);
    }

    public function show(int $id): AppIconTaskResource|JsonResponse
    {
        $task = $this->service->find($id);

        if ($task === null) {
            abort(404);
        }

        return new AppIconTaskResource($task);
    }
}
