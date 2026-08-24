$ErrorActionPreference = "Stop"

$outFile = Join-Path $PSScriptRoot "ResiSquare-Stakeholder-Application-Status-Report.docx"
$tmp = Join-Path $PSScriptRoot "_stakeholder_docx_build"
if (Test-Path $tmp) { Remove-Item -LiteralPath $tmp -Recurse -Force }
New-Item -ItemType Directory -Force -Path $tmp, (Join-Path $tmp "_rels"), (Join-Path $tmp "word") | Out-Null

function X([string]$s) {
    if ($null -eq $s) { return "" }
    return [System.Security.SecurityElement]::Escape($s)
}

function Para([string]$text, [string]$style = "Body", [string]$color = "111827", [bool]$bold = $false) {
    $size = switch ($style) {
        "Title" { "40" }
        "H1" { "28" }
        "H2" { "24" }
        "Small" { "18" }
        default { "21" }
    }
    $before = switch ($style) {
        "H1" { "260" }
        "H2" { "180" }
        default { "0" }
    }
    $after = switch ($style) {
        "Title" { "120" }
        "H1" { "120" }
        "H2" { "90" }
        default { "90" }
    }
    $b = if ($bold -or $style -in @("Title", "H1", "H2")) { "<w:b/>" } else { "" }
    return "<w:p><w:pPr><w:spacing w:before=`"$before`" w:after=`"$after`" w:line=`"276`" w:lineRule=`"auto`"/></w:pPr><w:r><w:rPr>$b<w:color w:val=`"$color`"/><w:sz w:val=`"$size`"/><w:szCs w:val=`"$size`"/><w:rFonts w:ascii=`"Calibri`" w:hAnsi=`"Calibri`"/></w:rPr><w:t>$(X $text)</w:t></w:r></w:p>"
}

function Bullet([string]$text) {
    return Para ("- $text")
}

function PageBreak() {
    return "<w:p><w:r><w:br w:type=`"page`"/></w:r></w:p>"
}

function Cell([string]$text, [int]$width, [string]$fill = "FFFFFF", [bool]$bold = $false, [string]$color = "111827") {
    $b = if ($bold) { "<w:b/>" } else { "" }
    return "<w:tc><w:tcPr><w:tcW w:w=`"$width`" w:type=`"dxa`"/><w:shd w:fill=`"$fill`"/><w:tcMar><w:top w:w=`"90`" w:type=`"dxa`"/><w:left w:w=`"110`" w:type=`"dxa`"/><w:bottom w:w=`"90`" w:type=`"dxa`"/><w:right w:w=`"110`" w:type=`"dxa`"/></w:tcMar></w:tcPr><w:p><w:pPr><w:spacing w:after=`"20`"/></w:pPr><w:r><w:rPr>$b<w:color w:val=`"$color`"/><w:sz w:val=`"18`"/><w:szCs w:val=`"18`"/><w:rFonts w:ascii=`"Calibri`" w:hAnsi=`"Calibri`"/></w:rPr><w:t>$(X $text)</w:t></w:r></w:p></w:tc>"
}

