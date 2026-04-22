<?php

namespace Shadow046\ZktecoAdms\Tests\Feature;

use Shadow046\ZktecoAdms\Tests\TestCase;

class PackageBootTest extends TestCase
{
    public function test_package_config_is_loaded(): void
    {
        $this->assertSame('iclock', config('zkteco-adms.route_prefix'));
    }
}
