<?php

namespace Shadow046\ZktecoAdms\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'zkteco-adms:install';

    protected $description = 'Publish shadow046/zkteco-adms config and migrations.';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'zkteco-adms-config']);
        $this->call('vendor:publish', ['--tag' => 'zkteco-adms-migrations']);

        $this->components->info('shadow046/zkteco-adms assets published.');
        $this->line('Next steps:');
        $this->line('1. Review config/zkteco-adms.php');
        $this->line('2. Run php artisan migrate:adms');
        $this->line('   This is legacy-safe for existing inout_raw/dtr and existing ADMS tables.');
        $this->line('3. Make sure storage is writable so ATTPHOTO files can be saved.');
        $this->line('4. Point your ZKTeco device to /'.trim((string) config('zkteco-adms.route_prefix', 'iclock'), '/'));
        $this->line('5. Toggle built-in DTR pairing with ZKTECO_ADMS_DTR_PAIRING_ENABLED');

        return self::SUCCESS;
    }
}
