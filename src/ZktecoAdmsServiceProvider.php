<?php

namespace Shadow046\ZktecoAdms;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Shadow046\ZktecoAdms\Commands\InstallCommand;
use Shadow046\ZktecoAdms\Events\AttendanceLogsStored;
use Shadow046\ZktecoAdms\Listeners\RunDtrPairingListener;

class ZktecoAdmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/zkteco-adms.php', 'zkteco-adms');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Event::listen(AttendanceLogsStored::class, RunDtrPairingListener::class);

        $this->publishes([
            __DIR__.'/../config/zkteco-adms.php' => config_path('zkteco-adms.php'),
        ], 'zkteco-adms-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'zkteco-adms-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
