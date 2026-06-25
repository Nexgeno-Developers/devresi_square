<div id="tabbed-repair-list">
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>Property</th>
                <th>Issue in</th>
                <th>Status</th>
                <th>Posted on</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($repairIssues as $item)
                @php
                    $isSelected = isset($selectedRepairId) && (int) $item->id === (int) $selectedRepairId;
                    $status = strtolower($item->status);
                    $badgeClass = match($status) {
                        'under process' => 'warning',
                        'completed', 'work completed' => 'success',
                        'pending' => 'info',
                        default => 'secondary',
                    };
                @endphp
                <tr class="align-middle repair-row {{ $isSelected ? 'selected' : '' }}"
                    data-repair-id="{{ $item->id }}"
                    style="cursor:pointer;">
                    <td>{{ getPropertyDetails($item->property_id, ['prop_name', 'line_1', 'city', 'country']) }}</td>
                    <td>{{ getRepairCategoryDetails($item->repair_category_id) }}</td>
                    <td>
                        <span class="badge bg-{{ $badgeClass }} text-capitalize">{{ $item->status }}</span>
                    </td>
                    <td>{{ $item->created_at->format('d, M Y') }}</td>
                    <td class="text-end">
                        <div class="dropdown">
                            <button class="btn btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-cog"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('admin.property_repairs.show', $item->id) }}">View</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.property_repairs.edit', $item->id) }}">Edit</a></li>
                                <li>
                                    <form action="{{ route('admin.property_repairs.delete', $item->id) }}" method="POST" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">Delete</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">No repair issues found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-center mt-3">
        {{ $repairIssues->appends(request()->except('list_only'))->links() }}
    </div>
</div>
