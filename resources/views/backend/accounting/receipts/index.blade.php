@extends('backend.layout.app')

@section('content')
<div class="mt-md-4 me-md-4 me-3 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">{{ $title }}</h2>
        <a href="{{ route($routeName . '.create') }}" class="btn btn-primary">Create</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Company ID</label>
                <input type="number" name="company_id" class="form-control" value="{{ $filters['company_id'] }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Customer</label>
                <select name="user_id" class="form-select">
                    <option value="">All</option>
                    @foreach($selectOptions['user_id'] as $id => $name)
                        <option value="{{ $id }}" @selected((string)$filters['user_id'] === (string)$id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(['unapplied' => 'Unapplied', 'partially_applied' => 'Partially Applied', 'applied' => 'Applied'] as $key => $label)
                        <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-outline-secondary flex-grow-1" type="submit">Filter</button>
                <a class="btn btn-link text-decoration-none" href="{{ route($routeName . '.index') }}">Reset</a>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th>{{ $column['label'] }}</th>
                    @endforeach
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    <tr>
                        @foreach($columns as $column)
                            @if(($column['key'] ?? '') === 'id' && ($column['label'] ?? '') === '#')
                                <td>{{ (($records->currentPage() - 1) * $records->perPage()) + $loop->parent->iteration }}</td>
                            @else
                                @php
                                    $value = data_get($record, $column['key']);
                                    $type = $column['type'] ?? 'text';
                                @endphp
                                <td>
                                    @if($type === 'boolean')
                                        <span class="badge bg-{{ $value ? 'success' : 'secondary' }}">{{ $value ? 'Yes' : 'No' }}</span>
                                    @elseif($type === 'money')
                                        {{ number_format((float) $value, 2) }}
                                    @elseif($type === 'date')
                                        {{ $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : '-' }}
                                    @else
                                        {{ $value ?? '-' }}
                                    @endif
                                </td>
                            @endif
                        @endforeach
                        <td class="d-flex gap-1 flex-wrap">
                            @php
                                $canDelete = ($record->status === 'unapplied') && (($record->applied_amount ?? 0) <= 0);
                            @endphp
                            <a href="{{ route($routeName . '.show', $record->id) }}" class="btn btn-sm btn-info text-white">View</a>
                            <a href="{{ route($routeName . '.edit', $record->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            @if($canDelete)
                                <form action="{{ route($routeName . '.destroy', $record->id) }}" method="POST" onsubmit="return confirm('Delete this receipt?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            @else
                                <span class="text-muted small">Applied. Use undo credit on invoice to remove.</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 1 }}" class="text-center">No receipts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $records->links() }}
</div>
@endsection
