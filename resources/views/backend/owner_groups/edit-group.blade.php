<div id="mainForm">
    <form id="owner-group-form" action="{{ route('admin.owner-groups.update_group', $ownerGroup->id) }}" method="POST">
        @csrf
        <input type="hidden" name="property_id" class="form-control" value="{{ old('property_id', $ownerGroup->property_id) }}">

        <button type="button" class="d-flex float-end btn btn-outline-primary btn-sm" id="addUserBtn">Add New User</button>

        <div class="form-group">
            <label for="user_id">Users</label>
            <select name="user_id[]" id="user_id" class="form-control select2" multiple="multiple" required>
                @foreach($users as $user)
                    <option value="{{ $user->id }}"
                        @if(in_array($user->id, $selectedUsers)) selected @endif>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div id="user-options" class="mt-3" data-current-main="{{ $ownerGroup->ownerGroupUsers->where('is_main', 1)->first()?->user_id ?? '' }}"></div>

        <div class="row">
            <div class="col-md-6 col-12">
                <div class="form-group">
                    <label for="purchased_date">Purchased Date</label>
                    <input type="date" name="purchased_date" id="purchased_date" class="form-control" value="{{ old('purchased_date', $ownerGroup->purchased_date) }}" required>
                </div>
            </div>
            <div class="col-md-6 col-12">
                <div class="form-group">
                    <label for="sold_date">Sold Date</label>
                    <input type="date" name="sold_date" id="sold_date" class="form-control" value="{{ old('sold_date', $ownerGroup->sold_date) }}">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 col-12">
                <div class="form-group">
                    <label for="archived_date">Archived Date</label>
                    <input type="date" name="archived_date" id="archived_date" class="form-control" value="{{ old('archived_date', $ownerGroup->archived_date) }}">
                </div>
            </div>
            <div class="col-md-6 col-12">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control" required>
                        <option value="active" @if($ownerGroup->status == 'active') selected @endif>Active</option>
                        {{-- <option value="inactive" @if($ownerGroup->status == 'inactive') selected @endif>Inactive</option> --}}
                        <option value="archived" @if($ownerGroup->status == 'archived') selected @endif>Archived</option>
                    </select>
                </div>
            </div>
        </div>

        <button type="submit" class="float-end mt-3 btn btn-secondary">Save</button>
    </form>
</div>

<div id="addUserFormContainer" style="display: none;">
    <form id="addUserForm">
        @csrf
        {{-- <input type="hidden" class="form-control" id="category_id" name="category_id" value="1"> --}}
        <input type="hidden" name="role" value="Owner">
        <div class="mb-3">
            <label for="user_name" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="user_name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="user_email" class="form-label">Email</label>
            <input type="email" class="form-control" id="user_email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="user_phone" class="form-label">Phone</label>
            <input type="text" class="form-control" id="user_phone" name="phone" required>
        </div>
        <button type="submit" class="btn btn-primary">Save User</button>
        <button type="button" class="btn btn-secondary" id="backToMainForm">Back</button>
    </form>
</div>

<script>
    initSelect2('.select2');

    // Re-render main owner radio buttons when user selection changes
    function renderMainOwnerOptions() {
        const $userSelect  = $('#user_id');
        const $container   = $('#user-options');
        const selectedIds  = $userSelect.val() || [];
        const defaultMain  = $container.data('current-main') || null;

        // Remember currently checked value before re-render (falls back to server-side default)
        const currentMain  = $('input[name="is_main"]:checked').val() || defaultMain;

        $container.empty();

        if (selectedIds.length === 0) return;

        $container.append('<label class="form-label fw-semibold mb-1">Select Main Owner <span class="text-danger">*</span></label>');

        selectedIds.forEach(function (userId) {
            const userName = $userSelect.find('option[value="' + userId + '"]').text();
            const checked  = (currentMain == userId) ? 'checked' : '';
            $container.append(
                '<div class="form-check">' +
                '<input type="radio" name="is_main" value="' + userId + '" id="is_main_' + userId + '" class="form-check-input" ' + checked + '>' +
                '<label for="is_main_' + userId + '" class="form-check-label">' + userName + '</label>' +
                '</div>'
            );
        });

        // Auto-select if only one owner
        if (selectedIds.length === 1) {
            $container.find('input[type="radio"]').prop('checked', true);
        }
    }

    // Bind to user select changes
    $('#user_id').on('change', function () {
        renderMainOwnerOptions();
    });

    // Run once on load to sync with pre-selected users
    renderMainOwnerOptions();

    // Form submission — validate then submit via AJAX
    $('#owner-group-form').on('submit', function (e) {
        e.preventDefault();

        // Validate main owner selected
        if ($('input[name="is_main"]:checked').length === 0) {
            $('#form-error-summary').remove();
            $('#owner-group-form').prepend(
                '<div id="form-error-summary" class="alert alert-danger mt-2">Please select a main owner.</div>'
            );
            return;
        }

        const $form    = $(this);
        const $btn     = $form.find('button[type="submit"]');
        const formData = new FormData(this);

        $btn.prop('disabled', true).text('Saving...');
        $('#form-error-summary').remove();

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.status) {
                    $('#smallModal').modal('hide');
                    location.reload();
                } else {
                    $btn.prop('disabled', false).text('Save');
                    let html = '<div id="form-error-summary" class="alert alert-danger mt-2"><ul class="mb-0">';
                    if (response.errors) {
                        $.each(response.errors, function (field, messages) {
                            messages.forEach(function (msg) { html += '<li>' + msg + '</li>'; });
                        });
                    } else {
                        html += '<li>' + (response.notification || 'Something went wrong.') + '</li>';
                    }
                    html += '</ul></div>';
                    $('#owner-group-form').prepend(html);
                    $('#smallModal .modal-body').scrollTop(0);
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false).text('Save');
                let html = '<div id="form-error-summary" class="alert alert-danger mt-2"><ul class="mb-0">';
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (field, messages) {
                        messages.forEach(function (msg) { html += '<li>' + msg + '</li>'; });
                    });
                } else {
                    html += '<li>Something went wrong. Please try again.</li>';
                }
                html += '</ul></div>';
                $('#owner-group-form').prepend(html);
                $('#smallModal .modal-body').scrollTop(0);
            }
        });
    });

    function confirmEdit() {
        const currentStatus = "{{ $ownerGroup->status }}";
        const newStatus = document.querySelector('[name="status"]').value;
        if (currentStatus === 'archived' && newStatus === 'active') {
            return confirm("You are activating an archived owner group. Proceed?");
        } else if (currentStatus === 'active' && newStatus === 'archived') {
            return confirm("You are archiving an active owner group. Proceed?");
        }
        return true;
    }
</script>
