# Resisquare UI improvement log

**Date:** 16 Sep 2026  
**Live URL:** `https://laravel.resisquare.co.uk/admin` → redirects to `/login`  
**Accounts walked:** Landlord owner (`Landlord.owner@Resisquare.test`) and Tenant (`tenant@resisquare.test`)  
**Constraint:** Observation only. No application files were changed except this log.

---

## Verdict

The client complaint is right. The product is not unattractive because it lacks a sidebar or because one page is ugly. It looks cheap because **three visual languages share one agency admin shell**, and only a handful of landlord pages received a coat of teal.

What a landlord actually sees in one session:

1. **A decent workspace** — Dashboard, Properties overview, Certificates, Add Property, some Finance/Portal banners (`landlord-workspace.css`, teal `#0f766e`, black CTAs, 12–16px radii).
2. **A leftover CRM** — Owner Groups, Billing, Repair list/detail, Contacts, Profile edit (Bootstrap blue / yellow / red, bordered tables, `#` columns).
3. **A thin tenant portal** sitting inside the same navy CMS chrome, with staff copy still on the page.

Until those three become **one** system, restyling single screens will not change the client’s mind.

**Sidebar stays.** It is the right pattern for a landlord workspace. The problem is not that a sidebar exists. The problem is that it still behaves like an agency CMS: menu search, nested “View Active / Archived / Inactive / Terminated”, truncated email, and some active items going black-on-navy.

---

## 1. What the product is

UK property workspace:

- **Landlord:** homes, occupancy, rent, repairs, documents, people, billing.
- **Tenant:** pay rent, report issues, see lease, documents, visits.

Human titles should be addresses, people, and months. The live UI currently leads with `RESISQP0000001`, `RENT-0003`, `Tenancy #2`, `Untitled`, and QA strings like `QA tenant repair 1789390597742`.

---

## 2. Cross-cutting (fix these before new screens)

### C1. Laravel Debugbar is on
It sits on every page (login through tenant home). Query counts hit **200–300+** on Properties. It covers primary buttons and makes the product look unfinished. Turn it off on any URL a client can open.

### C2. Three button languages on one product
Seen in one walk:

- Landlord black/teal pills (`+ Add property`, `Save tenancy`, `Invite tenant`)
- Bootstrap `btn-primary` blue (`Activate Subscription`, `Create Rent Invoice`, `Search`, `Next`)
- Yellow / red (`Edit`, `Delete`, `Change Password`, `Add Contact`)
- Outline cyan / yellow / blue chips on Tenancies (`View` / `Edit` / `Rent Ledger`)
- Orange wizard (`Next` on Add Contact)

Pick one set and use it everywhere:

| Role | Style |
|---|---|
| Primary | Black fill, pill (`#111827`) — already used on landlord CTAs |
| Secondary | White + teal border |
| Destructive | Text / outline red, not a fat red block |
| Accent | Teal `#0f766e` for banners, links, active nav |

Ban `btn-primary` / `btn-warning` / `btn-danger` on landlord and tenant routes.

### C3. Sidebar is still an agency CMS (keep it, flatten it)

Both roles get the same chrome:

- Desktop hamburger next to the logo **while the sidebar is already open**
- “Search menu…” plus a permanent `×` — landlords have ~11 items, tenants have **7**
- Nested status lists that duplicate in-page filters (Tenancies Active/Archived/Inactive/Terminated, Repair Issues All/Pending/Reported/…)
- Footer email truncated (`landlord.owner@resisquare.t…`)
- Active child items can go **black on navy** (Add New Tenancy / Add New Contact)

**Landlord sidebar (flat, one level):**

Dashboard · Properties · Tenancies · Finance · Repairs · Documents · People · Settings

Status filters belong **on the page** as chips, not as four extra nav links.  
“Owner Groups” and “Portal Access” are not first-class products — fold them into People / property.  
“Billing & Plan” belongs under Settings.

**Tenant sidebar:** drop search entirely. Same navy rail is fine: Home, My Tenancy, Rent, Calendar, Maintenance, Documents, Profile. On phones, this should become a **bottom tab bar**, not a 320px drawer that never collapses.

File: `resources/views/backend/partials/aside2.blade.php`

### C4. Every authenticated tab title is “Resisquare”
Browser history is useless. Set “Properties · Resisquare”, “Rent · Resisquare”, etc. (`backend/layout/app.blade.php`)

### C5. System IDs and dump formatting
The UI constantly shows internals:

- `RESISQP0000001`, `RESISQRPR0000003`, `RENT-0003`, `Tenancy #2`
- Datetimes as `2026-01-01 00:00:00` or `9/9/2026, 12:00:00 AM`
- Currency as `1,250.00` / `1442.31` / `233.00` with mixed or missing `£`
- `Property #1` instead of an address
- Files named `Untitled` or `Document`
- Repair titles `QA landlord repair photo 1789391099953`

