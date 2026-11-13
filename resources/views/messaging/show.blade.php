@extends('backend.layout.app')

@section('content')
@include('messaging.partials.styles')
@php
    $r = fn ($name, $params = []) => \App\Support\Messaging\MessagingArea::route($area, $name, $params);
    $active = $conversation->isActive();
@endphp
<div class="msg-shell" data-messaging-area="{{ $area }}">
    @include('messaging.partials.header')
    <div class="msg-layout has-thread">
        <aside class="msg-list" aria-label="Chats">
            @include('messaging.partials.list', ['items' => $active ? $sidebarItems : collect([['key' => $conversation->public_id, 'title' => $about['title'], 'label' => $about['label'], 'preview' => '', 'at' => $conversation->last_message_at, 'unread' => 0, 'url' => $r('show', $conversation), 'archived' => true]]), 'activeKey' => $conversation->public_id])
        </aside>
        <section class="msg-pane" aria-label="{{ $about['title'] }}">
            <div class="msg-thread-head">
                <div>
                    <a class="msg-back btn btn-sm btn-link px-0" href="{{ $r($active ? 'index' : 'archived') }}"><i class="bi bi-chevron-left" aria-hidden="true"></i> <span>All chats</span></a>
                    <h2>{{ $about['title'] }}</h2>
                    <div class="msg-muted">{{ $about['label'] }}</div>
                    <div class="msg-people" data-msg-people>
                        <span>With:</span>
                        @foreach($about['people'] as $person)
                            <span><strong>{{ $person['name'] }}</strong> · {{ $person['role'] }}</span>
                        @endforeach
                    </div>
                </div>
                <details class="msg-menu">
                    <summary class="btn btn-sm btn-outline-secondary" aria-label="Chat options"><i class="bi bi-three-dots" aria-hidden="true"></i></summary>
                    <div class="msg-menu-body">
                        <form method="POST" action="{{ $r('report', $conversation) }}">@csrf
                            <label class="form-label small mb-1" for="conv-report">Report this chat to ResiSquare</label>
                            <textarea class="form-control form-control-sm mb-2" id="conv-report" name="note" rows="3" maxlength="1000" required placeholder="What is wrong? Our support team will look into it."></textarea>
                            <button type="submit" class="btn btn-sm btn-outline-danger">Send report</button>
                        </form>
                    </div>
                </details>
            </div>

            @unless($active)
                <div class="msg-note" data-msg-archived>{{ $conversation->archived_reason ?: 'This chat is archived.' }} You can still read it.</div>
            @endunless

            <div class="msg-thread" id="msg-thread" aria-live="polite">
                @if($messages->isEmpty())
                    <div class="msg-empty" data-msg-empty>No messages yet.</div>
                @endif
                @foreach($messages as $message)
                    @if($firstUnreadId && $message->id === $firstUnreadId)
                        <div class="msg-divider">New messages</div>
                    @endif
                    @include('messaging.partials.message', ['message' => $message])
                @endforeach
                <span id="latest"></span>
            </div>

            @if($active)
                <form class="msg-composer" method="POST" action="{{ $r('send', $conversation) }}" enctype="multipart/form-data">
                    @csrf
                    <label class="visually-hidden" for="msg-body">Message</label>
                    <textarea id="msg-body" name="body" maxlength="{{ config('messaging.body_max') }}" placeholder="Write a message to everyone in this chat">{{ old('body') }}</textarea>
                    <div class="msg-composer-row">
                        <label class="small mb-0">
                            <i class="bi bi-paperclip" aria-hidden="true"></i>
                            <span class="visually-hidden">Attach files</span>
                            <input type="file" name="files[]" multiple accept=".{{ implode(',.', config('messaging.attachments.extensions')) }}" class="form-control form-control-sm d-inline-block w-auto">
                        </label>
                        <button type="submit" class="btn btn-sm lw-btn-primary">Send</button>
                    </div>
                    <div class="msg-muted mt-1">Up to {{ config('messaging.attachments.max_files') }} files, {{ (int) (config('messaging.attachments.max_kb') / 1024) }} MB each.</div>
                </form>
            @else
                <div class="msg-readonly">Archived chats are read-only.</div>
            @endif
        </section>
    </div>
</div>

@if($active)
<script>
(function () {
    var thread = document.getElementById('msg-thread');
    if (!thread) { return; }
    var pollUrl = @json($r('poll', $conversation));
    var lastId = {{ (int) ($messages->max('id') ?? 0) }};
    var fast = {{ (int) config('messaging.poll.conversation_seconds') }} * 1000;
    var slow = {{ (int) config('messaging.poll.hidden_seconds') }} * 1000;
    var timer = null;

    function scrollDown() { thread.scrollTop = thread.scrollHeight; }
    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(poll, document.hidden ? slow : fast);
    }
    function poll() {
        fetch(pollUrl + '?after=' + lastId, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data) { return; }
                if (data.html) {
                    var empty = thread.querySelector('[data-msg-empty]');
                    if (empty) { empty.remove(); }
                    document.getElementById('latest').insertAdjacentHTML('beforebegin', data.html);
                    scrollDown();
                }
                lastId = data.last_id || lastId;
                if (data.active === false) { window.location.reload(); }
            })
            .catch(function () {})
            .finally(schedule);
    }
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { poll(); } });
    scrollDown();
    schedule();
})();
</script>
@else
<script>(function () { var t = document.getElementById('msg-thread'); if (t) { t.scrollTop = t.scrollHeight; } })();</script>
@endif
@endsection
