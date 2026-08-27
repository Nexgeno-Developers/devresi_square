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
        const statsSlot = document.getElementById('pccDetailStatsSlot');
        const actionsSlot = document.getElementById('pccDetailActionsSlot');
        if (headerSlot && res.detail_header) headerSlot.innerHTML = res.detail_header;
        if (statsSlot && res.detail_stats) statsSlot.innerHTML = res.detail_stats;
        if (actionsSlot && res.detail_actions) actionsSlot.innerHTML = res.detail_actions;
    }

    function showDetailLoading() {
        if (!els.rightPane) return;
        let overlay = els.rightPane.querySelector('.pcc-loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'pcc-loading-overlay';
            overlay.innerHTML = '<div class="spinner-border text-primary"></div>';
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

    function renderTabs() {
        const tabs = state.tabs;
        if (!tabs.length || !els.tabNav) return;

        const primary = ['property', 'tenancy', 'documents', 'notes', 'compliance', 'statement'];
        const primaryTabs = tabs.filter((t) => primary.includes(String(t.name).toLowerCase()));
        const moreTabs = tabs.filter((t) => !primary.includes(String(t.name).toLowerCase()));

        let navHtml = primaryTabs.map((t) =>
            `<a href="#" class="pcc-tab-link ${String(t.name).toLowerCase() === state.tabName ? 'active' : ''}"
               data-tab="${String(t.name).toLowerCase()}" role="button">${t.name}</a>`
        ).join('');

        if (moreTabs.length) {
            navHtml += `
                <div class="dropdown">
                    <button class="pcc-tab-more-btn" data-bs-toggle="dropdown" type="button">
                        More <i class="bi bi-chevron-down"></i>
                    </button>
                    <ul class="dropdown-menu">
                        ${moreTabs.map((t) =>
                            `<li><a href="#" class="dropdown-item pcc-tab-link ${String(t.name).toLowerCase() === state.tabName ? 'active' : ''}"
                                      data-tab="${String(t.name).toLowerCase()}" role="button">${t.name}</a></li>`
                        ).join('')}
                    </ul>
                </div>`;
        }

        els.tabNav.innerHTML = navHtml;

        const cached = state.tabCache.get(state.tabName);
        if (els.tabContent) {
            els.tabContent.innerHTML = cached
                ? cached.html
                : '<div class="pcc-tab-loading"><div class="spinner-border text-primary"></div></div>';
        }

        if (!cached && state.propertyId) {
            fetchTab(state.tabName);
        } else {
            refreshTabComponents();
        }
    }

    function switchTab(tabName) {
        state.tabName = tabName;
        document.querySelectorAll('.pcc-tab-link').forEach((link) =>
            link.classList.toggle('active', link.dataset.tab === tabName));

        const cached = state.tabCache.get(tabName);
        if (els.tabContent) {
            els.tabContent.innerHTML = cached
                ? cached.html
                : '<div class="pcc-tab-loading"><div class="spinner-border text-primary"></div></div>';
        }

        if (!cached && state.propertyId) {
            fetchTab(tabName);
        } else {
            refreshTabComponents();
        }

        const url = new URL(window.location);
        url.searchParams.set('tabname', tabName);
        if (state.propertyId) url.searchParams.set('property_id', state.propertyId);
        window.history.replaceState(null, '', url);
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
            els.tabContent.innerHTML = '<div class="pcc-tab-loading"><div class="spinner-border text-primary"></div></div>';
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
        const link = e.target.closest('.pcc-tab-link');
        if (!link || !els.root?.contains(link)) return;
        e.preventDefault();
        const tabName = link.dataset.tab;
        if (tabName) switchTab(tabName);
    });

    function collectFilterParams() {
        const params = new URLSearchParams();
        if (!els.filterRail) return params;
        els.filterRail.querySelectorAll('input, select').forEach((el) => {
            if (!el.name) return;
            if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
            if ((el.type === 'text' || el.type === 'search') && !el.value.trim()) return;
            if (el.type === 'checkbox' && params.has(el.name)) {
                params.set(el.name, params.get(el.name) + ',' + el.value);
            } else {
                params.set(el.name, el.value);
            }
        });
        return params;
    }

    function bindFilters() {
        if (!els.filterRail) return;

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
                    updateActiveFilterChips(params);
                    const historyUrl = cfg.ajaxUrl + (params.toString() ? '?' + params.toString() : '');
                    window.history.replaceState(null, '', historyUrl);
                },
                complete: () => {
                    if (els.listScroll) els.listScroll.style.opacity = '1';
                },
            });
        }, 250);

        els.filterRail.addEventListener('change', applyFilters);
        els.filterRail.addEventListener('input', (e) => {
            if (e.target.name === 'search') applyFilters();
        });

        els.filterRail.querySelectorAll('.pcc-filter-section-toggle').forEach((btn) => {
            btn.addEventListener('click', () => {
                const body = btn.nextElementSibling;
                const isOpen = body.classList.toggle('open');
                btn.classList.toggle('open', isOpen);
            });
        });

        const drawerBtn = document.getElementById('pccFilterDrawerBtn');
        if (drawerBtn) {
            drawerBtn.addEventListener('click', () => {
                const open = els.filterRail.classList.toggle('is-open');
                drawerBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        }

        const resetBtn = document.getElementById('pccFilterReset');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                window.location.href = cfg.ajaxUrl;
            });
        }

        els.filterRail.addEventListener('click', (e) => {
            const chipRemove = e.target.closest('.pcc-filter-chip-remove');
            if (!chipRemove) return;
            const key = chipRemove.dataset.key;
            els.filterRail.querySelectorAll(`[name="${key}"]`).forEach((input) => {
                if (input.type === 'checkbox' || input.type === 'radio') input.checked = false;
                else input.value = '';
            });
            chipRemove.closest('.pcc-filter-chip')?.remove();
            applyFilters();
        });
    }

    function updateActiveFilterChips(params) {
        let container = document.getElementById('pccActiveFilters');
        if (!container && els.filterRail) {
            container = document.createElement('div');
            container.id = 'pccActiveFilters';
            container.className = 'pcc-active-filters';
            els.filterRail.appendChild(container);
        }
        if (!container) return;
        const chips = [];
        params.forEach((val, key) => {
            if (val) chips.push({ key, val: String(val) });
        });
        if (!chips.length) { container.innerHTML = ''; return; }
        container.innerHTML = '<span class="pcc-active-filters-label">Active:</span>' +
            chips.map((c) =>
                `<span class="pcc-filter-chip" data-key="${c.key}">${c.val}
                    <button class="pcc-filter-chip-remove" data-key="${c.key}" type="button">&times;</button>
                </span>`
            ).join('');
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
