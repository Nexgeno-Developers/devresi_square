{{-- @props(['repair'])

<div class="card mb-3 repair-card shadow-sm" 
     data-url="{{ route('admin.property_repairs.show', $repair->id) }}" 
     data-id="{{ $repair->id }}" 
     onclick="loadRepairDetailByUrl(this)" 
     style="cursor: pointer;">

    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <h5 class="card-title mb-1">
                    <span class="text-primary">#{{ $repair->id }}</span> - {{ getRepairCategoryDetails($repair->repair_category_id) }}
                </h5>
                <p class="mb-1"><strong>Property:</strong> {{ getPropertyDetails($repair->property_id, ['prop_name', 'line_1', 'city']) }}</p>
                <p class="mb-1"><strong>Description:</strong> {{ Str::limit($repair->description, 100) }}</p>
                <p class="text-muted mb-0"><small>Created: {{ $repair->created_at->format('d M, Y') }}</small></p>
            </div>
            <div>
                <span class="badge bg-info">{{ $repair->status }}</span>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('admin.property_repairs.show', $repair->id) }}" class="btn btn-sm btn-outline-info">View</a>
            <a href="{{ route('admin.property_repairs.edit', $repair->id) }}" class="btn btn-sm btn-outline-warning">Edit</a>
            <form action="{{ route('admin.property_repairs.delete', $repair->id) }}" method="POST" class="d-inline delete-form">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
        </div>
    </div>
</div> --}}

@props(['repair', 'selectedRepairId' => null])

{{-- @php
    $selectedRepairId = $selectedRepairId ?? null;
    $isSelected = ($repair->id == $selectedRepairId);
@endphp --}}

@php
    $isSelected = isset($selectedRepairId) && $repair->id == $selectedRepairId;
@endphp

<tr class="align-middle repair-row {{ $isSelected ? 'selected' : '' }}" data-url="{{ route('admin.property_repairs.show', $repair->id) }}" onclick="loadRepairDetailByUrl(this)" style="cursor:pointer;">
    <!-- Property -->
    <td>{{ getPropertyDetails($repair->property_id, ['prop_name', 'line_1', 'city', 'country']) }}</td>

    <!-- Issue in -->
    <td>{{ getRepairCategoryDetails($repair->repair_category_id) }}</td>

    <!-- Status -->
    <td>
        @php
            $status = strtolower($repair->status);
            $badgeClass = match($status) {
                'under process' => 'warning',
                'completed' => 'success',
                'pending' => 'info',
                default => 'secondary',
            };
        @endphp
        <span class="badge bg-{{ $badgeClass }} text-capitalize">{{ $repair->status }}</span>
    </td>

    <!-- Posted on -->
    <td>{{ $repair->created_at->format('d, M Y') }}</td>

    <!-- Actions -->
    <td class="text-end">
        <div class="dropdown">
            <button class="btn btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-cog"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="{{ route('admin.property_repairs.show', $repair->id) }}">View</a></li>
                <li><a class="dropdown-item" href="{{ route('admin.property_repairs.edit', $repair->id) }}">Edit</a></li>
                <li>
                    <form action="{{ route('admin.property_repairs.delete', $repair->id) }}" method="POST" class="d-inline delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger">Delete</button>
                    </form>
                </li>
            </ul>
        </div>
    </td>
</tr>

