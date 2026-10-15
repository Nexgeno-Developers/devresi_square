<?php

namespace App\Console\Commands;

use App\Mail\MailManager;
use App\Models\Account;
use App\Models\ComplianceRecord;
use App\Models\Property;
use App\Models\RentInvoice;
use App\Models\RentPayment;
use App\Models\RepairIssue;
use App\Models\Tenancy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendMonthlyPortfolioSummary extends Command
{
    protected $signature = 'accounts:monthly-summary';

    protected $description = 'Email each landlord last month’s portfolio summary.';

    public function handle(): int
    {
        $month = now()->subMonth();
        $label = $month->format('F Y');
        $sent = 0;

        Account::query()
            ->where('account_type', 'landlord')
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->with('owner')
            ->orderBy('id')
            ->each(function (Account $account) use ($month, $label, &$sent): void {
                $recipient = $account->owner?->email ?: $account->billing_email;
                if (! $recipient) {
                    return;
                }

                $key = 'portfolio-summary:'.$account->id.':'.$month->format('Y-m');
                if (! Cache::add($key, 1, now()->addDays(40))) {
                    return;
                }

                $total = Property::query()->where('account_id', $account->id)->count();
                $occupied = Tenancy::query()
                    ->where('account_id', $account->id)
                    ->where('status', 'Active')
                    ->distinct()
                    ->count('property_id');
                $rate = $total > 0 ? (int) round(($occupied / $total) * 100) : 0;
                $collected = (float) RentPayment::query()
                    ->whereHas('invoice', fn ($query) => $query->where('account_id', $account->id))
                    ->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                    ->sum('amount');
                $outstanding = (float) RentInvoice::query()
                    ->where('account_id', $account->id)
                    ->whereIn('status', [RentInvoice::STATUS_ISSUED, RentInvoice::STATUS_PARTIAL])
                    ->sum('balance');
                $repairs = RepairIssue::query()
                    ->where('account_id', $account->id)
                    ->whereNotIn('status', ['Completed', 'Closed', 'Cancelled', 'Rejected', 'Resolved', 'Invoice Paid'])
                    ->count();
                $compliance = ComplianceRecord::query()
                    ->whereHas('property', fn ($query) => $query->where('account_id', $account->id))
                    ->whereNull('completed_at')
                    ->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', now()->addDays(30))
                    ->count();

                $name = e(trim((string) ($account->owner?->first_name ?: $account->owner?->name)) ?: 'there');
                $workspace = e($account->account_name ?: 'your workspace');
                $money = fn (float $amount): string => '£'.number_format($amount, 2);
                $dashboard = e(route('backend.dashboard'));

                try {
                    Mail::to($recipient)->send(new MailManager([
                        'subject' => 'Monthly summary for '.$label,
                        'content' => '<p>Hi '.$name.',</p>'
                            .'<p>Here is your monthly operational summary for <strong>'.$workspace.'</strong> for '.e($label).':</p>'
                            .'<ul>'
                            .'<li><strong>Total Managed Units:</strong> '.$total.' ('.$rate.'% Occupied)</li>'
                            .'<li><strong>Rent Collected:</strong> '.$money($collected).'</li>'
                            .'<li><strong>Outstanding Arrears:</strong> '.$money($outstanding).'</li>'
                            .'<li><strong>Active Repairs:</strong> '.$repairs.' open tickets</li>'
                            .'<li><strong>Compliance Items Pending:</strong> '.$compliance.' items due soon</li>'
                            .'</ul>'
                            .'<p><a href="'.$dashboard.'">Go to Workspace Dashboard</a></p>'
                            .'<p>Best regards,<br>The ResiSquare Team</p>',
                        'attachments' => [],
                    ]));
                    $sent++;
                } catch (\Throwable $exception) {
                    Cache::forget($key);
                    Log::error('Monthly portfolio summary failed', [
                        'account_id' => $account->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });

        $this->info("Monthly summaries sent: {$sent}.");

        return self::SUCCESS;
    }
}
