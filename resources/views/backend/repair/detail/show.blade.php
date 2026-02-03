<!-- Button to Collapse/Expand All -->
<div class="d-flex justify-content-end gap-3 mb-3">
    <a href="{{ route('admin.property_repairs.edit', $repairIssue->id) }}" class="btn btn-outline-danger btn-sm">
        <i class="fas fa-edit"></i> Edit
    </a>

    <a class="btn btn-outline-primary btn-sm"
        href="{{ route('admin.repair.workorder.invoice', $repairIssue->id) }}">{{ $repairIssue->workOrder ? 'Edit Work Order & Invoice' : 'Create Work Order & Invoice' }}</a>
    <div class="d-flex justify-content-end mb-3">
        <a id="toggleAll" class="pointer underline">Collapse All</a>
    </div>
</div>

<div class="accordion" id="propertyAccordion">

    @php
        $formSections = [
            ['key' => 'property_details', 'title' => 'Property Details', 'order' => 1],
            ['key' => 'property_issue_details', 'title' => 'Property Issue Details', 'order' => 2],
            ['key' => 'manager_assign', 'title' => 'Manager Assignments', 'order' => 3],
            ['key' => 'contractor_assign', 'title' => 'Contractor Assignments', 'order' => 4],
            ['key' => 'final_contractor', 'title' => 'Final Contractor', 'order' => 5],
            ['key' => 'repair_history', 'title' => 'Repair History', 'order' => 6],
            ['key' => 'work_order_detail', 'title' => 'Work Order Detail', 'order' => 7],
            ['key' => 'invoice_detail', 'title' => 'Invoice Detail', 'order' => 8],
            // Add more sections with order values as needed
        ];

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
                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapse-{{ $formType }}" aria-expanded="true"
                    aria-controls="collapse-{{ $formType }}">
                    {{ $title }}
                </button>
            </h2>
            <div id="collapse-{{ $formType }}" class="accordion-collapse collapse show"
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