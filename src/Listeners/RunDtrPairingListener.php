<?php

namespace Shadow046\ZktecoAdms\Listeners;

use Carbon\Carbon;
use Shadow046\ZktecoAdms\Events\AttendanceLogsStored;
use Shadow046\ZktecoAdms\Services\DtrPairingService;

class RunDtrPairingListener
{
    public function __construct(private readonly DtrPairingService $pairingService)
    {
    }

    public function handle(AttendanceLogsStored $event): void
    {
        if (! (bool) config('zkteco-adms.dtr_pairing.enabled', true)) {
            return;
        }

        collect($event->rows)
            ->filter(fn ($row): bool => is_array($row) && trim((string) ($row['empno'] ?? '')) !== '' && trim((string) ($row['txndate'] ?? '')) !== '')
            ->groupBy(fn (array $row): string => trim((string) $row['empno']))
            ->each(function ($employeeRows, string $empno): void {
                $dates = collect($employeeRows)
                    ->pluck('txndate')
                    ->map(fn ($date): string => trim((string) $date))
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();

                if ($dates->isEmpty()) {
                    return;
                }

                $this->pairingService->pairRange(
                    Carbon::parse($dates->first()),
                    Carbon::parse($dates->last()),
                    $empno
                );
            });
    }
}
