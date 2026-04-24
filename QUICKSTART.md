# Quickstart

This is the shortest working path for most Laravel projects.

Compatibility:

- Laravel `9+`
- PHP `8.1+`

## 1. Install the package

```bash
composer require shadow046/zkteco-adms
php artisan zkteco-adms:install
php artisan migrate:adms
```

If you hit an unexpected `500` right after install or update, try:

```bash
php artisan cache:clear
```

## 2. Check the published files

The install command publishes these useful host-side files:

- `config/zkteco-adms.php`
- `routes/zkteco-adms/*.php`
- `app/Http/Controllers/ZktecoAdms/*.php`
- `app/Services/ZktecoAdms/*.php`
- `scripts/zkteco-adms/*`
- `docs/zkteco-adms/*.md`
- `nginx/zkteco-adms/*.example`

## 3. Point the device to the ADMS endpoint

Default route prefix:

- `/iclock`

Example:

- `https://your-domain.example/iclock`

If your device only supports plain `HTTP`, use the published nginx examples in:

- `docs/zkteco-adms/NGINX.md`
- `nginx/zkteco-adms/*.example`

You can expose the ADMS endpoint on:

- port `80`
- or a custom port such as `8081`

## 4. Open the built-in test UI

Default UI pages:

- `/shadow046/adms/dashboard`
- `/shadow046/adms/attendance`
- `/shadow046/adms/daily-logs`
- `/shadow046/adms/sequence-audit`

The dashboard includes both:

- ADMS ATTLOG queueing
- direct Python device log query

## 5. Optional Python tools

Enable the direct-device Python tools only if you need them:

```env
ZKTECO_ADMS_PYTHON_ENABLED=true
ZKTECO_ADMS_PYTHON_BIN=python3
ZKTECO_ADMS_PYZK_ROOT=scripts/zkteco-adms
```

Quick check:

```bash
python3 scripts/zkteco-adms/zk_query_logs.py --help
```

## 6. Important notes

- Make sure `storage/` is writable for `ATTPHOTO` uploads.
- The package can work with existing legacy `inout_raw` and `dtr` tables.
- Route override files are auto-detected from `routes/zkteco-adms/*.php`.
- Published controllers and services are optional host copies and are not auto-wired by default.

## 7. Where to read next

- `README.md` for the full package overview
- `OVERRIDES.md` for host customization strategy
- `CHANGELOG.md` for release history
- `PUBLISHING.md` for package release steps
