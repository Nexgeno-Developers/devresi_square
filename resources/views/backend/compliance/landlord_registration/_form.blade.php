<form id='landloard_registration'>
    @csrf
    @php
        $expiryDateLabel = 'Lease Expiry Date';
        $uploadHeading = 'Land Registry';
        $uploadLabel = 'Upload Document';
    @endphp
    @include('backend.compliance._common_field')

</form>
