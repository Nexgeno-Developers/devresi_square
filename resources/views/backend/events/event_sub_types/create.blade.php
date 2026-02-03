@extends('backend.layout.app')

@section('content')
<div class="container">
    <h3>Create Event SubType</h3>
    <form action="{{ route('backend.event_sub_types.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label">Event Type *</label>
            <select name="event_type_id" class="form-select" required>
                <option value="">— Select Type —</option>
                @foreach($types as $id => $label)
                    <option value="{{ $id }}" {{ old('event_type_id') == $id ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('event_type_id')<div class="text-danger">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label">SubType Name *</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            @error('name')<div class="text-danger">{{ $message }}</div>@enderror
        </div>

        {{-- <div class="mb-3">
            <label class="form-label">Slug (optional)</label>
            <input type="text" name="slug" class="form-control" value="{{ old('slug') }}">
            @error('slug')<div class="text-danger">{{ $message }}</div>@enderror
        </div> --}}

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control">{{ old('description') }}</textarea>
            @error('description')<div class="text-danger">{{ $message }}</div>@enderror
        </div>

        <button class="btn btn-primary">Save</button>
        <a href="{{ route('backend.event_sub_types.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
