@php
    $propertyTypeFilter = request('property_type');
    $statusFilter = request('status');
@endphp

@if($properties->count() > 0)
    @foreach ($properties as $property)
        @php
            $addressParts = array_filter([
                $property['prop_name'],
                $property['line_1'],
                $property['line_2'],
                $property['city'],
                $property['country'],
                $property['postcode'],
            ]);
            $fullAddress = implode(', ', $addressParts);

            $salesStatus = $property['sales_current_status'] ?? '';
            $lettingStatus = $property['letting_current_status'] ?? '';
            $displayStatus = $salesStatus ?: $lettingStatus;

            $actions = [];
            if (auth()->user()?->hasAnyRole(['Super Admin', 'Property Manager', 'Estate Agent', 'Staff'])) {
                $actions[] = [
                    'label' => 'View',
                    'url' => route('admin.properties.index', ['property_id' => $property['id'], 'tabname' => 'Property']),
                    'class' => 'btn-outline-primary',
                    'icon' => 'bi bi-eye',
                ];
            }
            if (auth()->user()?->can('edit properties')) {
                $actions[] = [
                    'label' => 'Edit',
                    'url' => route('admin.properties.edit', $property['id']),
                    'class' => 'btn-outline-secondary',
                    'icon' => 'bi bi-pencil',
                ];
            }
            if (auth()->user()?->can('delete properties')) {
                $actions[] = [
                    'label' => 'Delete',
                    'url' => 'javascript:void(0)',
                    'class' => 'btn-outline-danger',
                    'icon' => 'bi bi-trash',
                    'onclick' => "confirmModal('" . route('admin.properties.delete', $property['id']) . "', responseHandler)",
                ];
            }
        @endphp
        <x-backend.property-card
            class="property-card mb-2"
            propertyName="{{ $fullAddress }}"
            bed="{{ $property['bedroom'] ?? '' }}"
            bath="{{ $property['bathroom'] ?? '' }}"
            floor="{{ $property['floor'] ?? '' }}"
            living="{{ $property['reception'] ?? '' }}"
            type="{{ $property['property_type'] ?? '' }}"
            available="{{ $property['available_from'] ?? '' }}"
            price="{{ $property['price'] ?? '' }}"
            lettingPrice="{{ $property['letting_price'] ?? '' }}"
            cardStyle=""
            propertyId="{{ $property['id'] }}"
            brochure-url="{{ route('admin.properties.brochure', $property['id']) }}"
            important-note="{{ $property['imp_notes'] ?? '' }}"
            status="{{ $displayStatus }}"
            :actions="$actions"
        />
    @endforeach

    @if($properties->hasPages())
        <div class="pagination-wrapper p-3">
            {{ $properties->links() }}
        </div>
    @endif
@elseif(request()->has('search') || request()->filled('property_type') || request()->filled('status'))
    <div class="text-center py-5">
        <i class="bi bi-search display-4 text-muted"></i>
        <h5 class="mt-3 text-muted">No properties found</h5>
        <p class="text-muted">Try adjusting your search or filters.</p>
        <a href="{{ route('admin.properties.index') }}" class="btn btn-outline-primary btn-sm">Clear filters</a>
    </div>
@else
    <div class="text-center py-5">
        <i class="bi bi-building display-4 text-muted"></i>
        <h5 class="mt-3 text-muted">No properties yet</h5>
        <p class="text-muted">Get started by adding your first property.</p>
        @can('create properties')
            <a href="{{ route('admin.properties.quick') }}" class="btn btn-primary btn-sm">Add First Property</a>
        @endcan
    </div>
@endif
