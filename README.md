# Wallet Login for PHP

Sign-In with Ethereum (SIWE) for PHP 8.0+.
**No ext-gmp required** — uses bcmath (enabled by default on most PHP installs).

## Requirements

- PHP >= 8.0
- ext-bcmath (on by default — check with `php -m | grep bcmath`)
- Composer

## Install

```bash
composer install
```

That's it. Only one dependency: `kornrunner/keccak` for Keccak-256 hashing.
All secp256k1 elliptic curve math is handled in pure PHP using bcmath.

## File structure

```
wallet-login/
├── index.php              # Login page (frontend)
├── dashboard.php          # Example protected page
├── composer.json
├── src/
│   └── EthVerify.php      # Self-contained signature verifier (no gmp)
└── auth/
    ├── nonce.php          # GET  /auth/nonce.php?address=0x...
    ├── verify.php         # POST /auth/verify.php
    ├── logout.php         # POST /auth/logout.php
    └── auth_check.php     # Include at top of any protected page
```

## Protect any page

```php
require_once __DIR__ . '/auth/auth_check.php';
// $walletAddress is now set and verified
echo "Hello " . $walletAddress;
```

## How the signature verify works (no gmp)

`src/EthVerify.php` implements the full secp256k1 `ecrecover` algorithm using
only PHP's bcmath extension:

1. Prefix the message: `"\x19Ethereum Signed Message:\n" + len + message`
2. Keccak-256 hash it (via `kornrunner/keccak`)
3. Parse r, s, v from the 65-byte signature
4. Run secp256k1 point arithmetic (modular inverse, point add/double, scalar mul)
5. Derive the Ethereum address from the recovered public key

## If you hit "bcmath not found"

bcmath is bundled with PHP. Enable it in `php.ini`:
```
extension=bcmath
```
Or install it:
```bash
# Ubuntu/Debian
sudo apt install php8.0-bcmath

# CentOS/RHEL
sudo yum install php-bcmath
```

## Production checklist

- [ ] Replace session nonces with a database table (see schema below)
- [ ] Use HTTPS — required, signatures can be intercepted over plain HTTP
- [ ] Secure `php.ini` session settings:
      `session.cookie_secure = 1`
      `session.cookie_httponly = 1`
      `session.cookie_samesite = Strict`
- [ ] CSRF-protect the logout endpoint
- [ ] Set nonce expiry cleanup cron job

## Database schema (recommended for production)

```sql
CREATE TABLE wallet_nonces (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  address    VARCHAR(42)  NOT NULL,
  nonce      VARCHAR(64)  NOT NULL,
  expires_at DATETIME     NOT NULL,
  created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_address (address)
);

CREATE TABLE users (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  wallet_address VARCHAR(42)  NOT NULL UNIQUE,
  created_at     DATETIME     DEFAULT CURRENT_TIMESTAMP,
  last_login     DATETIME,
  INDEX idx_wallet (wallet_address)
);
```
