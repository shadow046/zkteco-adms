<?php

use Illuminate\Support\Facades\Route;
use Shadow046\ZktecoAdms\Http\Controllers\AdmsCommandController;

/*
|--------------------------------------------------------------------------
| ZKTeco ADMS Command Routes
|--------------------------------------------------------------------------
|
| Default package controller:
|   Shadow046\ZktecoAdms\Http\Controllers\AdmsCommandController
|
| Optional host override:
|   App\Http\Controllers\ZktecoAdms\AdmsCommandController
|
| If you want to use the host-published controller stub instead, replace
| the use statement above with:
|
| use App\Http\Controllers\ZktecoAdms\AdmsCommandController;
|
*/

Route::prefix(trim((string) config('zkteco-adms.command_route_prefix', 'zkteco-adms/commands'), '/'))
    ->middleware(config('zkteco-adms.command_middleware', []))
    ->group(function (): void {
        Route::post('/attlog-query', [AdmsCommandController::class, 'queueAttlogQuery']);
        Route::post('/fingertmp-query', [AdmsCommandController::class, 'queueFingertmpQuery']);
        Route::post('/user-update', [AdmsCommandController::class, 'queueUserUpdate']);
        Route::post('/fingertmp-update', [AdmsCommandController::class, 'queueFingertmpUpdate']);
    });
