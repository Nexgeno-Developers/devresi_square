@php
    /** @var \App\Models\Message $message */
    $mine = $message->sentBy($party);
    $sender = $message->senderParty();
    $authorLabel = null;
    if (! $message->isSystem() && ! $mine) {
        // Several people can be in a chat, so always say who wrote it.
        $authorLabel = $sender?->displayName();
        if ($sender?->isAccount() && $message->author) {
            $authorLabel = $message->author->name.' ('.$authorLabel.')';
        }
    } elseif ($mine && $party->isAccount() && $message->author && (int) $message->user_id !== (int) auth()->id()) {
        $authorLabel = $message->author->name;
    }
@endphp
@if($message->isSystem())
    <div class="msg-system" id="m-{{ $message->public_id }}" data-msg-id="{{ $message->id }}" data-system="1">
        {{ $message->body }}
        <div class="msg-muted"><time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('j M, H:i') }}</time></div>
    </div>
@else
<div class="msg-bubble {{ $mine ? 'is-mine' : '' }}" id="m-{{ $message->public_id }}" data-msg-id="{{ $message->id }}" data-mine="{{ $mine ? 1 : 0 }}">
    @if($authorLabel)<div class="msg-muted small fw-semibold">{{ $authorLabel }}</div>@endif
    @if(filled($message->body))<div>{!! nl2br(e($message->body)) !!}</div>@endif
    @if($message->attachments->isNotEmpty())
        <div class="msg-files">
            @foreach($message->attachments as $file)
                @php $fileUrl = \App\Support\Messaging\MessagingArea::route($area, 'download', [$conversation, $file]); @endphp
                @if($file->isImage())
                    <a href="{{ $fileUrl }}" title="Download {{ $file->original_name }}"><img src="{{ $fileUrl }}?inline=1" alt="{{ $file->original_name }}" loading="lazy"></a>
                @else
                    <a class="msg-file" href="{{ $fileUrl }}"><i class="bi bi-paperclip" aria-hidden="true"></i>{{ \Illuminate\Support\Str::limit($file->original_name, 40) }}</a>
                @endif
            @endforeach
        </div>
    @endif
    <div class="msg-meta msg-muted">
        <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('j M, H:i') }}</time>
    </div>
</div>
@endif
