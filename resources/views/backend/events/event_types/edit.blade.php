@extends('backend.layout.app')

@section('content')
    <div class="container">
        <h3>Edit Event Type</h3>

        <form action="{{ route('backend.event_types.update', $eventType) }}" method="POST" class="mt-4">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $eventType->name) }}" required>
                @error('name')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            {{-- <div class="mb-3">
                <label class="form-label">Slug (optional)</label>
                <input type="text" name="slug" class="form-control" value="{{ old('slug', $eventType->slug) }}">
                @error('slug')<div class="text-danger">{{ $message }}</div>@enderror
            </div> --}}

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control"
                    rows="3">{{ old('description', $eventType->description) }}</textarea>
                @error('description')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <button class="btn btn-primary">Update</button>
            <a href="{{ route('backend.event_types.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
@endsection