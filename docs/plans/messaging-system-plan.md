# Messaging system: plan (not built yet)

Status: **questions answered 2026-11-10. The decisions below override anything in sections 1–16 that disagrees.** Built on `feature/messaging` (2026-11-11) — see "Implementation notes" at the end.
Branch when building: a new branch off `main` (e.g. `feature/messaging`), not `fix/reported-site-issues`.

---

## 0. Decisions (from the user's answers)

| # | Decision | What changes in the plan |
|---|---|---|
| D1 | **Every kind of party may message every other kind** (landlord, agency, tenant, contractor, portal contacts). Who sees a "Message" button depends on context. | Section 4 matrix replaced by "any logged-in party ↔ any logged-in party", reached through **entry points** (see D1a), never a platform-wide directory. |
| D1a | Entry points: tenancy page, property participants, job offer (repair + compliance), calendar event attendees, owner group members, contractor list, and a **"message by email address"** form for anyone else. | The email form needs an exact match and answers "If this person uses ResiSquare, they'll get your request" whether or not they exist, so nobody can use it to check whether someone has an account. It counts towards the request limits. |
| D2 | Anyone who can log in (`can_login`, active membership, or a claimed contractor) can send and receive. Super Admin is still not a party. | Portal contact types (`contact`, `landlord` contact, `owner_contact`, `property_manager`) become `user` parties with their own inbox under the portal layout. |
| D3 | **Existing relationships skip the request**: tenant ↔ the account managing their tenancy, contractor ↔ an account that awarded them a job, event attendees ↔ the organiser. Emails such as "job assigned" and "event confirmed" get a **"Message {name}" link** into the inbox. | New context start routes, e.g. `GET /messages/start/repair-job/{offer}`, `/start/compliance-job/{offer}`, `/start/tenancy/{tenancy}`, `/start/event/{event}`. Each one checks the relationship with the same policies as the source record, then opens the conversation (or creates it already accepted). The pair still gets a `message_requests` row with status `auto` so the one-per-pair rule holds. URLs carry only the context record, never a user or account id. |
| D4 | **All staff** on an account can use its inbox. Conversations are not assigned to people or branches. | Drop the `messages.use` permission. Access = active operator membership (owner/admin/staff). |
| D5 | Guardrails after decline, expiry and withdrawal (see the table after this one). | Replaces the rules in section 5. |
| D6 | Production is **cPanel**: no websocket process. | **Polling only** (section 12 phase 2 dropped). Background jobs run through cPanel cron: `* * * * * php artisan schedule:run`, and the scheduler runs `queue:work --stop-when-empty --max-time=55`. Message sends must not depend on the queue. Only notifications are queued. |
| D7 | Ending a relationship (tenancy ended, contractor removed, account cancelled) makes the conversation **read-only**. | Only for conversations that exist **because of** that relationship (`auto` ones). A conversation started by an accepted request stays open. |
| D8 | **No email per message.** In-app (bell + unread badges) for messages. | `message.received` is system-only. Emails go only for `message.request_received` (rare, can be turned off in preferences) and the context emails in D3. |
| D9 | Hide phone numbers, emails and links in request intros and until the first reply. Attachments: up to 5 files of 10 MB each, from the existing allowlist. **No virus scanning** for launch. | As proposed. |
| D10 | Accepted as proposed: "Seen" shown, unsend within 15 minutes (leaves a tombstone), **no editing**, retention rules as in section 14, cancelled accounts' conversations become read-only. | — |
| D11 | Super Admin sees **only reported content plus up to 10 messages before and after it**. Actions as proposed: dismiss, warn, remove message, suspend messaging. | — |
| D12 | Limits accepted: 5 requests an hour, 20 a day, 30 messages a minute, auto-pause after declines or blocks from 5 different people in 7 days. | — |
| D13 | Messages gets **its own tab** and must be easy to reach. | Tenant/portal phone bottom nav gets a **6th tab, "Messages"**, with an unread dot; Docs stays. The landlord backend gets a sidebar item and an envelope icon with an unread count in the top bar. The contractor area gets a "Messages" nav link with a badge. |
| D14 | **Show all chats in one chat list**, including repair and compliance job chats. | The inbox list merges three sources (see D14a). |
| D14a | The list is built by `InboxFeed` from three sources: direct conversations, `RepairJobOffer` threads with messages, `ComplianceJobOffer` threads with messages. Job threads show a "Job · {ref}" label. Opening one shows it in the inbox pane, but posting goes through the existing `RepairJobAftercareService` / `ComplianceJobAftercareService::postMessage()`, so masking-before-award and closed-job rules are unchanged. Unread counts for job threads use their existing `read_at`. | Job chats are only between a landlord account and a contractor, so tenants see only direct conversations. Each job thread's access check reuses that job's current controller checks (`owns()` / `ownsCompliance()` for contractors, account + offer checks for landlords). The IDOR tests cover the merged list. |
| D15 | Two-person chats only. **No plan limits.** | — |
| D16 | Tenant-facing wording (see the explanation in the reply): show the **account name** with a small role label, e.g. "Acme Lettings · Letting agent" or "J. Smith · Landlord". The label comes from `AccountType`. | No decision needed from the user. |

