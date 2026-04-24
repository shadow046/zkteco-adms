<?php

namespace Shadow046\ZktecoAdms\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Shadow046\ZktecoAdms\Services\AdmsCommandService;
use Shadow046\ZktecoAdms\Services\DtrPairingService;

class AdmsUiController extends Controller
{
    public function dashboard()
    {
        $attendanceTable = (string) config('zkteco-adms.attendance_table', 'inout_raw');
        $deviceStateTable = (string) config('zkteco-adms.device_state_table', 'adms_device_state');
        $deviceCommandsTable = (string) config('zkteco-adms.device_commands_table', 'device_commands');

        $recentAttendance = Schema::hasTable($attendanceTable)
            ? DB::table($attendanceTable)->orderByDesc('txndate')->orderByDesc('txntime')->limit(20)->get()
            : collect();

        $recentCommands = Schema::hasTable($deviceCommandsTable)
            ? DB::table($deviceCommandsTable)->orderByDesc('id')->limit(20)->get()
            : collect();

        $deviceStates = Schema::hasTable($deviceStateTable)
            ? DB::table($deviceStateTable)->orderBy('serial_number')->limit(20)->get()
            : collect();

        return view('zkteco-adms::dashboard', [
            'uiPrefix' => trim((string) config('zkteco-adms.ui_route_prefix', 'shadow046/adms'), '/'),
            'pageTitle' => 'ADMS Dashboard',
            'activeNav' => 'dashboard',
            'recentAttendance' => $recentAttendance,
            'recentCommands' => $recentCommands,
            'deviceStates' => $deviceStates,
            'summary' => [
                'attendance_count' => Schema::hasTable($attendanceTable) ? DB::table($attendanceTable)->count() : 0,
                'command_count' => Schema::hasTable($deviceCommandsTable) ? DB::table($deviceCommandsTable)->count() : 0,
                'device_count' => Schema::hasTable($deviceStateTable) ? DB::table($deviceStateTable)->count() : 0,
            ],
        ]);
    }

    public function queueAttlogQuery(Request $request, AdmsCommandService $commands): RedirectResponse
    {
        $validated = $request->validate([
            'sn' => ['required', 'string', 'max:50'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ]);

        $command = $commands->queueAttlogQuery(
            $validated['sn'],
            Carbon::parse($validated['start']),
            Carbon::parse($validated['end'])
        );

        return redirect()->route('zkteco-adms.ui.dashboard')
            ->with('status', "Queued device command #{$command['id']}")
            ->with('command_text', $command['command_text']);
    }

    public function attendanceIndex(Request $request)
    {
        $attendanceTable = (string) config('zkteco-adms.attendance_table', 'inout_raw');

        if (! Schema::hasTable($attendanceTable)) {
            abort(404, 'Attendance table not found.');
        }

        $validated = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'empno' => ['nullable', 'string', 'max:15'],
            'per_page' => ['nullable', 'integer', 'min:20', 'max:500'],
        ]);

        $start = isset($validated['start'])
            ? Carbon::parse($validated['start'])
            : now()->startOfDay();
        $end = isset($validated['end'])
            ? Carbon::parse($validated['end'])
            : now()->endOfDay();
        $perPage = (int) ($validated['per_page'] ?? 100);
        $dateTimeExpression = DB::getDriverName() === 'sqlite'
            ? "datetime(txndate || ' ' || txntime)"
            : 'TIMESTAMP(txndate, txntime)';

        $rows = DB::table($attendanceTable)
            ->whereRaw("{$dateTimeExpression} >= ?", [$start->format('Y-m-d H:i:s')])
            ->whereRaw("{$dateTimeExpression} <= ?", [$end->format('Y-m-d H:i:s')])
            ->when(
                isset($validated['empno']) && trim((string) $validated['empno']) !== '',
                fn ($query) => $query->where('empno', trim((string) $validated['empno']))
            )
            ->orderByDesc('txndate')
            ->orderByDesc('txntime')
            ->paginate($perPage)
            ->withQueryString();

