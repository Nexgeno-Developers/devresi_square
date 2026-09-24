# CI (Launch Step 7)

GitHub Actions workflow: `.github/workflows/ci.yml`

## Jobs

| Job | What it does | Merge gate |
|---|---|---|
| **tests** | MySQL 8 → `migrate:fresh --seed` → PHPUnit | Required |
| **audit** | `composer audit --abandoned=ignore` | Required |

## Local equivalents

```bash
composer test
composer ci:audit
```

## Notes

- CI uses **MySQL 8**, matching the production-class engine (not SQLite).
- Debugbar / `APP_DEBUG` are forced off in the CI env.
- Security advisories: see `docs/launch/COMPOSER_ADVISORIES.md` (Step 13). CI still runs `composer audit --abandoned=ignore`; residual Laravel 11-only advisories are waived with expiry until Laravel 12.
- Abandoned packages (e.g. `niklasravnsborg/laravel-pdf`) are ignored by the audit gate so they do not block on deprecation alone.
- Frontend: `package-lock.json` pinned; production assets via `npm ci && npm run build`.
