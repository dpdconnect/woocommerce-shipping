# Compatibility remediation log

This file registers incompatibilities found when testing the plugin against a new
plugin, PHP, or WooCommerce update, together with the remediation needed (per the
"tested & compatible on every update" story). Add an entry whenever a compatibility
check surfaces a blocking or non-blocking finding.

## How compatibility is validated

**Automated** (CI, runs on merge requests and branches):

- **PHP syntax** — job `lint`: `composer install` + `php -l` across `dpdconnect.php` +
  `classes/`, on both ends of the declared range (PHP 8.1 and 8.5). The 8.1 run doubles as
  proof that the dependency tree resolves on the declared minimum.
- **PHP cross-version compatibility** — job `compatibility`: PHP_CodeSniffer +
  PHPCompatibilityWP against the supported PHP range in `phpcs.xml` (`testVersion` =
  `8.1-8.5`, WP-aware). Keep the floor in sync with the `Requires PHP` header.

  ```bash
  composer install
  vendor/bin/phpcs -p
  ```

  Note: `phpcompatibility/php-compatibility` is pinned at 9.3.5 — the newest stable release
  (10.x was never published) — and carries almost no 8.4/8.5 sniffs. The upper bound of
  `testVersion` therefore gives limited signal; `lint` on 8.5 and the activation smoke below
  are the real checks for newer runtimes.
- **Dependency advisories** — job `composer-audit` (non-blocking). Added after a
  high-severity `squizlabs/php_codesniffer` advisory (CVE-2026-67434) sat unnoticed in the
  lock file.

**Runtime activation smoke** (manual, on a real WordPress + WooCommerce):

Install WP + WooCommerce (current stable), symlink the plugin, activate with `WP_DEBUG`
on, and check `wp-content/debug.log` for fatals/deprecations from `classes/`. Static
analysis does **not** catch every runtime deprecation (see finding 2026-07-27 below), so
this step is the one that proves activation on a given WC version.

### Declared compatibility (plugin header — `dpdconnect.php`)

| Field | Value | Basis |
|-------|-------|-------|
| Requires PHP | 8.1 | plugin policy: PHP 8.0 is end of life. Not inherited from the SDK, which requires `>=7.4` as of 2.1.0. Linted on 8.1–8.3 locally, 8.1 + 8.5 in CI |
| Requires at least (WP) | 6.2 | conservative floor; README and docs/user/01-installation.md aligned to this |
| Tested up to (WP) | 7.0 | activated on **WordPress 7.0.2** |
| WC requires at least | 8.0 | conservative floor; README and docs/user/01-installation.md aligned to this |
| WC tested up to | 10.9 | activated on **WooCommerce 10.9.4** |

### Scope note (what is and isn't covered yet)

- ✅ Covered: static PHP cross-version compatibility of all plugin code; clean plugin
  activation on the current WordPress 7.0.2 + WooCommerce 10.9.4; **and the full
  plugin → SDK → Connect API chain end-to-end** for the auth + read path — the plugin's
  own `Connect\Country` (→ `Connection` password-auth → bundled SDK → upgraded Symfony 7.4
  API `/countries`) returns 31 countries against the local API. Re-run and confirmed on the
  bundled SDK **2.0.0** (see 2026-09-10 below).
- ⏳ Deferred (needs DPD **staging** credentials + connectivity to the DPD web service):
  the *runtime* validation of label creation / shipment submit / status against a live
  order (story AC1/AC3 end-to-end). Only the DPD-hitting endpoints remain; the plugin's
  own transport + auth + read code is now proven end-to-end. Do this once DPD test
  credentials are available.

## Findings

### 2026-09-10 — php-sdk 2.0.0 adoption (the deferred coordinated bump)