        return view('zkteco-adms::attendance-index', [
            'uiPrefix' => trim((string) config('zkteco-adms.ui_route_prefix', 'shadow046/adms'), '/'),
            'pageTitle' => 'Attendance Explorer',
            'activeNav' => 'attendance',
            'attendanceRows' => $rows,
            'filterStart' => $start->format('Y-m-d\TH:i'),
            'filterEnd' => $end->format('Y-m-d\TH:i'),
            'filterEmpno' => trim((string) ($validated['empno'] ?? '')),
            'perPage' => $perPage,
        ]);
    }

    public function dailyLogsIndex(Request $request)
    {
        $dtrTable = (string) config('zkteco-adms.dtr_table', 'dtr');

        if (! Schema::hasTable($dtrTable)) {
            abort(404, 'DTR table not found.');
        }

        $validated = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'empno' => ['nullable', 'string', 'max:15'],
            'per_page' => ['nullable', 'integer', 'min:20', 'max:500'],
        ]);

        $start = isset($validated['start'])
            ? Carbon::parse($validated['start'])
            : now()->startOfMonth();
        $end = isset($validated['end'])
            ? Carbon::parse($validated['end'])
            : now()->endOfMonth();
        $perPage = (int) ($validated['per_page'] ?? 100);
        $empno = trim((string) ($validated['empno'] ?? ''));

        $dtrRows = DB::table($dtrTable)
            ->whereBetween('txndate', [$start->toDateString(), $end->toDateString()])
            ->when($empno !== '', fn ($query) => $query->where('empno', $empno))
            ->orderBy('txndate')
            ->orderBy('empno')
            ->paginate($perPage)
            ->withQueryString();

        $uiPrefix = trim((string) config('zkteco-adms.ui_route_prefix', 'shadow046/adms'), '/');

        $dtrRows->setCollection(
            $dtrRows->getCollection()->map(function (object $row) use ($uiPrefix): object {
                $date = Carbon::parse((string) $row->txndate);
                $row->day_name = $date->format('D');
                $row->logs_url = url('/'.$uiPrefix.'/attendance?empno='.urlencode((string) $row->empno)
                    .'&start='.urlencode($date->copy()->startOfDay()->format('Y-m-d H:i:s'))
                    .'&end='.urlencode($date->copy()->endOfDay()->format('Y-m-d H:i:s')));

                return $row;
            })
        );

        return view('zkteco-adms::daily-logs-index', [
            'uiPrefix' => $uiPrefix,
            'pageTitle' => 'Daily Logs',
            'activeNav' => 'daily-logs',
            'dtrRows' => $dtrRows,
            'filterStart' => $start->format('Y-m-d'),
            'filterEnd' => $end->format('Y-m-d'),
            'filterEmpno' => $empno,
            'perPage' => $perPage,
        ]);
    }

    public function sequenceAuditIndex(Request $request)
    {
        $attendanceTable = (string) config('zkteco-adms.attendance_table', 'inout_raw');

        if (! Schema::hasTable($attendanceTable)) {
            abort(404, 'Attendance table not found.');
        }

        foreach (['serialno', 'txndate', 'seqno'] as $requiredColumn) {
            if (! Schema::hasColumn($attendanceTable, $requiredColumn)) {
                abort(404, "Attendance table is missing the {$requiredColumn} column needed for sequence audit.");
            }
        }

        $validated = $request->validate([
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
            'serialno' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:20', 'max:500'],
        ]);

        $start = isset($validated['start'])
            ? Carbon::parse($validated['start'])
            : now()->startOfMonth();
        $end = isset($validated['end'])
            ? Carbon::parse($validated['end'])
            : now()->endOfMonth();
        $serialno = trim((string) ($validated['serialno'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 100);

        $auditRows = DB::table($attendanceTable)
            ->selectRaw('serialno, txndate, COUNT(*) as row_count, MIN(seqno) as min_seqno, MAX(seqno) as max_seqno, COUNT(DISTINCT seqno) as distinct_seqno_count')
            ->whereBetween('txndate', [$start->toDateString(), $end->toDateString()])
            ->when($serialno !== '', fn ($query) => $query->where('serialno', $serialno))
            ->groupBy('serialno', 'txndate')
            ->orderByDesc('txndate')
            ->orderBy('serialno')
            ->paginate($perPage)
            ->withQueryString();

        $auditRows->setCollection(
            $auditRows->getCollection()->map(function (object $row): object {
                $rowCount = (int) ($row->row_count ?? 0);
                $minSeq = (int) ($row->min_seqno ?? 0);
                $maxSeq = (int) ($row->max_seqno ?? 0);
                $distinctSeqCount = (int) ($row->distinct_seqno_count ?? 0);
                $expectedDistinctCount = $maxSeq >= $minSeq ? ($maxSeq - $minSeq + 1) : 0;
                $duplicateCount = max(0, $rowCount - $distinctSeqCount);
                $gapCount = max(0, $expectedDistinctCount - $distinctSeqCount);

                $flags = [];

                if ($minSeq !== 0) {
                    $flags[] = 'bad_start';
                }

                if ($duplicateCount > 0) {
                    $flags[] = 'duplicates';
                }

                if ($gapCount > 0) {
                    $flags[] = 'gaps';
                }

                $row->duplicate_count = $duplicateCount;
                $row->gap_count = $gapCount;
                $row->expected_distinct_count = $expectedDistinctCount;
                $row->flag_labels = collect($flags)
                    ->map(fn (string $flag): string => Str::headline(str_replace('_', ' ', $flag)))
                    ->values()
                    ->all();
                $row->has_issue = $flags !== [];

                return $row;
            })
        );

        return view('zkteco-adms::sequence-audit-index', [
            'uiPrefix' => trim((string) config('zkteco-adms.ui_route_prefix', 'shadow046/adms'), '/'),
            'pageTitle' => 'Sequence Audit',
            'activeNav' => 'sequence-audit',
            'auditRows' => $auditRows,
            'filterStart' => $start->format('Y-m-d'),
            'filterEnd' => $end->format('Y-m-d'),
            'filterSerialno' => $serialno,
            'perPage' => $perPage,
        ]);
    }

    public function runDailyLogsPairing(Request $request, DtrPairingService $pairingService): RedirectResponse
    {
        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
            'empno' => ['nullable', 'string', 'max:15'],
            'per_page' => ['nullable', 'integer', 'min:20', 'max:500'],
        ]);

        $result = $pairingService->pairRange(
            Carbon::parse($validated['start']),
            Carbon::parse($validated['end']),
            isset($validated['empno']) ? trim((string) $validated['empno']) : null
        );

        return redirect()
            ->route('zkteco-adms.ui.daily-logs', [
                'start' => Carbon::parse($validated['start'])->format('Y-m-d'),
                'end' => Carbon::parse($validated['end'])->format('Y-m-d'),
                'empno' => trim((string) ($validated['empno'] ?? '')),
                'per_page' => (int) ($validated['per_page'] ?? 100),
            ])
            ->with('status', "DTR pairing complete: {$result['group_count']} rows paired from {$result['raw_count']} raw logs.")
            ->with(
                'command_text',
                "Next-day links: {$result['nextday_out_count']} | Duplicate groups: {$result['duplicate_group_count']} | Skipped empty: {$result['skipped_empty_group_count']}"
            );
    }
}
