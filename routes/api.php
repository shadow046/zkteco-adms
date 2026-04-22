<?php

use Shadow046\ZktecoAdms\Http\Controllers\AdmsEndpointController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('zkteco-adms.route_prefix', 'iclock'))
    ->middleware(config('zkteco-adms.middleware', []))
    ->group(function (): void {
        Route::match(['get', 'post'], '/cdata', [AdmsEndpointController::class, 'cdata']);
        Route::match(['get', 'post'], '/fdata', [AdmsEndpointController::class, 'fdata']);
        Route::get('/getrequest', [AdmsEndpointController::class, 'getrequest']);
        Route::match(['get', 'post'], '/devicecmd', [AdmsEndpointController::class, 'devicecmd']);
    });
