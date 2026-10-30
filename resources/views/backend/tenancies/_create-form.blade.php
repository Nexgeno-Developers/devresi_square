@include('backend.tenancies._record-form-styles')
<div id="mainForm" class="rs-sheet">
    <form id="addTenancyForm" action="{{ route('admin.tenancies.store') }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf

        <section class="rs-section">
            <div class="rs-section-head">
                <div>
                    <h2>Household</h2>
                    <p>The home, who lives there, and whether they can sign in.</p>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addUserBtn">Quick add tenant</button>
            </div>
            <div class="rs-grid">
                @if(! empty($propertyId))
                    @php
                        $linkedProperty = ($properties ?? collect())->firstWhere('id', (int) old('property_id', $propertyId));
                    @endphp
                    <input type="hidden" name="property_id" id="property_id" value="{{ old('property_id', $propertyId) }}" data-property-label="{{ $linkedProperty->full_address ?? ($linkedProperty->line_1 ?? '') }}">
                @else
                    <label class="rs-field rs-span-4" for="property_id">
                        Property
                        <select name="property_id" id="property_id" class="form-select" required>
                            <option value="">Select a property</option>
                            @foreach(($properties ?? []) as $property)
                                <option value="{{ $property->id }}" @selected((string) old('property_id') === (string) $property->id)>
                                    {{ $property->full_address ?: ($property->line_1 ?: 'Property #'.$property->id) }}
                                </option>
                            @endforeach
                        </select>
                        @error('property_id')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                        @if(($properties ?? collect())->isEmpty())
                            <span class="form-text">Add a property first, then you can create a tenancy for it.</span>
                        @endif
                    </label>
                @endif

                <label class="rs-field rs-span-2" for="tenancies-status">
                    Status
                    <select required id="tenancies-status" class="form-select" name="status">
                        <option value="Active" @selected(old('status', 'Active') === 'Active')>Active</option>
                        <option value="Archived" @selected(in_array(old('status'), ['Archived', 'Archive'], true))>Archived</option>
                    </select>
                    @error('status')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </label>

                <label class="rs-field rs-span-6" for="tenant_id">
                    Tenants
                    <select name="user_id[]" id="tenant_id" multiple class="form-control select2" required>
                        @foreach ($tenants as $user)
                            <option value="{{ $user->id }}" @selected(in_array($user->id, old('user_id', [])))>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </label>

                <div id="tenant-options" class="rs-span-6"></div>

                <div class="rs-span-6">
                    <label class="rs-check" for="invite_tenant">
                        <input type="checkbox" name="invite" value="1" id="invite_tenant" class="form-check-input" @checked(old('invite'))>
                        <span>
                            Invite these tenants to the portal
                            <span class="form-text">Leave this off to save the tenancy without a login.</span>
                        </span>
                    </label>
                </div>
            </div>
        </section>

        <section class="rs-section">
            <div class="rs-section-head">
                <div>
                    <h2>Dates</h2>
                    <p>When the let starts, how long it runs, and when it ends.</p>
                </div>
            </div>
            <div class="rs-grid">
                <label class="rs-field rs-span-2" for="tenancies-move_in">
                    Move in
                    <input type="date" id="tenancies-move_in" class="form-control" name="move_in" required value="{{ old('move_in') }}">
                    @error('move_in')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </label>
                <label class="rs-field rs-span-2" for="tenancies-term_months">
                    Term (months)
                    <input type="number" id="tenancies-term_months" class="form-control" name="term_months" min="1" max="36" value="{{ old('term_months', 12) }}">
                </label>
                <label class="rs-field rs-span-2" for="tenancies-move_out">
                    Move out
                    <input type="date" id="tenancies-move_out" class="form-control" name="move_out" value="{{ old('move_out') }}">
                </label>
            </div>
        </section>

        <section class="rs-section">
            <div class="rs-section-head">
                <div>
                    <h2>Rent</h2>
                    <p>The amount, how often it is paid, and the deposit held for this let.</p>
                </div>
            </div>
            <div class="rs-grid">
                <label class="rs-field rs-span-3" for="tenancies-rent">
                    Rent
                    <input type="number" inputmode="decimal" step="0.01" min="0.01" id="tenancies-rent" class="form-control" name="rent" required value="{{ old('rent') }}">
                    @error('rent')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </label>
                <label class="rs-field rs-span-3" for="tenancies-frequency">
                    How often
                    <select class="form-select" id="tenancies-frequency" name="frequency">
                        <option value="Monthly" @selected(old('frequency', 'Monthly') === 'Monthly')>Monthly</option>
                        <option value="Weekly" @selected(old('frequency') === 'Weekly')>Weekly</option>
                    </select>
                </label>
                <label class="rs-field rs-span-3" for="tenancies-rent-due-day">
                    Rent due day
                    <input type="number" min="1" max="28" class="form-control" id="tenancies-rent-due-day" name="rent_due_day" value="{{ old('rent_due_day', 1) }}">
                    <span class="form-text">Day of the month. Weekly rent is due on the first day of each week.</span>
                </label>
                <label class="rs-field rs-span-3" for="tenancies-deposit">
                    Deposit
                    <input type="number" inputmode="decimal" step="0.01" min="0" id="tenancies-deposit" class="form-control" name="deposit" required value="{{ old('deposit') }}">
                    @error('deposit')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </label>
            </div>
        </section>

        <section class="rs-section">
            <div class="rs-section-head">
                <div>
                    <h2>Deposit protection</h2>
                    <p>Scheme and the dates that start the 30-day protection clock. You can fill these after the let is saved.</p>
                </div>
            </div>
            <div class="rs-grid">
                <label class="rs-field rs-span-3" for="depositScheme">
                    Scheme
                    <select class="form-select" id="depositScheme" name="deposit_scheme">
                        <option value="">Not set</option>
                        <option value="dps" @selected(old('deposit_scheme') === 'dps')>Deposit Protection Service</option>
                        <option value="tds" @selected(old('deposit_scheme') === 'tds')>Tenancy Deposit Scheme</option>
                        <option value="drs" @selected(old('deposit_scheme') === 'drs')>Deposit Replacement Scheme</option>
                    </select>
                </label>
                <label class="rs-field rs-span-3" for="referenceNumber">
                    Scheme reference
                    <input type="text" class="form-control" id="referenceNumber" name="reference_number" value="{{ old('reference_number') }}">
                </label>
                <label class="rs-field rs-span-3">
                    Deposit received
                    <input type="datetime-local" class="form-control" name="deposit_received_at" value="{{ old('deposit_received_at') }}">
                </label>
                <label class="rs-field rs-span-3">
                    Deposit protected
                    <input type="datetime-local" class="form-control" name="deposit_protected_at" value="{{ old('deposit_protected_at') }}">
                </label>
                <label class="rs-field rs-span-3">
                    Prescribed information sent
                    <input type="datetime-local" class="form-control" name="prescribed_information_sent_at" value="{{ old('prescribed_information_sent_at') }}">
                </label>
                <label class="rs-field rs-span-3">
                    Written terms sent
                    <input type="datetime-local" class="form-control" name="written_terms_sent_at" value="{{ old('written_terms_sent_at') }}">
                </label>
            </div>
        </section>

        @if(! is_landlord_plan_user())
        <section class="rs-section">
            <div class="rs-section-head">
                <div>
                    <h2>Assignment</h2>
                    <p>Who looks after this let inside the agency.</p>
                </div>
            </div>
            <div class="rs-grid">
                <label class="rs-field rs-span-4" for="property_manager">
                    Property Manager
                    <select name="property_manager[]" id="property_manager" multiple class="form-control select2">
                        @foreach ($property_managers as $property_manager)
                            <option value="{{ $property_manager->id }}" @selected(in_array($property_manager->id, old('property_manager', [])))>{{ $property_manager->name }}</option>
                        @endforeach
                    </select>
                    @error('property_manager')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </label>
            </div>
        </section>
        @endif

        <div class="rs-actions">
            <button type="submit" class="btn lw-btn-primary">Save tenancy</button>
        </div>
    </form>
</div>

<div id="addUserFormContainer" class="rs-sheet" style="display: none;">
    <form id="addUserForm">
        @csrf
        <input type="hidden" name="role" value="Tenant">
        <section class="rs-section">
            <div class="rs-section-head">
                <div>
                    <h2>New tenant</h2>
                    <p>Name and email are enough. Phone can wait.</p>
                </div>
            </div>
            <div class="rs-grid">
                <label class="rs-field rs-span-2" for="user_name">
                    Full name
                    <input type="text" class="form-control" id="user_name" name="name" required>
                </label>
                <label class="rs-field rs-span-2" for="user_email">
                    Email
                    <input type="email" class="form-control" id="user_email" name="email" required>
                </label>
                <label class="rs-field rs-span-2" for="user_phone">
                    Phone
                    <input type="text" class="form-control" id="user_phone" name="phone" maxlength="40">
                </label>
            </div>
            <div class="rs-actions">
                <button type="button" class="btn btn-light" id="backToMainForm">Back</button>
                <button type="submit" class="btn lw-btn-primary">Save tenant</button>
            </div>
        </section>
    </form>
</div>
@if(request()->ajax())
    @include('backend.tenancies._create-form-scripts')
@endif
