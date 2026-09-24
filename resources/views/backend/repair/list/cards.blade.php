<div id="card-list">
    @if($repairIssues->count())
    <table class="table lw-table align-middle mb-0">
        <thead>
            <tr>
                <th>Property</th>
                <th>Issue</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Posted on</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($repairIssues as $item)
                <x-backend.repair-card :repair="$item" :selectedRepairId="$selectedRepairId" />
            @endforeach
        </tbody>
    </table>

    <div class="d-flex justify-content-center mt-3">
        {{ $repairIssues->appends(request()->query())->links() }}
    </div>
    @else
        <x-lw.empty title="No repairs yet">
            When something needs fixing, raise it here or wait for a tenant report.
            <x-slot:actions>
                <a href="{{ route('admin.property_repairs.create') }}" class="btn lw-btn-primary" data-next-action="raise-repair">Raise repair</a>
            </x-slot:actions>
        </x-lw.empty>
    @endif
</div>
