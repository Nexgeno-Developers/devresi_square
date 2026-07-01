# Resisquare user roles and permissions

## Access model

Access is the intersection of four checks:

```text
authenticated identity
  + active workspace membership and role
  + paid plan/add-on entitlement and capacity
  + resource scope (workspace, branch, property, tenancy or assignment)
= permitted action
```

A visible button is never sufficient authorisation. The server must repeat all four checks.

## Matrix legend

- **M** — create, view and manage within the role's permitted workspace scope
- **B** — manage only in assigned branch(es)
- **A** — access only self/assigned/linked/shared records
- **V** — view only within linked/shared scope
- **C** — configurable permission; off by default unless an admin enables it
- **—** — no access

“Manage Stripe billing” means access to billing operations. It never means access to Stripe secret keys.

## Role-permission matrix

| Permission | Super Admin | Estate Agent Admin | Branch Manager | Staff | Landlord | Property Manager | Tenant | Contractor | Owner |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| View dashboard | M | M | B | A | M | A | A | A | A |
| Manage company/workspace profile | M | M | V | — | M | — | — | — | — |
| Manage branches | M | M | B | — | — | — | — | — | — |
| Manage staff/invitations | M | M | B | — | — | — | — | — | — |
| Assign roles/permissions | M | M | B | — | — | — | — | — | — |
| Manage properties | M | M | B | C | M | A | V | V | V |
| Archive/delete properties | M | M | C | — | M | — | — | — | — |
| Manage tenancies | M | M | B | C | M | A | V | — | V |
| Manage tenants | M | M | B | C | M | A | A | — | V |
| Manage landlord contacts | M | M | B | C | — | A | — | — | — |
| Manage owner contacts | M | M | B | C | M | A | — | — | A |
| Manage contractors | M | M | B | C | M | A | — | A | — |
| Assign Property Managers | M | M | B | C | M* | — | — | — | — |
| Manage rent/sale invoices | M | M | B | C | M | C | V | — | V** |
| Record property payments | M | M | B | C | M | C | V | — | V** |
| Manage maintenance requests | M | M | B | C | M | A | A | A | V |
| Request/compare contractor quotes | M | M | B | C | M | A | — | A | — |
| Manage work orders | M | M | B | C | M | A | V | A | V |
| Upload documents | M | M | B | C | M | A | A | A | — |
| View shared documents | M | M | B | A | M | A | A | A | A |
| Share/delete documents | M | M | B | C | M | C | — | — | — |
| Send/view communications | M | M | B | C | M | A | A | A | A |
| View reports | M | M | B | C | M | A | V | — | V |
| Export reports/data | M | M | C | C | M | C | — | — | — |
| Manage own subscription | M | M | — | — | M | — | — | — | — |
| Manage Stripe billing | M | M*** | — | — | M*** | — | — | — | — |
| Manage plans/add-ons globally | M | — | — | — | — | — | — | — | — |
| Manage SaaS customers | M | — | — | — | — | — | — | — | — |
| View platform analytics | M | — | — | — | — | — | — | — | — |
| Suspend any workspace/user | M | — | — | — | — | — | — | — | — |
| Suspend own workspace member | M | M | B | — | M | — | — | — | — |

\* Landlord assignment of a Property Manager requires an active Property Manager add-on and an available seat.  
\** Owner invoice/report visibility is explicitly shared per property; it is not automatic.  
\*** Customer admins manage only their own Stripe Customer/subscription through safe application actions or the Customer Portal.

## Role definitions and boundaries

### Super Admin / Resisquare Admin

Platform-level role, outside customer workspace queries.

- Operates plans, add-ons, prices, subscriptions, platform users and customer workspaces.
- Can suspend/reactivate with a mandatory reason and audit record.
- Sees aggregate platform analytics and Stripe reconciliation state.
- Does not casually edit customer property data. Any support access should be explicit, time-limited and audited.
- Requires MFA in production.

### Estate Agent Admin

Billing owner or delegated administrator of one agency workspace.

- Manages company, branches, staff, role templates and all workspace modules.
- Can invite landlord/owner contacts with view-only portal access.
- Can assign branch and property scope to staff.
- Manages the agency subscription and add-ons.
- Cannot access any other customer workspace.

### Branch Manager

Agency member responsible for one or more branches.

- Sees and manages records linked to assigned branches.
- May invite/suspend branch staff and assign branch roles if the agency admin enables it.
- Cannot change company billing, plans, global workspace settings or other branches.
- Cross-branch records require explicit additional branch membership.

### Staff

Agency operational member.

- Starts from a designation/permission template.
- Access is limited to assigned branch/property/task scope.
- Finance, export, deletion, sharing and role administration are off by default.
- A custom permission override must not expand resource scope beyond the membership.

### Landlord

There are two modes that must be kept distinct:

1. **Landlord subscriber:** owns a paid Landlord workspace and manages its properties, tenants, contractors, invoices, maintenance, documents and reports.
2. **Landlord contact:** invited into an Estate Agent workspace and receives view-only access to linked/shared records. This role cannot manage the agency's data merely because the person is a landlord.

The user interface should label the mode clearly: “Landlord workspace owner” versus “Agency portal contact”.

### Property Manager

Workspace member under an Estate Agent, or under a Landlord with the add-on.

