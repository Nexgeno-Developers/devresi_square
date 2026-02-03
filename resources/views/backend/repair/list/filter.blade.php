<!-- Filter and Search -->
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex gap-2">
            <form method="GET" action="{{ route('admin.property_repairs.index') }}" id="filter-form">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by Property Name or ID"
                        value="{{ request('search') }}">
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </form>
            <form method="GET" action="{{ route('admin.property_repairs.index') }}">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Filter by Status --</option>
                    @foreach(['Pending', 'Reported', 'Under Process', 'Work Completed', 'Invoice Received', 'Invoice Paid', 'Closed'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                            {{ $status }}
                        </option>
                    @endforeach
                </select>
            </form>
            <a href="{{ route('admin.property_repairs.index') }}" class="btn btn-secondary">Reset Filters</a>
        </div> 
    </div>

    <div class="col">
       
    </div>
</div>
