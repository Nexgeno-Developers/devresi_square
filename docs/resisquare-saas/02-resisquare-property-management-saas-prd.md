# Resisquare Property Management SaaS — product requirements document

**Version:** 1.0  
**Date:** 20 June 2026  
**Product owner:** Resisquare  
**Initial market:** England-first, GBP, with a data model that can later support Scotland, Wales and Northern Ireland.

## Product overview

Resisquare is a multi-tenant property-management SaaS for estate agencies and self-managing landlords. It gives each paying customer an isolated workspace for branches, staff, properties, contacts, tenancies, rent invoices, maintenance, contractors, documents and reports. Tenants, contractors and owners receive limited portals tied only to their assigned records.

Resisquare itself is the platform operator and Super Admin. An Estate Agent or Landlord is the billing customer. Owner and landlord contacts created inside an agency workspace are view-only unless they separately buy a Landlord plan, in which case they also receive their own landlord workspace.

The prototype should demonstrate the product, roles, plan purchase and key workflows. It should be realistic and clickable, but must not imply that simulated compliance checks or Stripe payments are legally or financially complete.

## Product principles

1. **Workspace isolation first:** every business record belongs to one paying workspace.
2. **One identity, multiple memberships:** a person can be an owner in one workspace, contractor in another and a paying landlord in their own workspace.
3. **Least privilege:** portals expose only self/assigned/shared records.
4. **Plan access is separate from role access:** a role answers “what may this member do?”; an entitlement answers “has this workspace paid for the feature or capacity?”.
5. **Evidence over checkboxes:** compliance records hold certificates, issue/expiry dates, reminders and audit history.
6. **England-first, jurisdiction-aware:** do not encode one UK-wide legal workflow when rules differ by nation.
7. **Clear financial language:** distinguish rent invoices and property accounting from Resisquare subscription invoices.

## Business goals

- Convert estate agencies and landlords into monthly or annual subscriptions.
- Reduce property administration through one workspace and assigned-role portals.
- Make maintenance response and contractor coordination traceable.
- Improve rent/invoice visibility for staff, landlords and tenants.
- Provide expandable revenue through property limits, branches, staff and Property Manager add-ons.
- Reuse the strongest parts of the existing CRM rather than rebuilding its domain knowledge.

## Success measures

The MVP should instrument these metrics:

| Funnel | Measure |
|---|---|
| Acquisition | Pricing-page visit to registration start |
| Checkout | Registration start to successful subscription |
| Activation | Workspace creates first branch/property within 24 hours |
| Value | First tenancy, invoice or maintenance request within 7 days |
| Engagement | Weekly active workspaces and active portal users |
| Operations | Median maintenance acknowledgement and resolution time |
| Finance | Rent invoices issued, payment records reconciled and overdue balances |
| SaaS | MRR, ARR, trial conversion, expansion MRR, failed-payment recovery and churn |

Initial target definitions should be set after 10–20 design-partner interviews; the prototype should not invent performance claims.

## Target users

| User | Primary need | Account relationship |
|---|---|---|
| Resisquare Super Admin | Operate customers, plans, billing and platform health | Platform-level user |
| Estate Agent Admin | Run an agency with branches, staff and managed properties | Paying Estate Agent workspace owner |
| Branch Manager | Run one or more assigned branches | Agency workspace member |
| Staff | Perform assigned operational tasks | Agency workspace member |
| Accountant / Invoice Staff | Manage property invoices, receipts and reports | Permission profile within agency workspace |
| Landlord subscriber | Manage an owned portfolio directly | Paying Landlord workspace owner |
| Landlord contact | See records shared by an agency | View-only portal membership; not a paying workspace owner |
| Property Manager | Manage assigned properties for an agency or add-on landlord | Scoped workspace member |
| Tenant | See tenancy/rent and raise maintenance | Limited assigned portal |
| Contractor | Quote and update assigned maintenance jobs | Limited assigned portal; may work across workspaces |
| Owner | View linked property information, documents and reports | Limited view-only portal |

## Workspace model

There are two sellable workspace types:

- **Estate Agent workspace:** company profile, branches, staff and portfolio operations.
- **Landlord workspace:** portfolio management without agency branches or branch-staff administration.

The Resisquare platform tenant is separate and cannot be reached through ordinary workspace queries.

An invited contact does not automatically become a paying customer. If a landlord contact purchases a Landlord plan, the system creates a new Landlord workspace and keeps their existing agency portal membership. Data is not silently copied between workspaces; sharing or migration requires an explicit workflow and audit record.

## Core modules and requirements

### 1. Marketing, pricing and onboarding

