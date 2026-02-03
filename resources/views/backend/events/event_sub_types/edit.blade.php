@extends('backend.layout.app')

@section('content')
    <div class="container">
        <h3>Edit Event SubType</h3>

        <form action="{{ route('backend.event_sub_types.update', $eventSubType) }}" method="POST" class="mt-4">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Event Type <span class="text-danger">*</span></label>
                <select name="event_type_id" class="form-select" required>
                    <option value="">— Select Type —</option>
                    @foreach($types as $id => $label)
                        <option value="{{ $id }}" {{ old('event_type_id', $eventSubType->event_type_id) == $id ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('event_type_id')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">SubType Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $eventSubType->name) }}" required>
                @error('name')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            {{-- <div class="mb-3">
                <label class="form-label">Slug (optional)</label>
                <input type="text" name="slug" class="form-control" value="{{ old('slug', $eventSubType->slug) }}">
                @error('slug')<div class="text-danger">{{ $message }}</div>@enderror
            </div> --}}

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control"
                    rows="3">{{ old('description', $eventSubType->description) }}</textarea>
                @error('description')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <button class="btn btn-primary">Update</button>
            <a href="{{ route('backend.event_sub_types.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
@endsection