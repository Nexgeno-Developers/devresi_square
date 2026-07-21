@extends('frontend.layout.home')

@section('content')
    <section class="pricing_section pricing_page_section">
        <div class="container pricing_container">
            <div class="pricing_intro">
                <h2>Resisquare pricing plan</h2>
            </div>

            @if(session('error'))
                <div class="alert alert-danger text-center">{{ session('error') }}</div>
            @endif

            <div class="pricing_toggle_wrapper">
                <div class="btn-group billing-toggle" role="group" aria-label="Billing cycle">
                    <button type="button" class="btn btn-outline-primary active" data-billing-option="monthly">Monthly</button>
                    <button type="button" class="btn btn-outline-primary" data-billing-option="annual">Annual</button>
                </div>
            </div>
        </div>

        @php
            $planGroups = [
                'landlord' => 'Landlord plans',
                'estate_agent_freelance' => 'Estate agent freelance plans',
                'estate_agent_company' => 'Estate agent company plans',
            ];
        @endphp

        @if($plans->isEmpty())
            <div class="container">
                <div class="alert alert-info text-center">No active plans are available right now.</div>
            </div>
        @else
            <div class="container pricing_container">
                <div class="pricing_tables dynamic-pricing-tables pricing_cards pricing_plan_cards">
                    @foreach($plans as $plan)
                        <div class="price_table plan_table">
                            <div class="price_table_header">
                                <span class="pricing_badge">{{ $planGroups[$plan->target_account_type] ?? ucwords(str_replace('_', ' ', $plan->target_account_type)) }}</span>
                                <h3>{{ $plan->name }}</h3>
                                <h5>{{ ucwords(str_replace('_', ' ', $plan->target_account_type)) }}</h5>
                            </div>
                            <div class="price_table_content">
                                <p class="desc">{{ $plan->description ?: 'Property management tools for your selected account type.' }}</p>
                                <p class="price">
                                    <span class="js-plan-price"
                                        data-monthly="{{ $plan->formattedMonthlyPrice() }}"
                                        data-annual="{{ $plan->formattedAnnualPrice() }}">
                                        {{ $plan->formattedMonthlyPrice() }}
                                    </span>
                                    <span class="js-plan-period">/mo</span>
                                </p>
                                <p class="trial_note">{{ $plan->trial_days }}-day trial</p>

                                <div class="pricing_features_wrapper">
                                    <ul class="pricing_features">
                                        <li>{{ $plan->property_limit }} properties included</li>
                                        <li>{{ $plan->branch_limit }} branches included</li>
                                        <li>{{ $plan->staff_limit }} staff included</li>
                                        <li>{{ $plan->property_manager_limit }} property managers included</li>
                                        <li>Company profile: {{ $plan->allow_company_profile ? 'Yes' : 'No' }}</li>
                                        <li>Invoice branding: {{ $plan->allow_invoice_branding ? 'Yes' : 'No' }}</li>
                                        <li>Roles and permissions: {{ $plan->allow_roles_permissions ? 'Yes' : 'No' }}</li>
                                        <li>Contact portal login: {{ $plan->allow_contact_login ? 'Yes' : 'No' }}</li>
                                    </ul>
                                </div>
                            </div>
                            <a class="btn btn_secondary pricing_btn js-register-plan"
                                href="{{ route('register', ['plan_id' => $plan->id, 'billing_cycle' => 'monthly', 'account_type' => $plan->target_account_type]) }}"
                                data-monthly-url="{{ route('register', ['plan_id' => $plan->id, 'billing_cycle' => 'monthly', 'account_type' => $plan->target_account_type]) }}"
                                data-annual-url="{{ route('register', ['plan_id' => $plan->id, 'billing_cycle' => 'annual', 'account_type' => $plan->target_account_type]) }}">
                                Start Trial
                            </a>
                        </div>
                    @endforeach
                </div>

                <p class="pricing_trial_footer">7-day free trial is available on eligible plans.</p>
            </div>
        @endif
    </section>

    <section class="pricing_section pricing_page_section addons_section">
        <div class="container pricing_container">
            <div class="pricing_intro">
                <h2>Available addons</h2>
            </div>

            @if($addons->isNotEmpty())
                <div class="pricing_tables dynamic-pricing-tables pricing_cards pricing_addon_cards">
                    @foreach($addons as $addon)
                        <div class="price_table addon_table">
                            <div class="price_table_header">
                                <h3>{{ $addon->name }}</h3>
                                <h5>{{ ucwords(str_replace('_', ' ', $addon->addon_type)) }}</h5>
                            </div>
                            <div class="price_table_content">
                                <p class="price addon-price">
                                    <span class="js-plan-price"
                                        data-monthly="{{ $addon->formattedMonthlyPrice() }}"
                                        data-annual="{{ $addon->formattedAnnualPrice() }}">
                                        {{ $addon->formattedMonthlyPrice() }}
                                    </span>
                                    <span class="js-plan-period">/mo</span>
                                </p>
                                <div class="pricing_features_wrapper">
                                    <ul class="pricing_features">
                                        <li>Grant quantity: {{ $addon->grant_quantity }}</li>
                                        <li>Stackable: {{ $addon->is_stackable ? 'Yes' : 'No' }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-info text-center">No active addons are available right now.</div>
            @endif
        </div>
    </section>

    <section class="pricing_section pricing_page_section why_us_section">
        <div class="container pricing_container">
            <div class="pricing_intro">
                <h2>Why choose Resisquare?</h2>
            </div>
            <div class="pricing_tables pricing_cards why_us_cards">
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
            </div>
        </div>
    </section>

    <section class="trusted_by_section">
        <h4 class="my-5">Trusted by landlords and agencies alike</h4>
        <div class="trusted_by_wrapper"></div>
    </section>

    <div class="modal fade" id="bookDemoModal" tabindex="-1" aria-labelledby="bookDemoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="bookDemoModalLabel">Book a Demo</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <x-form-component action="{{ route('form.submit', 'book_demo') }}" formId="bookDemoForm"
                        submitText="Book Now" successMessage="Thank you! We will user you shortly." />
                </div>
            </div>
        </div>
    </div>

    <style>
        .pricing_page_section {
            padding: 56px 0;
        }
        .pricing_page_section + .pricing_page_section {
            padding-top: 32px;
        }
        .pricing_container {
            max-width: 1180px;
        }
        .pricing_intro {
            text-align: center;
            margin-bottom: 24px;
        }
        .pricing_intro h2 {
            margin: 0;
            color: var(--primary-900, #0f172a);
            font-size: 32px;
            font-weight: 700;
            line-height: 1.2;
        }
        .pricing_toggle_wrapper {
            display: flex;
            justify-content: center;
            margin-bottom: 32px;
        }
        .billing-toggle {
            border: 1px solid var(--primary-300, #cbd5e1);
            border-radius: 999px;
            overflow: hidden;
            background: var(--white, #fff);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }
        .billing-toggle .btn {
            min-width: 96px;
            border: 0;
            border-radius: 0;
            color: var(--primary-800, #1e293b);
            font-weight: 600;
        }
        .billing-toggle .btn.active {
            background: var(--secondary, #ff5c1d);
            color: #fff;
        }
        .pricing_cards,
        .pricing_section .dynamic-pricing-tables {
            display: grid;
            gap: 24px;
            align-items: stretch;
            justify-content: center;
        }
        .pricing_plan_cards {
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 360px));
        }
        .pricing_addon_cards {
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 240px), 1fr));
        }
        .why_us_cards {
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr));
        }
        .pricing_section .pricing_cards .price_table {
            width: 100%;
            min-height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid var(--primary-200, #e2e8f0);
            border-radius: 8px;
            background: var(--white, #fff);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            text-align: center;
        }
        .pricing_section .pricing_cards .price_table .price_table_header {
            min-height: 112px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 20px;
            background: var(--primary-900, #0f172a);
            color: #fff;
        }
        .pricing_section .pricing_cards .price_table .price_table_header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            line-height: 1.2;
        }
        .pricing_section .pricing_cards .price_table .price_table_header h5 {
            margin: 0;
            font-size: 13px;
            font-weight: 500;
            color: var(--primary-200, #e2e8f0);
        }
        .pricing_badge {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 3px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            font-size: 12px;
            font-weight: 600;
        }
        .pricing_section .pricing_cards .price_table .price_table_content {
            display: flex;
            flex: 1;
            flex-direction: column;
            padding: 24px;
        }
        .pricing_section .pricing_cards .price_table .desc {
            margin: 0 0 16px;
            min-height: 42px;
            color: var(--primary-600, #475569);
            font-size: 14px;
            line-height: 1.5;
        }
        .pricing_section .price_table .price {
            margin: 0;
            padding: 8px 0 4px;
            color: var(--primary-950, #020617);
            font-size: 34px;
            font-weight: 700;
            line-height: 1.15;
        }
        .pricing_section .price_table .price .js-plan-price {
            font-size: inherit;
            font-weight: inherit;
            color: inherit;
        }
        .pricing_section .price_table .price .js-plan-period {
            color: var(--primary-500, #64748b);
            font-size: 16px;
            font-weight: 500;
        }
        .trial_note,
        .pricing_trial_footer {
            color: var(--primary-500, #64748b);
            font-size: 13px;
        }
        .trial_note {
            margin: 6px 0 18px;
        }
        .pricing_trial_footer {
            margin: 24px 0 0;
            text-align: center;
        }
        .pricing_features_wrapper {
            display: flex;
            justify-content: center;
            margin-top: auto;
        }
        .pricing_features {
            width: 100%;
            margin: 0;
            padding: 0;
            text-align: left;
        }
        .pricing_features li {
            position: relative;
            margin: 0;
            padding: 7px 0 7px 24px;
            color: var(--primary-700, #334155);
            font-size: 14px;
            line-height: 1.35;
        }
        .pricing_features li::before {
            /* position: absolute; */
            position: relative;
            left: 0;
            content: "\2713";
            color: var(--secondary, #ff5c1d);
            font-weight: 700;
        }
        .pricing_section .pricing_cards .price_table .pricing_btn {
            width: auto;
            margin: 0 24px 24px;
            justify-content: center;
            font-weight: 700;
        }
        .pricing_section .addon_table .price_table_header {
            background-color: var(--primary-800, #16436f);
        }
        .pricing_section .addon-price {
            padding-top: 0;
        }
        .why_us_section {
            background: var(--white, #fff);
        }
        .pricing_section .why_us_cards .why_us_table {
            width: 100%;
            box-shadow: none;
        }
        .pricing_section .why_us_cards .price_table_content {
            gap: 24px;
            justify-content: center;
        }
        .pricing_section .why_us_cards img {
            width: 52px;
            height: 52px;
            object-fit: contain;
            margin-bottom: 12px;
        }
        .pricing_section .why_us_cards .desc {
            min-height: 0;
            margin: 0;
            color: var(--primary-800, #1e293b);
            font-size: 15px;
            font-weight: 600;
        }
        @media (max-width: 766px) {
            .pricing_page_section {
                padding: 36px 0;
            }
            .pricing_intro h2 {
                font-size: 26px;
            }
            .pricing_cards,
            .pricing_section .dynamic-pricing-tables {
                grid-template-columns: 1fr;
            }
            .pricing_section .pricing_cards .price_table .price_table_header {
                min-height: 96px;
            }
            .pricing_section .pricing_cards .price_table .price_table_content {
                padding: 20px;
            }
        }
    </style>
@endsection

@section('page.scripts')
<script>
(function () {
    const buttons = document.querySelectorAll('[data-billing-option]');
    const priceNodes = document.querySelectorAll('.js-plan-price');
    const periodNodes = document.querySelectorAll('.js-plan-period');
    const registerLinks = document.querySelectorAll('.js-register-plan');

    function setBillingCycle(cycle) {
        buttons.forEach(button => {
            button.classList.toggle('active', button.dataset.billingOption === cycle);
        });

        priceNodes.forEach(node => {
            node.textContent = cycle === 'annual' ? node.dataset.annual : node.dataset.monthly;
        });

        periodNodes.forEach(node => {
            node.textContent = cycle === 'annual' ? '/yr' : '/mo';
        });

        registerLinks.forEach(link => {
            link.href = cycle === 'annual' ? link.dataset.annualUrl : link.dataset.monthlyUrl;
        });
    }

    buttons.forEach(button => {
        button.addEventListener('click', () => setBillingCycle(button.dataset.billingOption));
    });
})();
</script>
@endsection
