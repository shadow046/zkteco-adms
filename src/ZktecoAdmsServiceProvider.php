<?php

namespace Shadow046\ZktecoAdms;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Shadow046\ZktecoAdms\Commands\InstallCommand;
use Shadow046\ZktecoAdms\Commands\MigrateAdmsCommand;
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
        $this->loadRoutesFrom($this->routeOverridePath('api.php'));
        $this->loadRoutesFrom($this->routeOverridePath('commands.php'));
        $this->loadRoutesFrom($this->routeOverridePath('web.php'));
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'zkteco-adms');

        Event::listen(AttendanceLogsStored::class, RunDtrPairingListener::class);

        $this->publishes([
            __DIR__.'/../config/zkteco-adms.php' => config_path('zkteco-adms.php'),
        ], 'zkteco-adms-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'zkteco-adms-migrations');

        $this->publishes([
            __DIR__.'/../scripts' => base_path('scripts/zkteco-adms'),
        ], 'zkteco-adms-scripts');

        $this->publishes([
            __DIR__.'/../routes/api.php' => base_path('routes/zkteco-adms/api.php'),
            __DIR__.'/../routes/commands.php' => base_path('routes/zkteco-adms/commands.php'),
            __DIR__.'/../routes/web.php' => base_path('routes/zkteco-adms/web.php'),
        ], 'zkteco-adms-routes');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                MigrateAdmsCommand::class,
            ]);
        }
    }

    private function routeOverridePath(string $filename): string
    {
        $hostPath = base_path('routes/zkteco-adms/'.$filename);

        if (is_file($hostPath)) {
            return $hostPath;
        }

        return __DIR__.'/../routes/'.$filename;
    }
}
