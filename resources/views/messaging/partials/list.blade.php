@if($items->count() > 3)
    <div class="msg-list-search">
        <label class="msg-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <span class="visually-hidden">Search chats</span>
            <input type="search" placeholder="Search chats" autocomplete="off" data-msg-search>
        </label>
    </div>
@endif
<div class="msg-list-items">
@forelse($items as $item)
    <a href="{{ $item['url'] }}" class="msg-item {{ $item['unread'] ? 'is-unread' : '' }} {{ ($activeKey ?? null) === $item['key'] ? 'is-active' : '' }}" data-msg-item="{{ $item['key'] }}" @if(($activeKey ?? null) === $item['key']) aria-current="true" @endif>
        <span class="msg-avatar is-lg is-kind {{ \App\Support\Messaging\ChatAvatar::kindClass($item['kind'] ?? null) }}" aria-hidden="true"><i class="bi {{ \App\Support\Messaging\ChatAvatar::kindIcon($item['kind'] ?? null) }}"></i></span>
        <span class="d-block" style="min-width:0">
            <span class="msg-item-top">
                <span class="msg-item-title">{{ $item['title'] }}</span>
                <span class="msg-item-time">{{ \App\Support\Messaging\ChatAvatar::listTime($item['at']) }}</span>
            </span>
            <span class="msg-item-label d-block">{{ $item['label'] }}</span>
            <span class="msg-item-bottom">
                <span class="msg-item-preview">{{ $item['preview'] ?: 'No messages yet' }}</span>
                @if($item['unread'])<span class="msg-pill" aria-label="{{ $item['unread'] }} unread">{{ $item['unread'] }}</span>@endif
            </span>
        </span>
    </a>
@empty
    <div class="msg-empty">
        <div class="msg-empty-icon"><i class="bi {{ ($archived ?? false) ? 'bi-archive' : 'bi-chat-dots' }}" aria-hidden="true"></i></div>
        @if($archived ?? false)
            <h3>Nothing archived yet</h3>
            <p>Job chats move here when the job is signed off, and tenancy chats when the tenancy ends.</p>
        @else
            <h3>No chats yet</h3>
            <p>
                @if(($area ?? null) === 'contractor')
                    A chat with the landlord and tenants opens when you are assigned a job.
                @elseif(($area ?? null) === 'portal')
                    Your chat with your landlord appears here once your tenancy is active.
                @else
                    Each active tenancy has a chat with its tenants, and each assigned job a chat with the contractor and tenants.
                @endif
            </p>
        @endif
    </div>
@endforelse
    <div class="msg-list-none" data-msg-search-none>No chats match your search.</div>
</div>
