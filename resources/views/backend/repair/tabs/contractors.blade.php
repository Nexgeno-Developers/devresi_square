<div class="d-flex justify-content-end gap-2 mb-3">
    <a href="{{ route('admin.property_repairs.scope_of_work_pdf', $repairIssue->id) }}" target="_blank"
        class="btn btn-sm btn-outline-secondary">
        Scope of Work PDF
    </a>
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#quoteRequestModal">
        send quote request
    </button>
    <button type="button" class="btn btn-sm btn-outline-danger editRepairForm"
        data-id="{{ $repairIssue->id }}"
        data-form="contractor_assign"
        data-title="Edit Contractor Assignment">
        <i class="fas fa-edit"></i> Edit Contractors
    </button>
</div>

<div class="accordion" id="repairContractorsTabAccordion">
    <div class="accordion-item">
        <h2 class="accordion-header" id="heading-contractor-assignments">
            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse-contractor-assignments" aria-expanded="true"
                aria-controls="collapse-contractor-assignments">
                Contractor Assignments
            </button>
        </h2>
        <div id="collapse-contractor-assignments" class="accordion-collapse collapse show"
            aria-labelledby="heading-contractor-assignments">
            <div class="accordion-body">
                @if($repairIssue->repairIssueContractorAssignments->count())
                    <div class="d-flex flex-column gap-3">
                        @foreach($repairIssue->repairIssueContractorAssignments as $index => $assignment)
                            <div class="border p-3 rounded">
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <p class="mb-1 fs-6"><span class="fw-semibold text-primary">#{{ $index + 1 }}</span></p>
                                        <p class="mb-1"><strong>Contractor:</strong> {{ $assignment->contractor->name ?? 'N/A' }}</p>
                                        <p class="mb-1"><strong>Email:</strong> {{ $assignment->contractor->email ?? 'N/A' }}</p>
                                        <p class="mb-1"><strong>Status:</strong> {{ $assignment->status }}</p>
                                    </div>
                                    @if((int) $repairIssue->final_contractor_id === (int) $assignment->contractor_id)
                                        <div>
                                            <span class="badge bg-success">Final Contractor</span>
                                        </div>
                                    @endif
                                </div>

                                <hr>
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <strong>Estimated Price</strong>
                                        <div>{{ $assignment->cost_price !== null ? getPoundSymbol() . number_format($assignment->cost_price, 2) : '-' }}</div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <strong>Preferred Availability</strong>
                                        <div>{{ optional($assignment->contractor_preferred_availability)->format('d M Y, H:i') ?: '-' }}</div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <strong>Submitted</strong>
                                        <div>{{ optional($assignment->quote_submitted_at)->format('d M Y, H:i') ?: '-' }}</div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <strong>Consultant/Engineer</strong>
                                        <div>{{ $assignment->consultant_name ?: '-' }}</div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <strong>Contact Number</strong>
                                        <div>{{ $assignment->consultant_phone ?: '-' }}</div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <strong>Job Dates</strong>
                                        <div>
                                            {{ optional($assignment->tentative_start_date)->format('d M Y') ?: '-' }}
                                            -
                                            {{ optional($assignment->tentative_end_date)->format('d M Y') ?: '-' }}
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <strong>Additional Availability</strong>
                                        @if(! empty($assignment->contractor_availability_options))
                                            <ul class="mb-0">
                                                @foreach($assignment->contractor_availability_options as $availability)
                                                    <li>{{ $availability }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <div>-</div>
                                        @endif
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <strong>Quote Document</strong>
                                        <div>
                                            @if($assignment->quote_attachment)
                                                <a href="{{ uploaded_asset($assignment->quote_attachment) }}" target="_blank">View File</a>
                                            @else
                                                -
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <strong>Notes/Remark</strong>
                                        <div>{{ $assignment->quote_notes ?: '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p>No contractor assignments available.</p>
                @endif
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="quoteRequestModal" tabindex="-1" aria-labelledby="quoteRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="quoteRequestForm" action="{{ route('admin.property_repairs.quote_requests.store', $repairIssue->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="quoteRequestModalLabel">send quote request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <a href="{{ route('admin.property_repairs.scope_of_work_pdf', $repairIssue->id) }}" target="_blank"
                            class="btn btn-sm btn-outline-secondary">
                            Preview Scope of Work PDF
                        </a>
                    </div>

                    <div class="mb-3 quote-contractor-select-wrapper">
                        <label for="quote-contractor-select" class="form-label d-block">Select Contractor(s)</label>
                        <select id="quote-contractor-select" name="contractor_ids[]"
                            class="form-select quote-contractor-select"
                            multiple
                            data-placeholder="Select contractor(s)"
                            style="width: 100%;">
                            @foreach($contractors as $contractor)
                                <option value="{{ $contractor->id }}">{{ $contractor->name }} - {{ $contractor->email }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Select one or more existing contractors, or add a new contractor below.</div>
                        <div class="small text-success mt-2 d-none" id="quoteSelectedContractors"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                        <h6 class="mb-0">Need a new contractor?</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="toggleAddContractorBtn">
                            Add Contractor
                        </button>
                    </div>

                    <div class="row mt-3 d-none" id="quoteAddContractorFields">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">First Name</label>
                            <input type="text" name="new_contractor[first_name]" class="form-control">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="new_contractor[last_name]" class="form-control">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Email</label>
                            <input type="email" name="new_contractor[email]" class="form-control quote-new-contractor-email">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Phone</label>
                            <input type="text" name="new_contractor[phone]" class="form-control">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Address Line 1</label>
                            <input type="text" name="new_contractor[address_line_1]" class="form-control">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Address Line 2</label>
                            <input type="text" name="new_contractor[address_line_2]" class="form-control">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">City</label>
                            <input type="text" name="new_contractor[city]" class="form-control">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Postcode</label>
                            <input type="text" name="new_contractor[postcode]" class="form-control">
                        </div>
                        <div class="col-12 text-end mt-2">
                            <button type="button" class="btn btn-sm btn-primary" id="saveQuoteContractorBtn" disabled>
                                Save Contractor
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="sendQuoteRequestBtn" disabled>send quote request</button>
                </div>
            </form>
        </div>
    </div>
</div>
