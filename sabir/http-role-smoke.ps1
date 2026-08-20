param(
    [string]$BaseUrl = 'http://127.0.0.1:8000',
    [string]$Password = 'Password123!',
    [int]$MaxLinksPerRole = 120,
    [string]$RoleFilter = ''
)

$ErrorActionPreference = 'Stop'

$users = @(
    @{ role = 'Super Admin'; email = 'admin@resisquare.test' },
    @{ role = 'Landlord Owner'; email = 'landlord.owner@resisquare.test' },
    @{ role = 'Estate Agent Owner'; email = 'estate.owner@resisquare.test' },
    @{ role = 'Estate Agent Staff'; email = 'estate.staff@resisquare.test' },
    @{ role = 'Landlord Contact'; email = 'landlord.contact@resisquare.test' },
    @{ role = 'Tenant'; email = 'tenant@resisquare.test' },
    @{ role = 'Contractor'; email = 'contractor@resisquare.test' },
    @{ role = 'Property Manager'; email = 'property.manager@resisquare.test' }
)

$unsafePathPattern = '(?i)(logout|destroy|delete|remove|clear-cache|optimize-clear|storage-link|test-sms|test/smtp|download|export|generate|send|approve|reject|cancel|webhook)'

if ($RoleFilter) {
    $users = @($users | Where-Object { $_.role -eq $RoleFilter })
}

function Get-PageTitle([string]$Content) {
    $match = [regex]::Match($Content, '<title[^>]*>(.*?)</title>', 'IgnoreCase,Singleline')
    if ($match.Success) {
        return ([System.Net.WebUtility]::HtmlDecode($match.Groups[1].Value) -replace '\s+', ' ').Trim()
    }
    return ''
}

function Get-Outcome([int]$Status, [string]$FinalUrl, [string]$Content) {
    if ($Status -ge 500) { return 'server_error' }
    if ($Status -eq 404) { return 'not_found' }
    if ($Status -eq 403) { return 'forbidden' }
    if ($Status -ge 400) { return 'http_error' }
    if ($FinalUrl -match '/login(?:\?|$)') { return 'redirected_to_login' }
    if ($Content -match '(?i)(Whoops, looks like something went wrong|Symfony Exception|Fatal error|Internal Server Error)') {
        return 'error_page'
    }
    return 'ok'
}

function Invoke-SafeGet([string]$Url, $Session) {
    try {
        $response = Invoke-WebRequest -UseBasicParsing -Uri $Url -WebSession $Session -MaximumRedirection 10
        return @{
            status = [int]$response.StatusCode
            final_url = $response.BaseResponse.ResponseUri.AbsoluteUri
            content = [string]$response.Content
        }
    } catch {
        $status = 0
        $finalUrl = $Url
        $content = $_.Exception.Message
        if ($_.Exception.Response) {
            $status = [int]$_.Exception.Response.StatusCode
            if ($_.Exception.Response.ResponseUri) {
                $finalUrl = $_.Exception.Response.ResponseUri.AbsoluteUri
            }
        }
        return @{ status = $status; final_url = $finalUrl; content = $content }
    }
}

function Get-SafeLinks([string]$Content, [string]$PageUrl) {
    $links = New-Object System.Collections.Generic.List[object]
    $seen = @{}
    $matches = [regex]::Matches($Content, '<a\b[^>]*href\s*=\s*["'']([^"'']+)["''][^>]*>(.*?)</a>', 'IgnoreCase,Singleline')
    foreach ($match in $matches) {
        $href = [System.Net.WebUtility]::HtmlDecode($match.Groups[1].Value).Trim()
        if (-not $href -or $href.StartsWith('#') -or $href -match '^(?i)(javascript:|mailto:|tel:)') { continue }
        try { $uri = [Uri]::new([Uri]$PageUrl, $href) } catch { continue }
        if ($uri.GetLeftPart([System.UriPartial]::Authority) -ne ([Uri]$BaseUrl).GetLeftPart([System.UriPartial]::Authority)) { continue }
        if ($uri.PathAndQuery -match $unsafePathPattern) { continue }
        $normalized = $uri.GetLeftPart([System.UriPartial]::Path)
        if ($uri.Query) { $normalized += $uri.Query }
        if ($seen.ContainsKey($normalized)) { continue }
        $seen[$normalized] = $true
        $text = [regex]::Replace($match.Groups[2].Value, '<[^>]+>', ' ')
        $text = ([System.Net.WebUtility]::HtmlDecode($text) -replace '\s+', ' ').Trim()
        $links.Add(@{ url = $normalized; text = $text })
    }
    return $links
}

$results = New-Object System.Collections.Generic.List[object]

foreach ($user in $users) {
    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $loginPage = Invoke-SafeGet "$BaseUrl/login" $session
    $tokenMatch = [regex]::Match($loginPage.content, 'name=["'']_token["'']\s+value=["'']([^"'']+)["'']', 'IgnoreCase')

    if (-not $tokenMatch.Success) {
        $results.Add([pscustomobject]@{
            role = $user.role; email = $user.email; source = 'login'; link_text = ''
            requested_url = "$BaseUrl/login"; status = $loginPage.status; final_url = $loginPage.final_url
            title = Get-PageTitle $loginPage.content; outcome = 'csrf_token_missing'
        })
        continue
    }

    try {
        $loginResponse = Invoke-WebRequest -UseBasicParsing -Uri "$BaseUrl/login" -Method Post -Body @{
            _token = $tokenMatch.Groups[1].Value
            email = $user.email
            password = $Password
            remember = 'on'
        } -WebSession $session -MaximumRedirection 10
        $landing = @{
            status = [int]$loginResponse.StatusCode
            final_url = $loginResponse.BaseResponse.ResponseUri.AbsoluteUri
            content = [string]$loginResponse.Content
        }
    } catch {
        $landing = @{ status = 0; final_url = "$BaseUrl/login"; content = $_.Exception.Message }
        if ($_.Exception.Response) { $landing.status = [int]$_.Exception.Response.StatusCode }
    }

    $results.Add([pscustomobject]@{
        role = $user.role; email = $user.email; source = 'login'; link_text = ''
        requested_url = "$BaseUrl/login"; status = $landing.status; final_url = $landing.final_url
        title = Get-PageTitle $landing.content; outcome = Get-Outcome $landing.status $landing.final_url $landing.content
    })

    $links = @(Get-SafeLinks $landing.content $landing.final_url | Select-Object -First $MaxLinksPerRole)
    foreach ($link in $links) {
        $page = Invoke-SafeGet $link.url $session
        $results.Add([pscustomobject]@{
            role = $user.role; email = $user.email; source = 'landing_link'; link_text = $link.text
            requested_url = $link.url; status = $page.status; final_url = $page.final_url
            title = Get-PageTitle $page.content; outcome = Get-Outcome $page.status $page.final_url $page.content
        })
    }
}

$outputName = 'http-role-smoke-results.json'
if ($RoleFilter) {
    $roleSlug = ($RoleFilter.ToLowerInvariant() -replace '[^a-z0-9]+', '-').Trim('-')
    $outputName = "http-role-smoke-results-$roleSlug.json"
}
$outputPath = Join-Path $PSScriptRoot $outputName
$results | ConvertTo-Json -Depth 5 | Set-Content -Encoding UTF8 $outputPath

$summary = $results | Group-Object role, outcome | ForEach-Object {
    [pscustomobject]@{ role_outcome = $_.Name; count = $_.Count }
}
$summary | Format-Table -AutoSize
Write-Output "Results: $outputPath"
