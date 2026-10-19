@extends('frontend.layout.home')

@section('title', 'ResiSquare Pricing — Simple Pricing for Your Entire Portfolio')
@section('meta_description', 'ResiSquare charges for the software — never a percentage of your rent. Choose the plan that fits your portfolio today and upgrade as you grow.')

@section('content')
    <section class="pricing_section pricing_page_section">
        <div class="container pricing_container">
            <div class="pricing_intro">
                <p class="pricing_kicker">Transparent SaaS Pricing</p>
                <h2>Simple Pricing That Grows With Your Portfolio</h2>
                <p class="pricing_lead">ResiSquare charges for the software — never a percentage of your rent. Choose the plan that fits your portfolio today and upgrade as you grow.</p>
            </div>

            @if(session('error'))
                <div class="alert alert-danger text-center">{{ session('error') }}</div>
            @endif
            @if(session('info'))
                <div class="alert alert-info text-center">{{ session('info') }}</div>
            @endif

            <div class="pricing_toggle_wrapper">
                <div class="btn-group billing-toggle" role="group" aria-label="Billing cycle">
                    <button type="button" class="btn btn-outline-primary active" data-billing-option="monthly">Monthly</button>
                    <button type="button" class="btn btn-outline-primary" data-billing-option="annual">Annual <span class="pricing_save">Save 2 Months</span></button>
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
                        @php
                            $limit = (int) $plan->property_limit;
                            $band = match (true) {
                                $limit <= 3 => 'starter',
                                $limit <= 10 => 'growth',
                                $limit <= 25 => 'pro',
                                default => 'business',
                            };
                            $bandCopy = [
                                'starter' => ['For landlords managing a small portfolio.', null, ['Properties & Tenancies', 'Rent Tracking', 'Repairs & Maintenance', 'Compliance & Documents', 'Calendar & Tenant Access']],
                                'growth' => ['For landlords growing their portfolio.', 'Everything in Starter, plus:', ['Contractor Access', 'Property CSV Imports', 'Payment Tracking & Priority Support', 'Additional User Access']],
                                'pro' => ['For professional landlords and independent agents.', 'Everything in Growth, plus:', ['Team Access & Advanced Permissions', 'Portfolio Reporting', 'Bulk Actions', 'Advanced Document Workflows']],
                                'business' => ['For larger portfolios and property teams.', 'Everything in Pro, plus:', ['Additional Team Members', 'Advanced Reporting', 'Priority Onboarding', 'Enhanced Portfolio Controls']],
                            ][$band];
                            $lowerBandShown = match ($band) {
                                'growth' => $plans->contains(fn ($item) => (int) $item->property_limit <= 3),
                                'pro' => $plans->contains(fn ($item) => (int) $item->property_limit > 3 && (int) $item->property_limit <= 10),
                                'business' => $plans->contains(fn ($item) => (int) $item->property_limit > 10 && (int) $item->property_limit <= 25),
                                default => false,
                            };
                            $showPopular = $band === 'growth' && $plans->count() > 1;
                            $starterFeatures = ['Properties & Tenancies', 'Rent Tracking', 'Repairs & Maintenance', 'Compliance & Documents', 'Calendar & Tenant Access'];
                            $features = ($band !== 'starter' && ! $lowerBandShown)
                                ? array_merge($starterFeatures, $bandCopy[2])
                                : $bandCopy[2];
                        @endphp
                        <div class="price_table plan_table {{ $showPopular ? 'is-popular' : '' }}">
                            <div class="price_table_header">
                                @if($showPopular)
                                    <span class="pricing_badge">Most Popular</span>
                                @else
                                    <span class="pricing_badge">{{ $planGroups[$plan->target_account_type] ?? ucwords(str_replace('_', ' ', $plan->target_account_type)) }}</span>
                                @endif
                                <h3>{{ $plan->name }}</h3>
                                <h5>Up to {{ $limit }} {{ \Illuminate\Support\Str::plural('property', $limit) }}</h5>
                            </div>
                            <div class="price_table_content">
                                <p class="desc">{{ $bandCopy[0] }}</p>
                                <p class="price">
                                    <span class="js-plan-price"
                                        data-monthly="{{ $plan->formattedMonthlyPrice() }}"
                                        data-annual="{{ $plan->formattedAnnualPrice() }}">
                                        {{ $plan->formattedMonthlyPrice() }}
                                    </span>
                                    <span class="js-plan-period">/month</span>
                                </p>
                                <p class="trial_note">{{ $plan->trial_days }}-day free trial</p>

                                <div class="pricing_features_wrapper">
                                    <ul class="pricing_features">
                                        @if($bandCopy[1] && $lowerBandShown)
                                            <li class="is-lead">{{ $bandCopy[1] }}</li>
                                        @endif
                                        @foreach($features as $feature)
                                            <li>{{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <a class="btn btn_secondary pricing_btn js-register-plan"
                                href="{{ route('register', ['plan_id' => $plan->id, 'billing_cycle' => 'monthly', 'account_type' => $plan->target_account_type]) }}"
                                data-monthly-url="{{ route('register', ['plan_id' => $plan->id, 'billing_cycle' => 'monthly', 'account_type' => $plan->target_account_type]) }}"
                                data-annual-url="{{ route('register', ['plan_id' => $plan->id, 'billing_cycle' => 'annual', 'account_type' => $plan->target_account_type]) }}">
                                Start Free Trial
                            </a>
                        </div>
                    @endforeach
                </div>

                <div class="pricing_portfolio">
                    <p class="pricing_kicker">Enterprise &amp; Large Agencies</p>
                    <h3>Portfolio — Custom Pricing</h3>
                    <p>For portfolios above 50 properties or businesses with custom requirements (Data migration, custom integrations, volume pricing).</p>
                    <button type="button" class="btn btn_secondary" data-bs-toggle="modal" data-bs-target="#bookDemoModal">Contact Sales</button>
                </div>

                @php $trialDays = (int) $plans->max('trial_days'); @endphp
                <p class="pricing_trial_footer">Eligible plans include a {{ $trialDays }}-day free trial. No card details required to start.</p>
                <p class="pricing_trial_footer">Prices shown exclude VAT where applicable.</p>
            </div>
        @endif
    </section>

    <section class="pricing_section pricing_page_section addons_section">
        <div class="container pricing_container">
            <div class="pricing_intro">
                <h2>Optional Add-Ons</h2>
                <p class="pricing_lead">Expand your plan as your requirements grow. Simple, clear, and transparent.</p>
            </div>

            @if($addons->isNotEmpty())
                <div class="pricing_tables dynamic-pricing-tables pricing_cards pricing_addon_cards">
                    @foreach($addons as $addon)
                        @php
                            $addonBlurbs = [
                                'extra_property' => 'Add individual properties as your portfolio expands.',
                                'extra_staff' => 'Grant access to managers, co-landlords or staff.',
                                'extra_branch' => 'Separate operations across regional offices.',
                                'property_manager' => 'Grant access to managers, co-landlords or staff.',
                            ];
                        @endphp
                        <div class="price_table addon_table">
                            <div class="price_table_header">
                                <h3>{{ $addon->name }}</h3>
                            </div>
                            <div class="price_table_content">
                                <p class="price addon-price">
                                    <span class="js-plan-price"
                                        data-monthly="{{ $addon->formattedMonthlyPrice() }}"
                                        data-annual="{{ $addon->formattedAnnualPrice() }}">
                                        {{ $addon->formattedMonthlyPrice() }}
                                    </span>
                                    <span class="js-plan-period">/month</span>
                                </p>
                                <p class="desc">{{ $addonBlurbs[$addon->code] ?? 'Expand your plan as your requirements grow.' }}</p>
                            </div>
                        </div>
                    @endforeach
                    <div class="price_table addon_table">
                        <div class="price_table_header">
                            <h3>Assisted Import / Setup</h3>
                        </div>
                        <div class="price_table_content">
                            <p class="price addon-price">Custom Pricing</p>
                            <p class="desc">Dedicated data migration and workspace onboarding.</p>
                        </div>
                        <button type="button" class="btn btn_secondary pricing_btn" data-bs-toggle="modal" data-bs-target="#bookDemoModal">Contact Sales</button>
                    </div>
                </div>
            @else
                <div class="alert alert-info text-center">No active addons are available right now.</div>
            @endif
        </div>
    </section>

    <section class="pricing_section pricing_page_section">
        <div class="container pricing_container">
            <div class="pricing_intro">
                <h2>Compare Plan Features</h2>
                <p class="pricing_lead">Detailed breakdown of what is included in every plan tier.</p>
            </div>
            <div class="table-responsive">
                <table class="table pricing_compare">
                    <thead>
                        <tr>
                            <th>Feature</th>
                            <th>Starter</th>
                            <th>Growth</th>
                            <th>Pro</th>
                            <th>Business</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach([
                            ['Managed Properties', '3', '10', '25', '50'],
                            ['Tenancies & Lease Tracking', '✓', '✓', '✓', '✓'],
                            ['Rent Tracking & Ledgers', '✓', '✓', '✓', '✓'],
                            ['Repairs & Maintenance', '✓', '✓', '✓', '✓'],
                            ['Compliance Tracking', '✓', '✓', '✓', '✓'],
                            ['Document Vault', '✓', '✓', '✓', '✓'],
                            ['Tenant Portal Access', '✓', '✓', '✓', '✓'],
                            ['Contractor Portal Access', '—', '✓', '✓', '✓'],
                            ['Property CSV Imports', '—', '✓', '✓', '✓'],
                            ['Team Access', '—', 'Limited', '✓', '✓'],
                            ['Advanced Permissions', '—', '—', '✓', '✓'],
                            ['Portfolio Reporting Export', '—', '—', '✓', '✓'],
                            ['Bulk Actions', '—', '—', '✓', '✓'],
                            ['Priority Onboarding', '—', '—', '—', '✓'],
                        ] as $row)
                            <tr>
                                @foreach($row as $cell)
                                    <td>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="pricing_section pricing_page_section">
        <div class="container pricing_container">
            <div class="pricing_intro">
                <h2>Frequently Asked Questions</h2>
            </div>
            <div class="pricing_faq">
                <div>
                    <h3>Is there a free trial?</h3>
                    <p>Yes. Eligible plans include a {{ $trialDays ?? ($plans->max('trial_days') ?: 14) }}-day free trial. No card details required to start.</p>
                </div>
                <div>
                    <h3>Do you take a percentage of rent?</h3>
                    <p>No. ResiSquare charges a flat subscription, never a percentage of your rental income.</p>
                </div>
                <div>
                    <h3>Can I change plans later?</h3>
                    <p>Yes. You can upgrade your plan instantly as your portfolio grows.</p>
                </div>
                <div>
                    <h3>Is ResiSquare suitable for independent agents?</h3>
                    <p>Yes. Pro, Business, and Portfolio plans are built specifically for professional landlords and independent letting agencies.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="pricing_section pricing_page_section">
        <div class="container pricing_container">
            <div class="pricing_intro">
                <h2>Ready to Bring Your Portfolio Together?</h2>
                <p class="pricing_lead">Manage your properties, tenancies, rent, repairs, compliance and documents from one connected workspace.</p>
                <div class="pricing_cta">
                    @php $entryPlan = $plans->first(); @endphp
                    @if($entryPlan)
                        <a class="btn btn_secondary" href="{{ route('register', ['plan_id' => $entryPlan->id, 'billing_cycle' => 'monthly', 'account_type' => $entryPlan->target_account_type]) }}">Start {{ $trialDays ?? ($entryPlan->trial_days ?: 14) }}-Day Free Trial</a>
                    @endif
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bookDemoModal">Book a Demo</button>
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
                        submitText="Book Now" successMessage="Thank you! We will contact you shortly." />
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
        .pricing_kicker {
            margin: 0 0 8px;
            color: var(--secondary, #ff5c1d);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .pricing_lead {
            max-width: 720px;
            margin: 12px auto 0;
            color: var(--primary-600, #475569);
            font-size: 16px;
            line-height: 1.5;
        }
        .pricing_save {
            display: inline-block;
            margin-left: 6px;
            padding: 2px 8px;
            border-radius: 999px;
            background: rgba(255, 92, 29, 0.12);
            color: var(--secondary, #ff5c1d);
            font-size: 11px;
            font-weight: 700;
        }
        .billing-toggle .btn.active .pricing_save {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }
        .pricing_portfolio {
            margin-top: 24px;
            padding: 24px;
            border: 1px solid var(--primary-200, #e2e8f0);
            border-radius: 8px;
            background: #fff;
            text-align: center;
        }
        .pricing_portfolio h3 {
            margin: 0 0 8px;
        }
        .pricing_faq {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            text-align: left;
        }
        .pricing_faq h3 {
            margin: 0 0 6px;
            font-size: 16px;
        }
        .pricing_faq p,
        .pricing_portfolio p {
            margin: 0;
            color: var(--primary-600, #475569);
        }
        .pricing_cta {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }
        .pricing_compare th,
        .pricing_compare td {
            text-align: center;
        }
        .pricing_compare td:first-child,
        .pricing_compare th:first-child {
            text-align: left;
        }
        .pricing_features li.is-lead {
            font-weight: 700;
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
            node.textContent = cycle === 'annual' ? '/year' : '/month';
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
