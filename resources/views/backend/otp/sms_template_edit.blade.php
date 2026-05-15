@extends('backend.layout.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">Edit SMS Template</h1></div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('sms-templates.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>
</div>

<div class="card col-lg-8 mx-auto">
    <div class="card-header">
        <h5 class="mb-0 h6">
            Template: <code>{{ $template->identifier }}</code>
        </h5>
    </div>
    <form action="{{ route('sms-templates.update', $template->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-group">
                <label class="fw-semibold">SMS Body</label>
                <textarea name="sms_body" class="form-control" rows="4" required>{{ $template->sms_body }}</textarea>
                <small class="text-muted">
                    Available placeholders:
                    @if($template->identifier === 'phone_number_verification')
                        <code>[[code]]</code>, <code>[[site_name]]</code>
                    @elseif($template->identifier === 'password_reset')
                        <code>[[code]]</code>
                    @elseif($template->identifier === 'account_opening')
                        <code>[[site_name]]</code>, <code>[[code]]</code>, <code>[[password]]</code>
                    @endif
                </small>
            </div>

            <div class="form-group">
                <label class="fw-semibold">DLT Template ID <span class="text-muted">(India only)</span></label>
                <input type="text" name="template_id" class="form-control"
                       value="{{ $template->template_id }}" placeholder="Leave blank if not using DLT route">
            </div>

            <div class="form-group">
                <label class="fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="1" {{ $template->status ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ !$template->status ? 'selected' : '' }}>Disabled</option>
                </select>
            </div>
        </div>
        <div class="card-footer text-right">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Save Template
            </button>
        </div>
    </form>
</div>
@endsection
