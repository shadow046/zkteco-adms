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

The install command also publishes optional service stubs into `app/Services/ZktecoAdms` so the host app can inspect or fork the package service layer without editing `vendor/`.

The package Markdown docs are also published into `docs/zkteco-adms` so the host app can read the package notes locally without digging through `vendor/`.

Start with [QUICKSTART.md](QUICKSTART.md) if you want the shortest working setup.

The install command also publishes nginx examples into `nginx/zkteco-adms` for setups where the device must push to plain `HTTP` on port `80` or a custom dedicated port.

See [OVERRIDES.md](OVERRIDES.md) for the recommended override strategy and the difference between package defaults and host-side published copies.

Make sure your Laravel `storage` path is writable, especially if you will receive `ATTPHOTO` uploads from the device.

If you hit an unexpected `500` immediately after install or package update, try:

```bash
php artisan cache:clear
```

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
- publishable host service stubs for optional customization
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

## Troubleshooting

### Unexpected 500 right after install or package update

Try:

```bash
php artisan cache:clear
```

If needed, follow with:

```bash
php artisan optimize:clear
```

### `ATTPHOTO` uploads fail

Make sure these paths are writable by the web server user:

- `storage/`
- `bootstrap/cache/`

### `bootstrap/cache directory must be present and writable`

Create the directory if it is missing, then fix ownership/permissions for:

- `bootstrap/cache/`
- `storage/`

### Python bridge says `No module named 'zk'`

Make sure you have run:

```bash
php artisan zkteco-adms:install
```

That publishes:

- `scripts/zkteco-adms/`
- bundled `zk` library

Then verify:

```bash
python3 scripts/zkteco-adms/zk_query_logs.py --help
```

If you use a custom Python location, set:

- `ZKTECO_ADMS_PYZK_ROOT`
- `ZKTECO_ADMS_PYTHON_SCRIPTS_PATH`

### ADMS host works but your host override controllers/services do not

Remember:

- route overrides are auto-detected from `routes/zkteco-adms/*.php`
- published controllers and services are not auto-wired

If you want to use host controllers, update the published route stubs to point to:

- `App\Http\Controllers\ZktecoAdms\...`

instead of:

- `Shadow046\ZktecoAdms\Http\Controllers\...`

See [OVERRIDES.md](OVERRIDES.md) for the full override strategy.