### D5 guardrails (replaces section 5 rules where they differ)

| Situation | Rule |
|---|---|
| Request declined | The sender can't ask that party again for **90 days**. A second decline closes the pair **for good** for the sender. The recipient can start a conversation with the sender at any time. |
| Request ignored | Expires after **30 days**. The sender can ask once more after another 30 days. If that one expires too, the pair is closed for the sender. |
| Request withdrawn | Counts as a request, so the sender waits 30 days before asking again. Stops spamming by withdrawing and resending. |
| Blocked | No requests either way until the blocker unblocks. |
| New or unverified login (unverified email, or account < 7 days old) | Max 3 requests a day, intros masked, no attachments until the first reply. |
| Mass declines | Declines or blocks from 5 different people in 7 days pause requests for 7 days and alert Super Admin. A second pause within 30 days suspends messaging until Super Admin reviews it. |
| Report upheld | Super Admin can suspend messaging for a set time or permanently. |

---

## 1. What we are building

People on ResiSquare can send another party **one message request**. The recipient accepts, declines or ignores it. Once it is accepted, both sides can chat in a conversation. Either side can block or report at any time.

This is a **general direct-messaging** feature. It does **not** replace the two job chats that already exist (`RepairJobMessage`, `ComplianceJobMessage`), which belong to a job's lifecycle and mask contact details until the job is awarded. Those stay as they are (see Q14).

## 2. What already exists and will be reused

| Existing piece | Where | How messaging reuses it |
|---|---|---|
| Account membership + roles | `AccountUser` (`member_type`), `App\Support\AccountMembership`, `AccountType` | Decides who acts for an account and who is a tenant/portal user |
| Current account | `current_account_id()`, `current_account_membership()` (checks `status = active`) | The acting account for operators; re-checked on every request, so revoked staff lose access immediately |
| Workspace guard | `WorkspaceAccess::canManageCurrentWorkspace`, `not.portal`, `portal.tenant`, `current.account`, `account.status` middleware | Route groups for each role |
| Contractor links to accounts | `AccountContractor` (`account_id`, `contractor_user_id`, `claimed_at`), repair/compliance job offers | Proves a contractor ↔ account relationship |
| Tenancy links | `TenantMember`, `Tenancy`, `PropertyParticipant` | Proves a tenant ↔ account relationship |
| Job chat pattern | `RepairJobAftercareService::postMessage()` / `markRead()` / `mask()` | Same service shape; `mask()` moves to a shared `App\Support\ContactMasker` so both use one copy |
| Message throttle | `throttle:30,1` on the job-message routes | Same limit for chat messages |
| CRM notifications | `CrmNotificationEvent` enum, `config/crm_notifications.php`, `CrmNotificationService::dispatch()` (idempotency key), `NotificationPreferenceService`, `NotificationPreference` | New `messages.*` events, a `messages` category and group switch |
| Bell for users without an account | `ContractorMail::bell()` (writes `notifications` with nullable `account_id`) | Contractor in-app alerts |
| Secure file storage | `SecureUploadService` (type allowlist, private disk `private:` prefix) | Message attachments, but **always private** and served through a messaging route (not AIZ uploader) |
| DSAR erasure | `privacy:dsar-delete` command | Extended to anonymise messaging data |
| IDOR test pattern | `tests/Feature/InScopeIdorMatrixHttpTest.php`, `NotificationAccountIsolationTest.php` | Template for messaging IDOR matrix |

