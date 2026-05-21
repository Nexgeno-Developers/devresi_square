# UK PropTech SaaS Due Diligence Audit

Prepared as a senior product, architecture, SaaS, compliance, and investment-readiness review of the current Laravel property management application.

This is a product and technical audit, not legal, tax, accounting, or financial advice. UK requirements change and must be validated with qualified advisers before launch.

## Executive Verdict

The current product is a broad internal agency ERP / property operations monolith with useful early modules, not an enterprise SaaS platform yet. It has the beginnings of a UK lettings/property management system and a newer double-entry accounting module, but it is not ready for multi-tenant commercial SaaS, regulated client accounting, enterprise agencies, or 20M GBP investment diligence.

| Dimension | Score | Verdict |
|---|---:|---|
| Overall product maturity | 4.0 / 10 | Wide module coverage, shallow workflow depth. |
| SaaS readiness | 2.0 / 10 | No proven tenant isolation, subscription, metering, API, or feature flag architecture. |
| UK compliance readiness | 3.0 / 10 | Compliance records exist, but statutory workflows and evidence packs are incomplete. |
| Scalability | 2.5 / 10 | Large Laravel monolith, server-rendered UI, weak background scheduling evidence, limited indexing strategy. |
| Enterprise readiness | 2.5 / 10 | Missing SLAs, audit governance, controls, integrations, portals, configurable workflows. |
| Investor attractiveness | 3.0 / 10 | Promising domain breadth but high rewrite, compliance, and accounting-risk discount. |

Most dangerous right now:

1. Multi-tenant isolation is not systemically enforced. Data leakage across agencies would be catastrophic.
2. Default user password behavior exists in the model (`123456`), which is not acceptable for SaaS.
3. Accounting is not yet a fully controlled client money ledger, despite GL foundations.
4. UK legal workflows are mostly records/reminders, not enforceable compliance processes.
5. Permissions are inconsistent between seeders and sidebar gates, indicating fragile access control.
6. The product has duplicate finance paths: legacy repair invoices and newer system accounting.
7. There is no clear API, integration, portal, billing, subscription, or tenant provisioning layer.

What would break first at scale:

- Query performance and search as property/user/ledger rows grow.
- Permissions and data scoping in multi-office/multi-company deployments.
- Finance reconciliation, rent allocation, landlord disbursement, and client money accounting.
- Admin productivity due to modal-heavy CRUD, route sprawl, and inconsistent workflows.
- Compliance evidence and legal deadline tracking under real UK agency operations.

## Evidence Base

Local repo evidence reviewed:

- `docs/application-flow.md`
- `routes/web.php`
- `routes/backend.php`
- `resources/views/backend/partials/aside2.blade.php`
- `app/Models/*`
- `app/Http/Controllers/Backend/*`
- `app/Http/Controllers/Backend/Accounting/*`
- `app/Services/Accounting/*`
- `app/Services/Notifications/NotificationService.php`
- `app/Console/Commands/*`
- `database/migrations/*`
- `database/seeders/RoleAndPermissionSeeder.php`
- `public/asset/backend/js/common-documents.js`
- `public/asset/backend/js/common-notes.js`

Public sources used for UK requirements and competitor benchmarking:

- Right to Rent guidance: https://www.gov.uk/check-tenant-right-to-rent-documents
- Tenancy deposit protection: https://www.gov.uk/tenancy-deposit-protection
- Deposit landlord guidance: https://www.gov.uk/deposit-protection-schemes-and-landlords
- Landlord safety responsibilities: https://www.gov.uk/private-renting/your-landlords-safety-responsibilities
- Electrical safety standards: https://www.gov.uk/government/publications/electrical-safety-standards-in-the-private-and-social-rented-sectors-guidance/electrical-safety-standards-in-the-private-and-social-rented-sectors-guidance
- EPC / MEES guidance: https://www.gov.uk/guidance/domestic-private-rented-property-minimum-energy-efficiency-standard-landlord-guidance
- Client Money Protection: https://www.gov.uk/client-money-protection-scheme-property-agents
- AML estate agency supervision: https://www.gov.uk/guidance/registration-guide-for-estate-agency-businesses
- Redress scheme requirement: https://www.gov.uk/redress-scheme-estate-agencies
- Making Tax Digital for VAT: https://www.gov.uk/guidance/find-software-thats-compatible-with-making-tax-digital-for-vat
- VAT Notice 700/22: https://www.gov.uk/government/publications/vat-notice-70022-making-tax-digital-for-vat/vat-notice-70022-making-tax-digital-for-vat
- Renters' Rights Act tenant overview: https://www.gov.uk/guidance/renters-rights-act-overview-for-tenants
- Renters' Rights Act landlord overview: https://www.gov.uk/guidance/renters-rights-act-an-overview-for-landlords
- Reapit platform: https://www.reapit.com/platform/
- Reapit UK site: https://www.reapit.com/
- PropCo lettings/client accounting: https://propco.co.uk/platform/lettings/
- Bric features: https://bric.co.uk/features/
- LettingGuru: https://lettingguru.co.uk/
- Rentol: https://www.rentol.net/
- Latch: https://www.uselatch.co.uk/for/letting-agents
- ManageLet: https://www.managelet.co.uk/
- RentAdmin: https://www.rentadmin.co.uk/

## Section 1 - Product Understanding

### Inferred Architecture

