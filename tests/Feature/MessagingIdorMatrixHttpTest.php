<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageRequest;
use App\Models\RepairJobOffer;
use App\Services\Messaging\MessageRequestService;
use App\Support\Messaging\Party;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\Feature\Concerns\MessagingFixtures;
use Tests\TestCase;

/**
 * Every messaging route, tried by people who must not get in:
 * another account, revoked staff, another tenant, an unrelated contractor,
 * a portal user on workspace routes, Super Admin, and mismatched ids.
 */
class MessagingIdorMatrixHttpTest extends TestCase
{
    use DatabaseTransactions;
    use MessagingFixtures;

    private Conversation $conversation;

    private Message $message;

    private MessageAttachment $attachment;

    private MessageRequest $pendingRequest;

    private int $accountA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMessaging();
        Mail::fake();
        Storage::fake('local');
    }

    /** Account A + its tenant with a conversation, a file, and a pending request to a contractor. */
    private function scenario(): array
    {
        [$owner, $this->accountA] = $this->landlord();
        [$tenant, $tenancy] = $this->tenant($this->accountA);
        $this->asAccount($tenant, $this->accountA)->get(route('portal.messages.start', ['context' => 'tenancy', 'id' => $tenancy->id]));
        $this->conversation = Conversation::query()->latest('id')->firstOrFail();
        $this->asAccount($tenant, $this->accountA)->post(route('portal.messages.send', $this->conversation), [
            'body' => 'private note', 'files' => [UploadedFile::fake()->image('room.jpg')],
        ]);
        $this->message = Message::query()->where('conversation_id', $this->conversation->id)->latest('id')->firstOrFail();
        $this->attachment = MessageAttachment::query()->latest('id')->firstOrFail();
        $contractor = $this->contractorUser();
        $this->pendingRequest = app(MessageRequestService::class)->send(Party::account($this->accountA), Party::user($contractor->id), $owner, 'hello');

        return [$owner, $tenant, $tenancy, $contractor];
    }

    /** @return array<int, array{0: string, 1: string, 2: array}> */
    private function conversationRoutes(string $area): array
    {
        $c = $this->conversation;

        return [
            ['get', $area.'.messages.show', [$c]],
            ['get', $area.'.messages.poll', [$c]],
            ['post', $area.'.messages.send', [$c]],
            ['delete', $area.'.messages.unsend', [$c, $this->message]],
            ['get', $area.'.messages.download', [$c, $this->attachment]],
            ['post', $area.'.messages.archive', [$c]],
            ['post', $area.'.messages.mute', [$c]],
            ['post', $area.'.messages.block', [$c]],
            ['post', $area.'.messages.unblock', [$c]],
            ['post', $area.'.messages.report', [$c]],
        ];
    }

    private function assertAllDenied($client, array $routes, array $data = ['body' => 'x', 'category' => 'spam']): void
    {
        foreach ($routes as [$method, $name, $params]) {
            $response = $client()->{$method}(route($name, $params), $method === 'get' ? [] : $data);
            $this->assertContains($response->status(), [403, 404], "{$name} returned {$response->status()}");
        }
    }

    public function test_another_account_cannot_reach_any_conversation_route(): void
    {
        $this->scenario();
        [$ownerB, $accountB] = $this->landlord();
        $this->assertAllDenied(fn () => $this->asAccount($ownerB, $accountB), $this->conversationRoutes('admin'));

        $this->asAccount($ownerB, $accountB)->get(route('admin.messages.index'))->assertOk()->assertDontSee('private note');
        $this->assertSame(1, Message::query()->where('conversation_id', $this->conversation->id)->count(), 'Nothing was posted by account B');
    }

    public function test_staff_with_a_revoked_membership_lose_access_immediately(): void
    {
        $this->scenario();
        $revoked = $this->staff($this->accountA, 'disabled');
        $this->asAccount($revoked, $this->accountA)->get(route('admin.messages.index'))->assertForbidden();
        $this->assertAllDenied(fn () => $this->asAccount($revoked, $this->accountA), $this->conversationRoutes('admin'));
    }

    public function test_tenants_cannot_use_workspace_routes_or_other_tenants_conversations(): void
    {
        [, $tenant] = $this->scenario();
        $this->asAccount($tenant, $this->accountA)->get(route('admin.messages.index'))->assertForbidden();
        $this->asAccount($tenant, $this->accountA)->get(route('admin.messages.show', $this->conversation))->assertForbidden();

        [$otherTenant] = $this->tenant($this->accountA);
        $this->assertAllDenied(fn () => $this->asAccount($otherTenant, $this->accountA), $this->conversationRoutes('portal'));
    }

    public function test_unrelated_contractor_cannot_reach_the_conversation_or_the_request(): void
    {
        $this->scenario();
        $stranger = $this->contractorUser();
        $this->assertAllDenied(fn () => $this->actingAs($stranger), $this->conversationRoutes('contractor'));
        foreach (['accept', 'decline', 'block', 'report'] as $action) {
            $status = $this->actingAs($stranger)->post(route('contractor.messages.requests.'.$action, $this->pendingRequest), ['category' => 'spam'])->status();
            $this->assertSame(404, $status, $action);
        }
        $this->assertSame(MessageRequest::PENDING, $this->pendingRequest->fresh()->status);
    }

    public function test_only_the_sender_can_withdraw_and_only_the_recipient_can_accept(): void
    {
        [$owner, , , $contractor] = $this->scenario();
        $this->actingAs($contractor)->post(route('contractor.messages.requests.withdraw', $this->pendingRequest))->assertNotFound();
        $this->asAccount($owner, $this->accountA)->post(route('admin.messages.requests.accept', $this->pendingRequest))->assertNotFound();
        $this->assertSame(MessageRequest::PENDING, $this->pendingRequest->fresh()->status);
    }

    public function test_switching_account_switches_the_inbox(): void
    {
        [$owner] = $this->scenario();
        [, $accountB] = $this->landlord();
        $this->member($accountB, $owner, 'admin');

        $this->asAccount($owner, $this->accountA)->get(route('admin.messages.show', $this->conversation))->assertOk();
        $this->asAccount($owner, $accountB)->get(route('admin.messages.show', $this->conversation))->assertNotFound();
        $this->asAccount($owner, $accountB)->get(route('admin.messages.index'))->assertOk()->assertDontSee('private note');
    }

    public function test_message_and_file_ids_from_another_conversation_do_not_bind(): void
    {
        [$owner] = $this->scenario();
        [$tenant2, $tenancy2] = $this->tenant($this->accountA);
        $this->asAccount($tenant2, $this->accountA)->get(route('portal.messages.start', ['context' => 'tenancy', 'id' => $tenancy2->id]));
        $second = Conversation::query()->latest('id')->firstOrFail();

        // Owner is in both conversations, but ids must match the conversation in the URL.
        $this->asAccount($owner, $this->accountA)->delete(route('admin.messages.unsend', [$second, $this->message]))->assertNotFound();
        $this->asAccount($owner, $this->accountA)->get(route('admin.messages.download', [$second, $this->attachment]))->assertNotFound();
        $this->assertNotSoftDeleted($this->message);
    }

    public function test_start_links_refuse_other_peoples_records(): void
    {
        [$owner, $tenant, $tenancy] = $this->scenario();
        [$ownerB, $accountB] = $this->landlord();
        [$tenantB, $tenancyB, $memberB] = $this->tenant($accountB);
        $contractor = $this->contractorUser();
        $offerB = $this->repairOffer($accountB, $ownerB, $contractor, RepairJobOffer::JOB_ACCEPTED);
        $openOffer = $this->repairOffer($this->accountA, $owner, $contractor, RepairJobOffer::OFFER_SENT);

        $this->asAccount($tenant, $this->accountA)->get(route('portal.messages.start', ['context' => 'tenancy', 'id' => $tenancyB->id]))->assertNotFound();
        $this->asAccount($owner, $this->accountA)->get(route('admin.messages.start', ['context' => 'tenant', 'id' => $memberB->id]))->assertNotFound();
        $this->asAccount($owner, $this->accountA)->get(route('admin.messages.start', ['context' => 'repair-job', 'id' => $offerB->id]))->assertNotFound();
        // Not awarded yet: talk in the job chat, not a direct conversation.
        $this->actingAs($contractor)->get(route('contractor.messages.start', ['context' => 'repair-job', 'id' => $openOffer->id]))->assertNotFound();
        $this->actingAs($this->contractorUser())->get(route('contractor.messages.start', ['context' => 'repair-job', 'id' => $offerB->id]))->assertNotFound();
        $this->asAccount($owner, $this->accountA)->get(route('admin.messages.create', ['contractor' => $contractor->id]))->assertNotFound();
    }

    public function test_super_admin_and_disabled_feature(): void
    {
        $this->scenario();
        $admin = $this->user('Root');
        $admin->assignRole(Role::findOrCreate('Super Admin', 'web'));
        $this->assertAllDenied(fn () => $this->asAccount($admin, $this->accountA), $this->conversationRoutes('admin'));

        config(['messaging.enabled' => false]);
        [$ownerB, $accountB] = $this->landlord();
        $this->asAccount($ownerB, $accountB)->get(route('admin.messages.index'))->assertNotFound();
    }

    public function test_portal_contacts_get_a_personal_inbox_but_not_the_workspace_one(): void
    {
        [, $accountId] = $this->landlord();
        $contact = $this->user('Owner contact');
        $this->member($accountId, $contact, 'owner_contact');

        $this->asAccount($contact, $accountId)->get(route('portal.messages.index'))->assertOk();
        $this->asAccount($contact, $accountId)->get(route('admin.messages.index'))->assertForbidden();

        $noLogin = $this->user('No login contact');
        $this->member($accountId, $noLogin, 'contact', 'active', false);
        $this->asAccount($noLogin, $accountId)->get(route('portal.messages.index'))->assertForbidden();
    }
}
