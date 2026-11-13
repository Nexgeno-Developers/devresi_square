# Messaging, simplified (replaces messaging-system-plan.md)

Status: **built 2026-11-12** on `feature/messaging`, with the recommended answers to Q1–Q9 (see "As built" at the end). Replaces the earlier request-based design from commit 06ed599.

## 1. The only rules

| # | Rule |
|---|---|
| R1 | A landlord (the account that manages the tenancy) and each of its tenants always have a direct chat. No request, no accept step. |
| R2 | When a contractor is assigned a repair or compliance job, a 3-way chat opens: contractor, landlord, tenant(s). When the job is finished it turns read-only and is archived automatically. |
| R3 | Nobody can start any other chat. There is no "new message" button, no request, no email lookup. Chats exist only because R1 or R2 created them. |

## 2. Purge list (from commit 06ed599)

| Remove | Files |
|---|---|
| Message requests (send/accept/decline/withdraw/expire, cooldowns, second-chance, pair rule) | `Models/MessageRequest.php`, `Services/Messaging/MessageRequestService.php`, `views/messaging/requests.blade.php`, `views/messaging/create.blade.php`, request routes |
| "Message by email address" form | `Services/Messaging/MessagingDirectory.php` |
| Generic start routes (`/start/{context}/{id}`), event-attendee and find-contractor "Message" buttons | `views/messaging/partials/start-button.blade.php` and its includes on repair marketplace, compliance actions, tenancy pages |
| Blocking, reporting, Super Admin report queue, pauses and suspensions, mass-decline guard | `SafetyController`, `SafetyService`, `MessageBlock`, `MessageReport`, `MessagingPartyFlag`, `MessagingGuard`, `Backend/Saas/MessageReportController`, `views/backend/saas/message-reports/*` |
| Contact masking until both sides reply | `Conversation::unmasked_at` logic (keep `ContactMasker` only if pre-award job questions stay, see Q3) |
| Unsend, mute, manual archive, "Seen" | routes and view controls |
| Request notifications and emails | `message.request_*` events in `CrmNotificationEvent` and `config/crm_notifications.php` |
| The two-pane "unified inbox" merge of old job-thread tables | `InboxFeed` job-thread sources, `JobThreadController` (replaced by real 3-way conversations) |
| Tests for all of the above | parts of `MessagingHttpTest`, `MessagingIdorMatrixHttpTest` |

Tables dropped: `message_requests`, `message_blocks`, `message_reports`, `messaging_party_flags`.

## 3. Keep

- Party model: a landlord is the **account** (shared inbox for its staff, each message shows who wrote it); tenants and contractors are **users**. Acting party is set by route group (`messaging.as:{area}`), never by input.
- Three inbox areas: `/admin/messages` (landlord staff), `/admin/portal/messages` (tenant), `/contractor/messages` (contractor). Sidebar item, navbar envelope badge, tenant 6th bottom tab, contractor nav link.
- Text messages, attachments (5 × 10 MB, private disk, streamed after a participant check). Photos matter for repairs.
- Per-user unread counts, polling (cPanel, no websockets), in-app bell for new messages, no email per message.
- Non-participant gets 404. IDOR test matrix.

## 4. Data model (one new migration replacing `2026_11_11_100000`)

```
conversations
  id, public_id (ulid)
  kind            enum: tenancy | repair_job | compliance_job
  context_id      tenancy_id, repair_job_offer_id or compliance_job_offer_id
  tenant_user_id  null; set for kind=tenancy when chats are 1:1 per tenant (see Q1)
  status          enum: active | archived
  archived_at, archived_reason
  last_message_at, last_message_id, timestamps
  UNIQUE (kind, context_id, tenant_user_id)

conversation_participants
  id, conversation_id, party_type (account|user), party_id,
  role enum: landlord | tenant | contractor,
  left_at null            -- tenant moved out mid-job, etc.
  UNIQUE (conversation_id, party_type, party_id)

messages             id, public_id, conversation_id, sender_party_type/id, user_id, body, kind (text|system), timestamps
message_attachments  unchanged
conversation_reads   unchanged (per user)
```

