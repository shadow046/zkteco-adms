# Nginx Guide

Some ZKTeco devices only push to plain `HTTP`, not `HTTPS`.

If the host Laravel project mainly runs behind `HTTPS`, the usual workaround is to expose a separate `HTTP` ADMS endpoint just for the device.

This package ships with publishable nginx templates for that purpose.

## Published Files

After running:

```bash
php artisan zkteco-adms:install
```

you will have:

- `nginx/zkteco-adms/http-default-port.conf.example`
- `nginx/zkteco-adms/http-custom-port.conf.example`

## When to use each template

### `http-default-port.conf.example`

Use this when:

- you are okay exposing the ADMS endpoint on plain `HTTP`
- and port `80` is available for the ADMS hostname or vhost

Typical device URL:

- `http://your-adms-host/iclock`

### `http-custom-port.conf.example`

Use this when:

- your main website already uses another nginx setup
- you do not want to reuse port `80`
- or you want a dedicated ADMS-only listener such as `8081`

Typical device URL:

- `http://your-adms-host:8081/iclock`

## Important placeholders

Edit the template before enabling it:

- `__SERVER_NAME__`
- `__PROJECT_PUBLIC__`
- `__PHP_FPM_SOCK__`
- `__ADMS_PORT__` in the custom-port version

Examples:

- `__SERVER_NAME__` -> `bio.example.com`
- `__PROJECT_PUBLIC__` -> `/var/www/project/public`
- `__PHP_FPM_SOCK__` -> `/run/php/php8.3-fpm.sock`
- `__ADMS_PORT__` -> `8081`

The shipped examples use `default_server` on purpose because some devices are more reliable when the ADMS listener is the nginx default listener for that HTTP port.

## Recommended pattern

If your public app is mainly HTTPS:

1. keep your normal website on HTTPS
2. expose a separate plain HTTP ADMS endpoint
3. point the device only to that HTTP ADMS endpoint

That keeps the device compatible without forcing the whole site to downgrade.

## After editing the template

Copy the finished config into your nginx sites folder, then test and reload:

```bash
nginx -t
systemctl reload nginx
```

## Notes

- The device route prefix is usually `/iclock`
- The templates assume a standard Laravel `public/index.php` front controller
- If your server uses TCP instead of a unix socket for PHP-FPM, replace `fastcgi_pass` accordingly
