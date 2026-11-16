@extends('backend.layout.app')

@section('content')
@include('messaging.partials.styles')
@php
    $r = fn ($name, $params = []) => \App\Support\Messaging\MessagingArea::route($area, $name, $params);
    $active = $conversation->isActive();
    $av = \App\Support\Messaging\ChatAvatar::class;
    $maxFiles = (int) config('messaging.attachments.max_files');
    $maxMb = (int) (config('messaging.attachments.max_kb') / 1024);
@endphp
<div class="msg-shell" data-messaging-area="{{ $area }}">
    @include('messaging.partials.header')
    <div class="msg-layout has-thread">
        <aside class="msg-list" aria-label="Chats">
            @include('messaging.partials.list', ['items' => $active ? $sidebarItems : collect([['key' => $conversation->public_id, 'kind' => $conversation->kind, 'title' => $about['title'], 'label' => $about['label'], 'preview' => '', 'at' => $conversation->last_message_at, 'unread' => 0, 'url' => $r('show', $conversation), 'archived' => true]]), 'activeKey' => $conversation->public_id])
        </aside>
        <section class="msg-pane" aria-label="{{ $about['title'] }}">
            <div class="msg-thread-head">
                <div style="min-width:0">
                    <a class="msg-back" href="{{ $r($active ? 'index' : 'archived') }}"><i class="bi bi-chevron-left" aria-hidden="true"></i> <span>All chats</span></a>
                    <div class="msg-thread-id">
                        <span class="msg-avatar is-lg is-kind {{ $av::kindClass($conversation->kind) }}" aria-hidden="true"><i class="bi {{ $av::kindIcon($conversation->kind) }}"></i></span>
                        <div>
                            <h2>{{ $about['title'] }}</h2>
                            <div class="msg-thread-sub">{{ $about['label'] }}</div>
                        </div>
                    </div>
                    @php $soloSameAsTitle = $about['people']->count() === 1 && $about['people']->first()['name'] === $about['title']; @endphp
                    <div class="msg-people {{ $soloSameAsTitle ? 'visually-hidden' : '' }}" data-msg-people>
                        <span class="visually-hidden">With:</span>
                        @foreach($about['people'] as $person)
                            <span class="msg-person"><span class="msg-avatar" style="{{ $av::style($person['name']) }}" aria-hidden="true">{{ $av::initials($person['name']) }}</span><strong>{{ $person['name'] }}</strong> {{ $person['role'] }}</span>
                        @endforeach
                    </div>
                </div>
                <details class="msg-menu">
                    <summary class="msg-icon-btn" aria-label="Chat options"><i class="bi bi-three-dots" aria-hidden="true"></i></summary>
                    <div class="msg-menu-body">
                        <form method="POST" action="{{ $r('report', $conversation) }}">@csrf
                            <h3><i class="bi bi-flag" aria-hidden="true"></i> Report this chat</h3>
                            <p>Tell ResiSquare support what is wrong. The other people in the chat are not told.</p>
                            <label class="visually-hidden" for="conv-report">What is wrong?</label>
                            <textarea id="conv-report" name="note" rows="3" maxlength="1000" required placeholder="What is wrong?"></textarea>
                            <button type="submit" class="msg-btn-danger">Send report</button>
                        </form>
                    </div>
                </details>
            </div>

            @unless($active)
                <div class="msg-note" data-msg-archived><i class="bi bi-archive" aria-hidden="true"></i> <span>{{ $conversation->archived_reason ?: 'This chat is archived.' }} You can still read it.</span></div>
            @endunless

            <div class="msg-thread" id="msg-thread" aria-live="polite">
                @if($messages->isEmpty())
                    <div class="msg-empty" data-msg-empty>
                        <div class="msg-empty-icon"><i class="bi bi-chat-heart" aria-hidden="true"></i></div>
                        <h3>No messages yet</h3>
                        <p>Say hello. Everyone in this chat will see it.</p>
                    </div>
                @endif
                @php $prev = null; @endphp
                @foreach($messages as $message)
                    @php
                        $day = $message->created_at->toDateString();
                        $newDay = ! $prev || $prev->created_at->toDateString() !== $day;
                        $unreadHere = $firstUnreadId && $message->id === $firstUnreadId;
                        $grouped = $prev && ! $newDay && ! $unreadHere
                            && ! $prev->isSystem() && ! $message->isSystem()
                            && $prev->sender_party_type === $message->sender_party_type
                            && (int) $prev->sender_party_id === (int) $message->sender_party_id
                            && (int) $prev->user_id === (int) $message->user_id
                            && $prev->created_at->diffInMinutes($message->created_at) < 5;
                    @endphp
                    @if($newDay)
                        <div class="msg-day" data-day="{{ $day }}">{{ $av::dayLabel($message->created_at) }}</div>
                    @endif
                    @if($unreadHere)
                        <div class="msg-divider">New messages</div>
                    @endif
                    @include('messaging.partials.message', ['message' => $message, 'grouped' => $grouped])
                    @php $prev = $message; @endphp
                @endforeach
                <span id="latest"></span>
            </div>

            @if($active)
                <form class="msg-composer" method="POST" action="{{ $r('send', $conversation) }}" enctype="multipart/form-data" data-msg-composer>
                    @csrf
                    <div class="msg-compose-files" data-msg-files aria-live="polite"></div>
                    <div class="msg-compose-box">
                        <label class="msg-attach" title="Attach files">
                            <i class="bi bi-paperclip" aria-hidden="true"></i>
                            <span class="visually-hidden">Attach files</span>
                            <input type="file" name="files[]" multiple accept=".{{ implode(',.', config('messaging.attachments.extensions')) }}" data-msg-file-input>
                        </label>
                        <label class="visually-hidden" for="msg-body">Message</label>
                        <textarea id="msg-body" name="body" rows="1" maxlength="{{ config('messaging.body_max') }}" placeholder="Write a message…">{{ old('body') }}</textarea>
                        <button type="submit" class="msg-send" data-msg-send><i class="bi bi-send-fill" aria-hidden="true"></i><span class="msg-send-label">Send</span><span class="visually-hidden">Send message</span></button>
                    </div>
                    <div class="msg-compose-hint">
                        <span><span class="msg-hint-who">Everyone in this chat sees your message. </span>Up to {{ $maxFiles }} files, {{ $maxMb }} MB each.</span>
                        <span class="msg-hint-keys">Ctrl + Enter to send</span>
                    </div>
                </form>
            @else
                <div class="msg-readonly"><i class="bi bi-lock" aria-hidden="true"></i> Archived chats are read-only.</div>
            @endif
        </section>
    </div>
