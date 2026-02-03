@extends('frontend.layout.home')

@php
    $hero_card = [
        [
            'title' => 'Still stuck with spreadsheets?',
            'details' => "We get it. Managing properties the old-fashioned way is a headache — scattered docs, delayed updates, endless chaos. With ResiSquare, everything comes together in one clean, simple dashboard that makes your property hustle feel like a breeze.",
        ],
        [
            'title' => 'Paying sky-high commissions? Why?',
            'details' => "Say goodbye to jaw-dropping fees that eat into your profits. ResiSquare puts everything you need — from maintenance tracking to automated rent collection — under one roof for one simple, transparent flat monthly rate. Because your hard-earned income deserves to stay exactly where it belongs: with you.",
        ],
        [
            'title' => 'Wasting hours on admin? Not anymore.',
            'details' => "Landlords weren’t meant to be full-time administrators. Let ResiSquare do the heavy lifting. With powerful automation, real-time alerts, and customizable workflows, our platform eliminates the admin burden—so you can focus on growing your portfolio, not getting buried in paperwork.",
        ],

    ];
    $features_list = [
        [
            'title' => 'Manage Properties',
            'details' => "Effortlessly oversee your property portfolio with our intuitive dashboard. Monitor occupancy rates, schedule maintenance, and ensure compliance, all in one place.",
        ],
        [
            'title' => 'Manage Tenancies',
            'details' => "Simplify tenancy agreements, track lease terms, and handle renewals seamlessly. Our platform ensures all tenant information is organized and accessible.",
        ],
        [
            'title' => 'Manage Users',
            'details' => "Maintain a comprehensive database of tenants, landlords, and service providers. Efficient communication tools keep everyone informed and connected.",
        ],
        [
            'title' => 'Manage Bookings',
            'details' => "Coordinate property viewings and maintenance appointments with ease. Our scheduling tools help prevent overlaps and ensure timely engagements.",
        ],
        [
            'title' => 'Scalable Solutions',
            'details' => "ResiSquare adapts to your business needs. Whether you're managing a handful of properties or an extensive portfolio, our platform scales with you.",
        ],
    ];
@endphp

@section('content')
    <section class="hero_section px-4">
        <div class="main_hero_section">
            <div class="">
                <div class="hero_title">Revolutionize Your <br />Property Management <br>
                    <span> Experience</span>
                </div>
                <p class="hero_description">Streamline operations, enhance tenant satisfaction, and maximize returns with
                    ResiSquare's innovative property management solutions.</p>
                <div class="hero_btns">
                    <button id="hero_get_started_btn" class="btn btn_primary">Get Started</button>
                    {{-- <button class="btn btn_secondary">Book a Demo</button> --}}
                    <!-- Trigger Button -->
                    <button type="button" id="hero_book_demo_btn" class="btn btn_secondary" data-bs-toggle="modal"
                        data-bs-target="#bookDemoModal">
                        Book a Demo
                    </button>

                    <!-- Modal -->
                    <div class="modal fade" id="bookDemoModal" tabindex="-1" aria-labelledby="bookDemoModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg rounded-4">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="bookDemoModalLabel">Book a Demo</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    {{-- Include your reusable form component --}}
                                    <x-form-component action="{{ route('form.submit', 'book_demo') }}" formId="bookDemoForm"
                                        submitText="Book Now" successMessage="Thank you! We will user you shortly." />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="">
                <img src="{{ static_asset('asset/img/hero_img.webp') }}" alt="Hero" class="hero_main_img | img-fluid">
            </div>
        </div>

        <div class="hero_cards">
            @foreach ($hero_card as $card)
                <div class="hero_card_item">
                    <div class="card_title">{{ $card['title'] }}</div>
                    <p>{{ $card['details'] }}</p>
                </div>
            @endforeach
        </div>


        <div class="features_list">
            <div class="text-center my-3">
                <h2>Enable features as you grow</h2>
            </div>
            <div class="carousel_wrapper">
                <div class="carousel">
                    @foreach ($features_list as $feature)
                        <div class="carousel-item feature_item {{ $loop->first ? 'active' : '' }}">
                            <div class="card_title">{{ $feature['title'] }}</div>
                            <p>{{ $feature['details'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    <section class="features_section">
        <div class="features_item item_left_1 bg_white">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 col-12 mb-md-0 mb-3">
                        <img src="{{ asset('asset/img/hero_img.webp') }}" alt="Feature" class="features_img_left img-fluid">
                    </div>
                    <div class="col-md-6 col-12 mb-md-0 mb-3">
                        <div class="feature_card ">
                            <h3>Adapt the Platform to Your Business Model</h3>
                            <p>Tailor ResiSquare to your unique operational structure. Whether you're a solo landlord or a
                                large letting agency, our flexible system fits seamlessly into your workflow.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="features_item item_right">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 col-12 mb-md-0 mb-3">
                        <div class="feature_card">
                            <h3>Tailor Notifications & Access for Each Team Member</h3>
                            <p>Ensure the right people get the right updates. With ResiSquare, you can fully control which
                                team members receive specific alerts and reports. Whether it's maintenance requests, rent
                                reminders, or tenancy updates, personalize your notification settings to enhance
                                productivity and reduce noise.</p>
                            <p>Empower your team with relevant information — no more, no less.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-12 mb-md-0 mb-3">
                        <img src="{{ asset('asset/img/hero_img.webp') }}" alt="Feature"
                            class="features_img_right img-fluid">
                    </div>
                </div>
            </div>
        </div>
        <div class="features_item item_left_2 bg_white">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 col-12 mb-md-0 mb-3">
                        <img src="{{ asset('asset/img/hero_img.webp') }}" alt="Feature" class="features_img_left img-fluid">
                    </div>
                    <div class="col-md-6 col-12 mb-md-0 mb-3">
                        <div class="feature_card">
                            <h3>Define Permissions for Better Control & Security</h3>
                            <p>Keep your operations secure and organized with customizable role-based permissions. Assign
                                different access levels to admins, managers, and assistants — so everyone works within their
                                scope. Whether managing documents, financial data, or tenancy records, you're always in
                                control of who sees what.</p>
                            <p>Give your team the access they need, and protect what matters most.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="book_demo_section">
        <div class="container">
            <div class="">
                <h2>Experience ResiSquare Firsthand</h2>
                <p>Discover how our platform can transform your property management tasks. Schedule a demo today and take
                    the first step towards efficient property management.</p>
            </div>
            <div class="book_demo_btn">
                <button class="btn btn_secondary btn-lg">Book Now</button>
            </div>
            <div class="book_demo_img">
                <img src="{{ asset('asset/img/hero_img.webp') }}" alt="Book a demo" class="">
            </div>
        </div>
    </section>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/gsap.min.js"
        integrity="sha512-NcZdtrT77bJr4STcmsGAESr06BYGE8woZdSdEgqnpyqac7sugNO+Tr4bGwGF3MsnEkGKhU2KL2xh6Ec+BqsaHA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.13.0/ScrollTrigger.min.js"
        integrity="sha512-P2IDYZfqSwjcSjX0BKeNhwRUH8zRPGlgcWl5n6gBLzdi4Y5/0O4zaXrtO4K9TZK6Hn1BenYpKowuCavNandERg=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{ static_asset('asset/js/gsap-animation.js') }}"></script>

@endsection