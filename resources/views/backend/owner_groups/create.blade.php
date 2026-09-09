@extends('backend.layout.app')

@section('content')
<div class="container">
    <h2>Create Owner Group</h2>
    <a href="{{ route('admin.owner-groups.index') }}" class="btn btn-outline-secondary mb-3">Back</a>

    <form action="{{ route('admin.owner-groups.store') }}" method="POST">
        @csrf

        <div class="form-group mb-3">
            <label for="property_id">Property</label>
            <select name="property_id" id="property_id" class="form-control @error('property_id') is-invalid @enderror" required>
                <option value="">Select a property</option>
                @foreach($properties as $property)
                    <option value="{{ $property->id }}" @selected((string) old('property_id') === (string) $property->id)>
                        {{ $property->full_address ?: ($property->line_1 ?: 'Property #'.$property->id) }}
                    </option>
                @endforeach
            </select>
            @error('property_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group mb-3">
            <label for="user_id">Owner</label>
            <select name="user_id" id="user_id" class="form-control @error('user_id') is-invalid @enderror" required>
                <option value="">Select an owner</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
            @error('user_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @if($users->isEmpty())
                <div class="form-text">Add a contact with the Owner role, or use your own account as the owner.</div>
            @endif
        </div>

        <div class="row">
            <div class="col-md-6 col-12">
                <div class="form-group mb-3">
                    <label for="purchased_date">Purchased Date</label>
                    <input type="date" name="purchased_date" id="purchased_date" class="form-control @error('purchased_date') is-invalid @enderror" value="{{ old('purchased_date') }}" required>
                    @error('purchased_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-6 col-12">
                <div class="form-group mb-3">
                    <label for="sold_date">Sold Date</label>
                    <input type="date" name="sold_date" id="sold_date" class="form-control @error('sold_date') is-invalid @enderror" value="{{ old('sold_date') }}">
                    @error('sold_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 col-12">
                <div class="form-group mb-3">
                    <label for="archived_date">Archived Date</label>
                    <input type="date" name="archived_date" id="archived_date" class="form-control @error('archived_date') is-invalid @enderror" value="{{ old('archived_date') }}">
                    @error('archived_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-6 col-12">
                <div class="form-group mb-3">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                        <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                        <option value="archived" @selected(old('status') === 'archived')>Archived</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <button type="submit" class="float-end mt-3 btn btn-secondary">Save</button>
    </form>
</div>
@endsection
