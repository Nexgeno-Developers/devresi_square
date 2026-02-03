@extends('backend.layout.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
	<div class="row align-items-center">
		<div class="col-md-6">
			<h1 class="h3">All Staffs</h1>
		</div>
        @can('add staff')
            <div class="col-md-6 text-md-right">
                <a href="{{ route('staffs.create') }}" class="btn btn-sm btn-outline-primary m-2">
                    <span>Add New Staffs</span>
                </a>
            </div>
        @endcan
	</div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">Staffs</h5>
    </div>
    <div class="card-body">
        <table class="table aiz-table">
            <thead>
                <tr>
                    <th data-breakpoints="lg" width="10%">#</th>
                    <th>Name</th>
                    <th data-breakpoints="lg">Email</th>
                    {{-- <th data-breakpoints="lg">Phone</th> --}}
                    <th data-breakpoints="lg">Role</th>
                    <th width="10%" class="text-right">Options</th>
                </tr>
            </thead>
            <tbody>
                @foreach($staffs as $key => $staff)
                    @if($staff->user != null)
                        <tr>
                            <td>{{ ($key+1) + ($staffs->currentPage() - 1)*$staffs->perPage() }}</td>
                            <td>{{$staff->user->name}}</td>
                            <td>{{$staff->user->email}}</td>
                            {{-- <td>{{$staff->user->phone}}</td> --}}
                            <td>
								@if ($staff->role != null)
									{{ $staff->role->name }}
								@endif
							</td>
                            <td class="text-right">
                                @can('edit staff')
                                    <a class="btn btn-sm btn-outline-primary mt-2" href="{{ route('staffs.edit', encrypt($staff->id)) }}" title="Edit">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
                                @endcan

                                @can('delete staff')
                                    <a href="#" class="btn btn-sm btn-outline-danger mt-2 confirm-delete" data-href="{{ route('staffs.destroy', $staff->id) }}" title="Delete">
                                        <i class="fa fa-trash"></i> Delete
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        <div class="aiz-pagination">
            {{ $staffs->appends(request()->input())->links() }}
        </div>
    </div>
</div>

@endsection

@section('modal')
    @include('backend.modals.delete_modal')
@endsection
