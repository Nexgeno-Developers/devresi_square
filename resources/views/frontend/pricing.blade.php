@extends('frontend.layout.home')



@section('content')
    <section class="pricing_section">
        <h2 class="mb-5">Resisquare pricing plan</h2>

        <div class="pricing_tables my-5">
            {{-- table --}}
            <div class="price_table">
                <div class="price_table_header">
                    <h3>Resi Pro</h3>
                </div>
                <div class="price_table_content">
                    <p class="desc">Full-featured property management for landlords and self-employed agents.
                        Includes unlimited properties, document storage, tenant maintenance reporting, and full CRM access
                        via app and desktop.</p>
                    <p class="price">£75<span>/mo</span></p>
                    <div class="pricing_features_wrapper">
                        <ul class="pricing_features">
                            <li>Unlimited properties</li>
                            <li>Document storage</li>
                            <li>Maintenance reporting via app</li>
                            <li>Full CRM access</li>
                        </ul>
                    </div>
                </div>
                <button class="btn btn_secondary pricing_btn">Get Started</button>
            </div>
            {{-- table end --}}
            {{-- table --}}
            <div class="price_table">
                <div class="price_table_header">
                    <h3>Resi Premium</h3>
                </div>
                <div class="price_table_content">
                    <p class="desc">Enhanced support with a dedicated property manager. Everything in ResiLite plus
                        personalized property management, priority issue resolution, and expert compliance guidance</p>
                    <p class="price">£150<span>/mo</span></p>
                    <div class="pricing_features_wrapper">
                        <ul class="pricing_features">
                            <li>All ResiPro features</li>
                            <li>Dedicated property manager</li>
                            <li>Priority issue resolution</li>
                            <li>Compliance help</li>
                        </ul>
                    </div>
                </div>
                <button class="btn btn_secondary pricing_btn">Get Started</button>
            </div>
            {{-- table end --}}
            {{-- table --}}
            <div class="price_table">
                <div class="price_table_header">
                    <h3>Resi Max</h3>
                    <h5 class="mt-2">For estate agencies</h5>
                </div>
                <div class="price_table_content">
                    <p class="desc">Comprehensive solution for estate agencies. Multi-user access, branded communications,
                        lead management tools, advanced dashboards, and priority support.</p>
                    <p class="price">£180<span>/mo</span></p>
                    <div class="pricing_features_wrapper">
                        <ul class="pricing_features">
                            <li>Multi-user access</li>
                            <li>Branded communications</li>
                            <li>Lead management</li>
                            <li>Dashboards</li>
                        </ul>
                    </div>
                </div>
                <button class="btn btn_secondary pricing_btn">Get Started</button>
            </div>
            {{-- table end --}}

        </div>

    </section>

    <section class="pricing_section why_us_section">
        <div>
            <h2 class="my-5">Why choose Resisquare?</h2>
            <div class="pricing_tables my-5">
                {{-- table --}}
                <div class="price_table why_us_table">
                    <div class="price_table_header">
                        <h3>All-inclusive</h3>
                    </div>
                    <div class="price_table_content">
                        <div>
                            <img src="{{ static_asset('asset/img/icons/no-hidden-fees.svg')}}" alt="">
                            <p class="desc">No hidden fees</p>
                        </div>
                        <div>
                            <img src="{{ static_asset('asset/img/icons/full_access.svg')}}" alt="">
                            <p class="desc">Full platform access</p>
                        </div>
                    </div>
                </div>
                {{-- table end --}}
                {{-- table --}}
                <div class="price_table why_us_table">
                    <div class="price_table_header">
                        <h3>Easy Management</h3>
                    </div>
                    <div class="price_table_content">
                        <div>
                            <img src="{{ static_asset('asset/img/icons/streamline-lettings.svg')}}" alt="">
                            <p class="desc">Streamline lettings</p>
                        </div>
                        <div>
                            <img src="{{ static_asset('asset/img/icons/sales.svg')}}" alt="">
                            <p class="desc">Sales</p>
                        </div>
                        <div>
                            <img src="{{ static_asset('asset/img/icons/maintenance-app.svg')}}" alt="">
                            <p class="desc">Maintenance in one app</p>
                        </div>
                    </div>
                </div>
                {{-- table end --}}
                {{-- table --}}
                <div class="price_table why_us_table">
                    <div class="price_table_header">
                        <h3>Expert Support</h3>
                    </div>
                    <div class="price_table_content">
                        <div>
                            <img src="{{ static_asset('asset/img/icons/manage-property.svg')}}" alt="">
                            <p class="desc">Dedicated property managers</p>
                        </div>
                        <div>
                            <img src="{{ static_asset('asset/img/icons/plans.svg')}}" alt="">
                            <p class="desc">Premium plans</p>
                        </div>
                    </div>
                </div>
                {{-- table end --}}
            </div>
        </div>

    </section>

    <section class="trusted_by_section">
        <h4 class="my-5">Trusted by landlords and agencies alike</h4>
        <div class="trusted_by_wrapper">
        </div>
    </section>

    <!-- Modal -->
    <div class="modal fade" id="bookDemoModal" tabindex="-1" aria-labelledby="bookDemoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="bookDemoModalLabel">Book a Demo</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    {{-- Include your reusable form component --}}
                    <x-form-component action="{{ route('form.submit', 'book_demo') }}" formId="bookDemoForm"
                        submitText="Book Now" successMessage="Thank you! We will user you shortly." />
                </div>
            </div>
        </div>
    </div>
@endsection