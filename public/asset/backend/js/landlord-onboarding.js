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
    const ownerList = root.querySelector('[data-lob-owners]');
    const laterBtns = root.querySelectorAll('[data-lob-later]');
    const postcodeInput = document.getElementById('lob-postcode');
    const doneCopy = root.querySelector('[data-lob-done-copy]');
    const openProperty = root.querySelector('[data-lob-open-property]');
    const stage = root.querySelector('[data-lob-stage]');
    const busyBar = root.querySelector('[data-lob-busybar]');
    const busyLabel = root.querySelector('[data-lob-busy-label]');
    const searchBtn = root.querySelector('[data-lob-search-btn]');

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
            el.disabled = isBusy;
        });
    }

    function setUploadBusy(kind, isUploading, fileName) {
        const status = root.querySelector(`[data-lob-file-status="${kind}"]`);
        const nameEl = root.querySelector(`[data-lob-file-name="${kind}"]`);
        const box = nameEl?.closest('.lob-upload');
        box?.classList.toggle('is-uploading', isUploading);
        if (status) {
            status.hidden = !isUploading;
        }
        if (isUploading && nameEl) {
            nameEl.textContent = fileName ? `Selected ${fileName}` : 'Uploading…';
        }
    }

    function closeModal() {
        document.body.classList.remove('lob-open');
        root.remove();
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
        const next = Math.max(1, Math.min(5, Number(step) || 1));
        state.step = next;
        root.querySelectorAll('[data-lob-screen]').forEach((screen) => {
            screen.hidden = Number(screen.dataset.lobScreen) !== next;
        });
        root.querySelectorAll('[data-lob-step-item]').forEach((item) => {
            const itemStep = Number(item.dataset.lobStepItem);
            item.classList.toggle('is-current', itemStep === next);
            item.classList.toggle('is-complete', itemStep < next || next === 5);
        });
        root.querySelectorAll('[data-lob-actions]').forEach((row) => {
            row.hidden = Number(row.dataset.lobActions) !== next;
        });
        laterBtns.forEach((btn) => {
            btn.hidden = next === 5;
        });
        if (persist !== false && next <= 4) {
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
        ].filter((pair) => pair[1]);
        selectedFacts.innerHTML = facts.map(([label, value]) => (
            `<div><dt>${label}</dt><dd>${value}</dd></div>`
        )).join('');
    }

    function ownerRow(owner) {
        const wrap = document.createElement('div');
        wrap.className = 'lob-owner';
        wrap.innerHTML = `
            <input type="text" name="name" placeholder="Name" value="${owner?.name || ''}">
            <input type="email" name="email" placeholder="Email" value="${owner?.email || ''}">
            <input type="tel" name="phone" placeholder="Phone" value="${owner?.phone || ''}">
            <button type="button" class="lob-owner-remove">Remove</button>
        `;
        wrap.querySelector('.lob-owner-remove').addEventListener('click', () => wrap.remove());
        return wrap;
    }

    function ensureOwnerRow() {
        if (!ownerList.children.length) {
            ownerList.appendChild(ownerRow());
        }
    }

    function renderDocuments() {
        ['photo_id', 'proof_of_address'].forEach((kind) => {
            const doc = state.documents?.[kind];
            const nameEl = root.querySelector(`[data-lob-file-name="${kind}"]`);
            const box = nameEl?.closest('.lob-upload');
            if (doc && nameEl) {
                nameEl.textContent = doc.name;
                box?.classList.add('is-ready');
            }
        });
    }

    function renderTenancy() {
        if (!state.tenancy) {
            return;
        }
        const form = root.querySelector('[data-lob-tenancy]');
        form.name.value = state.tenancy.name || '';
        form.email.value = state.tenancy.email || '';
        form.phone.value = state.tenancy.phone || '';
    }

    function hydrate() {
        renderFacts(state.property);
        renderDocuments();
        (state.owners || []).forEach((owner) => ownerList.appendChild(ownerRow(owner)));
        ensureOwnerRow();
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

    root.querySelectorAll('[data-lob-file]').forEach((input) => {
        input.addEventListener('change', async () => {
            if (!input.files[0]) {
                return;
            }
            showAlert('');
            setUploadBusy(input.dataset.lobFile, true, input.files[0].name);
            const body = new FormData();
            body.append('kind', input.dataset.lobFile);
            body.append('file', input.files[0]);
            setBusy(true, { label: 'Uploading document…' });
            try {
                const payload = await request(root.dataset.document, { method: 'POST', body });
                state.documents = state.documents || {};
                state.documents[input.dataset.lobFile] = payload.data;
                renderDocuments();
            } catch (error) {
                input.value = '';
                const nameEl = root.querySelector(`[data-lob-file-name="${input.dataset.lobFile}"]`);
                if (nameEl && !state.documents?.[input.dataset.lobFile]) {
                    nameEl.textContent = 'No file yet';
                }
                showAlert(error.message);
            } finally {
                setUploadBusy(input.dataset.lobFile, false);
                setBusy(false);
            }
        });
    });

    root.querySelector('[data-lob-add-owner]').addEventListener('click', () => {
        ownerList.appendChild(ownerRow());
    });

    async function saveOwnersAndContinue() {
        const owners = Array.from(ownerList.querySelectorAll('.lob-owner')).map((row) => ({
            name: row.querySelector('[name="name"]').value.trim(),
            email: row.querySelector('[name="email"]').value.trim(),
            phone: row.querySelector('[name="phone"]').value.trim(),
        })).filter((owner) => owner.name || owner.email || owner.phone);

        if (!owners.length) {
            goTo(4);
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
                body: JSON.stringify({ owners }),
            });
            state.owners = payload.data;
            goTo(4);
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
            return 'Tenancy saved without sending an invite.';
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

        if (!withTenancy && (name || email)) {
            if (!name || !email) {
                showAlert('Add both a tenant name and email, or clear the form to skip.');
                return;
            }
        }

        showAlert('');
        setBusy(true, {
            label: withTenancy ? 'Inviting tenant…' : 'Finishing setup…',
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
            goTo(5, false);
        } catch (error) {
            showAlert(error.message);
        } finally {
            setBusy(false);
        }
    }

    root.querySelector('[data-lob-next="3"]').addEventListener('click', () => {
        if (!state.documents?.photo_id || !state.documents?.proof_of_address) {
            showAlert('Upload both a photo ID and a proof of address to continue.');
            return;
        }
        showAlert('');
        goTo(3);
    });

    root.querySelector('[data-lob-save-owners]').addEventListener('click', saveOwnersAndContinue);
    root.querySelector('[data-lob-skip="4"]').addEventListener('click', () => goTo(4));
    root.querySelector('[data-lob-save-tenancy]').addEventListener('click', () => finish(true));
    root.querySelector('[data-lob-skip-complete]').addEventListener('click', () => finish(false));
    root.querySelector('[data-lob-done-close]').addEventListener('click', closeModal);

    root.querySelectorAll('[data-lob-back]').forEach((button) => {
        button.addEventListener('click', () => goTo(Math.max(1, (state.step || 2) - 1)));
    });

    laterBtns.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.body.contains(root)) {
            closeModal();
        }
    });

    hydrate();
})();
