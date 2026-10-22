<?php

namespace App\Http\Controllers;

use App\Services\Schema\WebMigrator;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SchemaMigrateController extends Controller
{
    public function show(WebMigrator $migrator): View
    {
        $this->ensureSuperAdmin();

        return view('schema.migrate', [
            'pending' => $migrator->pending(),
        ]);
    }

    public function store(Request $request, WebMigrator $migrator): View|Response
    {
        $this->ensureSuperAdmin();

        $action = (string) $request->input('action');
        if (! in_array($action, ['preview', 'run'], true)) {
            abort(404);
        }

        $lock = fopen(storage_path('framework/schema-migrate.lock'), 'c+');
        if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) {
                fclose($lock);
            }

            return response()->view('schema.migrate', [
                'error' => 'A migration is already running.',
                'pending' => $migrator->pending(),
            ], 409);
        }

        set_time_limit(180);

        try {
            $output = $migrator->run($action === 'preview');
            $failed = str_contains($output, 'Stopped at ') || str_contains($output, 'Stopped before ');

            return response()->view('schema.migrate', [
                'output' => $output,
                'pending' => $migrator->pending(),
                'preview' => $action === 'preview',
            ], $failed ? 500 : 200);
        } catch (Throwable $exception) {
            report($exception);

            return response()->view('schema.migrate', [
                'error' => 'The migration did not finish. Nothing further was run.',
                'pending' => [],
            ], 500);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->user()?->hasRole('Super Admin'), 403);
    }
}
