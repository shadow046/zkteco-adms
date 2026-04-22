<?php

namespace Shadow046\ZktecoAdms\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Shadow046\ZktecoAdms\ZktecoAdmsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ZktecoAdmsServiceProvider::class,
        ];
    }
}
