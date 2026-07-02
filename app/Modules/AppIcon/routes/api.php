<?php

use Illuminate\Support\Facades\Route;
use Modules\AppIcon\Http\Controllers\AppIconTaskController;

Route::prefix('v1')->group(function (): void {
    Route::prefix('app-icons')->group(function (): void {
        Route::get('tasks', [AppIconTaskController::class, 'index']);
        Route::post('tasks', [AppIconTaskController::class, 'store']);
        Route::get('tasks/{id}', [AppIconTaskController::class, 'show'])->whereNumber('id');
    });
});
