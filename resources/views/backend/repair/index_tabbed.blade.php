@extends('backend.layout.app')

@section('content')
    <style>
        .repair-row>td:hover {
            color: #d83434;
        }

        .repair-row.selected>td {
            background-color: #6c6c6c;
            color: #fff;
        }

        .repair-row>td {
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .repair-tabbed-filters {
            padding: 12px;
        }

        .repair-tabbed-filters form {
            width: 100%;
        }

        .repair-tabbed-filters .input-group,
        .repair-tabbed-filters .form-select {
            width: 100%;
        }

        .spinner-overlay {
            align-items: center;
            display: flex;
            justify-content: center;
            min-height: 240px;
        }

        #quoteRequestModal .quote-contractor-select {
            display: block;
            max-width: 100%;
            width: 100% !important;
        }

        #quoteRequestModal .select2-container {
            max-width: 100%;
            width: 100% !important;
        }

        #quoteRequestModal .select2-selection--multiple {
            min-height: 42px;
        }

        #quoteRequestModal .modal-body,
        #quoteRequestModal .modal-footer {
            overflow-x: hidden;
        }

        #quoteRequestModal .modal-body > *,
        #quoteRequestModal .modal-footer > * {
            max-width: 100%;
        }

        #quoteRequestModal .btn {
            white-space: normal;
        }

        #largeModalScrollable .repair-manager-select-wrap,
        #largeModalScrollable .repair-manager-select,
        #largeModalScrollable .select2-container {
            max-width: 100%;
            width: 100% !important;
        }

        #largeModalScrollable .repair-manager-select {
            min-height: 42px;
        }

        #largeModalScrollable .select2-selection--multiple {
            min-height: 42px;
            padding-bottom: 4px;
        }

        #largeModalScrollable .select2-search__field {
            min-width: 180px;
        }
    </style>

    <div class="row g-0 view_properties">
        <div class="col-lg-5 col-12">
            <div class="property_list_wrapper pt-lg-4 pt-2">
                <div class="pv_wrapper">
                    <div class="pv_header">
                        <div class="row">
                            <div class="col-12">
                                <div class="pv_title">Repair Issues</div>
                            </div>
                        </div>
                    </div>

                    <div class="repair-tabbed-filters">
                        <form method="GET" action="{{ route('admin.property_repairs.index_tabbed') }}" id="tabbed-filter-form">
                            <div class="d-flex gap-2 flex-wrap">
                                <div class="input-group flex-grow-1">
                                    <input type="text" name="search" id="tabbedRepairSearch" class="form-control"
                                        placeholder="Search by Property Name or ID" value="{{ request('search') }}">
                                    <button class="btn btn-primary" type="submit">Search</button>
                                </div>
                                <select name="status" id="tabbedRepairStatus" class="form-select">
                                    <option value="">-- Filter by Status --</option>
                                    @foreach(['Pending', 'Reported', 'Under Process', 'Work Completed', 'Invoice Received', 'Invoice Paid', 'Closed'] as $status)
                                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                            {{ $status }}
                                        </option>
                                    @endforeach
                                </select>
                                <a href="{{ route('admin.property_repairs.index_tabbed') }}" class="btn btn-secondary">Reset Filters</a>
                            </div>
                        </form>
                    </div>

                    <div class="pv_card_wrapper" id="repairListContainer">
                        @include('backend.repair.list.tabbed-cards', [
                            'repairIssues' => $repairIssues,
                            'selectedRepairId' => $selectedRepairId,
                        ])
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 col-12 property_detail_wrapper hide_this pt-lg-4 pt-0">
            <div class="pv_detail_wrapper">
                <div class="pv_tabs repair_tabs">
                    <ul class="nav">
                        @foreach ($tabs as $tab)
                            <li class="{{ $tab['key'] === $tabName ? 'active' : '' }}">
                                <a href="#{{ $tab['key'] }}"
                                    data-tab-name="{{ $tab['key'] }}"
                                    class="tab-link {{ $tab['key'] === $tabName ? 'active' : '' }}">
                                    {{ $tab['name'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="pv_detail_content">
                    <div class="pv_detail_header">
                        <div class="pv_main_title" id="repair-tab-title">
                            {{ collect($tabs)->firstWhere('key', $tabName)['name'] ?? 'Issue' }} Detail
                        </div>
                        <div class="pvdh_btns_wrapper d-flex gap-3"></div>
                    </div>
                    <div class="pv_content_detail_wrapper">
                        <div class="pv_content_detail">
                            {!! $content !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('backend.components.modal')
@endsection

@include('backend.partials.assets.select2')

@section('page.scripts')
    <script>
        $(function () {
            const tabbedIndexUrl = "{{ route('admin.property_repairs.index_tabbed') }}";
            const workOrderStoreUrl = "{{ route('admin.work_orders.store') }}";
            const repairLoadFormUrl = "{{ route('admin.property_repairs.loadForm') }}";
            const repairSaveFormUrl = "{{ route('admin.property_repairs.saveForm') }}";
            const propertyAjaxUrl = "{{ route('admin.properties.ajax') }}";
            const quoteContractorStoreUrl = "{{ route('admin.property_repairs.quote_contractors.store') }}";
            const jobSubTypeUrl = "{{ route('admin.job_types.getSubCategories', '__ID__') }}";
            const repairSubCategoryUrl = "{{ route('admin.property_repairs.getSubCategories', '__ID__') }}";
            const usersByPropertyUrl = "{{ route('admin.getUsersByProperty', ['propertyId' => 'PROPERTYID', 'roleId' => 'ROLEID']) }}";
            const tenantsByPropertyUrl = "{{ route('admin.getTenantsByProperty', ['propertyId' => 'PROPERTYID']) }}";
            const poundSymbol = @json(getPoundSymbol());
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            let currentRepairId = "{{ $selectedRepairId }}";
            let currentTab = "{{ $tabName }}";
            let searchTimer = null;
            let quoteContractorObserver = null;

            function tabLabel(tabName) {
                const labels = {
                    'issue': 'Issue',
                    'property-manager': 'Property Manager',
                    'contractors': 'Request a quote',
                    'work-order': 'Work Order'
                };

                return labels[tabName] || 'Issue';
            }

            function currentFilters() {
                return {
                    search: $('#tabbedRepairSearch').val(),
                    status: $('#tabbedRepairStatus').val()
                };
            }

            function buildUrl(params) {
                const url = new URL(tabbedIndexUrl, window.location.origin);
                Object.keys(params).forEach(function (key) {
                    if (params[key] !== undefined && params[key] !== null && params[key] !== '') {
                        url.searchParams.set(key, params[key]);
                    }
                });
                return url.toString();
            }

            function setActiveTab(tabName) {
                $('.tab-link').removeClass('active');
                $('.tab-link[data-tab-name="' + tabName + '"]').addClass('active');
                $('.repair_tabs li').removeClass('active');
                $('.tab-link[data-tab-name="' + tabName + '"]').closest('li').addClass('active');
                $('#repair-tab-title').text(tabLabel(tabName) + ' Detail');
            }

            function setSelectedRepair(repairId) {
                $('.repair-row').removeClass('selected');
                $('.repair-row[data-repair-id="' + repairId + '"]').addClass('selected');
            }

            function loadTabContent(repairId, tabName, pushState = true) {
                if (!repairId) {
                    $('.pv_content_detail').html('<div class="alert alert-info m-3">Select a repair item to view details.</div>');
                    return;
                }

                currentRepairId = repairId;
                currentTab = tabName;
                setActiveTab(tabName);
                setSelectedRepair(repairId);

                $('.pv_content_detail').html(`
                    <div class="spinner-overlay">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);

                const url = buildUrl(Object.assign({}, currentFilters(), {
                    repair_id: repairId,
                    tabname: tabName
                }));

                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (response) {
                        $('.pv_content_detail').html(response.content);
                        currentRepairId = response.repair_id || repairId;
                        currentTab = response.tabname || tabName;
                        setActiveTab(currentTab);
                        setSelectedRepair(currentRepairId);
                        initialiseWorkOrderTab();
                        initialiseTabPlugins();

                        if (pushState) {
                            window.history.pushState(null, '', buildUrl(Object.assign({}, currentFilters(), {
                                repair_id: currentRepairId,
                                tabname: currentTab
                            })));
                        }
                    },
                    error: function () {
                        $('.pv_content_detail').html('<div class="alert alert-danger m-3">Failed to load repair tab.</div>');
                    }
                });
            }

            function loadRepairList(url = null) {
                const requestUrl = url || tabbedIndexUrl;
                const data = Object.assign({}, currentFilters(), {
                    list_only: 1,
                    repair_id: currentRepairId,
                    tabname: currentTab
                });

                $.ajax({
                    url: requestUrl,
                    type: 'GET',
                    data: data,
                    dataType: 'json',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (response) {
                        $('#repairListContainer').html(response.html);
                        const selectedRepairId = response.selectedRepairId || $('#repairListContainer .repair-row').first().data('repair-id');
                        if (selectedRepairId) {
                            loadTabContent(selectedRepairId, currentTab);
                        } else {
                            currentRepairId = null;
                            $('.pv_content_detail').html('<div class="alert alert-info m-3">No repair issues found.</div>');
                        }
                    },
                    error: function () {
                        AIZ.plugins.notify('danger', 'Failed to load repair issues.');
                    }
                });
            }

            function loadJobSubTypes(jobTypeId, selectedSubTypeId = null) {
                if (!jobTypeId || !$('#jobSubTypeSelect').length) {
                    return;
                }

                $('#jobSubTypeSelect').html('<option value="">Loading...</option>');

                $.ajax({
                    url: jobSubTypeUrl.replace('__ID__', jobTypeId),
                    type: 'GET',
                    success: function (response) {
                        $('#jobSubTypeSelect').html('<option disabled value="">Select Job Sub Type</option>');
                        $.each(response, function (key, value) {
                            const selected = selectedSubTypeId == value.id ? 'selected' : '';
                            $('#jobSubTypeSelect').append(`<option value="${value.id}" ${selected}>${value.name}</option>`);
                        });
                    },
                    error: function () {
                        $('#jobSubTypeSelect').html('<option disabled value="">No Sub Types Found</option>');
                    }
                });
            }

            function updateStatusOptions(invoiceTo) {
                if (!$('#statusSelect').length) return;

                let statusOptions = [];
                if (invoiceTo === 'Company') {
                    statusOptions = [
                        { value: 'Raised', text: 'Raised' },
                        { value: 'Sent to Contractor', text: 'Sent to Contractor' },
                        { value: 'Completed', text: 'Completed' },
                        { value: 'Cancelled', text: 'Cancelled' }
                    ];
                } else if (invoiceTo === 'Landlord' || invoiceTo === 'Tenant') {
                    statusOptions = [
                        { value: 'Raised', text: 'Raised' },
                        { value: 'Sent to Contractor', text: 'Sent to Contractor' },
                        { value: 'Work Completed - Invoice Received From Contractor', text: 'Work Completed - Invoice Received From Contractor' },
                        { value: 'Work Completed - Invoice Generated to Landlord', text: 'Work Completed - Invoice Generated to Landlord (If landlord paying)' },
                        { value: 'Work Completed - Invoice Generated to Tenant', text: 'Work Completed - Invoice Generated to Tenant (If tenant paying)' },
                        { value: 'Completed - Invoice Generated', text: 'Completed - Invoice Generated (to Landlord-Tenant)' },
                        { value: 'Work Completed - Invoice Paid To Contractor', text: 'Work Completed - Invoice Paid To Contractor' },
                        { value: 'Cancelled', text: 'Cancelled' }
                    ];
                }

                const $statusDropdown = $('#statusSelect');
                $statusDropdown.html('');
                $.each(statusOptions, function (index, option) {
                    $statusDropdown.append(new Option(option.text, option.value));
                });

                const existingStatus = $('#existingStatus').val();
                if (existingStatus) {
                    $statusDropdown.val(existingStatus);
                }
            }

            function loadInvoiceToDetails(invoiceTo, propertyId) {
                if (!propertyId || !$('#invoiceToContainer').length) return;

                let endpoint = null;
                if (invoiceTo === 'Landlord') {
                    endpoint = usersByPropertyUrl.replace('PROPERTYID', propertyId).replace('ROLEID', 5);
                } else if (invoiceTo === 'Tenant') {
                    endpoint = tenantsByPropertyUrl.replace('PROPERTYID', propertyId);
                } else {
                    $('#invoiceToContainer').html('');
                    $('#userDetails').hide();
                    return;
                }

                $('#invoiceToContainer').html('<select class="form-control"><option>Loading...</option></select>');

                $.ajax({
                    url: endpoint,
                    type: 'GET',
                    success: function (response) {
                        let dropdown = '<div class="form-group">';
                        dropdown += '<label class="form-label">Select ' + invoiceTo + '</label>';
                        dropdown += '<select name="user_id" id="invoiceToSelect" class="form-control">';
                        dropdown += '<option value="">Select ' + invoiceTo + '</option>';
                        $.each(response, function (index, item) {
                            dropdown += `<option value="${item.id}" data-email="${item.email}" data-phone="${item.phone}" data-name="${item.name}" data-address="${item.full_address || ''}">${item.name}</option>`;
                        });
                        dropdown += '</select></div>';
                        $('#invoiceToContainer').html(dropdown);
                    },
                    error: function () {
                        $('#invoiceToContainer').html('<p class="text-danger">Unable to load details</p>');
                    }
                });
            }

            function calculateWorkOrderTotals() {
                let subtotal = 0;
                let taxTotal = 0;

                $('#workorder-items tr').each(function () {
                    const unitPrice = parseFloat($(this).find('.unit-price').val()) || 0;
                    const quantity = parseInt($(this).find('.quantity').val()) || 1;
                    const taxRate = parseFloat($(this).find('.tax-rate').val()) || 0;
                    const rowSubtotal = unitPrice * quantity;
                    const taxAmount = (rowSubtotal * taxRate) / 100;

                    $(this).find('.tax-amount').val(taxAmount.toFixed(2));
                    $(this).find('.total-price').val(rowSubtotal.toFixed(2));
                    subtotal += rowSubtotal;
                    taxTotal += taxAmount;
                });

                $('#subtotal').val(subtotal.toFixed(2));
                $('#tax-total').val(taxTotal.toFixed(2));
                $('#grand-total').val((subtotal + taxTotal).toFixed(2));
            }

            function initialiseWorkOrderTab() {
                if (!$('#workOrderForm').length) return;

                const existingJobTypeId = $('#jobTypeSelect').val();
                const existingJobSubTypeId = $('#jobSubTypeSelect').data('existing-sub-type') || '';
                if (existingJobTypeId) {
                    loadJobSubTypes(existingJobTypeId, existingJobSubTypeId);
                }

                const selectedInvoiceTo = $("input[name='invoice_to']:checked").val();
                updateStatusOptions(selectedInvoiceTo);
                loadInvoiceToDetails(selectedInvoiceTo, $('#property_id').val());
                calculateWorkOrderTotals();
                updateFinalContractorDetails(false);
            }

            function updateFinalContractorDetails(markUnsaved = true) {
                const $select = $('#finalContractorAssignmentSelect');
                if (!$select.length) return;

                const $selected = $select.find('option:selected');
                const selectedValue = $select.val();
                const $panel = $('#finalContractorDetailPanel');

                if (!selectedValue) {
                    $panel.addClass('d-none');
                    if (markUnsaved) {
                        $('#sendWorkOrderBtn, #downloadWorkOrderPdfBtn').prop('disabled', true);
                    }
                    return;
                }

                const costPrice = $selected.data('cost-price');
                const startDate = $selected.data('start-date');
                const endDate = $selected.data('end-date');

                $('#finalContractorName').text($selected.data('contractor-name') || 'N/A');
                $('#finalContractorEmail').text($selected.data('contractor-email') || 'N/A');
                $('#finalContractorPhone').text($selected.data('contractor-phone') || 'N/A');
                $('#finalContractorCost').text(costPrice !== undefined && costPrice !== '' ? poundSymbol + Number(costPrice).toFixed(2) : '-');
                $('#finalContractorAvailability').text($selected.data('availability') || '-');
                $('#finalContractorNotes').text($selected.data('notes') || '-');
                $panel.removeClass('d-none');

                if (markUnsaved && costPrice !== undefined && costPrice !== '') {
                    const $firstUnitPrice = $('#workorder-items tr:first .unit-price');
                    if ($firstUnitPrice.length) {
                        $firstUnitPrice.val(Number(costPrice).toFixed(2));
                    }
                }

                if (markUnsaved && startDate) {
                    $('input[name="tentative_start_date"]').val(startDate);
                }

                if (markUnsaved && endDate) {
                    $('input[name="tentative_end_date"]').val(endDate);
                }

                if (markUnsaved) {
                    calculateWorkOrderTotals();
                }

                if (markUnsaved) {
                    $('#sendWorkOrderBtn, #downloadWorkOrderPdfBtn').prop('disabled', true);
                    AIZ.plugins.notify('warning', 'Save the work order to apply this final contractor.');
                }
            }

            function initialiseTabPlugins() {
                if (! $.fn.select2) return;

                $('.select2').not('.quote-contractor-select').each(function () {
                    const $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) return;

                    $select.select2({
                        width: '100%',
                        placeholder: $select.data('placeholder') || 'Select option(s)',
                        dropdownParent: $select.closest('.modal-content').length
                            ? $select.closest('.modal-content')
                            : $(document.body)
                    });
                });
            }

            function initialiseRepairEditForm(formType) {
                initialiseTabPlugins();

                if (formType === 'property_issue_details') {
                    if (typeof AIZ !== 'undefined' && AIZ.uploader) {
                        AIZ.uploader.previewGenerate();
                    }

                    initialiseIssuePropertySelector();
                    initialiseIssueTenantSelector();
                    initialiseIssueCategorySelector();
                }

                if (formType === 'contractor_assign') {
                    if (typeof AIZ !== 'undefined' && AIZ.uploader) {
                        AIZ.uploader.previewGenerate();
                    }
                    initialiseContractorAssignmentSelects();
                    reindexContractorRows();
                    syncFinalContractorOptions();
                }
            }

            function initialiseContractorAssignmentSelects() {
                $('.contractor-select').each(function () {
                    const $select = $(this);
                    const contractorId = String($select.data('current-contractor-id') || '');

                    if (!contractorId) return;

                    if (!$select.find('option[value="' + contractorId + '"]').length) {
                        const label = $select.closest('.contractor-assignment-row').find('.current-contractor-label').text().replace(/^Current:\s*/, '').trim();
                        if (label) {
                            $select.append(new Option(label, contractorId, true, true));
                        }
                    }

                    $select.val(contractorId).trigger('change.select2');
                });
            }

            function initialiseIssuePropertySelector() {
                $('#changeIssuePropertyBtn').off('click').on('click', function () {
                    $('#issuePropertySelectorWrap').toggleClass('d-none');
                });

                if (! $.fn.select2 || !$('#issue_property_id').length) return;

                $('#issue_property_id').select2({
                    width: '100%',
                    dropdownParent: $('#largeModalScrollable .modal-content'),
                    ajax: {
                        url: propertyAjaxUrl,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term };
                        },
                        processResults: function (data) {
                            return { results: data.results || [] };
                        }
                    },
                    minimumInputLength: 2
                });
            }

            function initialiseIssueTenantSelector() {
                const propertyId = $('#issue_property_id').val() || $('#selected_property').val();
                const selectedTenantId = $('#selected_tenant').val();

                function fetchTenants(propertyIdToFetch) {
                    if (!propertyIdToFetch || !$('#tenant-select').length) return;

                    $('#tenant-select').html('<option value="">Loading...</option>');
                    $.ajax({
                        url: "{{ route('admin.getTenantsByProperty', ['propertyId' => 'PROPERTYID']) }}".replace('PROPERTYID', propertyIdToFetch),
                        type: 'GET',
                        success: function (response) {
                            let options = '<option value="">-- Select Tenant --</option>';
                            $.each(response, function (index, tenant) {
                                const selected = String(tenant.id) === String(selectedTenantId) ? 'selected' : '';
                                options += `<option value="${tenant.id}" data-email="${tenant.email || ''}" data-phone="${tenant.phone || ''}" ${selected}>${tenant.name}</option>`;
                            });
                            $('#tenant-select').html(options).trigger('change');
                        },
                        error: function () {
                            $('#tenant-select').html('<option value="">Unable to load tenants</option>');
                        }
                    });
                }

                fetchTenants(propertyId);

                $('#issue_property_id').off('change.issueTenant').on('change.issueTenant', function () {
                    $('#selected_property').val($(this).val());
                    fetchTenants($(this).val());
                });
            }

            function initialiseIssueCategorySelector() {
                $('#change-category-btn').off('click').on('click', function () {
                    $('#category-display-card').addClass('d-none');
                    $('#category-edit-card').removeClass('d-none');
                    $('#cancel-category-btn').removeClass('d-none');
                });

                $('#cancel-category-btn').off('click').on('click', function () {
                    $('#category-edit-card').addClass('d-none');
                    $('#category-display-card').removeClass('d-none');
                });

                $('.category-select').off('change').on('change', function () {
                    const $select = $(this);
                    const selectedId = $select.val();
                    const level = parseInt($select.data('level'), 10);
                    const selectedCategories = {};

                    $('.category-select').each(function () {
                        const value = $(this).val();
                        const itemLevel = $(this).data('level');
                        if (value && itemLevel <= level) {
                            selectedCategories['level_' + itemLevel] = value;
                        }
                    });

                    $('#selected_categories').val(JSON.stringify(selectedCategories));
                    $('#last_selected_category').val(selectedId);

                    $('.category-level').filter(function () {
                        return parseInt($(this).data('level'), 10) > level;
                    }).hide().find('select').html('<option value="">-- Select --</option>');

                    if (!selectedId) return;

                    $.ajax({
                        url: repairSubCategoryUrl.replace('__ID__', selectedId),
                        type: 'GET',
                        success: function (response) {
                            if (!Array.isArray(response) || response.length === 0) return;

                            const nextLevel = level + 1;
                            const $next = $('.category-level[data-level="' + nextLevel + '"]');
                            let options = '<option value="">-- Select --</option>';
                            response.forEach(function (category) {
                                options += `<option value="${category.id}">${category.name}</option>`;
                            });
                            $next.show().find('select').html(options);
                        }
                    });
                });
            }

            function reindexContractorRows() {
                $('#contractorAssignmentRows .contractor-assignment-row').each(function (index) {
                    $(this).find('input, select').each(function () {
                        const name = $(this).attr('name');
                        if (name) {
                            $(this).attr('name', name.replace(/contractor_assignments\[\d+\]/, 'contractor_assignments[' + index + ']'));
                        }
                    });
                });
            }

            function syncFinalContractorOptions() {
                const $finalSelect = $('#final_contractor_id');
                if (!$finalSelect.length) return;

                const currentValue = String($finalSelect.val() || '');
                const contractors = new Map();

                $('.contractor-select').each(function () {
                    const value = String($(this).val() || '');
                    if (!value || contractors.has(value)) return;

                    const text = $(this).find('option:selected').text();
                    contractors.set(value, text);
                });

                if ($finalSelect.hasClass('select2-hidden-accessible')) {
                    $finalSelect.select2('destroy');
                }

                $finalSelect.empty().append(new Option('No final contractor', '', false, currentValue === ''));

                contractors.forEach(function (text, value) {
                    $finalSelect.append(new Option(text, value, false, value === currentValue));
                });

                if (currentValue && !contractors.has(currentValue)) {
                    $finalSelect.val('');
                }

                initialiseTabPlugins();
            }

            function destroyQuoteContractorSelect() {
                $('.quote-contractor-select').each(function () {
                    const $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) {
                        try {
                            $select.select2('destroy');
                        } catch (error) {
                            $select.removeClass('select2-hidden-accessible');
                        }
                    }

                    $select.nextAll('.select2-container').remove();
                });
            }

            function initialiseQuoteContractorSelect(attempt = 0) {
                if (! $.fn.select2) return;

                $('.quote-contractor-select').each(function () {
                    const $select = $(this);
                    const optionCount = $select.find('option').length;

                    if (!optionCount && attempt < 10) {
                        setTimeout(function () {
                            initialiseQuoteContractorSelect(attempt + 1);
                        }, 100);
                        return;
                    }

                    const selectedValues = ($select.val() || []).map(String);
                    const contractorOptions = $select.find('option').map(function () {
                        return {
                            id: String(this.value),
                            text: $(this).text(),
                            selected: selectedValues.includes(String(this.value))
                        };
                    }).get();

                    destroyQuoteContractorSelect();
                    $select.select2({
                        width: '100%',
                        closeOnSelect: false,
                        minimumResultsForSearch: 0,
                        data: contractorOptions,
                        matcher: function (params, data) {
                            if ($.trim(params.term || '') === '') {
                                return data;
                            }

                            if ((data.text || '').toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                                return data;
                            }

                            return null;
                        },
                        placeholder: $select.data('placeholder') || 'Select contractor(s)',
                        dropdownParent: $('#quoteRequestModal .modal-content').length
                            ? $('#quoteRequestModal .modal-content')
                            : $(document.body)
                    });

                    $select.val(selectedValues).trigger('change.select2');
                });
            }

            function observeQuoteContractorOptions() {
                if (quoteContractorObserver) {
                    quoteContractorObserver.disconnect();
                    quoteContractorObserver = null;
                }

                const select = document.querySelector('.quote-contractor-select');
                if (!select) return;

                quoteContractorObserver = new MutationObserver(function () {
                    initialiseQuoteContractorSelect();
                    updateQuoteRequestSubmitState();
                });

                quoteContractorObserver.observe(select, { childList: true });
            }

            function updateQuoteRequestSubmitState() {
                const hasSelectedContractor = $('.quote-contractor-select').val()?.length > 0;
                const hasNewContractorEmail = $.trim($('.quote-new-contractor-email').val() || '') !== '';

                $('#sendQuoteRequestBtn').prop('disabled', !hasSelectedContractor);
                $('#saveQuoteContractorBtn').prop('disabled', !hasNewContractorEmail);

                const selectedLabels = $('.quote-contractor-select option:selected').map(function () {
                    return $(this).text();
                }).get();

                $('#quoteSelectedContractors')
                    .toggleClass('d-none', selectedLabels.length === 0)
                    .text(selectedLabels.length ? 'Selected: ' + selectedLabels.join(', ') : '');
            }

            function selectSavedQuoteContractor(contractor) {
                const $select = $('.quote-contractor-select');
                const contractorId = String(contractor.id);
                let selectedValues = ($select.val() || []).map(String);

                const $existingOption = $select.find('option[value="' + contractorId + '"]');
                if ($existingOption.length) {
                    $existingOption.text(contractor.label).prop('selected', true);
                } else {
                    $select.append(new Option(contractor.label, contractorId, true, true));
                }

                selectedValues.push(contractorId);
                selectedValues = Array.from(new Set(selectedValues));
                $select.val(selectedValues);

                initialiseQuoteContractorSelect();
                $select.trigger('change');
            }

            $(document).on('click', '.repair-row', function (e) {
                if ($(e.target).closest('.dropdown, a, button, form').length) return;
                loadTabContent($(this).data('repair-id'), currentTab);
            });

            $(document).on('click', '.tab-link', function (e) {
                e.preventDefault();
                loadTabContent(currentRepairId, $(this).data('tab-name'));
            });

            $('#tabbed-filter-form').on('submit', function (e) {
                e.preventDefault();
                loadRepairList();
            });

            $('#tabbedRepairStatus').on('change', function () {
                loadRepairList();
            });

            $('#tabbedRepairSearch').on('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(loadRepairList, 500);
            });

            $(document).on('click', '#repairListContainer .pagination a', function (e) {
                e.preventDefault();
                loadRepairList($(this).attr('href'));
            });

            $(document).on('click', '.editRepairForm', function () {
                const formType = $(this).data('form');
                const repairId = $(this).data('id');
                const modalTitle = $(this).data('title') || 'Edit Details';

                $('#largeModalScrollable .modal-title').text(modalTitle);
                $('#largeModalScrollable .modal-body').html('Loading...');

                $.ajax({
                    url: repairLoadFormUrl,
                    type: 'GET',
                    data: { form_type: formType, repair_id: repairId },
                    success: function (response) {
                        $('#largeModalScrollable .modal-body').html(response.form_html);
                        $('#largeModalScrollable').modal('show');
                        initialiseRepairEditForm(formType);
                    },
                    error: function (error) {
                        const message = error.responseJSON?.message || 'Failed to load edit form.';
                        AIZ.plugins.notify('danger', message);
                    }
                });
            });

            $(document).on('submit', '#largeModalScrollable form', function (e) {
                e.preventDefault();

                const $form = $(this);
                const formType = $form.find('input[name="form_type"]').val();

                $.ajax({
                    url: repairSaveFormUrl,
                    type: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    success: function () {
                        $('#largeModalScrollable').modal('hide');
                        AIZ.plugins.notify('success', 'Form updated successfully');
                        loadTabContent(currentRepairId, currentTab, false);
                    },
                    error: function (error) {
                        const message = error.responseJSON?.message || 'An error occurred while saving the form.';
                        AIZ.plugins.notify('danger', message);
                    }
                });
            });

            $(document).on('click', '#addContractorAssignmentRow', function () {
                const $firstRow = $('#contractorAssignmentRows .contractor-assignment-row:first');
                const $newRow = $firstRow.clone();

                $newRow.find('.select2-container').remove();
                $newRow.find('input[type="hidden"][name*="[contractor_id]"]').remove();
                $newRow.find('select')
                    .prop('disabled', false)
                    .addClass('select2')
                    .removeClass('select2-hidden-accessible')
                    .removeAttr('data-select2-id aria-hidden tabindex')
                    .removeAttr('data-current-contractor-id')
                    .attr('name', 'contractor_assignments[0][contractor_id]')
                    .val('');
                $newRow.find('.current-contractor-label').remove();
                $newRow.find('input[type="hidden"]').val('');
                $newRow.find('input[type="number"], input[type="datetime-local"]').val('');
                $newRow.find('.file-preview').empty();
                $('#contractorAssignmentRows').append($newRow);
                reindexContractorRows();
                initialiseTabPlugins();
                syncFinalContractorOptions();
            });

            $(document).on('click', '.remove-contractor-row', function () {
                if ($('#contractorAssignmentRows .contractor-assignment-row').length === 1) {
                    $(this).closest('.contractor-assignment-row').find('input, select').val('');
                    syncFinalContractorOptions();
                    return;
                }

                $(this).closest('.contractor-assignment-row').remove();
                reindexContractorRows();
                syncFinalContractorOptions();
            });

            $(document).on('change', '.contractor-select', function () {
                syncFinalContractorOptions();
            });

            $(document).on('click', '#toggleWorkOrderEdit', function () {
                const editing = $('#workOrderEditView').hasClass('d-none');
                $('#workOrderEditView').toggleClass('d-none', !editing);
                $('#workOrderDetailView').toggleClass('d-none', editing);
                $(this).text(editing ? 'View Work Order' : ($('#workOrderDetailView').length ? 'Edit Work Order' : 'Create Work Order'));
                initialiseWorkOrderTab();
            });

            $(document).on('shown.bs.modal', '#quoteRequestModal', function () {
                setTimeout(function () {
                    initialiseQuoteContractorSelect();
                    observeQuoteContractorOptions();
                    updateQuoteRequestSubmitState();
                }, 100);
            });

            $(document).on('hidden.bs.modal', '#quoteRequestModal', function () {
                if (quoteContractorObserver) {
                    quoteContractorObserver.disconnect();
                    quoteContractorObserver = null;
                }
                destroyQuoteContractorSelect();
            });

            $(document).on('click', '#toggleAddContractorBtn', function () {
                const $fields = $('#quoteAddContractorFields');
                const isHidden = $fields.hasClass('d-none');

                $fields.toggleClass('d-none', !isHidden);
                $(this).text(isHidden ? 'Cancel Add Contractor' : 'Add Contractor');

                if (!isHidden) {
                    $fields.find('input').val('');
                }

                updateQuoteRequestSubmitState();
            });

            $(document).on('change select2:select select2:unselect', '.quote-contractor-select', function () {
                updateQuoteRequestSubmitState();
            });

            $(document).on('input change', '.quote-new-contractor-email', function () {
                updateQuoteRequestSubmitState();
            });

            $(document).on('click', '#saveQuoteContractorBtn', function () {
                const $button = $(this);
                const originalText = $button.text();

                $button.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: quoteContractorStoreUrl,
                    type: 'POST',
                    data: $('#quoteRequestForm').serialize(),
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    success: function (response) {
                        selectSavedQuoteContractor(response.contractor);
                        $('#quoteAddContractorFields').find('input').val('');
                        $('#quoteAddContractorFields').addClass('d-none');
                        $('#toggleAddContractorBtn').text('Add Contractor');
                        AIZ.plugins.notify('success', response.message);
                        updateQuoteRequestSubmitState();
                    },
                    error: function (error) {
                        const message = error.responseJSON?.message || 'Failed to save contractor.';
                        AIZ.plugins.notify('danger', message);
                    },
                    complete: function () {
                        $button.text(originalText);
                        updateQuoteRequestSubmitState();
                    }
                });
            });

            $(document).on('change', '#jobTypeSelect', function () {
                loadJobSubTypes($(this).val());
            });

            $(document).on('change', "input[name='invoice_to']", function () {
                const invoiceTo = $(this).val();
                loadInvoiceToDetails(invoiceTo, $('#property_id').val());
                updateStatusOptions(invoiceTo);
            });

            $(document).on('change', '#invoiceToSelect', function () {
                const selectedOption = $(this).find(':selected');
                $('#userName').text(selectedOption.data('name'));
                $('#userAddress').text(selectedOption.data('address'));
                $('#userPhone').text(selectedOption.data('phone'));
                $('#userEmail').text(selectedOption.data('email'));
                $('#userDetails').toggle(Boolean(selectedOption.data('address')));
            });

            $(document).on('input change', '.unit-price, .quantity, .tax-rate', calculateWorkOrderTotals);

            $(document).on('change', '.tax-name', function () {
                const selectedTaxRate = $(this).find(':selected').data('rate') || 0;
                const $taxRateInput = $(this).closest('tr').find('.tax-rate');
                if (! $taxRateInput.val() || $taxRateInput.val() == 0) {
                    $taxRateInput.val(selectedTaxRate);
                }
                calculateWorkOrderTotals();
            });

            $(document).on('click', '.add-workorder-item', function () {
                const index = $('#workorder-items tr').length;
                const taxOptions = $('#workorder-items .tax-name:first').html() || '';
                const newRow = `
                    <tr>
                        <td><input type="text" name="items[${index}][title]" class="form-control" required></td>
                        <td><input type="text" name="items[${index}][description]" class="form-control" required></td>
                        <td><input type="number" name="items[${index}][unit_price]" class="form-control unit-price" required></td>
                        <td><input type="number" name="items[${index}][quantity]" class="form-control quantity" min="1" value="1" required></td>
                        <td><select name="items[${index}][tax_name]" class="form-control tax-name">${taxOptions}</select></td>
                        <td><input type="number" name="items[${index}][tax_rate]" class="form-control tax-rate" required></td>
                        <td><input type="text" class="form-control tax-amount" readonly></td>
                        <td><input type="text" class="form-control total-price" readonly></td>
                        <td><button type="button" class="btn btn_secondary add-workorder-item"><i class="fa-solid fa-plus"></i></button></td>
                    </tr>
                `;

                $('#workorder-items').append(newRow);
                $('#workorder-items tr').eq(index - 1).find('.add-workorder-item')
                    .removeClass('btn_secondary add-workorder-item')
                    .addClass('btn-danger remove-item')
                    .html('<i class="fa-solid fa-minus"></i>');
                $('#workorder-items tr:last .tax-name').trigger('change');
                calculateWorkOrderTotals();
            });

            $(document).on('click', '.remove-item', function () {
                $(this).closest('tr').remove();
                if ($('#workorder-items tr').length === 1) {
                    $('#workorder-items tr').eq(0).find('.remove-item')
                        .removeClass('btn-danger remove-item')
                        .addClass('btn_secondary add-workorder-item')
                        .html('<i class="fa-solid fa-plus"></i>');
                } else {
                    $('#workorder-items tr:last td:last').html('<button type="button" class="btn btn_secondary add-workorder-item"><i class="fa-solid fa-plus"></i></button>');
                }
                calculateWorkOrderTotals();
            });

            $(document).on('submit', '#workOrderForm', function (e) {
                e.preventDefault();

                const $finalContractorSelect = $('#finalContractorAssignmentSelect');
                if ($finalContractorSelect.length && !$finalContractorSelect.val()) {
                    AIZ.plugins.notify('danger', 'Please select final contractor before saving work order.');
                    $finalContractorSelect.focus();
                    return;
                }

                if (typeof initValidate === 'function') {
                    initValidate('#workOrderForm');
                    $(this).attr('novalidate', 'novalidate');
                    if (!$(this).valid()) return;
                }

                $.ajax({
                    url: workOrderStoreUrl,
                    type: 'POST',
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    success: function (response) {
                        AIZ.plugins.notify('success', response.message);
                        loadTabContent(currentRepairId, 'work-order', false);
                    },
                    error: function (error) {
                        const errorMessage = error.responseJSON?.message || 'Error Creating Work Order';
                        AIZ.plugins.notify('danger', errorMessage);
                    }
                });
            });

            $(document).on('change', '#finalContractorAssignmentSelect', function () {
                updateFinalContractorDetails(true);
            });

            $(document).on('click', '#sendWorkOrderBtn, .send-work-order-btn', function () {
                const $button = $(this);
                const sendUrl = $button.data('send-url');

                if (!sendUrl) {
                    AIZ.plugins.notify('warning', 'Save the work order before sending.');
                    return;
                }

                const originalText = $button.text();
                $button.prop('disabled', true).text('Sending...');

                $.ajax({
                    url: sendUrl,
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    success: function (response) {
                        AIZ.plugins.notify('success', response.message);
                    },
                    error: function (error) {
                        const message = error.responseJSON?.message || 'Failed to send work order.';
                        AIZ.plugins.notify('danger', message);
                    },
                    complete: function () {
                        $button.prop('disabled', false).text(originalText);
                    }
                });
            });

            $(document).on('submit', '#quoteRequestForm', function (e) {
                e.preventDefault();
                const $form = $(this);
                const $submitButton = $('#sendQuoteRequestBtn');
                const originalText = $submitButton.text();

                $submitButton.prop('disabled', true).text('Sending...');

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    success: function (response) {
                        $('#quoteRequestModal').modal('hide');
                        AIZ.plugins.notify('success', response.message);
                        loadTabContent(currentRepairId, 'contractors', false);
                    },
                    error: function (error) {
                        const message = error.responseJSON?.message || 'Failed to send quote request.';
                        AIZ.plugins.notify('danger', message);
                    },
                    complete: function () {
                        $submitButton.text(originalText);
                        updateQuoteRequestSubmitState();
                    }
                });
            });

            initialiseWorkOrderTab();
            initialiseTabPlugins();
        });
    </script>
@endsection
