@php
    $lobAccount = current_account();
    $lobUser = auth()->user();
    $lobService = app(\App\Services\Onboarding\LandlordOnboardingService::class);
    $lobState = $lobService->state($lobAccount, $lobUser);
    $lobCountries = \App\Models\Country::query()->orderBy('name')->get(['id', 'code', 'name']);
    $lobPropertyTypes = [
        'house' => 'House',
        'flat' => 'Flat',
        'appartment' => 'Apartment',
        'bunglow' => 'Bungalow',
        'studio' => 'Studio',
        'maisonette' => 'Maisonette',
        'hmo' => 'HMO',
    ];
    $lobUkSelected = false;
@endphp

<link rel="stylesheet" href="{{ asset('asset/backend/css/landlord-onboarding.css') }}?v={{ filemtime(public_path('asset/backend/css/landlord-onboarding.css')) }}">
<div
    id="lob-root"
    class="lob-root"
    data-step="{{ $lobState['step'] }}"
    data-csrf="{{ csrf_token() }}"
    data-search="{{ route('admin.onboarding.landlord.search') }}"
    data-property="{{ route('admin.onboarding.landlord.property') }}"
    data-property-manual="{{ route('admin.onboarding.landlord.property.manual') }}"
    data-owners="{{ route('admin.onboarding.landlord.owners') }}"
    data-tenancy="{{ route('admin.onboarding.landlord.tenancy') }}"
    data-complete="{{ route('admin.onboarding.landlord.complete') }}"
    data-step-url="{{ route('admin.onboarding.landlord.step') }}"
    data-dismiss="{{ route('admin.onboarding.landlord.dismiss') }}"
    data-properties-url="{{ route('admin.properties.index') }}"
