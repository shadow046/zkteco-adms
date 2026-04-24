<?php

use Illuminate\Support\Facades\Route;
use Shadow046\ZktecoAdms\Http\Controllers\AdmsEndpointController;

/*
|--------------------------------------------------------------------------
| ZKTeco ADMS API Routes
|--------------------------------------------------------------------------
|
| Default package controller:
|   Shadow046\ZktecoAdms\Http\Controllers\AdmsEndpointController
|
| Optional host override:
|   App\Http\Controllers\ZktecoAdms\AdmsEndpointController
|
| If you want to use the host-published controller stub instead, replace
| the use statement above with:
|
| use App\Http\Controllers\ZktecoAdms\AdmsEndpointController;
|
*/

Route::prefix(config('zkteco-adms.route_prefix', 'iclock'))
    ->middleware(config('zkteco-adms.middleware', []))
    ->group(function (): void {
        Route::match(['get', 'post'], '/cdata', [AdmsEndpointController::class, 'cdata']);
        Route::match(['get', 'post'], '/fdata', [AdmsEndpointController::class, 'fdata']);
        Route::get('/getrequest', [AdmsEndpointController::class, 'getrequest']);
        Route::match(['get', 'post'], '/devicecmd', [AdmsEndpointController::class, 'devicecmd']);
    });
