<?php

namespace Shadow046\ZktecoAdms\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MigrateAdmsCommand extends Command
{
    protected $signature = 'migrate:adms {--force : Force the operation to run in production} {--pretend : Dump the SQL queries that would be run} {--seed : Run seeders after migrations}';

    protected $description = 'Run only the shadow046/zkteco-adms package migrations.';

    public function handle(Filesystem $files): int
    {
        $migrationPath = __DIR__.'/../../database/migrations';

        if (! $files->isDirectory($migrationPath)) {
            $this->components->error('ADMS migration directory not found.');

            return self::FAILURE;
        }

        $parameters = [
            '--path' => $migrationPath,
            '--realpath' => true,
        ];

        if ($this->option('force')) {
            $parameters['--force'] = true;
        }

        if ($this->option('pretend')) {
            $parameters['--pretend'] = true;
        }

        if ($this->option('seed')) {
            $parameters['--seed'] = true;
        }

        $this->components->info('Running shadow046/zkteco-adms migrations only...');

        return (int) $this->call('migrate', $parameters);
    }
}
