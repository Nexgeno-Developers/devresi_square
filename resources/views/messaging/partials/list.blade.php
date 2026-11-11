@forelse($items as $item)
    <a href="{{ $item['url'] }}" class="msg-item {{ $item['unread'] ? 'is-unread' : '' }} {{ ($activeKey ?? null) === $item['key'] ? 'is-active' : '' }}" data-msg-item="{{ $item['key'] }}">
        <div class="msg-item-top">
            <span class="msg-item-title">{{ $item['title'] }}</span>
            <span class="msg-item-time">{{ $item['at'] ? $item['at']->diffForHumans(null, true, true) : '' }}</span>
        </div>
        <div class="msg-item-label">
            {{ $item['label'] }}
            @if($item['read_only']) · <span>Read-only</span>@endif
            @if($item['unread']) <span class="msg-pill" aria-label="{{ $item['unread'] }} unread">{{ $item['unread'] }}</span>@endif
        </div>
        <div class="msg-item-preview">{{ $item['preview'] }}</div>
    </a>
@empty
    <div class="msg-empty">
        <i class="bi bi-chat-dots fs-2 d-block mb-2" aria-hidden="true"></i>
        @if($archived ?? false)
            Nothing archived.
        @else
            No conversations yet.<br>
            <span class="small">Use “New message”, or the Message buttons on tenancies, jobs and appointments.</span>
        @endif
    </div>
@endforelse
