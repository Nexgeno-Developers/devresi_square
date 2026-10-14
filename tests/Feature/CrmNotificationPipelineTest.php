<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationJob;
use App\Models\User;
use App\Services\Notifications\CrmNotificationService;
use App\Services\Notifications\NotificationPreferenceService;
use App\Services\Notifications\NotificationRecipientResolver;
use App\Services\Notifications\NotificationPreferenceService as RealNotificationPreferenceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\Relation;
use Mockery;
use Tests\TestCase;

class PipelineSubject extends Model
{
    protected $table = 'pipeline_subjects';
    protected $guarded = [];
}

class CrmNotificationPipelineTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = DB::getDefaultConnection();
        config(['database.connections.crm_notification_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::purge('crm_notification_test');
        DB::setDefaultConnection('crm_notification_test');
        Relation::morphMap(['pipeline_subject' => PipelineSubject::class]);
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        DB::disconnect('crm_notification_test');
        DB::purge('crm_notification_test');
        DB::setDefaultConnection($this->originalConnection);
        parent::tearDown();
    }

    public function test_dispatch_is_account_scoped_idempotent_and_escapes_template_values(): void
    {
        Queue::fake();
        $user = User::query()->create(['name' => 'Recipient', 'email' => 'recipient@example.test', 'password' => 'unused']);
        $subject = PipelineSubject::query()->create(['account_id' => 10]);

        $resolver = Mockery::mock(NotificationRecipientResolver::class);
        $resolver->shouldReceive('resolve')->times(3)->andReturn(collect([$user]));
        $preferences = Mockery::mock(NotificationPreferenceService::class);
        $preferences->shouldReceive('channels')->times(3)->andReturn(['email', 'system']);
        $service = new CrmNotificationService($resolver, $preferences);

        $context = [
            'account_id' => 10,
            'milestone' => 'accepted-v1',
            'property_address' => '<script>alert(1)</script>',
            'action_url' => '/offers',
        ];
        $this->assertSame(2, $service->dispatch('offer.accepted', $subject, $context));
        $this->assertSame(0, $service->dispatch('offer.accepted', $subject, $context));

        $this->assertDatabaseCount('notification_logs', 2);
        $this->assertStringContainsString('&lt;script&gt;', DB::table('notification_logs')->value('subject'));
        $this->assertStringNotContainsString('<script>', DB::table('notification_logs')->value('message'));

        $this->assertSame(2, $service->dispatch('offer.accepted', $subject, [...$context, 'account_id' => 20]));
        $this->assertDatabaseCount('notification_logs', 4);
        Queue::assertPushed(SendNotificationJob::class, 4);
    }

    public function test_personal_preference_overrides_account_default_but_locked_email_stays_enabled(): void
    {
        $user = User::query()->create(['name' => 'Portal user', 'email' => 'portal@example.test', 'password' => 'unused']);
        DB::table('account_users')->insert(['account_id' => 10, 'user_id' => $user->id, 'status' => 'active', 'can_login' => true]);
        DB::table('notification_preferences')->insert([
            ['account_id' => 10, 'user_id' => null, 'event_key' => 'finance.invoice_issued', 'email_enabled' => false, 'in_app_enabled' => true],
            ['account_id' => 10, 'user_id' => $user->id, 'event_key' => 'finance.invoice_issued', 'email_enabled' => false, 'in_app_enabled' => false],
        ]);

        $channels = app(RealNotificationPreferenceService::class)->channels(10, $user, 'finance.invoice_issued', [
            'channels' => ['email', 'system'], 'locked_channels' => ['email'],
        ]);

        $this->assertSame(['email'], $channels);

        DB::table('notification_preferences')->where('user_id', $user->id)->update(['in_app_enabled' => true]);
        $channels = app(RealNotificationPreferenceService::class)->channels(10, $user, 'finance.invoice_issued', [
            'channels' => ['email', 'system'], 'locked_channels' => ['email'],
        ]);
        $this->assertEqualsCanonicalizing(['email', 'system'], $channels);
    }

    public function test_email_failure_still_delivers_the_in_app_notification(): void
    {
        config([
            'queue.default' => 'sync',
            'mail.default' => 'failing',
            'mail.mailers.failing' => ['transport' => 'failing'],
        ]);
        \Illuminate\Support\Facades\Mail::extend('failing', function () {
            return new class implements \Symfony\Component\Mailer\Transport\TransportInterface {
                public function send(\Symfony\Component\Mime\RawMessage $message, ?\Symfony\Component\Mailer\Envelope $envelope = null): ?\Symfony\Component\Mailer\SentMessage
                {
                    throw new \Symfony\Component\Mailer\Exception\TransportException('smtp down');
                }

                public function __toString(): string
                {
                    return 'failing';
                }
            };
        });
        app()->forgetInstance('mailer');
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('type');
            $table->string('event_key')->nullable();
            $table->string('category')->nullable();
            $table->string('priority')->nullable();
            $table->text('action_url')->nullable();
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        $user = User::query()->create(['name' => 'Landlord', 'email' => 'landlord@example.test', 'password' => 'unused']);
        DB::table('account_users')->insert([
            'account_id' => 10, 'user_id' => $user->id, 'status' => 'active', 'can_login' => true,
        ]);
        $subject = PipelineSubject::query()->create(['account_id' => 10]);
        $resolver = Mockery::mock(NotificationRecipientResolver::class);
        $resolver->shouldReceive('resolve')->once()->andReturn(collect([$user]));
        $preferences = Mockery::mock(NotificationPreferenceService::class);
        $preferences->shouldReceive('channels')->once()->andReturn(['email', 'system']);

        $created = (new CrmNotificationService($resolver, $preferences))->dispatch('offer.accepted', $subject, [
            'account_id' => 10,
            'milestone' => 'mail-down',
            'property_address' => '1 Test Street',
            'action_url' => '/offers',
        ]);
        $this->app->terminate();

        $this->assertSame(2, $created);
        $this->assertSame('failed', DB::table('notification_logs')->where('channel', 'email')->value('status'));
        $this->assertSame('sent', DB::table('notification_logs')->where('channel', 'system')->value('status'));
        $this->assertSame(1, DB::table('notifications')->count());
    }

    public function test_contractor_without_an_account_membership_still_gets_in_app_notifications(): void
    {
        $user = User::query()->create(['name' => 'Contractor', 'email' => 'contractor@example.test', 'password' => 'unused']);
        $roleId = DB::table('roles')->insertGetId(['name' => 'Contractor', 'guard_name' => 'web']);
        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);

        $channels = app(RealNotificationPreferenceService::class)->channels(10, $user, 'repair.job_invited', [
            'channels' => ['email', 'system'],
        ]);

        $this->assertEqualsCanonicalizing(['email', 'system'], $channels);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('email'); $table->string('password')->nullable(); $table->timestamps();
        });
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id(); $table->string('account_name')->nullable(); $table->string('billing_email')->nullable();
            $table->string('currency')->nullable(); $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('guard_name')->default('web'); $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id'); $table->string('model_type'); $table->unsignedBigInteger('model_id');
        });
        Schema::create('account_users', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('account_id'); $table->unsignedBigInteger('user_id');
            $table->string('status')->nullable(); $table->boolean('can_login')->default(true); $table->timestamps();
        });
        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('account_id'); $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event_key'); $table->boolean('email_enabled')->nullable(); $table->boolean('in_app_enabled')->nullable(); $table->timestamps();
        });
        Schema::create('pipeline_subjects', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('account_id'); $table->timestamps();
        });
        Schema::create('email_templates', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('account_id')->nullable(); $table->string('identifier');
            $table->string('subject')->nullable(); $table->text('default_text')->nullable(); $table->boolean('status')->default(true); $table->timestamps();
        });
        Schema::create('notification_logs', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('account_id')->nullable(); $table->string('identifier');
            $table->string('notifiable_type'); $table->unsignedBigInteger('notifiable_id');
            $table->string('subject_type')->nullable(); $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable(); $table->uuid('notification_uuid')->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique(); $table->string('channel'); $table->string('recipient')->nullable();
            $table->string('subject')->nullable(); $table->text('message'); $table->json('payload')->nullable();
            $table->string('status')->default('pending'); $table->unsignedInteger('attempt')->default(0);
            $table->unsignedInteger('max_attempts')->default(3); $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('last_attempt_at')->nullable(); $table->timestamp('sent_at')->nullable(); $table->text('error')->nullable(); $table->timestamps();
        });
    }
}
