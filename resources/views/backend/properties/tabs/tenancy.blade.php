
{{-- Hidden div for property ID --}}
<div id="hidden-property-id" class="d-none" data-property-id="{{ $propertyId }}">
    @php
        // Debugging the propertyId
        echo '<pre>';
        var_dump($propertyId);
        echo '</pre>';
    @endphp
</div>
<div class="tab-content">
    @if($tenancies)
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Status</th>
                <th>Sub Status</th>
                <th>Rent</th>
                {{-- <th>Frequency</th> --}}
                <th>Deposit</th>
                <th>Move In</th>
                <th>Move Out</th>
                <th>Tenancy Length</th>
                <th>Extension Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tenancies as $tenancy)
                <tr>
                    <td>{{ $tenancy->id }}</td>
                    <td>{{ $tenancy->status }}</td>
                    <td>{{ $tenancy->sub_status }}</td>
                    <td>{{ $tenancy->rent }}</td>
                    {{-- <td>{{ $tenancy->frequency }}</td> --}}
                    <td>{{ $tenancy->deposit }}</td>
                    <td>{{ $tenancy->move_in }}</td>
                    <td>{{ $tenancy->move_out }}</td>
                    <td>{{ $tenancy->tenancy_renewal_confirm_date }}</td>
                    <td>{{ $tenancy->extension_date }}</td>
                    <!-- Action -->
                    <td>
                        <button data-url="{{ route('admin.tenancies.edit', $tenancy->id) }}" class="popup-tab-tenancy-edit btn btn-sm btn-warning" >Edit</button>
                        <button class="btn btn-sm btn-danger action-icon" onclick="confirmModal('{{ route('admin.tenancies.delete', $tenancy->id) }}', responseHandler)">Delete</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p>No active tenancies found for this property.</p>
    @endif
</div>
