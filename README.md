# shadow046/zkteco-adms

Reusable ZKTeco ADMS server package for Laravel applications.

## Features
- Provides `/iclock` ADMS endpoints as a package.
- Stores ATTLOG, OPERLOG, USERINFO, FINGERTMP, photos, command queue, device polls, and device state.
- Creates a default `inout_raw` table when the host project does not already have one.
- Includes built-in `DtrPairingService` logic and optional automatic pairing after ATTLOG ingest.
- Dispatches `Shadow046\\ZktecoAdms\\Events\\AttendanceLogsStored` for host-level workflows.

## Install
```bash
composer require shadow046/zkteco-adms
php artisan zkteco-adms:install
php artisan migrate:adms
```

`migrate:adms` is safe for existing legacy databases. It skips already-existing ADMS tables and syncs missing ADMS columns into existing `inout_raw` and `dtr` tables when needed.

The install command also publishes the optional Python helper scripts and bundled `zk` library into the host project at `scripts/zkteco-adms` so the common direct-device setup does not need a custom scripts path.

It also publishes optional route stubs into `routes/zkteco-adms`. When those files exist, the package will automatically load them instead of its internal default route files, so the host app can customize package routes without editing `vendor/`.

For developers who prefer to inspect or fork the controller layer in the host app, the install command also publishes optional controller stubs into `app/Http/Controllers/ZktecoAdms`. These are not auto-wired by the package routes; they are there as editable host-side references.

Make sure your Laravel `storage` path is writable, especially if you will receive `ATTPHOTO` uploads from the device.

## Config
Published config file: `config/zkteco-adms.php`

Useful options:
- `ZKTECO_ADMS_ROUTE_PREFIX=iclock`
- `ZKTECO_ADMS_COMMAND_ROUTE_PREFIX=zkteco-adms/commands`
- `ZKTECO_ADMS_UI_ROUTE_PREFIX=shadow046/adms`
- `ZKTECO_ADMS_ATTENDANCE_TABLE=inout_raw`
- `ZKTECO_ADMS_DTR_TABLE=dtr`
- `ZKTECO_ADMS_DTR_PAIRING_ENABLED=true`
- `ZKTECO_ADMS_PHOTO_DISK=local`
- `ZKTECO_ADMS_PHOTO_DIRECTORY=adms_photos`
- `ZKTECO_ADMS_PYTHON_ENABLED=false`
- `ZKTECO_ADMS_PYTHON_BIN=python3`
- `ZKTECO_ADMS_PYZK_ROOT=scripts/zkteco-adms`
- `ZKTECO_ADMS_PYTHON_SCRIPTS_PATH=scripts/zkteco-adms`

## Included Components
- package service provider
- install command
- `/iclock` ADMS routes
- publishable route stubs with host override support
- publishable host controller stubs for optional customization
- package-owned test frontend for dashboard, attendance, and daily logs
- ADMS core ingest service
- optional Python bridge service and packaged helper scripts
- attendance photo storage and linking
- USERINFO and FINGERTMP mirrors
- command queue and device state tracking
- reusable command queue endpoints for ATTLOG, FINGERTMP, USERINFO, and FINGERTMP update
- built-in `DtrPairingService`
- automatic DTR pairing listener
- publishable config and migrations
- package test scaffold

## Notes
- If the host app already has `inout_raw` or `dtr`, the package migrations will skip creating those tables.
- The pairing logic handles punch `1/2/3/4`, chronology checks, next-day carry-over, and manual `*` protection.
- Routes are package-owned; the host app only needs to point the ZKTeco device to the configured prefix.
- This package directory is ready to be copied into its own standalone Git repository.

## Command Queue Endpoints
- `POST /zkteco-adms/commands/attlog-query`
- `POST /zkteco-adms/commands/fingertmp-query`
- `POST /zkteco-adms/commands/user-update`
- `POST /zkteco-adms/commands/fingertmp-update`

All endpoints return JSON and insert rows into the configured `device_commands` table.

## Built-in Test UI
- `GET /shadow046/adms/dashboard`
- `GET /shadow046/adms/attendance`
- `GET /shadow046/adms/daily-logs`

This frontend is package-owned and meant as a ready-to-use testing surface for host apps.

## Optional Python Bridge
The package now includes the direct-device helper scripts under `scripts/` and a reusable PHP wrapper service:

- `Shadow046\ZktecoAdms\Services\ZkPythonBridgeService`

Included scripts:
- `zk_backup.py`
- `zk_backup_user.py`
- `zk_restore.py`
- `zk_delete_user.py`
- `zk_enroll_user.py`
- `zk_query_logs.py`

Enable it with:

```env
ZKTECO_ADMS_PYTHON_ENABLED=true
ZKTECO_ADMS_PYTHON_BIN=python3
ZKTECO_ADMS_PYZK_ROOT=scripts/zkteco-adms
```

By default, `php artisan zkteco-adms:install` publishes the packaged scripts and bundled `zk` library into `scripts/zkteco-adms` in the host project. Use `ZKTECO_ADMS_PYTHON_SCRIPTS_PATH` only if you want to override that default location with a custom script directory.
