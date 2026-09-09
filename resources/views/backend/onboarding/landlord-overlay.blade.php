@php
    $lobAccount = current_account();
    $lobUser = auth()->user();
    $lobService = app(\App\Services\Onboarding\LandlordOnboardingService::class);
    $lobState = $lobService->state($lobAccount, $lobUser);
@endphp

<link rel="stylesheet" href="{{ asset('asset/backend/css/landlord-onboarding.css') }}?v={{ filemtime(public_path('asset/backend/css/landlord-onboarding.css')) }}">
<div
    id="lob-root"
    class="lob-root"
    data-step="{{ $lobState['step'] }}"
    data-csrf="{{ csrf_token() }}"
    data-search="{{ route('admin.onboarding.landlord.search') }}"
    data-property="{{ route('admin.onboarding.landlord.property') }}"
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
                    <p class="lob-help">Search by postcode. We match Chimnie with free UK open data (postcodes, local authority, EPC where available) so the property record is complete.</p>
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

                @if($lobState['test_mode'])
                    <p class="lob-test">Test mode: try <strong>SW1A 1AA</strong> or <strong>E14 9RU</strong>.</p>
                @endif

                <div class="lob-results" data-lob-results hidden></div>
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
