<div class="crm-command" id="crmCommand" hidden>
    <div class="crm-command-panel" role="dialog" aria-label="Search">
        <input type="search" id="crmCommandInput" placeholder="Search properties, tenants, owners, contractors, repairs..." autocomplete="off">
        <div class="crm-command-results" id="crmCommandResults"></div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('crmCommand');
    var input = document.getElementById('crmCommandInput');
    var results = document.getElementById('crmCommandResults');
    var openBtn = document.getElementById('crmCommandOpen');
    if (!root || !input || !results) return;

    var actions = [
        { label: 'Property', meta: 'Add New', url: @json(property_create_url()) },
        { label: 'Tenancy', meta: 'Add New', url: @json(route('admin.tenancies.create')) },
        { label: 'Repair', meta: 'Add New', url: @json(route('admin.property_repairs.create')) },
        { label: 'Event / Viewing', meta: 'Add New', url: @json(route('backend.events.calendar')) },
        { label: 'Contact', meta: 'Add New', url: @json(route('admin.people.index')) },
        { label: 'Document', meta: 'Add New', url: @json(route('admin.documents.index')) }
    ];

    function paint(items) {
        results.innerHTML = '';
        items.forEach(function (item) {
            var link = document.createElement('a');
            link.href = item.url;
            link.className = 'crm-command-item';
            link.innerHTML = '<strong></strong><span></span>';
            link.querySelector('strong').textContent = item.label;
            link.querySelector('span').textContent = item.meta || '';
            results.appendChild(link);
        });
        if (!items.length) {
            var empty = document.createElement('div');
            empty.className = 'crm-command-empty';
            empty.innerHTML = '<strong>No Results Found</strong><p>Try a different search or clear your filters.</p>';
            results.appendChild(empty);
        }
    }

    function open() {
        root.hidden = false;
        paint(actions);
        input.value = '';
        input.focus();
    }
    function close() { root.hidden = true; }

    openBtn && openBtn.addEventListener('click', open);
    root.addEventListener('click', function (event) {
        if (event.target === root) close();
    });
    document.addEventListener('keydown', function (event) {
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            root.hidden ? open() : close();
        }
        if (event.key === 'Escape') close();
    });

    var timer;
    input.addEventListener('input', function () {
        var term = input.value.trim();
        clearTimeout(timer);
        if (term.length < 2) {
            paint(actions.filter(function (item) {
                return item.label.toLowerCase().indexOf(term.toLowerCase()) !== -1;
            }));
            return;
        }
        timer = setTimeout(function () {
            fetch(@json(route('backend.command-search')) + '?q=' + encodeURIComponent(term), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) { return response.json(); }).then(function (payload) {
                var found = (payload.results || []).concat(actions.filter(function (item) {
                    return item.label.toLowerCase().indexOf(term.toLowerCase()) !== -1;
                }));
                paint(found);
            }).catch(function () {
                paint([]);
            });
        }, 200);
    });
});
</script>
