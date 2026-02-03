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
    <div class="user_detail-update-ajax" id="section-user_detail-{{ $userId }}">
        @include("backend.users.popup_forms.user_detail", ['user' => $user])
    </div>
    </span>
    @can('Edit Contacts')
    <button class="btn btn-outline-danger btn-sm editForm" data-form="{{ 'user_detail' }}" data-id="{{ $userId }}">
        Edit
    </button>
    @endcan
</div>
