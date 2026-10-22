<?php

namespace App\Services\Schema;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Throwable;

class WebMigrator
{
    /**
     * Pending migrations in filename order.
     *
     * @return list<array{name: string, path: string, blocked: ?string}>
     */
    public function pending(): array
    {
        $migrator = app('migrator');
        $files = $migrator->getMigrationFiles(database_path('migrations'));
        $ran = app('migration.repository')->repositoryExists()
            ? app('migration.repository')->getRan()
            : [];

        $pending = [];
        foreach (array_diff(array_keys($files), $ran) as $name) {
            $path = $this->guardedPath($files[$name]);
            $pending[] = [
                'name' => $name,
                'path' => $path,
                'blocked' => $this->rejectionReason($path),
            ];
        }

        return $pending;
    }

    /**
     * Apply or preview the pending prefix. Stops before a destructive file and on the first failure.
     * Does not seed, refresh, or roll back.
     */
    public function run(bool $pretend): string
    {
        $pending = $this->pending();
        if ($pending === []) {
            return 'Nothing to migrate.';
        }

        $lines = [];
        foreach ($pending as $migration) {
            if ($migration['blocked'] !== null) {
                $lines[] = 'Stopped before '.$migration['name'].'.';
                $lines[] = 'That file '.$migration['blocked'].', so it was not run. Nothing after it was run.';

                return implode("\n", $lines);
            }

            try {
                $code = Artisan::call('migrate', [
                    '--force' => true,
                    '--step' => true,
                    '--no-interaction' => true,
                    '--pretend' => $pretend,
                    '--path' => $this->relativePath($migration['path']),
                ]);
            } catch (Throwable $exception) {
                report($exception);
                $lines[] = 'Stopped at '.$migration['name'].'.';
                $lines[] = $exception->getMessage();
                $lines[] = 'Earlier migrations in this run were recorded. This one was not. Check the database before trying again. Nothing after it was run.';

                return implode("\n", $lines);
            }

            $output = trim(Artisan::output());
            if ($output !== '') {
                $lines[] = $output;
            }

            if ($code !== 0) {
                $lines[] = 'Stopped at '.$migration['name'].'.';
                $lines[] = 'Earlier migrations in this run were recorded. This one was not. Check the database before trying again. Nothing after it was run.';

                return implode("\n", $lines);
            }
        }

        return implode("\n", $lines);
    }

    public function rejectionReason(string $path): ?string
    {
        $up = $this->upSource((string) file_get_contents($path));
        $needles = [
            'dropIfExists' => 'drops a table',
            'dropColumn' => 'drops a column',
            'Schema::drop' => 'drops a table',
            '->truncate(' => 'empties a table',
            '::truncate(' => 'empties a table',
            '->delete(' => 'deletes rows',
            'DROP TABLE' => 'drops a table',
            'db:seed' => 'seeds data',
            'migrate:fresh' => 'wipes the database',
        ];

        foreach ($needles as $needle => $why) {
            if (stripos($up, $needle) !== false) {
                return $why;
            }
        }

        return null;
    }

    private function guardedPath(string $path): string
    {
        $migrations = str_replace('\\', '/', realpath(database_path('migrations')) ?: '');
        $resolved = str_replace('\\', '/', realpath($path) ?: '');
        if ($migrations === '' || $resolved === '' || ! str_starts_with($resolved, $migrations.'/') || ! str_ends_with($resolved, '.php')) {
            throw new RuntimeException('A pending migration is outside the migrations directory.');
        }

        return $resolved;
    }

    private function relativePath(string $absolute): string
    {
        $base = str_replace('\\', '/', base_path());
        $path = str_replace('\\', '/', $absolute);

        return ltrim(substr($path, strlen($base)), '/');
    }

    private function upSource(string $source): string
    {
        if (! preg_match('/function\s+up\s*\([^)]*\)\s*(?::\s*\??\\\\?void\s*)?\{/', $source, $match, PREG_OFFSET_CAPTURE)) {
            return $source;
        }

        $start = $match[0][1] + strlen($match[0][0]);
        $depth = 1;
        $length = strlen($source);
        for ($i = $start; $i < $length; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($source, $start, $i - $start);
                }
            }
        }

        return $source;
    }
}