- Public home, features, role-specific benefits, pricing, FAQ, contact and login pages.
- Monthly/annual toggle and clear “excluding/including VAT” wording.
- Registration choice: Estate Agent or Landlord. Owner and contractor public applications may remain invitation/approval based, not paid plan choices.
- Email verification, secure password creation and acceptance of current terms/privacy notice.
- Stripe Checkout for selected plan and add-ons.
- Webhook-driven activation; the success page may show “confirming payment” until Stripe state arrives.
- Guided setup: workspace name, company/portfolio profile, first branch where applicable, first property and invite team.

### 2. Workspace and branch management

- Estate Agent: company profile, registration/VAT details, brand assets and services.
- Estate Agent: create, edit, archive and select a head office; enforce branch limit.
- Landlord: portfolio profile only; no agency branch module.
- Workspace switcher for users with more than one membership.
- Audit workspace ownership transfers and billing-owner changes.

### 3. Staff, roles and invitations

- Invite by email; never send a generated reusable password.
- Assign branch, role template and optional permission overrides.
- Estate Agent roles: Admin, Branch Manager, Staff, Property Manager and Accountant/Invoice Staff.
- Landlord can invite view-only users; a Property Manager seat requires the add-on.
- Suspend or remove a membership without deleting the shared user identity.
- Show invitation pending/accepted/expired status.

### 4. Contacts

- Landlords, owners, tenants, contractors and other contacts in one searchable directory.
- A contact can exist without login access.
- Invite portal access separately and show precisely what will be shared.
- Contact profile, communication preferences, linked properties/tenancies/jobs, notes and documents.
- Do not expose internal notes to portal users unless explicitly marked shared.

### 5. Properties

- UK address fields, postcode, property reference, type, bedrooms, bathrooms, tenure and status.
- Link branch, landlords, legal owners and ownership shares.
- Assign property manager and operational responsibility.
- Media, features, descriptions and brochure output.
- Compliance records and expiries.
- Property timeline, documents, notes, appointments, invoices, maintenance and statement.
- Archive instead of destructive deletion when financial or tenancy history exists.

### 6. Tenancies and tenants

- Link property, tenant members, main tenant and assigned property manager.
- Record commencement, status, rent, frequency, deposit scheme/reference and notice events.
- England implementation must support assured periodic tenancy behaviour and avoid assuming a fixed-term tenancy is always valid.
- Rent schedule and ledger derived from invoices/payments, not a manually edited total.
- Tenant invitation and welcome flow.
- Tenant portal shows only their active/historical tenancy records and shared files.

### 7. Rent invoices, payments and reporting

- Create one-off and recurring rent/charge invoices.
- Draft, issue, partial, paid, overdue and cancelled states.
- Record/manual-match payments and provide downloadable invoices/receipts.
- Customer, property and tenancy statements.
- AR ageing and operational portfolio reports in MVP; full GL is an implementation reuse candidate but not required for every prototype screen.
- Keep Resisquare subscription billing in a visually separate “Plan & billing” area.

### 8. Maintenance and contractor coordination

- Tenant or authorised staff raises a request with category, priority, description, availability and photos.
- Staff triages, assigns a Property Manager, requests quotes and records history.
- Contractors receive an assigned portal or expiring signed quote link.
- Contractor submits quote, availability and attachments.
- Staff selects the final contractor, creates/sends a work order and updates status.
- Contractor uploads progress/completion photos and invoice.
- Tenant sees safe status updates but not internal cost/quote comparisons.
- Full audit timeline from report to close.

### 9. Documents and communications

- Private uploads with type, owner workspace, linked entity, visibility and expiry.
- Shared document categories such as tenancy agreement, EPC, gas safety, EICR, deposit evidence, quote, work order and invoice.
- Conversation threads among permitted participants with email notifications.
- Communication history records sender, recipients, time, channel and delivery status.
- File access must use authorised or time-limited URLs.

### 10. Reports

- Occupancy/vacancy, rent due/paid/overdue, maintenance status/SLA and compliance expiry.
- Estate Agent branch and staff filters.
- Landlord portfolio summaries.
- Owner reports limited to linked assets and explicitly shared financial values.
- Export permission is separate from view permission.

### 11. Plan and billing

- Current plan, renewal date, included limits, current usage and add-ons.
- Upgrade/downgrade preview with proration explanation.
- Stripe Customer Portal for payment method, subscription and SaaS invoices.
- Failed-payment banner and access-state explanation.
- Cancellation at period end and reactivation before period end.

### 12. Resisquare Super Admin

