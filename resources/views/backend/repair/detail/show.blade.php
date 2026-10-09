@php
    $detailCategory = getRepairCategoryDetails($repairIssue->repair_category_id);
    if ($detailCategory === 'Staging General Repair') {
        $detailCategory = 'General repair';
    }
    $detailStatus = rs_repair_status_label($repairIssue->status);
    $detailPill = match ($detailStatus) {
        'In progress' => 'lw-pill-warn',
        'Work Completed', 'Closed' => 'lw-pill-ok',
        default => 'lw-pill-idle',
    };
@endphp
<div class="lw-repair-summary">
    <div class="lw-repair-summary-row">
        <h2>{{ \Illuminate\Support\Str::limit($repairIssue->description ?: 'Repair', 140) }}</h2>
        <span class="lw-pill {{ $detailPill }}">{{ $detailStatus }}</span>
    </div>
    <div class="lw-repair-meta">
        @if($detailCategory)
            <span>{{ $detailCategory }}</span>
        @endif
        <span>{{ $repairIssue->priority ? ucfirst($repairIssue->priority) : 'Medium' }}</span>
        <span>Reported {{ rs_date($repairIssue->created_at) }}</span>
        @if($repairIssue->reference_number)
            <span class="lw-repair-ref">{{ $repairIssue->reference_number }}</span>
        @endif
    </div>
</div>
<div class="lw-repair-detail-actions">
    <a href="{{ route('admin.property_repairs.edit', $repairIssue->id) }}" class="btn lw-btn-secondary btn-sm">Edit</a>
    @unless(is_landlord_plan_user())
        <a class="btn lw-btn-ghost btn-sm" href="{{ route('admin.property_repairs.workorder.invoice', $repairIssue->id) }}">{{ $repairIssue->workOrder ? 'Work order' : 'Create work order' }}</a>
    @endunless
    <button type="button" id="toggleAll" class="btn lw-btn-ghost btn-sm ms-auto">Expand all</button>
</div>

@include('backend.repair.partials.priority-sla')

<div class="accordion" id="propertyAccordion">

    @php
        $formSections = [
            ['key' => 'property_details', 'title' => 'Home', 'order' => 1],
            ['key' => 'property_issue_details', 'title' => 'The issue', 'order' => 2],
            ['key' => 'manager_assign', 'title' => 'Managers', 'order' => 3],
            ['key' => 'marketplace', 'title' => 'Contractors', 'order' => 4],
            ['key' => 'repair_history', 'title' => 'History', 'order' => 6],
            ['key' => 'work_order_detail', 'title' => 'Work Order Detail', 'order' => 7],
            ['key' => 'invoice_detail', 'title' => 'Invoice Detail', 'order' => 8],
        ];

        if (is_landlord_plan_user()) {
            $formSections = array_values(array_filter($formSections, function ($section) {
                return in_array($section['key'], [
                    'property_details',
                    'property_issue_details',
                    'marketplace',
                    'repair_history',
                ], true);
            }));
        }

        // Sort by 'order' key
        usort($formSections, function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });
    @endphp

    @foreach($formSections as $section)
        @php
            $formType = $section['key'];
            $title = $section['title'];
        @endphp

        <div class="accordion-item">
            <h2 class="accordion-header" id="heading-{{ $formType }}">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapse-{{ $formType }}" aria-expanded="false"
                    aria-controls="collapse-{{ $formType }}">
                    {{ $title }}
                </button>
            </h2>
            <div id="collapse-{{ $formType }}" class="accordion-collapse collapse"
                aria-labelledby="heading-{{ $formType }}">
                {{-- <button class="btn btn-primary float-end editForm" data-form="{{ $formType }}"
                    data-id="{{ $repairIssue->id }}">
                    Edit
                </button> --}}
                <div class="accordion-body" id="section-{{ $formType }}-{{ $repairIssue->id }}">
                    @include("backend.repair.popup_forms.$formType", ['repairIssue' => $repairIssue])
                </div>
            </div>
        </div>
    @endforeach

    <!-- Features -->
    {{-- <div class="accordion-item">
        <h2 class="accordion-header" id="headingFeatures">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFeatures"
                aria-expanded="true" aria-controls="collapseFeatures">
                Features
            </button>
        </h2>
        <div id="collapseFeatures" class="accordion-collapse collapse show" aria-labelledby="headingFeatures">
            <div class="accordion-body">
                <ul>
                    @foreach($allFeatures as $feature)
                    <li>{{ $feature }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div> --}}

</div>

@push('extra.scripts')


@endpush