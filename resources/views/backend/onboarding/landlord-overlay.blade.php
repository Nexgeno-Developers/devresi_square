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
    data-document="{{ route('admin.onboarding.landlord.documents') }}"
    data-owners="{{ route('admin.onboarding.landlord.owners') }}"
    data-tenancy="{{ route('admin.onboarding.landlord.tenancy') }}"
    data-complete="{{ route('admin.onboarding.landlord.complete') }}"
    data-step-url="{{ route('admin.onboarding.landlord.step') }}"
    data-properties-url="{{ route('admin.properties.index') }}"
>
    <script type="application/json" id="lob-bootstrap">{!! json_encode($lobState, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>

    <div class="lob-stage" role="dialog" aria-modal="true" aria-labelledby="lob-title">
        <header class="lob-head">
            <div class="lob-head-row">
                <div>
                    <p class="lob-kicker">Setup</p>
                    <h1 id="lob-title" class="lob-title">Add your first property</h1>
                </div>
                <button type="button" class="lob-close" data-lob-later aria-label="Finish later">×</button>
            </div>
            <ol class="lob-steps" aria-label="Onboarding steps">
                <li data-lob-step-item="1" class="is-current"><span>1</span> Property</li>
                <li data-lob-step-item="2"><span>2</span> ID</li>
                <li data-lob-step-item="3"><span>3</span> Owners</li>
                <li data-lob-step-item="4"><span>4</span> Tenant</li>
            </ol>
            <div class="lob-selected" data-lob-selected hidden>
                <p class="lob-selected-address" data-lob-selected-address></p>
                <dl class="lob-facts" data-lob-selected-facts></dl>
            </div>
        </header>

        <section class="lob-panel">
            <p class="lob-alert" data-lob-alert hidden></p>

            <div class="lob-screen" data-lob-screen="1">
                <h2>Find the property</h2>
                <p class="lob-help">Enter the postcode and pick the matching address.</p>

                <form data-lob-search class="lob-search">
                    <label for="lob-postcode">Postcode</label>
                    <div class="lob-search-row">
                        <input id="lob-postcode" name="postcode" type="text" autocomplete="postal-code" placeholder="SW1A 1AA" maxlength="12">
                        <button type="submit" class="lob-btn lob-btn-primary">Search</button>
                    </div>
                </form>

                @if($lobState['test_mode'])
                    <p class="lob-test">Test mode: try <strong>SW1A 1AA</strong> or <strong>E14 9RU</strong>.</p>
                @endif

                <div class="lob-results" data-lob-results hidden></div>
            </div>

            <div class="lob-screen" data-lob-screen="2" hidden>
                <h2>Verify identity</h2>
                <p class="lob-help">Photo ID and a recent proof of address. JPG, PNG or PDF, up to 8MB.</p>

                <div class="lob-uploads">
                    <label class="lob-upload">
                        <input type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,image/*,application/pdf" data-lob-file="photo_id">
                        <span class="lob-upload-title">Photo ID</span>
                        <span class="lob-upload-copy">Passport, driving licence, or national ID.</span>
                        <span class="lob-upload-file" data-lob-file-name="photo_id">No file yet</span>
                    </label>
                    <label class="lob-upload">
                        <input type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,image/*,application/pdf" data-lob-file="proof_of_address">
                        <span class="lob-upload-title">Proof of address</span>
                        <span class="lob-upload-copy">Utility bill, bank letter, or council tax.</span>
                        <span class="lob-upload-file" data-lob-file-name="proof_of_address">No file yet</span>
                    </label>
                </div>
            </div>

            <div class="lob-screen" data-lob-screen="3" hidden>
                <h2>Other owners</h2>
                <p class="lob-help">Add co-owners if you share the title. You can skip this.</p>

                <div class="lob-owner-list" data-lob-owners></div>
                <button type="button" class="lob-btn lob-btn-ghost" data-lob-add-owner>Add another owner</button>
            </div>

            <div class="lob-screen" data-lob-screen="4" hidden>
                <h2>Current tenant</h2>
                <p class="lob-help">Name, email and phone are enough for now. Rent and dates can wait.</p>

                <form data-lob-tenancy class="lob-form">
                    <label>
                        Tenant name
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
                </form>
            </div>

            <div class="lob-screen" data-lob-screen="5" hidden>
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
                <button type="button" class="lob-btn lob-btn-primary" data-lob-next="3">Continue</button>
            </div>
            <div class="lob-actions" data-lob-actions="3" hidden>
                <button type="button" class="lob-btn" data-lob-back>Back</button>
                <button type="button" class="lob-btn" data-lob-skip="4">Skip</button>
                <button type="button" class="lob-btn lob-btn-primary" data-lob-save-owners>Save</button>
            </div>
            <div class="lob-actions" data-lob-actions="4" hidden>
                <button type="button" class="lob-btn" data-lob-back>Back</button>
                <button type="button" class="lob-btn" data-lob-skip-complete>Skip</button>
                <button type="button" class="lob-btn lob-btn-primary" data-lob-save-tenancy>Invite tenant</button>
            </div>
            <div class="lob-actions" data-lob-actions="5" hidden>
                <button type="button" class="lob-btn" data-lob-done-close>Done</button>
                <a class="lob-btn lob-btn-primary" data-lob-open-property href="{{ route('admin.properties.index') }}">View property</a>
            </div>
        </footer>
    </div>
</div>
<script src="{{ asset('asset/backend/js/landlord-onboarding.js') }}?v={{ filemtime(public_path('asset/backend/js/landlord-onboarding.js')) }}"></script>
