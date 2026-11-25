@extends('backend.layout.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="mb-0">{{ $title }}</h2>
        <a href="{{ $createUrl ?? route($routeName . '.create') }}" class="btn btn-primary">Create</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="rs-stack-table table table-bordered table-striped">
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
                    @php $stackTitlePlaced = false; @endphp
                    <tr>
                        @foreach($columns as $column)
                            @php
                                $isRowNumber = ($column['key'] ?? '') === 'id' && ($column['label'] ?? '') === '#';
                                $stackTitle = ! $isRowNumber && ! $stackTitlePlaced;
                                if ($stackTitle) {
                                    $stackTitlePlaced = true;
                                }
                            @endphp
                            @if($isRowNumber)
                                <td data-label="#">{{ (($records->currentPage() - 1) * $records->perPage()) + $loop->parent->iteration }}</td>
                            @else
                                @php
                                    $value = data_get($record, $column['key']);
                                    $type = $column['type'] ?? 'text';
                                @endphp
                                <td @class(['rs-stack-title' => $stackTitle]) data-label="{{ $column['label'] }}">
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
                        <td class="rs-stack-actions d-flex gap-1 flex-wrap">
                            <a href="{{ route($routeName . '.edit', $record->id) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route($routeName . '.destroy', $record->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this record?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 1 }}" class="text-center">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $records->links() }}
</div>
@endsection
