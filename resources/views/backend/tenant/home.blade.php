@extends('backend.layout.app')

@section('content')
<div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 60vh;">
    <div class="text-center">
        <i class="fa-solid fa-house fa-4x text-muted mb-4"></i>
        <h4 class="text-muted">Welcome, {{ auth()->user()->first_name ?: auth()->user()->name }}</h4>
        <p class="text-muted mb-3">Go to your tenant portal home for tenancy, rent and repairs.</p>
        <a href="{{ route('backend.home') }}" class="btn btn-primary">Open tenant home</a>
    </div>
</div>
@endsection