Show the human name first. Keep refs as secondary copy. Dates as `1 Jan 2026`. Always `£`.

### C6. Addresses are over-verbose
Almost every table concatenates `Flat 12, 1 Baltimore Wharf, London, Tower Hamlets, E14 9RU, United Kingdom`. Use **title + postcode**. Full string only on detail/print.

### C7. Nested scroll + leftover chrome
Main content lives in `#wrapper.main_content`, not the window. Property detail has a second pane. Result: huge empty grey, clipped copy, users hunting for an inner scrollbar. Prefer one page scroll.

### C8. Agency / staging copy leaked to customers
Seen live today:

- Login: “Please enter your login and password!”
- Tenant home: “Nothing else from the staff workspace is shown here”
- My Tenancy: “Staff-only tools are not shown”
- Calendar: “The office diary stays with your landlord”
- Documents: “portal-visible”
- Add property: “Test mode: try SW1A 1AA or E14 9RU”
- Tenant invoice note: **“MVP checklist invoice for Tina”**
- Tenant profile: **“Back to Users”**
- Profile edit: “Your current plan does not include estate agency company profile access”
- Repair detail: “Staging repair for contractor portal testing” / “use staging lockbox code 0000”

Rewrite in landlord/tenant English. Never mention staff tools, MVP, test mode, office diaries, or staging lockboxes.

### C9. Mobile is not a first-class layout
On a 390px viewport the tenant home **did not go full width**. Measured: viewport 390px, sidebar still `display: block` at 320px, `#wrapper` ~418px. Content sat in a left strip; `Logout` truncated to `Logou`; **two** “View my tenancy” buttons appeared. Landlords and tenants will use phones. This needs a dedicated mobile shell.

### C10. Empty / N/A rendered as UI
`N/A`, `Not specified`, `— — —` (invoice period), address `,`, `+440000000000`, silhouette avatars, generic building glyphs, pink square as a “repair photo”. Hide empty fields. Use a real photo or a designed empty illustration.

---

## 3. Login (`/admin` → `/login`)

**What I saw:** Centred card on a vast pale canvas. Uppercase LOGIN. Navy-style header with a hamburger on a **public** page. Login + Register in the header while already on Login. Outline Login button looks disabled. Debugbar along the bottom.

**Improve:**

- Dedicated auth layout: brand panel (property photography / illustration) + form. Do not reuse the logged-in navbar.
- Remove the hamburger on marketing/auth pages.
- Copy: “Sign in to Resisquare”, not “Please enter your login and password!”
- Primary button teal or black, full-width, impossible to cover.
- `/admin` should feel like an intentional destination, not an accidental redirect.

---

## 4. Landlord pages

### 4.1 Dashboard `/admin/dashboard` — closest to “good”

Hero, stat cards, quick actions, plan usage, recent lists. This is the visual target for everything else.

**Still wrong:**

- Repair list led by ticket IDs (`RESISQRPR0000003`, `STG-REP-001`) and “No property”
- Status chips inconsistent (`Pending` / `Under Process` / `Open`)
- Quick actions duplicate the sidebar without a hierarchy
- “Staging Landlord Account” chip is demo language
- Plan usage here is more polished than the Billing page — Billing should match this, not the other way around
- Hamburger + always-open sidebar

Keep the hero + cards. Push this language onto the unskinned modules.

### 4.2 Properties `/admin/properties`

Split-pane portfolio is the right idea. Execution is still “admin tool”, not a consumer product.

**Visual / UX:**

- Generic building glyph instead of photos. Empty white dominates the right pane.
- Buckingham Palace QA: **240 bed · 78 bath · 77,000 Sqm** — dummy data that makes the product look broken.
- Flat 12 is **Available** while showing 2 tenancies and £233/mo. Occupancy should be Let / Vacant / Notice.
- Header “0 Compliance” while EPC on the same property is Rating B.
- `Ctrl+K` badge feels like a developer tool.
- List items exposed as checkboxes named `5` / `6`.
- **Tenancy tab:** a wide admin table stuffed into the pane. Rows are ID `1` / `2` with **no tenant names**. Dates `2026-01-01 00:00:00`. Currency mixed (`1,250.00` vs `1442.31`). Empty columns. “No Archived tenancies found.”
- **Certificates tab** is actually well designed (EPC / Gas Safe / EICR cards) — extend this pattern. Intro copy still clips.

**Improve:** photo-first cards; occupancy badge; tenancy cards (name, rent, dates, one overflow menu); load tabs on demand (this page feels slow, which reads as cheap).

### 4.3 Add property (modal `?add_property=1`)

One of the better flows: stepper Property → Owners → Tenants, postcode search.

**Improve:** remove “Test mode: try SW1A 1AA…”. Rename “Later” to “Skip for now”. Consider a full-page wizard; a modal over the busy split pane feels cramped.

