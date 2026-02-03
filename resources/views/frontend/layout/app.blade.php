<!DOCTYPE html>
<html>

<head>
    <title>Backend Dashboard</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
	<meta name="app-url" content="{{ getBaseURL() }}">
	<meta name="file-base-url" content="{{ getFileBaseURL() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Use asset() to generate the correct URL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="{{ asset('asset/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('asset/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
    {{-- <link href="{{ asset('asset/css/toastr.min.css') }}" rel="stylesheet"> --}}
    <link href="{{ asset('asset/backend/css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Tagify CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet">




    {{-- <link href="{{ asset('asset/backend/css/aiz-core.css') }}" rel="stylesheet"> --}}
    {{-- <link href="{{ asset('asset/backend/css/media.css') }}" rel="stylesheet"> --}}
    {{-- <link href="{{ asset('asset/backend/css/vendors.css') }}" rel="stylesheet"> --}}
</head>
<body>
    <header  id="header" class="">
        <div class="container-fluid">
            <div class="top_header tw-ml-1">

                @include('backend.partials.navbar')
            </div>
        </div>
    </header>
    <!-- @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif -->
    <!-- resources/views/layouts/backend.blade.php -->
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

    {{-- @include('frontend.partials.navbar') --}}

    <div class="container mt-4">
        @yield('content')
    </div>
    <script src="asset/js/jquery.min.js"></script>
    <script src="asset/js/bootstrap.bundle.min.js"></script>
    <script src="asset/js/toastr.min.js"></script>
    @yield('page.scripts') 
    <script>
        @foreach (session('flash_notification', collect())->toArray() as $message)
            AIZ.plugins.notify('{{ $message['level'] }}', '{{ $message['message'] }}');
        @endforeach
    </script>
    {{-- <script>
$(document).ready(function() {
    @if(session('error'))
        toastr.error("{{ session('error') }}", "Login Failed", {
            "closeButton": true,
            "progressBar": true
        });
    @endif
    toastr.options = {
    showHideTransition: "plain",
    closeButton: true,
    newestOnTop: false,
    progressBar: true,
    positionClass: "toast-top-right",
    preventDuplicates: false,
    onclick: null,
    showDuration: "300",
    hideDuration: "500",
    timeOut: "7000",
    extendedTimeOut: "1000",
    showEasing: "swing",
    hideEasing: "linear",
    showMethod: "fadeIn",
    hideMethod: "fadeOut",
};
});

</script> --}}


</body>
</html>
