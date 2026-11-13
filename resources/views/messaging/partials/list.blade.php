@forelse($items as $item)
    <a href="{{ $item['url'] }}" class="msg-item {{ $item['unread'] ? 'is-unread' : '' }} {{ ($activeKey ?? null) === $item['key'] ? 'is-active' : '' }}" data-msg-item="{{ $item['key'] }}">
        <div class="msg-item-top">
            <span class="msg-item-title">{{ $item['title'] }}</span>
            <span class="msg-item-time">{{ $item['at'] ? $item['at']->diffForHumans(null, true, true) : '' }}</span>
        </div>
        <div class="msg-item-label">
            {{ $item['label'] }}
            @if($item['unread']) <span class="msg-pill" aria-label="{{ $item['unread'] }} unread">{{ $item['unread'] }}</span>@endif
        </div>
        <div class="msg-item-preview">{{ $item['preview'] }}</div>
    </a>
@empty
    <div class="msg-empty">
        <i class="bi bi-chat-dots fs-2 d-block mb-2" aria-hidden="true"></i>
        @if($archived ?? false)
            Nothing archived yet.<br>
            <span class="small">Job chats move here when the job is signed off, and tenancy chats when the tenancy ends.</span>
        @else
            No chats yet.<br>
            <span class="small">
                @if(($area ?? null) === 'contractor')
                    A chat with the landlord and tenants opens when you are assigned a job.
                @elseif(($area ?? null) === 'portal')
                    Your chat with your landlord appears here once your tenancy is active.
                @else
                    Each active tenancy has a chat with its tenants, and each assigned job a chat with the contractor and tenants.
                @endif
            </span>
        @endif
    </div>
@endforelse
