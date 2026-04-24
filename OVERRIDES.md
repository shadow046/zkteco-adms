# Override Guide

This package ships with working defaults out of the box, but it also supports an optional host-side override layer for teams that want to inspect, extend, or fork parts of the package without editing `vendor/`.

## Default Behavior

By default, the package uses its own internal:

- routes
- controllers
- services
- views
- migrations
- scripts

That means a normal install can work with only:

```bash
composer require shadow046/zkteco-adms
php artisan zkteco-adms:install
php artisan migrate:adms
```

## What `zkteco-adms:install` Publishes

The install command publishes optional host-side files so the consuming app can customize behavior in a clean way.

### Routes

Published to:

- `routes/zkteco-adms/api.php`
- `routes/zkteco-adms/commands.php`
- `routes/zkteco-adms/web.php`

If these files exist, the package will automatically load them instead of the internal package route files.

That means route overrides are the only override layer that is auto-detected by the package.

### Controllers

Published to:

- `app/Http/Controllers/ZktecoAdms/AdmsController.php`
- `app/Http/Controllers/ZktecoAdms/AdmsCommandController.php`
- `app/Http/Controllers/ZktecoAdms/AdmsEndpointController.php`

These are editable host-side controller stubs.

Important:

- they are not auto-wired by the package
- the host app must point its own route files to these controllers if it wants to use them

### Services

Published to:

- `app/Services/ZktecoAdms/AdmsCoreService.php`
- `app/Services/ZktecoAdms/AdmsCommandService.php`
- `app/Services/ZktecoAdms/DtrPairingService.php`
- `app/Services/ZktecoAdms/ZkPythonBridgeService.php`

These are editable host-side service stubs.

They are intended for teams that want to fork package service behavior at the application level.

### Python Scripts

Published to:

- `scripts/zkteco-adms/`

This includes:

- Python helper scripts
- bundled `zk` Python library

## Recommended Override Strategy

Use the smallest override layer needed.

### Option 1: Route-only override

Best when you only want to:

- add middleware
- change prefixes
- point some routes to host controllers

Recommended file to edit:

- `routes/zkteco-adms/*.php`

### Option 2: Route + controller override

Best when you want to:

- customize request validation
- change redirects or JSON responses
- adjust query/filter behavior in the package UI

Recommended files to edit:

- `routes/zkteco-adms/*.php`
- `app/Http/Controllers/ZktecoAdms/*.php`

### Option 3: Full host-side fork

Best when you want to:

- replace service logic
- change DTR pairing behavior
- customize direct device Python bridge integration

Recommended files to edit:

- `routes/zkteco-adms/*.php`
- `app/Http/Controllers/ZktecoAdms/*.php`
- `app/Services/ZktecoAdms/*.php`

## Important Notes

### 1. Published controllers and services do not auto-replace package classes

Only the route override files are auto-detected.

If you want to use host controllers, you must update the published route files to point to:

- `App\Http\Controllers\ZktecoAdms\...`

instead of:

- `Shadow046\ZktecoAdms\Http\Controllers\...`

The published route stubs already include commented examples showing the package controller imports and the matching host controller imports, so the swap is quick and explicit.

### 2. Published host controllers already point to host service stubs

The published controller stubs are wired to:

- `App\Services\ZktecoAdms\...`

This makes it easier to fully fork behavior inside the host app.

### 3. Package updates will not update your published host copies

Once routes/controllers/services are published into the host app and you edit them, they are your application copies.

That means:

- package bug fixes continue in `vendor`
- your host copies stay as-is until you manually merge changes

## Safe Upgrade Workflow

When upgrading the package:

1. update the package normally
2. review package release notes / commits
3. compare your host overrides against the new package files
4. merge only the changes you want

## Suggested Host Workflow

If you are just starting:

1. keep package defaults first
2. publish route/controller/service stubs
3. only switch a route to host classes when you really need customization

That keeps upgrades much simpler.
