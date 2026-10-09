@extends('backend.layout.app')

@section('content')
<div class="container-fluid lw-page">
    @include('backend.contractors._nav')

    <div class="card lw-card mb-3">
        <div class="card-body">
            <div class="lw-section-title mt-0">Active contractors per trade</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($perTrade as $trade => $count)
                    <span class="border rounded px-2 py-1 small {{ $count === 0 ? 'border-danger text-danger' : '' }}">{{ $trades[$trade] ?? $trade }}: <strong>{{ $count }}</strong></span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card lw-card">
        <div class="card-body">
            <div class="lw-section-title mt-0">Where landlords found nobody (last 90 days)</div>
            @if($gaps->isEmpty())
                <x-lw.empty title="No gaps">Every search found at least one contractor.</x-lw.empty>
            @else
                <table class="table lw-table align-middle mb-0">
                    <thead><tr><th>Postcode district</th><th>Trade</th><th>Searches</th><th>Last</th></tr></thead>
                    <tbody>
                        @foreach($gaps as $gap)
                            <tr>
                                <td>{{ $gap->postcode_district ?: '—' }}</td>
                                <td>{{ $trades[$gap->trade] ?? ($gap->trade ?: 'Any') }}</td>
                                <td>{{ $gap->total }}</td>
                                <td class="small text-muted">{{ \Illuminate\Support\Carbon::parse($gap->last_seen)->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
