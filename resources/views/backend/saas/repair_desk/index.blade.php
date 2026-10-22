@extends('backend.layout.app')

@section('content')
<div class="container-fluid lw-page">
    <div class="lw-hero">
        <h4>Repair desk</h4>
        <p>Open a repair, take it, keep the landlord and tenant posted, then mark it complete or hand it back.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex gap-2 mb-3">
        @foreach(['open' => 'Open', 'done' => 'Completed', 'returned' => 'Returned'] as $key => $label)
            <a href="{{ route('backend.saas.repair_desk.index', ['state' => $key]) }}" class="btn btn-sm {{ $state === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $label }} ({{ $counts[$key] }})
            </a>
        @endforeach
    </div>

    <div class="card lw-card">
        <div class="card-body table-responsive">
            <table class="table lw-table align-middle">
                <thead>
                    <tr>
                        <th>Repair</th>
                        <th>Account</th>
                        <th>From</th>
                        <th>Status</th>
                        <th>With</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $desk)
                        @php
                            $repair = $desk->repairIssue;
                            $property = $repair?->property;
                            $title = $property?->short_title ?: ($repair?->reference_number ?: 'Repair');
                            $where = $property?->full_address ?: trim(collect([$property?->city, $property?->postcode])->filter()->implode(' · '));
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $title }}</div>
                                <div class="small text-muted">{{ $where ?: 'No address on the repair' }}</div>
                                <div class="small">{{ \Illuminate\Support\Str::limit($repair?->description, 140) }}</div>
                                @if($desk->landlord_note)
                                    <div class="small mt-1">Landlord: {{ $desk->landlord_note }}</div>
                                @endif
                            </td>
                            <td>{{ $desk->account?->account_name ?: ('Account '.$desk->account_id) }}</td>
                            <td>
                                {{ $desk->requester?->name ?: 'Landlord' }}<br>
                                <span class="small text-muted">{{ rs_date($desk->created_at) }}</span>
                            </td>
                            <td>{{ $desk->statusLabel() }}</td>
                            <td>{{ $desk->assignee?->name ?: 'Nobody yet' }}</td>
                            <td class="text-end">
                                <a class="btn btn-primary btn-sm" href="{{ route('backend.saas.repair_desk.show', $desk) }}">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No repairs in this list.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
