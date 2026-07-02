<?php

namespace Modules\AppIcon\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\AppIcon\Http\Requests\StoreAppIconTaskRequest;
use Modules\AppIcon\Http\Resources\AppIconTaskResource;
use Modules\AppIcon\Services\AppIconTaskService;
use Symfony\Component\HttpFoundation\Response;

class AppIconTaskController extends Controller
{
    public function __construct(
        private readonly AppIconTaskService $service,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return AppIconTaskResource::collection($this->service->list());
    }

    public function store(StoreAppIconTaskRequest $request): AppIconTaskResource|JsonResponse
    {
        $task = $this->service->submit($request->validated('bundle_id'));

        $resource = new AppIconTaskResource($task);

        if (config('queue.default') === 'redis') {
            return $resource->response()->setStatusCode(Response::HTTP_ACCEPTED);
        }

        return $resource;
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
