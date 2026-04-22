# Publishing Guide

## 1. Create a standalone repository
Copy the contents of this directory to the root of a new Git repository named `shadow046/zkteco-adms`.

## 2. Verify package metadata
Check:
- `composer.json`
- `README.md`
- `LICENSE`
- `src/`
- `database/migrations/`
- `config/`
- `routes/`
- `tests/`

## 3. Run local checks
```bash
composer install
composer test
```

## 4. Initialize git
```bash
git init
git add .
git commit -m "Initial release of shadow046/zkteco-adms"
git branch -M main
git tag v0.1.0
```

## 5. Push to GitHub
```bash
git remote add origin git@github.com:shadow046/zkteco-adms.git
git push -u origin main
git push origin v0.1.0
```

## 6. Submit to Packagist
- Open https://packagist.org/packages/submit
- Submit `https://github.com/shadow046/zkteco-adms`
- Enable Packagist auto-update hook

## 7. Test from a fresh Laravel app
```bash
composer require shadow046/zkteco-adms
php artisan zkteco-adms:install
php artisan migrate:adms
```

For legacy databases, `migrate:adms` now skips tables that already exist and adds the missing ADMS support columns required on existing `inout_raw` and `dtr` tables.
