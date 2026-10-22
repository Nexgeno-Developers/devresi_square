<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Migrations</title>
    <style>
        body { margin: 0; font: 15px/1.45 ui-sans-serif, system-ui, sans-serif; background: #f4f6f8; color: #0b1220; }
        main { max-width: 720px; margin: 40px auto; padding: 0 16px 48px; }
        form, .panel { background: #fff; border: 1px solid #e6e9ef; border-radius: 12px; padding: 20px; }
        label { display: block; margin: 0 0 14px; font-size: 13px; font-weight: 650; }
        input { display: block; width: 100%; margin-top: 6px; box-sizing: border-box; height: 40px; padding: 0 10px; border: 1px solid #e6e9ef; border-radius: 8px; font: inherit; font-weight: 400; }
        .actions { display: flex; gap: 8px; }
        button { height: 40px; padding: 0 14px; border-radius: 8px; border: 1px solid #0f173a; background: #fff; font: inherit; font-weight: 650; cursor: pointer; }
        button.run { background: #0f173a; color: #fff; }
        .note, .error { margin: 14px 0 0; font-size: 13px; }
        .error { color: #9a3412; }
        pre { white-space: pre-wrap; word-break: break-word; margin: 16px 0 0; padding: 14px; background: #0f173a; color: #fff; border-radius: 12px; font-size: 13px; }
        ul { margin: 8px 0 0; padding-left: 18px; }
    </style>
</head>
<body>
<main>
    <form class="panel" method="POST" action="{{ route('schema.migrate.store') }}">
        @csrf
        <strong>Migrations not run yet</strong>
        @if(empty($pending))
            <p class="note">Nothing waiting. Every migration file has already run.</p>
        @else
            <ul>
                @foreach($pending as $migration)
                    <li>{{ $migration['name'] }}@if($migration['blocked']) — refused, {{ $migration['blocked'] }}@endif</li>
                @endforeach
            </ul>
            <div class="actions" style="margin-top:16px">
                <button type="submit" name="action" value="preview">Preview</button>
                <button class="run" type="submit" name="action" value="run">Run</button>
            </div>
        @endif
        <p class="note">Only files that are not in the migrations table. This does not seed, refresh, or wipe. A file that drops tables, drops columns, or deletes rows is refused, and nothing after it runs.</p>
        @if(! empty($error))
            <p class="error">{{ $error }}</p>
        @endif
    </form>

    @if(! empty($output))
        <pre>{{ $output }}</pre>
    @endif
</main>
</body>
</html>
