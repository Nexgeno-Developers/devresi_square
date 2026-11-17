@php
    /** @var \App\Models\Message $message */
    $mine = $message->sentBy($party);
    $sender = $message->senderParty();
    $grouped = $grouped ?? false;
    $authorLabel = null;
    $avatarName = null;
    if (! $message->isSystem() && ! $mine) {
        // Several people can be in a chat, so always say who wrote it.
        $authorLabel = $sender?->displayName();
        $avatarName = $authorLabel;
        if ($sender?->isAccount() && $message->author) {
            $avatarName = $message->author->name;
            $authorLabel = $message->author->name.' ('.$authorLabel.')';
        }
    } elseif ($mine && $party->isAccount() && $message->author && (int) $message->user_id !== (int) auth()->id()) {
        // A colleague wrote this on behalf of the same account.
        $authorLabel = $message->author->name;
    }
    $time = $message->created_at;
@endphp
@if($message->isSystem())
    <div class="msg-system" id="m-{{ $message->public_id }}" data-msg-id="{{ $message->id }}" data-system="1">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        <div>
            {{ $message->body }}
            <time datetime="{{ $time->toIso8601String() }}">{{ $time->format('j M, H:i') }}</time>
        </div>
    </div>
@else
<div class="msg-row {{ $mine ? 'is-mine' : '' }} {{ $grouped ? 'is-grouped' : '' }}" id="m-{{ $message->public_id }}" data-msg-id="{{ $message->id }}" data-mine="{{ $mine ? 1 : 0 }}" data-sender="{{ $message->sender_party_type }}:{{ $message->sender_party_id }}:{{ $message->user_id }}" data-day="{{ $time->toDateString() }}">
    @unless($mine)
        <span class="msg-avatar-slot" aria-hidden="true"><span class="msg-avatar is-sm" style="{{ \App\Support\Messaging\ChatAvatar::style($avatarName) }}">{{ \App\Support\Messaging\ChatAvatar::initials($avatarName) }}</span></span>
    @endunless
    <div class="msg-bubble {{ $mine ? 'is-mine' : '' }}">
        @if($authorLabel && ! $grouped)<div class="msg-author">{{ $authorLabel }}</div>@endif
        @if(filled($message->body))<div>{!! nl2br(e($message->body)) !!}</div>@endif
        @if($message->attachments->isNotEmpty())
            <div class="msg-files">
                @foreach($message->attachments as $file)
                    @php $fileUrl = \App\Support\Messaging\MessagingArea::route($area, 'download', [$conversation, $file]); @endphp
                    @if($file->isImage())
                        <a href="{{ $fileUrl }}" title="Download {{ $file->original_name }}"><img src="{{ $fileUrl }}?inline=1" alt="{{ $file->original_name }}" loading="lazy"></a>
                    @else
                        <a class="msg-file" href="{{ $fileUrl }}"><i class="bi bi-file-earmark-text" aria-hidden="true"></i>{{ \Illuminate\Support\Str::limit($file->original_name, 40) }}</a>
                    @endif
                @endforeach
            </div>
        @endif
        <div class="msg-meta">
            <time datetime="{{ $time->toIso8601String() }}" title="{{ $time->format('j M Y, H:i') }}">{{ $time->format('H:i') }}</time>
        </div>
    </div>
</div>
@endif