| Area | Current Inference | Due Diligence Assessment |
|---|---|---|
| Application style | Laravel 11 monolith with Blade views and jQuery/Bootstrap modals. | Fast for MVP/internal ERP; weak for composable SaaS and rich role-specific portals. |
| Routing | Large `routes/backend.php`, public `routes/web.php`. | Route sprawl; module boundaries are not enforced. |
| UI | Backend layout with sidebar `aside2`, server-rendered pages, AJAX modal fragments. | Admin-heavy, not portal-first. Operational users will hit UX friction at scale. |
| Data model | Single shared database with company, branch, user, property, tenancy, repairs, events, accounting tables. | Looks single-instance multi-company, not hardened tenant isolation. |
| Accounting | Legacy invoice tables plus newer `sys_*` accounting and GL tables. | Duplicate finance paths create reconciliation and reporting risk. |
| Permissions | Spatie permissions plus policies plus manual role checks. | Inconsistent and brittle. Needs RBAC/ABAC redesign. |
| Background processing | Commands for recurring events, reminders, invoices, penalties, notifications. | Good start, but scheduling/orchestration evidence is weak. |
| Integrations | Stripe present; no proven Open Banking, Xero, QuickBooks, DPS/TDS/MyDeposits, portal feeds, e-sign, SMS provider abstraction. | Major SaaS and UK proptech gap. |
| API | No visible external API architecture. | Not platform-ready. |

### Probable Core Entities

| Entity Cluster | Current Evidence | Gaps |
|---|---|---|
| Organisations | `Company`, `Branch`, `Staff`, `Designation`, `BusinessSetting`. | No explicit tenant/workspace model, subscription, plan, billing, feature flags, environment isolation. |
| People | `User`, `UserDetail`, roles, categories, bank details. | User is overloaded as tenant, owner, applicant, contractor, staff, customer. Needs contact-party model. |
| Property | `Property`, local authority, nearby lookup tables, property responsibilities. | No unit/room hierarchy strong enough for HMO and multi-unit blocks. |
| Ownership | `OwnerGroup`, `OwnerGroupUser`, owner group contacts. | Needs ownership percentages, tax treatment, payment splits, mandate records. |
| Tenancy | `Tenancy`, `TenantMember`, tenancy type/sub-status. | No robust tenancy state machine, deposit lifecycle, notices, arrears plans, renewals/periodic conversion logic. |
| Repairs | `RepairIssue`, categories, photos, assignments, histories, work orders. | Good skeleton, but lacks quote/approval/SLA/vendor portal automation. |
| Compliance | `ComplianceType`, `ComplianceRecord`, `ComplianceDetail`. | Too generic unless rules engine, certificate evidence, escalation, and statutory workflows exist. |
| Accounting | `SysSaleInvoice`, `SysReceipt`, `SysPayment`, GL accounts/journals/lines/balances. | Needs client money subledgers, landlord disbursement, bank feeds, VAT/MTD, controls. |
| Communications | `EmailTemplate`, `NotificationLog`, SMS templates table. | Needs unified inbox, conversations, consent, channel delivery tracking, templates by tenant/brand/branch. |
| Documents | `Document`, `DocumentType`, `Upload`. | Needs retention, versioning, signatures, access control, legal packs. |

### Probable Workflows

| Workflow | Current Likely State | Missing Mature Workflow |
|---|---|---|
| Property onboarding | Step and quick-step property forms. | Valuation, instruction, AML/KYC, marketing approval, portal listing, compliance gate before advertising. |
| Contact onboarding | User create/edit with role/category and profile details. | Dedicated applicant, landlord, tenant, contractor journeys with identity, consent, referencing, risk scoring. |
| Lettings | Offers and tenancies exist. | Applicant matching, viewing, offer negotiation, referencing, holding deposit, tenancy agreement, deposit registration, move-in pack. |
| Sales | Property sales status fields exist. | Enquiry, valuation, instruction, offer, memorandum of sale, sales progression, chain, AML/KYC, conveyancer workflow. |
| Tenancy management | Tenancy CRUD and rent ledger view. | State machine from applicant to active tenancy to periodic, arrears, variation, notice, checkout, deposit return. |
| Maintenance | Raise issue, assignments, work order, invoice. | Tenant portal, triage, quotes, landlord approval, contractor dispatch, SLA, completion evidence, cost recharge. |
| Accounting | Sale/purchase invoices, receipts, payments, GL. | Rent schedules, allocation, landlord statements, disbursements, client account reconciliation, bank feeds. |
| Compliance | Generic records and forms for EPC/Gas/EICR-style data. | Statutory compliance packs, rule-driven deadlines, legal blocker gates, evidence bundles. |

### Product Classification

| Product Type | Fit | Reason |
|---|---:|---|
| Internal ERP | High | Broad admin operations, manual workflows, monolithic backend. |
| CRM | Medium | Contacts, owners, users, offers, notes exist but pipeline automation is weak. |
| Property PMS | Medium | Property, tenancy, maintenance, docs, compliance exist but lack end-to-end maturity. |
| Accounting software | Medium-low | GL exists, but not yet robust client accounting or tax-grade product. |
| Agency operations tool | High | Best current description. |
| Marketplace | Low | No public supply/demand marketplace, applicant portal, tenant acquisition engine, or portal distribution. |
| Multi-tenant SaaS | Low | Companies/branches exist, but tenant isolation and SaaS commercial layer are missing. |
| Legacy monolith | High | Route/controller/view structure is monolithic and CRUD-heavy. |

## Section 2 - Core Gap Analysis

Severity scale: Critical blocks SaaS/enterprise adoption, High creates major operational/regulatory risk, Medium creates scale or UX debt, Low is improvement.

