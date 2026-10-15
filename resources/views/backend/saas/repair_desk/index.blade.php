@extends('backend.layout.app')

@section('content')
<div class="container-fluid lw-page">
    <div class="lw-hero">
        <h4>Repair desk</h4>
        <p>Repairs a landlord sent to Resisquare. Assign one to the team and update the status. This does not invite a contractor.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card lw-card">
        <div class="card-body table-responsive">
            <table class="table lw-table align-middle">
                <thead>
                    <tr>
                        <th>Repair</th>
                        <th>Account</th>
                        <th>From</th>
                        <th>Status</th>
                        <th>Team</th>
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
                                <div class="small text-muted">{{ $where ?: 'Address withheld until the team opens the job' }}</div>
                                <div class="small">{{ \Illuminate\Support\Str::limit($repair?->description, 120) }}</div>
                                @if($desk->landlord_note)
                                    <div class="small mt-1">Landlord note: {{ $desk->landlord_note }}</div>
                                @endif
                            </td>
                            <td>{{ $desk->account_id }}</td>
                            <td>
                                {{ $desk->requester?->name ?: 'Landlord' }}<br>
                                <span class="small text-muted">{{ rs_date($desk->created_at) }}</span>
                            </td>
                            <td>{{ $desk->statusLabel() }}</td>
                            <td style="min-width: 16rem;">
                                <form method="POST" action="{{ route('backend.saas.repair_desk.update', $desk) }}" class="d-grid gap-2">
                                    @csrf
                                    <select name="assigned_to" class="form-select form-select-sm">
                                        <option value="">Unassigned</option>
                                        @foreach($team as $member)
                                            <option value="{{ $member->id }}" @selected((int) $desk->assigned_to === (int) $member->id)>{{ $member->name }}</option>
                                        @endforeach
                                    </select>
                                    <select name="status" class="form-select form-select-sm">
                                        @foreach(\App\Models\RepairDeskRequest::STATUSES as $value => $label)
                                            <option value="{{ $value }}" @selected($desk->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input name="team_note" class="form-control form-control-sm" maxlength="2000" value="{{ $desk->team_note }}" placeholder="Note for the team">
                                    <button class="btn btn-primary btn-sm" type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No repairs have been sent to Resisquare.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
