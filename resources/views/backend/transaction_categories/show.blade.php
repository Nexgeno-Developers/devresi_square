@extends('backend.layout.app')

@section('content')
<div class="py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Category Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <tr>
                            <th>ID</th>
                            <td>{{ $transaction_category->id }}</td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td>{{ $transaction_category->name }}</td>
                        </tr>
                        <tr>
                            <th>Code</th>
                            <td>{{ $transaction_category->code ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Type</th>
                            <td>
                                <span class="badge bg-{{ $transaction_category->is_income ? 'success' : 'danger' }}">
                                    {{ $transaction_category->is_income ? 'Income' : 'Expense' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge bg-{{ $transaction_category->is_active ? 'primary' : 'secondary' }}">
                                    {{ $transaction_category->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>System</th>
                            <td>
                                <span class="badge bg-{{ $transaction_category->is_system ? 'dark' : 'light text-dark' }}">
                                    {{ $transaction_category->is_system ? 'Yes' : 'No' }}
                                </span>
                            </td>
                        </tr>
                    </table>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('backend.transaction_categories.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left-circle"></i> Back
                        </a>
                        <a href="{{ route('backend.transaction_categories.edit', $transaction_category) }}" class="btn btn-warning">
                            <i class="bi bi-pencil-square"></i> Edit
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
