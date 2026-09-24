@props([
    'code' => 'Error',
    'title' => 'Something went wrong',
    'message' => 'Please try again in a moment.',
])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} · Resisquare</title>
    <style>
        :root { --ink:#0b1220; --muted:#5b6573; --wash:#f4f6f8; --accent:#0f766e; --line:#e6e9ef; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; background:var(--wash); color:var(--ink); font-family:"Segoe UI",system-ui,sans-serif; }
        .box { text-align:center; max-width:28rem; padding:2rem 1.5rem; background:#fff; border:1px solid var(--line); border-radius:16px; }
        .code { letter-spacing:.14em; color:var(--accent); font-size:.78rem; font-weight:700; text-transform:uppercase; margin-bottom:.75rem; }
        h1 { font-size:1.35rem; font-weight:650; margin:0 0 .65rem; }
        p { color:var(--muted); margin:0 0 1.25rem; line-height:1.45; }
        .actions { display:flex; gap:.65rem; justify-content:center; flex-wrap:wrap; }
        .btn { display:inline-flex; align-items:center; justify-content:center; padding:.55rem 1.1rem; border-radius:10px; font-weight:600; font-size:.92rem; text-decoration:none; border:1px solid transparent; cursor:pointer; background:var(--ink); color:#fff; }
        .btn-ghost { background:#fff; color:var(--ink); border-color:var(--line); }
    </style>
</head>
<body>
    <div class="box">
        <div class="code">{{ $code }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <div class="actions">{{ $slot }}</div>
    </div>
</body>
</html>
