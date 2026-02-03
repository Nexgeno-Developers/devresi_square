@extends('backend.layout.app')

@section('content')
<div class="container">
    <h3>Deleted Properties</h3>
    
    <!-- Bulk Restore Button -->
    <button type="button" id="bulkRestoreButton" class="float-end btn btn-primary">Restore Selected</button>
    <!-- Bulk restore form -->
    <form id="bulkRestoreForm" method="POST" action="{{ route('admin.properties.bulk-restore') }}">
        @csrf
        <input type="hidden" name="property_ids" id="bulkPropertyIds" value="">
    </form>

    <!-- DataTable -->
    <table id="softDeletedPropertiesTable" class="table table-striped">
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAll"></th>
                <th>Property Name</th>
                <th>City</th>
                <th>Price</th>
                <th>Deleted At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($properties as $property)
                <tr data-id="{{ $property->id }}">
                    <td><input type="checkbox" class="propertyCheckbox" value="{{ $property->id }}"></td>
                    <td>{{ $property->prop_name }}</td>
                    <td>{{ $property->city }}</td>
                    <td>{{ $property->price }}</td>
                    <td>{{ $property->deleted_at->format('Y-m-d') }}</td>
                    <td>
                        <!-- Individual restore button -->
                        <form method="POST" action="{{ route('admin.properties.restore', $property->id) }}"
                            style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">Restore</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>

@endsection

@section('page.scripts')
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function () {
            // Initialize DataTable
            $('#softDeletedPropertiesTable').DataTable();

            // Select/Deselect All Checkboxes
            $('#selectAll').click(function () {
                var isChecked = $(this).prop('checked');
                $('.propertyCheckbox').prop('checked', isChecked);
            });

            // Bulk Restore Button Click
            $('#bulkRestoreButton').click(function () {
                var selectedIds = [];
                $('.propertyCheckbox:checked').each(function () {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length > 0) {
                    // Update the bulkRestoreForm input with selected property ids
                    $('#bulkPropertyIds').val(selectedIds.join(','));

                    // Submit the form
                    $('#bulkRestoreForm').submit();
                } else {
                    alert('Please select at least one property to restore.');
                }
            });
        });
    </script>
@endsection