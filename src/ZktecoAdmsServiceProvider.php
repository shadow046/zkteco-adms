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

        $this->publishes([
            __DIR__.'/../stubs/controllers/AdmsController.php' => app_path('Http/Controllers/ZktecoAdms/AdmsController.php'),
            __DIR__.'/../stubs/controllers/AdmsCommandController.php' => app_path('Http/Controllers/ZktecoAdms/AdmsCommandController.php'),
            __DIR__.'/../stubs/controllers/AdmsEndpointController.php' => app_path('Http/Controllers/ZktecoAdms/AdmsEndpointController.php'),
        ], 'zkteco-adms-controllers');

        $this->publishes([
            __DIR__.'/../stubs/services/AdmsCoreService.php' => app_path('Services/ZktecoAdms/AdmsCoreService.php'),
            __DIR__.'/../stubs/services/AdmsCommandService.php' => app_path('Services/ZktecoAdms/AdmsCommandService.php'),
            __DIR__.'/../stubs/services/DtrPairingService.php' => app_path('Services/ZktecoAdms/DtrPairingService.php'),
            __DIR__.'/../stubs/services/ZkPythonBridgeService.php' => app_path('Services/ZktecoAdms/ZkPythonBridgeService.php'),
        ], 'zkteco-adms-services');

        $this->publishes([
            __DIR__.'/../README.md' => base_path('docs/zkteco-adms/README.md'),
            __DIR__.'/../QUICKSTART.md' => base_path('docs/zkteco-adms/QUICKSTART.md'),
            __DIR__.'/../NGINX.md' => base_path('docs/zkteco-adms/NGINX.md'),
            __DIR__.'/../PUBLISHING.md' => base_path('docs/zkteco-adms/PUBLISHING.md'),
            __DIR__.'/../OVERRIDES.md' => base_path('docs/zkteco-adms/OVERRIDES.md'),
            __DIR__.'/../CHANGELOG.md' => base_path('docs/zkteco-adms/CHANGELOG.md'),
        ], 'zkteco-adms-docs');

        $this->publishes([
            __DIR__.'/../nginx/http-default-port.conf.example' => base_path('nginx/zkteco-adms/http-default-port.conf.example'),
            __DIR__.'/../nginx/http-custom-port.conf.example' => base_path('nginx/zkteco-adms/http-custom-port.conf.example'),
        ], 'zkteco-adms-nginx');

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
