<!-- Filter and Search -->
<div class="mb-3">
    <div class="lw-chip-filters mb-2" role="navigation" aria-label="Repair status">
        <a href="{{ route('admin.property_repairs.index') }}" class="{{ ! request('status') ? 'is-active' : '' }}">All</a>
        @foreach(client_facing_repair_statuses() as $status)
            <a href="{{ route('admin.property_repairs.index', ['status' => $status]) }}" class="{{ request('status') === $status ? 'is-active' : '' }}">{{ $status }}</a>
        @endforeach
    </div>
    <form method="GET" action="{{ route('admin.property_repairs.index') }}" class="d-flex gap-2 flex-wrap">
        @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
        <input type="text" name="search" class="form-control" style="max-width: 280px;" placeholder="Search address or ID"
            value="{{ request('search') }}">
        <button class="btn lw-btn-primary" type="submit">Search</button>
        <a href="{{ route('admin.property_repairs.index') }}" class="btn lw-btn-ghost">Reset</a>
        <a href="{{ route('admin.property_repairs.create') }}" class="btn lw-btn-secondary ms-auto">Raise repair</a>
    </form>
</div>
