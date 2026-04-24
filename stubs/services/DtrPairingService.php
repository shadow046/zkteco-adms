<?php

namespace App\Services\ZktecoAdms;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DtrPairingService
{
    public function pairRange(Carbon $fromDate, Carbon $toDate, ?string $empNo = null): array
    {
        $attendanceTable = (string) config('zkteco-adms.attendance_table', 'inout_raw');
        $dtrTable = (string) config('zkteco-adms.dtr_table', 'dtr');

        $rows = DB::table($attendanceTable)
            ->select(['empno', 'txndate', 'txntime', 'punch', 'serialno', 'raw_line'])
            ->whereBetween('txndate', [
                $fromDate->copy()->subDay()->toDateString(),
                $toDate->copy()->addDay()->toDateString(),
            ])
            ->when($empNo !== null && trim($empNo) !== '', fn ($query) => $query->where('empno', trim($empNo)))
            ->orderBy('empno')
            ->orderBy('txndate')
            ->orderBy('txntime')
            ->get();

        $now = now();
        $upserts = [];
        $nextDayLinkedCount = 0;
        $duplicatePunchGroups = 0;
        $skippedEmptyGroups = 0;
        $groupedByEmployee = $rows->groupBy('empno');

        foreach ($groupedByEmployee as $employeeRows) {
            $employeeNumber = (string) $employeeRows->first()->empno;
            $groupedByDate = $employeeRows->groupBy('txndate')->sortKeys();
            $existingDtrStates = $this->loadExistingDtrStates($dtrTable, $employeeNumber, $fromDate->copy()->subDay(), $toDate->copy()->addDay());
            $dateKeys = $groupedByDate->keys()->values()->all();
            $consumedPunch4 = [];
            $upsertsByKey = [];

            foreach ($dateKeys as $index => $dateKey) {
                /** @var Collection<int, object> $groupRows */
                $groupRows = $groupedByDate->get($dateKey, collect());
                $groupKey = $employeeNumber.'|'.$dateKey;
                $currentDate = Carbon::parse($dateKey);
                $previousDateKey = $index > 0 ? $dateKeys[$index - 1] : null;
                $existingCurrentState = $existingDtrStates[$groupKey] ?? $this->emptyDtrState($employeeNumber, $dateKey);
                $consumedForCurrentDate = $consumedPunch4[$dateKey] ?? [];
                $buckets = $this->buildPunchBuckets($groupRows, $consumedForCurrentDate);

                if ($previousDateKey !== null) {
                    $previousKey = $employeeNumber.'|'.$previousDateKey;
                    $resolvedPreviousState = $upsertsByKey[$previousKey]
                        ?? ($existingDtrStates[$previousKey] ?? $this->emptyDtrState($employeeNumber, $previousDateKey));
                    $carryover = $this->assignCarryoverPunchesToPreviousState(
                        $resolvedPreviousState,
                        $existingCurrentState,
                        $buckets,
                        $now
                    );

                    if ($carryover['updated']) {
                        $upsertsByKey[$previousKey] = $carryover['previous_state'];
                        $consumedPunch4[$dateKey] = array_merge($consumedPunch4[$dateKey] ?? [], $carryover['consumed_timestamps']);
                        $nextDayLinkedCount += $carryover['out_linked_count'];
                        $buckets = $this->buildPunchBuckets($groupRows, $consumedPunch4[$dateKey]);
                    }
                }

                $paired = $this->pairAttendanceGroup($buckets);

                if ($paired['duplicate_group']) {
                    $duplicatePunchGroups++;
                }

                if ($currentDate->lt($fromDate->copy()->startOfDay()) || $currentDate->gt($toDate->copy()->startOfDay())) {
                    continue;
                }

                if (! $this->hasAnyPairedValue($paired)) {
                    $skippedEmptyGroups++;
                    continue;
                }

                $upsertsByKey[$groupKey] = $this->mergePairedIntoState($existingCurrentState, $paired, $now);
            }

            foreach ($upsertsByKey as $upsert) {
                $upserts[] = $upsert;
            }
        }

        if ($upserts !== []) {
            $upserts = array_map(
                fn (array $state): array => $this->normalizeStateForPersistence($state),
                $upserts
            );

            DB::table($dtrTable)->upsert(
                $upserts,
                ['empno', 'txndate'],
                ['shift', 'shift_rest', 'in', 'break_out', 'break_in', 'out', 'nextday_out', 'txn_remarks', 'txn_remarks1', 'remarks', 'updated_at']
            );
        }

        return [
            'group_count' => count($upserts),
            'raw_count' => $rows->count(),
            'nextday_out_count' => $nextDayLinkedCount,
            'duplicate_group_count' => $duplicatePunchGroups,
            'skipped_empty_group_count' => $skippedEmptyGroups,
        ];
    }

