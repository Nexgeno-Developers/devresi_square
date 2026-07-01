# Lovable build prompt

Copy everything inside the prompt block into Lovable.

---

## Prompt

Build a polished, responsive, clickable SaaS prototype called **Resisquare** for UK property management. Resisquare is the platform owner. Paying customers are either Estate Agent companies or self-managing Landlords. This is a product prototype with realistic mock data and simulated Stripe screens; do not collect real card details or make real payments.

### Product model

Use a multi-tenant workspace concept:

- Resisquare Super Admin operates the full platform.
- Estate Agent customers own an agency workspace with company profile, multiple branches and staff.
- Landlord customers own a landlord portfolio workspace without agency branches or branch-staff operations.
- Tenant, Contractor and Owner users have limited assigned/view-only portals.
- Property Managers work inside an Estate Agent workspace or inside a Landlord workspace only when the landlord has purchased a Property Manager add-on.
- A landlord or owner added as an agency contact is view-only. Buying a Landlord plan creates a separate landlord workspace; it does not grant edit access inside the agency.

Make workspace/role scope visible throughout the UI. Include a prototype role/workspace switcher so reviewers can demonstrate every role without real authentication.

### Technical and visual direction

- Use the normal Lovable React + TypeScript component stack, Tailwind and accessible reusable UI components.
- Create a clean modern B2B SaaS visual style: professional, calm and property-focused.
- Primary colour: deep navy `#12324A`; accent: teal `#18A999`; supporting sky `#4A90E2`; warm warning amber; neutral slate backgrounds.
- Use generous whitespace, 12–16 px card radii, subtle borders/shadows, clear typography and restrained charts.
- Desktop uses collapsible left navigation plus top bar. Mobile uses a compact top bar and drawer/bottom-priority navigation.
- Meet strong accessibility basics: labelled inputs, visible focus, keyboard-friendly controls, sufficient contrast, semantic tables and non-colour status cues.
- Use `£`, `DD/MM/YYYY`, `Europe/London`, UK postcodes and UK property terms such as tenancy, landlord, letting, council tax and contractor.
- Include loading skeletons, empty states, validation errors, permission-denied states, confirmation dialogs, success toasts and plan-limit states.
- Persist prototype changes in local state/local storage so create/edit flows feel real after navigation.

### Public pages

Create these complete pages:

1. Home page with hero, benefits for agencies/landlords, product modules, portal benefits, testimonials clearly marked as sample, and calls to action.
2. Features page grouped into Properties, Tenancies, Rent & Invoices, Maintenance, Contractors, Documents, Communications, Reports, Branches & Staff.
3. Pricing page with monthly/annual toggle and prices shown excluding VAT:
   - Landlord: £19/month or £190/year; 5 active properties, 1 admin, unlimited assigned portal users, 5 GB storage.
   - Estate Agent: £149/month or £1,490/year; 100 active properties, 2 branches, 5 staff, unlimited portal contacts, 25 GB storage.
   - Add-ons: Property Manager seat £49/month for Landlords; extra branch £25/month; staff seat £9/month; Landlord +10 properties £10/month; Agency +100 properties £35/month; +25 GB storage £8/month.
   - Explain that Tenant, Contractor and Owner portals do not count as staff seats.
4. Login, forgot password, reset password, register, email verification and pending checkout screens.
5. Registration first asks “Estate Agent” or “Landlord”, billing interval and optional capacity/add-ons.
6. Contact/demo page and concise FAQ.

### Simulated Stripe purchase flow

Build clickable screens for:

1. Plan selected on pricing page.
2. Account and workspace details.
3. Order summary with plan, add-ons, VAT placeholder and renewal date.
4. A clearly labelled **Simulated Stripe Checkout** screen using test UI only—never request a real card number. Provide “Simulate successful payment”, “Simulate failed payment” and “Cancel” actions.
5. Success state that briefly says “Confirming your subscription”, then activates onboarding.
6. Failed/cancelled state with retry/change-plan actions.
7. Customer Plan & Billing page: current plan, trial/active/past-due status, renewal, usage bars, add-ons, mock SaaS invoices, payment-method placeholder, upgrade/downgrade preview, cancel at period end and reactivate.
8. Past-due grace banner and read-only restricted state. Billing, export and support remain available.

Use a 14-day trial state with a clear first charge date. Annual billing gives two months free. Clearly label prices “+ VAT”.

### Shared application shell

Top bar:

- Workspace name/type
- Current branch filter when relevant
- Global search
- Quick-create menu
- Notifications
- Help
- User menu
- Prototype role/workspace switcher

Shared sidebar sections should be permission-aware, not merely disabled:

- Dashboard
- Properties
- Contacts
- Tenancies
- Rent & Invoices
- Maintenance
- Contractors / Work Orders
- Documents
- Messages
- Calendar
- Reports
- Company / Branches / Staff where allowed
- Plan & Billing for billing owners
- Settings

Hide irrelevant modules for limited portals and Landlord workspaces. Show a friendly permission page if a demo URL is manually visited without access.

### Role dashboards

Build materially different dashboards for:

#### Resisquare Super Admin

- KPI cards: MRR, active workspaces, trials, past due, churn and failed payments.
- Charts: MRR trend, plan mix, sign-ups and subscription status.
- Customer workspaces table with company/portfolio name, owner, plan, status, property/staff usage, next renewal and actions.
- Pages for Customers, Users, Plans & Add-ons, Subscriptions, Stripe Events, Support Settings and Platform Analytics.
- Customer detail includes subscription timeline, usage, contacts, status and audited suspend/reactivate actions.
- Never display fake Stripe secret keys.

#### Estate Agent Admin

- Cards: 84/100 properties, 91% occupancy, £128,450 rent due this month, £9,820 overdue, 17 open maintenance requests, 8 compliance items expiring.
- Branch performance chart and open-task list.
- Quick actions: Add property, Add contact, Create tenancy, Raise invoice, Invite staff.
- Full access to Company, Branches, Staff, role templates and own Plan & Billing.

#### Branch Manager

- Same operational modules, filtered to assigned branch(es).
- Branch properties, arrears, maintenance queue, expiring compliance and staff workload.
- No subscription or company-wide plan actions.

#### Staff

- My tasks, appointments, assigned properties, recent contacts and maintenance queue.
- Finance/export/destructive actions hidden unless the Accountant/Invoice Staff profile is selected.

#### Accountant / Invoice Staff

- Invoice totals, overdue/partial balances, payments to match, recent receipts, AR ageing and statement shortcuts.
- No staff-role administration or property deletion.

#### Landlord subscriber

- Portfolio cards: 4/5 active properties, 3 occupied, £5,850 monthly rent, £450 overdue, 2 maintenance requests, 3 compliance expiries.
- Quick actions: Add property, Add tenant, Create invoice, Raise maintenance, Invite portal user.
- No company branches or agency staff screens.
- Show “Add a Property Manager — £49/month” with an upgrade modal and then a manager assignment flow.

#### Property Manager

- Assigned properties only, urgent maintenance, unread tenant messages, contractor quotes, invoice actions allowed by permission and upcoming compliance expiries.
- Assignment chips and branch/workspace context on every property.

#### Tenant portal

- Simple portal navigation: Home, My Tenancy, Rent & Payments, Maintenance, Documents, Messages, Profile.
- Show assigned property, tenancy status, next rent invoice, payment history and shared documents.
- Complete maintenance form with category, urgency, description, access notes, availability and image upload preview.
- Do not show owner financials, internal notes or contractor quote values.

#### Contractor portal

