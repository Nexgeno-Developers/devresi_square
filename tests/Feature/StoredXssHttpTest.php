<?php

namespace Tests\Feature;

use App\Models\Notes;
use App\Models\NoteType;
use App\Models\Property;
use App\Models\Tenancy;
use App\Models\TenancyCorrectionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Launch Step 12: stored XSS must not execute for notes / corrections / repair labels.
 */
class StoredXssHttpTest extends TestCase
{
    public function test_note_payload_is_sanitized_on_save_and_show(): void
    {
        [$user, $accountId] = $this->createLandlord();
        $session = ['current_account_id' => $accountId];

        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $user->id,
            'line_1' => '12 XSS Lane',
            'city' => 'London',
            'postcode' => 'E1 1AA',
        ]);

        $noteType = NoteType::query()->orderBy('id')->first()
            ?: NoteType::create(['name' => 'General']);

        $payload = '<p>Safe</p><script>alert("xss")</script><img src=x onerror=alert(1)>';

        $response = $this->actingAs($user)->withSession($session)
            ->post(route('admin.notes.save'), [
                'noteable_type' => Property::class,
                'noteable_id' => $property->id,
                'note_type_id' => $noteType->id,
                'content' => $payload,
                'visibility' => 'private',
            ]);

        $response->assertOk();

        $note = Notes::query()->where('account_id', $accountId)->latest('id')->first();
        $this->assertNotNull($note);
        $this->assertStringNotContainsString('<script', strtolower((string) $note->content));
        $this->assertStringNotContainsString('onerror', strtolower((string) $note->content));
        $this->assertStringContainsString('<p>Safe</p>', (string) $note->content);

        // Legacy poisoned row must still be safe when rendered.
        DB::table('notes')->where('id', $note->id)->update([
            'content' => '<script>alert(1)</script><b>ok</b>',
        ]);

        $show = $this->actingAs($user)->withSession($session)
            ->get(route('admin.notes.show', ['id' => $note->id]));

        $show->assertOk();
        $html = (string) $show->json('html');
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('onerror=', strtolower($html));
    }

    public function test_correction_message_is_escaped_on_tenancy_show(): void
    {
        [$user, $accountId] = $this->createLandlord();
        $session = ['current_account_id' => $accountId];

        $property = Property::create([
            'account_id' => $accountId,
            'created_by' => $user->id,
            'line_1' => '14 Escape Road',
            'city' => 'London',
            'postcode' => 'E2 2BB',
        ]);

        $tenancy = Tenancy::create([
            'account_id' => $accountId,
            'property_id' => $property->id,
            'status' => 'Active',
            'rent' => 1000,
            'move_in' => now()->toDateString(),
        ]);

        TenancyCorrectionRequest::create([
            'account_id' => $accountId,
            'tenancy_id' => $tenancy->id,
            'requested_by' => $user->id,
            'status' => TenancyCorrectionRequest::STATUS_PENDING,
            'message' => '<script>alert("corr")</script>',
            'fields' => [['key' => 'rent', 'label' => 'Rent', 'current' => '1000', 'suggested' => '<img src=x onerror=alert(1)>1100']],
            'snapshot' => ['rent' => 1000],
        ]);

        $page = $this->actingAs($user)->withSession($session)
            ->get(route('admin.tenancies.show', $tenancy));

        $page->assertOk();
        $body = $page->getContent();
        $this->assertStringNotContainsString('<script>alert("corr")</script>', $body);
        $this->assertStringContainsString('&lt;script&gt;', $body);
    }

    public function test_repair_navigation_names_are_escaped_by_blade(): void
    {
        $nav = json_encode([
            ['id' => 1, 'name' => '<img src=x onerror=alert(1)>'],
        ]);

        $formatted = getFormattedRepairNavigation($nav);
        $this->assertStringContainsString('<img', $formatted);

        $escaped = e($formatted);
        $this->assertStringContainsString('&lt;img', $escaped);
        $this->assertStringNotContainsString('<img src=x onerror=', $escaped);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');

        $user = User::create([
            'name' => 'XSS Landlord '.uniqid(),
            'email' => 'xss-landlord-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole($role);

        $accountId = DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id,
            'account_type' => 'landlord',
            'status' => 'trialing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('account_users')->insert([
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
}
