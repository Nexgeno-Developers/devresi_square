@extends('backend.layout.app')

@section('content')
<div class="container-fluid lw-page" style="max-width: 980px;">
    @include('backend.contractors._nav')

    <form method="POST" action="{{ route('admin.registrations.contractors.settings.save') }}">
        @csrf
        <div class="card lw-card mb-3">
            <div class="card-body">
                <div class="lw-section-title mt-0">Insurance and review</div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Minimum public liability £</label>
                        <input type="number" name="public_liability_minimum" class="form-control form-control-sm" value="{{ old('public_liability_minimum', $values['public_liability_minimum']) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Minimum employers’ liability £</label>
                        <input type="number" name="employers_liability_minimum" class="form-control form-control-sm" value="{{ old('employers_liability_minimum', $values['employers_liability_minimum']) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Review SLA (working days)</label>
                        <input type="number" name="review_sla_working_days" class="form-control form-control-sm" value="{{ old('review_sla_working_days', $values['review_sla_working_days']) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Expiry reminders (days before)</label>
                        <input name="reminder_days" class="form-control form-control-sm" value="{{ old('reminder_days', implode(', ', (array) $values['reminder_days'])) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Delete files of rejected/removed after (days)</label>
                        <input type="number" name="document_retention_days" class="form-control form-control-sm" value="{{ old('document_retention_days', $values['document_retention_days']) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card lw-card mb-3">
            <div class="card-body">
                <div class="lw-section-title mt-0">Sending jobs</div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Max contractors per send</label>
                        <input type="number" name="max_contractors_per_send" class="form-control form-control-sm" value="{{ old('max_contractors_per_send', $values['max_contractors_per_send']) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Offer digest every (hours)</label>
                        <input type="number" name="digest_hours" class="form-control form-control-sm" value="{{ old('digest_hours', $values['digest_hours']) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Contractor invoices due after (days)</label>
                        <input type="number" name="invoice_due_days" class="form-control form-control-sm" value="{{ old('invoice_due_days', $values['invoice_due_days']) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-check small">
                            <input type="hidden" name="emergency_sms" value="0">
                            <input type="checkbox" class="form-check-input" name="emergency_sms" value="1" @checked(old('emergency_sms', $values['emergency_sms']))>
                            Text contractors about emergency jobs, and text the landlord when the first emergency offer arrives
                        </label>
                    </div>
                </div>
                <table class="table table-sm mt-3 mb-0" style="max-width:520px">
                    <thead><tr><th></th><th>Reply window (h)</th><th>Offer valid (h)</th></tr></thead>
                    <tbody>
                        @foreach(\App\Models\RepairContractorRequest::URGENCIES as $key => $label)
                            <tr>
                                <td>{{ $label }}</td>
                                <td><input type="number" name="invite_hours[{{ $key }}]" class="form-control form-control-sm" value="{{ old('invite_hours.'.$key, $values['invite_hours'][$key] ?? '') }}"></td>
                                <td><input type="number" name="offer_valid_hours[{{ $key }}]" class="form-control form-control-sm" value="{{ old('offer_valid_hours.'.$key, $values['offer_valid_hours'][$key] ?? '') }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card lw-card mb-3">
            <div class="card-body">
                <div class="lw-section-title mt-0">Documents required per trade</div>
                <p class="small text-muted">Photo ID and public liability are always required. Gas must keep Gas Safe.</p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Trade</th>@foreach($documentTypes as $label)<th class="small">{{ $label }}</th>@endforeach</tr></thead>
                        <tbody>
                            @foreach($trades as $trade => $tradeLabel)
                                <tr>
                                    <td>{{ $tradeLabel }}</td>
                                    @foreach($documentTypes as $type => $label)
                                        <td><input type="checkbox" name="trade_documents[{{ $trade }}][]" value="{{ $type }}" @checked(in_array($type, $values['trade_documents'][$trade] ?? [], true))></td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <button class="btn btn-primary mb-4" type="submit">Save settings</button>
    </form>
</div>
@endsection
