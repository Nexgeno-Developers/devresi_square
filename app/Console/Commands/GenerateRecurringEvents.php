<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use App\Models\EventInstance;
use Carbon\Carbon;
use RRule\RRule;


class GenerateRecurringEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-recurring-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("⏳ Generating recurring event instances...");

        $now = Carbon::now();
        $cutoffDate = $now->copy()->addYear(); // Only generate up to 1 year from now

        $events = Event::whereNotNull('rrule')->get();

        foreach ($events as $event) {
            $firstInstance = $event->instances()->orderBy('start_datetime')->first();
            if (!$firstInstance) {
                $this->warn("⚠️ Event ID {$event->id} has no first instance. Skipping.");
                continue;
            }

            $start = Carbon::parse($firstInstance->start_datetime);
            $end   = Carbon::parse($firstInstance->end_datetime);
            $duration = abs($end->diffInSeconds($start));

            $rruleString = preg_replace('/^RRULE:/i', '', trim($event->rrule));
            $rule = new RRule($rruleString, $start);

            $existingDates = $event->instances()->pluck('start_datetime')->map(fn($d) => Carbon::parse($d)->toDateString())->toArray();
            $exdates = json_decode($event->exdates ?? '[]', true);

            foreach ($rule as $occurrence) {
                $occTs = Carbon::instance($occurrence);

                if ($occTs->greaterThan($cutoffDate)) {
                    break;
                }

                if ($occTs->equalTo($start)) {
                    continue; // Skip first instance
                }

                $dateOnly = $occTs->toDateString();
                if (in_array($dateOnly, $existingDates)) {
                    continue; // Already exists
                }

                if (in_array($dateOnly, $exdates, true)) {
                    $status = 'Cancelled';
                    $isException = true;
                } else {
                    $status = 'Scheduled';
                    $isException = false;
                }

                EventInstance::create([
                    'event_id' => $event->id,
                    'start_datetime' => $occTs,
                    'end_datetime' => $occTs->copy()->addSeconds($duration),
                    'instance_status' => $status,
                    'is_exception' => $isException,
                    'notified' => false,
                ]);
            }
        }

        $this->info("✅ Recurring event generation completed.");
    }
}
