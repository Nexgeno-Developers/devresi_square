@extends('backend.layout.app')

@section('content')
@php $trades = \App\Support\ContractorCompliance::trades(); @endphp
<div class="container-fluid lw-page">
    @include('backend.contractors._nav')

    <div class="card lw-card mb-3">
        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-end">
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:220px" placeholder="Name, email, postcode, company no.">
                <select name="status" class="form-select form-select-sm w-auto">
                    <option value="">Any status</option>
                    @foreach(['draft' => 'Not submitted', 'submitted' => 'Pending review', 'changes_requested' => 'More info needed', 'approved' => 'Approved', 'rejected' => 'Rejected', 'suspended' => 'Suspended', 'removed' => 'Removed'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="trade" class="form-select form-select-sm w-auto">
                    <option value="">Any trade</option>
                    @foreach($trades as $key => $label)
                        <option value="{{ $key }}" @selected(request('trade') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn lw-btn-secondary btn-sm" type="submit">Filter</button>
            </form>
            <form method="POST" action="{{ route('admin.registrations.contractors.legacy') }}" onsubmit="return confirm('Email every contractor from the old module a one-time link to claim their new account?')">
                @csrf
                <button class="btn btn-outline-primary btn-sm" type="submit">Invite old-module contractors</button>
                @if($legacyPending)<span class="small text-muted ms-1">{{ $legacyPending }} not claimed yet</span>@endif
            </form>
        </div>
    </div>

    <div class="card lw-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table lw-table align-middle mb-0">
                    <thead><tr><th>Contractor</th><th>Status</th><th>Trades</th><th>Coverage</th><th>Next expiry</th><th>Response</th><th>Cancels</th><th>Jobs</th><th>Rating</th></tr></thead>
                    <tbody>
                        @forelse($profiles as $profile)
                            @php
                                $stats = $performance[(int) $profile->user_id] ?? null;
                                $next = \App\Support\ContractorCompliance::nextExpiry($profile);
                                $paused = \App\Support\ContractorCompliance::isPaused($profile);
                                $tone = $paused ? 'warn' : match ($profile->approval_status) {
                                    'approved' => 'ok',
                                    'rejected', 'suspended', 'removed' => 'bad',
                                    'submitted', 'changes_requested' => 'warn',
                                    default => 'idle',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.registrations.contractors.show', $profile) }}"><strong>{{ $profile->displayName() }}</strong></a>
                                    @if($profile->legacy_contractor)<span class="badge text-bg-light">old module</span>@endif
                                    <div class="small text-muted">{{ $profile->user?->email }}</div>
                                </td>
                                <td>
                                    <x-lw.pill :tone="$tone">
                                        {{ $paused ? 'Paused' : $profile->statusLabel() }}
                                    </x-lw.pill>
                                </td>
                                <td class="small">
                                    @foreach($profile->tradeApprovals as $approval)
                                        <span class="badge {{ $approval->status === 'approved' ? 'text-bg-success' : ($approval->status === 'rejected' ? 'text-bg-danger' : 'text-bg-light') }}">{{ $trades[$approval->trade] ?? $approval->trade }}</span>
                                    @endforeach
                                </td>
                                <td class="small">{{ $profile->postcode ?: '—' }} @if($profile->postcode)· {{ $profile->coverage_radius_miles }} mi @endif</td>
                                <td class="small {{ $next && $next->expires_at->lte(now()->addDays(30)) ? 'text-danger' : '' }}">{{ $next ? $next->label().' '.$next->expires_at->format('j M Y') : '—' }}</td>
                                <td class="small">{{ $stats && $stats['rate'] !== null ? $stats['rate'].'%' : '—' }}</td>
                                <td class="small">{{ $stats['cancellations'] ?? 0 }}</td>
                                <td class="small">{{ $stats['awarded'] ?? 0 }}</td>
                                <td class="small">{{ ($stats['rating'] ?? null) !== null ? $stats['rating'].'★ ('.$stats['ratings'].')' : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-muted">No contractors match.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $profiles->links() }}</div>
        </div>
    </div>
</div>
@endsection
