@if(!isset($editMode) || !$editMode)
    <!-- Display View Mode -->
    <h5 class="fw-bold fs-4 my-3">Issue In</h5>

    <p class="mb-2 fs-6">
      <span class="fw-semibold">Category:</span>
      <span class="bg-light text-dark px-2 py-1 rounded">
        {{ getRepairCategoryDetails($repairIssue->repair_category_id) }}
      </span>
    </p>
    
    <p class="mb-4 fs-6">
      <span class="fw-semibold">Navigation:</span>
      <span class="bg-light text-dark px-2 py-1 rounded">
        {!! getFormattedRepairNavigation($repairIssue->repair_navigation) !!}
      </span>
    </p>
    
    <h5 class="fw-bold fs-4 my-3 pt-2 border-top">Repair Details</h5>
    
    <p class="mb-1 fs-6 fw-semibold">Description:</p>
    <p class="mb-3 fs-6">
      <span class="bg-light text-dark px-2 py-1 rounded">
        {{ $repairIssue->description }}
      </span>
    </p>
    
    <p class="mb-2 fs-6">
      <span class="fw-semibold">Priority:</span>
      <span class="bg-light text-dark px-2 py-1 rounded">
        {{ ucfirst($repairIssue->priority) }}
      </span>
    </p>
    
    <p class="mb-2 fs-6">
      <span class="fw-semibold">Status:</span>
      <span class="bg-light text-dark px-2 py-1 rounded">
        {{ $repairIssue->status }}
      </span>
    </p>
    
    <p class="mb-2 fs-6">
      <span class="fw-semibold">Estimated Price:</span>
      <span class="bg-light text-dark px-2 py-1 rounded">
        {{ $repairIssue->estimated_price }}
      </span>
    </p>
    
    @if ($repairIssue->vat_type == 'exclusive')
      <p class="mb-2 fs-6">
        <span class="fw-semibold">VAT Percentage:</span>
        <span class="bg-light text-dark px-2 py-1 rounded">
          {{ ucfirst($repairIssue->vat_percentage) }}
        </span>
      </p>    
    @endif
    
    @if($repairIssue->tenant_availability)
      <p class="mb-2 fs-6">
        <span class="fw-semibold">Tenant Availability:</span>
        <span class="bg-light text-dark px-2 py-1 rounded">
          {{ \Carbon\Carbon::parse($repairIssue->tenant_availability)->format('d M Y, H:i') }}
        </span>
      </p>
    @endif
    
    @if($repairIssue->access_details)
      <p class="mb-2 fs-6">
        <span class="fw-semibold">Access Details:</span>
        <span class="bg-light text-dark px-2 py-1 rounded">
          {{ $repairIssue->access_details }}
        </span>
      </p>
    @endif
    
    
    <h5 class="fw-bold fs-4 my-3 pt-2 border-top">Repair Photos</h5>

    @if($repairIssue->repairPhotos->count())

            @foreach($repairIssue->repairPhotos as $photo)
                @foreach (explode(',', $photo->photos) as $photo_id)
                    <img class="mb-2 d-block preview-img" role="button" title="Preview" width="100"
                        src="{{ uploaded_asset(trim($photo_id)) }}" 
                        alt="Repair Photo" 
                        onclick="openImageModal('{{ uploaded_asset(trim($photo_id)) }}')">
                @endforeach
            @endforeach
       
    @else
        <p class="text-muted fs-6">No photos available.</p>
    @endif
    
