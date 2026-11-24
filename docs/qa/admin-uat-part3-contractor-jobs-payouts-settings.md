# Admin UAT – Part 3 of 8: stuck jobs, payouts, contractor settings, old-module invite

Run on 24 Nov 2026 against local `resi/head`, signed in as the local Super Admin.
Setup is the same as Parts 1–2: XAMPP MySQL 3306, app on http://localhost:8003 with `MAIL_MAILER=log`, PHPUnit on `resisquare_qa_suite`.
Pages covered: Contractors › Stuck and disputed jobs, Payouts, Settings, and All contractors › "Invite old-module contractors".

## Test data

Created only in the dev DB, all on `@resisquare.test` addresses, on landlord account 1:

| Fixture | Contractor | State |
|---|---|---|
| A | Hotel Handyman | Awarded, in progress, disputed ("Tenant says the leak is back") |
| B | Hotel Handyman | Awarded, **invoiced, landlord paid £240**, disputed ("Landlord disputes the invoice amount"); payout waiting |
| C | Hotel Handyman | Awarded 10 days ago, no visit booked (overdue) |
| D | India Plumbing | Invoiced and paid; payout waiting |
| Compliance offer 2 | Ada Gas | Marked disputed for the test, then put back |

Restored afterwards:
- Contractor settings were saved before testing and put back (checked byte-for-byte).
- The 19 legacy profiles the invite run created were deleted.
- The compliance offer's dispute flag was cleared.

## White-box review

| # | Finding | Severity | Status |
|---|---|---|---|
| W1 | **Cancel award on an invoiced, paid job** was allowed. The job became "Cancelled", but the invoice stayed paid, the £240 payout stayed queued for the contractor, and the dispute flag stayed set, so the cancelled job sat in "Disputed" for good. The page offers "Cancel award" on every disputed job, and disputes are usually about invoiced work. | **High** (money) | **Fixed**: refused once the job is invoiced, with "Mark the dispute resolved instead, and refund the landlord in Stripe if needed". A successful cancel now also clears the dispute flag. |
| W2 | **Close, Mark resolved and Cancel award reported "Done" when nothing changed** (closing a job that isn't open, resolving one that isn't disputed, repeating a cancel). Each one also wrote a fake "Admin closed: …" or "Admin resolved dispute: …" line into the job's audit trail. | Medium | **Fixed**: each is refused with a clear message, and nothing is logged. The compliance-check dispute already behaved this way. |
| W3 | **Payouts showed no warning for cancelled or disputed jobs.** "Send £240.00" looked the same as any other payout. | Medium | **Fixed**: red line on the payout row, "This job was cancelled. Check whether the landlord should be refunded…" or "This job is disputed: … Resolve the dispute before sending." Applies to repair and compliance payouts. |
| W4 | **Raising the insurance minimum quietly took contractors out of work.** Moving public liability from £1m to £6m said "Settings saved." Afterwards 0 of 9 approved contractors could receive jobs, with no warning. | Medium | **Fixed**: the save message says how many approved contractors now have less cover than the new minimum. |
| W5 | **Expiry reminders accepted bad values**, such as "30, 30, 0, 999" (repeats, and 999 days, which would remind everyone at once). The 0 was silently dropped. | Low | **Fixed**: whole days from 1 to 365, no repeats, saved largest first |
| W6 | **The old-module invite never resends an expired link.** After the 72-hour link runs out, the contractor can never be invited again ("No old-module contractors are waiting"). There was 1 such contractor in the dev DB. | Medium | **Fixed**: invites whose link expired unclaimed are sent a new link |

### What changed

- `app/Services/Contractors/RepairJobOfferService.php`: `override()` checks state for each action, refuses cancelling an invoiced job, and clears the dispute when an award is cancelled.
- `app/Http/Controllers/Backend/ContractorReviewController.php`: `saveSettings()` validates reminder days and warns when a raised insurance minimum blocks contractors.
- `app/Services/Contractors/ContractorDirectory.php`: `sendLegacyClaims()` includes expired, unclaimed invites.
- `resources/views/backend/contractors/payouts.blade.php`: cancelled and disputed warnings.
- `tests/Feature/ContractorMarketplaceTest.php`: 3 regression tests (override refusals and the invoiced cancel; settings reminder check and minimum warning; expired-invite resend). All 3 fail on the old code and pass now.

## Black-box and UAT cases

| ID | Case | Expected | Result |
|---|---|---|---|
| P3-01 | Mark a disputed in-progress job resolved, with a note | Dispute cleared; both sides told; audit entry | Pass |
| P3-02 | Cancel award on a disputed job that is invoiced and paid | Refused | **Fail → fixed** (W1). Before: cancelled, payout still queued. After: refused with the message above |
| P3-03 | Cancel award on an awarded job with no visit for 10 days | Cancelled; leaves the overdue list; "Award cancelled" sent to both sides | Pass |
| P3-04 | Repeat Close, Mark resolved or Cancel award on an already cancelled job | Refused; no audit entry | **Fail → fixed** (W2) |
| P3-05 | Unknown action; unknown job ID | Validation error; 404 | Pass |
| P3-06 | Compliance dispute: note of only spaces | "The note field is required." | Pass |
| P3-07 | Compliance dispute: resolve with a note, then repeat | Resolved and both told; the repeat gives "This check is not disputed." | Pass |
| P3-08 | Record a payout with a reference of only spaces | "The payout reference field is required." | Pass |
| P3-09 | Record a payout, then send the same request 3 times at once | One record, one "Payment sent" notice, the repeats refused with "already recorded" | Pass (see note on concurrency) |
| P3-10 | Payout row for a cancelled job, and for a disputed job | Visible warning | **Fail → fixed** (W3) |
| P3-11 | Settings: untick Gas Safe for gas work | "Gas work must always require Gas Safe registration." | Pass |
| P3-12 | Settings: retention 10 days, commission 150% | Refused | Pass (only the first error is shown at a time) |
| P3-13 | Settings: reminders "30, 30, 0, 999" | Refused | **Fail → fixed** (W5) |
| P3-14 | Settings: raise public liability minimum to £6m | Saved, with a warning naming how many contractors are now blocked | **Fail → fixed** (W4). After: "5 approved contractors now have less cover…" in this DB |
| P3-15 | Invite old-module contractors (after the confirm dialog) | "Sent 19 claim invitations."; a second click sends nothing | Pass |
| P3-16 | Open a claim link from the email; open it with a wrong token | Claim page; 404 | Pass |
| P3-17 | Invite again after a link expired | New link sent | **Fail → fixed** (W6). Covered by PHPUnit; not re-run in the browser, to avoid replacing the claim token of the documented legacy test account (user 7791) |

## Minor notes (not changed)

- Electrical work can be set to need no registration document. Only Gas Safe is enforced. Whether electrical scheme registration should be compulsory is a product and legal call.
- "Cancel award" has no confirmation step. It needs a note, but one click then cancels. Payout and legacy invite both ask to confirm.
- Cancelling an award sets every contractor request on that repair back to "awaiting", including one already completed by another contractor.
- Settings shows only the first validation error at a time.
- After a failed payout save, every payout row's reference box is filled with the typed value, not just the row that failed.
- Recording a payout has no database lock. The local server handles one request at a time, so a true race couldn't be reproduced here. With several PHP workers, two simultaneous clicks could both pass the "already recorded" check and send the contractor two notices. No money moves, because recording is bookkeeping only.
- Settings that raise an insurance minimum don't pause anyone or withdraw open invitations. Contractors below the new minimum are blocked from new work only.
