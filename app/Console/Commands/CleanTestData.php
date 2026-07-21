<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\DatabaseCleanupTablePolicy;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class CleanTestData extends Command
{
    protected $signature = 'db:clean-test-data
        {--dry-run : Show the deletion summary and dependency order without changing data}';

    protected $description = 'Delete old/test data while preserving configuration and every Super Admin.';

    /** Tables that retain rows belonging to a Super Admin. */
    private const SELECTIVE_USER_TABLES = [
        'model_has_permissions',
        'model_has_roles',
        'user_details',
        'bank_details',
        'bank_accounts',
        'users',
    ];

    public function __construct(private readonly DatabaseCleanupTablePolicy $policy)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $schema = $this->validateCompleteSchema();
            $superAdmins = $this->superAdmins();
            $superAdminIds = $superAdmins->pluck('id')->map(fn ($id): int => (int) $id)->all();
            $plan = $this->buildDeletionPlan($schema['clear']);
            $summary = $this->dryRunSummary($plan['order'], $superAdminIds);
        } catch (Throwable $exception) {
            $this->error('Cleanup preflight failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->displayDryRun($summary, $plan['order']);

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN ONLY: no database data was modified.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn('WARNING:');
        $this->line('This will permanently delete old/test data from 73 tables.');
        $this->line('The tables themselves will NOT be deleted.');
        $this->line('Table structures will NOT be modified.');
        $this->line('The Super Admin will be preserved.');
        $this->newLine();

        if ($this->ask('Type CLEAN DATABASE to continue') !== 'CLEAN DATABASE') {
            $this->warn('Cleanup cancelled. No database data was modified.');

            return self::SUCCESS;
        }

        try {
            $result = $this->executeCleanup(
                $plan,
                $summary,
                $schema,
                $superAdmins,
                $superAdminIds,
            );
        } catch (Throwable $exception) {
            $this->error('DATABASE CLEANUP FAILED');
            $this->error($exception->getMessage());
            $this->line('The transaction was rolled back. No additional tables were processed after the failure.');

            return self::FAILURE;
        }

        $this->displayCompletion($result, $superAdmins);

        return self::SUCCESS;
    }

    /**
     * @return array{clear: list<string>, preserve: list<string>, all: list<string>}
     */
    private function validateCompleteSchema(): array
    {
        $liveTables = collect(Schema::getTables())
            ->pluck('name')
            ->sort()
            ->values();
        $configuredTables = collect($this->policy->configuredTables())->sort()->values();
        $missing = $configuredTables->diff($liveTables)->values()->all();
        $unclassified = $liveTables->diff($configuredTables)->values()->all();

        if ($configuredTables->count() !== 114 || $configuredTables->unique()->count() !== 114) {
            throw new RuntimeException('The cleanup policy must contain exactly 114 unique tables.');
        }

        if ($missing !== []) {
            throw new RuntimeException('Required tables not found: '.implode(', ', $missing));
        }

        if ($unclassified !== []) {
            throw new RuntimeException('Unclassified live tables require REVIEW: '.implode(', ', $unclassified));
        }

        $clear = $liveTables
            ->filter(fn (string $table): bool => $this->policy->classify($table)['category'] === DatabaseCleanupTablePolicy::CLEAR)
            ->values()
            ->all();
        $preserve = $liveTables
            ->filter(fn (string $table): bool => $this->policy->classify($table)['category'] === DatabaseCleanupTablePolicy::PRESERVE)
            ->values()
            ->all();

        if (count($clear) !== 73 || count($preserve) !== 41 || count($clear) + count($preserve) !== 114) {
            throw new RuntimeException(sprintf(
                'Classification mismatch: CLEAR %d + PRESERVE %d must equal 114.',
                count($clear),
                count($preserve),
            ));
        }

        $this->assertPreserveTablesDoNotDependOnClearRows($liveTables->all(), $clear);
        $this->assertNoForeignKeyOrphans();

        return ['clear' => $clear, 'preserve' => $preserve, 'all' => $liveTables->all()];
    }

    /**
     * Prevent cascades from a CLEAR parent from silently changing a PRESERVE or REVIEW child.
     *
     * @param  list<string>  $liveTables
     * @param  list<string>  $clearTables
     */
    private function assertPreserveTablesDoNotDependOnClearRows(array $liveTables, array $clearTables): void
    {
        foreach ($liveTables as $childTable) {
            if (in_array($childTable, $clearTables, true)) {
                continue;
            }

            foreach (Schema::getForeignKeys($childTable) as $foreignKey) {
                if (! in_array($foreignKey['foreign_table'], $clearTables, true)) {
                    continue;
                }

                $referencingRows = DB::table($childTable)
                    ->where(function (Builder $query) use ($foreignKey): void {
                        foreach ($foreignKey['columns'] as $column) {
                            $query->whereNotNull($column);
                        }
                    })
                    ->count();

                if ($referencingRows > 0) {
                    throw new RuntimeException(
                        "Protected table {$childTable} has {$referencingRows} row(s) depending on CLEAR table {$foreignKey['foreign_table']} through {$foreignKey['name']}."
                    );
                }
            }
        }
    }

    /** @return Collection<int, object{id: int, name: ?string, email: ?string}> */
    private function superAdmins(): Collection
    {
        $superAdmins = User::role('Super Admin')
            ->select(['users.id', 'users.name', 'users.email'])
            ->distinct()
            ->orderBy('users.id')
            ->get();

        if ($superAdmins->isEmpty()) {
            throw new RuntimeException('No user assigned to the Super Admin role was found.');
        }

        return $superAdmins;
    }

    /**
     * Build a child-first order from live foreign keys. Nullable edges are detached only when
     * necessary to break a real schema cycle; no constraints are disabled.
     *
     * @param  list<string>  $clearTables
     * @return array{order: list<string>, detachments: list<array{table: string, columns: list<string>, foreign_table: string, name: string}>}
     */
    private function buildDeletionPlan(array $clearTables): array
    {
        $edges = [];

        foreach ($clearTables as $childTable) {
            foreach (Schema::getForeignKeys($childTable) as $foreignKey) {
                $parentTable = $foreignKey['foreign_table'];
                if ($parentTable === $childTable || ! in_array($parentTable, $clearTables, true)) {
                    continue;
                }

                $edges[] = [
                    'table' => $childTable,
                    'columns' => $foreignKey['columns'],
                    'foreign_table' => $parentTable,
                    'name' => $foreignKey['name'],
                ];
            }
        }

        $activeEdges = $edges;
        $detachments = [];

        while (true) {
            [$order, $cycleTables] = $this->topologicalOrder($clearTables, $activeEdges);
            if ($cycleTables === []) {
                return ['order' => $order, 'detachments' => $detachments];
            }

            $edgeIndex = $this->nullableCycleEdgeIndex($activeEdges, $cycleTables);
            if ($edgeIndex === null) {
                throw new RuntimeException('Foreign-key cycle cannot be safely resolved: '.implode(', ', $cycleTables));
            }

            $detachments[] = $activeEdges[$edgeIndex];
            array_splice($activeEdges, $edgeIndex, 1);
        }
    }

    /**
     * @param  list<string>  $tables
     * @param  list<array{table: string, columns: list<string>, foreign_table: string, name: string}>  $edges
     * @return array{0: list<string>, 1: list<string>}
     */
    private function topologicalOrder(array $tables, array $edges): array
    {
        $inDegree = array_fill_keys($tables, 0);
        $outbound = array_fill_keys($tables, []);

        foreach ($edges as $index => $edge) {
            $inDegree[$edge['foreign_table']]++;
            $outbound[$edge['table']][] = $index;
        }

        $queue = array_keys(array_filter($inDegree, fn (int $degree): bool => $degree === 0));
        sort($queue);
        $order = [];

        while ($queue !== []) {
            $table = array_shift($queue);
            $order[] = $table;

            foreach ($outbound[$table] as $edgeIndex) {
                $parent = $edges[$edgeIndex]['foreign_table'];
                $inDegree[$parent]--;
                if ($inDegree[$parent] === 0) {
                    $queue[] = $parent;
                    sort($queue);
                }
            }
        }

        $cycleTables = array_values(array_diff($tables, $order));
        sort($cycleTables);

        return [$order, $cycleTables];
    }

    /**
     * @param  list<array{table: string, columns: list<string>, foreign_table: string, name: string}>  $edges
     * @param  list<string>  $cycleTables
     */
    private function nullableCycleEdgeIndex(array $edges, array $cycleTables): ?int
    {
        foreach ($edges as $index => $edge) {
            if (! in_array($edge['table'], $cycleTables, true)
                || ! in_array($edge['foreign_table'], $cycleTables, true)) {
                continue;
            }

            $nullableColumns = collect(Schema::getColumns($edge['table']))
                ->filter(fn (array $column): bool => $column['nullable'])
                ->pluck('name')
                ->all();

            if (collect($edge['columns'])->every(fn (string $column): bool => in_array($column, $nullableColumns, true))) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $orderedTables
     * @param  list<int>  $superAdminIds
     * @return list<array{table: string, current: int, expected: int}>
     */
    private function dryRunSummary(array $orderedTables, array $superAdminIds): array
    {
        return collect($orderedTables)->map(fn (string $table): array => [
            'table' => $table,
            'current' => DB::table($table)->count(),
            'expected' => $this->deletionQuery($table, $superAdminIds)->count(),
        ])->all();
    }

    /**
     * @param  list<array{table: string, current: int, expected: int}>  $summary
     * @param  list<string>  $order
     */
    private function displayDryRun(array $summary, array $order): void
    {
        $this->info('DRY-RUN SUMMARY');
        $this->table(
            ['Table Name', 'Current Row Count', 'Expected Rows To Delete'],
            array_map(fn (array $row): array => [$row['table'], $row['current'], $row['expected']], $summary),
        );
        $this->line(sprintf(
            'Classification: CLEAR %d + PRESERVE %d + REVIEW 0 = 114 tables.',
            count($summary),
            count($this->policy->preserveTables()),
        ));
        $this->line('Calculated child-first delete order: '.implode(' -> ', $order));
    }

    /** @param list<int> $superAdminIds */
    private function deletionQuery(string $table, array $superAdminIds): Builder
    {
        $query = DB::table($table);

        return match ($table) {
            'users' => $query->whereNotIn('id', $superAdminIds),
            'user_details', 'bank_details', 'bank_accounts' => $query->whereNotIn('user_id', $superAdminIds),
            'model_has_permissions', 'model_has_roles' => $query
                ->whereIn('model_type', [User::class, 'App\\User'])
                ->whereNotIn('model_id', $superAdminIds),
            default => $query,
        };
    }

    /**
     * @param  array{order: list<string>, detachments: list<array{table: string, columns: list<string>, foreign_table: string, name: string}>}  $plan
     * @param  list<array{table: string, current: int, expected: int}>  $summary
     * @param  array{clear: list<string>, preserve: list<string>, all: list<string>}  $schema
     * @param  Collection<int, object{id: int, name: ?string, email: ?string}>  $superAdmins
     * @param  list<int>  $superAdminIds
     * @return array{tables_cleared: int, rows_deleted: int, skipped: list<string>, not_found: list<string>}
     */
    private function executeCleanup(
        array $plan,
        array $summary,
        array $schema,
        Collection $superAdmins,
        array $superAdminIds,
    ): array {
        $schemaFingerprint = $this->schemaFingerprint($schema['all']);
        $preserveFingerprint = $this->preserveFingerprint($schema['preserve']);
        $expectedByTable = collect($summary)->keyBy('table');
        $deletedByTable = [];

        DB::transaction(function () use (
            $plan,
            $schema,
            $superAdmins,
            $superAdminIds,
            $schemaFingerprint,
            $preserveFingerprint,
            $expectedByTable,
            &$deletedByTable,
        ): void {
            foreach ($plan['detachments'] as $edge) {
                try {
                    DB::table($edge['table'])
                        ->where(function (Builder $query) use ($edge): void {
                            foreach ($edge['columns'] as $column) {
                                $query->whereNotNull($column);
                            }
                        })
                        ->update(array_fill_keys($edge['columns'], null));
                } catch (Throwable $exception) {
                    throw new RuntimeException(
                        "Failed resolving dependency {$edge['name']} on table {$edge['table']}: {$exception->getMessage()}",
                        previous: $exception,
                    );
                }
            }

            foreach ($plan['order'] as $table) {
                try {
                    $deletedByTable[$table] = $this->deletionQuery($table, $superAdminIds)->delete();
                } catch (Throwable $exception) {
                    throw new RuntimeException(
                        "Failed deleting table {$table}: {$exception->getMessage()}",
                        previous: $exception,
                    );
                }
            }

            foreach ($expectedByTable as $table => $row) {
                $expectedRemaining = $row['current'] - $row['expected'];
                $actualRemaining = DB::table($table)->count();
                if ($actualRemaining !== $expectedRemaining) {
                    throw new RuntimeException(
                        "Validation failed for {$table}: expected {$expectedRemaining} remaining row(s), found {$actualRemaining}."
                    );
                }
            }

            $this->assertFinalState(
                $schema,
                $superAdmins,
                $superAdminIds,
                $schemaFingerprint,
                $preserveFingerprint,
            );
        });

        return [
            'tables_cleared' => count(array_filter($deletedByTable, fn (int $count): bool => $count > 0)),
            'rows_deleted' => array_sum($deletedByTable),
            'skipped' => collect($summary)
                ->where('current', 0)
                ->pluck('table')
                ->all(),
            'not_found' => [],
        ];
    }

    /**
     * @param  array{clear: list<string>, preserve: list<string>, all: list<string>}  $schema
     * @param  Collection<int, object{id: int, name: ?string, email: ?string}>  $superAdmins
     * @param  list<int>  $superAdminIds
     */
    private function assertFinalState(
        array $schema,
        Collection $superAdmins,
        array $superAdminIds,
        string $schemaFingerprint,
        array $preserveFingerprint,
    ): void {
        $liveTables = collect(Schema::getTables())->pluck('name')->sort()->values()->all();
        if ($liveTables !== $schema['all']) {
            throw new RuntimeException('Final validation failed: the database table list changed.');
        }

        if ($this->schemaFingerprint($schema['all']) !== $schemaFingerprint) {
            throw new RuntimeException('Final validation failed: schema metadata changed.');
        }

        if ($this->preserveFingerprint($schema['preserve']) !== $preserveFingerprint) {
            throw new RuntimeException('Final validation failed: PRESERVE table data changed.');
        }

        $remainingSuperAdmins = $this->superAdmins();
        if ($remainingSuperAdmins->pluck('id')->map(fn ($id): int => (int) $id)->all() !== $superAdminIds) {
            throw new RuntimeException('Final validation failed: Super Admin identity or role assignment changed.');
        }

        foreach ($superAdmins as $before) {
            $after = $remainingSuperAdmins->firstWhere('id', $before->id);
            if (! $after || $after->name !== $before->name || $after->email !== $before->email) {
                throw new RuntimeException("Final validation failed: Super Admin #{$before->id} was modified.");
            }
        }

        if (DB::table('users')->whereNotIn('id', $superAdminIds)->exists()) {
            throw new RuntimeException('Final validation failed: non-Super Admin users remain.');
        }

        $this->assertNoForeignKeyOrphans();
    }

    /** @param list<string> $tables */
    private function schemaFingerprint(array $tables): string
    {
        $metadata = [];
        foreach ($tables as $table) {
            $metadata[$table] = [
                'columns' => Schema::getColumns($table),
                'indexes' => Schema::getIndexes($table),
                'foreign_keys' => Schema::getForeignKeys($table),
            ];
        }

        return hash('sha256', serialize($metadata));
    }

    /**
     * MySQL CHECKSUM TABLE is read-only and lets the command prove PRESERVE data was untouched.
     *
     * @param  list<string>  $tables
     * @return array<string, array{rows: int, checksum: mixed}>
     */
    private function preserveFingerprint(array $tables): array
    {
        $fingerprint = [];
        $grammar = DB::connection()->getQueryGrammar();

        foreach ($tables as $table) {
            $checksumResult = DB::selectOne('CHECKSUM TABLE '.$grammar->wrapTable($table));
            $checksum = $checksumResult ? array_values((array) $checksumResult)[1] ?? null : null;
            $fingerprint[$table] = [
                'rows' => DB::table($table)->count(),
                'checksum' => $checksum,
            ];
        }

        return $fingerprint;
    }

    private function assertNoForeignKeyOrphans(): void
    {
        foreach (Schema::getTables() as $tableMetadata) {
            $table = $tableMetadata['name'];
            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                if (count($foreignKey['columns']) !== 1 || count($foreignKey['foreign_columns']) !== 1) {
                    throw new RuntimeException("Cannot verify composite foreign key {$foreignKey['name']} on {$table}.");
                }

                $column = $foreignKey['columns'][0];
                $foreignTable = $foreignKey['foreign_table'];
                $foreignColumn = $foreignKey['foreign_columns'][0];
                $orphans = DB::table($table.' as child')
                    ->leftJoin($foreignTable.' as parent', 'child.'.$column, '=', 'parent.'.$foreignColumn)
                    ->whereNotNull('child.'.$column)
                    ->whereNull('parent.'.$foreignColumn)
                    ->count();

                if ($orphans > 0) {
                    throw new RuntimeException("Final validation found {$orphans} orphan row(s) through {$foreignKey['name']} on {$table}.");
                }
            }
        }
    }

    /**
     * @param  array{tables_cleared: int, rows_deleted: int, skipped: list<string>, not_found: list<string>}  $result
     * @param  Collection<int, object{id: int, name: ?string, email: ?string}>  $superAdmins
     */
    private function displayCompletion(array $result, Collection $superAdmins): void
    {
        $this->newLine();
        $this->info('DATABASE CLEANUP COMPLETED');
        $this->newLine();
        $this->line("Tables cleared: {$result['tables_cleared']}");
        $this->line("Rows deleted: {$result['rows_deleted']}");
        $this->newLine();

        foreach ($superAdmins as $superAdmin) {
            $this->line('Super Admin preserved:');
            $this->line("ID: {$superAdmin->id}");
            $this->line('Name: '.($superAdmin->name ?? ''));
            $this->line('Email: '.($superAdmin->email ?? ''));
            $this->newLine();
        }

        $this->line('Tables skipped because they were empty: '.($result['skipped'] === [] ? 'None' : implode(', ', $result['skipped'])));
        $this->line('Tables not found: '.($result['not_found'] === [] ? 'None' : implode(', ', $result['not_found'])));
        $this->newLine();
        $this->line('No tables were dropped.');
        $this->line('No table structures were modified.');
    }
}
