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


<div class="bank_detail-update-ajax" id="section-bank_detail-{{ $userId }}">
    @include("backend.users.popup_forms.bank_detail", ['user' => $user, 'bankDetails' => $bankDetails])
    {{-- @include("backend.users.popup_forms.bank_detail", ['user' => $user, 'bankDetails' => $bankDetails, 'bankDetail' => $bankDetail]) --}}
</div>

