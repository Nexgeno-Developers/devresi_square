{{-- Contractor portal detail view --}}
{{-- Sections shown: Property Details, Property Issue Details, Manager Assignments, Repair History, Work Order Detail --}}
{{-- Sections hidden: Contractor Assignments, Final Contractor, Invoice Detail --}}
{{-- Buttons hidden: Edit, Create Work Order & Invoice --}}

<div class="d-flex justify-content-end mb-3">
    <a id="toggleAll" class="pointer underline" style="cursor:pointer;">Collapse All</a>
</div>

<div class="accordion" id="contractorRepairAccordion">

    @php
        $sections = [
            ['key' => 'property_details',       'title' => 'Property Details'],
            ['key' => 'property_issue_details',  'title' => 'Property Issue Details'],
            ['key' => 'manager_assign',          'title' => 'Manager Assignments'],
            ['key' => 'repair_history',          'title' => 'Repair History'],
            ['key' => 'work_order_detail',       'title' => 'Work Order Detail'],
        ];
    @endphp

    @foreach($sections as $section)
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading-{{ $section['key'] }}">
                <button class="accordion-button" type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapse-{{ $section['key'] }}"
                        aria-expanded="true"
                        aria-controls="collapse-{{ $section['key'] }}">
                    {{ $section['title'] }}
                </button>
            </h2>
            <div id="collapse-{{ $section['key'] }}"
                 class="accordion-collapse collapse show"
                 aria-labelledby="heading-{{ $section['key'] }}">
                <div class="accordion-body">
                    @include('backend.repair.popup_forms.' . $section['key'], [
                        'repairIssue' => $repairIssue,
                        'editMode'    => false,
                    ])
                </div>
            </div>
        </div>
    @endforeach

</div>

@push('extra.scripts')
<script>
    // Re-init the Expand/Collapse toggle for dynamically loaded content
    let isExpanded = true;
    $(document).off('click', '#toggleAll').on('click', '#toggleAll', function () {
        if (isExpanded) {
            $('#contractorRepairAccordion .accordion-collapse').collapse('hide');
            $(this).text('Expand All');
        } else {
            $('#contractorRepairAccordion .accordion-collapse').collapse('show');
            $(this).text('Collapse All');
        }
        isExpanded = !isExpanded;
    });
</script>
@endpush
