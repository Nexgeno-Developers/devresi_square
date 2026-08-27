@if(empty($isPortalUser) && auth()->user()?->can('delete properties'))
<div class="pcc-bulk-toolbar" id="pccBulkToolbar" style="display:none;">
    <div class="pcc-bulk-info">
        <input type="checkbox" id="pccBulkSelectAll" title="Select all on this page">
        <span id="pccBulkCount">0 selected</span>
    </div>
    <div class="pcc-bulk-actions">
        <select class="form-select form-select-sm" id="pccBulkAction" style="width:auto;">
            <option value="">Bulk actions...</option>
            <option value="archive">Archive selected</option>
            <option value="delete">Permanently delete</option>
        </select>
        <button class="btn btn-sm btn-primary" id="pccBulkApply" type="button">Apply</button>
        <button class="btn btn-sm btn-outline-secondary" id="pccBulkClear" type="button">Clear</button>
    </div>
</div>
@endif
