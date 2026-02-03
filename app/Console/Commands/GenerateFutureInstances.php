<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventInstance;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateFutureInstances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'app:generate-future-instances';
    protected $signature = 'events:generate-future {days=30 : How many days ahead to keep populated}';

    /**
     * The console command description.
     *
     * @var string
     */
    // protected $description = 'Command description';
    protected $description = 'Generate future instances up to a certain window for recurring events';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $daysAhead = (int) $this->argument('days');
        $cutoff = Carbon::now()->addDays($daysAhead);

        // For each master that is recurring
        $masters = Event::where('repeat', '!=', 'none')->get();

        foreach ($masters as $master) {
            // Find the latest instance (regardless of status)
            $lastInst = $master->instances()->orderBy('start_datetime', 'desc')->first();

            if (!$lastInst) {
                continue; // no instances at all? skip
            }

            // As long as lastInst.start_datetime < cutoff, create next one
            $nextStart = Carbon::parse($lastInst->start_datetime);
            $nextEnd = Carbon::parse($lastInst->end_datetime);

            while (
                $nextStart->lt($cutoff) &&
                ($master->repeat_until_count === null ||
                    $master->instances()->count() <= ($master->repeat_until_count + 1))
            ) {

                switch ($master->repeat) {
                    case 'daily':
                        $nextStart->addDays($master->repeat_interval);
                        $nextEnd->addDays($master->repeat_interval);
                        break;
                    case 'weekly':
                        $nextStart->addWeeks($master->repeat_interval);
                        $nextEnd->addWeeks($master->repeat_interval);
                        break;
                    case 'monthly':
                        $nextStart->addMonths($master->repeat_interval);
                        $nextEnd->addMonths($master->repeat_interval);
                        break;
                }

                // If beyond repeat count, break
                if (
                    $master->repeat_until_count !== null &&
                    $master->instances()->count() >= ($master->repeat_until_count + 1)
                ) {
                    break;
                }

                // Create a new instance if not already exists
                if (
                    !EventInstance::where('event_id', $master->id)
                        ->where('start_datetime', $nextStart)
                        ->exists()
                ) {

                    $master->instances()->create([
                        'start_datetime' => $nextStart->copy(),
                        'end_datetime' => $nextEnd->copy(),
                        'instance_status' => 'Scheduled',
                        'notified' => false,
                    ]);
                }
            }
        }

        $this->info("Future instances generated up to {$cutoff->toDateString()}");
    }
}
