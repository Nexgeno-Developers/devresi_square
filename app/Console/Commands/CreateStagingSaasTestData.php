<?php

namespace App\Console\Commands;

use Database\Seeders\StagingSaasTestDataSeeder;
use Illuminate\Console\Command;

class CreateStagingSaasTestData extends Command
{
    protected $signature = 'saas:create-staging-test-data
        {--force : Allow running outside local/staging/testing, except production}';

    protected $description = 'Create idempotent SaaS staging test data for end-to-end QA.';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Refusing to create staging test data in production.');

            return self::FAILURE;
        }

        if (! app()->environment(['local', 'staging', 'testing']) && ! $this->option('force')) {
            $this->error('This command is intended for local/staging/testing only. Use --force for another non-production environment.');

            return self::FAILURE;
        }

        $this->call('db:seed', [
            '--class' => StagingSaasTestDataSeeder::class,
            '--force' => true,
        ]);

        $this->info('Staging SaaS test data is ready.');

        return self::SUCCESS;
    }
}
