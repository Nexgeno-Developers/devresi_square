# Admin UAT – Part 1 of 8: contractor document and trade decisions

Run on 24 Nov 2026 against local `resi/head`, signed in as the local Super Admin (`admin@resisquare.test`).
App: XAMPP MySQL 3306 (`resisquare_laravel_webdeveloper`), served on http://localhost:8003 with `MAIL_MAILER=log`, so no review emails went out through Gmail.
Automated tests: PHPUnit against the isolated DB `resisquare_qa_suite`.

## The 8 parts

| Part | Scope |
|---|---|
| **1** | **Contractor documents and trades: approve/reject a document, approve/more info/reject a trade, the one-click "Approve" (pack) decision** |
| 2 | Contractor status: suspend, reinstate, remove, reject application, request changes |
| 3 | Contractor operations: resolve stuck/disputed jobs, record a payout, contractor settings, "invite old-module contractors" email |
| 4 | Account actions: suspend, cancel, reactivate an account |
| 5 | Subscription detail actions |
| 6 | Plans and addons: addon create/edit/delete, plan changes against live Stripe prices |
| 7 | Admin repair issue list and tabbed view; offers (the 403 on the admin path) |
| 8 | Admin invoices, transactions, uncharged work orders |

## Test data

Created only in the dev DB, all on `@resisquare.test` addresses:

| Profile | Contractor | State |
|---|---|---|
| 2646 | Alpha Sparks (`p1-alpha`) | Submitted; plumbing and electrical; electrical scheme had no expiry date |
| 2647 | Bravo Gas (`p1-bravo`) | Submitted; gas; public liability cover exactly at the £1m minimum |
| 2648 | Charlie Fixit (`p1-charlie`) | Approved and working; general and plumbing |
| 2650 | Delta Volts (`p1-delta`, registration 107) | Submitted; electrical only; scheme with no expiry date |

## White-box review (`ContractorReviewController`)

| # | Finding | Severity | Status |
|---|---|---|---|
| W1 | The one-click **Approve** (`decidePack`) skipped the per-document rules (expiry required, reference, cover minimum, file present). It saved every document and trade as approved inside the transaction, then `approve()` refused the contractor. The result was a half-approved profile: documents and trade approved and logged, contractor still "submitted", and the error "No trade is approved yet" while the trade showed as approved. | High | **Fixed** |
| W2 | Rejecting a **live** document of an **approved** contractor (for example their public liability) blocked new work but left their open invitations and offers live. Rejecting a trade already withdraws them. | High | **Fixed** |
| W3 | A trade decision of "more info" or "reject" with no reason showed the framework text "The reason field is required unless decision is in approved." | Low (UX) | **Fixed** |
| W4 | A purged document (file deleted under retention), or a file-type document with no file, could still be approved. | Low | **Fixed** (same shared check) |
| W5 | In the pack, promoting a renewal deletes the old file from disk, which a database rollback cannot undo. | Low | **Fixed**: promotion now runs after the commit |

### What changed

- `app/Http/Controllers/Backend/ContractorReviewController.php`
  - New `approvalProblem()` holds the rules a document must meet to be approved. Both the single approve and the pack use it.
  - `decidePack()` checks every pending document first. Its writes run in a transaction that rolls back if the contractor still can't be approved. Renewals are promoted only after the commit.
  - `decideDocument()`: rejecting a document of an approved contractor now calls `withdrawBlocked()`.
  - `decideTrade()`: clear message when the reason is missing.
- `tests/Feature/ContractorMarketplaceTest.php`: 3 regression tests (pack blocked leaves nothing changed, then approves once fixed; a rejected live document withdraws an open offer; trade reason message). All 3 failed before the fix and pass after.

## Black-box and UAT cases

| ID | Case | Expected | Result |
|---|---|---|---|
| P1-01 | Reject a document with no reason | Blocked: "Pick or type a reason." | Pass |
| P1-02 | Reject Photo ID with a reason code and `<script>` in the free text | Rejected, reason shown escaped, history entry | Pass (escaped, no script runs) |
| P1-03 | Approve a previously rejected document | Approved, reason cleared | Pass |
| P1-04 | Approve electrical scheme with no expiry | Blocked: add the expiry date | Pass |
| P1-05 | Approve with an expiry date in the past | Blocked: already expired | Pass |
| P1-06 | Approve public liability with cover £100 | Blocked: cover must be at least £1,000,000; DB unchanged | Pass |
| P1-07 | Approve public liability with valid cover and expiry | Approved | Pass |
| P1-08 | Approve electrical trade before its document | Blocked: approve the trade documents first | Pass |
| P1-09 | Trade "more info" or "reject" with no reason | Blocked with a clear message | **Fail → fixed** (W3), re-tested Pass |
| P1-10 | Approve electrical trade once the scheme is approved | Approved; decision panel "Ready to approve for: Electrical" | Pass |
| P1-11 | Trade "more info" with a reason | Saved; reason shown on the trade | Pass |
| P1-12 | Open a document file | 200, PDF served, `document_viewed` logged | Pass |
| P1-13 | Document ID from another contractor (view and decide) | 404 | Pass |
| P1-14 | Unknown trade, unknown document ID | 404 | Pass |
| P1-15 | Invalid input: bad decision, bad reason code, reason over 500 chars, negative cover, invalid date | Rejected by validation; DB unchanged | Pass |
| P1-16 | Contractor role POSTs a document or trade decision | 403 | Pass |
| P1-17 | Landlord (account owner) POSTs or opens a file | 403 | Pass |
| P1-18 | Guest opens a document | Redirect to login | Pass |
| P1-19 | Reject the live public liability of an approved contractor | Rejected; contractor emailed and notified in app; review page shows "Not ready: Public liability insurance was rejected." | Pass |
| P1-20 | Same, with an open job invitation | Invitation withdrawn | **Fail → fixed** (W2). Covered by a PHPUnit test because the dev DB had no open offer for this contractor |
| P1-21 | Reject a trade of an approved contractor with a reason | Rejected; email and in-app notification "Plumbing: not approved yet" | Pass |
| P1-22 | One-click Approve on a pack whose scheme has no expiry | Blocked with a clear message; nothing written | **Fail → fixed** (W1). Before: 3 documents and the trade approved, contractor left "submitted" |
| P1-23 | Add the expiry, then one-click Approve | Contractor approved for Electrical; "You're approved" email | Pass |

## Minor notes (not changed)

- After an approve is blocked (for example low cover), the form shows the stored value again instead of what was typed.
- On an already approved contractor, the decision panel says "Ready to approve for: …", which reads oddly; "Approved for: …" would be clearer.
- On the registration page, the "Look through the documents… then approve or reject" hint still shows after approval.
- On the registration page the flash message appears both as a banner and as a toast.

## Not covered here

- The live documents of the seeded contractors were not changed.
- Email went to the log, not a real mailbox. Live email delivery is still a staging check.