Things that do **not** exist today: broadcasting (`BROADCAST_CONNECTION=log`, no Reverb/Pusher/Echo), a running queue in local `.env` (`QUEUE_CONNECTION=sync`), a contractor in-app bell page in the contractor frontend (to be confirmed during build).

## 3. Who is a party (sender/recipient)

The key decision: a message is sent **between parties**, not always between individual logins, because a landlord or agency account can have several staff.

| Party kind | Represents | Who may act for it | Notes |
|---|---|---|---|
| `account` | A SaaS workspace (landlord or estate agent account) | Members with `member_type` owner/admin, and staff with the new `messages.use` permission (Q4) | Shared inbox. Each message records the actual `user_id` who wrote it, shown as "Sam (Acme Lettings)" |
| `user` | One person who is not acting for a workspace: tenant, contractor | Only that user | A tenant is a person, even though their membership row sits in the landlord's account |

Excluded in v1:
- **Super Admin**: cannot be a party (mirrors `AccountUser` refusing Super Admin memberships). Super Admin only sees **reported** content in the moderation queue (Q11).
- **Portal contact types** `contact`, `landlord` (agency's landlord contact), `owner_contact`, `property_manager`: need a decision (Q2). Default proposal: treat them as `user` parties with the same relationship rule as tenants.
- Pending/unclaimed users (e.g. `AccountContractor` without `claimed_at`, invited tenants who never signed in): cannot receive requests.

**Acting identity is never taken from request input.** It is derived from the route group:
- `/admin/messages/*` → party = `account:current_account_id()` (requires an active operator membership)
- `/admin/portal/messages/*` → party = `user:auth()->id()` (requires `portal.tenant`)
- `/contractor/messages/*` → party = `user:auth()->id()` (requires role `Contractor`)

A user who is both an operator of account A and a tenant somewhere sees two separate inboxes, one per route group. That avoids mixing a business inbox with a personal one.

## 4. Who can message whom (reachability)

There is **no open directory or free-text search across the platform**: that would let anyone enumerate users and accounts. A party can only send a request to someone they already have a relationship with. Proposed matrix (Q1 confirms it):

| From \ To | Landlord/agency account | Tenant | Contractor |
|---|---|---|---|
| **Landlord/agency account** | Only if linked (e.g. same owner group, or later a "connections" feature) — **off in v1** | Tenant on an active or recent (≤ 12 months) tenancy in this account | Contractor in this account's `AccountContractor` list (claimed) or with any job offer from this account |
| **Tenant** | The account that manages their tenancy | Off (tenant ↔ tenant) | Off in v1 (tenants talk to contractors through the repair visit flow) |
| **Contractor** | Accounts that list them or have sent them a job offer | Off | Off |

Implemented as one class, `App\Services\Messaging\Reachability`, with `canRequest(Party $from, Party $to): bool` and `candidatesFor(Party $from, ?string $search)` (search only inside the candidate set). The same check runs again when the request is accepted and when each message is sent, so ending a tenancy or removing a contractor from the list stops new messages (Q7 decides whether the old conversation becomes read-only).

## 5. Request rules

- **One request per pair, ever, in either direction.** Enforced by a unique `pair_key` (the two party keys sorted, e.g. `account:12|user:45`). If B already requested A, A sees "B has already asked to message you — accept it" instead of a send button.
- A request carries a short intro (max 500 chars). Contact details in the intro are masked with the shared `ContactMasker` (Q9).
- Recipient actions:
  - **Accept** → creates the conversation; the intro becomes the first message.
  - **Decline** → sender is told "not accepted". By default the pair is **closed for good**; only the recipient can later start a request back (Q5).
  - **Ignore** → stays pending, sender just sees "pending". After 30 days it expires; expired counts as the one request (Q5).
  - **Block** → like decline, and also blocks future contact (section 7).
- Sender can **withdraw** a pending request. Withdrawn does not use up the one request? Proposal: it does, to stop request-spam by withdraw/resend (Q5).
- Account inbox: any staff member with messaging access can accept/decline; the decision records `decided_by_user_id`.
- Only one pending outgoing request per pair; idempotent creation inside a DB transaction with the unique index as the real guard (no race on double-click).

## 6. Rate limits and abuse

| Action | Limit (proposal) | Mechanism |
|---|---|---|
| Send request | 5 per hour, 20 per day per party | Named `RateLimiter` keyed by party key (not IP) |
| Pending outgoing requests | max 25 at once per party | Checked in the service |
| Send message | 30 per minute per user (same as job chat) | `throttle:30,1` + named limiter |
| Attachments | 20 files per hour per user | Named limiter |
| Reports | 10 per day per user | Named limiter |
| Polling endpoints | 60 per minute per user | `throttle:60,1` |

Auto-protection: if a party's requests are declined/blocked by 5+ different recipients in 7 days, further requests are paused and Super Admin is alerted (Q12).

## 7. Blocking

- Either party can block the other at any time, from a request or a conversation. For an account, any operator with messaging access can block for the whole account.
- Effect: no new request either way; the conversation turns read-only for both; the blocked side sees a neutral "You can't reply to this conversation" (it does not say "you were blocked").
- Unblock is allowed by the blocker; it does not reopen a declined request automatically.
- Blocks are checked in the service on request, accept and every message send.

## 8. Data model (new tables)

All ids exposed in URLs are ULIDs (`public_id`), never auto-increment ids. Party columns are a pair `(*_type enum('account','user'), *_id bigint)`.

1. **`message_requests`**
   `id, public_id (ulid, unique), pair_key (unique), from_type, from_id, to_type, to_id, sent_by_user_id, intro (text), intro_masked (bool), status enum(pending, accepted, auto, declined, expired, withdrawn, blocked), attempt (tinyint, for the D5 second-chance rule), decline_count, cooldown_until null, decided_by_user_id null, decided_at null, expires_at, conversation_id null, timestamps`
   Index: `(to_type, to_id, status)`, `(from_type, from_id, status)`.
2. **`conversations`**
   `id, public_id, pair_key (unique), request_id, origin enum(request, tenancy, repair_job, compliance_job, event), origin_id null, status enum(active, read_only, closed), read_only_reason null, last_message_at, last_message_id null, timestamps`
   (`origin` drives D7: a scheduled `messaging:sync-relationships` job and the tenancy-end / contractor-removal code paths set `read_only` for conversations whose origin relationship has ended.)
   (Two parties only in v1. Group chats are out of scope, Q15.)
3. **`conversation_participants`**
   `id, conversation_id, party_type, party_id, archived_at null, muted_until null, timestamps`; unique `(conversation_id, party_type, party_id)`.
4. **`messages`**
   `id, public_id, conversation_id, sender_party_type, sender_party_id, user_id (actual author), body (text, max 5000), masked (bool), kind enum(text, system), deleted_at (soft delete = "unsent" tombstone), timestamps`; index `(conversation_id, id)`.
5. **`message_attachments`**
   `id, public_id, message_id, stored_path ('private:...'), original_name, mime, extension, size, timestamps`. Not linked to the `uploads` table, because that table is account-scoped and the AIZ uploader routes list files by account (one of today's leaks).
6. **`conversation_reads`** (read state is **per user**, so two staff on one account inbox have their own unread counts)
   `conversation_id, user_id, last_read_message_id, updated_at`; primary `(conversation_id, user_id)`. Unread = messages with `id > last_read_message_id` not sent by this party. The other side sees "Seen" when **any** user of the recipient party has read it (Q10).
7. **`message_blocks`**
   `id, blocker_type, blocker_id, blocked_type, blocked_id, blocked_by_user_id, reason null, timestamps`; unique `(blocker_type, blocker_id, blocked_type, blocked_id)`.
8. **`message_reports`**
   `id, public_id, reporter_user_id, reporter_party_type/id, conversation_id, message_id null, request_id null, category enum(spam, harassment, scam, other), note, status enum(open, actioned, dismissed), handled_by null, handled_at null, outcome_note, timestamps`.

Models: `MessageRequest`, `Conversation`, `ConversationParticipant`, `Message`, `MessageAttachment`, `ConversationRead`, `MessageBlock`, `MessageReport`, plus a small value object `App\Support\Messaging\Party` (`type`, `id`, `key()`, `displayName()`, `usersWhoActFor()`).

None of these tables use `BelongsToSaasAccount` global scoping: a conversation sits **between** accounts/users, so scoping is done explicitly by participant membership in every query (the policy + a `Conversation::scopeVisibleTo(Party)` scope).

## 9. Services, policies, middleware, controllers, routes

**Services** (`app/Services/Messaging/`):
- `MessagingIdentity` – resolves the acting `Party` from the route group (section 3) and lists users acting for a party.
- `Reachability` – section 4.
- `MessageRequestService` – send, accept, decline, withdraw, expire (scheduled command `messaging:expire-requests`, daily).
- `ConversationService` – post message, unsend (own message, within 15 min), mark read, archive, mute.
- `BlockService`, `ReportService`.
- `MessagingNotifier` – the only place that sends notifications (section 11).
- `App\Support\ContactMasker` – the existing `mask()` moved here; `RepairJobAftercareService::mask()` becomes a thin wrapper so job chat behaviour is unchanged.

**Policies** (`app/Policies/`), all receive the acting `Party` from `MessagingIdentity`, never from input:
- `MessageRequestPolicy`: `accept/decline/block` → acting party is the recipient; `withdraw` → acting party is the sender and the user may act for it; `view` → either party.
- `ConversationPolicy`: `view` → acting party is a participant; `send` → `view` + conversation active + not blocked + reachability still true.
- `MessageAttachmentPolicy`: `download` → `ConversationPolicy::view` of the attachment's conversation.
- `MessagePolicy::unsend` → author `user_id` = auth user, within window.
- Non-participants get **404**, not 403, so ids do not reveal that a conversation exists.

**Middleware**: new `messaging.enabled` (feature flag via config/env, like the existing launch kill switches) on all messaging routes. Existing middleware stacks as listed below.

**Controllers** (shared logic, three thin entry points so each role gets its own views and layouts):
- `Messaging\InboxController` (index, show, poll), `Messaging\RequestController` (create, store, accept, decline, withdraw), `Messaging\MessageController` (store, destroy), `Messaging\AttachmentController` (download), `Messaging\BlockController`, `Messaging\ReportController`, `Messaging\RecipientSearchController` (candidates only).
- `Backend\Saas\MessageReportController` for Super Admin moderation.

**Routes**

| Group | Prefix / name | Middleware |
|---|---|---|
| Landlord / agency staff | `/admin/messages` → `admin.messages.*` | `auth, current.account, account.status, not.portal, messaging.enabled, can:messages.use` |
| Tenant | `/admin/portal/messages` → `tenant.messages.*` | `auth, current.account, account.status, portal.tenant, messaging.enabled` |
| Contractor | `/contractor/messages` → `contractor.messages.*` | `auth, role:Contractor, messaging.enabled` |
| Super Admin moderation | `/admin/saas/message-reports` → `backend.saas.message-reports.*` | `auth, role:Super Admin` |

Per group: `GET /` inbox, `GET /requests`, `GET /new` (candidate picker), `POST /requests` (throttled), `POST /requests/{request}/accept|decline|withdraw|block`, `GET /c/{conversation}`, `GET /c/{conversation}/poll?after={messageId}` (throttle 60/min), `POST /c/{conversation}/messages` (throttle 30/min), `DELETE /c/{conversation}/messages/{message}`, `POST /c/{conversation}/read`, `POST /c/{conversation}/archive|mute|block|report`, `GET /attachments/{attachment}`. Route model binding by `public_id`, and child bindings scoped (`->scopeBindings()`) so a message id from another conversation 404s.

## 10. UI placement

**Landlord / agency backend**
- Sidebar item "Messages" with unread badge (respecting `LandlordSidebarFlattenHttpTest` structure).
- Navbar bell already shows notifications; a separate envelope icon with unread count is proposed (Q13).
- Inbox page: two panes on desktop (list | thread), single pane on phone. Tabs: *Inbox*, *Requests (n)*, *Archived*.
- Entry points: "Message" button on a tenant's tenancy page and on a contractor's card in the contractor list / job offer page (opens existing conversation or the request form).

**Tenant portal**
- Page under `/admin/portal/messages` using the tenant portal layout.
- Phone bottom nav (`_bottom-nav.blade.php`) already has 5 tabs (Home, Rent, Repairs, Docs, Me). Options (Q13): (a) replace *Docs* with *Messages* and move Docs into *Me*; (b) 6 tabs; (c) Messages inside *Me* with a badge on *Me*. Proposal: (a), with an unread dot.
- Home page card: "Message your landlord/agent" (or the pending request).

**Contractor frontend**
- Add "Messages" next to Jobs in the contractor nav, with unread badge.
- Conversation link on job pages when a conversation with that account exists (separate from the job chat, Q14).

All pages: empty states in the existing designed-empty-state style, form validation messages, messages rendered as escaped plain text with line breaks (`e()` + `nl2br`), links not auto-linked before acceptance (Q9).

## 11. Notifications

New `CrmNotificationEvent` cases and catalog entries in `config/crm_notifications.php`, category `messages`, new group switch `CRM_NOTIFICATIONS_MESSAGES_ENABLED`:

| Event | Recipients | Channels (default) | Locked? |
|---|---|---|---|
| `message.request_received` | Users acting for the recipient party | email + system | no |
| `message.request_accepted` | Sender user (+ party users) | system (email optional) | no |
| `message.received` | Users acting for the other party who are not currently viewing | system; email as a **digest** (Q8) | no |
| `message.report_actioned` | Reporter | system | no |

- Users can switch these on/off via the existing preference pages (`backend.notifications.preferences`, `tenant.notifications`). `NotificationPreference` is keyed by `account_id`; for contractors and tenants without a matching account context, the preference is stored with the conversation's counterpart account id, or we add a nullable-account variant (decided during build; Q8).
- Email flood control: at most one "new messages" email per conversation per user per 15 minutes, via the existing idempotency key with a time bucket.
- Email content: subject "New message from {name}" and a link only; **no message body** in email by default (privacy; Q8).
- Contractors get the bell via `ContractorMail::bell()` (or the CRM pipeline if it supports null account cleanly).
- Notifications must never reveal a block or a decline beyond "not accepted".

## 12. Real-time vs polling

Today there is no broadcasting stack and local queues run `sync`. Proposal for v1: **polling**.
- Open conversation: poll `/poll?after=` every 10 s while the tab is visible, back off to 60 s when hidden; returns only new messages + read receipts.
- Unread badge: refresh every 60 s (or piggyback on page loads).
- Phase 2 (optional): Laravel Reverb + Echo private channels `conversation.{publicId}` with the same `ConversationPolicy` in `routes/channels.php`. Needs a long-running process and a real queue on hosting (Q6).

## 13. Attachments

- Up to 5 files per message, 10 MB each, types from `SecureUploadService` allowlist (images, PDF, office docs; no archives or executables) (Q9).
- **All** messaging attachments go to the private disk (`uploads/private/messages/{conversation}`), including images, and are streamed by `AttachmentController` after `MessageAttachmentPolicy::download`. Image previews are served through the same route.
- File names are sanitised; `Content-Disposition: attachment` for non-images; `X-Content-Type-Options: nosniff`.
- Attachments are blocked before a request is accepted.
- Virus scanning: not present today; listed as Q9.

## 14. Moderation, privacy and retention

**Reporting**: report a message, request or conversation. Super Admin queue shows the reported item plus up to 10 surrounding messages, nothing else. Actions: dismiss, warn, remove message (tombstone "Removed by ResiSquare"), suspend the sender's messaging (`messaging_suspended_until` on user) . Every Super Admin view and action is written to the audit log (`AuditTrail`) (Q11).

**Privacy**
- Super Admin cannot browse conversations outside reports (Q11).
- Masking of phone/email/links in request intros (and optionally until first reply), reusing `ContactMasker` (Q9).
- Messages are plain text; HTML escaped; covered by `StoredXssHttpTest`-style tests.

**Retention** (Q10)
- Proposal: keep conversations for the life of both parties; "unsend" within 15 minutes leaves a tombstone; per-participant archive hides but does not delete.
- Expired/declined requests: intro text purged after 90 days, row kept for the one-request rule.
- Account closed/cancelled: its conversations become read-only for the other side; data deleted after the account data retention period.
- DSAR (`privacy:dsar-delete`): anonymise the user's name in messages they wrote, delete their attachments, keep the other side's messages; reports kept for 1 year.
- Pruning command `messaging:prune` scheduled daily.

## 15. Test plan

Feature tests (`tests/Feature/Messaging*`), using the existing factories and `RefreshDatabase` setup:

1. **MessagingRequestHttpTest** – send/accept/decline/ignore→expire/withdraw; one request per pair in both directions; double-submit creates one row; decline closes the pair; intro masking.
2. **MessagingReachabilityTest** – each cell of the section 4 matrix, including: ended tenancy, unclaimed contractor, contractor removed from list, Super Admin cannot be a party or a target, pending users unreachable.
3. **MessagingIdorMatrixHttpTest** (same style as `InScopeIdorMatrixHttpTest`) – for every route, a user from account B, a tenant of account A on another tenancy, an unrelated contractor, staff without `messages.use`, a revoked staff member (membership `status != active`), and a portal user on admin routes all get 404/403 and see no data; message ids from another conversation are 404 via scoped bindings; attachment download by non-participants 404; poll endpoint leaks nothing.
4. **MessagingAccountSwitchTest** – an operator of accounts A and B sees only the current account's inbox after `switch-account`; a user who is operator in A and tenant in C sees separate inboxes.
5. **MessagingBlockTest** – block/unblock both ways, blocked side cannot request/send, wording reveals nothing.
6. **MessagingRateLimitTest** – request, message, attachment and report limits; pause after mass declines.
7. **MessagingAttachmentTest** – type/size rejects, stored on private disk, streamed with correct headers, not available before acceptance.
8. **MessagingReadStateTest** – per-user unread counts on a shared inbox, "seen" rule, poll returns only newer messages.
9. **MessagingNotificationTest** – events dispatched to the right users only (never to another account's users), preference opt-out works, 15-minute email bucket, no body in email, `NotificationAccountIsolationTest` style isolation.
10. **MessagingModerationTest** – report flow, Super Admin sees only reported context, actions audited, non-Super Admin 403.
11. **MessagingXssTest** – script tags/HTML in body, intro, file names render escaped.
12. **MessagingRetentionTest** – expiry, prune, DSAR anonymisation.
13. **Regression** – existing job-chat tests (`ContractorMarketplaceTest`, `ComplianceBookingTest`) still pass after moving `mask()` to `ContactMasker`.
14. **MessagingStartFromContextTest** (D3) – each start route opens/creates an `auto` conversation only for the real parties of that tenancy/job/event; another account's tenancy/offer/event id → 404; email links in "job assigned" / "event confirmed" point to the start route.
15. **MessagingEmailLookupTest** (D1a) – identical response for existing vs unknown email; counts towards limits; can't target Super Admin or non-login users.
16. **MessagingUnifiedInboxTest** (D14) – job threads appear only for the landlord account and contractor on that offer; posting from the inbox still masks before award and refuses closed jobs; a contractor never sees another contractor's job thread on the same repair.
17. **MessagingGuardrailTest** (D5) – 90-day cooldown, second decline closes pair, expiry second chance, withdraw cooldown, new-login limits, mass-decline pause and suspension.
18. **MessagingReadOnlyTest** (D7) – tenancy end / contractor removal / account cancellation makes `auto` conversations read-only, request-origin ones stay open.
19. **Manual QA** – add the new cases to the role test inventory (`resisquare-role-test-inventory.html`) for landlord, staff, tenant (desktop + phone bottom nav), contractor and Super Admin.

## 16. Build order (after answers)

1. Migrations, models, `Party`, `ContactMasker` extraction (+ regression tests).
2. `Reachability`, `MessageRequestService`, policies + IDOR tests first.
3. Conversation, messages, read state, polling endpoints.
4. Landlord UI → tenant portal UI (bottom nav) → contractor UI.
5. Notifications.
6. Attachments.
7. Blocking, reporting, Super Admin queue.
8. Retention/prune/DSAR, feature flag, docs.

---

## Questions to answer before implementation

**Answered 2026-11-10 — see section 0.** Original questions kept for reference:

1. **Who may request whom?** Confirm the section 4 matrix. In particular: landlord ↔ landlord / agency ↔ agency (off in v1?), tenant ↔ contractor (off?), contractor ↔ contractor (off?).
2. **Portal contact types** (`contact`, `landlord` contact on an agency account, `owner_contact`, `property_manager`): can they message, and with whom?
3. **Existing relationships**: should a tenant ↔ their managing account (and a contractor ↔ an account that awarded them a job) still need a request, or start as an accepted conversation automatically?
4. **Account inbox access**: which staff can read/send for the account — all staff, or only staff given a new `messages.use` permission? Should conversations be assignable to a staff member or branch?
5. **After decline / expiry / withdraw**: is the pair closed forever, closed for a cooldown (e.g. 90 days), or can the recipient always reverse it? Does a withdrawn request count as the one request? How long until an ignored request expires (30 days proposed)?
6. **Real-time**: is polling (10 s in an open chat) fine for v1, or do you want live updates (Reverb) now? Can production run a queue worker and a long-running websocket process?
7. **When the relationship ends** (tenancy ends, contractor removed): conversation becomes read-only, stays open, or is closed?
8. **Notifications**: email on every message, a 15-minute digest, or in-app only? May email include a message preview, or only "you have a new message"? Any channels locked on?
9. **Content rules**: mask phone/email/links in request intros (and until the first reply)? Allowed attachment types and size (5 × 10 MB proposed)? Is virus scanning needed before launch?
10. **Read receipts and retention**: show "Seen" to the other side? Allow unsend (15 minutes)? Edit messages? How long to keep messages and attachments, and what happens on account cancellation?
11. **Moderation**: may Super Admin read only reported content (proposed), or any conversation for support? Who handles reports and what actions are allowed (warn, remove, suspend)?
12. **Rate limits**: are 5 requests/hour, 20/day and 30 messages/minute acceptable? Automatic pause after 5 declines/blocks in 7 days?
13. **UI**: tenant phone bottom nav — replace Docs with Messages (Docs moves under Me), add a 6th tab, or put Messages under Me? Landlord backend — sidebar item only, or also an envelope icon in the navbar?
14. **Existing job chats**: keep repair/compliance job chats separate (proposed), or show them in the new inbox as well?
15. **Scope**: two-party conversations only for v1 (no group chats, e.g. landlord + tenant + contractor)? Any plan or add-on gating (only paid plans can message)?
16. **Language / branding**: tenant-facing wording "landlord" vs "agent" depends on account type — OK to derive from `AccountType`? Any copy you want fixed?

---

## Implementation notes (2026-11-11)

Where the build differs from, or adds to, the plan above:

- **Routes**: `/admin/messages/*` (workspace, `admin.messages.*`), `/admin/portal/messages/*` (tenants and other portal contacts, `portal.messages.*`), `/contractor/messages/*` (`contractor.messages.*`), Super Admin queue at `/admin/saas/message-reports`. All registered by `App\Support\Messaging\MessagingRoutes`; the acting party is set by the `messaging.as:{area}` middleware (`EnsureMessagingIdentity`).
- **No separate policy classes**: participation is checked in the controllers (`conversationFor()` → 404) and again in the services (`Party::actsFor()`), which is the same rule a policy would hold. Scoped bindings stop message ids from another conversation.
- **Super Admin "log in as account"** cannot open messages (impersonation session → 403), so Super Admin only ever sees reported content plus 10 messages either side.
- **Suspensions** are per party (`messaging_party_flags`), not per user; "until reviewed" is stored as 31 Dec 2037 because MySQL `TIMESTAMP` ends in Jan 2038.
- **New logins** = user account younger than 7 days (email verification is almost never set in this app, so it is not used).
- **Notifications** are written straight to the bell (`notifications` table) by `MessagingNotifier`, honouring `notification_preferences` and the new `message.*` catalog entries; email only for `message.request_received`. The CRM pipeline needs an account context, which tenant↔contractor or contractor bells do not have.
- **Read-only (D7)** is checked lazily on every view/send and by the daily `messaging:maintain` command. "Contractor removed from the list" is not tracked separately: job-origin conversations end when the job is no longer accepted/closed.
- **Entry points built**: tenancy page (each tenant), tenant home + tenancy page, awarded repair job (landlord and contractor), accepted compliance job (both sides), "My contractor" rows on the find-contractor page (request), email form. Owner-group and property-participant buttons are not added yet (the email form covers them).
- **Emails with inbox links (D3)**: repair "Job accepted", compliance "You are booked", appointment invitations.
- **cPanel**: scheduler now also runs `queue:work --stop-when-empty --max-time=55` every minute; production needs the usual `* * * * * php artisan schedule:run` cron.
