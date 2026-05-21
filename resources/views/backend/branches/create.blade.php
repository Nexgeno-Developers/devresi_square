@extends('backend.layout.app')

@section('content')
<div class="branch-form-page">
    <div class="branch-form-header">
        <div>
            <p class="branch-form-eyebrow">Company setup</p>
            <h1>Create Branch</h1>
            <p class="branch-form-subtitle">Add a branch under {{ $company->name ?? 'your company' }}.</p>
        </div>
        <a href="{{ route('admin.branches.index') }}" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Branches</span>
        </a>
    </div>

    <form action="{{ route('admin.branches.store') }}" method="POST">
        @csrf
        @include('backend.branches.form', ['buttonText' => 'Create Branch'])
    </form>
</div>
@endsection

@include('backend.branches.partials.styles')
