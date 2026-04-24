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

## Example split setup

This is a common production-friendly pattern:

- users open the site on `HTTPS`
- the device pushes only to a dedicated plain `HTTP` listener

Example:

- user-facing app: `https://bio.example.com`
- ADMS device endpoint: `http://bio.example.com/iclock`

### HTTPS site for users

This stays on your normal SSL-enabled nginx server block.

Example idea:

```nginx
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;

    server_name bio.example.com;
    root /var/www/project/public;

    ssl_certificate /path/to/fullchain.pem;
    ssl_certificate_key /path/to/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

### Dedicated HTTP listener for the device

This is the important part for older devices that do not reliably talk to `HTTPS`.

Example idea:

```nginx
server {
    listen 80 default_server;
    listen [::]:80 default_server;

    server_name _;
    root /var/www/project/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

In that setup, the device can still push to:

- `http://bio.example.com/iclock`

or, if DNS is not important to the device:

- `http://SERVER_IP/iclock`

### Custom-port variant

If you do not want to use port `80`, you can instead expose a dedicated HTTP listener such as:

- `http://bio.example.com:8081/iclock`

That is what `http-custom-port.conf.example` is for.

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
