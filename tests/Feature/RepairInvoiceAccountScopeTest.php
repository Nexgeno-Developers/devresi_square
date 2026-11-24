<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Repair invoices belong to the account of their work order; other accounts must not reach them. */
class RepairInvoiceAccountScopeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_an_agent_only_reaches_invoices_of_their_own_account(): void
    {
        [$agentA, $accountA] = $this->agentAccount();
        [, $accountB] = $this->agentAccount();
        $ours = $this->invoice($accountA, 'QA-OURS');
        $theirs = $this->invoice($accountB, 'QA-THEIRS');

        $as = fn () => $this->actingAs($agentA)->withSession(['current_account_id' => $accountA]);

        $as()->get(route('admin.invoices.index'))->assertOk()->assertSee('QA-OURS')->assertDontSee('QA-THEIRS');
        $as()->get(route('admin.invoices.show', $ours->id))->assertOk();
        foreach (['admin.invoices.show', 'admin.invoices.download', 'admin.invoices.edit'] as $name) {
            $as()->get(route($name, $theirs->id))->assertNotFound();
        }
        $as()->getJson(route('admin.invoices.json', $theirs))->assertNotFound();
        $found = collect($as()->getJson(route('admin.invoices.search', ['q' => 'QA-']))->json('results'))->pluck('id');
        $this->assertTrue($found->contains($ours->id));
        $this->assertFalse($found->contains($theirs->id));

        $as()->postJson(route('admin.invoices.mark_paid', $theirs->id))->assertNotFound();
        $this->assertSame(1, (int) $theirs->fresh()->status_id);
        $as()->postJson(route('admin.invoices.mark_paid', $ours->id))->assertOk();
        $this->assertSame(2, (int) $ours->fresh()->status_id);

        $as()->postJson(route('admin.invoices.generate', $theirs->work_order_id))->assertNotFound();
    }

    public function test_the_status_filter_uses_the_same_ids_as_the_badges(): void
    {
        [$agent, $account] = $this->agentAccount();
        $partly = $this->invoice($account, 'QA-PARTLY');
        $partly->forceFill(['status_id' => 3])->save();

        $as = fn () => $this->actingAs($agent)->withSession(['current_account_id' => $account]);
        $as()->get(route('admin.invoices.index', ['status' => 'partially_paid']))->assertSee('QA-PARTLY');
        $as()->get(route('admin.invoices.index', ['status' => 'overdue']))->assertDontSee('QA-PARTLY');
    }

    /** @return array{0: User, 1: int} */
    private function agentAccount(): array
    {
        $user = User::create([
            'name' => 'Agent '.uniqid(),
            'email' => 'agent-'.uniqid().'@resisquare.test',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);
        $user->assignRole(Role::findOrCreate('Estate Agent', 'web'));
        $accountId = \DB::table('accounts')->insertGetId([
            'owner_user_id' => $user->id,
            'account_type' => 'estate_agent_company',
            'status' => 'active',
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

    private function invoice(int $accountId, string $number): Invoice
    {
        $propertyId = \DB::table('properties')->insertGetId(['account_id' => $accountId, 'created_at' => now(), 'updated_at' => now()]);
        $repairId = \DB::table('repair_issues')->insertGetId([
            'account_id' => $accountId,
            'property_id' => $propertyId,
            'repair_category_id' => \DB::table('repair_categories')->value('id') ?? 1,
            'repair_navigation' => '[]',
            'description' => 'QA',
            'tenant_id' => \DB::table('account_users')->where('account_id', $accountId)->value('user_id'),
            'priority' => 'Medium',
            'sub_status' => 'Reported',
            'status' => 'Pending',
            'reference_number' => 'QA-REP-'.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $workOrder = WorkOrder::withoutGlobalScopes()->forceCreate([
            'works_order_no' => 'WO-'.$number.'-'.uniqid(),
            'repair_issue_id' => $repairId,
            'account_id' => $accountId,
        ]);

        return Invoice::forceCreate([
            'invoice_number' => $number,
            'work_order_id' => $workOrder->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'subtotal' => 100,
            'tax_amount' => 0,
            'total_amount' => 100,
            'status_id' => 1,
        ]);
    }
}
