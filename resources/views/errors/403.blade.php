<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 · Resisquare</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #0f172a; color: #e2e8f0; font-family: "Segoe UI", system-ui, sans-serif; }
        .box { text-align: center; max-width: 32rem; padding: 24px; }
        .code { letter-spacing: .2em; color: #94a3b8; font-size: 13px; margin-bottom: 12px; }
        h1 { font-size: 18px; font-weight: 600; text-transform: uppercase; margin: 0 0 16px; }
        p { color: #94a3b8; }
        .btn { display: inline-block; margin-top: 12px; padding: 8px 14px; border: 0; border-radius: 6px; background: #f59e0b; color: #0f172a; font-weight: 650; cursor: pointer; text-decoration: none; }
        .ghost { background: transparent; color: #93c5fd; }
    </style>
</head>
<body>
    <div class="box">
        <div class="code">403</div>
        <h1>{{ (isset($exception) && $exception->getMessage()) ? $exception->getMessage() : 'You are not allowed to view this area.' }}</h1>
        @if(session()->has('impersonator_user_id'))
            <p>You are viewing a customer account. Return to Super Admin, or open a page that is allowed on this plan.</p>
            <form action="{{ route('backend.accounts.leave-login') }}" method="POST">
                @csrf
                <button type="submit" class="btn">Return to Super Admin</button>
            </form>
        @elseif(auth()->check())
            <a class="btn ghost" href="{{ route('backend.dashboard') }}">Back to dashboard</a>
        @else
            <a class="btn ghost" href="{{ route('login') }}">Login</a>
        @endif
    </div>
</body>
</html>
