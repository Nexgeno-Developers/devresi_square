@php
    /** @var \App\Models\Message $message */
    $mine = $message->sentBy($party);
    $gone = $message->trashed() || $message->isRemoved();
    $canUnsend = $mine && ! $gone && (int) $message->user_id === (int) auth()->id()
        && $message->created_at->gt(now()->subMinutes((int) config('messaging.unsend_minutes')));
    $authorLabel = null;
    if (! $mine && $message->senderParty()->isAccount() && $message->author) {
        $authorLabel = $message->author->name;
    } elseif ($mine && $party->isAccount() && $message->author && (int) $message->user_id !== (int) auth()->id()) {
        $authorLabel = $message->author->name;
    }
@endphp
<div class="msg-bubble {{ $mine ? 'is-mine' : '' }} {{ $gone ? 'is-gone' : '' }}" id="m-{{ $message->public_id }}" data-msg-id="{{ $message->id }}" data-mine="{{ $mine ? 1 : 0 }}">
    @if($message->trashed())
        Message taken back
    @elseif($message->isRemoved())
        Message removed by ResiSquare
    @else
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
        @if($message->masked)
            <div class="msg-muted small mt-1">Contact details are hidden until you have both replied.</div>
        @endif
    @endif
    <div class="msg-meta msg-muted">
        <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('j M, H:i') }}</time>
        @if($mine && ! $gone)
            <span data-seen-label @if((int) $message->id !== (int) ($lastSeenMineId ?? 0)) hidden @endif>Seen</span>
        @endif
        @if($canUnsend)
            <form method="POST" action="{{ \App\Support\Messaging\MessagingArea::route($area, 'unsend', [$conversation, $message]) }}" onsubmit="return confirm('Take this message back? The other side will see that a message was taken back.')">
                @csrf @method('DELETE')
                <button type="submit" class="msg-btn-link">Take back</button>
            </form>
        @endif
        @if(! $mine && ! $gone && $message->kind === \App\Models\Message::TEXT)
            <details class="msg-menu d-inline">
                <summary class="msg-btn-link">Report</summary>
                <div class="msg-menu-body">
                    <form method="POST" action="{{ \App\Support\Messaging\MessagingArea::route($area, 'report', $conversation) }}">
                        @csrf
                        <input type="hidden" name="message" value="{{ $message->public_id }}">
                        <label class="form-label small mb-1" for="cat-{{ $message->public_id }}">What is wrong?</label>
                        <select class="form-select form-select-sm mb-2" name="category" id="cat-{{ $message->public_id }}" required>
                            <option value="spam">Spam</option>
                            <option value="harassment">Harassment or abuse</option>
                            <option value="scam">Scam or fraud</option>
                            <option value="other">Something else</option>
                        </select>
                        <textarea class="form-control form-control-sm mb-2" name="note" rows="2" maxlength="1000" placeholder="Anything we should know (optional)"></textarea>
                        <button class="btn btn-sm btn-outline-danger" type="submit">Send report</button>
                    </form>
                </div>
            </details>
        @endif
    </div>
</div>
