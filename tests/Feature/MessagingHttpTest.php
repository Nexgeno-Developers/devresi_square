<?php

namespace Tests\Feature;

use App\Models\ComplianceJobOffer;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\RepairJobOffer;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Services\Contractors\ContractorMarketplaceException;
use App\Services\Contractors\RepairJobAftercareService;
use App\Support\Messaging\Party;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\MessagingFixtures;
use Tests\TestCase;

/**
 * The three messaging rules:
 *  1. A landlord and the tenants on an active tenancy always have a chat.
 *  2. An assigned job opens a landlord + contractor + tenants chat, archived
 *     when the job is signed off or cancelled.
 *  3. Nobody can start any other chat.
 */
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

    // ── Rule 1: landlord ↔ tenants ──────────────────────────────────────────

    public function test_an_active_tenancy_has_a_chat_between_the_landlord_and_its_tenants(): void
    {
        [$owner, $accountId] = $this->landlord();
        [$tenant, $tenancy] = $this->tenant($accountId);

        $chat = $this->chatFor(Conversation::KIND_TENANCY, $tenancy->id);
        $this->assertNotNull($chat);
        $this->assertTrue($chat->hasParticipant(Party::account($accountId)));
        $this->assertTrue($chat->hasParticipant(Party::user($tenant->id)));

        $this->asAccount($tenant, $accountId)->get(route('portal.messages.index'))->assertOk()->assertSee('data-msg-item="'.$chat->public_id.'"', false);
        $this->asAccount($tenant, $accountId)->post(route('portal.messages.send', $chat), ['body' => 'The boiler is off. Call me on 07123 456 789'])->assertRedirect();
        $message = Message::query()->where('conversation_id', $chat->id)->where('kind', Message::TEXT)->firstOrFail();
        $this->assertStringContainsString('07123 456 789', $message->body, 'Nothing is masked.');

        // Landlord: unread badge, bell, no email.
        $this->asAccount($owner, $accountId)->getJson(route('admin.messages.unread'))->assertJson(['unread' => 1]);
        $this->asAccount($owner, $accountId)->get(route('admin.messages.show', $chat))->assertOk()->assertSee('The boiler is off')->assertSee($tenant->name);
        $this->asAccount($owner, $accountId)->getJson(route('admin.messages.unread'))->assertJson(['unread' => 0]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $owner->id, 'event_key' => 'message.received', 'account_id' => $accountId]);
        Mail::assertNothingSent();

        // Staff share the account inbox with their own read state, and can reply.
        $staff = $this->staff($accountId);
        $this->asAccount($staff, $accountId)->getJson(route('admin.messages.unread'))->assertJson(['unread' => 1]);
        $this->asAccount($staff, $accountId)->post(route('admin.messages.send', $chat), ['body' => 'An engineer is on the way.'])->assertRedirect();
        $this->asAccount($tenant, $accountId)->get(route('portal.messages.show', $chat))->assertSee('An engineer is on the way.');
    }

    public function test_joint_tenants_share_one_household_chat_and_a_tenant_who_leaves_loses_access(): void
    {
        [$owner, $accountId] = $this->landlord();
        [$first, $tenancy] = $this->tenant($accountId);
        [$second, $secondMember] = $this->addTenant($tenancy);

        $chat = $this->chatFor(Conversation::KIND_TENANCY, $tenancy->id);
        $this->assertSame(1, Conversation::query()->where('kind', Conversation::KIND_TENANCY)->where('context_id', $tenancy->id)->count());
        $this->assertTrue($chat->hasParticipant(Party::user($second->id)));

        $this->asAccount($first, $accountId)->post(route('portal.messages.send', $chat), ['body' => 'Hello from flatmate one']);
        $this->asAccount($second, $accountId)->get(route('portal.messages.show', $chat))->assertOk()->assertSee('Hello from flatmate one');

        $secondMember->delete();
        $this->asAccount($second, $accountId)->get(route('portal.messages.show', $chat))->assertNotFound();
        $this->asAccount($second, $accountId)->post(route('portal.messages.send', $chat), ['body' => 'still here?'])->assertNotFound();
        $this->asAccount($first, $accountId)->get(route('portal.messages.show', $chat))->assertOk();
    }

    public function test_when_the_tenancy_ends_the_chat_is_archived_and_read_only(): void
    {
        [$owner, $accountId] = $this->landlord();
        [$tenant, $tenancy] = $this->tenant($accountId);
        $chat = $this->chatFor(Conversation::KIND_TENANCY, $tenancy->id);
        $this->asAccount($tenant, $accountId)->post(route('portal.messages.send', $chat), ['body' => 'Moving out Friday']);

        $tenancy->update(['status' => 'Archived']);
        $chat->refresh();
        $this->assertSame(Conversation::ARCHIVED, $chat->status);

        $this->asAccount($owner, $accountId)->get(route('admin.messages.index'))->assertDontSee('data-msg-item="'.$chat->public_id.'"', false);
        $this->asAccount($owner, $accountId)->get(route('admin.messages.archived'))->assertSee('data-msg-item="'.$chat->public_id.'"', false);
        $this->asAccount($tenant, $accountId)->get(route('portal.messages.show', $chat))->assertOk()->assertSee('Moving out Friday')->assertSee('archived');
        $this->asAccount($owner, $accountId)->post(route('admin.messages.send', $chat), ['body' => 'One more thing'])->assertSessionHas('messaging_error');
        $this->assertDatabaseMissing('messages', ['conversation_id' => $chat->id, 'body' => 'One more thing']);
    }

    // ── Rule 2: job chats ───────────────────────────────────────────────────

    public function test_an_assigned_repair_opens_a_three_way_chat_that_is_archived_when_signed_off(): void
    {
        [$owner, $accountId] = $this->landlord();
        [$tenant, $tenancy] = $this->tenant($accountId);
        $property = $tenancy->property;
        $contractor = $this->contractorUser();

        $offer = $this->repairOffer($accountId, $tenant, $contractor, RepairJobOffer::OFFER_SENT, $property);
        $this->assertNull($this->chatFor(Conversation::KIND_REPAIR_JOB, $offer->id), 'No chat before the job is assigned.');

        $offer->forceFill(['status' => RepairJobOffer::JOB_ACCEPTED, 'job_status' => RepairJobOffer::JOB_ACCEPTED_STATE])->save();
        $chat = $this->chatFor(Conversation::KIND_REPAIR_JOB, $offer->id);
        $this->assertNotNull($chat);
        $roles = $chat->participants->mapWithKeys(fn (ConversationParticipant $p) => [$p->party()->key() => $p->role])->sortKeys()->all();
        $expected = collect([
            'account:'.$accountId => ConversationParticipant::LANDLORD,
            'user:'.$contractor->id => ConversationParticipant::CONTRACTOR,
            'user:'.$tenant->id => ConversationParticipant::TENANT,
        ])->sortKeys()->all();
        $this->assertSame($expected, $roles);
        $this->assertTrue(Message::query()->where('conversation_id', $chat->id)->where('kind', Message::SYSTEM)->exists());
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $tenant->id, 'event_key' => 'message.received']);

        $this->actingAs($contractor)->post(route('contractor.messages.send', $chat), ['body' => 'Can I come Tuesday at 10?'])->assertRedirect();
        $this->asAccount($tenant, $accountId)->get(route('portal.messages.show', $chat))->assertOk()->assertSee('Can I come Tuesday at 10?')->assertSee('Contractor');
        $this->asAccount($tenant, $accountId)->post(route('portal.messages.send', $chat), ['body' => 'Tuesday works']);
        $this->asAccount($owner, $accountId)->get(route('admin.messages.show', $chat))->assertOk()->assertSee('Tuesday works')->assertSee('Can I come Tuesday at 10?');

        // Contractor marking it complete keeps the chat open for snagging; landlord sign-off archives it.
        $offer->forceFill(['job_status' => RepairJobOffer::JOB_COMPLETED])->save();
        $this->assertSame(Conversation::ACTIVE, $chat->fresh()->status);
        $offer->forceFill(['job_status' => RepairJobOffer::JOB_CONFIRMED])->save();
        $chat->refresh();
        $this->assertSame(Conversation::ARCHIVED, $chat->status);
        $this->assertStringContainsString('signed off', $chat->archived_reason);
        $this->actingAs($contractor)->post(route('contractor.messages.send', $chat), ['body' => 'late'])->assertSessionHas('messaging_error');
        $this->actingAs($contractor)->get(route('contractor.messages.archived'))->assertSee('data-msg-item="'.$chat->public_id.'"', false);
    }

    public function test_a_cancelled_job_archives_its_chat(): void
    {
        [$owner, $accountId] = $this->landlord();
        $contractor = $this->contractorUser();
        $offer = $this->repairOffer($accountId, $owner, $contractor);
        $chat = $this->chatFor(Conversation::KIND_REPAIR_JOB, $offer->id);
        $this->assertCount(2, $chat->participants, 'Empty property: landlord and contractor only.');

        $offer->forceFill(['status' => RepairJobOffer::CANCELLED])->save();
        $this->assertSame(Conversation::ARCHIVED, $chat->fresh()->status);
        $this->assertStringContainsString('cancelled', $chat->fresh()->archived_reason);
    }

    public function test_compliance_job_chat_includes_every_tenant_at_the_property(): void
    {
        [$owner, $accountId] = $this->landlord();
        [$tenantA, $tenancyA] = $this->tenant($accountId);
        [$tenantB] = $this->tenant($accountId, 'Active', $tenancyA->property);
        [$elsewhere] = $this->tenant($accountId);
        $contractor = $this->contractorUser();

        $offer = $this->complianceOffer($accountId, $owner, $contractor, $tenancyA->property);
        $chat = $this->chatFor(Conversation::KIND_COMPLIANCE_JOB, $offer->id);
        $this->assertTrue($chat->hasParticipant(Party::user($tenantA->id)));
        $this->assertTrue($chat->hasParticipant(Party::user($tenantB->id)));
        $this->assertFalse($chat->hasParticipant(Party::user($elsewhere->id)));
        $this->asAccount($elsewhere, $accountId)->get(route('portal.messages.show', $chat))->assertNotFound();

        $offer->forceFill(['job_status' => ComplianceJobOffer::JOB_CONFIRMED])->save();
        $this->assertSame(Conversation::ARCHIVED, $chat->fresh()->status);
    }

    public function test_in_a_shared_house_only_the_reporting_tenants_household_joins_a_repair_chat(): void
    {
        [$owner, $accountId] = $this->landlord();
        [$roomOne, $tenancyOne] = $this->tenant($accountId);
        [$roomTwo] = $this->tenant($accountId, 'Active', $tenancyOne->property);
        $contractor = $this->contractorUser();

        $offer = $this->repairOffer($accountId, $roomOne, $contractor, RepairJobOffer::JOB_ACCEPTED, $tenancyOne->property);
        $chat = $this->chatFor(Conversation::KIND_REPAIR_JOB, $offer->id);
        $this->assertTrue($chat->hasParticipant(Party::user($roomOne->id)));
        $this->assertFalse($chat->hasParticipant(Party::user($roomTwo->id)));
    }

    public function test_a_tenant_moving_in_during_a_job_joins_its_chat(): void
    {
        [$owner, $accountId] = $this->landlord();
        $property = $this->property($accountId);
        $offer = $this->repairOffer($accountId, $owner, $this->contractorUser(), RepairJobOffer::JOB_ACCEPTED, $property);
        $chat = $this->chatFor(Conversation::KIND_REPAIR_JOB, $offer->id);
        $this->assertCount(2, $chat->participants);

        [$newTenant] = $this->tenant($accountId, 'Active', $property);
        $this->assertTrue($chat->fresh()->load('participants')->hasParticipant(Party::user($newTenant->id)));
    }

    public function test_questions_on_the_job_offer_are_for_before_award_only(): void
    {
        [$owner, $accountId] = $this->landlord();
        $contractor = $this->contractorUser();
        $offer = $this->repairOffer($accountId, $owner, $contractor, RepairJobOffer::OFFER_SENT);
        $this->assertTrue($offer->canMessage());

        $offer->forceFill(['status' => RepairJobOffer::JOB_ACCEPTED])->save();
        $this->assertFalse($offer->fresh()->canMessage());
        $this->expectException(ContractorMarketplaceException::class);
        app(RepairJobAftercareService::class)->postMessage($offer->fresh(), $contractor, 'contractor', 'after award');
    }

    // ── Rule 3: nobody starts other chats ───────────────────────────────────

    public function test_there_is_no_way_to_start_a_chat(): void
    {
        foreach (['admin', 'portal', 'contractor'] as $area) {
            foreach (['create', 'start', 'requests', 'requests.store', 'block', 'unsend', 'archive', 'mute'] as $name) {
                $this->assertFalse(Route::has($area.'.messages.'.$name), "{$area}.messages.{$name} should not exist");
            }
        }
        $this->assertFalse(Route::has('backend.saas.message-reports.index'));
        foreach (['message_requests', 'message_blocks', 'message_reports', 'messaging_party_flags'] as $table) {
            $this->assertFalse(DB::getSchemaBuilder()->hasTable($table), "{$table} should be gone");
        }

        [$owner, $accountId] = $this->landlord();
        $this->asAccount($owner, $accountId)->post('/admin/messages', ['body' => 'hi'])->assertStatus(405);
        $this->asAccount($owner, $accountId)->get('/admin/messages/new')->assertNotFound();
    }

    public function test_an_unrelated_contractor_or_a_losing_bidder_cannot_see_the_job_chat(): void
    {
        [$owner, $accountId] = $this->landlord();
        [$tenant, $tenancy] = $this->tenant($accountId);
        $winner = $this->contractorUser();
        $offer = $this->repairOffer($accountId, $tenant, $winner, RepairJobOffer::JOB_ACCEPTED, $tenancy->property);
        $loser = $this->contractorUser();
        $this->repairOffer($accountId, $tenant, $loser, RepairJobOffer::NOT_SELECTED, null, (int) $offer->repair_issue_id);
        $chat = $this->chatFor(Conversation::KIND_REPAIR_JOB, $offer->id);

        $this->actingAs($loser)->get(route('contractor.messages.show', $chat))->assertNotFound();
        $this->actingAs($loser)->post(route('contractor.messages.send', $chat), ['body' => 'x'])->assertNotFound();
        $this->actingAs($loser)->get(route('contractor.messages.index'))->assertOk()->assertDontSee('data-msg-item="'.$chat->public_id.'"', false);
        // The contractor never sees the tenancy chat.
        $tenancyChat = $this->chatFor(Conversation::KIND_TENANCY, $tenancy->id);
        $this->actingAs($winner)->get(route('contractor.messages.show', $tenancyChat))->assertNotFound();
    }

    // ── Rollout and safety net ──────────────────────────────────────────────

    public function test_tenancies_and_jobs_from_before_messaging_get_chats_when_the_inbox_opens(): void
    {
        [$owner, $accountId] = $this->landlord();
        $contractor = $this->contractorUser();
        [$tenancy, $offer] = Tenancy::withoutEvents(fn () => RepairJobOffer::withoutEvents(function () use ($accountId, $owner, $contractor) {
            $property = $this->property($accountId);
            $tenancy = Tenancy::create(['account_id' => $accountId, 'property_id' => $property->id, 'status' => 'Active', 'rent' => 900]);

            return [$tenancy, $this->repairOffer($accountId, $owner, $contractor, RepairJobOffer::JOB_ACCEPTED, $property)];
        }));
        $this->assertNull($this->chatFor(Conversation::KIND_TENANCY, $tenancy->id));
        $this->assertNull($this->chatFor(Conversation::KIND_REPAIR_JOB, $offer->id));

        $this->asAccount($owner, $accountId)->get(route('admin.messages.index'))->assertOk();
        $this->assertNotNull($this->chatFor(Conversation::KIND_TENANCY, $tenancy->id));
        $this->assertNotNull($this->chatFor(Conversation::KIND_REPAIR_JOB, $offer->id));
    }

    public function test_daily_sync_archives_jobs_changed_without_model_events(): void
    {
        [$owner, $accountId] = $this->landlord();
        $offer = $this->repairOffer($accountId, $owner, $this->contractorUser());
        $chat = $this->chatFor(Conversation::KIND_REPAIR_JOB, $offer->id);
        DB::table('repair_job_offers')->where('id', $offer->id)->update(['job_status' => RepairJobOffer::JOB_INVOICED]);

        $this->artisan('messaging:sync')->assertSuccessful();
        $this->assertSame(Conversation::ARCHIVED, $chat->fresh()->status);
    }

    // ── Files and reports ───────────────────────────────────────────────────

    public function test_files_are_private_to_the_chat(): void
    {
        [$owner, $accountId] = $this->landlord();
        [$tenant, $tenancy] = $this->tenant($accountId);
        $chat = $this->chatFor(Conversation::KIND_TENANCY, $tenancy->id);

        $this->asAccount($tenant, $accountId)->post(route('portal.messages.send', $chat), [
            'files' => [UploadedFile::fake()->image('damp.jpg')],
        ])->assertRedirect();
        $file = MessageAttachment::query()->latest('id')->firstOrFail();
        $this->assertStringStartsWith('private:', $file->getRawOriginal('stored_path'));

        $this->asAccount($owner, $accountId)->get(route('admin.messages.download', [$chat, $file]))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        [$other] = $this->tenant($accountId);
        $this->asAccount($other, $accountId)->get(route('portal.messages.download', [$chat, $file]))->assertNotFound();

        $this->asAccount($tenant, $accountId)->post(route('portal.messages.send', $chat), [
            'files' => [UploadedFile::fake()->create('tool.exe', 5)],
        ])->assertSessionHas('messaging_error');
    }

    public function test_reporting_a_chat_only_sends_a_note_to_support(): void
    {
        [$owner, $accountId] = $this->landlord();
        [$tenant, $tenancy] = $this->tenant($accountId);
        $chat = $this->chatFor(Conversation::KIND_TENANCY, $tenancy->id);

        $this->asAccount($tenant, $accountId)->post(route('portal.messages.report', $chat), ['note' => 'Rude messages late at night'])
            ->assertRedirect(route('portal.messages.show', $chat))
            ->assertSessionHas('messaging_status');
        $this->assertSame(Conversation::ACTIVE, $chat->fresh()->status, 'Reporting does not block or close anything.');
    }
}
