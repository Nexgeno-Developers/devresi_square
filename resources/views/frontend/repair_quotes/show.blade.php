<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Submit Repair Quote</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-4">
        <div class="mx-auto bg-white border rounded p-4" style="max-width: 920px;">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <h1 class="h4 mb-0">Submit Repair Quote</h1>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="mb-4">
                <p class="mb-1"><strong>Repair:</strong> {{ $assignment->repairIssue->reference_number }}</p>
                <p class="mb-1"><strong>Property:</strong> {{ getPropertyDetails($assignment->repairIssue->property_id, ['prop_name', 'line_1', 'city', 'postcode']) }}</p>
                <p class="mb-0"><strong>Issue:</strong> {{ getRepairCategoryDetails($assignment->repairIssue->repair_category_id) }}</p>
                @if($assignment->repairIssue->repair_navigation)
                    <p class="mb-1 mt-2"><strong>Navigation:</strong> {!! getFormattedRepairNavigation($assignment->repairIssue->repair_navigation) !!}</p>
                @endif
                <p class="mb-1 mt-2">
                    <strong>Description:</strong>
                    {!! nl2br(e($assignment->repairIssue->description)) !!}
                </p>
                @if($assignment->repairIssue->access_details)
                    <p class="mb-1 mt-2">
                        <strong>Access Details:</strong>
                        {!! nl2br(e($assignment->repairIssue->access_details)) !!}
                    </p>
                @endif
            </div>

            <form method="POST" action="{{ $submitUrl }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Estimated Price</label>
                        <input type="number" step="0.01" min="0" name="estimated_price" class="form-control" required
                            value="{{ old('estimated_price', $assignment->cost_price) }}">
                        @error('estimated_price')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Preferred Availability</label>
                        <input type="datetime-local" name="contractor_preferred_availability" class="form-control"
                            value="{{ old('contractor_preferred_availability', optional($assignment->contractor_preferred_availability)->format('Y-m-d\TH:i')) }}">
                        @error('contractor_preferred_availability')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Additional Availability Options</label>
                    <textarea name="availability" rows="3" class="form-control" placeholder="One option per line">{{ old('availability', implode("\n", $assignment->contractor_availability_options ?? [])) }}</textarea>
                    @error('availability')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Name of Consultant/Engineer Visiting Property</label>
                        <input type="text" name="consultant_name" class="form-control" required
                            value="{{ old('consultant_name', $assignment->consultant_name) }}">
                        @error('consultant_name')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Consultant/Engineer Contact Number</label>
                        <input type="text" name="consultant_phone" class="form-control" required
                            value="{{ old('consultant_phone', $assignment->consultant_phone) }}">
                        @error('consultant_phone')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tentative Initiation Date of Job</label>
                        <input type="date" name="tentative_start_date" class="form-control"
                            value="{{ old('tentative_start_date', optional($assignment->tentative_start_date)->format('Y-m-d')) }}">
                        @error('tentative_start_date')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tentative Completion Date of Job</label>
                        <input type="date" name="tentative_end_date" class="form-control"
                            value="{{ old('tentative_end_date', optional($assignment->tentative_end_date)->format('Y-m-d')) }}">
                        @error('tentative_end_date')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes/Remark</label>
                    <textarea name="quote_notes" rows="4" class="form-control">{{ old('quote_notes', $assignment->quote_notes) }}</textarea>
                    @error('quote_notes')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Attach PDF Quotation</label>
                    <input type="file" name="quote_attachment" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    @if($assignment->quote_attachment)
                        <a href="{{ uploaded_asset($assignment->quote_attachment) }}" target="_blank" class="d-inline-block mt-2">View submitted quotation</a>
                    @endif
                    @error('quote_attachment')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn btn-primary">Submit Quote</button>
            </form>
        </div>
    </main>
</body>
</html>
