@extends('backend.layout.app')

@section('content')

<style>
    /* Styling improvements */
    .table {
        border-radius: 12px;
        overflow: hidden;
    }

    thead.table-light th {
        background-color: #f7f7f9;
        color: #495057;
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
    }

    .table td, .table th {
        vertical-align: middle;
        padding: 0.75rem 1rem;
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.45em 0.65em;
    }

    .btn-group .btn {
        padding: 0.375rem 0.55rem;
    }

    .btn-outline-info {
        border-color: #0dcaf0;
        color: #0dcaf0;
    }

    .btn-outline-warning {
        border-color: #ffc107;
        color: #ffc107;
    }

    .btn-outline-danger {
        border-color: #dc3545;
        color: #dc3545;
    }

    .btn {
        border-radius: 6px !important;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.05);
    }

    .table-striped > tbody > tr:nth-of-type(odd) {
        background-color: #f9fbfd;
    }

    .table-hover tbody tr:hover {
        background-color: #eef5ff;
        transition: background-color 0.2s ease-in-out;
    }

    .table-primary {
        background-color: #e7f1ff !important;
    }

    code {
        font-size: 0.8rem;
        color: #8a2be2;
        font-weight: 500;
    }
</style>

<div class="py-4">
    <div class="row justify-content-center">
        <div class="col-10">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">Transaction Categories</h3>
                <a href="{{ route('backend.transaction_categories.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Category
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Name <i class="bi bi-tag-fill ms-1 text-muted small"></i></th>
                                    <th>Type <i class="bi bi-arrow-left-right ms-1 text-muted small"></i></th>
                                    <th>Status <i class="bi bi-check-circle ms-1 text-muted small"></i></th>
                                    <th>Code <i class="bi bi-code-slash ms-1 text-muted small"></i></th>
                                    <th>System <i class="bi bi-gear ms-1 text-muted small"></i></th>
                                    <th class="text-center" style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $index => $cat)
                                    <tr class="{{ $cat->is_system ? 'table-primary' : '' }}">
                                        <td>{{ $index+1 }}</td>
                                        <td>{{ $cat->name }}</td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ $cat->is_income ? 'success' : 'danger' }}">
                                                {{ $cat->is_income ? 'Income' : 'Expense' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ $cat->is_active ? 'primary' : 'secondary' }}">
                                                {{ $cat->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td><code class="text-uppercase">{{ $cat->code ?? '-' }}</code></td>
                                        <td>
                                            <span class="badge rounded-pill bg-{{ $cat->is_system ? 'dark' : 'light text-dark' }}">
                                                {{ $cat->is_system ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group" aria-label="Actions">
                                                <a href="{{ route('backend.transaction_categories.show', $cat) }}"
                                                    class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="{{ route('backend.transaction_categories.edit', $cat) }}"
                                                    class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                @if(!$cat->is_system)
                                                    <form action="{{ route('backend.transaction_categories.destroy', $cat) }}"
                                                        method="POST" class="d-inline-block"
                                                        onsubmit="return confirm('Delete this category?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>                                            
                                        <td colspan="7" class="text-center text-muted py-5 bg-light rounded">
                                            <i class="bi bi-inbox display-6 d-block mb-3"></i>
                                            <strong>No transaction categories found.</strong>
                                            <p class="mb-0 small">Click the "Add Category" button to create your first entry.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light text-center rounded-bottom">
                    {{ $categories->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
