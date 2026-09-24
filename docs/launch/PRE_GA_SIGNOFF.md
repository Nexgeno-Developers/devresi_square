# Pre-GA sign-off (Step 49)

**Done when:** Written triple sign-off — Integrity · Functionality · Usability.  
**Prereqs:** Steps 45–48 packs executed or explicitly waived with risk note.

## Integrity

Re-run `docs/launch/INTEGRITY_REAUDIT.md` checklist on staging/prod dress-rehearsal.

- [ ] Kill switches still on (debug, SMTP UI, UI Lab, public artisan)
- [ ] IDOR matrix green (`InScopeIdorMatrixHttpTest` + spot manual)
- [ ] Uploads private + XSS sanitiser still in path
- [ ] Dual Stripe secrets distinct in production
- [ ] `launch:assert-money-config --env=production`
- [ ] `launch:assert-ops --env=production`
- [ ] Privacy pack published / linked (notice, DPA, retention, DSAR command)

**Integrity signer:** _________________ date ________ · GO / NO-GO

## Functionality

- [ ] Acceptance scripts A–D green on staging (UAT_PACK)
- [ ] Recurring rent cron observed once
- [ ] Portal invite → confirm/correction → rent pay → repair photo → document download
- [ ] Billing: trial → Checkout → past_due recovery path rehearsed
- [ ] Automated proof filter green (`AUTOMATED_PROOF.md`)

**Functionality signer:** _________________ date ________ · GO / NO-GO

## Usability

- [ ] Desktop walk of every IN landlord route (nav flat, kit, human copy, empties)
- [ ] 390px walk of tenant shell (bottom tabs, pay-first rent, profile)
- [ ] Auth pages branded; error shells 403/404/419/500
- [ ] A11y spot-check: login, pay, report repair, invite (keyboard + focus visible)
- [ ] UI Lab not visible to customer roles
- [ ] MFA / TOTP for landlord owners: enabled or explicitly deferred with risk note ________

**Usability signer:** _________________ date ________ · GO / NO-GO

## Triple gate

| Pillar | Result |
|---|---|
| Integrity | GO / NO-GO |
| Functionality | GO / NO-GO |
| Usability | GO / NO-GO |

All three GO required to open Step 50.
