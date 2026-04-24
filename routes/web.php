<?php

use Illuminate\Support\Facades\Route;
use Shadow046\ZktecoAdms\Http\Controllers\AdmsUiController;

Route::prefix(trim((string) config('zkteco-adms.ui_route_prefix', 'shadow046/adms'), '/'))
    ->middleware(config('zkteco-adms.ui_middleware', []))
    ->name('zkteco-adms.ui.')
    ->group(function (): void {
        Route::get('/', [AdmsUiController::class, 'dashboard'])->name('home');
        Route::get('/dashboard', [AdmsUiController::class, 'dashboard'])->name('dashboard');
        Route::post('/dashboard/attlog-query', [AdmsUiController::class, 'queueAttlogQuery'])->name('attlog-query');
        Route::get('/attendance', [AdmsUiController::class, 'attendanceIndex'])->name('attendance');
        Route::get('/daily-logs', [AdmsUiController::class, 'dailyLogsIndex'])->name('daily-logs');
        Route::get('/sequence-audit', [AdmsUiController::class, 'sequenceAuditIndex'])->name('sequence-audit');
        Route::post('/daily-logs/run-pairing', [AdmsUiController::class, 'runDailyLogsPairing'])->name('daily-logs.run-pairing');
    });
