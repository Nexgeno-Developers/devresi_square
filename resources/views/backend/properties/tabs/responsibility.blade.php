@php
    $responsibilityLabels = [
        'property_manager' => 'Property Manager',
        'sales_consultant' => 'Sales Consultant',
        'lettings_consultant' => 'Lettings Consultant',
        'sales_manager' => 'Sales Manager',
        'lettings_manager' => 'Lettings Manager',
    ];
@endphp

<div class="property-tab-section" id="section-responsibility-{{ $propertyId }}">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-1">Responsibility</h4>
            <p class="text-muted mb-0">Staff/user responsibility mapping attached to this property.</p>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary editForm" data-form="responsibility" data-id="{{ $propertyId }}">
            Edit Mapping
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Responsibility Type</th>
                    <th>Staff/User ID</th>
                    <th>Staff/User</th>
                </tr>
            </thead>
            <tbody>
                @forelse($responsibilities as $responsibility)
                    <tr>
                        <td>{{ $responsibilityLabels[$responsibility->responsibility_type] ?? 'N/A' }}</td>
                        <td>{{ $responsibility->user_id }}</td>
                        <td>{{ $responsibility->user?->name ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-muted text-center py-4">No responsibility mappings added.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
