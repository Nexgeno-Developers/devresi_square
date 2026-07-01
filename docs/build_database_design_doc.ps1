$ErrorActionPreference = "Stop"

$outFile = Join-Path $PSScriptRoot "resisquare_saas_database_design.docx"
$tmp = Join-Path $PSScriptRoot "_docx_build"
if (Test-Path $tmp) { Remove-Item -LiteralPath $tmp -Recurse -Force }
New-Item -ItemType Directory -Force -Path $tmp, (Join-Path $tmp "_rels"), (Join-Path $tmp "word") | Out-Null

function X([string]$s) {
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
    $b = if ($bold -or $style -in @("Title","H1","H2")) { "<w:b/>" } else { "" }
    return "<w:p><w:pPr><w:spacing w:before=`"$before`" w:after=`"$after`" w:line=`"276`" w:lineRule=`"auto`"/></w:pPr><w:r><w:rPr>$b<w:color w:val=`"$color`"/><w:sz w:val=`"$size`"/><w:szCs w:val=`"$size`"/><w:rFonts w:ascii=`"Calibri`" w:hAnsi=`"Calibri`"/></w:rPr><w:t>$(X $text)</w:t></w:r></w:p>"
}

function Cell([string]$text, [int]$width, [string]$fill = "FFFFFF", [bool]$bold = $false, [string]$color = "111827") {
    $b = if ($bold) { "<w:b/>" } else { "" }
    return "<w:tc><w:tcPr><w:tcW w:w=`"$width`" w:type=`"dxa`"/><w:shd w:fill=`"$fill`"/><w:tcMar><w:top w:w=`"90`" w:type=`"dxa`"/><w:left w:w=`"110`" w:type=`"dxa`"/><w:bottom w:w=`"90`" w:type=`"dxa`"/><w:right w:w=`"110`" w:type=`"dxa`"/></w:tcMar></w:tcPr><w:p><w:pPr><w:spacing w:after=`"20`"/></w:pPr><w:r><w:rPr>$b<w:color w:val=`"$color`"/><w:sz w:val=`"18`"/><w:szCs w:val=`"18`"/><w:rFonts w:ascii=`"Calibri`" w:hAnsi=`"Calibri`"/></w:rPr><w:t>$(X $text)</w:t></w:r></w:p></w:tc>"
}

