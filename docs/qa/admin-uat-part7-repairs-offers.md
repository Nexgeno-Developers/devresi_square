# Admin UAT – Part 7 of 8: repair issue list, tabbed view, offers

Run on 24 Nov 2026 against local `resi/head`. Accounts used: the local Super Admin; `landlord.owner@resisquare.test` (landlord, account 1); `estate.owner@resisquare.test` (estate agent, account 2).
Setup is the same as before: XAMPP MySQL 3306, app on http://localhost:8003, PHPUnit on `resisquare_qa_suite`.

Pages covered: Repair › Issue list (`/admin/property-repairs/issue-list`), the tabbed view (`issue-list-tabbed`, with Issue, Property Manager, Request a quote and Work Order tabs), the repair show and edit pages, and Sales Offer (`/admin/offers`).

## The offers 403: expected, not a bug

| Who | `/admin/offers` | Menu shows "Sales Offer" |
|---|---|---|
| Landlord owner (account 1) | **403** "This area is not available on the landlord plan." | No |
| Estate agent owner (account 2) | 200; list and create work | Yes |
| Super Admin | 200 | Yes |

Sales offers are an estate-agent feature. `RestrictLandlordRoutes` keeps landlord accounts to an allowlist, and the landlord menu and property pages never link to offers. The 403 seen earlier came from opening the offers URL while working in a landlord account, which is the intended block.

## White-box review (`PropertyRepairController`)

| # | Finding | Severity | Status |
|---|---|---|---|
| W1 | **Other accounts' contractors and managers were shown to every account.** The "Request a quote" tab listed every contractor on the platform by name and email, including those of other agencies and marketplace contractors who had not joined. The Property Manager tab listed every Property Manager user across all accounts. The same unscoped lists were loaded on the issue list, show and edit pages. Reproduced as the landlord: **48 contractors** listed, including my test contractors from other accounts. | **High** (privacy, cross-account) | **Fixed**: two account-scoped helpers. Contractors: the account's contractor members, the contractors it has added, and anyone already asked to quote on this repair. Managers: active members of the account, plus anyone already assigned. Used in all 6 places, and also when sending quote requests, so a forged contractor ID from another account is ignored. After the fix the landlord sees only their own contractor (Carl Contractor). |
| W2 | **"Add contractor" could take over any user.** `saveQuoteContractor()` looked the email up across the whole platform. It then overwrote that person's name, phone and address and gave them the **Contractor** role, even if they were another account's landlord, a tenant, or staff. | **High** (latent) | **Fixed**: an existing user is only linked, never edited. If they aren't a contractor, the request is refused ("…belongs to an existing user who is not a contractor"). New contractors are linked to the account. **Not exploitable today**: these endpoints sit behind `LEGACY_CONTRACTOR_MODULE` (off, so 404), and landlords are also blocked by plan. It would have become live if the legacy module were switched on. |

### What changed

- `app/Http/Controllers/Backend/PropertyRepairController.php`: `accountContractorsQuery()` and `accountPropertyManagersQuery()` used for every contractor and manager list and for quote requests; `saveQuoteContractor()` never edits an existing user and links new contractors to the account.
- `tests/Feature/ContractorMarketplaceTest.php`: 2 regression tests (the tabs only list this account's contractors and managers; adding a quote contractor never edits an existing user or gives them a role). Both fail on the old code and pass now.

## Black-box and UAT cases

| ID | Case | Expected | Result |
|---|---|---|---|
| P7-01 | Landlord opens Sales Offer URL | 403 "not available on the landlord plan"; no menu entry | Pass (by design) |
| P7-02 | Estate agent opens Offers list and Create | 200; menu shows Pending, Accepted, Create | Pass |
| P7-03 | Issue list: search by property, by repair reference "RESISQRPR", by status; unknown status; page 999 | 200 each; unknown status ignored | Pass (the reference search works through an accidental correlated column; see notes) |
| P7-04 | Tabbed view with no repair selected | Redirects to the first repair, Issue tab | Pass |
| P7-05 | Switch to the Work Order tab (desktop) | Tab loads by AJAX; URL updates to `tabname=work-order` | Pass |
| P7-06 | Phone width (375px): list → tap a repair → detail | Detail with "Back to repairs" and all 4 tabs | Pass (the tab row scrolls sideways; "Work Order" is cut off at the edge) |
| P7-07 | "Request a quote" tab as the landlord | Only this account's contractors | **Fail → fixed** (W1). Before: 48 platform-wide |
| P7-08 | Property Manager tab as the landlord | Only this account's people | **Fail → fixed** (W1) |
| P7-09 | Issue list, show and edit pages contain no other-account contractors | None | Pass after the fix |
| P7-10 | Landlord or agent POSTs "add contractor" with another account's user email | Refused; that user unchanged | Pass at runtime (404 or 403 from the module and plan gates). The code path is fixed and tested (W2) |

## Minor notes (not changed)

- The tabbed view's browser title is "Index Tabbed · Resisquare". "Repairs" would be clearer.
- The issue-list search puts `reference_number` inside the property subquery. It only works because MySQL resolves it to the outer `repair_issues` column. It would be cleaner to search the repair's own column directly.
- At phone width the list table scrolls sideways to reach "Posted on" and Actions.
- With `APP_DEBUG=true` (local), a blocked JSON request returns the full stack trace. Make sure production has `APP_DEBUG=false`.
