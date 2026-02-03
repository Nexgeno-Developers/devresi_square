@extends('backend.layout.app')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h1>Properties</h1>
    <div>
        <a id="quick-add-btn" href="{{ route('admin.properties.quick') }}" class="btn btn-primary">Add Property</a>
        <!-- <a id="quick-add-btn" href="{{ route('admin.properties.quick') }}" class="btn btn-primary">Quick Add Property</a> -->
        <!-- <a id="quick-add-btn" href="{{ route('admin.properties.create') }}" class="btn btn-primary">Quick Add Property</a> -->
        <!-- <a href="{{ route('admin.properties.create') }}?stepform" class="btn btn-secondary">Add Property</a> -->
    </div>
</div>

<!-- Properties DataTable -->
<table id="properties-table" class="table table-striped">
    <thead>
        <tr>
            <th>Property Name</th>
            <th>City</th>
            <th>Country</th>
            <th>Price</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($properties as $property)
            <tr>
                <td>{{ $property->prop_name }}</td>
                <td>{{ $property->city }}</td>
                <td>{{ $property->country }}</td>
                <td>{{ getPoundSymbol() }}{{ number_format($property->price, 2) }}</td>
                <td>
                    <button class="btn btn-info" onclick="showOptions(this)">Actions</button>
                    <div class="action-options" style="display: none;">
                        <ul>
                            <li><a href="{{ route('admin.properties.view', $property->id) }}">View</a></li>
                            <li><a href="{{ route('admin.properties.edit', $property->id) }}">Edit</a></li>
                            <li><a href="javascript:void(0);" class="action-icon" onclick="confirmModal('{{ url(route('admin.properties.delete', $property->id)) }}', responseHandler)"><i class="mdi mdi-delete" title="Delete"></i>Delete</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@include('backend.components.modal')
@endsection

@section('page.scripts')
<script>
    $(document).ready(function () {
        $('#properties-table').DataTable();
    });
    function showOptions(button) {
        const actionOptions = $(button).siblings('.action-options');
        actionOptions.toggle();
    }
    var responseHandler = function(response) {
        location.reload();
    }
</script>
@endsection