| Module | Current Weaknesses | Missing Enterprise Capabilities | SaaS Maturity | Scale Concern | Monetization Potential | Score |
|---|---|---|---|---|---|---:|
| 1. Property Management | Property model is broad and flat; `company_id` is not clearly first-class on property; property search uses app queries, not search infra. | Property lifecycle state machine, unit/room/block hierarchy, valuation/instruction workflow, portal syndication, key management, inspections. | Low-mid | Flat records and modal tabs degrade with large portfolios. | Core per-unit pricing and premium property intelligence. | 4 |
| 2. Sales Pipeline | Sales fields exist, but no buyer/vendor pipeline depth. | Valuations, viewings, offers, sales progression, chains, memorandum of sale, conveyancer tasks, AML. | Low | CRM data will fragment into notes. | High for hybrid agencies. | 2 |
| 3. Lettings Pipeline | Offers and tenancies exist but no strong applicant pipeline. | Applicant matching, viewings, referencing, holding deposit, offer negotiation, tenancy pack. | Low-mid | Manual chasing and duplicate data. | Very high. | 3 |
| 4. Tenancy Lifecycle | CRUD plus rent ledger; no clear state machine. | Periodic tenancy conversion, Renters' Rights logic, notice workflows, arrears plans, check-in/out, deposit return. | Low-mid | Legal deadlines will be missed manually. | High retention feature. | 4 |
| 5. Landlord Management | Owner groups exist but finance and ownership semantics are immature. | Landlord portal, ownership splits, mandates, NRL tax, statements, disbursements, preferences. | Low-mid | Co-owner allocations will become fragile. | High through landlord portal and statement automation. | 4 |
| 6. Tenant Management | Tenant is an overloaded user role; some tabs and statements exist. | Tenant portal, onboarding, Right to Rent, referencing, rent schedules, arrears, communications. | Low-mid | User table becomes a dumping ground. | High through tenant self-service and payments. | 4 |
| 7. Contractor Management | Contractor role and repair assignments exist. | Contractor portal, insurance/accreditation, quote workflow, job acceptance, SLA, payment batches. | Low | Email/manual dispatch bottleneck. | Medium-high through contractor marketplace/payments. | 3 |
| 8. Maintenance / Work Orders | Repair issue, work order, history, invoice flow exist. | Triage, quote comparison, landlord approval, emergency escalation, tenant updates, quality control. | Mid | Manual status management. | High as operational differentiator. | 5 |
| 9. Accounting | Double-entry foundations exist but incomplete client accounting. | Segregated client/office ledgers, rent runs, landlord pay runs, bank feeds, controls, reversals, period close. | Mid | Incorrect balances at scale if journals/allocation are weak. | Very high, sticky enterprise feature. | 5 |
| 10. Client Money Handling | Bank accounts, receipts, payments, reconciliation exist. | CMP compliance evidence, client account segregation, trust/client ledger, suspense, bank feed matching, audit packs. | Low-mid | Regulatory and reputational risk. | Very high for UK agencies. | 3 |
| 11. Invoice Engine | Legacy and new invoice systems coexist. | Unified invoice domain, numbering by branch/entity, VAT rules, recurring schedules, approvals, audit locks. | Mid | Duplicate engines create reporting mismatch. | Medium-high. | 5 |
| 12. Receipt Engine | Receipts and credits exist. | Allocation rules, unapplied cash, payment plans, tenant-ledger allocation, bank feed auto-match. | Mid | Cash allocation becomes labor-intensive. | High through automation. | 5 |
| 13. Credit / Debit Notes | Credit/debit note models and routes exist. | Approval workflow, linked refunds, ledger reversal rules, tax treatment, audit evidence. | Low-mid | Manual adjustments can corrupt ledger. | Medium. | 4 |
| 14. Document Management | Polymorphic docs and AIZ uploader exist. | Versioning, e-sign, legal pack templates, access control, retention policies, full-text search, virus scan. | Low-mid | File sprawl and permission leakage. | High with e-sign/compliance packs. | 4 |
| 15. Compliance | Generic records and some forms exist. | UK-specific rule engine, statutory blockers, expiry escalation, document serving, court-ready evidence bundles. | Low-mid | Missed certificate deadlines and legal risk. | High premium feature. | 4 |
| 16. Reporting & Analytics | GL reports and statements exist; dashboards basic. | BI layer, portfolio KPIs, branch/team performance, arrears ageing, compliance risk, sales/lettings funnels. | Low-mid | SQL reports will not scale as product grows. | High for enterprise tiers. | 4 |
| 17. Notifications | Template/log/job foundations exist. | Multi-channel orchestration, preferences, consent, delivery webhooks, retries, campaign workflows. | Mid | Queue and retries need operational hardening. | Medium. | 5 |
| 18. Communication System | Email templates and notes; no unified inbox. | Email/SMS/WhatsApp/portal inbox, conversation threading, AI drafting, call logs, SLA. | Low | Context split across notes/emails. | High. | 2 |
| 19. Audit Logs | OwenIt auditing and GL audit logs exist. | Tenant-wide immutable audit trail, legal evidence bundles, admin action audit, export, tamper resistance. | Low-mid | Investor/compliance review will challenge evidential reliability. | Medium. | 4 |
| 20. User Roles & Permissions | Spatie plus policies plus manual checks; inconsistent names. | Hierarchical roles, office/team/property scoping, ABAC, permission matrix, tests. | Low | Data leakage and privilege escalation risk. | Low direct, high trust impact. | 3 |
| 21. Branch Management | Branch model exists; route/menu exists. | Branch-level ledgers, numbering, permissions, reporting, branding, bank accounts. | Low-mid | Branch scoping not uniformly enforced. | Medium. | 4 |
| 22. Multi-office Operations | Branches and responsibilities exist. | Regional hierarchy, shared contacts, transfer workflows, inter-branch reporting, office-specific configs. | Low | Manual filters cannot support national agencies. | High enterprise tier. | 3 |
| 23. Multi-company Support | Company model and user/company_id exist. | Workspace tenant model, hard isolation, per-company settings, billing, data export, backups. | Low | Current app likely leaks data without global scopes. | Core SaaS monetization. | 2 |
| 24. API Architecture | No visible external API strategy. | REST/GraphQL, OAuth, webhooks, API keys, rate limits, partner marketplace. | Very low | Integrations require direct DB or ad hoc routes. | Very high platform upside. | 1 |
| 25. Integrations | Stripe only is visible; no core UK integrations. | Open Banking, DPS/TDS/MyDeposits, Rightmove/Zoopla/OTM, Xero, QuickBooks, e-sign, referencing, SMS. | Very low | Manual rekeying and broken workflows. | Very high. | 2 |
| 26. Mobile Readiness | Server-rendered backend, no mobile app architecture. | Responsive portals, PWA/mobile apps for tenant/landlord/contractor/inspector, offline inspection. | Low | Field teams and tenants avoid the platform. | High. | 2 |
| 27. Automation Engine | Commands exist for reminders/invoices/events. | Workflow builder, triggers/actions, approvals, SLAs, escalation, feature-scoped automations. | Low-mid | One-off commands multiply into unmaintainable jobs. | Very high. | 4 |
| 28. AI Readiness | No AI platform layer; document/notes data exists. | Structured context store, permission-aware AI, extraction pipeline, AI audit log, human-in-the-loop. | Low | AI on messy data becomes unsafe. | Very high if built responsibly. | 2 |
| 29. Search & Filtering | Direct DB queries, AJAX lists. | Meilisearch/Elasticsearch/OpenSearch, saved filters, global search, fuzzy address/contact search. | Low | DB LIKE queries degrade. | Medium. | 3 |
| 30. Dashboard UX | Basic dashboard route and sidebar. | Role-specific dashboards, exception queues, KPI cards, workload inbox, portfolio risk map. | Low | Users need to click through many modules. | High for retention. | 3 |

