<?php

use Illuminate\Support\Facades\Route;
use Shadow046\ZktecoAdms\Http\Controllers\AdmsCommandController;

Route::prefix(trim((string) config('zkteco-adms.command_route_prefix', 'zkteco-adms/commands'), '/'))
    ->middleware(config('zkteco-adms.command_middleware', []))
    ->group(function (): void {
        Route::post('/attlog-query', [AdmsCommandController::class, 'queueAttlogQuery']);
        Route::post('/fingertmp-query', [AdmsCommandController::class, 'queueFingertmpQuery']);
        Route::post('/user-update', [AdmsCommandController::class, 'queueUserUpdate']);
        Route::post('/fingertmp-update', [AdmsCommandController::class, 'queueFingertmpUpdate']);
    });
