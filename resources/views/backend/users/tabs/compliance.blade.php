{{-- Hidden div for user ID --}}
<div id="hidden-user-id" class="d-none" data-user-id="{{ $userId }}">
    @php
        // Debugging the userId
        echo '<pre>';
        echo 'User ID: ';
        var_dump($userId);
        echo '</pre>';
    @endphp
</div>

<div class="property_note">
    <span class="fw-semibold">
    <div class="compliance-update-ajax" id="section-compliance-{{ $userId }}">
        @include("backend.users.popup_forms.compliance", ['user' => $user])
    </div>
    </span>
    <button class="btn btn-outline-danger btn-sm editForm" data-form="{{ 'compliance' }}" data-id="{{ $userId }}">
        Edit
    </button>
</div>