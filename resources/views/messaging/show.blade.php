@extends('backend.layout.app')

@section('content')
@include('messaging.partials.styles')
@php
    $r = fn ($name, $params = []) => \App\Support\Messaging\MessagingArea::route($area, $name, $params);
    $lastSeenMineId = (int) $messages->filter(fn ($m) => $m->sentBy($party) && ! $m->trashed() && $m->id <= $seenUpTo)->max('id');
    $muted = $participant?->muted_until?->isFuture();
    $isArchived = $participant?->archived_at !== null;
@endphp
<div class="msg-shell" data-messaging-area="{{ $area }}">
    @include('messaging.partials.header')
    <div class="msg-layout has-thread">
        <aside class="msg-list" aria-label="Conversations">
            @include('messaging.partials.list', ['items' => $sidebarItems, 'activeKey' => 'c-'.$conversation->public_id])
        </aside>
        <section class="msg-pane" aria-label="Conversation with {{ $other?->displayName() }}">
            <div class="msg-thread-head">
                <div>
                    <a class="msg-back btn btn-sm btn-link px-0" href="{{ $r('index') }}"><i class="bi bi-chevron-left" aria-hidden="true"></i> <span>All messages</span></a>
                    <h2>{{ $other?->displayName() ?? 'Conversation' }}</h2>
                    <div class="msg-muted">{{ $other?->roleLabel() }}</div>
                </div>
                <details class="msg-menu">
                    <summary class="btn btn-sm btn-outline-secondary" aria-label="Conversation options"><i class="bi bi-three-dots" aria-hidden="true"></i></summary>
                    <div class="msg-menu-body">
                        <form method="POST" action="{{ $r('mute', $conversation) }}">@csrf
                            @if($muted)<input type="hidden" name="unmute" value="1">@endif
                            <button type="submit" class="btn btn-sm btn-light w-100 text-start">{{ $muted ? 'Turn notifications on' : 'Turn notifications off' }}</button>
                        </form>
                        <form method="POST" action="{{ $r('archive', $conversation) }}">@csrf
                            @if($isArchived)<input type="hidden" name="restore" value="1">@endif
                            <button type="submit" class="btn btn-sm btn-light w-100 text-start">{{ $isArchived ? 'Move to inbox' : 'Archive' }}</button>
                        </form>
                        @if($iBlocked)
                            <form method="POST" action="{{ $r('unblock', $conversation) }}">@csrf
                                <button type="submit" class="btn btn-sm btn-light w-100 text-start">Unblock</button>
                            </form>
                        @else
                            <form method="POST" action="{{ $r('block', $conversation) }}" onsubmit="return confirm('Block this person? They will not be able to message you or send requests.')">@csrf
                                <button type="submit" class="btn btn-sm btn-light w-100 text-start text-danger">Block</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ $r('report', $conversation) }}">@csrf
                            <label class="form-label small mb-1" for="conv-report">Report this conversation</label>
                            <select class="form-select form-select-sm mb-2" id="conv-report" name="category" required>
                                <option value="spam">Spam</option>
                                <option value="harassment">Harassment or abuse</option>
                                <option value="scam">Scam or fraud</option>
                                <option value="other">Something else</option>
                            </select>
                            <textarea class="form-control form-control-sm mb-2" name="note" rows="2" maxlength="1000" placeholder="Anything we should know (optional)"></textarea>
                            <button type="submit" class="btn btn-sm btn-outline-danger">Send report</button>
                        </form>
                    </div>
                </details>
            </div>

            @if(! $conversation->unmasked_at)
                <div class="msg-note">Phone numbers, emails and links are hidden until you have both replied. Files can be sent after that too.</div>
            @endif

            <div class="msg-thread" id="msg-thread" aria-live="polite">
                @if($messages->isEmpty())
                    <div class="msg-empty" data-msg-empty>Say hello to start the conversation.</div>
                @endif
                @foreach($messages as $message)
                    @if($firstUnreadId && $message->id === $firstUnreadId)
                        <div class="msg-divider">New messages</div>
                    @endif
                    @include('messaging.partials.message', ['message' => $message])
                @endforeach
                <span id="latest"></span>
            </div>

            @if($conversation->isActive() && ! $blocked)
                <form class="msg-composer" method="POST" action="{{ $r('send', $conversation) }}" enctype="multipart/form-data">
                    @csrf
                    <label class="visually-hidden" for="msg-body">Message</label>
                    <textarea id="msg-body" name="body" maxlength="{{ config('messaging.body_max') }}" placeholder="Write a message">{{ old('body') }}</textarea>
                    <div class="msg-composer-row">
                        @if($canAttach)
                            <label class="small mb-0">
                                <i class="bi bi-paperclip" aria-hidden="true"></i>
                                <span class="visually-hidden">Attach files</span>
                                <input type="file" name="files[]" multiple accept=".{{ implode(',.', config('messaging.attachments.extensions')) }}" class="form-control form-control-sm d-inline-block w-auto">
                            </label>
                        @else
                            <span class="msg-muted">You can send files once you have both replied.</span>
                        @endif
                        <button type="submit" class="btn btn-sm lw-btn-primary">Send</button>
                    </div>
                    <div class="msg-muted mt-1">Up to {{ config('messaging.attachments.max_files') }} files, {{ (int) (config('messaging.attachments.max_kb') / 1024) }} MB each. You can take a message back for {{ config('messaging.unsend_minutes') }} minutes.</div>
                </form>
            @elseif($blocked)
                <div class="msg-readonly">{{ $iBlocked ? 'You blocked this conversation. Unblock to reply.' : 'You can’t reply to this conversation.' }}</div>
            @else
                <div class="msg-readonly">{{ $conversation->read_only_reason ?: 'This conversation is read-only.' }}</div>
            @endif
        </section>
    </div>
</div>

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
    function applySeen(seenUpTo) {
        var mine = thread.querySelectorAll('[data-mine="1"]');
        var target = null;
        mine.forEach(function (el) {
            var label = el.querySelector('[data-seen-label]');
            if (label) { label.hidden = true; }
            if (parseInt(el.dataset.msgId, 10) <= seenUpTo) { target = el; }
        });
        if (target) { var l = target.querySelector('[data-seen-label]'); if (l) { l.hidden = false; } }
    }
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
                    var anchor = document.getElementById('latest');
                    anchor.insertAdjacentHTML('beforebegin', data.html);
                    scrollDown();
                }
                lastId = data.last_id || lastId;
                applySeen(data.seen_up_to || 0);
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
@endsection