- Platform KPIs: workspaces, trials, active/past-due/cancelled subscriptions, MRR and failed payments.
- Search/filter workspaces and users; view account health and usage.
- Suspend/reactivate workspace or membership with reason and audit log.
- Manage plan catalogue, prices, features, add-ons and limits. Published price changes require an explicit effective-date workflow.
- View Stripe identifiers and event state; do not expose secrets.
- Support impersonation only as a later audited feature with a visible banner and strict controls.
- Manage platform templates, support settings and legal-page versions.

## Dashboard requirements by role

| Dashboard | Required cards and actions |
|---|---|
| Super Admin | MRR, active/trial/past-due accounts, failed payments, new sign-ups, plan mix, usage alerts; manage customer/plan actions |
| Estate Agent Admin | Properties, occupancy, rent due/overdue, open maintenance, compliance expiring, branches/staff, plan usage; add property/invite staff |
| Branch Manager | Assigned-branch properties, tasks, tenancies, arrears, maintenance and compliance; assign staff |
| Staff | My tasks, appointments, assigned properties, maintenance queue and recent communications |
| Accountant | Invoice totals, overdue/partial balances, receipts to match and statement/report shortcuts |
| Landlord | Portfolio value indicators, occupancy, rent, maintenance, documents, compliance and plan limits; add property/tenant |
| Property Manager | Assigned properties, urgent maintenance, tenant messages, contractor quotes, invoices awaiting action and expiries |
| Tenant | Property/tenancy summary, next rent invoice, payment history, maintenance list, shared documents and messages |
| Contractor | Assigned/requested jobs, quotes due, confirmed work, status updates, upload quote/invoice/photos |
| Owner | Linked properties, occupancy summary, shared reports and documents; no management actions |

Every dashboard count must use the same tenant and assignment rules as its destination list.

## Key user journeys

### Estate Agent purchase and setup

1. Visitor selects Estate Agent and monthly/annual price.
2. Creates identity and verifies email.
3. Enters company name and billing details.
4. Stripe Checkout completes.
5. Webhook activates the workspace and plan limits.
6. User completes company profile, creates/edits head office, adds first property and invites staff.
7. Dashboard displays progress and real usage, not fabricated analytics.

### Landlord purchase and Property Manager add-on

1. Visitor selects Landlord plan and property capacity.
2. Completes verification and Stripe Checkout.
3. Creates a landlord portfolio workspace and first property.
4. Adds tenants/contractors and manages normally.
5. Selecting “Add Property Manager” shows the add-on price and permission scope.
6. After confirmed subscription update, landlord invites/assigns a manager to selected properties.

### Agency adds a landlord contact

1. Agency creates a landlord contact and links selected properties.
2. Agency optionally sends a portal invitation.
3. Landlord sees only shared/linked property information in that agency workspace.
4. Upgrade CTA explains that buying a Landlord plan creates a separate management workspace; it does not grant edit rights inside the agency.

### Tenant maintenance request

1. Tenant selects their assigned property/tenancy.
2. Adds issue category, description, access notes, availability and photos.
3. Submission creates an auditable maintenance request and notifies the assigned team.
4. Tenant sees acknowledgement, appointment and safe progress updates.
5. Internal quotes, commissions and private notes remain hidden.

### Contractor job

1. Contractor receives an assignment or quote request.
2. Opens only the relevant job, submits availability/quote and uploads files.
3. If selected, views work order and updates accepted/in progress/completed states.
4. Uploads completion photos and invoice.
5. Cannot browse other contractors, properties or jobs.

### Failed SaaS payment

1. Stripe webhook changes subscription status to past due.
2. Billing owner receives email and an in-app banner.
3. During a short grace period the workspace remains usable with repeated billing prompts.
4. After grace, mutation of premium/business records is restricted while billing, export and support remain reachable.
5. A successful webhook restores entitlements automatically.

## UK property-management context

The product should say **England** where England-specific rules are used. Scotland, Wales and Northern Ireland require separate rule packs and should not be represented as identical.

