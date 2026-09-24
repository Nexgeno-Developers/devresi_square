<?php

namespace Tests\Feature;

use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Launch Step 11: secure uploads — reject dangerous types, accept allowlisted docs.
 */
class SecureUploadHttpTest extends TestCase
{
    public function test_php_upload_is_rejected(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        [$user, $accountId] = $this->createLandlord();

        $file = UploadedFile::fake()->create('shell.php', 12, 'application/x-php');

        $response = $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->post('/aiz-uploader/upload', [
                'aiz_file' => $file,
            ]);

        $response->assertStatus(422);
        $this->assertSame(0, Upload::query()->where('account_id', $accountId)->count());
        Storage::disk('public')->assertDirectoryEmpty('uploads');
        Storage::disk('local')->assertDirectoryEmpty('uploads');
    }

    public function test_svg_upload_is_rejected(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        [$user, $accountId] = $this->createLandlord();

        $file = UploadedFile::fake()->create('icon.svg', 8, 'image/svg+xml');

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->post('/aiz-uploader/upload', [
                'aiz_file' => $file,
            ])
            ->assertStatus(422);

        $this->assertSame(0, Upload::query()->where('account_id', $accountId)->count());
    }

    public function test_pdf_upload_is_accepted_on_private_disk(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        [$user, $accountId] = $this->createLandlord();

        $file = UploadedFile::fake()->create('tenancy-agreement.pdf', 64, 'application/pdf');

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->post('/aiz-uploader/upload', [
                'aiz_file' => $file,
            ])
            ->assertOk();

        $upload = Upload::query()->where('account_id', $accountId)->latest('id')->first();
        $this->assertNotNull($upload);
        $this->assertSame('pdf', $upload->extension);
        $this->assertSame('document', $upload->type);
        $this->assertSame($accountId, (int) $upload->account_id);
        $this->assertTrue(str_starts_with((string) $upload->file_name, 'private:'));
        $this->assertStringNotContainsString('.pdf.php', (string) $upload->file_name);

        $relative = substr((string) $upload->file_name, strlen('private:'));
        Storage::disk('local')->assertExists($relative);
        Storage::disk('public')->assertMissing($relative);
    }

    public function test_png_upload_is_accepted_on_public_disk(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        [$user, $accountId] = $this->createLandlord();

        $file = UploadedFile::fake()->image('photo.png', 120, 80);

        $this->actingAs($user)
            ->withSession(['current_account_id' => $accountId])
            ->post('/aiz-uploader/upload', [
                'aiz_file' => $file,
            ])
            ->assertOk();

        $upload = Upload::query()->where('account_id', $accountId)->latest('id')->first();
        $this->assertNotNull($upload);
        $this->assertSame('image', $upload->type);
        $this->assertFalse(str_starts_with((string) $upload->file_name, 'private:'));
        Storage::disk('public')->assertExists($upload->file_name);
    }

    public function test_cross_account_download_is_blocked(): void
    {
        Storage::fake('local');

        [$landlordA, $accountA] = $this->createLandlord();
        [$landlordB, $accountB] = $this->createLandlord();

        $relative = 'uploads/private/secret.pdf';
        Storage::disk('local')->put($relative, '%PDF-1.4 test');

        $upload = Upload::create([
            'account_id' => $accountB,
            'file_original_name' => 'secret',
            'file_name' => 'private:'.$relative,
            'user_id' => $landlordB->id,
            'extension' => 'pdf',
            'type' => 'document',
            'file_size' => 12,
        ]);

        $this->actingAs($landlordA)
            ->withSession(['current_account_id' => $accountA])
            ->get(route('download_attachment', $upload->id))
            ->assertStatus(403);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createLandlord(): array
    {
        $role = Role::findOrCreate('Landlord', 'web');

        $user = User::create([
            'name' => 'Upload Landlord '.uniqid(),
            'email' => 'upload-landlord-'.uniqid().'@resisquare.test',
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
}
