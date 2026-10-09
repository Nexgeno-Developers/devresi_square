@extends('backend.layout.app')

@section('content')
<div class="container-fluid lw-page">
    @include('backend.contractors._nav')

    <div class="card lw-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="lw-section-title m-0">{{ $rows->count() }} waiting · oldest first</div>
                @if($waitingOnContractor)
                    <a class="small" href="{{ route('admin.registrations.contractors.directory', ['status' => 'changes_requested']) }}">{{ $waitingOnContractor }} waiting on the contractor</a>
                @endif
            </div>
            @if($rows->isEmpty())
                <x-lw.empty title="Queue is clear">New applications and renewals appear here.</x-lw.empty>
            @else
                <div class="table-responsive">
                    <table class="table lw-table align-middle mb-0">
                        <thead><tr><th>Contractor</th><th>What to review</th><th>Trades</th><th>Waiting</th></tr></thead>
                        <tbody>
                            @foreach($rows as $row)
                                @php $profile = $row->profile; @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.registrations.contractors.show', $profile) }}"><strong>{{ $profile->displayName() }}</strong></a>
                                        <div class="small text-muted">{{ $profile->user?->email }}</div>
                                    </td>
                                    <td class="small">
                                        {{ $profile->approval_status === 'submitted' ? 'New application' : 'Renewal or new trade' }}
                                        · {{ $row->pendingDocs }} {{ \Illuminate\Support\Str::plural('document', $row->pendingDocs) }}
                                        @if($row->pendingTrades) · {{ $row->pendingTrades }} {{ \Illuminate\Support\Str::plural('trade', $row->pendingTrades) }} @endif
                                    </td>
                                    <td class="small">{{ collect($profile->trades ?? [])->map(fn ($t) => \App\Support\ContractorCompliance::trades()[$t] ?? $t)->implode(', ') }}</td>
                                    <td>
                                        @if($row->age > $sla)
                                            <x-lw.pill tone="bad">{{ $row->age }} working days · over SLA</x-lw.pill>
                                        @elseif($row->age === $sla)
                                            <x-lw.pill tone="warn">{{ $row->age }} working days</x-lw.pill>
                                        @else
                                            <span class="small text-muted">{{ $row->since?->diffForHumans() }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