## Section 3 - UK Property Industry Requirement Gaps

| Requirement | Why It Matters | Expected Implementation | Risk If Missing | Current Gap Severity |
|---|---|---|---|---|
| Right to Rent | Agents/landlords in England must check adult occupiers before tenancy start; new 2026 guidance highlights changing private renting rules. | Applicant/tenant check workflow, document capture, expiry/follow-up checks, non-discrimination evidence, immutable audit. | Civil penalties, discrimination risk, unlawful letting. | Critical |
| AML / KYC | Estate agency work must be registered/supervised for AML where applicable; sales and certain lettings create KYC obligations. | Identity verification, beneficial owner checks, risk assessment, PEP/sanctions screening, source of funds/wealth, SAR workflow. | Criminal/regulatory exposure, sales agency unusable for enterprise. | Critical |
| CMP | English letting/property management agents holding client money must join an approved scheme and display/provide certificate. | Agency CMP certificate storage, expiry alerts, website/branch display evidence, client money bank account mapping. | Fines, trust failure, inability to sell to agencies. | Critical |
| Deposit protection | Tenancy deposits must be protected in approved schemes within statutory windows; prescribed information must be served. | Deposit lifecycle, scheme integration, deadline timer, prescribed information pack, dispute/return workflow. | Penalties, invalid possession route, tenant claims. | Critical |
| DPS / TDS / MyDeposits | Real agencies need direct scheme operation, not manual reminders. | API/CSV integration, registration status, certificate storage, prescribed info, return/deduction. | Manual duplication and missed deadlines. | High |
| EPC tracking | Landlords must provide EPC and MEES applies; F/G restrictions and exemptions matter. | EPC register lookup/import, rating, expiry, MEES blocker, exemption register fields. | Illegal letting, fines, invalid marketing decisions. | High |
| Gas Safety | Annual check and copy provision are mandatory where gas applies. | Certificate dates, engineer/provider, served-to-tenant proof, renewal work order. | Safety, enforcement, possession complications. | High |
| EICR | Electrical installations generally need inspection at least every 5 years and tenant/local authority copy workflows. | EICR certificate, result codes, remedial action tracking, copy served proof. | Local authority enforcement, unsafe property. | High |
| ICO / GDPR | Platform processes tenant, landlord, applicant, finance, and document data. | Privacy notices, consent/legal basis, DSAR, retention, deletion/anonymisation, breach log, processor agreements. | Fines, enterprise blocker, reputational damage. | Critical |
| Renters' Rights Act readiness | From 1 May 2026, ASTs changed to assured periodic tenancies; Section 21 ended; new notices/rent/pet/anti-discrimination workflows matter. | Tenancy model update, information sheet service, Section 8 grounds workflow, rent increase validator, pet request workflow. | Incorrect tenancy docs, illegal notices, fines, disputes. | Critical |
| HMRC MTD | VAT-registered businesses must keep digital records and use compatible software/API/bridging for VAT returns. | Digital VAT records, VAT return summary, digital links, HMRC API or export to compatible software. | Finance product cannot claim MTD-ready. | High |
| Client accounting segregation | Client funds must be separated from office money; agency finance depends on exact ledger controls. | Client/office bank accounts, landlord/tenant subledgers, suspense, disbursements, reconciliation, lock periods. | Misappropriation risk, failed audit, enterprise rejection. | Critical |
| ARLA / Propertymark practices | Enterprise agencies expect professional controls and reports aligned to industry practice. | Client account reports, rent runs, audit pack, fee schedules, arrears, complaints, CMP proof. | Procurement failure for serious agencies. | High |
| Landlord statements | Core agency requirement. | Owner-group aware statements, deductions, fees, VAT, reserve/float, disbursement, PDF/email/portal. | Manual finance back office. | High |
| Tenant statements | Needed for arrears, disputes, move-out. | Rent schedule, charges, receipts, allocations, adjustments, export. | Disputes and manual support. | High |
| Contractor payment workflows | Maintenance needs contractor invoices, approvals, and payment runs. | Supplier ledger, quote approval, invoice matching, batch payments, CIS/VAT support if needed. | Cost leakage and angry contractors. | High |
| Holding deposit workflows | Lettings onboarding needs holding deposit rules, deductions, conversion to tenancy deposit/rent. | Payment receipt, deadline tracking, applicant withdrawal/landlord rejection handling, conversion posting. | Tenant Fees Act risk and finance confusion. | High |
| Section 21 / Section 8 | Section 21 is no longer viable under 2026 reforms; Section 8 grounds need evidence. | Disable old Section 21 workflows, Section 8 decision tree, notices, evidence packs, arrears plan. | Illegal possession process. | Critical |
| Prescribed information | Deposit compliance requires prescribed information service. | Template generation, sign/serve tracking, versioned evidence. | Deposit penalty and possession issues. | High |
| HMO management | UK portfolios often include HMOs with room-level tenancy/licensing/compliance. | Property-unit-room hierarchy, HMO licence, room occupancy, shared cost allocation, inspections. | Cannot serve HMO landlords/agents properly. | High |
| Property inspections | Lettings operations require move-in, periodic, checkout, evidence. | Mobile/offline inspections, room templates, photos, signatures, comparison reports. | Deposit disputes and weak compliance. | High |
| Inventory management | Required for move-in/out evidence and deposit disputes. | Inventory templates, condition schedule, photo/video, signature, checkout comparison. | Deposit disputes and manual third-party dependency. | High |
| Check-in / check-out | End-to-end tenancy lifecycle depends on these. | Move-in pack, keys, meter reads, inventory signoff, checkout inspection, deposit return. | Operational gaps and support burden. | High |
| Rent arrears automation | Cashflow and legal possession evidence depend on arrears process. | Arrears ageing, payment plans, automated reminders, escalation, landlord updates, evidence log. | Lost rent, late landlord payment, weak Section 8 evidence. | Critical |