- Navigation: Jobs, Quote Requests, Documents, Messages, Profile.
- Job cards for Quote Requested, Assigned, In Progress, Awaiting Approval and Completed.
- Job detail supports quote, availability, status update, progress/completion photos and contractor invoice upload.
- Do not show competing quotes, other contractors or unrelated properties/jobs.

#### Owner portal

- Navigation: Overview, Properties, Shared Reports, Documents, Messages, Profile.
- View-only linked properties, occupancy summary, selected shared financial figures and files.
- No create/edit/delete controls.

### Company, branch and staff modules

Estate Agent only:

- Company profile with trading/registered name, company number, VAT number, addresses, phones/emails, services, logo and website.
- Branch list/cards and create/edit drawer. One branch can be Head Office. Show active property and staff counts.
- Staff table with name, designation, branch, status, last login and invitation state.
- Invite staff flow with branch, role template and permission summary.
- Role templates: Estate Agent Admin, Branch Manager, Staff, Property Manager and Accountant / Invoice Staff.
- Permission editor grouped by Properties, Contacts, Tenancies, Finance, Maintenance, Documents, Reports and Administration.
- Plan-limit modal when trying to create a third included branch or sixth included staff seat, with add-on CTA.

### Contacts module

- Tabs/filters for Landlords, Owners, Tenants, Contractors, Property Managers and Other.
- Contact table/card toggle, search, status, portal-access badge and linked property count.
- Contact detail has Overview, Linked Properties, Tenancies/Jobs, Documents, Messages, Notes and Activity.
- Internal notes have a clear “Internal only” label; shared content is explicitly marked.
- “Invite to portal” explains view-only/assigned access.
- For a landlord contact, show a tasteful “Manage your own portfolio with a Landlord plan” CTA that creates a separate workspace, not edit access to the agency.

### Properties module

- Search/filter by branch, manager, status, occupancy, postcode and compliance risk.
- Card/table views and an interactive property detail.
- Property tabs: Overview, Owners & Landlords, Tenancy, Compliance, Maintenance, Invoices, Documents, Media, Messages, Activity.
- Create/edit flow with UK address, postcode, property reference, type, bedrooms, bathrooms, tenure, rent, branch, landlord/owners and manager.
- Compliance section with EPC, Gas Safety, EICR, deposit evidence and other certificate types, issue/expiry, status and file.
- Show archive confirmation, not destructive delete, for properties with history.
- Add a plan-limit state when active property capacity is reached.

### Tenancies and rent

- Tenancy list with property, tenants, status, start, rent, frequency, deposit and balance.
- Create tenancy flow: property, tenant members/main tenant, property manager, commencement, assured periodic status, rent/frequency, deposit scheme/reference and documents.
- Use England-aware wording and do not present fixed terms as the only tenancy type.
- Tenancy detail: summary, tenant members, rent ledger, invoices/payments, maintenance, documents, messages and activity.
- Rent invoice list/detail/create with statuses Draft, Issued, Partial, Paid, Overdue, Cancelled.
- Recurring monthly charge setup, downloadable mock invoice, record-payment modal and statement screen.
- Keep this property finance UI separate from Resisquare Plan & Billing.

### Maintenance, quotes and work orders

Create a complete clickable workflow:

1. Tenant reports “Boiler losing pressure” with photos and availability.
2. Estate Agent/Property Manager triages priority and assigns manager.
3. Request quotes from two contractors.
4. Contractors submit price and availability.
5. Internal comparison screen shows both quotes; tenant cannot see it.
6. Select final contractor.
7. Generate/send work order with line items and dates.
8. Contractor accepts, marks in progress, uploads completion photos and invoice.
9. Manager approves and closes request.
10. Timeline records every event.

Statuses: Reported, Triaged, Quote Requested, Contractor Assigned, Scheduled, In Progress, Awaiting Approval, Completed, Closed.

### Documents, messages, calendar and reports

