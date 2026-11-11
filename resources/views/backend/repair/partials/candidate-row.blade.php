<div class="list-group-item d-flex gap-3 align-items-start">
    @if($row->can_take)
        <input type="checkbox" class="form-check-input mt-1 pick" name="contractor_ids[]" value="{{ $row->user_id }}" @checked(in_array($row->user_id, (array) old('contractor_ids', [])))>
    @endif
    <span class="flex-grow-1">
        <strong>{{ $row->name }}</strong>
        <x-lw.pill :tone="$row->tone">{{ $row->scope }}</x-lw.pill>
        @if($row->mine)<x-lw.pill tone="ok">My contractor</x-lw.pill>@endif
        @if($row->marketplace)<x-lw.pill tone="idle">Marketplace</x-lw.pill>@endif
        @if($row->worked)<x-lw.pill tone="idle">Worked with you</x-lw.pill>@endif
        <span class="d-block small text-muted">
            {{ $row->miles !== null ? $row->miles.' miles away' : 'Distance unknown' }}
            @foreach($row->badges as $badge) · {{ $badge }} @endforeach
            @if(($row->rating ?? null) !== null) · {{ $row->rating }}★ ({{ $row->ratings }}) @endif
            @if(! $row->can_take && $row->reason) · {{ $row->reason }} @endif
        </span>
    </span>
    @if($row->mine && config('messaging.enabled', true) && \App\Support\Messaging\MessagingBadge::area() === 'admin')
        <a class="btn btn-link btn-sm p-0 text-decoration-none" href="{{ route('admin.messages.create', ['contractor' => $row->user_id]) }}" title="Message {{ $row->name }}">
            <i class="bi bi-chat-dots" aria-hidden="true"></i><span class="visually-hidden">Message</span>
        </a>
    @endif
    <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none" title="{{ $row->favourite ? 'Remove favourite' : 'Add favourite' }}"
        formaction="{{ route('admin.property_repairs.marketplace.favourite', $row->user_id) }}" formnovalidate>{{ $row->favourite ? '★' : '☆' }}</button>
</div>
