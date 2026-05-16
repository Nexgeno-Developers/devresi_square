<div id="card-list">
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>Property</th>
                <th>Issue in</th>
                <th>Status</th>
                <th>Posted on</th>
            </tr>
        </thead>
        <tbody>
            @forelse($repairIssues as $item)
                @php
                    $isSelected = isset($selectedRepairId) && $item->id == $selectedRepairId;
                    $status = strtolower($item->status);
                    $badgeClass = match($status) {
                        'under process' => 'warning',
                        'work completed' => 'success',
                        'pending'        => 'info',
                        default          => 'secondary',
                    };
                @endphp
                <tr class="align-middle repair-row {{ $isSelected ? 'selected' : '' }}"
                    data-url="{{ route('contractor.repairs.show', $item->id) }}"
                    onclick="loadRepairDetailByUrl(this)"
                    style="cursor:pointer;">
                    <td>{{ getPropertyDetails($item->property_id, ['prop_name','line_1','city','country']) }}</td>
                    <td>{{ getRepairCategoryDetails($item->repair_category_id) }}</td>
                    <td><span class="badge bg-{{ $badgeClass }} text-capitalize">{{ $item->status }}</span></td>
                    <td>{{ $item->created_at->format('d, M Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        No repair assignments found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-center mt-3">
        {{ $repairIssues->appends(request()->query())->links() }}
    </div>
</div>
