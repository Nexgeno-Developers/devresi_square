@extends('backend.layout.app')

@section('content')

<!-- Title Bar -->
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="h3">Website Header</h1>
        </div>
    </div>
</div>

<!-- Form Section -->
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">Header Settings</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('business_settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Header Logo Field -->
                    <div class="form-group row mb-3">
                        <label class="col-md-3 col-form-label">Header Logo</label>
                        <div class="col-md-9">
                            <div class="input-group" data-toggle="aizuploader" data-type="image">
                                <div class="input-group-prepend">
                                    <div class="input-group-text bg-soft-secondary font-weight-medium">Browse</div>
                                </div>
                                <div class="form-control file-amount">Choose File</div>
                                <input type="hidden" name="types[]" value="header_logo">
                                <input type="hidden" name="header_logo" class="selected-files" value="{{ get_setting('header_logo') }}">
                            </div>
                            <div class="file-preview mt-2"></div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4 py-2">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
