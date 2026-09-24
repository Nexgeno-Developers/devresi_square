# Public GA gate (Step 50)

**Done when:** Marketing/onboarding may invite the public; support + rollback ready.  
**Requires:** Step 49 triple GO.

## Freeze

- [ ] Main branch green on CI + launch-critical filter
- [ ] Feature freeze window agreed (exceptions need Scope Contract change)
- [ ] Production secrets rotated / verified (APP_KEY, Stripe, mail, DB)
- [ ] `launch:assert-money-config --env=production` PASS
- [ ] `launch:assert-ops --env=production` PASS

## Go-live day

1. Enable public registration (or open waitlist → self-serve as designed).
2. Smoke: one new landlord signup → OTP → first home → invite tenant → rent invoice → card pay (or bank path).
3. Watch: 5xx, webhook logs, queue depth, ESP bounces for 2 hours.
4. Support: inbox + runbooks (`OPS_RUNBOOK.md`, `PRODUCTION_MAIL_AND_STRIPE.md`) linked in team channel.

## Rollback

| Trigger | Action |
|---|---|
| Money mis-apply | Disable rent Checkout (`STRIPE_RENT_*` / feature flag); keep bank transfer; page eng |
| Auth / isolation incident | Maintenance mode; revoke sessions; fix + re-audit |
| Mail total failure | Pause invites; switch ESP; do not batch-resend blindly |
| Bad deploy | Roll previous release artifact; `queue:restart`; verify `/up` |

## Public message

- Scope stays landlord MVP + tenant portal (`SCOPE_CONTRACT.md`).
- Do not market agency GL, TDS APIs, or contractor marketplace at GA.

## Final gate

| Check | Owner | GO |
|---|---|---|
| Triple sign-off (49) attached | | |
| Support rota named | | |
| Rollback rehearsal done once | | |
| Public invite / registration opened | | |

**GA decision:** GO / NO-GO · _________________ date ________
