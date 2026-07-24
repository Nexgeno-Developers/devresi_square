@php
    $activeAddressProvider = config('address_lookup.default', 'postcodes_io');
    $activeAddressProviderName = config(
        "address_lookup.providers.{$activeAddressProvider}.name",
        $activeAddressProvider
    );
    $addressSearchPlaceholder = config(
        "address_lookup.providers.{$activeAddressProvider}.placeholder",
        'Enter a UK postcode or part of an address'
    );
    $supportsFullAddress = (bool) config(
        "address_lookup.providers.{$activeAddressProvider}.full_address",
        false
    );
    $autoSearchPostcode = (bool) config(
        "address_lookup.providers.{$activeAddressProvider}.auto_search_postcode",
        false
    );
@endphp

<div class="property-address-lookup mb-4 {{ $supportsFullAddress ? 'supports-full-address' : 'postcode-only-provider' }}"
    data-search-url="{{ route('admin.properties.address_lookup.search') }}"
    data-resolve-url="{{ route('admin.properties.address_lookup.resolve') }}"
    data-auto-search-postcode="{{ $autoSearchPostcode ? 'true' : 'false' }}">
    <div class="address-lookup-heading text-center">
        <h3>Find an address</h3>
        <p>Type a UK postcode to see the available addresses</p>
    </div>
    <div class="input-group input-group-lg">
        <input type="search"
            class="form-control js-address-lookup-query"
            placeholder="{{ $addressSearchPlaceholder }}"
            aria-label="UK postcode or address"
            aria-autocomplete="list"
            autocomplete="postal-code">
        <button type="button" class="btn btn-primary js-address-lookup-submit">
            <i class="bi bi-search me-1" aria-hidden="true"></i>
            Search
        </button>
    </div>
    <div class="address-lookup-status small mt-2" role="status" aria-live="polite">
        @if ($supportsFullAddress)
            Enter a postcode to find and select the complete property address.
        @else
            {{ $activeAddressProviderName }} supplies postcode details only. Configure a full-address provider for premises.
        @endif
    </div>
    <div class="address-lookup-results list-group mt-2 d-none" role="listbox"></div>
    <div class="address-lookup-attribution small text-muted mt-2 d-none"></div>
    <button type="button" class="btn btn-link btn-sm px-0 mt-2 js-address-manual-toggle">
        Enter the address manually
    </button>

    <input type="hidden" name="uprn" value="{{ old('uprn', $property->uprn ?? '') }}">
</div>

<noscript>
    <style>.address-manual-fields { display: block !important; }</style>
</noscript>
