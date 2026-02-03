{{-- @extends('backend.layout.app')

@section('content')
<div class="container">
    <h2>Edit Owner Group</h2> --}}

    <form action="{{ route('admin.owner-groups.update', $ownerGroup->id) }}" method="POST">
        @csrf
        @method('PUT')  {{-- Specify the HTTP method for updating --}}

        <input type="hidden" name="property_id" class="form-control" value="{{ $ownerGroup->property_id }}">

        <div class="form-group">
            <label for="user_id">User</label>
            <select name="user_id" id="user_id" class="form-control" required>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ $user->id == $ownerGroup->user_id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="purchased_date">Purchased Date</label>
            <input type="date" name="purchased_date" id="purchased_date" class="form-control" value="{{ $ownerGroup->purchased_date }}" required>
        </div>

        <div class="form-group">
            <label for="sold_date">Sold Date</label>
            <input type="date" name="sold_date" id="sold_date" class="form-control" value="{{ $ownerGroup->sold_date }}">
        </div>

        <div class="form-group">
            <label for="archived_date">Archived Date</label>
            <input type="date" name="archived_date" id="archived_date" class="form-control" value="{{ $ownerGroup->archived_date }}">
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-control" required>
                <option value="active" {{ $ownerGroup->status == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $ownerGroup->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="archived" {{ $ownerGroup->status == 'archived' ? 'selected' : '' }}>Archived</option>
            </select>
        </div>

        <button type="submit" class="float-end mt-3 btn btn_secondary">Update</button>
    </form>
{{-- </div>
@endsection --}}
