<?php

namespace Shadow046\ZktecoAdms\Events;

class AttendanceLogsStored
{
    public function __construct(
        public readonly string $serialNumber,
        public readonly array $rows,
    ) {
    }
}
