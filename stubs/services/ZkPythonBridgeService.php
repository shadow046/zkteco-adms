<?php

namespace App\Services\ZktecoAdms;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ZkPythonBridgeService
{
    public function backupDevice(
        string $ipAddress,
        int $port = 4370,
        int $password = 0,
        bool $forceUdp = false,
        ?string $backupLabel = null
    ): array {
        $payload = $this->runScript('zk_backup.py', [
            '--ip', $ipAddress,
            '--port', (string) $port,
            '--password', (string) $password,
        ], $forceUdp, 'Invalid backup payload returned by Python bridge.');

        return $this->persistBackupPayload($payload, $ipAddress, $backupLabel);
    }

    public function backupSingleUser(
        string $userId,
        string $ipAddress,
        int $port = 4370,
        int $password = 0,
        bool $forceUdp = false,
        ?string $backupLabel = null
    ): array {
        $payload = $this->runScript('zk_backup_user.py', [
            '--user-id', trim($userId),
            '--ip', $ipAddress,
            '--port', (string) $port,
            '--password', (string) $password,
        ], $forceUdp, 'Invalid single-user backup payload returned by Python bridge.');

        return $this->persistBackupPayload($payload, $ipAddress, $backupLabel);
    }

    public function restoreDevice(
        string $filename,
        string $ipAddress,
        int $port = 4370,
        int $password = 0,
        bool $forceUdp = false
    ): array {
        $storagePath = $this->backupStoragePath($filename);

        if (! Storage::disk($this->backupDisk())->exists($storagePath)) {
            throw new RuntimeException('Selected backup file was not found.');
        }

        return $this->runScript('zk_restore.py', [
            '--backup', Storage::disk($this->backupDisk())->path($storagePath),
            '--ip', $ipAddress,
            '--port', (string) $port,
            '--password', (string) $password,
        ], $forceUdp, 'Invalid restore payload returned by Python bridge.');
    }

    public function restoreSingleUser(
        string $filename,
        string $userId,
        string $ipAddress,
        int $port = 4370,
        int $password = 0,
        bool $forceUdp = false
    ): array {
        $storagePath = $this->backupStoragePath($filename);

        if (! Storage::disk($this->backupDisk())->exists($storagePath)) {
            throw new RuntimeException('Selected backup file was not found.');
        }

        return $this->runScript('zk_restore.py', [
            '--backup', Storage::disk($this->backupDisk())->path($storagePath),
            '--user-id', trim($userId),
            '--ip', $ipAddress,
            '--port', (string) $port,
            '--password', (string) $password,
        ], $forceUdp, 'Invalid single-user restore payload returned by Python bridge.');
    }

    public function deleteUser(
        string $userId,
        string $ipAddress,
        int $port = 4370,
        int $password = 0,
        bool $forceUdp = false
    ): array {
        return $this->runScript('zk_delete_user.py', [
            '--user-id', trim($userId),
            '--ip', $ipAddress,
            '--port', (string) $port,
            '--password', (string) $password,
        ], $forceUdp, 'Invalid delete-user payload returned by Python bridge.');
    }

    public function enrollUser(
        string $userId,
        string $name,
        int $fid,
        string $ipAddress,
        int $port = 4370,
        int $password = 0,
        int $privilege = 0,
        string $userPassword = '',
        int $card = 0,
        bool $forceUdp = false
    ): array {
        return $this->runScript('zk_enroll_user.py', [
            '--user-id', trim($userId),
            '--name', trim($name),
            '--fid', (string) $fid,
            '--ip', $ipAddress,
            '--port', (string) $port,
            '--password', (string) $password,
            '--privilege', (string) $privilege,
            '--user-password', $userPassword,
            '--card', (string) $card,
        ], $forceUdp, 'Invalid enroll-user payload returned by Python bridge.');
    }

    public function queryLogs(
        string $ipAddress,
        int $port = 4370,
        int $password = 0,
        int $limit = 300,
        bool $forceUdp = false
    ): array {
        return $this->runScript('zk_query_logs.py', [
            '--ip', $ipAddress,
            '--port', (string) $port,
            '--password', (string) $password,
            '--limit', (string) max(1, $limit),
        ], $forceUdp, 'Invalid attendance log payload returned by Python bridge.');
    }

    public function recentBackups(int $limit = 10): Collection
    {
        return collect(Storage::disk($this->backupDisk())->files($this->backupDirectory()))
            ->filter(fn (string $path): bool => str_ends_with($path, '.json'))
            ->map(function (string $path): array {
                $filename = basename($path);

                return [
                    'filename' => $filename,
                    'filesize' => Storage::disk($this->backupDisk())->size($path),
                ];
            })
            ->take($limit)
            ->values();
    }