    private function buildPunchBuckets(Collection $rows, array $consumedPunch4Timestamps = []): array
    {
        $punchBuckets = ['1' => [], '2' => [], '3' => [], '4' => []];

        foreach ($rows as $row) {
            $punch = trim((string) ($row->punch ?? ''));
            if (! array_key_exists($punch, $punchBuckets)) {
                continue;
            }

            $timestamp = Carbon::parse($row->txndate.' '.$row->txntime)->format('Y-m-d H:i:s');
            if ($punch === '4' && in_array($timestamp, $consumedPunch4Timestamps, true)) {
                continue;
            }

            $punchBuckets[$punch][] = $timestamp;
        }

        return $punchBuckets;
    }

    private function pairAttendanceGroup(array $punchBuckets): array
    {
        $remarks = [];
        $duplicateDetails = [];
        $duplicateGroup = false;

        foreach ($punchBuckets as $punch => $items) {
            if (count($items) > 1) {
                $duplicateGroup = true;
                $remarks[] = 'multiple_punch_'.$punch.'('.count($items).')';
                $duplicateDetails[] = 'punch_'.$punch.': '.implode(', ', $items);
            }
        }

        return [
            'in' => $this->firstOrNull($punchBuckets['1']),
            'break_out' => $this->firstOrNull($punchBuckets['2']),
            'break_in' => $this->firstOrNull($punchBuckets['3']),
            'out' => $this->lastOrNull($punchBuckets['4']),
            'nextday_out' => 'N',
            'txn_remarks' => implode(' | ', $remarks),
            'txn_remarks1' => implode(' | ', $duplicateDetails),
            'remarks' => '',
            'duplicate_group' => $duplicateGroup,
        ];
    }

    private function firstOrNull(array $items): ?string { return $items[0] ?? null; }
    private function lastOrNull(array $items): ?string { return $items === [] ? null : $items[count($items) - 1]; }

    private function hasAnyPairedValue(array $paired): bool
    {
        return $paired['in'] !== null
            || $paired['break_out'] !== null
            || $paired['break_in'] !== null
            || $paired['out'] !== null
            || trim((string) $paired['txn_remarks']) !== ''
            || trim((string) $paired['txn_remarks1']) !== '';
    }

    private function appendRemark(string $existing, string $remark): string
    {
        $existing = trim($existing);
        $remark = trim($remark);
        if ($remark === '') return $existing;
        if ($existing === '') return $remark;
        return $existing.' | '.$remark;
    }

    private function loadExistingDtrStates(string $table, string $empNo, Carbon $fromDate, Carbon $toDate): array
    {
        return DB::table($table)
            ->where('empno', $empNo)
            ->whereBetween('txndate', [$fromDate->toDateString(), $toDate->toDateString()])
            ->get()
            ->mapWithKeys(function (object $row): array {
                $key = (string) $row->empno.'|'.(string) $row->txndate;
                return [$key => $this->stateFromRow($row)];
            })
            ->all();
    }

    private function stateFromRow(object $row): array
    {
        return [
            'empno' => (string) $row->empno,
            'txndate' => (string) $row->txndate,
            'shift' => (string) ($row->shift ?? ''),
            'shift_rest' => (string) ($row->shift_rest ?? ''),
            'in' => $row->in,
            'in_manual' => (string) ($row->in_manual ?? ''),
            'break_out' => $row->break_out,
            'break_out_manual' => (string) ($row->break_out_manual ?? ''),
            'break_in' => $row->break_in,
            'break_in_manual' => (string) ($row->break_in_manual ?? ''),
            'out' => $row->out,
            'out_manual' => (string) ($row->out_manual ?? ''),
            'nextday_out' => (string) ($row->nextday_out ?? 'N'),
            'txn_remarks' => (string) ($row->txn_remarks ?? ''),
            'txn_remarks1' => (string) ($row->txn_remarks1 ?? ''),
            'remarks' => (string) ($row->remarks ?? ''),
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ];
    }

