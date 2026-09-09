<div class="event-list-panel">
    <div class="event-list-header">
        <h6 class="mb-3 fw-bold">Appointments</h6>
    </div>

    {{-- Filters --}}
    <div class="event-filters p-3 border-bottom">
        <div class="mb-2">
            <input type="text" id="eventSearch" class="form-control form-control-sm" placeholder="Search events..." value="{{ request('search') }}">
        </div>
        
        <div class="row g-2">
            <div class="col-6">
                <select name="type_id" class="form-control form-control-sm calendar-filter">
                    <option value="all">All Types</option>
                    @foreach($filterData['eventTypes'] as $type)
                        <option value="{{ $type->id }}" {{ request('type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6">
                <select name="sub_type_id" class="form-control form-control-sm calendar-filter">
                    <option value="all">All Sub-Types</option>
                    @foreach($filterData['eventSubTypes'] as $subType)
                        <option value="{{ $subType->id }}" {{ request('sub_type_id') == $subType->id ? 'selected' : '' }}>{{ $subType->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row g-2 mt-1">
            <div class="{{ is_landlord_plan_user() ? 'col-12' : 'col-6' }}">
                <select name="status" class="form-control form-control-sm calendar-filter">
                    <option value="all">All Statuses</option>
                    @foreach($filterData['statuses'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            @unless(is_landlord_plan_user())
            <div class="col-6">
                <select name="office" class="form-control form-control-sm calendar-filter">
                    <option value="all">All Offices</option>
                    @foreach($filterData['offices'] as $office)
                        <option value="{{ $office }}" {{ request('office') == $office ? 'selected' : '' }}>{{ $office }}</option>
                    @endforeach
                </select>
            </div>
            @endunless
        </div>

        <div class="row g-2 mt-1">
            @unless(is_landlord_plan_user())
            <div class="col-6">
                <select name="diary_owner" class="form-control form-control-sm calendar-filter">
                    <option value="all">All Diary Owners</option>
                    @foreach($filterData['users'] as $user)
                        <option value="{{ $user->id }}" {{ request('diary_owner') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            @endunless
            <div class="{{ is_landlord_plan_user() ? 'col-12' : 'col-6' }}">
                <select name="property_id" class="form-control form-control-sm calendar-filter">
                    <option value="all">All Properties</option>
                    @foreach($filterData['properties'] as $property)
                        <option value="{{ $property->id }}" {{ request('property_id') == $property->id ? 'selected' : '' }}>{{ $property->prop_name ?: $property->line_1 }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row g-2 mt-1">
            <div class="col-6">
                <select name="repair_id" class="form-control form-control-sm calendar-filter">
                    <option value="all">All Repairs</option>
                    @foreach($filterData['repairIssues'] as $repair)
                        <option value="{{ $repair->id }}" {{ request('repair_id') == $repair->id ? 'selected' : '' }}>{{ $repair->reference_number ?: 'Repair #'.$repair->id }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6">
                <select name="has_reminders" class="form-control form-control-sm calendar-filter">
                    <option value="all">All Reminders</option>
                    <option value="1" {{ request('has_reminders') == '1' ? 'selected' : '' }}>Has Reminders</option>
                    <option value="0" {{ request('has_reminders') == '0' ? 'selected' : '' }}>No Reminders</option>
                </select>
            </div>
        </div>

        <div class="row g-2 mt-1">
            <div class="col-6">
                <input type="date" name="date_from" class="form-control form-control-sm calendar-filter" placeholder="From" value="{{ request('date_from') }}">
            </div>
            <div class="col-6">
                <input type="date" name="date_to" class="form-control form-control-sm calendar-filter" placeholder="To" value="{{ request('date_to') }}">
            </div>
        </div>

        <div class="mt-2 text-center">
            <button type="button" class="btn btn-sm btn-outline-secondary me-1" id="clearFiltersBtn">Clear</button>
            <button type="button" class="btn btn-sm btn-primary" id="applyFiltersBtn">Apply</button>
        </div>

        <input type="hidden" name="page" id="eventListPage" value="1">
    </div>

    {{-- Event List --}}
    <div id="eventListContainer" class="event-list-container">
        <div class="text-muted text-center py-4">Loading events...</div>
    </div>

    <div id="eventListPagination" class="event-list-pagination px-3 py-2 border-top"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Clear filters
    document.getElementById('clearFiltersBtn').addEventListener('click', function() {
        document.querySelectorAll('.calendar-filter').forEach(function(el) {
            el.value = 'all';
        });
        const searchEl = document.getElementById('eventSearch');
        if (searchEl) searchEl.value = '';
        const pageInput = document.getElementById('eventListPage');
        if (pageInput) pageInput.value = 1;
        if (typeof window.refreshEventList === 'function') {
            window.refreshEventList();
        }
    });

    // Apply filters
    document.getElementById('applyFiltersBtn').addEventListener('click', function() {
        const pageInput = document.getElementById('eventListPage');
        if (pageInput) pageInput.value = 1;
        if (typeof window.refreshEventList === 'function') {
            window.refreshEventList();
        }
    });
});
</script>