function Table($headers, $rows, $widths, $rowFills = @{}) {
    $grid = ($widths | ForEach-Object { "<w:gridCol w:w=`"$_`"/>" }) -join ""
    $xml = "<w:tbl><w:tblPr><w:tblStyle w:val=`"TableGrid`"/><w:tblW w:w=`"9360`" w:type=`"dxa`"/><w:tblInd w:w=`"120`" w:type=`"dxa`"/><w:tblBorders><w:top w:val=`"single`" w:sz=`"4`" w:color=`"CBD5E1`"/><w:left w:val=`"single`" w:sz=`"4`" w:color=`"CBD5E1`"/><w:bottom w:val=`"single`" w:sz=`"4`" w:color=`"CBD5E1`"/><w:right w:val=`"single`" w:sz=`"4`" w:color=`"CBD5E1`"/><w:insideH w:val=`"single`" w:sz=`"4`" w:color=`"CBD5E1`"/><w:insideV w:val=`"single`" w:sz=`"4`" w:color=`"CBD5E1`"/></w:tblBorders></w:tblPr><w:tblGrid>$grid</w:tblGrid>"
    $xml += "<w:tr>" + (($headers | ForEach-Object -Begin { $i = 0 } -Process { $c = Cell $_ $widths[$i] "E8EEF5" $true; $i++; $c }) -join "") + "</w:tr>"
    for ($r = 0; $r -lt $rows.Count; $r++) {
        $fill = if ($rowFills.ContainsKey($r)) { $rowFills[$r] } else { "FFFFFF" }
        $xml += "<w:tr>"
        for ($c = 0; $c -lt $headers.Count; $c++) {
            $xml += Cell ([string]$rows[$r][$c]) $widths[$c] $fill ($c -eq 0)
        }
        $xml += "</w:tr>"
    }
    $xml += "</w:tbl>"
    return $xml
}

function ModuleBlock([string]$name, [string]$status, [string]$readiness, [string[]]$done, [string[]]$pending) {
    $b = @()
    $b += Para "$name - $status ($readiness)" "H2"
    $b += Para "Done:" "Body" "111827" $true
    foreach ($item in $done) { $b += Bullet $item }
    $b += Para "Pending:" "Body" "111827" $true
    foreach ($item in $pending) { $b += Bullet $item }
    return $b
}

$body = @()

# Cover
$body += Para "ResiSquare Application Status and MVP Readiness" "Title" "0B2545"
$body += Para "Stakeholder report pack - all versions in one document" "Body" "475569"
$body += Para "Prepared: August 2026 | Product: ResiSquare Property Management SaaS" "Small" "64748B"
$body += Para "Contents: (1) Stakeholder summary, (2) Module-wise report, (3) MVP launch readiness, (4) Executive summary, (5) Presentation notes, (6) Client email draft." "Body"

# VERSION 1
$body += PageBreak
$body += Para "VERSION 1 - Stakeholder Summary Document" "H1" "0B2545"
$body += Para "Simple English summary for business stakeholders." "Body" "475569"

$body += Para "What is ResiSquare?" "H2"
$body += Para "ResiSquare is a cloud-based property management platform for landlords and estate agents. It helps manage properties, tenants, repairs, documents, invoices, and subscriptions in one place." "Body"

$body += Para "Overall status" "H2"
$body += Table @("Area", "Status") @(
    @("Core property management", "Strong - mostly built"),
    @("SaaS billing (plans, Stripe)", "Good - built, needs testing"),
    @("Data security between customers", "In progress"),
    @("Tenant / owner portals", "Weak - not MVP ready"),
    @("Testing and launch readiness", "Not ready - staging QA incomplete")
) @(3200, 6160)

$body += Para "Bottom line: The product has a lot of working functionality, but it is not yet safe or complete enough for a full commercial MVP launch without finishing security, billing verification, and testing." "Body"

$body += Para "What has been done recently" "H2"
foreach ($item in @(
    "Customer account/workspace system built",
    "Subscription plans, add-ons, and Stripe billing added",
    "Property, tenancy, repair, and document modules improved",
    "Account-based data protection added in many areas",
    "Duplicate property protection within same account",
    "Login error messages and linked property views improved",
    "Staging checklists and QA documentation created"
)) { $body += Bullet $item }

$body += Para "What is still pending" "H2"
$body += Para "Must fix before launch:" "Body" "111827" $true
foreach ($item in @(
    "Complete security review and remove public debug routes",
    "Verify Stripe billing end-to-end in staging",
    "Ensure one customer cannot see another customer's data everywhere",
    "Strengthen permission controls on admin pages",
    "Complete staging testing with real test accounts",
    "Set up production environment (mail, SMS, queue, backups)"
)) { $body += Bullet $item }

$body += Para "Can wait until after MVP:" "Body" "111827" $true
foreach ($item in @(
    "Full tenant portal",
    "Full owner/contact portal",
    "Estate agent advanced features (branches, staff seats)",
    "Old finance modules",
    "Advanced business analytics"
)) { $body += Bullet $item }

$body += Para "Recommended launch approach" "H2"
$body += Para "Launch MVP as Landlord-first: signup, billing, properties, tenancies, repairs, documents, basic invoicing, and plan limits. Do not launch the full multi-portal product until tenant, owner, and contractor experiences are completed and tested." "Body"

$body += Para "Status legend" "H2"
$body += Table @("Term", "Meaning") @(
    @("Ready", "Can be used with minor polish"),
    @("Mostly ready", "Main work done; fixes still needed"),
    @("Partly ready", "Exists but incomplete or risky"),
    @("Not ready", "Should not be in MVP yet")
) @(2200, 7160)

# VERSION 2
$body += PageBreak
$body += Para "VERSION 2 - Detailed Module-Wise Status Report" "H1" "0B2545"

$modules = @(
    @{ Name = "1. SaaS Account System"; Status = "Mostly ready"; Readiness = "70%"; Done = @("Accounts, memberships, plan limits", "Account switching", "Subscription status handling"); Pending = @("Full account isolation on all modules", "Workspace-scoped roles", "More automated tests") },
    @{ Name = "2. Public Website"; Status = "Mostly ready"; Readiness = "75%"; Done = @("Homepage, pricing page", "Public forms"); Pending = @("Content polish", "Final marketing alignment") },
    @{ Name = "3. Login and Password"; Status = "Mostly ready"; Readiness = "75%"; Done = @("Login, logout, password reset", "Error notifications"); Pending = @("Consolidate dual login paths", "Remove insecure default password behaviour") },
    @{ Name = "4. Registration and Signup"; Status = "Mostly ready"; Readiness = "70%"; Done = @("OTP registration", "Plan-based signup", "Account and subscription creation"); Pending = @("Full staging E2E test", "Clean separation from legacy approval flow") },
    @{ Name = "5. Billing and Subscriptions"; Status = "Mostly ready"; Readiness = "70%"; Done = @("Plans, add-ons, billing page", "Stripe Checkout and webhooks", "Addon purchase flow"); Pending = @("Live Stripe verification", "Webhook-only activation", "Failed payment UX") },
    @{ Name = "6. Super Admin"; Status = "Mostly ready"; Readiness = "70%"; Done = @("Manage plans and add-ons", "View accounts and subscriptions"); Pending = @("MRR/churn dashboard", "Audit trail for login-as") },
    @{ Name = "7. Dashboard"; Status = "Mostly ready"; Readiness = "70%"; Done = @("Role-aware dashboard", "Navigation"); Pending = @("Fully account-scoped counts", "Real tenant dashboard") },
    @{ Name = "8. Property Management"; Status = "Ready / Mostly ready"; Readiness = "80%"; Done = @("Full CRUD, quick create, soft delete", "UK address lookup, brochure", "Per-account duplicate address rule"); Pending = @("HTTP isolation tests") },
    @{ Name = "9. Contacts / Users"; Status = "Mostly ready"; Readiness = "75%"; Done = @("Contact CRUD, categories", "Linked properties view", "User profile"); Pending = @("Portal participant rules incomplete", "Letters and appointments tabs unfinished") },
    @{ Name = "10. Owner Groups"; Status = "Mostly ready"; Readiness = "70%"; Done = @("Group CRUD", "Property linking"); Pending = @("Owner portal journey incomplete") },
    @{ Name = "11. Tenancies"; Status = "Mostly ready"; Readiness = "75%"; Done = @("CRUD, rent ledger", "Tenant members"); Pending = @("Route-level isolation tests", "Dedicated tenant UX") },
    @{ Name = "12. Offers / Applicants"; Status = "Partly ready"; Readiness = "70%"; Done = @("Offer CRUD", "Status updates"); Pending = @("Not MVP-critical", "Pipeline incomplete") },
    @{ Name = "13. Compliance"; Status = "Partly ready"; Readiness = "65%"; Done = @("Compliance records on properties", "Dynamic forms"); Pending = @("Legal workflow automation", "UK statutory depth") },
    @{ Name = "14. Repairs and Maintenance"; Status = "Mostly ready"; Readiness = "80%"; Done = @("Raise and track issues", "Contractor quotes", "Finalize contractor workflow"); Pending = @("Isolation test coverage", "Email/queue verification") },
    @{ Name = "15. Work Orders"; Status = "Mostly ready"; Readiness = "75%"; Done = @("Create, PDF, email send"); Pending = @("Depends on repair hardening") },
    @{ Name = "16. Legacy Work-Order Invoices"; Status = "Partly ready"; Readiness = "55%"; Done = @("Generate from work order", "PDF and mark paid"); Pending = @("Overlaps new accounting - defer for MVP") },
    @{ Name = "17. Notes and Documents"; Status = "Mostly ready"; Readiness = "75%"; Done = @("Polymorphic attach", "Visibility flags"); Pending = @("File download security audit", "Portal visibility tests") },
    @{ Name = "18. File Uploads"; Status = "Partly ready"; Readiness = "65%"; Done = @("Upload, preview, download"); Pending = @("Cross-account file access risk", "Remove debug/public routes") },
    @{ Name = "19. Calendar / Events"; Status = "Mostly ready"; Readiness = "75%"; Done = @("FullCalendar", "Recurring instances", "Reminder scheduler"); Pending = @("Some routes missing account middleware") },
    @{ Name = "20. New Accounting (sys_*)"; Status = "Mostly ready"; Readiness = "75%"; Done = @("Sale/purchase invoices", "Receipts, payments, GL", "Statements and reports"); Pending = @("Scope for MVP", "Customer Stripe pay not production-ready") },
    @{ Name = "21. Old Finance"; Status = "Partly ready"; Readiness = "40%"; Done = @("Legacy modules still routed"); Pending = @("No account scoping - hide or retire") },
    @{ Name = "22. Estate Charges"; Status = "Partly ready"; Readiness = "65%"; Done = @("Charge CRUD"); Pending = @("Low MVP priority") },
    @{ Name = "23. Company / Branch / Staff"; Status = "Mostly ready"; Readiness = "70%"; Done = @("Agency structure", "Designations and permissions"); Pending = @("Landlord vs agent split enforcement", "Estate-agent phase") },
    @{ Name = "24. Roles and Permissions"; Status = "Partly ready"; Readiness = "60%"; Done = @("Spatie roles", "Staff overrides"); Pending = @("Most routes login-only", "Major launch risk") },
    @{ Name = "25. Portals"; Status = "Mixed"; Readiness = "15-55%"; Done = @("Contractor repairs", "Signed quotes", "Customer statements"); Pending = @("Tenant home blank", "Owner portal incomplete") },
    @{ Name = "26. Email / SMS / OTP"; Status = "Mostly ready"; Readiness = "70%"; Done = @("Templates", "OTP config", "Retry jobs"); Pending = @("Production credentials", "Remove test-sms route") },
    @{ Name = "27. Master Data Setup"; Status = "Mostly ready"; Readiness = "80%"; Done = @("Categories, types, statuses"); Pending = @("Low risk") },
    @{ Name = "28. Reports"; Status = "Partly ready"; Readiness = "55%"; Done = @("Financial reports", "Statements"); Pending = @("Unified ops reports", "SaaS analytics") },
    @{ Name = "29. Testing and QA"; Status = "Not ready"; Readiness = "25%"; Done = @("Some isolation tests", "Checklists exist"); Pending = @("Staging QA No-go", "CI pipeline missing") }
)

foreach ($m in $modules) {
    $body += ModuleBlock $m.Name $m.Status $m.Readiness $m.Done $m.Pending
}

# VERSION 3
$body += PageBreak
$body += Para "VERSION 3 - MVP Launch Readiness Document" "H1" "0B2545"

$body += Para "Executive verdict" "H2"
$body += Para "Current readiness for paid MVP launch: approximately 25-35%. The application is feature-rich but operationally and security-wise not launch-ready without completing the blockers below." "Body"

$body += Para "Recommended MVP definition (Landlord-first)" "H2"
foreach ($item in @(
    "View pricing and register",
    "Verify email (OTP)",
    "Pay via Stripe subscription",
    "Log in to own workspace",
    "Add properties within plan limit",
    "Manage tenancies and tenants",
    "Raise and track repairs",
    "Store documents and notes",
    "Issue basic rent invoices and view statements",
    "Manage billing and plan usage"
)) { $body += Bullet $item }

$body += Para "P0 launch blockers" "H2"
$body += Table @("#", "Item", "Status") @(
    @("1", "Remove/lock public routes (storage-link, command, helper, test-sms)", "Not done"),
    @("2", "Disable admin .env editing from UI", "Risk exists"),
    @("3", "Stripe webhook verified in staging", "Not tested"),
    @("4", "Production env: Stripe, mail, SMS", "Incomplete"),
    @("5", "Account isolation on all exposed routes", "Partial"),
    @("6", "Route-level permission enforcement", "Mostly missing"),
    @("7", "Staging deployment and full manual QA", "No-go"),
    @("8", "Clean database migrations on staging", "Drift reported"),
    @("9", "Queue worker and scheduler in production", "Not verified"),
    @("10", "Backup and rollback plan", "Docs only")
) @(600, 5760, 3000)

$body += Para "Go / No-Go criteria (all must be true)" "H2"
foreach ($item in @(
    "Staging E2E: register, pay, webhook, login, add property",
    "Account A cannot access Account B data via URL",
    "Property plan limit enforced",
    "Suspended/cancelled accounts blocked from business modules",
    "No public maintenance endpoints exposed",
    "Production Stripe test purchase succeeds",
    "Mail delivery confirmed",
    "Automated test suite passes in CI"
)) { $body += Bullet $item }
$body += Para "Current decision: NO-GO (per staging QA report)." "Body" "B91C1C" $true

$body += Para "Estimated effort to MVP" "H2"
$body += Table @("Workstream", "Estimate") @(
    @("Security hardening", "1-2 weeks"),
    @("Isolation and permissions pass", "2-3 weeks"),
    @("Stripe and staging UAT", "1 week"),
    @("Ops setup (env, queue, mail, deploy)", "1 week"),
    @("Test and CI fixes", "1 week"),
    @("Total focused effort", "6-8 weeks")
) @(5200, 4160)

$body += Para "Risks if launched today" "H2"
$body += Table @("Risk", "Impact") @(
    @("Cross-customer data leak", "Critical"),
    @("Billing without webhook", "Customers pay but cannot access"),
    @("Public maintenance routes", "Security breach"),
    @("Weak permissions", "Unauthorized admin access"),
    @("Untested mail/SMS", "OTP and notifications fail")
) @(4680, 4680)

# VERSION 4
$body += PageBreak
$body += Para "VERSION 4 - One-Page Executive Summary" "H1" "0B2545"

$body += Table @("Field", "Detail") @(
    @("Product", "UK property management SaaS (landlords + estate agents)"),
    @("Technology", "Laravel 11, Stripe subscriptions, multi-tenant accounts"),
    @("Overall MVP readiness", "~30% (operational + security + QA)"),
    @("QA decision", "No-go for staging launch")
) @(2800, 6560)

$body += Para "Strengths" "H2"
foreach ($item in @(
    "Broad CRM: properties, tenancies, repairs, documents, GL accounting",
    "SaaS foundation: accounts, plans, subscriptions, billing UI, webhooks",
    "Recent hardening: account scoping, property duplicate rules, isolation tests started"
)) { $body += Bullet $item }

$body += Para "Gaps" "H2"
foreach ($item in @(
    "Staging QA not completed",
    "Security: public debug routes, weak permissions, env editing from UI",
    "Portals: tenant landing empty; owner journey incomplete",
    "Testing: minimal coverage; no CI pipeline"
)) { $body += Bullet $item }

$body += Para "Recommendation" "H2"
$body += Table @("Decision", "Detail") @(
    @("Launch scope", "Landlord MVP only - not full multi-role platform"),
    @("Timeline", "6-8 weeks focused work after scope lock"),
    @("Do not", "Launch before isolation and Stripe UAT pass")
) @(2400, 6960)

$body += Para "Module snapshot" "H2"
$body += Table @("Green (include in MVP)", "Amber (fix first)", "Red (defer)") @(
    @("Properties", "Billing (test in staging)", "Tenant portal"),
    @("Tenancies", "Registration flow", "Legacy finance"),
    @("Repairs", "Permissions", "Estate agent branches"),
    @("Documents", "File security", "Advanced reports"),
    @("Basic accounting", "Dashboard scoping", "Letters/appointments")
) @(3120, 3120, 3120)

$body += Para "Ask from stakeholders" "H2"
foreach ($item in @(
    "Confirm Landlord-first MVP scope",
    "Approve 6-8 week hardening and UAT phase before launch",
    "Provide staging environment and Stripe test account for QA",
    "Accept deferral of tenant/owner portals to Phase 2"
)) { $body += Bullet $item }

# VERSION 5
$body += PageBreak
$body += Para "VERSION 5 - Presentation-Style Notes" "H1" "0B2545"
$body += Para "Slide-by-slide bullets for PowerPoint or Google Slides." "Body" "475569"

$slides = @(
    @{ Title = "Slide 1 - Title"; Bullets = @("ResiSquare Application Status and MVP Readiness", "Property Management SaaS | Stakeholder Update | 2026") },
    @{ Title = "Slide 2 - What is ResiSquare?"; Bullets = @("Cloud software for landlords and estate agents", "Manage properties, tenants, repairs, documents, invoices", "Subscription-based SaaS with Stripe payments", "England-first, GBP, UK addresses") },
    @{ Title = "Slide 3 - Overall status"; Bullets = @("Built: large working application (~80+ backend modules)", "SaaS: accounts, plans, billing - code complete, not fully tested", "Launch ready: ~30% (needs security, QA, ops)", "QA decision today: No-go for staging launch") },
    @{ Title = "Slide 4 - What works well (Green)"; Bullets = @("Property management", "Tenancy management", "Repairs and contractor quotes", "Work orders", "Notes and documents", "New accounting", "Pricing page and OTP registration") },
    @{ Title = "Slide 5 - What needs work (Amber)"; Bullets = @("Stripe billing - needs staging proof", "Account data isolation - partial", "Admin permissions - too weak", "Dashboard numbers - not fully account-scoped", "Contractor portal - basic only", "Calendar - needs security pass") },
    @{ Title = "Slide 6 - What to defer (Red)"; Bullets = @("Tenant self-service portal", "Owner/contact full portal", "Estate agent branches and staff seats", "Old finance modules", "Letters and appointments tabs", "Advanced SaaS analytics") },
    @{ Title = "Slide 7 - Security and launch blockers"; Bullets = @("Remove public debug routes", "Admin must not edit server secrets from UI", "Every customer's data must be fully separated", "Permissions on admin pages must be enforced", "Staging test: signup to pay to use product") },
    @{ Title = "Slide 8 - Recommended MVP scope"; Bullets = @("Include: signup, Stripe, properties, tenancies, repairs, documents, basic invoicing, billing", "Exclude for v1: tenant portal, owner portal, agency branch/staff SaaS, legacy finance") },
    @{ Title = "Slide 9 - Timeline (indicative)"; Bullets = @("Security + isolation: 2-3 weeks", "Stripe + staging UAT: 1 week", "Ops setup: 1 week", "Test + CI: 1 week", "Buffer + fixes: 1-2 weeks", "Total: 6-8 weeks to Landlord MVP go-live") },
    @{ Title = "Slide 10 - Recent progress"; Bullets = @("SaaS account tables and migrations", "Stripe checkout and webhook handling", "Account middleware on main modules", "Property duplicate protection per account", "Isolation tests started", "Login UX improvements", "Staging QA docs and checklists") },
    @{ Title = "Slide 11 - Testing status"; Bullets = @("Automated tests: small suite, some failures", "Manual staging checklist: 0 flows passed (blocked)", "No CI/CD pipeline yet", "Need two test accounts and full isolation + billing UAT") },
    @{ Title = "Slide 12 - Business recommendation"; Bullets = @("Approve Landlord-first MVP - fastest path to revenue", "Do not launch full platform until portals ready", "Invest 6-8 weeks in hardening before first paying customer", "Provide staging and Stripe test for joint UAT sign-off") },
    @{ Title = "Slide 13 - Success criteria for launch"; Bullets = @("Customer can register, pay, and use product same day", "Customer A never sees Customer B data", "Plan limits enforced", "Failed payment handled gracefully", "Production monitoring, backups, support process in place") },
    @{ Title = "Slide 14 - Q&A / Next steps"; Bullets = @("Confirm MVP scope with client", "Lock launch date after UAT pass", "Assign owner for staging environment", "Schedule weekly stakeholder status calls") }
)

foreach ($slide in $slides) {
    $body += Para $slide.Title "H2"
    foreach ($bullet in $slide.Bullets) { $body += Bullet $bullet }
}

# Email draft
$body += PageBreak
$body += Para "BONUS - Client Email Draft" "H1" "0B2545"
$body += Para "Subject: ResiSquare - Application Status and MVP Readiness Summary" "H2"
$body += Para "Hi," "Body"
$body += Para "Please find below a brief update on the ResiSquare application status." "Body"
$body += Para "Product: Property management SaaS for landlords and estate agents (UK-focused)." "Body"
$body += Para "Current state: The application has extensive functionality already built - including properties, tenancies, repairs, documents, accounting, and subscription billing with Stripe. This is not a prototype; it is a working system with significant development completed." "Body"
$body += Para "Launch readiness: We estimate approximately 30% ready for a safe paid MVP launch. The main gaps are security hardening, complete data isolation between customers, full Stripe/billing testing in staging, and quality assurance. Our staging QA report currently shows No-go." "Body"
$body += Para "Recommendation: Launch a Landlord-focused MVP first (signup, billing, properties, tenancies, repairs, documents, basic invoicing). Defer full tenant/owner portals and estate-agent advanced features to a later phase." "Body"
$body += Para "Timeline: Approximately 6-8 weeks of focused work after scope confirmation." "Body"
$body += Para "Happy to walk through the detailed module report or presentation slides if helpful." "Body"
$body += Para "Best regards," "Body"
$body += Para "[Your name]" "Body"

$document = "<?xml version=`"1.0`" encoding=`"UTF-8`" standalone=`"yes`"?><w:document xmlns:w=`"http://schemas.openxmlformats.org/wordprocessingml/2006/main`"><w:body>$($body -join '')<w:sectPr><w:pgSz w:w=`"12240`" w:h=`"15840`"/><w:pgMar w:top=`"1440`" w:right=`"1440`" w:bottom=`"1440`" w:left=`"1440`" w:header=`"708`" w:footer=`"708`" w:gutter=`"0`"/></w:sectPr></w:body></w:document>"

$contentTypes = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>
'@

$rels = @'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
'@

Set-Content -LiteralPath (Join-Path $tmp "[Content_Types].xml") -Value $contentTypes -Encoding UTF8
Set-Content -LiteralPath (Join-Path $tmp "_rels\.rels") -Value $rels -Encoding UTF8
Set-Content -LiteralPath (Join-Path $tmp "word\document.xml") -Value $document -Encoding UTF8

if (Test-Path $outFile) { Remove-Item -LiteralPath $outFile -Force }
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($tmp, $outFile)
Remove-Item -LiteralPath $tmp -Recurse -Force
Write-Output $outFile
