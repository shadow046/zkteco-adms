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
php artisan migrate
```

## Config
Published config file: `config/zkteco-adms.php`

Useful options:
- `ZKTECO_ADMS_ROUTE_PREFIX=iclock`
- `ZKTECO_ADMS_ATTENDANCE_TABLE=inout_raw`
- `ZKTECO_ADMS_DTR_TABLE=dtr`
- `ZKTECO_ADMS_DTR_PAIRING_ENABLED=true`
- `ZKTECO_ADMS_PHOTO_DISK=local`
- `ZKTECO_ADMS_PHOTO_DIRECTORY=adms_photos`

## Included Components
- package service provider
- install command
- `/iclock` ADMS routes
- ADMS core ingest service
- attendance photo storage and linking
- USERINFO and FINGERTMP mirrors
- command queue and device state tracking
- built-in `DtrPairingService`
- automatic DTR pairing listener
- publishable config and migrations
- package test scaffold

## Notes
- If the host app already has `inout_raw` or `dtr`, the package migrations will skip creating those tables.
- The pairing logic handles punch `1/2/3/4`, chronology checks, next-day carry-over, and manual `*` protection.
- Routes are package-owned; the host app only needs to point the ZKTeco device to the configured prefix.
- This package directory is ready to be copied into its own standalone Git repository.
