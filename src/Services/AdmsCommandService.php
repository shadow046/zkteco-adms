<?php

namespace Shadow046\ZktecoAdms\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdmsCommandService
{
    public function queueAttlogQuery(string $serialNumber, Carbon $startAt, Carbon $endAt): array
    {
        $wireId = 700;
        $commandText = sprintf(
            'C:%d:DATA QUERY ATTLOG StartTime=%s'."\t".'EndTime=%s',
            $wireId,
            $startAt->format('Y-m-d\TH:i:s'),
            $endAt->format('Y-m-d\TH:i:s')
        );

        return $this->insertCommand(
            $serialNumber,
            'QUERY_ATTLOG',
            $commandText,
            [
                'table' => 'ATTLOG',
                'wire_id' => $wireId,
                'stime' => $startAt->toIso8601String(),
                'etime' => $endAt->toIso8601String(),
            ]
        );
    }

    public function queueFingertmpQuery(string $serialNumber, string $pin, string $fid): array
    {
        $wireId = 800;
        $commandText = sprintf(
            'C:%d:DATA QUERY FINGERTMP PIN=%s'."\t".'FID=%s',
            $wireId,
            trim($pin),
            trim($fid)
        );

        return $this->insertCommand(
            $serialNumber,
            'QUERY_FINGERTMP',
            $commandText,
            [
                'table' => 'FINGERTMP',
                'wire_id' => $wireId,
                'pin' => trim($pin),
                'fid' => trim($fid),
            ]
        );
    }

    public function queueUserUpdate(string $serialNumber, string $pin, array $attributes): array
    {
        $wireId = 900;
        $commandText = sprintf(
            'C:%d:DATA UPDATE USERINFO PIN=%s'."\t".'Name=%s'."\t".'Pri=%s'."\t".'Passwd=%s'."\t".'Card=%s'."\t".'Grp=%s'."\t".'TZ=%s',
            $wireId,
            trim($pin),
            trim((string) ($attributes['name'] ?? $pin)),
            trim((string) ($attributes['pri'] ?? '0')),
            trim((string) ($attributes['passwd'] ?? '')),
            trim((string) ($attributes['card'] ?? '')),
            trim((string) ($attributes['grp'] ?? '')),
            trim((string) ($attributes['tz'] ?? ''))
        );

        return $this->insertCommand(
            $serialNumber,
            'UPDATE_USERINFO',
            $commandText,
            [
                'wire_id' => $wireId,
                'pin' => trim($pin),
                'attributes' => $attributes,
            ]
        );
    }

    public function queueFingertmpUpdate(
        string $serialNumber,
        string $pin,
        string $fid,
        string $template,
        string $valid = '1'
    ): array {
        $wireId = 901;
        $commandText = sprintf(
            'C:%d:DATA UPDATE FINGERTMP PIN=%s'."\t".'FID=%s'."\t".'Size=%d'."\t".'Valid=%s'."\t".'TMP=%s',
            $wireId,
            trim($pin),
            trim($fid),
            strlen($template),
            trim($valid),
            $template
        );

        return $this->insertCommand(
            $serialNumber,
            'UPDATE_FINGERTMP',
            $commandText,
            [
                'wire_id' => $wireId,
                'pin' => trim($pin),
                'fid' => trim($fid),
                'size' => strlen($template),
                'valid' => trim($valid),
            ]
        );
    }

    private function insertCommand(string $serialNumber, string $commandName, string $commandText, array $meta): array
    {
        $commandId = DB::table((string) config('zkteco-adms.device_commands_table', 'device_commands'))
            ->insertGetId([
                'serial_number' => trim($serialNumber),
                'command_name' => $commandName,
                'command_text' => $commandText,
                'status' => 'pending',
                'meta' => json_encode($meta, JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return [
            'id' => $commandId,
            'command_text' => $commandText,
        ];
    }
}
