@if($events->isEmpty())
    <p class="text-muted mb-0">{{ $fallback ?? 'Nothing has been recorded yet.' }}</p>
@else
    <ol class="list-unstyled mb-0">
        @foreach($events as $event)
            <li class="mb-3">
                <div class="fw-semibold">{{ $event->label() }}</div>
                <div>{{ $event->body }}</div>
                <div class="small text-muted">
                    {{ $event->actor?->name ?: 'Resisquare' }}
                    @if($event->created_at)
                        · {{ $event->created_at->timezone(config('app.timezone'))->format('j M Y, H:i') }}
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
@endif
