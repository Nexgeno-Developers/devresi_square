@extends('backend.layout.app')

@section('content')
<div class="container-fluid lw-page" data-certificate-gaps="{{ count($rows) }}">
    <div class="lw-hero d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <h4>Compliance</h4>
            <p>Keep requirements, certificates, inspections and important dates visible.</p>
        </div>
        <a href="{{ route('admin.properties.index') }}" class="btn lw-btn-secondary">Properties</a>
    </div>

    @php
        $complianceRows = collect($rows)->map(function ($row) {
            $row['state'] = \App\Support\CrmLanguage::complianceState($row['state'] ?? '');
            return $row;
        });
        $slotTotal = max(0, (int) $propertyCount * (int) $typeCount);
        $complianceCounts = [
            'Current' => max(0, $slotTotal - $complianceRows->count()),
            'Due Soon' => $complianceRows->where('state', 'Due Soon')->count(),
            'Expired' => $complianceRows->where('state', 'Expired')->count(),
            'Missing' => $complianceRows->where('state', 'Missing')->count(),
        ];
    @endphp
    <div class="d-flex gap-2 flex-wrap mb-3">
        @foreach($complianceCounts as $label => $count)
            <span class="badge bg-light text-dark border">{{ $count }} {{ $label }}</span>
        @endforeach
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
            @elseif($complianceRows->count() === 0)
                <x-lw.empty title="Everything Is Up to Date">
                    There are no compliance items currently requiring attention.
                </x-lw.empty>
            @else
                <table class="table lw-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Compliance Item</th>
                            <th>Status</th>
                            <th>Issue Date</th>
                            <th>Expiry / Review Date</th>
                            <th>Document</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($complianceRows as $row)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.properties.index', ['property_id' => $row['property']->id, 'tabname' => 'Property']) }}">
                                        {{ rs_property_title($row['property']) }}
                                    </a>
                                </td>
                                <td>{{ $row['type']->name }}</td>
                                <td>{{ $row['state'] }}</td>
                                <td>{{ $row['record']?->issued_date ? rs_date($row['record']->issued_date) : '—' }}</td>
                                <td>{{ $row['record']?->expiry_date ? rs_date($row['record']->expiry_date) : '—' }}</td>
                                <td>{{ $row['record']?->hasFile() ? 'On file' : '—' }}</td>
                                <td>
                                    @if($row['record']?->served_to_tenant_at)
                                        <div class="small text-muted mb-1">Served {{ rs_date($row['record']->served_to_tenant_at) }}</div>
                                    @endif
                                    <form method="POST" action="{{ route('admin.compliance.share') }}" enctype="multipart/form-data" class="d-flex flex-column gap-1">
                                        @csrf
                                        <input type="hidden" name="property_id" value="{{ $row['property']->id }}">
                                        <input type="hidden" name="compliance_type_id" value="{{ $row['type']->id }}">
                                        <input type="file" name="certificate" accept="application/pdf,image/*" required class="form-control form-control-sm"
                                            oninvalid="this.setCustomValidity('Choose the certificate file before sharing it.')"
                                            onchange="this.setCustomValidity('')">
                                        <button type="submit" class="btn btn-sm lw-btn-primary">Upload and share</button>
                                        <span class="small text-muted">Choose the PDF or photo first.</span>
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
