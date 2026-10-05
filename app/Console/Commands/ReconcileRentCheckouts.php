<?php

namespace App\Console\Commands;

use App\Models\RentInvoice;
use App\Services\Finance\RentStripeCheckoutService;
use Illuminate\Console\Command;

class ReconcileRentCheckouts extends Command
{
    protected $signature = 'rent:reconcile-checkouts';

    protected $description = 'Mark open rent invoices paid when Stripe has already collected the card.';

    public function handle(RentStripeCheckoutService $checkout): int
    {
        $invoices = RentInvoice::query()
            ->whereIn('status', [RentInvoice::STATUS_ISSUED, RentInvoice::STATUS_PARTIAL])
            ->where('balance', '>', 0)
            ->whereNotNull('stripe_checkout_session_id')
            ->where('stripe_checkout_session_id', '!=', '')
            ->orderBy('id')
            ->get();

        $checkout->reconcileOpenCheckouts($invoices);

        $this->info('Checked '.$invoices->count().' open card checkouts.');

        return self::SUCCESS;
    }
}
