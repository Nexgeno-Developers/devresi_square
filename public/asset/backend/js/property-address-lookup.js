(function () {
    'use strict';

    const lookupState = new WeakMap();

    function stateFor(wrapper) {
        if (!lookupState.has(wrapper)) {
            lookupState.set(wrapper, {
                suggestions: [],
                activeIndex: -1,
                autoSearchTimer: null,
                fullAddress: wrapper.classList.contains('supports-full-address'),
                applyingAddress: false
            });
        }

        return lookupState.get(wrapper);
    }

    function setStatus(wrapper, message, type) {
        const status = wrapper.querySelector('.address-lookup-status');

        if (!status) {
            return;
        }

        status.textContent = message;
        status.classList.toggle('is-error', type === 'error');
        status.classList.toggle('is-success', type === 'success');
    }

    function setLoading(wrapper, loading) {
        const button = wrapper.querySelector('.js-address-lookup-submit');

        if (!button) {
            return;
        }

        button.disabled = loading;
        button.setAttribute('aria-busy', loading ? 'true' : 'false');
        button.innerHTML = loading
            ? '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Searching'
            : '<i class="bi bi-search me-1" aria-hidden="true"></i>Search';
    }

    function hideResults(wrapper) {
        const results = wrapper.querySelector('.address-lookup-results');

        if (results) {
            results.classList.add('d-none');
            results.replaceChildren();
        }

        const state = stateFor(wrapper);
        state.suggestions = [];
        state.activeIndex = -1;
    }

    function showAttribution(wrapper, attribution) {
        const container = wrapper.querySelector('.address-lookup-attribution');

        if (!container) {
            return;
        }

        container.replaceChildren();

        if (!attribution || !attribution.label || !attribution.url) {
            container.classList.add('d-none');
            return;
        }

        const link = document.createElement('a');
        link.href = attribution.url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = attribution.label;

        container.appendChild(link);
        container.classList.remove('d-none');
    }

    function renderSuggestions(wrapper, suggestions) {
        const results = wrapper.querySelector('.address-lookup-results');
        const state = stateFor(wrapper);

        if (!results) {
            return;
        }

        results.replaceChildren();
        state.suggestions = Array.isArray(suggestions) ? suggestions : [];
        state.activeIndex = -1;

        state.suggestions.forEach(function (suggestion, index) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'list-group-item list-group-item-action address-lookup-result';
            button.setAttribute('role', 'option');
            button.dataset.addressIndex = String(index);
            button.textContent = suggestion.label;
            results.appendChild(button);
        });

        results.classList.toggle('d-none', state.suggestions.length === 0);
    }

    function setField(form, name, value) {
        const field = form.querySelector('[name="' + name + '"]');

        if (!field || value === null || typeof value === 'undefined') {
            return;
        }

        field.value = value;
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function setCountry(form, address) {
        const field = form.querySelector('[name="country"]');

        if (!field) {
            return;
        }

        if (field.tagName !== 'SELECT') {
            setField(form, 'country', address.country || 'United Kingdom');
            return;
        }

        const countryCode = String(address.country_code || 'GB').toUpperCase();
        const countryName = String(address.country || '').toLowerCase();
        const matchingOption = Array.from(field.options).find(function (option) {
            return String(option.dataset.countryCode || '').toUpperCase() === countryCode
                || (countryName !== '' && option.textContent.trim().toLowerCase() === countryName);
        });

        if (matchingOption) {
            field.value = matchingOption.value;
            field.dispatchEvent(new Event('change', { bubbles: true }));

            if (window.jQuery) {
                window.jQuery(field).trigger('change.select2');
            }
        }
    }

    function applyAddress(wrapper, address) {
        const form = wrapper.closest('form');

        if (!form || !address) {
            return;
        }

        const state = stateFor(wrapper);
        state.applyingAddress = true;

        try {
            setField(form, 'line_1', address.line_1 || '');
            setField(form, 'line_2', address.line_2 || '');
            setField(form, 'city', address.city || '');
            setField(form, 'county', address.county || '');
            setField(form, 'postcode', address.postcode || '');
            setField(form, 'uprn', address.uprn || '');
            setCountry(form, address);
        } finally {
            state.applyingAddress = false;
        }

        form.querySelector('.address-manual-fields')?.classList.remove('d-none');
        hideResults(wrapper);

        const lineOne = form.querySelector('[name="line_1"]');

        if (!address.line_1) {
            setStatus(wrapper, 'Postcode selected. Please enter the building and street in Address Line 1.', 'success');
            lineOne?.focus();
            return;
        }

        setStatus(wrapper, 'Address selected. Please check the populated fields.', 'success');
    }

    function clearLookupIdentityAfterManualEdit(event) {
        if (!event.target.matches(
            '[name="line_1"], [name="line_2"], [name="city"], [name="county"], [name="postcode"], [name="country"]'
        )) {
            return;
        }

        const form = event.target.closest('form');
        const wrapper = form?.querySelector('.property-address-lookup');

        if (!wrapper || stateFor(wrapper).applyingAddress) {
            return;
        }

        const uprn = form.querySelector('[name="uprn"]');

        if (uprn) {
            uprn.value = '';
        }
    }

    async function jsonRequest(url) {
        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const payload = await response.json().catch(function () {
            return {};
        });

        if (!response.ok) {
            throw new Error(payload.message || 'Address search failed. Please enter the address manually.');
        }

        return payload;
    }

    async function search(wrapper) {
        const input = wrapper.querySelector('.js-address-lookup-query');
        const query = input ? input.value.trim() : '';

        if (query.length < 2) {
            hideResults(wrapper);
            setStatus(wrapper, 'Enter at least two characters or a UK postcode.', 'error');
            input?.focus();
            return;
        }

        setLoading(wrapper, true);
        hideResults(wrapper);
        setStatus(wrapper, 'Searching for UK addresses…');

        try {
            const url = new URL(wrapper.dataset.searchUrl, window.location.origin);
            url.searchParams.set('q', query);
            const payload = await jsonRequest(url);
            const suggestions = Array.isArray(payload.suggestions) ? payload.suggestions : [];
            const state = stateFor(wrapper);

            state.fullAddress = Boolean(payload.full_address);
            showAttribution(wrapper, payload.attribution);
            renderSuggestions(wrapper, suggestions);

            if (!suggestions.length) {
                setStatus(wrapper, 'No address was found. Check the postcode or enter the address manually.', 'error');
            } else if (state.fullAddress) {
                setStatus(
                    wrapper,
                    suggestions.length + ' address' + (suggestions.length === 1 ? '' : 'es') + ' found. Select yours below.'
                );
            } else {
                setStatus(
                    wrapper,
                    'The postcode was found, but this provider does not include individual premises. Select it, then enter the building and street.',
                    'error'
                );
            }
        } catch (error) {
            setStatus(wrapper, error.message, 'error');
        } finally {
            setLoading(wrapper, false);
        }
    }

    async function selectSuggestion(wrapper, index) {
        const suggestion = stateFor(wrapper).suggestions[index];

        if (!suggestion) {
            return;
        }

        if (suggestion.address) {
            applyAddress(wrapper, suggestion.address);
            return;
        }

        setLoading(wrapper, true);
        setStatus(wrapper, 'Loading the selected address…');

        try {
            const url = new URL(wrapper.dataset.resolveUrl, window.location.origin);
            url.searchParams.set('id', suggestion.id);
            const payload = await jsonRequest(url);
            applyAddress(wrapper, payload.address);
        } catch (error) {
            setStatus(wrapper, error.message, 'error');
        } finally {
            setLoading(wrapper, false);
        }
    }

    function moveActiveResult(wrapper, direction) {
        const state = stateFor(wrapper);

        if (!state.suggestions.length) {
            return;
        }

        state.activeIndex = (state.activeIndex + direction + state.suggestions.length) % state.suggestions.length;

        wrapper.querySelectorAll('.address-lookup-result').forEach(function (button, index) {
            const active = index === state.activeIndex;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');

            if (active) {
                button.focus();
            }
        });
    }

    document.addEventListener('click', function (event) {
        const searchButton = event.target.closest('.js-address-lookup-submit');

        if (searchButton) {
            search(searchButton.closest('.property-address-lookup'));
            return;
        }

        const manualButton = event.target.closest('.js-address-manual-toggle');

        if (manualButton) {
            const wrapper = manualButton.closest('.property-address-lookup');
            const form = wrapper.closest('form');
            form?.querySelector('.address-manual-fields')?.classList.remove('d-none');
            form?.querySelector('[name="line_1"]')?.focus();
            setStatus(wrapper, 'Manual address entry enabled.');
            return;
        }

        const resultButton = event.target.closest('.address-lookup-result');

        if (resultButton) {
            selectSuggestion(
                resultButton.closest('.property-address-lookup'),
                Number(resultButton.dataset.addressIndex)
            );
            return;
        }

        document.querySelectorAll('.property-address-lookup').forEach(function (wrapper) {
            if (!wrapper.contains(event.target)) {
                hideResults(wrapper);
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        const wrapper = event.target.closest('.property-address-lookup');

        if (!wrapper) {
            return;
        }

        if (event.target.matches('.js-address-lookup-query') && event.key === 'Enter') {
            event.preventDefault();
            search(wrapper);
            return;
        }

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            moveActiveResult(wrapper, event.key === 'ArrowDown' ? 1 : -1);
            return;
        }

        if (event.key === 'Escape') {
            hideResults(wrapper);
            wrapper.querySelector('.js-address-lookup-query')?.focus();
        }
    });

    document.addEventListener('input', function (event) {
        clearLookupIdentityAfterManualEdit(event);

        if (!event.target.matches('.js-address-lookup-query')) {
            return;
        }

        const wrapper = event.target.closest('.property-address-lookup');
        const state = stateFor(wrapper);
        const postcode = event.target.value.toUpperCase().replace(/\s+/g, '');
        const isCompletePostcode = /^(GIR0AA|[A-Z]{1,2}[0-9][A-Z0-9]?[0-9][A-Z]{2})$/.test(postcode);

        window.clearTimeout(state.autoSearchTimer);

        if (wrapper.dataset.autoSearchPostcode !== 'true' || !isCompletePostcode) {
            return;
        }

        state.autoSearchTimer = window.setTimeout(function () {
            search(wrapper);
        }, 450);
    });

    document.addEventListener('change', clearLookupIdentityAfterManualEdit);
}());
