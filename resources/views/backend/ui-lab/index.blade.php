<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;650;750&display=swap">
    <link rel="stylesheet" href="{{ asset('asset/backend/css/ui-lab.css') }}?v={{ filemtime(public_path('asset/backend/css/ui-lab.css')) }}">
</head>
<body class="lab">
    <div class="lab-banner">
        <span><strong>Experimental design</strong> — proposed chrome. Not the live app.</span>
        <a href="{{ auth()->user() && is_tenant_portal_user() ? route('backend.home') : route('backend.dashboard') }}">Back to live app</a>
    </div>

    <nav class="lab-switcher" aria-label="Lab screens">
        @php $lastGroup = null; @endphp
        @foreach($screens as $key => $meta)
            @if($lastGroup !== $meta['group'])
                @if($lastGroup !== null)
                    </div>
                @endif
                <div class="lab-switcher-group">
                    <span>{{ $meta['group'] }}</span>
                @php $lastGroup = $meta['group']; @endphp
            @endif
            <a href="{{ route('backend.ui_lab', ['screen' => $key]) }}" class="{{ $screen === $key ? 'is-active' : '' }}">{{ $meta['title'] }}</a>
        @endforeach
        </div>
    </nav>

    <div class="{{ $screen === 'login' ? 'lab-stage lab-stage-wide' : (in_array($screen, ['shell', 'first-home'], true) ? 'lab-stage lab-stage-fill' : 'lab-stage') }}">
        @include('backend.ui-lab.screens.'.$screens[$screen]['view'])
    </div>

    <script src="{{ asset('asset/backend/js/ui-lab.js') }}?v={{ filemtime(public_path('asset/backend/js/ui-lab.js')) }}"></script>
</body>
</html>