### 4.4 Calendar `/admin/calendar`

Teal banner + a **left column of unused filters** + FullCalendar. One event: `Move-in -- Flat 108` with attendee rendered as **`?? Lara Landlord`** (encoding bug). Date shown as `9/9/2026, 12:00:00 AM`. `month / week / day` vs `today` / arrows misaligned. Pagination `Prev 1 Next` for a single event. “All Sub-Types” is agency jargon. Apply is Bootstrap blue.

**Improve:** hide advanced filters behind “Filters”. Landlords need Upcoming + Month. Fix the `??` name. Friendly empty/occupied states. Drop Sub-Types.

### 4.5 Tenancies `/admin/tenancies?status=Active`

Teal banner is fine. The table is a spreadsheet:

- `#` column
- Three differently coloured outline buttons per row
- Tenants dumped as a comma list (`Sabir Sayyed, Invite QA Guest, Lucid Mail Test`)
- Helper copy: “Lets on your properties. Link any tenancy that has no property…”
- Sidebar duplicates the in-page Active dropdown
- Vast empty canvas under two rows

**Improve:** card or single row (property, household, rent, status, overflow menu). Filter chips on the page, not four sidebar links.

### 4.6 Add tenancy `/admin/tenancies/create`

Long agency form. “Quick add tenant” is an outline-primary chip sitting **under Property**, not under Tenants. Awkward label **“Number of deposit type (Weeks)”**. Deposit Service dropdown shows `*TDS or DPS Number` as if it were a placeholder. Four tiny `dd-mm-yyyy --:--` fields crammed on one row. Legal deposit fields dumped with no grouping.

**Improve:** 3-step wizard — Household → Rent & term → Deposit protection. Hide TDS until “Protect deposit” is chosen. Full-size date pickers.

### 4.7 Tenancy view `/admin/tenancies/2` — **broken (P0)**

Opening **View** serves a page **without the app layout**: black background, unstyled native controls, `dd-mm-yyyy --:--` fields, “Notice type”, “Record & notify”. No header, no sidebar.

This is not a polish issue. It is an unfinished partial served as a full page. Worst landlord screen. Fix before any visual pass on Tenancies.

### 4.8 Rent ledger `/admin/tenancies/2/rent-ledger`

Closer to a product page, but:

- Title `Rent Ledger - Tenancy #2`
- Amounts **without `£`** (`233.00`, `0.00`, `1,232.00`)
- Move in `2026-09-09 00:00:00`
- Full UK address + `RESISQP0000001`
- Bright blue `Create Rent Invoice`
- Cyan Bootstrap alerts for empty invoices/payments
- No teal landlord banner — different chrome from neighbouring pages

### 4.9 Owner Groups `/admin/owner-groups` — **no landlord skin**

Raw Bootstrap. This page alone can lose a client demo.

- Full-width blue `Add New Owner Group`
- Bordered table, `#` IDs
- Full United Kingdom addresses
- Yellow Edit + red Delete stacked in the cell
- No hero, no cards, no occupancy story

**Improve:** ownership belongs on the property (people + shares). If a list is needed, use the same cards as Finance.

File: `resources/views/backend/owner_groups/index.blade.php`

### 4.10 Portal Access `/admin/portal-access`

Teal banner + invite form is reasonable. The table underneath lists **everyone**: landlords, contractors, property managers, `temp name`, invalid emails, QA mailinator addresses. Column headers `CAN LOGIN` / `ASSIGNED PROPERTIES` are staff language. View/Edit (blue outline) + Revoke (red outline).

**Improve:** “People with portal access” (tenants only, avatar, last login) + Invite. Move landlord users out of this list.

### 4.11 Finance `/admin/finance` and `/create`

List is sparse: wrapping invoice numbers, wrapping names, `Property #1`, status as plain text (`Issued` / `Paid`) with no colour system. No “collected this month / outstanding” summary — the dashboard has better finance UX than Finance.

Create invoice: oversized empty fields, mixed date formats (`16-09-2026` vs `dd-mm-yyyy`), period optional, does not look like a bill.

**Improve:** summary strip + invoice cards titled “Tina · Sep 2026” with a status pill. Compose as a bill (period, amount, due date, note).

### 4.12 Contacts `/admin/users`

Different product again. Red `Add Contact`. Split list. Detail is a `Label: value` dump.

- `Role:Tenant` missing space
- Address rendered as `,` or `—`
- `Phone: Not specified` wrapping
- Long emails overflow
- Tabs **Link / Compliance / Documents / Notes** are agency CRM
- `Allow: Email: No | Post: No | Text: No | Call: No`
- Placeholder phones `+440000000000`
- No avatars, no teal banner

**Improve:** People directory with role chips (Tenant / Owner / Other), phone/email, linked property. Hide empty fields. Drop Compliance from the contact chrome.

