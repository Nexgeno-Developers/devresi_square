@php
    $r = fn ($name, $params = []) => \App\Support\Messaging\MessagingArea::route($area, $name, $params);
@endphp
<div class="msg-head">
    <h1>Messages</h1>
    <nav class="msg-tabs" aria-label="Message folders">
        <a href="{{ $r('index') }}" class="{{ request()->routeIs($area.'.messages.index') || (request()->routeIs($area.'.messages.show') && ($conversation ?? null)?->isActive()) ? 'is-active' : '' }}">Chats</a>
        <a href="{{ $r('archived') }}" class="{{ request()->routeIs($area.'.messages.archived') || (request()->routeIs($area.'.messages.show') && ! (($conversation ?? null)?->isActive() ?? true)) ? 'is-active' : '' }}">Archived</a>
    </nav>
</div>
@if(session('messaging_status'))
    <div class="alert alert-success py-2" role="status">{{ session('messaging_status') }}</div>
@endif
@if(session('messaging_error'))
    <div class="alert alert-warning py-2" role="alert">{{ session('messaging_error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-warning py-2" role="alert">{{ $errors->first() }}</div>
@endif
