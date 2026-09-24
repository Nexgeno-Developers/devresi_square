<?php

namespace Tests\Feature;

use App\Enums\CrmNotificationEvent;
use App\Models\ComplianceRecord;
use App\Models\ComplianceType;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\NotificationLog;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\TenantMember;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentsHttpTest extends TestCase
{
    public function test_document_save_requires_name_and_type_and_shows_title_not_untitled(): void
    {
        Storage::fake('public');

        [$landlord, $accountId] = $this->createLandlord();
        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '5 Docs Lane',
            'city' => 'London',
            'postcode' => 'N1 1AA',
        ]);
        $session = ['current_account_id' => $accountId];

        $type = DocumentType::query()->orderBy('id')->first()
            ?: DocumentType::create(['name' => 'Gas Safety', 'status' => 1]);

        $upload = Upload::create([
            'account_id' => $accountId,
            'file_original_name' => 'certificate',
            'file_name' => 'uploads/all/certificate.pdf',
            'user_id' => $landlord->id,
            'extension' => 'pdf',
            'type' => 'document',
            'file_size' => 32,
        ]);
        Storage::disk('public')->put('uploads/all/certificate.pdf', 'pdf-bytes');

        $this->actingAs($landlord)->withSession($session)
            ->postJson(route('admin.documents.save'), [
                'documentable_type' => Property::class,
                'documentable_id' => $property->id,
                'upload_ids' => (string) $upload->id,
                'share_with_tenant' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'document_type_id']);

        $this->actingAs($landlord)->withSession($session)
            ->postJson(route('admin.documents.save'), [
                'documentable_type' => Property::class,
                'documentable_id' => $property->id,
                'upload_ids' => (string) $upload->id,
                'title' => 'Gas safety certificate 2026',
                'document_type_id' => $type->id,
                'share_with_tenant' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $document = Document::query()->forAccount($accountId)->first();
        $this->assertNotNull($document);
        $this->assertSame('Gas safety certificate 2026', $document->title);
        $this->assertSame('portal', $document->visibility);

        $this->actingAs($landlord)->withSession($session)
            ->get(route('admin.documents.index'))
            ->assertOk()
            ->assertSee('Gas safety certificate 2026', false)
            ->assertDontSee('>Untitled<', false);
    }

    public function test_shared_files_reach_the_tenant_and_private_files_do_not(): void
    {
        Storage::fake('public');
        config(['crm_notifications.enabled' => true]);

        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenant($accountId);
        [$other] = $this->createTenant($accountId);
        [$property, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];
        $type = DocumentType::query()->orderBy('id')->first()
            ?: DocumentType::create(['name' => 'Tenancy agreement', 'status' => 1]);

        $upload = $this->makeUpload($accountId, $landlord, 'agreement.pdf');
        $document = Document::create([
            'account_id' => $accountId,
            'documentable_type' => $property->getMorphClass(),
            'documentable_id' => $property->id,
            'upload_ids' => (string) $upload->id,
            'document_type_id' => $type->id,
            'title' => 'Assured shorthold tenancy',
            'visibility' => 'private',
            'created_by' => $landlord->id,
        ]);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.documents'))
            ->assertOk()
            ->assertDontSee('Assured shorthold tenancy', false);

        $before = NotificationLog::query()->where('identifier', CrmNotificationEvent::DocumentShared->value)->count();

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.documents.share', $document), ['share_with_tenant' => 1])
            ->assertRedirect();

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.documents'))
            ->assertOk()
            ->assertSee('Assured shorthold tenancy', false);

        $notice = NotificationLog::query()
            ->where('identifier', CrmNotificationEvent::DocumentShared->value)
            ->where('subject_id', $document->id)
            ->where('notifiable_id', $tenant->id)
            ->first();
        $this->assertNotNull($notice);
        $this->assertSame(route('tenant.documents'), $notice->payload['action_url'] ?? null);
        $this->assertGreaterThan($before, NotificationLog::query()->where('identifier', CrmNotificationEvent::DocumentShared->value)->count());

        $this->actingAs($other)->withSession($session)
            ->get(route('tenant.documents.download', $document))
            ->assertNotFound();

        $this->get(route('tenant.documents.download', $document))
            ->assertNotFound();

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.documents.download', $document))
            ->assertOk();

        $sharedCount = NotificationLog::query()
            ->where('identifier', CrmNotificationEvent::DocumentShared->value)
            ->where('subject_id', $document->id)
            ->count();

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.documents.share', $document), ['share_with_tenant' => 0])
            ->assertRedirect();

        $this->assertSame($sharedCount, NotificationLog::query()
            ->where('identifier', CrmNotificationEvent::DocumentShared->value)
            ->where('subject_id', $document->id)
            ->count());

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.documents'))
            ->assertOk()
            ->assertSee('Your landlord has not shared a document yet', false);
    }

    public function test_tenancy_documents_and_untitled_files_use_the_type_name(): void
    {
        Storage::fake('public');

        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenant($accountId);
        [, $tenancy] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];
        $type = DocumentType::query()->firstOrCreate(
            ['name' => 'Prescribed information'],
            ['description' => 'Deposit prescribed information']
        );
        $upload = $this->makeUpload($accountId, $landlord, 'storage-name-should-not-show.pdf');

        Document::create([
            'account_id' => $accountId,
            'documentable_type' => $tenancy->getMorphClass(),
            'documentable_id' => $tenancy->id,
            'upload_ids' => (string) $upload->id,
            'document_type_id' => $type->id,
            'title' => null,
            'visibility' => 'portal',
            'created_by' => $landlord->id,
        ]);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.documents'))
            ->assertOk()
            ->assertSee($type->name, false)
            ->assertDontSee('storage-name-should-not-show', false)
            ->assertDontSee('Untitled', false);
    }

    public function test_portal_documents_paginate_past_the_old_cap(): void
    {
        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenant($accountId);
        [$property] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];

        $agreement = Document::create([
            'account_id' => $accountId,
            'documentable_type' => $property->getMorphClass(),
            'documentable_id' => $property->id,
            'upload_ids' => '1',
            'title' => 'Older tenancy agreement',
            'visibility' => 'portal',
            'created_by' => $landlord->id,
        ]);
        $agreement->forceFill(['updated_at' => now()->subYear()])->save();

        for ($i = 1; $i <= 15; $i++) {
            Document::create([
                'account_id' => $accountId,
                'documentable_type' => $property->getMorphClass(),
                'documentable_id' => $property->id,
                'upload_ids' => '1',
                'title' => 'Newer photo '.$i,
                'visibility' => 'portal',
                'created_by' => $landlord->id,
            ]);
        }

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.documents'))
            ->assertOk()
            ->assertDontSee('Older tenancy agreement', false);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.documents', ['page' => 2]))
            ->assertOk()
            ->assertSee('Older tenancy agreement', false);
    }

    public function test_certificate_share_records_service_and_shows_in_docs(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        config(['crm_notifications.enabled' => true]);

        [$landlord, $accountId] = $this->createLandlord();
        [$tenant] = $this->createTenant($accountId);
        [$property] = $this->createLet($accountId, $landlord, $tenant);
        $session = ['current_account_id' => $accountId];
        $type = ComplianceType::query()->firstOrCreate(
            ['alias' => 'gas'],
            ['name' => 'Gas safety', 'description' => 'Gas safety certificate']
        );

        $this->actingAs($landlord)->withSession($session)
            ->post(route('admin.compliance.share'), [
                'property_id' => $property->id,
                'compliance_type_id' => $type->id,
                'certificate' => UploadedFile::fake()->image('gas.jpg'),
            ])
            ->assertRedirect(route('admin.compliance.index'));

        $record = ComplianceRecord::query()
            ->where('property_id', $property->id)
            ->where('compliance_type_id', $type->id)
            ->first();
        $this->assertNotNull($record);
        $this->assertNotNull($record->served_to_tenant_at);

        $this->actingAs($tenant)->withSession($session)
            ->get(route('tenant.documents'))
            ->assertOk()
            ->assertSee($type->name, false);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');
        $user = User::create([
            'name' => 'Docs Landlord',
            'email' => 'docs-landlord-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        $accountId = \DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'member_type' => 'owner',
            'access_level' => 'full',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $accountId];
    }

    /**
     * @return array{0: User}
     */
    private function createTenant(int $accountId): array
    {
        $role = Role::findOrCreate('Tenant', 'web');
        $user = User::create([
            'name' => 'Docs Tenant',
            'email' => 'docs-tenant-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        \DB::table('account_users')->insert([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'member_type' => 'tenant',
            'access_level' => 'full',
            'can_login' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user];
    }

    /**
     * @return array{0: Property, 1: Tenancy}
     */
    private function createLet(int $accountId, User $landlord, User $tenant): array
    {
        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $landlord->id,
            'line_1' => '5 Docs Lane',
            'city' => 'London',
            'postcode' => 'N1 1AA',
        ]);
        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 900,
            'frequency' => 'Monthly',
        ]);
        TenantMember::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'user_id' => $tenant->id,
            'is_main_person' => true,
            'can_login' => true,
        ]);

        return [$property, $tenancy];
    }

    private function makeUpload(int $accountId, User $landlord, string $name): Upload
    {
        $path = 'uploads/all/'.$name;
        Storage::disk('public')->put($path, 'file-bytes');

        return Upload::create([
            'account_id' => $accountId,
            'file_original_name' => pathinfo($name, PATHINFO_FILENAME),
            'file_name' => $path,
            'user_id' => $landlord->id,
            'extension' => pathinfo($name, PATHINFO_EXTENSION),
            'type' => 'document',
            'file_size' => 32,
        ]);
    }
}
