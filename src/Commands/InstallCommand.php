<?php

namespace Shadow046\ZktecoAdms\Commands;

use Composer\InstalledVersions;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'zkteco-adms:install';

    protected $description = 'Publish shadow046/zkteco-adms config and migrations.';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'zkteco-adms-config']);
        $this->call('vendor:publish', ['--tag' => 'zkteco-adms-migrations']);
        $this->call('vendor:publish', ['--tag' => 'zkteco-adms-scripts']);
        $this->call('vendor:publish', ['--tag' => 'zkteco-adms-routes']);
        $this->call('vendor:publish', ['--tag' => 'zkteco-adms-controllers']);

        $this->components->info('shadow046/zkteco-adms assets published.');
        $this->line('Package version: '.$this->packageVersion());
        $this->line('Next steps:');
        $this->line('1. Review config/zkteco-adms.php');
        $this->line('2. Run php artisan migrate:adms');
        $this->line('   This is legacy-safe for existing inout_raw/dtr and existing ADMS tables.');
        $this->line('3. Package route stubs were published to routes/zkteco-adms for optional host-side customization.');
        $this->line('4. Optional host controller stubs were published to app/Http/Controllers/ZktecoAdms.');
        $this->line('5. Python helper scripts and the bundled zk library were published to scripts/zkteco-adms.');
        $this->line('6. Make sure storage is writable so ATTPHOTO files can be saved.');
        $this->line('7. Point your ZKTeco device to /'.trim((string) config('zkteco-adms.route_prefix', 'iclock'), '/'));
        $this->line('8. Toggle built-in DTR pairing with ZKTECO_ADMS_DTR_PAIRING_ENABLED');
        $this->line('9. Optional: enable the Python bridge with ZKTECO_ADMS_PYTHON_ENABLED for direct device tools.');
        $this->line('10. The default Python setup now points ZKTECO_ADMS_PYZK_ROOT to scripts/zkteco-adms.');

        return self::SUCCESS;
    }

    private function packageVersion(): string
    {
        if (class_exists(InstalledVersions::class)) {
            $version = InstalledVersions::getPrettyVersion('shadow046/zkteco-adms');

            if (is_string($version) && trim($version) !== '') {
                return trim($version);
            }
        }

        return 'dev-main';
    }
}
