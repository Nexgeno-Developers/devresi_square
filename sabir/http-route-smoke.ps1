param(
    [string]$BaseUrl = 'http://127.0.0.1:8000',
    [string]$Email = 'admin@resisquare.test',
    [string]$Password = 'Password123!'
)

$ErrorActionPreference = 'Stop'
$unsafePattern = '(?i)(logout|destroy|delete|remove|clear|optimize|storage-link|test|debug|download|export|generate|send|approve|reject|cancel|webhook|retry|impersonat|login-as)'

function Get-PageTitle([string]$Content) {
    $match = [regex]::Match($Content, '<title[^>]*>(.*?)</title>', 'IgnoreCase,Singleline')
    if ($match.Success) {
        return ([System.Net.WebUtility]::HtmlDecode($match.Groups[1].Value) -replace '\s+', ' ').Trim()
    }
    return ''
}

function Invoke-SmokeGet([string]$Url, $Session) {
    try {
        $response = Invoke-WebRequest -UseBasicParsing -Uri $Url -WebSession $Session -MaximumRedirection 10
        $content = [string]$response.Content
        $outcome = 'ok'
        if ($response.BaseResponse.ResponseUri.AbsolutePath -match '/login$') { $outcome = 'redirected_to_login' }
        if ($content -match '(?i)(Whoops, looks like something went wrong|Symfony Exception|Fatal error|Internal Server Error)') { $outcome = 'error_page' }
        return [pscustomobject]@{
            status = [int]$response.StatusCode
            final_url = $response.BaseResponse.ResponseUri.AbsoluteUri
            title = Get-PageTitle $content
            outcome = $outcome
        }
    } catch {
        $status = 0
        $finalUrl = $Url
        if ($_.Exception.Response) {
            $status = [int]$_.Exception.Response.StatusCode
            if ($_.Exception.Response.ResponseUri) { $finalUrl = $_.Exception.Response.ResponseUri.AbsoluteUri }
        }
        $outcome = if ($status -ge 500) { 'server_error' } elseif ($status -eq 404) { 'not_found' } elseif ($status -eq 403) { 'forbidden' } else { 'request_error' }
        return [pscustomobject]@{ status = $status; final_url = $finalUrl; title = $_.Exception.Message; outcome = $outcome }
    }
}

$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$loginPage = Invoke-WebRequest -UseBasicParsing -Uri "$BaseUrl/login" -WebSession $session
$token = [regex]::Match($loginPage.Content, 'name=["'']_token["'']\s+value=["'']([^"'']+)["'']', 'IgnoreCase').Groups[1].Value
$landing = Invoke-WebRequest -UseBasicParsing -Uri "$BaseUrl/login" -Method Post -Body @{
    _token = $token
    email = $Email
    password = $Password
    remember = 'on'
} -WebSession $session -MaximumRedirection 10

if ($landing.BaseResponse.ResponseUri.AbsolutePath -match '/login$') {
    throw "Login failed for $Email"
}

$routeJson = php artisan route:list --json --except-vendor | Out-String
if ($LASTEXITCODE -ne 0) { throw 'Unable to obtain Laravel route list.' }
$routes = $routeJson | ConvertFrom-Json
$targets = $routes | Where-Object {
    $_.method -match '(^|\|)GET(\||$)' -and
    $_.uri -notmatch '\{' -and
    (($_.uri + ' ' + $_.name + ' ' + $_.action) -notmatch $unsafePattern)
} | Sort-Object uri -Unique

$results = New-Object System.Collections.Generic.List[object]
foreach ($route in $targets) {
    $url = if ($route.uri -eq '/') { "$BaseUrl/" } else { "$BaseUrl/$($route.uri.TrimStart('/'))" }
    $page = Invoke-SmokeGet $url $session
    $results.Add([pscustomobject]@{
        method = $route.method
        uri = $route.uri
        name = $route.name
        action = $route.action
        requested_url = $url
        status = $page.status
        final_url = $page.final_url
        title = $page.title
        outcome = $page.outcome
    })
}

$outputPath = Join-Path $PSScriptRoot 'http-route-smoke-results.json'
$results | ConvertTo-Json -Depth 5 | Set-Content -Encoding UTF8 $outputPath
$results | Group-Object outcome | Sort-Object Name | Select-Object Name,Count | Format-Table -AutoSize
Write-Output "ROUTES_TESTED=$($results.Count)"
Write-Output "Results: $outputPath"

