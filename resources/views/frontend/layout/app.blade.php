<!DOCTYPE html>
<html>
<head>
    <title>Frontend</title>
    <link href="asset/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="asset/css/toastr.min.css">

</head>
<body>
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

    @include('frontend.partials.navbar')

    <div class="container mt-4">
        @yield('content')
    </div>
    <script src="asset/js/jquery.min.js"></script>
    <script src="asset/js/bootstrap.bundle.min.js"></script>
    <script src="asset/js/toastr.min.js"></script>
    @yield('page.scripts') 
    <script>
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

</script>

</body>
</html>