- Accesses assigned properties only.
- Manages assigned tenants, maintenance, contractors, work orders, documents and communications.
- Invoice handling and reports are separate permissions.
- Cannot manage the workspace subscription, staff roles or unassigned properties.
- Removing an assignment removes object access immediately without deleting history.

### Tenant

Limited portal member.

- Accesses only their tenancy and linked property.
- Sees rent invoices/payment history intended for the tenant.
- Raises and follows maintenance requests; uploads relevant photos/documents.
- Communicates with authorised workspace members.
- Cannot see owner details, internal notes, contractor comparisons, commissions or unrelated tenants.

### Contractor

Limited job-based portal member.

- Access is derived from a quote request or confirmed job assignment.
- Sees only minimum property/access information required for that job.
- Submits quotes, availability, job status, photos and contractor invoice.
- Cannot see competing quotes, landlord financials, tenant payment data or other jobs.
- A single contractor identity may have separate memberships/assignments across workspaces.

### Owner

View-only portal member linked to one or more properties.

- Sees property details, shared documents and limited shared reports.
- Financial visibility is explicitly configured per property/workspace.
- Cannot edit property, tenancy, invoices or maintenance.
- Becoming a paying Landlord creates a separate Landlord workspace; it does not elevate permissions inside an agency workspace.

## Estate Agent staff profiles

These are workspace role templates rather than global platform roles:

| Template | Default scope | Notable defaults |
|---|---|---|
| Estate Agent Admin | Entire workspace | All business modules and own subscription |
| Branch Manager | Assigned branch(es) | People, property, tenancy and maintenance management; no billing |
| Staff | Assigned branch/property | View/create operational records; no deletion/export/finance by default |
| Property Manager | Assigned properties | Tenant, maintenance, contractor, document and communication actions |
| Accountant / Invoice Staff | Workspace or branch | Invoices, payments, statements and reports; no property deletion or staff admin |

## Resource-scope rules

1. Every request resolves an active workspace before loading tenant-owned data.
2. Route-model binding includes the workspace key; `findOrFail(id)` alone is prohibited for tenant records.
3. Branch scope is additive within one workspace, never a replacement for workspace scope.
4. Property access is derived from workspace plus role/branch/assignment/link.
5. Tenancy, invoice, maintenance, work-order, document and conversation access inherits from their parent property or explicit assignment, with a matching tenant key.
6. Super Admin platform access uses a separate administrative policy and audit path.
7. Background jobs carry immutable workspace and record IDs and re-resolve authorisation-safe data.
8. Exports use the same query policy as screens and record who requested them.

## Entitlement rules

Role permissions cannot bypass plan limits.

| Entitlement | Enforcement point |
|---|---|
| Branch limit | Before branch create/restore |
| Staff seat limit | Before invitation and reactivation |
| Property limit | Before property create/import/restore |
| Property Manager add-on | Before manager invitation/assignment in Landlord workspace |
| Finance/report tier | Before route/controller action and navigation rendering |
| Storage allowance | Before upload finalisation |

Existing records remain readable when a downgrade reduces capacity, but creating or reactivating over-limit records is blocked until usage is reduced or the plan is upgraded. Downgrade consequences must be shown before confirmation.

## Suggested canonical permission names

Use stable, verb-first identifiers in code and human labels in the UI:

- `workspace.view`, `workspace.update`, `workspace.members.manage`
- `branches.view`, `branches.manage`
- `properties.view`, `properties.create`, `properties.update`, `properties.archive`
- `contacts.view`, `contacts.manage`, `contacts.invite`
- `tenancies.view`, `tenancies.manage`
- `invoices.view`, `invoices.manage`, `payments.record`
- `maintenance.view`, `maintenance.create`, `maintenance.manage`, `maintenance.assign`
- `work_orders.view`, `work_orders.manage`
- `documents.view`, `documents.upload`, `documents.share`, `documents.delete`
- `communications.view`, `communications.send`
- `reports.view`, `reports.export`
- `billing.view`, `billing.manage`

Avoid mixed capitalization and synonyms such as `Edit Contacts`, `edit users`, and `manage users` for the same action.

## Mapping from the existing CRM

| Existing mechanism | SaaS direction |
|---|---|
| Global Spatie roles on `users` | Workspace membership role, using Spatie teams or explicit membership-role joins |
| Designation permissions | Retain as agency role templates scoped to a workspace |
| Direct staff permission override | Retain with audit, but always bounded by workspace/resource scope |
| `created_by` filters | Keep for authorship only; replace as access boundary |
| Sidebar `@can` checks | Keep for UX after equivalent server-side policy is present |
| Hardcoded role checks in controllers | Replace with policies/abilities and reusable scope queries |
| `selected_properties` JSON | Replace with relational assignments/memberships |
| Owner/Tenant/Contractor user subclasses | Keep one identity model and express context through memberships/relationships |

## Authorisation acceptance tests

- A user in Workspace A cannot view, update, export or infer a record from Workspace B by changing an ID.
- Branch Manager cannot access another branch unless assigned to both.
- Staff permission override cannot grant access outside the staff member's workspace/property scope.
- Landlord contact cannot mutate agency records.
- Paying Landlord can manage their own workspace without receiving agency edit rights.
- Tenant cannot see internal notes, other tenants or contractor quote values.
- Contractor cannot see competing contractors or unassigned jobs.
- Owner cannot mutate linked property data.
- Past-due plan rules restrict features consistently on UI and server.
- Super Admin support access is logged with actor, target workspace, reason and time.

