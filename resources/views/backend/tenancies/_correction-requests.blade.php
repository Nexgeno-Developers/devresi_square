@php
    $pending = $tenancy->correctionRequests->where('status', 'pending');
    $history = $tenancy->correctionRequests->where('status', '!=', 'pending');
@endphp
<div class="mb-4 border rounded p-3" data-pending-corrections="{{ $pending->count() }}">
    <strong>Tenant confirmation</strong>
    <p class="text-muted small mb-3">Invited tenants confirm rent, dates and deposit on first login. If something looks wrong they can send a correction request here.</p>

    <ul class="list-unstyled mb-3">
        @forelse($tenancy->tenantMembers as $member)
            @php
                $status = $member->details_status ?: 'pending';
                $badge = match ($status) {
                    'confirmed' => 'bg-success',
                    'correction_requested' => 'bg-warning text-dark',
                    default => 'bg-secondary',
                };
                $label = match ($status) {
                    'confirmed' => 'Confirmed'.($member->details_confirmed_at ? ' '.$member->details_confirmed_at->format('d M Y') : ''),
                    'correction_requested' => 'Correction requested',
                    default => 'Waiting to confirm',
                };
            @endphp
            <li class="d-flex justify-content-between gap-3 py-1">
                <span>{{ $member->user?->name ?? 'Tenant' }}</span>
                <span class="badge {{ $badge }}">{{ $label }}</span>
            </li>
        @empty
            <li class="text-muted">No tenants linked yet.</li>
        @endforelse
    </ul>

    @foreach($pending as $request)
        @php
            $suggested = collect($request->fields ?? []);
        @endphp
        <div class="border rounded p-3 mb-3 bg-light">
            <div class="d-flex justify-content-between gap-2 flex-wrap mb-2">
                <strong>Correction from {{ $request->requester?->name ?: 'tenant' }}</strong>
                <span class="badge bg-warning text-dark">Pending review</span>
            </div>
            <p class="mb-2">{{ $request->message }}</p>
            <table class="table table-sm mb-3">
                <thead>
                    <tr>
                        <th>Detail</th>
                        <th>Current</th>
                        <th>Tenant suggestion</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($suggested as $field)
                        <tr>
                            <td>{{ $field['label'] ?? $field['key'] }}</td>
                            <td>{{ $field['current'] ?? '—' }}</td>
                            <td>{{ $field['suggested'] ?? 'No suggested value' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @can('update', $tenancy)
                <form method="POST" action="{{ route('admin.tenancies.correction-requests.approve', [$tenancy, $request]) }}" class="row g-2 mb-2">
                    @csrf
                    @foreach($suggested as $field)
                        @php $key = $field['key'] ?? ''; @endphp
                        @if($key === 'move_in')
                            <div class="col-md-4">
                                <label class="form-label">Move in</label>
                                <input type="date" name="move_in" class="form-control" value="{{ $field['suggested'] ?: ($tenancy->move_in?->format('Y-m-d')) }}" required>
                            </div>
                        @elseif($key === 'move_out')
                            <div class="col-md-4">
                                <label class="form-label">Move out</label>
                                <input type="date" name="move_out" class="form-control" value="{{ $field['suggested'] ?: ($tenancy->move_out?->format('Y-m-d')) }}">
                            </div>
                        @elseif($key === 'rent')
                            <div class="col-md-4">
                                <label class="form-label">Rent</label>
                                <input type="number" min="0" step="0.01" name="rent" class="form-control" value="{{ $field['suggested'] ?: $tenancy->rent }}" required>
                            </div>
                        @elseif($key === 'frequency')
                            <div class="col-md-4">
                                <label class="form-label">Frequency</label>
                                <select name="frequency" class="form-select">
                                    <option value="Monthly" @selected(($field['suggested'] ?: $tenancy->frequency) === 'Monthly')>Monthly</option>
                                    <option value="Weekly" @selected(($field['suggested'] ?: $tenancy->frequency) === 'Weekly')>Weekly</option>
                                </select>
                            </div>
                        @elseif($key === 'deposit')
                            <div class="col-md-4">
                                <label class="form-label">Deposit</label>
                                <input type="number" min="0" step="0.01" name="deposit" class="form-control" value="{{ $field['suggested'] ?: $tenancy->deposit }}">
                            </div>
                        @elseif($key === 'term_months')
                            <div class="col-md-4">
                                <label class="form-label">Term (months)</label>
                                <input type="number" min="0" max="60" name="term_months" class="form-control" value="{{ $field['suggested'] ?: $tenancy->term_months }}">
                            </div>
                        @endif
                    @endforeach
                    <div class="col-12">
                        <textarea name="landlord_note" class="form-control" rows="2" placeholder="Optional note to the tenant"></textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-success">Approve and update tenancy</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('admin.tenancies.correction-requests.reject', [$tenancy, $request]) }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-8">
                            <input type="text" name="landlord_note" class="form-control" required placeholder="Why you are keeping the original details">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-danger w-100">Decline request</button>
                        </div>
                    </div>
                </form>
            @endcan
        </div>
    @endforeach

    @if($history->isNotEmpty())
        <details>
            <summary class="small text-muted">Earlier requests</summary>
            <ul class="mt-2 mb-0">
                @foreach($history as $request)
                    <li class="small">
                        {{ ucfirst($request->status) }} · {{ $request->requester?->name }} · {{ $request->reviewed_at?->format('d M Y') ?: $request->created_at?->format('d M Y') }}
                        @if($request->landlord_note)
                            — {{ $request->landlord_note }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </details>
    @endif
</div>
