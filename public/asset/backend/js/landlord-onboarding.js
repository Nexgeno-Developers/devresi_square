(function () {
    const root = document.getElementById('lob-root');
    if (!root) {
        return;
    }

    document.body.classList.add('lob-open');

    const csrf = root.dataset.csrf;
    const bootstrap = JSON.parse(document.getElementById('lob-bootstrap').textContent || '{}');
    const alertEl = root.querySelector('[data-lob-alert]');
    const resultsEl = root.querySelector('[data-lob-results]');
    const selectedEl = root.querySelector('[data-lob-selected]');
    const selectedAddress = root.querySelector('[data-lob-selected-address]');
    const selectedFacts = root.querySelector('[data-lob-selected-facts]');
    const sourcesEl = root.querySelector('[data-lob-sources]');
    const ownerList = root.querySelector('[data-lob-owners]');
    const occupantList = root.querySelector('[data-lob-occupants]');
    const laterBtns = root.querySelectorAll('[data-lob-later]');
    const postcodeInput = document.getElementById('lob-postcode');
    const doneCopy = root.querySelector('[data-lob-done-copy]');
    const openProperty = root.querySelector('[data-lob-open-property]');
    const stage = root.querySelector('[data-lob-stage]');
    const busyBar = root.querySelector('[data-lob-busybar]');
    const busyLabel = root.querySelector('[data-lob-busy-label]');
    const searchBtn = root.querySelector('[data-lob-search-btn]');
    const leadOwnerForm = root.querySelector('[data-lob-lead-owner]');

    let state = bootstrap;
    let lastPostcode = state.property?.postcode || '';
    let busy = false;
    let activeButton = null;

    function showAlert(message) {
        if (!message) {
            alertEl.hidden = true;
            alertEl.textContent = '';
            return;
        }
        alertEl.hidden = false;
        alertEl.textContent = message;
    }

    function firstError(payload) {
        if (!payload) {
            return 'Something went wrong. Try again.';
        }
        if (payload.message && !payload.errors) {
            return payload.message;
        }
        const errors = payload.errors || {};
        const first = Object.values(errors)[0];
        if (Array.isArray(first) && first[0]) {
            return first[0];
        }
        return payload.message || 'Something went wrong. Try again.';
    }

    async function request(url, options = {}) {
        const headers = Object.assign({
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
        }, options.headers || {});

        const response = await fetch(url, Object.assign({}, options, { headers, credentials: 'same-origin' }));
        const payload = await response.json().catch(() => null);

        if (!response.ok || (payload && payload.ok === false)) {
            throw new Error(firstError(payload));
        }

        return payload;
    }

    function setBusy(isBusy, options = {}) {
        busy = isBusy;
        root.classList.toggle('is-busy', isBusy);
        stage?.setAttribute('aria-busy', isBusy ? 'true' : 'false');

        if (busyBar) {
            busyBar.hidden = !isBusy;
        }
        if (busyLabel) {
            busyLabel.hidden = !isBusy;
            busyLabel.textContent = options.label || 'Working…';
        }

        if (activeButton && !isBusy) {
            activeButton.classList.remove('is-loading');
            activeButton = null;
        }
        if (isBusy && options.button) {
            activeButton = options.button;
            activeButton.classList.add('is-loading');
        }

        root.querySelectorAll('button, input, a.lob-btn').forEach((el) => {
            if (el.matches('[data-lob-later], [data-lob-done-close]')) {
                return;
            }
            if (el.readOnly) {
                return;
            }
            el.disabled = isBusy;
        });
    }

    async function closeModal() {
        const dismissUrl = root.dataset.dismiss;
        document.body.classList.remove('lob-open');
        root.remove();

        if (!dismissUrl) {
            return;
        }

        try {
            await request(dismissUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({}),
            });
        } catch (error) {
            // Overlay is already gone; next page load will retry the session flag.
        }
    }

    function updateDashboard(data) {
        const counts = data?.counts || {};
        const map = {
            'Portfolio properties': counts.properties,
            'Active tenancies': counts.tenancies,
        };

        document.querySelectorAll('[data-dash-label]').forEach((el) => {
            const next = map[el.dataset.dashLabel];
            if (next != null) {
                el.textContent = Number(next).toLocaleString();
            }
        });
    }

    function goTo(step, persist) {
        const next = Math.max(1, Math.min(4, Number(step) || 1));
        state.step = next;
        root.querySelectorAll('[data-lob-screen]').forEach((screen) => {
            screen.hidden = Number(screen.dataset.lobScreen) !== next;
        });
        root.querySelectorAll('[data-lob-step-item]').forEach((item) => {
            const itemStep = Number(item.dataset.lobStepItem);
            item.classList.toggle('is-current', itemStep === next);
            item.classList.toggle('is-complete', itemStep < next || next === 4);
        });
        root.querySelectorAll('[data-lob-actions]').forEach((row) => {
            row.hidden = Number(row.dataset.lobActions) !== next;
        });
        laterBtns.forEach((btn) => {
            btn.hidden = next === 4;
        });
        if (persist !== false && next <= 3) {
            request(root.dataset.stepUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ step: next }),
            }).catch(() => {});
        }
    }

    function renderFacts(property) {
        if (!property) {
            selectedEl.hidden = true;
            return;
        }
        selectedEl.hidden = false;
        selectedAddress.textContent = property.label;
        const facts = [
            ['Type', property.specific_property_type],
            ['Beds', property.bedroom],
            ['Baths', property.bathroom],
            ['EPC', property.epc_rating],
            ['Tenure', property.tenure],
            ['Band', property.council_tax_band],
            ['Floor', property.floor],
            ['m²', property.square_meter],
        ].filter((pair) => pair[1]);
        selectedFacts.innerHTML = facts.map(([label, value]) => (
            `<div><dt>${label}</dt><dd>${value}</dd></div>`
        )).join('');

        const sources = property.data_sources || [];
        if (sourcesEl) {
            if (sources.length) {
                sourcesEl.hidden = false;
                sourcesEl.textContent = 'Filled from ' + sources.join(' · ');
            } else {
                sourcesEl.hidden = true;
            }
        }
    }

    function personRow(person, kind) {
        const wrap = document.createElement('div');
        wrap.className = 'lob-owner';
        wrap.innerHTML = `
            <input type="text" name="name" placeholder="Name" value="${person?.name || ''}">
            <input type="email" name="email" placeholder="Email" value="${person?.email || ''}">
            <input type="tel" name="phone" placeholder="Phone" value="${person?.phone || ''}">
            <button type="button" class="lob-owner-remove">Remove</button>
        `;
        wrap.querySelector('.lob-owner-remove').addEventListener('click', () => wrap.remove());
        return wrap;
    }

    function collectPeople(list) {
        return Array.from(list.querySelectorAll('.lob-owner')).map((row) => ({
            name: row.querySelector('[name="name"]').value.trim(),
            email: row.querySelector('[name="email"]').value.trim(),
            phone: row.querySelector('[name="phone"]').value.trim(),
        })).filter((person) => person.name || person.email || person.phone);
    }

    function renderLeadOwner() {
        if (!leadOwnerForm || !state.owner) {
            return;
        }
        leadOwnerForm.name.value = state.owner.name || '';
        leadOwnerForm.email.value = state.owner.email || '';
        leadOwnerForm.phone.value = state.owner.phone || '';
    }

    function renderTenancy() {
        const form = root.querySelector('[data-lob-tenancy]');
        if (!form) {
            return;
        }
        form.name.value = state.tenancy?.name || '';
        form.email.value = state.tenancy?.email || '';
        form.phone.value = state.tenancy?.phone || '';
        if (form.rent) {
            form.rent.value = state.tenancy?.rent || '';
        }
        if (form.deposit) {
            form.deposit.value = state.tenancy?.deposit || '';
        }
        if (form.frequency) {
            form.frequency.value = state.tenancy?.frequency || 'Monthly';
        }
        if (form.term_months) {
            form.term_months.value = state.tenancy?.term_months || 12;
        }
        if (form.move_in) {
            form.move_in.value = state.tenancy?.move_in || '';
        }
        occupantList.innerHTML = '';
        (state.tenancy?.occupants || []).forEach((person) => occupantList.appendChild(personRow(person)));
    }

    function hydrate() {
        renderFacts(state.property);
        renderLeadOwner();
        (state.owners || []).forEach((owner) => ownerList.appendChild(personRow(owner)));
        renderTenancy();
        if (state.property?.postcode && postcodeInput) {
            postcodeInput.value = state.property.postcode;
        }
        goTo(state.step || 1);
    }

    root.querySelector('[data-lob-search]').addEventListener('submit', async (event) => {
        event.preventDefault();
        showAlert('');
        lastPostcode = postcodeInput.value.trim();
        resultsEl.hidden = false;
        resultsEl.innerHTML = `
            <div class="lob-skeleton" aria-hidden="true">
                <div class="lob-skeleton-card"></div>
                <div class="lob-skeleton-card"></div>
            </div>
        `;
        setBusy(true, { label: 'Looking up that postcode…', button: searchBtn });
        try {
            const payload = await request(`${root.dataset.search}?postcode=${encodeURIComponent(lastPostcode)}`);
            const rows = payload.data || [];
            resultsEl.hidden = false;
            if (!rows.length) {
                resultsEl.innerHTML = '<p class="lob-help">No properties came back for that postcode.</p>';
                return;
            }
            resultsEl.innerHTML = rows.map((row) => `
                <button type="button" class="lob-result" data-chimnie-id="${row.id}">
                    <strong>${row.label}</strong>
                    <div class="lob-result-meta">
                        <span>${row.specific_property_type || 'property'}</span>
                        <span>${row.bedroom || '?'} bed</span>
                        <span>${row.bathroom || '?'} bath</span>
                        <span>EPC ${row.epc_rating || '—'}</span>
                        <span>${row.tenure || ''}</span>
                    </div>
                </button>
            `).join('');
            resultsEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } catch (error) {
            resultsEl.innerHTML = '';
            resultsEl.hidden = true;
            showAlert(error.message);
        } finally {
            setBusy(false);
        }
    });

    resultsEl.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-chimnie-id]');
        if (!button || busy) {
            return;
        }
        showAlert('');
        button.classList.add('is-picking');
        setBusy(true, { label: 'Saving this property…' });
        try {
            const payload = await request(root.dataset.property, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    chimnie_id: button.dataset.chimnieId,
                    postcode: lastPostcode || postcodeInput.value.trim(),
                }),
            });
            state.property = payload.data;
            renderFacts(state.property);
            goTo(2);
        } catch (error) {
            button.classList.remove('is-picking');
            showAlert(error.message);
        } finally {
            setBusy(false);
        }
    });

    root.querySelector('[data-lob-add-owner]').addEventListener('click', () => {
        ownerList.appendChild(personRow());
    });

    root.querySelector('[data-lob-add-occupant]').addEventListener('click', () => {
        occupantList.appendChild(personRow());
    });

    async function saveOwnersAndContinue(skipExtras) {
        const owner = {
            name: leadOwnerForm.name.value.trim(),
            email: leadOwnerForm.email.value.trim(),
            phone: leadOwnerForm.phone.value.trim(),
        };
        const owners = skipExtras ? [] : collectPeople(ownerList);

        if (!owner.name) {
            showAlert('Confirm the lead owner name to continue.');
            return;
        }

        setBusy(true, {
            label: 'Saving owners…',
            button: root.querySelector('[data-lob-save-owners]'),
        });
        showAlert('');
        try {
            const payload = await request(root.dataset.owners, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ owner, owners }),
            });
            state.owner = payload.data.owner || owner;
            state.owners = payload.data.owners || owners;
            goTo(3);
        } catch (error) {
            showAlert(error.message);
        } finally {
            setBusy(false);
        }
    }

    function doneMessage(completeData, tenancyData) {
        if (tenancyData?.invited && tenancyData?.sent) {
            return `Invite sent to ${tenancyData.email}.`;
        }
        if (tenancyData?.invited && !tenancyData?.sent) {
            return 'Tenancy saved, but the email did not send.';
        }
        if (tenancyData) {
            return 'Household saved without sending invites.';
        }
        if (completeData?.property?.label) {
            return `${completeData.property.label} is on your portfolio.`;
        }
        return 'It’s on your portfolio. You can keep working from here.';
    }

    async function finish(withTenancy) {
        const form = root.querySelector('[data-lob-tenancy]');
        const name = form.name.value.trim();
        const email = form.email.value.trim();
        const phone = form.phone.value.trim();
        const occupants = collectPeople(occupantList);

        const rent = parseFloat(form.rent?.value || '0');
        const deposit = parseFloat(form.deposit?.value || '0');
        const frequency = form.frequency?.value || 'Monthly';
        const termMonths = parseInt(form.term_months?.value || '12', 10);
        const moveIn = form.move_in?.value || '';

        if (!withTenancy && (name || email || occupants.length)) {
            if (!name || !email) {
                showAlert('Add both a lead tenant name and email, or clear the household to skip.');
                return;
            }
        }

        if ((withTenancy || name || email) && !(rent > 0)) {
            showAlert('Enter the rent for this tenancy.');
            return;
        }

        if (withTenancy && occupants.length && (!name || !email)) {
            showAlert('Add the lead tenant before extra occupants.');
            return;
        }

        showAlert('');
        setBusy(true, {
            label: withTenancy ? 'Inviting household…' : 'Finishing setup…',
            button: root.querySelector(withTenancy ? '[data-lob-save-tenancy]' : '[data-lob-skip-complete]'),
        });
        try {
            let tenancyData = null;
            if (withTenancy || name || email) {
                const tenancyPayload = await request(root.dataset.tenancy, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name,
                        email,
                        phone,
                        occupants,
                        rent,
                        deposit: Number.isFinite(deposit) ? deposit : 0,
                        frequency,
                        term_months: Number.isFinite(termMonths) ? termMonths : 12,
                        move_in: moveIn || null,
                        invite: withTenancy,
                    }),
                });
                tenancyData = tenancyPayload.data;
            }
            const payload = await request(root.dataset.complete, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    invited: Boolean(tenancyData?.invited),
                    sent: Boolean(tenancyData?.sent),
                    invite_error: tenancyData?.error || null,
                }),
            });
            updateDashboard(payload.data);
            if (doneCopy) {
                doneCopy.textContent = doneMessage(payload.data, tenancyData);
            }
            if (payload.data?.property?.id && openProperty) {
                const url = new URL(root.dataset.propertiesUrl, window.location.origin);
                url.searchParams.set('property_id', payload.data.property.id);
                url.searchParams.set('tabname', 'property');
                openProperty.href = url.toString();
            }
            goTo(4, false);
        } catch (error) {
            showAlert(error.message);
        } finally {
            setBusy(false);
        }
    }

    root.querySelector('[data-lob-save-owners]').addEventListener('click', () => saveOwnersAndContinue(false));
    root.querySelector('[data-lob-skip="3"]').addEventListener('click', () => saveOwnersAndContinue(true));
    root.querySelector('[data-lob-save-tenancy]').addEventListener('click', () => finish(true));
    root.querySelector('[data-lob-skip-complete]').addEventListener('click', () => finish(false));
    root.querySelector('[data-lob-done-close]').addEventListener('click', () => {
        document.body.classList.remove('lob-open');
        root.remove();
    });

    root.querySelectorAll('[data-lob-back]').forEach((button) => {
        button.addEventListener('click', () => goTo(Math.max(1, (state.step || 2) - 1)));
    });

    laterBtns.forEach((button) => {
        button.addEventListener('click', () => closeModal());
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.body.contains(root)) {
            closeModal();
        }
    });

    hydrate();
})();
