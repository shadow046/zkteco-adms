<?php

namespace Shadow046\ZktecoAdms\Services;

use Shadow046\ZktecoAdms\Events\AttendanceLogsStored;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AdmsCoreService
{
    private array $columnCache = [];

    public function handleCdata(Request $request): Response
    {
        $serialNumber = trim((string) $request->query('SN', ''));
        $options = trim((string) $request->query('options', ''));
        $table = Str::upper(trim((string) $request->query('table', '')));
        $stamp = trim((string) $request->query('Stamp', ''));
        $content = (string) $request->getContent();

        $this->logHttpRequest($request, $serialNumber, $table, $content);
        $this->touchDeviceStateFromRequest($request);

        if ($serialNumber === '') {
            return response('ERROR', 400);
        }

        if ($options === 'all') {
            return response(
                $this->buildCdataOptionsResponse(
                    $serialNumber,
                    trim((string) $request->query('pushver', ''))
                ),
                200,
                ['Content-Type' => 'text/plain']
            );
        }

        if ($table === '') {
            return response('ERROR', 400);
        }

        match ($table) {
            'ATTLOG' => $this->storeAttendanceLogs($serialNumber, $stamp, $content, $request),
            'OPERLOG' => $this->storeOperationLogs($serialNumber, $stamp, $content, $request),
            'USERINFO' => $this->storeUserInfo($serialNumber, $content, $request),
            'FINGERTMP' => $this->storeFingerprintTemplates($serialNumber, $content, $request),
            'ATTPHOTO' => $this->storeAttendancePhoto($serialNumber, $stamp, $content, $request),
            default => null,
        };

        return response('OK');
    }

    public function handleFdata(Request $request): Response
    {
        $serialNumber = trim((string) $request->query('SN', ''));
        $table = Str::upper(trim((string) $request->query('table', 'ATTPHOTO')));
        $stamp = trim((string) $request->query('PhotoStamp', $request->query('Stamp', '')));
        $content = (string) $request->getContent();

        $this->logHttpRequest($request, $serialNumber, $table, $content);
        $this->touchDeviceStateFromRequest($request);

        if ($serialNumber !== '' && $table === 'ATTPHOTO') {
            $this->storeAttendancePhoto($serialNumber, $stamp, $content, $request);
        }

        return response('OK');
    }

    public function handleGetRequest(Request $request): Response
    {
        $serialNumber = trim((string) $request->query('SN', ''));
        $deviceInfo = $this->nullableString((string) $request->query('INFO', ''));

        if ($serialNumber === '') {
            return response('OK');
        }

        DB::table($this->table('device_polls'))->insert([
            'serial_number' => $serialNumber,
            'device_info' => $deviceInfo,
            'query_string' => $request->getQueryString(),
            'client_ip' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $state = $this->getOrCreateDeviceState($serialNumber);
        $this->updateDeviceState($serialNumber, [
            'options' => $deviceInfo ?? ($state->options ?? ''),
            'pushver' => trim((string) $request->query('pushver', $state->pushver ?? '')),
            'language' => trim((string) $request->query('language', $state->language ?? '')),
        ]);

        $command = DB::table($this->table('device_commands'))
            ->where('serial_number', $serialNumber)
            ->where('status', 'pending')
            ->orderBy('id')
            ->first();

        if ($command === null) {
            return response('OK');
        }

        DB::table($this->table('device_commands'))
            ->where('id', $command->id)
            ->update([
                'status' => 'sent',
                'sent_at' => now(),
                'last_polled_at' => now(),
                'client_ip' => $request->ip(),
                'updated_at' => now(),
            ]);

        return response((string) $command->command_text, 200, ['Content-Type' => 'text/plain']);
    }

    public function handleDeviceCmd(Request $request): Response
    {
        $serialNumber = trim((string) $request->query('SN', ''));
        $content = (string) $request->getContent();

        $this->logHttpRequest($request, $serialNumber, 'DEVICECMD', $content);
        $this->touchDeviceStateFromRequest($request);

        if ($serialNumber !== '') {
            $this->updateDeviceState($serialNumber, [
                'lasttxndatetime' => now()->format('Y-m-d H:i:s'),
            ]);
        }

        return response('OK');
    }

    public function storeDirectAttendanceRecords(array $records, string $serialNumber, ?string $clientIp = null): array
    {
        if ($serialNumber === '') {
            return [
                'inserted' => 0,
                'updated' => 0,
                'rows' => [],
            ];
        }

        $attendanceTable = $this->table('attendance');
        $state = $this->getOrCreateDeviceState($serialNumber);
        $latestTxn = $this->parseTimestamp($state->lasttxndatetime ?? null);
        $latestAttlogDate = $this->parseTimestamp($state->attlogdate ?? null);
        $rows = [];
        $inserted = 0;
        $updated = 0;
        $affectedDates = [];

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $timestamp = $this->parseTimestamp($record['timestamp'] ?? null);

            if ($timestamp === null) {
                continue;
            }

            $empno = $this->nullableString(isset($record['user_id']) ? (string) $record['user_id'] : null);

            if ($empno === null) {
                $empno = $this->nullableString(isset($record['uid']) ? (string) $record['uid'] : null);
            }

            if ($empno === null) {
                continue;
            }

            $txndate = $timestamp->toDateString();
            $txntime = $timestamp->format('H:i:s');
            $row = [
                'empno' => $empno,
                'txndate' => $txndate,
                'txntime' => $txntime,
            ];

            $this->fillIfColumnExists($attendanceTable, $row, 'entity03', '');
            $this->fillIfColumnExists($attendanceTable, $row, 'serialno', $serialNumber);
            $this->fillIfColumnExists($attendanceTable, $row, 'seqno', 0);
            $this->fillIfColumnExists($attendanceTable, $row, 'punch', $this->nullableString(isset($record['punch']) ? (string) $record['punch'] : null) ?? '');
            $this->fillIfColumnExists($attendanceTable, $row, 'status', $this->nullableString(isset($record['status']) ? (string) $record['status'] : null) ?? '');
            $this->fillIfColumnExists($attendanceTable, $row, 'stamp', null);
            $this->fillIfColumnExists($attendanceTable, $row, 'raw_line', json_encode($record, JSON_UNESCAPED_SLASHES));
            $this->fillIfColumnExists($attendanceTable, $row, 'client_ip', $clientIp);
            $this->fillIfColumnExists($attendanceTable, $row, 'created_at', now());
            $this->fillIfColumnExists($attendanceTable, $row, 'updated_at', now());

            $rows[] = $row;
            $affectedDates[$txndate] = true;

            if ($latestTxn === null || $timestamp->gt($latestTxn)) {
                $latestTxn = $timestamp->copy();
            }

            if ($latestAttlogDate === null || $timestamp->gt($latestAttlogDate)) {
                $latestAttlogDate = $timestamp->copy();
            }
        }

        foreach ($rows as $row) {
            $match = $this->attendanceRecordIdentity($attendanceTable, $row);
            $existing = DB::table($attendanceTable)->where($match)->exists();

            if ($existing) {
                $updatePayload = $row;
                unset($updatePayload['created_at']);

                DB::table($attendanceTable)
                    ->where($match)
                    ->update($updatePayload);

                $updated++;

                continue;
            }

            DB::table($attendanceTable)->insert($row);
            $inserted++;
        }

        $lastSeqSnapshot = $this->resequencedAttendanceDates(
            $attendanceTable,
            $serialNumber,
            array_keys($affectedDates)
        );

        $latestSeqDate = $latestAttlogDate?->toDateString();
        $latestSeqValue = $latestSeqDate !== null
            ? (int) ($lastSeqSnapshot[$latestSeqDate] ?? 0)
            : (int) ($state->seqno ?? 0);

        $this->updateDeviceState($serialNumber, [
            'sysdate' => $latestSeqDate ?? ($state->sysdate ?? ''),
            'seqno' => $latestSeqValue,
            'lasttxndatetime' => $latestTxn?->format('Y-m-d H:i:s') ?? ($state->lasttxndatetime ?? ''),
            'attlogdate' => $latestAttlogDate?->format('Y-m-d H:i:s') ?? ($state->attlogdate ?? ''),
        ]);

        if ($rows !== []) {
            try {
                Event::dispatch(new AttendanceLogsStored($serialNumber, $rows));
            } catch (\Throwable $exception) {
                Log::error('ZKTeco ADMS direct attendance import pairing failed after raw log ingest.', [
                    'serial_number' => $serialNumber,
                    'row_count' => count($rows),
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'rows' => $rows,
        ];
    }

    private function buildCdataOptionsResponse(string $serialNumber, string $pushver): string
    {
        $state = $this->getOrCreateDeviceState($serialNumber);

        $lines = [
            "GET OPTION FROM: {$serialNumber}",
            'Stamp=0',
            'OPStamp=0',
            'PhotoStamp=0',
            'ErrorDelay=60',
            'Delay=30',
            'TransTimes=00:00;23:59',
            'TransInterval=1',
            'TransFlag=1111000000',
            'Realtime=1',
            'Encrypt=0',
        ];

        if ($pushver !== '2.32') {
            $lines[] = 'TimeZone=+08:00';
            $lines[] = 'Timeout=60';
        }

        $lines[] = 'SyncTime=3600';
        $lines[] = $pushver === '2.32'
            ? 'ServerVer=3.4.1 20100607'
            : 'ServerVer=3.4.1 2010-06-07';

        if ($pushver === '2.32') {
            $lines[] = 'ATTLOGStamp='.(string) ($state->attlogstamp ?? '0');
            $lines[] = 'OPERLOGStamp='.(string) ($state->oplogstamp ?? '0');
            $lines[] = 'ATTPHOTOStamp='.(string) ($state->attphotostamp ?? '0');

            return implode("\n", $lines)."\n";
        }

        $lines[] = 'ATTLOGStamp='.$this->formatDeviceStampValue(
            $state->lasttxndatetime ?? null,
            $state->attlogdate ?? null
        );
        $lines[] = 'OPERLOGStamp='.$this->formatDeviceStampValue(
            $state->lasttxndatetime ?? null,
            $state->oplogdate ?? null
        );
        $lines[] = 'ATTPHOTOStamp='.$this->formatDeviceStampValue(
            $state->lasttxndatetime ?? null,
            $state->attphotodate ?? null
        );

        return implode("\n", $lines)."\n";
    }

    private function formatDeviceStampValue(?string $lastTxnDatetime, ?string $dateField): string
    {
        if (! empty($dateField)) {
            try {
                return Carbon::parse($dateField, config('app.display_timezone', 'Asia/Manila'))
                    ->format('Y-m-d\TH:i:s');
            } catch (\Throwable) {
            }
        }

        if (! empty($lastTxnDatetime)) {
            try {
                return Carbon::parse($lastTxnDatetime, config('app.display_timezone', 'Asia/Manila'))
                    ->format('Y-m-d\TH:i:s');
            } catch (\Throwable) {
            }
        }

        return '2020-09-01T00:00:01';
    }

    private function storeAttendanceLogs(string $serialNumber, string $stamp, string $content, Request $request): void
    {
        $attendanceTable = $this->table('attendance');
        $lines = $this->splitLines($content);
        $rows = [];
        $state = $this->getOrCreateDeviceState($serialNumber);
        $affectedDates = [];
        $latestTxn = $this->parseTimestamp($state->lasttxndatetime ?? null);
        $latestAttlogDate = $this->parseTimestamp($state->attlogdate ?? null);

        foreach ($lines as $line) {
            $columns = explode("\t", $line);

            if (count($columns) < 2) {
                continue;
            }

            $timestamp = $this->parseTimestamp($columns[1] ?? null);

            if ($timestamp === null) {
                continue;
            }

            $empno = trim((string) ($columns[0] ?? ''));
            if ($empno === '') {
                continue;
            }

            $txndate = $timestamp->toDateString();
            $txntime = $timestamp->format('H:i:s');

            $record = [
                'empno' => $empno,
                'txndate' => $txndate,
                'txntime' => $txntime,
            ];

            $this->fillIfColumnExists($attendanceTable, $record, 'entity03', '');
            $this->fillIfColumnExists($attendanceTable, $record, 'serialno', $serialNumber);
            $this->fillIfColumnExists($attendanceTable, $record, 'punch', $this->nullableString($columns[2] ?? null) ?? '');
            $this->fillIfColumnExists($attendanceTable, $record, 'status', '');
            $this->fillIfColumnExists($attendanceTable, $record, 'stamp', $stamp !== '' ? $stamp : null);
            $this->fillIfColumnExists($attendanceTable, $record, 'raw_line', $line);
            $this->fillIfColumnExists($attendanceTable, $record, 'client_ip', $request->ip());
            $this->fillIfColumnExists($attendanceTable, $record, 'created_at', now());
            $this->fillIfColumnExists($attendanceTable, $record, 'updated_at', now());
            $this->fillIfColumnExists($attendanceTable, $record, 'seqno', 0);

            $rows[] = $record;
            $affectedDates[$txndate] = true;

            if ($latestTxn === null || $timestamp->gt($latestTxn)) {
                $latestTxn = $timestamp->copy();
            }

            if ($latestAttlogDate === null || $timestamp->gt($latestAttlogDate)) {
                $latestAttlogDate = $timestamp->copy();
            }
        }

        if ($rows === []) {
            Log::info('ZKTeco ADMS ATTLOG payload produced no parsable rows.', [
                'serial_number' => $serialNumber,
                'stamp' => $stamp,
                'content_length' => strlen($content),
                'line_count' => count($lines),
                'sample_line' => $lines[0] ?? null,
            ]);
            return;
        }

        $inserted = 0;
        $updated = 0;

        foreach ($rows as $record) {
            $match = $this->attendanceRecordIdentity($attendanceTable, $record);
            $existing = DB::table($attendanceTable)->where($match)->exists();

            if ($existing) {
                $updatePayload = $record;
                unset($updatePayload['created_at']);

                DB::table($attendanceTable)
                    ->where($match)
                    ->update($updatePayload);

                $updated++;

                continue;
            }

            DB::table($attendanceTable)->insert($record);
            $inserted++;
        }

        $lastSeqSnapshot = $this->resequencedAttendanceDates(
            $attendanceTable,
            $serialNumber,
            array_keys($affectedDates)
        );

        $latestSeqDate = $latestAttlogDate?->toDateString();
        $latestSeqValue = $latestSeqDate !== null
            ? (int) ($lastSeqSnapshot[$latestSeqDate] ?? 0)
            : (int) ($state->seqno ?? 0);

        $this->updateDeviceState($serialNumber, [
            'attlogstamp' => $stamp !== '' ? $stamp : ($state->attlogstamp ?? '0'),
            'attlogdate' => $latestAttlogDate?->format('Y-m-d H:i:s') ?? ($state->attlogdate ?? ''),
            'sysdate' => $latestSeqDate ?? ($state->sysdate ?? ''),
            'seqno' => $latestSeqValue,
            'lasttxndatetime' => $latestTxn?->format('Y-m-d H:i:s') ?? ($state->lasttxndatetime ?? ''),
        ]);

        Log::info('ZKTeco ADMS ATTLOG rows stored.', [
            'serial_number' => $serialNumber,
            'stamp' => $stamp,
            'content_length' => strlen($content),
            'line_count' => count($lines),
            'parsed_rows' => count($rows),
            'inserted' => $inserted,
            'updated' => $updated,
            'first_row' => $rows[0] ?? null,
        ]);

        try {
            Event::dispatch(new AttendanceLogsStored($serialNumber, $rows));
        } catch (\Throwable $exception) {
            Log::error('ZKTeco ADMS attendance pairing failed after raw log ingest.', [
                'serial_number' => $serialNumber,
                'row_count' => count($rows),
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function storeOperationLogs(string $serialNumber, string $stamp, string $content, Request $request): void
    {
        $records = [];

        foreach ($this->splitLines($content) as $line) {
            $columns = explode("\t", $line);

            if (count($columns) < 3) {
                continue;
            }

            $records[] = [
                'serial_number' => $serialNumber,
                'stamp' => $stamp !== '' ? $stamp : null,
                'operation' => trim((string) $columns[0]),
                'operator_id' => $this->nullableString($columns[1] ?? null),
                'occurred_at' => $this->parseTimestamp($columns[2] ?? null),
                'param_1' => $this->nullableString($columns[3] ?? null),
                'param_2' => $this->nullableString($columns[4] ?? null),
                'param_3' => $this->nullableString($columns[5] ?? null),
                'param_4' => $this->nullableString($columns[6] ?? null),
                'raw_line' => $line,
                'client_ip' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($records === []) {
            return;
        }

        DB::table($this->table('operation_logs'))->upsert(
            $records,
            ['serial_number', 'operation', 'operator_id', 'occurred_at'],
            ['stamp', 'param_1', 'param_2', 'param_3', 'param_4', 'raw_line', 'client_ip', 'updated_at']
        );
    }

    private function storeAttendancePhoto(string $serialNumber, string $stamp, string $content, Request $request): void
    {
        $jpegOffset = strpos($content, "\xFF\xD8");

        if ($jpegOffset === false) {
            return;
        }

        $headerText = substr($content, 0, $jpegOffset);
        $imageBinary = substr($content, $jpegOffset);
        $headers = [];

        foreach ($this->splitLines($headerText) as $line) {
            if (! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $headers[Str::upper(trim($key))] = trim($value);
        }

        $sourcePin = $headers['PIN'] ?? null;
        $capturedAt = $this->parsePhotoTimestamp($sourcePin);
        $empno = $this->extractPhotoEmpno($sourcePin);
        $filename = $this->buildAttendancePhotoFilename($empno, $capturedAt, $sourcePin);
        $relativePath = trim(config('zkteco-adms.photo_directory', 'adms_photos'), '/').'/'.$serialNumber.'/'.$filename;

        Storage::disk((string) config('zkteco-adms.photo_disk', 'local'))->put($relativePath, $imageBinary);

        DB::table($this->table('attendance_photos'))->updateOrInsert(
            [
                'serial_number' => $serialNumber,
                'filename' => $filename,
            ],
            [
                'stamp' => $stamp !== '' ? $stamp : null,
                'pin' => $this->nullableString($sourcePin),
                'command' => $this->nullableString($headers['CMD'] ?? null),
                'declared_size' => $this->nullableInt($headers['SIZE'] ?? null),
                'captured_at' => $capturedAt,
                'storage_path' => $relativePath,
                'sha256' => hash('sha256', $imageBinary),
                'client_ip' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $state = $this->getOrCreateDeviceState($serialNumber);
        $this->updateDeviceState($serialNumber, [
            'attphotostamp' => $stamp !== '' ? $stamp : ($state->attphotostamp ?? '0'),
            'attphotodate' => $capturedAt?->format('Y-m-d H:i:s') ?? ($state->attphotodate ?? ''),
        ]);
    }

    private function storeUserInfo(string $serialNumber, string $content, Request $request): void
    {
        $records = [];

        foreach ($this->splitLines($content) as $line) {
            $fields = $this->parseEqualsTabbedPairs($line);
            $pin = $this->nullableString($fields['PIN'] ?? null);

            if ($pin === null) {
                continue;
            }

            $records[] = [
                'serial_number' => $serialNumber,
                'pin' => $pin,
                'name' => $this->nullableString($fields['NAME'] ?? null),
                'privilege' => $this->nullableString($fields['PRI'] ?? null),
                'password' => $this->nullableString($fields['PASSWD'] ?? null),
                'card' => $this->nullableString($fields['CARD'] ?? null),
                'grp' => $this->nullableString($fields['GRP'] ?? null),
                'tz' => $this->nullableString($fields['TZ'] ?? null),
                'raw_line' => $line,
                'client_ip' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($records === []) {
            return;
        }

        DB::table($this->table('userinfo'))->upsert(
            $records,
            ['serial_number', 'pin'],
            ['name', 'privilege', 'password', 'card', 'grp', 'tz', 'raw_line', 'client_ip', 'updated_at']
        );
    }

    private function storeFingerprintTemplates(string $serialNumber, string $content, Request $request): void
    {
        $fields = $this->parseEqualsTabbedPairs($content);
        $pin = $this->nullableString($fields['PIN'] ?? null);
        $fid = $this->nullableString($fields['FID'] ?? null) ?? '';

        if ($pin === null) {
            return;
        }

        DB::table($this->table('fingertmp'))->updateOrInsert(
            [
                'serial_number' => $serialNumber,
                'pin' => $pin,
                'fid' => $fid,
            ],
            [
                'size' => $this->nullableInt($fields['SIZE'] ?? null),
                'valid' => $this->nullableString($fields['VALID'] ?? null),
                'template' => $this->nullableString($fields['TMP'] ?? null),
                'raw_line' => $content,
                'client_ip' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function logHttpRequest(Request $request, ?string $serialNumber, ?string $table, string $content): void
    {
        try {
            DB::table($this->table('http_logs'))->insert([
                'endpoint' => trim($request->path(), '/'),
                'method' => $request->method(),
                'serial_number' => $this->nullableString($serialNumber),
                'table_name' => $this->nullableString($table),
                'query_string' => $request->getQueryString(),
                'content_type' => $request->header('Content-Type'),
                'body_preview' => $this->bodyPreview($content, (string) $table),
                'body_size' => strlen($content),
                'client_ip' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            // HTTP logging should never block the device ingest flow.
        }
    }

    private function bodyPreview(string $content, string $table): string
    {
        $upperTable = Str::upper(trim($table));

        if ($upperTable === 'ATTPHOTO') {
            $jpegOffset = strpos($content, "\xFF\xD8");
            $header = $jpegOffset === false ? $content : substr($content, 0, $jpegOffset);

            return trim($header)."\n[BINARY_JPEG_OMITTED]";
        }

        $sanitized = preg_replace('/[^\P{C}\t\n\r]/u', '', $content) ?? $content;

        return mb_substr($sanitized, 0, 4000);
    }

    private function getOrCreateDeviceState(string $serialNumber): object
    {
        $table = $this->table('device_state');
        $state = DB::table($table)->where('serial_number', $serialNumber)->first();

        if ($state !== null) {
            return $state;
        }

        DB::table($table)->insert([
            'serial_number' => $serialNumber,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table($table)->where('serial_number', $serialNumber)->first();
    }

    private function touchDeviceStateFromRequest(Request $request): void
    {
        $serialNumber = trim((string) $request->query('SN', ''));

        if ($serialNumber === '') {
            return;
        }

        $state = $this->getOrCreateDeviceState($serialNumber);
        $updates = [
            'options' => trim((string) $request->query('options', $state->options ?? '')),
            'pushver' => trim((string) $request->query('pushver', $state->pushver ?? '')),
            'language' => trim((string) $request->query('language', $state->language ?? '')),
        ];

        $table = Str::upper(trim((string) $request->query('table', '')));
        $stamp = trim((string) $request->query('Stamp', ''));

        if ($stamp !== '') {
            if ($table === 'ATTLOG') {
                $updates['attlogstamp'] = $stamp;
            } elseif ($table === 'OPERLOG') {
                $updates['oplogstamp'] = $stamp;
            } elseif ($table === 'ATTPHOTO') {
                $updates['attphotostamp'] = $stamp;
            }
        }

        $this->updateDeviceState($serialNumber, $updates);
    }

    private function updateDeviceState(string $serialNumber, array $attributes): void
    {
        $payload = ['updated_at' => now()];

        foreach ($attributes as $key => $value) {
            if ($value !== null) {
                $payload[$key] = $value;
            }
        }

        DB::table($this->table('device_state'))
            ->where('serial_number', $serialNumber)
            ->update($payload);
    }

    private function splitLines(string $content): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($content));

        return array_values(array_filter($lines ?: [], static fn (string $line): bool => trim($line) !== ''));
    }

    private function parseEqualsTabbedPairs(string $content): array
    {
        $pairs = preg_split("/\t|\r\n|\n|\r/", trim($content)) ?: [];
        $data = [];

        foreach ($pairs as $pair) {
            if (! str_contains($pair, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $pair, 2);
            $data[Str::upper(trim($key))] = trim($value);
        }

        return $data;
    }

    private function parseTimestamp(?string $value): ?Carbon
    {
        $value = $this->nullableString($value);
        if ($value === null) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function parsePhotoTimestamp(?string $value): ?Carbon
    {
        $value = $this->nullableString($value);
        if ($value === null) {
            return null;
        }

        $patterns = [
            '/^(?<ts>\d{14})-/',
            '/[_-](?<ts>\d{8}[_-]?\d{6})(?:\.[A-Za-z0-9]+)?$/',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $value, $matches)) {
                continue;
            }

            $normalized = str_replace(['_', '-'], '', (string) $matches['ts']);

            try {
                return Carbon::createFromFormat('YmdHis', $normalized);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function extractPhotoEmpno(?string $value): ?string
    {
        $value = $this->nullableString($value);
        if ($value === null) {
            return null;
        }

        if (preg_match('/^\d{14}-(?<empno>[^.]+)(?:\.[A-Za-z0-9]+)?$/', $value, $matches)) {
            return trim((string) $matches['empno']);
        }

        if (preg_match('/^(?<empno>[^._-][^.]*?)[_-]\d{8}[_-]?\d{6}(?:\.[A-Za-z0-9]+)?$/', $value, $matches)) {
            return trim((string) $matches['empno']);
        }

        return null;
    }

    private function buildAttendancePhotoFilename(?string $empno, ?Carbon $capturedAt, ?string $fallback = null): string
    {
        if ($empno !== null && $capturedAt !== null) {
            $safeEmpno = preg_replace('/[^A-Za-z0-9._-]/', '_', $empno) ?: 'unknown';
            return $safeEmpno.'_'.$capturedAt->format('Ymd_His').'.jpg';
        }

        return $this->safeFilename($fallback);
    }

    private function safeFilename(?string $value): string
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            return now()->format('YmdHis').'-'.Str::random(8).'.jpg';
        }

        return preg_replace('/[^A-Za-z0-9._-]/', '_', $value) ?: now()->format('YmdHis').'-photo.jpg';
    }

    private function nullableString(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;
        return $value === '' ? null : $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        $value = $this->nullableString(is_string($value) ? $value : (is_numeric($value) ? (string) $value : null));
        return $value !== null && is_numeric($value) ? (int) $value : null;
    }

    private function table(string $key): string
    {
        return match ($key) {
            'attendance' => (string) config('zkteco-adms.attendance_table', 'inout_raw'),
            'operation_logs' => (string) config('zkteco-adms.operation_logs_table', 'operation_logs'),
            'attendance_photos' => (string) config('zkteco-adms.attendance_photos_table', 'attendance_photos'),
            'device_polls' => (string) config('zkteco-adms.device_polls_table', 'adms_device_polls'),
            'device_commands' => (string) config('zkteco-adms.device_commands_table', 'device_commands'),
            'http_logs' => (string) config('zkteco-adms.http_logs_table', 'adms_http_logs'),
            'userinfo' => (string) config('zkteco-adms.userinfo_table', 'adms_userinfo'),
            'fingertmp' => (string) config('zkteco-adms.fingertmp_table', 'adms_fingertmp'),
            'device_state' => (string) config('zkteco-adms.device_state_table', 'adms_device_state'),
            default => $key,
        };
    }

    private function fillIfColumnExists(string $table, array &$record, string $column, mixed $value): void
    {
        if ($this->hasColumn($table, $column)) {
            $record[$column] = $value;
        }
    }

    private function attendanceRecordIdentity(string $table, array $record): array
    {
        if ($this->hasColumn($table, 'serialno') && $this->hasColumn($table, 'raw_line')) {
            $identity = [
                'serialno' => (string) ($record['serialno'] ?? ''),
                'raw_line' => (string) ($record['raw_line'] ?? ''),
            ];

            if ($this->hasColumn($table, 'stamp')) {
                $identity['stamp'] = $record['stamp'] ?? null;
            }

            return $identity;
        }

        $identity = [
            'empno' => (string) ($record['empno'] ?? ''),
            'txndate' => (string) ($record['txndate'] ?? ''),
            'txntime' => (string) ($record['txntime'] ?? ''),
        ];

        if ($this->hasColumn($table, 'punch')) {
            $identity['punch'] = (string) ($record['punch'] ?? '');
        }

        return $identity;
    }

    private function resequencedAttendanceDates(string $table, string $serialNumber, array $dates): array
    {
        $dates = array_values(array_filter(array_unique(array_map(
            static fn ($date): string => trim((string) $date),
            $dates
        ))));

        if ($dates === [] || ! $this->hasColumn($table, 'seqno')) {
            return [];
        }

        $snapshots = [];

        foreach ($dates as $date) {
            $query = DB::table($table)
                ->where('txndate', $date);

            if ($this->hasColumn($table, 'serialno')) {
                $query->where('serialno', $serialNumber);
            }

            $rows = $query
                ->orderBy('txntime')
                ->orderBy('empno');

            if ($this->hasColumn($table, 'punch')) {
                $rows->orderBy('punch');
            }

            if ($this->hasColumn($table, 'raw_line')) {
                $rows->orderBy('raw_line');
            }

            $rows = $rows->get();

            foreach ($rows as $index => $row) {
                $rowQuery = DB::table($table)
                    ->where('empno', (string) $row->empno)
                    ->where('txndate', (string) $row->txndate)
                    ->where('txntime', (string) $row->txntime);

                if ($this->hasColumn($table, 'serialno')) {
                    $rowQuery->where('serialno', (string) ($row->serialno ?? ''));
                }

                if ($this->hasColumn($table, 'raw_line')) {
                    $rowQuery->where('raw_line', (string) ($row->raw_line ?? ''));
                }

                if ($this->hasColumn($table, 'stamp')) {
                    $rowQuery->where('stamp', $row->stamp ?? null);
                }

                $rowQuery->update(['seqno' => $index]);
            }

            $snapshots[$date] = $rows->isEmpty() ? 0 : ($rows->count() - 1);
        }

        return $snapshots;
    }

    private function hasColumn(string $table, string $column): bool
    {
        $cacheKey = $table.':'.$column;

        if (! array_key_exists($cacheKey, $this->columnCache)) {
            $this->columnCache[$cacheKey] = Schema::hasColumn($table, $column);
        }

        return $this->columnCache[$cacheKey];
    }
}
