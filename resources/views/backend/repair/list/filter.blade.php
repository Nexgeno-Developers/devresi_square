<div class="lw-repair-toolbar">
    <div class="lw-chip-filters" role="navigation" aria-label="Repair status">
        <a href="{{ route('admin.property_repairs.index') }}" class="{{ ! request('status') ? 'is-active' : '' }}">All</a>
        @foreach(client_facing_repair_statuses() as $status)
            <a href="{{ route('admin.property_repairs.index', ['status' => $status]) }}" class="{{ request('status') === $status ? 'is-active' : '' }}">{{ rs_repair_status_label($status) }}</a>
        @endforeach
    </div>
    <form method="GET" action="{{ route('admin.property_repairs.index') }}" class="lw-repair-search">
        @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
        <input type="text" name="search" class="form-control" placeholder="Search address" value="{{ request('search') }}">
        <button class="btn lw-btn-primary" type="submit">Search</button>
        @if(request('search') || request('status'))
            <a href="{{ route('admin.property_repairs.index') }}" class="btn lw-btn-ghost">Clear Filters</a>
        @endif
    </form>
</div>
