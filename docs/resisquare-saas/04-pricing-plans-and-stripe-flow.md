# Resisquare pricing plans and Stripe subscription flow

**Pricing status:** recommended launch hypothesis, not a validated commercial decision. Prices should be tested with UK estate agents and landlords before production publication.

## Pricing principles

- Charge the paying workspace, not every invited tenant, owner or contractor.
- Keep the first choice simple: Estate Agent or Landlord.
- Use capacity add-ons for expansion without inventing many confusing editions.
- Bill in GBP and state clearly whether VAT is included. The proposal below is **excluding VAT**.
- Give annual customers two months free, equivalent to paying for ten months.
- Keep property-management invoices separate from Resisquare subscription invoices.
- Never turn a landlord contact created by an agency into a paid user automatically.

The existing public page advertises flat £75, £150 and £180 monthly plans but those buttons are not connected to a plan catalogue or subscription. A current market anchor, [Arthur's published pricing](https://www.arthuronline.co.uk/pricing/), starts from £82.50 per month for 55 units and increases by unit/edition. Resisquare can enter below that agency minimum while using limits/add-ons to preserve expansion revenue. This is a positioning hypothesis, not a claim that the products are directly equivalent.

## Suggested launch plans

| Plan | Monthly | Annual | Included capacity | Included modules | Excluded/requires add-on |
|---|---:|---:|---|---|---|
| Landlord | £19 + VAT | £190 + VAT | 5 active properties; 1 admin; unlimited linked tenant/owner/contractor portal users; 5 GB files | Properties, tenants/tenancies, rent invoices/payment records, maintenance, contractors, documents, communications, standard reports | No company branches or branch staff; Property Manager seat is an add-on; extra property packs |
| Estate Agent | £149 + VAT | £1,490 + VAT | 100 active properties; 2 branches; 5 staff seats; unlimited portal contacts; 25 GB files | Company/branch/staff, roles, property managers, all core property/tenancy/maintenance/document modules, finance, branch reports | Extra branches, staff seats, property packs and storage |

“Active property” means a non-archived property. Archived records remain available for history and do not consume capacity unless reactivated.

### Add-ons

| Add-on | Suggested price | Availability | Rule |
|---|---:|---|---|
| Property Manager seat | £49/month + VAT each | Landlord | Allows one active manager membership and assignment to selected properties; core Landlord plan remains required |
| Additional branch | £25/month + VAT each | Estate Agent | Adds one active branch beyond the included two |
| Additional staff seat | £9/month + VAT each | Estate Agent | Adds one active internal staff membership; portal users do not count |
| Landlord property pack | £10/month + VAT | Landlord | Adds 10 active properties |
| Agency property pack | £35/month + VAT | Estate Agent | Adds 100 active properties |
| Storage pack | £8/month + VAT | Both | Adds 25 GB; final storage economics should be checked before launch |

Add-ons should be Stripe subscription items, not unrelated subscriptions. This keeps one invoice and one renewal date per workspace.

## Included and excluded users

| User type | Counts as paid seat? | Notes |
|---|---:|---|
| Estate Agent Admin | Yes, included staff seat | Billing owner may be changed with audit |
| Branch Manager/Staff/Accountant | Yes | Consumes a staff seat while active |
| Agency Property Manager | Yes | Consumes a staff seat |
| Landlord Admin | Included | One admin in base Landlord plan |
| Landlord add-on Property Manager | Yes, add-on | One add-on per active manager seat |
| Tenant | No | Assigned portal only |
| Contractor | No | Assigned job/quote portal only |
| Owner or landlord contact | No | Linked view-only portal only |

## Trial recommendation

Offer a **14-day card-required trial** for self-serve sign-up:

- Stripe Checkout collects the payment method and creates a `trialing` subscription.
- Show the exact first charge date and send reminders seven days and two days before it.
- Allow cancellation before the first charge.
- Trials receive full base-plan features but cannot buy usage beyond reasonable anti-abuse limits until a payment method is valid.
- One trial per verified billing identity/workspace.

This reduces manual activation and tests payment intent. For early design partners, Super Admin may create a separate, audited promotional trial without card details. Trial conversion should be measured; if checkout abandonment is high, test a no-card product tour rather than guessing.

## Stripe object model

| Resisquare concept | Stripe object | Local record |
|---|---|---|
| Estate Agent/Landlord offer | Product | `plans` |
| Monthly/annual amount | recurring Price | `plan_prices` |
| Branch/staff/property/PM add-on | Product + recurring Price | `add_ons` / `plan_prices` |
| Paying workspace | Customer | `companies.stripe_customer_id` or billing profile |
| Plan and add-ons | Subscription + Subscription Items | `subscriptions` + `subscription_items` |
| Signup payment UI | Checkout Session in `subscription` mode | optional `checkout_attempts` |
| SaaS billing invoice | Invoice | local Stripe ID/status projection, not property invoice tables |
| Self-service billing | Customer Portal Session | ephemeral; do not persist portal URL |
| Asynchronous truth | Webhook Event | `stripe_webhook_events` |

Stripe's current Checkout subscription guide explicitly uses Products/Prices, a Checkout Session, webhook-driven provisioning and the Customer Portal: [Build a subscriptions integration with Checkout](https://docs.stripe.com/payments/checkout/build-subscriptions).

## Plan purchase flow

```text
Pricing page
  -> choose workspace type, billing interval and allowed add-ons
  -> create/verify identity
  -> create pending workspace + billing profile
  -> server validates published Price IDs and creates Checkout Session
  -> redirect to Stripe Checkout
  -> return to success or cancel page
  -> webhook verifies payment/subscription state
  -> activate trial/full entitlements
  -> guided workspace setup
```

### Detailed requirements

1. The browser sends only Resisquare plan/add-on codes. It never sets an arbitrary amount or Stripe Price ID.
2. The server resolves active, published Prices from its own catalogue.
3. Create/reuse one Stripe Customer per paying workspace.
4. Create Checkout Session with:
   - `mode=subscription`
   - correct recurring line items
   - workspace/checkout-attempt metadata
   - verified success and cancel URLs
   - trial configuration when eligible
   - promotion/tax settings only when deliberately configured
5. The success page displays the Checkout attempt but does **not** grant access on its own.
6. A verified webhook stores Stripe customer/subscription IDs and activates entitlements only when status is `trialing` or `active`.
7. Redirect the user into onboarding after activation. If the webhook is still processing, poll a local status endpoint and show a safe pending state.

## Webhook flow

Stripe states that subscription activity is asynchronous and should be managed with verified webhooks: [Using webhooks with subscriptions](https://docs.stripe.com/billing/subscriptions/webhooks).

```text
Stripe POST /stripe/webhooks
  -> read raw body
  -> verify Stripe-Signature with endpoint secret
  -> insert event ID once (idempotency)
  -> return 2xx quickly or queue processing
  -> lock local subscription/workspace
  -> retrieve Stripe object if required
  -> update local subscription/items/status/period
  -> recompute entitlements and capacity
  -> log result and notify billing owner when needed
```

### Minimum event handling

| Event | Local action |
|---|---|
| `checkout.session.completed` | Attach Customer and Subscription IDs to the pending workspace/attempt; do not infer long-term state from the event alone |
| `customer.subscription.created` | Upsert subscription, items, trial and billing periods; activate only for allowed statuses |
| `customer.subscription.updated` | Synchronise plan, add-ons, quantity, cancellation and status; recompute entitlements |
| `customer.subscription.deleted` | Mark cancelled/ended and apply retention/read-only policy |
| `invoice.paid` | Clear delinquency, store SaaS invoice reference and restore access where appropriate |
| `invoice.payment_failed` | Mark payment issue, notify billing owner and start grace-period policy |
| `customer.subscription.trial_will_end` | Notify billing owner of charge date/payment method requirement |
| `invoice.finalized` | Store invoice metadata if needed for the billing dashboard |
| `charge.dispute.created` | Alert Resisquare finance/support; do not automatically destroy customer data |

Webhook requirements:

- Store Stripe `event.id` with a unique index before applying side effects.
- Treat duplicate and out-of-order delivery as normal.
- Compare Stripe object timestamps/version state before overwriting newer local state.
- Verify signatures against the raw body.
- Never log secrets, full payment details or unnecessary personal data.
- Provide a Super Admin reconciliation screen and safe replay command.
- Use Stripe test clocks/CLI fixtures in automated tests.

## Subscription status and access logic

| Local status | Typical Stripe state | Product access |
|---|---|---|
| `pending_checkout` | no subscription yet | Onboarding/checkout only |
| `trialing` | `trialing` | Full base-plan access within trial limits |
| `active` | `active` | Full purchased entitlements |
| `past_due_grace` | `past_due` | Full access for 7 days, persistent billing banner, owner notifications |
| `past_due_restricted` | `past_due` after grace | Read-only business records; allow billing, export and support; block creates/updates and paid add-ons |
| `unpaid` | `unpaid` | Same restricted state; Resisquare review may suspend further |
| `paused` | `paused` where supported | Read-only unless Super Admin policy says otherwise |
| `cancel_at_period_end` | active/trialing with cancellation date | Normal access until paid period ends; show end date/reactivate action |
| `cancelled` | `canceled`/deleted | Read-only retention period; billing/history/export/support only |
| `suspended` | local admin/security state | No business access; status independent of payment |

Do not delete records because a payment fails. A proposed retention policy is 30 days of read-only access after subscription end followed by restricted archive retention according to contract/privacy requirements. Legal and commercial review must set the final period.

## Upgrades, downgrades and cancellation

### Upgrade

- Show old/new plan, quantities, effective time and estimated proration.
- Confirm before changing the Stripe subscription.
- Apply new entitlements after verified Stripe update/payment state.
- Quantity add-ons can be adjusted from a usage screen.

### Downgrade

- Default to period-end change.
- Show current usage versus new limits.
- Never silently delete excess properties, branches, staff or files.
- After effective date, existing over-limit records remain readable but new/reactivated records are blocked until within limit.

### Cancellation

- Offer cancel at period end by default.
- Show access end date and data/export policy.
- Allow reactivation before period end.
- Record optional cancellation reason without obstructing cancellation.
- Stripe's [Customer Portal](https://docs.stripe.com/customer-management) can manage billing information, payment methods, subscription changes/cancellation and SaaS invoices, subject to its supported subscription configurations.

## Billing dashboard requirements

### Customer billing owner

- Current plan and monthly/annual interval
- Subscription status, next renewal/charge date and cancellation date
- Included limits, add-on quantities and current usage
- Billing contact, address, VAT/tax ID status and payment method summary from Stripe
- SaaS invoice history with links/downloads from Stripe
- Upgrade/downgrade/add-on preview and confirmation
- Open Customer Portal action
- Failed-payment alert with “update payment method” action
- Trial countdown and first charge date

### Resisquare Super Admin

- Workspaces by trialing/active/past due/unpaid/cancelled/suspended
- MRR/ARR, new/expansion/contraction/churn MRR and plan mix
- Failed invoices and days delinquent
- Local-versus-Stripe mismatch queue
- Webhook failures, last event, retry/reconciliation state
- Price/product catalogue with live/test IDs and publication status
- Manual comp/trial/suspension actions with reason and audit
- No display or editing of secret API/webhook keys

## VAT and tax considerations

- Store tax behaviour on each Stripe Price and keep it consistent across upgrade paths.
- Capture billing country/address and business tax ID where appropriate.
- Decide with Resisquare's accountant whether displayed prices are VAT-exclusive and whether Stripe Tax is used.
- The UI must say “+ VAT” or “VAT included”; never leave it ambiguous.
- Property rent/contractor tax records and Resisquare SaaS VAT are separate accounting contexts.

## Existing CRM implementation note

The current repository has Stripe test keys and a one-off `payment` Checkout for `SysSaleInvoice`. It records payment from a signed success redirect and has no webhook. For SaaS billing:

- use the installed Stripe SDK consistently rather than hand-written cURL
- add separate subscription controllers/services/models
- add a CSRF-exempt but signature-verified webhook endpoint
- do not reuse property accounting invoice tables for Stripe subscription invoices
- do not activate an account from a success URL
- preserve the accounting invoice payment feature only after its own Session verification/webhook design is hardened

## Pricing validation plan

Before production launch:

1. Interview at least 10 landlords and 10 agency decision-makers across portfolio sizes.
2. Test £19/£149 against two alternate price points without misleading “discount” claims.
3. Validate which limits drive value: active properties, staff, branches, storage or accounting features.
4. Measure trial start, activation, paid conversion, support cost and add-on attachment.
5. Revisit prices after the first 20 paying workspaces; grandfather or communicate changes transparently.