>
    <script type="application/json" id="lob-bootstrap">{!! json_encode($lobState, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>

    <div class="lob-stage" role="dialog" aria-modal="true" aria-labelledby="lob-title" data-lob-stage>
        <div class="lob-busybar" data-lob-busybar hidden></div>
        <p class="lob-busy-copy" data-lob-busy-label hidden aria-live="polite">Working…</p>
        <header class="lob-head">
            <div class="lob-head-row">
                <div>
                    <p class="lob-kicker">{{ !empty($lobState['add_mode']) ? 'Add property' : 'Portfolio setup' }}</p>
                    <h1 id="lob-title" class="lob-title">{{ !empty($lobState['add_mode']) ? 'Add another property' : 'Add your first property' }}</h1>
                    <p class="lob-manual-lead" data-lob-manual-lead hidden>Enter the property details below. Add properties across the UK, Europe, the US and the Middle East.</p>
                </div>
                <button type="button" class="lob-close" data-lob-later aria-label="Finish later">×</button>
            </div>
            <ol class="lob-steps" aria-label="Onboarding steps">
                <li data-lob-step-item="1" class="is-current"><span>1</span> Property</li>
                <li data-lob-step-item="2"><span>2</span> Owners</li>
                <li data-lob-step-item="3"><span>3</span> Tenants</li>
            </ol>
            <div class="lob-selected" data-lob-selected hidden>
                <p class="lob-selected-address" data-lob-selected-address></p>
                <dl class="lob-facts" data-lob-selected-facts></dl>
                <p class="lob-sources" data-lob-sources hidden></p>
            </div>
        </header>

        <section class="lob-panel">
            <p class="lob-alert" data-lob-alert hidden></p>

            <div class="lob-screen" data-lob-screen="1">
                <div class="lob-intro">
                    <h2>Find the property</h2>
                    <p class="lob-help">Search by postcode. We match UK open data (postcodes, local authority, EPC where available) so the property record is complete.</p>
                </div>

                <form data-lob-search class="lob-search">
                    <label for="lob-postcode">Postcode</label>
                    <div class="lob-search-row">
                        <input id="lob-postcode" name="postcode" type="text" autocomplete="postal-code" placeholder="SW1A 1AA" maxlength="12">
                        <button type="submit" class="lob-btn lob-btn-primary" data-lob-search-btn>
                            <span class="lob-btn-label">Search</span>
                            <span class="lob-spinner" aria-hidden="true"></span>
                        </button>
                    </div>
                </form>

                @if($lobState['test_mode'] && app()->environment(['local', 'testing']))
                    <p class="lob-hint">Try postcode <strong>SW1A 1AA</strong> or <strong>E14 9RU</strong>.</p>
                @endif

                <p class="lob-manual-cue">
                    <button type="button" data-lob-open-manual>Add property manually</button>
                    if the postcode search doesn't find it.
                </p>

                <div class="lob-results" data-lob-results hidden></div>

                <form class="lob-rooms" data-lob-rooms hidden>
                    <h2>How many rooms?</h2>
                    <p class="lob-help">Confirm the rooms at <strong data-lob-rooms-address></strong>. Change the numbers if the lookup is wrong.</p>
                    <div class="lob-rooms-grid">
                        <label>Bedrooms <span class="lob-req" aria-hidden="true">*</span>
                            <span class="lob-stepper">
                                <button type="button" data-lob-step="-1" aria-label="Fewer bedrooms">−</button>
                                <input type="number" name="bedroom" min="0" max="50" value="0" required inputmode="numeric">
                                <button type="button" data-lob-step="1" aria-label="More bedrooms">+</button>
                            </span>
                        </label>
                        <label>Bathrooms <span class="lob-req" aria-hidden="true">*</span>
                            <span class="lob-stepper">
                                <button type="button" data-lob-step="-1" aria-label="Fewer bathrooms">−</button>
                                <input type="number" name="bathroom" min="0" max="50" value="0" required inputmode="numeric">
                                <button type="button" data-lob-step="1" aria-label="More bathrooms">+</button>
                            </span>
                        </label>
                        <label>Reception rooms <span class="lob-req" aria-hidden="true">*</span>
                            <span class="lob-stepper">
                                <button type="button" data-lob-step="-1" aria-label="Fewer reception rooms">−</button>
                                <input type="number" name="reception" min="0" max="50" value="0" required inputmode="numeric">
                                <button type="button" data-lob-step="1" aria-label="More reception rooms">+</button>
                            </span>
                        </label>
                    </div>
                    <button type="submit" class="lob-btn lob-btn-primary" data-lob-rooms-save>Use this property</button>
                </form>
            </div>

            <div class="lob-screen" data-lob-screen="manual" hidden>
                <form data-lob-manual class="lob-manual">
                    <section class="lob-manual-section">
                        <h2 class="lob-manual-heading">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                            Property address
                        </h2>
                        <div class="lob-manual-grid-2">
                            <label>Address line 1 <span class="lob-req" aria-hidden="true">*</span>
                                <input type="text" name="line_1" maxlength="255" required autocomplete="address-line1" placeholder="e.g. 14 Elm Row">
                            </label>
                            <label>Address line 2
                                <input type="text" name="line_2" maxlength="255" autocomplete="address-line2" placeholder="Optional">
                            </label>
                        </div>
                        <div class="lob-manual-grid-4">
                            <label>City / town <span class="lob-req" aria-hidden="true">*</span>
                                <input type="text" name="city" maxlength="255" required autocomplete="address-level2">
                            </label>
                            <label>State / region / province
                                <input type="text" name="county" maxlength="155" autocomplete="address-level1" placeholder="Optional">
                            </label>
                            <label>Postcode / ZIP code <span class="lob-req" aria-hidden="true">*</span>
                                <input type="text" name="postcode" maxlength="20" required autocomplete="postal-code">
                            </label>
                            <label>Country <span class="lob-req" aria-hidden="true">*</span>
                                <select name="country" required>
                                    <option value="">Select country</option>
                                    @foreach ($lobCountries as $country)
                                        @php
                                            $selectCountry = ! $lobUkSelected && in_array(strtoupper((string) $country->code), ['GB', 'UK', 'GBR'], true);
                                            if ($selectCountry) {
                                                $lobUkSelected = true;
                                            }
                                        @endphp
                                        <option value="{{ $country->id }}" @selected($selectCountry)>{{ $country->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </section>

                    <section class="lob-manual-section">
                        <h2 class="lob-manual-heading">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V9.5L12 4l8 5.5V20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 20v-6h6v6M4 20h16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                            Property details
                        </h2>
                        <div class="lob-manual-details">
                            <label>Property type <span class="lob-req" aria-hidden="true">*</span>
                                <select name="specific_property_type" required>
                                    <option value="">Select property type</option>
                                    @foreach ($lobPropertyTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Bedrooms <span class="lob-req" aria-hidden="true">*</span>
                                <span class="lob-stepper">
                                    <button type="button" data-lob-step="-1" aria-label="Fewer bedrooms">−</button>
                                    <input type="number" name="bedroom" min="0" max="50" value="1" required inputmode="numeric">
                                    <button type="button" data-lob-step="1" aria-label="More bedrooms">+</button>
                                </span>
                            </label>
                            <label>Bathrooms <span class="lob-req" aria-hidden="true">*</span>
                                <span class="lob-stepper">
                                    <button type="button" data-lob-step="-1" aria-label="Fewer bathrooms">−</button>
                                    <input type="number" name="bathroom" min="0" max="50" value="1" required inputmode="numeric">
                                    <button type="button" data-lob-step="1" aria-label="More bathrooms">+</button>
                                </span>
                            </label>
                            <label>Reception rooms <span class="lob-req" aria-hidden="true">*</span>
                                <span class="lob-stepper">
                                    <button type="button" data-lob-step="-1" aria-label="Fewer reception rooms">−</button>
                                    <input type="number" name="reception" min="0" max="50" value="1" required inputmode="numeric">
                                    <button type="button" data-lob-step="1" aria-label="More reception rooms">+</button>
                                </span>
                            </label>
                            <label>Floor area
                                <span class="lob-area">
                                    <input type="number" data-lob-area min="0" max="999999" step="0.01" inputmode="decimal" placeholder="Optional" aria-label="Floor area">
                                    <span class="lob-unit" role="group" aria-label="Floor area unit">
                                        <button type="button" class="is-active" data-lob-unit="sqm" aria-pressed="true">m²</button>
                                        <button type="button" data-lob-unit="sqft" aria-pressed="false">sq ft</button>
                                    </span>
                                </span>
                            </label>
                        </div>
                    </section>

                    <div class="lob-manual-split">
                        <section class="lob-manual-section">
                            <h2 class="lob-manual-heading">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M3.5 19c.6-3 2.7-4.5 5.5-4.5S14.4 16 15 19M16 8.2a2.6 2.6 0 0 1 0 4.6M17 14.6c2.2.3 3.8 1.5 4.5 4.4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                Property status
                            </h2>
                            <label>Occupancy <span class="lob-req" aria-hidden="true">*</span>
                                <select name="letting_current_status" required>
                                    <option value="">Select occupancy status</option>
                                    <option value="available">Vacant</option>
                                    <option value="let agreed">Occupied</option>
                                    <option value="notice">Notice</option>
                                </select>
                            </label>
                        </section>
                        <section class="lob-manual-section">
                            <h2 class="lob-manual-heading">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><ellipse cx="12" cy="7" rx="7" ry="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M5 7v5c0 1.7 3.1 3 7 3s7-1.3 7-3V7M5 12v5c0 1.7 3.1 3 7 3s7-1.3 7-3v-5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
                                Rent
                            </h2>
                            <div class="lob-manual-grid-2">
                                <label>Monthly rent <span class="lob-req" aria-hidden="true">*</span>
                                    <input type="number" name="letting_price" min="0.01" max="999999.99" step="0.01" required placeholder="e.g. 1,250">
                                </label>
                                <label>Currency <span class="lob-req" aria-hidden="true">*</span>
                                    <select name="currency" required>
                                        <option value="GBP" selected>GBP (£)</option>
                                        <option value="EUR">EUR (€)</option>
                                        <option value="USD">USD ($)</option>
                                        <option value="AED">AED (د.إ)</option>
                                    </select>
                                </label>
                            </div>
                        </section>
                    </div>

                    <section class="lob-manual-section">
                        <h2 class="lob-manual-heading">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3.5h7l5 5V20a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M14 3.5V9h5M8.5 13h7M8.5 16.5h5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            Additional details
                        </h2>
                        <div class="lob-manual-grid-2">
                            <label>Property reference
                                <input type="text" name="prop_ref_no" maxlength="50" placeholder="Optional internal reference">
                            </label>
                            <label>Notes
                                <input type="text" name="imp_notes" maxlength="5000" placeholder="Optional">
                            </label>
                        </div>
                    </section>
                </form>
            </div>

            <div class="lob-screen" data-lob-screen="2" hidden>
                <h2>Confirm the owner</h2>
                <p class="lob-help">This is you, prefilled from your account. Check the details, then add co-owners if the title is shared.</p>

                <form data-lob-lead-owner class="lob-form lob-card-form">
                    <p class="lob-card-label">Lead owner</p>
                    <div class="lob-grid-3">
                        <label>
                            Full name
                            <input type="text" name="name" maxlength="120" autocomplete="name">
                        </label>
                        <label>
                            Email
                            <input type="email" name="email" maxlength="190" autocomplete="email" readonly>
                        </label>
                        <label>
                            Phone
                            <input type="tel" name="phone" maxlength="40" autocomplete="tel">
                        </label>
                    </div>
                </form>

                <p class="lob-section-title">Co-owners</p>
                <div class="lob-owner-list" data-lob-owners></div>
                <button type="button" class="lob-btn lob-btn-ghost" data-lob-add-owner>Add a co-owner</button>
            </div>

            <div class="lob-screen" data-lob-screen="3" hidden>
                <h2>Lead tenant</h2>
                <p class="lob-help">Add the household if the property is let. Enter rent and dates so the tenancy is ready to use. Skip if it is vacant.</p>

                <form data-lob-tenancy class="lob-form lob-card-form">
                    <p class="lob-card-label">Lead tenant</p>
                    <div class="lob-grid-3">
                        <label>
                            Full name
                            <input type="text" name="name" maxlength="120" placeholder="Alex Tenant" autocomplete="name">
                        </label>
                        <label>
                            Email
                            <input type="email" name="email" maxlength="190" placeholder="alex@example.com" autocomplete="email">
                        </label>
                        <label>
                            Phone
                            <input type="tel" name="phone" maxlength="40" placeholder="07123 456789" autocomplete="tel">
                        </label>
                    </div>

                    <p class="lob-card-label">Tenancy terms</p>
                    <div class="lob-grid-3">
                        <label>
                            Rent (£)
                            <input type="number" name="rent" min="0.01" step="0.01" placeholder="1250">
                        </label>
                        <label>
                            Deposit (£)
                            <input type="number" name="deposit" min="0" step="0.01" placeholder="1250">
                        </label>
                        <label>
                            Frequency
                            <select name="frequency">
                                <option value="Monthly">Monthly</option>
                                <option value="Weekly">Weekly</option>
                            </select>
                        </label>
                    </div>
                    <div class="lob-grid-3">
                        <label>
                            Start date
                            <input type="date" name="move_in">
                        </label>
                        <label>
                            Term (months)
                            <input type="number" name="term_months" min="1" max="36" value="12">
                        </label>
                    </div>
                </form>

                <p class="lob-section-title">Other tenants</p>
                <div class="lob-owner-list" data-lob-occupants></div>
                <button type="button" class="lob-btn lob-btn-ghost" data-lob-add-occupant>Add another tenant</button>
            </div>

            <div class="lob-screen" data-lob-screen="4" hidden>
                <div class="lob-done">
                    <p class="lob-done-mark" aria-hidden="true">✓</p>
                    <h2>Property added</h2>
                    <p class="lob-help" data-lob-done-copy>It’s on your portfolio. You can keep working from here.</p>
                </div>
            </div>
        </section>

        <footer class="lob-foot" data-lob-foot>
            <div class="lob-actions" data-lob-actions="1">
                <button type="button" class="lob-btn" data-lob-later>Later</button>
            </div>
            <div class="lob-actions lob-actions-manual" data-lob-actions="manual" hidden>
                <p class="lob-manual-note">
                    <span aria-hidden="true">i</span>
                    You can add tenancy, owner, compliance and document details after the property has been created.
                </p>
                <div class="lob-actions-end">
                    <button type="button" class="lob-btn" data-lob-manual-cancel>Cancel</button>
                    <button type="button" class="lob-btn lob-btn-primary" data-lob-manual-save>
                        <span class="lob-btn-label">Save Property</span>
                        <span class="lob-spinner" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
            <div class="lob-actions" data-lob-actions="2" hidden>
                <button type="button" class="lob-btn" data-lob-back>Back</button>
                <button type="button" class="lob-btn" data-lob-skip="3">Skip co-owners</button>
                <button type="button" class="lob-btn lob-btn-primary" data-lob-save-owners>
                    <span class="lob-btn-label">Save owners</span>
                    <span class="lob-spinner" aria-hidden="true"></span>
                </button>
            </div>
            <div class="lob-actions" data-lob-actions="3" hidden>
                <button type="button" class="lob-btn" data-lob-back>Back</button>
                <button type="button" class="lob-btn" data-lob-skip-complete>
                    <span class="lob-btn-label">Skip</span>
                    <span class="lob-spinner" aria-hidden="true"></span>
                </button>
                <button type="button" class="lob-btn lob-btn-primary" data-lob-save-tenancy>
                    <span class="lob-btn-label">Invite household</span>
                    <span class="lob-spinner" aria-hidden="true"></span>
                </button>
            </div>
            <div class="lob-actions" data-lob-actions="4" hidden>
                <button type="button" class="lob-btn" data-lob-done-close>Done</button>
                <a class="lob-btn lob-btn-primary" data-lob-open-property href="{{ route('admin.properties.index') }}">View property</a>
            </div>
        </footer>
    </div>
</div>
<script src="{{ asset('asset/backend/js/landlord-onboarding.js') }}?v={{ filemtime(public_path('asset/backend/js/landlord-onboarding.js')) }}"></script>
