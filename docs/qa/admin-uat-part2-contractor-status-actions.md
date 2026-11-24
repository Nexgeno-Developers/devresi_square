# Admin UAT – Part 2 of 8: contractor status actions

Run on 24 Nov 2026 against local `resi/head`, signed in as the local Super Admin.
Setup is the same as Part 1: XAMPP MySQL 3306, app on http://localhost:8003 with `MAIL_MAILER=log`, PHPUnit on `resisquare_qa_suite`.
Scope: suspend, reinstate, remove, reject application, request changes ("More info needed"). The full list of parts is in `admin-uat-part1-contractor-documents-trades.md`.

## Test data

Created only in the dev DB, all on `@resisquare.test` addresses:

| Profile | Contractor | State |
|---|---|---|
| Echo Builds Ltd (`p2-echo`) | Approved limited company | One open invitation, one accepted job |
| Foxtrot Fixers Ltd (`p2-foxtrot`) | Approved limited company, company number QA654321 | One open invitation |
| Golf Repairs (`p2-golf`) | Submitted application | Documents and trades pending |

## White-box review (`ContractorReviewController`, `RepairJobOfferService`)

| # | Finding | Severity | Status |
|---|---|---|---|
| W1 | Suspend, reject and request changes had **no server-side status check**. Only the page hid the buttons. A stale page or a forged POST could move an approved contractor to "More info needed", which left their open offers live; reject or suspend a removed contractor; or remove the same contractor twice. Only reinstate checked status. | Medium | **Fixed** |
| W2 | **Remove** clears the company number so the business can sign up again, but **reinstate** didn't bring it back. A reinstated limited company had no Companies House number. | Medium | **Fixed**: removal saves the number in the history entry, and reinstate restores it. If another active account now uses it, the number is not restored and the admin is told why. |
| W3 | **Remove** sent the contractor no notice. Suspend, reject and reinstate all do. The only email the contractor got was "This job was withdrawn". | Medium | **Fixed**: email and in-app notice "Your contractor account was removed" with the admin's reason |
| W4 | Suspending, removing or rejecting a contractor who has an **accepted job** told the contractor and landlord "Contractor insurance or registration needs attention", and told admins "Awarded job with lapsed compliance". Nothing had lapsed. | Low–Medium | **Fixed**: "Contractor is no longer active" and "Awarded job with an inactive contractor". Real document lapses keep the old wording. |
| W5 | The suspend and remove reasons are sent to the contractor, but the boxes didn't say so ("Reason"). | Low (UX) | **Fixed**: placeholders now say "(the contractor sees this)" |

### What changed

- `app/Http/Controllers/Backend/ContractorReviewController.php`: `refuseUnless()` status checks on request changes, reject and suspend; remove refuses an already removed contractor. Remove keeps the company number in the history snapshot and notifies the contractor. Reinstate restores the number.
- `app/Services/Contractors/RepairJobOfferService.php`: `flagCompliance()` takes `$statusChange`, which `withdrawBlocked()` sets when the admin changed the contractor's status, and words the messages to match.
- `resources/views/backend/contractors/review-show.blade.php`: reason placeholders.
- `tests/Feature/ContractorMarketplaceTest.php`: 3 regression tests (wrong-status actions refused; removal notifies and reinstate restores the company number; suspension with an accepted job is not called a compliance lapse). All 3 failed on the old code and pass now.

## Black-box and UAT cases

| ID | Case | Expected | Result |
|---|---|---|---|
| P2-01 | Request changes with nothing rejected and no note | Blocked with a clear message | Pass |
| P2-02 | Reject application with an empty reason | Browser blocks it (required field) | Pass |
| P2-03 | Request changes with a note containing HTML | Status "More info needed"; email "A few things to fix"; note escaped | Pass |
| P2-04 | Reject application with a reason | Status "Rejected"; email; message "They can re-apply" | Pass |
| P2-05 | Suspend an approved contractor with a reason | Suspended; open invitation withdrawn; accepted job kept; contractor and landlord told | Pass |
| P2-06 | Messages sent on P2-05 | Messages say the contractor is no longer active | **Fail → fixed** (W4), re-tested Pass |
| P2-07 | Reinstate a suspended contractor | Approved; "active again" email; history entry | Pass |
| P2-08 | Reinstate an approved contractor, or a rejected application (forged) | Refused (404) | Pass |
| P2-09 | Suspend or remove with no reason; reason over 2,000 characters | Validation error; no change | Pass |
| P2-10 | Unknown or non-numeric profile ID | 404 | Pass |
| P2-11 | Forged request changes or reject on an approved contractor | Refused, status unchanged | **Fail → fixed** (W1). Before: moved to "More info needed" and emailed. Now: "This contractor is approved, so they cannot be sent back for more info." |
| P2-12 | Remove an approved contractor through the expandable "Remove contractor" section | Removed; open invitation withdrawn; redirect to All contractors | Pass |
| P2-13 | Contractor told about removal | Email and in-app notice with the reason | **Fail → fixed** (W3), re-tested Pass |
| P2-14 | Removed contractor logs in | Cannot reach jobs, profile or bank pages | Pass (403 "This contractor account has been removed") |
| P2-15 | Reinstate a removed limited company | Approved with company number QA654321 | **Fail → fixed** (W2). Before: company number blank |
| P2-16 | Suspend, reject or remove an already removed contractor (forged) | Refused | **Fail → fixed** (W1), covered by a PHPUnit test |

## Minor notes (not changed)

- The decision panel says "Ready to approve for: …" on approved and suspended contractors; "Approved for: …" would read better.
- "Reject application" is still offered on a contractor who is already rejected, so a second click sends a second email.
- A removed contractor who logs in lands on a bare 403 page. A friendly page that says who to contact would be kinder.
- Suspend, then reinstate, leaves `compliance_notified_at` set on an accepted job. If that contractor's insurance later lapses, no second warning goes out for that job. This pattern predates this pass.
- The "company number now used by another account" branch of reinstate is covered by code review only, not by a browser case.
