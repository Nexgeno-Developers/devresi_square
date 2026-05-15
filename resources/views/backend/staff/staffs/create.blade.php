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
                    

                    {{-- Primary Email --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="email">Email</label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <input type="text" placeholder="Email" id="email" name="email" class="form-control" required>
                                <button type="button" class="btn btn-danger" id="add-email-btn" title="Add another email">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Extra Emails --}}
                    <div id="extra-emails-list"></div>

                    {{-- Primary Phone --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="phone">Phone</label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <input type="text" placeholder="Phone Number" id="phone" name="phone" class="form-control">
                                <button type="button" class="btn btn-danger" id="add-phone-btn" title="Add another phone">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Extra Phones --}}
                    <div id="extra-phones-list"></div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="password">Password</label>
                        <div class="col-sm-9">
                            <input type="password" placeholder="Password" id="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    {{-- Designation --}}
                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label" for="designation_id">Designation</label>
                        <div class="col-sm-9">
                            <select id="designation_id" name="designation_id" class="form-control select2" required>
                                <option value="">Select Designation</option>
                                @foreach($designations as $designation)
                                    <option value="{{ $designation->id }}">{{ $designation->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-from-label pt-2">Custom Permission</label>
                    </div>

                    @php
                        $oldPermissionIds = collect(old('custom_permissions', []))->map(fn($id) => (int) $id)->toArray();
                        $hasOldPermissions = old('custom_permissions_submitted') !== null;
                    @endphp

                    <div id="custom-permissions-wrapper">
                        <input type="hidden" name="custom_permissions_submitted" value="1">
                        @foreach($permissions->groupBy(fn($permission) => $permission->section ?? 'general') as $section => $permissionGroup)
                            <ul class="list-group mb-4">
                                <li class="list-group-item bg-light fw-semibold">{{ Str::headline($section) }}</li>
                                <li class="list-group-item">
                                    <div class="row">
                                        @foreach($permissionGroup as $permission)
                                            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6 permission-item"
                                                 data-permission-id="{{ $permission->id }}">
                                                <div class="p-2 border mt-1 mb-2">
                                                    <label class="control-label d-flex small">{{ Str::headline($permission->name) }}</label>
                                                    <label class="aiz-switch aiz-switch-success">
                                                        <input type="checkbox"
                                                               name="custom_permissions[]"
                                                               class="form-control custom-permission-checkbox"
                                                               value="{{ $permission->id }}"
                                                               {{ $hasOldPermissions && in_array($permission->id, $oldPermissionIds) ? 'checked' : '' }}>
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
@section('page.scripts')
<script>
    const designationPermissionsMap = @json(
        $designations->mapWithKeys(fn($designation) => [
            $designation->id => $designation->permissions->pluck('id')->values()
        ])
    );
    const hasOldPermissionInput = @json($hasOldPermissions);

    function applyDesignationPermissions() {
        const designationId = $('#designation_id').val();
        const permissionIds = new Set((designationPermissionsMap[designationId] || []).map(Number));

        $('.custom-permission-checkbox').each(function () {
            $(this).prop('checked', permissionIds.has(Number($(this).val())));
        });
    }

    function addContactRow(listId, inputName, placeholder) {
        const row = $(`
            <div class="form-group row contact-row">
                <div class="col-sm-9 offset-sm-3">
                    <div class="input-group">
                        <input type="text" name="${inputName}[]" class="form-control" placeholder="${placeholder}">
                        <button type="button" class="btn btn-outline-danger remove-contact-btn" title="Remove">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `);
        $(`#${listId}`).append(row);
    }

    $(document).ready(function () {
        initSelect2('.select2');

        $('#designation_id').on('change', applyDesignationPermissions);
        if (!hasOldPermissionInput) {
            applyDesignationPermissions();
        }

        $('#add-email-btn').on('click', function () {
            addContactRow('extra-emails-list', 'extra_emails', 'Enter email address');
        });

        $('#add-phone-btn').on('click', function () {
            addContactRow('extra-phones-list', 'extra_phones', 'Enter phone number');
        });

        $(document).on('click', '.remove-contact-btn', function () {
            $(this).closest('.input-group').remove();
        });
    });
</script>
@endsection
