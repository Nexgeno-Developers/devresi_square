@if (!isset($editMode) || !$editMode)
    <div class="mb-3">
        @if($repairIssue->repairIssueContractorAssignments->count())
            <div class="d-flex flex-column gap-3">
                @foreach($repairIssue->repairIssueContractorAssignments as $index => $assignment)
                    <div class="border p-3 rounded">
                        <p class="mb-1 fs-6">
                            <span class="fw-semibold text-primary">#{{ $index + 1 }}</span>
                        </p>
                        <div class="row mb-2">
                            <div class="col-sm-3 fw-bold">Contractor</div>
                            <div class="col-sm-9">{{ $assignment->contractor->name ?? 'N/A' }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-3 fw-bold">Cost Price</div>
                            <div class="col-sm-9">{{ $assignment->cost_price !== null ? getPoundSymbol() . number_format($assignment->cost_price, 2) : '-' }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-3 fw-bold">Preferred Availability</div>
                            <div class="col-sm-9">{{ optional($assignment->contractor_preferred_availability)->format('d M Y, H:i') ?: '-' }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-3 fw-bold">Status</div>
                            <div class="col-sm-9">{{ $assignment->status }}</div>
                        </div>
                        <div class="row">
                            <div class="col-sm-3 fw-bold">Quote Document</div>
                            <div class="col-sm-9">
                                @if($assignment->quote_attachment)
                                    <a href="{{ uploaded_asset($assignment->quote_attachment) }}" target="_blank">View File</a>
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p>No contractor assignments available.</p>
        @endif
    </div>
@else
    @php
        $assignments = ($contractorAssignments ?? $repairIssue->repairIssueContractorAssignments);
        if ($assignments->isEmpty()) {
            $assignments = collect([(object) [
                'id' => null,
                'contractor_id' => null,
                'cost_price' => null,
                'quote_attachment' => null,
                'contractor_preferred_availability' => null,
            ]]);
        }
    @endphp

    <form id="contractorAssignForm">
        @csrf
        <input type="hidden" name="repair_id" value="{{ $repairIssue->id }}">
        <input type="hidden" name="form_type" value="contractor_assign">

        <h6 class="mb-3">Contractor Assignment</h6>
        <div id="contractorAssignmentRows" class="d-flex flex-column gap-3">
            @foreach($assignments as $index => $assignment)
                @php
                    $assignedUser = ($assignedContractorUsers ?? collect())->get($assignment->contractor_id);
                    $assignedLabel = $assignedUser
                        ? trim(($assignedUser->name ?? 'Contractor #' . $assignedUser->id) . ($assignedUser->email ? ' - ' . $assignedUser->email : ''))
                        : ($assignment->contractor_id ? 'Contractor #' . $assignment->contractor_id : '');
                @endphp
                <div class="border rounded p-3 contractor-assignment-row">
                    <input type="hidden" name="contractor_assignments[{{ $index }}][id]" value="{{ $assignment->id }}">
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Contractor</label>
                            @if($assignment->contractor_id)
                                <input type="hidden" name="contractor_assignments[{{ $index }}][contractor_id]" value="{{ $assignment->contractor_id }}">
                            @endif
                            <select name="{{ $assignment->contractor_id ? '' : 'contractor_assignments[' . $index . '][contractor_id]' }}"
                                class="form-control {{ $assignment->contractor_id ? '' : 'select2' }} contractor-select"
                                data-current-contractor-id="{{ $assignment->contractor_id }}"
                                {{ $assignment->contractor_id ? 'disabled' : '' }}>
                                <option value="">Select Contractor</option>
                                @foreach(($contractors ?? collect()) as $contractor)
                                    <option value="{{ $contractor->id }}" {{ (int) $assignment->contractor_id === (int) $contractor->id ? 'selected' : '' }}>
                                        {{ $contractor->name }}{{ $contractor->email ? ' - ' . $contractor->email : '' }}
                                    </option>
                                @endforeach
                            </select>
                            {{-- @if($assignedUser)
                                <div class="form-text current-contractor-label">
                                    Current: {{ $assignedUser->name }}{{ $assignedUser->email ? ' - ' . $assignedUser->email : '' }}
                                </div>
                            @endif --}}
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Estimated Price</label>
                            <input type="number" step="0.01" name="contractor_assignments[{{ $index }}][cost_price]"
                                class="form-control" value="{{ $assignment->cost_price }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Preferred Availability</label>
                            <input type="datetime-local" name="contractor_assignments[{{ $index }}][contractor_preferred_availability]"
                                class="form-control"
                                value="{{ optional($assignment->contractor_preferred_availability)->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="col-md-10 mb-3">
                            <label class="form-label">Quote Attachment</label>
                            <div class="input-group" data-toggle="aizuploader" data-type="document">
                                <div class="input-group-prepend">
                                    <div class="input-group-text bg-soft-secondary font-weight-medium">Browse</div>
                                </div>
                                <div class="form-control file-amount">Choose File</div>
                                <input type="hidden" name="contractor_assignments[{{ $index }}][quote_attachment]"
                                    value="{{ $assignment->quote_attachment }}" class="selected-files">
                            </div>
                            <div class="file-preview box sm"></div>
                        </div>
                        <div class="col-md-2 mb-3 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger remove-contractor-row">Remove</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- <button type="button" class="btn btn-sm btn-outline-primary mt-3" id="addContractorAssignmentRow">Add Contractor</button> --}}

        <div class="modal-footer px-0">
            <button type="button" class="btn btn_outline_secondary" onclick="closeModel();" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn_secondary">Save Changes</button>
        </div>
    </form>
@endif
