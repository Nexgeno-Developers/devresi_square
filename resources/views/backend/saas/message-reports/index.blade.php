@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 mb-0">Message reports</h1>
            <p class="text-muted mb-0 small">You only see reported content and up to {{ config('messaging.moderation_context') }} messages either side of it. Every view is logged.</p>
        </div>
        <div class="btn-group btn-group-sm" role="group" aria-label="Filter reports">
            @foreach(['open' => 'Open', 'actioned' => 'Actioned', 'dismissed' => 'Dismissed'] as $key => $label)
                <a class="btn {{ $status === $key ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('backend.saas.message-reports.index', ['status' => $key]) }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="card mb-4">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Reported</th><th>Reason</th><th>About</th><th>By</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($reports as $report)
                    <tr>
                        <td>{{ $report->created_at->format('j M Y H:i') }}</td>
                        <td>{{ ucfirst($report->category) }}</td>
                        <td>{{ $report->reportedParty()?->displayName() ?? '—' }} <span class="text-muted small">{{ $report->reportedParty()?->roleLabel() }}</span></td>
                        <td>{{ $report->reporter?->name ?? 'Deleted user' }}</td>
                        <td>{{ ucfirst($report->status) }}</td>
                        <td><a class="btn btn-sm btn-outline-secondary" href="{{ route('backend.saas.message-reports.show', $report) }}">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted text-center py-4">No {{ $status }} reports.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $reports->links() }}

    <h2 class="h6">Paused or suspended senders</h2>
    <div class="card">
        <ul class="list-group list-group-flush">
            @forelse($flags as $flag)
                @php $p = \App\Support\Messaging\Party::make($flag->party_type, (int) $flag->party_id); @endphp
                <li class="list-group-item small">
                    <strong>{{ $p->displayName() }}</strong> ({{ $p->roleLabel() }}) —
                    @if($flag->isSuspended()) suspended until {{ $flag->suspended_until->year >= 2037 ? 'reviewed' : $flag->suspended_until->format('j M Y') }}. {{ $flag->suspended_reason }}
                    @else paused until {{ $flag->paused_until->format('j M Y') }} @endif
                </li>
            @empty
                <li class="list-group-item text-muted small">Nobody is paused or suspended.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
