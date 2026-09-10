<div id="mainForm">

    <form id="addTenancyForm" action="{{ route('admin.tenancies.store') }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        @if(! empty($propertyId))
            <input type="hidden" name="property_id" class="form-control" value="{{ old('property_id', $propertyId) }}">
        @else
            <div class="form-group mb-3">
                <label for="property_id">Property <span class="text-danger">*</span></label>
                <select name="property_id" id="property_id" class="form-control" required>
                    <option value="">Select a property</option>
                    @foreach(($properties ?? []) as $property)
                        <option value="{{ $property->id }}" @selected((string) old('property_id') === (string) $property->id)>
                            {{ $property->full_address ?: ($property->line_1 ?: 'Property #'.$property->id) }}
                        </option>
                    @endforeach
                </select>
                @error('property_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
                @if(($properties ?? collect())->isEmpty())
                    <div class="form-text">Add a property first, then you can create a tenancy for it.</div>
                @endif
            </div>
        @endif

        <div class="form-group">
            <button type="button" class="btn btn-outline-primary btn-sm" id="addUserBtn">
                Quick add tenant
            </button>
            <label for="tenant_id">Tenants <span class="text-danger">*</span></label>
            <select name="user_id[]" id="tenant_id" multiple class="form-control select2" required>
                @foreach ($tenants as $user)
                    <option value="{{ $user->id }}" {{ in_array($user->id, old('user_id', [])) ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
            @error('user_id')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div id="tenant-options" class="mt-3"></div>

        <div class="row">
            <div class="col">
                <div class="mb-3">
                    <div class="form-group field-tenancies-status required">
                        <label class="control-label" for="tenancies-status">Status</label>
                        <select required id="tenancies-status" class="form-control" name="status" aria-required="true">
                            <option value="Active" {{ old('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Archived" {{ in_array(old('status'), ['Archived', 'Archive'], true) ? 'selected' : '' }}>Archived</option>
                        </select>
                        @error('status')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    <div class="form-group field-tenancy-type required">
                        <label class="control-label" for="tenancy-type">Tenancy type</label>
                        <select id="tenancy-type" class="form-control" aria-required="true" name="tenancy_type_id" required>
                            <option value="" disabled {{ old('tenancy_type_id') ? '' : 'selected' }}>Select tenancy type</option>
                            @foreach ($tenancyTypes as $tenancyType)
                                <option value="{{ $tenancyType->id }}" {{ old('tenancy_type_id') == $tenancyType->id ? 'selected' : '' }}>{{ $tenancyType->name }}</option>
                            @endforeach
                        </select>
                        @error('tenancy_type_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="mb-3">
                    <div class="form-group field-tenancies-sub_status">
                        @if(is_landlord_plan_user())
                            @php
                                $currentSub = $tenancySubStatuses->firstWhere('name', 'Current Tenancy')
                                    ?? $tenancySubStatuses->first();
                            @endphp
                            <input type="hidden" name="tenancy_sub_status_id" value="{{ old('tenancy_sub_status_id', $currentSub->id ?? '') }}">
                        @else
                        <label class="control-label" for="tenancies-sub_status">Sub Status</label>
                        <select id="tenancies-sub_status" class="form-control" name="tenancy_sub_status_id" aria-required="true" required>
                            <option value="" disabled {{ old('tenancy_sub_status_id') ? '' : 'selected' }}>Select Sub Status</option>
                            @foreach ($tenancySubStatuses as $subStatus)
                                <option value="{{ $subStatus->id }}" {{ old('tenancy_sub_status_id') == $subStatus->id ? 'selected' : '' }}>{{ $subStatus->name }}</option>
                            @endforeach
                        </select>
                        @error('tenancy_sub_status_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        @endif
                    </div>
                </div>
            </div>

            <div class="lw-chip-row col-12">
                <label>
                    <input type="checkbox" name="periodic" {{ old('periodic') ? 'checked' : '' }}>
                    Periodic
                </label>
                <label>
                    <input type="checkbox" name="rolling_contract" {{ old('rolling_contract') ? 'checked' : '' }}>
                    Rolling contract
                </label>
                <label>
                    <input type="checkbox" name="renewal_exempt" {{ old('renewal_exempt') ? 'checked' : '' }}>
                    Renewal exempt
                </label>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col">
                <div class="mb-3">
                    <label class="control-label" for="tenancies-move_in">Move In <span class="text-danger">*</span></label>
                    <input type="date" id="tenancies-move_in" class="form-control" name="move_in" required value="{{ old('move_in') }}">
                    @error('move_in')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    <label class="control-label" for="tenancies-term_months">Term (Months)</label>
                    <input type="number" id="tenancies-term_months" class="form-control" name="term_months" min="0" pattern="^[0-9]+$" value="{{ old('term_months') }}">
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    <label class="control-label" for="tenancies-term_days">Term (Days)</label>
                    <input type="number" id="tenancies-term_days" class="form-control" name="term_days" min="0" pattern="^[0-9]+$" value="{{ old('term_days') }}">
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    <label class="control-label" for="tenancies-move_out">Move Out</label>
                    <input type="date" id="tenancies-move_out" class="form-control" name="move_out" value="{{ old('move_out') }}">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <div class="mb-3">
                    <div class="form-group field-tenancies-tenancy_renewal_confirm_date">
                        <label class="control-label" for="tenancies-tenancy_renewal_confirm_date">Renewal Confirm Date</label>
                        <input type="date" id="tenancies-tenancy_renewal_confirm_date" class="form-control"
                            name="tenancy_renewal_confirm_date" min="{{ todayDate() }}" value="{{ old('tenancy_renewal_confirm_date') }}">
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    <div class="form-group field-tenancies-extension_date">
                        <label class="control-label" for="tenancies-extension_date">Extension Date</label>
                        <input type="date" id="tenancies-extension_date" class="form-control"
                            name="extension_date" min="{{ tomorrowDate() }}" value="{{ old('extension_date') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <div class="mb-3">
                    <div class="form-group field-tenancies-rent">
                        <label class="control-label" for="tenancies-rent">Rent <span class="text-danger">*</span></label>
                        <input type="number" inputmode="numeric" pattern="[0-9]" id="tenancies-rent"
                            class="form-control" name="rent" required value="{{ old('rent') }}">
                        @error('rent')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    <div class="form-group field-tenancies-deposit">
                        <label class="control-label" for="tenancies-deposit">Deposit <span class="text-danger">*</span></label>
                        <input type="number" inputmode="numeric" pattern="[0-9]" id="tenancies-deposit"
                            class="form-control" name="deposit" required value="{{ old('deposit') }}" readonly>
                        @error('deposit')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="row my-4">
            <div class="col">
                <div class="mb-3">
                    <label for="depositType" class="form-label">Deposit Type</label>
                    <select class="form-select" id="depositType" name="deposit_type">
                        <option value="weeks_deposit">No of Weeks Deposit</option>
                        {{-- <option value="months_deposit">No of Months Deposit</option> --}}
                    </select>
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    <label for="depositNumber" class="form-label">Number of deposit type (Weeks)</label>
                    <input type="number" class="form-control" id="depositNumber" name="deposit_number" min="1" placeholder="Enter number of weeks or months" required value="{{ old('deposit_number') }}">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="mb-3">
                    <label for="deposit_held_by" class="form-label">Deposit Held By</label>
                    <select class="form-select" id="deposit_held_by" name="deposit_held_by">
                        <option value="landlord_holding">Landlord holding</option>
                        <option value="deposit_protection_service">Deposit Protection Service</option>
                        <option value="deposit_replacement_scheme">Deposit Replacement Scheme</option>
                        @unless(is_landlord_plan_user())
                        <option value="agent_holding_as_stakeholder">Agent Holding As Stake Holder</option>
                        @endunless
                    </select>
                </div>
            </div>
            <div class="col">
                <div class="mb-3">
                    <label for="depositService" class="form-label">Deposit Service</label>
                    <select class="form-select" id="depositService" name="deposit_service">
                        <option value="tds_dps_number">*TDS or DPS Number</option>
                        <option value="number_of_scheme_or_number_of_reference">Name of the scheme Reference | Number of Scheme</option>
                    </select>
                </div>

                <div class="mb-3 d-none" id="tds_dps_numberField">
                    <label for="tds_dps_number" class="form-label">TDS / DPS Reference Number</label>
                    <input type="text" class="form-control" id="tds_dps_number" name="tds_dps_number" />
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-3"><label class="form-label">Deposit received</label><input type="datetime-local" class="form-control" name="deposit_received_at" value="{{ old('deposit_received_at') }}"></div>
                    <div class="col-md-3"><label class="form-label">Deposit protected</label><input type="datetime-local" class="form-control" name="deposit_protected_at" value="{{ old('deposit_protected_at') }}"></div>
                    <div class="col-md-3"><label class="form-label">Prescribed information sent</label><input type="datetime-local" class="form-control" name="prescribed_information_sent_at" value="{{ old('prescribed_information_sent_at') }}"></div>
                    <div class="col-md-3"><label class="form-label">Written information sent</label><input type="datetime-local" class="form-control" name="written_terms_sent_at" value="{{ old('written_terms_sent_at') }}"></div>
                </div>

                <div class="mb-3 d-none" id="referenceNumberSchemeField">
                    <label for="referenceNumber" class="form-label">Reference Number</label>
                    <input type="text" class="form-control" id="referenceNumber" name="reference_number" />
                </div>
            
                <div class="mb-3 d-none" id="depositSchemeDropdown">
                    <label for="depositScheme" class="form-label">Deposit Scheme</label>
                    <select class="form-select" id="depositScheme" name="deposit_scheme">
                        <option value="">-- Select a scheme --</option>
                        <option value="dps">Deposit Protection Service</option>
                        <option value="tds">Tenancy Deposit Scheme</option>
                        <option value="drs">Deposit Replacement Scheme</option>
                    </select>
                </div>

            </div>
        </div>
        @if(! is_landlord_plan_user())
        <div class="row">
            <div class="col">
                <div class="mb-3">
                    <div class="form-group field-property_manager required has-success">
                        <label class="control-label" for="property_manager">Property Manager</label>
                        <select name="property_manager[]" id="property_manager" multiple class="form-control select2">
                            @foreach ($property_managers as $property_manager)
                                <option value="{{ $property_manager->id }}" {{ in_array($property_manager->id, old('property_manager', [])) ? 'selected' : '' }}>{{ $property_manager->name }}</option>
                            @endforeach
                        </select>
                        @error('property_manager')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        @endif
        <button type="submit" class="float-end mt-3 btn btn-dark lw-btn-primary">Save tenancy</button>
    </form>
</div>

<!-- Add User Form (Step 2) -->
<div id="addUserFormContainer" style="display: none;">
    <form id="addUserForm">
        @csrf
        {{-- <input type="hidden" class="form-control" id="category_id" name="category_id" value="3"> --}}
        <input type="hidden" name="role" value="Tenant">
        <div class="mb-3">
            <label for="user_name" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="user_name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="user_email" class="form-label">Email</label>
            <input type="email" class="form-control" id="user_email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="user_phone" class="form-label">Phone</label>
            <input type="text" class="form-control" id="user_phone" name="phone" required>
        </div>
        <button type="submit" class="btn btn-primary">Save User</button>
        <button type="button" class="btn btn-secondary" id="backToMainForm">Back</button>
    </form>
</div>
@if(request()->ajax())
    @include('backend.tenancies._create-form-scripts')
@endif