## Section 4 - SaaS Transformation Analysis

### SaaS Readiness Findings

| Area | Finding | Severity | Required Fix |
|---|---|---|---|
| Tenant isolation | `company_id` exists on users and some accounting rows, but property and many modules do not show systematic isolation. | Critical | Introduce `account/workspace_id` as mandatory tenant key and enforce through global scopes, policies, DB constraints, tests. |
| Data partitioning | Shared database with inconsistent scoping. | Critical | Start with shared DB + tenant_id everywhere; consider schema-per-tenant only for enterprise regulated tenants later. |
| Permissions | Spatie plus policies plus manual checks plus inconsistent permission naming. | Critical | Central RBAC/ABAC model with scope: company, branch, team, property, ledger. |
| Billing | No subscription/plan/metering model visible. | High | Add plans, subscriptions, invoices for SaaS, metered units, entitlements, trials, cancellation/export. |
| Feature flags | No flags visible. | High | Add tenant-level feature flags and rollout controls. |
| White label | Website settings exist but not tenant-branded SaaS portals. | Medium-high | Tenant branding, custom domains, email sender domains, portal themes. |
| Environment separation | No evidence of SaaS deployment patterns. | High | Separate dev/stage/prod, secrets manager, tenant-safe seed/test data. |
| CI/CD | No pipeline evidence reviewed. | High | Automated tests, static analysis, migrations, smoke tests, deployment gates. |
| Queue/job processing | Commands exist; schedule evidence weak. | High | Central scheduler, job idempotency, retries, dead letter queue, observability. |
| Event architecture | One-off command/service calls, no domain event bus. | Medium-high | Domain events for tenancy, invoice, payment, repair, compliance. |
| API | No partner/public API. | High | Versioned API, OAuth, webhooks, rate limits, audit logs. |
| File storage | Uploads exist, but tenant isolation and access model unclear. | High | S3-compatible object storage, per-tenant paths, signed URLs, virus scanning, retention. |

### Target SaaS Architecture

| Layer | Recommended Direction |
|---|---|
| Backend | Modular Laravel monolith first, not premature microservices. Create bounded modules: Identity, Tenancy, Property, Lettings, Sales, Maintenance, Compliance, Client Accounting, Documents, Notifications, Integrations, SaaS Billing. |
| Frontend | Keep Blade for admin only short term; introduce API-backed React/Vue/Inertia or separate SPA for role portals and complex workflows. Mobile-first tenant/landlord/contractor portals. |
| Database | Shared Postgres/MySQL with mandatory `tenant_id` on every tenant-owned row; composite indexes on tenant + status/date/search keys; row-level testing. |
| Search | Meilisearch/OpenSearch for global search, properties, contacts, documents, invoices, repairs. |
| Cache | Redis for sessions, cache, queues, locks, rate limits, idempotency keys. |
| Files | S3-compatible storage with tenant prefixes, signed URLs, malware scanning, retention classification. |
| Queue | Redis/SQS queues with Horizon or equivalent, idempotent jobs, scheduled jobs, retry policy, dead-letter reporting. |
| Observability | Central logs, metrics, traces, job dashboards, audit event stream. |
| Integrations | Integration service layer with OAuth credential vault, webhook receiver, retry/outbox pattern. |
| Security | MFA, SSO/SAML for enterprise, password policy, device/session management, tenant audit log, encryption at rest. |
| Deployment | Containerized app, managed database, managed Redis, object storage, CDN/WAF, CI/CD, zero-downtime deploys. |

### Recommended Database Structure

Minimum SaaS tenant backbone:

