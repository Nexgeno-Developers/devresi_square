@extends('backend.layout.app')

@section('content')
@php $reported = $report->reportedParty(); @endphp
<div class="container-fluid py-3" style="max-width: 960px">
    <a href="{{ route('backend.saas.message-reports.index') }}" class="small">&larr; All reports</a>
    <h1 class="h4 mt-2">Report: {{ ucfirst($report->category) }}</h1>
    @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif

    <dl class="row small">
        <dt class="col-sm-3">About</dt><dd class="col-sm-9">{{ $reported?->displayName() ?? '—' }} ({{ $reported?->roleLabel() }})</dd>
        <dt class="col-sm-3">Reported by</dt><dd class="col-sm-9">{{ $report->reporter?->name ?? 'Deleted user' }} for {{ $report->reporterParty()->displayName() }}</dd>
        <dt class="col-sm-3">When</dt><dd class="col-sm-9">{{ $report->created_at->format('j M Y H:i') }}</dd>
        <dt class="col-sm-3">Note</dt><dd class="col-sm-9">{{ $report->note ?: '—' }}</dd>
        <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ ucfirst($report->status) }} @if($report->outcome) ({{ $report->outcome }}) @endif</dd>
    </dl>

    @if($report->request)
        <div class="card mb-3"><div class="card-body">
            <div class="text-muted small mb-1">Reported request note</div>
            {!! nl2br(e($report->request->intro ?: '(no note)')) !!}
        </div></div>
    @endif

    @if($context->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header small">Reported message and up to {{ config('messaging.moderation_context') }} messages either side</div>
            <ul class="list-group list-group-flush">
                @foreach($context as $m)
                    <li class="list-group-item small {{ (int) $m->id === (int) $report->message_id ? 'list-group-item-warning' : '' }}">
                        <div class="text-muted">{{ $m->senderParty()->displayName() }} · {{ $m->created_at->format('j M Y H:i') }}
                            @if($m->trashed()) · taken back @endif @if($m->isRemoved()) · removed @endif</div>
                        <div>{!! nl2br(e($m->body ?? '')) !!}</div>
                        @if($m->attachments->isNotEmpty())<div class="text-muted">{{ $m->attachments->count() }} file(s): {{ $m->attachments->pluck('original_name')->implode(', ') }}</div>@endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('backend.saas.message-reports.resolve', $report) }}" class="card card-body">
        @csrf
        <div class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label small" for="mod-action">Action</label>
                <select class="form-select form-select-sm" id="mod-action" name="action" required>
                    <option value="dismiss">Dismiss (no action)</option>
                    <option value="warn">Warn the sender</option>
                    @if($report->message_id)<option value="remove">Remove the message</option>@endif
                    <option value="suspend">Suspend their messaging</option>
                    <option value="lift">Lift a pause or suspension</option>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label small" for="mod-days">Days (suspend)</label>
                <input class="form-control form-control-sm" id="mod-days" type="number" min="1" max="3650" name="days" placeholder="Until reviewed">
            </div>
            <div class="col-sm-6">
                <label class="form-label small" for="mod-note">Note (shown to the sender when warning)</label>
                <input class="form-control form-control-sm" id="mod-note" name="note" maxlength="1000">
            </div>
        </div>
        <div class="mt-2"><button class="btn btn-sm btn-primary" type="submit">Save</button></div>
    </form>
</div>
@endsection