- Document centre with entity, type, visibility, expiry, uploader, date and file preview placeholder.
- Upload drawer supports drag/drop preview and explicit Private/Internal/Shared visibility.
- Messages use thread list and conversation panel with participants, linked property/job and delivery state.
- Calendar with maintenance visits, compliance expiries, inspections and tenancy events.
- Reports page with occupancy, rent collection/arrears, maintenance response, compliance expiries and branch performance where applicable.
- Filters and mock CSV/PDF export actions; export permission matters.

### Sample UK data

Use coherent sample records across all screens:

- Agency: **Northstar Lettings Ltd**, company no. `11223344`, head office in Manchester.
- Branches: Manchester Central and Salford Quays.
- Agency admin: **Amelia Hart**.
- Branch manager: **Daniel Okafor**.
- Property manager: **Priya Shah**.
- Landlord subscriber workspace: **Aisha Khan Portfolio**.
- Tenant: **Oliver Bennett**.
- Contractor: **Northern Heating Services Ltd**, contact **Lewis Grant**.
- Owner: **Margaret Wilson**.
- Properties:
  - `RS-MAN-001`, 24 Deansgate Mews, Manchester, M3 2BW
  - `RS-SAL-014`, Apartment 18, 7 Dockside Avenue, Salford, M50 3AB
  - `RS-LDS-009`, 11 Park Row, Leeds, LS1 5HD
- Use consistent linked tenancy, invoices, maintenance request, quotes and documents across these records.
- Dates must be plausible around June/July 2026. Currency is GBP.
- Show compliance guidance as product reminders, with “Not legal advice” where appropriate.

### Required clickable interactions

- Role/workspace switcher changes dashboard, navigation and allowed actions.
- Create/edit/archive property.
- Create branch and trigger branch limit.
- Invite staff and trigger staff-seat limit.
- Create contact and invite view-only portal.
- Create tenancy and view rent ledger.
- Create invoice, record part payment and see status/balance update.
- Complete the full maintenance/quote/work-order flow.
- Upload/share a document using mock file metadata.
- Send a mock message and update unread state.
- Select plan, add add-ons, simulate Checkout success/failure, upgrade, cancel/reactivate and past-due restriction.
- Filters, search, pagination controls, mobile drawer and toasts should work.

### Route/page map

Use clean routes or equivalent application state for:

- `/`, `/features`, `/pricing`, `/login`, `/register`, `/verify-email`
- `/checkout`, `/checkout/success`, `/checkout/failed`
- `/app/dashboard`
- `/app/properties`, `/app/properties/:id`
- `/app/contacts`, `/app/contacts/:id`
- `/app/tenancies`, `/app/tenancies/:id`
- `/app/invoices`, `/app/invoices/:id`
- `/app/maintenance`, `/app/maintenance/:id`
- `/app/contractors`, `/app/work-orders/:id`
- `/app/documents`, `/app/messages`, `/app/calendar`, `/app/reports`
- `/app/company`, `/app/branches`, `/app/staff`, `/app/roles`
- `/app/billing`, `/app/settings`
- `/platform/customers`, `/platform/subscriptions`, `/platform/plans`, `/platform/stripe-events`
- `/portal/tenant`, `/portal/contractor`, `/portal/owner`

### Definition of done

- The prototype is visually complete on desktop, tablet and mobile.
- Every requested role has a distinct, permission-correct experience.
- Estate Agent can manage company/branches/staff; Landlord cannot see those agency features.
- Landlord contact view-only mode is distinct from a paying Landlord workspace.
- Tenant, Contractor and Owner see only assigned/shared mock data.
- Pricing and simulated Stripe lifecycle states are complete and clickable.
- Core property, tenancy/invoice and maintenance workflows work end to end in mock state.
- UK terminology, addresses, dates and GBP formatting are consistent.
- No real payment or sensitive personal data is collected.
- Do not leave dead buttons, lorem ipsum, duplicated dashboard layouts or generic placeholder pages.

---

