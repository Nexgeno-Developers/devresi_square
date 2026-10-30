$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Speech

$shots = 'C:\Users\abc\AppData\Local\Temp\cursor\screenshots'
$out = 'C:\Users\abc\Desktop\faaz-dev_resisqaure\devresi_square\demo-video'
$clips = Join-Path $out 'clips'
New-Item -ItemType Directory -Force -Path $clips | Out-Null

# file, emailCrop 0/1, spoken caption (also the subtitle)
$slides = @(
@('01-landlord-signup-form.png','0','A landlord starts on Resisquare. Priya Sharma creates an account, accepts the terms, and chooses the landlord plan.'),
@('02-stripe-trial-checkout.png','0','The paywall is Stripe in test mode. A successful demo card starts a seven day trial. The card is saved, and nothing is charged today.'),
@('03-landlord-billing-after-trial.png','0','The trial is live. Priya is asked to add her first property before she can use the workspace.'),
@('04-property-owner-step.png','0','She searches the demo postcode, S W 1 A 1 A A, and chooses 1 The Mall in London.'),
@('05-lead-tenant-invite.png','0','Next she adds the lead tenant. James Okonkwo, his email, phone, rent of one thousand four hundred and fifty pounds, and a twelve month term.'),
@('05b-invite-household-button.png','0','Invite household sends the tenancy to James. If he is new, the email asks him to create a password. If he already has an account, he just signs in.'),
@('06-property-added-invite-sent.png','0','The property is saved and the portal invite has gone out.'),
@('07-tenant-invite-email.png','1','This is James inbox. The invite email is the way into the tenant portal.'),
@('08-tenant-set-password.png','0','James sets a password from that link and accepts the terms.'),
@('09-tenant-confirm-tenancy.png','0','He checks the tenancy the landlord entered: the address, the rent, the deposit, and the move in date.'),
@('10-tenant-tenancy-confirmed.png','0','He confirms it. The tenancy is now active on both sides.'),
@('11-tenant-inapp-invite-notification.png','0','The same invite also appears in the app. Every important step sends both an email and an in app notification.'),
@('12-tenant-raise-repair.png','0','From maintenance, James raises a repair. The kitchen tap is dripping. He adds a photo and says he is free on Tuesday morning.'),
@('13-tenant-repair-submitted.png','0','The repair is in. Reference R E S I S Q R P R 23. The landlord is notified straight away.'),
@('14-tenant-three-repairs.png','0','James reports two more, so we can show every route. A sticking bedroom door, and a hallway light that flickers.'),
@('15-contractor-signup.png','0','Case one uses a contractor already on the marketplace. Aisha Khan signs up, accepts the contractor terms, and verifies her email.'),
@('16-contractor-business.png','0','She is a sole trader, covering general repairs and plumbing, within twenty five miles of the same demo postcode.'),
@('17-contractor-documents.png','0','She uploads photo I D and public liability insurance of two million pounds, valid to December 2027.'),
@('18-contractor-pending-review.png','0','Jobs stay locked until a super admin approves the documents.'),
@('19-admin-review-aisha.png','0','Production Admin opens the review queue, checks the files, and approves the documents and both trades.'),
@('20-admin-aisha-approved.png','0','Aisha is approved for general repairs and plumbing. C I S is recorded as net, twenty percent. The platform does not deduct C I S itself.'),
@('21-contractor-approved-email.png','1','Her inbox confirms it. Landlords can now send her work.'),
@('22-landlord-repair-notifications.png','0','Back with Priya. The bell shows three new repair reports, one for each issue James raised.'),
@('23-landlord-repair-list.png','0','On the repair, she can find a contractor, invite her own, or hand the job to the Resisquare team.'),
@('24-landlord-pick-aisha.png','0','She picks Aisha, sets the job as routine, and shows a budget of one hundred and eighty pounds.'),
@('25-landlord-job-sent-to-aisha.png','0','The request is sent. Aisha has until Monday to reply. Other contractors were left unticked.'),
@('26-contractor-job-email.png','1','Aisha email says London S W 1 A only. Before she is accepted she never sees the full address or the tenant phone number.'),
@('27-contractor-job-inbox.png','0','The same job is waiting in her jobs list, with the tenant availability and a decline option if she cannot take it.'),
@('28-contractor-quote-form.png','0','She quotes a fixed price of one hundred and forty five pounds, materials included, and accepts Tuesday morning, the slot James offered.'),
@('29-contractor-offer-sent.png','0','The offer is sent. She could have proposed a different time. Here she agrees to the tenant time.'),
@('30-landlord-offer-email.png','1','Priya receives the offer by email as well as in the app.'),
@('31-landlord-review-quote.png','0','On the repair she compares the price, the visit, and the note. Several contractors can quote. She accepts the one she wants.'),
@('32-landlord-confirm-accept.png','0','Accepting shares the full address, access notes, and tenant contact, and tells James who is coming.'),
@('33-landlord-job-accepted.png','0','The job is scheduled. Visit Tuesday at nine, and the tenant time is confirmed.'),
@('35-contractor-instructed-address.png','0','Only now does Aisha see 1 The Mall and James phone number. She saves demo bank details so she can invoice later.'),
@('36-contractor-marked-complete.png','0','She starts the job, then marks it complete with a photo and a note that the drip has stopped.'),
@('37-landlord-confirm-complete.png','0','Priya reviews that update and confirms the work is done.'),
@('38-landlord-confirmed-and-rated.png','0','She rates Aisha five out of five. The repair moves to work completed.'),
@('39-contractor-invoice.png','0','Aisha issues invoice R J 8 for one hundred and forty five pounds, due the sixth of November, with her bank details on it.'),
@('40-landlord-invoice-unpaid.png','0','Priya sees the invoice as unpaid. Resisquare records the payment. It does not take the money.'),
@('41-landlord-invoice-paid.png','0','She marks it paid. Aisha is told immediately.'),
@('71-aisha-paid-email.png','1','And the paid notice lands in Aisha inbox. Case one is closed. Appointed, updated, confirmed, and paid.'),
@('42-landlord-invited-owen.png','0','Case two. For the bedroom door, Priya does not use the marketplace. She invites her own carpenter, Owen Brooks.'),
@('44-owen-invite-email.png','1','Owen inbox has the invite. He signs up on that link, still free, and still has to be approved.'),
@('45-owen-signup.png','0','The form already knows his name and email, because they came from the invite. He accepts the contractor terms.'),
@('46-owen-otp.png','0','A six digit code arrives by email. He enters it before the two minute window closes.'),
@('47-owen-business.png','0','He registers as a sole trader for general repairs and carpentry, covering twenty five miles.'),
@('48-owen-documents.png','0','Photo I D and public liability are both ready. He sends them for review.'),
@('49-owen-pending.png','0','Until approval, his jobs page stays locked.'),
@('50-admin-review-owen.png','0','The super admin reviews Owen, approves both documents, approves both trades, and approves the profile.'),
@('67-owen-approved-email.png','1','Owen is emailed that he is approved for general repairs and carpentry.'),
@('56-landlord-sent-to-owen.png','0','Priya can now see him as her contractor. She sends only this job to Owen, with a budget of one hundred and sixty pounds.'),
@('57-owen-job-email.png','1','His email still hides the full address. It gives the district, London S W 1 A, and a reply deadline.'),
@('58-owen-sees-job.png','0','The bedroom door is now a live offer. He can decline, or make an offer.'),
@('59-owen-quote.png','0','He quotes ninety five pounds and keeps Wednesday afternoon, the time James said he would be home.'),
@('61-landlord-owen-quote.png','0','Priya sees that quote on the repair, next to the insurance and the visit.'),
@('62-landlord-accept-owen.png','0','She accepts. Owen receives the address and a calendar invite. James is told who is coming.'),
@('63-landlord-owen-accepted.png','0','The door job is scheduled for Wednesday at one. From here it follows the same update, confirm, and invoice path as case one.'),
@('70-tenant-booked-email.png','1','James email says a contractor will visit on Wednesday at one.'),
@('43-landlord-sent-to-resisquare.png','0','Case three. The hallway light goes to Resisquare instead of a contractor. Priya adds a note asking for an electrician on Thursday evening.'),
@('51-admin-owen-approved.png','0','On the repair desk, the super admin sees the job waiting, with the landlord note and the tenant availability.'),
@('52-admin-take-repair.png','0','Taking the repair tells the landlord and the tenant that Resisquare is handling it. The team books Thursday at five, when James is home.'),
@('53-admin-desk-update.png','0','A progress update is posted on the desk. That note is emailed and shown in the app. No contractor quote is involved.'),
@('65-landlord-desk-complete.png','0','When the fitting is replaced, the admin marks it complete. Priya sees the outcome on the repair. Work completed.'),
@('69-tenant-desk-complete-email.png','1','James gets the same outcome by email. The rest of a Resisquare job stays with the team. Only the updates travel to the landlord and the tenant.'),
@('64-landlord-notifications.png','0','Priya notification list shows the whole trail. Job accepted, offer received, sent to contractors, and the Resisquare completion.'),
@('66-tenant-notifications.png','0','James list matches it. A contractor booked for the door, and the hallway light updated, then completed. Repairs raised, appointed, and resolved, with every notice in the inbox and in the app.')
)

