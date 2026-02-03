
@if (!isset($editMode) || !$editMode)
    <!-- Display View Mode -->

    <!-- Button to add new bank details -->
    <div class="mb-3">
        <button class="btn btn-outline-primary addForm" data-form="bank_detail" data-id="{{ $user->id }}">Add New Bank Detail</button>
    </div>

    @if($bankDetails->isEmpty())
        <div class="alert alert-warning">No bank details found for this user.</div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Account Name</th>
                        <th>Account Number</th>
                        <th>Sort Code</th>
                        <th>Bank Name</th>
                        <th>Swift Code</th>
                        <th>Status</th>
                        <th>Default</th>
                        <th>Last Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bankDetails as $index => $bank)
                        @php
                            $isActive = $bank->is_active ? booleanToString($bank->is_active, 'Active', 'Inactive') : '';
                            $isPrimary = $bank->is_primary ? booleanToString($bank->is_primary, 'Primary', 'Secondary') : '';
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $bank->account_name }}</td>
                            <td>{{ $bank->account_no }}</td>
                            <td>{{ $bank->sort_code }}</td>
                            <td>{{ $bank->bank_name }}</td>
                            <td>{{ $bank->swift_code }}</td>
                            <td>
                                @if($isActive == 'Active')
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @if($isPrimary == 'Primary')
                                    <span class="badge bg-primary">Primary</span>
                                @else
                                    <span class="badge bg-secondary">Secondary</span>
                                @endif
                            </td>
                            <td>{{ formatDateTime($bank->updated_at) }}</td>
                            <td>
                                <div class="d-flex justify-content-end">
                                    <button class="btn btn-sm btn-outline-info viewBank me-1" data-type="Bank Detail" data-id="1" data-url="{{ route('admin.bank_details.show', $bank->id) }}" title="View Full Bank Detail">
                                        <i class="bi bi-eye"> View</i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger editBank editForm me-1" data-form="bank_detail" data-id="{{ $user->id }}" data-bank-detail-id="{{ $bank->id }}" title="Edit Bank Detail">
                                        <i class="bi bi-pencil">Edit</i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger deleteBank me-1" title="Delete Bank Detail" data-bank-detail-id="{{ $bank->id }}" onclick="confirmModal('{{ url(route('admin.bank_details.delete', $bank->id)) }}', responseHandler)">
                                        <i class="bi bi-trash">Delete</i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

@else

    <form id="addeditBankDetailForm">
        @csrf

        <input type="hidden" name="user_id" value="{{ $user->id }}">
        <input type="hidden" name="form_type" value="bank_detail">
        <input type="hidden" name="bank_detail_id" value="{{ $bankDetail->id ?? '' }}">

        {{-- ACCOUNT NAME --}}
        <div class="mb-3">
            <label for="account_name" class="form-label">Account Name</label>
            <input type="text" name="account_name" id="account_name" class="form-control"
                value="{{ old('account_name', $bankDetail->account_name ?? '') }}" required>
        </div>

        {{-- ACCOUNT NUMBER --}}
        <div class="mb-3">
            <label for="account_no" class="form-label">Account Number</label>
            <input type="text" name="account_no" id="account_no" class="form-control"
                value="{{ old('account_no', $bankDetail->account_no ?? '') }}" required>
        </div>

        {{-- SORT CODE --}}
        <div class="mb-3">
            <label for="sort_code" class="form-label">Sort Code</label>
            <input type="text" name="sort_code" id="sort_code" class="form-control"
                value="{{ old('sort_code', $bankDetail->sort_code ?? '') }}" required>
        </div>

        {{-- BANK NAME --}}
        <div class="mb-3">
            <label for="bank_name" class="form-label">Bank Name</label>
            <input type="text" name="bank_name" id="bank_name" class="form-control"
                value="{{ old('bank_name', $bankDetail->bank_name ?? '') }}" required>
        </div>

        {{-- SWIFT CODE --}}
        <div class="mb-3">
            <label for="swift_code" class="form-label">Swift Code</label>
            <input type="text" name="swift_code" id="swift_code" class="form-control"
                value="{{ old('swift_code', $bankDetail->swift_code ?? '') }}">
        </div>

        {{-- IS ACTIVE --}}
        <div class="mb-3">
            <label>
                <input type="checkbox" name="is_active" value="1"
                    {{ old('is_active', $bankDetail->is_active ?? true) ? 'checked' : '' }}>
                Active
            </label>
        </div>

        {{-- IS PRIMARY --}}
        <div class="mb-3">
            <label>
                <input type="checkbox" name="is_primary" value="1"
                    {{ old('is_primary', $bankDetail->is_primary ?? false) ? 'checked' : '' }}>
                Make Primary
            </label>
        </div>

        <button type="submit" class="btn btn_secondary mt-3 float-end">Save Changes</button>
    </form>

@endif
