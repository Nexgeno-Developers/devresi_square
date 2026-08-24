@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Notifications</h2>
        <div><button type="button" class="btn btn-outline-primary" id="notification-page-read-all">Mark all read</button> <a class="btn btn-outline-secondary" href="{{ route('backend.notifications.preferences') }}">Preferences</a></div>
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-2"><select name="state" class="form-select"><option value="">All states</option><option value="unread" @selected(request('state')==='unread')>Unread</option><option value="read" @selected(request('state')==='read')>Read</option></select></div>
        <div class="col-md-2"><select name="category" class="form-select"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(request('category')===$category)>{{ ucfirst($category) }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="priority" class="form-select"><option value="">All priorities</option>@foreach(['normal','high','critical'] as $priority)<option value="{{ $priority }}" @selected(request('priority')===$priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
        <div class="col-md-2"><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
        <div class="col-md-2"><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
        <div class="col-md-2"><button class="btn btn-primary">Filter</button> <a href="{{ route('backend.notifications.index') }}" class="btn btn-light">Clear</a></div>
    </form>

    <div class="card">
        <div class="list-group list-group-flush">
            @forelse($notifications as $notification)
                <a href="{{ $notification->action_url ?: ($notification->data['url'] ?? '#') }}" class="list-group-item list-group-item-action {{ $notification->read_at ? '' : 'bg-light' }} notification-page-item" data-notification-id="{{ $notification->id }}">
                    <div class="d-flex justify-content-between gap-3">
                        <div><strong>{{ $notification->data['title'] ?? 'Notification' }}</strong><div>{{ $notification->data['message'] ?? '' }}</div><small class="text-muted">{{ ucfirst($notification->category ?? 'general') }} &middot; {{ $notification->created_at?->timezone(current_account()?->timezone ?? 'Europe/London')->format('d M Y, H:i') }}</small></div>
                        <span class="badge {{ $notification->priority === 'critical' ? 'bg-danger' : ($notification->priority === 'high' ? 'bg-warning text-dark' : 'bg-secondary') }} align-self-start">{{ ucfirst($notification->priority ?? 'normal') }}</span>
                    </div>
                </a>
            @empty
                <div class="p-4 text-muted">No notifications match these filters.</div>
            @endforelse
        </div>
    </div>
    <div class="mt-3">{{ $notifications->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
$(document).on('click', '.notification-page-item', function (event) {
    event.preventDefault();
    const destination = this.href;
    $.post('{{ route('backend.notifications.read', ':notification') }}'.replace(':notification', $(this).data('notification-id')), {_token: $('meta[name="csrf-token"]').attr('content')})
        .always(function () { window.location.href = destination; });
});
$(document).on('click', '#notification-page-read-all', function () {
    $.post('{{ route('backend.notifications.read_all') }}', {_token: $('meta[name="csrf-token"]').attr('content')}).done(function () { window.location.reload(); });
});
</script>
@endpush