No `pair_key`: the unique key on `(kind, context_id, tenant_user_id)` is what stops duplicates.

## 5. Lifecycle

### Landlord ↔ tenant (R1)
- **Created** when a tenant becomes a member of a tenancy (and lazily on first open of the inbox, so existing tenancies get chats without a backfill job).
- Landlord party = the account that owns the tenancy (agency account for agency-managed lets).
- **Tenancy ends**: chat becomes read-only and moves to Archived (see Q2). A renewal or new tenancy gets its own chat, so history stays tied to the right tenancy.

### Job chat (R2)
- **Opens** when the offer reaches `JOB_ACCEPTED` (repair: `RepairJobOfferService` award path; compliance: `ComplianceBookingService` accept path). A system message is posted: "Job {ref} assigned to {contractor}. This chat closes when the job is finished."
- **Participants**: the landlord account, the contractor user, and **every tenant on the property's current tenancy** (not just the tenant who reported the repair). Vacant property → landlord + contractor only.
- **Finished** = landlord confirms completion (`job_status = confirmed`) **or** the offer is cancelled/withdrawn after award. At that moment: system message "Job finished, this chat is now archived", status `archived`, read-only, moved to Archived for everyone (see Q4 for grace period).
- Invoicing and payment after confirmation keep using the existing invoice screens, not chat.

### Safety net
- Daily `messaging:sync` command (replaces `messaging:maintain`): creates missing chats for active tenancies and accepted jobs, archives chats whose tenancy ended or job finished. Hooks do the real-time work; this only catches misses.
- Every send re-checks: conversation active + acting party is a current participant. A tenant removed from the tenancy loses send access at once.

## 6. Routes (per area)

```
GET  /                       inbox (active chats)
GET  /archived               archived chats (read-only)
GET  /unread                 badge count (throttle 60/min)
GET  /c/{conversation}       open chat
GET  /c/{conversation}/poll  new messages (throttle 60/min)
POST /c/{conversation}/messages      send (throttle 30/min)
GET  /c/{conversation}/files/{attachment}
```

No create/start/request routes exist, so R3 is enforced by absence plus the participant check. Entry links from tenancy and job pages go straight to `/c/{conversation}` (resolved server-side from the tenancy/offer the user can already see).

## 7. Build order

1. Delete purge-list code, routes, views, tests; replace the migration (roll back the old one on local DB first).
2. New `Conversation` / `ConversationParticipant` shape; `ConversationProvisioner` service with `forTenancy()`, `forRepairJob()`, `forComplianceJob()`, `archiveFor...()`.
3. Hook provisioner into tenancy member add/remove, tenancy end, job award, job confirm, job cancel.
4. Slim `ConversationService` (send, read, attachments) and `MessagingController` (index, archived, show, poll, send, download, unread).
5. Views: remove request tabs/buttons, add 3-person header (names + role chips), archived banner.
6. `messaging:sync` command + scheduler.
7. Tests: provisioning per rule, archive on finish, R3 (no route can create a chat; an unrelated tenant/contractor/account gets 404 on every route), tenant on another tenancy can't see the job chat, contractor on a different offer for the same repair can't see it.
8. Manual QA rows in `resisquare-role-test-inventory.html`.

## 8. Problems found and suggestions

