@extends('backend.layout.app')

@section('content')
<div class="py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Edit Transaction Category</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('backend.transaction_categories.update', $transaction_category) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @include('backend.transaction_categories._form')

                        <div class="d-flex justify-content-between mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-pencil-square"></i> Update Category
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