### 4.13 Add contact `/admin/users/create`

Orange typography wizard: “What is **Category** for this user?” plus a tiny unlabelled dropdown and an orange Next. Completely off-brand vs teal landlord pages. Vast empty canvas. “Category” is internal.

**Improve:** “Who are you adding?” with large choices: Tenant / Owner / Other. Same stepper as Add Property.

### 4.14 Billing & Plan `/admin/billing`

Looks like a Stripe admin console:

- Bright blue `Activate Subscription`
- Definition tables
- **Stripe subscription ID**, **Stripe price ID**, **Price snapshot**, **Stackable**
- Add-ons **Extra Branch** and **Extra Staff** (agency leftovers)
- `GBP 15.00` vs `£5.00`
- Disabled grey `Activate subscription first` on every add-on
- Usage rows for Branches / Staff / Property managers that a private landlord does not need

**Improve:** consumer billing — plan name, price, trial end, usage vs limits (copy the dashboard plan card), one CTA. Hide Stripe IDs and agency add-ons.

### 4.15 Repair list `/admin/property-repairs/issue-list`

Second-worst landlord screen after tenancy View. Agency split pane:

- Search by Property Name, `-- Filter by Status --`, blue Search, grey Reset, Hide Detail
- Selected row dark grey
- Property cell repeats the address until it clips (`Flat 12, 1 Baltimore Wharf, Flat 12, 1 Baltimore Wharf…`)
- Issue titles are `Staging General Repair`
- Description: `QA landlord repair photo 1789391099953`
- Pink square as a photo
- Availability: N/A
- Red outline Edit + Collapse All
- Sidebar still has nested Repair Issues → All / Pending / Reported / Under Process / Work Completed / Closed

**Improve:** landlord repair inbox — card per issue (photo, property, human title, status pill, date). Detail as a **page**, not a colliding split view. Collapse status filters into chips.

### 4.16 Raise repair `/admin/property-repairs/raise-repair-issue-create`

Teal banner is fine. Wizard is thin: duplicate questions (“Where is the problem?” / “Which property needs the repair?”), Previous grey / Next **Bootstrap blue**, lots of empty white.

**Improve:** match Add Property stepper. Property cards with photos, then area of home, then description + photos.

### 4.17 Repair detail `/admin/property-repairs/repair-show/1`

Unskinned accordion rainbow on one page:

- Bright blue H1 `Repair Issue Details`
- Cyan / grey / **black** / blue / **yellow** section bars
- Yellow Edit
- “No property selected”
- Category and Navigation both `Staging General Repair`
- Staging lockbox copy
- Contractor quoting table shown to a landlord

**Improve:** one designed case page: photo, property, status timeline, description, documents. Do not show contractor quoting chrome unless the landlord uses it.

### 4.18 Documents `/admin/documents`

Teal banner, then a table of **Untitled** files, full addresses, `14/09/2026 06:33 PM`, outline Download + grey Share. Tenant column actually means visibility (`Shared` / `Private`). No upload on this page.

**Improve:** file list with icon, real name, property, date. Upload here. “Shared with tenant” as a toggle.

### 4.19 Notifications `/admin/notifications`

Empty-state copy is decent. The **filter bar is overkill** for an empty inbox (states, categories, priorities, two date pickers, blue Filter). “All states” is developer language.

**Improve:** list + “Unread only”. Move date filters into an advanced drawer.

### 4.20 Profile `/admin/users/profile` and `/profile/edit`

View: generic “User Profile / View and manage user information”, silhouette, `N/A` address lines, duplicate email/phone, placeholder `+440000000000`, blue Edit.

**Edit is a layout collision (P0):** a bright blue `Edit User Profile` bar **covers the logo** and runs under the bell / account chip. Native `Choose File | No file chosen`. Yellow “Change Password” block. Agency plan message about estate-agency company profile.

**Improve:** Settings page in landlord chrome. Fix the overlapping header immediately. Designed avatar uploader.

---

## 5. Tenant pages

IA is clearer (Home, My Tenancy, Rent, Calendar, Maintenance, Documents, Profile) but it still sits inside the **agency admin shell** — search box, hamburger, navy CMS, `Logout` in the header instead of an account chip.

### 5.1 Home `/admin/home`

Friendly “Welcome, Tina” and metric cards. Problems:

- Meta copy about the staff workspace
- Full `United Kingdom` address as the home name
- Repair titles are QA strings with numeric suffixes
- Invoice codes as the only invoice title
- Search menu still present for seven links
- Silhouette avatar in the footer

### 5.2 My Tenancy `/admin/portal/tenancy`

Sparse definition list on a vast empty canvas. Heading “Your lease” vs nav “My Tenancy”. “Staff-only tools are not shown.” No photo, no landlord contact, no download-agreement CTA. Household is just the tenant’s own name. Deposit `£1,442.31` with odd precision.