`dpdconnect/php-sdk` **2.0.0** was released (2026-09-07, on Packagist 2026-09-08), which is
the release the 2026-07-27 decision below was waiting for. Bumped `^1.1` (locked 1.1.9) →
`^2.0`, `Requires PHP` 7.4 → **8.1**, `phpcs` `testVersion` → `8.1-8.5`.

> **Correction (2026-10-07):** the `Requires PHP` bump was justified above as following
> the SDK. That reasoning no longer holds. php-sdk **2.1.0** (2026-10-07) lowered the SDK
> floor back to `>=7.4` so the DPD PrestaShop module could adopt 2.x. The plugin keeps
> 8.1 as its own policy, because PHP 8.0 is end of life, not because the SDK demands it.
> The constraint here is now `^2.1`.

- **Result:** ✅ Plugin activates cleanly on WordPress 7.0.2 + WooCommerce 10.9.4 with
  `WP_DEBUG` on, and `wp-content/debug.log` now stays **empty** — the
  `Authentication::$httpClient` dynamic-property deprecation is gone. `php -l` clean on PHP
  8.1.34 / 8.2.31 / 8.3.31; `phpcs` clean over 65 files at `testVersion 8.1-8.5`;
  `composer install` resolves on PHP 8.1 and 8.3. `Client::CLIENT_VERSION` (the
  `x-sdk-version` header the API and DPD see) is now `2.0.0`, up from `1.0.4`.
- **Result (chain, auth + read):** ✅ Re-ran the 2026-07-27 chain test on SDK 2.0.0 against
  the local API: `Connection::getPublicJwtToken()` returns a token, `Connect\Country::getList()`
  returns **31 countries** (same as on 1.1.9) and `Connect\Product::getList()` 32 products.
  No deprecations logged during those calls either.

- **Finding (behavioural, fixed here):** the jump from 1.1.9 crosses the 1.1.10–1.1.14
  releases, so the SDK's own "public API unchanged" note — which is written against 1.1.14 —
  does not cover everything we inherit. The one change that reaches the plugin is
  `Common\HttpClient`'s default cURL timeouts: **1000s/1000s → 30s total / 2s connect**, and
  `ClientBuilder` builds its client from those defaults. Every plugin call inherited that,
  including the synchronous label request (up to `Option::MAX_ASYNC_TRESHOLD` shipments).
  - **Remediation:** ✅ **Fixed** — `Connect\Connection` now owns the timeouts
    (`HTTP_TIMEOUT` 300s, `HTTP_CONNECT_TIMEOUT` 10s) and injects them via
    `ClientBuilder::setHttpClient()`; `Connection::getPublicJwtToken()` and
    `Handlers\Pickup::getPublicToken()` build their clients through the same
    `Connection::httpClient()` factory. Verified at runtime: the client reports 300/10.
  - Assessed and **not** affecting the plugin: the `Objects\BaseObject` rewrite
    (`$dynamicProperties`, `jsonSerialize()` now returns `array_filter($this->dynamicProperties)`)
    — the plugin builds its shipment payload as a plain array in `Connect\Shipment::create()`
    and uses no SDK request models; `Objects\MetaData` uses declared properties with getters;
    `Resources\Token::__construct` now creates its own `CacheWrapper`, which the plugin
    overwrites via `setCacheWrapper()`; the `CustomsLinesInterface` namespace fix touches a
    class the plugin never references.

- **Finding (security, dev-only):** `composer audit` flagged
  `squizlabs/php_codesniffer` 3.13.5 — CVE-2026-67434, OS command injection, high severity.
  - **Remediation:** ✅ **Fixed** — constraint `^3.10` → `^3.13.6`, locked at 3.13.6. Audit
    clean. A non-blocking `composer-audit` CI job was added so the next one surfaces on its
    own.