- `accounts` or `workspaces`: the SaaS tenant.
- `subscriptions`, `plans`, `features`, `entitlements`, `usage_events`.
- `branches`: belongs to account.
- `users`: belongs to account, optional branch; separate from `contacts`.
- `contacts`: people/organisations that may be tenant, landlord, applicant, contractor, vendor, buyer, solicitor.
- `contact_roles`: many-to-many role assignments with context.
- `properties`: belongs to account and branch.
- `property_units` and `rooms`: required for HMOs and blocks.
- `tenancies`: belongs to account, property/unit/room.
- `ledgers`: account-level ledger partition, with client/office classification.
- Every business row: mandatory `account_id`, plus optional `branch_id`.

## Section 5 - Accounting and Finance Audit

### Accounting Current State

Positive signs:

- GL accounts, journals, journal lines, balances, period closes, audit logs, bank reconciliations, fixed assets exist.
- Posting service implements sale/purchase invoice, receipt, payment, apply-credit, undo-credit flows.
- Statements and reports exist for trial balance, P&L, balance sheet, AR/AP ageing.
- Sale invoices support recurring, penalties, receipts, payments, Stripe, PDF, and credit application.

Major risks:

- Legacy invoice tables and newer `sys_*` accounting can disagree.
- No visible mandatory tenant/client ledger isolation.
- No complete rent schedule to receipt to landlord disbursement chain.
- Bank reconciliation exists but bank feed ingestion/open banking is not visible.
- Client money, office money, deposits, landlord floats, contractor payables, and agency fees need explicit subledger design.
- VAT/MTD appears incomplete.

### Finance Gap Matrix

| Concept | Current Evidence | Enterprise Expectation | Risk |
|---|---|---|---|
| Double-entry bookkeeping | GL tables and posting service exist. | Every financial event posts balanced, immutable, reversible journals with period locks. | Partial implementation can create false confidence. |
| Ledger isolation | Company/user IDs on some GL lines. | Tenant/account + branch + client/office ledger isolation enforced everywhere. | Cross-agency or cross-client money contamination. |
| Client vs office accounts | Sys bank accounts and GL account mappings exist. | Explicit bank account type, client account controls, office fee transfer, CMP reports. | Regulatory failure. |
| Reconciliation | Bank reconciliation tables/routes exist. | Open Banking feed, import, matching, exceptions, approvals, audit trail. | Manual finance overhead and errors. |
| Rent apportionment | Not clearly visible. | Rent schedule per tenancy, daily apportionment for move-in/out, multi-tenant allocation. | Incorrect statements and landlord payments. |
| Partial payments | Receipts/payment balances exist. | Allocation against rent periods/charges/invoices with priority rules. | Arrears reports unreliable. |
| Journal entries | GL journal CRUD exists. | Approval, attachment, reversal, period lock, audit, role separation. | Unauthorized or untraceable adjustments. |
| VAT handling | Tax models exist. | VAT rates, tax point, partial exemption if needed, VAT returns, MTD digital links. | Bad tax reports. |
| Multi-tax handling | SysTax and tax rates exist. | Rate history, jurisdiction, inclusive/exclusive, line-level treatment. | Invoice/tax inaccuracies. |
| Payment allocation | Apply credit exists. | Automatic bank feed allocation, unapplied/suspense, split allocations, write-offs. | Cash not trusted. |
| Escrow/client funds | Advance receipts exist. | Client money ledger, deposit escrow vs rent funds, scheme funds separation. | Serious compliance exposure. |
| Refund workflows | Refund models exist. | Approval, bank execution, reversal, deposit refund, overpayment refund. | Manual leakage and disputes. |
| Landlord disbursement | Not evident as a full engine. | Rent run, fee deduction, floats, owner splits, NRL tax, payment batch, statements. | Cannot compete with PropCo/Reapit. |
| Contractor payment batching | Not mature. | Approved invoice queue, batch payments, remittance advice, supplier ledger. | Manual AP workload. |
| Bank feeds | Not visible. | Open Banking/BACS import, statement matching, rule engine. | Reconciliation bottleneck. |
| Xero/QuickBooks | Not visible. | Sync chart, invoices, bills, payments, contacts, tracking categories. | Agencies using accountants reject platform. |
| Audit-ready reporting | Some reports exist. | Exportable period reports, client ledgers, audit trail, locked periods, exception reports. | Fails enterprise finance diligence. |

Priority finance recommendations:

1. Collapse legacy and new invoice domains into one controlled finance domain.
2. Define ledger architecture before adding more accounting features.
3. Make client money segregation a first-class invariant, not a report filter.
4. Build rent schedule, allocation, arrears, landlord disbursement, and reconciliation as one flow.
5. Add Open Banking and Xero/QuickBooks as strategic integrations.

## Section 6 - AI and Automation Opportunities

| Opportunity | Business Value | Complexity | Priority |
|---|---|---:|---:|
| Rent arrears prediction | Predict default risk and reduce arrears before escalation. | Medium | P1 after clean rent ledger. |
| Maintenance triage | Classify urgency, category, likely contractor, and safety risk from tenant reports/photos. | Medium | P1 |
| Tenant communication AI | Draft replies with property/tenancy context; reduce inbox workload. | Medium-high | P2 with approval workflow. |
| Document extraction | Extract EPC/Gas/EICR/deposit/invoice fields from uploads. | Medium | P1 |
| Invoice OCR | Capture contractor invoices and map to work orders/ledger. | Medium | P1 |
| Smart categorization | Auto-classify expenses, repairs, notes, compliance docs. | Low-medium | P1 |
| AI assistant | Query portfolio, arrears, compliance, repairs, statements. | High | P2 after data permissions. |
| Voice note processing | Convert property manager inspection/repair voice notes into structured tasks. | Medium | P3 |
| Compliance risk alerts | Surface expiring certificates, blocked lettings, missing evidence. | Medium | P1 |
| Portfolio health scoring | Aggregate arrears, compliance, maintenance, voids, yield. | Medium | P2 |
| Predictive maintenance | Use repair history to forecast boiler/roof/electrical risk. | High | P4 |
| Smart reminders | Dynamic reminders based on risk, role, and deadlines. | Low-medium | P1 |
| Automated workflows | Trigger tasks/actions from domain events. | Medium-high | P1 foundational |
| Conversational reporting | Natural language over BI/reporting layer. | High | P3 |

