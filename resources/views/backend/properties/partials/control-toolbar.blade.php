<div class="pcc-toolbar">
    <button class="pcc-back-btn" id="pccBackBtn" title="Back to list">
        <i class="bi bi-chevron-left"></i>
    </button>
    <span class="pcc-toolbar-title">Properties</span>
    <span class="pcc-toolbar-spacer"></span>
    <button class="btn btn-sm btn-outline-secondary pcc-search-btn" id="pccCommandTrigger" title="Search properties & actions (Ctrl+K)" type="button">
        <i class="bi bi-search"></i>
        <span class="pcc-search-label">Search</span>
        <span class="pcc-kbd">Ctrl+K</span>
    </button>
    @can('create properties')
        <a href="{{ route('admin.properties.quick') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle"></i>
            <span>Add Property</span>
        </a>
    @endcan
</div>
