<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\MessageAttachment;
use App\Models\RepairJobOffer;
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

    private MessageAttachment $attachment;

    private int $accountA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMessaging();
        Mail::fake();
        Storage::fake('local');
    }

    /** Account A + its tenant with a tenancy chat holding a file. */
    private function scenario(): array
    {
        [$owner, $this->accountA] = $this->landlord();
        [$tenant, $tenancy] = $this->tenant($this->accountA);
        $this->conversation = $this->chatFor(Conversation::KIND_TENANCY, $tenancy->id);
        $this->asAccount($tenant, $this->accountA)->post(route('portal.messages.send', $this->conversation), [
            'body' => 'private note', 'files' => [UploadedFile::fake()->image('room.jpg')],
        ]);
        $this->attachment = MessageAttachment::query()->latest('id')->firstOrFail();

        return [$owner, $tenant, $tenancy];
    }

    /** @return array<int, array{0: string, 1: string, 2: array}> */
    private function conversationRoutes(string $area): array
    {
        $c = $this->conversation;

        return [
            ['get', $area.'.messages.show', [$c]],
            ['get', $area.'.messages.poll', [$c]],
            ['post', $area.'.messages.send', [$c]],
            ['get', $area.'.messages.download', [$c, $this->attachment]],
            ['post', $area.'.messages.report', [$c]],
        ];
    }

    private function assertAllDenied($client, array $routes, array $data = ['body' => 'x', 'note' => 'x']): void
    {
        foreach ($routes as [$method, $name, $params]) {
            $response = $client()->{$method}(route($name, $params), $method === 'get' ? [] : $data);
            $this->assertContains($response->status(), [403, 404], "{$name} returned {$response->status()}");
        }
    }

    public function test_another_account_cannot_reach_any_chat_route(): void
    {
        $this->scenario();
        [$ownerB, $accountB] = $this->landlord();
        $this->assertAllDenied(fn () => $this->asAccount($ownerB, $accountB), $this->conversationRoutes('admin'));
        $this->asAccount($ownerB, $accountB)->get(route('admin.messages.index'))->assertOk()->assertDontSee('private note');
    }

    public function test_staff_with_a_revoked_membership_lose_access_immediately(): void
    {
        $this->scenario();
        $revoked = $this->staff($this->accountA, 'disabled');
        $this->assertAllDenied(fn () => $this->asAccount($revoked, $this->accountA), $this->conversationRoutes('admin'));
    }

    public function test_tenants_cannot_use_workspace_routes_or_other_tenants_chats(): void
    {
        [, $tenant] = $this->scenario();
        $this->assertAllDenied(fn () => $this->asAccount($tenant, $this->accountA), $this->conversationRoutes('admin'));

        [$otherTenant] = $this->tenant($this->accountA);
        $this->assertAllDenied(fn () => $this->asAccount($otherTenant, $this->accountA), $this->conversationRoutes('portal'));
    }

    public function test_an_unrelated_contractor_cannot_reach_the_chat(): void
    {
        $this->scenario();
        $contractor = $this->contractorUser();
        $this->assertAllDenied(fn () => $this->actingAs($contractor), $this->conversationRoutes('contractor'));
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

    public function test_file_ids_from_another_chat_do_not_bind(): void
    {
        [$owner] = $this->scenario();
        [, $tenancy2] = $this->tenant($this->accountA);
        $second = $this->chatFor(Conversation::KIND_TENANCY, $tenancy2->id);

        // The owner is in both chats, but the file must belong to the chat in the URL.
        $this->asAccount($owner, $this->accountA)->get(route('admin.messages.download', [$second, $this->attachment]))->assertNotFound();
    }

    public function test_job_chat_of_another_account_is_out_of_reach(): void
    {
        $this->scenario();
        [$ownerB, $accountB] = $this->landlord();
        [$tenantB, $tenancyB] = $this->tenant($accountB);
        $offerB = $this->repairOffer($accountB, $tenantB, $this->contractorUser(), RepairJobOffer::JOB_ACCEPTED, $tenancyB->property);
        $this->conversation = $this->chatFor(Conversation::KIND_REPAIR_JOB, $offerB->id);

        [$tenantA] = $this->tenant($this->accountA);
        $this->assertAllDenied(fn () => $this->asAccount($tenantA, $this->accountA), array_slice($this->conversationRoutes('portal'), 0, 3));
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

    public function test_portal_contacts_without_a_tenancy_see_an_empty_inbox(): void
    {
        [, $accountId] = $this->landlord();
        $contact = $this->user('Owner contact');
        $this->member($accountId, $contact, 'owner_contact');

        $this->asAccount($contact, $accountId)->get(route('portal.messages.index'))->assertOk()->assertSee('No chats yet');
        $this->asAccount($contact, $accountId)->get(route('admin.messages.index'))->assertForbidden();
    }
}