@else
    <form id="propertyNotesForm">
        @csrf
        <!-- somewhere above your tenant block… -->
        <input type="hidden" id="selected_property" value="{{ $repairIssue->property_id ?? '' }}">
        <input type="hidden" name="repair_id" value="{{ $repairIssue->id }}">
        <input type="hidden" name="form_type" value="property_issue_details">
        <div class="row">
            <div class="col-12">
                <!-- Category Display Card: Read-only view with current selection -->
                <div class="card mb-3 validate-card" id="category-display-card">
                    <div class="card-header d-flex justify-content-between align-items-center">Category <button type="button" class="btn btn-info" id="change-category-btn">Change Category</button></div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="repair_category_display">Category</label>
                            <input readonly type="text" class="form-control" id="repair_category_display"
                                value="{{ old('repair_category_id', getRepairCategoryDetails($repairIssue->repair_category_id)) }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <p><b>Navigation:</b> {{ getFormattedRepairNavigation($repairIssue->repair_navigation) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Category Edit Card: Multi-step category selector (initially hidden) -->
                <div class="card mb-3 d-none validate-card" id="category-edit-card">
                    <div class="card-header d-flex justify-content-between align-items-center">Select New Category <button type="button" class="btn btn-warning mt-2 d-none" id="cancel-category-btn">Cancel Category Change</button>
                    </div>
                    <div class="card-body">
                        <!-- Breadcrumb Navigation (optional) -->
                        <nav class="d-none" aria-label="breadcrumb">
                            <ol class="breadcrumb"></ol>
                        </nav>
                        <h6>Category</h6>
                        <!-- in your Blade template, replace each level’s radio group with a single <select> -->
                        <div id="category-main-view" class="main-view">
                            @for ($level = 1; $level <= $maxLevel; $level++)
                            <div class="category-level mb-3" data-level="{{ $level }}" style="{{ $level > 1 ? 'display:none;' : '' }}">
                                <label for="category-select-{{ $level }}">Level {{ $level }}</label>
                                <select class="form-select category-select" id="category-select-{{ $level }}" data-level="{{ $level }}">
                                <option value="">-- Select --</option>
                                @if ($level === 1)
                                    @foreach ($categories as $cat)
                                    @if (is_null($cat->parent_id))
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endif
                                    @endforeach
                                @endif
                                </select>
                            </div>
                            @endfor
                        </div>
    
                        <!-- Hidden inputs to store new category navigation -->
                        <input type="hidden" name="repair_navigation_old" id="selected_categories_old" value="{{ $repairIssue->repair_navigation }}">
                        <input type="hidden"  name="repair_category_id_old" id="last_selected_category_old" value="{{ $repairIssue->repair_category_id }}">
                        <input type="hidden" name="repair_navigation" id="selected_categories" value="{{ $repairIssue->repair_navigation }}">
                        <input type="hidden" name="repair_category_id" id="last_selected_category" value="{{ $repairIssue->repair_category_id }}">
                        <!-- Navigation buttons for category selection -->
                        <div class="d-flex justify-content-between mt-3 d-none" id="category-nav-buttons">
                            <button type="button" class="btn btn-secondary" id="category-prev-btn">Previous</button>
                            <button type="button" class="btn btn-primary d-none " id="category-next-btn" disabled>Next</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="mb-3 validate-card">
                    <div class="form-group mb-3">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" required>{{ old('description', $repairIssue->description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="mb-3 validate-card">
                    <h6>Photos</h6>
                    <div class="form-group rs_upload_btn">
                        <h5 class="sub_title mt-4">Select images</h5>
                        <div class="media_wrapper">
                            <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="true">
                                <label for="repair_photos">Upload Photos</label>
                                <div class="d-none input-group-prepend">
                                    <div class="input-group-text bg-soft-secondary font-weight-medium">Browse</div>
                                </div>
                                <div class="d-none form-control file-amount">Choose File</div>
                                <input type="hidden" id="repair_photos" name="repair_photos" value="{{ $repairIssue->repairPhotos->isNotEmpty() ? $repairIssue->repairPhotos->first()->photos : '' }}" class="selected-files">
                            </div>
                            <div class="d-flex gap-3 file-preview box sm">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="mb-3">
                    <h6>Priority</h6>
                    <div class="form-group">
                        <label for="priority">Select Priority</label>
                        <select name="priority" id="priority" class="form-control">
                            <option value="low" {{ $repairIssue->priority == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ $repairIssue->priority == 'medium' ? 'selected' : '' }}>Medium
                            </option>
                            <option value="high" {{ $repairIssue->priority == 'high' ? 'selected' : '' }}>High
                            </option>
                            <option value="critical" {{ $repairIssue->priority == 'critical' ? 'selected' : '' }}>
                                Urgent</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <h6>Repair Statuses</h6>
                <div class="form-group mb-2">
                    <label for="sub_status">Job Status(contractor)</label>
                    <select name="sub_status" id="sub_status" class="form-control">
                        @foreach(['Pending', 'Quoted', 'Awarded','Work Completed'] as $sub_status)
                            <option value="{{ $sub_status }}" {{ $repairIssue->sub_status == $sub_status ? 'selected' : '' }}>
                                {{ $sub_status }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-2">
                    <label for="status">Repair Ticket Status</label>
                    <select name="status" id="status" class="form-control">
                        @foreach(['Pending', 'Reported', 'Under Process', 'Work Completed', 'Closed', 'Cancelled'] as $status)
                            <option value="{{ $status }}" {{ $repairIssue->status == $status ? 'selected' : '' }}>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-6">
                <!-- New Fields: Tenant/Owner Availability and Access Details -->
                <h6>Tenant/Owner Details</h6>
                <div class="form-group">
                    <label for="tenant_availability">Preferred Availability for Repair (Tenant/Owner)</label>
                    <input type="datetime-local" name="tenant_availability" id="tenant_availability"
                        class="form-control"
                        value="{{ old('tenant_availability', optional($repairIssue->tenant_availability)->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="form-group">
                    <label for="access_details">Access Details Note</label>
                    <!-- You might replace this with a rich text editor -->
                    <textarea name="access_details" id="access_details" class="form-control"
                        rows="3">{{ old('access_details', $repairIssue->access_details) }}</textarea>
                </div>
                <div class="form-group">
                    <input type="hidden" id="selected_tenant" value="{{ $repairIssue->tenant_id ?? '' }}">
                    <label for="tenant-select">Select Tenant</label>
                    <select name="tenant_id" id="tenant-select" class="form-control">
                        <option value="">-- Select Tenant --</option>
                        <!-- Options will be populated dynamically via AJAX -->
                    </select>
                </div>
                <div id="tenant-preview" class="mt-3">
                    <!-- Tenant details preview will appear here -->
                </div>
            </div>
            <div class="col-6">
                <!-- Estimated Price (Only for admin/property manager) -->
                {{-- @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('property_manager')) --}}

                <!-- Estimated Price Input -->
                <div class="form-group">
                    <label for="estimated_price">Estimated Price</label>
                    <input type="number" step="0.01" name="estimated_price" id="estimated_price"
                        class="form-control"
                        value="{{ old('estimated_price', $repairIssue->estimated_price) }}">
                </div>
                <!-- VAT Type Radio Buttons -->
                <div class="form-group mt-3">
                    <label>VAT Type:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="vat_type" id="vat_type_inclusive"
                            value="inclusive" {{ old('vat_type', $repairIssue->vat_type) == 'inclusive' ? 'checked' : '' }}>
                        <label class="form-check-label" for="vat_type_inclusive">
                            Inclusive VAT
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="vat_type" id="vat_type_exclusive"
                            value="exclusive" {{ old('vat_type', $repairIssue->vat_type) == 'exclusive' ? 'checked' : '' }}>
                        <label class="form-check-label" for="vat_type_exclusive">
                            Exclusive VAT
                        </label>
                    </div>
                </div>
                <!-- Exclusive VAT Fields (hidden by default) -->
                <div class="form-group mt-3 d-none" id="exclusive_vat_fields">
                    <label for="vat_percentage">VAT Percentage</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text" id="basic-addon1">%</span>
                        <input type="text" name="vat_percentage" id="vat_percentage" value="{{ old('vat_percentage', $repairIssue->vat_percentage) }}" class="form-control" placeholder="Enter VAT Percentage" aria-label="VAT Percentage" aria-describedby="basic-addon1">
                        </div>
                </div>

                <!-- VAT Calculation Preview -->
                <div class="form-group d-none" id="vat_calculation_preview">
                    <label for="vat_calculation">VAT Calculation Preview</label>
                    <div class="form-control" id="vat_calculation">
                        <!-- Calculation preview will be displayed here -->
                    </div>
                </div>

            
                {{-- @endif --}}
            </div>
        </div>
        <div class="modal-footer px-0">
            <div class="row">
                <div class="col-auto">
                    <button type="button" class="btn btn_outline_secondary" onclick="closeModel();" data-bs-dismiss="modal">Close</button>
                </div>
                <div class="col-auto px-0">
                    <button type="submit" class="btn btn_secondary">Save Changes</button>
                </div>
            </div>
        </div>
    </form>
@endif