    private function emptyDtrState(string $empNo, string $txnDate): array
    {
        $now = now();
        return [
            'empno' => $empNo,
            'txndate' => $txnDate,
            'shift' => '',
            'shift_rest' => '',
            'in' => null,
            'in_manual' => '',
            'break_out' => null,
            'break_out_manual' => '',
            'break_in' => null,
            'break_in_manual' => '',
            'out' => null,
            'out_manual' => '',
            'nextday_out' => 'N',
            'txn_remarks' => '',
            'txn_remarks1' => '',
            'remarks' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function mergePairedIntoState(array $existingState, array $paired, $now): array
    {
        $merged = $existingState;
        $candidateState = $existingState;
        $pairingRemarks = trim((string) ($paired['txn_remarks'] ?? ''));
        $pairingRemarkDetails = trim((string) ($paired['txn_remarks1'] ?? ''));

        foreach (['in', 'break_out', 'break_in', 'out'] as $field) {
            if (! $this->isFieldManualLocked($existingState, $field)) {
                $candidateState[$field] = $paired[$field] ?? null;
            }
        }

        foreach (['in', 'break_out', 'break_in', 'out'] as $field) {
            if ($this->isFieldManualLocked($existingState, $field)) {
                $pairingRemarks = $this->appendRemark($pairingRemarks, 'manual_'.$field.'_preserved');
                continue;
            }

            $merged[$field] = null;
            if ($paired[$field] !== null && $this->isChronologicallyValid($field, $paired[$field], $candidateState)) {
                $merged[$field] = $paired[$field];
            } elseif ($paired[$field] !== null) {
                $pairingRemarks = $this->appendRemark($pairingRemarks, 'ignored_'.$field.'_chronology');
                $pairingRemarkDetails = $this->appendRemark($pairingRemarkDetails, $field.': '.$paired[$field]);
            }
        }

        if (! $this->isFieldManualLocked($existingState, 'out')) {
            $merged['nextday_out'] = ($paired['nextday_out'] ?? 'N') === 'Y' ? 'Y' : 'N';
        }

        $merged['txn_remarks'] = $pairingRemarks;
        $merged['txn_remarks1'] = $pairingRemarkDetails;
        $merged['remarks'] = (string) ($existingState['remarks'] ?? '');
        $merged['updated_at'] = $now;
        $merged['created_at'] = $existingState['created_at'] ?? $now;
        return $this->normalizeStateForPersistence($merged);
    }

    private function dtrHasDayActivity(array $state): bool
    {
        return ! empty($state['in']) || ! empty($state['break_out']) || ! empty($state['break_in']);
    }

    private function assignCarryoverPunchesToPreviousState(array $previousState, array $currentState, array $currentBuckets, $now): array
    {
        if ($this->currentDayOwnsPunches($currentState, $currentBuckets) || ! $this->dtrHasDayActivity($previousState)) {
            return ['updated' => false, 'previous_state' => $previousState, 'consumed_timestamps' => [], 'out_linked_count' => 0];
        }

        $consumed = [];
        $outLinkedCount = 0;
        $updated = false;
        $carryMappings = [
            '2' => ['field' => 'break_out', 'remark' => 'nextday_break_out_linked', 'detail' => 'borrowed_break_out: '],
            '3' => ['field' => 'break_in', 'remark' => 'nextday_break_in_linked', 'detail' => 'borrowed_break_in: '],
            '4' => ['field' => 'out', 'remark' => 'nextday_out_linked', 'detail' => 'borrowed_out: '],
        ];

        foreach ($carryMappings as $punch => $meta) {
            if (! empty($previousState[$meta['field']])) continue;
            $candidate = $punch === '4' ? $this->lastOrNull($currentBuckets[$punch]) : $this->firstOrNull($currentBuckets[$punch]);
            if ($candidate === null) continue;
            $candidateState = $previousState;
            $candidateState[$meta['field']] = $candidate;
            if (! $this->isChronologicallyValid($meta['field'], $candidate, $candidateState)) {
                $previousState['txn_remarks'] = $this->appendRemark((string) ($previousState['txn_remarks'] ?? ''), 'ignored_'.$meta['field'].'_chronology');
                $previousState['txn_remarks1'] = $this->appendRemark((string) ($previousState['txn_remarks1'] ?? ''), $meta['field'].': '.$candidate);
                continue;
            }

            $previousState[$meta['field']] = $candidate;
            $previousState['txn_remarks'] = $this->appendRemark((string) ($previousState['txn_remarks'] ?? ''), $meta['remark']);
            $previousState['txn_remarks1'] = $this->appendRemark((string) ($previousState['txn_remarks1'] ?? ''), $meta['detail'].$candidate);
            $previousState['updated_at'] = $now;
            $previousState['created_at'] ??= $now;
            $updated = true;
            $consumed[] = $candidate;
            if ($meta['field'] === 'out') {
                $previousState['nextday_out'] = 'Y';
                $outLinkedCount++;
            }
        }

        return [
            'updated' => $updated,
            'previous_state' => $this->normalizeStateForPersistence($previousState),
            'consumed_timestamps' => $consumed,
            'out_linked_count' => $outLinkedCount,
        ];
    }

    private function normalizeStateForPersistence(array $state): array
    {
        $state['txn_remarks'] = $this->truncateString((string) ($state['txn_remarks'] ?? ''), 100);
        $state['txn_remarks1'] = $this->truncateString((string) ($state['txn_remarks1'] ?? ''), 100);
        $state['remarks'] = $this->truncateString((string) ($state['remarks'] ?? ''), 100);

        return $state;
    }

    private function truncateString(string $value, int $maxLength): string
    {
        if ($value === '' || mb_strlen($value) <= $maxLength) {
            return $value;
        }

        return mb_substr($value, 0, $maxLength);
    }

    private function currentDayOwnsPunches(array $currentState, array $currentBuckets): bool
    {
        return ! empty($currentState['in'])
            || ! empty($currentState['break_out'])
            || ! empty($currentState['break_in'])
            || ! empty($currentState['out'])
            || $currentBuckets['1'] !== [];
    }

    private function isFieldManualLocked(array $state, string $field): bool
    {
        $manualColumn = match ($field) {
            'in' => 'in_manual',
            'break_out' => 'break_out_manual',
            'break_in' => 'break_in_manual',
            'out' => 'out_manual',
            default => null,
        };

        return $manualColumn !== null && trim((string) ($state[$manualColumn] ?? '')) === '*';
    }

    private function isChronologicallyValid(string $field, string $candidate, array $state): bool
    {
        return match ($field) {
            'in' => $this->isBefore($candidate, $state['break_out'] ?? null)
                && $this->isBefore($candidate, $state['break_in'] ?? null)
                && $this->isBefore($candidate, $state['out'] ?? null),
            'break_out' => $this->isAfter($candidate, $state['in'] ?? null)
                && $this->isBefore($candidate, $state['break_in'] ?? null)
                && $this->isBefore($candidate, $state['out'] ?? null),
            'break_in' => $this->isAfter($candidate, $state['in'] ?? null)
                && $this->isAfter($candidate, $state['break_out'] ?? null)
                && $this->isBefore($candidate, $state['out'] ?? null),
            'out' => $this->isAfter($candidate, $state['in'] ?? null)
                && $this->isAfter($candidate, $state['break_out'] ?? null)
                && $this->isAfter($candidate, $state['break_in'] ?? null),
            default => true,
        };
    }

    private function isBefore(string $candidate, ?string $other): bool
    {
        return $other === null || trim($other) === '' ? true : $candidate < $other;
    }

    private function isAfter(string $candidate, ?string $other): bool
    {
        return $other === null || trim($other) === '' ? true : $candidate > $other;
    }
}
