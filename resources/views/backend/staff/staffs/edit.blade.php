@extends('backend.layout.app')

@section('content')
<div class="row">
    <div class="col-lg-10 mt-3 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">Staff Information</h5>
            </div>

            <form action="{{ route('staffs.update', $staff->id) }}" method="POST">
                @method('PATCH')
                @csrf
                <div class="card-body">
                    {{-- Title dropdown --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="title">Title</label>
                        <div class="col-sm-9">
                            <select id="title" name="title" class="form-control">
                                <option value="">Select Title</option>
                                <option value="Mr" {{ $staff->user->title == 'Mr' ? 'selected' : '' }}>Mr</option>
                                <option value="Mrs" {{ $staff->user->title == 'Mrs' ? 'selected' : '' }}>Mrs</option>
                                <option value="Miss" {{ $staff->user->title == 'Miss' ? 'selected' : '' }}>Miss</option>
                                {{-- <option value="Dr" {{ $staff->user->title == 'Dr' ? 'selected' : '' }}>Dr</option> --}}
                            </select>
                        </div>
                    </div>

                    {{-- first name --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="first_name">First Name</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="First Name" id="first_name" name="first_name" value="{{ $staff->user->first_name }}" class="form-control" required>
                        </div>
                    </div>
                    {{-- middle name --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="middle_name">Middle Name</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="Middle Name" id="middle_name" name="middle_name" value="{{ $staff->user->middle_name }}" class="form-control">
                        </div>
                    </div>
                    {{-- last name --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="last_name">Last Name</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="Last Name" id="last_name" name="last_name" value="{{ $staff->user->last_name }}" class="form-control" required>
                        </div>
                    </div>

                    {{-- <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="name">Name</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="Name" id="name" name="name" value="{{ $staff->user->name }}" class="form-control" required>
                        </div>
                    </div> --}}

                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="email">Email</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="Email" id="email" name="email" value="{{ $staff->user->email }}" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="password">Password</label>
                        <div class="col-sm-9">
                            <input type="password" placeholder="Password" id="password" name="password" class="form-control">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="role_id">Role</label>
                        <div class="col-sm-9">
                            <select name="role_id" id="role_id" class="form-control select2" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" @if($staff->role_id == $role->id) selected @endif>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Toggle Additional Permissions --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label">Add Additional Permissions</label>
                        <div class="col-sm-9">
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input type="checkbox" id="enable-permissions" name="enable_additional_permissions" {{ count($userPermissions) ? 'checked' : '' }}>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>

                    {{-- Permissions Section --}}
                    <div id="additional-permissions-wrapper" style="display: {{ count($userPermissions) ? 'block' : 'none' }};">
                        @php
                            $permission_groups = $permissions->groupBy('section');
                            $rolePermissions = $staff->role->permissions->pluck('name')->toArray();
                        @endphp

                        @foreach ($permission_groups as $section => $permission_group)
                            <ul class="list-group mb-4">
                                <li class="list-group-item bg-light">{{ Str::headline($section) }}</li>
                                <li class="list-group-item">
                                    <div class="row">
                                        @foreach ($permission_group as $permission)
                                            @php
                                                $permName = $permission->name;
                                                $isInherited = in_array($permName, $rolePermissions);
                                                $isChecked = in_array($permName, $userPermissions);
                                            @endphp
                                            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6 permission-item" data-permission="{{ $permName }}" style="{{ $isInherited ? 'display: none;' : '' }}">
                                                <div class="p-2 border mt-1 mb-2">
                                                    <label class="control-label d-flex">{{ Str::headline($permName) }}</label>
                                                    <label class="aiz-switch aiz-switch-success">
                                                        <input type="checkbox" name="additional_permissions[]" class="form-control" value="{{ $permName }}" {{ $isChecked ? 'checked' : '' }}>
                                                        <span class="slider round"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </li>
                            </ul>
                        @endforeach
                    </div>

                    <div class="form-group mb-0 text-right">
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@include('backend.partials.assets.select2')

{{-- @section('page.scripts')
<script>
    const rolePermissionsMap = @json(
        $roles->mapWithKeys(fn($role) => [$role->id => $role->permissions->pluck('name')])
    );

    function updatePermissionVisibility() {
        const roleId = $('#role_id').val();
        const inherited = new Set(rolePermissionsMap[roleId] || []);

        $('.permission-item').each(function () {
            const perm = $(this).data('permission');
            if (inherited.has(perm)) {
                $(this).hide();
                $(this).find('input[type="checkbox"]').prop('checked', false);
            } else {
                $(this).show();
            }
        });
    }

    $(document).ready(function () {
        initSelect2('.select2');

        $('#enable-permissions').on('change', function () {
            $('#additional-permissions-wrapper').toggle(this.checked);
        });

        $('#role_id').on('change', updatePermissionVisibility);

        updatePermissionVisibility();
    });
</script>
@endsection --}}

@section('page.scripts')
<script>
    const rolePermissionsMap = @json(
        $roles->mapWithKeys(fn($role) => [$role->id => $role->permissions->pluck('name')])
    );

    let manuallyCheckedPermissions = new Set();

    function updatePermissionVisibility() {
        const roleId = $('#role_id').val();
        const inherited = new Set(rolePermissionsMap[roleId] || []);

        $('.permission-item').each(function () {
            const $checkbox = $(this).find('input[type="checkbox"]');
            const perm = $(this).data('permission');

            // Store checked permissions before hiding
            if ($checkbox.is(':checked')) {
                manuallyCheckedPermissions.add(perm);
            }

            if (inherited.has(perm)) {
                $(this).hide();
            } else {
                $(this).show();
                $checkbox.prop('checked', manuallyCheckedPermissions.has(perm));
            }
        });
    }

    $(document).ready(function () {
        initSelect2('.select2');

        $('#enable-permissions').on('change', function () {
            $('#additional-permissions-wrapper').toggle(this.checked);
        });

        $('#role_id').on('change', updatePermissionVisibility);

        updatePermissionVisibility();
    });
</script>
@endsection

