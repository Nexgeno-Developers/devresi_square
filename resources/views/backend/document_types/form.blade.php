@extends('backend.layout.app')

@section('content')
<div class="mt-5">
    <h2 class="mb-4">{{ $documentType->exists ? 'Edit' : 'Create' }} Document Type</h2>

    <form method="POST" action="{{ $documentType->exists ? route('admin.document-types.update', $documentType) : route('admin.document-types.store') }}">
        @csrf
        @if($documentType->exists)
            @method('PUT')
        @endif

        <div class="row mb-3">
            <label for="name" class="col-sm-2 col-form-label">Name</label>
            <div class="col-sm-6">
                <input 
                    type="text" 
                    class="form-control @error('name') is-invalid @enderror" 
                    id="name" 
                    name="name" 
                    value="{{ old('name', $documentType->name) }}" 
                    required
                >
                @error('name')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6 offset-sm-2">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        {{ $documentType->exists ? 'Update' : 'Create' }}
                    </button>
                    <a href="{{ route('admin.document-types.index') }}" class="btn btn-secondary">
                        Back
                    </a>
                </div>
            </div>
        </div>
        
    </form>
</div>
@endsection
