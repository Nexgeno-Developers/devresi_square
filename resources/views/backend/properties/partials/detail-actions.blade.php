<div class="pcc-quick-actions" id="pccDetailActions">
    @can('edit properties')
        <a href="{{ route('admin.properties.edit', $property->id) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil"></i>
            <span>Edit</span>
        </a>
    @endcan
    @can('create tenancies')
        <a href="{{ route('admin.tenancies.create', ['property_id'=>$property->id]) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle"></i>
            <span>Tenancy</span>
        </a>
    @endcan
    <a href="{{ route('admin.property_repairs.create') }}?property_id={{ $property->id }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-wrench"></i>
        <span>Repair</span>
    </a>
    <a href="{{ route('admin.properties.brochure', $property->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-file-earmark-pdf"></i>
        <span>Brochure</span>
    </a>
    @can('delete properties')
        <button type="button" class="btn btn-sm btn-outline-danger pcc-icon-btn"
            title="Delete property"
            onclick="confirmModal('{{ route('admin.properties.delete', $property->id) }}', responseHandler)">
            <i class="bi bi-trash"></i>
        </button>
    @endcan
</div>
