<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ReconcilePendingMigrations extends Command
{
    protected $signature = 'migrate:reconcile-pending
                            {--dry-run : Show what would be marked without writing}
                            {--force : Mark reconciled migrations without confirmation}';

    protected $description = 'Mark pending migrations as run when their target tables/columns already exist (legacy DB drift fix)';

    public function handle(): int
    {
        $repository = app('migration.repository');
        $files = app('migrator')->getMigrationFiles(database_path('migrations'));
        $ran = $repository->getRan();
        $pending = array_diff(array_keys($files), $ran);

        if ($pending === []) {
            $this->info('No pending migrations.');

            return self::SUCCESS;
        }

        $this->info('Found '.count($pending).' pending migration(s).');

        $toMark = [];

        foreach ($pending as $migration) {
            $path = $files[$migration];
            $content = File::get($path);
            $creates = $this->extractCreateTables($content);
            $alters = $this->extractAlterColumns($content);

            if ($creates !== [] && $this->allTablesExist($creates)) {
                $toMark[] = [$migration, 'tables exist: '.implode(', ', $creates)];
                continue;
            }

            if ($creates === [] && $alters !== [] && $this->allAlterColumnsExist($alters)) {
                $toMark[] = [$migration, 'columns exist: '.$this->formatAlters($alters)];
            }
        }

        if ($toMark === []) {
            $this->warn('No pending migrations could be safely reconciled. Run php artisan migrate:status for details.');

            return self::FAILURE;
        }

        $this->table(['Migration', 'Reason'], $toMark);

        if ($this->option('dry-run')) {
            $this->comment('Dry run — no changes written.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Mark these '.count($toMark).' migration(s) as run?', true)) {
            return self::SUCCESS;
        }

        $batch = $repository->getNextBatchNumber();

        foreach ($toMark as [$migration]) {
            $repository->log($migration, $batch);
            $this->line("Marked: {$migration}");
        }

        $this->info('Reconciliation complete. Run php artisan migrate for any remaining migrations.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function extractCreateTables(string $content): array
    {
        preg_match_all("/Schema::create\(\s*['\"]([^'\"]+)['\"]/", $content, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * @return list<array{table: string, column: string}>
     */
    private function extractAlterColumns(string $content): array
    {
        if (! preg_match("/Schema::table\(\s*['\"]([^'\"]+)['\"]/", $content, $tableMatch)) {
            return [];
        }

        $table = $tableMatch[1];
        preg_match_all("/->(?:\w+\([^)]*\)\s*->)?(\w+)\(\s*['\"]([^'\"]+)['\"]/", $content, $matches, PREG_SET_ORDER);

        $columns = [];
        foreach ($matches as $match) {
            if (in_array($match[1], ['foreign', 'index', 'unique', 'dropColumn', 'dropForeign', 'dropIndex', 'dropUnique'], true)) {
                continue;
            }
            $columns[] = ['table' => $table, 'column' => $match[2]];
        }

        return $columns;
    }

    /**
     * @param  list<string>  $tables
     */
    private function allTablesExist(array $tables): bool
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array{table: string, column: string}>  $alters
     */
    private function allAlterColumnsExist(array $alters): bool
    {
        foreach ($alters as $alter) {
            if (! Schema::hasTable($alter['table']) || ! Schema::hasColumn($alter['table'], $alter['column'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array{table: string, column: string}>  $alters
     */
    private function formatAlters(array $alters): string
    {
        return collect($alters)
            ->map(fn (array $alter) => "{$alter['table']}.{$alter['column']}")
            ->implode(', ');
    }
}
