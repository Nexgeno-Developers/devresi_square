@extends('backend.layout.app')

@php
    $isPortalUser = $isPortalUser ?? (current_account_id() && app(\App\Services\Saas\PortalAccessService::class)->isPortalUser(auth()->user(), current_account_id()));
    $selectedPropertyId = $property->id ?? ($propertyId ?? null);
    $commandItems = [];
    if (auth()->user()?->can('create properties')) {
        $commandItems[] = [
            'label' => 'Add property',
            'hint' => 'Create',
            'icon' => 'bi-plus-circle',
            'url' => property_create_url(),
        ];
    }
    $commandItems[] = [
        'label' => 'Properties',
        'hint' => 'Go to list',
        'icon' => 'bi-building',
        'url' => route('admin.properties.index'),
    ];
    if (Route::has('admin.tenancies.all')) {
        $commandItems[] = [
            'label' => 'Tenancies',
            'hint' => 'Page',
            'icon' => 'bi-house-door',
            'url' => route('admin.tenancies.all'),
        ];
    }
    if (Route::has('admin.property_repairs.index')) {
        $commandItems[] = [
            'label' => 'Repairs',
            'hint' => 'Page',
            'icon' => 'bi-wrench',
            'url' => route('admin.property_repairs.index'),
        ];
    }
    foreach ($tabs ?? [] as $tab) {
        if ($selectedPropertyId) {
            $commandItems[] = [
                'label' => $tab['name'] . ' tab',
                'hint' => 'Current property',
                'icon' => 'bi-folder',
                'url' => route('admin.properties.index', [
                    'property_id' => $selectedPropertyId,
                    'tabname' => $tab['name'],
                ]),
            ];
        }
    }

    $pccState = [
        'propertyId' => $selectedPropertyId,
        'tabName' => strtolower($tabName ?? 'property'),
        'tabs' => $tabs ?? [],
        'ajaxUrl' => route('admin.properties.index'),
        'tabsAllUrlTemplate' => str_replace('999999', '__ID__', route('admin.properties.tabs-all', ['property' => 999999])),
        'searchUrl' => route('backend.properties.search-ajax'),
        'quickCreateUrl' => property_create_url(),
        'bulkActionUrl' => route('admin.properties.bulk-action'),
        'isPortal' => (bool) $isPortalUser,
        'canCreate' => (bool) auth()->user()?->can('create properties'),
        'canDelete' => (bool) auth()->user()?->can('delete properties'),
    ];
@endphp

@section('content')
<div id="property-control-center" class="pcc-root">
    @include('backend.properties.partials.control-toolbar')

    <div class="pcc-split">
        <aside class="pcc-left-pane" id="pccLeftPane">
            @include('backend.properties.partials.filter-rail')
            @include('backend.properties.partials.bulk-toolbar')
            <div class="pcc-list-column" id="pccListColumn">
                <div class="pcc-list-scroll" id="pccListScroll">
                    @include('backend.properties.partials.property-list')
                </div>
            </div>
            <div class="pcc-list-footer" id="pccListFooter">
                @if(isset($properties) && $properties->hasPages())
                    {{ $properties->links() }}
                @endif
            </div>
        </aside>

        <div class="pcc-resizer" id="pccResizer" title="Drag to resize"></div>

        <main class="pcc-right-pane {{ !empty($property) ? 'pcc-detail-visible' : '' }}" id="pccRightPane">
            <div id="pccDetailShell" @if(empty($property)) hidden @endif>
                <div id="pccDetailHeaderSlot">
                    @if(!empty($property))
                        @include('backend.properties.partials.detail-header', ['property' => $property])
                    @endif
                </div>
                <div id="pccDetailStatsSlot">
                    @if(!empty($property))
                        @include('backend.properties.partials.detail-stats', ['property' => $property])
                    @endif
                </div>
                <div id="pccDetailActionsSlot">
                    @if(!empty($property))
                        @include('backend.properties.partials.detail-actions', ['property' => $property])
                    @endif
                </div>
                <div class="pcc-tab-nav" id="pccTabNav"></div>
                <div class="pcc-tab-content" id="pccTabContent">
                    {!! $content ?? '' !!}
                </div>
            </div>
            <div class="pcc-empty-state" id="pccEmptyState" @if(!empty($property)) hidden @endif>
                <i class="bi bi-building display-4 text-muted"></i>
                <h5 class="mt-3 text-muted">Select a property</h5>
                <p class="text-muted">Choose one from the list to see details.</p>
            </div>
        </main>
    </div>
</div>

@include('backend.properties.partials.command-palette')
@include('backend.components.modal')
@include('backend.events.modal')
@include('backend.partials._calendar_modals')
@endsection

@push('styles')
<link href="{{ asset('asset/backend/css/property-control-center.css') }}?v={{ filemtime(public_path('asset/backend/css/property-control-center.css')) }}" rel="stylesheet">
@endpush

@section('page.scripts')
<script>
    window.pccState = @json($pccState);
    window.pccCommandItems = @json($commandItems);
</script>
<script src="{{ asset('asset/backend/js/property-control-center.js') }}?v={{ filemtime(public_path('asset/backend/js/property-control-center.js')) }}"></script>
@endsection
