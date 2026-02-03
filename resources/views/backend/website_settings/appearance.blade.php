@extends('backend.layout.app')

@section('content')

<!-- Title Bar -->
<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="h3">Appearance</h1>
        </div>
    </div>
</div>

<!-- General Settings Section -->
<div class="row">
    <div class="col-lg-8 mx-auto">
        <!-- General Settings Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="fw-600 mb-0">General</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('business_settings.update') }}" method="POST">
                    @csrf
                    <!-- Website Name Field -->
                    <div class="form-group row mb-3">
                        <label class="col-md-3 col-form-label">Frontend Website Name</label>
                        <div class="col-md-8">
                            <input type="hidden" name="types[]" value="website_name">
                            <input type="text" name="website_name" class="form-control" placeholder="Website Name" value="{{ get_setting('website_name') }}">
                        </div>
                    </div>
                    <!-- Site Motto Field -->
                    <div class="form-group row mb-3">
                        <label class="col-md-3 col-form-label">Site Motto</label>
                        <div class="col-md-8">
                            <input type="hidden" name="types[]" value="site_motto">
                            <input type="text" name="site_motto" class="form-control" placeholder="Best eCommerce Website" value="{{ get_setting('site_motto') }}">
                        </div>
                    </div>
                    <!-- Site Icon Field -->
                    <div class="form-group row mb-3">
                        <label class="col-md-3 col-form-label">Site Icon</label>
                        <div class="col-md-8">
                            <div class="input-group" data-toggle="aizuploader" data-type="image">
                                <div class="input-group-prepend">
                                    <div class="input-group-text bg-soft-secondary">Browse</div>
                                </div>
                                <div class="form-control file-amount">Choose File</div>
                                <input type="hidden" name="types[]" value="site_icon">
                                <input type="hidden" name="site_icon" value="{{ get_setting('site_icon') }}" class="selected-files">
                            </div>
                            <div class="file-preview mt-2"></div>
                            <small class="text-muted">Website favicon. 32x32 .png</small>
                        </div>
                    </div>
                    <!-- Base Color Field -->
                    <div class="form-group row mb-3">
                        <label class="col-md-3 col-form-label">Website Base Color</label>
                        <div class="col-md-8">
                            <input type="hidden" name="types[]" value="base_color">
                            <input type="text" name="base_color" class="form-control" placeholder="#377dff" value="{{ get_setting('base_color') }}">
                            <small class="text-muted">Hex Color Code</small>
                        </div>
                    </div>
                    <!-- Base Hover Color Field -->
                    <div class="form-group row mb-3">
                        <label class="col-md-3 col-form-label">Website Base Hover Color</label>
                        <div class="col-md-8">
                            <input type="hidden" name="types[]" value="base_hov_color">
                            <input type="text" name="base_hov_color" class="form-control" placeholder="#377dff" value="{{ get_setting('base_hov_color') }}">
                            <small class="text-muted">Hex Color Code</small>
                        </div>
                    </div>
                    <!-- Submit Button -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4 py-2 float-end">Update</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Global SEO Settings Section -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="fw-600 mb-0">Global SEO</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('business_settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <!-- Meta Title Field -->
                    <div class="form-group row mb-3">
                        <label class="col-md-3 col-form-label">Meta Title</label>
                        <div class="col-md-8">
                            <input type="hidden" name="types[]" value="meta_title">
                            <input type="text" class="form-control" placeholder="Title" name="meta_title" value="{{ get_setting('meta_title') }}">
                        </div>
                    </div>
                    <!-- Meta Description Field -->
                    <div class="form-group row mb-3">
                        <label class="col-md-3 col-form-label">Meta Description</label>
                        <div class="col-md-8">
                            <input type="hidden" name="types[]" value="meta_description">
                            <textarea class="resize-off form-control" placeholder="Description" name="meta_description">{{ get_setting('meta_description') }}</textarea>
                        </div>
                    </div>
                    <!-- Meta Keywords Field -->
                    <div class="form-group row mb-3">
                        <label class="col-md-3 col-form-label">Keywords</label>
                        <div class="col-md-8">
                            <input type="hidden" name="types[]" value="meta_keywords">
                            <textarea class="resize-off form-control" placeholder="Keyword, Keyword" name="meta_keywords">{{ get_setting('meta_keywords') }}</textarea>
                            <small class="text-muted">Separate with commas</small>
                        </div>
                    </div>
                    <!-- Meta Image Field -->
                    <div class="form-group row mb-3">
                        <label class="col-md-3 col-form-label">Meta Image</label>
                        <div class="col-md-8">
                            <div class="input-group" data-toggle="aizuploader" data-type="image">
                                <div class="input-group-prepend">
                                    <div class="input-group-text bg-soft-secondary">Browse</div>
                                </div>
                                <div class="form-control file-amount">Choose File</div>
                                <input type="hidden" name="types[]" value="meta_image">
                                <input type="hidden" name="meta_image" value="{{ get_setting('meta_image') }}" class="selected-files">
                            </div>
                            <div class="file-preview mt-2"></div>
                        </div>
                    </div>
                    <!-- Submit Button -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4 py-2 float-end">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