**Improve:** property photo, rent, deposit, landlord/agent contact, “Download agreement” if shared.

### 5.3 Rent `/admin/portal/rent` and invoice `/admin/portal/rent/3`

List is one of the better tenant screens (Unpaid / Paid pills, Pay CTA, outstanding chip). Invoice codes as titles still.

Invoice detail issues:

- Period rendered as `— — —`
- Note **“MVP checklist invoice for Tina”** visible to the tenant
- Duplicate `RENT-0003` heading
- “View only — ask your landlord if anything looks wrong” while a Pay button is present
- Does not look like a bill (no payee, no payment reference, no line items)

**Improve:** title “Rent due 28 Sep”. Hide internal notes. Show period, due date, how to pay, itemised amount.

### 5.4 Calendar `/admin/portal/calendar`

Not a calendar. Empty list plus leftover “office diary” copy. Landlord has FullCalendar with a move-in; tenant has a stub and a vast empty canvas.

**Improve:** same month view as landlord (read-only) or a proper illustrated empty state. “No visits booked” — never mention the office.

### 5.5 Maintenance `/admin/portal/maintenance`

Report form is a small card in the **top-right**; the table is disconnected below. No photo upload on the visible form. Priority `Normal` in the form vs `medium` in the table. Agency copy: “Quotes, costs and contractor notes stay with the office.” Addresses wrap with United Kingdom. QA titles.

**Improve:** “Report a problem” as the hero (photos required). Requests as cards with photo, status, last update. Align priority labels.

### 5.6 Documents `/admin/portal/documents`

One row named `Document`, type `Document`, visibility `Shared`. Subtitle mentions “portal-visible”. No icon, size, or preview.

**Improve:** named files (AST, How to rent, Inventory) with PDF/image icons.

### 5.7 Profile `/admin/users/profile` (as tenant)

**“Back to Users” must not appear for tenants.** This is the admin users module reused as a portal page. Same `N/A` addresses and placeholder phone as the landlord profile. Blue Edit.

Gate admin-only actions with `is_tenant_portal_user()`.

### 5.8 Mobile (tenant home, 390×844)

This is a product-level miss, not a breakpoint tweak:

- Header collision: logo + hamburger + bell + truncated Logout
- Main column does **not** use full width (sidebar offset remains; content is a left strip)
- Duplicate “View my tenancy”
- Cards stack but sit in a left column with a huge empty right

Tenants will primarily use phones. Dedicated mobile shell: overlay sidebar or bottom tabs, full-width content, untruncated header.

---

## 6. What already looks closer to “good”

Do not throw these away; **extend them**:

- Landlord dashboard hero + stats + plan usage
- Property split list (direction is right; execution is cramped)
- Add-property postcode stepper
- Property certificates cards (EPC / Gas Safe / EICR)
- Tenant rent list (status pills + Pay)
- Landlord CSS tokens in `landlord-workspace.css` (`--lw-accent: #0f766e`, 12–16px radii, black CTAs)
- Keep the **navy sidebar** — flatten it, don’t replace it with a top-only nav

The gap is that Owner Groups, Repairs, Billing, Contacts, Profile, Tenancy View, and the tenant shell never adopted those tokens.

---

## 7. Design system (so the next pass is consistent)

| Token | Use |
|---|---|
| Navy `#0b1220` | Sidebar only |
| Teal `#0f766e` | Hero banners, links, active nav |
| Black `#111827` | Primary buttons |
| Wash `#f4f6f8` | Page background |
| Card `#fff`, 16px radius, 1px `#e6e9ef` | All lists and forms |
| Status | Green Let/Paid, Amber Due/Pending, Red Overdue/Urgent, Grey Closed |
| Type | One sans, no Bootstrap blue headings |
| Density | Cards for 1–20 records; tables only when exporting |

---

## 8. Priority backlog (what would actually change the client’s mind)

### P0 — looks broken

1. Tenancy View served without layout (`/admin/tenancies/2`).
2. Profile edit header covering the logo.
3. Debugbar off on client URLs.
4. Tenant “Back to Users”.
5. Mobile: full-width content + untruncated header.
6. Active sidebar links black-on-navy.
7. Calendar attendee `?? Lara Landlord`.

### P1 — attractiveness (the actual complaint)

8. Restyle Owner Groups, Billing, Repair list/detail, Contacts, Profile to `lw-*` components.
9. One button system; kill yellow/red/blue Bootstrap on customer routes.
10. Flatten sidebar; remove Search menu for tenants; remove nested status lists. **Keep the sidebar.**
11. Human titles (addresses, “September rent”) instead of `RESISQ*` / `RENT-0003` / `Untitled`.
12. Property photos; hide empty `N/A`.
13. Tenancy-in-property: cards, not a clipped DataTable of timestamps.
14. Rewrite staff / MVP / test-mode / staging copy.
15. Tenant calendar should be a calendar (or an illustrated empty state).
16. Tenant maintenance: photo-first report, not a side form + admin table.