| # | Issue | Suggestion |
|---|---|---|
| Q1 | **Joint tenancies.** "Landlord ↔ his tenants": one chat per tenant, or one household chat per tenancy? | Recommend **one chat per tenancy** (landlord + all tenants). Landlords usually message the household, and it matches the job chat which also includes all tenants. 1:1 per tenant is possible via `tenant_user_id`. |
| Q2 | **"Always" vs tenancy ending.** A former tenant keeping a live line to the landlord forever is risky (disputes, harassment, deposit arguments). | Read-only + archived when the tenancy ends; history stays visible to both. |
| Q3 | **Pre-award questions.** Today contractors ask the landlord questions on a job offer *before* being assigned (masked Q&A on the offer page). R3 bans this. Removing it means contractors quote blind. | Keep it as an **offer-page Q&A box, not a chat** (not in the inbox, ends when the offer is decided). Or drop it if you want strict R3. |
| Q4 | **What is "finished"?** Contractor marks complete → landlord confirms → invoice. If we archive at "contractor marked complete", the landlord can't ask about snagging. | Archive at **landlord confirms** (or cancel). Optional 48 h grace after confirm before it goes read-only. |
| Q5 | **No block/report at all.** With everything purged, a tenant harassed by a landlord or contractor has no in-app recourse, and you have no audit path. | Keep a single "Report this chat" that emails ResiSquare support with the chat id (no moderation UI). Cheap, and legally safer. |
| Q6 | **Contractor sees tenants directly.** Tenants could share phone numbers with the contractor; the old build masked contact details. | Fine once assigned (they need to arrange access). No masking in job chats. |
| Q7 | **Agency-managed lets.** "Landlord" in the chat = the managing agency account; the property owner (a portal contact) is not in it. | Confirm that's intended. Owner can be added later as an optional participant. |
| Q8 | **Old job-chat tables** (`repair_job_messages`, `compliance_job_messages`) already hold post-award messages in production. | Leave old threads readable on the job page; new chats start fresh from award. No data migration. |
| Q9 | **Migration already run on local dev DB.** Branch is unpushed, so rewriting the migration is safe if it never ran anywhere else. | Roll back locally, replace the migration file. |

## 9. As built (2026-11-12)

Decisions taken (the recommended answers):

- **Q1** one chat per tenancy: the managing account + every tenant on it. The `tenant_user_id` column was not needed; the unique key is `(kind, context_id)`.
- **Q2** tenancy no longer `Active` → chat archived and read-only, history still visible. Back to `Active` → reopened.
- **Q3** pre-award questions kept on the offer page (`RepairJobMessage` / `ComplianceJobMessage`), now only while the offer is open (`canMessage()` = `OPEN_STATUSES`). Old post-award messages stay readable there. They are not in the inbox.
- **Q4** job chat opens at `status = job_accepted`; archived at `job_status` `confirmed` / `invoiced` (landlord sign-off), or when the offer is cancelled / closed. "Contractor marked complete" keeps it open.
- **Q5** "Report this chat" emails ResiSquare admins (`ContractorMail::toAdmins`) and logs it. No moderation screen, no blocking.
- **Q6** no masking anywhere in chats.
- **Q7** the landlord participant is the account that owns the tenancy / job (agency for agency-managed lets).
- **Q8** no data migration of old job messages.
- **Q9** migration file replaced in place (branch never deployed).

Shared house: a repair chat takes only the reporting tenant's tenancy when several active tenancies share the property; a compliance chat takes all of them.

Code map:

| Piece | File |
|---|---|
| Creates / syncs / archives every chat | `app/Services/Messaging/ConversationProvisioner.php` |
| Hooks on Tenancy, TenantMember, RepairJobOffer, ComplianceJobOffer | `app/Observers/MessagingLifecycleObserver.php` (registered in `AppServiceProvider`) |
| Daily safety net (02:30) | `php artisan messaging:sync` |
| Backfill on inbox open | `MessagingController@index` → `syncForParty()` |
| Send, read, files | `app/Services/Messaging/ConversationService.php` |
| Bells + report email | `app/Services/Messaging/MessagingNotifier.php` |
| Links from tenancy / job pages | `App\Support\Messaging\ChatLink` + `messaging/partials/chat-link.blade.php` |
| Tests | `tests/Feature/MessagingHttpTest.php`, `tests/Feature/MessagingIdorMatrixHttpTest.php` |

Deploy note: run `php artisan messaging:sync` once after migrating so every active tenancy and live job gets its chat (inbox pages also backfill on open). On a large database the first run takes a while.
