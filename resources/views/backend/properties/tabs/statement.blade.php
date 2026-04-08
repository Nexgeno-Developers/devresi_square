@php
    $preset = $filters['preset'] ?? 'this_month';
    $dateFrom = $filters['date_from'] ?? '';
    $dateTo = $filters['date_to'] ?? '';
    $warnings = $statement['warnings'] ?? [];
@endphp

<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-end">
        <form method="GET" class="row g-2 flex-grow-1" id="propertyStatementFilterForm">
            <input type="hidden" name="property_id" value="{{ $property->id }}">
            <input type="hidden" name="tabname" value="Statement">
            <div class="col-md-2 col-6">
                <label class="form-label">Preset</label>
                <select name="preset" class="form-select" id="presetSelectProperty">
                    <option value="this_month" @selected($preset==='this_month')>This Month</option>
                    <option value="last_month" @selected($preset==='last_month')>Last Month</option>
                    <option value="ytd" @selected($preset==='ytd')>Year to Date</option>
                    <option value="custom" @selected($preset==='custom')>Custom</option>
                </select>
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}" id="dateFromProperty">
            </div>
            <div class="col-md-2 col-6">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}" id="dateToProperty">
            </div>
            <div class="col-md-2 col-6 d-flex align-items-end">
                <button class="btn btn-primary w-100" type="submit">Run</button>
            </div>
            <div class="col-md-2 col-12 d-flex align-items-end">
                <a class="btn btn-outline-secondary w-100"
                   href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}">Export CSV</a>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Function to update date fields based on preset
    function updateDatesFromPreset(preset) {
        const today = new Date();
        let fromDate, toDate;

        switch(preset) {
            case 'this_month':
                fromDate = new Date(today.getFullYear(), today.getMonth(), 1);
                toDate = today;
                break;
            case 'last_month':
                fromDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                toDate = new Date(today.getFullYear(), today.getMonth(), 0); // Last day of last month
                break;
            case 'ytd':
                fromDate = new Date(today.getFullYear(), 0, 1); // Jan 1st
                toDate = today;
                break;
            case 'custom':
                // Don't change dates for custom
                return;
        }

        // Format dates as YYYY-MM-DD
        const formatDate = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        $('#dateFromProperty').val(formatDate(fromDate));
        $('#dateToProperty').val(formatDate(toDate));
    }

    // When preset changes, update date fields
    $('#presetSelectProperty').on('change', function() {
        const preset = $(this).val();
        if (preset !== 'custom') {
            updateDatesFromPreset(preset);
        }
    });

    // When dates are manually changed, switch to custom
    $('#dateFromProperty, #dateToProperty').on('change', function() {
        $('#presetSelectProperty').val('custom');
    });

    // Handle form submission via AJAX to stay in tab context
    $('#propertyStatementFilterForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        var url = '{{ route('admin.properties.index') }}?' + formData;
        
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $('.pv_content_detail').html(response.content);
                window.history.pushState(null, null, url);
            },
            error: function(xhr, status, error) {
                console.error('Error loading statement:', error);
            }
        });
    });

    // Initialize dates on page load based on current preset
    const currentPreset = $('#presetSelectProperty').val();
    if (currentPreset && currentPreset !== 'custom') {
        updateDatesFromPreset(currentPreset);
    }
});
</script>

@if(!empty($warnings))
    <div class="alert alert-warning">
        @foreach($warnings as $warn)
            <div>{{ $warn }}</div>
        @endforeach
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Beginning Balance</div>
                <div class="h5 mb-0">{{ number_format($statement['opening'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Invoiced Amount</div>
                <div class="h5 mb-0">{{ number_format($statement['summary']['invoiced'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Amount Paid</div>
                <div class="h5 mb-0">{{ number_format($statement['summary']['paid'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Balance Due</div>
                <div class="h5 mb-0">{{ number_format($statement['summary']['balance_due'] ?? $statement['closing'], 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Statement for Property #{{ $property->id }}</h5>
            <div class="text-muted small">{{ $statement['from'] }} @if($statement['to']) - {{ $statement['to'] }} @endif</div>
        </div>

        @if(($statement['lines'] ?? collect())->isEmpty())
            <div class="alert alert-info mb-0">No transactions found for the selected period.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Details</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                            <th class="text-end">Delta</th>
                            <th class="text-end">Running</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5"><strong>Opening Balance</strong></td>
                            <td class="text-end">{{ number_format($statement['opening'], 2) }}</td>
                        </tr>
                        @foreach($statement['lines'] as $line)
                            <tr>
                                <td>{{ $line['date'] }}</td>
                                <td>
                                    {{ $line['memo'] ?? '-' }}
                                    <div class="text-muted small">
                                        {{ $line['account_code'] ?? '' }} {{ $line['account_name'] ?? '' }}
                                        @if(!empty($line['source_type']))
                                            ({{ $line['source_type'] }} {{ $line['source_id'] ?? '' }})
                                        @endif
                                    </div>
                                </td>
                                <td class="text-end">{{ number_format($line['debit'], 2) }}</td>
                                <td class="text-end">{{ number_format($line['credit'], 2) }}</td>
                                <td class="text-end">{{ number_format($line['delta'], 2) }}</td>
                                <td class="text-end">{{ number_format($line['running'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
