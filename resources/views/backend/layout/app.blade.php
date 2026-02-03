<!DOCTYPE html>
<html>

<head>
    <title>{{ get_setting('website_name') }} - Backend Dashboard</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ getBaseURL() }}">
    <meta name="file-base-url" content="{{ getFileBaseURL() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- <title>@yield('meta_title', get_setting('website_name').' | '.get_setting('site_motto'))</title> --}}

    <meta charset="utf-8">
    <meta name="description" content="@yield('meta_description', get_setting('meta_description'))" />
    <meta name="keywords" content="@yield('meta_keywords', get_setting('meta_keywords'))">

    <!-- Favicon -->
    <link rel="icon" href="{{ uploaded_asset(get_setting('site_icon')) }}">
    <title>{{ get_setting('website_name') . ' | ' . get_setting('site_motto') }}</title>

    <!-- Use asset() to generate the correct URL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="{{ asset('asset/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('asset/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
    {{-- <link href="{{ asset('asset/css/toastr.min.css') }}" rel="stylesheet"> --}}
    <link href="{{ asset('asset/backend/css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Tagify CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet">

    {{-- This will render all CSS pushed to the "styles" stack --}}
    @stack('styles')


    <link href="{{ asset('asset/backend/css/aiz-main.css') }}" rel="stylesheet">
    {{-- <link href="{{ asset('asset/backend/css/media.css') }}" rel="stylesheet"> --}}
    {{-- <link href="{{ asset('asset/backend/css/vendors.css') }}" rel="stylesheet"> --}}
</head>

<body class="show-sidebar">
    <header id="header" class="">
        <div class="container-fluid">
            <div class="top_header tw-ml-1">

                @include('backend.partials.navbar')
            </div>
        </div>
    </header>
    <main>
        <div class="main_wrapper">
            @include('backend.partials.aside2')
            {{-- @include('backend.partials.aside') --}}

            <div id="wrapper" class="main_content">
                {{-- <div class="alert_wrapper">
                    <!-- Display success message -->
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <!-- Display error message -->
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Display validation errors -->
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div> --}}

                @yield('content')
            </div>
        </div>
    </main>
    @yield('modal')
    {{-- Include the scripts partial to push these scripts to the 'scripts' stack --}}
    @include('backend.partials.assets.scripts')
    {{-- This will render all JS pushed to the "scripts" stack --}}
    @stack('scripts')
    <script>
        @foreach (session('flash_notification', collect())->toArray() as $message)
            AIZ.plugins.notify('{{ $message['level'] }}', '{{ $message['message'] }}');
        @endforeach
    </script>
    @yield('page.scripts')
    @yield('quickstepform.scripts')
    {{-- <script>
        $(document).ready(function () {
            @if (session('error'))
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
    <script>
        $(document).ready(function() {
            // Check if there are validation errors
            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    AIZ.plugins.notify('danger', "{{ $error }}", {
                        "closeButton": true,
                        "progressBar": true,
                        "positionClass": "toast-top-right",
                        "timeOut": 7000,
                        "extendedTimeOut": 1000
                    });
                @endforeach
            @endif

            // Check if a custom error message is present
            @if (session('error'))
                AIZ.plugins.notify('danger', "{{ session('error') }}", {
                    "closeButton": true,
                    "progressBar": true,
                    "positionClass": "toast-top-right",
                    "timeOut": 7000,
                    "extendedTimeOut": 1000
                });
            @endif
        });

        $('.dropdown-menu a[data-toggle="tab"]').click(function(e) {
            e.stopPropagation()
            $(this).tab('show')
        })


        let debounceTimer;
        $("#menu-search").on("input", function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                menuSearch();
            }, 300); // adjust delay as needed
        });

        $("#reset-search").on("click", function () {
            $("#menu-search").val('');
            menuSearch();
        });

        function menuSearch() {
            var filter = $("#menu-search").val().trim().toUpperCase();

            if (filter === '') {
                // If input is empty, show everything
                $(".sidebar-list-item, .sidebar-sub-list-item, .sidebar-sub-sub-list-item").show();
                $(".collapse").removeClass("show");
                return;
            }

            // Hide all first
            $(".sidebar-list-item, .sidebar-sub-list-item, .sidebar-sub-sub-list-item").hide();
            $(".collapse").removeClass("show");

            // Search all anchor tags
            $(".sidebar-list-item a, .sidebar-sub-list-item a, .sidebar-sub-sub-list-item a").each(function() {
                var $link = $(this);
                var text = $link.text().trim().toUpperCase();

                if (text.includes(filter)) {
                    // Show the current item
                    $link.closest("li").show();

                    // Expand parent menus and show them
                    $link.parents(".sidebar-sub-list-item").show();
                    $link.parents(".sidebar-list-item").show();

                    // Open all ancestor collapsible menus
                    $link.parents(".collapse").addClass("show");
                }
            });
        }
    </script>
    <!-- Modal HTML (Bootstrap 5.3) -->
    <div class="modal fade" id="aizUploaderDelete" tabindex="-1" aria-labelledby="aizUploaderDeleteLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="aizUploaderDeleteLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this file? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger aiz-uploader-confirmed-delete">Delete</button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
