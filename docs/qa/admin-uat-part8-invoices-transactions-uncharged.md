# Admin UAT – Part 8 of 8: repair invoices, transactions, uncharged work orders

Run on 24 Nov 2026 against local `resi/head`. Accounts used: the local Super Admin; `estate.owner@resisquare.test` (estate agent, account 2); `landlord.owner@resisquare.test` (landlord, account 1); plus the seeded tenant and contractor.
Setup is the same as before: XAMPP MySQL 3306, app on http://localhost:8003, PHPUnit on `resisquare_qa_suite`.

Pages covered: Invoices (`/admin/invoices`: list, status filters, view, PDF, edit, mark paid, generate from work order, search and JSON lookups), Transactions (`/admin/transactions`: list, create, delete) and Accounting › Uncharged repair work orders.

## Who can reach what

| Role | Invoices | Transactions | Uncharged work orders |
|---|---|---|---|
| Super Admin | Yes | Yes | Yes |
| Estate agent owner | **Yes** | 403 | 403 |
| Landlord owner | 403 (landlord plan) | 403 | 403 |
| Tenant, contractor | 403 | 403 | 403 |

## White-box review (`InvoiceController`, `Invoice`, `TransactionController`, `UnchargedRepairWorkOrderController`)

| # | Finding | Severity | Status |
|---|---|---|---|
| W1 | **Any estate agent could reach every account's repair invoices.** No invoice query checked the account: the list, view, PDF, edit, update, **mark paid**, generate-from-work-order, search and JSON lookup. Reproduced: the agent (account 2) saw the landlord's (account 1) £480 invoice in the list, opened it, downloaded its PDF, and got it from search with the landlord's name. They also **marked it paid** (status changed in the DB). The edit page offered every user on the platform as "Invoice to". | **Critical** (cross-account read and write) | **Fixed**: an invoice belongs to the account of its work order. Every action now goes through an account-scoped query, so another account's invoice returns 404 and nothing changes. Search and JSON are scoped the same way. "Invoice to" offers only the account's members, and update refuses anyone else. A Super Admin outside an account still sees all. |
| W2 | **Invoice status numbers disagreed.** Payments, badges and the PDF follow 3 Partially Paid, 4 Overdue, 5 Cancelled. The list filter used 3 Overdue, 4 Cancelled, and so did the `invoice_statuses` table, which had no 5. So "Overdue" listed part-paid invoices, the PDF printed **"OVERDUE"** on a part-paid invoice, and a cancelled invoice's PDF would fail (`null->name`). | Medium | **Fixed**: filter map corrected (Partially Paid filter added); migration `2026_11_24_180000_align_repair_invoice_statuses` aligns the table, **run on 3306 and 3307** and on the test DB |
| W3 | **Transactions couldn't be recorded with no bank account.** The form marked Bank account as required, but the server treats it as optional, and none exist on this install. The admin could not save any transaction. | Medium (UX) | **Fixed**: the field is optional; the empty choice reads "-- No bank accounts set up --" or "-- None --" |
| — | Transactions and Uncharged work orders have no per-account scoping | — | **Not changed, as designed**: both live in the Accounting module, which you locked to Super Admin on 20 Nov because GL data has no account scoping. Everyone else gets 403, as confirmed above. |

### What changed

- `app/Http/Controllers/Backend/InvoiceController.php`: `invoices()` and `billableUsers()` scoped helpers used by every action; status filter ids corrected.
- `app/Models/Invoice.php`: `ajaxSearchForSelect()` takes an account id.
- `database/migrations/2026_11_24_180000_align_repair_invoice_statuses.php` (new): data migration. **Production needs `php artisan migrate`.**
- `resources/views/backend/transactions/_form.blade.php`: bank account optional.
- `tests/Feature/RepairInvoiceAccountScopeTest.php` (new): 2 tests. (1) An agent only reaches their own account's invoices: list, view, PDF, edit, JSON, search, mark paid, generate. (2) The status filter matches the badges. Both fail on the old code and pass now.

## Black-box and UAT cases

| ID | Case | Expected | Result |
|---|---|---|---|
| P8-01 | Each role opens Invoices, Transactions, Uncharged | As in the access table | Pass |
| P8-02 | Agent lists invoices; another account's invoice exists | Not shown | **Fail → fixed** (W1) |
| P8-03 | Agent opens, downloads or edits another account's invoice by ID | 404 | **Fail → fixed** (W1). Before: 200, PDF downloaded |
| P8-04 | Agent marks another account's invoice paid | 404; unchanged | **Fail → fixed** (W1). Before: marked Paid |
| P8-05 | Agent generates an invoice from another account's work order | 404 | **Fail → fixed** (W1) |
| P8-06 | Agent search and JSON lookup for another account's invoice | Not returned | **Fail → fixed** (W1). Before: number, payer name and outstanding £480 returned |
| P8-07 | Agent views, downloads, edits and marks paid their **own** invoice | Works; "Invoice to" lists the 2 people on the account | Pass |
| P8-08 | Super Admin invoice list | Invoices from all accounts | Pass |
| P8-09 | Record a £200 completed payment on a £480 invoice (no bank account) | Saved; invoice becomes Partially Paid | **Fail → fixed** (W3: form blocked). Then Pass |
| P8-10 | Filter "Partially paid" and "Overdue"; PDF label | Part-paid shows under Partially paid only; PDF prints "PARTIALLY PAID" | **Fail → fixed** (W2) |
| P8-11 | Cancelled invoice PDF | Prints "CANCELLED" | **Fail → fixed** (W2). Before: no status 5 row, so the PDF failed |
| P8-12 | Delete the payment (confirm dialog) | Deleted; invoice back to Pending | Pass |
| P8-13 | Uncharged work orders list; search with `'"><script>` | 15 rows; search shown escaped | Pass |

## Minor notes (not changed)

- "Mark paid" on an invoice sets it to Paid without recording a payment. The next payment or deletion on that invoice recalculates the status from real payments and can flip it back.
- Transaction amount allows 0 and has no upper limit. Over-paying an invoice is accepted and simply marks it Paid.
- After creating a transaction the list showed no success message (deleting does show one).
- The invoice edit page posts by AJAX and returns JSON errors. Not checked in depth here.
- The test invoices, work orders and payment were removed from the dev DB afterwards.
