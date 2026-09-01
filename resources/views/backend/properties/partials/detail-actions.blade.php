@php
    $letting = strtolower((string) ($property->letting_current_status ?? ''));
    $vacantLettings = in_array($letting, ['', 'available', 'not available'], true);

    $primary = null;
    if (auth()->user()?->can('create tenancies') && $vacantLettings) {
        $primary = 'tenancy';
    } elseif (Route::has('admin.property_repairs.create') && ! $vacantLettings) {
        $primary = 'repair';
    } elseif (auth()->user()?->can('edit properties')) {
        $primary = 'edit';
    }
@endphp
<div class="pcc-quick-actions" id="pccDetailActions">
    @if($primary === 'tenancy')
        <a href="{{ route('admin.tenancies.create', ['property_id'=>$property->id]) }}" class="pcc-btn-ink">
            Add tenancy
        </a>
    @elseif($primary === 'repair')
        <a href="{{ route('admin.property_repairs.create') }}?property_id={{ $property->id }}" class="pcc-btn-ink">
            Add repair
        </a>
    @elseif($primary === 'edit')
        <a href="{{ route('admin.properties.edit', $property->id) }}" class="pcc-btn-ink">
            Edit
        </a>
    @endif

    <div class="dropdown">
        <button class="pcc-icon-ghost" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More actions">
            <i class="bi bi-three-dots"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end pcc-overflow-menu">
            @can('edit properties')
                @if($primary !== 'edit')
                    <li><a class="dropdown-item" href="{{ route('admin.properties.edit', $property->id) }}">Edit property</a></li>
                @endif
            @endcan
            @can('create tenancies')
                @if($primary !== 'tenancy')
                    <li><a class="dropdown-item" href="{{ route('admin.tenancies.create', ['property_id'=>$property->id]) }}">Add tenancy</a></li>
                @endif
            @endcan
            @if($primary !== 'repair')
                <li><a class="dropdown-item" href="{{ route('admin.property_repairs.create') }}?property_id={{ $property->id }}">Add repair</a></li>
            @endif
            <li><a class="dropdown-item" href="{{ route('admin.properties.brochure', $property->id) }}" target="_blank">Brochure</a></li>
            @can('delete properties')
                <li><hr class="dropdown-divider"></li>
                <li>
                    <button type="button" class="dropdown-item text-danger"
                        onclick="confirmModal('{{ route('admin.properties.delete', $property->id) }}', responseHandler)">
                        Delete property
                    </button>
                </li>
            @endcan
        </ul>
    </div>
</div>
