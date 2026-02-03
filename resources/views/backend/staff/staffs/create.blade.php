@extends('backend.layout.app')

@section('content')
<div class="row">
    <div class="col-lg-10 mt-3 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0 h6">Staff Information</h5>
            </div>

            <form class="form-horizontal" action="{{ route('staffs.store') }}" method="POST" enctype="multipart/form-data">
            	@csrf
                <div class="card-body">
                    {{-- Title dropdown --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="title">Title</label>
                        <div class="col-sm-9">
                            <select id="title" name="title" class="form-control">
                                <option value="">Select Title</option>
                                <option value="Mr">Mr</option>
                                <option value="Mrs">Mrs</option>
                                <option value="Miss">Miss</option>
                                {{-- <option value="Dr">Dr</option> --}}
                            </select>
                        </div>
                    </div>

                    {{-- first name --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="first_name">First Name</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="First Name" id="first_name" name="first_name" class="form-control" required>
                        </div>
                    </div>
                    {{-- middle name --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="middle_name">Middle Name</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="Middle Name" id="middle_name" name="middle_name" class="form-control">
                        </div>
                    </div>
                    {{-- last name --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="last_name">Last Name</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="Last Name" id="last_name" name="last_name" class="form-control" required>
                        </div>
                    </div>

                    {{-- <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="name">Name</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="Name" id="name" name="name" class="form-control" required>
                        </div>
                    </div> --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="email">Email</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="Email" id="email" name="email" class="form-control" required>
                        </div>
                    </div>
                    {{-- <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="mobile">Phone</label>
                        <div class="col-sm-9">
                            <input type="text" placeholder="Phone" id="mobile" name="mobile" class="form-control" required>
                        </div>
                    </div> --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="password">Password</label>
                        <div class="col-sm-9">
                            <input type="password" placeholder="Password" id="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="name">Role</label>
                        <div class="col-sm-9">
                            <select name="role_id" id="role_id" class="form-control select2" required>
                                @foreach($roles as $role)
                                    <option value="{{$role->id}}">{{$role->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label">Add Additional Permissions</label>
                        <div class="col-sm-9">
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input type="checkbox" id="enable-permissions" name="enable_additional_permissions">
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    <div id="additional-permissions-wrapper" style="display: none;">
                        <div class="card-header">
                            <h5 class="mb-0 h6">Additional Permissions</h5>
                        </div>
                        <br>
                        @php
                            $permission_groups = \App\Models\Permission::all()->groupBy('section');
                            $addons = ["offline_payment", "club_point", "pos_system", "paytm", "seller_subscription", "otp_system", "refund_request", "affiliate_system", "african_pg", "delivery_boy", "auction", "wholesale"];
                        @endphp

                        @foreach ($permission_groups as $permission_group)
                            @php
                                $show_permission_group = true;
                                if (in_array($permission_group[0]['section'], $addons) && !addon_is_activated($permission_group[0]['section'])) {
                                    $show_permission_group = false;
                                }
                            @endphp

                            @if($show_permission_group)
                                <ul class="list-group mb-4">
                                    <li class="list-group-item bg-light">{{ Str::headline($permission_group[0]['section']) }}</li>
                                    <li class="list-group-item">
                                        <div class="row">
                                            @foreach ($permission_group as $permission)
                                                <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6 permission-item" data-permission="{{ $permission->name }}">
                                                    <div class="p-2 border mt-1 mb-2">
                                                        <label class="control-label d-flex">{{ Str::headline($permission->name) }}</label>
                                                        <label class="aiz-switch aiz-switch-success">
                                                            <input type="checkbox" name="additional_permissions[]" class="form-control" value="{{ $permission->name }}">
                                                            <span class="slider round"></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </li>
                                </ul>
                            @endif
                        @endforeach
                    </div>

                    {{-- <div class="form-group row">
                        <label class="col-sm-3 col-from-label">Roles</label>
                        <div class="col-sm-9">
                            <select name="role_id[]" id="role_id" class="form-control select2" multiple required>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="card-header">
                        <h5 class="mb-0 h6">Additional Permissions</h5>
                    </div>
                    <br>
                    @php
                        $permission_groups = \App\Models\Permission::all()->groupBy('section');
                        $addons = ["offline_payment", "club_point", "pos_system", "paytm", "seller_subscription", "otp_system", "refund_request", "affiliate_system", "african_pg", "delivery_boy", "auction", "wholesale"];
                    @endphp

                    @foreach ($permission_groups as $key => $permission_group)
                        @php
                            $show_permission_group = true;

                            if(in_array($permission_group[0]['section'], $addons)) {
                                if (!addon_is_activated($permission_group[0]['section'])) {
                                    $show_permission_group = false;
                                }
                            }
                        @endphp

                        @if($show_permission_group)
                            <ul class="list-group mb-4 permission-group" data-section="{{ $permission_group[0]['section'] }}">
                                <li class="list-group-item bg-light" aria-current="true">{{ Str::headline($permission_group[0]['section']) }}</li>
                                <li class="list-group-item">
                                    <div class="row">
                                        @foreach ($permission_group as $permission)
                                            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6 permission-item" data-permission="{{ $permission->name }}">
                                                <div class="p-2 border mt-1 mb-2">
                                                    <label class="control-label d-flex">{{ Str::headline($permission->name) }}</label>
                                                    <label class="aiz-switch aiz-switch-success">
                                                        <input type="checkbox" name="additional_permissions[]" class="form-control demo-sw" value="{{ $permission->name }}">
                                                        <span class="slider round"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </li>
                            </ul>
                        @endif
                    @endforeach --}}
                    
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
@section('page.scripts')
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
@endsection
{{-- 
@section('page.scripts')
<script>
    const rolePermissionsMap = @json(
        $roles->mapWithKeys(fn($role) => [$role->id => $role->permissions->pluck('name')])
    );

    function updatePermissionVisibility() {
        const selectedRoleIds = $('#role_id').val() || [];
        let inheritedPermissions = new Set();

        selectedRoleIds.forEach(id => {
            (rolePermissionsMap[id] || []).forEach(p => inheritedPermissions.add(p));
        });

        $('.permission-item').each(function () {
            const permission = $(this).data('permission');
            if (inheritedPermissions.has(permission)) {
                $(this).hide();
                $(this).find('input[type="checkbox"]').prop('checked', false);
            } else {
                $(this).show();
            }
        });
    }

    $(document).ready(function () {
        initSelect2('.select2');
        updatePermissionVisibility();
        $('#role_id').on('change', updatePermissionVisibility);
    });
</script>
@endsection --}}
