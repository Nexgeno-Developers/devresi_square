<?php

namespace Tests\Feature;

use App\Mail\MailManager;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageReport;
use App\Models\MessageRequest;
use App\Models\MessagingPartyFlag;
use App\Models\RepairJobMessage;
use App\Models\RepairJobOffer;
use App\Services\Messaging\MessageRequestService;
use App\Support\Messaging\Party;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\Feature\Concerns\MessagingFixtures;
use Tests\TestCase;

class MessagingHttpTest extends TestCase
{
    use DatabaseTransactions;
    use MessagingFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMessaging();
        Mail::fake();
        Storage::fake('local');
    }

    public function test_tenant_starts_conversation_with_their_landlord_without_a_request(): void
    {
        [$owner, $accountId] = $this->landlord();
        [$tenant, $tenancy] = $this->tenant($accountId);

        $response = $this->asAccount($tenant, $accountId)->get(route('portal.messages.start', ['context' => 'tenancy', 'id' => $tenancy->id]));
        $conversation = Conversation::query()->where('pair_key', Party::pairKey(Party::account($accountId), Party::user($tenant->id)))->firstOrFail();
        $response->assertRedirect(route('portal.messages.show', $conversation));
        $this->assertSame(Conversation::ORIGIN_TENANCY, $conversation->origin);
        $this->assertNotNull($conversation->unmasked_at);
        $this->assertSame(MessageRequest::AUTO, MessageRequest::query()->where('pair_key', $conversation->pair_key)->value('status'));

        $this->asAccount($tenant, $accountId)->post(route('portal.messages.send', $conversation), ['body' => 'The boiler is off. Call me on 07123 456 789'])
            ->assertRedirect();
        $message = Message::query()->where('conversation_id', $conversation->id)->firstOrFail();
        // Existing relationships share details already, so nothing is masked.
        $this->assertStringContainsString('07123 456 789', $message->body);

        // Landlord sees it unread, in-app only (no email per message).
        $this->asAccount($owner, $accountId)->getJson(route('admin.messages.unread'))->assertJson(['unread' => 1]);
        $this->asAccount($owner, $accountId)->get(route('admin.messages.index'))->assertOk()->assertSee($tenant->name);
        $this->asAccount($owner, $accountId)->get(route('admin.messages.show', $conversation))->assertOk()->assertSee('The boiler is off');
        $this->asAccount($owner, $accountId)->getJson(route('admin.messages.unread'))->assertJson(['unread' => 0]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $owner->id, 'event_key' => 'message.received', 'account_id' => $accountId]);
        Mail::assertNothingSent();

        // Staff on the same account share the inbox and have their own read state.
        $staff = $this->staff($accountId);
        $this->asAccount($staff, $accountId)->getJson(route('admin.messages.unread'))->assertJson(['unread' => 1]);

        // The tenant sees "Seen" once the landlord read it.
        $this->asAccount($tenant, $accountId)->getJson(route('portal.messages.poll', $conversation).'?after=0')
            ->assertOk()->assertJson(['seen_up_to' => $message->id]);
    }

    public function test_landlord_starts_conversation_from_a_tenant_on_their_tenancy_only(): void
    {
        [$owner, $accountId] = $this->landlord();
        [, , $member] = $this->tenant($accountId);
        [, $otherAccount] = $this->landlord();
        [, , $foreignMember] = $this->tenant($otherAccount);

        $this->asAccount($owner, $accountId)->get(route('admin.messages.start', ['context' => 'tenant', 'id' => $member->id]))->assertRedirect();
        $this->asAccount($owner, $accountId)->get(route('admin.messages.start', ['context' => 'tenant', 'id' => $foreignMember->id]))->assertNotFound();
    }

    public function test_request_by_email_gives_the_same_answer_for_known_and_unknown_addresses(): void
    {
        [$ownerA, $accountA] = $this->landlord();
        [$ownerB, $accountB] = $this->landlord();

        $known = $this->asAccount($ownerA, $accountA)->post(route('admin.messages.requests.store'), ['email' => $ownerB->email, 'intro' => 'Hi, email me at me@example.com']);
        $unknown = $this->asAccount($ownerA, $accountA)->post(route('admin.messages.requests.store'), ['email' => 'nobody-'.uniqid().'@example.com', 'intro' => 'Hi']);

        $known->assertRedirect(route('admin.messages.requests'));
        $unknown->assertRedirect(route('admin.messages.requests'));
        $this->assertSame(session('messaging_status'), 'If this person uses ResiSquare, they’ll get your request.');

        $request = MessageRequest::query()->where('to_type', 'account')->where('to_id', $accountB)->firstOrFail();
        $this->assertSame(MessageRequest::PENDING, $request->status);
        $this->assertTrue($request->intro_masked);
        $this->assertStringNotContainsString('me@example.com', $request->intro);
        $this->assertDatabaseHas('message_requests', ['from_id' => $accountA, 'to_type' => 'email']);
        Mail::assertSent(MailManager::class, 1);

        // The sender's list shows what they typed for both, so nothing leaks.
        $this->asAccount($ownerA, $accountA)->get(route('admin.messages.requests'))->assertSee($ownerB->email);
    }

    public function test_accepting_a_request_opens_a_masked_conversation_until_both_reply(): void
    {
        [$ownerA, $accountA] = $this->landlord();
        $contractor = $this->contractorUser();
        $request = app(MessageRequestService::class)->send(Party::account($accountA), Party::user($contractor->id), $ownerA, 'Hello, quote please');

        $this->actingAs($contractor)->get(route('contractor.messages.requests'))->assertOk()->assertSee('Hello, quote please');
        $this->actingAs($contractor)->post(route('contractor.messages.requests.accept', $request))->assertRedirect();
        $conversation = Conversation::query()->where('request_id', $request->id)->firstOrFail();
        $this->assertNull($conversation->unmasked_at);
        $this->assertSame('Hello, quote please', $conversation->messages()->first()->body);

        // Before the contractor replies, the landlord's phone number is hidden.
        $this->asAccount($ownerA, $accountA)->post(route('admin.messages.send', $conversation), ['body' => 'Ring 07123 456 789']);
        $this->assertTrue(Message::query()->where('conversation_id', $conversation->id)->latest('id')->first()->masked);

        // Files are refused until both replied.
        $this->asAccount($ownerA, $accountA)->post(route('admin.messages.send', $conversation), ['files' => [UploadedFile::fake()->create('quote.pdf', 10, 'application/pdf')]])
            ->assertSessionHas('messaging_error');

        // The contractor's reply lifts masking for both.
        $this->actingAs($contractor)->post(route('contractor.messages.send', $conversation), ['body' => 'Sure, mine is 07999 888 777']);
        $this->assertNotNull($conversation->fresh()->unmasked_at);
        $this->assertStringContainsString('07999 888 777', Message::query()->where('conversation_id', $conversation->id)->latest('id')->first()->body);
    }

    public function test_one_request_per_pair_in_either_direction(): void
    {
        [$ownerA, $accountA] = $this->landlord();
        $contractor = $this->contractorUser();
        $service = app(MessageRequestService::class);
        $service->send(Party::account($accountA), Party::user($contractor->id), $ownerA, null);

        $this->expectExceptionMessage('They have already asked to message you. Accept their request instead.');
        $service->send(Party::user($contractor->id), Party::account($accountA), $contractor, null);
    }

    public function test_decline_cooldown_second_decline_closes_and_recipient_can_ask_back(): void
    {
        [$ownerA, $accountA] = $this->landlord();
        $contractor = $this->contractorUser();
        $service = app(MessageRequestService::class);
        $a = Party::account($accountA);
        $c = Party::user($contractor->id);

        $request = $service->send($a, $c, $ownerA, null);
        $service->decline($request, $c, $contractor);
        $this->assertSame(MessageRequest::DECLINED, $request->fresh()->status);

        try {
            $service->send($a, $c, $ownerA, null);
            $this->fail('Cooldown not applied');
        } catch (\App\Services\Messaging\MessagingException $e) {
            $this->assertStringContainsString('You can ask again from', $e->getMessage());
        }

        $request->forceFill(['cooldown_until' => now()->subDay()])->save();
        $again = $service->send($a, $c, $ownerA, null);
        $this->assertSame(2, $again->attempt);
        $service->decline($again, $c, $contractor);
        $again->forceFill(['cooldown_until' => now()->subDay()])->save();
        try {
            $service->send($a, $c, $ownerA, null);
            $this->fail('Second decline should close the pair');
        } catch (\App\Services\Messaging\MessagingException $e) {
            $this->assertStringContainsString('isn’t accepting requests', $e->getMessage());
        }

        // The person who declined can still start one back.
        $back = $service->send($c, $a, $contractor, 'Changed my mind');
        $this->assertTrue($back->isFrom($c));
        $this->assertSame(MessageRequest::PENDING, $back->status);
    }

    public function test_ignored_requests_expire_and_get_one_more_try(): void
    {
        [$ownerA, $accountA] = $this->landlord();
        $contractor = $this->contractorUser();
        $service = app(MessageRequestService::class);
        $a = Party::account($accountA);
        $c = Party::user($contractor->id);

        $request = $service->send($a, $c, $ownerA, null);
        $request->forceFill(['expires_at' => now()->subMinute()])->save();
        $this->artisan('messaging:maintain')->assertSuccessful();
        $this->assertSame(MessageRequest::EXPIRED, $request->fresh()->status);

        $request->fresh()->forceFill(['cooldown_until' => now()->subDay()])->save();
        $second = $service->send($a, $c, $ownerA, null);
        $second->forceFill(['status' => MessageRequest::EXPIRED, 'cooldown_until' => now()->subDay()])->save();
        $this->expectExceptionMessage('hasn’t replied to your requests');
        $service->send($a, $c, $ownerA, null);
    }

    public function test_withdrawn_request_counts_and_waits_thirty_days(): void
    {
        [$ownerA, $accountA] = $this->landlord();
        $contractor = $this->contractorUser();
        $request = app(MessageRequestService::class)->send(Party::account($accountA), Party::user($contractor->id), $ownerA, null);

        $this->asAccount($ownerA, $accountA)->post(route('admin.messages.requests.withdraw', $request))->assertRedirect();
        $this->assertSame(MessageRequest::WITHDRAWN, $request->fresh()->status);
        $this->assertTrue($request->fresh()->cooldown_until->gt(now()->addDays(29)));
    }

    public function test_blocking_stops_requests_and_replies_without_saying_so(): void
    {
        [$owner, $accountId] = $this->landlord();
        [$tenant, $tenancy] = $this->tenant($accountId);
        $this->asAccount($tenant, $accountId)->get(route('portal.messages.start', ['context' => 'tenancy', 'id' => $tenancy->id]));
        $conversation = Conversation::query()->latest('id')->firstOrFail();

        $this->asAccount($owner, $accountId)->post(route('admin.messages.block', $conversation))->assertRedirect();
        $this->asAccount($tenant, $accountId)->post(route('portal.messages.send', $conversation), ['body' => 'hello'])
            ->assertSessionHas('messaging_error', 'You can’t reply to this conversation.');
        $this->asAccount($tenant, $accountId)->get(route('portal.messages.show', $conversation))->assertOk()->assertDontSee('blocked');

        $this->asAccount($owner, $accountId)->post(route('admin.messages.unblock', $conversation));
        $this->asAccount($tenant, $accountId)->post(route('portal.messages.send', $conversation), ['body' => 'hello again'])->assertSessionHas('messaging_status');
    }

    public function test_request_rate_limits_and_new_login_limit(): void
    {
        [$ownerA, $accountA] = $this->landlord();
        for ($i = 0; $i < 5; $i++) {
            $this->asAccount($ownerA, $accountA)->post(route('admin.messages.requests.store'), ['email' => "x{$i}-".uniqid().'@example.com'])->assertSessionHas('messaging_status');
        }
        $this->asAccount($ownerA, $accountA)->post(route('admin.messages.requests.store'), ['email' => 'six-'.uniqid().'@example.com'])
            ->assertSessionHas('messaging_error', 'You have sent a lot of requests. Try again later.');

        $fresh = $this->user('New', ['created_at' => now()]);
        $fresh->forceFill(['created_at' => now()])->save();
        $fresh->assignRole(Role::findOrCreate('Contractor', 'web'));
        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($fresh)->post(route('contractor.messages.requests.store'), ['email' => "n{$i}-".uniqid().'@example.com'])->assertSessionHas('messaging_status');
        }
        $this->actingAs($fresh)->post(route('contractor.messages.requests.store'), ['email' => 'n4-'.uniqid().'@example.com'])->assertSessionHas('messaging_error');
    }

    public function test_many_declines_pause_then_suspend_the_sender(): void
    {
        $contractor = $this->contractorUser();
        $c = Party::user($contractor->id);
        $service = app(MessageRequestService::class);
        $declineFrom = function (int $count) use ($service, $c, $contractor) {
            for ($i = 0; $i < $count; $i++) {
                [$owner, $accountId] = $this->landlord();
                $request = MessageRequest::create([
                    'pair_key' => Party::pairKey($c, Party::account($accountId)),
                    'from_type' => 'user', 'from_id' => $contractor->id, 'to_type' => 'account', 'to_id' => $accountId,
                    'sent_by_user_id' => $contractor->id, 'status' => MessageRequest::PENDING, 'expires_at' => now()->addDays(30),
                ]);
                $service->decline($request, Party::account($accountId), $owner);
            }
        };

        $declineFrom(5);
        $flag = MessagingPartyFlag::for($c);
        $this->assertTrue($flag->isPaused());
        $this->actingAs($contractor)->post(route('contractor.messages.requests.store'), ['email' => 'p-'.uniqid().'@example.com'])
            ->assertSessionHas('messaging_error');

        $flag->forceFill(['paused_until' => now()->subMinute()])->save();
        $declineFrom(5);
        $this->assertTrue(MessagingPartyFlag::for($c)->isSuspended());
    }

    public function test_attachments_are_private_and_only_participants_can_download(): void
    {
        [$owner, $accountId] = $this->landlord();
        [$tenant, $tenancy] = $this->tenant($accountId);
        $this->asAccount($tenant, $accountId)->get(route('portal.messages.start', ['context' => 'tenancy', 'id' => $tenancy->id]));
        $conversation = Conversation::query()->latest('id')->firstOrFail();

        $this->asAccount($tenant, $accountId)->post(route('portal.messages.send', $conversation), [
            'body' => 'Photo of the leak',
            'files' => [UploadedFile::fake()->image('leak.jpg', 400, 300)],
        ])->assertSessionHas('messaging_status');
        $attachment = MessageAttachment::query()->latest('id')->firstOrFail();
        $this->assertStringStartsWith('private:uploads/private/messages/', $attachment->stored_path);
        Storage::disk('local')->assertExists(substr($attachment->stored_path, 8));

        $this->asAccount($owner, $accountId)->get(route('admin.messages.download', [$conversation, $attachment]))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        [$outsider, $otherAccount] = $this->landlord();
        $this->asAccount($outsider, $otherAccount)->get(route('admin.messages.download', [$conversation, $attachment]))->assertNotFound();

        $this->asAccount($tenant, $accountId)->post(route('portal.messages.send', $conversation), [
            'files' => [UploadedFile::fake()->create('evil.php', 1, 'application/x-php')],
        ])->assertSessionHas('messaging_error');
        $this->asAccount($tenant, $accountId)->post(route('portal.messages.send', $conversation), [
            'files' => array_fill(0, 6, UploadedFile::fake()->image('a.jpg')),
        ])->assertSessionHasErrors('files');
    }

    public function test_unsend_only_own_message_within_fifteen_minutes(): void
    {
        [$owner, $accountId] = $this->landlord();
        [$tenant, $tenancy] = $this->tenant($accountId);
        $this->asAccount($tenant, $accountId)->get(route('portal.messages.start', ['context' => 'tenancy', 'id' => $tenancy->id]));
        $conversation = Conversation::query()->latest('id')->firstOrFail();
        $this->asAccount($tenant, $accountId)->post(route('portal.messages.send', $conversation), ['body' => 'oops']);
        $message = Message::query()->latest('id')->firstOrFail();

        $this->asAccount($owner, $accountId)->delete(route('admin.messages.unsend', [$conversation, $message]))->assertSessionHas('messaging_error');
        $this->asAccount($tenant, $accountId)->delete(route('portal.messages.unsend', [$conversation, $message]))->assertSessionHas('messaging_status');
        $this->assertSoftDeleted($message);

        $this->asAccount($tenant, $accountId)->post(route('portal.messages.send', $conversation), ['body' => 'old']);
        $old = Message::query()->latest('id')->firstOrFail();
        $old->forceFill(['created_at' => now()->subMinutes(16)])->save();
        $this->asAccount($tenant, $accountId)->delete(route('portal.messages.unsend', [$conversation, $old]))->assertSessionHas('messaging_error');
    }

    public function test_conversation_turns_read_only_when_the_tenancy_ends_but_request_ones_stay_open(): void
    {
        [$owner, $accountId] = $this->landlord();
        [$tenant, $tenancy] = $this->tenant($accountId);
        $this->asAccount($tenant, $accountId)->get(route('portal.messages.start', ['context' => 'tenancy', 'id' => $tenancy->id]));
        $conversation = Conversation::query()->latest('id')->firstOrFail();

        $tenancy->forceFill(['status' => 'Terminated'])->save();
        $this->asAccount($owner, $accountId)->get(route('admin.messages.show', $conversation))->assertOk()->assertSee('The tenancy has ended');
        $this->asAccount($owner, $accountId)->post(route('admin.messages.send', $conversation), ['body' => 'hi'])->assertSessionHas('messaging_error');

        $contractor = $this->contractorUser();
        $request = app(MessageRequestService::class)->send(Party::account($accountId), Party::user($contractor->id), $owner, null);
        $open = app(MessageRequestService::class)->accept($request, Party::user($contractor->id), $contractor);
        $this->artisan('messaging:maintain')->assertSuccessful();
        $this->assertTrue($open->fresh()->isActive());
        $this->assertFalse($conversation->fresh()->isActive());
    }

    public function test_job_chats_show_in_the_inbox_for_the_two_sides_only(): void
    {
        [$owner, $accountId] = $this->landlord();
        $contractor = $this->contractorUser();
        $rival = $this->contractorUser();
        $offer = $this->repairOffer($accountId, $owner, $contractor, RepairJobOffer::OFFER_SENT);
        RepairJobMessage::create(['repair_job_offer_id' => $offer->id, 'user_id' => $contractor->id, 'side' => 'contractor', 'body' => 'When can I visit?']);

        $this->asAccount($owner, $accountId)->get(route('admin.messages.index'))->assertOk()->assertSee('When can I visit?')->assertSee('Job ·');
        $this->actingAs($contractor)->get(route('contractor.messages.index'))->assertOk()->assertSee('When can I visit?');
        $this->actingAs($rival)->get(route('contractor.messages.index'))->assertOk()->assertDontSee('When can I visit?');
        $this->actingAs($rival)->get(route('contractor.messages.jobs.repair', $offer))->assertNotFound();
        [$other, $otherAccount] = $this->landlord();
        $this->asAccount($other, $otherAccount)->get(route('admin.messages.jobs.repair', $offer))->assertNotFound();

        // Posting from the inbox still goes through the job rules (masked before award).
        $this->asAccount($owner, $accountId)->post(route('admin.messages.jobs.repair.send', $offer), ['body' => 'Call 07123 456 789'])->assertRedirect();
        $reply = RepairJobMessage::query()->where('repair_job_offer_id', $offer->id)->latest('id')->first();
        $this->assertTrue($reply->masked);
        $this->assertSame('landlord', $reply->side);
    }

    public function test_reports_reach_super_admin_with_only_ten_messages_either_side(): void
    {
        [$owner, $accountId] = $this->landlord();
        [$tenant, $tenancy] = $this->tenant($accountId);
        $this->asAccount($tenant, $accountId)->get(route('portal.messages.start', ['context' => 'tenancy', 'id' => $tenancy->id]));
        $conversation = Conversation::query()->latest('id')->firstOrFail();
        for ($i = 1; $i <= 30; $i++) {
            Message::create(['conversation_id' => $conversation->id, 'sender_party_type' => 'account', 'sender_party_id' => $accountId, 'user_id' => $owner->id, 'body' => "msg-{$i}-x", 'kind' => 'text']);
        }
        $target = Message::query()->where('body', 'msg-15-x')->firstOrFail();

        $this->asAccount($tenant, $accountId)->post(route('portal.messages.report', $conversation), ['category' => 'harassment', 'message' => $target->public_id])
            ->assertSessionHas('messaging_status');
        $report = MessageReport::query()->latest('id')->firstOrFail();

        $admin = $this->user('Admin');
        $admin->assignRole(Role::findOrCreate('Super Admin', 'web'));
        $page = $this->actingAs($admin)->get(route('backend.saas.message-reports.show', $report))->assertOk();
        $page->assertSee('msg-5-x')->assertSee('msg-25-x')->assertDontSee('msg-4-x')->assertDontSee('msg-26-x');

        $this->actingAs($admin)->post(route('backend.saas.message-reports.resolve', $report), ['action' => 'remove'])->assertRedirect();
        $this->assertNotNull($target->fresh()->removed_at);
        $this->assertDatabaseHas('audit_events', ['action' => 'messaging.report.remove', 'subject_id' => $report->id]);

        // Super Admin cannot open the inbox itself, nor while logged in as the account.
        $this->actingAs($admin)->get(route('admin.messages.show', $conversation))->assertForbidden();
        $this->actingAs($owner)->withSession(['current_account_id' => $accountId, 'impersonator_user_id' => $admin->id])
            ->get(route('admin.messages.show', $conversation))->assertForbidden();
        $this->asAccount($owner, $accountId)->get(route('backend.saas.message-reports.index'))->assertForbidden();
    }

    public function test_message_text_and_names_are_escaped(): void
    {
        [$owner, $accountId] = $this->landlord();
        [$tenant, $tenancy] = $this->tenant($accountId);
        $tenant->forceFill(['name' => '<script>alert(1)</script>'])->save();
        $this->asAccount($tenant, $accountId)->get(route('portal.messages.start', ['context' => 'tenancy', 'id' => $tenancy->id]));
        $conversation = Conversation::query()->latest('id')->firstOrFail();
        $this->asAccount($tenant, $accountId)->post(route('portal.messages.send', $conversation), ['body' => '<img src=x onerror=alert(2)>']);

        $html = $this->asAccount($owner, $accountId)->get(route('admin.messages.show', $conversation))->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x onerror=alert(2)>', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(2)&gt;', $html);
    }

    public function test_request_email_respects_preference_and_messages_never_email(): void
    {
        [$ownerA, $accountA] = $this->landlord();
        $contractor = $this->contractorUser();
        DB::table('notification_preferences')->insert([
            'account_id' => $accountA, 'user_id' => $contractor->id, 'event_key' => 'message.request_received',
            'email_enabled' => 0, 'in_app_enabled' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        app(MessageRequestService::class)->send(Party::account($accountA), Party::user($contractor->id), $ownerA, null);
        Mail::assertNothingSent();
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $contractor->id, 'event_key' => 'message.request_received']);
    }
}
