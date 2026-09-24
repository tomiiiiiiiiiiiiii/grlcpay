# grlcpay

A small, database-free Garlicoin (GRLC) web payment handler written in PHP.

It creates one-time payment links tied to a fresh GRLC address. The payment page checks supported GRLC explorers for the address balance and releases the configured access code after payment is detected.

## Requirements

- PHP 7.4+ or PHP 8.x
- OpenSSL extension
- HTTPS strongly recommended
- A writable, non-public `data/` directory
- Optional: PEAR Mail if email notifications are enabled

## Installation

1. Copy `index.php` and the `data/` directory to your web server.
2. Keep `data/` blocked from direct web access. The included `.htaccess` does this on Apache.
3. Make `data/` writable by the PHP/web-server user. Do **not** use world-writable `0777` permissions unless your hosting environment leaves no safer option.
4. Configure `$domain_name`.
5. Set a long, random encryption key.

The recommended way to provide the encryption key is through the environment:

```
GRLCPAY_ENCRYPTION_KEY="replace-with-a-long-random-secret"
```

The built-in placeholder key is only a fallback for compatibility and must be changed before production use.

## Security notes

- Payment metadata is encrypted at rest.
- Payment IDs are generated from cryptographically secure random bytes.
- Input and HTML output are validated/escaped.
- The `data/` directory must not be publicly readable.
- Use HTTPS.
- Explorer availability is external to this project; payment verification depends on at least one configured explorer responding with valid balance data.

## Email

Email notifications remain optional. The legacy Gmail "less secure apps" workflow is no longer recommended. If mail is enabled, use an SMTP provider and authentication method supported by your current mail service.

## Demo

https://grlc.eu/pay

## License

LGPL, as included in `LICENSE`.