    public function backupStoragePath(string $filename): string
    {
        return trim($this->backupDirectory(), '/').'/'.$filename;
    }

    public function enabled(): bool
    {
        return (bool) config('zkteco-adms.python.enabled', false);
    }

    private function runScript(string $scriptName, array $arguments, bool $forceUdp, string $invalidPayloadMessage): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Python bridge is disabled. Set ZKTECO_ADMS_PYTHON_ENABLED=true to use direct device tools.');
        }

        $scriptPath = $this->scriptsPath().'/'.$scriptName;

        if (! is_file($scriptPath)) {
            throw new RuntimeException("Python bridge script not found: {$scriptName}");
        }

        $command = array_merge(
            [(string) config('zkteco-adms.python.bin', 'python3'), $scriptPath],
            $arguments
        );

        if ($forceUdp) {
            $command[] = '--force-udp';
        }

        $process = new Process($command, base_path());
        $process->setEnv([
            'ZK_PYZK_ROOT' => $this->pyzkRoot(),
        ]);
        $process->setTimeout((int) config('zkteco-adms.python.timeout', 180));

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            $message = trim($process->getErrorOutput()) !== ''
                ? trim($process->getErrorOutput())
                : $exception->getMessage();

            throw new RuntimeException($message);
        }

        $payload = json_decode($process->getOutput(), true);

        if (! is_array($payload)) {
            throw new RuntimeException($invalidPayloadMessage);
        }

        if (($payload['status'] ?? null) !== 'ok') {
            throw new RuntimeException((string) ($payload['message'] ?? 'Python bridge operation failed.'));
        }

        return $payload;
    }

    private function persistBackupPayload(array $payload, string $ipAddress, ?string $backupLabel): array
    {
        $serialNumber = trim((string) ($payload['serial'] ?? ''));

        if ($serialNumber === '') {
            throw new RuntimeException('Device did not return a serial number.');
        }

        $backupLabel = $this->sanitizeBackupLabel($backupLabel);
        $capturedAt = now()->format('Ymd-His');
        $filename = $this->buildBackupFilename($capturedAt, $serialNumber, $ipAddress, $backupLabel);
        $storagePath = $this->backupStoragePath($filename);

        Storage::disk($this->backupDisk())->put(
            $storagePath,
            json_encode([
                'captured_at' => now()->toIso8601String(),
                'device_ip' => $ipAddress,
                'serial' => $serialNumber,
                'label' => $backupLabel,
                'port' => $payload['port'] ?? 4370,
                'fp_version' => $payload['fp_version'] ?? null,
                'user_count' => count($payload['users'] ?? []),
                'template_count' => count($payload['templates'] ?? []),
                'users' => array_values($payload['users'] ?? []),
                'templates' => array_values($payload['templates'] ?? []),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return [
            'serial_number' => $serialNumber,
            'ip_address' => $ipAddress,
            'user_count' => count($payload['users'] ?? []),
            'template_count' => count($payload['templates'] ?? []),
            'backup_filename' => $filename,
            'backup_label' => $backupLabel,
            'backup_path' => $storagePath,
        ];
    }

    private function scriptsPath(): string
    {
        $configured = trim((string) config('zkteco-adms.python.scripts_path', ''));

        return $configured !== '' ? $configured : $this->packageRoot().'/scripts';
    }

    private function pyzkRoot(): string
    {
        $configured = trim((string) config('zkteco-adms.python.pyzk_root', ''));

        return $configured !== '' ? $configured : $this->scriptsPath();
    }

    private function backupDisk(): string
    {
        return (string) config('zkteco-adms.python.backup_disk', 'local');
    }

    private function backupDirectory(): string
    {
        return (string) config('zkteco-adms.python.backup_directory', 'zkteco_adms_backups');
    }

    private function packageRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    private function sanitizeBackupLabel(?string $label): ?string
    {
        $label = trim((string) $label);

        if ($label === '') {
            return null;
        }

        $sanitized = preg_replace('/[^A-Za-z0-9._-]+/', '_', $label) ?? '';
        $sanitized = trim($sanitized, '._-');

        return $sanitized === '' ? null : mb_substr($sanitized, 0, 80);
    }

    private function buildBackupFilename(string $capturedAt, string $serialNumber, string $ipAddress, ?string $label): string
    {
        $safeIp = str_replace(['.', ':'], '_', trim($ipAddress));
        $base = "{$capturedAt}--{$serialNumber}--{$safeIp}";

        if ($label !== null) {
            $base .= "--{$label}";
        }

        return $base.'.json';
    }
}
