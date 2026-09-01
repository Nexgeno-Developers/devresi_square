(() => {
    'use strict';

    const cfg = window.pccState || {};
    const state = {
        propertyId: cfg.propertyId || null,
        tabName: cfg.tabName || 'property',
        tabs: cfg.tabs || [],
        tabCache: new Map(),
        selectedIds: new Set(),
        isPortal: !!cfg.isPortal,
    };

    const els = {
        root: document.getElementById('property-control-center'),
        listScroll: document.getElementById('pccListScroll'),
        listFooter: document.getElementById('pccListFooter'),
        tabNav: document.getElementById('pccTabNav'),
        tabContent: document.getElementById('pccTabContent'),
        bulkToolbar: document.getElementById('pccBulkToolbar'),
        bulkCount: document.getElementById('pccBulkCount'),
        filterRail: document.getElementById('pccFilterRail'),
        rightPane: document.getElementById('pccRightPane'),
        detailShell: document.getElementById('pccDetailShell'),
        emptyState: document.getElementById('pccEmptyState'),
        palette: document.getElementById('pccCommandPalette'),
    };

    function debounce(fn, ms) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    }

    function showToast(message, type = 'info') {
        if (window.AIZ?.plugins?.notify) {
            AIZ.plugins.notify(type, message);
        } else if (window.toastr && toastr[type]) {
            toastr[type](message);
        } else {
            console.log('[PCC]', message);
        }
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function tabsAllUrl(propertyId) {
        const template = cfg.tabsAllUrlTemplate || '';
        return template.replace('__ID__', String(propertyId));
    }

    function currentCards() {
        return [...(els.listScroll?.querySelectorAll('.property-card') || [])];
    }

    function init() {
        if (!els.root) return;

        bindFilters();
        bindBulkActions();
        bindKeyboard();
        bindCommandPalette();
        bindResizer();
        bindMobileBack();
        bindListDelegation();
        bindPagination();

        if (state.propertyId && state.tabs.length) {
            if (els.tabContent && state.tabName) {
                state.tabCache.set(state.tabName.toLowerCase(), {
                    html: els.tabContent.innerHTML,
                    timestamp: Date.now(),
                });
            }
            renderTabs();
            preFetchAllTabs(state.propertyId, false);
        }
    }

    function bindListDelegation() {
        if (!els.listScroll) return;

        els.listScroll.addEventListener('click', (e) => {
            const card = e.target.closest('.property-card');
            if (!card || e.target.closest('a, button, input, label.pcc-hcard-check, .pcc-hcard-actions')) return;
            selectProperty(parseInt(card.dataset.propertyId, 10));
        });

        els.listScroll.addEventListener('change', (e) => {
            if (e.target.matches('.pcc-bulk-checkbox')) {
                toggleSelect(parseInt(e.target.value, 10), e.target.checked);
            }
        });
    }

    function bindPagination() {
        if (!els.listFooter) return;
        els.listFooter.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (!link || !link.href) return;
            e.preventDefault();
            loadList(link.href);
        });
    }

    function showDetailShell() {
        els.emptyState?.setAttribute('hidden', '');
        els.detailShell?.removeAttribute('hidden');
        els.rightPane?.classList.add('pcc-detail-visible');
    }

    function hideDetailShell() {
        els.detailShell?.setAttribute('hidden', '');
        els.emptyState?.removeAttribute('hidden');
        els.rightPane?.classList.remove('pcc-detail-visible');
    }

    function selectProperty(propertyId) {
        if (!propertyId || state.propertyId === propertyId) {
            showDetailShell();
            return;
        }
        state.propertyId = propertyId;
        state.tabCache.clear();

        const url = new URL(cfg.ajaxUrl, window.location.origin);
        url.searchParams.set('property_id', propertyId);
        url.searchParams.set('tabname', state.tabName);
        url.searchParams.set('list_only', '1');
        window.history.pushState(null, '', `${cfg.ajaxUrl}?property_id=${propertyId}&tabname=${encodeURIComponent(state.tabName)}`);

        $.ajax({
            url: url.toString(),
            method: 'GET',
            dataType: 'json',
            beforeSend: () => showDetailLoading(),
            success: (res) => {
                currentCards().forEach((card) => {
                    card.classList.toggle('current', parseInt(card.dataset.propertyId, 10) === propertyId);
                });
                hydrateDetail(res);
                if (res.tabs) state.tabs = res.tabs;
                showDetailShell();
                preFetchAllTabs(propertyId, true);
            },
            error: (xhr) => {
                hideDetailLoading();
                showToast('Error loading property: ' + (xhr.responseJSON?.message || 'Unknown'), 'danger');
            },
        });
    }

    function hydrateDetail(res) {
        const headerSlot = document.getElementById('pccDetailHeaderSlot');
        if (headerSlot && res.detail_header) headerSlot.innerHTML = res.detail_header;
    }

    function showDetailLoading() {
        if (!els.rightPane) return;
        let overlay = els.rightPane.querySelector('.pcc-loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'pcc-loading-overlay';
            overlay.innerHTML = '<div class="spinner-border pcc-spinner"></div>';
            els.rightPane.appendChild(overlay);
        }
        overlay.classList.remove('d-none');
        overlay.removeAttribute('hidden');
    }

    function hideDetailLoading() {
        document.querySelectorAll('.pcc-loading-overlay').forEach((el) => el.classList.add('d-none'));
    }

    function preFetchAllTabs(propertyId, showLoading) {
        if (!propertyId) return;
        $.ajax({
            url: tabsAllUrl(propertyId),
            method: 'GET',
            beforeSend: () => { if (showLoading) showTabLoading(); },
            success: (tabsHtml) => {
                Object.entries(tabsHtml || {}).forEach(([name, html]) => {
                    state.tabCache.set(String(name).toLowerCase(), { html, timestamp: Date.now() });
                });
                renderTabs();
                hideDetailLoading();
            },
            error: () => {
                hideDetailLoading();
                showToast('Error loading tabs', 'danger');
            },
        });
    }

    function allowedTabNames() {
        return (state.tabs || []).map((t) => String(t.name).toLowerCase());
    }

    function tabGroups() {
        const allowed = new Set(allowedTabNames());
        const groups = cfg.tabGroups || [];
        return groups
            .map((group) => ({
                ...group,
                tabs: (group.tabs || []).filter((name) => allowed.has(String(name).toLowerCase())),
            }))
            .filter((group) => group.tabs.length > 0);
    }

    function groupForTab(tabName) {
        const name = String(tabName || '').toLowerCase();
        return tabGroups().find((group) => group.tabs.includes(name)) || tabGroups()[0] || null;
    }

    function renderTabs() {
        const tabs = state.tabs;
        if (!tabs.length || !els.tabNav) return;

        const groups = tabGroups();
        const current = String(state.tabName || '').toLowerCase();
        let activeGroup = groupForTab(current);
        if (activeGroup && !activeGroup.tabs.includes(current)) {
            state.tabName = activeGroup.tabs[0];
        }

        const groupHtml = groups.map((group) =>
            `<button type="button" class="pcc-tab-group ${group.id === activeGroup?.id ? 'active' : ''}"
                data-group="${group.id}">${group.label}</button>`
        ).join('');

        const subTabs = (activeGroup?.tabs || [])
            .map((name) => tabs.find((t) => String(t.name).toLowerCase() === name))
            .filter(Boolean);
        const subHtml = subTabs.length > 1
            ? `<div class="pcc-tab-subs">${subTabs.map((t) => {
                const key = String(t.name).toLowerCase();
                return `<a href="#" class="pcc-tab-link ${key === state.tabName ? 'active' : ''}"
                    data-tab="${key}" role="button">${t.name}</a>`;
            }).join('')}</div>`
            : '';

        els.tabNav.innerHTML = `<div class="pcc-tab-groups">${groupHtml}</div>${subHtml}`;

        const cached = state.tabCache.get(state.tabName);
        if (els.tabContent) {
            els.tabContent.innerHTML = cached
                ? cached.html
                : '<div class="pcc-tab-loading"><div class="spinner-border pcc-spinner"></div></div>';
        }

        if (!cached && state.propertyId) {
            fetchTab(state.tabName);
        } else {
            refreshTabComponents();
        }
    }

    function switchTab(tabName) {
        state.tabName = tabName;
        const url = new URL(window.location);
        url.searchParams.set('tabname', tabName);
        if (state.propertyId) url.searchParams.set('property_id', state.propertyId);
        window.history.replaceState(null, '', url);
        renderTabs();
    }

    function fetchTab(tabName) {
        if (!state.propertyId) return;
        $.ajax({
            url: tabsAllUrl(state.propertyId),
            method: 'GET',
            beforeSend: () => showTabLoading(),
            success: (tabsHtml) => {
                Object.entries(tabsHtml || {}).forEach(([name, html]) => {
                    state.tabCache.set(String(name).toLowerCase(), { html, timestamp: Date.now() });
                });
                const cached = state.tabCache.get(tabName);
                if (cached && els.tabContent) {
                    els.tabContent.innerHTML = cached.html;
                    refreshTabComponents();
                }
            },
            error: () => showToast('Error loading tab', 'danger'),
        });
    }

    function showTabLoading() {
        if (els.tabContent) {
            els.tabContent.innerHTML = '<div class="pcc-tab-loading"><div class="spinner-border pcc-spinner"></div></div>';
        }
    }

    function refreshTabComponents() {
        if (state.tabName === 'documents') {
            $('.documents-component').trigger('documents:refresh');
        }
        if (state.tabName === 'notes') {
            $('.notes-component').trigger('notes:refresh');
        }
    }

    document.addEventListener('click', (e) => {
        const groupBtn = e.target.closest('.pcc-tab-group');
        if (groupBtn && els.root?.contains(groupBtn)) {
            e.preventDefault();
            const group = tabGroups().find((g) => g.id === groupBtn.dataset.group);
            if (group?.tabs?.[0]) switchTab(group.tabs[0]);
            return;
        }
        const link = e.target.closest('.pcc-tab-link');
        if (!link || !els.root?.contains(link)) return;
        e.preventDefault();
        const tabName = link.dataset.tab;
        if (tabName) switchTab(tabName);
    });

    function collectFilterParams() {
        const params = new URLSearchParams();
        const search = document.getElementById('pccListSearch');
        if (search?.value.trim()) params.set('search', search.value.trim());
        if (!els.filterRail) return params;
        els.filterRail.querySelectorAll('input, select').forEach((el) => {
            if (!el.name) return;
            if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
            if (!String(el.value || '').trim()) return;
            if (el.type === 'checkbox' && params.has(el.name)) {
                params.set(el.name, params.get(el.name) + ',' + el.value);
            } else {
                params.set(el.name, el.value);
            }
        });
        return params;
    }

    function syncChipState() {
        els.filterRail?.querySelectorAll('.pcc-chip').forEach((chip) => {
            const input = chip.querySelector('input');
            chip.classList.toggle('is-on', !!(input && input.checked && input.value !== ''));
        });
        const allChip = els.filterRail?.querySelector('input[name="property_type"][value=""]')?.closest('.pcc-chip');
        if (allChip) {
            const selected = els.filterRail.querySelector('input[name="property_type"]:checked');
            allChip.classList.toggle('is-on', !selected || selected.value === '');
        }
    }

    function bindFilters() {
        const applyFilters = debounce(() => {
            const params = collectFilterParams();
            const url = cfg.ajaxUrl + '?' + params.toString() + (params.toString() ? '&' : '') + 'list_only=1';
            $.ajax({
                url,
                method: 'GET',
                dataType: 'json',
                beforeSend: () => { if (els.listScroll) els.listScroll.style.opacity = '0.5'; },
                success: (res) => {
                    if (els.listScroll && res.html) els.listScroll.innerHTML = res.html;
                    if (els.listFooter) els.listFooter.innerHTML = res.pagination || '';
                    const historyUrl = cfg.ajaxUrl + (params.toString() ? '?' + params.toString() : '');
                    window.history.replaceState(null, '', historyUrl);
                },
                complete: () => {
                    if (els.listScroll) els.listScroll.style.opacity = '1';
                },
            });
        }, 250);

        els.filterRail?.addEventListener('change', () => {
            syncChipState();
            applyFilters();
        });
        document.getElementById('pccListSearch')?.addEventListener('input', applyFilters);

        document.getElementById('pccFilterReset')?.addEventListener('click', () => {
            window.location.href = cfg.ajaxUrl;
        });
    }

    function bindBulkActions() {
        if (state.isPortal || !els.bulkToolbar) return;

        const bulkSelectAll = document.getElementById('pccBulkSelectAll');
        bulkSelectAll?.addEventListener('change', (e) => {
            const checked = e.target.checked;
            els.listScroll?.querySelectorAll('.pcc-bulk-checkbox').forEach((cb) => {
                cb.checked = checked;
                toggleSelect(parseInt(cb.dataset.id || cb.value, 10), checked);
            });
        });

        document.getElementById('pccBulkClear')?.addEventListener('click', () => {
            state.selectedIds.clear();
            updateBulkToolbar();
            els.listScroll?.querySelectorAll('.pcc-bulk-checkbox').forEach((cb) => { cb.checked = false; });
        });

        document.getElementById('pccBulkApply')?.addEventListener('click', () => {
            const action = document.getElementById('pccBulkAction')?.value;
            if (!action) return showToast('Select an action', 'warning');
            if (!state.selectedIds.size) return showToast('No properties selected', 'warning');
            executeBulkAction(action, [...state.selectedIds]);
        });
    }

    function toggleSelect(id, checked) {
        if (!id) return;
        if (checked) state.selectedIds.add(id);
        else state.selectedIds.delete(id);
        updateBulkToolbar();
    }

    function updateBulkToolbar() {
        const count = state.selectedIds.size;
        if (els.bulkCount) els.bulkCount.textContent = count + ' selected';
        if (els.bulkToolbar) els.bulkToolbar.style.display = count > 0 ? 'flex' : 'none';
        els.listScroll?.classList.toggle('pcc-bulk-on', count > 0);
    }

    function executeBulkAction(action, ids) {
        const label = action === 'delete' ? 'permanently delete' : 'archive';
        if (!confirm(`This will ${label} ${ids.length} properties. Continue?`)) return;
        $.ajax({
            url: cfg.bulkActionUrl,
            method: 'POST',
            data: { _token: csrfToken(), action, ids },
            success: (res) => {
                showToast(`Updated ${res.affected || 0} properties`, 'success');
                state.selectedIds.clear();
                updateBulkToolbar();
                loadList();
            },
            error: (xhr) => {
                showToast(xhr.responseJSON?.message || 'Bulk action failed', 'danger');
            },
        });
    }

    function loadList(href) {
        const url = href ? new URL(href, window.location.origin) : new URL(cfg.ajaxUrl, window.location.origin);
        url.searchParams.set('list_only', '1');
        if (state.propertyId) url.searchParams.set('property_id', state.propertyId);

        $.ajax({
            url: url.toString(),
            method: 'GET',
            dataType: 'json',
            success: (res) => {
                if (els.listScroll && res.html) els.listScroll.innerHTML = res.html;
                if (els.listFooter) els.listFooter.innerHTML = res.pagination || '';
            },
        });
    }

    function bindKeyboard() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeCommandPalette();
                return;
            }

            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                openCommandPalette();
                return;
            }

            const tag = document.activeElement?.tagName?.toLowerCase();
            if (['input', 'textarea', 'select'].includes(tag)) return;

            const cards = currentCards();
            if (!cards.length) return;
            const currentIdx = cards.findIndex((c) => c.classList.contains('current'));

            if (e.key === 'ArrowDown' || e.key === 'j') {
                e.preventDefault();
                const next = cards[Math.min(cards.length - 1, currentIdx + 1)];
                if (next) {
                    next.scrollIntoView({ block: 'nearest' });
                    selectProperty(parseInt(next.dataset.propertyId, 10));
                }
            } else if (e.key === 'ArrowUp' || e.key === 'k') {
                e.preventDefault();
                const prev = cards[Math.max(0, currentIdx - 1)];
                if (prev) {
                    prev.scrollIntoView({ block: 'nearest' });
                    selectProperty(parseInt(prev.dataset.propertyId, 10));
                }
            }
        });
    }

    function commandItemHtml(item) {
        const url = item.url || '#';
        return `<a href="${url}" class="pcc-command-item" data-url="${url}" data-property-id="${item.propertyId || ''}">
            <i class="bi ${item.icon || 'bi-cursor'}"></i>
            <span>${item.label}</span>
            <small class="text-muted">${item.hint || ''}</small>
        </a>`;
    }

    function renderCommandResults(items) {
        const list = document.getElementById('pccCommandResults');
        if (!list) return;
        if (!items.length) {
            list.innerHTML = '<div class="pcc-command-item text-muted">No results found</div>';
            return;
        }
        list.innerHTML = items.map(commandItemHtml).join('');
    }

    function bindCommandPalette() {
        const input = document.getElementById('pccCommandInput');
        const list = document.getElementById('pccCommandResults');
        document.getElementById('pccCommandTrigger')?.addEventListener('click', openCommandPalette);
        els.palette?.addEventListener('click', (e) => {
            if (e.target === els.palette) closeCommandPalette();
        });

        if (!input || !list) return;

        const runSearch = debounce((q) => {
            const actions = (window.pccCommandItems || []).filter((it) =>
                it.label.toLowerCase().includes(q) || (it.hint && it.hint.toLowerCase().includes(q))
            );
            if (!q) {
                renderCommandResults(window.pccCommandItems || []);
                return;
            }
            if (!cfg.searchUrl) {
                renderCommandResults(actions);
                return;
            }
            $.ajax({
                url: cfg.searchUrl,
                method: 'GET',
                data: { query: q },
                success: (properties) => {
                    const propertyItems = (properties || []).map((p) => ({
                        label: p.display_label || p.prop_name || p.prop_ref_no || ('Property #' + p.id),
                        hint: p.prop_ref_no || '',
                        icon: 'bi-building',
                        url: `${cfg.ajaxUrl}?property_id=${p.id}&tabname=property`,
                        propertyId: p.id,
                    }));
                    renderCommandResults([...propertyItems, ...actions]);
                },
                error: () => renderCommandResults(actions),
            });
        }, 120);

        input.addEventListener('input', (e) => runSearch(e.target.value.trim().toLowerCase()));

        list.addEventListener('click', (e) => {
            const item = e.target.closest('.pcc-command-item');
            if (!item) return;
            e.preventDefault();
            const propertyId = parseInt(item.dataset.propertyId, 10);
            closeCommandPalette();
            if (propertyId) {
                selectProperty(propertyId);
            } else if (item.dataset.url) {
                window.location.href = item.dataset.url;
            }
        });
    }

    function openCommandPalette() {
        if (!els.palette) return;
        els.palette.classList.add('open');
        els.palette.removeAttribute('hidden');
        const input = document.getElementById('pccCommandInput');
        if (input) { input.value = ''; input.focus(); }
        renderCommandResults(window.pccCommandItems || []);
    }

    function closeCommandPalette() {
        els.palette?.classList.remove('open');
        els.palette?.setAttribute('hidden', '');
    }

    function bindResizer() {
        const resizer = document.getElementById('pccResizer');
        const left = document.getElementById('pccLeftPane');
        if (!resizer || !left) return;

        let startX, startWidth;
        resizer.addEventListener('mousedown', (e) => {
            startX = e.clientX;
            startWidth = left.offsetWidth;
            resizer.classList.add('active');
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
        });

        function onMouseMove(e) {
            const dx = e.clientX - startX;
            const next = Math.max(320, Math.min(startWidth + dx, window.innerWidth - 400));
            left.style.width = next + 'px';
            left.style.flexBasis = next + 'px';
        }

        function onMouseUp() {
            resizer.classList.remove('active');
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
        }

        window.addEventListener('resize', debounce(() => {
            if (window.innerWidth < 992) {
                left.style.width = '';
                left.style.flexBasis = '';
            }
        }, 150));
    }

    function bindMobileBack() {
        document.getElementById('pccBackBtn')?.addEventListener('click', () => {
            state.propertyId = null;
            hideDetailShell();
            window.history.pushState(null, '', cfg.ajaxUrl);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
