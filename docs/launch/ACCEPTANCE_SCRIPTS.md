# Launch acceptance scripts

**Status:** Active (Step 2 complete)  
**Use:** Only checklist that can sign off GA (Step 48–50).  
**Environments:** Staging first; production dress-rehearsal before GA.

Record for each run: date, build/commit, actor, pass/fail, screenshots for failures.

---

## A. Landlord Happy Path

1. Register new landlord (OTP) → land in trial.
2. Complete first-home overlay (address → owners → lead tenant).
3. See property on portfolio with human address title (not only system ID).
4. Open property → create/edit tenancy (rent, dates, deposit, members).
5. Tenancy show page renders in full workspace layout.
6. Invite tenant portal access; resend works.
7. Create rent invoice for a period; amount shows with £.
8. Raise a repair with photo; status visible.
9. Upload a named document; set portal visibility.
10. Add Gas/EPC/EICR with expiry; see needs-you / reminder path.
11. Record deposit protection fields + attach prescribed-info doc.
12. Create a calendar visit; reminder fires (mail or in-app).
13. Open Billing & Plan; see trial/plan without raw Stripe IDs as primary UI.
14. Log out / log in as co-owner (if used) → portfolio still correct.

**Pass:** Steps 1–14 without support, no Debugbar, no staff copy, no blank pages.

---

## B. Tenant Happy Path

1. Accept invite / set password → tenant home (not staff dashboard).
2. My Tenancy shows correct address and household; confirm details.
3. Request a correction → landlord sees request; approve/reject updates tenant.
4. Rent: open invoice → pay by card (client-money Stripe) → paid state.
5. Report repair with photo on phone (≤390px) in under 2 minutes.
6. Download a portal-visible document.
7. See visit on calendar (real calendar or designed empty — not a stub pretending).
8. Profile: update contact / password / notification prefs without “Back to Users”.

**Pass:** Steps 1–8 on desktop and phone width; bottom tabs on phone.

---

## C. Money Path

1. Landlord creates invoice £X.
2. Tenant pays via Stripe rent checkout.
3. Webhook marks invoice paid exactly once (no double-apply).
4. Landlord ledger shows paid; tenant sees paid.
5. Manual bank-transfer payment path works when card rent is off.
6. Void invoice blocks further pay.
7. Subscription: trial → Checkout → active; Customer Portal opens.
8. Force past_due (test clock or failed card) → AccountStatusGuard behaviour matches contract.

**Pass:** Ledger and Stripe dashboard agree; no silent failures.

---

## D. Failure Path

1. Tenant opens another account’s tenancy/invoice/document URL → 403.
2. Landlord opens another account’s property by ID → 403.
3. Soft-delete / archive property → no orphan “Active” tenancy UX lie.
4. Expired certificate surfaces on dashboard / compliance.
5. Rejected tenancy correction notifies tenant; data unchanged.
6. Failed card pay shows clear error; invoice remains unpaid.
7. Suspended/cancelled account blocked from business modules; billing still reachable as designed.
8. Empty portfolio / empty rent / empty repairs show designed empty states (not blank tables).

**Pass:** Every failure is explicit and safe; no data leak; no 500 HTML dump to clients.

---

## Sign-off

| Script | Staging | Prod dress-rehearsal | Signer |
|---|---|---|---|
| A Landlord Happy Path | | | |
| B Tenant Happy Path | | | |
| C Money Path | | | |
| D Failure Path | | | |
