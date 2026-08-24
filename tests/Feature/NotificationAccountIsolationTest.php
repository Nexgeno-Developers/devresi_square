<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;
use Illuminate\Http\Request;
use App\Http\Controllers\Backend\NotificationController;

class NotificationAccountIsolationTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = DB::getDefaultConnection();
        config(['database.connections.notification_isolation_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::purge('notification_isolation_test');
        DB::setDefaultConnection('notification_isolation_test');
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        DB::disconnect('notification_isolation_test');
        DB::purge('notification_isolation_test');
        DB::setDefaultConnection($this->originalConnection);
        parent::tearDown();
    }

    public function test_shared_user_cannot_view_or_mark_another_accounts_notification(): void
    {
        $userId = DB::table('users')->insertGetId(['name' => 'Shared user', 'email' => 'shared@example.test', 'password' => 'x']);
        DB::table('accounts')->insert([
            ['id' => 10, 'account_name' => 'First', 'status' => 'active'],
            ['id' => 20, 'account_name' => 'Second', 'status' => 'active'],
        ]);
        DB::table('account_users')->insert([
            ['account_id' => 10, 'user_id' => $userId, 'member_type' => 'tenant', 'status' => 'active', 'can_login' => true],
            ['account_id' => 20, 'user_id' => $userId, 'member_type' => 'tenant', 'status' => 'active', 'can_login' => true],
        ]);

        $firstId = (string) Str::uuid();
        $secondId = (string) Str::uuid();
        $this->insertNotification($firstId, 10, $userId, 'First account message');
        $this->insertNotification($secondId, 20, $userId, 'Second account secret');

        $user = User::findOrFail($userId);
        $this->actingAs($user)->withSession(['current_account_id' => 10]);

        session(['current_account_id' => 10]);
        $request = Request::create('/notifications', 'GET');
        $request->setUserResolver(fn () => $user);
        $view = app(NotificationController::class)->index($request);
        $messages = collect($view->getData()['notifications']->items())->pluck('data.message');
        $this->assertContains('First account message', $messages);
        $this->assertNotContains('Second account secret', $messages);

        $this->post(route('backend.notifications.read', $secondId))->assertNotFound();
        $this->assertNull(DB::table('notifications')->where('id', $secondId)->value('read_at'));

        $this->post(route('backend.notifications.read', $firstId))->assertOk();
        $this->assertNotNull(DB::table('notifications')->where('id', $firstId)->value('read_at'));
    }

    private function insertNotification(string $id, int $accountId, int $userId, string $message): void
    {
        DB::table('notifications')->insert([
            'id' => $id, 'account_id' => $accountId, 'type' => 'crm_event', 'event_key' => 'offer.accepted',
            'category' => 'offers', 'priority' => 'normal', 'notifiable_type' => User::class,
            'notifiable_id' => $userId, 'data' => json_encode(['title' => 'Offer', 'message' => $message]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('email'); $table->string('password');
            $table->unsignedBigInteger('last_active_account_id')->nullable(); $table->rememberToken(); $table->timestamps();
        });
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id(); $table->string('account_name')->nullable(); $table->string('status')->nullable(); $table->string('timezone')->default('Europe/London'); $table->timestamps();
        });
        Schema::create('account_users', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('account_id'); $table->unsignedBigInteger('user_id');
            $table->string('member_type')->nullable(); $table->string('status')->nullable(); $table->boolean('can_login')->default(true); $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('guard_name')->default('web'); $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id'); $table->string('model_type'); $table->unsignedBigInteger('model_id');
        });
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary(); $table->unsignedBigInteger('account_id'); $table->string('type'); $table->string('event_key')->nullable();
            $table->string('category')->nullable(); $table->string('priority')->default('normal'); $table->text('action_url')->nullable();
            $table->string('notifiable_type'); $table->unsignedBigInteger('notifiable_id'); $table->text('data');
            $table->timestamp('read_at')->nullable(); $table->timestamps();
        });
    }
}
