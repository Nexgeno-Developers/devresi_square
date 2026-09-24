<?php

namespace App\Console\Commands;

use App\Services\Finance\RentFinanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateRecurringRentInvoices extends Command
{
    protected $signature = 'rent-invoices:generate-recurring
        {--as-of= : Optional YYYY-MM-DD date to simulate generation}';

    protected $description = 'Generate due automatic rent invoices for Active tenancies';

    public function handle(RentFinanceService $finance): int
    {
        $asOfOption = $this->option('as-of');
        $asOf = $asOfOption ? Carbon::parse((string) $asOfOption)->startOfDay() : now()->startOfDay();

        $created = $finance->generateDueRecurring($asOf);

        $this->info("Created {$created} rent invoice".($created === 1 ? '' : 's').' as of '.$asOf->toDateString().'.');

        return self::SUCCESS;
    }
}
