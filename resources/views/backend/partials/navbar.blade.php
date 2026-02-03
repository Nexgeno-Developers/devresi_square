@push('styles')
<style>
#header .btn-soft-danger {
    background-color: #ef486726;
    color: #ef486a;
}

</style>
@endpush
<!-- resources/views/backend/partials/navbar.blade.php -->
<nav class="navbar navbar-expand-lg ">
    <div class="w_full flex items_center">
        <div class="rs_logo">
            <img src="{{ uploaded_asset(get_setting('header_logo')) }}" alt="Resisquare logo">
            {{-- <img src="{{ asset('asset/images/resisquare-logo.svg') }}" alt="Resisquare logo"> --}}
        </div>
        {{-- <a class="navbar-brand" href="{{ route('backend.dashboard') }}">Property CRM Admin</a> --}}
        {{-- <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin" aria-controls="navbarAdmin" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button> --}}
        <div class="flex justify_between items_center w_full">
            <div class="toggle_icon_wrapper rs_tooltip hide-menu" data-label="Toggle Side Menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-text-left toggle_icon" viewBox="0 0 16 16" alt="Toggle Menu">
                    <path fill-rule="evenodd" d="M2 12.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5"/>
                </svg>
            </div>
            <div class="d-flex justify-content-around align-items-center align-items-stretch ml-3">
                <div class="aiz-topbar-item">
                    <div class="d-flex align-items-center">
                        <a class="btn btn-soft-danger btn-sm d-flex align-items-center" href="{{ route('cache.clear')}}">
                            <i class="fa-regular fa-hard-drive fs-20"></i>
                            <span class="fw-500 mx-2">Clear Cache</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="collapse navbar-collapse" id="navbarAdmin">
                <ul class="navbar-nav ms-auto">               
                    @if (Auth::check())                  
                        <li class="nav-item">
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="nav-link btn btn-link">Logout</button>
                            </form>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">Register</a>
                        </li>
                    @endif             
                </ul>
            </div>
        </div>
    </div>
</nav>