AI guardrails:

- AI must be tenant-aware and permission-aware.
- Every AI action must be logged with reason, data sources, and human approval status.
- AI must draft by default; autonomous actions need explicit workflow permissions.
- Do not train on tenant data across agencies without contractual basis and privacy design.

## Section 7 - Competitor Gap Analysis

| Competitor | Publicly Evident Strengths | What They Likely Have That This App Lacks | Architecture/UX Advantage | How To Beat Them |
|---|---|---|---|---|
| Reapit / Alto category | End-to-end sales CRM, lettings CRM, property management, client accounts, bookings, mobile, integrations, data warehouse, workflows, AppMarket. | Mature sales/lettings pipelines, client accounts, integrations marketplace, enterprise reporting, SSO/support processes. | Enterprise platform, strong integrations, operational best practices. | Win with faster UK compliance automation, better AI, better pricing for small/mid agencies, modern UX. |
| PropCo | Lettings, management, client accounting, maintenance, site visits, ARLA/RICS/NFoPP-aligned accounting, bank imports, BACS, TDS uploads, BI, workflows. | Mature client accounting, rent chasing, landlord payments, workflows, mail merge, multi-branch scale. | Deep UK agency operations and finance maturity. | Beat with cloud-native UX, Open Banking, AI ops, easier onboarding, API-first platform. |
| LettingGuru | All-in-one letting agency software, AML, Right to Rent, deposit protection, compliance, Xero, NRL tax, multi-branch, AI assistant, mobile portals. | Stronger modern positioning, AI, portals, onboarding, Renters' Rights readiness. | Newer AI/portal narrative and clear SaaS packaging. | Beat with credible accounting controls, open integrations, transparent auditability, lower implementation friction. |
| Bric | Branches, compliance/safety, Open Banking, landlord ledgers, bulk landlord payments, contractor payments, deposit registration, portals, AI property data, portal integrations. | Open Banking, deposit integrations, bulk payments, marketing portal feeds, key log, AI listings. | Modern workflows and UK-specific finance/lettings automation. | Beat with stronger enterprise controls and hybrid sales + lettings workflows. |
| RentAdmin | Landlord/property management, rent automation, bank connection, documents, compliance reminders, contractor work management, HMO support, reports. | Simpler landlord UX, bank-connected rent tracking, HMO/multi-unit features. | Simplicity and landlord self-serve. | Beat by serving agencies with richer client accounting and compliance workflows. |
| ManageLet | UK landlord SaaS, HMO support, rent automation, compliance, tenant portal, tax-year reports, maintenance, inspections, analytics. | Room-level HMO workflows, inspection reports, tenant portal, UK tax-year exports. | Focused landlord workflow and clean SaaS positioning. | Beat with agency-grade multi-branch operations and accounting. |
| Latch | Letting agents, AI tenant communications, compliance dashboard, batch operations, landlord reports, white-label portfolio view. | White-label portals, batch operations, compliance ops, AI communications. | SaaS growth/automation framing. | Beat with deeper accounting, open APIs, and enterprise workflow controls. |
| Rentol | AI-first team/personas, compliance, repairs, rent/arrears, owner reporting, tenant comms, onboarding, portals, MTD-ready finance. | AI activity feed, named AI agents, AI-controlled workflows, visible undo. | AI-native product story. | Beat by making AI legally safe, auditable, and deeply integrated into UK compliance/accounting. |

What would make this platform meaningfully better:

1. Become the most compliance-safe UK property OS, not just another CRUD PMS.
2. Build accounting that finance teams trust: client ledgers, bank feeds, landlord disbursement, audit packs.
3. Build AI around real workflows with visible evidence and undo, not generic chat.
4. Support hybrid sales + lettings agencies with one contact/property/compliance/accounting backbone.
5. Offer open APIs and an integration marketplace earlier than smaller competitors.
6. Make onboarding/migration excellent: import from spreadsheets/Reapit/PropCo, reconcile opening balances, validate compliance.

## Section 8 - Recommended Roadmap

### Phase 1 - 30 Days: Stop the Bleeding

| Workstream | Actions | Complexity | Impact |
|---|---|---:|---:|
| Security | Remove default password behavior, enforce password policy, add MFA plan, review upload access and admin routes. | Medium | Critical |
| Tenant isolation design | Define `account_id/workspace_id`, map current `company_id`, identify every table needing scope. | Medium | Critical |
| Permission audit | Normalize permission names, map sidebar gates, add policy tests for property/user/tenancy/accounting access. | Medium | Critical |
| Product truth | Create module inventory, user journey map, duplicate invoice/accounting decision. | Low-medium | High |
| UK compliance gap closure plan | Define Right to Rent, deposit, EPC, Gas, EICR, CMP, AML workflows and data fields. | Medium | High |
| Finance architecture | Write ledger design for client/office accounts, rent schedule, allocation, landlord disbursement. | High | Critical |
| Ops readiness | Add route list smoke test, scheduler inventory, queue retry dashboard plan. | Low-medium | High |

### Phase 2 - 90 Days: Build SaaS Foundations

