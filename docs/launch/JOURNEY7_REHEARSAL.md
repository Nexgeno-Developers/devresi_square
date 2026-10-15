# Journey 7 rehearsal

Fill this on staging after `launch:seed-content`, the cron line in `docs/launch/deploy/scheduler.cron`, and the worker in `docs/launch/deploy/queue-worker.conf` are installed. `launch:assert-ops` must pass and `APP_DEBUG` must be false. A failed step is fixed on a new commit and the whole script is run again.

Public registration stays closed until `docs/launch/GA_GATE.md` is signed.

| Script | Commit | Result | Notes |
|---|---|---|---|
| A | | | |
| B | | | |
| C subscription Checkout and rent Checkout, including webhook replay | | | |
| D failure script and the stop-ship list | | | |
| E | | | |
| Probe data deleted; counts match the content seed | | | |

Production, after that commit is deployed: migrate, `launch:seed-content`, install cron and the worker, `php artisan queue:restart`. Secrets stay in the environment file. Write `APP_KEY` only when it is absent. Operating and rent Stripe accounts are different accounts, with `sk_live_` secrets and `whsec_` webhook secrets. Then:

```bash
php artisan launch:assert-money-config --env=production
php artisan launch:assert-ops --env=production
```

Charge a live card only when the rent account is allowed to take a real payment. Otherwise the signed script C is the card proof.
