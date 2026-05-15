@extends('backend.layout.app')

@section('content')
    <div class="designation-form-page">
        <div class="designation-form-header">
            <div>
                <p class="designation-form-eyebrow">Staff setup</p>
                <h1>Add Designation</h1>
                <p class="designation-form-subtitle">Create a role and choose the permissions staff inherit from it.</p>
            </div>

            <a href="{{ route('admin.designations.index') }}" class="btn btn-outline-secondary designation-back-btn">
                <i class="bi bi-arrow-left"></i>
                <span>Back to list</span>
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger designation-form-alert">
                <i class="bi bi-exclamation-triangle"></i>
                <span>Please review the highlighted fields and try again.</span>
            </div>
        @endif

        <form action="{{ route('admin.designations.store') }}" method="POST" class="designation-form">
            @csrf

            <div class="designation-form-card">
                <div class="designation-card-header">
                    <div>
                        <h2>Role details</h2>
                        <p>Name the designation as it should appear across staff profiles.</p>
                    </div>
                </div>

                <div class="designation-title-field">
                    <label for="title" class="form-label">Designation title</label>
                    <input
                        type="text"
                        name="title"
                        id="title"
                        class="form-control @error('title') is-invalid @enderror"
                        value="{{ old('title') }}"
                        placeholder="e.g. Property Manager"
                        required
                    >
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="designation-form-card">
                <div class="designation-card-header">
                    <div>
                        <h2>Permissions</h2>
                        <p>Select what users with this designation are allowed to access.</p>
                    </div>
                    <span class="designation-count-badge">
                        <span data-selected-permission-count>{{ count(old('permissions', [])) }}</span> selected
                        / {{ $permissions->count() }} available
                    </span>
                </div>

                <div class="permission-section-list">
                    @foreach($permissions->groupBy(fn($permission) => $permission->section ?? 'general') as $section => $permissionGroup)
                        <section class="permission-section">
                            <div class="permission-section-header">
                                <div>
                                    <h3>{{ Str::headline($section) }}</h3>
                                    <span>{{ $permissionGroup->count() }} {{ Str::plural('permission', $permissionGroup->count()) }}</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary permission-section-toggle" data-section-permission-toggle>
                                    Enable all
                                </button>
                            </div>

                            <div class="permission-grid">
                                @foreach($permissionGroup as $permission)
                                    <label class="permission-card">
                                        <span>{{ Str::headline($permission->name) }}</span>
                                        <span class="aiz-switch aiz-switch-success">
                                            <input
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission->id }}"
                                                {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}
                                            >
                                            <span class="slider round"></span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>

            <div class="designation-form-actions">
                <a href="{{ route('admin.designations.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2"></i>
                    <span>Create Designation</span>
                </button>
            </div>
        </form>
    </div>
@endsection

@include('backend.designations.partials.form-styles')
