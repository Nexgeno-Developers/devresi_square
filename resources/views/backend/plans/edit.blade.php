@extends('backend.layout.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">Edit Plan</h1>
            <small class="text-muted">{{ $plan->name }}</small>
        </div>
        <div class="col-md-6 text-right">
            <a href="{{ route('admin.plans.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <form action="{{ route('admin.plans.update', $plan) }}" method="POST">
            @csrf @method('PUT')
            @include('backend.plans._form')
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Update Plan
                </button>
                <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
    <div class="col-lg-4">
        @include('backend.plans._preview')
    </div>
</div>
@endsection
