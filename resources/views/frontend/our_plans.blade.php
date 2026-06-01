@extends('frontend.layout.home')

@section('content')

    <section class="pricing_section">
        <h2 class="mb-5">Resisquare pricing plan</h2>

        {{-- Monthly / Yearly toggle --}}
        <div class="text-center mb-4">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary active" id="toggleMonthly">Monthly</button>
                <button type="button" class="btn btn-outline-primary" id="toggleYearly">
                    Yearly &nbsp;<span class="badge bg-success">Save up to 20%</span>
                </button>
            </div>
        </div>

        <div class="pricing_tables my-5">
            @forelse($plans as $plan)
            <div class="price_table">
                <div class="price_table_header">
                    <h3>{{ $plan->name }}</h3>
                    @if($plan->badge_label)
                        <h5 class="mt-2">{{ $plan->badge_label }}</h5>
                    @endif
                </div>
                <div class="price_table_content">
                    @if($plan->description)
                        <p class="desc">{{ $plan->description }}</p>
                    @endif

                    <p class="price price-monthly">
                        £{{ number_format($plan->price_monthly, 0) }}<span>/mo</span>
                    </p>
                    <p class="price price-yearly d-none">
                        £{{ number_format($plan->price_yearly, 0) }}<span>/yr</span>
                        @if($plan->price_monthly > 0 && $plan->price_yearly > 0)
                            <small class="d-block text-success" style="font-size:0.9rem;font-weight:500;">
                                Save £{{ number_format(($plan->price_monthly * 12) - $plan->price_yearly, 0) }}
                            </small>
                        @endif
                    </p>

                    <div class="pricing_features_wrapper">
                        <ul class="pricing_features">
                            <li>{{ $plan->max_properties ? $plan->max_properties . ' properties' : 'Unlimited properties' }}</li>
                            <li>{{ $plan->max_staff ? $plan->max_staff . ' staff members' : 'Unlimited staff' }}</li>
                            <li>{{ $plan->max_tenancies ? $plan->max_tenancies . ' tenancies' : 'Unlimited tenancies' }}</li>
                            @foreach($plan->features ?? [] as $feature)
                                <li>{{ ucfirst($feature) }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <a href="{{ route('register') }}" class="btn btn_secondary pricing_btn">Get Started</a>
            </div>
            @empty
            <div class="text-center py-5 w-100">
                <p class="text-muted">No plans available at the moment. Please check back soon.</p>
            </div>
            @endforelse
        </div>
    </section>

    <section class="pricing_section why_us_section">
        <div>
            <h2 class="my-5">Why choose Resisquare?</h2>
            <div class="pricing_tables my-5">
                <div class="price_table why_us_table">
                    <div class="price_table_header"><h3>All-inclusive</h3></div>
                    <div class="price_table_content">
                        <div>
                            <img src="{{ static_asset('asset/img/icons/no-hidden-fees.svg') }}" alt="">
                            <p class="desc">No hidden fees</p>
                        </div>
                        <div>
                            <img src="{{ static_asset('asset/img/icons/full_access.svg') }}" alt="">
                            <p class="desc">Full platform access</p>
                        </div>
                    </div>
                </div>
                <div class="price_table why_us_table">
                    <div class="price_table_header"><h3>Easy Management</h3></div>
                    <div class="price_table_content">
                        <div>
                            <img src="{{ static_asset('asset/img/icons/streamline-lettings.svg') }}" alt="">
                            <p class="desc">Streamline lettings</p>
                        </div>
                        <div>
                            <img src="{{ static_asset('asset/img/icons/sales.svg') }}" alt="">
                            <p class="desc">Sales</p>
                        </div>
                        <div>
                            <img src="{{ static_asset('asset/img/icons/maintenance-app.svg') }}" alt="">
                            <p class="desc">Maintenance in one app</p>
                        </div>
                    </div>
                </div>
                <div class="price_table why_us_table">
                    <div class="price_table_header"><h3>Expert Support</h3></div>
                    <div class="price_table_content">
                        <div>
                            <img src="{{ static_asset('asset/img/icons/manage-property.svg') }}" alt="">
                            <p class="desc">Dedicated property managers</p>
                        </div>
                        <div>
                            <img src="{{ static_asset('asset/img/icons/plans.svg') }}" alt="">
                            <p class="desc">Premium plans</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="trusted_by_section">
        <h4 class="my-5">Trusted by landlords and agencies alike</h4>
        <div class="trusted_by_wrapper"></div>
    </section>

    <div class="modal fade" id="bookDemoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Book a Demo</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-form-component action="{{ route('form.submit', 'book_demo') }}" formId="bookDemoForm"
                        submitText="Book Now" successMessage="Thank you! We will contact you shortly." />
                </div>
            </div>
        </div>
    </div>

@endsection

@section('page.scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnMonthly = document.getElementById('toggleMonthly');
    const btnYearly  = document.getElementById('toggleYearly');

    btnMonthly.addEventListener('click', function () {
        btnMonthly.classList.add('active');
        btnYearly.classList.remove('active');
        document.querySelectorAll('.price-monthly').forEach(el => el.classList.remove('d-none'));
        document.querySelectorAll('.price-yearly').forEach(el => el.classList.add('d-none'));
    });

    btnYearly.addEventListener('click', function () {
        btnYearly.classList.add('active');
        btnMonthly.classList.remove('active');
        document.querySelectorAll('.price-yearly').forEach(el => el.classList.remove('d-none'));
        document.querySelectorAll('.price-monthly').forEach(el => el.classList.add('d-none'));
    });
});
</script>
@endsection
