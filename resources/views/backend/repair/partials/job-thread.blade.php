@php
    /** @var \App\Models\RepairJobOffer $offer */
    $mySide = $side;
    $otherLabel = $mySide === \App\Models\RepairJobMessage::CONTRACTOR ? 'Landlord' : ($offer->contractor?->contractorProfile?->displayName() ?: 'Contractor');
    $messages = $offer->messages;
    $canWrite = $offer->canMessage() && ! $offer->isFinished();
    $unread = $messages->where('side', '!=', $mySide)->whereNull('read_at')->count();
@endphp
@if($canWrite || $messages->isNotEmpty())
<details class="job-thread {{ $class ?? '' }}" @if($open ?? false) open @endif>
    <summary class="small {{ $unread ? 'fw-semibold text-primary' : 'text-muted' }}">
        {{ $messages->isEmpty() ? 'Ask a question' : 'Messages ('.$messages->count().')' }}
        @if($unread)<span class="badge text-bg-primary ms-1">{{ $unread }} new</span>@endif
    </summary>
    <div class="mt-2">
        @if($messages->isNotEmpty())
            <ul class="list-unstyled mb-2 d-flex flex-column gap-2" style="max-height: 280px; overflow:auto">
                @foreach($messages as $message)
                    @php $mine = $message->side === $mySide; @endphp
                    <li class="d-flex {{ $mine ? 'justify-content-end' : '' }}">
                        <div class="px-3 py-2 rounded small {{ $mine ? 'bg-primary-subtle' : 'bg-light border' }}" style="max-width: 85%">
                            <div class="text-muted" style="font-size:.75rem">{{ $mine ? 'You' : $otherLabel }} · {{ $message->created_at?->format('j M H:i') }}</div>
                            <div style="white-space: pre-line">{{ $message->body }}</div>
                            @if($message->masked && $mine)
                                <div class="text-muted fst-italic" style="font-size:.75rem">Contact details were hidden. They’re shared once the job is accepted.</div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
        @if($canWrite)
            <form method="POST" action="{{ $action }}" class="d-flex gap-2 align-items-start">
                @csrf
                <textarea name="body" rows="2" maxlength="2000" required class="form-control form-control-sm" placeholder="{{ $mySide === \App\Models\RepairJobMessage::CONTRACTOR ? 'Ask the landlord about the job' : 'Reply to the contractor' }}"></textarea>
                <button class="btn btn-outline-primary btn-sm" type="submit">Send</button>
            </form>
            @unless($offer->unlocksAccess())
                <div class="small text-muted mt-1">Phone numbers, emails and links are hidden until the job is accepted.</div>
            @endunless
        @else
            <div class="small text-muted">Messages are closed for this job.</div>
        @endif
    </div>
</details>
@endif
