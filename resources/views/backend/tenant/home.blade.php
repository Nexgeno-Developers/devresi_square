@extends('backend.layout.app')

@section('content')
<div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 60vh;">
    <div class="text-center">
        <i class="fa-solid fa-building fa-4x text-muted mb-4"></i>
        <h4 class="text-muted">Welcome, {{ auth()->user()->first_name ?: auth()->user()->name }}</h4>
        <p class="text-muted">Use the sidebar to navigate to Properties, Contacts, or Repair.</p>
    </div>
</div>
@endsection
