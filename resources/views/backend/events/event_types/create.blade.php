@extends('backend.layout.app')

@section('content')
<div class="container">
    <h3>Create Event Type</h3>

    <form action="{{ route('backend.event_types.store') }}" method="POST" class="mt-4">
        @csrf

        <div class="mb-3">
            <label class="form-label">Name <span class="text-danger">*</span></label>
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
            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
            @error('description')<div class="text-danger">{{ $message }}</div>@enderror
        </div>

        <button class="btn btn-primary">Save</button>
        <a href="{{ route('backend.event_types.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