- **Environment note (not a plugin issue):** the chain test first failed with a 500 from the
  local API — `Lcobucci\JWT\Signer\InvalidKeyProvided: It was not possible to parse your key
  … bad decrypt` (API `var/log/dev.log`, logref `6aa262b805f74`): that dev environment's JWT
  keypair no longer matched its configured passphrase. The plugin side was already proven at
  that point (its request reached the API and the credentials were accepted — the stack got as
  far as Lexik's `AuthenticationSuccessHandler`). Regenerating the API keypair with the
  configured passphrase cleared it. Worth knowing for the next run: a stale
  `dpdconnect_jwt_token` WP option survives an API key change, so delete it before retesting.

- **⏳ Still deferred (unchanged):** label creation / shipment submit / status against the
  live DPD web service — needs DPD staging credentials. That is the path where the timeout
  change would have mattered most, which is why the conservative values above were chosen
  rather than the SDK defaults.

### 2026-07-27 — Full plugin → SDK → Connect API chain (auth + read)
- **Result:** ✅ End-to-end pass. Configured the plugin's WordPress options
  (`dpdconnect_user_credentials`, `dpdconnect_advanced` → local API `http://dpd-connect-api.xint.in`)
  and drove `DpdConnect\classes\Connect\Country::getList()` via `wp eval` in the wptest env.
  The plugin authenticated by password through the bundled SDK against the upgraded
  Symfony 7.4 API and returned **31 countries**. Proves the plugin's own transport/auth/read
  code works against the upgraded API — not just the SDK smoke test.
- **Finding (non-blocking, PHP 8.2+):** `Creation of dynamic property
  DpdConnect\Sdk\Resources\Authentication::$httpClient is deprecated`
  (`vendor/dpdconnect/php-sdk/src/Resources/Authentication.php:26`). The **bundled SDK 1.x**
  assigns `$this->httpClient` without declaring the property. Becomes a fatal in PHP 9.0.
  - **Already fixed in php-sdk 2.0** (property is declared `private $httpClient;`).
  - **Remediation:** `1.1.9` is already the newest 1.x and still has the issue, so a plain
    `composer update` within `^1.1` does **not** fix it. Two real paths: (a) backport the
    property declaration to a php-sdk `1.1.10` patch (plugin stays `^1.1`, keeps PHP 7.4
    support), or (b) adopt php-sdk `^2.0`, which moves the plugin's `Requires PHP` up.
  - **Decision (2026-07-27):** deferred — fold this fix into a coordinated **php-sdk 2.0
    release + plugin bump** (path b), done in one pass rather than a standalone patch.
    No plugin/SDK change made now. Re-run the chain test above after that release to
    confirm the notice is gone.
  - **Correction (2026-09-10):** this entry assumed 2.0 would require **PHP ^8.2**. The
    released 2.0.0 requires `>=8.1`, so the floor moved 7.4 → **8.1**, not 8.2 — 8.1 support
    is retained. ✅ **Resolved** in the entry for 2026-09-10 below.

### 2026-07-27 — Runtime activation on WP 7.0.2 + WC 10.9.4
- **Result:** ✅ Plugin activates cleanly (no fatal).
- **Finding (non-blocking, PHP 8.1+):** `Automatic conversion of false to array` in
  `classes/Handlers/Update.php:29` — `get_option('dpdconnect_parcelshop')` returns `false`
  when the option is absent, then the array is mutated. Becomes a fatal in PHP 9.0.
- **Remediation:** ✅ **Fixed** — guard `$options` to `[]` when `get_option()` returns non-array.
- Not caught by the static PHPCompatibility scan (dynamic false→array conversion).

### 2026-07-27 — PHP 7.4–8.3 compatibility scan
- **Result:** ✅ Clean. PHPCompatibilityWP scanned 65 files, 0 errors / 0 warnings.
- **Remediation:** none required.

<!-- Template for future entries:
### YYYY-MM-DD — <trigger, e.g. "WooCommerce 11.0" / "PHP 8.4">
- Finding: <what breaks>
- Severity: blocking | non-blocking
- Remediation: <required change> (ticket: <link>)
-->