</div>

<script>
(function () {
    var thread = document.getElementById('msg-thread');
    if (!thread) { return; }
    var narrow = function () { return thread.scrollHeight <= thread.clientHeight + 2 && getComputedStyle(thread).overflowY === 'visible'; };
    function scrollDown() {
        if (narrow()) {
            // Phone layout: the page (or the layout's content box) scrolls, not the thread.
            var el = thread.parentElement;
            while (el && el !== document.body) {
                var oy = getComputedStyle(el).overflowY;
                if ((oy === 'auto' || oy === 'scroll') && el.scrollHeight > el.clientHeight) { el.scrollTop = el.scrollHeight; return; }
                el = el.parentElement;
            }
            window.scrollTo(0, document.documentElement.scrollHeight);
        } else {
            thread.scrollTop = thread.scrollHeight;
        }
    }

    // Composer: grow with the text, Ctrl/Cmd + Enter sends, show picked files.
    var form = document.querySelector('[data-msg-composer]');
    if (form) {
        var box = form.querySelector('#msg-body');
        var send = form.querySelector('[data-msg-send]');
        var fileInput = form.querySelector('[data-msg-file-input]');
        var chips = form.querySelector('[data-msg-files]');
        var sync = function () {
            box.style.height = 'auto';
            box.style.height = Math.min(box.scrollHeight, 160) + 'px';
            send.disabled = !box.value.trim() && !(fileInput.files && fileInput.files.length);
        };
        box.addEventListener('input', sync);
        box.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey) && !send.disabled) { e.preventDefault(); form.requestSubmit ? form.requestSubmit() : form.submit(); }
        });
        fileInput.addEventListener('change', function () {
            chips.innerHTML = '';
            Array.prototype.forEach.call(fileInput.files || [], function (f) {
                var chip = document.createElement('span');
                chip.className = 'msg-chip';
                chip.innerHTML = '<i class="bi bi-file-earmark" aria-hidden="true"></i><span></span>';
                chip.querySelector('span').textContent = f.name;
                chips.appendChild(chip);
            });
            sync();
        });
        form.addEventListener('submit', function () { send.disabled = true; });
        sync();
    }

    @if($active)
    var pollUrl = @json($r('poll', $conversation));
    var lastId = {{ (int) ($messages->max('id') ?? 0) }};
    var fast = {{ (int) config('messaging.poll.conversation_seconds') }} * 1000;
    var slow = {{ (int) config('messaging.poll.hidden_seconds') }} * 1000;
    var timer = null;
    var today = @json(now()->toDateString());

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
                    var latest = document.getElementById('latest');
                    var days = thread.querySelectorAll('.msg-day');
                    var lastDay = days.length ? days[days.length - 1].getAttribute('data-day') : null;
                    if (lastDay !== today) { latest.insertAdjacentHTML('beforebegin', '<div class="msg-day" data-day="' + today + '">Today</div>'); }
                    latest.insertAdjacentHTML('beforebegin', data.html);
                    scrollDown();
                }
                lastId = data.last_id || lastId;
                if (data.active === false) { window.location.reload(); }
            })
            .catch(function () {})
            .finally(schedule);
    }
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { poll(); } });
    schedule();
    @endif
    scrollDown();
})();
</script>
@include('messaging.partials.list-script')
@endsection
