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
    {{-- <link href="{{ asset('asset/backend/css/aiz-core.css') }}" rel="stylesheet"> --}}
    {{-- <link href="{{ asset('asset/backend/css/media.css') }}" rel="stylesheet"> --}}
    {{-- <link href="{{ asset('asset/backend/css/vendors.css') }}" rel="stylesheet"> --}}
</head>

<body class="show-sidebar">
    <header  id="header" class="">
        <div class="container-fluid">
            <div class="top_header tw-ml-1">

                @include('backend.partials.navbar')
            </div>
        </div>
    </header>
    <main>
        <div class="main_wrapper">
            @include('backend.partials.aside')

            <div id="wrapper" class="main_content">
                <div class="alert_wrapper">
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
                </div>

                @yield('content')
            </div>
        </div>
    </main>

    <!-- Use asset() to generate the correct URL for JS files -->

    <script src="{{ asset('asset/js/jquery.min.js') }}"></script>

    <script src="{{ asset('asset/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('asset/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('asset/js/toastr.min.js') }}"></script>
    <script src="{{ asset('asset/js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('asset/backend/js/Init.js') }}"></script>
    <script src="{{ asset('asset/backend/js/vendors.js') }}"></script>
    <script src="{{ asset('asset/backend/js/style.js') }}"></script>
    <!-- <script src="{{ asset('asset/js/dataTables.bootstrap5.min.js') }}"></script> -->
    <script>
    	var AIZ = AIZ || {};
        AIZ.local = {
            nothing_selected: 'Nothing selected',
            nothing_found: 'Nothing found',
            choose_file: 'Choose File',
            file_selected: 'File selected',
            files_selected: 'Files selected',
            add_more_files: 'Add more files',
            adding_more_files: 'Adding more files',
            drop_files_here_paste_or: 'Drop files here, paste or',
            browse: 'Browse',
            upload_complete: 'Upload complete',
            upload_paused: 'Upload paused',
            resume_upload: 'Resume upload',
            pause_upload: 'Pause upload',
            retry_upload: 'Retry upload',
            cancel_upload: 'Cancel upload',
            uploading: 'Uploading',
            processing: 'Processing',
            complete: 'Complete',
            file: 'File',
            files: 'Files',
        }
	</script>
    <script src="{{ asset('asset/backend/js/aiz-core.js') }}"></script>
    <script>
        @foreach (session('flash_notification', collect())->toArray() as $message)
            AIZ.plugins.notify('{{ $message['level'] }}', '{{ $message['message'] }}');
        @endforeach
    </script>
    @yield('page.scripts')
    @yield('quickstepform.scripts')
    <script>
        $(document).ready(function () {
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
