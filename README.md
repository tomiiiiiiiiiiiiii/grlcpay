# grlcpay

A small, database-free Garlicoin (GRLC) web payment handler written in PHP.

It creates one-time payment links tied to a fresh GRLC address. The payment page checks the configured GRLC explorer for the address balance and releases the configured access code after payment is detected.

## Requirements

- PHP 5.6+ (also compatible with PHP 7.x and PHP 8.x)
- OpenSSL extension
- HTTPS strongly recommended
- A writable, non-public `data/` directory
- Optional: PEAR Mail if email notifications are enabled

## Installation

1. Copy `index.php`, `config.example.php` and the `data/` directory to your web server.
2. Copy `config.example.php` to `config.php`.
3. Generate a private encryption key, for example with `openssl rand -hex 32`, and paste it into `config.php`:
   ```php
   <?php
   $encryption_key = 'PASTE_YOUR_RANDOM_KEY_HERE';
   ```
4. Keep `config.php` private. It is listed in `.gitignore` and must never be committed or shared.
5. Keep `data/` blocked from direct web access. The included `.htaccess` does this on Apache.
6. Make `data/` writable by the PHP/web-server user. Do **not** use world-writable `0777` permissions unless your hosting environment leaves no safer option.
7. Configure `$domain_name` in `index.php` if needed.

Payment creation and existing payment-link decryption are refused until `config.php` contains an encryption key of at least 32 characters.

## PHP 5.6 compatibility

The current code intentionally avoids PHP 7-only syntax. On PHP 5.6, secure random bytes fall back to OpenSSL and new payment files use the same authenticated v3 format as newer PHP versions.

## Security notes

- New payment metadata uses AES-256-CBC with HMAC-SHA256 (encrypt-then-MAC), chosen so the same payment format works across PHP 5.6, 7.x and 8.x.
- Interim AES-256-GCM v2 payment files remain readable on PHP 7.1+.
- Original legacy AES-256-CBC payment files remain readable for migration compatibility.
- Payment IDs are generated from cryptographically secure random bytes.
- Payment metadata files are created with private permissions where supported.
- Concurrent payment checks are locked so the same one-time secret is not released twice.
- A fresh address is accepted only when the configured explorer reports the expected empty balance.
- Input and HTML output are validated/escaped.
- The `data/` directory must not be publicly readable.
- Keep `config.php` private and never commit it. Losing or changing its encryption key makes existing encrypted payment files unreadable.
- Use HTTPS.
- Explorer availability is external to this project; payment verification depends on the configured explorer returning valid balance data.


## API

For new integrations, use a POST request so the access code is carried in the request body rather than the URL:

```
POST index.php
pid=api_create
amount=1.25
addr=YOUR_FRESH_GRLC_ADDRESS
email=optional@example.com
code=CONTENT_OR_HTTPS_URL_RELEASED_AFTER_PAYMENT
```

The legacy `pid=api_get` GET endpoint remains available for compatibility, but it is not recommended for secrets because query strings can be stored in browser history, proxies and web-server access logs.

## Continuous integration

GitHub Actions checks PHP 5.6, 7.4 and 8.4, runs the full HTTP payment flow, and verifies the live production explorer contract.

## Email

Email notifications remain optional. The legacy Gmail "less secure apps" workflow is no longer recommended. If mail is enabled, use an SMTP provider and authentication method supported by your current mail service.

## Demo

https://grlc.eu/pay

## License

LGPL, as included in `LICENSE`.
