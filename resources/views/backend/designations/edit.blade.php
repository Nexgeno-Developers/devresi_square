@extends('backend.layout.app')

@section('content')
    <h1>Edit Designation</h1>

    <form action="{{ route('admin.designations.update', $designation->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" class="form-control" value="{{ $designation->title }}" required>
        </div>

        <h5 class="mt-4">Permissions</h5>
        @foreach($permissions->groupBy(fn($permission) => $permission->section ?? 'general') as $section => $permissionGroup)
            <ul class="list-group mb-4">
                <li class="list-group-item bg-light fw-semibold">{{ Str::headline($section) }}</li>
                <li class="list-group-item">
                    <div class="row">
                        @foreach($permissionGroup as $permission)
                            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
                                <div class="p-2 border mt-1 mb-2">
                                    <label class="control-label d-flex small">{{ Str::headline($permission->name) }}</label>
                                    <label class="aiz-switch aiz-switch-success">
                                        <input type="checkbox"
                                               name="permissions[]"
                                               value="{{ $permission->id }}"
                                               {{ in_array($permission->id, $selectedPermissions) ? 'checked' : '' }}>
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </li>
            </ul>
        @endforeach

        <button type="submit" class="btn btn-warning mt-3">Update Designation</button>
    </form>
@endsection
