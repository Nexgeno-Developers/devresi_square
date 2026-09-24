@if($repairIssue->isPriorityComplaint())
    @php
        $snapshot = is_array($repairIssue->classification_snapshot) ? $repairIssue->classification_snapshot : [];
        $formatUk = function ($value) {
            return $value ? $value->timezone('Europe/London')->format('j M Y, H:i').' UK' : 'Not recorded';
        };
        $actions = [
            'dispatched' => ['label' => 'Dispatch trade', 'done' => (bool) $repairIssue->dispatched_at],
            'made_safe' => ['label' => 'Mark made safe', 'done' => (bool) $repairIssue->make_safe_at],
            'resolved' => ['label' => 'Mark resolved', 'done' => (bool) $repairIssue->resolved_at],
        ];
    @endphp
    <div class="card lw-card mb-3">
        <div class="card-body">
            <div class="lw-section-title mt-0">Priority complaint</div>
            <p class="mb-1"><strong>{{ $snapshot['title'] ?? 'Priority repair' }}</strong></p>
            <p class="mb-2">{{ $snapshot['tenant_phrase'] ?? '' }}</p>
            <p class="mb-3 small text-muted">
                {{ ! empty($snapshot['emergency_access'])
                    ? 'Emergency entry is allowed without 24 hours\' written notice.'
                    : '24 hours\' written notice applies before entry.' }}
            </p>
            <dl class="row small mb-3">
                <dt class="col-sm-4">Reported</dt>
                <dd class="col-sm-8">{{ $formatUk($repairIssue->reported_at ?: $repairIssue->created_at) }}</dd>
                <dt class="col-sm-4">Dispatched</dt>
                <dd class="col-sm-8">{{ $formatUk($repairIssue->dispatched_at) }}</dd>
                <dt class="col-sm-4">Made safe</dt>
                <dd class="col-sm-8">{{ $formatUk($repairIssue->make_safe_at) }}</dd>
                <dt class="col-sm-4">Resolved</dt>
                <dd class="col-sm-8">{{ $formatUk($repairIssue->resolved_at) }}</dd>
            </dl>
            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach($actions as $event => $action)
                    @unless($action['done'])
                        <form method="POST" action="{{ route('admin.property_repairs.sla.store', [$repairIssue, $event]) }}">
                            @csrf
                            <button type="submit" class="btn lw-btn-secondary btn-sm">{{ $action['label'] }}</button>
                        </form>
                    @endunless
                @endforeach
            </div>
            @if($repairIssue->slaEvents->isNotEmpty())
                <ul class="list-unstyled mb-0 small">
                    @foreach($repairIssue->slaEvents as $slaEvent)
                        <li class="mb-1">
                            <strong>{{ str_replace('_', ' ', $slaEvent->event) }}</strong>
                            · {{ $formatUk($slaEvent->occurred_at) }}
                            @if($slaEvent->note)
                                · {{ $slaEvent->note }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endif
