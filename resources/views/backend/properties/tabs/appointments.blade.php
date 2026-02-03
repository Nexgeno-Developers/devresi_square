<button class="btn btn-primary btn-sm w-100" id="btn-add-appointment">Add Appointment</button>
<form id="appointments-filter-form" class="row g-3 align-items-end mb-4">
    <div class="col-md-3 form-floating">
        <input type="text" name="search" class="form-control" id="search" placeholder="Search title or diary owner">
        <label for="search">Search</label>
    </div>

    <div class="col-md-2 form-floating">
        <select name="status" class="form-select" id="status" aria-label="Status">
            <option value="" selected>All Statuses</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Cancelled">Cancelled</option>
            <option value="Pending">Pending</option>
        </select>
        <label for="status">Status</label>
    </div>

    <div class="col-md-2 form-floating">
        <input type="date" name="start_date" class="form-control" id="start_date" placeholder="Start Date">
        <label for="start_date">Start Date</label>
    </div>

    <div class="col-md-2 form-floating">
        <input type="date" name="end_date" class="form-control" id="end_date" placeholder="End Date">
        <label for="end_date">End Date</label>
    </div>

    <input type="hidden" name="property_id" value="{{ $propertyId }}">

    <div class="col-md-1">
        <button type="submit" class="btn btn-primary w-100">Filter</button>
    </div>

    <div class="col-md-2">
        <button type="button" id="reset-appointments-filter" class="btn btn-secondary w-100">Reset</button>
    </div>
</form>


<div id="appointments-results">
    @include('backend.properties.tabs.component._appointments_table', ['events' => $events])
</div>