### P2 — craft

17. Per-page `<title>`.
18. Invoice period not `— — —`.
19. Consistent `£` and date format.
20. Occupancy vs “Available” on let properties; “0 Compliance” vs EPC B.
21. Billing: hide Stripe IDs and agency add-ons.
22. Add-contact orange wizard → same stepper as Add Property.
23. Login as a branded auth screen.
24. Lazy-load property tabs (300-query pages feel cheap).

---

## 9. Pages walked (this session)

**Landlord:** Login, Dashboard, Properties (overview / Flat 12 / tenancy tab / certificates), Add property modal, Calendar, Tenancies list, Add tenancy, Tenancy view (broken), Rent ledger, Owner Groups, Portal Access, Finance list, New invoice, Contacts, Add contact, Billing, Repair list, Raise repair, Repair detail, Documents, Notifications, Profile, Profile edit.

**Tenant:** Home, My Tenancy, Rent, Invoice RENT-0003, Calendar, Maintenance, Documents, Profile. Mobile home at 390px.

**Not deeply exercised:** Stripe checkout, actual card payment, notification preferences, owner-group create internals.

---

## 10. Suggested next implementation slice

If engineering time is limited, one sprint that would visibly answer the client:

1. Fix P0 broken screens (tenancy show, profile header, tenant Back to Users, debugbar, mobile width).
2. Extract a small Blade kit: `lw-hero`, `lw-card`, `lw-table`, `lw-btn`, `lw-empty`, `lw-status` and apply it to Owner Groups, Billing, Contacts, Repair list/detail, Profile.
3. Flatten the sidebar (keep it) — one link per module, chips on the page.
4. Ban raw `Y-m-d H:i:s`, missing `£`, `Untitled`, and staff/MVP copy on landlord/tenant views.

That is a visual unification pass, not a new feature set. It matches how the dashboard already wants to look.

---

## 11. Solution we should follow (before any UI-lab mock)

This is the design contract for landlord and tenant screens. The UI lab at `/admin/ui-lab` is only for proving these rules. Live routes should later adopt the same chrome, not a parallel product.

### 11.1 North star

Resisquare is a **workspace for homes**, not an estate-agency CRM.

Every screen should answer, in this order:

1. Which home?
2. Who lives there?
3. What money is due?
4. What is broken?
5. What paperwork exists?

If a layout, label, or table does not help those five questions, it does not belong on a landlord or tenant page.

We do **not** invent a new brand. We finish the one already started on Dashboard / Properties / Certificates (`landlord-workspace.css` + Inter, navy, teal, wash, black pills).

### 11.2 Two products, one visual system

| | Landlord | Tenant |
|---|---|---|
| Job | Run a small portfolio | Live in one home |
| Desktop chrome | Top bar + **navy sidebar** + wash canvas | Same chrome, fewer links |
| Phone | Overlay sidebar (hamburger actually hides the rail) | **Bottom tabs**, full-width, no leftover sidebar offset |
| Primary object | The home | My home + pay + report |
| Density | Cards for 1–20 records | Cards always |

Agency staff tools (accounting, branches, SaaS, registrations) keep the old CRM. They are out of scope for this pass.

### 11.3 Chrome (non-negotiable)

**Keep the sidebar.** Flatten it. That was the client-facing navigation request and it is the right landlord pattern.

```
[ logo ]                         [ bell ]  [ Lara · Landlord Basic ]
[ navy rail, 240px ]
  Dashboard
  Properties
  Tenancies
  Finance
  Repairs
  Documents
  People
  Calendar
  Settings
```

Rules for the rail:

- One level only. No “View Active / Archived / Inactive / Terminated”.
- No “Search menu…”.
- No hamburger while the rail is already open on desktop.
- Active item: teal wash on navy, white text. Never black-on-navy.
- Footer: full name + truncated email on hover/title, not mid-word cut.
- Filters live **on the page** as chips (All / Let / Vacant, Unpaid / Paid, Open / Closed).

**Fold these out of the rail:**

- Owner Groups → People, and on the property
- Portal Access → People (“can sign in”)
- Billing & Plan → Settings
- Raise Repair / Repair Issues children → one Repairs item + in-page chips
- Add New Property / Tenancy / Contact → primary button on that page, not a nav child

**Top bar:** logo, page context if needed, bell, account chip. Tenant uses the same chip (not a raw Logout button).

**Page canvas:** `#f4f6f8`. One window scroll. No `#wrapper` inner scrollbar. No second pane that clips.

**Auth:** dedicated layout. Brand panel + form. No logged-in navbar, no hamburger, no debugbar.

### 11.4 Colour, type, buttons

Same tokens already in `landlord-workspace.css` and `ui-lab.css`:

| Token | Value | Use |
|---|---|---|
| Ink | `#0b1220` | Text, sidebar |
| Teal | `#0f766e` | Active nav, links, hero, focus |
| Teal deep | `#064e3b` | Hero gradient end |
| Black | `#111827` | Primary buttons |
| Wash | `#f4f6f8` | Page |
| Line | `#e6e9ef` | Card borders |
| Card | `#fff`, 16px radius | Surfaces |
| Type | Inter | Everything |

**Buttons — three only:**

- Primary: black pill (`Add property`, `Pay £1,100`, `Save`)
- Secondary: white + teal/ink border (`View ledger`, `Invite`)
- Destructive: text or outline red, never a fat red block

No Bootstrap `btn-primary` / `btn-warning` / `btn-danger` on landlord or tenant routes. No orange Add-Contact wizard. No cyan accordion headers.

**Status pills (one set):**

- Green: Let, Paid, Active, On the register
- Amber: Pending, Due, Vacant notice, Upload needed
- Red: Overdue, Urgent, Unpaid
- Grey: Closed, Private, Inactive

Occupancy language: **Let / Vacant / Notice**. Never “Available” on a lived-in flat.

### 11.5 Copy and data on screen

Show humans first. Refs second, or not at all.

| Instead of | Show |
|---|---|
| `RENT-0003` | September rent |
| `Tenancy #2` | Tina · Flat 12 |
| `RESISQP0000001` | Flat 12, E14 9RU |
| `Untitled` / `Document` | AST, Gas Safe, Inventory |
| `QA tenant repair 1789…` | Kitchen tap dripping |
| `2026-01-01 00:00:00` | 1 Jan 2026 |
| `1,250.00` / `1442.31` | £1,250.00 / £1,442.31 |
| Full UK address in tables | **Flat 12** + `E14 9RU` |
| `N/A`, `Not specified`, `— — —` | Hide the row |
| Staff / MVP / test mode / office diary | Landlord or tenant English |

Page `<title>` is `Properties · Resisquare`, `Rent · Resisquare`, never a generic `Resisquare` on every tab.

Empty states get a short sentence + one action. Not a filter bar over a blank table.

### 11.6 Interaction model

Three surfaces, used the same way everywhere:

1. **Page** — the list or workspace (dashboard, homes, invoices, repairs).
2. **Overlay (dialog)** — short forms: add home, invite, issue invoice, change password.
3. **Case (wide overlay or full page)** — inspect a tenancy, a repair, an invoice. The list stays in the product; we do not dump a Blade partial onto a black page.

On tenant **phone**, the same actions are a **bottom sheet**.

Wizards (add home, add tenancy, add person, report repair) are 2–3 steps with the same stepper chrome. Never a different colour language per wizard.

One primary CTA per page. Row actions collapse to one overflow (`···`), not three coloured outline chips.

### 11.7 Information architecture (what each module is for)

**Dashboard**  
“Needs you” first: unpaid rent, open repair, missing certificate. Then 3–4 stats. Quick actions are the same as the sidebar, so keep them to three (Add home, Issue rent, Report repair). Plan usage is a small card that opens Settings/Billing — do not duplicate a Stripe console here.

**Properties**  
The home is the object. Photo, occupancy pill, title + postcode, rent, household, open repair, certificates.  
Detail stays a split list on desktop (that pattern is right) but:

- Photo-first cards, not building glyphs
- Overview metrics that do not truncate
- Tenancy tab = stacked cards (person, rent, dates, overflow), not a DataTable of IDs
- Certificates stay as EPC / Gas Safe / EICR cards (already good)
- Add home = the existing postcode stepper, as an overlay or full-page wizard, **without** “Test mode”

Cross-property work (all invoices, all repairs) lives in Finance / Repairs, not as nested property AJAX dumps.

**Tenancies**  
A list of lets, because landlords think “who is in that flat”. Same card language as Finance. Clicking a row opens a **designed tenancy case** (people, rent, deposit, notices) inside the app layout — never `show.blade.php` without chrome. Add tenancy = Household → Rent & term → Deposit protection.

**Finance**  
Summary strip (collected this month, outstanding, overdue) + invoice cards titled “Tina · Sep 2026”. Status pills. Compose invoice like a bill (period, amount, due, note). Hide `Property #1` and `RENT-0003` as the headline.

**Repairs**  
An inbox of cards: photo, home, human title, status, date. Open a case page (timeline, description, files). Raise repair uses the same stepper as add home. No split-pane CRM, no contractor quoting unless the landlord uses contractors.

**Documents**  
Named files, icon, home, date, “Shared with tenant” toggle, upload on this page.

**People**  
Directory of Tenant / Owner / Other. Avatar, name, email/phone, linked home, “can sign in”. Invite and ownership shares are overlays. Contacts, Owner Groups, and Portal Access become this one module.

