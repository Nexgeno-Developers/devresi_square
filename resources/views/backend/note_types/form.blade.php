@extends('backend.layout.app')

@section('content')
<h2>{{ $noteType->exists ? 'Edit' : 'Create' }} Note Type</h2>

<form method="POST" action="{{ $noteType->exists ? route('admin.note-types.update', $noteType) : route('admin.note-types.store') }}">
    @csrf
    @if($noteType->exists)
        @method('PUT')
    @endif

    <div>
        <label for="name">Name</label>
        <input type="text" name="name" value="{{ old('name', $noteType->name) }}" required>
    </div>

    <button type="submit">{{ $noteType->exists ? 'Update' : 'Create' }}</button>
    <a href="{{ route('admin.note-types.index') }}">Back</a>
</form>
@endsection
