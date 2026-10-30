$ErrorActionPreference = 'Stop'
$out = 'C:\Users\abc\Desktop\faaz-dev_resisqaure\devresi_square\demo-video'
$clips = Join-Path $out 'clips'
$ffmpeg = Join-Path $out 'tools\ffmpeg.exe'
$shots = 'C:\Users\abc\AppData\Local\Temp\cursor\screenshots'
$slides = Get-Content (Join-Path $out 'manifest.json') -Raw | ConvertFrom-Json

function Wrap-Caption([string]$text, [int]$width = 72) {
    $lines = New-Object System.Collections.Generic.List[string]
    $cur = ''
    foreach ($word in ($text -split ' ')) {
        if ($cur.Length -gt 0 -and ($cur.Length + 1 + $word.Length) -gt $width) {
            $lines.Add($cur)
            $cur = $word
        } elseif ($cur.Length -eq 0) {
            $cur = $word
        } else {
            $cur = "$cur $word"
        }
    }
    if ($cur) { $lines.Add($cur) }
    return ($lines -join "`n")
}

$list = Join-Path $clips 'concat.txt'
if (Test-Path $list) { Remove-Item $list }
$n = 0
foreach ($slide in $slides) {
    $n++
    $id = $slide.id
    $image = $slide.image
    if ($slide.image -like '*69-tenant-desk-complete-email.png') {
        $image = Join-Path $shots '68-aisha-paid-email.png'
    }
    if (-not (Test-Path $image)) { throw "Missing $image" }
    $secs = [string]$slide.seconds
    $end = [TimeSpan]::FromSeconds([double]$slide.seconds).ToString('hh\:mm\:ss\,fff')
    $body = Wrap-Caption $slide.caption 96
    @"
1
00:00:00,000 --> $end
$body
"@ | Set-Content -Path (Join-Path $clips "$id.srt") -Encoding utf8

    $style = 'FontName=Arial\,FontSize=12\,PrimaryColour=&H00FFFFFF\,BackColour=&HCC111827\,BorderStyle=4\,Alignment=2\,MarginV=22\,Outline=0'
    $fit = 'scale=1920:1080:force_original_aspect_ratio=decrease,pad=1920:1080:(ow-iw)/2:(oh-ih)/2:color=0x0f172a'
    $srtEsc = ((Join-Path $clips "$id.srt") -replace '\\','/') -replace ':','\:'
    $sub = "subtitles='$srtEsc':force_style='$style'"
    if ([string]$slide.email -eq '1') {
        $vf = "crop=iw:trunc(ih*46/100/2)*2:0:0,$fit,$sub"
    } else {
        $vf = "$fit,$sub"
    }
    $mp4 = Join-Path $clips "$id.mp4"
    Write-Host "ENCODE $id"
    & $ffmpeg -y -hide_banner -loglevel error -loop 1 -framerate 30 -i $image -i $slide.wav -t $secs -vf $vf -c:v libx264 -preset veryfast -crf 20 -pix_fmt yuv420p -c:a aac -b:a 160k -ar 44100 -ac 2 -af "apad=whole_dur=${secs}" $mp4
    if ($LASTEXITCODE -ne 0) { throw "ffmpeg failed on $id exit $LASTEXITCODE" }
    Add-Content -Path $list -Value ("file '{0}'" -f $mp4.Replace('\','/'))
}

$final = Join-Path $out 'Resisquare-repair-walkthrough.mp4'
& $ffmpeg -y -hide_banner -loglevel error -f concat -safe 0 -i $list -c copy $final
if ($LASTEXITCODE -ne 0) { throw "concat failed $LASTEXITCODE" }
$item = Get-Item $final
Write-Host ("DONE {0:N1} MB" -f ($item.Length/1MB))
