# Composer security advisories (Launch Step 13)

**Date:** 22 Sep 2026  
**Command:** `composer audit --abandoned=ignore`  
**Status after patch pass:** **3 residual advisories** on `laravel/framework` v11.x (all others cleared).

## Patched this step

Updated lockfile / vendor (via `composer update … --with-all-dependencies`):

| Package | From → To (approx.) |
|---|---|
| guzzlehttp/guzzle | 7.10.0 → 7.15.5 |
| guzzlehttp/psr7 | 2.9.0 → 2.13.1 |
| laravel/framework | 11.51.0 → **11.56.1** (latest 11.x) |
| league/commonmark | 2.8.x → 2.10.3 |
| setasign/fpdi | 2.6.6 → 2.6.8 |
| Symfony http-foundation / http-kernel / mailer / mime / routing / yaml / polyfill-intl-idn | 7.4.8 → 7.4.18/19 |

Abandoned package `niklasravnsborg/laravel-pdf` remains ignored by CI (`--abandoned=ignore`). Replacement tracked post-launch.

## Residual advisories (waived)

These three affect **all Laravel 11.x** builds; Packagist lists fixes only on **Laravel 12.60+ / 12.61+** (and corresponding 13.x). There is no clean 11.x release that satisfies `composer audit` today.

| Severity | Advisory | Notes |
|---|---|---|
| high | [GHSA-5vg9-5847-vvmq](https://github.com/advisories/GHSA-5vg9-5847-vvmq) / CVE-2026-48019 | CRLF in default `email` validation rule |
| medium | [GHSA-crmm-hgp2-wgrp](https://github.com/advisories/GHSA-crmm-hgp2-wgrp) | Temporary signed URL path confusion |

### Mitigations while on Laravel 11

1. Prefer explicit `Rule::email()` / custom validators on auth and invite flows; avoid relying on bare `email` rule for any attacker-controlled header injection paths.
2. Temporary signed URLs: only use Laravel signed routes for IN-scope downloads already gated by account auth (`download_attachment`, document download); do not mint signed URLs for cross-tenant assets.
3. CI audit job stays required; residual IDs are documented here until Laravel 12.

### Waiver

| Field | Value |
|---|---|
| Waived packages | `laravel/framework` @ `^11.56` (three advisories above) |
| Reason | Fixes not backported to 11.x; Laravel 12 upgrade is a separate launch epic |
| Owner | Engineering |
| Expiry | **31 Dec 2026** or earlier when Laravel 12 upgrade lands |
| Re-check | Re-run `composer audit` after every `composer update`; remove this waiver when audit is clean |

## Frontend lockfile

- `package-lock.json` pinned via `npm install`.
- Documented build: `npm ci && npm run build` (CI / deploy).
- npm audit: esbuild/vite moderate findings are **dev-server only**; fixing requires Vite 8 (`npm audit fix --force`). Waived until post-launch Vite major upgrade (same expiry as Composer waivers).

## Local / CI commands

```bash
composer ci:audit          # php scripts/ci-composer-audit.php (waivers applied)
composer update            # prefer reviewing COMPOSER_ADVISORIES.md after any lock change
npm ci && npm run build
```
