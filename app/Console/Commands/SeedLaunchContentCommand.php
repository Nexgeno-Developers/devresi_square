<?php

namespace App\Console\Commands;

use App\Services\Launch\LaunchContent;
use Illuminate\Console\Command;

class SeedLaunchContentCommand extends Command
{
    protected $signature = 'launch:seed-content';

    protected $description = 'Upsert Landlord Basic and the launch email templates. Creates no users.';

    public function handle(LaunchContent $content): int
    {
        $content->seed();
        $this->info('Launch content is in place. Running this again changes nothing when the rows already match.');

        return self::SUCCESS;
    }
}
