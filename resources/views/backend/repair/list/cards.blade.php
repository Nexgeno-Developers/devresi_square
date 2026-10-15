<div id="card-list">
    @if($repairIssues->count())
        <div class="lw-repair-grid">
            @foreach ($repairIssues as $item)
                <x-backend.repair-card :repair="$item" :selectedRepairId="$selectedRepairId ?? null" />
            @endforeach
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $repairIssues->appends(request()->query())->links() }}
        </div>
    @else
        <x-lw.empty title="No Open Repairs">
            Everything is currently resolved. New issues will appear here when they are reported.
            <x-slot:actions>
                <a href="{{ route('admin.property_repairs.create') }}" class="btn lw-btn-primary" data-next-action="raise-repair">Add Repair</a>
            </x-slot:actions>
        </x-lw.empty>
    @endif
</div>