function Table($headers, $rows, $widths, $rowFills = @{}) {
    $grid = ($widths | ForEach-Object { "<w:gridCol w:w=`"$_`"/>" }) -join ""
    $xml = "<w:tbl><w:tblPr><w:tblStyle w:val=`"TableGrid`"/><w:tblW w:w=`"9360`" w:type=`"dxa`"/><w:tblInd w:w=`"120`" w:type=`"dxa`"/><w:tblBorders><w:top w:val=`"single`" w:sz=`"4`" w:color=`"CBD5E1`"/><w:left w:val=`"single`" w:sz=`"4`" w:color=`"CBD5E1`"/><w:bottom w:val=`"single`" w:sz=`"4`" w:color=`"CBD5E1`"/><w:right w:val=`"single`" w:sz=`"4`" w:color=`"CBD5E1`"/><w:insideH w:val=`"single`" w:sz=`"4`" w:color=`"CBD5E1`"/><w:insideV w:val=`"single`" w:sz=`"4`" w:color=`"CBD5E1`"/></w:tblBorders></w:tblPr><w:tblGrid>$grid</w:tblGrid>"
    $xml += "<w:tr>" + (($headers | ForEach-Object -Begin {$i=0} -Process { $c = Cell $_ $widths[$i] "E8EEF5" $true; $i++; $c }) -join "") + "</w:tr>"
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

$existingModules = @(
    @("Core identity", "users, roles, permissions, model_has_permissions, model_has_roles, role_has_permissions, staff, designations, branches, companies"),
    @("Property portfolio", "properties, countries, currencies, local_authority_groups, local_authorities, station_names, school_names, religious_places, offers, charges, charges_items"),
    @("Owners and contacts", "owner_group, owner_group_contacts, user_details, bank_details, user_categories, property_responsibilities, property_manager_tenancy"),
    @("Tenancy", "tenancies, tenant_members, tenancy_types, tenancy_sub_statuses"),
    @("Repair and work orders", "repair_categories, repair_issues, repair_photos, repair_assignments, repair_histories, repair_issue_users, repair_issue_property_managers, repair_issue_contractor_assignments, work_orders, work_order_items, job_types"),
    @("Sales and transactions", "invoices, invoice_items, invoice_statuses, tax_rates, transaction_categories, transactions, payment_methods, bank_accounts"),
    @("Accounting / GL", "accounting_headers, sys_* accounting tables, gl_accounts, gl_journals, gl_journal_lines, gl_account_balances, gl_period_closes, bank_reconciliations, gl_audit_logs, fixed_assets"),
    @("Events and activity", "events, event_types, event_sub_types, event_instances, event_instance_changes, event_reminders, eventables, audits"),
    @("Documents and notes", "document_types, documents, document_sequences, notes, note_types, credit_notes, debit_note_refunds, credit_note_refunds, note_applications"),
    @("System and messaging", "uploads, cache, sessions, form_submissions, notification_logs")
)

$newTables = @(
    @("staff_contacts", "New SaaS table", "Multiple email and phone records per staff member, with label and primary flag."),
    @("registrations", "New SaaS table", "Pre-approval registration queue for landlord, owner, freelancing agent, contractor, and estate agent onboarding."),
    @("otp_configurations", "New SaaS table", "Configurable OTP provider activation settings seeded with supported providers."),
    @("sms_templates", "New SaaS table", "Reusable SMS bodies for verification, password reset, and account-opening messages."),
    @("designation_has_permissions", "New SaaS pivot", "Maps designations directly to permissions after staff role_id removal."),
    @("company_owner_transfers", "New SaaS audit table", "Tracks company owner changes with old owner, new owner, actor, note, and transfer timestamp.")
)

$newTableColumns = @(
    @("staff_contacts", "id, staff_id, type, value, label, is_primary, created_at, updated_at"),
    @("registrations", "id, first_name, last_name, email, phone, type, verify_via, otp_code, otp_expires_at, otp_verified_at, email_verification_token, email_verified_at, phone_otp, phone_otp_expires_at, phone_verified_at, status, approved_by, approved_at, rejected_at, user_id, ip, ref_url, created_at, updated_at"),
    @("otp_configurations", "id, type, value, created_at, updated_at"),
    @("sms_templates", "id, identifier, sms_body, template_id, status, created_at, updated_at"),
    @("designation_has_permissions", "designation_id, permission_id, created_at, updated_at"),
    @("company_owner_transfers", "id, company_id, old_owner_user_id, new_owner_user_id, transferred_by, note, transferred_at, created_at, updated_at")
)

$addedColumns = @(
    @("companies", "owner_user_id", "foreignId nullable -> users.id", "Links company to current owner user."),
    @("companies", "registration_number", "string nullable", "Company registration reference."),
    @("companies", "registered_address", "text nullable", "Legal registered office address."),
    @("companies", "communication_address", "text nullable", "Operational correspondence address."),
    @("companies", "emails, phones", "json nullable", "Multiple company contact values."),
    @("companies", "logo_path, stamp_path", "string nullable", "Company branding assets."),
    @("companies", "vat_number, website", "string nullable", "Tax and public web metadata."),
    @("companies", "social_media, services", "json nullable", "Profile channels and offered services."),
    @("branches", "company_id", "foreignId nullable -> companies.id", "Associates branch with SaaS company/agency."),
    @("branches", "is_main_head_office", "boolean default false", "Marks primary head office branch."),
    @("branches", "address_line_1, address_line_2, county", "string nullable", "Expanded branch address fields."),
    @("branches", "user_email, user_phone", "string nullable", "Primary branch contact values."),
    @("branches", "alternate_phone, alternate_email", "string nullable", "Secondary branch contact values."),
    @("branches", "social_media", "json nullable", "Branch-level social channels."),
    @("users", "profile_picture", "string nullable", "User avatar/profile image path."),
    @("staff", "branch_id", "foreignId nullable -> branches.id", "Places staff under a branch."),
    @("staff", "permissions_customized", "boolean default false", "Flags staff permission overrides."),
    @("property_responsibilities", "responsibility_type", "string nullable", "Classifies responsibility assignment type."),
    @("user_details", "primary_email, primary_phone", "string nullable", "Primary values derived from JSON contact arrays."),
    @("repair_issue_contractor_assignments", "quote_token", "string(80) nullable unique", "Public quote submission token."),
    @("repair_issue_contractor_assignments", "quote_requested_at, quote_submitted_at", "timestamp nullable", "Quote lifecycle timestamps."),
    @("repair_issue_contractor_assignments", "contractor_availability_options", "json nullable", "Multiple contractor availability choices."),
    @("repair_issue_contractor_assignments", "consultant_name, consultant_phone", "string nullable", "Contractor consultant contact."),
    @("repair_issue_contractor_assignments", "tentative_start_date, tentative_end_date", "date nullable", "Proposed execution window."),
    @("repair_issue_contractor_assignments", "quote_notes", "text nullable", "Contractor quote notes.")
)

$modified = @(
    @("registrations.type", "Enum expanded", "Adds estate_agent to landlord/owner/freelancing_agent/contractor."),
    @("registrations.email", "Unique dropped later", "Supports repeated/pending registration flows where required."),
    @("staff.role_id", "Removed", "Permission inheritance moves from staff role to user designation permissions."),
    @("property_responsibilities extra fields", "Nullable", "Later migration relaxes extra responsibility fields to support partial SaaS workflows."),
    @("permissions", "Seeded values", "Adds manage own company, transfer company owner, and download property brochure.")
)

$body = @()
$body += Para "ResiSquare SaaS Database Design" "Title" "0B2545"
$body += Para "Existing schema inventory with color-filled SaaS additions from the latest migrations." "Body" "475569"
$body += Para "Legend" "H1"
$body += Table @("Color", "Meaning") @(
    @("Green fill", "New table introduced for SaaS registration, OTP, staff contact, permission, or ownership workflow."),
    @("Yellow fill", "New column added to an existing table."),
    @("Orange fill", "Existing field or constraint changed."),
    @("White fill", "Existing schema/module inventory.")
) @(1800,7560) @{0="D9EAD3";1="FFF2CC";2="FCE4D6"}

$body += Para "Scope And Baseline" "H1"
$body += Para "Baseline existing schema is taken from Laravel migrations before the 2026-05-14 SaaS layer. The highlighted additions are taken from migrations dated 2026-05-14 through 2026-05-26. This document is intended for database design review, migration planning, and Lovable/SaaS feature alignment."

$body += Para "Existing Database Modules" "H1"
$body += Table @("Module", "Existing tables") $existingModules @(2200,7160)

$body += Para "New SaaS Tables" "H1"
$body += Table @("Table", "Status", "Purpose") $newTables @(2300,1900,5160) @{0="D9EAD3";1="D9EAD3";2="D9EAD3";3="D9EAD3";4="D9EAD3";5="D9EAD3"}

$body += Para "Columns In New Tables" "H2"
$body += Table @("New table", "Columns") $newTableColumns @(2300,7060) @{0="D9EAD3";1="D9EAD3";2="D9EAD3";3="D9EAD3";4="D9EAD3";5="D9EAD3"}

$body += Para "Added Columns In Existing Tables" "H1"
$body += Table @("Existing table", "Added column(s)", "Type / constraint", "Purpose") $addedColumns @(2100,2500,2200,2560) @{
    0="FFF2CC";1="FFF2CC";2="FFF2CC";3="FFF2CC";4="FFF2CC";5="FFF2CC";6="FFF2CC";7="FFF2CC";8="FFF2CC";9="FFF2CC";10="FFF2CC";11="FFF2CC";12="FFF2CC";13="FFF2CC";14="FFF2CC";15="FFF2CC";16="FFF2CC";17="FFF2CC";18="FFF2CC";19="FFF2CC";20="FFF2CC";21="FFF2CC";22="FFF2CC";23="FFF2CC";24="FFF2CC"
}

$body += Para "Changed Or Removed Items" "H1"
$body += Table @("Object", "Change", "Reason / impact") $modified @(2600,2200,4560) @{0="FCE4D6";1="FCE4D6";2="FCE4D6";3="FCE4D6";4="FCE4D6"}

$body += Para "Relationship Notes" "H1"
$body += Para "companies.owner_user_id -> users.id; branches.company_id -> companies.id; staff.branch_id -> branches.id; company_owner_transfers links company_id, old_owner_user_id, new_owner_user_id, and transferred_by back to companies/users; designation_has_permissions links designations to permissions; staff_contacts belongs to staff; registrations can link to users after approval."
$body += Para "Recommended Review Checklist" "H1"
$body += Table @("Check", "Status") @(
    @("Confirm whether duplicate sms_templates migrations should be consolidated or renamed before production migration runs.", "Open"),
    @("Confirm registration email uniqueness rule after drop_unique_email_from_registrations migration.", "Open"),
    @("Add indexes for frequent lookups: registrations.status, registrations.verify_via, staff_contacts.staff_id/type, company_owner_transfers.company_id/transferred_at, repair quote_token.", "Recommended"),
    @("Confirm foreign keys for approved_by and user_id on registrations if referential enforcement is required.", "Recommended"),
    @("Backfill branch/company ownership data before enabling tenant/company-scoped permissions.", "Required before SaaS go-live")
) @(6500,2860)

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
