{{-- Hidden div for property ID --}}
<div id="hidden-property-id" class="d-none" data-property-id="{{ $propertyId }}"></div>

<div class="tab-content">

    {{-- Filter by status --}}
    <div class="mb-3">
        <label for="statusFilter">Filter by Status</label>
        <select id="statusFilter" class="form-control">
            <option value="">All</option>
            @foreach($statuses as $status)
                <option value="{{ $status }}">{{ $status }}</option>
            @endforeach
        </select>
    </div>

    {{-- Display Tenancies --}}
    <div id="tenanciesTable">
        @foreach($statuses as $status)
            <div class="status-section" id="status-{{ strtolower($status) }}">
                <h4>{{ $status }} Tenancies</h4>
                @php
                    $filteredTenancies = $tenancies->where('status', $status);
                @endphp
                @if($filteredTenancies->isNotEmpty())
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Status</th>
                                <th>Sub Status</th>
                                <th>Rent</th>
                                <th>Deposit</th>
                                <th>Move In</th>
                                <th>Move Out</th>
                                <th>Tenancy Length</th>
                                <th>Extension Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($filteredTenancies as $tenancy)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $tenancy->status }}</td>
                                    <td>{{ $tenancy->tenancySubStatus->name ?? $tenancy->sub_status ?? '-' }}</td>
                                    <td class="text-end">{{ number_format((float) $tenancy->rent, 2) }}</td>
                                    <td>{{ $tenancy->deposit }}</td>
                                    <td>{{ $tenancy->move_in }}</td>
                                    <td>{{ $tenancy->move_out }}</td>
                                    <td>{{ $tenancy->tenancy_renewal_confirm_date }}</td>
                                    <td>{{ $tenancy->extension_date }}</td>
                                    <td>
                                        <div class="d-flex justify-content-end">
                                            <a href="{{ route('admin.tenancies.rent-ledger', $tenancy->id) }}" class="btn btn-sm btn-outline-primary me-1" title="Rent Ledger">
                                                Rent Ledger
                                            </a>
                                            <button data-url="{{ route('admin.tenancies.show', $tenancy->id) }}" class="popup-tab-tenancy-view btn btn-sm btn-outline-info me-1" title="View Tenancy">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            @unless(auth()->user()->hasRole('Tenant'))
                                            <button data-url="{{ route('admin.tenancies.edit', $tenancy->id) }}" class="popup-tab-tenancy-edit btn btn-sm btn-outline-warning me-1" title="Edit Tenancy">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger me-1 action-icon" title="Delete Tenancy"
                                                onclick="deleteTenancy('{{ route('admin.tenancies.delete', $tenancy->id) }}', this)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p>No {{ $status }} tenancies found.</p>
                    {{-- <p>No tenancies found for {{ $status }} status.</p> --}}
                @endif
            </div>
        @endforeach
    </div>

</div>

{{-- JavaScript for filtering --}}
<script>
    document.getElementById('statusFilter').addEventListener('change', function() {
        const selectedStatus = this.value.toLowerCase();
        const sections = document.querySelectorAll('.status-section');

        sections.forEach(function(section) {
            if (selectedStatus === '' || section.id === 'status-' + selectedStatus) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });
    });
</script>
