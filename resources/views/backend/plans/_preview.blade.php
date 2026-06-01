<div class="card sticky-top" style="top: 80px;">
    <div class="card-header">
        <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Live Preview</h6>
        <small class="text-muted">Updates as you type</small>
    </div>
    <div class="card-body p-0">
        <div class="p-3 text-center">
            <div id="previewBadge" class="card-header bg-primary text-white text-center fw-semibold py-2 d-none"
                 style="margin:-1px -1px 0;border-radius:0;"></div>
            <div class="p-3">
                <h5 class="fw-bold mb-1" id="previewName">Plan Name</h5>
                <p class="text-muted small mb-3" id="previewDesc"></p>
                <div class="mb-3">
                    <span class="fs-2 fw-bold" id="previewMonthlyPrice">£0</span>
                    <span class="text-muted">/month</span>
                    <div class="text-muted small" id="previewYearly"></div>
                    <div class="text-success small mt-1" id="previewSaving"></div>
                </div>
                <ul class="list-unstyled text-start mb-3">
                    <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i><span id="previewProps">Unlimited Properties</span></li>
                    <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i><span id="previewStaff">Unlimited Staff</span></li>
                    <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i><span id="previewTenancies">Unlimited Tenancies</span></li>
                </ul>
                <ul class="list-unstyled text-start mb-0" id="previewFeatures"></ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function val(id) { return (document.getElementById(id) || {}).value || ''; }
    function el(id)  { return document.getElementById(id); }

    function update() {
        const name    = val('planName')    || 'Plan Name';
        const desc    = val('description') || '';
        const monthly = parseFloat(val('priceMonthly')) || 0;
        const yearly  = parseFloat(val('priceYearly'))  || 0;
        const props   = val('max_properties') ? val('max_properties') + ' Properties' : 'Unlimited Properties';
        const staff   = val('max_staff')      ? val('max_staff')      + ' Staff'      : 'Unlimited Staff';
        const ten     = val('max_tenancies')  ? val('max_tenancies')  + ' Tenancies'  : 'Unlimited Tenancies';
        const badge   = val('badge_label');
        const feats   = Array.from(document.querySelectorAll('input[name="features[]"]'))
                            .map(i => i.value.trim()).filter(v => v);

        el('previewName').textContent         = name;
        el('previewDesc').textContent         = desc;
        el('previewMonthlyPrice').textContent = '£' + monthly.toFixed(2);
        el('previewYearly').textContent       = yearly > 0 ? 'or £' + yearly.toFixed(2) + '/year' : '';
        el('previewProps').textContent        = props;
        el('previewStaff').textContent        = staff;
        el('previewTenancies').textContent    = ten;

        if (monthly > 0 && yearly > 0) {
            const saving = Math.round((1 - (yearly / (monthly * 12))) * 100);
            el('previewSaving').textContent = saving > 0 ? 'Save ' + saving + '% with yearly' : '';
        } else {
            el('previewSaving').textContent = '';
        }

        const badgeEl = el('previewBadge');
        if (badge) { badgeEl.textContent = badge; badgeEl.classList.remove('d-none'); }
        else        { badgeEl.classList.add('d-none'); }

        el('previewFeatures').innerHTML = feats.map(f =>
            '<li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i>' + f + '</li>'
        ).join('');
    }

    ['planName','description','priceMonthly','priceYearly',
     'max_properties','max_staff','max_tenancies','badge_label']
    .forEach(function(id) {
        const e = document.getElementById(id) || document.querySelector('[name="' + id + '"]');
        if (e) e.addEventListener('input', update);
    });

    document.addEventListener('input', function(e) {
        if (e.target.matches('input[name="features[]"]')) update();
    });

    update();
});
</script>
@endpush
