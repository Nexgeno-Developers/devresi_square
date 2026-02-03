<div id="card-list">
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>Property</th>
                <th>Issue in</th>
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



    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-3">
        {{ $repairIssues->appends(request()->query())->links() }}
    </div>

</div>