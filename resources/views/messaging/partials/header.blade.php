@php
    $r = fn ($name, $params = []) => \App\Support\Messaging\MessagingArea::route($area, $name, $params);
    $onArchived = request()->routeIs($area.'.messages.archived') || (request()->routeIs($area.'.messages.show') && ! (($conversation ?? null)?->isActive() ?? true));
    $headSub = match ($area) {
        'contractor' => 'Talk to landlords and tenants about the jobs you are doing.',
        'portal' => 'Talk to your landlord or letting agent about your home.',
        default => 'Chats with your tenants, and with contractors on live jobs.',
    };
@endphp
<div class="msg-head">
    <div>
        <h1>Messages</h1>
        <div class="msg-head-sub">{{ $headSub }}</div>
    </div>
    <nav class="msg-tabs" aria-label="Message folders">
        <a href="{{ $r('index') }}" class="{{ $onArchived ? '' : 'is-active' }}" @unless($onArchived) aria-current="page" @endunless><i class="bi bi-chat-dots" aria-hidden="true"></i> Chats</a>
        <a href="{{ $r('archived') }}" class="{{ $onArchived ? 'is-active' : '' }}" @if($onArchived) aria-current="page" @endif><i class="bi bi-archive" aria-hidden="true"></i> Archived</a>
    </nav>
</div>
@if(session('messaging_status'))
    <div class="msg-alert is-ok" role="status"><i class="bi bi-check-circle" aria-hidden="true"></i> {{ session('messaging_status') }}</div>
@endif
@if(session('messaging_error'))
    <div class="msg-alert is-warn" role="alert"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> {{ session('messaging_error') }}</div>
@endif
@if($errors->any())
    <div class="msg-alert is-warn" role="alert"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> {{ $errors->first() }}</div>
@endif
