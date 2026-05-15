@extends('backend.layout.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6"><h1 class="h3">SMS Templates</h1></div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('otp.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> OTP Config
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Identifier</th>
                    <th>Template Body</th>
                    <th>DLT Template ID</th>
                    <th>Status</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($templates as $i => $t)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><code>{{ $t->identifier }}</code></td>
                        <td class="small text-muted" style="max-width:300px;">{{ $t->sms_body }}</td>
                        <td>{{ $t->template_id ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $t->status ? 'success' : 'secondary' }}">
                                {{ $t->status ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('sms-templates.edit', $t->id) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