| Workstream | Actions | Complexity | Impact |
|---|---|---:|---:|
| Multi-tenant foundation | Add account/workspace model, tenant scopes, indexes, data migration, isolation tests. | High | Critical |
| RBAC/ABAC | Company/branch/property/ledger scoped roles, permission matrix, UI gates. | High | Critical |
| SaaS billing | Plans, subscriptions, feature entitlements, usage metrics, tenant provisioning. | Medium-high | High |
| Compliance v1 | EPC/Gas/EICR/Right to Rent/deposit workflows with evidence and reminders. | High | High |
| Accounting v1 hardening | Unified invoice engine decision, client/office ledger separation, allocation rules. | High | Critical |
| Search | Add global search infrastructure for contacts/properties/repairs/docs/invoices. | Medium | High |
| API foundation | Versioned internal API, auth, webhook/outbox foundations. | Medium-high | High |

### Phase 3 - 6 Months: UK Agency Product Fit

| Workstream | Actions | Complexity | Impact |
|---|---|---:|---:|
| Lettings lifecycle | Applicant to move-in workflow: matching, viewing, holding deposit, referencing, e-sign, deposit registration, move-in pack. | High | Critical |
| Rent and arrears | Rent schedules, partial allocations, reminders, payment plans, arrears ageing, Section 8 evidence. | High | Critical |
| Landlord finance | Statements, disbursement engine, owner splits, fees, floats, NRL placeholders. | High | Critical |
| Maintenance automation | Tenant portal, contractor portal, quote/approval/job/invoice workflow. | High | High |
| Portals | Tenant, landlord, contractor portals with role-specific UX. | High | High |
| Integrations v1 | Open Banking, Xero/QuickBooks export/sync, SMS/email provider, e-sign. | High | High |
| Reporting | Branch/team KPIs, compliance risk, arrears, maintenance, finance packs. | Medium-high | High |

### Phase 4 - 12 Months: Enterprise and AI Differentiation

| Workstream | Actions | Complexity | Impact |
|---|---|---:|---:|
| Sales CRM | Valuations, vendors/buyers, offers, memorandum, chain/progression, AML. | High | High |
| Compliance automation | Renters' Rights workflows, legal evidence packs, pet/rent increase validators, HMO licences. | High | Critical |
| Integration marketplace | Portal feeds, deposit schemes, referencing, HMRC MTD, payments/BACS, BI exports. | High | Very high |
| AI ops layer | Maintenance triage, document extraction, portfolio assistant, arrears prediction, communication drafting. | High | Very high |
| Enterprise controls | SSO/SAML, audit exports, data warehouse, custom roles, advanced branch hierarchy, SLAs. | High | Very high |
| Marketplace potential | Contractor network, insurance, referencing, payments, compliance services. | High | Very high |

### Monetization Roadmap

| Revenue Lever | Model | Timing |
|---|---|---|
| Core SaaS subscription | Per branch + per unit/property. | Phase 2 |
| Portals | Included in Pro/Agency tiers, white-label in Enterprise. | Phase 3 |
| Accounting/client money | Premium add-on due to support and compliance cost. | Phase 3 |
| Open Banking/payment processing | Transaction or payment rail margin. | Phase 3 |
| AI agents | Usage-based or tiered AI add-on. | Phase 4 |
| Compliance services | Certificate renewals, checks, legal pack generation partnerships. | Phase 4 |
| Integration marketplace | Partner referral/transaction revenue. | Phase 4 |
| Data/BI | Enterprise analytics/data warehouse add-on. | Phase 4 |

## Section 9 - Final Risk Matrix

| Risk | Severity | Probability | Business Impact | Fix Priority |
|---|---|---:|---:|---:|
| Cross-tenant data leakage | Critical | High | Existential | P0 |
| Weak/default passwords | Critical | Medium-high | Existential | P0 |
| Client money accounting gaps | Critical | High | Enterprise blocker/regulatory | P0 |
| UK compliance workflow gaps | Critical | High | Product not market-fit | P0 |
| Duplicate finance engines | High | High | Incorrect reporting/support pain | P0 |
| Inconsistent permissions | High | High | Security and UX failure | P0 |
| Missing integrations | High | High | Competitive disadvantage | P1 |
| Poor search/performance | High | Medium | Scale failure | P1 |
| Modal-heavy admin UX | Medium-high | High | Low adoption | P1 |
| No subscription/metering | High | High | Not SaaS | P1 |
| No API/webhooks | High | High | Platform ceiling | P2 |
| No mobile portals | High | Medium-high | Competitor weakness | P2 |

### What Prevents Enterprise Adoption

- No hard multi-tenant isolation proof.
- No client accounting certification-grade controls.
- No enterprise SSO, audit export, role matrix, data retention tooling, or API program.
- No proven integrations with banks, portals, accounting platforms, deposit schemes, e-sign, referencing.
- No workflow configuration for multi-office agencies.
- No evidence of performance engineering, CI/CD, observability, support tooling, or SLAs.

### What Prevents Investor Confidence

- Product breadth hides implementation depth gaps.
- UK compliance requirements are not encoded as enforceable workflows.
- Accounting is high-risk and partially duplicated.
- Architecture is a monolith without tenant isolation discipline.
- Security fundamentals need hardening.
- No clear monetization engine or SaaS operating model.
- Competitors already market AI, portals, compliance automation, Open Banking, and enterprise integrations.

### What Could Make This Category-Leading

The route to category leadership is not to copy every competitor screen. The winning angle is:

1. UK compliance-first property OS.
2. Trustworthy client accounting and landlord disbursement engine.
3. AI-assisted, audit-visible operations.
4. Hybrid sales + lettings in one model.
5. Open integration marketplace.
6. Multi-office SaaS with strong isolation, portals, and analytics.

If executed well, the product could move from internal ERP to a serious SaaS platform. Without the P0 fixes, it remains an agency back-office application with high compliance and scale risk.