**Calendar**  
Upcoming list + month. Filters behind a “Filters” control. Same month view, read-only, for tenants. Empty: “No visits booked this month”.

**Settings**  
Profile + plan/billing. Consumer billing: plan name, price, trial end, usage vs limit, one CTA. No Stripe IDs, Extra Branch, Extra Staff.

**Tenant**  
Home (pay first), My tenancy (photo + rent + landlord contact + download agreement), Rent (pills + pay), Maintenance (photo-first report + cards), Documents, Calendar, Profile. No “Back to Users”. No staff copy.

### 11.8 Mobile

Landlord: the rail becomes an overlay. Content is 100% wide. Header controls must not truncate.

Tenant: bottom tabs — Home, Rent, Repairs, Docs, Me. Sheets for Pay and Report. This is a different shell, not a squashed desktop.

### 11.9 What we will *not* do

- Do not restyle one random page in isolation (that is how the product got three languages).
- Do not replace the sidebar with a top-only nav.
- Do not turn the whole app into a single “homes” scroll with no modules — Finance, People, Calendar still need destinations.
- Do not keep nested CRM lists “because the data model has statuses”.
- Do not show Debugbar, query counts, or staging copy on a client URL.

### 11.10 How the UI lab should prove this

`/admin/ui-lab` is a demo of **this contract**, not a gallery of unrelated ideas. When we design there, show:

1. **Shell** — flattened navy sidebar + top bar (the chrome every landlord page inherits).
2. **Dashboard** — needs-you, not a CMS.
3. **A home** — photo, Let pill, rent / repair / certificates as work on that home.
4. **One leftover CRM page restyled** — Repairs or People or Billing, so the client can see “this is what Owner Groups should become”.
5. **Login** — branded auth, no hamburger.
6. **Tenant phone** — full width, pay first, bottom tabs.

Same dummy data as live (Lara, Tina, Flat 12 / E14 9RU, September rent £1,100, kitchen tap), but with human titles and real photos. That makes the before/after obvious.

### 11.11 Rollout into the live app (after the lab is agreed)

1. **P0 integrity** — tenancy show layout, profile header, tenant Back to Users, debugbar, mobile width, sidebar contrast.
2. **Chrome** — flatten `aside2.blade.php`, one button + pill kit in `landlord-workspace.css`, apply to every landlord/tenant view.
3. **Copy helpers** — one date formatter, always `£`, short address, hide empty fields. Ban `Untitled` and staff sentences.
4. **Module restyles in this order** (highest demo damage first): Repairs → Owner Groups/People → Billing/Settings → Tenancy case → Contacts → Tenant shell.
5. **Then** occupancy vs Available, property photos, lazy property tabs.

The lab is the picture. The Blade kit (`lw-hero`, `lw-card`, `lw-btn`, `lw-pill`, `lw-empty`, flattened sidebar) is the implementation. If a new screen cannot be built from those pieces, the design is not in the system yet.

## 12. Live UAT — 28 Sep 2026

Browser walk of https://laravel.resisquare.co.uk/ as a new landlord trial and the invited tenant. Full tables, scores, and file references are in `software-testing-report-2026-09-28-1630.html`. Score 61%. Not ready for production.

Data left on the staging host: property 11 (1 The Mall, SW1A 1AA), tenancy 6 (Sabir Sayyed, confirmed), unpaid invoice RENT-0001 for £1,250 due 28 Sep 2026. Landlord Basic trial ends 5 Oct 2026.

What worked: signup OTP, Chimnie lookup, property and tenancy, tenant confirmation, Stripe sandbox trial, tenant blocked from landlord finance/people/tenancies (403), invoice visible on the tenant home.

Open defects from this walk:

1. Property stats show Compliance 0 in green while Certificates lists gas, EPC, and EICR as missing. `resources/views/backend/properties/partials/detail-stats.blade.php`.
2. New invoice leaves amount blank and defaults the due date to 12 Oct 2026, not the tenancy due day (28).
3. Tenant profile heading is Sabir Sayyed but first name and last name inputs are empty. `resources/views/backend/tenant/portal/profile.blade.php`.
4. At 390px the dashboard intro is 483px wide and clipped.
5. Share with tenant on a missing certificate focuses a required empty field and does not share.
6. Tenant home says there are no appointments. Calendar lists the move-in on 28 Sep 2026 at 10:00.
7. Notification bell stayed empty for both roles after RENT-0001.
8. Tenant account switcher is labelled with landlord account names. Selecting one did not open the landlord workspace.
9. Registration still hides a verify_via mail error. `resources/views/frontend/register.blade.php` `showFieldError`. Mail itself was working by the end of the session.
10. Homepage has no CSP, frame, content-type, HSTS, or referrer header.
11. Copy: “1 deposit need you”, “We will user you shortly”, “Staging General Repair”, tenancy table datetime `2026-09-28 00:00:00` and deposit `1250.00`.
