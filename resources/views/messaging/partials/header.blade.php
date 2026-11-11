@php
    $r = fn ($name, $params = []) => \App\Support\Messaging\MessagingArea::route($area, $name, $params);
@endphp
<div class="msg-head">
    <h1>Messages</h1>
    <nav class="msg-tabs" aria-label="Message folders">
        <a href="{{ $r('index') }}" class="{{ request()->routeIs($area.'.messages.index', $area.'.messages.show', $area.'.messages.jobs.*') ? 'is-active' : '' }}">Inbox</a>
        <a href="{{ $r('requests') }}" class="{{ request()->routeIs($area.'.messages.requests') ? 'is-active' : '' }}">
            Requests @if($pendingRequests)<span class="msg-pill" data-msg-requests>{{ $pendingRequests }}</span>@endif
        </a>
        <a href="{{ $r('archived') }}" class="{{ request()->routeIs($area.'.messages.archived') ? 'is-active' : '' }}">Archived</a>
    </nav>
    <a class="btn btn-sm lw-btn-primary" href="{{ $r('create') }}"><i class="bi bi-pencil-square" aria-hidden="true"></i> <span>New message</span></a>
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