function Get-WavDuration([string]$path) {
    $fs = [IO.File]::OpenRead($path)
    $br = New-Object IO.BinaryReader($fs)
    try {
        $null = $br.ReadBytes(12)
        $rate = 0; $channels = 0; $bits = 0; $dataLen = 0
        while ($fs.Position -lt ($fs.Length - 8)) {
            $id = [Text.Encoding]::ASCII.GetString($br.ReadBytes(4))
            $size = $br.ReadInt32()
            if ($id -eq 'fmt ') {
                $null = $br.ReadInt16()
                $channels = $br.ReadInt16()
                $rate = $br.ReadInt32()
                $null = $br.ReadInt32()
                $null = $br.ReadInt16()
                $bits = $br.ReadInt16()
                $remain = $size - 16
                if ($remain -gt 0) { $null = $br.ReadBytes($remain) }
            } elseif ($id -eq 'data') {
                $dataLen = $size
                break
            } else {
                if ($size -gt 0) { $null = $br.ReadBytes($size) }
            }
        }
    } finally { $br.Close() }
    return [double]$dataLen / ($rate * $channels * ($bits / 8.0))
}

$synth = New-Object System.Speech.Synthesis.SpeechSynthesizer
$voice = $synth.GetInstalledVoices() | Where-Object { $_.VoiceInfo.Name -match 'Zira' } | Select-Object -First 1
if ($voice) { $synth.SelectVoice($voice.VoiceInfo.Name) }
$synth.Rate = -1

