@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3">
    <h2>Notification delivery log</h2>
    <form method="GET" class="row g-2 mb-3"><div class="col-md-2"><select name="status" class="form-select"><option value="">All statuses</option>@foreach(['pending','sent','failed'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div><div class="col-md-2"><select name="channel" class="form-select"><option value="">All channels</option><option value="email" @selected(request('channel')==='email')>Email</option><option value="system" @selected(request('channel')==='system')>In-app</option></select></div><div class="col-md-4"><input name="event_key" value="{{ request('event_key') }}" class="form-control" placeholder="Event key"></div><div class="col-md-2"><button class="btn btn-primary">Filter</button></div></form>
    <div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Created</th><th>Event</th><th>Channel</th><th>Recipient</th><th>Status</th><th>Error</th><th></th></tr></thead><tbody>
    @forelse($deliveries as $delivery)<tr><td>{{ $delivery->created_at?->timezone(current_account()?->timezone ?? 'Europe/London')->format('d M Y H:i') }}</td><td>{{ $delivery->identifier }}</td><td>{{ $delivery->channel === 'system' ? 'In-app' : ucfirst($delivery->channel) }}</td><td>{{ $delivery->recipient ?: 'User #'.$delivery->notifiable_id }}</td><td><span class="badge {{ $delivery->status==='sent'?'bg-success':($delivery->status==='failed'?'bg-danger':'bg-secondary') }}">{{ ucfirst($delivery->status) }}</span></td><td class="text-truncate" style="max-width:300px" title="{{ $delivery->error }}">{{ $delivery->error }}</td><td>@if($delivery->status==='failed')<form method="POST" action="{{ route('backend.notifications.deliveries.retry',$delivery) }}">@csrf<button class="btn btn-sm btn-outline-primary">Retry</button></form>@endif</td></tr>
    @empty<tr><td colspan="7" class="text-muted">No deliveries found.</td></tr>@endforelse
    </tbody></table></div></div><div class="mt-3">{{ $deliveries->links() }}</div>
</div>
@endsection
