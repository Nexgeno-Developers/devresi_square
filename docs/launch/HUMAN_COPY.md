# Human copy system (Launch Step 38)

Primary labels on IN screens must read as humans speak.

## Rules

| Kind | Do | Don’t |
|---|---|---|
| Property | Address / short title (`rs_property_title`) | `RESISQP…` alone, `Property #12` |
| Person | Name (`rs_person`) | Email or `Tenant #3` as the heading |
| Rent | `September 2026 rent` (`rs_rent_title`) | `RENT-0003` as the only label |
| Money | `£1,100.00` (`rs_money`) | bare `1100` / `GBP 1100` |
| Date | `1 Jan 2026` (`rs_date`) | `2026-01-01` |
| Document | Real title (`rs_document_title`) | `Untitled` |

System refs may appear as secondary muted text under the human title.

## Review checklist (before merge)

- [ ] No new `prop_ref_no` as the sole visible title on landlord/tenant IN views
- [ ] No new `Untitled` document titles without a type fallback
- [ ] List/detail money uses `rs_money` or an explicit `£`
- [ ] Display dates use `rs_date` / `rs_datetime` / `rs_month` (form inputs may stay `Y-m-d`)
