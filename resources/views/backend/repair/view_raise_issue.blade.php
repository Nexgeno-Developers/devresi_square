@extends('backend.layout.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-primary">Repair Issue Details</h1>
        <a href="{{ route('admin.property_repairs.edit', $repairIssue->id) }}" class="btn btn-warning">
            <i class="fas fa-edit"></i> Edit
        </a>
    </div>

    <!-- Property Details Card -->
    <div class="card mb-3">
        <div class="card-header bg-info text-white">
            Property Details
        </div>
        <div class="card-body">
            @if($repairIssue->property)
                <p><strong>Property:</strong> {{ $repairIssue->property->prop_name ?? 'N/A' }}</p>
                <p><strong>Address:</strong>
                    {{ $repairIssue->property->line_1 ?? '' }} {{ $repairIssue->property->line_2 ?? '' }},
                    {{ $repairIssue->property->city ?? '' }},
                    {{ $repairIssue->property->postcode ?? '' }}
                </p>
                <p><strong>Type:</strong> {{ $repairIssue->property->specific_property_type ?? 'N/A' }}</p>
                <p><strong>Availability:</strong> {{ $repairIssue->property->availability ?? 'N/A' }}</p>
            @else
                <p>No property selected.</p>
            @endif
        </div>
    </div>

    <!-- Category Details Card -->
    <div class="card mb-3">
        <div class="card-header bg-secondary text-white">
            Category
        </div>
        <div class="card-body">
            <p><strong>Category:</strong> {{ getRepairCategoryDetails($repairIssue->repair_category_id) }}</p>
            <p><strong>Navigation:</strong> {!! getFormattedRepairNavigation($repairIssue->repair_navigation) !!}</p>
        </div>
    </div>

    <!-- Repair Details Card -->
    <div class="card mb-3">
        <div class="card-header bg-dark text-white">
            Repair Details
        </div>
        <div class="card-body">
            <p><strong>Description:</strong></p>
            <p>{{ $repairIssue->description }}</p>
            <p><strong>Priority:</strong> {{ ucfirst($repairIssue->priority) }}</p>
            <p><strong>Status:</strong> {{ $repairIssue->status }}</p>
            <p><strong>Estimated Price:</strong> {{ $repairIssue->estimated_price }}</p>
            <p><strong>VAT Type:</strong> {{ ucfirst($repairIssue->vat_type) }}</p>
            @if($repairIssue->tenant_availability)
                <p><strong>Tenant Availability:</strong> {{ \Carbon\Carbon::parse($repairIssue->tenant_availability)->format('d M Y, H:i') }}</p>
            @endif
            @if($repairIssue->access_details)
                <p><strong>Access Details:</strong> {{ $repairIssue->access_details }}</p>
            @endif
        </div>
    </div>

    <!-- Repair Photos Card -->
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            Photos
        </div>
        <div class="card-body">
            @if($repairIssue->repairPhotos->count())
                <div class="row">
                    @foreach($repairIssue->repairPhotos as $photo)
                        @php
                            // Explode the photos field by comma. It may be a single value or multiple IDs.
                            $photoIds = explode(',', $photo->photos);
                        @endphp
                        @foreach($photoIds as $photoId)
                            @php $photoId = trim($photoId); @endphp
                            @if($photoId)
                                <div class="col-md-3 mb-2">
                                    <a title="view image" href="{{ uploaded_asset($photoId) }}" target="_blank">
                                        <img src="{{ uploaded_asset($photoId) }}" class="img-fluid" alt="Repair Photo">
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    @endforeach
                </div>
            @else
                <p>No photos available.</p>
            @endif
        </div>
    </div>

    <!-- Property Manager Assignments Card -->
    <div class="card mb-3">
        <div class="card-header bg-warning text-dark">
            Property Manager Assignments
        </div>
        <div class="card-body">
            @if($repairIssue->repairIssuePropertyManagers->count())
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Manager</th>
                            <th>Assigned At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($repairIssue->repairIssuePropertyManagers as $index => $assignment)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    {{ $assignment->propertyManager->name ?? 'N/A' }}
                                    ({{ $assignment->propertyManager->email ?? 'N/A' }})
                                </td>
                                <td>{{ \Carbon\Carbon::parse($assignment->assigned_at)->format('d M Y, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>No property manager assignments available.</p>
            @endif
        </div>
    </div>

    <!-- Contractor Assignments Card -->
    <div class="card mb-3">
        <div class="card-header bg-warning text-dark">
            Contractor Assignments
        </div>
        <div class="card-body">
            @if($repairIssue->repairIssueContractorAssignments->count())
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Contractor</th>
                            <th>Cost Price</th>
                            <th>Preferred Availability</th>
                            <th>Status</th>
                            <th>Quote Document</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($repairIssue->repairIssueContractorAssignments as $index => $assignment)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $assignment->contractor->name ?? 'N/A' }}</td>
                                <td>{{ $assignment->cost_price }}</td>
                                <td>{{ $assignment->contractor_preferred_availability }}</td>
                                <td>{{ $assignment->status }}</td>
                                <td>
                                    @if($assignment->quote_attachment)
                                        <a href="{{ uploaded_asset($assignment->quote_attachment) }}" target="_blank">View File</a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>No contractor assignments available.</p>
            @endif
        </div>
    </div>

    <!-- Final Contractor Detail Card -->
    <div class="card mb-3">
        <div class="card-header bg-success text-white">
            Final Contractor Detail
        </div>
        <div class="card-body">
            @if ($repairIssue->final_contractor_id && $repairIssue->finalContractor)
                <p><strong>Name:</strong> {{ $repairIssue->finalContractor->name }}</p>
                <p><strong>Email:</strong> {{ $repairIssue->finalContractor->email }}</p>
                <p><strong>Phone:</strong> {{ $repairIssue->finalContractor->phone }}</p>
            @else
                <p>No final contractor selected.</p>
            @endif
        </div>
    </div>

    <!-- Repair History Card -->
    <div class="card mb-3">
        <div class="card-header bg-dark text-white">
            Repair History
        </div>
        <div class="card-body">
            @if($repairIssue->repairHistories->count())
                <ul class="list-group">
                    @foreach($repairIssue->repairHistories as $history)
                        <li class="list-group-item">
                            <strong>{{ $history->action }}:</strong>
                            Changed from <em>{{ $history->previous_status }}</em> to <em>{{ $history->new_status }}</em>
                            <br>
                            <small>{{ \Carbon\Carbon::parse($history->created_at)->format('d M Y, H:i') }}</small>
                        </li>
                    @endforeach
                </ul>
            @else
                <p>No history recorded.</p>
            @endif
        </div>
    </div>

    <a href="{{ route('admin.property_repairs.index') }}" class="btn btn-secondary">Back to List</a>
</div>
@endsection