$manifest = @()
$i = 0
foreach ($slide in $slides) {
    $i++
    $id = '{0:D2}' -f $i
    $img = Join-Path $shots $slide[0]
    if (-not (Test-Path $img)) { throw "Missing screenshot $($slide[0])" }
    $wav = Join-Path $clips "$id.wav"
    $srt = Join-Path $clips "$id.srt"
    if (-not (Test-Path $wav)) {
        $synth.SetOutputToWaveFile($wav)
        $synth.Speak($slide[2])
        $synth.SetOutputToNull()
    }
    $secs = [math]::Round((Get-WavDuration $wav) + 0.45, 2)
    $end = [TimeSpan]::FromSeconds($secs).ToString('hh\:mm\:ss\,fff')
    $caption = $slide[2]
    @"
1
00:00:00,000 --> $end
$caption
"@ | Set-Content -Path $srt -Encoding utf8
    $manifest += [pscustomobject]@{ id = $id; image = $img; wav = $wav; srt = $srt; seconds = $secs; email = $slide[1]; caption = $caption }
    Write-Host ("NARRATED {0} {1:N1}s {2}" -f $id, $secs, $slide[0])
}
$synth.Dispose()
$manifest | ConvertTo-Json | Set-Content (Join-Path $out 'manifest.json') -Encoding utf8
Write-Host 'NARRATION DONE'