For England, the product should support evidence and reminders for safe premises, gas/electrical safety, EPCs, deposit protection and Right to Rent, reflecting the current [GOV.UK landlord responsibilities](https://www.gov.uk/renting-out-a-property/landlord-responsibilities). This is workflow support, not legal advice.

As of this PRD date, phase 1 of the Renters' Rights Act 2025 took effect on **1 May 2026**, including the new assured periodic tenancy regime, abolition of section 21, rent-increase process changes and other rules. The official roadmap says the PRS Database and Landlord Ombudsman begin from late 2026. Store the data needed for these future workflows, but label unreleased functions as planned until requirements are final. See the [official implementation roadmap](https://www.gov.uk/government/publications/renters-rights-act-2025-implementation-roadmap/implementing-the-renters-rights-act-2025-our-roadmap-for-reforming-the-private-rented-sector).

Prototype/data conventions:

- currency: GBP and `£`
- dates: `DD/MM/YYYY`
- timezone: `Europe/London`
- addresses: UK lines, town/city, county optional, postcode and nation/jurisdiction
- phone examples: UK formatting
- terminology: letting, landlord, tenancy, rent, deposit, contractor, branch and property manager
- privacy: role-specific access, consent/preferences, retention and subject-request readiness; implementation should follow current [ICO UK GDPR guidance](https://ico.org.uk/for-organisations/uk-gdpr-guidance-and-resources/)
- accessibility target: WCAG 2.2 AA for production

## MVP scope

### Must be in the clickable prototype

- Marketing and pricing pages
- Login, register, verify and forgot-password screens
- Estate Agent and Landlord plan selection/Checkout success/failure states
- Super Admin, Estate Agent, Landlord, Property Manager, Tenant, Contractor and Owner dashboards
- Company, branch, staff, contacts and role/permission screens
- Properties, tenancies/tenants, invoices, maintenance, contractors, documents, messages and reports
- Property Manager, branch, staff and property-capacity add-on states
- View-only contact versus paying landlord distinction
- Responsive navigation, tables/cards, filters, empty/loading/error/limit states
- Realistic UK sample data and role switcher for prototype demonstration

### Must be in the implementation MVP

- Mandatory tenant isolation and membership-scoped authorisation
- Email verification, secure invitations and account status enforcement
- Stripe subscription Checkout, verified webhooks and Customer Portal
- Plan limits and entitlement checks on the server
- Estate Agent and Landlord workspaces
- Branch/staff/property/contact CRUD with plan rules
- Property, tenancy, invoice/payment record, maintenance/work order, documents and essential reports
- Tenant, contractor and owner assigned portals
- Audit log, private files, notification queue and backup/restore procedures
- Automated tests for tenant isolation, roles, limits and billing state

### Explicitly out of MVP

- Native mobile apps
- Open marketplace for contractors
- Automated bank feeds or Open Banking
- Full client-money accounting certification
- AI-generated legal/compliance advice
- Scotland/Wales/Northern Ireland rule automation
- PRS Database submission before an official integration/requirements path exists
- Complex white-label domains, SSO and enterprise provisioning
- Public API except endpoints strictly required by the web application and Stripe

## Future scope

- Mobile apps and push notifications
- Xero/QuickBooks/Open Banking integrations
- Digital signatures and tenancy template automation
- Applicant, viewing and offer pipeline expansion
- Owner remittance/client-money features
- Configurable workflow automation and SLA rules
- White label and custom domains
- Jurisdiction packs for the rest of the UK
- PRS Database/Ombudsman support when official requirements are available
- Data warehouse, portfolio benchmarking and forecasting
- Audited support impersonation and enterprise SSO

## Non-functional requirements

| Area | Requirement |
|---|---|
| Security | Deny by default; server-side policy on every object/action; MFA for Super Admin and optional customer admins |
| Isolation | No tenant-owned query without resolved workspace scope; automated cross-tenant tests |
| Privacy | Data minimisation, private storage, configurable retention, audit/export/delete workflows |
| Availability | Production monitoring, queue supervision, backups and tested restore process |
| Performance | Typical list/dashboard response under 2 seconds at agreed data volumes; pagination everywhere |
| Billing | Idempotent webhooks, replay protection, reconciliation dashboard and no activation from browser redirect alone |
| Accessibility | WCAG 2.2 AA target, keyboard navigation, visible focus, labelled forms and sufficient contrast |
| Observability | Structured logs with request/workspace IDs, error tracking and alerts without leaking personal data |
| Data | Store money as decimal/minor units as appropriate, UTC timestamps, company timezone display and immutable audit events |

## Prototype acceptance criteria

1. A reviewer can switch among all requested roles and see materially different navigation/actions.
2. Estate Agent can create branches/staff; Landlord cannot see those agency features.
3. A landlord contact is visibly view-only, while a landlord subscriber can manage their own portfolio workspace.
4. Tenant, Contractor and Owner screens show assigned records only.
5. Pricing, checkout, success, past-due, upgrade and cancellation states are clickable.
6. The main property-to-tenancy-to-invoice and maintenance-to-contractor-to-work-order journeys are complete.
7. All sample currency, dates, addresses and terms are UK-appropriate.
8. Desktop, tablet and mobile layouts are coherent, with empty/error/loading and plan-limit states.
9. The prototype never asks for real card details and clearly labels Stripe screens as simulated.

