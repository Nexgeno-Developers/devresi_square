(function () {
    function overlay(id) {
        return document.getElementById(id);
    }

    function openOverlay(id) {
        var el = overlay(id);
        if (!el) return;
        document.body.appendChild(el);
        document.querySelectorAll('[data-lab-overlay]:not([hidden])').forEach(function (open) {
            if (open !== el) open.hidden = true;
        });
        el.hidden = false;
        document.body.classList.add('lab-lock');
        var search = el.querySelector('[data-lab-search]');
        if (search) {
            search.focus();
            return;
        }
        var closeBtn = el.querySelector('[data-lab-close]');
        if (closeBtn) closeBtn.focus();
    }

    function closeOverlay(el) {
        var root = el && el.closest ? el.closest('[data-lab-overlay]') : el;
        if (root) root.hidden = true;
        if (!document.querySelector('[data-lab-overlay]:not([hidden])')) {
            document.body.classList.remove('lab-lock');
        }
    }

    function showPanel(home, panel) {
        var card = document.querySelector('[data-home="' + home + '"]');
        if (!card) return;
        card.classList.add('is-open');
        card.querySelectorAll('.pf-tabs button').forEach(function (btn) {
            btn.classList.toggle('is-on', btn.getAttribute('data-lab-tab') === panel);
        });
        card.querySelectorAll('.pf-panel').forEach(function (pane) {
            var on = pane.getAttribute('data-panel') === panel;
            pane.classList.toggle('is-on', on);
            pane.hidden = !on;
        });
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function pwsRoot() {
        return document.querySelector('[data-pws]');
    }

    function selectHome(id) {
        var root = pwsRoot();
        if (!root || !id) return;
        root.querySelectorAll('[data-pws-home]').forEach(function (row) {
            row.classList.toggle('is-on', row.getAttribute('data-pws-home') === id);
        });
        root.querySelectorAll('[data-pws-detail]').forEach(function (pane) {
            var on = pane.getAttribute('data-pws-detail') === id;
            pane.classList.toggle('is-on', on);
            pane.hidden = !on;
        });
        selectTab(id, 'overview');
    }

    function selectTab(homeId, tab) {
        var root = pwsRoot();
        if (!root) return;
        var detail = homeId
            ? root.querySelector('[data-pws-detail="' + homeId + '"]')
            : root.querySelector('.pws-detail.is-on');
        if (!detail) return;
        detail.querySelectorAll('.pws-tabs [data-pws-tab]').forEach(function (btn) {
            btn.classList.toggle('is-on', btn.getAttribute('data-pws-tab') === tab);
        });
        detail.querySelectorAll('[data-pws-panel]').forEach(function (pane) {
            var on = pane.getAttribute('data-pws-panel') === tab;
            pane.classList.toggle('is-on', on);
            pane.hidden = !on;
        });
    }

    function currentHomeId() {
        var on = document.querySelector('[data-pws-home].is-on');
        return on ? on.getAttribute('data-pws-home') : null;
    }

    function applyHomeFilter() {
        var root = pwsRoot();
        if (!root) return;
        var chip = root.querySelector('[data-pws-filter].is-on');
        var filter = chip ? chip.getAttribute('data-pws-filter') : 'all';
        var q = (root.querySelector('[data-pws-search]') || {}).value || '';
        q = q.toLowerCase().trim();
        var firstVisible = null;
        root.querySelectorAll('[data-pws-home]').forEach(function (row) {
            var occupancy = row.getAttribute('data-occupancy');
            var repairs = Number(row.getAttribute('data-repairs') || 0);
            var certsDue = row.getAttribute('data-certs-due') === '1';
            var show = filter === 'all'
                || occupancy === filter
                || (filter === 'repairs' && repairs > 0)
                || (filter === 'certs' && certsDue);
            if (q && (row.getAttribute('data-search') || '').indexOf(q) === -1) {
                show = false;
            }
            row.hidden = !show;
            if (show && !firstVisible) firstVisible = row;
        });
        var selected = root.querySelector('[data-pws-home].is-on');
        if (!selected || selected.hidden) {
            if (firstVisible) selectHome(firstVisible.getAttribute('data-pws-home'));
        }
        var homeId = currentHomeId();
        if (!homeId) return;
        if (filter === 'Let' || filter === 'lets') selectTab(homeId, 'tenancy');
        if (filter === 'certs') selectTab(homeId, 'certificates');
        if (filter === 'repairs') selectTab(homeId, 'overview');
    }

    document.addEventListener('click', function (event) {
        var openBtn = event.target.closest('[data-lab-open]');
        if (openBtn) {
            event.preventDefault();
            openOverlay(openBtn.getAttribute('data-lab-open'));
            return;
        }

        if (event.target.hasAttribute('data-lab-overlay')) {
            closeOverlay(event.target);
            return;
        }

        if (event.target.closest('[data-lab-close]')) {
            closeOverlay(event.target);
            return;
        }

        var chip = event.target.closest('.chips .chip');
        if (chip) {
            chip.parentElement.querySelectorAll('.chip').forEach(function (el) {
                el.classList.toggle('is-on', el === chip);
            });
            if (chip.hasAttribute('data-pws-filter')) applyHomeFilter();
            if (chip.hasAttribute('data-llphone-filter')) applyLlphoneFilter();
            return;
        }

        var llHome = event.target.closest('[data-llphone-home]');
        if (llHome) {
            event.preventDefault();
            showLlView(llHome.getAttribute('data-llphone-home'));
            return;
        }

        if (event.target.closest('[data-llphone-back], [data-llphone-nav="homes"]')) {
            event.preventDefault();
            showLlView('list');
            return;
        }

        var llTab = event.target.closest('[data-llphone-tab]');
        if (llTab) {
            event.preventDefault();
            var llView = llTab.closest('[data-llphone-view]');
            if (llView) {
                selectLlTab(llView.getAttribute('data-llphone-view'), llTab.getAttribute('data-llphone-tab'));
            }
            return;
        }

        var homeRow = event.target.closest('[data-pws-home]');
        if (homeRow) {
            event.preventDefault();
            selectHome(homeRow.getAttribute('data-pws-home'));
            return;
        }

        var pwsTab = event.target.closest('[data-pws-tab]');
        if (pwsTab) {
            event.preventDefault();
            selectTab(currentHomeId(), pwsTab.getAttribute('data-pws-tab'));
            return;
        }

        var need = event.target.closest('[data-lab-home][data-lab-panel]');
        if (need) {
            showPanel(need.getAttribute('data-lab-home'), need.getAttribute('data-lab-panel'));
            return;
        }

        var tab = event.target.closest('[data-lab-tab]');
        if (tab) {
            showPanel(tab.getAttribute('data-home'), tab.getAttribute('data-lab-tab'));
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('[data-lab-overlay]:not([hidden])').forEach(function (el) {
            el.hidden = true;
        });
    });

    document.addEventListener('submit', function (event) {
        var pay = event.target.closest('[data-lab-pay]');
        if (pay) {
            event.preventDefault();
            var row = pay.closest('.pf-panel').querySelector('[data-invoice]');
            if (row) {
                var status = row.querySelector('[data-invoice-status]');
                if (status) {
                    status.textContent = 'Paid';
                    status.className = 'lab-pill lab-pill-ok';
                }
            }
            pay.innerHTML = '<p class="pf-done">Recorded. September rent is marked paid on this tenancy.</p>';
            return;
        }
        var repair = event.target.closest('[data-lab-repair]');
        if (repair) {
            event.preventDefault();
            repair.innerHTML = '<p class="pf-done">Repair updated. Tina will see this on her portal.</p>';
            return;
        }
        var letHome = event.target.closest('[data-lab-let]');
        if (letHome) {
            event.preventDefault();
            letHome.innerHTML = '<p class="pf-done">Tenancy saved. Invite them to the portal from People on this home.</p>';
        }
    });

    function llRoot() {
        return document.querySelector('[data-llphone]');
    }

    function showLlView(id) {
        var root = llRoot();
        if (!root || !id) return;
        root.querySelectorAll('[data-llphone-view]').forEach(function (view) {
            var on = view.getAttribute('data-llphone-view') === id;
            view.classList.toggle('is-on', on);
            view.hidden = !on;
        });
        var body = root.querySelector('.phone-body');
        if (body) body.scrollTop = 0;
        if (id !== 'list') selectLlTab(id, 'overview');
    }

    function selectLlTab(homeId, tab) {
        var root = llRoot();
        if (!root) return;
        var view = root.querySelector('[data-llphone-view="' + homeId + '"]');
        if (!view) return;
        view.querySelectorAll('.llphone-seg [data-llphone-tab]').forEach(function (btn) {
            btn.classList.toggle('is-on', btn.getAttribute('data-llphone-tab') === tab);
        });
        view.querySelectorAll('[data-llphone-panel]').forEach(function (pane) {
            var on = pane.getAttribute('data-llphone-panel') === tab;
            pane.classList.toggle('is-on', on);
            pane.hidden = !on;
        });
        var body = root.querySelector('.phone-body');
        if (body) body.scrollTop = 0;
    }

    function applyLlphoneFilter() {
        var root = llRoot();
        if (!root) return;
        var chip = root.querySelector('[data-llphone-filter].is-on');
        var filter = chip ? chip.getAttribute('data-llphone-filter') : 'all';
        var q = (root.querySelector('[data-llphone-search]') || {}).value || '';
        q = q.toLowerCase().trim();
        root.querySelectorAll('[data-llphone-home]').forEach(function (row) {
            var occupancy = row.getAttribute('data-occupancy');
            var repairs = Number(row.getAttribute('data-repairs') || 0);
            var certsDue = row.getAttribute('data-certs-due') === '1';
            var show = filter === 'all'
                || occupancy === filter
                || (filter === 'lets' && occupancy === 'Let')
                || (filter === 'repairs' && repairs > 0)
                || (filter === 'certs' && certsDue);
            if (q && (row.getAttribute('data-search') || '').indexOf(q) === -1) {
                show = false;
            }
            row.hidden = !show;
        });
    }

    document.addEventListener('input', function (event) {
        if (event.target.matches('[data-pws-search]')) applyHomeFilter();
        if (event.target.matches('[data-llphone-search]')) applyLlphoneFilter();
        if (event.target.matches('[data-lab-search]')) {
            var q = event.target.value.toLowerCase().trim();
            var root = event.target.closest('[data-lab-overlay]');
            if (!root) return;
            root.querySelectorAll('[data-lab-search-row]').forEach(function (row) {
                var hay = (row.getAttribute('data-search') || '').toLowerCase();
                row.hidden = !!(q && hay.indexOf(q) === -1);
            });
        }
    });

    document.addEventListener('click', function (event) {
        var collapse = event.target.closest('[data-shell-collapse]');
        if (!collapse) return;
        var shell = collapse.closest('.shell');
        if (!shell) return;
        shell.classList.toggle('is-collapsed');
        collapse.querySelector('span').textContent = shell.classList.contains('is-collapsed') ? 'Expand' : 'Collapse menu';
    });
})();
