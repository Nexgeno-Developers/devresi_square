@extends('backend.layout.app')

@section('content')
<div class="py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Add Transaction Category</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('backend.transaction_categories.store') }}" method="POST">
                        @csrf
                        
                        @include('backend.transaction_categories._form')

                        <div class="d-flex justify-content-between mt-4">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-save"></i> Save Category
                            </button>
                            <a href="{{ route('backend.transaction_categories.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
