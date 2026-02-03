@extends('backend.layout.app')

@section('content')
<div class="aiz-titlebar text-left mt-2 mb-3">
    <h5 class="mb-0 h6">Role Information</h5>
</div>

<div class="col-lg-12 mx-auto">
    <div class="card">
        <div class="card-body p-0">
           <form class="p-4" action="{{ route('roles.update', $role->id) }}" method="POST">
                <input name="_method" type="hidden" value="PATCH">
                @csrf

                {{-- Role Name --}}
                <div class="form-group row align-items-center mb-4">
                    <label class="col-md-2 col-form-label text-md-right fw-semibold" for="name">
                        Role Name <i class="las la-language text-danger" title="Translatable"></i>
                    </label>
                    <div class="col-md-10">
                        @php $roleForTranslation = \App\Models\Role::where('id',$role->id)->first(); @endphp
                        <input readonly type="text" placeholder="Name" id="name" name="name" class="form-control shadow-sm" value="{{ $roleForTranslation->name }}" required>
                    </div>
                </div>

                {{-- Permissions Header --}}
                <div class="card-header border-bottom">
                    <h5 class="mb-0 h6 fw-bold text-primary">Permissions</h5>
                </div>

                {{-- Search Bar --}}
                <div class="form-group px-4 py-3">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white" id="permission-search-label">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" id="permissionSearch" class="form-control" placeholder="Search permissions..." aria-label="Search Permissions" aria-describedby="permission-search-label">
                    </div>
                </div>

                {{-- Permission Groups --}}
                @php
                    $permission_groups = \App\Models\Permission::all()->groupBy('section');
                @endphp
                @foreach ($permission_groups as $key => $permission_group)
                    @php $section_slug = Str::slug($permission_group[0]['section']); @endphp

                    <ul class="list-group mb-4 shadow-sm mx-3">
                        {{-- Group Title with Enable/Disable Buttons --}}
                        <li class="list-group-item bg-light d-flex mb-0 justify-content-between align-items-center flex-wrap border-bottom">
                            <strong class="text-secondary">{{ Str::headline($permission_group[0]['section']) }}</strong>
                            <div class="d-flex gap-2 mt-2 mt-md-0">
                                <button type="button" class="btn btn-sm btn-outline-success enable-all-btn" data-target="{{ $section_slug }}">
                                    <i class="fas fa-check-circle me-1"></i> Enable All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger disable-all-btn" data-target="{{ $section_slug }}">
                                    <i class="fas fa-times-circle me-1"></i> Disable All
                                </button>
                            </div>
                        </li>

                        {{-- Permission List --}}
                        <li class="list-group-item pt-3 pb-2">
                            <div class="row permission-group" data-group="{{ $section_slug }}">
                                @foreach ($permission_group as $key => $permission)
                                    <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                                        <div class="p-2 border rounded hover-shadow-sm mb-2">
                                            <label class="control-label d-block fw-medium small">{{ Str::headline($permission->name) }}</label>
                                            <label class="aiz-switch aiz-switch-success">
                                                <input type="checkbox" name="permissions[]" class="form-control demo-sw" value="{{ $permission->id }}"
                                                    @if ($role->hasPermissionTo($permission->name)) checked @endif>
                                                <span class="slider round"></span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </li>
                    </ul>
                @endforeach

                {{-- Submit --}}
                <div class="form-group mb-3 text-end px-4">
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="fas fa-save me-1"></i> Update
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

@endsection
@section('page.scripts')
<script>
    $(document).ready(function () {
        // Enable All in group
        $('.enable-all-btn').on('click', function () {
            let group = $(this).data('target');
            $(`.permission-group[data-group="${group}"] input[type="checkbox"]`).prop('checked', true);
        });

        // Disable All in group
        $('.disable-all-btn').on('click', function () {
            let group = $(this).data('target');
            $(`.permission-group[data-group="${group}"] input[type="checkbox"]`).prop('checked', false);
        });

        // Search filter
        $('#permissionSearch').on('keyup', function () {
            let value = $(this).val().toLowerCase();

            $('.permission-group').each(function () {
                let group = $(this);
                let visibleCount = 0;

                group.find('.col-lg-2, .col-md-3, .col-sm-4, .col-xs-6').each(function () {
                    let text = $(this).find('.control-label').text().toLowerCase();
                    if (text.includes(value)) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                    }
                });

                // Show or hide the entire permission group block
                if (visibleCount === 0) {
                    group.closest('.list-group').hide();
                } else {
                    group.closest('.list-group').show();
                }
            });
        });
    });
</script>
@endsection
