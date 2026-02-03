<!-- components/frontend/header.blade.php -->
<header  id="header" class="">
    <div class="container-fluid">
        <div class="frontend_top_header tw-ml-1">
            <div class="rs_logo">
                <a href="{{ route('home') }}" class="navbar-brand">
                    <img src="{{ uploaded_asset(get_setting('header_logo')) }}" alt="Resisquare logo">
                </a>
                {{-- <img src="{{ asset('asset/images/resisquare-logo.svg') }}" alt="Resisquare logo"> --}}
            </div>
            <div class="navbar-nav ms-auto">    
                <div class="nav-item">
                    <button class="btn btn_secondary pricing_btn" data-bs-toggle="modal" data-bs-target="#bookDemoModal">Book a demo</button>
                </div>           
                <div class="nav-item">
                    <a class="nav-link" href="{{ route('pricing') }}">Pricing</a>
                </div>           
                @if (Auth::check())                  
                    <div class="nav-item">
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="nav-link btn btn-link">Logout</button>
                        </form>
                    </div>
                @else
                    <div class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Login</a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link" href="{{ route('register') }}">Register</a>
                    </div>
                @endif             
            </div>
        </div>
    </div>
</header>