@extends('backend.layout.app')

@section('content')

<div class="aiz-titlebar mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">All Role</h1>
        </div>
        @can('add staff role')
        <div class="col-md-6 text-end">
            <a href="{{ route('roles.create') }}" class="btn btn-info btn-sm">
                <i class="fas fa-plus me-1"></i> Add New Role
            </a>
        </div>
        @endcan
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Roles</h5>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th width="10%">#</th>
                    <th>Name</th>
                    <th class="text-end" width="15%">Options</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $key => $role)
                <tr>
                    <td>{{ ($key + 1) + ($roles->currentPage() - 1) * $roles->perPage() }}</td>
                    <td>{{ $role->name }}</td>
                    <td class="d-flex text-end">
                        @can('edit staff role')
                        <a href="{{ route('roles.edit', ['id' => $role->id, 'lang' => env('DEFAULT_LANGUAGE')]) }}" class="btn btn-outline-primary btn-sm rounded-circle" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        @endcan
                        @if($role->id != 1 && auth()->user()->can('delete staff role'))
                        <a href="#" class="btn btn-outline-danger btn-sm rounded-circle confirm-delete" data-href="{{ route('roles.destroy', $role->id) }}" title="Delete">
                            <i class="fas fa-trash"></i>
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-3">
            {{ $roles->appends(request()->input())->links() }}
        </div>
    </div>
</div>

@endsection

@section('modal')
    @include('backend.modals.delete_modal')
@endsection
