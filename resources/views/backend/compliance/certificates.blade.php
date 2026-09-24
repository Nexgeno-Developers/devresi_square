@extends('backend.layout.app')

@section('content')
<div class="container-fluid lw-page" data-certificate-gaps="{{ count($rows) }}">
    <div class="lw-hero d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <h4>Certificates</h4>
            <p>Gas safety, EPC and EICR. Missing, expired, and due within 60 days are listed here.</p>
        </div>
        <a href="{{ route('admin.properties.index') }}" class="btn lw-btn-secondary">Properties</a>
    </div>

    <div class="card lw-card">
        <div class="card-body">
            @if($typeCount === 0)
                <x-lw.empty title="Certificate types are not set up">
                    Gas, EPC and EICR types need to exist before this list can show gaps.
                </x-lw.empty>
            @elseif($propertyCount === 0)
                <x-lw.empty title="No properties yet">
                    Add a home before you can track gas, EPC and EICR.
                    <x-slot:actions>
                        <a href="{{ route('admin.properties.index', ['add_property' => 1]) }}" class="btn lw-btn-primary">Add property</a>
                    </x-slot:actions>
                </x-lw.empty>
            @elseif(count($rows) === 0)
                <x-lw.empty title="Certificates are up to date">
                    Every home has gas, EPC and EICR on file, and none are due within 60 days.
                </x-lw.empty>
            @else
                <table class="table lw-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Certificate</th>
                            <th>Status</th>
                            <th>Expiry</th>
                            <th>Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.properties.index', ['property_id' => $row['property']->id, 'tabname' => 'Property']) }}">
                                        {{ rs_property_title($row['property']) }}
                                    </a>
                                </td>
                                <td>{{ $row['type']->name }}</td>
                                <td>{{ $row['state'] }}</td>
                                <td>{{ $row['record']?->expiry_date ? rs_date($row['record']->expiry_date) : 'Not on file' }}</td>
                                <td>
                                    @if($row['record']?->served_to_tenant_at)
                                        <div class="small text-muted mb-1">Served {{ rs_date($row['record']->served_to_tenant_at) }}</div>
                                    @endif
                                    <form method="POST" action="{{ route('admin.compliance.share') }}" enctype="multipart/form-data" class="d-flex flex-column gap-1">
                                        @csrf
                                        <input type="hidden" name="property_id" value="{{ $row['property']->id }}">
                                        <input type="hidden" name="compliance_type_id" value="{{ $row['type']->id }}">
                                        <input type="file" name="certificate" accept="application/pdf,image/*" required class="form-control form-control-sm">
                                        <button type="submit" class="btn btn-sm lw-btn-primary">Share with tenant</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
